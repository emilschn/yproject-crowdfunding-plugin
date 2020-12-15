<?php
class WDGFormUsers {
	
	public static function login_facebook() {
		$do_fb_login = FALSE;
		$fbcallback = filter_input( INPUT_GET, 'fbcallback' );
		if ( !empty( $fbcallback ) ) {
			try {
				$fb = new Facebook\Facebook([
					'app_id' => YP_FB_APP_ID,
					'app_secret' => YP_FB_SECRET,
					'default_graph_version' => 'v2.8',
				]);

				$helper = $fb->getRedirectLoginHelper();
				$accessToken = $helper->getAccessToken();
				
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
				// When Graph returns an error
				ypcf_debug_log( 'Graph returned an error: ' . $e->getMessage() );

			} catch(Facebook\Exceptions\FacebookSDKException $e) {
				// When validation fails or other local issues
				ypcf_debug_log( 'Facebook SDK returned an error: ' . $e->getMessage() );
			}

			if ( !isset( $accessToken ) ) {
				if ( $helper->getError() ) {
					header('HTTP/1.0 401 Unauthorized');
					ypcf_debug_log( "Error: " . $helper->getError() . "\n"
					."Error Code: " . $helper->getErrorCode() . "\n"
					."Error Reason: " . $helper->getErrorReason() . "\n"
					."Error Description: " . $helper->getErrorDescription() . "\n" );
				} else {
					//header('HTTP/1.0 400 Bad Request');
					ypcf_debug_log( 'Bad request' );
				}
			}

			
			try {
				// The OAuth 2.0 client handler helps us manage access tokens
				$oAuth2Client = $fb->getOAuth2Client();

				// Get the access token metadata from /debug_token
				$tokenMetadata = $oAuth2Client->debugToken($accessToken);
				$fbUserId = $tokenMetadata->getField("user_id");
				$sc_provider_identity_key = 'social_connect_facebook_id';
				global $wpdb;
				$sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s' AND meta_value = '%s'";
				$user_id = $wpdb->get_var( $wpdb->prepare( $sql, $sc_provider_identity_key, $fbUserId ) );
				
			} catch ( Exception $e ) {
				ypcf_debug_log( 'getOAuth2Client returned an error: ' . $e->getMessage() );
			}


			// On a trouvé l'utilisateur correspondant
			if ( $user_id ) {
				$user_id += 0; // Transformation en entier

			} else {
				// On va chercher les infos de l'utilisateur en cours
				try {
					$response = $fb->get('/me?fields=id,email,first_name,last_name,link', $accessToken);
					$fb_user = $response->getGraphUser();

					$user_email = $fb_user['email'];
					$user_first_name = $fb_user['first_name'];
					$user_last_name = $fb_user['last_name'];
					$user_profile_url = $fb_user['link'];
					$user_login = strtolower( str_replace( ' ', '', $user_first_name . $user_last_name ) );

					$user_id = email_exists( $user_email );

					// On n'a pas trouvé l'utilisateur avec son id fb, mais il existe avec son mail
					if ( $user_id ) {
						update_user_meta( $user_id, $sc_provider_identity_key, $fbUserId );
						$user_data  = get_userdata( $user_id );
						$user_login = $user_data->user_login;

					// On crée l'utilisateur avec les infos recues depuis fb
					} else {
						$index = 0;
						$user_login_base = $user_login;
						while ( username_exists( $user_login ) ) {
							$index++;
							$user_login = $user_login_base . '-' . $index;
						}

						$userdata = array(
							'user_login'	=> $user_login,
							'user_email'	=> $user_email,
							'first_name'	=> $user_first_name,
							'last_name'		=> $user_last_name,
							'user_url'		=> substr( $user_profile_url, 0, 99 ),
							'user_pass'		=> wp_generate_password()
						);

						// Create a new user
						$user_id = wp_insert_user( $userdata );

						if ( $user_id && is_integer( $user_id ) ) {
							NotificationsAPI::user_registration( $user_email, $user_first_name );
							WDGQueue::add_notification_registered_without_investment( $user_id );
							update_user_meta( $user_id, $sc_provider_identity_key, $fbUserId );
						} else {
							ypcf_debug_log( 'WDGFormUsers::login_facebook ' . print_r($user_id, true) );
						}
					}


				} catch(Facebook\Exceptions\FacebookResponseException $e) {
					ypcf_debug_log( 'Graph returned an error: ' . $e->getMessage() );
				} catch(Facebook\Exceptions\FacebookSDKException $e) {
					ypcf_debug_log( 'Facebook SDK returned an error: ' . $e->getMessage() );
				}
			}

			if ( $user_id && is_integer( $user_id ) ) {
				wp_set_auth_cookie( $user_id, true, is_ssl() );
				$do_fb_login = TRUE;
			}
		}
		return $do_fb_login;
	}
    
