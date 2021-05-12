<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

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
	public static function get($id) {
		return WDGWPRESTLib::call_get_wdg( 'organization/' . $id );
	}

	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGOrganization $organization
	 * @return array
	 */
	public static function set_post_parameters(WDGOrganization $organization) {
		$accountant_info = array(
			'name'		=> $organization->get_accountant_name(),
			'email'		=> $organization->get_accountant_email(),
			'address'	=> $organization->get_accountant_address()
		);
		$parameters = array(
			'wpref'						=> $organization->get_wpref(),
			'name'						=> $organization->get_name(),
			'email'						=> $organization->get_email(),
			'strong_authentication'		=> ( $organization->get_strong_authentication() === TRUE ) ? 1 : 0,
			'type'						=> $organization->get_type(),
			'representative_function'	=> $organization->get_representative_function(),
			'description'				=> $organization->get_description(),
			'website_url'				=> $organization->get_website(),
			'legalform'					=> $organization->get_legalform(),
			'idnumber'					=> $organization->get_idnumber(),
			'rcs'						=> $organization->get_rcs(),
			'ape'						=> $organization->get_ape(),
			'vat'						=> $organization->get_vat(),
			'fiscal_year_end_month'		=> $organization->get_fiscal_year_end_month(),
			'employees_count'			=> $organization->get_employees_count(),
			'capital'					=> $organization->get_capital(),
			'address_number'			=> $organization->get_address_number(),
			'address_number_comp'		=> $organization->get_address_number_comp(),
			'address'					=> $organization->get_address(),
			'postalcode'				=> $organization->get_postal_code(),
			'city'						=> $organization->get_city(),
			'country'					=> $organization->get_nationality(),
			'accountant'				=> json_encode( $accountant_info ),
			'bank_owner'				=> $organization->get_bank_owner(),
			'bank_address'				=> $organization->get_bank_address(),
			'bank_address2'				=> $organization->get_bank_address2(),
			'bank_iban'					=> $organization->get_bank_iban(),
			'bank_bic'					=> $organization->get_bank_bic(),
			'id_quickbooks'				=> $organization->get_id_quickbooks(),
			'gateway_list'				=> $organization->get_encoded_gateway_list(),
			'mandate_info'				=> $organization->get_encoded_mandate_info()
		);

		return $parameters;
	}

	/**
	 * Crée une organisation sur l'API
	 * @param WDGOrganization $organization
	 * @return object
	 */
	public static function create(WDGOrganization $organization) {
		$parameters = WDGWPREST_Entity_Organization::set_post_parameters( $organization );
		$date = new DateTime("NOW");
		$parameters['creation_date'] = $date->format('Y') .'-'. $date->format('m') .'-'. $date->format('d');

		$result_obj = WDGWPRESTLib::call_post_wdg( 'organization', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) {
			$result_obj = '';
		}

		return $result_obj;
	}

	/**
	 * Mise à jour de l'organisation à partir d'un id
	 * @param WDGOrganization $organization
	 * @return object
	 */
	public static function update(WDGOrganization $organization) {
		$parameters = WDGWPREST_Entity_Organization::set_post_parameters( $organization );
		$result_obj = WDGWPRESTLib::call_post_wdg( 'organization/' . $organization->get_api_id(), $parameters );
		WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$organization->get_api_id() );

		$organization_projects = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization->get_api_id(). '/projects' );
		if ( $organization_projects ) {
			foreach ( $organization_projects as $projects ) {
				WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$projects->id. '?with_investments=1&with_organization=1&with_poll_answers=1' );
			}
		}

		if (isset($result_obj->code) && $result_obj->code == 400) {
			$result_obj = '';
		}

		return $result_obj;
	}

	/**
	 * Lie un utilisateur à une organisation en définissant son rôle
	 * @param int $organization_id
	 * @param int $user_id
	 * @param string $role_slug
	 * @return object
	 */
	public static function link_user($organization_id, $user_id, $role_slug) {
		$request_params = array(
			'id_user' => $user_id,
			'type' => $role_slug
		);
		$result_obj = WDGWPRESTLib::call_post_wdg( 'organization/' .$organization_id. '/users', $request_params );
		WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$user_id. '?with_links=1' );

		return $result_obj;
	}

	/**
	 *
	 * @param type $organization_id
	 */
	public static function get_linked_users($organization_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization_id. '/users' );

		return $result_obj;
	}

	/**
	 * Retourne les investissements liés à une organisation
	 * @return array
	 */
	public static function get_investments($organization_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization_id. '/investments' );

		return $result_obj;
	}

	/**
	 * Retourne les contrats d'investissements liés à une organisation
	 * @return array
	 */
	public static function get_investment_contracts($organization_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization_id. '/investment-contracts' );

		return $result_obj;
	}

	/**
	 * Retourne les ROIs liés à une organisation
	 * @return array
	 */
	public static function get_rois($organization_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization_id. '/rois' );

		return $result_obj;
	}

	/**
	 * Retourne les transactions liées à une organisation
	 * @return array
	 */
	public static function get_transactions($organization_id) {
		$buffer = array();
		if ( !empty( $organization_id ) ) {
			$buffer = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization_id. '/transactions' );
		}

		return $buffer;
	}

	/**
	 * Retourne le vIBAN lié à une organisation
	 * @return array
	 */
	public static function get_viban($organization_id) {
		$buffer = array();
		if ( !empty( $organization_id ) ) {
			$buffer = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization_id. '/virtual-iban' );
		}

		return $buffer;
	}

	/**
	 * Retourne la liste des projets liés à une organisation
	 * @param int $organization_id
	 * @return array
	 */
	public static function get_projects($organization_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'organization/' .$organization_id. '/projects' );

		return $result_obj;
	}
}
