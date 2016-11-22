<?php
/**
 * Classe de gestion des organisations
 */
class YPOrganisation {
	/**
	 * Clés d'accès à l'api BOPP
	 */
	public static $key_bopp_id = 'organisation_bopp_id';
	public static $key_description = 'description';
	public static $key_lemonway_status = 'lemonway_status';
	
	/**
	 * Données
	 */
	private $creator;
	private $bopp_id;
	private $bopp_object;
	private $mangopay_id;
	private $wpref;
	private $name;
	private $email;
	private $description;
	private $strong_authentication;
	private $address;
	private $postal_code;
	private $city;
	private $nationality;
	private $type;
	private $legalform;
	private $capital;
	private $idnumber;
	private $rcs;
	private $ape;
	private $bank_owner;
	private $bank_address;
	private $bank_iban;
	private $bank_bic;
	
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
     * @return bool|YPOrganisation The new organisation or FALSE if failure
     */
	public static function createSimpleOrganisation($user_id, $orga_name, $orga_email){
        $org_object = new YPOrganisation();
        $org_object->set_strong_authentication(FALSE);
        $org_object->set_name($orga_name);
        $org_object->set_email($orga_email);

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
        $org_object->set_bank_owner('---');
        $org_object->set_bank_address('---');
        $org_object->set_bank_iban('---');
        $org_object->set_bank_bic('---');

        $org_user_id = $org_object->create();
        if($org_user_id==false) return false;
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
			$this->bopp_id = get_user_meta($user_id, YPOrganisation::$key_bopp_id, TRUE);
			$this->bopp_object = BoppOrganisations::get($this->bopp_id);
			$this->wpref = $user_id;
			
			$this->name = $this->bopp_object->organisation_name;
			
			$meta_email = get_user_meta( $user_id, 'orga_contact_email', TRUE );
			if (empty($meta_email)) {
				$this->email = $this->creator->user_email;
			} else {
				$this->email = $meta_email;
			}
			
			$this->description = get_user_meta($user_id, YPOrganisation::$key_description, TRUE);
			$this->strong_authentication = $this->bopp_object->organisation_strong_authentication;
			$this->address = $this->bopp_object->organisation_address;
			$this->postal_code = $this->bopp_object->organisation_postalcode;
			$this->city = $this->bopp_object->organisation_city;
			$this->nationality = $this->bopp_object->organisation_country;
			$this->type = $this->bopp_object->organisation_type;
			$this->legalform = $this->bopp_object->organisation_legalform;
			$this->capital = $this->bopp_object->organisation_capital;
			$this->idnumber = $this->bopp_object->organisation_idnumber;
			$this->rcs = $this->bopp_object->organisation_rcs;
			$this->ape = $this->bopp_object->organisation_ape;
			
			$this->bank_owner = $this->bopp_object->organisation_bank_owner;
			$this->bank_address = $this->bopp_object->organisation_bank_address;
			$this->bank_iban = $this->bopp_object->organisation_bank_iban;
			$this->bank_bic = $this->bopp_object->organisation_bank_bic;
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
		if ($this->get_type() == "") { array_push( $errors_create_orga, __("Merci de remplir le type de l'organisation", 'yproject') ); }
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
			return FALSE;
		}
		
		$organisation_user_id = $this->create_user($this->get_name());
		$this->set_wpref($organisation_user_id);
		
		//Si il y a eu une erreur lors de la création de l'utilisateur, on arrête la procédure
		if (isset($organisation_user_id->errors) && count($organisation_user_id->errors) > 0) {
			$errors_submit_new = $organisation_user_id;
			return FALSE;
		}
                
		if ( $this->get_bank_owner() == '' ) { $this->set_bank_owner("---"); }
		if ( $this->get_bank_address() == '' ) { $this->set_bank_address("---"); }
		if ( $this->get_bank_iban() == '' ) { $this->set_bank_iban("---"); }
		if ( $this->get_bank_bic() == '' ) { $this->set_bank_bic("---"); }
		
		$return_obj = BoppOrganisations::create(
			$this->get_wpref(),
			$this->get_name(), 
			FALSE,
			$this->get_type(), 
			$this->get_legalform(), 
			$this->get_idnumber(), 
			$this->get_rcs(), 
			$this->get_capital(), 
			$this->get_address(), 
			$this->get_postal_code(), 
			$this->get_city(), 
			$this->get_nationality(), 
			$this->get_ape(),
			$this->get_bank_owner(),
			$this->get_bank_address(),
			$this->get_bank_iban(),
			$this->get_bank_bic()
		);
		$this->bopp_id = $return_obj;
		
