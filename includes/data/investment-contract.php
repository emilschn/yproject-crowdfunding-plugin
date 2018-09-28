<?php
/**
 * Gestion des contrats d'investissement
 */
class WDGInvestmentContract {
	private $api_id;
	private $api_data;
	public $investor_id;
	public $investor_type;
	public $project_id;
	public $organization_id;
	public $subscription_id;
	public $subscription_date;
	public $subscription_amount;
	public $status;
	public $start_date;
	public $end_date;
	public $frequency;
	public $turnover_type;
	public $turnover_percent;
	public $amount_received;
	public $minimum_to_receive;
	public $maximum_to_receive;
	
	public static $investor_type_user = 'user';
	public static $investor_type_orga = 'organization';
	
	public static $status_active = 'active';
	public static $status_canceled = 'canceled';
	public static $status_finished = 'finished';
	
	public static $frequency_default = 3;
	
	public static $turnover_type_overall = 'overall';
	public static $turnover_type_limited = 'limited';
	
	public function __construct( $api_id = FALSE, $api_data = FALSE ) {
		if ( !empty( $api_id ) ) {
			$this->api_id = $api_id;
		}
		
		if ( !empty( $api_data ) ) {
			$this->api_data = $api_data;
		
			// Initialisation des données à partir de celles de l'API
			if ( empty( $this->api_id ) ) {
				$this->api_id = $this->api_data->id;
			}
			
		} else {
			// Récupération dans l'API
			$this->api_data = WDGWPREST_Entity_InvestmentContract::get( $this->api_id );
		}
		
		if ( !empty( $this->api_data ) ) {
			$this->investor_id = $this->api_data->investor_id;
			$this->investor_type = $this->api_data->investor_type;
			$this->project_id = $this->api_data->project_id;
			$this->organization_id = $this->api_data->organization_id;
			$this->subscription_id = $this->api_data->subscription_id;
			$this->subscription_date = $this->api_data->subscription_date;
			$this->subscription_amount = $this->api_data->subscription_amount;
			$this->status = $this->api_data->status;
			$this->start_date = $this->api_data->start_date;
			$this->end_date = $this->api_data->end_date;
			$this->frequency = $this->api_data->frequency;
			$this->turnover_type = $this->api_data->turnover_type;
			$this->turnover_percent = $this->api_data->turnover_percent;
			$this->minimum_to_receive = $this->api_data->minimum_to_receive;
			$this->maximum_to_receive = $this->api_data->maximum_to_receive;
			$this->amount_received = $this->api_data->amount_received;
		}
	}
	
	public function create() {
		WDGWPREST_Entity_InvestmentContract::create( $this );
	}
	
	/**
	 * Génère une liste de contrats d'investissement liés à une campagne
	 * @param int $campaign_id
	 */
	public static function create_list( $campaign_id ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$investments = $campaign->payments_data();
		$declarations = $campaign->get_roi_declarations();
		
		// Parcours de tous les investissements
		foreach ( $investments as $investment ) {
			$investment_contract = new WDGInvestmentContract();
			
			// Initialisation de l'investisseur
			if ( WDGOrganization::is_user_organization( $investment[ 'user' ] ) ) {
				$WDGOrganization = new WDGOrganization( $investment[ 'user' ] );
				$investment_contract->investor_id = $WDGOrganization->get_api_id();
				$investment_contract->investor_type = 'organization';
			} else {
				$WDGUser = new WDGUser( $investment[ 'user' ] );
				$investment_contract->investor_id = $WDGUser->get_api_id();
				$investment_contract->investor_type = 'user';
			}
			
			// Initialisation de la campagne et de l'organisation
			$investment_contract->project_id = $campaign->get_api_id();
			$campaign_organization = $campaign->get_organization();
			$investment_contract->organization_id = $campaign_organization->id;
			
			// Initialisation de l'investissement
			$investment_contract->subscription_id = $investment[ 'ID' ];
			$investment_contract->subscription_date = $investment[ 'date' ];
			$investment_contract->subscription_amount = $investment[ 'amount' ];
			
			// Initialisation du statut
			$investment_contract->status = WDGInvestmentContract::$status_active;
			
			// Initialisation des dates de début et de fin
			$investment_contract->start_date = $campaign->contract_start_date();
			// La date de fin correspond à date de début + durée du contrat - 1 jour
			$contract_end_date = new DateTime( $campaign->contract_start_date() );
			$contract_end_date->modify( '+' .$campaign->funding_duration(). ' years' );
			$contract_end_date->modify( '-1 day' );
			$investment_contract->end_date = $contract_end_date->format( 'Y-m-d' );
			
			// Initialisation des données relatives au contrat : fréquence, type et pourcent de CA, retour minimum et maximum
			$investment_contract->frequency = WDGInvestmentContract::$frequency_default;
			$investment_contract->turnover_type = WDGInvestmentContract::$turnover_type_overall;
			
			$investor_proportion = $investment[ 'amount' ] / $campaign->current_amount( false );
			$investment_contract->turnover_percent = $investor_proportion * $campaign->roi_percent();
			
			$investment_contract->minimum_to_receive = $campaign->minimum_profit() * $investment[ 'amount' ];
			if ( $campaign->maximum_profit() == 'infinite' ) {
				$investment_contract->maximum_to_receive = 0;
			} else {
				$investment_contract->maximum_to_receive = floatval( $campaign->maximum_profit() .'.'. $campaign->maximum_profit_precision() ) * $investment[ 'amount' ];
			}
			
			// Initialisation des montants perçus à partir des versements qui ont déjà eu lieu
			$investment_contract->amount_received = 0;
			foreach ( $declarations as $declaration ) {
				if ( !empty( $declaration[ 'roi_list_by_investment_id' ][ $investment[ 'ID' ] ] ) ) {
					$investment_contract->amount_received += $declaration[ 'roi_list_by_investment_id' ][ $investment[ 'ID' ] ][ 'amount' ];
				}
			}
			
			$investment_contract->create();
		}
	}
	
	/**
	 * Retourne la liste des contrats d'investissement d'un projet
	 * @param int $campaign_id
	 * @return array
	 */
	public static function get_list( $campaign_id ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$campaign_api_id = $campaign->get_api_id();
		$buffer = WDGWPREST_Entity_Project::get_investment_contracts( $campaign_api_id );
		return $buffer;
	}
	
	/**
	 * Retourne la liste des contrats d'investissement d'un projet selon un statut
	 * @param int $campaign_id
	 * @param string $status
	 * @return array
	 */
	public static function get_list_by_status( $campaign_id, $status = '' ) {
		$investment_contracts = self::get_list( $campaign_id );
		if ( !empty( $status ) ) {
			$buffer = array();
			foreach ( $investment_contracts as $investment_contract ) {
				if ( $investment_contract->status == $status ) {
					array_push( $buffer, $investment_contract );
				}
				return $buffer;
			}
			
		} else {
			return $investment_contracts;
		}
	}
}