<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des ROITax côté WDGWPREST
 */
class WDGWPREST_Entity_ROITax {
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGROITax $roitax
	 * @return array
	 */
	public static function set_post_parameters( WDGROITax $roitax ) {
		$parameters = array(
			'id_roi'			=> $roitax->id_roi,
			'id_recipient'		=> $roitax->id_recipient,
			'recipient_type'	=> $roitax->recipient_type,
			'date_transfer'		=> $roitax->date_transfer,
			'amount_taxed_in_cents'	=> $roitax->amount_taxed_in_cents,
			'amount_tax_in_cents'	=> $roitax->amount_tax_in_cents,
			'percent_tax'		=> $roitax->percent_tax,
			'tax_country'		=> $roitax->tax_country,
			'has_tax_exemption'		=> $roitax->has_tax_exemption,
		);
		return $parameters;
	}
	
	/**
	 * Crée un ROITax
	 * @param WDGROITax $roi
	 * @return object
	 */
	public static function create( WDGROITax $roitax ) {
		$parameters = self::set_post_parameters( $roitax );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'roi-tax', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour d'un ROITax à partir d'un id
	 * @param WDGROITax $roi
	 * @return object
	 */
	public static function update( WDGROITax $roitax ) {
		$parameters = self::set_post_parameters( $roitax );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'roi-tax/' . $roitax->id, $parameters );
		if ( isset( $result_obj->code ) && $result_obj->code == 400 ) { $result_obj = ''; }
		return $result_obj;
	}
}