		//Vérification si on reçoit bien un entier pour identifiant
		if (filter_var($this->bopp_id, FILTER_VALIDATE_INT) === FALSE) {
			array_push( $errors_create_orga, __("Probl&egrave;me interne de cr&eacute;ation d'organisation.", 'yproject') );
			return FALSE;
		}
		
		update_user_meta($organisation_user_id, YPOrganisation::$key_bopp_id, $this->bopp_id);
		
		return $organisation_user_id;
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
		$organisation_user_id = wp_create_user($username, $password, $email);
		if (email_exists($email_input) && !empty($email_input)) {
			update_user_meta($organisation_user_id, 'orga_contact_email', $email_input);
		}
		return $organisation_user_id;
	}
	
	/**
	 * Enregistre les modifications sur l'api bopp
	 */
	public function save() {
		BoppOrganisations::update($this->bopp_id, 
			$this->get_strong_authentication(),
			$this->get_type(), 
			$this->get_legalform(), 
			$this->get_idnumber(), 
			$this->get_rcs(), 
			$this->get_capital(), 
			$this->get_address(), 
			$this->get_postal_code(), 
			$this->get_city(), 
			$this->get_nationality(), 
			$this->get_ape(),
			$this->get_bank_owner(),
			$this->get_bank_address(),
			$this->get_bank_iban(),
			$this->get_bank_bic()
		);
		update_user_meta( $this->wpref, YPOrganisation::$key_description, $this->get_description() );
		
		
		$new_mail = $this->get_email();
		$meta_email = get_user_meta( $this->wpref, 'orga_contact_email', TRUE );
		if (empty($meta_email) && !email_exists($new_mail)) {
			wp_update_user( array ( 'ID' => $this->wpref, 'user_email' => $new_mail ) );
		} else {
			update_user_meta( $this->wpref, 'orga_contact_email', $new_mail );
		}
	}
	
	/**
	 * Attributions / Récupération de données
	 */
	public function get_creator() {
		return $this->creator;
	}
	public function get_bopp_id() {
		return $this->bopp_id;
	}
	
	public function get_mangopay_id() {
		if (!isset($this->mangopay_id)) {
			$this->mangopay_id = ypcf_mangopay_get_mp_user_id($this->get_wpref());
		}
		return $this->mangopay_id;
	}
	/**
	 * Définir l'identifiant de l'orga sur lemonway
	 */
	public function get_lemonway_id() {
		return 'ORGA'.$this->bopp_id.'W'.$this->wpref;
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
	public function get_description() {
		return $this->description;
	}
	public function set_description($value) {
		$this->description = $value;
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
	
	public function get_postal_code() {
		return $this->postal_code;
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
		require_once("country_list.php");
		global $country_list;
		return $country_list[ $nationality_code ];
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
	
	
	public function get_wallet_amount() {
		return 0; //ypcf_mangopay_get_user_personalamount_by_wpid($this->get_wpref()) / 100;
	}
	
	public function get_operations() {
		return 0; //ypcf_mangopay_get_operations_by_user_id($this->get_mangopay_id());
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
	
	public function transfer_wallet($beneficiary_id) {
		$mp_amount = $this->get_wallet_amount() * 100;
//		$withdrawal_obj = ypcf_mangopay_make_withdrawal($this->get_wpref(), $beneficiary_id, $mp_amount);

		//Si il y a une erreur lors du retrait
		if (is_string($withdrawal_obj)) {
			return $withdrawal_obj;

		//Enregistrer le withdrawal pour garder une trace
		} else {
			//Enregistrement de l'id du withdrawal (en tant que post wp)
			$withdrawal_post = array(
			    'post_author'   => $this->get_wpref(),
			    'post_title'    => $mp_amount,
			    'post_content'  => $withdrawal_obj->ID,
			    'post_status'   => 'pending',
			    'post_type'	    => 'withdrawal_order'
			);
			wp_insert_post( $withdrawal_post );
			
			return TRUE;
		}
	}
	
	/**
	 * Liaisons utilisateurs
     * ATTENTION : L'organisation doit être déjà créée sur l'API (avec create()) avant d'y lier un compte
	 */
	public function set_creator($wp_user_id) {
		$bopp_user_id = BoppLibHelpers::get_api_user_id($wp_user_id);
		BoppLibHelpers::check_create_role(BoppLibHelpers::$organisation_creator_role['slug'], BoppLibHelpers::$organisation_creator_role['title']);
		BoppOrganisations::link_user_to_organisation($this->bopp_id, $bopp_user_id, BoppLibHelpers::$organisation_creator_role['slug']);
	}
	
	/**
	 * Mise à jour du statut de strong authentication
	 */
	public function check_strong_authentication() {
		$save = FALSE;
		/*
		switch ($this->strong_authentication) {
			case 0:
				//Vérifie si les docs ont été vérifiés
				if (ypcf_mangopay_is_user_strong_authenticated($this->wpref)) {
					$this->strong_authentication = '1';
					$save = TRUE;
				} else {
					$this->strong_authentication = '5';
					$save = TRUE;
				}
			    break;
			case 1:
			    //Envoyé et vérifié, on ne fait rien
			    break;
			case 5:
			    //Vérifie si les docs ont été vérifiés
			    if (ypcf_mangopay_is_user_strong_authenticated($this->wpref)) {
				    $this->strong_authentication = '1';
				    $save = TRUE;
			    }
			    break;
		}
		 * 
		 */
		if ($save == TRUE) {
			$this->save();
		}
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
			'org_doc_bank'		=> WDGKYCFile::$type_bank,
			'org_doc_kbis'		=> WDGKYCFile::$type_kbis,
			'org_doc_status'	=> WDGKYCFile::$type_status,
			'org_doc_id'		=> WDGKYCFile::$type_id,
			'org_doc_home'		=> WDGKYCFile::$type_home
		);
		$notify = 0;
		foreach ($documents_list as $document_key => $document_type) {
			if ( isset( $_FILES[$document_key]['tmp_name'] ) && !empty( $_FILES[$document_key]['tmp_name'] ) ) {
				$result = WDGKYCFile::add_file( $document_type, $this->get_wpref(), WDGKYCFile::$owner_organization, $_FILES[$document_key] );
				if ($result == 'ext') {
					$errors_submit->add('document-wrong-extension', __("Le format de fichier n'est pas accept&eacute;.", 'yproject'));
				} else if ($result == 'size') {
					$errors_submit->add('document-heavy-size', __("Le fichier est trop lourd.", 'yproject'));
				} else if ($result != FALSE) {
					$notify++;
				}
			}
		}
		if ($notify > 0) {
			NotificationsEmails::document_uploaded_admin($this, $notify);
		}
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
					WDGKYCFile::$type_home		=> '1'
				);
				foreach ( $documents_type_list as $document_type => $lemonway_type ) {
					$document_filelist = WDGKYCFile::get_list_by_owner_id( $this->wpref, WDGKYCFile::$owner_organization, $document_type );
					$current_document = $document_filelist[0];
					LemonwayLib::wallet_upload_file( $this->get_lemonway_id(), $current_document->file_name, $lemonway_type, $current_document->get_byte_array() );
				}
			}
		}
	}
	
