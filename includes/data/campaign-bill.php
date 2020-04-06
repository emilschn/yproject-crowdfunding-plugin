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
	private $bill_type;
	/**
	 * @var WDGROIDeclaration
	 */
	private $roideclaration;
	
	public static $tool_name_quickbooks = 'quickbooks';
	
	public static $bill_type_crowdfunding_commission = 'crowdfunding-commission';
	public static $bill_type_royalties_commission = 'royalties-commission';
	
	public static $item_types = array(
		'crowdfunding' => array(
			'label' => 'CROWDFUNDING',
			'quickbooks_campaign' => 34,
			'quickbooks_royalties' => 35
		),
		'private' => array(
			'label' => 'RESEAU PRIVE',
			'quickbooks_campaign' => 36,
			'quickbooks_royalties' => 37
		),
		'lovemoney' => array(
			'label' => 'LOVE-MONEY',
			'quickbooks_campaign' => 38,
			'quickbooks_royalties' => 39
		),
		'positivesavings' => array(
			'label' => 'EPARGNE POSITIVE',
			'quickbooks_campaign' => 47,
			'quickbooks_royalties' => 48
		)
	);

	public static $classes = array(
		'other' => array(
			'label' => 'CA source inconnue / autre',
			'quickbooks_id' => '1400000000001624307'
		),
		'events' => array(
			'label' => 'Evénements',
			'quickbooks_id' => '1400000000000673977'
		),
		'prescription' => array(
			'label' => 'Prescription',
			'quickbooks_id' => '1400000000001624227'
		),
		'press' => array(
			'label' => 'Presse',
			'quickbooks_id' => '1400000000001624228'
		),
		'prospection' => array(
			'label' => 'Prospection',
			'quickbooks_id' => '1400000000001624232'
		),
		'socialnetworks' => array(
			'label' => 'Réseaux sociaux',
			'quickbooks_id' => '1400000000001624230'
		),
		'web' => array(
			'label' => 'Visibilité Web',
			'quickbooks_id' => '1400000000001624225'
		),
	);
	
	public static $item_tax_20 = 31;// identifiant du type de TVA à 20% dans quickbooks
	public static $location_company_id = 6;// identifiant du site "Entrepreneurs" dans quickbooks
	

	public function __construct( $campaign, $tool_name, $bill_type ) {
		$this->campaign = $campaign;
		$this->tool_name = $tool_name;
		$this->bill_type = $bill_type;
	}
	
	public function set_declaration( $roi_declaration ) {
		$this->roideclaration = $roi_declaration;
	}
	
	public function can_generate() {
		$platform_commission = $this->campaign->platform_commission();
		$campaign_organization = $this->campaign->get_organization();
		$WDGOrganization = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
		$id_quickbooks = $WDGOrganization->get_id_quickbooks();
		$line_type = $this->get_line_type_by_platform_commission();
		$can_generate = ( !empty( $platform_commission ) && !empty( $id_quickbooks ) && !empty( $line_type ) );
		if( !$can_generate ){
			ypcf_debug_log( 'WDGCampaignBill :: can_generate $platform_commission = '.$platform_commission, false);
			ypcf_debug_log( 'WDGCampaignBill :: can_generate $id_quickbooks = '.$id_quickbooks, false);
			ypcf_debug_log( 'WDGCampaignBill :: can_generate $line_type = '.$line_type, false);
			ypcf_debug_log( 'WDGCampaignBill :: can_generate $can_generate = '.$can_generate, false);
		}
		return $can_generate;
	}
	
	public function generate() {
		switch ( $this->tool_name ) {
			case WDGCampaignBill::$tool_name_quickbooks:
				return $this->generate_quickbooks();
				break;
		}
	}
	
	private function generate_quickbooks() {
		$options = FALSE;
		switch ( $this->bill_type ) {
			case WDGCampaignBill::$bill_type_crowdfunding_commission:
				$options = $this->get_quickbooks_crowdfunding_commission_options();
				break;
			case WDGCampaignBill::$bill_type_royalties_commission:
				$options = $this->get_quickbooks_royalties_commission_options();
				break;
		}
		
		$params = array(
			'tool'		=> WDGCampaignBill::$tool_name_quickbooks,
			'object'	=> $this->bill_type,
			'options'	=> json_encode( $options )
		);
		
		switch ( $this->bill_type ) {
			case WDGCampaignBill::$bill_type_crowdfunding_commission:
				$params[ 'object_id' ] = $this->campaign->ID;
				break;
			case WDGCampaignBill::$bill_type_royalties_commission:
				$params[ 'object_id' ] = $this->roideclaration->id;
				break;
		}
		
		$result = WDGWPRESTLib::call_post_wdg( 'bill', $params );
		ypcf_debug_log( 'WDGCampaignBill :: generate_quickbooks $result = '.$result, false);
		return $result;
	}
	
