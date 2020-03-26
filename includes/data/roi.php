<?php
/**
 * Classe de gestion des ROI
 */
class WDGROI {
	public static $table_name = 'ypcf_roi';
	
	public static $status_waiting_authentication = "waiting_authentication";
	public static $status_waiting_transfer = "waiting_transfer";
	public static $status_transferred = "transferred";
	public static $status_canceled = "canceled";
	public static $status_error = "error";
	
	public $id;
	public $id_investment;
	public $id_investment_contract;
	public $id_campaign;
	public $id_orga;
	public $id_user;
	public $recipient_type;
	public $id_declaration;
	public $date_transfer;
	public $amount;
	public $amount_taxed_in_cents;
	public $id_transfer;
	public $status;
	public $on_api;
	
	
	public function __construct( $roi_id = FALSE, $local = FALSE, $data = FALSE ) {
		if ( !empty( $roi_id ) ) {
			// Récupération en priorité depuis l'API
			$roi_api_item = ( $data !== FALSE ) ? $data : FALSE;
			if ( empty( $roi_api_item ) ) {
				$roi_api_item = ( !$local ) ? WDGWPREST_Entity_ROI::get( $roi_id ) : FALSE;
			}
			if ( $roi_api_item != FALSE ) {

				$this->id = $roi_id;
				$this->id_investment = $roi_api_item->id_investment;
				$this->id_investment_contract = $roi_api_item->id_investment_contract;
				$this->id_campaign = $roi_api_item->id_project;
				$this->id_orga = $roi_api_item->id_orga;
				$this->id_user = $roi_api_item->id_user;
				$this->recipient_type = $roi_api_item->recipient_type;
				$this->id_declaration = $roi_api_item->id_declaration;
				$this->date_transfer = $roi_api_item->date_transfer;
				$this->amount = $roi_api_item->amount;
				$this->amount_taxed_in_cents = $roi_api_item->amount_taxed_in_cents;
				$this->id_transfer = $roi_api_item->id_transfer;
				$this->status = $roi_api_item->status;
				$this->on_api = TRUE;
			}
		}
	}
	
	/**
	 * Sauvegarde les données dans l'API
	 */
	public function update() {
		WDGWPREST_Entity_ROI::update( $this );
	}
	
	/**
	 * deprecated
	 */
	public function save( $local = FALSE ) {
		$this->update();
	}
	
	/**
	 * 
	 */
	public function get_formatted_date( $type = 'transfer' ) {
		$buffer = '';
		$temp_date = '';
		switch ($type) {
			case 'transfer':
				$temp_date = $this->date_transfer;
				break;
		}
		if ( !empty($temp_date) ) {
			$exploded_date = explode('-', $temp_date);
			$buffer = $exploded_date[2] .'/'. $exploded_date[1] .'/'. $exploded_date[0];
		}
		return $buffer;
	}
	