/*******************************************************************************
 * Gestion RIB
*******************************************************************************/
	/**
	 * Gère la mise à jour du RIB
	 */
	public function submit_bank_info() {
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
		if ($save) {
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
	public function submit_transfer_wallet() {
		/*
		global $errors_submit;
		$errors_submit = new WP_Error();
		
		if ($this->get_wallet_amount() > 0 && filter_input(INPUT_POST, 'mangopaytoaccount') != '') {
			$beneficiary_id = ypcf_mangopay_get_mp_user_beneficiary_id($this->get_wpref());
			if ($beneficiary_id == '' && $this->get_bank_owner() != '' && $this->get_bank_address() != '' && $this->get_bank_iban() != '' && $this->get_bank_bic() != '') {
				$beneficiary_id = ypcf_init_mangopay_beneficiary(
					$this->get_wpref(),
					$this->get_bank_owner(),
					$this->get_bank_address(),
					$this->get_bank_iban(),
					$this->get_bank_bic()
				);
			}
			if ($beneficiary_id != '') {
				$result = $this->transfer_wallet($beneficiary_id);
				if ($result !== TRUE) {
					$errors_submit->add('transfer-wallet', $result);
				}
				
			} else {
				$errors_submit->add('transfer-wallet', __('Il y a eu une erreur lors du transfert.', 'yproject'));
			}
		}
		 * 
		 */
	}
	
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
					$result_transfer = LemonwayLib::ask_transfer_to_iban( $this->get_lemonway_id(), $transfer_amount + $transfer_commission, 0, $transfer_commission );
					$buffer = ($result_transfer->TRANS->HPAY->ID) ? "success" : $result_transfer->TRANS->HPAY->MSG;

					if ($buffer == "success") {
						// Enregistrement de l'objet Lemon Way
						$withdrawal_post = array(
							'post_author'   => $this->get_wpref(),
							'post_title'    => $transfer_amount,
							'post_content'  => print_r( $result_transfer, TRUE ),
							'post_status'   => 'publish',
							'post_type'		=> 'withdrawal_order'
						);
						wp_insert_post( $withdrawal_post );
					}
				}
			}
		}
	}
	
