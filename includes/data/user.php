<?php
/**
 * Lib de gestion des utilisateurs
 */
class WDGUser {
	public static $key_validated_general_terms_version = 'validated_general_terms_version';
	public static $key_lemonway_status = 'lemonway_status';
	public static $edd_general_terms_version = 'terms_general_version';
	public static $edd_general_terms_excerpt = 'terms_general_excerpt';
	
/*******************************************************************************
 * Variables statiques : clÃ©s des mÃ©tas utilisÃ©es
*******************************************************************************/
	public static $key_api_id = 'id_api';
	
	/**
	 * @var WP_User 
	 */
	public $wp_user;
	private $api_data;
	private $wallet_details;
	
	private $gender;
	private $first_name;
	private $last_name;
	private $use_last_name;
	private $login;
	private $birthday_date;
	private $birthday_city;
	private $birthday_district;
	private $birthday_department;
	private $birthday_country;
	private $nationality;
	private $address_number;
	private $address_number_complement;
	private $address;
	private $postalcode;
	private $city;
	private $country;
	private $tax_country;
	private $email;
	private $phone_number;
	private $contact_if_deceased;
	private $bank_iban;
	private $bank_bic;
	private $bank_holdername;
	private $bank_address;
	private $bank_address2;
	private $authentification_mode;
	private $signup_date;
	
	protected static $_current = null;
	
	
/*******************************************************************************
 * CrÃ©ations
*******************************************************************************/
	/**
	 * Constructeur
	 * @param int $user_id
	 */
	public function __construct( $user_id = '', $load_api_data = TRUE ) {
		// Initialisation avec l'objet WP
		if ($user_id === '') {
			$this->wp_user = wp_get_current_user();
		} else {
			$this->wp_user = new WP_User($user_id);
		}
		
		// NÃ©cessaire pour Ã©viter boucle infinie
		// Dans cette fonction, il a des appels Ã  l'API oÃ¹ on vÃ©rifie l'utilisateur en cours
		// Il ne faut pas faire ces appels Ã  l'API tant que l'inialisation n'est pas terminÃ©e
		if ( $load_api_data && !is_null( self::$_current ) ) {
			$this->construct_with_api_data();
		}
	}
	
	/**
	 * Initialisation des donnÃ©es avec les donnÃ©es de l'API
	 */
	public function construct_with_api_data() {
		$api_id = $this->get_api_id();
		
		if ( !empty( $api_id ) && !WDGOrganization::is_user_organization( $this->get_wpref() ) ) {
			if ( function_exists( 'is_user_logged_in' ) ) {
				$this->api_data = WDGWPREST_Entity_User::get( $api_id );
			
				if ( isset( $this->api_data ) ) {
					$this->gender = $this->api_data->gender;
					$this->first_name = $this->api_data->name;
					$this->last_name = $this->api_data->surname;
					$this->use_last_name = $this->api_data->surname_use;
					$this->birthday_date = $this->api_data->birthday_date;
					$this->birthday_city = $this->api_data->birthday_city;
					$this->birthday_district = $this->api_data->birthday_district;
					$this->birthday_department = $this->api_data->birthday_department;
					$this->birthday_country = $this->api_data->birthday_country;
					$this->nationality = $this->api_data->nationality;
					$this->address_number = $this->api_data->address_number;
					$this->address_number_complement = $this->api_data->address_number_comp;
					$this->address = $this->api_data->address;
					$this->postalcode = $this->api_data->postalcode;
					$this->city = $this->api_data->city;
					$this->country = $this->api_data->country;
					$this->tax_country = $this->api_data->tax_country;
					$this->email = $this->api_data->email;
					$this->phone_number = $this->api_data->phone_number;
					$this->contact_if_deceased = $this->api_data->contact_if_deceased;
					$this->bank_iban = $this->api_data->bank_iban;
					$this->bank_bic = $this->api_data->bank_bic;
					$this->bank_holdername = $this->api_data->bank_holdername;
					$this->bank_address = $this->api_data->bank_address;
					$this->bank_address2 = $this->api_data->bank_address2;
					$this->authentification_mode = $this->api_data->authentification_mode;
					$this->signup_date = $this->api_data->signup_date;
					$this->royalties_notifications = $this->api_data->royalties_notifications;
				}
			}
		}
	}
	
	/**
	 * RÃ©cupÃ©ration de l'utilisateur en cours
	 * @return WDGUser
	 */
	public static function current() {
		if ( is_null( self::$_current ) ) {
			self::$_current = new self();
			self::$_current->construct_with_api_data();
		}
		return self::$_current;
	}
	
	/**
	 * Recharge systématiquement l'utilisateur en cours
	 * @return WDGUser
	 */
	public static function reload_current() {
		self::$_current = new self();
		self::$_current->construct_with_api_data();
		return self::$_current;
	} 

	/**
	 * Retourne un utilisateurs en dÃ©coupant l'id de l'API
	 * @param int $api_id
	 */
	public static function get_by_api_id( $api_id ) {
		$buffer = FALSE;
		if ( !empty( $api_id ) ) {
			$api_data = WDGWPREST_Entity_User::get( $api_id );
			if ( !empty( $api_data->wpref ) ) {
				$buffer = new WDGUser( $api_data->wpref );
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne un utilisateurs en dÃ©coupant l'id transmis par LW
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
 * Destruction
*******************************************************************************/
	/**
	 * "Supprime" cet utilisateur
	 */
	public function delete() {
		//Si possible de supprimer, transformer en __deleted202001011212 les données importantes dans l'API et dans le site
		//Préparer une chaîne, qu'on appelle “deleted”, sous cette forme, pour conserver la date exacte de suppression : __deletedAAAAMMJJHHMM
		$deleted_string = '__deleted'.date("YmdHi");
		$id_user = $this->get_wpref();
		$email_user = $this->get_email(); 

		/* Aller dans la table wpwdg_users
			Dans le champ user_activation_key, stocker l'user_email et le display name, juste au cas où, sous cette forme user_email;display_name
			Remplacer user_login, user_pass, user_nicename, user_email, display_name par la chaine “deleted” créée ci-dessus*/
		
		global $wpdb;
		$table_name = $wpdb->prefix . "users";

		$wpdb->update( 
			$table_name, 
			array( 
				'user_login' => $deleted_string,
				'user_pass' => $deleted_string,
				'user_nicename' => $deleted_string,
				'user_email' => $deleted_string,
				'display_name' => $deleted_string,
				'user_activation_key' => $this->get_email().';'.$this->get_display_name()
			),
			array(
				'ID' => $this->get_wpref()
			)
		);
		
		/* Aller dans la table wpwdg_usermeta
			Faire une recherche par user_id, avec l'ID noté ci-dessus
			Supprimer toutes les meta sauf les 3 suivantes id_api, lemonway_id, lemonway_status*/
		$metas = get_user_meta( $this->get_wpref() );		
		foreach ( $metas as $key => $value ) {
			if ($key != 'id_api' && $key != 'lemonway_id' && $key != 'lemonway_status' ) {
				delete_user_meta( $this->get_wpref(), $key );
			} elseif ($key == 'lemonway_id') {
				// on mémorise l'id lemonway de l'utilisateur pour envoyer un mail au support de lemonway
				$lemonway_id = $value;
			}			
		}

		/*Aller dans la table wdgrestapi1524_entity_user
			Chercher l'utilisateur en mettant par wpref avec l'ID noté ci-dessus
			Remplacer les champs email, username par la chaine “deleted”créée ci-dessus
			Vider les informations de tous les autres champs SAUF id, wpref, signup_date, client_user_id, authentification_mode
		*/
		// on recharge l'utilisateur avec les données wordpress qu'on vient de modifier
		$WDGUserReload = new WDGUser( $this->get_wpref(), FALSE );
		$WDGUserReload->set_login($deleted_string);
		$WDGUserReload->set_email($deleted_string);
		// on met à jour les données de l'API
		WDGWPREST_Entity_User::update( $WDGUserReload );
		
		// on supprime les fichiers Kyc s'il y en a
		$this->delete_all_documents();

		if ( $lemonway_id ){
			// on envoie un mail à admin@wedogood.co pour informer de la suppression de l'utilisateur
			NotificationsEmails::send_wedogood_delete_order( $email_user );
			NotificationsSlack::send_wedogood_delete_order( $email_user );
		}
	}

/*******************************************************************************
 * Identification
*******************************************************************************/
	/**
	 * Retourne l'identifiant WordPress
	 * @return int
	 */
	public function get_wpref() {
		return $this->wp_user->ID;
	}
	
	/**
	 * Retourne l'id au sein de l'API
	 * @return int
	 */
	private $api_id;
	public function get_api_id() {
		if ( !isset( $this->api_id ) ) {
			if ( $this->get_wpref() == '' ) {
				return FALSE;
			}
			
			$this->api_id = get_user_meta( $this->get_wpref(), WDGUser::$key_api_id, TRUE );
			if ( empty( $this->api_id ) ) {
				$user_create_result = WDGWPREST_Entity_User::create( $this );
				$this->api_id = $user_create_result->id;
//				ypcf_debug_log('WDGUser::get_api_id > ' . $this->api_id);
				update_user_meta( $this->get_wpref(), WDGUser::$key_api_id, $this->api_id );
			}
		}
		return $this->api_id;
	}
	
	/**
	 * Retourne l'identifiant Facebook Ã©ventuellement liÃ© au compte
	 * @return int
	 */
	public function get_facebook_id() {
		return $this->wp_user->get( 'social_connect_facebook_id' );
	}
	
	/**
	 * Retourne true si l'utilisateur est identifiÃ© grÃ¢ce Ã  Facebook
	 * @return boolean
	 */
	public function is_logged_in_with_facebook() {
		$authentication_mode = $this->authentification_mode;
		if ( !empty( $authentication_mode ) ) {
			return ( $authentication_mode == 'facebook' );
		} else {
			$facebook_id = $this->get_facebook_id();
			return ( !empty( $facebook_id ) );
		}
	}
	
/*******************************************************************************
 * AccÃ¨s aux donnÃ©es standards
*******************************************************************************/
	public function get_metadata( $key ) {
		if ( !empty( $key ) ) {
			return $this->wp_user->get( 'user_' . $key );
		}
	}
	
	public function get_login() {
		$buffer = $this->login;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->user_login;
		}
		return $buffer;
	}
	
	public function set_login($login) {
		$this->login = $login;
	}

	public function get_signup_date() {
		$buffer = $this->signup_date;
		if ( empty( $buffer ) ) {
			$buffer = $this->wp_user->user_registered;
		}
		return $buffer;
	}
	
	public function get_api_login() {
		return $this->get_metadata( 'api_login' );
	}
	
	public function get_api_password() {
		return $this->get_metadata( 'api_password' );
	}
	
	public function get_email() {
		$buffer = $this->email;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->user_email;
		}
		// Si le dernier caractÃ¨re est un espace, on le supprime
		if ( substr( $buffer, -1 ) == ' ' ) {
			$buffer = substr( $buffer, 0, -1 );
		}
		return $buffer;
	}
	
	public function set_email($email) {
		$this->email = $email;
	}
	
	public function get_gender() {
		$buffer = $this->gender;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->get('user_gender');
		}
		return $buffer;
	}
	
