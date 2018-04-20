<?php
/**
 * Classe de gestion des organisations
 */
class WDGOrganization {
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
	private $bank_owner;
	private $bank_address;
	private $bank_iban;
	private $bank_bic;
	private $id_quickbooks;
	
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
	public static function createSimpleOrganization($user_id, $orga_name, $orga_email){
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
	public function __construct($user_id = FALSE) {
		if ($user_id === FALSE) {
			$user_id = filter_input(INPUT_GET, 'orga_id');
		}
			
		if (!empty($user_id)) {
			$this->creator = get_user_by('id', $user_id);
			$this->api_id = get_user_meta($user_id, WDGOrganization::$key_api_id, TRUE);
			$this->bopp_object = WDGWPREST_Entity_Organization::get( $this->api_id );
			$this->wpref = $user_id;
			
			$this->name = $this->bopp_object->name;
			
			$this->email = $this->bopp_object->email;
			if ( empty( $this->email ) ) {
				$meta_email = get_user_meta( $user_id, 'orga_contact_email', TRUE );
				if (empty($meta_email)) {
					$this->email = $this->creator->user_email;
				} else {
					$this->email = $meta_email;
				}
			}
			
			$this->representative_function = $this->bopp_object->representative_function;
			$this->description = $this->bopp_object->description;
			if ( empty( $this->description ) ) {
				$this->description = get_user_meta( $user_id, WDGOrganization::$key_description, TRUE );
			}
			$this->website = $this->bopp_object->website_url;
			$this->strong_authentication = $this->bopp_object->strong_authentication;
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
			$geolocation = explode( ',', $this->bopp_object->geolocation );
			if ( count( $geolocation ) > 1 ) {
				$this->latitude = $geolocation[0];
				$this->longitude = $geolocation[1];
			}
			
			$this->bank_owner = $this->bopp_object->bank_owner;
			$this->bank_address = $this->bopp_object->bank_address;
			$this->bank_iban = $this->bopp_object->bank_iban;
			$this->bank_bic = $this->bopp_object->bank_bic;
			
			$this->id_quickbooks = $this->bopp_object->id_quickbooks;
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
		
		if ($this->get_name() == "") { array_push( $errors_create_orga, __("Merci de remplir le nom de l'organisation", 'yproject') ); }
		if ($this->get_email() == "") { array_push( $errors_create_orga, __("Merci de remplir l'adresse e-mail de l'organisation", 'yproject') ); }
		if ( email_exists( $this->get_email() ) ) { array_push( $errors_create_orga, __("L'e-mail est d&eacute;j&agrave; utilis&eacute.", 'yproject') ); }
		if ($this->get_type() == "") { array_push( $errors_create_orga, __("Merci de remplir le type de l'organisation", 'yproject') ); }
		if ($this->get_description() == "") { array_push( $errors_create_orga, __("Merci de remplir le descriptif de l'activit&eacute;", 'yproject') ); }
		if ($this->get_legalform() == "") { array_push( $errors_create_orga, __("Merci de remplir la forme juridique de l'organisation", 'yproject') ); }
		if ($this->get_idnumber() == "") { array_push( $errors_create_orga, __("Merci de remplir le num&eacute;ro SIREN de l'organisation", 'yproject') ); }
		if ($this->get_rcs() == "") { array_push( $errors_create_orga, __("Merci de remplir le RCS de l'organisation", 'yproject') ); }
		if ($this->get_capital() == "") { $this->set_capital(0); }
		if ($this->get_ape() == "") { array_push( $errors_create_orga, __("Merci de remplir le code APE de l'organisation", 'yproject') ); }
		if ($this->get_address() == "") { array_push( $errors_create_orga, __("Merci de remplir l'adresse de l'organisation", 'yproject') ); }
		if ($this->get_postal_code() == "") { array_push( $errors_create_orga, __("Merci de remplir le code postal de l'organisation", 'yproject') ); }
		if ($this->get_city() == "") { array_push( $errors_create_orga, __("Merci de remplir la ville de l'organisation", 'yproject') ); }
		if ($this->get_nationality() == "") { array_push( $errors_create_orga, __("Merci de remplir le pays de l'organisation", 'yproject') ); }
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
                
		if ( $this->get_bank_owner() == '' ) { $this->set_bank_owner("---"); }
		if ( $this->get_bank_address() == '' ) { $this->set_bank_address("---"); }
		if ( $this->get_bank_iban() == '' ) { $this->set_bank_iban("---"); }
		if ( $this->get_bank_bic() == '' ) { $this->set_bank_bic("---"); }
		
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
		WDGWPREST_Entity_Organization::update( $this );
		
		$new_mail = $this->get_email();
		$meta_email = get_user_meta( $this->wpref, 'orga_contact_email', TRUE );
		if (empty($meta_email) && !email_exists($new_mail)) {
			wp_update_user( array ( 'ID' => $this->wpref, 'user_email' => $new_mail ) );
		} else {
			update_user_meta( $this->wpref, 'orga_contact_email', $new_mail );
		}
	}
	
	/**
	 * Retourne une organisation via l'id de l'API
	 * @param int $api_id
	 */
	public static function get_by_api_id( $api_id ) {
		$buffer = FALSE;
		if ( !empty( $api_id ) ) {
			$api_data = WDGWPREST_Entity_Organization::get( $api_id );
			$buffer = new WDGOrganization( $api_data->wpref );
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
			$wallet_details_by_email = $this->get_wallet_details( true, true );
			if ( isset( $wallet_details_by_email->ID ) ) {
				$db_lw_id = $wallet_details_by_email->ID;
				
			} else {
				$db_lw_id = 'ORGA'.$this->api_id.'W'.$this->wpref;
				if ( defined( YP_LW_USERID_PREFIX ) ) {
					$db_lw_id = YP_LW_USERID_PREFIX . $db_lw_id;
				}
			}
			
			update_user_meta( $this->wpref, 'lemonway_id', $db_lw_id );
		}
		return $db_lw_id;
	}
	
	
	public function get_wpref() {
		return $this->wpref;
	}
	public function set_wpref($value) {
		$this->wpref = $value;
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
	public function set_representative_function( $value ) {
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
	
	public function get_address() {
		return $this->address;
	}
	public function set_address($value) {
		$this->address = $value;
	}
	
	public function get_postal_code( $complete_french = false ) {
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
		$this->type = $value;
	}
	
	public function get_legalform() {
		return $this->legalform;
	}
	public function set_legalform($value) {
		$this->legalform = $value;
	}
	
	public function get_capital() {
		return $this->capital;
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
	
	public function get_bank_owner() {
		return $this->bank_owner;
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
	
	public function get_id_quickbooks() {
		return $this->id_quickbooks;
	}
	public function set_id_quickbooks($value) {
		if ( !empty( $value ) ) {
			$this->id_quickbooks = $value;
		}
	}
	
	/**
	 * Détermine si l'organisation a rempli ses informations nécessaires pour investir
	 * @return boolean
	 */
	public function has_filled_invest_infos() {
		global $organization_can_invest_errors;
		$organization_can_invest_errors = array();
		
		//Infos nécessaires pour tout type de financement
		if ($this->get_type() != 'society') { array_push($organization_can_invest_errors, __("Ce type d'organisation ne peut pas investir.", 'yproject')); }
		if ($this->get_legalform() == '') { array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser la forme juridique de l'organisation", 'yproject')); }
		if ($this->get_idnumber() == '') { array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le num&eacute;ro SIREN de l'organisation", 'yproject')); }
		if ($this->get_rcs() == '') { array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le RCS de l'organisation", 'yproject')); }
		if ($this->get_capital() == '') { array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le capital de l'organisation", 'yproject')); }
		if ($this->get_address() == '') { array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser l'adresse de l'organisation", 'yproject')); }
		if ($this->get_postal_code() == '') { array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le code postal de l'organisation", 'yproject')); }
		if ($this->get_city() == '') { array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser la ville de l'organisation", 'yproject')); }
		if ($this->get_nationality() == '') { array_push($organization_can_invest_errors, __("Merci de pr&eacute;ciser le pays de l'organisation", 'yproject')); }
		
		return (empty($organization_can_invest_errors));
	}
	
	public function get_transfers() {
		$args = array(
		    'author'    => $this->wpref,
		    'post_type' => 'withdrawal_order',
		    'post_status' => 'any',
		    'orderby'   => 'post_date',
		    'order'     =>  'ASC'
		);
		$transfers = get_posts($args);
		return $transfers;
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
	public function set_creator( $wp_user_id ) {
		$wdg_current_user = new WDGUser( $wp_user_id );
		$api_user_id = $wdg_current_user->get_api_id();
		if ( !empty( $this->api_id ) && !empty( $api_user_id ) ) {
			WDGWPREST_Entity_Organization::link_user( $this->api_id, $api_user_id, WDGWPREST_Entity_Organization::$link_user_type_creator );
		}
	}
	
	/**
	 * 
	 */
	public function get_linked_users( $type = '' ) {
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
	
	/**
	 * Gère les documents à enregistrer en local
	 */
	public function submit_documents() {
		global $errors_submit;
		if ( empty( $errors_submit ) ) {
			$errors_submit = new WP_Error();
		}
		
		$documents_list = array(
			'org_doc_bank'					=> WDGKYCFile::$type_bank,
			'org_doc_kbis'					=> WDGKYCFile::$type_kbis,
			'org_doc_status'				=> WDGKYCFile::$type_status,
			'org_doc_id'					=> WDGKYCFile::$type_id,
			'org_doc_home'					=> WDGKYCFile::$type_home,
			'org_doc_capital_allocation'	=> WDGKYCFile::$type_capital_allocation,
			'org_doc_id_2'					=> WDGKYCFile::$type_id_2,
			'org_doc_home_2'				=> WDGKYCFile::$type_home_2,
			'org_doc_id_3'					=> WDGKYCFile::$type_id_3,
			'org_doc_home_3'				=> WDGKYCFile::$type_home_3
		);
		$files_info = array();//stocke les infos des fichiers uploadés
		$notify = 0;
		foreach ($documents_list as $document_key => $document_type) {
			$files_info[$document_key]['date'] = "";
			if ( isset( $_FILES[$document_key]['tmp_name'] ) && !empty( $_FILES[$document_key]['tmp_name'] ) ) {
				$result = WDGKYCFile::add_file( $document_type, $this->get_wpref(), WDGKYCFile::$owner_organization, $_FILES[$document_key] );
				if ($result == 'ext') {
					$errors_submit->add('document-wrong-extension', __("Le format de fichier n'est pas accept&eacute;.", 'yproject'));
					$files_info[$document_key]['code'] = 1;
					$files_info[$document_key]['info'] = $errors_submit->get_error_message('document-wrong-extension');
				} 
				else if ($result == 'size') {
					$errors_submit->add('document-heavy-size', __("Le fichier est trop lourd.", 'yproject'));
					$files_info[$document_key]['code'] = 1;
					$files_info[$document_key]['info'] = $errors_submit->get_error_message('document-heavy-size');
				} else if ($result != FALSE) {
					$notify++;
					$kycfile = new WDGKYCFile($result);
					$filepath = $kycfile->get_public_filepath();
					$date_upload = $kycfile->get_date_uploaded();
					$files_info[$document_key]['code'] = 0;
					$files_info[$document_key]['info'] = $filepath;
					$files_info[$document_key]['date'] = __("T&eacute;l&eacute;charger le fichier envoy&eacute; le ", 'yproject').$date_upload;
				}
			}
			else {
				$files_info[$document_key]['code'] = 0;
				$files_info[$document_key]['info'] = null;
			}
		}
		if ($notify > 0) {
			NotificationsEmails::document_uploaded_admin($this, $notify);
		}
		return $files_info;
	}
	/**
	 * Détermine si l'organisation a envoyé tous ses documents
	 */
	public function has_sent_all_documents() {
		$buffer = TRUE;
		$documents_type_list = array( WDGKYCFile::$type_kbis, WDGKYCFile::$type_status, WDGKYCFile::$type_id, WDGKYCFile::$type_home );
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
		if (isset($_POST['authentify_lw']) && $this->can_register_lemonway()) {
			if ( $this->register_lemonway() ) {
				$documents_type_list = array( 
					WDGKYCFile::$type_bank		=> '2',
					WDGKYCFile::$type_kbis		=> '7',
					WDGKYCFile::$type_status	=> '11',
					WDGKYCFile::$type_id		=> '0',
					WDGKYCFile::$type_home		=> '1',
					WDGKYCFile::$type_capital_allocation		=> '20',
					WDGKYCFile::$type_id_2		=> '16',
					WDGKYCFile::$type_home_2	=> '17',
					WDGKYCFile::$type_id_3		=> '18',
					WDGKYCFile::$type_home_3	=> '19'
				);
				foreach ( $documents_type_list as $document_type => $lemonway_type ) {
					$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wpref, WDGKYCFile::$owner_organization, $document_type );
					if ( count( $document_filelist ) > 0 ) {
						$current_document = $document_filelist[0];
						LemonwayLib::wallet_upload_file( $this->get_lemonway_id(), $current_document->file_name, $lemonway_type, $current_document->get_byte_array() );
					}
				}
			}
		}
	}
	
	/**
	 * Récupère les infos du fichier uploadé concernant la banque
	 * @return fichier banque
	 */
	public function get_doc_bank(){
		$filelist_bank = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_bank);
		$file_bank = $filelist_bank[0];
		return (isset($file_bank)) ? $file_bank : null;
	}
	/**
	 * Récupère les infos du fichier uploadé concernant le kbis
	 * @return fichier kbis
	 */
	public function get_doc_kbis(){
		$filelist_kbis = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_kbis);
		$file_kbis = $filelist_kbis[0];
		return (isset($file_kbis)) ? $file_kbis : null;
	}
	/**
	 * Récupère les infos du fichier uploadé concernant les statuts
	 * @return fichier statuts
	 */
	public function get_doc_status(){
		$filelist_status = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_status);
		$file_status = $filelist_status[0];
		return (isset($file_status)) ? $file_status : null;
	}
	/**
	 * Récupère les infos du fichier uploadé concernant l'identité
	 * @return fichier identité
	 */
	public function get_doc_id(){
		$filelist_id = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_id);
		$file_id = $filelist_id[0];
		return (isset($file_id)) ? $file_id : null;
	}
	/**
	 * Récupère les infos du fichier uploadé concernant le domicile
	 * @return fichier domicile
	 */
	public function get_doc_home(){
		$filelist_home = WDGKYCFile::get_list_by_owner_id($this->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_home);
		$file_home = $filelist_home[0];
		return (isset($file_home)) ? $file_home : null;
	}

/*******************************************************************************
 * Gestion RIB
*******************************************************************************/
	/**
	 * Gère la mise à jour du RIB
	 */
	public function submit_bank_info( $skip_save = FALSE ) {
		$save = FALSE;
		if (filter_input(INPUT_POST, 'org_bankownername') != '') {
			$this->set_bank_owner(filter_input(INPUT_POST, 'org_bankownername'));
			$save = TRUE;
		}
		if (filter_input(INPUT_POST, 'org_bankowneraddress') != '') {
			$this->set_bank_address(filter_input(INPUT_POST, 'org_bankowneraddress'));
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
		$saved_holdername = $this->get_bank_owner();
		return (!empty($saved_holdername));
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
		$lemonway_balance = $this->get_lemonway_balance();
		if ( $WDGUser_current->is_admin() && $form_posted == "1" && $lemonway_balance > 0 ) {
			
			$buffer = FALSE;

			//Il faut qu'un iban ait déjà été enregistré
			if ($this->has_saved_iban()) {
				//Vérification que des IBANS existent
				$wallet_details = $this->get_wallet_details();
				$first_iban = $wallet_details->IBANS->IBAN;
				//Sinon on l'enregistre auprès de Lemonway
				if (empty($first_iban)) {
					$saved_holdername = $this->get_bank_owner();
					$saved_iban = $this->get_bank_iban();
					$saved_bic = $this->get_bank_bic();
					$saved_dom1 = $this->get_bank_address();
					$result_iban = LemonwayLib::wallet_register_iban( $this->get_lemonway_id(), $saved_holdername, $saved_iban, $saved_bic, $saved_dom1 );
					if ($result_iban == FALSE) {
						$buffer = LemonwayLib::get_last_error_message();
					}
				}
				
				if ($buffer == FALSE) {
					// Récupération des montants à transférer
					$transfer_amount = filter_input( INPUT_POST, 'transfer_amount' );
					$transfer_commission = filter_input( INPUT_POST, 'transfer_commission' );
					$this->transfer_to_iban( $transfer_amount, $transfer_commission );
				}
			}
		}
	}
	
	public function transfer_wallet_to_bankaccount( $amount_without_commission, $amount_commission = 0 ) {
		$buffer = FALSE;
		
		if ( !empty( $amount_without_commission ) ) {
			$result_transfer = LemonwayLib::ask_transfer_to_iban( $this->get_lemonway_id(), $amount_without_commission + $amount_commission, 0, $amount_commission );
			$buffer = ($result_transfer->TRANS->HPAY->ID) ? TRUE : $result_transfer->TRANS->HPAY->MSG;

			if ( $buffer === TRUE ) {
				// Enregistrement de l'objet Lemon Way
				$withdrawal_post = array(
					'post_author'   => $this->get_wpref(),
					'post_title'    => $amount_without_commission,
					'post_content'  => print_r( $result_transfer, TRUE ),
					'post_status'   => 'publish',
					'post_type'		=> 'withdrawal_order'
				);
				wp_insert_post( $withdrawal_post );
			}
		}
		
		return $buffer;
	}
	
/*******************************************************************************
 * Gestion Lemonway
*******************************************************************************/
	private function get_wallet_details( $reload = false, $by_email = false ) {
		if ( !isset($this->wallet_details) || empty($this->wallet_details) || $reload == true ) {
			if ( $by_email ) {
				$this->wallet_details = LemonwayLib::wallet_get_details( FALSE, $this->get_email() );
			} else {
				$this->wallet_details = LemonwayLib::wallet_get_details( $this->get_lemonway_id() );
			}
			if ( false ) {
				$this->update_lemonway();
			}
		}
		return $this->wallet_details;
	}
	
	/**
	 * Enregistrement sur Lemonway
	 */
	public function register_lemonway() {
		if ( !$this->can_register_lemonway() ) {
			return FALSE;
		}
		
		//Vérifie que le wallet n'est pas déjà enregistré
		$wallet_details = $this->get_wallet_details();
		if ( !isset($wallet_details->NAME) || empty($wallet_details->NAME) ) {
			$WDGUser_creator = new WDGUser();
			return LemonwayLib::wallet_company_register(
				$this->get_lemonway_id(),
				$this->get_email(),
				html_entity_decode( $WDGUser_creator->wp_user->user_firstname ),
				html_entity_decode( $WDGUser_creator->wp_user->user_lastname ),
				html_entity_decode( $this->get_name() ),
				html_entity_decode( $this->get_description() ),
				$this->get_website(),
				$WDGUser_creator->get_country( 'iso3' ),
				$WDGUser_creator->get_lemonway_birthdate(),
				$WDGUser_creator->get_lemonway_phone_number(),
				$this->get_idnumber(),
				LemonwayLib::$wallet_type_beneficiary
			);
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
		$buffer = ($this->get_name() != "")
					&& ($this->get_description() != "")
					&& ($this->get_idnumber() != "")
					&& $this->has_sent_all_documents();
		return $buffer;
	}
	
	/**
	 * Met à jour les données sur LW si nécessaire
	 */
	private function update_lemonway() {
		$WDGUser_creator = new WDGUser();
		LemonwayLib::wallet_company_update(
			$this->get_lemonway_id(),
			$this->get_email(),
			$WDGUser_creator->wp_user->user_firstname,
			$WDGUser_creator->wp_user->user_lastname,
			$WDGUser_creator->get_country( 'iso3' ),
			$WDGUser_creator->get_lemonway_phone_number(),
			$WDGUser_creator->get_lemonway_birthdate(),
			$this->get_name(),
			$this->get_description(),
			$this->get_website(),
			$this->get_idnumber()
		);
	}
	
	/**
	 * Retourne le statut de l'identification sur lemonway
	 */
	public function get_lemonway_status( $force_reload = TRUE ) {
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
									foreach($wallet_details->DOCS->DOC as $document_object) {
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
	public function can_pay_with_wallet( $amount, $campaign ) {
		$lemonway_amount = $this->get_lemonway_balance();
		return ($lemonway_amount > 0 && $lemonway_amount >= $amount && $campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway);
	}
	
	/**
	 * Détermine si l'utilisateur peut payer avec sa carte et son porte-monnaie
	 * @param int $amount
	 * @param ATCF_Campaign $campaign
	 * @return bool
	 */
	public function can_pay_with_card_and_wallet( $amount, $campaign ) {
		$lemonway_amount = $this->get_lemonway_balance();
		//Il faut de l'argent dans le porte-monnaie, que la campagne soit sur lemonway et qu'il reste au moins 5€ à payer par carte
		return ($lemonway_amount > 0 && $amount - $lemonway_amount > 5 && $campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway);
	}
	
	/**
	 * Donne l'argent disponible sur le compte utilisateur
	 */
	public function get_lemonway_balance() {
		$wallet_details = $this->get_wallet_details();
		$buffer = 0;
		if (isset($wallet_details->BAL)) {
			$buffer = $wallet_details->BAL;
		}
		return $buffer;
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
		return LemonwayLib::wallet_register_mandate( $this->get_lemonway_id(), $this->get_bank_owner(), $this->get_bank_iban(), $this->get_bank_bic(), 1, 0, $this->get_address(), $this->get_postal_code(), $this->get_city(), $this->get_country() );
	}
	
	/**
	 * Retourne un token pour se rendre sur la page d'acceptation de mandat de prélèvement
	 */
	public function get_sign_mandate_token( $phone_number, $url_return, $url_error ) {
		// Récupération du dernier mandat de la liste
		$mandate_list = $this->get_lemonway_mandates();
		$last_mandate = end( $mandate_list );
		return LemonwayLib::wallet_sign_mandate_init( $this->get_lemonway_id(), $phone_number, $last_mandate['ID'], $url_return, $url_error );;
	}
	
	public function has_signed_mandate() {
		$buffer = FALSE;
		$mandates_list = $this->get_lemonway_mandates();
		if ( !empty( $mandates_list ) ) {
			$last_mandate = end( $mandates_list );
			$last_mandate_status = $last_mandate[ "S" ];
			$buffer = ( $last_mandate_status == 5 || $last_mandate_status == 6 );
		}
		return $buffer;
	}
	
	public function remove_lemonway_mandate( $mandate_id ) {
		LemonwayLib::wallet_unregister_mandate( $this->get_lemonway_id(), $mandate_id );
	}
	
	/**
	 * Retourne true si le RIB est validé sur Lemon Way
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
				$buffer += $roi_item->amount;
			}
		}
		return $buffer;
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
	public function has_royalties_for_year( $year ) {
		$royalties_list = $this->get_royalties_for_year( $year );
		return ( count( $royalties_list ) > 0 );
	}
	
	/**
	 * Retourne le nom du fichier de certificat
	 * @return string
	 */
	private function get_royalties_yearly_certificate_filename( $year ) {
		$buffer = 'certificate-roi-' .$year. '-user-' .$this->creator->id. '.pdf';
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
			$this->get_name(),
			$this->get_idnumber(),
			$this->get_vat(),
			'',
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
	
	/**
	 * Formulaire de nouvelle organisation
	 */
	public static function submit_new($redirect = TRUE) {
		$errors_edit = array();
		$errors_submit_new = new WP_Error();
				
		//Dans le TB, data-action = save_new_organization
		if($redirect){
			//Vérification que l'on a posté le formulaire
			$action = filter_input(INPUT_POST, 'action');
			if ($action !== 'save_new_organization') {
				return FALSE;
			}
		}

		//Vérification que l'utilisateur est connecté
		if (!is_user_logged_in()) {
			$errors_submit_new->add('not-loggedin', __('Vous devez vous connecter.', 'yproject'));
			return FALSE;
		} else {
			$current_user = wp_get_current_user();
		}
		
		//Vérification de la case à cocher
		if (filter_input(INPUT_POST, 'org_capable', FILTER_VALIDATE_BOOLEAN) !== TRUE) {
			$errors_submit_new->add('not-capable', __('Vous devez cocher la case pour certifier que vous &ecirc;tes en capacit&eacute; de repr&eacute;senter l&apos;organisation.', 'yproject'));
			$errors_edit['org_capable'] = $errors_submit_new->get_error_message('not-capable');
		}
		
		//Vérification du code postal
		$org_postal_code = filter_input(INPUT_POST, 'org_postal_code');
		if (substr($org_postal_code, 0, 1) === '0') { $org_postal_code = substr($org_postal_code, 1); }
		$org_postal_code = filter_var($org_postal_code, FILTER_VALIDATE_INT);
		if ($org_postal_code === FALSE) {
			$errors_submit_new->add('postalcode-not-integer', __('Le code postal doit &ecirc;tre un nombre entier.', 'yproject'));
			$errors_edit['org_postal_code'] = $errors_submit_new->get_error_message('postalcode-not-integer');
		} else {
			if (strlen($org_postal_code) === 4) { $org_postal_code = '0' . $org_postal_code; }
		}

		//Vérification du capital
		$org_capital = filter_input(INPUT_POST, 'org_capital', FILTER_VALIDATE_INT);
		if ($org_capital === FALSE || $org_capital === 0) {
			$errors_submit_new->add('capital-not-integer', __('Le capital doit &ecirc;tre un nombre entier et sup&eacute;rieur &agrave; z&eacute;ro.', 'yproject'));
			$errors_edit['org_capital'] = $errors_submit_new->get_error_message('capital-not-integer');
		}

		//Vérification du code APE
		$org_ape = filter_input(INPUT_POST, 'org_ape');
		if ( empty( $org_ape ) ) {
			$errors_submit_new->add('ape-not-valid', __('Le code APE ne doit pas &ecirc;tre vide.', 'yproject'));
			$errors_edit['org_ape'] = $errors_submit_new->get_error_message('ape-not-valid');
		}
		
		//Vérification des données obligatoires
		$necessary_fields = array(
				'd&eacute;nomination sociale' => 'org_name',
				'e-mail' => 'org_email',
				'descriptif de l\'activit&eacute;' => 'org_description',
				'adresse' => 'org_address',
				'ville' => 'org_city',
				'pays' => 'org_nationality',
				'forme juridique' =>'org_legalform',
				'num&eacute;ro SIREN' =>'org_idnumber',
				'APE' =>'org_ape',
				'RCS' => 'org_rcs'
			);
		foreach ($necessary_fields as $name => $field) {
			$value = filter_input(INPUT_POST, $field);
			if ($value === "") {
				$errors_submit_new->add('empty_'.$field, __('Le champ', 'yproject').' '.$name.' '.__('ne doit pas &ecirc;tre vide.', 'yproject'));
				$errors_edit[$field] = $errors_submit_new->get_error_message('empty_'.$field);
			}
		}

		//Si on n'a pas d'erreur, on crée l'organisation
		if(count($errors_edit) == 0){
			//Création de l'objet organisation
			global $current_user;
			$org_object = new WDGOrganization();
			$org_object->set_strong_authentication(FALSE);
			$org_object->set_name(filter_input(INPUT_POST, 'org_name'));
			$org_object->set_email(filter_input(INPUT_POST, 'org_email'));
			$org_object->set_representative_function(filter_input(INPUT_POST, 'org_representative_function'));
			$org_object->set_description(filter_input(INPUT_POST, 'org_description'));
			$org_object->set_address(filter_input(INPUT_POST, 'org_address'));
			$org_object->set_postal_code($org_postal_code);
			$org_object->set_city(filter_input(INPUT_POST, 'org_city'));
			$org_object->set_nationality(filter_input(INPUT_POST, 'org_nationality'));
			$org_object->set_type('society');
			$org_object->set_legalform(filter_input(INPUT_POST, 'org_legalform'));
			$org_object->set_capital($org_capital);
			$org_object->set_idnumber(filter_input(INPUT_POST, 'org_idnumber'));
			$org_object->set_rcs(filter_input(INPUT_POST, 'org_rcs'));
			$org_object->set_ape(filter_input(INPUT_POST, 'org_ape'));
			$org_object->set_vat(filter_input(INPUT_POST, 'org_vat'));
			$org_object->set_fiscal_year_end_month(filter_input(INPUT_POST, 'org_fiscal_year_end_month'));
			$org_object->set_bank_owner(filter_input(INPUT_POST, 'org_bankownername'));
			$org_object->set_bank_address(filter_input(INPUT_POST, 'org_bankowneraddress'));
			$org_object->set_bank_iban(filter_input(INPUT_POST, 'org_bankowneriban'));
			$org_object->set_bank_bic(filter_input(INPUT_POST, 'org_bankownerbic'));
			$wp_orga_user_id = $org_object->create();

			if ($wp_orga_user_id !== FALSE) {
				$org_object->set_creator($current_user->ID);
				if($redirect){
					if (session_id() == '') session_start();
					if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] == 'new_organization') {
						$_SESSION['redirect_current_invest_type'] = $wp_orga_user_id;
						wp_redirect(ypcf_login_gobackinvest_url());
						exit();

					} else {
						wp_safe_redirect( home_url( '/mon-compte' ) );
						exit();
					}
				}
			}
		}else{
			$org_object = null;
		}
		$return['org_object'] = $org_object;
		$return['errors_edit'] = $errors_edit;
		return $return;
	}
	
	public static function edit($org_object) {
		global $errors_edit;
		$errors_edit = new WP_Error();
		
		$errors_data = WDGOrganization::control_data();
		//Vérification que l'on a posté le formulaire
		$action = filter_input(INPUT_POST, 'action');
		if ($action !== 'save_edit_organization') { 
			return FALSE;
		}
		
		//Vérification que l'utilisateur est connecté
		if (!is_user_logged_in()) {
			$errors_edit->add('not-loggedin', __('Vous devez vous connecter.', 'yproject'));
		}
		
		//On poursuit la procédure
		if (count($errors_edit->errors) > 0) {
			return FALSE;
		}
		
		if (count($errors_data) == 0) {
			$org_object->set_email(filter_input(INPUT_POST, 'org_email'));
			$org_object->set_representative_function(filter_input(INPUT_POST, 'org_representative_function'));
			$org_object->set_description(filter_input(INPUT_POST, 'org_description'));
			$org_object->set_legalform(filter_input(INPUT_POST, 'org_legalform'));
			$org_object->set_idnumber(filter_input(INPUT_POST, 'org_idnumber'));
			$org_object->set_rcs(filter_input(INPUT_POST, 'org_rcs'));
			$org_object->set_capital(filter_input(INPUT_POST, 'org_capital'));
			$org_object->set_ape(filter_input(INPUT_POST, 'org_ape'));
			$org_object->set_vat(filter_input(INPUT_POST, 'org_vat'));
			$org_object->set_fiscal_year_end_month(filter_input(INPUT_POST, 'org_fiscal_year_end_month'));
			$org_object->set_address(filter_input(INPUT_POST, 'org_address'));
			$org_object->set_postal_code(filter_input(INPUT_POST, 'org_postal_code'));
			$org_object->set_city(filter_input(INPUT_POST, 'org_city'));
			$org_object->set_nationality(filter_input(INPUT_POST, 'org_nationality'));
			$org_object->set_id_quickbooks( filter_input( INPUT_POST, 'org_id_quickbooks' ) );
			$org_object->submit_bank_info( TRUE );
			$org_object->save();
			$files_info = $org_object->submit_documents();
		} else {
			$files_info = null;
		}
		$return['files_info'] = $files_info;
		$return['errors_edit'] = $errors_data;
		return $return;
	}
	/**
	 * Fonction de contrôle des données pour les formulaires de création et d'édition
	 * @return array messages d'erreurs
	 */
	public static function control_data() {
		$errors_edit = array();
		$errors_submit = new WP_Error();

		//Vérification du code postal
		$org_postal_code = filter_input(INPUT_POST, 'org_postal_code');
		if (substr($org_postal_code, 0, 1) === '0') { $org_postal_code = substr($org_postal_code, 1); }
		$org_postal_code = filter_var($org_postal_code, FILTER_VALIDATE_INT);
		if ($org_postal_code === FALSE) {
			$errors_submit->add('postalcode-not-integer', __('Le code postal doit &ecirc;tre un nombre entier.', 'yproject'));
			$errors_edit['org_postal_code'] = $errors_submit->get_error_message('postalcode-not-integer');
		} else {
			if (strlen($org_postal_code) === 4) { $org_postal_code = '0' . $org_postal_code; }
		}

		//Vérification du capital
		$org_capital = filter_input(INPUT_POST, 'org_capital', FILTER_VALIDATE_INT);
		if ($org_capital === FALSE || $org_capital === 0) {
			$errors_submit->add('capital-not-integer', __('Le capital doit &ecirc;tre un nombre entier et sup&eacute;rieur &agrave; z&eacute;ro.', 'yproject'));
			$errors_edit['org_capital'] = $errors_submit->get_error_message('capital-not-integer');
		}

		//Vérification du code APE
		$org_ape = filter_input(INPUT_POST, 'org_ape');
		if ($org_ape == 0) {
			$errors_submit->add('ape-not-valid', __('Le code APE ne doit pas &ecirc;tre &eacute;gal &agrave; z&eacute;ro.', 'yproject'));
			$errors_edit['org_ape'] = $errors_submit->get_error_message('ape-not-valid');
		}

		//Vérification des données obligatoires
		$necessary_fields = array(
				'd&eacute;nomination sociale' => 'org_name',
				'e-mail' => 'org_email',
				'descriptif de l\'activit&eacute;' => 'org_description',
				'adresse' => 'org_address',
				'ville' => 'org_city',
				'pays' => 'org_nationality',
				'forme juridique' =>'org_legalform',
				'num&eacute;ro SIREN' =>'org_idnumber',
				'APE' =>'org_ape',
				'RCS' => 'org_rcs'
			);
		foreach ($necessary_fields as $name => $field) {
			$value = filter_input(INPUT_POST, $field);
			if ($value === "") {
				$errors_submit->add('empty_'.$field, __('Le champ', 'yproject').' '.$name.' '.__('ne doit pas &ecirc;tre vide.', 'yproject'));
				$errors_edit[$field] = $errors_submit->get_error_message('empty_'.$field);
			}
		}
		return $errors_edit;
	}

}