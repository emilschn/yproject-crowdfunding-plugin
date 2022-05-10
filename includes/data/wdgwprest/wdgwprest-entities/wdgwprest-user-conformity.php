<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des utilisateurs côté WDGWPREST
 */
class WDGWPREST_Entity_UserConformity {
	public static $current_version = 1;
	
	/**
	 * Retourne les données de conformité d'un utilisateur à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get_by_user_api_id( $user_api_id, $shortcut_call = FALSE ) {
		if ( empty( $user_api_id ) ) {
			return FALSE;
		}
		return WDGWPRESTLib::call_get_wdg( 'user/' .$user_api_id. '/conformity', $shortcut_call );
	}

	private static function transform_ajax_to_metadata( $user_api_id, $ajax_data ) {
		$ajax_data_decoded = json_decode( $ajax_data );

		$buffer = array();
		$buffer[ 'user_id' ] = $user_api_id;
		$buffer[ 'version' ] = self::$current_version;

		$financial_details = array();
		$financial_details[ 'monthlyRevenue' ] = $ajax_data_decoded->monthlyRevenue;
		$financial_details[ 'complementaryRevenue' ] = $ajax_data_decoded->complementaryRevenue;
		$financial_details[ 'investmentsValue' ] = $ajax_data_decoded->investmentsValue;
		$financial_details[ 'commitmentValue' ] = $ajax_data_decoded->commitmentValue;
		$buffer[ 'financial_details' ] = json_encode( $financial_details );
		$buffer[ 'financial_result_in_cents' ] = $ajax_data_decoded->yearlyCapacityAmount * 100;

		$buffer[ 'knowledge_details' ] = json_encode( $ajax_data_decoded->knowledge );
		$buffer[ 'knowledge_result' ] = true ? 'unsophisticated' : 'sophisticated';

		return $buffer;
	}
	
	/**
	 * Crée une donnée de conformité sur l'API
	 */
	public static function create( $user_id, $ajax_data ) {
		if ( empty( $user_id ) ) {
			return FALSE;
		}

		// Build conformity data
		$conformity_data = self::transform_ajax_to_metadata( $user_id, $ajax_data );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'user-conformity', $conformity_data, TRUE );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour de la donnée de conformité de l'utilisateur à partir d'un id
	 */
	public static function update( $conformity_id, $user_id, $ajax_data ) {
		if ( empty( $conformity_id ) ) {
			return FALSE;
		}

		// Build conformity data
		$conformity_data = self::transform_ajax_to_metadata( $user_id, $ajax_data );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'user-conformity/' . $conformity_id, $conformity_data, TRUE );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
}