	public function get_firstname() {
		$buffer = $this->first_name;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->first_name;
		} 
		return $buffer;
	}
	public function set_firstname($value) {
		$value = mb_convert_case( $value , MB_CASE_TITLE );
		$this->first_name = $value;
	}

	public function get_lastname() {
		$buffer = $this->last_name;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->last_name;
		} 
		return $buffer;
	}
	public function set_lastname($value) {
		$value = mb_convert_case( $value , MB_CASE_TITLE );
		$this->last_name = $value;
	}
	

	public function get_use_lastname() {
		$buffer = $this->use_last_name;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->use_last_name;
		} 
		return $buffer;
	}
	public function set_use_lastname($value) {
		$value = mb_convert_case( $value , MB_CASE_TITLE );
		$this->use_last_name = $value;
	}
	
	public function get_display_name() {
		$buffer = $this->wp_user->display_name;
		$user_firstname = $this->get_firstname();
		$user_lastname = $this->get_lastname();
		if ( !empty( $user_firstname ) && !empty( $user_lastname ) ) {
			$buffer = $user_firstname. ' ' .substr( $user_lastname, 0, 1 ). '.';
		}
		return $buffer;
	}
	
	/**
	 * La nationalitÃ© est enregistrÃ©e en ISO2
	 * @param string $format
	 * @return string
	 */
	public function get_nationality( $format = '' ) {
		$buffer = $this->nationality;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->get('user_nationality');
		}
		
		if ( !empty( $format ) && $format == 'iso3' ) {
			// La nationalitÃ© est enregistrÃ©e au format iso2, il faut juste la convertir
			global $country_list_iso2_to_iso3;
			if ( !empty( $country_list_iso2_to_iso3[ $buffer ] ) ) {
				$buffer = $country_list_iso2_to_iso3[ $buffer ];
			}
		}
		return $buffer;
	}
	
	public function get_address_number() {
		return $this->address_number;
	}
	public function get_address_number_complement() {
		return $this->address_number_complement;
	}
	public function get_address() {
		$buffer = $this->address;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->get('user_address');
		}
		return $buffer;
	}
	public function get_full_address_str() {
		$buffer = '';
		
		$address_number = $this->get_address_number();
		if ( !empty( $address_number ) && $address_number != 0 ) {
			$buffer = $address_number . ' ';
		}
		
		$address_number_complement = $this->get_address_number_complement();
		if ( !empty( $address_number_complement ) ) {
			$buffer .= $address_number_complement . ' ';
		}
		
		$buffer .= $this->get_address();
				
		return $buffer;
	}
	
	public function get_postal_code( $complete_french = false ) {
		$buffer = $this->postalcode;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->get('user_postal_code');
		}
		
		if ( $complete_french && strlen( $buffer ) == 4 ) {
			$buffer = '0' . $buffer;
		}
		return $buffer;
	}
	
	public function get_city() {
		$buffer = $this->city;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->get('user_city');
		}
		return $buffer;
	}
	
	public function get_country( $format = '' ) {
		$buffer = $this->country;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->get('user_country');
		}
		// Si le dernier caractÃ¨re est un espace, on le supprime
		if ( substr( $buffer, -1 ) == ' ' ) {
			$buffer = substr( $buffer, 0, -1 );
		}
		
		if ( !empty( $format ) ) {
			// Le pays est saisi, il faut tenter de le convertir
			global $country_list, $country_list_iso2_to_iso3, $country_translation;
			// D'abord, on le met en majuscule
			$upper_country = strtoupper( $buffer );
			if ( isset( $country_translation[ htmlentities( $upper_country ) ] ) ) {
				$upper_country = $country_translation[ htmlentities( $upper_country ) ];
			}
			
			// On le cherche en iso2
			$iso2_key = array_search( $upper_country, $country_list );
			
			if ( $format == 'iso3' ) {
				// On le transforme en iso3
				if ( !empty( $iso2_key ) && !empty( $country_list_iso2_to_iso3[ $iso2_key ] ) ) {
					$buffer = $country_list_iso2_to_iso3[ $iso2_key ];
				} else if ( !empty( $country_list_iso2_to_iso3[ $buffer ] ) ) {
					$buffer = $country_list_iso2_to_iso3[ $buffer ];
				}

			} else if ( $format == 'iso2' ) {
				if ( !empty( $iso2_key ) ) {
					$buffer = $iso2_key;
				}
				
			} else if ( $format == 'full' ) {
				if ( !empty( $iso2_key ) ) {
					$buffer = ucfirst( strtolower( $country_list[ $iso2_key ] ) );
				} else if ( !empty( $country_list[ $upper_country ] ) ) {
					$buffer = ucfirst( strtolower( $country_list[ $upper_country ] ) );
				} else {
					$buffer = ucfirst( strtolower( $upper_country ) );
				}
			}
		}
		
		return $buffer;
	}
	
	public function get_tax_country( $format = '' ) {
		$buffer = $this->tax_country;
		
		if ( !empty( $format ) && $format == 'iso3' ) {
			// Le pays d'imposition est enregistrÃ© au format iso2, il faut juste le convertir
			global $country_list_iso2_to_iso3;
			if ( !empty( $country_list_iso2_to_iso3[ $buffer ] ) ) {
				$buffer = $country_list_iso2_to_iso3[ $buffer ];
			}
		}
		return $buffer;
	}
	
	public function get_phone_number( $formatted = FALSE ) {
		$buffer = $this->phone_number;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->get('user_mobile_phone');
		}

		if ( $formatted ) {
			// Supprime tous les caractères qui ne sont pas des chiffres 
			$buffer = preg_replace( '/[^0-9]+/', '', $buffer ); 
			// Garde les 9 derniers chiffres 
			$buffer = substr( $buffer, -9 ); 
			// Ajoute +33 
			$motif = '+33\1\2\3\4\5';
			$buffer = preg_replace('/(\d{1})(\d{2})(\d{2})(\d{2})(\d{2})/', $motif, $buffer); 
		}

		return $buffer;
	}
	
	public function has_phone_number_correct() {
		$user_phone_number = $this->get_phone_number( TRUE );
		$classic_phone_number = '+33612345678';
		return ( !empty( $user_phone_number ) && strlen( $user_phone_number ) == strlen( $classic_phone_number ) );
	}
	
	private function get_local_formatted_birthday_date() {
		$buffer = FALSE;
		$birthday_day = $this->wp_user->get('user_birthday_day');
		$birthday_month = $this->wp_user->get('user_birthday_month');
		$birthday_year = $this->wp_user->get('user_birthday_year');
		if ( !empty( $birthday_day ) && !empty( $birthday_month ) && !empty( $birthday_year ) ) {
			$birthday_day = ( $birthday_day < 10 && strlen( $birthday_day ) < 2 ) ? '0' . $birthday_day : $birthday_day;
			$birthday_month = ( $birthday_month < 10 && strlen( $birthday_month ) < 2 ) ? '0' . $birthday_month : $birthday_month;
			if ( (int)$birthday_year < 100 ) {
				$birthday_year = '19' . (int)$birthday_year;
			}
			$buffer = $birthday_year. '-' .$birthday_month. '-' .$birthday_day;
		}
		return $buffer;
	}
	public function get_birthday_date() {
		$buffer = $this->birthday_date;
		if ( empty( $buffer ) || $buffer == '---' || $buffer == '0000-00-00' ) {
			$buffer = $this->get_local_formatted_birthday_date();
		}
		return $buffer;
	}
	public function get_birthday_day() {
		$birthday_date = $this->get_birthday_date();
		$birthday_datetime = new DateTime( $birthday_date );
		$buffer = $birthday_datetime->format( 'd' );
		return $buffer;
	}
	public function get_birthday_month() {
		$birthday_date = $this->get_birthday_date();
		$birthday_datetime = new DateTime( $birthday_date );
		$buffer = $birthday_datetime->format( 'm' );
		return $buffer;
	}
	public function get_birthday_year() {
		$birthday_date = $this->get_birthday_date();
		$birthday_datetime = new DateTime( $birthday_date );
		$buffer = $birthday_datetime->format( 'Y' );
		return $buffer;
	}
	
	public function get_contact_if_deceased() {
		$buffer = $this->contact_if_deceased;
		return $buffer;
	}
	
	public function get_birthplace() {
		$buffer = $this->birthday_city;
		if ( empty( $buffer ) || $buffer == '---' ) {
			$buffer = $this->wp_user->get('user_birthplace');
		}
		return $buffer;
	}
	
	public function get_birthplace_district( $formatted = FALSE ) {
		$buffer = $this->birthday_district;
		if ( $formatted ) {
			if ( $buffer < 10 ) {
				$buffer = '0' . $buffer;	
		}
		}
		return $buffer;
	}
		
	public function get_birthplace_department() {
		return $this->birthday_department;
	}
		
	public function get_birthplace_country() {
		return $this->birthday_country;
	}
	
	
	public function get_royalties_notifications() {
		$buffer = $this->royalties_notifications;
		return $buffer;
	}

	public function set_royalties_notifications($value) {
		if($this->royalties_notifications != $value) {
			$this->royalties_notifications = $value;		
			$this->update_api();
		}
	}