	/**
	 * Tente de se connecter au site
	 * @return boolean
	 */
	public static function login() {
		//Pas la peine de tenter un login si l'utilisateur est déjà connecté
		if (is_user_logged_in()) { return FALSE; }
		//Pas la peine de tenter un login si on ne l'a pas demandé
		$posted_login_form = filter_input(INPUT_POST, 'login-form');
		if (empty($posted_login_form)) { return FALSE; }
		
		remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
		add_filter('authenticate', 'WDGFormUsers::filter_login_email', 20, 3);
		add_action('wp_login', 'WDGFormUsers::redirect_after_login');
		add_action('wp_login_failed', 'WDGFormUsers::redirect_after_login_failed'); 
		global $signon_errors;
		$signon_result = wp_signon('', is_ssl());
		if (is_wp_error($signon_result) && !isset($signon_errors)) {
			$signon_errors = $signon_result;
		}
	}
	
	/**
	 * permet d'autoriser l'identification par email
	 * @param type $user
	 * @param type $username
	 * @param type $password
	 * @return type
	 */
	public static function filter_login_email( $user, $username, $password ) {
		if ( is_a( $user, 'WP_User' ) ) {
			if ( !WDGOrganization::is_user_organization( $user->ID ) ) {
				return $user;
			}
		}
		
		// Vérifie que des champs ont bien été remplis
		if (empty($username) || empty($password)) {
			global $signon_errors;
			$signon_errors = new WP_Error();
			$signon_errors->add('empty_authentication', __( 'login.ERROR_EMPTY_FIELD', 'yproject' ));
			WDGFormUsers::redirect_after_login_failed( 'empty_fields' );
		}

		$username = rtrim( $username );
		if ( !empty( $username ) ) {
			// Récupération éventuelle d'un utilisateur en fonction de l'e-mail
			$username = str_replace( '&', '&amp;', stripslashes( $username ) );
			$user = get_user_by( 'email', $username );
			if ( isset( $user, $user->user_login, $user->user_status ) && 0 == (int) $user->user_status ) {
				$username = $user->user_login;
			}
		}
		
		if ( !empty( $username ) ) {
			$user_by_login = get_user_by( 'login', $username );
			if ( WDGOrganization::is_user_organization( $user_by_login->ID ) ) {
				global $signon_errors;
				$signon_errors = new WP_Error();
				$signon_errors->add('empty_authentication', __('login.ERROR_ORGANIZATION_ACCOUNT', 'yproject'));
				WDGFormUsers::redirect_after_login_failed( 'orga_account' );
			}
		}

		return wp_authenticate_username_password( null, $username, $password );
	}
	