	/**
	 * Retenter transfert de fonds
	 */
	public function retry() {
		//Si il y avait une erreur sur le transfert
		if ( ( $this->status == WDGROI::$status_error || $this->status == WDGROI::$status_waiting_authentication ) && $this->id_transfer == 0 ) {
			
			$api_org_object = WDGWPREST_Entity_Organization::get( $this->id_orga );
			$organization_obj = new WDGOrganization( $api_org_object->wpref );
			$date_now = new DateTime();
			
			// Versement projet vers organisation
			if ( $this->recipient_type == 'orga' ) {
				$WDGOrga = WDGOrganization::get_by_api_id( $this->id_user );
				$WDGOrga->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_royalties_lemonway_id(), $WDGOrga->get_lemonway_id(), $this->amount );

				// Enregistrement des données de taxe
				if ( $ROI->amount_taxed_in_cents > 0 ) {
					WDGROITax::insert( $this->id, $this->id_user, 'orga', $date_now->format( 'Y-m-d' ), $this->amount_taxed_in_cents, 0, 0, $WDGOrga->get_country(), '0' );
				}

			// Versement projet vers utilisateur personne physique
			} else {
				$WDGUser = WDGUser::get_by_api_id( $this->id_user );
				$WDGUser->register_lemonway();

				// Transfert sur le wallet de séquestre d'impots de l'organisation
				$amount_tax_in_cents = 0;
				if ( $ROI->amount_taxed_in_cents > 0 ) {
					$amount_tax_in_cents = $WDGUser->get_tax_amount_in_cents_round( $ROI->amount_taxed_in_cents );
					if ( $amount_tax_in_cents > 0 ) {
						$organization_obj->check_register_tax_lemonway_wallet();
						LemonwayLib::ask_transfer_funds( $organization_obj->get_royalties_lemonway_id(), $organization_obj->get_tax_lemonway_id(), $amount_tax_in_cents / 100 );
						$percent_tax = $WDGUser->get_tax_percent();
						WDGROITax::insert( $this->id, $this->id_user, 'user', $date_now->format( 'Y-m-d' ), $this->amount_taxed_in_cents, $amount_tax_in_cents, $percent_tax, $WDGUser->get_tax_country(), $WDGUser->has_tax_exemption_for_year( $date_now->format( 'Y' ) ) );
						WDGQueue::add_tax_monthly_summary( $this->id_declaration );
					}
				}

				$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_royalties_lemonway_id(), $WDGUser->get_lemonway_id(), $this->amount - $amount_tax_in_cents / 100 );
			}
			
			if ($transfer != FALSE) {
				$this->status = WDGROI::$status_transferred;
				$this->id_transfer = $transfer->ID;
				$date_now_formatted = $date_now->format( 'Y-m-d' );
				$this->date_transfer = $date_now_formatted;
				$this->update();
			}
		}
	}
	
	/**
	 * Annule un transfert de ROI
	 */
	public function cancel() {
		//Si le ROI était bien transféré
		if ( $this->status == WDGROI::$status_transferred ) {
			
			//Si il y avait bien un ID de transfert sur LW
			if ( $this->id_transfer > 0 ) {
				$organization_obj = WDGOrganization::get_by_api_id( $this->id_orga );

				//Gestion versement organisation vers projet
				$WDGUser = WDGUser::get_by_api_id( $this->id_user );
				if ( WDGOrganization::is_user_organization( $WDGUser->get_wpref() ) ) {
					$WDGOrga = new WDGOrganization( $WDGUser->get_wpref() );
					$transfer = LemonwayLib::ask_transfer_funds( $WDGOrga->get_lemonway_id(), $organization_obj->get_royalties_lemonway_id(), $this->amount );

				//Versement utilisateur personne physique vers projet
				} else {
					$amount_tax_in_cents = $WDGUser->get_tax_amount_in_cents_round( $ROI->amount_taxed_in_cents );
					if ( $amount_tax_in_cents > 0 ) {
						LemonwayLib::ask_transfer_funds( $organization_obj->get_tax_lemonway_id(), $organization_obj->get_royalties_lemonway_id(), $amount_tax_in_cents / 100 );
					}
					$transfer = LemonwayLib::ask_transfer_funds( $WDGUser->get_lemonway_id(), $organization_obj->get_royalties_lemonway_id(), $this->amount - $amount_tax_in_cents / 100 );
				}
			}

			$this->status = WDGROI::$status_canceled;
			$this->update();
		}
	}
	
	
