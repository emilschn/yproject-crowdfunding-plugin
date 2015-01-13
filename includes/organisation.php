<?php
/**
 * Classe de gestion des organisations
 */
class YPOrganisation {
	/**
	 * Données
	 */
	private $creator;
	private $bopp_id;
	private $bopp_object;
	private $wpref;
	private $name;
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
	
	/**
	 * Clés d'accès aux meta
	 */
	public static $key_bopp_id = 'organisation_bopp_id';
    
	/**
	 * Constructeur
	 */
	public function __construct($user_id = FALSE) {
		if ($user_id !== FALSE) {
			$this->creator = get_user_by('id', $user_id);
			$this->bopp_id = get_user_meta($user_id, YPOrganisation::$key_bopp_id, TRUE);
			$this->bopp_object = BoppOrganisations::get($this->bopp_id);
			$this->wpref = $user_id;
			
			$this->name = $this->bopp_object->organisation_name;
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
		}
	}
	
	/**
	 * Retourne un objet Organisation chargé avec un paramètre get "orga_id"
	 * @return \YPOrganisation
	 */
	public static function get_current() {
		$orga_id = filter_input(INPUT_GET, 'orga_id');
		$organisation_obj = FALSE;
		if (isset($orga_id)) {
			$organisation_obj = new YPOrganisation($orga_id);
		}
		return $organisation_obj;
	}
	
	/**
	 * Crée un utilisateur dans la base de données et l'initialise
	 * @return boolean
	 */
	public function create() {
		global $errors_submit_new;
		
		$organisation_user_id = $this->create_user($this->get_name());
		
		//Si il y a eu une erreur lors de la création de l'utilisateur, on arrête la procédure
		if (isset($organisation_user_id->errors) && count($organisation_user_id->errors) > 0) {
			$errors_submit_new = $organisation_user_id;
			return FALSE;
		}
		
		$return_obj = BoppOrganisations::create(
			$this->creator->ID,
			$this->get_name(), 
			$this->get_type(), 
			$this->get_legalform(), 
			$this->get_idnumber(), 
			$this->get_rcs(), 
			$this->get_capital(), 
			$this->get_address(), 
			$this->get_postal_code(), 
			$this->get_city(), 
			$this->get_nationality(), 
			$this->get_ape()
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
			$this->get_type(), 
			$this->get_legalform(), 
			$this->get_idnumber(), 
			$this->get_rcs(), 
			$this->get_capital(), 
			$this->get_address(), 
			$this->get_postal_code(), 
			$this->get_city(), 
			$this->get_nationality(), 
			$this->get_ape()
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
	
	/**
	 * Liaisons utilisateurs
	 */
	public function set_creator($wp_user_id) {
		$bopp_user_id = BoppLibHelpers::get_api_user_id($wp_user_id);
		BoppLibHelpers::check_create_role(BoppLibHelpers::$organisation_creator_role['slug'], BoppLibHelpers::$organisation_creator_role['title']);
		BoppOrganisations::link_user_to_organisation($this->bopp_id, $bopp_user_id, BoppLibHelpers::$organisation_creator_role['slug']);
	}
}