<?php
/**
 * Lib de gestion des utilisateurs
 */
class WDGUser {
	public static $key_validated_general_terms_version = 'validated_general_terms_version';
	public static $key_lemonway_status = 'lemonway_status';
	public static $edd_general_terms_version = 'terms_general_version';
	public static $edd_general_terms_excerpt = 'terms_general_excerpt';
	
	/**
	 * @var WP_User 
	 */
	public $wp_user;
	private $wallet_details;
	
	protected static $_current = null;
	
	public function __construct($user_id = '') {
		if ($user_id === '') {
			$this->wp_user = wp_get_current_user();
		} else {
			$this->wp_user = new WP_User($user_id);
		}
	}
	
	/**
	 * @return WDGUser
	 */
	public static function current() {
		if ( is_null( self::$_current ) ) {
			self::$_current = new self();
		}
		return self::$_current;
	}
	
	/**
	 * Retourne un utilisateurs en découpant l'id transmis par LW
	 * @param int $lemonway_id
	 */
	public static function get_by_lemonway_id( $lemonway_id ) {
		$buffer = FALSE;
		
		// USER : 'USERW'.$this->wp_user->ID; ORGA : 'ORGA'.$this->bopp_id.'W'.$this->wpref;
		$wp_user_id_start = strpos( $lemonway_id, 'W' );
		if ( $wp_user_id_start !== FALSE ) {
			$wp_user_id_start++;
			$wp_user_id = substr( $lemonway_id, $wp_user_id_start );
			$buffer = new WDGUser( $wp_user_id );
		}

		return $buffer;
	}
	
/*******************************************************************************
 * Accès aux données classiques
*******************************************************************************/
	public function get_wpref() {
		return $this->wp_user->ID;
	}
	
	public static $key_api_id = 'id_api';
	public function get_api_id() {
		$api_user_id = get_user_meta( $this->get_wpref(), WDGUser::$key_api_id, TRUE );
		if ( empty($api_user_id) ) {
			$user_create_result = WDGWPREST_Entity_User::create( $this );
			$api_user_id = $user_create_result->id;
			ypcf_debug_log('WDGUser::get_api_id > ' . $api_user_id);
			update_user_meta( $this->get_wpref(), WDGUser::$key_api_id, $api_user_id );
		}
		return $api_user_id;
		
	}
	
	public function get_metadata( $key ) {
		if ( !empty( $key ) ) {
			return $this->wp_user->get( 'user_' . $key );
		}
	}
	
	public function get_login() {
		return $this->wp_user->user_login;
	}
	
	public function get_api_login() {
		return $this->get_metadata( 'api_login' );
	}
	
	public function get_api_password() {
		return $this->get_metadata( 'api_password' );
	}
	
	public function get_email() {
		return $this->wp_user->user_email;
	}
	
	public function get_gender() {
		return $this->wp_user->get('user_gender');
	}
	
	public function get_firstname() {
		return $this->wp_user->first_name;
	}
	
	public function get_lastname() {
		return $this->wp_user->last_name;
	}
	
	/**
	 * La nationalité est enregistrée en ISO2
	 * @param string $format
	 * @return string
	 */
	public function get_nationality( $format = '' ) {
		$buffer = $this->wp_user->get('user_nationality');
		if ( !empty( $format ) && $format == 'iso3' ) {
			// La nationalité est enregistrée au format iso2, il faut juste la convertir
			global $country_list_iso2_to_iso3;
			if ( !empty( $country_list_iso2_to_iso3[ $buffer ] ) ) {
				$buffer = $country_list_iso2_to_iso3[ $buffer ];
			}
		}
		return $buffer;
	}
	
	public function get_address() {
		return $this->wp_user->get('user_address');
	}
	
	public function get_postal_code() {
		return $this->wp_user->get('user_postal_code');
	}
	
	public function get_city() {
		return $this->wp_user->get('user_city');
	}
	
	public function get_country( $format = '' ) {
		$buffer = $this->wp_user->get('user_country');
		if ( !empty( $format ) && $format == 'iso3' ) {
			// Le pays est saisi, il faut tenter de le convertir
			global $country_list, $country_list_iso2_to_iso3;
			// D'abord, on le met en majuscule
			$upper_country = strtoupper( $buffer );
			// On le cherche en iso2
			$iso2_key = array_search( $upper_country, $country_list );
			// On le transforme en iso3
			if ( !empty( $iso2_key ) && !empty( $country_list_iso2_to_iso3[ $iso2_key ] ) ) {
				$buffer = $country_list_iso2_to_iso3[ $iso2_key ];
			}
		}
		return $buffer;
	}
	
	public function get_phone_number() {
		return $this->wp_user->get('user_mobile_phone');
	}
	
	public function get_birthday_date() {
		$birthday_day = ($this->wp_user->get('user_birthday_day') < 10) ? '0' . $this->wp_user->get('user_birthday_day') : $this->wp_user->get('user_birthday_day');
		$birthday_month = ($this->wp_user->get('user_birthday_month') < 10) ? '0' . $this->wp_user->get('user_birthday_month') : $this->wp_user->get('user_birthday_month');
		return $this->wp_user->get('user_birthday_year'). '-' .$birthday_month. '-' .$birthday_day;
	}
	
