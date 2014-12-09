<?php
/**
 * Classe de gestion des organisations
 */
class YPOrganisation {
	/**
	 * Données
	 */
	private $creator;
	private $group;
	
	/**
	 * Clés d'accès aux meta
	 */
	public static $key_address = 'user_address';
	public static $key_postal_code = 'user_postal_code';
	public static $key_city = 'user_city';
	public static $key_nationality = 'user_nationality';
	public static $key_type = 'organisation_type'; //default : society
	public static $key_legalform = 'organisation_legalform';
	public static $key_capital = 'organisation_capital';
	public static $key_idnumber = 'organisation_idnumber';
	public static $key_rcs = 'organisation_rcs';
    
	/**
	 * Constructeur
	 */
	public function __construct($group_id) {
		$group_type = groups_get_groupmeta($group_id, 'group_type');
		if ($group_type == 'organisation') {
			$group_temp = groups_get_group( array( 'group_id' => $group_id ) );
			$this->group = $group_temp;
			$this->creator = get_user_by('id', $group_temp->creator_id);
		}
	}
	
	public static function get_current() {
		$orga_id = filter_input(INPUT_GET, 'orga_id');
		$organisation_obj = FALSE;
		if (isset($orga_id)) {
			$organisation_obj = new YPOrganisation($orga_id);
		}
		return $organisation_obj;
	}
	
	/**
	 * Attributions / Récupération de données
	 */
	public function get_name() {
		return $this->creator->display_name;
	}
	
	
	public function get_address() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_address, TRUE);
	}
	public function set_address($value) {
		YPOrganisation::set_address_by_id($this->creator->ID, $value);
	}
	public static function set_address_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_address, $value);
	}
	
	
	public function get_postal_code() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_postal_code, TRUE);
	}
	public function set_postal_code($value) {
		YPOrganisation::set_postal_code_by_id($this->creator->ID, $value);
	}
	public static function set_postal_code_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_postal_code, $value);
	}
	
	public function get_city() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_city, TRUE);
	}
	public function set_city($value) {
		YPOrganisation::set_city_by_id($this->creator->ID, $value);
	}
	public static function set_city_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_city, $value);
	}
	
	public function get_nationality() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_nationality, TRUE);
	}
	public function set_nationality($value) {
		YPOrganisation::set_nationality_by_id($this->creator->ID, $value);
	}
	public static function set_nationality_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_nationality, $value);
	}
	
	public function get_type() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_type, TRUE);
	}
	public function set_type($value) {
		YPOrganisation::set_type_by_id($this->creator->ID, $value);
	}
	public static function set_type_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_type, $value);
	}
	
	public function get_legalform() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_legalform, TRUE);
	}
	public function set_legalform($value) {
		YPOrganisation::set_legalform_by_id($this->creator->ID, $value);
	}
	public static function set_legalform_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_legalform, $value);
	}
	
	public function get_capital() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_capital, TRUE);
	}
	public function set_capital($value) {
		YPOrganisation::set_capital_by_id($this->creator->ID, $value);
	}
	public static function set_capital_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_capital, $value);
	}
	
	public function get_idnumber() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_idnumber, TRUE);
	}
	public function set_idnumber($value) {
		YPOrganisation::set_idnumber_by_id($this->creator->ID, $value);
	}
	public static function set_idnumber_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_idnumber, $value);
	}
	
	public function get_rcs() {
		return get_user_meta($this->creator->ID, YPOrganisation::$key_rcs, TRUE);
	}
	public function set_rcs($value) {
		YPOrganisation::set_rcs_by_id($this->creator->ID, $value);
	}
	public static function set_rcs_by_id($user_id, $value) {
		update_user_meta($user_id, YPOrganisation::$key_rcs, $value);
	}
}