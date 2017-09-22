<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des organisations côté WDGWPREST
 */
class WDGWPREST_Entity_Organization {
	
	public static $link_user_type_creator = 'organization-creator';
	
	/**
	 * Retourne une organisation à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'organization/' . $id );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGOrganization $organization
	 * @return array
	 */
	public static function set_post_parameters( WDGOrganization $organization ) {
		$parameters = array(
			'wpref'						=> $organization->get_wpref(),
			'name'						=> $organization->get_name(),
			'strong_authentication'		=> ( $organization->get_strong_authentication() === TRUE ) ? 1 : 0,
			'type'						=> $organization->get_type(),
			'representative_function'	=> $organization->get_representative_function(),
			'description'				=> $organization->get_description(),
			'legalform'					=> $organization->get_legalform(),
			'idnumber'					=> $organization->get_idnumber(),
			'rcs'						=> $organization->get_rcs(),
			'ape'						=> $organization->get_ape(),
			'vat'						=> $organization->get_vat(),
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
	
	/**
	 * Crée une organisation sur l'API
	 * @param WDGOrganization $organization
	 * @return object
	 */
	public static function create( WDGOrganization $organization ) {
		$parameters = WDGWPREST_Entity_Organization::set_post_parameters( $organization );
		$date = new DateTime("NOW");
		$parameters['creation_date'] = $date->format('Y') .'-'. $date->format('m') .'-'. $date->format('d');
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'organization', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour de l'organisation à partir d'un id
	 * @param WDGOrganization $organization
	 * @return object
	 */
	public static function update( WDGOrganization $organization ) {
		$parameters = WDGWPREST_Entity_Organization::set_post_parameters( $organization );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'organization/' . $organization->get_api_id(), $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}

	/**
	 * Lie un utilisateur à une organisation en définissant son rôle
	 * @param int $organization_id
	 * @param int $user_id
	 * @param string $role_slug
	 * @return object
	 */
	public static function link_user( $organization_id, $user_id, $role_slug ) {
		$request_params = array(
			'id_user' => $user_id,
			'type' => $role_slug
		);
		$result_obj = WDGWPRESTLib::call_post_wdg( 'organization/' .$organization_id. '/users', $request_params );
		return $result_obj;
	}
	
	/**
	 * 
	 * @param type $organization_id
	 */
	public static function get_linked_users( $organization_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization_id. '/users' );
		return $result_obj;
	}
}