/*******************************************************************************
 * Préférences d'affichage de l'aide contextuelle
*******************************************************************************/
	private $removed_help_items;
	public function get_removed_help_items() {
		if ( !isset( $this->removed_help_items ) ) {
			$removed_help_items_meta = $this->wp_user->get( 'removed_help_items' );
			if ( !empty( $removed_help_items_meta ) ) {
				$this->removed_help_items = json_decode( $removed_help_items_meta );
			} else {
				$this->removed_help_items = new stdClass();
			}
		}
		return $this->removed_help_items;
	}

	public function has_removed_help_item( $item_name, $version ) {
		$removed_help_items = $this->get_removed_help_items();
		return isset( $removed_help_items->{ $item_name } ) && $removed_help_items->{ $item_name } >= $version;
	}

	public function set_removed_help_items( $item_name, $version ) {
		// Initialisation de la liste dans la variable de classe, qu'on modifie directement après
		$this->get_removed_help_items();
		$this->removed_help_items->{ $item_name } = $version;
		$removed_help_items_meta = json_encode( $this->removed_help_items );
		update_user_meta( $this->get_wpref(), 'removed_help_items', $removed_help_items_meta );
	}


/*******************************************************************************
 * Fonctions nécessitant des requetes
*******************************************************************************/
	public function get_projects_list() {
		global $WDG_cache_plugin;
		$cache_id = 'WDGUser::' .$this->get_wpref(). '::get_projects_list';
		$cache_version = 1;
		$result_cached = $WDG_cache_plugin->get_cache( $cache_id, $cache_version );
		$buffer = unserialize($result_cached);
		
		if ( empty($buffer) ) {
			$buffer = array();
			//RÃ©cupÃ©ration des projets dont l'utilisateur est porteur
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

			//RÃ©cupÃ©ration des projets dont l'utilisateur appartient Ã  l'Ã©quipe
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
	
	public function can_edit_organization( $organization_wpref ) {
		$buffer = $this->is_admin();
		if ( !$buffer ) {
			$organization_list = $this->get_organizations_list();
			foreach ( $organization_list as $organization_item ) {
				if ( $organization_wpref == $organization_item->wpref ) {
					$buffer = TRUE;
				}
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
	
	public function get_votes_with_amount() {
		global $wpdb;
		$table_name = $wpdb->prefix . "ypcf_project_votes";
		$buffer = $wpdb->get_results( 'SELECT id, post_id, invest_sum FROM '.$table_name.' WHERE user_id = '.$this->get_wpref(). ' AND invest_sum > 0' );
		return $buffer;
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
		$table_name = $wpdb->prefix . 'ypcf_project_votes';
		$hasvoted_results = $wpdb->get_results( 'SELECT id, invest_sum FROM '.$table_name.' WHERE post_id = '.$campaign_id.' AND user_id = '.$this->get_wpref() );
		if ( !empty( $hasvoted_results[0]->id ) ) {
			$buffer = $hasvoted_results[0]->invest_sum;
		}
		return $buffer;
	}
	
	private $has_invested_by_campaign;
	public function has_invested_on_campaign( $campaign_id ) {
		if ( !isset( $this->has_invested_by_campaign ) ) {
			$this->has_invested_by_campaign = array();
		}
		if ( !isset( $this->has_invested_by_campaign[ $campaign_id ] ) ) {
			$payments = edd_get_payments( array(
				'number'	=> -1,
				'download'	=> $campaign_id,
				'user'		=> $this->get_wpref()
			) );
			$this->has_invested_by_campaign[ $campaign_id ] = ( count( $payments ) > 0 );
		}
		return $this->has_invested_by_campaign[ $campaign_id ];
	}
	
	public function get_campaigns_followed() {
		$buffer = array();
		
		global $wpdb;
		$table = $wpdb->prefix . 'jycrois';
		$campaigns_followed = $wpdb->get_results( 'SELECT campaign_id FROM ' .$table. ' WHERE user_id=' .$this->get_wpref() );
		
		foreach ( $campaigns_followed as $campaign_item ) {
			$campaign = new ATCF_Campaign( $campaign_item->campaign_id );
			$buffer[ $campaign_item->campaign_id ] = $campaign->get_name();
		}
		
		return $buffer;
	}

	/**
	 * vérifie si la campagne est en cours selon son statut
	 *
	 * @return array
	 */
	public function get_campaigns_current_voted() {
		$buffer = array();		
		$campaigns_voted = $this->get_campaigns_voted();
		foreach ( $campaigns_voted as $campaign_item ) {
			if ( $campaign_item['status'] == ATCF_Campaign::$campaign_status_collecte || $campaign_item['status'] == ATCF_Campaign::$campaign_status_vote) {
				$buffer[] = $campaign_item;
			}
		}
		return $buffer;
	}
	/**
	 * renvoie la liste des identifiants des campagnes sur lesquelles il a voté
	 *
	 * @return array
	 */
	public function get_campaigns_voted() {
		$buffer = array();		
		$list_campaign = ATCF_Campaign::get_list_all( );
		foreach ( $list_campaign as $project_post ) {
			$amount_voted = $this->get_amount_voted_on_campaign( $project_post->ID );
			if ( $amount_voted > 0 && !$this->has_invested_on_campaign( $project_post->ID ) ) {
				$intention_item = array(
					'campaign_name'	=> $project_post->post_title,
					'campaign_id'	=> $project_post->ID,
					'vote_amount'	=> $amount_voted,
					'status'		=> get_post_meta( $project_post->ID, 'campaign_vote', TRUE )
				);
				array_push( $buffer, $intention_item );
			}
		}

		return $buffer;
	}
	
/*******************************************************************************
 * Fonctions de sauvegarde
*******************************************************************************/
	/**
	 * Enregistre les donnÃ©es nÃ©cessaires pour l'investissement
	 */
	public function save_data( $email, $gender, $firstname, $lastname, $use_lastname, $birthday_day, $birthday_month, $birthday_year, $birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality, $address_number, $address_number_complement, $address, $postal_code, $city, $country, $tax_country, $phone_number, $contact_if_deceased = '' ) {
		if ( !empty( $email ) ) {
			$this->email = $email;
			$this->copy_sendinblue_params_to_new_email( $this->wp_user->user_email, $email );
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'user_email' => $email ) );
		}
		if ( !empty( $firstname ) ) {
			$this->set_firstname($firstname);
			$firstname = $this->get_firstname();
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'first_name' => $firstname ) ) ;
		}
		if ( !empty( $lastname ) ) {
			$this->set_lastname($lastname);
			$lastname = $this->get_lastname();
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'last_name' => $lastname ) ) ;
		}
		if ( !empty( $use_lastname ) ) {
			$this->use_last_name = $use_lastname;
		}
		
		if ( !empty( $birthday_day ) && $birthday_day != '00' && $birthday_day > 0 ) {
			$this->save_meta( 'user_birthday_day', $birthday_day );
		}
		if ( !empty( $birthday_month ) && $birthday_month != '00' && $birthday_month > 0 ) {
			$this->save_meta( 'user_birthday_month', $birthday_month );
		}
		if ( !empty( $birthday_year ) && $birthday_year != '00' && $birthday_year > 0 ) {
			$this->save_meta( 'user_birthday_year', $birthday_year );
		}
		if ( ( !empty( $birthday_day ) && $birthday_day != '00' && $birthday_day > 0 ) 
				|| ( !empty( $birthday_month ) && $birthday_month != '00' && $birthday_month > 0 )
				|| ( !empty( $birthday_year ) && $birthday_year != '00' && $birthday_year > 0 ) ) {
			$this->birthday_date = $this->get_local_formatted_birthday_date();
		}
		
		
		if ( !empty( $gender ) ) {
			$this->gender = $gender;
			$this->save_meta( 'user_gender', $gender );
		}
		if ( !empty( $birthplace ) ) {
			$this->birthday_city = $birthplace;
			$this->save_meta( 'user_birthplace', $birthplace );
		}
		if ( !empty( $birthplace_district ) ) {
			$this->birthday_district = $birthplace_district;
		}
		if ( !empty( $birthplace_department ) ) {
			$this->birthday_department = $birthplace_department;
		}
		if ( !empty( $birthplace_country ) ) {
			$this->birthday_country = $birthplace_country;
		}
		if ( !empty( $nationality ) ) {
			$this->nationality = $nationality;
			$this->save_meta( 'user_nationality', $nationality );
		}
		if ( !empty( $address_number  ) ) {
			$this->address_number = $address_number;
		}
		if ( !empty( $address_number_complement ) ) {
			$this->address_number_complement = $address_number_complement;
		}
		if ( !empty( $address ) ) {
			$this->address = $address;
			$this->save_meta( 'user_address', $address );
		}
		if ( !empty( $postal_code ) ) {
			$this->postalcode = $postal_code;
			$this->save_meta( 'user_postal_code', $postal_code );
		}
		if ( !empty( $city ) ) {
			$this->city = $city;
			$this->save_meta( 'user_city', $city );
		}
		if ( !empty( $country ) ) {
			$this->country = $country;
			$this->save_meta( 'user_country', $country );
		}
		if ( !empty( $tax_country ) ) {
			$this->tax_country = $tax_country;
		}
		if ( !empty( $phone_number ) ) {
			$this->phone_number = $phone_number;
			$this->save_meta( 'user_mobile_phone', $phone_number );
		}
		if ( !empty( $contact_if_deceased ) ) {
			$this->contact_if_deceased = $contact_if_deceased;
		}
		
		$this->update_api();
	}
	
	/**
	 * Enregistre les donnÃ©es de base d'un utilisateur
	 * @param string $email
	 * @param string $firstname
	 * @param string $lastname
	 */
	public function save_basics( $email, $firstname, $lastname ) {
		if ( !empty( $email ) ) {
			$this->email = $email;
			$this->copy_sendinblue_params_to_new_email( $this->wp_user->user_email, $email );
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'user_email' => $email ) );
		}
		if ( !empty( $firstname ) ) {
			$this->set_firstname($firstname);
			$firstname = $this->get_firstname();
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'first_name' => $firstname ) ) ;
		}
		if ( !empty( $lastname ) ) {
			$this->set_lastname($lastname);
			$lastname = $this->get_lastname();
			wp_update_user( array ( 'ID' => $this->wp_user->ID, 'last_name' => $lastname ) ) ;
		}
		
		$this->update_api();
	}
	
	/**
	 * Enregistre une meta particuliÃ¨re
	 * @param string $meta_name
	 * @param string $meta_value
	 */
	public function save_meta( $meta_name, $meta_value ) {
		update_user_meta( $this->wp_user->ID, $meta_name, $meta_value );
	}

	public function save_phone_number( $phone_number ) {
		if ( !empty( $phone_number ) ) {
			$this->phone_number = $phone_number;
			$this->save_meta( 'user_mobile_phone', $phone_number );
			$this->update_api();
		}
	}
	
	/**
	 * Envoie les donnÃ©es sur l'API
	 */
	public function update_api() {
		WDGWPREST_Entity_User::update( $this );
	}
	
	/**
	 * DÃ©place les donnÃ©es des utilisateurs sur l'API
	 */
	public static function move_users_to_api() {
		$wpusers = get_users();
		foreach ( $wpusers as $wpuser ) {
			if ( !WDGOrganization::is_user_organization( $wpuser->ID ) ) {
				$WDGUser = new WDGUser( $wpuser->ID );
				$WDGUser->update_api();
			}
		}
	}
	
