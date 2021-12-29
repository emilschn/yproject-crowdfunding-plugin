<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des ajustements côté WDGWPREST
 */
class WDGWPREST_Entity_Adjustment {
	
	/**
	 * Récupère un ajustement à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'adjustment/' . $id );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGAdjustment $adjustment
	 * @return array
	 */
	public static function set_post_parameters( WDGAdjustment $adjustment ) {
		$parameters = array(
			'id_project'			=> $adjustment->id_api_campaign,
			'id_declaration'		=> $adjustment->id_declaration,
			'date_created'			=> $adjustment->date_created,
			'type'					=> $adjustment->type,
			'turnover_checked'		=> $adjustment->turnover_checked,
			'turnover_difference'	=> $adjustment->turnover_difference,
			'amount'				=> $adjustment->amount,
			'message_organization'	=> $adjustment->message_organization,
			'message_investors'		=> $adjustment->message_investors,
		);
		return $parameters;
	}
	
	/**
	 * Crée un ajustement sur l'API
	 * @param WDGAdjustment $adjustment
	 * @return object
	 */
	public static function create( WDGAdjustment $adjustment ) {
		$parameters = self::set_post_parameters( $adjustment );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'adjustment', $parameters );
		// Suppression cache déclaration liée
		WDGWPRESTLib::unset_cache( 'wdg/v1/declaration/' .$adjustment->id_declaration );
		WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$adjustment->id_api_campaign. '/declarations' );
		// Suppression cache projet lié
		WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$adjustment->id_api_campaign. '?with_investments=1&with_organization=1&with_poll_answers=1' );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour d'une déclaration à partir d'un id
	 * @param WDGAdjustment $adjustment
	 * @return object
	 */
	public static function update( WDGAdjustment $adjustment ) {
		$parameters = self::set_post_parameters( $adjustment );
		
		if ( !empty( $adjustment->id ) ) {
			$result_obj = WDGWPRESTLib::call_post_wdg( 'adjustment/' . $adjustment->id, $parameters );
			WDGWPRESTLib::unset_cache( 'wdg/v1/adjustment/' .$adjustment->id );
			// Suppression cache déclaration liée
			WDGWPRESTLib::unset_cache( 'wdg/v1/declaration/' .$adjustment->id_declaration );
			WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$adjustment->id_api_campaign. '/declarations' );
			// Suppression cache projet lié
			WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$adjustment->id_api_campaign. '?with_investments=1&with_organization=1&with_poll_answers=1' );
			if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
			return $result_obj;
		} else {
			return FALSE;
		}
	}
	
	public static function get_list_by_project_id( $project_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/adjustments?with_links=1' );
		return $result_obj;
	}
	
	public static function get_list_by_declaration_id( $declaration_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'declaration/' .$declaration_id. '/adjustments' );
		return $result_obj;
	}
	
	public static function get_list_linked_to_declaration_id( $declaration_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'declaration/' .$declaration_id. '/adjustments?linked=1' );
		return $result_obj;
	}
	
	public static function get_linked_declarations( $adjustment_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'adjustment/' .$adjustment_id. '/declarations' );
		return $result_obj;
	}
	
	public static function link_declaration( $adjustment_id, $declaration_id, $type = '' ) {
		$request_params = array(
			'id_declaration'	=> $declaration_id,
			'type'				=> $type
		);
		$result_obj = WDGWPRESTLib::call_post_wdg( 'adjustment/' .$adjustment_id. '/declarations', $request_params );
		return $result_obj;
	}
	
	public static function unlink_declaration( $adjustment_id, $declaration_id, $type = '' ) {
		$result_obj = WDGWPRESTLib::call_delete_wdg( 'adjustment/' .$adjustment_id. '/declaration/' .$declaration_id );
		return $result_obj;
	}
	
	
	public static function get_linked_files( $adjustment_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'adjustment/' .$adjustment_id. '/files' );
		return $result_obj;
	}
	
	public static function link_file( $adjustment_id, $file_id, $type = '' ) {
		$request_params = array(
			'id_file'	=> $file_id,
			'type'		=> $type
		);
		$result_obj = WDGWPRESTLib::call_post_wdg( 'adjustment/' .$adjustment_id. '/files', $request_params );
		return $result_obj;
	}
	
	public static function unlink_file( $adjustment_id, $file_id, $type = '' ) {
		$result_obj = WDGWPRESTLib::call_delete_wdg( 'adjustment/' .$adjustment_id. '/file/' .$file_id );
		return $result_obj;
	}
}
