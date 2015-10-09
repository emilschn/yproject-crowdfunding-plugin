<?php
/**
 * Classe de gestion des organisations
 */
class YPOrganisation {
	/**
	 * Clés d'accès à l'api BOPP
	 */
	public static $key_bopp_id = 'organisation_bopp_id';
	
	/**
	 * Données
	 */
	private $creator;
	private $bopp_id;
	private $bopp_object;
	private $wpref;
	private $name;
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
	 * @return boolean
	 */
	public function create() {
		global $errors_submit_new;
		
		$organisation_user_id = $this->create_user($this->get_name());
		$this->set_wpref($organisation_user_id);
		
		//Si il y a eu une erreur lors de la création de l'utilisateur, on arrête la procédure
		if (isset($organisation_user_id->errors) && count($organisation_user_id->errors) > 0) {
			$errors_submit_new = $organisation_user_id;
			return FALSE;
		}
                
                if($this->get_capital()==''){
                    $this->set_capital(0);
                }
                if($this->get_bank_owner()==''){
                    $this->set_bank_owner("---");
                }
                if($this->get_bank_address()==''){
                    $this->set_bank_address("---");
                }
                if($this->get_bank_iban()==''){
                    $this->set_bank_iban("---");
                }
                if($this->get_bank_bic()==''){
                    $this->set_bank_bic("---");
                }
		
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
		$email = $sanitized_name . '@wedogood.co';
		$organisation_user_id = wp_create_user($username, $password, $email);
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
	
	public function get_wallet_amount() {
		return ypcf_mangopay_get_user_personalamount_by_wpid($this->wpref) / 100;
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
		$mp_amount = $this->get_wallet_amount();
		$withdrawal_obj = ypcf_mangopay_make_withdrawal($this->get_wpref(), $beneficiary_id, $mp_amount);

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
		switch ($this->strong_authentication) {
			case 0:
			    //Vérifie les docs ont été envoyés
			    if (ypcf_mangopay_is_user_strong_authentication_sent($this->wpref)) {
				    //Vérifie si les docs ont été vérifiés
				    if (ypcf_mangopay_is_user_strong_authenticated($this->wpref)) {
					    $this->strong_authentication = '1';
					    $save = TRUE;
				    } else {
					    $this->strong_authentication = '5';
					    $save = TRUE;
				    }
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
		if ($save == TRUE) {
			$this->save();
		}
	}
	
	/**
	 * Gère les fichiers éventuellement transmis pour la Strong Authentication
	 */
	public function submit_strong_authentication() {
		global $errors_submit;
		$errors_submit = new WP_Error();
		
		if (isset($_FILES['org_file_cni']['tmp_name']) && isset($_FILES['org_file_status']['tmp_name']) && isset($_FILES['org_file_extract']['tmp_name'])) {
			$wp_organisation_user = get_user_by('id', $this->get_wpref());	
			$url_request = ypcf_init_mangopay_user_strongauthentification($wp_organisation_user);
			$curl_result_cni = ypcf_mangopay_send_strong_authentication($url_request, 'org_file_cni');
			$curl_result_status = ypcf_mangopay_send_strong_authentication($url_request, 'org_file_status');
			$curl_result_extract = ypcf_mangopay_send_strong_authentication($url_request, 'org_file_extract');
			
			if ($curl_result_cni && $curl_result_status && $curl_result_extract) {
				ypcf_mangopay_set_user_strong_authentication_doc_transmitted($this->get_wpref());
			} else {
				$errors_submit->add('strongauthentication-sendfile-error', __('Il y a eu une erreur lors de l&apos;envoi. Contactez-nous si cela se reproduit.', 'yproject'));
			}
		} else {
			if (isset($_FILES['org_file_cni']['tmp_name']) || isset($_FILES['org_file_status']['tmp_name']) || isset($_FILES['org_file_extract']['tmp_name'])) {
				$errors_submit->add('strongauthentication-incomplete', __('Les 3 fichiers d&apos;identification obligatoires doivent &ecirc;tre envoy&eacute;s en m&ecirc;me temps.', 'yproject'));
			}
		}
		
		if (isset($_FILES['org_file_declaration']['tmp_name'])) {
			ypcf_mangopay_send_strong_authentication($url_request, 'org_file_declaration');
		}
	}
	
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
	
	public function submit_transfer_wallet() {
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
	public static function submit_new() {
		global $errors_submit_new;
		$errors_submit_new = new WP_Error();
		
		//Vérification que l'on a posté le formulaire
		$action = filter_input(INPUT_POST, 'action');
		if ($action !== 'submit-new-organisation') { 
			return FALSE;
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
		$necessary_fields = array('org_name', 'org_address', 'org_city', 'org_nationality', 'org_legalform', 'org_idnumber', 'org_ape');
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
		$org_object->set_address(filter_input(INPUT_POST, 'org_address'));
		$org_object->set_postal_code($org_postal_code);
		$org_object->set_city(filter_input(INPUT_POST, 'org_city'));
		$org_object->set_nationality(filter_input(INPUT_POST, 'org_nationality'));
		$org_object->set_type('society');
		$org_object->set_legalform(filter_input(INPUT_POST, 'org_legalform'));
		$org_object->set_capital($org_capital);
		$org_object->set_idnumber(filter_input(INPUT_POST, 'org_idnumber'));
		$org_object->set_rcs('---');
		$org_object->set_ape(filter_input(INPUT_POST, 'org_ape'));
		$org_object->set_bank_owner(filter_input(INPUT_POST, 'org_bankownername'));
		$org_object->set_bank_address(filter_input(INPUT_POST, 'org_bankowneraddress'));
		$org_object->set_bank_iban(filter_input(INPUT_POST, 'org_bankowneriban'));
		$org_object->set_bank_bic(filter_input(INPUT_POST, 'org_bankownerbic'));
		$wp_orga_user_id = $org_object->create();
		
		if ($wp_orga_user_id !== FALSE) {
			$org_object->set_creator($current_user->ID);
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
//		$org_object->set_rcs(filter_input(INPUT_POST, 'org_rcs'));
		$org_object->set_ape(filter_input(INPUT_POST, 'org_ape'));
		$org_object->submit_bank_info();
		$org_object->submit_strong_authentication();
	}
}