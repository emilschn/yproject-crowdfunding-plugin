<?php
/**
 * Classe de gestion des ROI
 */
class WDGROI {
	public static $table_name = 'ypcf_roi';
	
	public static $status_transferred = "transferred";
	public static $status_canceled = "canceled";
	public static $status_error = "error";
	
	public $id;
	public $id_investment;
	public $id_campaign;
	public $id_orga;
	public $id_user;
	public $id_declaration;
	public $date_transfer;
	public $amount;
	public $id_transfer;
	public $status;
	public $on_api;
	
	
	public function __construct( $roi_id ) {
		// Récupération en priorité depuis l'API
		$roi_api_item = WDGWPREST_Entity_ROI::get( $roi_id );
		if ( $roi_api_item != FALSE ) {
			
			$this->id = $roi_id;
			$this->id_investment = $roi_api_item->id_investment;
			$this->id_campaign = $roi_api_item->id_project;
			$this->id_orga = $roi_api_item->id_orga;
			$this->id_user = $roi_api_item->id_user;
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
				$this->id_declaration = $roi_item->id_declaration;
				$this->date_transfer = $roi_item->date_transfer;
				$this->amount = $roi_item->amount;
				$this->id_transfer = $roi_item->id_transfer;
				$this->status = $roi_item->status;
				$this->on_api = ( $roi_item->on_api == 1 );
			}
		}
	}
	
	public function save() {
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
		if ( $this->status == WDGROI::$status_error && $this->id_transfer == 0 ) {
			
			$organization_obj = new WDGOrganization( $this->id_orga );
			
			// Versement projet vers organisation
			if (WDGOrganization::is_user_organization( $this->id_user )) {
				$WDGOrga = new WDGOrganization( $this->id_user );
				$WDGOrga->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGOrga->get_lemonway_id(), $this->amount );

			// Versement projet vers utilisateur personne physique
			} else {
				$WDGUser = new WDGUser( $this->id_user );
				$WDGUser->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGUser->get_lemonway_id(), $this->amount );
			}
			
			if ($transfer != FALSE) {
				$this->status = WDGROI::$status_transferred;
				$this->id_transfer = $transfer->ID;
				$date_now = new DateTime();
				$date_now_formatted = $date_now->format( 'Y-m-d' );
				$this->date_transfer = $date_now_formatted;
				$this->save();
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
				$organization_obj = new WDGOrganization( $this->id_orga );

				//Gestion versement organisation vers projet
				if (WDGOrganization::is_user_organization( $this->id_user )) {
					$WDGOrga = new WDGOrganization( $this->id_user );
					$transfer = LemonwayLib::ask_transfer_funds( $WDGOrga->get_lemonway_id(), $organization_obj->get_lemonway_id(), $this->amount );

				//Versement utilisateur personne physique vers projet
				} else {
					$WDGUser = new WDGUser( $this->id_user );
					$transfer = LemonwayLib::ask_transfer_funds( $WDGUser->get_lemonway_id(), $organization_obj->get_lemonway_id(), $this->amount );
				}
			}

			$this->status = WDGROI::$status_canceled;
			$this->save();
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
	public static function insert( $id_investment, $id_campaign, $id_orga, $id_user, $id_declaration, $date_transfer, $amount, $id_transfer, $status ) {
		global $wpdb;
		$result = $wpdb->insert( 
			$wpdb->prefix . WDGROI::$table_name, 
			array( 
				'id_investment'	=> $id_investment, 
				'id_campaign'	=> $id_campaign, 
				'id_orga'		=> $id_orga,
				'id_user'		=> $id_user,
				'id_declaration'=> $id_declaration,
				'date_transfer'	=> $date_transfer,
				'amount'		=> $amount,
				'id_transfer'	=> $id_transfer,
				'status'		=> $status
			) 
		);
		if ($result !== FALSE) {
			return $wpdb->insert_id;
		}
	}
	
	/**
	 * Retourne une liste de ROI enregistrés en fonction d'un utilisateur
	 * @param int $id_user
	 * @param int $year
	 */
	public static function get_roi_list_by_user( $id_user, $year = '' ) {
		$buffer = array();
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROI::$table_name;
		$query .= " WHERE id_user=".$id_user;
		$query .= " AND status='".WDGROI::$status_transferred."'";
		if ( !empty( $year ) ) {
			$query .= " AND YEAR(date_transfer)=".$year;
		}
		$query .= " ORDER BY date_transfer ASC";
		
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
			$ROI = new WDGROI( $roi_item->id );
			array_push($buffer, $ROI);
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne une liste de ROI enregistrés en fonction d'un projet et d'un utilisateur
	 * @param int $id_campaign
	 * @param int $id_user
	 */
	public static function get_roi_list_by_campaign_user( $id_campaign, $id_user ) {
		$buffer = array();
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROI::$table_name;
		$query .= " WHERE id_campaign=".$id_campaign;
		$query .= " AND id_user=".$id_user;
		$query .= " AND status='".WDGROI::$status_transferred."'";
		$query .= " ORDER BY date_transfer ASC";
		
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
			$ROI = new WDGROI( $roi_item->id );
			array_push($buffer, $ROI);
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne une liste de ROI enregistrés en fonction d'une déclaration
	 * @param int $id_declaration
	 */
	public static function get_roi_list_by_declaration( $id_declaration ) {
		$buffer = array();
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROI::$table_name;
		$query .= " WHERE id_declaration=".$id_declaration;
		$query .= " AND status='".WDGROI::$status_transferred."'";
		$query .= " ORDER BY date_transfer ASC";
		
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
			$ROI = new WDGROI( $roi_item->id );
			array_push($buffer, $ROI);
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne les ID de ROI concernés par une déclaration, un utilisateur et un montant
	 * @param int $id_declaration
	 * @param int $id_user
	 * @param float $amount
	 * @return array
	 */
	public static function get_roiid_list_by_declaration_user_amount( $id_declaration, $id_user, $amount ) {
		$buffer = array();
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROI::$table_name;
		$query .= " WHERE id_declaration=".$id_declaration;
		$query .= " AND id_user=".$id_user;
		$query .= " AND amount LIKE '".$amount."'";
		$query .= " AND status='".WDGROI::$status_transferred."'";
		$query .= " ORDER BY date_transfer ASC";
		
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
			array_push($buffer, $roi_item->id);
		}
		
		return $buffer;
	}
	
	public static function get_roi_by_declaration_invest( $id_declaration, $id_investment ) {
		$buffer = array();
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROI::$table_name;
		$query .= " WHERE id_investment=".$id_investment;
		$query .= " AND id_declaration=".$id_declaration;
		$query .= " AND status='".WDGROI::$status_transferred."'";
		
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
			$ROI = new WDGROI( $roi_item->id );
			array_push($buffer, $ROI);
		}
		
		return $buffer;
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
	 * Transfère les données du ROI vers l'API
	 */
	public static function transfer_to_api( $old_declaration_id, $new_declaration_id ) {
		global $wpdb;
		$campaign_wpref_to_api = array();
		$orga_wpref_to_api = array();
		$user_wpref_to_api = array();
		
		$query = "SELECT id, on_api FROM " .$wpdb->prefix. WDGROI::$table_name. " WHERE id_declaration=" .$old_declaration_id;
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
			if ( !$roi_item->on_api ) {
				$roi = new WDGROI( $roi_item->id );
				
				if ( empty( $campaign_wpref_to_api[ $roi->id_campaign ] ) ) {
					$campaign = new ATCF_Campaign( $roi->id_campaign );
					$campaign_wpref_to_api[ $roi->id_campaign ] = $campaign->get_api_id();
				}
				$temp_campaign_id = $roi->id_campaign;
				if ( empty( $orga_wpref_to_api[ $roi->id_orga ] ) ) {
					$orga = new WDGOrganization( $roi->id_orga );
					$orga_wpref_to_api[ $roi->id_orga ] = $orga->get_api_id();
				}
				$temp_orga_id = $roi->id_orga;
				if ( empty( $user_wpref_to_api[ $roi->id_user ] ) ) {
					if ( WDGOrganization::is_user_organization( $roi->id_user ) ) {
						$orga = new WDGOrganization( $roi->id_user );
						$user_wpref_to_api[ $roi->id_user ] = $orga->get_api_id();
					} else {
						$user = new WDGUser( $roi->id_user );
						$user_wpref_to_api[ $roi->id_user ] = $user->get_api_id();
					}
				}
				$temp_user_id = $roi->id_user;
				
				$roi->id_campaign = $campaign_wpref_to_api[ $roi->id_campaign ];
				$roi->id_declaration = $new_declaration_id;
				$roi->id_orga = $orga_wpref_to_api[ $roi->id_orga ];
				$roi->id_user = $user_wpref_to_api[ $roi->id_user ];
				WDGWPREST_Entity_ROI::create( $roi );
				
				$roi->on_api = true;
				$roi->id_campaign = $temp_campaign_id;
				$roi->id_declaration = $old_declaration_id;
				$roi->id_orga = $temp_orga_id;
				$roi->id_user = $temp_user_id;
				$roi->save();
			}
		}
	}
}