/*******************************************************************************
 * Fonctions meta
*******************************************************************************/
	/**
	 * DÃ©termine si l'utilisateur est admin
	 * @return boolean
	 */
	public function is_admin() {
		return ( $this->wp_user->has_cap( 'manage_options' ) );
	}
	
	/**
	 * DÃ©termine si l'utilisateur a un accÃ¨s direct Ã  l'API
	 */
	public function has_access_to_api() {
		$api_login = $this->get_api_login();
		$api_password = $this->get_api_password();
		return ( !empty( $api_login ) && !empty( $api_password ) );
	}
	
	/**
	 * DÃ©termine l'age de l'utilisateur
	 * @return int
	 */
	public function get_age( $ref_date = FALSE ) {
		$day = $this->get_birthday_day();
		$month = $this->get_birthday_month();
		$year = $this->get_birthday_year();
		if ( !empty( $day ) && !empty( $month ) && !empty( $year ) ) {
			if ( !empty( $ref_date ) ) {
				$ref_datetime = new DateTime( $ref_date );
				$ref_day = $ref_datetime->format( 'j' );
				$ref_month = $ref_datetime->format( 'n' );
				$ref_year = $ref_datetime->format( 'Y' );
			} else {
				$ref_day = date('j');
				$ref_month = date('n');
				$ref_year = date('Y');
			}
			$years_diff = $ref_year - $year;
			if ( $ref_month <= $month ) {
				if ( $month == $ref_month ) {
					if ( $day > $ref_day ) {
						$years_diff--;
					}
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
	 * DÃ©termine si l'utilisateur est majeur
	 * @return boolean
	 */
	public function is_major() {
		$age = $this->get_age();
		return ( !empty( $age ) && $age >= 18 );
	}
	
	/**
	 * DÃ©termine si l'utilisateur a rempli ses informations nÃ©cessaires pour investir
	 * @param string $campaign_funding_type
	 * @return boolean
	 */
	public function has_filled_invest_infos($campaign_funding_type) {
		global $user_can_invest_errors;
		$user_can_invest_errors = array();
		
		//Infos nÃ©cessaires pour tout type de financement
		if ( $this->get_firstname() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre pr&eacute;nom.', 'yproject')); }
		if ( $this->get_lastname() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre nom.', 'yproject')); }
		if ( $this->get_email() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre e-mail.', 'yproject')); }
		if ( $this->get_nationality() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre nationalit&eacute;.', 'yproject')); }
		if ( $this->get_birthday_day() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre jour de naissance.', 'yproject')); }
		if ( $this->get_birthday_month() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre mois de naissance.', 'yproject')); }
		if ( $this->get_birthday_year() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre ann&eacute;e de naissance.', 'yproject')); }
		
		//Infos nÃ©cessaires pour l'investissement
		if ( $campaign_funding_type != 'fundingdonation' ) {
			if ( !$this->is_major() ) { array_push($user_can_invest_errors, __('Seules les personnes majeures peuvent investir.', 'yproject')); }
			if ( $this->get_address() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre adresse pour investir.', 'yproject')); }
			if ( $this->get_postal_code() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre code postal pour investir.', 'yproject')); }
			if ( $this->get_city() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre ville pour investir.', 'yproject')); }
			if ( $this->get_country() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre pays pour investir.', 'yproject')); }
			if ( $this->get_birthplace() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre ville de naissance pour investir.', 'yproject')); }
			if ( $this->get_gender() == "" ) { array_push($user_can_invest_errors, __('Vous devez renseigner votre sexe pour investir.', 'yproject')); }
		}
		
		return (empty($user_can_invest_errors));
	}
	
	/**
	 * Retourne true si on doit afficher une lightbox de mise Ã  jour des informations de l'utilisateur
	 * @return boolean
	 */
	public function get_show_details_confirmation() {
		$buffer = false;
		
		$last_details_confirmation = $this->wp_user->get( 'last_details_confirmation' );
		// Si Ã§a n'a jamais Ã©tÃ© fait, on demande validation e-mail, prÃ©nom et nom
		if ( empty( $last_details_confirmation ) ) {
			$buffer = WDG_Form_User_Details::$type_basics;
			
		} else {
			
			$current_date_time = new DateTime();
			$last_confirmation_date_time = new DateTime( $last_details_confirmation );
			$date_diff = $current_date_time->diff( $last_confirmation_date_time );
			$email = $this->get_email();
			$firstname = $this->get_firstname();
			$lastname = $this->get_lastname();
			
			// Si Ã§a fait plus de 7 jours et qu'il n'y a pas d'adresse e-mail, de prÃ©nom ou de nom
			if ( $date_diff->days > 7 && ( empty( $email ) || empty( $firstname ) || empty( $lastname ) ) ) {
				$buffer = WDG_Form_User_Details::$type_basics;
				
			// Si Ã§a fait plus de 180 jours (6 mois), on demande une vÃ©rification complÃ¨te des informations
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
	private $user_investments;
	/**
	 * @return WDGUserInvestments
	 */
	private function get_user_investments_object() {
		if ( !isset( $this->user_investments ) ) {
			$this->user_investments = new WDGUserInvestments( $this );
		}
		return $this->user_investments;
	}
	
	public function get_investments( $payment_status ) {
		return $this->get_user_investments_object()->get_investments( $payment_status );
	}
	public function get_validated_investments() {
		return $this->get_user_investments_object()->get_validated_investments();
	}
	
	public function get_pending_investments() {
		return $this->get_user_investments_object()->get_pending_investments();
	}
	
	public function get_pending_not_validated_investments() {
		return $this->get_user_investments_object()->get_pending_not_validated_investments();
	}
	public function get_first_pending_not_validated_investment() {
		return $this->get_user_investments_object()->get_first_pending_not_validated_investment();
	}
	public function has_pending_not_validated_investments() {
		return $this->get_user_investments_object()->has_pending_not_validated_investments();
	}
	
	public function get_pending_preinvestments( $force_reload = FALSE ) {
		return $this->get_user_investments_object()->get_pending_preinvestments( $force_reload );
	}
	public function get_first_pending_preinvestment() {
		return $this->get_user_investments_object()->get_first_pending_preinvestment();
	}
	public function has_pending_preinvestments() {
		return $this->get_user_investments_object()->has_pending_preinvestments();
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
	
	public function get_rois_amount() {
		$buffer = 0;
		$rois = $this->get_rois();
		if ( !empty( $rois ) ) {
			foreach ( $rois as $roi_item ) {
				if ( $roi_item->status == WDGROI::$status_transferred ) {
					$buffer += $roi_item->amount;
				}
			}
		}
		return $buffer;
	}
	
	public function get_pending_rois_amount() {
		$buffer = 0;
		$rois = $this->get_rois();
		if ( !empty( $rois ) ) {
			foreach ( $rois as $roi_item ) {
				if ( $roi_item->status == WDGROI::$status_waiting_authentication ) {
					$buffer += $roi_item->amount;
				}
			}
		}
		return $buffer;
	}
	
	private $royalties_per_year;
	/**
	 * Retourne la liste des royalties d'une annÃ©e
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
				if ( $roi_date_transfer->format('Y') == $year && $roi_item->status == WDGROI::$status_transferred ) {
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
	public function get_royalties_by_campaign_id( $campaign_id, $campaign_api_id = FALSE ) {
		$buffer = array();
		
		if ( empty( $campaign_api_id ) ) {
			$campaign_api_id = get_post_meta( $campaign_id, ATCF_Campaign::$key_api_id, TRUE );
		}
		
		$rois = $this->get_rois();
		foreach ( $rois as $roi_item ) {
			if ( $roi_item->id_project == $campaign_api_id && $roi_item->status == WDGROI::$status_transferred ) {
				array_push( $buffer, $roi_item );
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne la liste des royalties par id d'investissement
	 * @return array
	 */
	public function get_royalties_by_investment_id( $investment_id, $status = 'transferred' ) {
		$buffer = array();
		$rois = $this->get_rois();
		foreach ( $rois as $roi_item ) {
			if ( $roi_item->id_investment == $investment_id ) {
				if ( empty( $status ) || $roi_item->status == $status ) {
					array_push( $buffer, $roi_item );
				}
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne la liste des royalties par id de contrat d'investissement
	 * @return array
	 */
	public function get_royalties_by_investment_contract_id( $investment_contract_id, $status = 'transferred' ) {
		$buffer = array();
		$rois = $this->get_rois();
		foreach ( $rois as $roi_item ) {
			if ( $roi_item->id_investment_contract == $investment_contract_id ) {
				if ( empty( $status ) || $roi_item->status == $status ) {
					array_push( $buffer, $roi_item );
				}
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne TRUE si l'utilisateur a reÃ§u des royalties pour l'annÃ©e en paramÃ¨tre
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
	 * Retourne le lien vers l'attestation de royalties d'une annÃ©e
	 * - Si le fichier n'existe pas, crÃ©e le fichier auparavant
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
		
		global $country_list;
		$invest_list = array();
		$roi_total = 0;
		$taxed_total = 0;
		
		// Récupération d'abord de la liste des royalties de l'année pour ne faire un récapitulatif que pour ceux-là
		$royalties_list = $this->get_royalties_for_year( $year );
		foreach ( $royalties_list as $roi_item ) {
			if ( $roi_item->id_investment > 0 ) {
				array_push( $invest_list, $roi_item->id_investment );
			}
		}
		$invest_list_unique = array_unique( $invest_list );
		
		// Parcours de la liste des investissements
		$investment_list = array();
		foreach ( $invest_list_unique as $invest_id ) {
			$invest_item = array();
			
			$downloads = edd_get_payment_meta_downloads( $invest_id );
			if ( !is_array( $downloads[0] ) ) {
				// Infos campagne et organisations
				$campaign = atcf_get_campaign( $downloads[0] );
				$invest_item['project_name'] = $campaign->get_name();
				$campaign_organization = $campaign->get_organization();
				$wdg_organization = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
				$invest_item['organization_name'] = $wdg_organization->get_name();
				$organization_country = $country_list[ $wdg_organization->get_nationality() ];
				$invest_item['organization_address'] = $wdg_organization->get_full_address_str(). ' ' .$wdg_organization->get_postal_code(). ' ' .$wdg_organization->get_city(). ' ' .$organization_country;
				$invest_item['organization_id'] = $wdg_organization->get_idnumber();
				$invest_item['organization_vat'] = $wdg_organization->get_vat();
			
				// Infos date et montant
				$date_invest = new DateTime( get_post_field( 'post_date', $invest_id ) );
				$invest_item['date'] = $date_invest->format('d/m/Y');
				$invest_item_amount = edd_get_payment_amount( $invest_id );

				// Infos royalties liés
				$invest_item['roi_list'] = array();
				$invest_item['roi_total'] = 0;
				$invest_item['roi_for_year'] = 0;
				$invest_item['tax_for_year'] = 0;
				$investment_royalties = $this->get_royalties_by_investment_id( $invest_id );
				foreach ( $investment_royalties as $investment_roi ) {
					$invest_item['roi_total'] += $investment_roi->amount;
					$date_transfer = new DateTime( $investment_roi->date_transfer );
					if ( $date_transfer->format( 'Y' ) == $year ) {
						$roi_item = array();
						$roi_item[ 'trimester_months' ] = '';
						$investment_roi_declaration = new WDGROIDeclaration( $investment_roi->id_declaration );
						$month_list = $investment_roi_declaration->get_month_list();
						foreach ( $month_list as $month_item ) {
							if ( !empty( $roi_item[ 'trimester_months' ] ) ) {
								$roi_item[ 'trimester_months' ] .= ', ';
							}
							$roi_item[ 'trimester_months' ] .= $month_item;
						}
						
						$roi_item[ 'date' ] = $date_transfer->format('d/m/Y');
						$invest_item['roi_for_year'] += $investment_roi->amount;
						$roi_total += $investment_roi->amount;

						// Calcul de la part imposable
						if ( $invest_item['roi_total'] > $invest_item_amount ) {
							$investment_roi_taxed = $investment_roi->amount_taxed_in_cents / 100;
							$invest_item['taxed_for_year'] += $investment_roi_taxed;
							$taxed_total += $investment_roi_taxed;
						}

						$roi_item[ 'amount' ] = UIHelpers::format_number( $investment_roi->amount ) . ' &euro;';
						array_push( $invest_item['roi_list'], $roi_item );
					}
				}

				$invest_item['amount'] = UIHelpers::format_number( $invest_item_amount ) . ' &euro;';
				$invest_item['roi_total'] = UIHelpers::format_number( $invest_item['roi_total'] ) . ' &euro;';
				$invest_item['roi_for_year'] = UIHelpers::format_number( $invest_item['roi_for_year'] ) . ' &euro;';
				$invest_item['taxed_for_year'] = UIHelpers::format_number( $invest_item['taxed_for_year'] ) . ' &euro;';
				array_push( $investment_list, $invest_item );
			}
		}
 		
		$info_yearly_certificate = apply_filters( 'the_content', WDGROI::get_parameter( 'info_yearly_certificate' ) );
		
		require_once __DIR__. '/../control/templates/pdf/certificate-roi-yearly-user.php';
		$html_content = WDG_Template_PDF_Certificate_ROI_Yearly_User::get(
			'',
			'',
			'',
			$this->get_firstname(). ' ' .$this->get_lastname(),
			$this->get_email(),
			$this->get_full_address_str(),
			$this->get_postal_code(),
			$this->get_city(),
			'01/01/'.($year + 1),
			$year,
			$investment_list,
			UIHelpers::format_number( $roi_total ). ' &euro;',
			UIHelpers::format_number( $taxed_total ). ' &euro;',
			$info_yearly_certificate
		);
		
		$html2pdf = new HTML2PDF( 'P', 'A4', 'fr', true, 'UTF-8', array(12, 5, 15, 8) );
		$html2pdf->WriteHTML( urldecode( $html_content ) );
		$html2pdf->Output( $filepath, 'F' );
		
		return $buffer;
	}
	
	public function has_tax_exemption_for_year( $year ) {
		$buffer = FALSE;
		$tax_exemption_filename = get_user_meta( $this->get_wpref(), 'tax_exemption_' .$year, TRUE );
		if ( !empty( $tax_exemption_filename ) ) {
			$buffer = home_url( '/wp-content/plugins/appthemer-crowdfunding/files/tax-exemption/' .$year. '/' .$tax_exemption_filename );
		}
		return $buffer;
	}
	
	public function has_tax_document_for_year( $year ) {
		$buffer = FALSE;
		$tax_exemption_filename = get_user_meta( $this->get_wpref(), 'tax_document_' .$year, TRUE );
		if ( !empty( $tax_exemption_filename ) ) {
			$buffer = home_url( '/wp-content/plugins/appthemer-crowdfunding/files/tax-documents/' .$year. '/' .$tax_exemption_filename );
		}
		return $buffer;
	}

	public function get_tax_amount_in_cents_round( $roi_amount_in_cents ) {
		$tax_amount_in_cents = $this->get_tax_percent() * $roi_amount_in_cents / 100;
		return floor( $tax_amount_in_cents / 100 ) * 100;
	}

	public function get_tax_percent() {
		$date_now = new DateTime();
		$tax_country = $this->get_tax_country();
		if ( empty( $tax_country ) || $tax_country == 'FR' ) {
			if ( $this->has_tax_exemption_for_year( $date_now->format( 'Y' ) ) ) {
				return WDGROIDeclaration::$tax_with_exemption;
			} else {
				return WDGROIDeclaration::$tax_without_exemption;
			}
		}
		return 0;
	}
	
/*******************************************************************************
 * Gestion RIB
*******************************************************************************/
	public static $key_bank_holdername = "bank_holdername";
	public static $key_bank_iban = "bank_iban";
	public static $key_bank_bic = "bank_bic";
	public static $key_bank_address1 = "bank_address1";
	public static $key_bank_address2 = "bank_address2";
	
	/**
	 * Retourne une info correspondante au IBAN
	 * @param string $info
	 * @return string
	 */
	public function get_iban_info( $info ) {
		return get_user_meta( $this->wp_user->ID, "bank_" . $info, TRUE);
	}
	
	public function get_bank_iban() {
		$buffer = $this->bank_iban;
		if ( empty( $buffer ) ) {
			$buffer = $this->get_iban_info( 'iban' );
		}
		return $buffer;
	}
	
	public function get_bank_bic() {
		$buffer = $this->bank_bic;
		if ( empty( $buffer ) ) {
			$buffer = $this->get_iban_info( 'bic' );
		}
		return $buffer;
	}
	
	public function get_bank_holdername() {
		$buffer = $this->bank_holdername;
		if ( empty( $buffer ) ) {
			$buffer = $this->get_iban_info( 'holdername' );
		}
		return $buffer;
	}
	
	public function get_bank_address() {
		$buffer = $this->bank_address;
		if ( empty( $buffer ) ) {
			$buffer = $this->get_iban_info( 'address' );
		}
		return $buffer;
	}
	
	public function get_bank_address2() {
		$buffer = $this->bank_address2;
		if ( empty( $buffer ) ) {
			$buffer = $this->get_iban_info( 'address2' );
		}
		return $buffer;
	}
	
	/**
	 * Est-ce que le RIB est enregistrÃ© ?
	 */
	public function has_saved_iban() {
		$saved_holdername = $this->get_bank_holdername();
		$saved_iban = $this->get_bank_iban();
		return ( !empty( $saved_holdername ) && !empty( $saved_iban ) );
	}
	
	/**
	 * Enregistre le RIB
	 */
	public function save_iban( $holder_name, $iban, $bic, $address1, $address2 = '' ) {
		$this->bank_holdername = $holder_name;
		$this->save_meta( WDGUser::$key_bank_holdername, $holder_name );
		$this->bank_iban = $iban;
		$this->save_meta( WDGUser::$key_bank_iban, $iban );
		$this->bank_bic = $bic;
		$this->save_meta( WDGUser::$key_bank_bic, $bic );
		$this->bank_address = $address1;
		$this->save_meta( WDGUser::$key_bank_address1, $address1 );
		if ( !empty( $address2 ) ) {
			$this->bank_address2 = $address2;
			$this->save_meta( WDGUser::$key_bank_address2, $address2 );
		}
	}
	
/*******************************************************************************
 * Gestion Lemonway
*******************************************************************************/
	public function get_encoded_gateway_list() {
		$array_buffer = array();
		$lw_id = $this->get_lemonway_id();
		if ( !empty( $lw_id ) ) {
			$array_buffer[ 'lemonway' ] = $lw_id;
		}
		return json_encode( $array_buffer );
	}

	/**
	 * RÃ©cupÃ¨re les infos sur LW, via l'ID ou via l'e-mail
	 * @param boolean $reload
	 * @param boolean $by_email
	 * @return object
	 */
	public function get_wallet_details( $reload = false, $by_email = false ) {
		if ( !isset($this->wallet_details) || empty($this->wallet_details) || $reload == true ) {
			if ( $by_email ) {
				$this->wallet_details = LemonwayLib::wallet_get_details( FALSE, $this->get_email() );
				
			} else {
				$this->wallet_details = LemonwayLib::wallet_get_details( $this->get_lemonway_id() );

				if ( isset( $this->wallet_details->EMAIL ) && $this->wallet_details->EMAIL != $this->get_email() ) {
					$this->update_lemonway();
				}
			}
		}
		return $this->wallet_details;
	}
	
	public function has_lemonway_wallet( $reload = false ) {
		$buffer = FALSE;
		$wallet_details = $this->get_wallet_details( $reload );
		if ( isset( $wallet_details->NAME ) && !empty( $wallet_details->NAME ) ) {
			$buffer = TRUE;
		}
		return $buffer;
	}
	
	/**
	 * Enregistrement sur Lemonway
	 */
	public function register_lemonway() {
		//VÃ©rifie que le wallet n'est pas dÃ©jÃ  enregistrÃ©
		$wallet_details = $this->get_wallet_details();
		if ( !isset($wallet_details->NAME) || empty($wallet_details->NAME) ) {
			ypcf_debug_log_backtrace();
			return LemonwayLib::wallet_register(
				$this->get_lemonway_id(),
				$this->get_email(),
				$this->get_lemonway_title(),
				html_entity_decode( $this->get_firstname() ), 
				html_entity_decode( $this->get_lastname() ),
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
	 * DÃ©termine si les informations nÃ©cessaires sont remplies : mail, prÃ©nom, nom, pays, date de naissance, nationality
	 */
	public function can_register_lemonway() {
		$buffer = ( $this->get_email() != "" ) && ( $this->get_firstname() != "" ) && ( $this->get_lastname() != "" )
						&& ( $this->get_country() != "" ) && ( $this->get_birthday_date() != "" )&& ( $this->get_nationality() != "" );
		return $buffer;
	}
	
	/**
	 * Met Ã  jour les donnÃ©es sur LW si nÃ©cessaire
	 */
	private function update_lemonway() {
		LemonwayLib::wallet_update(
			$this->get_lemonway_id(),
			$this->get_email(),
			$this->get_lemonway_title(),
			$this->get_firstname(), 
			$this->get_lastname(),
			$this->get_country( 'iso3' ),
			$this->get_lemonway_phone_number(),
			$this->get_lemonway_birthdate(),
			$this->get_nationality( 'iso3' )
		);
	}
	
	/**
	 * DÃ©finit l'identifiant de l'utilisateur sur lemonway
	 * @return string
	 */
	public function get_lemonway_id() {
		// RÃ©cupÃ©ration dans la BDD
		$db_lw_id = $this->wp_user->get( 'lemonway_id' );
		if ( empty( $db_lw_id ) ) {
			
			// Cross-platform
			// Si n'existe pas dans la BDD, 
			// -> on vÃ©rifie d'abord, via l'e-mail, si il existe sur LW
			$wallet_details_by_email = $this->get_wallet_details( true, true );
			if ( isset( $wallet_details_by_email->ID ) ) {
				$db_lw_id = $wallet_details_by_email->ID;
				
			} elseif ( !empty( $this->wp_user->ID ) ) {
				$db_lw_id = 'USERW'.$this->wp_user->ID;
				if ( defined( 'YP_LW_USERID_PREFIX' ) ) {
					$db_lw_id = YP_LW_USERID_PREFIX . $db_lw_id;
				}
			}
			
			if ( !empty( $this->wp_user->ID ) ) {
				update_user_meta( $this->wp_user->ID, 'lemonway_id', $db_lw_id );
			}
		}
		return $db_lw_id;
	}
	
	/**
	 * RÃ©cupÃ¨re le genre de l'utilisateur, formattÃ© pour lemonway
	 * @return string
	 */
	public function get_lemonway_title() {
		$buffer = "U";
		if ( $this->get_gender() == 'male' ) {
			$buffer = "M";
		} elseif ( $this->get_gender() == "female" ) {
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
		$birthday_datetime = new DateTime( $this->get_birthday_date() );
		return $birthday_datetime->format( 'd/m/Y' );
	}
	
	public function get_lemonway_registered_cards() {
		$buffer = array();
		$wallet_details = $this->get_wallet_details();
		if ( !empty( $wallet_details->CARDS ) && !empty( $wallet_details->CARDS->CARD ) ) {
			if ( is_array( $wallet_details->CARDS->CARD ) ) {
				foreach( $wallet_details->CARDS->CARD as $card_object ) {
					if ( isset( $card_object->ID ) && $card_object->ID !== FALSE ) {
						$card_item = array();
						$card_item[ 'id' ] = $card_object->ID;
						if ( isset( $card_object->EXTRA->EXP ) && $card_object->EXTRA->EXP !== FALSE ) {
							$card_item[ 'expiration' ] = $card_object->EXTRA->EXP;
						}
						if ( isset( $card_object->EXTRA->NUM ) && $card_object->EXTRA->NUM !== FALSE ) {
							$card_item[ 'number' ] = $card_object->EXTRA->NUM;
						}
						array_push( $buffer, $card_item );
					}
				}

			} elseif ( isset( $wallet_details->CARDS->CARD ) ) {
				$card_object = $wallet_details->CARDS->CARD;
				if ( isset( $card_object->ID ) && $card_object->ID !== FALSE ) {
					$card_item = array();
					$card_item[ 'id' ] = $card_object->ID;
					if ( isset( $card_object->EXTRA->EXP ) && $card_object->EXTRA->EXP !== FALSE ) {
						$card_item[ 'expiration' ] = $card_object->EXTRA->EXP;
					}
					if ( isset( $card_object->EXTRA->NUM ) && $card_object->EXTRA->NUM !== FALSE ) {
						$card_item[ 'number' ] = $card_object->EXTRA->NUM;
					}
					array_push( $buffer, $card_item );
				}

			}
		}
		return $buffer;
	}

	public function unregister_card( $id_card ) {
		LemonwayLib::unregister_card( $this->get_lemonway_id(), $id_card );
	}

	/**
	 * Enregistre la date d'expiration de la carte qui vient d'être utilisée et enregistrée
	 */
	public function save_lemonway_card_expiration_date() {
		$expiration_date = FALSE;
		$wallet_details = $this->get_wallet_details();
		if ( !empty( $wallet_details->CARDS ) && !empty( $wallet_details->CARDS->CARD ) ) {
			if ( is_array( $wallet_details->CARDS->CARD ) ) {
				foreach( $wallet_details->CARDS->CARD as $card_object ) {
					if ( isset( $card_object->EXTRA->EXP ) && $card_object->EXTRA->EXP !== FALSE ) {
						$expiration_date = $card_object->EXTRA->EXP;
					}
				}

			} elseif ( isset( $wallet_details->CARDS->CARD ) ) {
				$card_object = $wallet_details->CARDS->CARD;
				if ( isset( $card_object->EXTRA->EXP ) && $card_object->EXTRA->EXP !== FALSE ) {
					$expiration_date = $card_object->EXTRA->EXP;
				}
			}
		}

		if ( !empty( $expiration_date ) ) {
			update_user_meta( $this->get_wpref(), 'save_card_expiration_date', $expiration_date );
		}
	}

	/**
	 * Retourne vrai si il a enregistré une carte bancaire précédemment
	 */
	public function has_saved_card_expiration_date() {
		$expiration_date = get_user_meta( $this->get_wpref(), 'save_card_expiration_date', TRUE );
		return !empty( $expiration_date );
	}

	/**
	 * Copie les paramètres d'inscription à une NL de l'ancienne adresse mail d'un compte à la nouvelle adresse
	 */
	public function copy_sendinblue_params_to_new_email( $old_email, $new_email ) {
		if ( $old_email == $new_email ) {
			return;
		}

		try {
			$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 15000 );
			$return = $mailin->get_user( array(
				"email"		=> $old_email
			) );

			$lists_is_in = array();
			if ( isset( $return[ 'code' ] ) && $return[ 'code' ] != 'failure' ) {
				if ( isset( $return[ 'data' ] ) && isset( $return[ 'data' ][ 'listid' ] ) ) {
					foreach( $return[ 'data' ][ 'listid' ] as $list_id ) {
						array_push( $lists_is_in, $list_id );
					}
				}
			}
	
			$mailin->create_update_user( array(
				"email"		=> $new_email,
				"listid"	=> $lists_is_in
			) );
		
		} catch ( Exception $e ) {
			ypcf_debug_log( "WDGUser::copy_sendinblue_params_to_new_email > erreur sendinblue" );
		}
	}

	/**
	 * Met à jour la souscription à la notification d'authentification
	 */
	public function set_subscribe_authentication_notification( $value ) {
		if ( $value === TRUE ) {
			update_user_meta( $this->get_wpref(), 'subscribe_authentication_notification', '1' );
			
			try {
				$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 15000 );
				$return = $mailin->create_update_user( array(
					"email"			=> $this->get_email(),
					"attributes"	=> array(
						"SMS"	=> $this->get_lemonway_phone_number()
					)
				) );
			} catch ( Exception $e ) {
				ypcf_debug_log( "WDGUser::set_subscribe_authentication_notification > erreur sendinblue" );
			}

		} else {
			delete_user_meta( $this->get_wpref(), 'subscribe_authentication_notification' );
		}
	}

	/**
	 * Retourne vrai si il a souscrit à la notification d'authentification
	 */
	public function has_subscribed_authentication_notification() {
		$subscribe_authentication_notification = get_user_meta( $this->get_wpref(), 'subscribe_authentication_notification', TRUE );
		return !empty( $subscribe_authentication_notification );
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

				update_user_meta( $this->wp_user->ID, WDGUser::$key_lemonway_status, $buffer );
			}
		} else {
			$buffer = get_user_meta( $this->wp_user->ID, WDGUser::$key_lemonway_status, TRUE );
		}
		return $buffer;
	}
	
	/**
	 * DÃ©termine si l'utilisateur est authentifiÃ© auprÃ¨s de LW
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
	
	public function get_lemonway_iban() {
		$buffer = FALSE;
		$wallet_details = $this->get_wallet_details();
		if ( isset( $wallet_details->IBANS->IBAN ) ) {
			if ( is_array( $wallet_details->IBANS->IBAN ) ) {
				$buffer = $wallet_details->IBANS->IBAN[ 0 ];
				// Si le premier IBAN est désactivé, on va chercher dans la suite
				if ( count( $wallet_details->IBANS->IBAN ) > 1 && ( $buffer->S == self::$iban_status_disabled || $buffer->S == self::$iban_status_rejected ) ) {
					foreach ( $wallet_details->IBANS->IBAN as $iban_item ) {
						if ( $iban_item->S == self::$iban_status_validated || $iban_item->S == self::$iban_status_waiting ) {
							$buffer = $iban_item;
						}
					}
				}
			} else {
				$buffer = $wallet_details->IBANS->IBAN;
			}
		}
		return $buffer;
	}
	
	public static $iban_status_waiting = 4;
	public static $iban_status_validated = 5;
	public static $iban_status_disabled = 8;
	public static $iban_status_rejected = 9;
	public function get_lemonway_iban_status() {
		$first_iban = $this->get_lemonway_iban();
		if ( !empty( $first_iban ) ) {
			return $first_iban->S;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * DÃ©termine si l'utilisateur peut payer avec son porte-monnaie
	 * @param int $amount
	 * @param ATCF_Campaign $campaign
	 * @return bool
	 */
	public function can_pay_with_wallet( $amount, $campaign ) {
		$lemonway_amount = $this->get_lemonway_wallet_amount();
		return ($lemonway_amount > 0 && $lemonway_amount >= $amount && $campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway);
	}
	
	/**
	 * DÃ©termine si l'utilisateur peut payer avec sa carte et son porte-monnaie
	 * @param int $amount
	 * @param ATCF_Campaign $campaign
	 * @return bool
	 */
	public function can_pay_with_card_and_wallet( $amount, $campaign ) {
		$lemonway_amount = $this->get_lemonway_wallet_amount();
		//Il faut de l'argent dans le porte-monnaie, que la campagne soit sur lemonway et qu'il reste au moins 1 euro à payer par carte
		return ($lemonway_amount > 0 && $amount - $lemonway_amount >= 1 && $campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway);
	}
	
	/**
	 * Transfère l'argent du porte-monnaie utilisateur vers son compte bancaire
	 */
	public function transfer_wallet_to_bankaccount( $amount = FALSE ) {
		$buffer = __( "Votre compte bancaire n'est pas encore valid&eacute;.", 'yproject' );
		
		//Il faut qu'un iban ait déjà été enregistré
		if ($this->has_saved_iban()) {
			//Vérification que des IBANS existent
			$wallet_details = $this->get_wallet_details();
			$first_iban = $wallet_details->IBANS->IBAN;
			$save_transfer = TRUE;
			//Sinon on l'enregistre auprès de Lemonway
			if (empty($first_iban)) {
				$saved_holdername = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_holdername, TRUE );
				$saved_iban = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_iban, TRUE );
				$saved_bic = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_bic, TRUE );
				$saved_dom1 = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_address1, TRUE );
				$saved_dom2 = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_address2, TRUE );
				$result_iban = LemonwayLib::wallet_register_iban( $this->get_lemonway_id(), $saved_holdername, $saved_iban, $saved_bic, $saved_dom1, $saved_dom2 );
				if ($result_iban == FALSE) {
					$buffer .= ' ' . LemonwayLib::get_last_error_message();
					$save_transfer = FALSE;
				}
			}
			
			if ( $save_transfer ) {
				//Exécution du transfert vers le compte du montant du solde
				if ( empty( $amount ) ) {
					$amount = $wallet_details->BAL;

				} elseif( $amount > $wallet_details->BAL ) {
					$amount = FALSE;
					$buffer = __( "Montant non-autoris&eacute;", 'yproject' );
				}

				if ( !empty( $amount ) ) {
					$result_transfer = LemonwayLib::ask_transfer_to_iban( $this->get_lemonway_id(), $amount );
					$buffer = ($result_transfer->TRANS->HPAY->ID) ? TRUE : $result_transfer->TRANS->HPAY->MSG;
				}

				if ( $buffer === TRUE ) {
					$withdrawal_post = array(
						'post_author'   => $this->wp_user->ID,
						'post_title'    => $amount,
						'post_content'  => print_r( $result_transfer, TRUE ),
						'post_status'   => 'publish',
						'post_type'	    => 'withdrawal_order_lw'
					);
					wp_insert_post( $withdrawal_post );
					$WDGUser = new WDGUser( $this->wp_user->ID );
					NotificationsAPI::transfer_to_bank_account_confirmation( $WDGUser->get_email(), $WDGUser->get_firstname(), $amount );
				}
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne true si l'iban est enregistrÃ© sur Lemon Way
	 */
	public function has_registered_iban() {
		$buffer = true;
		$first_iban = $this->get_lemonway_iban();
		if (empty($first_iban)) {
			$buffer = false;
		}
		return $buffer;
	}
	
	/**
	 * Retourne true si le RIB est validÃ© sur Lemon Way
	 */
	public function is_document_lemonway_registered( $document_type ) {
		$lemonway_document = LemonwayDocument::get_by_id_and_type( $this->get_lemonway_id(), $document_type, $this->get_wallet_details() );
		return ( $lemonway_document->get_status() == LemonwayDocument::$document_status_accepted );
	}
	
	public function get_document_lemonway_status( $document_type ) {
		$lemonway_document = LemonwayDocument::get_by_id_and_type( $this->get_lemonway_id(), $document_type, $this->get_wallet_details() );
		return $lemonway_document->get_status();
	}
	
	public function get_document_lemonway_error( $document_type ) {
		$lemonway_document = LemonwayDocument::get_by_id_and_type( $this->get_lemonway_id(), $document_type, $this->get_wallet_details() );
		return $lemonway_document->get_error_str();
	}
	
	public function has_document_lemonway_error( $document_type ) {
		$rib_lemonway_error = $this->get_document_lemonway_error( $document_type );
		return ( !empty( $rib_lemonway_error ) );
	}

	public function get_transactions() {
		if ( !$this->is_lemonway_registered() ) {
			return FALSE;
		}

		if ( empty( $this->api_data->gateway_list ) || empty( $this->api_data->gateway_list[ 'lemonway' ] ) ) {
			$this->update_api();
		}
		return WDGWPREST_Entity_User::get_transactions( $this->get_api_id() );
	}
	
/*******************************************************************************
 * Gestion Lemonway - KYC
*******************************************************************************/
	/**
	 * DÃ©termine si l'organisation a envoyÃ© tous ses documents en local sur WDG
	 */
	public function has_sent_all_documents() {
		$is_id_doc_sent = FALSE;
		$nb_docs_sent = 0;
		$documents_type_list = array( WDGKYCFile::$type_id, WDGKYCFile::$type_idbis );
		foreach ( $documents_type_list as $document_type ) {
			$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wp_user->ID, WDGKYCFile::$owner_user, $document_type );
			$current_document = $document_filelist[0];
			if ( isset($current_document) ) {
				$nb_docs_sent++;
				if ( $document_type == WDGKYCFile::$type_id ) {
					$is_id_doc_sent = true;
				}
			}
		}
		
		return ( $nb_docs_sent > 1 && $is_id_doc_sent );
	}
	
	/**
	 * Upload des KYC vers Lemonway si possible
	 */
	public function send_kyc( $force_upload = TRUE ) {
		if ($this->can_register_lemonway()) {
			if ( $this->register_lemonway() ) {
				$documents_type_list = array( 
					WDGKYCFile::$type_id		=> LemonwayDocument::$document_type_id,
					WDGKYCFile::$type_id_back	=> LemonwayDocument::$document_type_id_back,
					WDGKYCFile::$type_id_2		=> LemonwayDocument::$document_type_idbis,
					WDGKYCFile::$type_id_2_back	=> LemonwayDocument::$document_type_idbis_back
				);
				foreach ( $documents_type_list as $document_type => $lemonway_type ) {
					$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wp_user->ID, WDGKYCFile::$owner_user, $document_type );
					if ( !empty( $document_filelist ) ) {
						$current_document = $document_filelist[0];
						if ( !empty( $current_document ) ) {
							$do_upload = TRUE;
							if ( !$force_upload ) {
								$document_status = $this->get_document_lemonway_status( $lemonway_type );
								if ( $document_status !== FALSE ) {
									$do_upload = FALSE;
								}
							}
							if ( $do_upload ) {
								LemonwayLib::wallet_upload_file( $this->get_lemonway_id(), $current_document->file_name, $lemonway_type, $current_document->get_byte_array() );
							}
						}
					}
				}
			}
		}
	}
	/**
	 * Récupère la liste des documents kyc envoyés par l'utilisateur
	 */
	public function get_all_documents() {
		$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wp_user->ID, WDGKYCFile::$owner_user );
		return $document_filelist;
	}
	/**
	 * Supprime un document de l'utilisateur
	 * @param WDGKYCFile $document
	 */
	public function delete_document($document) {
		$document->delete();

	}
	/**
	 * Supprime tous les documents de l'utilisateur
	 *
	 * @return void
	 */
	public function delete_all_documents() {
		$document_filelist = $this->get_all_documents();
		foreach ( $document_filelist as $document ) {
			$this->delete_document($document);
		}
	}
    
/*******************************************************************************
 * Fonctions statiques
*******************************************************************************/
	/**
	 * VÃ©rifie si l'utilisateur a bien validÃ© les cgu
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
		return ( isset( $edd_options[ WDGUser::$edd_general_terms_version ] ) && $current_signed_terms == $edd_options[ WDGUser::$edd_general_terms_version ] );
	}
	
	/**
	 * VÃ©rifie si le formulaire est complet et valide les cgu
	 * @global type $edd_options
	 * @global type $current_user
	 * @param type $user_id
	 * @return boolean
	 */
	public static function check_validate_general_terms($user_id = FALSE) {
		//VÃ©rification des champs de formulaire
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
	 * VÃ©rifie si il est nÃ©cessaie d'afficher la lightbox de cgu
	 * @global type $post
	 * @param type $user_id
	 * @return type
	 */
	public static function must_show_general_terms_block($user_id = FALSE) {
		global $post, $edd_options;
		$isset_general_terms = isset( $edd_options[ WDGUser::$edd_general_terms_version ] ) && !empty( $edd_options[ WDGUser::$edd_general_terms_version ] );
		//On affiche la lightbox de cgu si : l'utilisateur est connectÃ©, il n'est pas sur la page cgu, il ne les a pas encore validÃ©es
		return (is_user_logged_in() && $post->post_name != 'cgu' && !WDGUser::has_validated_general_terms($user_id) && $isset_general_terms);
	}
	
	/**
	 * RÃ©cupÃ©ration de la liste des id des projets auxquels un utilisateur est liÃ©
	 * @param type $user_id
	 * @param type $complete
	 * @return array
	 */
	public static function get_projects_by_id($user_id, $complete = FALSE) {
		$buffer = array();
		
		//RÃ©cupÃ©ration des projets dont l'utilisateur est porteur
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
		
		//RÃ©cupÃ©ration des projets dont l'utilisateur appartient Ã  l'Ã©quipe
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
	 * DÃ©finit la page vers laquelle il faudrait rediriger l'utilisateur lors de sa connexion
	 * @global type $post
	 * @return type
	 */
	public static function get_login_redirect_page( $anchor = '') {
		// ypcf_debug_log( 'WDGUser::get_login_redirect_page ', FALSE );
		global $post;
		$buffer = home_url();
		
		//Si on est sur la page de connexion ou d'inscription,
		// il faut retrouver la page prÃ©cÃ©dente et vÃ©rifier qu'elle est de WDG
		if ( $post->post_name == 'connexion' || $post->post_name == 'inscription' ) {
			// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A1', FALSE );
			//On vÃ©rifie d'abord si cela a Ã©tÃ© passÃ© en paramÃ¨tre d'URL
			$get_redirect_page = filter_input( INPUT_GET, 'redirect-page' );
			if ( !empty( $get_redirect_page ) ) {
				// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A2', FALSE );
				$buffer = home_url( $get_redirect_page );
				// on ajoute un éventuel id de campagne
				$input_get_campaign_id = filter_input( INPUT_GET, 'campaign_id' );
				if ( !empty( $input_get_campaign_id ) ) {
					$buffer .= '?campaign_id=' . $input_get_campaign_id;
					// on ajoute un éventuel id de déclaration
					$input_get_declaration_id = filter_input( INPUT_GET, 'declaration_id' );
					if ( !empty( $input_get_declaration_id ) ) {
						$buffer .= '&declaration_id=' . $input_get_declaration_id;					
					}					
				}
			} else {
				// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A1b', FALSE );
				ypcf_session_start();
				if ( !empty( $_SESSION[ 'login-fb-referer' ] ) ) {
					// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A2b', FALSE );
					$buffer = $_SESSION[ 'login-fb-referer' ];
					if ( strpos( $buffer, '/connexion/' ) !== FALSE || strpos( $buffer, '/inscription/' ) !== FALSE ) {
						$buffer = home_url();
					}
					
				} else {
					//RÃ©cupÃ©ration de la page prÃ©cÃ©dente
					$referer_url = wp_get_referer();
					//On vÃ©rifie que l'url appartient bien au site en cours (home_url dans referer)
					if (strpos($referer_url, $buffer) !== FALSE) {

						//Si la page prÃ©cÃ©dente Ã©tait dÃ©jÃ  la page connexion ou enregistrement, 
						// on tente de voir si la redirection Ã©tait passÃ©e en paramÃ¨tre
						if ( strpos($referer_url, '/connexion/') !== FALSE || strpos($referer_url, '/inscription/') !== FALSE ) {
							$posted_redirect_page = filter_input(INPUT_POST, 'redirect-page');
							if (!empty($posted_redirect_page)) {
								// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A3a', FALSE );
								$buffer = $posted_redirect_page;
							} else {
								// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A3b', FALSE );
								$buffer = home_url();
							}

						//Sinon on peut effectivement rediriger vers la page prÃ©cÃ©dente
						} else {
							//Si c'est une page projet et qu'il y a un vote en cours, on redirige vers le formulaire de vote
							$path = substr( $referer_url, strlen( home_url() ) + 1, -1 );
							$page_by_path = get_page_by_path( $path, OBJECT, 'download' );
							// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A4', FALSE );
							if ( !empty( $page_by_path->ID ) ) {
								$campaign = new ATCF_Campaign( $page_by_path->ID );
								if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote && $campaign->is_remaining_time() ) {
									// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A4a', FALSE );
									$anchor = '#vote';
								}
							}
							$buffer = $referer_url;
						}
						
					} else {
						// ypcf_debug_log( 'WDGUser::get_login_redirect_page > A5 ' . $referer_url, FALSE );
					}
				}
			}
			
		//Sur les autres pages
		} else {
			// ypcf_debug_log( 'WDGUser::get_login_redirect_page > B1', FALSE );
			//On tente de voir si une redirection n'avait pas Ã©tÃ© demandÃ©e auparavant
			$posted_redirect_page = filter_input(INPUT_POST, 'redirect-page');
			if (!empty($posted_redirect_page)) {
				// ypcf_debug_log( 'WDGUser::get_login_redirect_page > B2', FALSE );
				$buffer = $posted_redirect_page;
			
			//Sinon, on rÃ©cupÃ¨re simplement la page en cours
			} else {
				if ( isset( $post->ID ) ) {
					// ypcf_debug_log( 'WDGUser::get_login_redirect_page > B3', FALSE );
					$buffer = get_permalink( $post->ID );
					$input_get_campaign_id = filter_input( INPUT_GET, 'campaign_id' );
					if ( !empty( $input_get_campaign_id ) ) {
						$buffer .= '?campaign_id=' . $input_get_campaign_id;
						
					} elseif (ATCF_Campaign::is_campaign( $post->ID ) ) {
						$campaign = new ATCF_Campaign( $post->ID );
						if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote && $campaign->is_remaining_time() ) {
							$anchor = '#vote';
						}
					}
					
					ypcf_session_start();
					$_SESSION[ 'login-fb-referer' ] = $buffer . $anchor;
				}
			}
		}
		
		// ypcf_debug_log( 'WDGUser::get_login_redirect_page > result = ' .$buffer . $anchor, FALSE );
		return $buffer . $anchor;
	}
}