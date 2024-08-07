<?php
/**
 * Classe de gestion des organisations
 */
class WDGOrganization implements WDGUserInterface {
	/**
	 * Clés d'accès à l'api BOPP
	 */
	public static $key_api_id = 'organisation_bopp_id';
	public static $key_description = 'description';
	public static $key_lemonway_status = 'lemonway_status';

	/**
	 * Données
	 */
	private $creator;
	private $api_id;
	private $bopp_object;
	private $wpref;
	private $name;
	private $email;
	private $representative_function;
	private $description;
	private $website;
	private $strong_authentication;
	private $address_number;
	private $address_number_comp;
	private $address;
	private $postal_code;
	private $city;
	private $nationality;
	private $latitude;
	private $longitude;
	private $type;
	private $legalform;
	private $capital;
	private $idnumber;
	private $rcs;
	private $ape;
	private $vat;
	private $fiscal_year_end_month;
	private $employees_count;
	private $bank_owner;
	private $bank_address;
	private $bank_address2;
	private $bank_iban;
	private $bank_bic;
	private $accountant_name;
	private $accountant_email;
	private $accountant_address;
	private $id_quickbooks;
	private $mandate_info;
	private $mandate_file_url;

	protected static $_current = null;
	public static function current() {
		if ( is_null( self::$_current ) ) {
			self::$_current = new self();
		}

		return self::$_current;
	}

	/**
	 * Quickly creates a new Organization with limited informations
	 * @param $user_id int The organization creator id
	 * @param $orga_name string Name of the new organization
	 * @param $orga_email string Mail
	 * @return bool|WDGOrganization The new organization or FALSE if failure
	 */
	public static function createSimpleOrganization($user_id, $orga_name, $orga_email) {
		$org_object = new WDGOrganization();
		$org_object->set_strong_authentication(FALSE);
		$org_object->set_name($orga_name);
		$org_object->set_email($orga_email);

		$org_object->set_representative_function('---');
		$org_object->set_description('---');
		$org_object->set_address('---');
		$org_object->set_postal_code('00000');
		$org_object->set_city('---');
		$org_object->set_nationality('---');
		$org_object->set_type('society');
		$org_object->set_legalform('---');
		$org_object->set_capital(0);
		$org_object->set_idnumber('---');
		$org_object->set_rcs('---');
		$org_object->set_ape('---');
		$org_object->set_vat('---');
		$org_object->set_fiscal_year_end_month('---');
		$org_object->set_employees_count(0);
		$org_object->set_bank_owner('---');
		$org_object->set_bank_address('---');
		$org_object->set_bank_iban('---');
		$org_object->set_bank_bic('---');

		$org_user_id = $org_object->create();
		if ($org_user_id == false) {
			ypcf_debug_log( "WDGOrganization::createSimpleOrganization renvoie false" );

			return false;
		}
		$org_object->set_creator( $user_id );

		return $org_object;
	}

	/**
	 * Constructeur
	 */
	public function __construct($user_id = FALSE, $api_object = FALSE) {
		if ($user_id === FALSE) {
			$user_id = filter_input(INPUT_GET, 'orga_id');
		}

		if (!empty($user_id)) {
			$this->creator = get_user_by('id', $user_id);
			$this->api_id = get_user_meta($user_id, WDGOrganization::$key_api_id, TRUE);
			if ( !empty( $api_object ) ) {
				$this->bopp_object = $api_object;
			} else {
				$this->bopp_object = WDGWPREST_Entity_Organization::get( $this->api_id );
			}
			$this->wpref = $user_id;

			if ( !empty( $this->bopp_object ) ) {
				$this->name = $this->bopp_object->name;
				$this->email = $this->bopp_object->email;
				$this->representative_function = $this->bopp_object->representative_function;
				$this->description = $this->bopp_object->description;
				$this->website = $this->bopp_object->website_url;
				$this->strong_authentication = $this->bopp_object->strong_authentication;
				$this->address_number = $this->bopp_object->address_number;
				$this->address_number_comp = $this->bopp_object->address_number_comp;
				$this->address = $this->bopp_object->address;
				$this->postal_code = $this->bopp_object->postalcode;
				$this->city = $this->bopp_object->city;
				$this->nationality = $this->bopp_object->country;
				$this->type = $this->bopp_object->type;
				$this->legalform = $this->bopp_object->legalform;
				$this->capital = $this->bopp_object->capital;
				$this->idnumber = $this->bopp_object->idnumber;
				$this->rcs = $this->bopp_object->rcs;
				$this->ape = $this->bopp_object->ape;
				$this->vat = $this->bopp_object->vat;
				$this->fiscal_year_end_month = $this->bopp_object->fiscal_year_end_month;
				$this->employees_count = $this->bopp_object->employees_count;
				$geolocation = explode( ',', $this->bopp_object->geolocation );
				if ( count( $geolocation ) > 1 ) {
					$this->latitude = $geolocation[0];
					$this->longitude = $geolocation[1];
				}

				$accountant_info = json_decode( $this->bopp_object->accountant );
				if ( !empty( $accountant_info->name ) ) {
					$this->accountant_name = $accountant_info->name;
				}
				if ( !empty( $accountant_info->email ) ) {
					$this->accountant_email = $accountant_info->email;
				}
				if ( !empty( $accountant_info->address ) ) {
					$this->accountant_address = $accountant_info->address;
				}

				$this->bank_owner = $this->bopp_object->bank_owner;
				$this->bank_address = $this->bopp_object->bank_address;
				$this->bank_address2 = $this->bopp_object->bank_address2;
				$this->bank_iban = $this->bopp_object->bank_iban;
				$this->bank_bic = $this->bopp_object->bank_bic;
				$this->id_quickbooks = $this->bopp_object->id_quickbooks;
				$this->mandate_info = array();
				if ( !empty( $this->bopp_object->mandate_info ) ) {
					$this->mandate_info = json_decode( $this->bopp_object->mandate_info, TRUE );
				}
				$this->mandate_file_url = $this->bopp_object->mandate_file_url;
			}

			if ( empty( $this->email ) ) {
				$meta_email = get_user_meta( $user_id, 'orga_contact_email', TRUE );
				if ( !empty( $meta_email ) ) {
					$this->email = $meta_email;
				} else {
					if ( !empty( $this->creator ) ) {
						$this->email = $this->creator->user_email;
					}
				}
			}
			if ( empty( $this->description ) ) {
				$this->description = get_user_meta( $user_id, WDGOrganization::$key_description, TRUE );
			}
		}
	}

