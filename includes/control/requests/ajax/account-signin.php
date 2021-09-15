<?php
require_once dirname(__FILE__) . '/account-signin/account-signin-autoload.php';

/**
 * Gestion des appels Ajax liés à l'appli vuejs de connexion / inscription
 */
class WDGAjaxActionsAccountSignin {
	/**
	 * Vérifie si l'identification fonctionne entre une adresse e-mail et un mot de passe
	 */
	public static function account_signin_check_password() {
		$input_email = filter_input( INPUT_POST, 'email-address' );
		// On re-vérifie le type d'adresse en fonction de la saisie
		$result = AccountSigninHelper::get_user_type_by_email_address( $input_email );

		// Si c'est bien un utilisateur existant (et pas lié à Facebook)
		if ( $result[ 'status' ] == 'existing-account' ) {
			$result[ 'signin_status' ] = 'success';
			$result[ 'signin_errors' ] = array();

			// On fait la comparaison pour voir si on arrive à conclure le signin
			$input_password = filter_input( INPUT_POST, 'password' );
			$signin_return = WDGFormUsers::get_signin_return( $input_email, $input_password );

			// Si il y a une erreur, on retourne les codes d'erreur
			if ( is_wp_error( $signin_return ) ) {
				$result[ 'signin_status' ] = 'error';
				$result[ 'signin_errors' ] = $signin_return->get_error_codes();

			// Sinon, c'est ok, on vérifie juste si on se souvient de la personne
			} else {
				$rememberme = filter_input( INPUT_POST, 'rememberme' );
				wp_set_auth_cookie( $signin_return->ID, ( $rememberme === 'true' ), is_ssl() );
			}
		}
		exit( json_encode( $result ) );
	}

	/**
	 * Essaie de créer un compte sur la plateforme à partir d'un e-mail, mot de passe, prénom et nom de famille
	 */
	public static function account_signin_create_account() {
		$input_email = filter_input( INPUT_POST, 'email-address' );
		$input_password = filter_input( INPUT_POST, 'password' );
		$input_firstname = filter_input( INPUT_POST, 'first-name' );
		$input_lastname = filter_input( INPUT_POST, 'last-name' );
		$input_language = filter_input( INPUT_POST, 'language' );

		$result[ 'status' ] = '';
		// Normalement, on ne passe pas ici
		if ( empty( $input_email ) || empty( $input_password ) || empty( $input_firstname ) || empty( $input_lastname ) ) {
			$result[ 'status' ] = 'empty';
		} else {
			// On le fait par sécurité :
			// On re-vérifie le type d'adresse en fonction de la saisie
			$result = AccountSigninHelper::get_user_type_by_email_address( $input_email );
		}

		// Cas normal
		// Si le compte n'existe pas
		if ( $result[ 'status' ] == 'not-existing-account' ) {
			$result[ 'signin_status' ] = 'success';
			$result[ 'signin_errors' ] = array();

			$user_email = rtrim( $input_email );
			$user_name = $user_email;
			$user_firstname = mb_convert_case( $input_firstname, MB_CASE_TITLE );
			$user_lastname = mb_convert_case( $input_lastname, MB_CASE_TITLE );
			$display_name = $user_firstname. ' ' .substr( $user_lastname, 0, 1 ). '.';

			$wp_user_id = wp_insert_user( array(
				'user_login'	=> $user_name,
				'user_pass'		=> $input_password,
				'user_email'	=> $user_email,
				'first_name'	=> $user_firstname,
				'last_name'		=> $user_lastname,
				'display_name'	=> $display_name,
				'user_nicename' => sanitize_title( $display_name )
			) );
			ypcf_debug_log( 'account_signin_create_account >> ' . print_r($wp_user_id, true), false );

			// Si il y a une erreur d'insertion
			if ( is_wp_error( $wp_user_id ) ) {
				$result[ 'signin_status' ] = 'error';
				$result[ 'signin_errors' ] = $wp_user_id->get_error_codes();

			// Sinon, on connecte l'utilisateur
			} else {
				global $wpdb, $edd_options;
				$wpdb->update( $wpdb->users, array( sanitize_key( 'user_status' ) => 0 ), array( 'ID' => $wp_user_id ) );
				update_user_meta($wp_user_id, WDGUser::$key_validated_general_terms_version, $edd_options[WDGUser::$edd_general_terms_version]);
				$WDGUser = new WDGUser( $wp_user_id );
				$WDGUser->update_last_details_confirmation();
				$WDGUser->set_language($input_language);
				$WDGUser->update_api();
				WDGQueue::add_notification_registered_without_investment( $wp_user_id );
				wp_set_auth_cookie( $wp_user_id, false, is_ssl() );
			}
		}

		// Normalement, on ne passe pas ici
		// Si le compte existe déjà
		// Mais si il y a eu une coupure pendant une précédente requête de création
		// Peut-être qu'on peut identifier l'utilisateur avec son mot de passe ?
		if ( $result[ 'status' ] == 'existing-account' ) {
			$result[ 'signin_status' ] = 'success';
			$result[ 'signin_errors' ] = array();

			// On fait la comparaison pour voir si on arrive à conclure le signin
			$signin_return = WDGFormUsers::get_signin_return( $input_email, $input_password );

			// Si il y a une erreur, on retourne les codes d'erreur
			if ( is_wp_error( $signin_return ) ) {
				$result[ 'signin_status' ] = 'error';
				$result[ 'signin_errors' ] = $signin_return->get_error_codes();
			} else {
				wp_set_auth_cookie( $wp_user_id, false, is_ssl() );
			}
		}

		exit( json_encode( $result ) );
	}

