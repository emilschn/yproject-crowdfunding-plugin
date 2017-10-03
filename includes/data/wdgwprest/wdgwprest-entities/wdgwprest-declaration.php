<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des déclérations ROI côté WDGWPREST
 */
class WDGWPREST_Entity_Declaration {
	
	/**
	 * Retourne une déclaration à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'declaration/' . $id );
	}
	
	/**
	 * Retourne une déclaration à partir de son token de paiement
	 */
	public static function get_by_payment_token( $token ) {
		return WDGWPRESTLib::call_get_wdg( 'declaration/token/' . $token );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGROIDeclaration $declaration
	 * @return array
	 */
	public static function set_post_parameters( WDGROIDeclaration $declaration ) {
		$parameters = array(
			'id_project'			=> $declaration->id_campaign,
			'date_due'				=> $declaration->date_due,
			'date_paid'				=> $declaration->date_paid,
			'date_transfer'			=> $declaration->date_transfer,
			'amount'				=> $declaration->amount,
			'remaining_amount'		=> $declaration->remaining_amount,
			'transfered_previous_remaining_amount'	=> $declaration->transfered_previous_remaining_amount,
			'percent_commission'	=> $declaration->percent_commission,
			'status'				=> $declaration->status,
			'mean_payment'			=> $declaration->mean_payment,
			'payment_token'			=> $declaration->payment_token,
			'file_list'				=> $declaration->file_list,
			'turnover'				=> $declaration->turnover,
			'message'				=> $declaration->message,
			'adjustment'			=> $declaration->adjustment,
			'employees_number'		=> $declaration->employees_number,
			'other_fundings'		=> $declaration->other_fundings
		);
		return $parameters;
	}
	
	/**
	 * Crée une déclaration sur l'API
	 * @param WDGROIDeclaration $declaration
	 * @return object
	 */
	public static function create( WDGROIDeclaration $declaration ) {
		$parameters = WDGWPREST_Entity_Declaration::set_post_parameters( $declaration );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'declaration', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour d'une déclaration à partir d'un id
	 * @param WDGROIDeclaration $declaration
	 * @return object
	 */
	public static function update( WDGROIDeclaration $declaration ) {
		$parameters = WDGWPREST_Entity_Declaration::set_post_parameters( $declaration );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'declaration/' . $declaration->id, $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Retourne des déclarations en fonction d'une date
	 */
	public static function get_list_by_date( $in_date_start, $in_date_end = FALSE, $type = 'due' ) {
		$date_start = $in_date_start;
		$date_end = ( !empty( $in_date_end ) ) ? $in_date_end : $in_date_start;
		return WDGWPRESTLib::call_get_wdg( 'declarations?type=' .$type. '&start_date=' .$date_start. '&end_date=' .$date_end );
	}
	
	/**
	 * Retourne la liste des ROIs liés à une déclaration
	 * @param int $declaration_id
	 */
	public static function get_roi_list( $declaration_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'declaration/' .$declaration_id. '/rois' );
		return $result_obj;
	}
}