	/**
	 * Crée un utilisateur dans la base de données et l'initialise
	 * @return boolean|int
	 */
	public function create() {
		global $errors_submit_new, $errors_create_orga;
		if (!isset($errors_create_orga)) {
			$errors_create_orga = array();
		}

		if ($this->get_name() == "") {
			array_push( $errors_create_orga, __("Merci de remplir le nom de l'organisation", 'yproject') );
		}
		if ($this->get_email() == "") {
			array_push( $errors_create_orga, __("Merci de remplir l'adresse e-mail de l'organisation", 'yproject') );
		}
		if ( email_exists( $this->get_email() ) ) {
			array_push( $errors_create_orga, __("L'e-mail est d&eacute;j&agrave; utilis&eacute;", 'yproject') );
		}
		if ($this->get_type() == "") {
			array_push( $errors_create_orga, __("Merci de remplir le type de l'organisation", 'yproject') );
		}
		if ($this->get_description() == "") {
			array_push( $errors_create_orga, __("Merci de remplir le descriptif de l'activit&eacute;", 'yproject') );
		}
		if ($this->get_legalform() == "") {
			array_push( $errors_create_orga, __("Merci de remplir la forme juridique de l'organisation", 'yproject') );
		}
		if ($this->get_idnumber() == "") {
			array_push( $errors_create_orga, __("Merci de remplir le num&eacute;ro SIRET de l'organisation", 'yproject') );
		}
		if ($this->get_rcs() == "") {
			array_push( $errors_create_orga, __("Merci de remplir le RCS de l'organisation", 'yproject') );
		}
		if ($this->get_capital() == "") {
			$this->set_capital(0);
		}
		if ($this->get_ape() == "") {
			array_push( $errors_create_orga, __("Merci de remplir le code APE de l'organisation", 'yproject') );
		}
		if ($this->get_address() == "") {
			array_push( $errors_create_orga, __("Merci de remplir l'adresse de l'organisation", 'yproject') );
		}
		if ($this->get_postal_code() == "") {
			array_push( $errors_create_orga, __("Merci de remplir le code postal de l'organisation", 'yproject') );
		}
		if ($this->get_city() == "") {
			array_push( $errors_create_orga, __("Merci de remplir la ville de l'organisation", 'yproject') );
		}
		if ($this->get_nationality() == "") {
			array_push( $errors_create_orga, __("Merci de remplir le pays de l'organisation", 'yproject') );
		}
		if (!empty($errors_create_orga)) {
			ypcf_debug_log( "WDGOrganization::create renvoie des erreurs" );

			return FALSE;
		}

		$organization_user_id = $this->create_user($this->get_name());
		$this->set_wpref($organization_user_id);

		//Si il y a eu une erreur lors de la création de l'utilisateur, on arrête la procédure
		if (isset($organization_user_id->errors) && count($organization_user_id->errors) > 0) {
			$errors_submit_new = $organization_user_id;
			ypcf_debug_log( "WDGOrganization::create a eu un souci pour créer l'utilisateur" );

			return FALSE;
		}

		if ( $this->get_bank_owner() == '' ) {
			$this->set_bank_owner("---");
		}
		if ( $this->get_bank_address() == '' ) {
			$this->set_bank_address("---");
		}
		if ( $this->get_bank_address2() == '' ) {
			$this->set_bank_address2("---");
		}
		if ( $this->get_bank_iban() == '' ) {
			$this->set_bank_iban("---");
		}
		if ( $this->get_bank_bic() == '' ) {
			$this->set_bank_bic("---");
		}

		$return_obj = WDGWPREST_Entity_Organization::create( $this );
		$this->api_id = $return_obj->id;

		//Vérification si on reçoit bien un entier pour identifiant
		if (filter_var($this->api_id, FILTER_VALIDATE_INT) === FALSE) {
			array_push( $errors_create_orga, __("Probl&egrave;me interne de cr&eacute;ation d'organisation.", 'yproject') );
			ypcf_debug_log( "WDGOrganization::create a eu un souci pour créer l'organisation sur l'API" );

			return FALSE;
		}

		update_user_meta($organization_user_id, WDGOrganization::$key_api_id, $this->api_id);

		return $organization_user_id;
	}

	/**
	 * Crée l'utilisateur qui sert de référence d'organisation dans wordpress
	 * @param type $name
	 */
	private function create_user($name) {
		$sanitized_name = sanitize_title_with_dashes($name);
		$username = 'org_' . $sanitized_name;
		$password = wp_generate_password();
		$email_input = $this->get_email();
		if (empty($email_input) || email_exists($email_input)) {
			$email = $sanitized_name . '@wedogood.co';
		} else {
			$email = $email_input;
		}
		remove_action( 'user_register', 'yproject_user_register' );
		ypcf_debug_log( "WDGOrganization::create_user > " .$username. " ; " . $email);
		$organization_user_id = wp_create_user($username, $password, $email);
		ypcf_debug_log( "WDGOrganization::create_user >>> " .print_r($organization_user_id, true));
		if (email_exists($email_input) && !empty($email_input)) {
			update_user_meta($organization_user_id, 'orga_contact_email', $email_input);
		}

		return $organization_user_id;
	}

	/**
	 * Enregistre les modifications sur l'api bopp
	 */
	public function save() {
		$this->update_api();

		$new_mail = $this->get_email();
		if ( !email_exists($new_mail) ) {
			wp_update_user( array( 'ID' => $this->wpref, 'user_email' => $new_mail ) );
		}
		update_user_meta( $this->wpref, 'orga_contact_email', $new_mail );
	}

	private function update_api() {
		WDGWPREST_Entity_Organization::update( $this );
	}

	/**
	 * Retourne une organisation via l'id de l'API
	 * @param int $api_id
	 */
	public static function get_by_api_id($api_id) {
		$buffer = FALSE;
		if ( !empty( $api_id ) ) {
			$api_data = WDGWPREST_Entity_Organization::get( $api_id );
			if ( !empty( $api_data->wpref ) ) {
				$buffer = new WDGOrganization( $api_data->wpref );
			}
		}

		return $buffer;
	}

	/**
	 * Attributions / Récupération de données
	 */
	public function get_creator() {
		return $this->creator;
	}
	public function get_api_id() {
		return $this->api_id;
	}

	/**
	 * Définit l'identifiant de l'orga sur lemonway
	 * @return string
	 */
	public function get_lemonway_id() {
		// Récupération dans la BDD
		$db_lw_id = get_user_meta( $this->wpref, 'lemonway_id', true );
		if ( empty( $db_lw_id ) ) {
			// Cross-platform
			// Si n'existe pas dans la BDD,
			// -> on vérifie d'abord, via l'e-mail, si il existe sur LW
			$wallet_details_by_email = $this->get_wallet_details( '', true, true );
			if ( isset( $wallet_details_by_email->ID ) ) {
				$db_lw_id = $wallet_details_by_email->ID;
			} else {
				$db_lw_id = 'ORGA'.$this->api_id.'W'.$this->wpref;
				if ( defined( 'YP_LW_USERID_PREFIX' ) ) {
					$db_lw_id = YP_LW_USERID_PREFIX . $db_lw_id;
				}
			}

			update_user_meta( $this->wpref, 'lemonway_id', $db_lw_id );
		}

		return $db_lw_id;
	}

	public function get_campaign_lemonway_id() {
		$db_lw_id = get_user_meta( $this->wpref, 'lemonway_campaign_id', true );
		if ( empty( $db_lw_id ) ) {
			$db_lw_id = 'ORGA' .$this->api_id. 'W' .$this->wpref. 'CAMPAIGN';
			if ( defined( 'YP_LW_USERID_PREFIX' ) ) {
				$db_lw_id = YP_LW_USERID_PREFIX . $db_lw_id;
			}

			update_user_meta( $this->wpref, 'lemonway_campaign_id', $db_lw_id );
		}
		if ($db_lw_id == 'ORGAWCAMPAIGN') {
			ypcf_debug_log( "WDGOrganization::get_campaign_lemonway_id > " .$this->api_id. " ; " . $this->wpref);
		}

		return $db_lw_id;
	}

	private function get_campaign_lemonway_email() {
		$current_email = $this->get_email();
		$buffer = str_replace( '@', '+campaign@', $current_email );

		return $buffer;
	}

	public function get_royalties_lemonway_id() {
		$db_lw_id = get_user_meta( $this->wpref, 'lemonway_royalties_id', true );
		if ( empty( $db_lw_id ) ) {
			$db_lw_id = 'ORGA' .$this->api_id. 'W' .$this->wpref. 'ROYALTIES';
			if ( defined( 'YP_LW_USERID_PREFIX' ) ) {
				$db_lw_id = YP_LW_USERID_PREFIX . $db_lw_id;
			}

			update_user_meta( $this->wpref, 'lemonway_royalties_id', $db_lw_id );
		}

		return $db_lw_id;
	}

	private function get_royalties_lemonway_email() {
		$current_email = $this->get_email();
		$buffer = str_replace( '@', '+royalties@', $current_email );

		return $buffer;
	}

	public function get_tax_lemonway_id() {
		$db_lw_id = get_user_meta( $this->wpref, 'lemonway_tax_id', true );
		if ( empty( $db_lw_id ) ) {
			$db_lw_id = 'ORGA' .$this->api_id. 'W' .$this->wpref. 'TAX';
			if ( defined( 'YP_LW_USERID_PREFIX' ) ) {
				$db_lw_id = YP_LW_USERID_PREFIX . $db_lw_id;
			}

			update_user_meta( $this->wpref, 'lemonway_tax_id', $db_lw_id );
		}

		return $db_lw_id;
	}

	private function get_tax_lemonway_email() {
		$current_email = $this->get_email();
		$buffer = str_replace( '@', '+tax@', $current_email );

		return $buffer;
	}

	public function get_wpref() {
		return $this->wpref;
	}
	public function set_wpref($value) {
		$this->wpref = $value;
	}

	public function get_firstname() {
		return $this->get_name();
	}
	public function get_name() {
		return $this->name;
	}
	public function set_name($value) {
		$this->name = $value;
	}

	public function get_email() {
		return $this->email;
	}
	public function set_email($value) {
		$this->email = $value;
	}
	public function get_representative_function() {
		return $this->representative_function;
	}
	public function set_representative_function($value) {
		$this->representative_function = $value;
	}
	public function get_description() {
		return $this->description;
	}
	public function set_description($value) {
		$this->description = $value;
	}

