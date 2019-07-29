<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des ROIs côté WDGWPREST
 */
class WDGWPREST_Entity_ROI {
	
	/**
	 * Retourne un ROI à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'roi/' . $id );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGROI $roi
	 * @return array
	 */
	public static function set_post_parameters( WDGROI $roi ) {
		$parameters = array(
			'id_investment'			=> $roi->id_investment,
			'id_investment_contract'	=> $roi->id_investment_contract,
			'id_project'			=> $roi->id_campaign,
			'id_orga'				=> $roi->id_orga,
			'id_user'				=> $roi->id_user,
			'recipient_type'		=> $roi->recipient_type,
			'id_declaration'		=> $roi->id_declaration,
			'date_transfer'			=> $roi->date_transfer,
			'amount'				=> $roi->amount,
			'id_transfer'			=> $roi->id_transfer,
			'status'				=> $roi->status
		);
		return $parameters;
	}
	
	/**
	 * Crée un ROI sur l'API
	 * @param WDGROI $roi
	 * @return object
	 */
	public static function create( WDGROI $roi ) {
		$parameters = WDGWPREST_Entity_ROI::set_post_parameters( $roi );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'roi', $parameters );
		if ( $parameters[ 'recipient_type' ] == 'user' ) {
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$parameters[ 'id_user' ]. '/rois' );
		} else {
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$parameters[ 'id_user' ]. '/rois' );
		}
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour d'un ROI à partir d'un id
	 * @param WDGROI $roi
	 * @return object
	 */
	public static function update( WDGROI $roi ) {
		$parameters = WDGWPREST_Entity_ROI::set_post_parameters( $roi );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'roi/' . $roi->id, $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
}