	public function get_projects_list() {
		global $WDG_cache_plugin;
		$cache_id = 'WDGUser::' .$this->get_wpref(). '::get_projects_list';
		$cache_version = 1;
		$result_cached = $WDG_cache_plugin->get_cache( $cache_id, $cache_version );
		$buffer = unserialize($result_cached);
		
		if ( empty($buffer) ) {
			$buffer = array();
			//Récupération des projets dont l'utilisateur est porteur
			$campaign_status = array('publish');
			$args = array(
				'post_type' => 'download',
				'author' => $this->get_wpref(),
				'post_status' => $campaign_status
			);
			$args['meta_key'] = 'campaign_vote';
			$args['meta_compare'] = '!='; 
			$args['meta_value'] = 'preparing';

			query_posts($args);
			if (have_posts()) {
				while (have_posts()) {
					the_post();
					array_push($buffer, get_the_ID());
				}
			}
			wp_reset_query();

			//Récupération des projets dont l'utilisateur appartient à l'équipe
			$project_list = WDGWPREST_Entity_User::get_projects_by_role( $this->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );
			if ( !empty( $project_list ) ) {
				foreach ( $project_list as $project ) {
					$post_campaign = get_post( $project->wpref );
					if ( !empty( $post_campaign ) ) {
						array_push( $buffer, $project->wpref );
					}
				}
			}
			
			$result_save = serialize($buffer);
			if ( !empty( $result_save ) ) {
				$WDG_cache_plugin->set_cache( $cache_id, $result_save, 60*60*12, $cache_version );
			}
		}
		
		return $buffer;
	}
	
	public function get_organizations_list() {
		if ( !isset( $this->organizations_list ) ) {
			$this->organizations_list = WDGWPREST_Entity_User::get_organizations_by_role( $this->get_api_id(), WDGWPREST_Entity_Organization::$link_user_type_creator );
		}
		return $this->organizations_list;
	}
	
	public function has_voted_on_campaign( $campaign_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "ypcf_project_votes";
		$hasvoted_results = $wpdb->get_results( 'SELECT id FROM '.$table_name.' WHERE post_id = '.$campaign_id.' AND user_id = '.$this->get_wpref() );
		return ( !empty( $hasvoted_results[0]->id ) );
	}
	
	public function get_amount_voted_on_campaign( $campaign_id ) {
		global $wpdb;
		$buffer = 0;
		$table_name = $wpdb->prefix . "ypcf_project_votes";
		$hasvoted_results = $wpdb->get_results( 'SELECT id, invest_sum FROM '.$table_name.' WHERE post_id = '.$campaign_id.' AND user_id = '.$this->get_wpref() );
		if ( !empty( $hasvoted_results[0]->id ) ) {
			$buffer = $hasvoted_results[0]->invest_sum;
		}
		return $buffer;
	}
	
/*******************************************************************************
 * Fonctions de sauvegarde
*******************************************************************************/
	/**
	 * Enregistre les données nécessaires pour l'investissement
	 */
	public function save_data($email, $gender, $firstname, $lastname, $birthday_day, $birthday_month, $birthday_year, $birthplace, $nationality, $address, $postal_code, $city, $country, $telephone) {
		if ( !empty( $email ) ) {
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'user_email' => $email ) );
		}
		if ( !empty( $gender ) ) {
			update_user_meta( $this->wp_user->ID, 'user_gender', $gender );
		}
		if ( !empty( $firstname ) ) {
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'first_name' => $firstname ) ) ;
		}
		if ( !empty( $lastname ) ) {
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'last_name' => $lastname ) ) ;
		}
		if ( !empty( $birthday_day ) && $birthday_day != '00' && $birthday_day > 0 ) {
			update_user_meta( $this->wp_user->ID, 'user_birthday_day', $birthday_day );
		}
		if ( !empty( $birthday_month ) && $birthday_month != '00' && $birthday_month > 0 ) {
			update_user_meta( $this->wp_user->ID, 'user_birthday_month', $birthday_month );
		}
		if ( !empty( $birthday_year ) && $birthday_year != '00' && $birthday_year > 0 ) {
			update_user_meta( $this->wp_user->ID, 'user_birthday_year', $birthday_year );
		}
		if ( !empty( $birthplace ) ) {
			update_user_meta( $this->wp_user->ID, 'user_birthplace', $birthplace );
		}
		if ( !empty( $nationality ) ) {
			update_user_meta( $this->wp_user->ID, 'user_nationality', $nationality );
		}
		if ( !empty( $address ) ) {
			update_user_meta( $this->wp_user->ID, 'user_address', $address );
		}
		if ( !empty( $postal_code ) ) {
			update_user_meta( $this->wp_user->ID, 'user_postal_code', $postal_code );
		}
		if ( !empty( $city ) ) {
			update_user_meta( $this->wp_user->ID, 'user_city', $city );
		}
		if ( !empty( $country ) ) {
			update_user_meta( $this->wp_user->ID, 'user_country', $country );
		}
		if ( !empty( $telephone ) ) {
			update_user_meta( $this->wp_user->ID, 'user_mobile_phone', $telephone );
		}
	}
	
	/**
	 * Enregistre les données de base d'un utilisateur
	 * @param string $email
	 * @param string $firstname
	 * @param string $lastname
	 */
	public function save_basics( $email, $firstname, $lastname ) {
		wp_update_user( array ( 'ID' => $this->wp_user->ID, 'user_email' => $email ) );
		wp_update_user( array ( 'ID' => $this->wp_user->ID, 'first_name' => $firstname ) ) ;
		wp_update_user( array ( 'ID' => $this->wp_user->ID, 'last_name' => $lastname ) ) ;
	}
	
	/**
	 * Enregistre une meta particulière
	 * @param string $meta_name
	 * @param string $meta_value
	 */
	public function save_meta( $meta_name, $meta_value ) {
		update_user_meta( $this->wp_user->ID, $meta_name, $meta_value );
	}
	