	public function get_website() {
		return $this->website;
	}
	public function set_website($value) {
		$this->website = $value;
	}

	public function get_strong_authentication() {
		return $this->strong_authentication;
	}
	public function set_strong_authentication($value) {
		$this->strong_authentication = $value;
	}

	public function get_address_number() {
		return $this->address_number;
	}
	public function set_address_number($value) {
		if ( !empty( $value ) ) {
			$this->address_number = $value;
		}
	}

	public function get_address_number_comp() {
		return $this->address_number_comp;
	}
	public function set_address_number_comp($value) {
		if ( !empty( $value ) ) {
			$this->address_number_comp = $value;
		}
	}

	public function get_address() {
		return $this->address;
	}
	public function set_address($value) {
		$this->address = $value;
	}

	public function get_full_address_str() {
		$buffer = '';

		$address_number = $this->get_address_number();
		if ( !empty( $address_number ) && $address_number != 0 ) {
			$buffer = $address_number . ' ';
		}

		$address_number_complement = $this->get_address_number_comp();
		if ( !empty( $address_number_complement ) ) {
			$buffer .= $address_number_complement . ' ';
		}

		$buffer .= $this->get_address();

		return $buffer;
	}

	public function get_postal_code($complete_french = false) {
		$buffer = $this->postal_code;
		if ( $complete_french && strlen( $buffer ) == 4 ) {
			$buffer = '0' . $buffer;
		}

		return $buffer;
	}
	public function set_postal_code($value) {
		$this->postal_code = $value;
	}

	public function get_city() {
		return $this->city;
	}
	public function set_city($value) {
		$this->city = $value;
	}

	public function get_nationality() {
		return $this->nationality;
	}
	public function set_nationality($value) {
		$this->nationality = $value;
	}
	// Retourne le texte complet du pays à partir du code de nationalité
	public function get_country() {
		$nationality_code = $this->get_nationality();
		global $country_list;

		return $country_list[ $nationality_code ];
	}

	public function get_latitude() {
		return $this->latitude;
	}
	public function get_longitude() {
		return $this->longitude;
	}

	public function get_type() {
		return $this->type;
	}
	public function set_type($value) {
		if ($value == 'society') {
			$this->type = $value;
		}
	}

	public function get_legalform() {
		return $this->legalform;
	}
	public function set_legalform($value) {
		$this->legalform = $value;
	}

	//Supprime les espaces
	public function get_capital() {
		$temp_capital = $this->capital;
		if (preg_match('#\s#', $temp_capital)) {
			$temp_capital = str_replace(' ', '', $temp_capital);
			$this->set_capital($temp_capital);
		}

		return $temp_capital;
	}
	public function set_capital($value) {
		$this->capital = $value;
	}

	public function get_idnumber() {
		return $this->idnumber;
	}
	public function set_idnumber($value) {
		$this->idnumber = $value;
	}

	public function get_rcs() {
		return $this->rcs;
	}
	public function set_rcs($value) {
		$this->rcs = $value;
	}

	public function get_ape() {
		return $this->ape;
	}
	public function set_ape($value) {
		$this->ape = $value;
	}

	public function get_vat() {
		return $this->vat;
	}
	public function set_vat($value) {
		$this->vat = $value;
	}

	public function get_fiscal_year_end_month() {
		return $this->fiscal_year_end_month;
	}
	public function set_fiscal_year_end_month($value) {
		$this->fiscal_year_end_month = $value;
	}

	public function get_employees_count() {
		return $this->employees_count;
	}
	public function set_employees_count($value) {
		$this->employees_count = $value;
	}

	public function get_bank_owner() {
		$buffer = $this->bank_owner;
		if ( $buffer == '---' || empty( $buffer ) ) {
			$buffer = $this->get_name();
		}

		return $buffer;
	}
	public function set_bank_owner($value) {
		$this->bank_owner = $value;
	}

	public function get_bank_address() {
		return $this->bank_address;
	}
	public function set_bank_address($value) {
		$this->bank_address = $value;
	}

	public function get_bank_address2() {
		return $this->bank_address2;
	}
	public function set_bank_address2($value) {
		$this->bank_address2 = $value;
	}

	public function get_bank_iban() {
		return $this->bank_iban;
	}
	public function set_bank_iban($value) {
		$this->bank_iban = $value;
	}

	public function get_bank_bic() {
		return $this->bank_bic;
	}
	public function set_bank_bic($value) {
		$this->bank_bic = $value;
	}

	public function get_accountant_name() {
		return $this->accountant_name;
	}
	public function set_accountant_name($value) {
		$this->accountant_name = $value;
	}
	public function get_accountant_email() {
		return $this->accountant_email;
	}
	public function set_accountant_email($value) {
		$this->accountant_email = $value;
	}
	public function get_accountant_address() {
		return $this->accountant_address;
	}
	public function set_accountant_address($value) {
		$this->accountant_address = $value;
	}

	public function get_id_quickbooks() {
		return $this->id_quickbooks;
	}
	public function set_id_quickbooks($value) {
		if ( !empty( $value ) ) {
			$this->id_quickbooks = $value;
		}
	}

	public function get_language() {
		return $this->get_owner_language();
	}
	private function get_owner_language() {
		$linked_users_creator = $this->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
		if ( empty( $linked_users_creator ) ) {
			return;
		}
		$WDGUser_creator = $linked_users_creator[ 0 ];
		$WDGUser_creator->get_language();
	}

	/**
	 * Détermine si l'organisation a rempli ses informations nécessaires pour investir
	 * @return boolean
	 */
	public function has_filled_invest_infos() {
		global $organization_can_invest_errors;
		$organization_can_invest_errors = array();

		//Infos nécessaires pour tout type de financement
		if ($this->get_legalform() == '' || $this->get_legalform() == '---') {
			array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser la forme juridique de l'organisation", 'yproject'));
		}
		if ($this->get_idnumber() == '' || $this->get_idnumber() == '---') {
			array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le num&eacute;ro SIRET de l'organisation", 'yproject'));
		}
		if ($this->get_rcs() == '' || $this->get_rcs() == '---') {
			array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le RCS de l'organisation", 'yproject'));
		}
		if ($this->get_capital() == '' || $this->get_capital() == '---') {
			array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le capital de l'organisation", 'yproject'));
		}
		if ($this->get_address() == '' || $this->get_address() == '---') {
			array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser l'adresse de l'organisation", 'yproject'));
		}
		if ($this->get_postal_code() == '' || $this->get_postal_code() == '---') {
			array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le code postal de l'organisation", 'yproject'));
		}
		if ($this->get_city() == '' || $this->get_city() == '---') {
			array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser la ville de l'organisation", 'yproject'));
		}
		if ($this->get_nationality() == '' || $this->get_nationality() == '---') {
			array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le pays de l'organisation", 'yproject'));
		}

		return (empty($organization_can_invest_errors));
	}

	private $transfers;
	public function get_transfers() {
		if ( isset( $this->transfers ) ) {
			return $this->transfers;
		}

		$args = array(
		    'author'		=> $this->wpref,
		    'post_type'		=> 'withdrawal_order',
		    'post_status'	=> 'any',
		    'orderby'		=> 'post_date',
		    'order'			=>  'ASC',
		    'numberposts'	=>  -1
		);
		$this->transfers = get_posts($args);

		return $this->transfers;
	}

	public function get_pending_transfers() {
		$args = array(
			'author'    => $this->wpref,
			'post_type' => 'withdrawal_order',
			'post_status'   => 'pending'
		);
		$pending_transfers = get_posts($args);

		return $pending_transfers;
	}

	/**
	 * Liaisons utilisateurs
     * ATTENTION : L'organisation doit être déjà créée sur l'API (avec create()) avant d'y lier un compte
	 */
	public function set_creator($wp_user_id) {
		$wdg_current_user = new WDGUser( $wp_user_id );
		$api_user_id = $wdg_current_user->get_api_id();
		if ( !empty( $this->api_id ) && !empty( $api_user_id ) ) {
			WDGWPREST_Entity_Organization::link_user( $this->api_id, $api_user_id, WDGWPREST_Entity_Organization::$link_user_type_creator );
		}
	}

