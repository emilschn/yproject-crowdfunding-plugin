<?php
/**
 * Classe de gestion des taxes de royalties
 */
class WDGROITax {
	public $id;
	public $id_roi;
	public $id_recipient;
	public $recipient_type;
	public $date_transfer;
	public $amount_taxed_in_cents;
	public $amount_tax_in_cents;
	public $percent_tax;
	public $tax_country;
	public $has_tax_exemption;
	
	
	public function __construct( $roitax_id = FALSE, $data = FALSE ) {
		if ( !empty( $roitax_id ) ) {
			// Récupération en priorité depuis l'API
			$roitax_api_item = ( $data !== FALSE ) ? $data : FALSE;
			if ( empty( $roitax_api_item ) ) {
				$roitax_api_item = WDGWPREST_Entity_ROITax::get( $roitax_id );
			}
			
			if ( $roitax_api_item != FALSE ) {
				$this->id = $roitax_id;
				$this->id_roi = $roitax_api_item->id_roi;
				$this->id_recipient = $roitax_api_item->id_recipient;
				$this->recipient_type = $roitax_api_item->recipient_type;
				$this->date_transfer = $roitax_api_item->date_transfer;
				$this->amount_taxed_in_cents = $roitax_api_item->amount_taxed_in_cents;
				$this->amount_tax_in_cents = $roitax_api_item->amount_tax_in_cents;
				$this->percent_tax = $roitax_api_item->percent_tax;
				$this->tax_country = $roitax_api_item->tax_country;
				$this->has_tax_exemption = $roitax_api_item->has_tax_exemption;
			}
		}
	}
	
	/**
	 * Sauvegarde les données dans l'API
	 */
	public function update() {
		WDGWPREST_Entity_ROITax::update( $this );
	}
	
	
/*******************************************************************************
 * REQUETES STATIQUES
 ******************************************************************************/
	/**
	 * Ajout d'une nouvelle ROITax
	 */
	public static function insert( $id_roi, $id_recipient, $recipient_type, $date_transfer, $amount_taxed_in_cents, $amount_tax_in_cents, $percent_tax, $tax_country, $has_tax_exemption ) {
		$roitax = new WDGROITax();
		$roitax->id_roi = $id_roi;
		$roitax->id_recipient = $id_recipient;
		$roitax->recipient_type = $recipient_type;
		$roitax->date_transfer = $date_transfer;
		$roitax->amount_taxed_in_cents = $amount_taxed_in_cents;
		$roitax->amount_tax_in_cents = $amount_tax_in_cents;
		$roitax->percent_tax = $percent_tax;
		$roitax->tax_country = $tax_country;
		$roitax->has_tax_exemption = $has_tax_exemption;
		WDGWPREST_Entity_ROITax::create( $roitax );
	}
}
