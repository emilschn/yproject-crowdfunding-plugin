<?php
/**
 * Classe de gestion des ROI
 */
class WDGROI {
	public static $table_name = 'ypcf_roi';
	
	public static $status_waiting_authentication = "waiting_authentication";
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
				$this->id_transfer = $roi_api_item->id_transfer;
				$this->status = $roi_api_item->status;
				$this->on_api = TRUE;

			// Sinon récupération sur la bdd locale (deprecated)
			} else {
				global $wpdb;
				$table_name = $wpdb->prefix . WDGROI::$table_name;
				$query = 'SELECT * FROM ' .$table_name. ' WHERE id=' .$roi_id;
				$roi_item = $wpdb->get_row( $query );
				if ( $roi_item ) {
					$this->id = $roi_item->id;
					$this->id_investment = $roi_item->id_investment;
					$this->id_campaign = $roi_item->id_campaign;
					$this->id_orga = $roi_item->id_orga;
					$this->id_user = $roi_item->id_user;
					$this->recipient_type = 'user';
					$this->id_declaration = $roi_item->id_declaration;
					$this->date_transfer = $roi_item->date_transfer;
					$this->amount = $roi_item->amount;
					$this->id_transfer = $roi_item->id_transfer;
					$this->status = $roi_item->status;
					$this->on_api = ( $roi_item->on_api == 1 );
				}
			}
		}
	}
	
	/**
	 * Sauvegarde les données dans l'API
	 */
	public function update() {
		WDGWPREST_Entity_ROI::update( $this );
	}
	
	public function save( $local = FALSE ) {
		if ( $this->on_api && !$local ) {
			$this->update();
			
		} else {
			global $wpdb;
			$table_name = $wpdb->prefix . WDGROI::$table_name;
			$result = $wpdb->update( 
				$table_name, 
				array( 
					'id_investment' => $this->id_investment,
					'id_campaign' => $this->id_campaign,
					'id_orga' => $this->id_orga,
					'id_user' => $this->id_user,
					'id_declaration' => $this->id_declaration,
					'date_transfer' => $this->date_transfer, 
					'amount' => $this->amount,
					'id_transfer' => $this->id_transfer,
					'status' => $this->status,
					'on_api' => ( $this->on_api ? 1 : 0 )
				),
				array(
					'id' => $this->id
				)
			);
			if ($result !== FALSE) {
				return $this->id;
			}
		}
	}
	
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
			
			$WDGUser = WDGUser::get_by_api_id( $this->id_user );
			// Versement projet vers organisation
			if (WDGOrganization::is_user_organization( $WDGUser->get_wpref() )) {
				$WDGOrga = new WDGOrganization( $WDGUser->get_wpref() );
				$WDGOrga->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_royalties_lemonway_id(), $WDGOrga->get_lemonway_id(), $this->amount );

			// Versement projet vers utilisateur personne physique
			} else {
				$WDGUser->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_royalties_lemonway_id(), $WDGUser->get_lemonway_id(), $this->amount );
			}
			
			if ($transfer != FALSE) {
				$this->status = WDGROI::$status_transferred;
				$this->id_transfer = $transfer->ID;
				$date_now = new DateTime();
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
					$transfer = LemonwayLib::ask_transfer_funds( $WDGUser->get_lemonway_id(), $organization_obj->get_royalties_lemonway_id(), $this->amount );
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
	 * Mise à jour base de données
	 */
	public static function upgrade_db() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$table_name = $wpdb->prefix . WDGROI::$table_name;
		$sql = "CREATE TABLE " .$table_name. " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			id_investment mediumint(9) NOT NULL,
			id_campaign mediumint(9) NOT NULL,
			id_orga mediumint(9) NOT NULL,
			id_user mediumint(9) NOT NULL,
			id_declaration mediumint(9) NOT NULL,
			date_transfer date DEFAULT '0000-00-00',
			amount float,
			id_transfer mediumint(9) NOT NULL,
			status tinytext,
			on_api tinyint DEFAULT 0,
			UNIQUE KEY id (id)
		) $charset_collate;";
		$result = dbDelta( $sql );
	}
	
	/**
	 * Ajout d'une nouvelle déclaration
	 */
	public static function insert( $id_investment, $id_campaign, $id_orga, $id_user, $recipient_type, $id_declaration, $date_transfer, $amount, $id_transfer, $status, $id_investment_contract ) {
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
		$roi->id_transfer = $id_transfer;
		$roi->status = $status;
		WDGWPREST_Entity_ROI::create( $roi );
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
}
