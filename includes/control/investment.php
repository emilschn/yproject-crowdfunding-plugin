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
	private $id_user_subscription;
	private $id_subscription;
	private $session_amount;
	private $session_user_type;
	private $payment_key;
	private $payment_status;
	/**
	 * @var LemonwayLibErrors
	 */
	public $error_item;

	public static $payment_post_type = '';
	public static $payment_meta_key_user_id = '_edd_payment_user_id';
	public static $payment_meta_key_ip = '_edd_payment_ip';

	public static $log_post_type = 'edd_log';
	public static $log_meta_key_payment_id = '_edd_log_payment_id';

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

	public function __construct($post_id = FALSE, $invest_token = FALSE) {
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
	 * transfère un investissement vers une campagne
	 * @param ATCF_Campaign $to_campaign
	 */
	public function transfer($to_campaign) {
		// on mémorise l'id de la campagne de départ et d'arrivée
		$from_campaign_id = $this->get_saved_campaign()->ID;
		$to_campaign_id = $to_campaign->ID;

		// on mémorise tous les paiements de la campagne de départ
		$payments = edd_get_payments( array(
			'number'	 => -1,
			'download'   => $from_campaign_id
		) );

		// on mémorise l'id du paiement en cours
		$payment_id = $this->get_id();

		// on change la campagne du paiement en cours
		$payment_data = edd_get_payment_meta( $payment_id );
		$new_downloads = edd_get_payment_meta_downloads( $payment_id );
		if ( !is_array( $new_downloads[ 0 ] ) ) {
			$campaign_id = $new_downloads[ 0 ];
			$new_downloads[ 0 ] = $to_campaign_id;
		} else {
			if ( isset( $new_downloads[ 0 ][ 'id' ] ) ) {
				$campaign_id = $new_downloads[ 0 ][ 'id' ];
				$new_downloads[ 0 ][ 'id' ] = $to_campaign_id;
			}
		}
		$payment_data['downloads'] = $new_downloads;
		$payment_data['cart_details'][ 0 ][ 'name' ] = $to_campaign->get_name();
		$payment_data['cart_details'][ 0 ][ 'id' ] = $to_campaign_id;
		$payment_data['cart_details'][ 0 ][ 'item_number' ][ 'id' ] = $to_campaign_id;
		// sécurité
		if ($campaign_id == $from_campaign_id) {
			// Donnée investissement sur site : table postmeta : modifier l'identifiant du projet WP dans les meta (_edd_payment_meta).
			update_post_meta($payment_id, '_edd_payment_meta', $payment_data);
			// on met à jour le post d'investissement
			$log_post_items = get_posts(array(
				'post_type'		=> 'edd_log',
				'meta_key'		=> '_edd_log_payment_id',
				'meta_value'	=> $payment_id
			));
			foreach ( $log_post_items as $log_post_item ) {
				$postdata = array(
					'ID'			=> $log_post_item->ID,
					'post_parent'	=> $to_campaign_id
				);
				wp_update_post($postdata);
			}

			// Donnée investissement sur API : table entity_investment : modifier la donnée "project" avec l'ID API du nouveau projet
			$payment = FALSE;
			if ( $payments ) {
				foreach ( $payments as $payment_item ) {
					if ( $payment_item->ID == $this->id ) {
						$payment = $payment_item;
					}
				}
			}

			if ( !empty( $payment ) ) {
				$user_info = edd_get_payment_meta_user_info( $payment->ID );
				$user_id = (isset( $user_info['id'] ) && $user_info['id'] != -1) ? $user_info['id'] : $user_info['email'];
				WDGWPREST_Entity_Investment::create_or_update( $to_campaign, $payment, $user_id, edd_get_payment_status( $payment, true ) );
			}

			// déplacement du fichier de contrat d'un dossier de projet à l'autre
			$filename = WDGInvestmentContract::get_and_create_path_for_campaign( $this->get_saved_campaign() ) . $payment_id . '.pdf';
			$new_filename = WDGInvestmentContract::get_and_create_path_for_campaign( $to_campaign ) . $payment_id . '.pdf';
			rename( $filename, $new_filename );
		}
	}
	/**
	 * coupe un investissement en 2 et transfère la valeur de amount vers une campagne
	 * @param ATCF_Campaign $to_campaign
	 * @param int $amount
	 */
	public function cut_and_transfer($to_campaign, $amount) {
		// on mémorise l'id de la campagne de départ et d'arrivée
		$from_campaign_id = $this->get_saved_campaign()->ID;
		$to_campaign_id = $to_campaign->ID;

		// on crée un nouvel investissement dans la campagne de destination avec la somme manquante $amount
		$payment_key = edd_get_payment_key( $this->get_id() );
		$user_info = edd_get_payment_meta_user_info( $this->get_id() );
		$user_id = $user_info['id'];
		$orga_email = '';

		// si l'investisseur est une organisation, on récupère son email
		if ( WDGOrganization::is_user_organization( $user_id ) ) {
			$WDGOrganization = new WDGOrganization( $user_id );
			$orga_email = $WDGOrganization->get_email();
			$linked_users_creator = $WDGOrganization->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			if ( !empty( $linked_users_creator ) ) {
				$WDGUser_creator = $linked_users_creator[ 0 ];
				$user_id = $WDGUser_creator->get_wpref();
			}
		}

		$user = new WDGUser( $user_id );
		$user_email = $user->get_email();

		// la fonction add_investment créé l'investissement dans le site, dans l'API, génère le contrat et envoie un mail de notification
		$new_investment_id = $to_campaign->add_investment($payment_key, $user_email, $amount, 'publish', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $orga_email);

		if ( $new_investment_id ) {
			// on change le statut et la date du nouvel investissement
			$WDGInvestment = new WDGInvestment( $new_investment_id );
			$postdata = array(
				'ID'			=> $new_investment_id,
				'post_status'	=> 'publish',
				'post_date'		=> $this->get_saved_date(),
				'post_date_gmt'	=> $this->get_saved_date_gmt()
			);
			wp_update_post( $postdata );
			$WDGInvestment->save_to_api();
			// on conserve une trace de l'origine de ce nouveau paiement
			$id_meta = add_post_meta( $new_investment_id, 'created-from-cutting', $this->get_id() );
			//si l'investissement a un identifiant de signature électronique
			$eversign_contract_id = get_post_meta( $this->get_id(), 'eversign_contract_id', TRUE );
			if ( !empty( $eversign_contract_id ) ) {
				// on reprend le même identifiant dans les post_meta avec "eversign_contract_id"
				$id_meta_eversign = add_post_meta( $new_investment_id, 'eversign_contract_id', $eversign_contract_id );
			}
			// on modifie le montant de l'investissement en cours (on soustrait $amount)
			$this->set_amount($this->get_saved_amount() - $amount);

			// on renomme l'ancien fichier de contrat pour le garder de côté
			$filename = WDGInvestmentContract::get_and_create_path_for_campaign( $this->get_saved_campaign() ) . $this->get_id() . '.pdf';
			$new_filename = WDGInvestmentContract::get_and_create_path_for_campaign( $this->get_saved_campaign() ) . '_old_' . $this->get_id() . '.pdf';
			rename( $filename, $new_filename );


			// on génère 1 contrat pour le nouvel investissement
			$new_investment_downloads = edd_get_payment_meta_downloads($new_investment_id);
			$new_investment_download_id = '';
			if (is_array($new_investment_downloads[0])) {
				$new_investment_download_id = $new_investment_downloads[0]["id"];
			} else {
				$new_investment_download_id = $new_investment_downloads[0];
			}
			getNewPdfToSign($new_investment_download_id, $new_investment_id, $user_id);
			if ( !empty( $WDGNewInvestment ) && $WDGNewInvestment->has_token() ) {
				$new_investment_contract_pdf_url = WDGInvestmentContract::get_investment_file_url( $to_campaign, $new_investment_id );
				$WDGNewInvestment->update_contract_url( $new_investment_contract_pdf_url );
			}

			// on regénère un contrat pour l'investissement en cours
			$current_investment_downloads = edd_get_payment_meta_downloads($this->id);
			$current_investment_download_id = '';
			if (is_array($current_investment_downloads[0])) {
				$current_investment_download_id = $current_investment_downloads[0]["id"];
			} else {
				$current_investment_download_id = $current_investment_downloads[0];
			}
			getNewPdfToSign($current_investment_download_id, $this->id, $user_id);
			if ( !empty( $this ) && $this->has_token() ) {
				$current_investment_contract_pdf_url = WDGInvestmentContract::get_investment_file_url( $this->get_saved_campaign(), $this->id );
				$this->update_contract_url( $current_investment_contract_pdf_url );
			}
		} else {
			ypcf_debug_log( 'WDGInvestment::cut_and_transfer erreur d\'ajout du nouvel investissement ');
		}
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
	public function update_session($amount = FALSE, $user_type = FALSE) {
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
			if ( !is_array( $downloads[0] ) ) {
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
	 * Définit la valeur de l'investissement en session
	 * A utiliser avec attention
	 */
	public function set_session_amount( $amount ) {
		$_SESSION[ 'invest_amount' ] = $amount;
		$this->session_amount = $amount;
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

	public function get_saved_date_gmt() {
		$post_invest = get_post( $this->id );

		return $post_invest->post_date_gmt;
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
		$user_id = get_post_meta( $this->get_id(), self::$payment_meta_key_user_id, TRUE );

		if ( empty( $user_id ) ) {
			$user_info = edd_get_payment_meta_user_info( $this->get_id() );
			$user_id = (isset( $user_info['id'] ) && $user_info['id'] != -1) ? $user_info['id'] : $user_info['email'];
		}

		return $user_id;
	}

	/**
	 * Retourne l'adresse mail d'investisseur liée à un investissement
	 */
	public function get_saved_user_email() {
		$user_info = edd_get_payment_meta_user_info( $this->get_id() );
		if ( !empty( $user_info ) && !empty( $user_info['email'] ) ) {
			return $user_info['email'];
		}
		return false;
	}

	/**
	 * Retourne le statut du post de paiement
	 * @return string
	 */
	public function get_saved_status() {
		$post_invest = get_post( $this->get_id() );

		return $post_invest->post_status;
	}

	public function get_payment_status() {
		if ( !empty( $this->payment_status ) ) {
			return $this->payment_status;
		}
		return $this->get_saved_status();
	}

	public function get_saved_payment_key() {
		return edd_get_payment_key( $this->get_id() );
	}

	public function get_session_save_card() {
		return ( $_SESSION[ 'save_card' ] == '1' );
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
	public function get_redirection($redirection_type, $param = '', $param2 = '') {
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
			wp_remote_post($this->token_info->notification_url, array(
					'body'		=> $parameters
				));
		}
	}

	/**
	 * Modifie le montant de l'investissement
	 * @param int $new_amount
	 */
	public function set_amount($new_amount) {
		// on sauvegarde le nouveau montant sur le site
		if ( !empty( $this->id ) ) {
			$meta = edd_get_payment_meta($this->get_id());
			edd_update_payment_meta($this->get_id(), '_edd_payment_total', $new_amount);
		}
		// on sauvegarde le nouveau montant sur l'API
		$this->save_to_api();
	}

	/**
	 * Détermine le nouveau statut de l'investissement
	 * @param string $status
	 */
	public function set_status($status) {
		if ( $this->has_token() ) {
			$this->token_info->status = $status;
			$parameters = array(
				'status' => $status
			);
			WDGWPRESTLib::call_post_wdg( 'investment/' . $this->token, $parameters );
		}
	}

	public function set_contract_status($status) {
		if ( !empty( $this->id ) ) {
			update_post_meta( $this->id, WDGInvestment::$contract_status_meta, $status );
			if ( $status == WDGInvestment::$contract_status_investment_validated && $this->get_saved_status() != 'publish' ) {
				$postdata = array(
					'ID'			=> $this->id,
					'post_status'	=> 'publish'
				);
				wp_update_post($postdata);

				$campaign = $this->get_saved_campaign();
				$this->save_to_api();
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
	public function update_contract_url($contract_url) {
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
		$wdg_current_user->save_data($this->token_info->email, $this->token_info->gender, $this->token_info->firstname, $this->token_info->lastname, $use_lastname, $this->token_info->birthday_day, $this->token_info->birthday_month, $this->token_info->birthday_year, $this->token_info->birthday_city, $birthplace_district, $birthplace_department, $birthplace_country, $this->token_info->nationality, $address_number, $address_number_complement, $this->token_info->address, $this->token_info->postalcode, $this->token_info->city, $this->token_info->country, $tax_country, '');
		$wdg_current_user->set_language( WDG_Languages_Helpers::get_current_locale_id() );
		$wdg_current_user->update_api();
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
	private function save_payment($payment_key, $mean_of_payment, $is_failed = FALSE, $amount_param = 0, $amount_by_card = 0, $lw_transaction = FALSE) {
		if ( $this->exists_payment( $payment_key ) ) {
			return 'publish';
		}

		//Récupération des bonnes informations utilisateur
		$WDGUser_current = WDGUser::current();
		$invest_type = $this->get_session_user_type();
		if ( !empty( $this->id_user_subscription ) ) {
			$WDGUser_current = new WDGUser( $this->id_user_subscription );
			$invest_type = WDGOrganization::is_user_organization( $this->id_user_subscription ) ? $this->id_user_subscription : 'user';
		}
		
		$save_user_id = $WDGUser_current->get_wpref();
		$viban_item = FALSE;
		if ( $invest_type != 'user' && !empty( $invest_type ) ) {
			$WDGOrganization = new WDGOrganization( $invest_type );
			if ( $WDGOrganization ) {
				$current_user_organization = $WDGOrganization->get_creator();
				$save_user_id = $current_user_organization->ID;
				$viban_item = $WDGOrganization->get_viban();
			}
		}
		if ( empty( $viban_item ) ) {
			$viban_item = $WDGUser_current->get_viban();
		}

		$amount = 0;
		if ( $amount_param > 0 ) {
			$amount = $amount_param;
		} else {
			$amount = $this->get_session_amount();
		}

		// GESTION DU PAIEMENT COTE EDD
		WDGInvestment::unset_session();

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
					'quantity'		=> $amount,
					'options'		=> array()
				),
				'item_price'	=> 1,
				'subtotal'		=> $amount,
				'price'			=> $amount,
				'quantity'		=> $amount
			)
		);

		$this->set_status( WDGInvestment::$status_validated );

		$payment_data = array(
			'subtotal'		=> $amount,
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

		// L'utilisateur en cours (utilisé par défaut) peut être faux si une autre personne valide un investissement (ou si fait par une tache cron)
		// Donc MAJ de l'id de author avec le vrai investisseur
		wp_update_post( array(
			'ID'			=> $payment_id,
			'post_author'	=> $save_user_id
		) );

		// Ajustements des meta gérées automatiquement
		update_post_meta( $payment_id, '_edd_payment_total', $amount );
		$this->id = $payment_id;
		update_post_meta( $payment_id, '_edd_payment_ip', $_SERVER[ 'REMOTE_ADDR' ] );
		if ( strpos( $mean_of_payment, 'wallet' ) !== FALSE ) {
			update_post_meta( $payment_id, 'amount_with_wallet', $this->get_session_amount() - $amount_by_card );
			update_post_meta( $payment_id, 'amount_with_card', $amount_by_card );
		}
		edd_record_sale_in_log( $this->campaign->ID, $payment_id );
		$log_id = $payment_id + 1; // Pas propre du tout, mais je ne vois pas d'autre moyen
		delete_post_meta( $payment_id, '_edd_payment_customer_id' );
		update_post_meta( $payment_id, '_edd_payment_user_id', $save_user_id );
		// MAJ de l'id de author avec le vrai investisseur dans le log
		wp_update_post( array(
			'ID'			=> $log_id,
			'post_author'	=> $save_user_id
		) );
		// FIN GESTION DU PAIEMENT COTE EDD

		$this->save_to_api();

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
			if ( $invest_type != 'user' && !empty( $invest_type ) ) {
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
			$buffer = ypcf_get_updated_payment_status( $payment_id, false, $lw_transaction, $this );

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
				$this->save_to_api();
			}
		}

		$this->post_token_notification();

		// Notifications
		if ( $mean_of_payment == WDGInvestment::$meanofpayment_wire ) {
			$buffer = 'pending';
			NotificationsSlack::investment_pending_wire( $payment_id );
			NotificationsAsana::investment_pending_wire( $payment_id );

			$viban_iban = '';
			$viban_bic = '';
			$viban_holder = '';
			$viban_code = '';
			if ( !empty( $viban_item ) ) {
				$viban_iban = $viban_item[ 'iban' ];
				$viban_bic = $viban_item[ 'bic' ];
				$viban_holder = $viban_item[ 'holder' ];
				//  si c'est l'iban LX par défaut, on envoie le code backup
				if ( !empty( $viban_item[ 'backup' ] ) && !empty( $viban_item[ 'backup' ][ 'lemonway_id' ] ) ){
					$viban_code = $viban_item[ 'backup' ][ 'lemonway_id' ]; 
				}
			}
			NotificationsAPI::investment_pending_wire( $WDGUser_current, $this, $this->campaign, $viban_iban, $viban_bic, $viban_holder, $viban_code );
		}

		//Si un utilisateur investit, il croit au projet
		global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";
		$users = $wpdb->get_results( "SELECT user_id FROM " .$table_jcrois. " WHERE campaign_id = " .$this->campaign->ID. " AND user_id = " . $WDGUser_current->get_wpref() );
		if ( !$users ) {
			$wpdb->insert( $table_jcrois, array(
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
	private function exists_payment($payment_key) {
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

	/**
	 * Initialise les données qui serviront pour l'investissement à partir des données présentes dans un object d'abonnement
	 */
	public function init_with_subscription_data( $subscription_id, $user_id, $campaign, $amount ) {
		$this->id_subscription = $subscription_id;
		$this->id_user_subscription = $user_id;
		$this->campaign = $campaign;
		$this->set_session_amount( $amount );
	}

	public function try_payment($meanofpayment, $save_card = FALSE, $card_type = FALSE) {
		$payment_key = FALSE;
		switch ( $meanofpayment ) {
			case WDGInvestment::$meanofpayment_wallet:
				$payment_key = $this->try_payment_wallet( $this->get_session_amount() );
				if ( !empty( $payment_key ) ) {
					$buffer = $this->save_payment( $payment_key, $meanofpayment );
				}
				break;
			case WDGInvestment::$meanofpayment_cardwallet:
				$buffer = $this->try_payment_card( TRUE, $save_card, $card_type );
				break;
			case WDGInvestment::$meanofpayment_card:
				$buffer = $this->try_payment_card( FALSE, $save_card, $card_type );
				break;
		}

		return $buffer;
	}

	public function try_payment_wallet($amount, $amount_by_card = FALSE) {
		$buffer = FALSE;

		if ( !empty( $this->id_user_subscription ) ) {
			$WDGUser_current = new WDGUser( $this->id_user_subscription );
			$invest_type = WDGOrganization::is_user_organization( $this->id_user_subscription ) ? $this->id_user_subscription : 'user';
			$campaign = $this->get_campaign();
		} else {
			$WDGUser_current = WDGUser::current();
			$invest_type = $this->get_session_user_type();
			$campaign = $this->campaign;
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
			if ( !$campaign_organization ) {
				ypcf_debug_log( 'WDGInvestment::try_payment_wallet > error -  get_organization ne renvoie rien pour '. $campaign->data->post_title. ' get_api_data("organsiation") '. $campaign->get_api_data( 'organisation' ) . '  get_api_id = ' . $campaign->get_api_id());
			}
			$WDGOrganization_campaign = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
			if ( !$WDGOrganization_campaign->check_register_campaign_lemonway_wallet() ) {
				ypcf_debug_log( 'WDGInvestment::try_payment_wallet > error - check_register_campaign_lemonway_wallet  :: get_campaign_lemonway_id = '. $WDGOrganization_campaign->get_campaign_lemonway_id());
			}

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
			NotificationsAsana::new_purchase_admin_error_wallet( $WDGUser_current, $campaign->data->post_title, $amount );
		}

		return $buffer;
	}

	private function try_payment_card($with_wallet = FALSE, $save_card = FALSE, $card_type = FALSE) {
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

		$return_url = WDG_Redirect_Engine::override_get_page_url( 'paiement-effectue' ) . '?campaign_id='. $this->campaign->ID;

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
		// Si on a demandé à enregistrer la carte
		if ( $save_card ) {
			$register_card = 1;
			$return_url .= '&savecard=1';
		}

		$error_url = $return_url . '&error=1';
		$cancel_url = $return_url . '&cancel=1';

		if ( !empty( $card_type ) && $card_type != 'other' ) {
			$result = LemonwayLib::ask_payment_registered_card( $wallet_id, $card_type, $amount );
			$purchase_key = $result->TRANS->HPAY->ID;
			$return_url .= '&response_wkToken=' . $purchase_key . '&with_registered_card=1';
			wp_redirect( $return_url );
			exit();
		} else {
			$ask_payment_webkit_url = LemonwayLib::ask_payment_webkit( $wallet_id, $amount, 0, $wk_token, $return_url, $error_url, $cancel_url, $register_card );
			if ( $ask_payment_webkit_url !== FALSE ) {
				wp_redirect( $ask_payment_webkit_url );
				exit();
			} else {
				ypcf_debug_log( 'WDGInvestment::try_payment_card > error - ' .LemonwayLib::get_last_error_code(). ' - ' .LemonwayLib::get_last_error_message() );
				array_push( $this->error, LemonwayLib::get_last_error_code(). ' - ' .LemonwayLib::get_last_error_message() );

				return FALSE;
			}
		}
	}

	/**
	 * Retour de paiement par carte
	 * @param string $mean_of_payment
	 * @return mixed
	 */
	public function payment_return($mean_of_payment) {
		$buffer = FALSE;

		if ( empty( $mean_of_payment ) ) {
			$mean_of_payment = WDGInvestment::$meanofpayment_card;
		}

		// Retour de paiement par carte
		if ( $mean_of_payment == WDGInvestment::$meanofpayment_card || $mean_of_payment == WDGInvestment::$meanofpayment_cardwallet ) {
			$payment_key = $_REQUEST[ 'response_wkToken' ];
			$input_with_registered_card = filter_input( INPUT_GET, 'with_registered_card' );
			if ( !empty( $input_with_registered_card ) ) {
				$lw_transaction_result = LemonwayLib::get_transaction_by_id( $payment_key, 'transactionId' );
				$payment_key = 'TRANSID' . $payment_key;
			} else {
				$lw_transaction_result = LemonwayLib::get_transaction_by_id( $payment_key );
			}

			if ( !$this->exists_payment( $payment_key ) ) {
				$return_cancel = filter_input( INPUT_GET, 'cancel' );
				$return_error = filter_input( INPUT_GET, 'error' );
				$is_failed = ( !empty( $return_cancel ) || !empty( $return_error ) );
				$is_failed = $is_failed || ( $lw_transaction_result->STATUS != 3 && $lw_transaction_result->STATUS != 0 );
				$amount_by_card = $lw_transaction_result->CRED;

				if ( !$is_failed ) {
					$invest_type = $this->get_session_user_type();
					$input_savecard = filter_input( INPUT_GET, 'savecard' );
					if ( $invest_type != 'user' ) {
						$WDGOrganization_debit = new WDGOrganization( $invest_type );
						$amount = min( $this->get_session_amount(), $amount_by_card + $WDGOrganization_debit->get_available_rois_amount() );
						// Sauvegarde de la date d'expiration
						if ( !empty( $input_savecard ) ) {
							$WDGOrganization_debit->save_lemonway_card_expiration_date();
						}
					} else {
						$WDGUser_current = WDGUser::current();
						$amount = min( $this->get_session_amount(), $WDGUser_current->get_lemonway_wallet_amount() );
						// Sauvegarde de la date d'expiration
						if ( !empty( $input_savecard ) ) {
							$WDGUser_current->save_lemonway_card_expiration_date();
						}
					}

					// Compléter par wallet
					$wallet_payment_key = $this->try_payment_wallet( $amount, $amount_by_card );
					if ( !empty( $wallet_payment_key ) ) {
						$payment_key .= '_' . $wallet_payment_key;
					} else {
						$payment_key .= '_wallet_FAILED';
					}
				}

				// Sauvegarde du paiement (la session est écrasée)
				$buffer = $this->save_payment( $payment_key, $mean_of_payment, $is_failed, $amount, $amount_by_card, $lw_transaction_result );

				if ( $buffer == 'failed' ) {
					$WDGUser_current = WDGUser::current();
					$this->error_item = new LemonwayLibErrors( $lw_transaction_result->INT_MSG );
					NotificationsSlack::new_purchase_admin_error( $WDGUser_current->wp_user, $this->campaign->data->post_title, $this->get_session_amount() );
					NotificationsAsana::new_purchase_admin_error( $WDGUser_current->wp_user, $lw_transaction_result->INT_MSG, $this->error_item->get_error_message(), $this->campaign->data->post_title, $this->get_session_amount(), $this->error_item->ask_restart() );

					$investment_link = WDG_Redirect_Engine::override_get_page_url( 'investir' ) . '?campaign_id=' . $this->campaign->ID . '&invest_start=1&init_invest=' . $this->get_session_amount();
					$investment_link = '<a href="'.$investment_link.'" target="_blank">'.$investment_link.'</a>';
					NotificationsAPI::investment_error( $WDGUser_current, $this, $this->campaign, $this->error_item->get_error_message( FALSE, FALSE ), $investment_link );
				}
			}

			// Retour de paiement par virement
		} elseif ( $mean_of_payment == WDGInvestment::$meanofpayment_wire ) {
			$random = rand(10000, 99999);
			$payment_key = 'wire_TEMP_' . $random;
			$this->set_status( WDGInvestment::$status_waiting_wire );
			$this->post_token_notification();
			$buffer = $this->save_payment( $payment_key, $mean_of_payment );
			WDGInvestment::unset_session();
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

	public function cancel( $payment_status = '' ) {
		$payment_id = $this->get_id();
		if ( !empty( $payment_id ) ) {
			$postdata = array(
				'ID'			=> $payment_id,
				'post_status'	=> 'failed'
			);
			wp_update_post($postdata);
			if ( !empty( $payment_status ) ) {
				$this->payment_status = $payment_status;
			}
			$this->save_to_api();

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
	public static function save_campaign_to_api($campaign) {
		if ( !empty( $campaign->ID ) ) {
			$payments = edd_get_payments( array(
				'number'	 => -1,
				'download'   => $campaign->ID
			) );

			if ( $payments ) {
				foreach ( $payments as $payment ) {					
					$user_info = edd_get_payment_meta_user_info( $payment->ID );
					$user_id = (isset( $user_info['id'] ) && $user_info['id'] != -1) ? $user_info['id'] : $user_info['email'];
					WDGWPREST_Entity_Investment::create_or_update( $campaign, $payment, $user_id,  edd_get_payment_status( $payment, true ));
				}
			}
		}
	}

	public function save_to_api() {
		$payments = edd_get_payments( array(
			'number'	 => -1,
			'download'   => $this->get_saved_campaign()->ID
		) );
		$payment = FALSE;
		if ( $payments ) {
			foreach ( $payments as $payment_item ) {
				if ( $payment_item->ID == $this->id ) {
					$payment = $payment_item;
					break;
				}
			}
		}

		if ( !empty( $payment ) ) {
			WDGWPREST_Entity_Investment::create_or_update( $this->get_saved_campaign(), $payment, $this->get_saved_user_id(), $this->payment_status, $this->id_subscription );
		}
	}
}
