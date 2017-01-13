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
	
	
	public function __construct( $roi_id ) {
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
				'status' => $this->status
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
			
			$organisation_obj = new YPOrganisation( $this->id_orga );
			
			//Gestion versement organisation vers projet
			if (YPOrganisation::is_user_organisation( $this->id_user )) {
				$WDGOrga = new YPOrganisation( $this->id_user );
				$WDGOrga->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organisation_obj->get_lemonway_id(), $WDGOrga->get_lemonway_id(), $this->amount );

			//Versement utilisateur personne physique vers projet
			} else {
				$WDGUser = new WDGUser( $this->id_user );
				$WDGUser->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organisation_obj->get_lemonway_id(), $WDGUser->get_lemonway_id(), $this->amount );
			}
			
			if ($transfer != FALSE) {
				$this->status = WDGROIDeclaration::$status_finished;
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
				$organisation_obj = new YPOrganisation( $this->id_orga );

				//Gestion versement organisation vers projet
				if (YPOrganisation::is_user_organisation( $this->id_user )) {
					$WDGOrga = new YPOrganisation( $this->id_user );
					$transfer = LemonwayLib::ask_transfer_funds( $WDGOrga->get_lemonway_id(), $organisation_obj->get_lemonway_id(), $this->amount );

				//Versement utilisateur personne physique vers projet
				} else {
					$WDGUser = new WDGUser( $this->id_user );
					$transfer = LemonwayLib::ask_transfer_funds( $WDGUser->get_lemonway_id(), $organisation_obj->get_lemonway_id(), $this->amount );
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
}
