<?php
/**
 * Classe de factures de campagnes
 */
class WDGCampaignBill {
	
	/**
	 * @var ATCF_Campaign
	 */
	private $campaign;
	private $tool_name;
	
	public static $tool_name_quickbooks = 'quickbooks';
	
	public static $bill_type_crowdfunding_commission = 'crowdfunding-commission';
	
	public static $item_types = array(
		'crowdfunding' => array(
			'quickbooks_id' => 16, // TODO
			'label' => 'Formule Crowdfunding'
		),
		'selfservice' => array(
			'quickbooks_id' => 16, // TODO
			'label' => 'Formule Self-Service'
		)
	);
	
	public static $item_tax_20 = 31; // TODO


	public function __construct( $campaign, $tool_name ) {
		$this->campaign = $campaign;
		$this->tool_name = $tool_name;
	}
	
	public function can_generate() {
		$platform_commission = $this->campaign->platform_commission();
		$campaign_organization = $this->campaign->get_organization();
		$WDGOrganization = new WDGOrganization( $campaign_organization->wpref );
		$id_quickbooks = $WDGOrganization->get_id_quickbooks();
		$line_type = $this->get_line_type_by_platform_commission();
		return ( !empty( $platform_commission ) && !empty( $id_quickbooks ) && !empty( $line_type ) );
	}
	
	public function generate() {
		switch ( $this->tool_name ) {
			case WDGCampaignBill::$tool_name_quickbooks:
				$this->generate_quickbooks();
				break;
		}
	}
	
	private function generate_quickbooks() {
		$line_description = $this->get_line_description();
		$bill_description = $this->get_bill_description();
		$platform_commission_amount = $this->campaign->platform_commission_amount();
		$campaign_organization = $this->campaign->get_organization();
		$WDGOrganization = new WDGOrganization( $campaign_organization->wpref );
		$options = array(
			'customerid'		=> $WDGOrganization->get_id_quickbooks(),
			'customeremail'		=> $WDGOrganization->get_email(),
			'itemtitle'			=> $this->get_line_title_id(),
			'itemdescription'	=> $line_description,
			'itemvalue'			=> $platform_commission_amount,
			'itemtaxid'			=> WDGCampaignBill::$item_tax_20,
			'billdescription'	=> $bill_description
		);
		$params = array(
			'tool'		=> WDGCampaignBill::$tool_name_quickbooks,
			'object'	=> WDGCampaignBill::$bill_type_crowdfunding_commission,
			'options'	=> json_encode( $options )
		);
		$result = WDGWPRESTLib::call_post_wdg( 'bill', $params );
	}
	
	private function get_line_type_by_platform_commission() {
		$buffer = FALSE;
		switch ( $this->campaign->platform_commission() ) {
			case 8:
				$buffer = 'crowdfunding';
				break;
			case 5:
				$buffer = 'selfservice';
				break;
		}
		return $buffer;
	}
	
	public function get_line_title() {
		return WDGCampaignBill::$item_types[ $this->get_line_type_by_platform_commission() ][ 'label' ];
	}
	
	public function get_line_title_id() {
		return WDGCampaignBill::$item_types[ $this->get_line_type_by_platform_commission() ][ 'quickbooks_id' ];
	}
	
	public function get_line_description() {
		$amount_collected = UIHelpers::format_number( $this->campaign->current_amount( FALSE ) );
		$amount_collected_check = UIHelpers::format_number( $this->campaign->current_amount_with_check() );
		$platform_commission = UIHelpers::format_number( $this->campaign->platform_commission() );
		$platform_commission_amount = UIHelpers::format_number( $this->campaign->platform_commission_amount() );
		$buffer = "Campagne de crowdfunding.
Montant collecté : ".$amount_collected." € (dont ".$amount_collected_check." € par chèque), commission de ".$platform_commission." % HT : ".$platform_commission_amount." €.";
		return $buffer;
	}
	
	public function get_bill_description() {
		$amount_collected = $this->campaign->current_amount( FALSE );
		$amount_collected_formatted = UIHelpers::format_number( $amount_collected );
		$amount_collected_check = $this->campaign->current_amount_with_check();
		$platform_commission_amount = $this->campaign->platform_commission_amount();
		$platform_commission_amount_with_tax_formatted = UIHelpers::format_number( $platform_commission_amount * 1.2 );
		$transfered_amount_formatted = UIHelpers::format_number( $amount_collected - ( $platform_commission_amount * 1.2 ) );
		$buffer = "Le règlement est effectué par prélèvement sur les fonds collectés par carte bleue et virement sur internet lors du versement sur votre compte :
Montant collecté sur la plateforme : ".$amount_collected_formatted." €
Montant de la commission TTC : ".$platform_commission_amount_with_tax_formatted." €
Montant reversé : ".$transfered_amount_formatted." €";
		
		if ( $amount_collected_check > 0 ) {
			$buffer .= "
Les chèques vous seront directement adressés.";
		}
		return $buffer; 
	}
	
}