	/**
	 *
	 */
	public function get_linked_users($type = '') {
		$buffer = array();
		$result = WDGWPREST_Entity_Organization::get_linked_users( $this->api_id );
		if ( $result ) {
			foreach ( $result as $user_item ) {
				if ( empty( $type ) || $user_item->type == $type ) {
					$user_api = WDGWPREST_Entity_User::get( $user_item->id_user );
					if ( $user_api != FALSE ) {
						$user = new WDGUser( $user_api->wpref );
						array_push( $buffer, $user );
					}
				}
			}
		}

		return $buffer;
	}

	public function get_campaigns() {
		$buffer = array();
		$result = WDGWPREST_Entity_Organization::get_projects( $this->api_id );

		return $result;
	}

	/**
	 * Détermine si l'organisation a envoyé tous ses documents
	 */
	public function has_sent_all_documents() {
		$buffer = TRUE;
		$documents_type_list = array( WDGKYCFile::$type_kbis, WDGKYCFile::$type_status, WDGKYCFile::$type_id );
		foreach ( $documents_type_list as $document_type ) {
			$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wpref, WDGKYCFile::$owner_organization, $document_type );
			$current_document = $document_filelist[0];
			if ( !isset($current_document) ) {
				$buffer = FALSE;
				break;
			}
		}