	/**
	 * Détecte et gère l'affichage des erreurs de login
	 * @global type $signon_errors
	 * @return type
	 */
	public static function display_login_errors() {
		global $signon_errors;
		$buffer = '';
		if (is_wp_error($signon_errors)) {
			switch ($signon_errors->get_error_code()) {
				case 'empty_authentication':
				case 'empty_username':
				case 'empty_password':
					$buffer = __('login.ERROR_EMPTY_FIELD', 'yproject');
					break;
				case 'invalid_username':
				case 'incorrect_password':
					$buffer = __('login.ERROR_USER_NOT_FOUND', 'yproject');
					break;
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne si il y a eu des erreurs pendant le login
	 * @global type $signon_errors
	 * @return type
	 */
	public static function has_login_errors() {
		global $signon_errors; return is_wp_error($signon_errors);
	}
	
	/**
	 * Redirige après la connexion
	 */
	public static function redirect_after_login() {
		//Récupération de la page de redirection à appliquer
		$posted_redirect_page = filter_input(INPUT_POST, 'redirect-page');
		//Si ce n'est pas défini, on retourne à l'accueil
		if (empty($posted_redirect_page)) { wp_safe_redirect(home_url()); }
		
		//Vérification si l'url ne contient pas de liens vers l'admin
		if (strpos($posted_redirect_page, 'wp-admin') !== FALSE) {
			wp_safe_redirect(home_url());
		} else {
			wp_safe_redirect($posted_redirect_page);
		}
		
		exit();
	}
	
	/**
	 * Redirige après une connexion échouée
	 */
	public static function redirect_after_login_failed( $reason ) {
		$posted_redirect_error_page = filter_input(INPUT_POST, 'redirect-error');
		if (!empty($posted_redirect_error_page)) {
			wp_safe_redirect($posted_redirect_error_page);
		} else {
			$url_reason = '';
			if ( !empty( $reason ) ) {
				$url_reason = '?error_reason=' .$reason;
			}
			ypcf_debug_log( 'WDGFormUsers::redirect_after_login_failed' );
			wp_redirect( home_url( '/connexion/' ) . $url_reason );
		}
		exit();
	}
	
	public static function register( $user_email = null, $user_firstname = null, $user_lastname = null, $password = null, $password_confirm = null, $validate_terms_check = null ) {
		if ( is_user_logged_in() ) { return FALSE; }
			
		global $signup_errors, $signup_step;
		$signup_errors = new WP_Error();
		$signup_step = 'request-details';
		
		$register_form_posted = filter_input(INPUT_POST, 'signup_submit');
		if ( empty( $register_form_posted ) && is_null( $user_email ) ) { return FALSE; }

		$need_post_data = is_null( $user_email );
		
		// Vérifications de l'e-mail
		if ( is_null( $user_email ) ) {
			$user_email = rtrim( filter_input( INPUT_POST, 'signup_email' ) );
		}
		$user_name = $user_email;
		if ( empty( $user_email ) ) {
			$signup_errors->add( 'user_email', __( 'signup.ERROR_EMAIL_EMPTY', 'yproject' ) );
		}
		if ( email_exists( $user_email ) || username_exists( $user_email ) ) {
			$signup_errors->add( 'user_name', __( 'signup.ERROR_EMAIL_ALREADY_USED', 'yproject' ) );
		}
		if ( !is_email( $user_email ) ) {
			$signup_errors->add( 'user_email', __( 'signup.ERROR_EMAIL_NOT_OK', 'yproject' ) );
		}

		// Vérifications sur prénom et nom
		if ( is_null( $user_firstname ) ) {
			$user_firstname = filter_input( INPUT_POST, 'signup_firstname' );
		}
		$user_firstname = mb_convert_case( $user_firstname , MB_CASE_TITLE );
		if ( empty( $user_firstname ) ) {
			$signup_errors->add( 'user_firstname', __( 'signup.ERROR_FIRST_NAME_EMPTY', 'yproject' ) );
		}
		if ( is_null( $user_lastname ) ) {
			$user_lastname = filter_input( INPUT_POST, 'signup_lastname' );
		}
		$user_lastname = mb_convert_case( $user_lastname , MB_CASE_TITLE );
		if ( empty( $user_lastname ) ) {
			$signup_errors->add( 'user_lastname', __( 'signup.ERROR_LAST_NAME_EMPTY', 'yproject' ) );
		}

		// Vérifications concernant le mot de passe
		if ( is_null( $password ) ) {
			$password = filter_input(INPUT_POST, 'signup_password');
		}
		if ( is_null( $password_confirm ) ) {
			$password_confirm = filter_input(INPUT_POST, 'signup_password_confirm');
		}
		if ( empty( $password ) || empty( $password_confirm ) ) {
			$signup_errors->add( 'user_password', __( 'signup.ERROR_PASSWORD_EMPTY', 'yproject' ) );
		}
		if ( !empty( $password ) && !empty( $password_confirm ) && $password != $password_confirm ) {
			$signup_errors->add( 'user_password', __( 'signup.ERROR_PASSWORDS_DONT_MATCH', 'yproject' ) );
		}

		// Vérifications CGU
		if ( is_null( $validate_terms_check ) ) {
			$validate_terms_check = filter_input(INPUT_POST, 'validate-terms-check');
		}
		if ( empty( $validate_terms_check ) ) {
			$signup_errors->add( 'validate_terms_check', __( 'signup.ERROR_TERMS_NOT_CHECKED', 'yproject' ) );
		}
		
		
		// Si le formulaire d'inscription est rempli
		if ( !$need_post_data || ( wp_verify_nonce( $_POST['_wpnonce'], 'register_form_posted' ) && WDGFormUsers::check_recaptcha($_POST['g-recaptcha-response']) ) ) {

			$signup_error_message = $signup_errors->get_error_message();
			if ( empty( $signup_error_message ) ) {

				$display_name = $user_firstname. ' ' .substr( $user_lastname, 0, 1 ). '.';
				$wp_user_id = wp_insert_user( array(
					'user_login'	=> $user_name,
					'user_pass'		=> $password,
					'user_email'	=> $user_email,
					'first_name'	=> $user_firstname,
					'last_name'		=> $user_lastname,
					'display_name'	=> $display_name,
					'user_nicename' => sanitize_title( $display_name )
				) );

				if ( is_wp_error( $wp_user_id ) ) {
					$signup_errors->add( 'user_insert', __( 'signup.ERROR_USER_CREATION', 'yproject' ) );
					
				} else {
					global $wpdb, $edd_options;
					$signup_step = 'completed-confirmation';
					$wpdb->update( $wpdb->users, array( sanitize_key( 'user_status' ) => 0 ), array( 'ID' => $wp_user_id ) );
					update_user_meta($wp_user_id, WDGUser::$key_validated_general_terms_version, $edd_options[WDGUser::$edd_general_terms_version]);
					NotificationsAPI::user_registration( $user_email, $user_firstname );
					WDGQueue::add_notification_registered_without_investment( $wp_user_id );
					wp_set_auth_cookie( $wp_user_id, false, is_ssl() );
					if ( $need_post_data ) {
						if ( isset( $_POST[ 'redirect-home' ] ) ) {
							ypcf_debug_log( 'WDGFormUsers::register > redirect home' );
							wp_redirect(home_url());
						} else {
							ypcf_debug_log( 'WDGFormUsers::register > redirect page' );
							wp_redirect( wp_unslash( WDGUser::get_login_redirect_page() ) );
						}
						exit();
					}
				}
			}

		} else {
			$signup_errors->add( 'user_insert', __( 'signup.ERROR_ROBOT_CHECKBOX', 'yproject' ) );
			
		}

		if ( $signup_step == 'completed-confirmation' ) {
			return new WDGUser( $wp_user_id );
		} else {
			return $signup_errors;
		}
	}
	
	public static function check_recaptcha( $code ) {
		if (WP_IS_DEV_SITE){ return TRUE; }

		if (empty($code)) { return false; }
		$params = [
			'secret'    => RECAPTCHA_SECRET,
			'response'  => $code
		];
		$url = "https://www.google.com/recaptcha/api/siteverify?" . http_build_query($params);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curl);

		if (empty($response) || is_null($response)) {
			return false;
		}

		$json = json_decode($response);
		return $json->success;
	}
	
	
	/**
	 * 
	 */
	public static function wallet_to_bankaccount() {
		$action = filter_input( INPUT_POST, 'action' );
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$orga_id = filter_input( INPUT_POST, 'orga_id' );
		if ( ( empty( $user_id ) && empty( $orga_id ) ) || empty( $action ) || $action != 'user_wallet_to_bankaccount') {
			return FALSE;
		}
		$WDGUser_current = WDGUser::current();
		if ( $WDGUser_current->wp_user->ID != $user_id && !$WDGUser_current->is_admin() ) {
			return __( 'account.transfert.TRANSFERT_NOT_AUTHORIZED', 'yproject' );
		}

		$amount_to_bank = filter_input( INPUT_POST, 'amount_to_bank' );
		$amount = FALSE;
		if ( !empty( $amount_to_bank ) ) {
			$amount = WDG_Form::clean_input_number( $amount_to_bank );
		}
		
		$buffer = __( "Votre compte bancaire n'est pas encore valid&eacute;.", 'yproject' );
		if ( !empty( $orga_id ) ) {
			$WDGOrganization = new WDGOrganization( $orga_id );
			if ( $WDGOrganization->has_saved_iban() && $WDGOrganization->get_rois_amount() > 0 ) {
				$buffer = $WDGOrganization->transfer_wallet_to_bankaccount( $WDGOrganization->get_available_rois_amount() );
			}
			
		} else {
			$WDGUser = new WDGUser( $user_id );
			$buffer = $WDGUser->transfer_wallet_to_bankaccount( $amount );
		}
		
		return $buffer;
	}

	
	/**
	 * 
	 */
	public static function change_wire_amount() {
		$action = filter_input( INPUT_POST, 'action' );
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$orga_id = filter_input( INPUT_POST, 'orga_id' );
		$investment_id = filter_input( INPUT_POST, 'investment_id' );
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );

		$buffer = FALSE;
		if ( ( empty( $user_id ) && empty( $orga_id ) ) || empty( $action ) || $action != 'change_wire_value' || empty($investment_id) || empty($campaign_id)) {
			return $buffer;
		}
		$WDGUser_current = WDGUser::current();
		if ( $WDGUser_current->wp_user->ID != $user_id && !$WDGUser_current->is_admin() ) {
			return __( "Ce changement n'est pas autoris&eacute;.", 'yproject' );
		}

		$amount_to_wire = filter_input( INPUT_POST, 'amount_to_wire' );
        if (!empty($amount_to_wire)) {
            $amount = WDG_Form::clean_input_number($amount_to_wire);
            $WDGInvestment = new WDGInvestment($investment_id);
            $WDGInvestment->set_amount($amount);
            $buffer = TRUE;
        } 
				
		return $buffer;
	}

	
	public static function register_rib() {
		$action = filter_input( INPUT_POST, 'action' );
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$orga_id = filter_input( INPUT_POST, 'orga_id' );
		if ( ( empty( $user_id ) && empty( $orga_id ) ) || empty( $action ) || $action != 'register_rib') {
			return FALSE;
		}
		$WDGUser_current = WDGUser::current();
		if ( $WDGUser_current->wp_user->ID != $user_id && !$WDGUser_current->is_admin() ) {
			return FALSE;
		}
		
		if ( !empty( $orga_id ) ) {
			$WDGOrganization = new WDGOrganization( $orga_id );
			$save_iban = filter_input( INPUT_POST, 'iban' );
			if ( isset( $save_iban ) && !empty( $save_iban ) ) {
				$save_holdername = filter_input( INPUT_POST, 'holdername' );
				$save_bic = filter_input( INPUT_POST, 'bic' );
				$save_address = filter_input( INPUT_POST, 'address' );
				$WDGOrganization->set_bank_owner( $save_holdername );
				$WDGOrganization->set_bank_address( $save_address );
				$WDGOrganization->set_bank_iban( $save_iban );
				$WDGOrganization->set_bank_bic( $save_bic );
				$WDGOrganization->save();
			}
			
			if ( isset( $_FILES[ 'rib' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'rib' ][ 'tmp_name' ] ) ) {
				$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_bank, $orga_id, WDGKYCFile::$owner_organization, $_FILES[ 'rib' ] );
				
				if ( is_int( $file_id ) ) {
					$WDGFile = new WDGKYCFile( $file_id );
					$WDGOrganization->register_lemonway();
					if ( $WDGOrganization->can_register_lemonway() ) {
						LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
					}
				}
			}
			
		} else {
			$WDGUser = new WDGUser( $user_id );
			$save_iban = filter_input( INPUT_POST, 'iban' );
			if ( isset( $save_iban ) && !empty( $save_iban ) ) {
				$save_holdername = filter_input( INPUT_POST, 'holdername' );
				$save_bic = filter_input( INPUT_POST, 'bic' );
				$save_address = filter_input( INPUT_POST, 'address' );
				$save_address2 = filter_input( INPUT_POST, 'address2' );
				$WDGUser->save_iban( $save_holdername, $save_iban, $save_bic, $save_address, $save_address2 );
				$WDGUser->update_api();
			}

			if ( isset( $_FILES[ 'rib' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'rib' ][ 'tmp_name' ] ) ) {
				$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_bank, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'rib' ] );
				
				if ( is_int( $file_id ) ) {
					$WDGFile = new WDGKYCFile( $file_id );
					$WDGUser->register_lemonway();
					if ( $WDGUser->can_register_lemonway() ) {
						LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
					}
				}
			}
		}
		
		return TRUE;
	}
}
