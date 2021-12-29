<?php
/**
 * Classe de fichers de recouvrement
 */
class WDGCampaignDebtFiles {
	
	/**
	 * @var ATCF_Campaign
	 */
	private $campaign;
	
	

	public function __construct( $campaign ) {
		$this->campaign = $campaign;
	}
	
	public function get_recover_certificate() {
		$this->make_recover_certificate();
		$buffer = site_url() . '/wp-content/plugins/appthemer-crowdfunding/files/debt-certificate/';
		$buffer .= $this->campaign->ID . '_certificate.pdf';
		return $buffer;
	}

	private function make_recover_certificate() {
			
		$campaign_organization = $this->campaign->get_organization();
		$WDGOrganization = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );

		$orga_users_creator = $WDGOrganization->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
		if ( empty( $orga_users_creator ) ) {
			return;
		}
		$WDGOrganization_creator = $orga_users_creator[ 0 ];
		$user_title = ( $WDGOrganization_creator->get_gender() == "male" ) ? "Monsieur" : "Madame";

	
		$start_datetime = new DateTime( $this->campaign->contract_start_date() );
		$start_datetime2 = new DateTime( $this->campaign->contract_start_date() );
		$end_datetime = $start_datetime2->add(new DateInterval('P'.$this->campaign->funding_duration().'Y')); 
		
		$date_today = new DateTime();

		// création du pdf
		$filepath = dirname( __FILE__ ). '/../../files/debt-certificate/' . $this->campaign->ID . '_certificate';

		$ext = 'pdf';				// Création du fichier PDF correspondant
		$core = ATCF_CrowdFunding::instance();
		$core->include_control( 'templates/pdf/debt-certificate' );

		$html_content = WDG_Template_PDF_Debt_Certificate::get( 
			$WDGOrganization->get_name(), 
			$WDGOrganization->get_full_address_str(), 
			$WDGOrganization->get_postal_code(), 
			$WDGOrganization->get_city(), 
			$WDGOrganization->get_legalform(), 
			$WDGOrganization->get_capital(), 
			$WDGOrganization->get_idnumber(), 
			$WDGOrganization->get_rcs(),
			$WDGOrganization->get_representative_function(),
			$user_title,
			$WDGOrganization_creator->get_firstname(),
			$WDGOrganization_creator->get_lastname(),
			$this->campaign->end_date('d/m/Y'), 
			count(array_unique($this->campaign->backers_id_list())), // nombre d'investisseurs uniques, pas d'investissement
			$this->campaign->current_amount( ), 
			$this->campaign->get_roi_declarations_total_roi_amount(), // amount paid
			$this->campaign->current_amount( FALSE ) - $this->campaign->get_roi_declarations_total_roi_amount(), // amount_left
			$start_datetime->format( 'd/m/Y' ), 
			$end_datetime->format( 'd/m/Y' ), 
			UIHelpers::format_number( $this->campaign->roi_percent(), 10 ),
			$date_today->format( 'd/m/Y' )
		);

		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_html2pdf();
		$h2p_instance = HTML2PDFv5Helper::instance();
		$h2p_instance->writePDF( $html_content, $filepath.'.'.$ext );
		
	}
	
	public function get_recover_list() {
		$this->make_recover_list();
		$buffer = site_url() . '/wp-content/plugins/appthemer-crowdfunding/files/debt-certificate/';
		$buffer .= $this->campaign->ID . '_liste.csv';
		return $buffer;
	}

	private function make_recover_list(){
		// création du fichier xls
		// calcul du reliquat
		$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $this->campaign->ID );
		$sum_remaining_amount = 0;
		foreach ( $declaration_list as $declaration ) {
			$sum_remaining_amount += $declaration->remaining_amount;
		}

		$liste_file_path = dirname( __FILE__ ). '/../../files/debt-certificate/' . $this->campaign->ID . '_liste.csv';

		$csv = new SplFileObject($liste_file_path, 'w');

		$line_title = array('LISTE INVESTISSEURS ' . $this->campaign->get_name(), '', '', '', '');
		$csv->fputcsv($line_title, ';');
		
		$line_empty = array('', '', '', '', '');
		$csv->fputcsv($line_empty, ';');

		$csv_cols = array('Nom','Montant investi','Statut', 'Montant perçu',  'Reste à percevoir');
		$csv->fputcsv($csv_cols, ';');

		$investment_contracts = WDGInvestmentContract::get_list( $this->campaign->ID );
		$total_amount = 0;
		$total_amount_received = 0;
		$total_amount_left = 0;
			foreach ( $investment_contracts as $investment_contract ){
			$line = array();
			$name = '';
			$status = ( $investment_contract->status == 'active' ) ? 'Actif' : 'Arrêté';
			if ( $investment_contract->investor_type == WDGInvestmentContract::$investor_type_user ) {
				$WDGUser = WDGUser::get_by_api_id( $investment_contract->investor_id );
				if ( !empty( $WDGUser ) ) {
					$name = $WDGUser->get_lastname() .' '. $WDGUser->get_firstname();
				}
			} else {
				$WDGOrganization = WDGOrganization::get_by_api_id( $investment_contract->investor_id );
				if ( !empty( $WDGOrganization ) ) {
					$name = $WDGOrganization->get_name();
				}
			}
			$total_amount +=  $investment_contract->subscription_amount;
			$total_amount_received +=  $investment_contract->amount_received;
			
			array_push($line, $name);
			array_push($line, UIHelpers::format_number( $investment_contract->subscription_amount ));
			array_push($line, $status);
			array_push($line, UIHelpers::format_number($investment_contract->amount_received));
			array_push($line, UIHelpers::format_number($investment_contract->subscription_amount - $investment_contract->amount_received));
			
			$csv->fputcsv($line, ';');
		}
		$line_remaining = array('Arrondis non versés', '', '', UIHelpers::format_number($sum_remaining_amount), '');
		$csv->fputcsv($line_remaining, ';');

		// TOTAL
		// on ajoute le reliquat à la totalité des sommes percues
		$total_amount_received +=  $sum_remaining_amount;
		$total_amount_left =  $total_amount - $total_amount_received;
		$line_total = array('TOTAL', UIHelpers::format_number($total_amount), '', UIHelpers::format_number($total_amount_received), UIHelpers::format_number($total_amount_left));
		$csv->fputcsv($line_total, ';');
	}
	
}
