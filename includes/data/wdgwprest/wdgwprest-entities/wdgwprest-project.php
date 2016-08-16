<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des projets côté WDGWPREST
 */
class WDGWPREST_Entity_Project {
	
	/**
	 * Retourne un projet à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'project/' . $id );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param ATCF_Campaign $campaign
	 * @return array
	 */
	public static function set_post_parameters( ATCF_Campaign $campaign ) {
		$parameters = array(
			'wpref'				=> $campaign->ID,
			'name'				=> $campaign->data->post_title
		);
		return $parameters;
	}
	
	/**
	 * Crée un projet sur l'API
	 * @param ATCF_Campaign $campaign
	 * @return object
	 */
	public static function create( ATCF_Campaign $campaign ) {
		$parameters = WDGWPREST_Entity_Project::set_post_parameters( $campaign );
		$date = new DateTime("NOW");
		$parameters['creation_date'] = $date->format('Y') .'-'. $date->format('m') .'-'. $date->format('d');
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour du projet à partir d'un id
	 * @param ATCF_Campaign $campaign
	 * @return object
	 */
	public static function update( ATCF_Campaign $campaign ) {
		$parameters = WDGWPREST_Entity_Project::set_post_parameters( $campaign );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project/' . $cam, $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
}
