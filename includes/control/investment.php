<?php
/**
 * Classe de gestion du processus d'investissement
 */
class WDGInvestment {
	private $id;
	private $token;
	private $token_info;
	private $error;
	/**
	 * @var ATCF_Campaign 
	 */
	private $campaign;
	private $session_amount;
	private $session_user_type;
	private $payment_key;
	/**
	 * @var LemonwayLibErrors
	 */
	public $error_item;
	
	public static $status_init = 'init';
	public static $status_expired = 'expired';
	public static $status_started = 'started';
	public static $status_waiting_check = 'waiting-check';
	public static $status_waiting_wire = 'waiting-wire';
	public static $status_waiting_payment = 'waiting-payment';
	public static $status_error = 'error';
	public static $status_canceled = 'canceled';
	public static $status_validated = 'validated';
	
	public static $contract_status_meta = 'contract_status';
	public static $contract_status_not_validated = 'investment_not_validated';
	public static $contract_status_preinvestment_validated = 'preinvestment_validated';
	public static $contract_status_investment_refused = 'investment_refused';
	public static $contract_status_investment_validated = 'investment_validated';
	
	public static $meanofpayment_unset = 'unset';
	public static $meanofpayment_wallet = 'wallet';
	public static $meanofpayment_cardwallet = 'cardwallet';
	public static $meanofpayment_card = 'card';
	public static $meanofpayment_wire = 'wire';
	public static $meanofpayment_check = 'check';
	
	public static $session_max_duration_hours = '2';
	
	public function __construct( $post_id = FALSE, $invest_token = FALSE ) {
		if ( !empty( $post_id ) ) {
			$this->id = $post_id;
		}
		if ( !empty( $invest_token ) ) {
			$this->token = $invest_token;
			$this->token_info = WDGWPREST_Entity_Investment::get( $this->token );
		}
		$this->error = array();
	}
	
	protected static $_current = null;
	/**
	 * @return WDGInvestment
	 */
	public static function current() {
		if ( is_null( self::$_current ) ) {
			ypcf_session_start();
			if ( isset( $_SESSION[ 'investment_token' ] ) ) {
				self::$_current = new self( FALSE, $_SESSION[ 'investment_token' ] );
			} elseif ( isset( $_SESSION[ 'investment_id' ] ) ) {
				self::$_current = new self( $_SESSION[ 'investment_id' ] );
			} else {
				self::$_current = new self();
			}
		}
		return self::$_current;
	}
	
	public function get_id() {
		return $this->id;
	}
	