/*******************************************************************************
 * Fonctions meta
*******************************************************************************/
	/**
	 * Détermine si l'utilisateur est admin
	 * @return boolean
	 */
	public function is_admin() {
		return ($this->wp_user->has_cap('manage_options'));
	}
	
	/**
	 * Détermine si l'utilisateur a un accès direct à l'API
	 */
	public function has_access_to_api() {
		$api_login = $this->get_api_login();
		$api_password = $this->get_api_password();
		return ( !empty( $api_login ) && !empty( $api_password ) );
	}
	
	/**
	 * Détermine l'age de l'utilisateur
	 * @return int
	 */
	public function get_age() {
		$day = $this->wp_user->get('user_birthday_day');
		$month = $this->wp_user->get('user_birthday_month');
		$year = $this->wp_user->get('user_birthday_year');
		if ( !empty( $day ) && !empty( $month ) && !empty( $year ) ) {
			$today_day = date('j');
			$today_month = date('n');
			$today_year = date('Y');
			$years_diff = $today_year - $year;
			if ($today_month <= $month) {
				if ($month == $today_month) {
					if ($day > $today_day) $years_diff--;
				} else {
					$years_diff--;
				}
			}
		} else {
			$years_diff = 0;
		}
		return $years_diff;
	}
	
	/**
	 * Détermine si l'utilisateur est majeur
	 * @return boolean
	 */
	public function is_major() {
		$age = $this->get_age();
		return ( !empty( $age ) && $age >= 18 );
	}
	
	/**
	 * Détermine si l'utilisateur a rempli ses informations nécessaires pour investir
	 * @param string $campaign_funding_type
	 * @return boolean
	 */
	public function has_filled_invest_infos($campaign_funding_type) {
		global $user_can_invest_errors;
		$user_can_invest_errors = array();
		
		//Infos nécessaires pour tout type de financement
		if ($this->wp_user->user_firstname == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre pr&eacute;nom.', 'yproject')); }
		if ($this->wp_user->user_lastname == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre nom.', 'yproject')); }
		if ($this->wp_user->user_email == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre e-mail.', 'yproject')); }
		if ($this->wp_user->get('user_nationality') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre nationalit&eacute;.', 'yproject')); }
		if ($this->wp_user->get('user_birthday_day') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre jour de naissance.', 'yproject')); }
		if ($this->wp_user->get('user_birthday_month') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre mois de naissance.', 'yproject')); }
		if ($this->wp_user->get('user_birthday_year') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre ann&eacute;e de naissance.', 'yproject')); }
		
		//Infos nécessaires pour l'investissement
		if ($campaign_funding_type != 'fundingdonation') {
			if (!$this->is_major()) { array_push($user_can_invest_errors, __('Seules les personnes majeures peuvent investir.', 'yproject')); }
			if ($this->wp_user->get('user_address') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre adresse pour investir.', 'yproject')); }
			if ($this->wp_user->get('user_postal_code') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre code postal pour investir.', 'yproject')); }
			if ($this->wp_user->get('user_city') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre ville pour investir.', 'yproject')); }
			if ($this->wp_user->get('user_country') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre pays pour investir.', 'yproject')); }
			if ($this->wp_user->get('user_birthplace') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre ville de naissance pour investir.', 'yproject')); }
			if ($this->wp_user->get('user_gender') == "") { array_push($user_can_invest_errors, __('Vous devez renseigner votre sexe pour investir.', 'yproject')); }
		}
		
		return (empty($user_can_invest_errors));
	}
	
	/**
	 * Retourne true si on doit afficher une lightbox de mise à jour des informations de l'utilisateur
	 * @return boolean
	 */
	public function get_show_details_confirmation() {
		$buffer = false;
		
		$last_details_confirmation = $this->wp_user->get( 'last_details_confirmation' );
		// Si ça n'a jamais été fait, on demande validation e-mail, prénom et nom
		if ( empty( $last_details_confirmation ) ) {
			$buffer = WDG_Form_User_Details::$type_basics;
			
		} else {
			
			$current_date_time = new DateTime();
			$last_confirmation_date_time = new DateTime( $last_details_confirmation );
			$date_diff = $current_date_time->diff( $last_confirmation_date_time );
			$email = $this->get_email();
			$firstname = $this->get_firstname();
			$lastname = $this->get_lastname();
			
			// Si ça fait plus de 7 jours et qu'il n'y a pas d'adresse e-mail, de prénom ou de nom
			if ( $date_diff->days > 7 && ( empty( $email ) || empty( $firstname ) || empty( $lastname ) ) ) {
				$buffer = WDG_Form_User_Details::$type_basics;
				
			// Si ça fait plus de 180 jours (6 mois), on demande une vérification complète des informations
			} else if ( $date_diff->days > 180 ) {
				$buffer = WDG_Form_User_Details::$type_complete;
			}
		}
		
		return $buffer;
	}
	
	public function update_last_details_confirmation() {
		$current_date = new DateTime();
		update_user_meta( $this->get_wpref(), 'last_details_confirmation', $current_date->format( 'Y-m-d' ) );
	}
	
/*******************************************************************************
 * Gestion investissements
*******************************************************************************/
	/**
	 * Retourne les ID d'investissements d'un utilisateur, triés par ID de projets ; filtré selon statut de l'utilisateur
	 */
	public function get_investments( $payment_status ) {
		$buffer = array();
		$purchases = edd_get_users_purchases( $this->wp_user->ID, -1, false, $payment_status );
		
		if ( !empty($purchases) ) {
			foreach ( $purchases as $purchase_post ) { /*setup_postdata( $post );*/
				$downloads = edd_get_payment_meta_downloads( $purchase_post->ID ); 
				$download_id = '';
				if ( !is_array( $downloads[0] ) ){
					$download_id = $downloads[0];
					if ( !isset($buffer[$download_id]) ) {
						$buffer[$download_id] = array();
					}
					array_push( $buffer[$download_id], $purchase_post->ID );
				}
			}
		}
			
		return $buffer;
	}
	
	/**
	 * Retourne les ID d'investissements valides d'un utilisateur, triés par ID de projets
	 */
	public function get_validated_investments() {
		$payment_status = array( "publish", "completed" );
		return $this->get_investments( $payment_status );
	}
	
	/**
	 * Retourne les ID d'investissements en attente d'un utilisateur, triés par ID de projets
	 */
	public function get_pending_investments() {
		$payment_status = array( "pending" );
		return $this->get_investments( $payment_status );
	}
	
	/**
	 * Gestion des pré-investissements
	 */
	private $pending_preinvestments;
	private function get_pending_preinvestments() {
		if ( !isset( $this->pending_preinvestments ) ) {
			$this->pending_preinvestments = array();
			$pending_investments = $this->get_pending_investments();
			foreach ( $pending_investments as $campaign_id => $campaign_investments ) {
				$investment_campaign = new ATCF_Campaign( $campaign_id );
				if ( $investment_campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
					foreach ( $campaign_investments as $investment_id ) {
						$wdg_investment = new WDGInvestment( $investment_id );
						if ( $wdg_investment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated ) {
							array_push( $this->pending_preinvestments, $wdg_investment );
						}
					}
				}
			}
		}
		return $this->pending_preinvestments;
	}
	
	public function get_first_pending_preinvestment() {
		$buffer = FALSE;
		if ( $this->has_pending_preinvestments() ) {
			$pending_preinvestments = $this->get_pending_preinvestments();
			$buffer = $pending_preinvestments[0];
		}
		return $buffer;
	}
	
	public function has_pending_preinvestments() {
		$pending_preinvestments = $this->get_pending_preinvestments();
		return ( !empty( $pending_preinvestments ) );
	}
	
/*******************************************************************************
 * Gestion royalties
*******************************************************************************/
	private $rois;
	public function get_rois() {
		if ( !isset( $this->rois ) ) {
			$this->rois = WDGWPREST_Entity_User::get_rois( $this->get_api_id() );
		}
		return $this->rois;
	}
	
	private $royalties_per_year;
	/**
	 * Retourne la liste des royalties d'une année
	 * @param int $year
	 * @return array
	 */
	public function get_royalties_for_year( $year ) {
		if ( !isset( $this->royalties_per_year ) ) {
			$this->royalties_per_year = array();
		}
		if ( !isset( $this->royalties_per_year[ $year ] ) ) {
			$this->royalties_per_year[ $year ] = array();
			$rois = $this->get_rois();
			foreach ( $rois as $roi_item ) {
				$roi_date_transfer = new DateTime( $roi_item->date_transfer );
				if ( $roi_date_transfer->format('Y') == $year ) {
					array_push( $this->royalties_per_year[ $year ], $roi_item );
				}
			}
		}
		
		return $this->royalties_per_year[ $year ];
	}
	
	/**
	 * Retourne la liste des royalties par campagne
	 * @param int $campaign_id
	 * @return array
	 */
	public function get_royalties_by_campaign_id( $campaign_id ) {
		$buffer = array();
		$campaign_api_id = get_post_meta( $campaign_id, ATCF_Campaign::$key_api_id, TRUE );
		$rois = $this->get_rois();
		foreach ( $rois as $roi_item ) {
			if ( $roi_item->id_project == $campaign_api_id ) {
				array_push( $buffer, $roi_item );
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne TRUE si l'utilisateur a reçu des royalties pour l'année en paramètre
	 * @param int $year
	 * @return boolean
	 */
	public function has_royalties_for_year( $year ) {
		$royalties_list = $this->get_royalties_for_year( $year );
		return ( count( $royalties_list ) > 0 );
	}
	
	/**
	 * Retourne le nom du fichier de certificat
	 * @return string
	 */
	private function get_royalties_yearly_certificate_filename( $year ) {
		$buffer = 'certificate-roi-' .$year. '-user-' .$this->wp_user->id. '.pdf';
		return $buffer;
	}
	
	/**
	 * Retourne le lien vers l'attestation de royalties d'une année
	 * - Si le fichier n'existe pas, crée le fichier auparavant
	 * @param int $year
	 * @return string
	 */
	public function get_royalties_certificate_per_year( $year, $force = false ) {
		$filename = $this->get_royalties_yearly_certificate_filename( $year );
		$buffer = home_url() . '/wp-content/plugins/appthemer-crowdfunding/files/certificate-roi-yearly-user/' . $filename;
		$filepath = __DIR__ . '/../../files/certificate-roi-yearly-user/' . $filename;
		if ( !$force && file_exists( $filepath ) ) {
			return $buffer;
		}
		
		$invest_list = array();
		$roi_list = array();
		$roi_number = 0;
		$roi_total = 0;
		$royalties_list = $this->get_royalties_for_year( $year );
		foreach ( $royalties_list as $wdg_roi ) {
			$roi_item = array();
			if ( $wdg_roi->id_investment > 0 ) {
				array_push( $invest_list, $wdg_roi->id_investment );
			}
			$wdg_organization = new WDGOrganization( $wdg_roi->id_orga );
			$wdg_roi_declaration = new WDGROIDeclaration( $wdg_roi->id_declaration );
			$roi_item[ 'organization_name' ] = $wdg_organization->get_name();
			$roi_item[ 'trimester_months' ] = '';
			$month_list = $wdg_roi_declaration->get_month_list();
			foreach ( $month_list as $month_item ) {
				if ( !empty( $roi_item[ 'trimester_months' ] ) ) {
					$roi_item[ 'trimester_months' ] .= ', ';
				}
				$roi_item[ 'trimester_months' ] .= $month_item;
			}
			
			$date_transfer = new DateTime( $wdg_roi->date_transfer );
			$roi_item[ 'date' ] = $date_transfer->format('d/m/Y');
			$roi_item[ 'amount' ] = UIHelpers::format_number( $wdg_roi->amount ) . ' &euro;';
			$roi_number++;
			$roi_total += $wdg_roi->amount;
			array_push( $roi_list, $roi_item );
		}
		
		global $country_list;
		$investment_list = array();
		$invest_list_unique = array_unique( $invest_list );
		foreach ( $invest_list_unique as $invest_id ) {
			$invest_item = array();
			
			$invest_item['organization_name'] = '';
			$invest_item['organization_id'] = '';
			$downloads = edd_get_payment_meta_downloads( $invest_id );
			if ( !is_array( $downloads[0] ) ){
				$campaign = atcf_get_campaign( $downloads[0] );
				$campaign_organization = $campaign->get_organization();
				$wdg_organization = new WDGOrganization( $campaign_organization->wpref );
				$invest_item['organization_name'] = $wdg_organization->get_name();
				$organization_country = $country_list[ $wdg_organization->get_nationality() ];
				$invest_item['organization_address'] = $wdg_organization->get_address(). ' ' .$wdg_organization->get_postal_code(). ' ' .$wdg_organization->get_city(). ' ' .$organization_country;
				$invest_item['organization_id'] = $wdg_organization->get_idnumber();
				$invest_item['organization_vat'] = $wdg_organization->get_vat();
			}
			
			$date_invest = new DateTime( get_post_field( 'post_date', $invest_id ) );
			$invest_item['date'] = $date_invest->format('d/m/Y');
			$invest_item['amount'] = UIHelpers::format_number( edd_get_payment_amount( $invest_id ) ) . ' &euro;';
			array_push( $investment_list, $invest_item );
		}
 		
		$info_yearly_certificate = apply_filters( 'the_content', WDGROI::get_parameter( 'info_yearly_certificate' ) );
		
		require __DIR__. '/../control/templates/pdf/certificate-roi-yearly-user.php';
		$html_content = WDG_Template_PDF_Certificate_ROI_Yearly_User::get(
			'',
			'',
			'',
			$this->get_firstname(). ' ' .$this->get_lastname(),
			$this->get_email(),
			$this->get_address(),
			$this->get_postal_code(),
			$this->get_city(),
			'01/01/'.($year + 1),
			$year,
			$investment_list,
			$roi_number,
			$roi_list,
			UIHelpers::format_number( $roi_total ). ' &euro;',
			$info_yearly_certificate
		);
		
		$html2pdf = new HTML2PDF( 'P', 'A4', 'fr', true, 'UTF-8', array(12, 5, 15, 8) );
		$html2pdf->WriteHTML( urldecode( $html_content ) );
		$html2pdf->Output( $filepath, 'F' );
		
		return $buffer;
	}
	
/*******************************************************************************
 * Gestion RIB
*******************************************************************************/
	public static $key_bank_holdername = "bank_holdername";
	public static $key_bank_iban = "bank_iban";
	public static $key_bank_bic = "bank_bic";
	public static $key_bank_address1 = "bank_address1";
	
	/**
	 * Retourne une info correspondante au IBAN
	 * @param string $info
	 * @return string
	 */
	public function get_iban_info( $info ) {
		return get_user_meta( $this->wp_user->ID, "bank_" . $info, TRUE);
	}
	
	/**
	 * Est-ce que le RIB est enregistré ?
	 */
	public function has_saved_iban() {
		$saved_holdername = $this->get_iban_info("holdername");
		$saved_iban = $this->get_iban_info("iban");
		return (!empty($saved_holdername) && !empty($saved_iban));
	}
	
	/**
	 * Enregistre le RIB
	 */
	public function save_iban( $holder_name, $iban, $bic, $address1, $address2 = '' ) {
		update_user_meta( $this->wp_user->ID, WDGUser::$key_bank_holdername, $holder_name );
		update_user_meta( $this->wp_user->ID, WDGUser::$key_bank_iban, $iban );
		update_user_meta( $this->wp_user->ID, WDGUser::$key_bank_bic, $bic );
		update_user_meta( $this->wp_user->ID, WDGUser::$key_bank_address1, $address1 );
		if ( !empty( $address2 ) ) {
			update_user_meta( $this->wp_user->ID, "bank_address2", $address2 );
		}
	}
	
/*******************************************************************************
 * Gestion Lemonway
*******************************************************************************/
	/**
	 * Récupère les infos sur LW, via l'ID ou via l'e-mail
	 * @param boolean $reload
	 * @param boolean $by_email
	 * @return object
	 */
	private function get_wallet_details( $reload = false, $by_email = false ) {
		if ( !isset($this->wallet_details) || empty($this->wallet_details) || $reload == true ) {
			if ( $by_email ) {
				$this->wallet_details = LemonwayLib::wallet_get_details( FALSE, $this->wp_user->user_email );
				
			} else {
				$this->wallet_details = LemonwayLib::wallet_get_details( $this->get_lemonway_id() );

				if ( isset( $this->wallet_details->EMAIL ) && $this->wallet_details->EMAIL != $this->wp_user->user_email ) {
					$this->update_lemonway();
				}
			}
		}
		return $this->wallet_details;
	}
	
	/**
	 * Enregistrement sur Lemonway
	 */
	public function register_lemonway() {
		//Vérifie que le wallet n'est pas déjà enregistré
		$wallet_details = $this->get_wallet_details();
		if ( !isset($wallet_details->NAME) || empty($wallet_details->NAME) ) {
			return LemonwayLib::wallet_register(
				$this->get_lemonway_id(),
				$this->wp_user->user_email,
				$this->get_lemonway_title(),
				$this->wp_user->user_firstname, 
				$this->wp_user->user_lastname,
				$this->get_country( 'iso3' ),
				$this->get_lemonway_phone_number(),
				$this->get_lemonway_birthdate(),
				$this->get_nationality( 'iso3' ),
				LemonwayLib::$wallet_type_payer
			);
		}
		return TRUE;
	}
	
	/**
	 * Détermine si les informations nécessaires sont remplies : mail, prénom, nom
	 */
	public function can_register_lemonway() {
		$buffer = ($this->wp_user->user_email != "")
				&& ($this->wp_user->user_firstname != "")
				&& ($this->wp_user->user_lastname != "");
		return $buffer;
	}
	
	/**
	 * Met à jour les données sur LW si nécessaire
	 */
	private function update_lemonway() {
		LemonwayLib::wallet_update(
			$this->get_lemonway_id(),
			$this->wp_user->user_email,
			$this->get_lemonway_title(),
			$this->wp_user->user_firstname, 
			$this->wp_user->user_lastname,
			$this->get_country( 'iso3' ),
			$this->get_lemonway_phone_number(),
			$this->get_lemonway_birthdate(),
			$this->get_nationality( 'iso3' )
		);
	}
	
	/**
	 * Définit l'identifiant de l'orga sur lemonway
	 * @return string
	 */
	public function get_lemonway_id() {
		// Récupération dans la BDD
		$db_lw_id = $this->wp_user->get( 'lemonway_id' );
		if ( empty( $db_lw_id ) ) {
			
			// Cross-platform
			// Si n'existe pas dans la BDD, 
			// -> on vérifie d'abord, via l'e-mail, si il existe sur LW
			$wallet_details_by_email = $this->get_wallet_details( true, true );
			if ( isset( $wallet_details_by_email->ID ) ) {
				$db_lw_id = $wallet_details_by_email->ID;
				
			} else {
				$db_lw_id = 'USERW'.$this->wp_user->ID;
				if ( defined( YP_LW_USERID_PREFIX ) ) {
					$db_lw_id = YP_LW_USERID_PREFIX . $db_lw_id;
				}
			}
			
			update_user_meta( $this->wp_user->ID, 'lemonway_id', $db_lw_id );
		}
		return $db_lw_id;
	}
	
	/**
	 * Récupère le genre de l'utilisateur, formatté pour lemonway
	 * @return string
	 */
	public function get_lemonway_title() {
		$buffer = "U";
		if ($this->wp_user->get('user_gender') == "male") {
			$buffer = "M";
		} elseif ($this->wp_user->get('user_gender') == "female") {
			$buffer = "F";
		}
		return $buffer;
	}
	
	public function get_lemonway_phone_number() {
		$phone_number = $this->get_phone_number();
		if ( !empty( $phone_number ) ) {
			$lemonway_phone_number = LemonwayLib::check_phone_number( $phone_number );
		}
		return $lemonway_phone_number;
	}
	
	public function get_lemonway_birthdate() {
		// format : dd/MM/yyyy
		$birthday_day = ($this->wp_user->get('user_birthday_day') < 10 && strlen($this->wp_user->get('user_birthday_day')) < 2) ? '0' . $this->wp_user->get('user_birthday_day') : $this->wp_user->get('user_birthday_day');
		$birthday_month = ($this->wp_user->get('user_birthday_month') < 10 && strlen($this->wp_user->get('user_birthday_month')) < 2) ? '0' . $this->wp_user->get('user_birthday_month') : $this->wp_user->get('user_birthday_month');
		$birthday_year = ( $this->wp_user->get('user_birthday_year') != '' ) ? $this->wp_user->get('user_birthday_year') : '0';
		$lemonway_birthdate = $birthday_day. '/' .$birthday_month. '/' .$birthday_year;
		return $lemonway_birthdate;
	}
	
	/**
	 * Retourne le statut de l'identification sur lemonway
	 */
	public function get_lemonway_status( $force_reload = TRUE ) {
		if ( $force_reload ) {
			$user_meta_status = get_user_meta( $this->wp_user->ID, WDGUser::$key_lemonway_status, TRUE );
			if ( $user_meta_status == LemonwayLib::$status_registered ) {
				$buffer = $user_meta_status;

			} else {
				if (!$this->can_register_lemonway()) {
					$buffer = LemonwayLib::$status_blocked;
				} else {
					$buffer = LemonwayLib::$status_ready;
					$wallet_details = $this->get_wallet_details();
					if ( isset($wallet_details->STATUS) && !empty($wallet_details->STATUS) ) {
						switch ($wallet_details->STATUS) {
							case '2':
							case '8':
								$buffer = LemonwayLib::$status_incomplete;
								break;
							case '3':
							case '9':
								$buffer = LemonwayLib::$status_rejected;
								break;
							case '6':
								$buffer = LemonwayLib::$status_registered;
								break;

							default:
							case '5':
								if ( !empty( $wallet_details->DOCS ) && !empty( $wallet_details->DOCS->DOC ) ) {
									foreach($wallet_details->DOCS->DOC as $document_object) {
										if (isset($document_object->TYPE) && $document_object->TYPE !== FALSE) {
											switch ($document_object->S) {
												case '1':
													$buffer = LemonwayLib::$status_waiting;
													break;
											}
										}
									}
								}
								break;
						}
					}
				}

				update_user_meta( $this->wp_user->ID, WDGUser::$key_lemonway_status, $buffer );
			}
		} else {
			$buffer = get_user_meta( $this->wp_user->ID, WDGUser::$key_lemonway_status, TRUE );
		}
		return $buffer;
	}
	
	/**
	 * Détermine si l'utilisateur est authentifié auprès de LW
	 * @param bool $force_reload
	 * @return bool
	 */
	public function is_lemonway_registered( $force_reload = TRUE ) {
		return ( $this->get_lemonway_status($force_reload) == LemonwayLib::$status_registered );
	}
	
	/**
	 * Retourne le montant actuel sur le compte bancaire
	 * @return number
	 */
	public function get_lemonway_wallet_amount() {
		$wallet_details = $this->get_wallet_details();
		$buffer = 0;
		if (isset($wallet_details->BAL)) {
			$buffer = $wallet_details->BAL;
		}
		return $buffer;
	}
	
	/**
	 * Détermine si l'utilisateur peut payer avec son porte-monnaie
	 * @param int $amount
	 * @param ATCF_Campaign $campaign
	 * @return bool
	 */
	public function can_pay_with_wallet( $amount, $campaign ) {
		$lemonway_amount = $this->get_lemonway_wallet_amount();
		return ($lemonway_amount > 0 && $lemonway_amount >= $amount && $campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway);
	}
	
	/**
	 * Détermine si l'utilisateur peut payer avec sa carte et son porte-monnaie
	 * @param int $amount
	 * @param ATCF_Campaign $campaign
	 * @return bool
	 */
	public function can_pay_with_card_and_wallet( $amount, $campaign ) {
		$lemonway_amount = $this->get_lemonway_wallet_amount();
		//Il faut de l'argent dans le porte-monnaie, que la campagne soit sur lemonway et qu'il reste au moins 5€ à payer par carte
		return ($lemonway_amount > 0 && $amount - $lemonway_amount > 5 && $campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway);
	}
	
	/**
	 * Transfère l'argent du porte-monnaie utilisateur vers son compte bancaire
	 */
	public function transfer_wallet_to_bankaccount( $amount = FALSE ) {
		$buffer = FALSE;
		
		//Il faut qu'un iban ait déjà été enregistré
		if ($this->has_saved_iban()) {
			//Vérification que des IBANS existent
			$wallet_details = $this->get_wallet_details();
			$first_iban = $wallet_details->IBANS->IBAN;
			//Sinon on l'enregistre auprès de Lemonway
			if (empty($first_iban)) {
				$saved_holdername = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_holdername, TRUE );
				$saved_iban = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_iban, TRUE );
				$saved_bic = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_bic, TRUE );
				$saved_dom1 = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_address1, TRUE );
				$result_iban = LemonwayLib::wallet_register_iban( $this->get_lemonway_id(), $saved_holdername, $saved_iban, $saved_bic, $saved_dom1 );
				if ($result_iban == FALSE) {
					$buffer = LemonwayLib::get_last_error_message();
				}
			}
			
			if ($buffer == FALSE) {
				//Exécution du transfert vers le compte du montant du solde
				if ( empty( $amount ) ) {
					$amount = $wallet_details->BAL;
				}
				$result_transfer = LemonwayLib::ask_transfer_to_iban($this->get_lemonway_id(), $amount);
				$buffer = ($result_transfer->TRANS->HPAY->ID) ? "success" : $result_transfer->TRANS->HPAY->MSG;
				if ($buffer == "success") {
					NotificationsEmails::wallet_transfer_to_account( $this->wp_user->ID, $amount );
					$withdrawal_post = array(
						'post_author'   => $this->wp_user->ID,
						'post_title'    => $amount,
						'post_content'  => print_r($result_transfer, TRUE),
						'post_status'   => 'publish',
						'post_type'	    => 'withdrawal_order_lw'
					);
					wp_insert_post( $withdrawal_post );
				}
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne true si l'iban est enregistré sur lemonway
	 */
	public function has_registered_iban() {
		$buffer = true;
		$wallet_details = $this->get_wallet_details();
		$first_iban = $wallet_details->IBANS->IBAN;
		if (empty($first_iban)) {
			$buffer = false;
		}
		return $buffer;
	}
	
/*******************************************************************************
 * Gestion Lemonway - KYC
*******************************************************************************/
	/**
	 * Détermine si l'organisation a envoyé tous ses documents en local sur WDG
	 */
	public function has_sent_all_documents() {
		$buffer = TRUE;
		$documents_type_list = array( WDGKYCFile::$type_id, WDGKYCFile::$type_home );
		foreach ( $documents_type_list as $document_type ) {
			$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wp_user->ID, WDGKYCFile::$owner_user, $document_type );
			$current_document = $document_filelist[0];
			if ( !isset($current_document) ) {
				$buffer = FALSE;
				break;
			}
		}
		return $buffer;
	}
	
	/**
	 * Upload des KYC vers Lemonway si possible
	 */
	public function send_kyc() {
		if ($this->can_register_lemonway()) {
			if ( $this->register_lemonway() ) {
				$documents_type_list = array( 
					WDGKYCFile::$type_id		=> '0', 
					WDGKYCFile::$type_home		=> '1'
				);
				foreach ( $documents_type_list as $document_type => $lemonway_type ) {
					$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wp_user->ID, WDGKYCFile::$owner_user, $document_type );
					$current_document = $document_filelist[0];
					LemonwayLib::wallet_upload_file( $this->get_lemonway_id(), $current_document->file_name, $lemonway_type, $current_document->get_byte_array() );
				}
			}
		}
	}
    
/*******************************************************************************
 * Fonctions statiques
*******************************************************************************/
	/**
	 * Vérifie si l'utilisateur a bien validé les cgu
	 * @global type $edd_options
	 * @global type $current_user
	 * @param type $user_id
	 * @return type
	 */
	public static function has_validated_general_terms($user_id = FALSE) {
		global $edd_options;
		if ($user_id === FALSE) {
			global $current_user;
			$user_id = $current_user->ID;
		}
		$current_signed_terms = get_user_meta($user_id, WDGUser::$key_validated_general_terms_version, TRUE);
		return ($current_signed_terms == $edd_options[WDGUser::$edd_general_terms_version]);
	}
	
	/**
	 * Vérifie si le formulaire est complet et valide les cgu
	 * @global type $edd_options
	 * @global type $current_user
	 * @param type $user_id
	 * @return boolean
	 */
	public static function check_validate_general_terms($user_id = FALSE) {
		//Vérification des champs de formulaire
		if (WDGUser::has_validated_general_terms($user_id)) return FALSE;
		if (!isset($_POST['action']) || $_POST['action'] != 'validate-terms') return FALSE;
		if (!isset($_POST['validate-terms-check']) || !$_POST['validate-terms-check']) return FALSE;
			    
		global $edd_options;
		if ($user_id === FALSE) {
			global $current_user;
			$user_id = $current_user->ID;
		}
		update_user_meta($user_id, WDGUser::$key_validated_general_terms_version, $edd_options[WDGUser::$edd_general_terms_version]);
	}
	
	/**
	 * Vérifie si il est nécessaie d'afficher la lightbox de cgu
	 * @global type $post
	 * @param type $user_id
	 * @return type
	 */
	public static function must_show_general_terms_block($user_id = FALSE) {
		global $post, $edd_options;
		if (isset($edd_options[WDGUser::$edd_general_terms_version]) && !empty($edd_options[WDGUser::$edd_general_terms_version])) $isset_general_terms = TRUE;
		//On affiche la lightbox de cgu si : l'utilisateur est connecté, il n'est pas sur la page cgu, il ne les a pas encore validées
		return (is_user_logged_in() && $post->post_name != 'cgu' && !WDGUser::has_validated_general_terms($user_id) && $isset_general_terms);
	}
	
	/**
	 * Récupération de la liste des id des projets auxquels un utilisateur est lié
	 * @param type $user_id
	 * @param type $complete
	 * @return array
	 */
	public static function get_projects_by_id($user_id, $complete = FALSE) {
		$buffer = array();
		
		//Récupération des projets dont l'utilisateur est porteur
		$campaign_status = array('publish');
		if ($complete === TRUE) {
			array_push($campaign_status, 'private');
		}
		$args = array(
			'post_type' => 'download',
			'author' => $user_id,
			'post_status' => $campaign_status
		);
		if ($complete === FALSE) {
			$args['meta_key'] = 'campaign_vote';
			$args['meta_compare'] = '!='; 
			$args['meta_value'] = ATCF_Campaign::$campaign_status_preparing;
		}
		query_posts($args);
		if (have_posts()) {
			while (have_posts()) {
				the_post();
				array_push($buffer, get_the_ID());
			}
		}
		wp_reset_query();
		
		//Récupération des projets dont l'utilisateur appartient à l'équipe
		$wdg_user = new WDGUser( $user_id );
		$project_list = WDGWPREST_Entity_User::get_projects_by_role( $wdg_user->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );
		if (!empty($project_list)) {
			foreach ($project_list as $project) {
				array_push($buffer, $project->wpref);
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Définit la page vers laquelle il faudrait rediriger l'utilisateur lors de sa connexion
	 * @global type $post
	 * @return type
	 */
	public static function get_login_redirect_page( $anchor = '' ) {
		ypcf_debug_log( 'WDGFormUsers::get_login_redirect_page' );
		global $post;
		$buffer = home_url();
		
		//Si on est sur la page de connexion ou d'identification,
		// il faut retrouver la page précédente et vérifier qu'elle est de WDG
		if ( $post->post_name == 'connexion' ) {
			ypcf_debug_log( 'WDGFormUsers::get_login_redirect_page > A1' );
			//On vérifie d'abord si cela a été passé en paramètre d'URL
			$get_redirect_page = filter_input( INPUT_GET, 'redirect-page' );
			if ( !empty( $get_redirect_page ) ) {
				ypcf_debug_log( 'WDGFormUsers::get_login_redirect_page > A2' );
				$buffer = home_url( $get_redirect_page );
				
			} else {
				//Récupération de la page précédente
				$referer_url = wp_get_referer();
				//On vérifie que l'url appartient bien au site en cours (home_url dans referer)
				if (strpos($referer_url, $buffer) !== FALSE) {

					//Si la page précédente était déjà la page connexion ou enregistrement, 
					// on tente de voir si la redirection était passée en paramètre
					if ( strpos($referer_url, '/connexion') !== FALSE ) {
						$posted_redirect_page = filter_input(INPUT_POST, 'redirect-page');
						if (!empty($posted_redirect_page)) {
							ypcf_debug_log( 'WDGFormUsers::get_login_redirect_page > A3' );
							$buffer = $posted_redirect_page;
						}

					//Sinon on peut effectivement rediriger vers la page précédente
					} else {
						//Si c'est une page projet et qu'il y a un vote en cours, on redirige vers le formulaire de vote
						$path = substr( $referer_url, strlen( home_url() ) + 1, -1 );
						$page_by_path = get_page_by_path( $path, OBJECT, 'download' );
						if ( !empty( $page_by_path->ID ) ) {
							$campaign = new ATCF_Campaign( $page_by_path->ID );
							if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote && $campaign->is_remaining_time() ) {
								$anchor = '#vote';
							}
						}
						ypcf_debug_log( 'WDGFormUsers::get_login_redirect_page > A4' );
						$buffer = $referer_url;
					}
				}
			}
			
		//Sur les autres pages
		} else {
			ypcf_debug_log( 'WDGFormUsers::get_login_redirect_page > B1' );
			//On tente de voir si une redirection n'avait pas été demandée auparavant
			$posted_redirect_page = filter_input(INPUT_POST, 'redirect-page');
			if (!empty($posted_redirect_page)) {
				ypcf_debug_log( 'WDGFormUsers::get_login_redirect_page > B2' );
				$buffer = $posted_redirect_page;
			
			//Sinon, on récupère simplement la page en cours
			} else {
				if (isset($post->ID)) {
					ypcf_debug_log( 'WDGFormUsers::get_login_redirect_page > B3' );
					$buffer = get_permalink($post->ID);
				}
			}
		}
		
		return $buffer . $anchor;
	}
}