/*******************************************************************************
 * Gestion Lemonway
*******************************************************************************/
	private function get_wallet_details( $reload = false ) {
		if ( !isset($this->wallet_details) || empty($this->wallet_details) || $reload == true ) {
			$this->wallet_details = LemonwayLib::wallet_get_details($this->get_lemonway_id());
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
			$WDGUser_creator = new WDGUser();
			return LemonwayLib::wallet_company_register( $this->get_lemonway_id(), $this->get_email(), $WDGUser_creator->wp_user->user_firstname, $WDGUser_creator->wp_user->user_lastname, $this->get_name(), $this->get_description() );
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
	 * Retourne le statut de l'identification sur lemonway
	 */
	public function get_lemonway_status( $force_reload = TRUE ) {
		if ( $force_reload ) {
			$user_meta_status = get_user_meta( $this->wpref, YPOrganisation::$key_lemonway_status, TRUE );
			if ( $user_meta_status == YPOrganisation::$lemonway_status_registered ) {
				$buffer = $user_meta_status;

			} else {
				if (!$this->can_register_lemonway()) {
					$buffer = YPOrganisation::$lemonway_status_blocked;
				} else {
					$buffer = YPOrganisation::$lemonway_status_ready;
					$wallet_details = $this->get_wallet_details();
					if ( isset($wallet_details->STATUS) && !empty($wallet_details->STATUS) ) {
						switch ($wallet_details->STATUS) {
							case '2':
							case '8':
								$buffer = YPOrganisation::$lemonway_status_incomplete;
								break;
							case '3':
							case '9':
								$buffer = YPOrganisation::$lemonway_status_rejected;
								break;
							case '6':
								$buffer = YPOrganisation::$lemonway_status_registered;
								break;

							default:
							case '5':
								foreach($wallet_details->DOCS->DOC as $document_object) {
									if (isset($document_object->TYPE) && $document_object->TYPE !== FALSE) {
										switch ($document_object->S) {
											case '1':
												$buffer = YPOrganisation::$lemonway_status_waiting;
												break;
										}
									}
								}
								break;
						}
					}
				}

				update_user_meta( $this->wpref, YPOrganisation::$key_lemonway_status, $buffer );
			}
		} else {
			$buffer = get_user_meta( $this->wpref, YPOrganisation::$key_lemonway_status, TRUE );
		}
		return $buffer;
	}

	/**
	 * Retourne si l'identification sur lemonway est validée
	 */
	public function is_registered_lemonway_wallet() {
		return ( $this->get_lemonway_status() == YPOrganisation::$lemonway_status_registered );
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
		return $buffer;
	}
	
	/**
	 * Ajoute un mandat de prélévement lié au wallet de l'organisation
	 */
	public function add_lemonway_mandate() {
		return LemonwayLib::wallet_register_mandate( $this->get_lemonway_id(), $this->get_bank_owner(), $this->get_bank_iban(), $this->get_bank_bic(), 1, 1, $this->get_address(), $this->get_postal_code(), $this->get_city(), $this->get_country() );
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
    
/*******************************************************************************
 * Fonctions statiques
*******************************************************************************/
	/**
	 * Retourne TRUE si l'utilisateur dont l'id est passé en paramètre est une organisation
	 * @param type $user_id
	 */
	public static function is_user_organisation($user_id) {
		$result = get_user_meta($user_id, YPOrganisation::$key_bopp_id, TRUE);
		return (isset($result) && !empty($result));
	}
	
	/**
	 * Formulaire de nouvelle organisation
	 */
	public static function submit_new($redirect = TRUE) {
		global $errors_submit_new;
		$errors_submit_new = new WP_Error();
				
                //Dans le TB, data-action = save_new_organisation
                if($redirect){
                    //Vérification que l'on a posté le formulaire
                    $action = filter_input(INPUT_POST, 'action');
                    if ($action !== 'submit-new-organisation') { 
			return FALSE;
                    }
                }
				
		//Vérification que l'utilisateur est connecté
		if (!is_user_logged_in()) {
			$errors_submit_new->add('not-loggedin', __('Vous devez vous connecter.', 'yproject'));
		} else {
			$current_user = wp_get_current_user();
		}
		
		//Vérification de la case à cocher
		if (filter_input(INPUT_POST, 'org_capable', FILTER_VALIDATE_BOOLEAN) !== TRUE) {
			$errors_submit_new->add('not-capable', __('Vous devez cocher la case pour certifier que vous &ecirc;tes en capacit&eacute; de repr&eacute;senter l&apos;organisation.', 'yproject'));
		}
		
		//Vérification de l'adresse e-mail
		/*$org_email = filter_input(INPUT_POST, 'org_email');
		if (bp_core_validate_email_address($org_email) !== TRUE) {
			$errors_submit_new->add('not-email', __('Cet e-mail n&apos;est pas valide.', 'yproject'));
		}*/
		
		//Vérification du code postal
		$org_postal_code = filter_input(INPUT_POST, 'org_postal_code');
		if (substr($org_postal_code, 0, 1) === '0') { $org_postal_code = substr($org_postal_code, 1); }
		$org_postal_code = filter_var($org_postal_code, FILTER_VALIDATE_INT);
		if ($org_postal_code === FALSE) {
			$errors_submit_new->add('postalcode-not-integer', __('Le code postal doit &ecirc;tre un nombre entier.', 'yproject'));
		} else {
			if (strlen($org_postal_code) === 4) { $org_postal_code = '0' . $org_postal_code; }
		}

		//Vérification du capital
		$org_capital = filter_input(INPUT_POST, 'org_capital', FILTER_VALIDATE_INT);
		if ($org_capital === FALSE) {
			$errors_submit_new->add('capital-not-integer', __('Le capital doit &ecirc;tre un nombre entier.', 'yproject'));
		}
		
		//Vérification des données obligatoires
		$necessary_fields = array('org_name', 'org_address', 'org_city', 'org_nationality', 'org_legalform', 'org_idnumber', 'org_ape', 'org_rcs');
		$necessary_fields_full = TRUE;
		foreach ($necessary_fields as $field) {
			$value = filter_input(INPUT_POST, $field);
			if (empty($value)) {
				$necessary_fields_full = FALSE;
			}
		}
		if (!$necessary_fields_full) {
			$errors_submit_new->add('empty-fields', __('Certains champs obligatoires sont vides. Veuillez les renseigner.', 'yproject'));
		}

		//On poursuit la procédure
		if (count($errors_submit_new->errors) > 0) {
			return FALSE;
		}
		
		//Création de l'objet organisation
		global $current_user;
		$org_object = new YPOrganisation();
		$org_object->set_strong_authentication(FALSE);
		$org_object->set_name(filter_input(INPUT_POST, 'org_name'));
		$org_object->set_email(filter_input(INPUT_POST, 'org_email'));
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
		$org_object->set_bank_owner(filter_input(INPUT_POST, 'org_bankownername'));
		$org_object->set_bank_address(filter_input(INPUT_POST, 'org_bankowneraddress'));
		$org_object->set_bank_iban(filter_input(INPUT_POST, 'org_bankowneriban'));
		$org_object->set_bank_bic(filter_input(INPUT_POST, 'org_bankownerbic'));
		$wp_orga_user_id = $org_object->create();
		               
		if ($wp_orga_user_id !== FALSE) {
			$org_object->set_creator($current_user->ID);
                        if($redirect){
                            if (session_id() == '') session_start();
                            if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] == 'new_organisation') {
                                    $_SESSION['redirect_current_invest_type'] = $wp_orga_user_id;
                                    wp_redirect(ypcf_login_gobackinvest_url());                              
                                    exit();

                            } else {
                                    wp_safe_redirect(bp_loggedin_user_domain() . '#community');
                                    exit();
                            }
                        }			
		}
                return $org_object;
	}
	
	public static function edit($org_object) {
		global $errors_edit;
		$errors_edit = new WP_Error();
		
		//Vérification que l'on a posté le formulaire
		$action = filter_input(INPUT_POST, 'action');
		if ($action !== 'edit-organisation') { 
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
		
		$org_object->set_address(filter_input(INPUT_POST, 'org_address'));
		$org_object->set_nationality(filter_input(INPUT_POST, 'org_nationality'));
		$org_object->set_postal_code(filter_input(INPUT_POST, 'org_postal_code'));
		$org_object->set_city(filter_input(INPUT_POST, 'org_city'));
		$org_object->set_legalform(filter_input(INPUT_POST, 'org_legalform'));
		$org_object->set_capital(filter_input(INPUT_POST, 'org_capital'));
		$org_object->set_idnumber(filter_input(INPUT_POST, 'org_idnumber'));
		$org_object->set_rcs(filter_input(INPUT_POST, 'org_rcs'));
		$org_object->set_ape(filter_input(INPUT_POST, 'org_ape'));
		$org_object->set_email(filter_input(INPUT_POST, 'org_email'));
		$org_object->set_description(filter_input(INPUT_POST, 'org_description'));
		$org_object->submit_bank_info();
		$org_object->submit_documents();
	}
}