/*******************************************************************************
 * REQUETES STATIQUES
 ******************************************************************************/
	/**
	 * Renvoie le contenu d'un paramètre de la base de données (réglé en BO)
	 */
	public static $option_name = 'wdg_roi_options';
	public static function get_parameter( $parameter_key ) {
		$options_roi = get_option( WDGROI::$option_name );
		return $options_roi[ $parameter_key ];
	}
	
	/**
	 * Ajout d'une nouvelle déclaration
	 */
	public static function insert( $id_investment, $id_campaign, $id_orga, $id_user, $recipient_type, $id_declaration, $date_transfer, $amount, $id_transfer, $status, $id_investment_contract, $amount_taxed_in_cents ) {
		$roi = new WDGROI();
		$roi->id_investment = $id_investment;
		$roi->id_investment_contract = $id_investment_contract;
		$roi->id_campaign = $id_campaign;
		$roi->id_orga = $id_orga;
		$roi->id_user = $id_user;
		$roi->recipient_type = $recipient_type;
		$roi->id_declaration = $id_declaration;
		$roi->date_transfer = $date_transfer;
		$roi->amount = $amount;
		$roi->amount_taxed_in_cents = $amount_taxed_in_cents;
		$roi->id_transfer = $id_transfer;
		$roi->status = $status;
		WDGWPREST_Entity_ROI::create( $roi );
		
		if ( $roi->amount > 0 ) {
			self::check_has_reached_maximum_royalties_amount( $id_investment, $id_campaign, $id_user, $recipient_type );
		}
	}
	
	/**
	 * Annule transferts
	 * @param int $id_start
	 * @param int $id_end
	 */
	public static function cancel_list( $id_start, $id_end = -1 ) {
		if ( $id_end == -1 ) {
			$ROI = new WDGROI( $id_start );
			$ROI->cancel();
			
		} else {
			for ( $id = $id_start; $id <= $id_end; $id++ ) {
				$ROI = new WDGROI( $id );
				$ROI->cancel();
			}
			
		}
	}
	
	/**
	 * Vérifie si le montant max de versement a été atteint pour envoyer une notif
	 * @param type $id_investment
	 * @param type $id_api_campaign
	 * @param type $id_user
	 * @param type $recipient_type
	 */
	public static function check_has_reached_maximum_royalties_amount( $id_investment, $id_api_campaign, $id_user, $recipient_type ) {
		$rois = array();
		$recipient_mail = '';
		$recipient_name = '';
		if ( $recipient_type == 'user' ) {
			$WDGUser = new WDGUser( $id_user );
			$recipient_mail = $WDGUser->get_email();
			$recipient_name = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname();
			$rois = $WDGUser->get_royalties_by_investment_id( $id_investment );
			
		} else {
			$WDGOrganization = new WDGOrganization( $id_user );
			$recipient_mail = $WDGOrganization->get_email();
			$recipient_name = $WDGOrganization->get_name();
			$rois = $WDGOrganization->get_royalties_by_investment_id( $id_investment );
		}
		
		$is_max_profit_reached = FALSE;
		if ( !empty( $rois ) ) {
			$amount_received = 0;
			foreach ( $rois as $roi_item ) {
				$amount_received += $roi_item->amount;
			}
		}
		
		$project_name = '';
		$max_profit_str = '';
		$date_investment = '';
		$url_project = '';
		$amount_investment = 0;
		if ( $amount_received > 0 ) {
			$WDGInvestment = new WDGInvestment( $id_investment );
			$amount_investment = $WDGInvestment->get_saved_amount();
			$date_investment = $WDGInvestment->get_saved_date();
			if ( $amount_received > $amount_investment ) {
				// Test en deux fois pour éviter trop de requêtes
				$campaign = new ATCF_Campaign( FALSE, $id_api_campaign );
				if ( $amount_received >= $amount_investment * $campaign->maximum_profit_complete() ) {
					$is_max_profit_reached = TRUE;
					$project_name = $campaign->get_name();
					$max_profit_str = $campaign->maximum_profit_str();
					$url_project = $campaign->get_public_url();
				}
			}
		}
		
		if ( $is_max_profit_reached ) {
			$amount_investment_str = UIHelpers::format_number( $amount_investment );
			$amount_royalties_str = UIHelpers::format_number( $amount_received );
			NotificationsAPI::roi_transfer_with_max_reached( $recipient_mail, $recipient_name, $project_name, $max_profit_str, $date_investment, $url_project, $amount_investment_str, $amount_royalties_str );
		}
	}
}