/*******************************************************************************
 * FONCTIONS LIEES A LA COMMISSION SUR LA CAMPAGNE
*******************************************************************************/
	private function get_quickbooks_crowdfunding_commission_options() {
		$product_type = $this->campaign->get_api_data( 'product_type' );
		$acquisition = $this->campaign->get_api_data( 'acquisition' );
		if ( !empty( $product_type ) && !empty( self::$item_types[ $product_type ] ) 
				&& !empty( $acquisition ) && !empty( self::$classes[ $acquisition ] ) 
					) {
			$line_description = $this->get_line_description();
			$bill_description = $this->get_bill_description();
			$platform_commission_amount = $this->campaign->platform_commission_amount( FALSE );
			$campaign_organization = $this->campaign->get_organization();
			$WDGOrganization = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
			$options = array(
				'customerid'		=> $WDGOrganization->get_id_quickbooks(),
				'customeremail'		=> $WDGOrganization->get_email(),
				'itemtitle'			=> self::$items_types[ $product_type ][ 'quickbooks_campaign' ],
				'itemdescription'	=> $line_description,
				'itemvalue'			=> $platform_commission_amount,
				'itemtaxid'			=> WDGCampaignBill::$item_tax_20,
				'locationid'		=> WDGCampaignBill::$location_company_id,
				'classid'			=> self::$classes[ $acquisition ][ 'quickbooks_id' ],
				'billdescription'	=> $bill_description
			);
			return $options;
		}
		return FALSE;
	}
	
	public function get_line_title() {
		$product_type = $this->campaign->get_api_data( 'product_type' );
		if ( !empty( $product_type ) && !empty( self::$item_types[ $product_type ] ) ) {
			return self::$item_types[ $product_type ][ 'label' ];
		}
		return '';
	}
	
	public function get_line_description() {
		$amount_collected = UIHelpers::format_number( $this->campaign->current_amount( FALSE ) );
		$amount_collected_check = UIHelpers::format_number( $this->campaign->current_amount_with_check() );
		$platform_commission = UIHelpers::format_number( $this->campaign->platform_commission( FALSE ) );
		$platform_commission_amount = UIHelpers::format_number( $this->campaign->platform_commission_amount( FALSE ) );
		$platform_commission_below_100000 = UIHelpers::format_number( $this->campaign->platform_commission( FALSE ) );
		$platform_commission_below_100000_amount = UIHelpers::format_number( $this->campaign->platform_commission_below_100000_amount( FALSE ) );
		$platform_commission_above_100000 = UIHelpers::format_number( $this->campaign->platform_commission_above_100000( FALSE ) );
		$platform_commission_above_100000_amount = UIHelpers::format_number( $this->campaign->platform_commission_above_100000_amount( FALSE ) );
		
		$product_type = $this->campaign->get_api_data( 'product_type' );
		if ( $product_type == 'private' || $product_type == 'lovemoney' ) {
			$buffer = "Levée de fonds privée.
";
		} else {
			$buffer = "Levée de fonds de crowdfunding.
";
		}
		
		if ( $platform_commission_above_100000_amount > 0 && $platform_commission_below_100000 != $platform_commission_above_100000 ) {
			$buffer .= "Montant collecté : ".$amount_collected." € (dont ".$amount_collected_check." € par chèque), commission de ".$platform_commission_below_100000." % HT de 100 000 € : ".$platform_commission_below_100000_amount." € et commission de ".$platform_commission_above_100000." % HT au-delà de 100 000 € : ".$platform_commission_above_100000_amount." €.";
			
		} else {
			$buffer .= "Montant collecté : ".$amount_collected." € (dont ".$amount_collected_check." € par chèque), commission de ".$platform_commission." % HT : ".$platform_commission_amount." €.";
		}
		
		return $buffer;
	}
	
	public function get_bill_description() {
		$amount_collected_check = $this->campaign->current_amount_with_check();
		$amount_collected = $this->campaign->current_amount( FALSE ) - $amount_collected_check;
		$amount_collected_formatted = UIHelpers::format_number( $amount_collected );
		$platform_commission_amount = $this->campaign->platform_commission_amount( FALSE );
		$platform_commission_amount_with_tax_formatted = UIHelpers::format_number( $platform_commission_amount * 1.2 );
		$transfered_amount_formatted = UIHelpers::format_number( max( 0, $amount_collected - ( $platform_commission_amount * 1.2 ) ) );
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
	
/*******************************************************************************
 * FONCTIONS LIEES A LA COMMISSION SUR LES ROYALTIES
*******************************************************************************/
	private function get_quickbooks_royalties_commission_options() {
		$product_type = $this->campaign->get_api_data( 'product_type' );
		$acquisition = $this->campaign->get_api_data( 'acquisition' );
		if ( !empty( $product_type ) && !empty( self::$item_types[ $product_type ] ) 
				&& !empty( $acquisition ) && !empty( self::$classes[ $acquisition ] ) 
					) {
			$line_description = $this->get_royalties_line_description();
			$campaign_organization = $this->campaign->get_organization();
			$WDGOrganization = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
			$commission_to_pay_without_tax = $this->roideclaration->get_commission_to_pay_without_tax( TRUE );
			$options = array(
				'customerid'		=> $WDGOrganization->get_id_quickbooks(),
				'customeremail'		=> $WDGOrganization->get_email(),
				'itemtitle'			=> self::$item_types[ $product_type ][ 'quickbooks_royalties' ],
				'itemdescription'	=> $line_description,
				'itemvalue'			=> $commission_to_pay_without_tax,
				'itemtaxid'			=> WDGCampaignBill::$item_tax_20,
				'locationid'		=> WDGCampaignBill::$location_company_id,
				'classid'			=> self::$classes[ $acquisition ][ 'quickbooks_id' ],
				'billdescription'	=> '',
				'sendemail'			=> '1'
			);
			return $options;
		}
		return FALSE;
	}
	
	public function get_royalties_line_description() {
		$declaration_cost_to_organization = $this->campaign->get_costs_to_organization();
		$this->roideclaration->get_month_list_str();
		$declaration_amount = UIHelpers::format_number( $this->roideclaration->get_amount_with_adjustment() );
		$declaration_date_object = new DateTime( $this->roideclaration->date_due );
		$declaration_month_num = $declaration_date_object->format( 'n' );
		$declaration_year = $declaration_date_object->format( 'Y' );
		$declaration_trimester = 4;
		switch ( $declaration_month_num ) {
			case 1:
				$declaration_year--;
				break;
			case 4:
			case 5:
			case 6:
				$declaration_trimester = 1;
				break;
			case 7:
			case 8:
			case 9:
				$declaration_trimester = 2;
				break;
			case 10:
			case 11:
			case 12:
				$declaration_trimester = 3;
				break;
		}
		
		$buffer = "Frais de gestion royalties T".$declaration_trimester." ".$declaration_year."
".$declaration_cost_to_organization." % TTC de la Redevance
La Redevance de ce trimestre étant de ".$declaration_amount." euros";
		return $buffer;
	}
	
}
