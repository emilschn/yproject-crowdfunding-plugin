<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des brouillons d'investissements côté WDGWPREST
 */
class WDGWPREST_Entity_InvestmentDraft {
	
	/**
	 * Retourne un brouillon d'investissement à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'investment-draft/' . $id );
	}
	
	public static function set_post_parameters( $status, $project_api_id, $data ) {
		$data_encoded = json_encode( $data );
		$parameters = array(
			'status'			=> $status,
			'project_id'		=> $project_api_id,
			'data'				=> $data_encoded
		);
		return $parameters;
	}
	
	/**
	 * Crée une ligne de donnée sur l'API
	 * @param string $status
	 * @param int $project_api_id
	 * @return boolean
	 */
	public static function create( $status, $project_api_id, $data ) {
		$buffer = FALSE;
		
		$parameters = self::set_post_parameters( $status, $project_api_id, $data );
		if ( !empty( $parameters ) ) {
			$buffer = WDGWPRESTLib::call_post_wdg( 'investment-draft', $parameters );
			if ( isset( $buffer->code ) && $buffer->code == 400 ) { $buffer = FALSE; }
		}
		
		return $buffer;
	}
	
	public static function edit( $investment_draft_id, $status ) {
		if ( !empty( $investment_draft_id ) ) {
			$parameters = array(
				'status'	=> $status
			);
			return WDGWPRESTLib::call_post_wdg( 'investment-draft/' .$investment_draft_id, $parameters );
		}
	}
	
}
