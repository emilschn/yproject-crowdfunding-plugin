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
			if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
			return $result_obj;
		} else {
			return FALSE;
		}
	}
}