	public function get_payment_key() {
		if ( empty( $this->payment_key ) ) {
			$this->payment_key = edd_get_payment_key( $this->get_id() );
		}
		return $this->payment_key;
	}
	
	
	/**
	 * Détermine si les valeurs de sessions sont correctes pour l'investissement
	 */
	public function is_session_correct() {
		if ( !isset( $_SESSION[ 'invest_update_date' ] ) ) {
			ypcf_debug_log( 'WDGInvestment::is_session_correct >> invest_update_date not set' );
			return FALSE;
		} else {
			ypcf_debug_log( 'WDGInvestment::is_session_correct >> invest_update_date = ' . $_SESSION[ 'invest_update_date' ] );
		}
		$invest_update_date = $_SESSION[ 'invest_update_date' ];
		
		date_default_timezone_set("Europe/Paris");
		$current_date = new DateTime();
		$difference_in_hours = floor( ( strtotime( $current_date->format( 'Y-m-d H:i:s' ) ) - strtotime( $invest_update_date ) ) / 3600 );
		if ( $difference_in_hours > self::$session_max_duration_hours ) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	public function init_session_with_saved_values() {
		$amount = $this->get_saved_amount();
		$user_id = $this->get_saved_user_id();
		$user_type = ( WDGOrganization::is_user_organization( $user_id ) ) ? $user_id : 'user';
		$this->update_session( $amount, $user_type );
		$_SESSION[ 'investment_saved_id' ] = $this->get_id();
	}
	
	/**
	 * Met à jour les valeurs de session qui concernent l'investissement en cours
	 * @param int $amount
	 * @param string $user_type
	 */
	public function update_session( $amount = FALSE, $user_type = FALSE ) {
		ypcf_session_start();
		date_default_timezone_set("Europe/Paris");
		$current_datetime = new DateTime();
		$_SESSION[ 'invest_update_date' ] = $current_datetime->format( 'Y-m-d H:i:s' );
		
		if ( !isset( $_SESSION[ 'invest_update_date' ] ) ) {
			ypcf_debug_log( 'WDGInvestment::update_session >> UPDATE invest_update_date NOT SET' );
		} else {
			ypcf_debug_log( 'WDGInvestment::update_session >> UPDATE invest_update_date = ' . $_SESSION[ 'invest_update_date' ] );
		}
		
		if ( !empty( $amount ) ) {
			$_SESSION[ 'invest_amount' ] = $amount;
			$_SESSION[ 'redirect_current_amount_part' ] = $amount;
		}
		if ( !empty( $user_type ) ) {
			$_SESSION[ 'invest_user_type' ] = $user_type;
			$_SESSION[ 'redirect_current_user_type' ] = $user_type;
		}
	}
	
	/**
	 * Retourne la campagne en cours
	 * @return ATCF_Campaign
	 */
	public function get_campaign() {
		$buffer = FALSE;
		if ( isset( $this->campaign ) ) {
			$buffer = $this->campaign;
			
		} elseif ( $this->has_token() && isset( $this->token_info->project ) ) {
			$this->campaign = new ATCF_Campaign( $this->token_info->project );
			$buffer = $this->campaign;
			
		} elseif ( isset( $_GET['campaign_id'] ) ) {
			$this->campaign = new ATCF_Campaign( $_GET['campaign_id'] );
			$buffer = $this->campaign;
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne la campagne liée dans l'investissement
	 * @return \ATCF_Campaign
	 */
	public function get_saved_campaign() {
		$buffer = FALSE;
		if ( !empty( $this->id ) ) {
			$downloads = edd_get_payment_meta_downloads( $this->id ); 
			$download_id = '';
			if ( !is_array( $downloads[0] ) ){
				$download_id = $downloads[0];
			} else {
				$download_id = $downloads[0]['id'];
			}
			if ( !empty( $download_id ) ) {
				$buffer = new ATCF_Campaign( $download_id );
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne la valeur d'investissement stockée en session
	 */
	public function get_session_amount() {
		if ( !isset( $this->session_amount ) ) {
			$this->session_amount = $_SESSION[ 'redirect_current_amount_part' ];
			if ( empty( $this->session_amount ) ) {
				$this->session_amount = $_SESSION[ 'invest_amount' ];
			}
		}
		return $this->session_amount;
	}
	
	/**
	 * Retourne le montant de l'investissement
	 * @return int
	 */
	public function get_amount() {
		return $this->token_info->amount;
	}
	
	/**
	 * Retourne le montant de l'investissement enregistré
	 * @return int
	 */
	public function get_saved_amount() {
		$buffer = FALSE;
		if ( !empty( $this->id ) ) {
			$buffer = edd_get_payment_amount( $this->id );
		}
		return $buffer;
	}
	
	public function get_saved_date() {
		$post_invest = get_post( $this->id );
		return $post_invest->post_date;
	}
	
	/**
	 * Retourne le type d'utilisateur / id d'organisation stocké en session
	 */
	public function get_session_user_type() {
		if ( !isset( $this->session_user_type ) ) {
			$this->session_user_type = $_SESSION[ 'redirect_current_user_type' ];
			if ( empty( $this->session_user_type ) ) {
				$this->session_user_type = $_SESSION[ 'invest_user_type' ];
			}
		}
		return $this->session_user_type;
	}
	
	/**
	 * Retourne l'id de l'investisseur lié à l'investissement
	 */
	public function get_saved_user_id() {
		$user_info = edd_get_payment_meta_user_info( $this->get_id() );
		$user_id = (isset( $user_info['id'] ) && $user_info['id'] != -1) ? $user_info['id'] : $user_info['email'];
		return $user_id;
	}
	
	/**
	 * Retourne le statut du post de paiement
	 * @return string
	 */
	public function get_saved_status() {
		$post_invest = get_post( $this->get_id() );
		return $post_invest->post_status;
	}
	
	public function get_saved_payment_key() {
		return edd_get_payment_key( $this->get_id() );
	}
	
	/**
	 * Retourne le token d'investissement
	 * @return string
	 */
	public function get_token() {
		return $this->token;
	}
	
	/**
	 * Retourne l'url de redirection
	 * @param string $redirection_type
	 * @return string
	 */
	public function get_redirection( $redirection_type, $param = '', $param2 = '' ) {
		$buffer = '';
		if ( $this->has_token() ) {
			switch ( $redirection_type ) {
				case 'error':
					$buffer = $this->token_info->redirect_url_nok;
					break;
				case 'success':
					$buffer = $this->token_info->redirect_url_ok;
					break;
			}
			if ( !empty( $param ) ) {
				$buffer .= '?param=' . $param;
				if ( !empty( $param2 ) ) {
					$buffer .= '&param2=' . $param2;
				}
			}
		}
		return $buffer;
	}
	
	/**
	 * Fait un post sur l'url transmise pour les notifications
	 */
	public function post_token_notification() {
		if ( $this->has_token() && !empty( $this->token_info->notification_url ) ) {
			$parameters = array(
				'token'		=> $this->token,
				'status'	=> $this->token_info->status
			);
			wp_remote_post( 
				$this->token_info->notification_url, 
				array(
					'body'		=> $parameters
				) 
			);
		}
	}
	
	/**
	 * Détermine le nouveau statut de l'investissement
	 * @param string $status
	 */
	public function set_status( $status ) {
		if ( $this->has_token() ) {
			$this->token_info->status = $status;
			$parameters = array(
				'status' => $status
			);
			WDGWPRESTLib::call_post_wdg( 'investment/' . $this->token, $parameters );
		}
	}
	
	public function set_contract_status( $status ) {
		if ( !empty( $this->id ) ) {
			update_post_meta( $this->id, WDGInvestment::$contract_status_meta, $status );
			if ( $status == WDGInvestment::$contract_status_investment_validated ) {
				$buffer = 'publish';
				$postdata = array(
					'ID'			=> $this->id,
					'post_status'	=> $buffer
				);
				wp_update_post($postdata);
			}
		}
	}
	
	public function get_contract_status() {
		return get_post_meta( $this->id, WDGInvestment::$contract_status_meta, TRUE );
	}
	
	/**
	 * Met à jour l'URL du contrat sur l'API
	 * @param string $contract_url
	 */
	public function update_contract_url( $contract_url ) {
		if ( $this->has_token() ) {
			$parameters = array(
				'contract_url' => $contract_url
			);
			WDGWPRESTLib::call_post_wdg( 'investment/' . $this->token, $parameters );
		}
	}
	
	/**
	 * Retourne le tableau d'erreurs d'investissements
	 * @return array
	 */
	public function get_error() {
		if ( !isset( $this->error ) ) {
			$this->error = array();
		}
		return $this->error;
	}
	
	/**
	 * Analyse les informations reçues au démarrage du processus d'investissement
	 */
	public static function init() {
		$buffer = true;
		
		$invest_start = filter_input( INPUT_GET, 'invest_start' );
		$token_start = filter_input( INPUT_GET, 'token' );
		if ( !empty( $invest_start ) || !empty( $token_start ) ) {
			ypcf_session_start();
			WDGInvestment::unset_session();
			
			if ( !empty( $token_start ) ) {
				$wdg_investment = new WDGInvestment( FALSE, $token_start );
				self::$_current = $wdg_investment;
				$_SESSION[ 'investment_token' ] = $token_start;
				$buffer = $wdg_investment->start_with_token();
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Remet la session à zero
	 */
	public static function unset_session() {
		$session_vars_list = array(
			'invest_update_date',
			'redirect_current_amount_part',
			'redirect_current_invest_type',
			'new_orga_just_created',
			'error_invest',
			'redirect_current_selected_reward',
			'investment_token',
			'investment_id',
			'remaining_amount_when_authenticated'
		);
		foreach ( $session_vars_list as $session_var_key ) {
			if ( isset( $_SESSION[ $session_var_key ] ) ) {
				unset( $_SESSION[ $session_var_key ] );
			}
		}
	}
	
	/**
	 * Retourne TRUE si un token a été défini
	 * @return boolean
	 */
	public function has_token() {
		return ( !empty( $this->token ) );
	}
	
	/**
	 * Démarre l'investissement avec les données reçues via le token
	 */
	public function start_with_token() {
		$this->error = array();
		$buffer = TRUE;
		
		// Déconnecter l'utilisateur en cours, au cas où
		wp_destroy_current_session();
		wp_clear_auth_cookie();
		global $current_user;
		$current_user = null;
		wp_set_current_user( 0 );
		
		// Vérifier que la date d'expiration du token n'est pas passée
		if ( $this->token_info->status == WDGInvestment::$status_expired ) {
			array_push( $this->error, __( "Le jeton d'investissement a expir&eacute;.", 'yproject' ) );
			return FALSE;
		}
		
		// Vérifier que le statut du token est valide
		if ( $this->token_info->status != WDGInvestment::$status_init ) {
			array_push( $this->error, __( "Le statut du jeton d'investissement n'est pas valable.", 'yproject' ) );
			return FALSE;
		}
		
		// Vérifier qu'il est possible d'investir sur le projet concerné
		$campaign = $this->get_campaign();
		if ( !$campaign->is_investable() ) {
			array_push( $this->error, __( "Impossible d'investir sur ce projet", 'yproject' ) );
			return FALSE;
		}
		
		// Vérifier que le montant est valide
		if ( !$this->is_amount_valid() ) {
			array_push( $this->error, __( "Le montant d'investissement n'est pas une valeur accept&eacute;e.", 'yproject' ) );
			return FALSE;
		}
		
		// Vérifier le compte utilisateur
		// Est-ce qu'il existe ?
		$wdg_user_by_email = get_user_by( 'email', $this->token_info->email );
		// Si il n'existe pas, il faut le créer
		if ( !$wdg_user_by_email ) {
			$wp_user_id = wp_insert_user( array(
				'user_login'	=> $this->token_info->email,
				'user_pass'		=> md5( $this->token_info->email ),
				'display_name'	=> sanitize_title( $this->token_info->firstname ) . ' ' . $this->token_info->lastname,
				'user_email'	=> $this->token_info->email
			) );
			
			
			if ( is_wp_error( $wp_user_id ) ) {
				array_push( $this->error, __( "Probl&egrave;me de cr&eacute;ation d'utilisateur", 'yproject' ) );
				return FALSE;
			}
			global $wpdb, $edd_options;
			$wpdb->update( $wpdb->users, array( sanitize_key( 'user_status' ) => 0 ), array( 'ID' => $wp_user_id ) );
			update_user_meta( $wp_user_id, WDGUser::$key_validated_general_terms_version, $edd_options[WDGUser::$edd_general_terms_version] );
			NotificationsSlack::send_new_user( $wp_user_id );
			
		} else {
			$wp_user_id = $wdg_user_by_email->ID;
		}
		// On connecte l'utilisateur
		wp_set_auth_cookie( $wp_user_id, false, is_ssl() );
		
		// On enregistre les informations
		$wdg_current_user = new WDGUser( $wp_user_id );
		$use_lastname = '';
		$birthplace_district = '';
		$birthplace_department = '';
		$birthplace_country = '';
		$address_number = '';
		$address_number_complement = '';
		$tax_country = '';
		$wdg_current_user->save_data(
			$this->token_info->email,
			$this->token_info->gender,
			$this->token_info->firstname,
			$this->token_info->lastname,
			$use_lastname,
			$this->token_info->birthday_day,
			$this->token_info->birthday_month,
			$this->token_info->birthday_year,
			$this->token_info->birthday_city,
			$birthplace_district,
			$birthplace_department,
			$birthplace_country,
			$this->token_info->nationality,
			$address_number,
			$address_number_complement,
			$this->token_info->address,
			$this->token_info->postalcode,
			$this->token_info->city,
			$this->token_info->country,
			$tax_country,
			''
		);
		// On vérifie les informations de l'utilisateur
		if ( !$wdg_current_user->has_filled_invest_infos( $campaign->funding_type() ) ) {
			global $user_can_invest_errors;
			$this->error = $user_can_invest_errors;
			return FALSE;
		}
		
		// Vérifier les infos d'une organisation
		if ( $this->token_info->is_legal_entity ) {
			/*
			 * Gérer plus tard :
			 * - vérifier si l'organisation existe
			 * - sinon la créer
			 * - enregistrer les données
			 * - lier à l'utilisateur
			'legal_entity_form'			=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_id'			=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_rcs'			=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_capital'		=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_address'		=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_postalcode'	=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_city'			=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_nationality'	=> array( 'type' => 'varchar', 'other' => '' ),
			 */
		}
		
		// On dit à l'API que la procédure a démarré
		$this->set_status( WDGInvestment::$status_started );
		
		return $buffer;
	}
	
	/**
	 * Retourne TRUE si le montant renseigné est ok
	 * @return boolean
	 */
	public function is_amount_valid() {
		$amount = 0;
		if ( $this->has_token() ) {
			$amount = $this->token_info->amount;
		}
		return isset( $amount ) && is_numeric( $amount ) && ctype_digit( $amount ) 
			&& intval( $amount ) == $amount && $amount >= 1 && $amount <= $this->get_max_value_to_invest();
	}
	
	/**
	 * Retourne la valeur maximum qui peut être investie
	 * @return int
	 */
	public function get_max_value_to_invest() {
		return ( $this->campaign->goal( FALSE ) - $this->campaign->current_amount( FALSE ) );
	}
	
	/**
	 * @return LemonwayLibErrors
	 */
	public function get_error_item() {
		return $this->error_item;
	}
	
/******************************************************************************/
// PAYMENT
/******************************************************************************/
	private function save_payment( $payment_key, $mean_of_payment, $is_failed = FALSE, $amount_param = 0, $amount_by_card = 0 ) {
		if ( $this->exists_payment( $payment_key ) ) {
			return 'publish';
		}
		
		//Récupération des bonnes informations utilisateur
		$WDGUser_current = WDGUser::current();
		$save_user_id = $WDGUser_current->get_wpref();
		$save_display_name = $WDGUser_current->wp_user->display_name;
		$invest_type = $this->get_session_user_type();
		$lemonway_id = $WDGUser_current->get_lemonway_id();
		if ( $invest_type != 'user' ) {
			$WDGOrganization = new WDGOrganization( $invest_type );
			if ( $WDGOrganization ) {
				$current_user_organization = $WDGOrganization->get_creator();
				$save_user_id = $current_user_organization->ID;
				$save_display_name = $WDGOrganization->get_name();
				$lemonway_id = $WDGOrganization->get_lemonway_id();
			}
		}
		
		$amount = 0;
		if ( $amount_param > 0 ) {
			$amount = $amount_param;
		} else {
			$amount = $this->get_session_amount();
		}
		
		// GESTION DU PAIEMENT COTE EDD
		if ( !$this->needs_signature() ) {
			WDGInvestment::unset_session();
		}

		//Création d'un paiement pour edd
		$user_info = array(
			'id'			=> $save_user_id,
			'gender'		=> $WDGUser_current->get_gender(),
			'email'			=> $WDGUser_current->get_email(),
			'first_name'	=> $WDGUser_current->get_firstname(),
			'last_name'		=> $WDGUser_current->get_lastname(),
			'discount'		=> '',
			'address'		=> array()
		);

		$cart_details = array(
			array(
				'name'			=> $this->campaign->data->post_title,
				'id'			=> $this->campaign->ID,
				'item_number'	=> array(
					'id'			=> $this->campaign->ID,
					'options'		=> array()
				),
				'price'			=> 1,
				'quantity'		=> $amount
			)
		);

		$this->set_status( WDGInvestment::$status_validated );

		$payment_data = array( 
			'price'			=> $amount, 
			'date'			=> date('Y-m-d H:i:s'), 
			'user_email'	=> $WDGUser_current->get_email(),
			'purchase_key'	=> $payment_key,
			'currency'		=> edd_get_currency(),
			'downloads'		=> array( $this->campaign->ID ),
			'user_info'		=> $user_info,
			'cart_details'	=> $cart_details,
			'status'		=> 'pending'
		);
		$payment_id = edd_insert_payment( $payment_data );
		$this->id = $payment_id;
		$_SESSION[ 'investment_id' ] = $payment_id;
		update_post_meta( $payment_id, '_edd_payment_ip', $_SERVER[ 'REMOTE_ADDR' ] );
		if ( strpos( $mean_of_payment, 'wallet' ) !== FALSE ) {
			update_post_meta( $payment_id, 'amount_with_wallet', $this->get_session_amount() - $amount_by_card );
			update_post_meta( $payment_id, 'amount_with_card', $amount_by_card );
		}
		
		edd_record_sale_in_log( $this->campaign->ID, $payment_id );
		// FIN GESTION DU PAIEMENT COTE EDD

		// Si on sait déjà que ça a échoué, pas la peine de tester
		if ( $is_failed ) {
			// Paiement
			$buffer = 'failed';
			$this->cancel();
			
		} else {
			// Annulation de l'investissement qui était la référence au démarrage du processus, si il y en avait un
			if ( !empty( $_SESSION[ 'investment_saved_id' ] ) ) {
				$WDGInvestment_Canceled = new WDGInvestment( $_SESSION[ 'investment_saved_id' ] );
				$WDGInvestment_Canceled->cancel();
			}
			
			// Annulation des investissements non-démarrés du même investisseur
			$pending_not_validated_investments = array();
			if ( $invest_type != 'user' ) {
				$pending_not_validated_investments = $WDGOrganization->get_pending_not_validated_investments();
			} else {
				$pending_not_validated_investments = $WDGUser_current->get_pending_not_validated_investments();
			}
			if ( !empty( $pending_not_validated_investments ) ) {
				foreach ( $pending_not_validated_investments as $pending_not_validated_investment_item ) {
					$pending_not_validated_investment_item->cancel();
				}
			}
			

			// Vérifie le statut du paiement, envoie un mail de confirmation et crée un contrat si on est ok
			$buffer = ypcf_get_updated_payment_status( $payment_id, false, false, $this );
			
			// Si c'est un préinvestissement,
			//	on passe le statut de préinvestissement
			//  et on repasse l'investissement comme en attente
			if ( $this->campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
				$this->set_contract_status( WDGInvestment::$contract_status_preinvestment_validated );
				$postdata = array(
					'ID'			=> $payment_id,
					'post_status'	=> 'pending'
				);
				wp_update_post( $postdata );
			}
		}

		$this->post_token_notification();
		
		// Notifications
		if ( $mean_of_payment == WDGInvestment::$meanofpayment_wire ) {
			NotificationsEmails::new_purchase_pending_wire_admin( $payment_id );
			NotificationsEmails::new_purchase_pending_wire_user( $payment_id, $lemonway_id );
		}
		
		//Si un utilisateur investit, il croit au projet
		global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";
		$users = $wpdb->get_results( "SELECT user_id FROM " .$table_jcrois. " WHERE campaign_id = " .$this->campaign->ID. " AND user_id = " . $WDGUser_current->get_wpref() );
		if ( !$users ) {
			$wpdb->insert( $table_jcrois,
				array(
					'user_id'		=> $WDGUser_current->get_wpref(),
					'campaign_id'	=> $this->campaign->ID
				)
			);
		}
		
		if ( $buffer == 'publish' ) {
			do_action('wdg_delete_cache', array(
				'home-projects',
				'projectlist-projects-current',
				'cache_campaign_' . $this->campaign->ID
			));
		}
		
		return $buffer;
	}
	
	/**
	 * Vérifie si un paiement avec la même clé a déjà été enregistré, pour ne pas le faire 2 fois
	 */
	private function exists_payment( $payment_key ) {
		$buffer = FALSE;
		$paymentlist = edd_get_payments(array(
		    'number'	 => -1,
		    'download'   => $this->campaign->ID
		));
		foreach ( $paymentlist as $payment ) {
			if ( strpos( edd_get_payment_key( $payment->ID ), $payment_key ) !== FALSE ) {
				$buffer = TRUE;
				array_push( $this->error, __( "Le paiement a d&eacute;j&agrave; &eacute;t&eacute; pris en compte. Merci de nous contacter.", 'yproject' ) );
				break;
			}
		}
		return $buffer;
	}
	
	public function try_payment( $meanofpayment ) {
		$payment_key = FALSE;
		switch ( $meanofpayment ) {
			case WDGInvestment::$meanofpayment_wallet:
				$payment_key = $this->try_payment_wallet( $this->get_session_amount() );
				$buffer = $this->save_payment( $payment_key, $meanofpayment );
				break;
			case WDGInvestment::$meanofpayment_cardwallet:
				$buffer = $this->try_payment_card( TRUE );
				break;
			case WDGInvestment::$meanofpayment_card:
				$buffer = $this->try_payment_card();
				break;
		}
		
		return $buffer;
	}
	
	public function try_payment_wallet( $amount, $current = TRUE, $amount_by_card = FALSE ) {
		$buffer = FALSE;
		if ( $current ) {
			$WDGUser_current = WDGUser::current();
			$invest_type = $this->get_session_user_type();
			$campaign = $this->campaign;
		} else {
			$WDGUser_current = new WDGUser( $this->get_saved_user_id() );
			$invest_type = WDGOrganization::is_user_organization( $this->get_saved_user_id() ) ? $this->get_saved_user_id() : 'user';
			$campaign = $this->get_saved_campaign();
		}

		// Vérifications de sécurité
		$can_use_wallet = FALSE;
		if ( $invest_type == 'user' ) {
			$can_use_wallet = $WDGUser_current->can_pay_with_wallet( $amount, $campaign );
			
		} else {
			$WDGOrganization_debit = new WDGOrganization( $invest_type );
			$can_use_wallet = $WDGOrganization_debit->can_pay_with_wallet( $amount, $campaign, $amount_by_card );
		}
		
		// Tentative d'exécution du transfert d'argent
		$transfer_funds_result = FALSE;
		if ( $can_use_wallet ) {
			$campaign_organization = $campaign->get_organization();
			$WDGOrganization_campaign = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
			$WDGOrganization_campaign->check_register_campaign_lemonway_wallet();
			
			if ( $invest_type == 'user' ) { 
				$transfer_funds_result = LemonwayLib::ask_transfer_funds( $WDGUser_current->get_lemonway_id(), $WDGOrganization_campaign->get_campaign_lemonway_id(), $amount );
			
			} else {
				$transfer_funds_result = LemonwayLib::ask_transfer_funds( $WDGOrganization_debit->get_lemonway_id(), $WDGOrganization_campaign->get_campaign_lemonway_id(), $amount );
			}
		}
		
		// Enregistrement des données selon résultat du transfert
		if ( !empty( $transfer_funds_result ) && isset( $transfer_funds_result->ID ) ) {
			$buffer = 'wallet_'. $transfer_funds_result->ID;

		} else {
			NotificationsEmails::new_purchase_admin_error_wallet( $WDGUser_current, $campaign->data->post_title, $amount );
		}
		
		return $buffer;
	}
	
	private function try_payment_card( $with_wallet = FALSE) {
		$invest_type = $this->get_session_user_type();
		
		$WDGuser_current = WDGUser::current();
		if ( $invest_type != 'user' ) {
			$WDGOrganization_debit = new WDGOrganization( $invest_type );
			$WDGUserInvestments_current = new WDGUserInvestments( $WDGOrganization_debit );
			$WDGOrganization_debit->register_lemonway();
			$wallet_id = $WDGOrganization_debit->get_lemonway_id();
		} else {
			$WDGUserInvestments_current = new WDGUserInvestments( $WDGuser_current );
			$WDGuser_current->register_lemonway();
			$wallet_id = $WDGuser_current->get_lemonway_id();
		}
		
		$current_token_id = 'U'.$WDGuser_current->wp_user->ID .'C'. $this->campaign->ID;
		$wk_token = LemonwayLib::make_token($current_token_id);
		
		$return_url = home_url( '/paiement-effectue/' ) . '?campaign_id='. $this->campaign->ID;
		
		$register_card = 0;
		$amount = $this->get_session_amount();
		// Si on paie en s'aidant du wallet, on diminue d'autant le montant total
		if ( $with_wallet ) {
			if ( $invest_type == 'user' ) {
				$amount -= $WDGuser_current->get_lemonway_wallet_amount();
			} else {
				$amount -= $WDGOrganization_debit->get_available_rois_amount();
			}
			$return_url .= '&meanofpayment=' .WDGInvestment::$meanofpayment_cardwallet;
		}
		// Si le montant dépasse toujours le montant maximal, le montant par carte reste le maximum autorisé
		if ( $amount > $WDGUserInvestments_current->get_maximum_investable_amount_without_alert() ) {
			$_SESSION[ 'remaining_amount_when_authenticated' ] = $this->get_session_amount() - $WDGUserInvestments_current->get_maximum_investable_amount_without_alert();
			$amount = $WDGUserInvestments_current->get_maximum_investable_amount_without_alert();
			$register_card = 1;
		}
		
		$error_url = $return_url . '&error=1';
		$cancel_url = $return_url . '&cancel=1';
		
		$return = LemonwayLib::ask_payment_webkit( $wallet_id, $amount, 0, $wk_token, $return_url, $error_url, $cancel_url, $register_card );
		if ( !empty($return->MONEYINWEB->TOKEN) ) {
			$url_css = 'https://www.wedogood.co/wp-content/themes/yproject/_inc/css/lemonway.css';
			$url_css_encoded = urlencode( $url_css );
			wp_redirect( YP_LW_WEBKIT_URL . '?moneyInToken=' . $return->MONEYINWEB->TOKEN . '&lang=fr&p=' . $url_css_encoded );
			exit();
			
		} else {
			ypcf_debug_log( 'WDGInvestment::try_payment_card > error - ' .LemonwayLib::get_last_error_code(). ' - ' .LemonwayLib::get_last_error_message() );
			array_push( $this->error, LemonwayLib::get_last_error_code(). ' - ' .LemonwayLib::get_last_error_message() );
			return FALSE;
		}
	}
	
	/**
	 * Retour de paiement par carte
	 * @param string $mean_of_payment
	 * @return mixed
	 */
	public function payment_return( $mean_of_payment ) {
		$buffer = FALSE;
		
		if ( empty( $mean_of_payment ) ) {
			$mean_of_payment = WDGInvestment::$meanofpayment_card;
		}
		
		// Retour de paiement par carte
		if ( $mean_of_payment == WDGInvestment::$meanofpayment_card || $mean_of_payment == WDGInvestment::$meanofpayment_cardwallet ) {
			
			$payment_key = $_REQUEST["response_wkToken"];
			if ( !$this->exists_payment( $payment_key ) ) {
				$lw_transaction_result = LemonwayLib::get_transaction_by_id( $payment_key );
				$return_cancel = filter_input( INPUT_GET, 'cancel' );
				$return_error = filter_input( INPUT_GET, 'error' );
				$is_failed = ( !empty( $return_cancel ) || !empty( $return_error ) );
				$is_failed = $is_failed || ( $lw_transaction_result->STATUS != 3 && $lw_transaction_result->STATUS != 0 );
				$amount_by_card = $lw_transaction_result->CRED;

				// Compléter par wallet
				if ( !$is_failed ) {
					$invest_type = $this->get_session_user_type();
					if ( $invest_type != 'user' ) {
						$WDGOrganization_debit = new WDGOrganization( $invest_type );
						$amount = min( $this->get_session_amount(), $amount_by_card + $WDGOrganization_debit->get_available_rois_amount() );
					} else {
						$WDGUser_current = WDGUser::current();
						$amount = min( $this->get_session_amount(), $WDGUser_current->get_lemonway_wallet_amount() );
					}
					$wallet_payment_key = $this->try_payment_wallet( $amount, TRUE, $amount_by_card );
					if ( !empty( $wallet_payment_key ) ) {
						$payment_key .= '_' . $wallet_payment_key;
					} else {
						$payment_key .= '_wallet_FAILED';
					}
				}

				// Sauvegarde du paiement (la session est écrasée)
				$buffer = $this->save_payment( $payment_key, $mean_of_payment, $is_failed, $amount, $amount_by_card );

				if ( $buffer == 'failed' ) {
					$WDGUser_current = WDGUser::current();
					$this->error_item = new LemonwayLibErrors( $lw_transaction_result->INT_MSG );
					NotificationsEmails::new_purchase_admin_error( $WDGUser_current->wp_user, $lw_transaction_result->INT_MSG, $this->error_item->get_error_message(), $this->campaign->data->post_title, $this->get_session_amount(), $this->error_item->ask_restart() );
					$investment_link = home_url( '/investir/' ) . '?campaign_id=' . $this->campaign->ID . '&invest_start=1&init_invest=' . $this->get_session_amount();
					$investment_link = '<a href="'.$investment_link.'" target="_blank">'.$investment_link.'</a>';
					NotificationsAPI::investment_error( $WDGUser_current->wp_user->user_email, $WDGUser_current->wp_user->user_firstname, $this->get_session_amount(), $this->campaign->data->post_title, $this->campaign->get_api_id(), $this->error_item->get_error_message( FALSE, FALSE ), $investment_link );

				}
			}
			
		// Retour de paiement par virement
		} elseif ( $mean_of_payment == WDGInvestment::$meanofpayment_wire ) {
			$random = rand(10000, 99999);
			$payment_key = 'wire_TEMP_' . $random;
			$this->set_status( WDGInvestment::$status_waiting_wire );
			$this->post_token_notification();
			$buffer = $this->save_payment( $payment_key, $mean_of_payment );
			if ( !$this->needs_signature() ) {
				WDGInvestment::unset_session();
			}
			
		} elseif ( $mean_of_payment == WDGInvestment::$meanofpayment_unset ) {
			$random = rand(10000, 99999);
			$payment_key = 'unset_' . $random;
			while ( $this->exists_payment( $payment_key ) ) {
				$random = rand(10000, 99999);
				$payment_key = 'unset_' . $random;
			}
			$this->set_status( WDGInvestment::$status_waiting_payment );
			$buffer = $this->save_payment( $payment_key, $mean_of_payment );
			$this->set_contract_status( WDGInvestment::$contract_status_not_validated );
			WDGInvestment::unset_session();
		}

		edd_empty_cart();
		
		return $buffer;
	}
	
/******************************************************************************/
// SIGNATURE
/******************************************************************************/
	public function create_signature() {
		$payment_id = $this->get_id();
		$contract = new WDGInvestmentContract( $payment_id );
		if ( !$contract->exists() ) {
			$campaign_id = $this->get_campaign()->ID;
			$user_id = $this->get_saved_user_id();
			$contract_filename = getNewPdfToSign( $campaign_id, $payment_id, $user_id );
			$WDGUser = new WDGUser( $user_id );
			WDGInvestmentContract::create( $payment_id, $contract_filename, $WDGUser );
		}
	}
	
	public function needs_signature() {
		return ( $this->get_saved_amount() > WDGInvestmentContract::$signature_minimum_amount ||  $this->get_session_amount() > WDGInvestmentContract::$signature_minimum_amount );
	}
	
/******************************************************************************/
// REFUND
/******************************************************************************/
	public function refund() {
		$payment_key = edd_get_payment_key( $this->get_id() );
		if ( $payment_key != 'check' && strpos( $payment_key, 'unset' ) === FALSE ) {
			$campaign = $this->get_saved_campaign();
			$organization = $campaign->get_organization();
			$organization_obj = new WDGOrganization( $organization->wpref, $organization );
			$credit_wallet_id = '';
			$user_id = $this->get_saved_user_id();
			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$credit_organization = new WDGOrganization( $user_id );
				$credit_wallet_id = $credit_organization->get_lemonway_id();
			} else {
				$credit_user = new WDGUser( $user_id );
				$credit_wallet_id = $credit_user->get_lemonway_id();
			}

			// Si c'est un virement
			if ( strpos($payment_key, 'wire_') !== FALSE ) {
				$amount = $this->get_saved_amount();
				$transfer_funds_result = LemonwayLib::ask_transfer_funds( $organization_obj->get_campaign_lemonway_id(), $credit_wallet_id, $amount );
				if (LemonwayLib::get_last_error_code() == '') {
					update_post_meta( $this->get_id(), 'refund_wire_id', $transfer_funds_result->ID );
				}

			// Si c'est par carte ou wallet
			} else {
				$card_token = '';
				$wallet_token = '';
				if ( strpos( $payment_key, '_wallet_' ) !== FALSE ) {
					$key_exploded = explode( '_wallet_', $payment_key );
					$card_token = $key_exploded[0];
					$wallet_token = $key_exploded[1];

				} elseif ( strpos( $payment_key, 'wallet_' ) !== FALSE ) {
					$key_exploded = explode( 'wallet_', $payment_key );
					$wallet_token = $key_exploded[1];

				} else {
					$card_token = $payment_key;
				}

				if ( !empty( $card_token ) ) {
					// amount_with_card n'est défini que si on a utilisé la carte + le wallet pour payer.
					$amount_with_card = get_post_meta( $this->get_id(), 'amount_with_card', TRUE );
					// Sinon on prend le montant total
					if ( empty( $amount_with_card ) ) {
						$amount_with_card = $this->get_saved_amount();
					}
					// D'abord, on reverse sur le porte-monnaie utilisateur
					$transfer_funds_result = LemonwayLib::ask_transfer_funds( $organization_obj->get_campaign_lemonway_id(), $credit_wallet_id, $amount_with_card );
					
					// Ensuite on fait le remboursement
					$lw_transaction_result = LemonwayLib::get_transaction_by_id( $card_token );
					$lw_refund = LemonwayLib::ask_refund( $lw_transaction_result->ID );
					if (LemonwayLib::get_last_error_code() == '') {
						update_post_meta( $this->get_id(), 'refund_wallet_id', $transfer_funds_result->ID );
						update_post_meta( $this->get_id(), 'refund_id', $lw_refund->TRANS->HPAY->ID );
					}
				}
				
				if ( !empty( $wallet_token ) ) {
					// amount_with_wallet n'est défini que si on a utilisé la carte + le wallet pour payer. 
					$amount_with_wallet = get_post_meta( $this->get_id(), 'amount_with_wallet', TRUE );
					if ( !empty( $amount_with_wallet ) ) {
						$transfer_funds_result = LemonwayLib::ask_transfer_funds( $organization_obj->get_campaign_lemonway_id(), $credit_wallet_id, $amount_with_wallet );
						if (LemonwayLib::get_last_error_code() == '') {
							update_post_meta( $this->get_id(), 'refund_wallet_id', $transfer_funds_result->ID );
						}
					}

				}

			}

		}
	}
	
	public function cancel() {
		$payment_id = $this->get_id();
		if ( !empty( $payment_id ) ) {
			$postdata = array(
				'ID'			=> $payment_id,
				'post_status'	=> 'failed'
			);
			wp_update_post($postdata);

			$log_post_items = get_posts(array(
				'post_type'		=> 'edd_log',
				'meta_key'		=> '_edd_log_payment_id',
				'meta_value'	=> $payment_id
			));
			foreach ( $log_post_items as $log_post_item ) {
				$postdata = array(
					'ID'			=> $log_post_item->ID,
					'post_status'	=> 'failed'
				);
				wp_update_post($postdata);
			}
		}
	}
	
	/**
	 * A ne faire qu'une fois par campagne : enregistre les investissements
	 * @param ATCF_Campaign $campaign
	 */
	public static function save_campaign_to_api( $campaign ) {
		if ( !empty( $campaign->ID ) ) {
			$payments = edd_get_payments( array(
				'number'	 => -1,
				'download'   => $campaign->ID
			) );

			if ( $payments ) {
				foreach ( $payments as $payment ) {
					WDGWPREST_Entity_Investment::create( $campaign, $payment );
				}
			}
		}
	}
}