	/**
	 * Envoie un mail de réinitialisation de mot de passe
	 */
	public static function account_signin_send_reinit_pass() {
		$input_email = sanitize_text_field( filter_input(INPUT_POST, 'email-address'));
		$page_forgot_password = WDG_Redirect_Engine::override_get_page_url( 'mot-de-passe-oublie' );
		$result[ 'status' ] = '';
		global $wpdb;
		if ( empty( $input_email ) ) {
			// Normalement, on ne passe pas ici
			$result[ 'status' ] = 'empty';
		} else {
			$user = get_user_by( 'email', trim( $input_email ) );
			if ( empty( $user ) ) {
				// normalement on n'arrive pas ici
				$result[ 'status' ] = 'not-existing-account';
			}
		}

		do_action('lostpassword_post');
		if (!$user) {
			// Normalement, on ne passe pas ici
			$result[ 'status' ] = 'not-existing-account';
		} else {
			$user_login = $user->user_login;
			$key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login));
			if (empty($key)) {
				$key = wp_generate_password(20, false);
				do_action('retrieve_password_key', $user_login, $key);
				$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
			}
			$link = $page_forgot_password . "?action=rp&key=$key&user_login=" . rawurlencode($user_login);
			$WDGUser = new WDGUser( $user->ID );
			$mail_sent = NotificationsAPI::password_reinit($WDGUser, $link);
			if ( $mail_sent === FALSE ) {
				$result[ 'status' ] = 'email-not-sent';
			} else {
				$result[ 'status' ] = 'email-sent';
			}
		}

		ypcf_debug_log( 'send_reinit_pass >> ' . print_r($result, true), false );
		exit( json_encode( $result ) );
	}

	/**
	 * Envoie un mail de validation de compte
	 */
	public static function account_signin_send_validation_email() {
		$input_email = sanitize_text_field( filter_input(INPUT_POST, 'email-address'));
		$is_new_account = filter_input(INPUT_POST, 'is-new-account');
		$redirect_url_after_validation = filter_input(INPUT_POST, 'redirect-url-after-validation');

		$result[ 'status' ] = '';
		if ( empty( $input_email ) ) {
			// Normalement, on ne passe pas ici
			$result[ 'status' ] = 'empty';
		} else {
			$user = get_user_by( 'email', trim( $input_email ) );
			if ( empty( $user ) ) {
				// normalement on n'arrive pas ici
				$result[ 'status' ] = 'not-existing-account';
			}
		}

		if (!$user) {
			// Normalement, on ne passe pas ici
			$result[ 'status' ] = 'not-existing-account';
		} else {
			// Récupération informations utilisateur courant
			WDGUser::current();
			$WDGUser = new WDGUser( $user->ID );
			global $force_language_to_translate_to;
			$force_language_to_translate_to = $WDGUser->get_language();
			$page_validation_email = WDG_Redirect_Engine::override_get_page_url( 'activer-compte' );
			update_user_meta( $WDGUser->get_wpref(), 'redirect_url_after_validation', $redirect_url_after_validation );
			$is_new_account_param = ( $is_new_account !== 'false') ? '1' : '0';
			$link = $page_validation_email . "?action=validate&is-new-account=".$is_new_account_param."&validation-code=" . $WDGUser->get_email_validation_code();

			$mail_sent = NotificationsAPI::user_account_email_validation($WDGUser, $link, ( $is_new_account !== 'false') );

			if ( $mail_sent === FALSE ) {
				$result[ 'status' ] = 'email-not-sent';
			} else {
				$result[ 'status' ] = 'email-sent';
			}
		}

		exit( json_encode( $result ) );
	}

	/**
	 * Change l'adresse mail d'un compte existant
	 */
	public static function account_signin_change_account_email() {
		$input_email = sanitize_text_field( filter_input(INPUT_POST, 'email-address'));
		$input_new_email = sanitize_text_field( filter_input(INPUT_POST, 'new-email-address'));

		$result[ 'status' ] = '';
		if ( empty( $input_email ) || empty( $input_new_email ) ) {
			// Normalement, on ne passe pas ici
			$result[ 'status' ] = 'empty';
		} else {
			$user = get_user_by( 'email', trim( $input_email ) );
			if ( empty( $user ) ) {
				// normalement on n'arrive pas ici
				$result[ 'status' ] = 'not-existing-account';
			}
		}

		if (!$user) {
			// Normalement, on ne passe pas ici
			$result[ 'status' ] = 'not-existing-account';
		} else {
			if ( !is_email( $input_new_email ) || !WDGRESTAPI_Lib_Validator::is_email( $input_new_email )  ) {
				// Normalement, on ne passe pas ici
				$result[ 'status' ] = 'email-adress-not-ok';
			} else {
				$WDGUser = new WDGUser( $user->ID, FALSE );
				global $force_language_to_translate_to;
				$force_language_to_translate_to = $WDGUser->get_language();
				$WDGUser->save_data( $input_new_email, $WDGUser->get_gender(), $WDGUser->get_firstname(), $WDGUser->get_lastname(), $WDGUser->get_use_lastname(), $WDGUser->get_birthday_day(), $WDGUser->get_birthday_month(), $WDGUser->get_birthday_year(), $WDGUser->get_birthplace(), $WDGUser->get_birthplace_district(), $WDGUser->get_birthplace_department(), $WDGUser->get_birthplace_country(), $WDGUser->get_nationality(), $WDGUser->get_address_number(), $WDGUser->get_address_number_complement(), $WDGUser->get_address(), $WDGUser->get_postal_code(), $WDGUser->get_city(), $WDGUser->get_country(), $WDGUser->get_tax_country(), $WDGUser->get_phone_number(), $WDGUser->get_contact_if_deceased(), $WDGUser->get_language() );

				// on envoie alors un mail de validation à cette nouvelle adresse mail
				$page_validation_email = WDG_Redirect_Engine::override_get_page_url( 'activer-compte' );
				$user_login = $user->user_login;
				$redirect_page = 'test';
				$link = $page_validation_email . "?action=rp&redirect-page=".$redirect_page."&login=" . rawurlencode($user_login);
				$mail_sent = NotificationsAPI::user_account_email_validation($WDGUser, $link, FALSE );

				if ( $mail_sent === FALSE ) {
					$result[ 'status' ] = 'email-not-sent';
				} else {
					$result[ 'status' ] = 'email-changed';
				}
			}
		}

		exit( json_encode( $result ) );
	}
}