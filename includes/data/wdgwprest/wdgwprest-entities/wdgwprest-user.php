<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des utilisateurs côté WDGWPREST
 */
class WDGWPREST_Entity_User {
	
	/**
	 * Retourne un utilisateur à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'user/' . $id );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param YPOrganisation $organization
	 * @return array
	public static function set_post_parameters( YPOrganisation $organization ) {
		$parameters = array(
			'wpref'						=> $organization->get_wpref(),
			'name'						=> $organization->get_name(),
			'strong_authentication'		=> ( $organization->get_strong_authentication() === TRUE ) ? 1 : 0,
			'type'						=> $organization->get_type(),
			'legalform'					=> $organization->get_legalform(),
			'idnumber'					=> $organization->get_idnumber(),
			'rcs'						=> $organization->get_rcs(),
			'ape'						=> $organization->get_ape(),
			'capital'					=> $organization->get_capital(),
			'address'					=> $organization->get_address(),
			'postalcode'				=> $organization->get_postal_code(),
			'city'						=> $organization->get_city(),
			'country'					=> $organization->get_nationality(),
			'bank_owner'				=> $organization->get_bank_owner(),
			'bank_address'				=> $organization->get_bank_address(),
			'bank_iban'					=> $organization->get_bank_iban(),
			'bank_bic'					=> $organization->get_bank_bic(),
		);
		return $parameters;
	}
	 */
	
	/**
	 * Crée une organisation sur l'API
	 * @return object
	public static function create( YPOrganisation $organization ) {
		$parameters = WDGWPREST_Entity_Organization::set_post_parameters( $organization );
		$date = new DateTime("NOW");
		$parameters['creation_date'] = $date->format('Y') .'-'. $date->format('m') .'-'. $date->format('d');
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'organization', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	 */
	
	/**
	 * Mise à jour de l'organisation à partir d'un id
	 * @param int $id
	 * @param string $first_name
	 * @param string $last_name
	 * @return object
	public static function update( YPOrganisation $organization ) {
		$parameters = WDGWPREST_Entity_Organization::set_post_parameters( $organization );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'organization/' . $organization->get_bopp_id(), $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	 */
	
	/**
	 * Retourne la liste des organisations d'un utilisateur
	 * @param int $user_id
	 * @return array
	 */
	public static function get_organizations( $user_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'user/' .$user_id. '/organizations' );
		return $result_obj;
	}
	
	/**
	 * Retourne la liste des organisations d'un utilisateur, filtrées par un role
	 * @param int $user_id
	 * @param string $role_slug
	 * @return array
	 */
	public static function get_organizations_by_role( $user_id, $role_slug ) {
		$buffer = array();
		$organization_list = WDGWPREST_Entity_User::get_organizations( $user_id );
		foreach ( $organization_list as $organization ) {
			if ( $organization->type == $role_slug ) {
				array_push( $buffer, $organization );
			}
		}
		return $buffer;
	}
}