		return $buffer;
	}

	/**
	 * Détermine si l'organisation a envoyé tous ses documents
	 */
	public function has_sent_orga_documents() {
		$buffer = TRUE;
		$documents_type_list = array( WDGKYCFile::$type_kbis, WDGKYCFile::$type_status );
		foreach ( $documents_type_list as $document_type ) {
			$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wpref, WDGKYCFile::$owner_organization, $document_type );
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
		if ($this->has_lemonway_wallet()) {
			$this->sync_creator_kyc();
			// on récupère tous les kyc de l'organisation
			$document_filelist = WDGKYCFile::get_list_by_owner_id($this->wpref, WDGKYCFile::$owner_organization);
			// on les parcourt
			foreach ($document_filelist as $kyc_document) {
				// on récupère le type LW selon le type "maison" et l'index
				$lemonway_type = LemonwayDocument::get_lw_document_id_from_document_type($kyc_document->type, $kyc_document->doc_index);
				$document_status = $this->get_document_lemonway_status($lemonway_type);
				//on vérifie le status du fichier, et on renvoie vers LW, si ce n'est pas un statut d'attente ou de validation
				if ($document_status !== LemonwayDocument::$document_status_waiting_verification &&  $document_status !== LemonwayDocument::$document_status_waiting &&  $document_status !== LemonwayDocument::$document_status_accepted) {
					// si ce fichier a besoin d'être uploadé vers LW et qu'il n'était pas sur l'API
					if (!$kyc_document->is_api_file) {
						// on le transfère sur l'API ce qui forcera son upload vers LW
						WDGKYCFile::transfer_file_to_api($kyc_document, WDGKYCFile::$owner_organization);
					} else if ( !isset($kyc_document->gateway_organization_id) && !isset($kyc_document->gateway_user_id) ) {
						WDGWPREST_Entity_FileKYC::send_to_lemonway($kyc_document->id);
					}
				}
			}
		}
	}

	/**
	 * Récupération des documents du créateur si jamais les documents ne sont pas remplis
	 */
	public function sync_creator_kyc() {
		// Récupération du créateur de l'organisation
		$linked_users_creator = $this->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
		if ( empty( $linked_users_creator ) ) {
			return;
		}
		$WDGUser_creator = $linked_users_creator[ 0 ];

		// Récupération de la liste des fichiers liés à ce créateur
		$kyc_list_by_owner_id = WDGKYCFile::get_list_by_owner_id( $WDGUser_creator->get_wpref(), WDGKYCFile::$owner_user );
		if ( count( $kyc_list_by_owner_id ) == 0 ) {
			return;
		}

		// Pour chacun des documents du créateur
		// Si l'organisation n'est pas définie
		// Et qu'il s'agit d'un document d'identité
		// On lie l'organisation au même document
		foreach ( $kyc_list_by_owner_id as $kyc_document ) {
			if ( $kyc_document->orga_id == 0 ) {
				switch ( $kyc_document->type ) {
					case WDGKYCFile::$type_id:
					case WDGKYCFile::$type_id_back:
					case WDGKYCFile::$type_id_2:
					case WDGKYCFile::$type_id_2_back:
					case WDGKYCFile::$type_passport:
					case WDGKYCFile::$type_tax:
					case WDGKYCFile::$type_welfare:
					case WDGKYCFile::$type_family:
					case WDGKYCFile::$type_birth:
					case WDGKYCFile::$type_driving:
						$kyc_document->orga_id = $this->get_wpref();
						$kyc_document->save();
						break;
				}
			}
		}
	}

	/**
	 * Récupère les infos du fichier uploadé concernant la banque
	 * @return fichier banque
	 */
	public function get_doc_bank() {
		$filelist_bank = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_bank);
		$file_bank = $filelist_bank[0];

		return (isset($file_bank)) ? $file_bank : null;
	}
	/**
	 * Récupère les infos du fichier uploadé concernant le kbis
	 * @return fichier kbis
	 */
	public function get_doc_kbis() {
		$filelist_kbis = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_kbis);
		$file_kbis = $filelist_kbis[0];

		return (isset($file_kbis)) ? $file_kbis : null;
	}
	/**
	 * Récupère les infos du fichier uploadé concernant les statuts
	 * @return fichier statuts
	 */
	public function get_doc_status() {
		$filelist_status = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_status);
		$file_status = $filelist_status[0];

		return (isset($file_status)) ? $file_status : null;
	}
	/**
	 * Récupère les infos du fichier uploadé concernant l'identité
	 * @return fichier identité
	 */
	public function get_doc_id() {
		$filelist_id = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_id);
		$file_id = $filelist_id[0];

		return (isset($file_id)) ? $file_id : null;
	}

	/*******************************************************************************
	 * Gestion RIB
	*******************************************************************************/
	/**
	 * Gère la mise à jour du RIB
	 */
	public function submit_bank_info($skip_save = FALSE) {
		$save = FALSE;
		if (filter_input(INPUT_POST, 'org_bankownername') != '') {
			$this->set_bank_owner(filter_input(INPUT_POST, 'org_bankownername'));
			$save = TRUE;
		}
		if (filter_input(INPUT_POST, 'org_bankowneraddress') != '') {
			$this->set_bank_address(filter_input(INPUT_POST, 'org_bankowneraddress'));
			$save = TRUE;
		}
		if (filter_input(INPUT_POST, 'org_bankowneraddress2') != '') {
			$this->set_bank_address2(filter_input(INPUT_POST, 'org_bankowneraddress2'));
			$save = TRUE;
		}
		if (filter_input(INPUT_POST, 'org_bankowneriban') != '') {
			$this->set_bank_iban(filter_input(INPUT_POST, 'org_bankowneriban'));
			$save = TRUE;
		}
		if (filter_input(INPUT_POST, 'org_bankownerbic') != '') {
			$this->set_bank_bic(filter_input(INPUT_POST, 'org_bankownerbic'));
			$save = TRUE;
		}
		if ( !$skip_save && $save ) {
			$this->save();
		}
	}

	/**
	 * Est-ce que le RIB est enregistré ?
	 */
	public function has_saved_iban() {
		$saved_iban = $this->get_bank_iban();

		return (!empty($saved_iban) && WDGRESTAPI_Lib_Validator::is_iban( $saved_iban ));
	}

	/*******************************************************************************
	 * Gestion transferts bancaires
	*******************************************************************************/
	/**
	 * Formulaire de transfert de fonds pour une organisation
	 */
	public function submit_transfer_wallet_lemonway() {
		// Vérifications sur le droit de poster le formulaire
		$form_posted = filter_input( INPUT_POST, 'submit_transfer_wallet_lemonway' );
		$WDGUser_current = WDGUser::current();
		$lemonway_balance = $this->get_lemonway_balance( 'campaign' );
		if ( $WDGUser_current->is_admin() && $form_posted == "1" && $lemonway_balance > 0 ) {
			$buffer = FALSE;

			//Il faut qu'un iban ait déjà été enregistré
			if ($this->has_saved_iban()) {
				//Vérification que des IBANS existent
				$wallet_details = $this->get_wallet_details();
				$first_iban = $wallet_details->IBANS->IBAN;
				//Sinon on l'enregistre auprès de Lemonway
				if (empty($first_iban)) {
					$result_iban = $this->register_lemonway_iban();
					if ($result_iban == FALSE) {
						$buffer = LemonwayLib::get_last_error_message();
					}
				}

				if ($buffer == FALSE) {
					// Récupération des montants à transférer
					$transfer_amount = filter_input( INPUT_POST, 'transfer_amount' );
					$transfer_amount = str_replace( ' ', '', $transfer_amount );
					$transfer_amount = str_replace( ',', '.', $transfer_amount );
					$transfer_commission = filter_input( INPUT_POST, 'transfer_commission' );
					$transfer_commission = str_replace( ' ', '', $transfer_commission );
					$transfer_commission = str_replace( ',', '.', $transfer_commission );
					LemonwayLib::ask_transfer_funds( $this->get_campaign_lemonway_id(), $this->get_lemonway_id(), ( $transfer_amount + $transfer_commission ) );
					if ( $transfer_amount > 0 ) {
						$this->transfer_wallet_to_bankaccount( $transfer_amount, $transfer_commission, 'campaign' );
					} else {
						LemonwayLib::ask_transfer_funds( $this->get_lemonway_id(), 'SC', $transfer_commission );
					}
				}
			}
		}
	}

	public function transfer_wallet_to_bankaccount($amount_without_commission, $amount_commission = 0, $wallet_type = '') {
		$buffer = __( 'account.transfert.AMOUNT_ERROR', 'yproject' );

		if ( !empty( $amount_without_commission ) ) {
			$lemonway_id = ( $wallet_type == 'campaign ') ? $this->get_campaign_lemonway_id() : $this->get_lemonway_id();
			$message = $this->get_name() . ' - WE DO GOOD';
			$result_transfer = LemonwayLib::ask_transfer_to_iban( $lemonway_id, $amount_without_commission + $amount_commission, 0, $amount_commission, $message );
			$buffer = ($result_transfer->TRANS->HPAY->ID) ? TRUE : $result_transfer->TRANS->HPAY->MSG;
			$post_type = 'withdrawal_order';
			if ( $amount_commission == 0 ) {
				$post_type .= '_lw';
			}

			if ( $buffer === TRUE ) {
				// Enregistrement de l'objet Lemon Way
				$withdrawal_post = array(
					'post_author'   => $this->get_wpref(),
					'post_title'    => $amount_without_commission,
					'post_content'  => print_r( $result_transfer, TRUE ),
					'post_status'   => 'publish',
					'post_type'		=> $post_type
				);
				wp_insert_post( $withdrawal_post );
			}
		}

		return $buffer;
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

	public function get_wallet_details($type = '', $reload = false, $by_email = false) {
		if ( !isset($this->{ 'wallet_details_' . $type }) || empty($this->{ 'wallet_details_' . $type }) || $reload == true ) {
			if ( $by_email ) {
				$this->{ 'wallet_details_' . $type } = LemonwayLib::wallet_get_details( FALSE, $this->get_email() );
			} else {
				switch ( $type ) {
					case 'campaign':
						$lemonway_id = $this->get_campaign_lemonway_id();
						break;
					case 'royalties':
						$lemonway_id = $this->get_royalties_lemonway_id();
						break;
					case 'tax':
						$lemonway_id = $this->get_tax_lemonway_id();
						break;
					default:
						$lemonway_id = $this->get_lemonway_id();
						break;
				}
				$this->{ 'wallet_details_' . $type } = LemonwayLib::wallet_get_details( $lemonway_id );
			}
			if ( false ) {
				$this->update_lemonway();
			}
		}

		return $this->{ 'wallet_details_' . $type };
	}

	public function has_lemonway_wallet($reload = false) {
		$buffer = FALSE;
		$wallet_details = $this->get_wallet_details( $reload );
		if ( isset( $wallet_details->NAME ) && !empty( $wallet_details->NAME ) ) {
			$buffer = TRUE;
		}

		return $buffer;
	}

	public function get_lemonway_registered_cards() {
		$wallet_details = $this->get_wallet_details();
		return LemonwayLib::wallet_get_registered_cards_from_wallet_details( $wallet_details );
	}

	public function unregister_card($id_card) {
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
				foreach ( $wallet_details->CARDS->CARD as $card_object ) {
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
	 * Enregistrement sur Lemonway
	 */
	public function register_lemonway($is_managing_project = FALSE) {
		if ( !$this->can_register_lemonway() ) {
			return FALSE;
		}

		//Vérifie que le wallet n'est pas déjà enregistré
		$wallet_details = $this->get_wallet_details();
		if ( !isset($wallet_details->NAME) || empty($wallet_details->NAME) ) {
			$linked_users_creator = $this->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			if ( !empty( $linked_users_creator ) ) {
				$WDGUser_creator = $linked_users_creator[ 0 ];
			} else {
				$WDGUser_creator = new WDGUser();
			}

			// Si on n'a pas défini la valeur par défaut, on fait le test si il y a des campagnes liées
			if ( !$is_managing_project ) {
				$campaign_list = $this->get_campaigns();
				$is_managing_project = !empty( $campaign_list );
			}
			$wallet_type = ( $is_managing_project ) ? LemonwayLib::$wallet_type_beneficiary : LemonwayLib::$wallet_type_payer;

			$result = LemonwayLib::wallet_company_register($this->get_lemonway_id(), $this->get_email(), html_entity_decode( $WDGUser_creator->wp_user->user_firstname ), html_entity_decode( $WDGUser_creator->wp_user->user_lastname ), html_entity_decode( $this->get_name() ), html_entity_decode( $this->get_description() ), $this->get_website(), $WDGUser_creator->get_country( 'iso3' ), $WDGUser_creator->get_lemonway_birthdate(), $WDGUser_creator->get_lemonway_phone_number(), $this->get_idnumber(), $wallet_type);

			$this->send_kyc();

			return $result;
		}

		return TRUE;
	}

	public static $lemonway_status_blocked = 'blocked';
	public static $lemonway_status_ready = 'ready';
	public static $lemonway_status_waiting = 'waiting';
	public static $lemonway_status_incomplete = 'incomplete';
	public static $lemonway_status_rejected = 'rejected';
	public static $lemonway_status_registered = 'registered';
	/**
	 * Détermine si les données sont bien remplies pour pouvoir enregistrer sur Lemonway
	 */
	public function can_register_lemonway() {
		$buffer = ( $this->get_name() != "" && $this->get_name() != "---" )
					&& ( $this->get_description() != "" && $this->get_description() != "---")
					&& ( $this->get_website() != "" && $this->get_website() != "---")
					&& ( $this->get_idnumber() != "" && $this->get_idnumber() != "---");

		return $buffer;
	}

	public function check_register_campaign_lemonway_wallet() {
		if ( !$this->can_register_lemonway() ) {
			return FALSE;
		}

		//Vérifie que le wallet n'est pas déjà enregistré
		$wallet_details = $this->get_wallet_details( 'campaign' );
		if ( !isset($wallet_details->NAME) || empty($wallet_details->NAME) ) {
			$linked_users_creator = $this->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			if ( !empty( $linked_users_creator ) ) {
				$WDGUser_creator = $linked_users_creator[ 0 ];
			} else {
				$WDGUser_creator = new WDGUser();
			}

			return LemonwayLib::wallet_company_register($this->get_campaign_lemonway_id(), $this->get_campaign_lemonway_email(), html_entity_decode( $WDGUser_creator->wp_user->user_firstname ), html_entity_decode( $WDGUser_creator->wp_user->user_lastname ), html_entity_decode( $this->get_name() ), html_entity_decode( $this->get_description() ), $this->get_website(), $WDGUser_creator->get_country( 'iso3' ), $WDGUser_creator->get_lemonway_birthdate(), $WDGUser_creator->get_lemonway_phone_number(), $this->get_idnumber(), LemonwayLib::$wallet_type_beneficiary, '1');
		}

		return TRUE;
	}

	public function check_register_royalties_lemonway_wallet() {
		if ( !$this->can_register_lemonway() ) {
			return FALSE;
		}

		//Vérifie que le wallet n'est pas déjà enregistré
		$wallet_details = $this->get_wallet_details( 'royalties' );
		if ( !isset($wallet_details->NAME) || empty($wallet_details->NAME) ) {
			$linked_users_creator = $this->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			if ( !empty( $linked_users_creator ) ) {
				$WDGUser_creator = $linked_users_creator[ 0 ];
			} else {
				$WDGUser_creator = new WDGUser();
			}

			return LemonwayLib::wallet_company_register($this->get_royalties_lemonway_id(), $this->get_royalties_lemonway_email(), html_entity_decode( $WDGUser_creator->wp_user->user_firstname ), html_entity_decode( $WDGUser_creator->wp_user->user_lastname ), html_entity_decode( $this->get_name() ), html_entity_decode( $this->get_description() ), $this->get_website(), $WDGUser_creator->get_country( 'iso3' ), $WDGUser_creator->get_lemonway_birthdate(), $WDGUser_creator->get_lemonway_phone_number(), $this->get_idnumber(), LemonwayLib::$wallet_type_beneficiary, '1');
		}

		return TRUE;
	}

	public function check_register_tax_lemonway_wallet() {
		if ( !$this->can_register_lemonway() ) {
			return FALSE;
		}

		//Vérifie que le wallet n'est pas déjà enregistré
		$wallet_details = $this->get_wallet_details( 'tax' );
		if ( !isset($wallet_details->NAME) || empty($wallet_details->NAME) ) {
			$linked_users_creator = $this->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			if ( !empty( $linked_users_creator ) ) {
				$WDGUser_creator = $linked_users_creator[ 0 ];
			} else {
				$WDGUser_creator = new WDGUser();
			}

			return LemonwayLib::wallet_company_register($this->get_tax_lemonway_id(), $this->get_tax_lemonway_email(), html_entity_decode( $WDGUser_creator->wp_user->user_firstname ), html_entity_decode( $WDGUser_creator->wp_user->user_lastname ), html_entity_decode( $this->get_name() ), html_entity_decode( $this->get_description() ), $this->get_website(), $WDGUser_creator->get_country( 'iso3' ), $WDGUser_creator->get_lemonway_birthdate(), $WDGUser_creator->get_lemonway_phone_number(), $this->get_idnumber(), LemonwayLib::$wallet_type_beneficiary, '1');
		}

		return TRUE;
	}

	/**
	 * Met à jour les données sur LW si nécessaire
	 */
	private function update_lemonway() {
		$WDGUser_creator = new WDGUser();
		LemonwayLib::wallet_company_update($this->get_lemonway_id(), $this->get_email(), $WDGUser_creator->wp_user->user_firstname, $WDGUser_creator->wp_user->user_lastname, $WDGUser_creator->get_country( 'iso3' ), $WDGUser_creator->get_lemonway_phone_number(), $WDGUser_creator->get_lemonway_birthdate(), $this->get_name(), $this->get_description(), $this->get_website(), $this->get_idnumber());
	}

	/**
	 * Retourne le statut de l'identification sur lemonway
	 */
	public function get_lemonway_status($force_reload = TRUE) {
		if ( $force_reload ) {
			$user_meta_status = get_user_meta( $this->wpref, WDGOrganization::$key_lemonway_status, TRUE );
			if ( $user_meta_status == WDGOrganization::$lemonway_status_registered ) {
				$buffer = $user_meta_status;
			} else {
				if (!$this->can_register_lemonway()) {
					$buffer = WDGOrganization::$lemonway_status_blocked;
				} else {
					$buffer = WDGOrganization::$lemonway_status_ready;
					$wallet_details = $this->get_wallet_details();
					if ( isset($wallet_details->STATUS) && !empty($wallet_details->STATUS) ) {
						switch ($wallet_details->STATUS) {
							case '2':
							case '8':
								$buffer = WDGOrganization::$lemonway_status_incomplete;
								break;
							case '3':
							case '9':
								$buffer = WDGOrganization::$lemonway_status_rejected;
								break;
							case '6':
								$buffer = WDGOrganization::$lemonway_status_registered;
								break;

							default:
							case '5':
								if ($wallet_details->DOCS && $wallet_details->DOCS->DOC) {
									foreach ($wallet_details->DOCS->DOC as $document_object) {
										if (isset($document_object->TYPE) && $document_object->TYPE !== FALSE) {
											switch ($document_object->S) {
												case '1':
													$buffer = WDGOrganization::$lemonway_status_waiting;
													break;
											}
										}
									}
								}
								break;
						}
					}
				}

				update_user_meta( $this->wpref, WDGOrganization::$key_lemonway_status, $buffer );
			}
		} else {
			$buffer = get_user_meta( $this->wpref, WDGOrganization::$key_lemonway_status, TRUE );
		}

		if ( $buffer == WDGOrganization::$lemonway_status_registered ) {
			$this->set_strong_authentication( true );
		}

		return $buffer;
	}

	/**
	 * Retourne si l'identification sur lemonway est validée
	 */
	public function is_registered_lemonway_wallet() {
		return ( $this->get_lemonway_status() == WDGOrganization::$lemonway_status_registered );
	}

	/**
	 * Détermine si l'utilisateur peut payer avec son porte-monnaie
	 * @param int $amount
	 * @param ATCF_Campaign $campaign
	 * @return bool
	 */
	public function can_pay_with_wallet($amount, $campaign, $amount_by_card = FALSE) {
		$lemonway_amount = $this->get_available_rois_amount();
		if ( !empty( $amount_by_card ) ) {
			$lemonway_amount += $amount_by_card;
		}

		return ($lemonway_amount > 0 && $lemonway_amount >= $amount && $campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway);
	}

	/**
	 * Détermine si l'utilisateur peut payer avec sa carte et son porte-monnaie
	 * @param int $amount
	 * @param ATCF_Campaign $campaign
	 * @return bool
	 */
	public function can_pay_with_card_and_wallet($amount, $campaign) {
		$lemonway_amount = $this->get_lemonway_balance();
		//Il faut de l'argent dans le porte-monnaie, que la campagne soit sur lemonway et qu'il reste au moins 5€ à payer par carte
		return ($lemonway_amount > 0 && $amount - $lemonway_amount > 5 && $campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway);
	}

	/**
	 * Donne l'argent disponible sur le compte utilisateur
	 */
	public function get_lemonway_balance($type = '') {
		$wallet_details = $this->get_wallet_details( $type );
		$buffer = 0;
		if (isset($wallet_details->BAL)) {
			$buffer = $wallet_details->BAL;
		}

		return $buffer;
	}

	public function get_lemonway_wallet_amount() {
		return $this->get_lemonway_balance();
	}

	public function register_lemonway_iban() {
		$saved_holdername = $this->get_bank_owner();
		$saved_iban = $this->get_bank_iban();
		$saved_bic = $this->get_bank_bic();
		$saved_dom1 = $this->get_bank_address();
		$saved_dom2 = $this->get_bank_address2();
		$result_iban = LemonwayLib::wallet_register_iban( $this->get_lemonway_id(), $saved_holdername, $saved_iban, $saved_bic, $saved_dom1, $saved_dom2 );

		return $result_iban;
	}

	/**
	 * Infos de mandat de prélèvement enregistrées localement
	 */
	public function get_encoded_mandate_info() {
		return json_encode( $this->mandate_info );
	}

	/**
	 * enregistre une info de mandat de prélèvement dans la BDD
	 */
	private function add_mandate_info($gateway, $b2b, $iban, $status, $save_api = true) {
		if ( empty( $this->mandate_info ) ) {
			$this->mandate_info = array();
		}
		if ( empty( $this->mandate_info[ $gateway ] ) ) {
			$this->mandate_info[ $gateway ][ 'core' ] = array();
			$this->mandate_info[ $gateway ][ 'b2b' ] = array();
		}
		$type = $b2b ? 'b2b' : 'core';
		$this->mandate_info[ $gateway ][ $type ] = array();
		$this->mandate_info[ $gateway ][ $type ][ 'iban' ] = $iban;
		$this->mandate_info[ $gateway ][ $type ][ 'status' ] = $status;
		$this->mandate_info[ $gateway ][ $type ][ 'approved_by_bank' ] = 0;

		if ( $save_api ) {
			$this->update_api();
		}
	}

	/**
	 * met à jour les infos de mandat de prélèvement dans la BDD
	 */
	public function update_mandate_info($gateway, $iban, $status, $approved_by_bank) {
		if ( !empty( $this->mandate_info[ $gateway ][ 'b2b' ] ) ) {
			if ( !empty( $status ) ) {
				$this->mandate_info[ $gateway ][ 'b2b' ][ 'status' ] = $status;
			}
			if ( $approved_by_bank === '0' || $approved_by_bank === '1' ) {
				$this->mandate_info[ $gateway ][ 'b2b' ][ 'approved_by_bank' ] = $approved_by_bank;
			}
		} else {
			if ( empty( $this->mandate_info[ $gateway ][ 'core' ] ) && !empty( $iban ) && !empty( $status ) ) {
				$this->add_mandate_info( $gateway, 'core', $iban, $status, false );
			}
			if ( !empty( $status ) ) {
				$this->mandate_info[ $gateway ][ 'core' ][ 'status' ] = $status;
			}
		}

		$this->update_api();
	}

	/**
	 * retourne TRUE si c'est un mandat b2b
	 */
	public function is_mandate_b2b() {
		return !empty( $this->mandate_info[ 'lemonway' ][ 'b2b' ] );
	}

	/**
	 * retourne TRUE si c'est un mandat b2b et si la banque l'a validé
	 */
	public function is_mandate_b2b_approved_by_bank() {
		return !empty( $this->mandate_info[ 'lemonway' ][ 'b2b' ] && $this->mandate_info[ 'lemonway' ][ 'b2b' ][ 'approved_by_bank' ] == 1 );
	}

	public function get_mandate_file_url() {
		return $this->mandate_file_url;
	}

	/**
	 * Liste les mandats enregistrés auprès de LW
	 * @return array
	 */
	public function get_lemonway_mandates() {
		$wallet_details = $this->get_wallet_details();
		$buffer = array();
		if ( isset( $wallet_details->SDDMANDATES ) && isset( $wallet_details->SDDMANDATES->SDDMANDATE ) ) {
			foreach ( $wallet_details->SDDMANDATES->SDDMANDATE as $mandate_temp ) {
				if ( isset( $mandate_temp->ID ) ) {
					$return_item = array(
						"ID"	=> $mandate_temp->ID,
						"S"		=> $mandate_temp->S,
						"DATA"	=> $mandate_temp->DATA,
						"SWIFT"	=> $mandate_temp->SWIFT
					);
					array_push( $buffer, $return_item );
				}
			}
			if ( count( $buffer ) == 0 ) {
				foreach ( $wallet_details->SDDMANDATES as $mandate_temp ) {
					$return_item = array(
						"ID"	=> $mandate_temp->ID,
						"S"		=> $mandate_temp->S,
						"DATA"	=> $mandate_temp->DATA,
						"SWIFT"	=> $mandate_temp->SWIFT
					);
					array_push( $buffer, $return_item );
				}
			}
		}

		return $buffer;
	}

	/**
	 * Ajoute un mandat de prélévement lié au wallet de l'organisation
	 */
	public function add_lemonway_mandate() {
		// Enregistrement infos mandat B2B
		$this->add_mandate_info( 'lemonway', true, $this->get_bank_iban(), 0 );

		return LemonwayLib::wallet_register_mandate( $this->get_lemonway_id(), $this->get_bank_owner(), $this->get_bank_iban(), $this->get_bank_bic(), 1, 1, $this->get_full_address_str(), $this->get_postal_code(), $this->get_city(), $this->get_country() );
	}

	/**
	 * Retourne un token pour se rendre sur la page d'acceptation de mandat de prélèvement
	 */
	public function get_sign_mandate_token($phone_number, $url_return, $url_error) {
		// Récupération du dernier mandat de la liste
		$mandate_list = $this->get_lemonway_mandates();
		$last_mandate = end( $mandate_list );

		return LemonwayLib::wallet_sign_mandate_init( $this->get_lemonway_id(), $phone_number, $last_mandate['ID'], $url_return, $url_error );
		;
	}

	/**
	 * Retourne TRUE si le dernier mandat créé est signé
	 */
	public function has_signed_mandate() {
		$buffer = FALSE;
		$mandates_list = $this->get_lemonway_mandates();
		if ( !empty( $mandates_list ) ) {
			$last_mandate = end( $mandates_list );
			$last_mandate_status = $last_mandate[ "S" ];
			$this->update_mandate_info( 'lemonway', $last_mandate[ 'DATA' ], $last_mandate_status, FALSE );
			$buffer = ( $last_mandate_status == 5 || $last_mandate_status == 6 );
		}

		return $buffer;
	}

	public function get_mandate_infos_str() {
		$buffer = '';
		$mandates_list = $this->get_lemonway_mandates();
		if ( !empty( $mandates_list ) ) {
			$last_mandate = end( $mandates_list );
			$last_mandate_status = $last_mandate[ "S" ];
			if ( $last_mandate_status == 5 || $last_mandate_status == 6 ) {
				$buffer = $last_mandate[ "DATA" ] . ' (' . $last_mandate[ "SWIFT" ] . ')';
			}
		}

		return $buffer;
	}

	public function remove_lemonway_mandate($mandate_id) {
		LemonwayLib::wallet_unregister_mandate( $this->get_lemonway_id(), $mandate_id );
	}

	/**
	 * Retourne true si le RIB est validé sur Lemon Way
	 */
	public function is_document_lemonway_registered($document_type) {
		$lemonway_document = LemonwayDocument::get_by_id_and_type( $this->get_lemonway_id(), $document_type, $this->get_wallet_details() );

		return ( $lemonway_document->get_status() == LemonwayDocument::$document_status_accepted );
	}

	public function get_document_lemonway_status($document_type) {
		$lemonway_document = LemonwayDocument::get_by_id_and_type( $this->get_lemonway_id(), $document_type, $this->get_wallet_details() );

		return $lemonway_document->get_status();
	}

	public function get_document_lemonway_error($document_type) {
		$lemonway_document = LemonwayDocument::get_by_id_and_type( $this->get_lemonway_id(), $document_type, $this->get_wallet_details() );

		return $lemonway_document->get_error_str();
	}

	public function has_document_lemonway_error($document_type) {
		$rib_lemonway_error = $this->get_document_lemonway_error( $document_type );

		return ( !empty( $rib_lemonway_error ) );
	}

	public function get_transactions() {
		if ( !$this->is_registered_lemonway_wallet() ) {
			return FALSE;
		}

		if ( empty( $this->api_data->gateway_list ) || empty( $this->api_data->gateway_list[ 'lemonway' ] ) ) {
			$this->save();
		}

		return WDGWPREST_Entity_Organization::get_transactions( $this->get_api_id() );
	}

	public function get_viban() {
		if ( !$this->is_registered_lemonway_wallet() ) {
			return FALSE;
		}

		if ( empty( $this->api_data->gateway_list ) || empty( $this->api_data->gateway_list[ 'lemonway' ] ) ) {
			$this->save();
		}

		$iban_info = WDGWPREST_Entity_Organization::get_viban( $this->get_api_id() );

		$buffer = array();
		if ( empty( $iban_info ) ) {
			$buffer[ 'error' ] = '1';
			$buffer[ 'holder' ] = LemonwayLib::$lw_wire_holder;
			$buffer[ 'iban' ] = LemonwayLib::$lw_wire_iban;
			$buffer[ 'bic' ] = LemonwayLib::$lw_wire_bic;
			$buffer[ 'backup' ] = array();
			$buffer[ 'backup' ][ 'lemonway_id' ] = LemonwayLib::$lw_wire_id_prefix . $this->get_lemonway_id();

		} else {
			$buffer[ 'holder' ] = $iban_info->HOLDER;
			$buffer[ 'iban' ] = $iban_info->DATA;
			$buffer[ 'bic' ] = $iban_info->SWIFT;
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
				// de même si cet iban a LEMON WAY comme holder (viban)
				if ( count( $wallet_details->IBANS->IBAN ) > 1 && ( $buffer->S == WDGUser::$iban_status_disabled || $buffer->S == WDGUser::$iban_status_rejected || strtolower( str_replace(' ', '', $buffer->HOLDER) ) == WDGUser::$iban_holder_lw ) ) {
					foreach ( $wallet_details->IBANS->IBAN as $iban_item ) {
						if ( ( $iban_item->S == WDGUser::$iban_status_validated || $iban_item->S == WDGUser::$iban_status_waiting ) && strtolower( str_replace(' ', '', $iban_item->HOLDER) ) != WDGUser::$iban_holder_lw ) {
							$buffer = $iban_item;
						}
					}
				}
			} else {
				$iban_item = $wallet_details->IBANS->IBAN;
				if ( ( $iban_item->S == WDGUser::$iban_status_validated || $iban_item->S == WDGUser::$iban_status_waiting ) && strtolower( str_replace(' ', '', $iban_item->HOLDER) ) != WDGUser::$iban_holder_lw ) {
					$buffer = $iban_item;
				}
			}
		}

		return $buffer;
	}

	public function get_lemonway_iban_status() {
		$first_iban = $this->get_lemonway_iban();
		if ( !empty( $first_iban ) ) {
			return $first_iban->S;
		} else {
			return FALSE;
		}
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

	public function get_investments($payment_status) {
		return $this->get_user_investments_object()->get_investments( $payment_status );
	}
	public function get_validated_investments($trimestre = false) {
		return $this->get_user_investments_object()->get_validated_investments($trimestre);
	}
	public function get_count_validated_investments() {
		$list = $this->get_user_investments_object()->get_posts_investments( 'publish' );
		return count( $list );
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
	public function has_pending_wire_investments() {
		return $this->get_user_investments_object()->has_pending_wire_investments();
	}
	public function get_pending_wire_investments() {
		return $this->get_user_investments_object()->get_pending_wire_investments();
	}

	/*******************************************************************************
	 * Gestion royalties
	*******************************************************************************/
	private $rois;
	public function get_rois() {
		if ( !isset( $this->rois ) ) {
			$this->rois = WDGWPREST_Entity_Organization::get_rois( $this->get_api_id() );
		}

		return $this->rois;
	}

	/**
	 * Retourne la somme des royalties perçues
	 * @return float
	 */
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

	/**
	 *
	 */
	public function get_available_rois_amount() {
		$buffer = $this->get_lemonway_balance();

		return $buffer;
	}

	/**
	 * Récupération des sommes déjà transférées sur le compte bancaire
	 */
	public function get_transferred_amount() {
		$buffer = 0;
		$args = array(
			'author'		=> $this->wpref,
			'post_type'		=> 'withdrawal_order_lw',
			'post_status'	=> 'any',
			'orderby'		=> 'post_date',
			'order'			=> 'ASC',
			'showposts'		=> -1
		);
		$transfers = get_posts($args);
		foreach ( $transfers as $post_transfer ) {
			$post_transfer = get_post( $post_transfer );
			$buffer += $post_transfer->post_title;
		}

		return $buffer;
	}

	private $royalties_per_year;
	/**
	 * Retourne la liste des royalties d'une année
	 * @param int $year
	 * @return array
	 */
	public function get_royalties_for_year($year) {
		if ( !isset( $this->royalties_per_year ) ) {
			$this->royalties_per_year = array();
		}
		if ( !isset( $this->royalties_per_year[ $year ] ) ) {
			$this->royalties_per_year[ $year ] = array();
			$rois = $this->get_rois();
			if ( !empty( $rois ) ) {
				foreach ( $rois as $roi_item ) {
					$roi_date_transfer = new DateTime( $roi_item->date_transfer );
					if ( $roi_date_transfer->format('Y') == $year ) {
						array_push( $this->royalties_per_year[ $year ], $roi_item );
					}
				}
			}
		}

		return $this->royalties_per_year[ $year ];
	}

	/**
	 * Retourne TRUE si l'utilisateur a reçu des royalties pour l'année en paramètre
	 * @param int $year
	 * @return boolean
	 */
	public function has_royalties_for_year($year) {
		$royalties_list = $this->get_royalties_for_year( $year );

		return ( count( $royalties_list ) > 0 );
	}

	/**
	 * Retourne la liste des royalties par campagne
	 * @param int $campaign_id
	 * @return array
	 */
	public function get_royalties_by_campaign_id($campaign_id) {
		$buffer = array();

		if ( empty( $campaign_api_id ) ) {
			$campaign_api_id = get_post_meta( $campaign_id, ATCF_Campaign::$key_api_id, TRUE );
		}

		$rois = $this->get_rois();
		foreach ( $rois as $roi_item ) {
			if ( $roi_item->id_project == $campaign_api_id ) {
				array_push( $buffer, $roi_item );
			}
		}

		return $buffer;
	}

	/**
	 * Retourne la liste des royalties par id d'investissement
	 * @return array
	 */
	public function get_royalties_by_investment_id($investment_id, $status = 'transferred') {
		$buffer = array();
		$rois = $this->get_rois();
		if ( !empty( $rois ) ) {
			foreach ( $rois as $roi_item ) {
				if ( $roi_item->id_investment == $investment_id ) {
					if ( empty( $status ) || $roi_item->status == $status ) {
						array_push( $buffer, $roi_item );
					}
				}
			}
		}

		return $buffer;
	}

	/**
	 * Retourne la liste des royalties par id de contrat d'investissement
	 * @return array
	 */
	public function get_royalties_by_investment_contract_id($investment_contract_id, $status = 'transferred') {
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
	 * Retourne le nom du fichier de certificat
	 * @return string
	 */
	private function get_royalties_yearly_certificate_filename($year) {
		$buffer = 'certificate-roi-' .$year. '-user-' .$this->creator->id. '.pdf';

		return $buffer;
	}

	/**
	 * Retourne le lien vers l'attestation de royalties d'une année
	 * - Si le fichier n'existe pas, crée le fichier auparavant
	 * @param int $year
	 * @return string
	 */
	public function get_royalties_certificate_per_year($year, $force = false) {
		$filename = $this->get_royalties_yearly_certificate_filename( $year );
		$buffer = site_url() . '/wp-content/plugins/appthemer-crowdfunding/files/certificate-roi-yearly-user/' . $filename;
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

			$WDGInvestment = new WDGInvestment( $invest_id );
			$campaign = $WDGInvestment->get_saved_campaign();

			if ( !empty( $campaign ) ) {
				// Infos campagne et organisations
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
				$invest_item_amount = $WDGInvestment->get_saved_amount();

				// Infos royalties liés
				$invest_item['roi_list'] = array();
				$invest_item['roi_total'] = 0;
				$invest_item['roi_for_year'] = 0;
				$invest_item['taxed_for_year'] = 0;
				$investment_royalties = $this->get_royalties_by_investment_id( $invest_id );
				foreach ( $investment_royalties as $investment_roi ) {
					$date_transfer = new DateTime( $investment_roi->date_transfer );
					// On ne compte dans le total de royalties perçues que si ça a été versé lors d'une année écoulée
					if ( $date_transfer->format( 'Y' ) <= $year ) {
						$invest_item['roi_total'] += $investment_roi->amount;
					}
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
							// Certains vieux roi ne sont pas définis sur le montant imposable
							// Si c'est défini, on reprend le montant déjà calculé
							if ( $investment_roi->amount_taxed_in_cents > 0 ) {
								$investment_roi_taxed = $investment_roi->amount_taxed_in_cents / 100;
							// Sinon, on prend le minimum entre le montant reçu sur ce versement ET la différence entre le montant reçu au total et le montant investi
							} else {
								$investment_roi_taxed = min( $investment_roi->amount, $invest_item['roi_total'] - $invest_item_amount );
							}
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
		$html_content = WDG_Template_PDF_Certificate_ROI_Yearly_User::get($this->get_name(), $this->get_idnumber(), $this->get_vat(), '', $this->get_email(), $this->get_full_address_str(), $this->get_postal_code(), $this->get_city(), '01/01/'.($year + 1), $year, $investment_list, UIHelpers::format_number( $roi_total ). ' &euro;', UIHelpers::format_number( $taxed_total ). ' &euro;', $info_yearly_certificate);

		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_html2pdf();
		$h2p_instance = HTML2PDFv5Helper::instance();
		$h2p_instance->writePDF( $html_content, $filepath );

		return $buffer;
	}

	public function has_tax_document_for_year($year) {
		$buffer = FALSE;
		$tax_exemption_filename = get_user_meta( $this->get_wpref(), 'tax_document_' .$year, TRUE );
		if ( !empty( $tax_exemption_filename ) ) {
			$buffer = site_url( '/wp-content/plugins/appthemer-crowdfunding/files/tax-documents/' .$year. '/' .$tax_exemption_filename );
		}

		return $buffer;
	}

	/*******************************************************************************
	 * Fonctions statiques
	*******************************************************************************/
	/**
	 * Retourne TRUE si l'utilisateur dont l'id est passé en paramètre est une organisation
	 * @param type $user_id
	 */
	public static function is_user_organization($user_id) {
		$result = get_user_meta($user_id, WDGOrganization::$key_api_id, TRUE);

		return (isset($result) && !empty($result));
	}
}