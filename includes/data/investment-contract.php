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
	
	public function get_api_id() {
		return $this->api_id;
	}
	
	public function create() {
		WDGWPREST_Entity_InvestmentContract::create( $this );
	}

	/**
	 * Retourne le chemin de dossier où sont créés les contrat d'un projet (et le crée si nécessaire)
	 * @param ATCF_Campaign $campaign
	 */
	public static function get_and_create_path_for_campaign( $campaign ) {
		$final_path = dirname( __FILE__ ). '/../../files/contracts/campaigns/' .$campaign->ID. '-' .$campaign->get_url(). '/';
		if ( !is_dir( $final_path ) ) {
			mkdir( $final_path, 0755, TRUE );
		}
		return $final_path;
	}

	/**
	 * Retourne le chemin de fichier du zip des contrats d'une campagne
	 */
	public static function get_contracts_zip_path_for_campaign( $campaign ) {
		return dirname( __FILE__ ). '/../../files/contracts/' .$campaign->ID. '-' .$campaign->data->post_name. '.zip';
	}

	/**
	 * Retourne l'URL publique d'un fichier d'investissement
	 * @param ATCF_Campaign $campaign
	 * @param int $investment_id
	 */
	public static function get_investment_file_path( $campaign, $investment_id ) {
		$path = self::get_and_create_path_for_campaign( $campaign );
		return $path .  $investment_id . '.pdf';
	}

	/**
	 * Retourne l'URL publique d'un fichier d'investissement
	 * @param ATCF_Campaign $campaign
	 * @param int $investment_id
	 */
	public static function get_investment_file_url( $campaign, $investment_id ) {
		return site_url( '/wp-content/plugins/appthemer-crowdfunding/files/contracts/campaigns/' .$campaign->ID. '-' .$campaign->get_url(). '/' .  $investment_id . '.pdf' );
	}

	/**
	 * Retourne l'ancienne URL des fichiers
	 */
	public static function get_deprecated_file_url( $filename ) {
		return site_url( '/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/' . $filename );
	}

	/**
	 * Retourne une expression régulière pour récupérer la vieille liste de fichiers
	 */
	public static function get_deprecated_file_list_expression( $campaign, $user_id ) {
		return dirname( __FILE__ ). '/../pdf_files/' .$campaign->ID. '_' .$user_id. '_*.pdf';
	}

	/**
	 * Retourne le dossier temporaire utile pour les amandements
	 */
	public static function get_and_create_deprecated_tmp_path() {
		$buffer = dirname( __FILE__ ). '/../pdf_files/tmp';
		if ( !is_dir( $buffer ) ) {
			mkdir( $buffer, 0777, true );
		}
		return $buffer;
	}
	
	/**
	 * Vérifie les montants reçus par l'investisseur
	 */
	public function check_amount_received( $amount_received, $amount_current_declaration ) {
		// Est-ce que l'investisseur a reçu une plus-value
		if ( $amount_received > $this->subscription_amount ) {
			NotificationsSlack::roi_received_exceed_investment( $this->investor_id, $this->project_id );
			NotificationsAsana::roi_received_exceed_investment( $this->investor_id, $this->investor_type, $this->project_id );
		}

		// Notification de sécurité : Est-ce que l'investisseur a dépassé le maximum qu'il devrait pouvoir recevoir ?
		if ( $this->maximum_to_receive > 0 && $amount_received > $this->maximum_to_receive ) {
			NotificationsSlack::roi_received_exceed_maximum( $this->investor_id, $this->project_id );
			NotificationsAsana::roi_received_exceed_maximum( $this->investor_id, $this->investor_type, $this->project_id );
		}
	}
	
	/**
	 * Génère une liste de contrats d'investissement liés à une campagne
	 * @param int $campaign_id
	 */
	public static function create_list( $campaign_id ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$investments = $campaign->payments_data();
		$declarations = $campaign->get_roi_declarations();
		
		$investment_contracts = self::get_list( $campaign_id );
		
		// Parcours de tous les investissements
		foreach ( $investments as $investment ) {
			// Si on est déjà passé dans la procédure, on ne le refait pas
			$create_this_item = ( $investment[ 'status' ] == 'publish' );

			if ( $create_this_item && !empty( $investment_contracts ) ) {
				foreach ( $investment_contracts as $investment_contract ) {
					if ( $investment_contract->subscription_id == $investment[ 'ID' ] ) {
						$create_this_item = false;
						break;
					}
				}
			}
			
			if ( $create_this_item ) {
				self::create_item_from_payment_data( $investment, $campaign, $declarations );
			}
		}
	}

	public static function create_item_from_payment_data( $investment, $campaign, $declarations = FALSE ) {
		if ( empty( $declarations ) ) {
			$declarations = $campaign->get_roi_declarations();
		}
		
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

	/**
	 * Retourne la liste des contrats d'investissement d'un projet dans un tableau associatif dont la clé est l'identifiant de souscription
	 * @param int $campaign_id
	 * @return array
	 */
	public static function get_list_sorted_by_subscription_id( $campaign_id ) {
		$investment_contracts = self::get_list( $campaign_id );
		$investment_contracts_sorted_by_subscription_id = array();
		foreach ( $investment_contracts as $investment_contract_item ) {
			$investment_contracts_sorted_by_subscription_id[ $investment_contract_item->subscription_id ] = $investment_contract_item;
		}
		return $investment_contracts_sorted_by_subscription_id;
	}
	
	/**
	 * Déplace les contrats qui sont dans includes/pdf_files vers files/contracts
	 * @param int $campaign_id
	 */
	public static function move_campaign_contracts_to_final_directory( $campaign_id ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		
		// On commence par créer le dossier final
		$final_path = self::get_and_create_path_for_campaign( $campaign );
		
		// Ensuite on parcourt les investissements
		$list_investments = $campaign->payments_data( TRUE );
		foreach ( $list_investments as $investment_item ) {
			if ( $investment_item[ 'status' ] == 'publish' ) {
				$investment_item_id = $investment_item[ 'ID' ];

				// On recherche le fichier pdf qui correspond au pattern
				$investment_item_user_id = $investment_item[ 'user' ];
				$exp = dirname( __FILE__ ). '/../pdf_files/' .$campaign_id. '_' .$investment_item_user_id. '*.pdf';
				$files = glob( $exp );
				foreach ( $files as $file ) {
					// On le déplace dans le dossier final
					copy( $file, $final_path. $investment_item_id . '.pdf' );
				}
			}
		}
	}
}