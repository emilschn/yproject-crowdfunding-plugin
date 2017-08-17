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
			'adjustment'			=> $declaration->adjustment
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
}
