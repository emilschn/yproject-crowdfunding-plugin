<?php
/**
 * Classe de gestion des déclarations de ROI
 */
class WDGROIDeclaration {
	public static $table_name = 'ypcf_roideclaration';
	
	public static $status_declaration = 'declaration';
	public static $status_payment = 'payment';
	public static $status_waiting_transfer = 'waiting_transfer';
	public static $status_transfer = 'transfer';
	public static $status_finished = 'finished';
	
	public static $mean_payment_card = 'card';
	public static $mean_payment_wire = 'wire';
	
	public $id;
	public $id_campaign;
	public $date_due;
	public $date_paid;
	public $date_transfer;
	public $amount;
	public $percent_commission;
	public $status;
	public $mean_payment;
	public $payment_token;
	public $file_list;
	private $turnover;
	private $message;
	
	
	public function __construct( $declaration_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . WDGROIDeclaration::$table_name;
		$query = 'SELECT * FROM ' .$table_name. ' WHERE id=' .$declaration_id;
		$declaration_item = $wpdb->get_row( $query );
		if ( $declaration_item ) {
			$this->id = $declaration_item->id;
			$this->id_campaign = $declaration_item->id_campaign;
			$this->date_due = $declaration_item->date_due;
			$this->date_paid = $declaration_item->date_paid;
			$this->date_transfer = $declaration_item->date_transfer;
			$this->amount = $declaration_item->amount;
			$this->percent_commission = $declaration_item->percent_commission;
			$this->status = $declaration_item->status;
			$this->mean_payment = $declaration_item->mean_payment;
			$this->payment_token = $declaration_item->payment_token;
			$this->file_list = $declaration_item->file_list;
			$this->turnover = $declaration_item->turnover;
			$this->message = $declaration_item->message;
		}
	}
	
	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . WDGROIDeclaration::$table_name;
		$result = $wpdb->update( 
			$table_name, 
			array( 
				'id_campaign' => $this->id_campaign,
				'date_due' => $this->date_due,
				'date_paid' => $this->date_paid,
				'date_transfer' => $this->date_transfer,
				'amount' => $this->amount,
				'percent_commission' => $this->percent_commission,
				'status' => $this->status,
				'mean_payment' => $this->mean_payment,
				'payment_token' => $this->payment_token,
				'file_list' => $this->file_list,
				'turnover' => $this->turnover,
				'message' => $this->message
			),
			array(
				'id' => $this->id
			)
		);
		if ($result !== FALSE) {
			return $this->id;
		}
	}
	
	public function get_formatted_date( $type = 'due' ) {
		$buffer = '';
		$temp_date = '';
		switch ($type) {
			case 'due':
				$temp_date = $this->date_due;
				break;
			case 'paid':
				$temp_date = $this->date_paid;
				break;
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
	
	public function get_status() {
		if ( empty( $this->status ) ) {
			$this->status = WDGROIDeclaration::$status_declaration;
		}
		if ( $this->status == WDGROIDeclaration::$status_declaration && $this->amount > 0 ) {
			$this->status = WDGROIDeclaration::$status_payment;
		}
		return $this->status;
	}
	
	/**
	 * Retourne le montant additionné avec la commission
	 * @return number
	 */
	public function get_amount_with_commission() {
		return ($this->amount + $this->get_commission_to_pay());
	}
	
	/**
	 * Retourne la commission éventuelle que doit payer le porteur de projet au moment de reverser les fonds
	 * @return number
	 */
	public function get_commission_to_pay() {
		$buffer = 0;
		
		//Si le porteur de projet a déjà payé, on considère qu'on a déjà enregistré la commission
		if ( $this->status == WDGROIDeclaration::$status_transfer || $this->status == WDGROIDeclaration::$status_finished ) {
			$cost = $this->percent_commission;
			
		//Sinon, on la calcule avec les frais enregistrés en rapport avec la campagne
		} else {
			$campaign = new ATCF_Campaign( $this->id_campaign );
			$cost = $campaign->get_costs_to_organization();
		}
		
		if ( $cost > 0 ) {
			$buffer = (round(($this->amount * $cost / 100) * 100) / 100);
		}
		return $buffer;
	}
	
	/**
	 * Traite un fichier uploadé qui doit être ajouté à la liste
	 * @param array $file_uploaded_data
	 */
	public function add_file( $file_uploaded_data ) {
		$file_name = $file_uploaded_data['name'];
		$file_name_exploded = explode('.', $file_name);
		$ext = $file_name_exploded[count($file_name_exploded) - 1];
		
		$random_filename = '';
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$size = strlen( $chars );
		for( $i = 0; $i < 15; $i++ ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		while ( file_exists( __DIR__ . '/../accounts/' . $random_filename . '.' . $ext ) ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		$random_filename = $random_filename . '.' . $ext;
		move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../accounts/' . $random_filename );
		
		if ( empty($this->file_list) ) {
			$this->file_list = $random_filename;
		} else {
			$this->file_list .= ';' . $random_filename;
		}
		
		$this->save();
	}
	/**
	 * Renvoie la liste des fichiers avec leur bonne url
	 * @return array
	 */
	public function get_file_list() {
		$buffer = array();
		if ( !empty( $this->file_list ) ) {
			$filename_array = explode(';', $this->file_list);
			foreach ($filename_array as $filename) {
				array_push($buffer, home_url() . '/wp-content/plugins/appthemer-crowdfunding/includes/accounts/' . $filename);
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne le CA sous forme de tableau
	 * @return array
	 */
	public function get_turnover() {
		$buffer = json_decode($this->turnover);
		return $buffer;
	}
	/**
	 * Enregistre le CA en json
	 * @param array $turnover_array
	 */
	public function set_turnover($turnover_array) {
		$saved_turnover = json_encode($turnover_array);
		$this->turnover = $saved_turnover;
	}
	/**
	 * Retourne le montant total de CA déclaré
	 * @return int
	 */
	public function get_turnover_total() {
		$buffer = 0;
		$turnover_array = $this->get_turnover();
		foreach ($turnover_array as $turnover_amount) {
			$buffer += $turnover_amount;
		}
		return $buffer;
	}
	
	public function get_message() {
		return nl2br( $this->message, ENT_HTML5 );
	}
	public function set_message( $message ) {
		$this->message = htmlentities( $message );
	}
	
	/**
	 * S'occuper des versements vers les utilisateurs
	 */
	public function make_transfer( $send_notifications = true ) {
		$buffer = false;
		$date_now = new DateTime();
		$date_now_formatted = $date_now->format( 'Y-m-d' );
		$campaign = new ATCF_Campaign($this->id_campaign);
		$current_organization = $campaign->get_organization();
		if ( !empty( $current_organization ) ) {
			$organization_obj = new WDGOrganization($current_organization->wpref);
			$organization_obj->register_lemonway();
			$investments_list = $campaign->roi_payments_data($this);
			$total_fees = 0;
			foreach ($investments_list as $investment_item) {
				
				$saved_roi = WDGROI::get_roi_by_declaration_invest( $this->id, $investment_item['ID'] );
				if ( empty( $saved_roi ) ) {
					$total_fees += $investment_item['roi_fees'];

					//Versement vers organisation
					if (WDGOrganization::is_user_organization( $investment_item['user'] )) {
						$WDGOrga = new WDGOrganization( $investment_item['user'] );
						$WDGOrga->register_lemonway();
						$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGOrga->get_lemonway_id(), $investment_item['roi_amount'] );

					//Versement vers utilisateur personne physique
					} else {
						$WDGUser = new WDGUser( $investment_item['user'] );
						$WDGUser->register_lemonway();
						$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGUser->get_lemonway_id(), $investment_item['roi_amount'] );
					}

					if ( $transfer != FALSE ) {
						WDGROI::insert($investment_item['ID'], $this->id_campaign, $current_organization->wpref, $investment_item['user'], $this->id, $date_now_formatted, $investment_item['roi_amount'], $transfer->ID, WDGROI::$status_transferred);
						if ( $send_notifications ) {
							if ($investment_item['roi_amount'] > 0) {
								NotificationsEmails::roi_transfer_success_user( $this->id, $investment_item['user'], $this->get_message() );
							} else {
								NotificationsEmails::roi_transfer_null_user( $this->id, $investment_item['user'], $this->get_message() );
							}
						}

					} else {
						WDGROI::insert($investment_item['ID'], $this->id_campaign, $current_organization->wpref, $investment_item['user'], $this->id, $date_now_formatted, $investment_item['roi_amount'], 0, WDGROI::$status_error);

					}
					
				}
			}
			if ($total_fees > 0) {
				LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), "SC", $total_fees);
			}
			$this->status = WDGROIDeclaration::$status_finished;
			$this->date_transfer = $date_now_formatted;
			$this->save();
			$buffer = true;
		}
		return $buffer;
	}
	
	/**
	 * Répare un versement qui n'a pas eu lieu vers un utilisateur
	 */
	public function redo_transfers() {
		$campaign = new ATCF_Campaign($this->id_campaign);
		$current_organization = $campaign->get_organization();
		if (!empty($current_organization)) {
			$organization_obj = new WDGOrganization($current_organization->wpref);
		}
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROI::$table_name;
		$query .= " WHERE id_declaration=".$this->id;
		$query .= " AND amount>0";
		$query .= " AND id_transfer=0";
		$query .= " AND status='" .WDGROI::$status_transferred. "'";
		
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
			$ROI = new WDGROI( $roi_item->id );
			
			//Gestion versement vers organisation
			if (WDGOrganization::is_user_organization( $ROI->id_user )) {
				$WDGOrga = new WDGOrganization( $ROI->id_user );
				$WDGOrga->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGOrga->get_lemonway_id(), $ROI->amount );
				
			//Versement vers utilisateur personne physique
			} else {
				$WDGUser = new WDGUser( $ROI->id_user );
				$WDGUser->register_lemonway();
				$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGUser->get_lemonway_id(), $ROI->amount );
			}
			
			$ROI->id_transfer = $transfer->ID;
			$ROI->save();
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
		
		$table_name = $wpdb->prefix . WDGROIDeclaration::$table_name;
		$sql = "CREATE TABLE " .$table_name. " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			id_campaign mediumint(9) NOT NULL,
			date_due date DEFAULT '0000-00-00',
			date_paid date DEFAULT '0000-00-00',
			date_transfer date DEFAULT '0000-00-00',
			amount float,
			percent_commission float,
			status tinytext,
			mean_payment tinytext,
			payment_token tinytext,
			file_list text,
			turnover text,
			message text,
			UNIQUE KEY id (id)
		) $charset_collate;";
		$result = dbDelta( $sql );
	}
	
	/**
	 * Ajout d'une nouvelle déclaration
	 */
	public static function insert( $id_campaign, $date_due ) {
		global $wpdb;
		$result = $wpdb->insert( 
			$wpdb->prefix . WDGROIDeclaration::$table_name, 
			array( 
				'id_campaign'	=> $id_campaign, 
				'date_due'		=> $date_due
			) 
		);
		if ($result !== FALSE) {
			return $wpdb->insert_id;
		}
	}
	
	/**
	 * Liste des déclarations ROI pour un projet
	 */
	public static function get_list_by_campaign_id( $id_campaign ) {
		$buffer = array();
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROIDeclaration::$table_name;
		$query .= " WHERE id_campaign=".$id_campaign;
		$query .= " ORDER BY date_due ASC";
		
		$declaration_list = $wpdb->get_results( $query );
		foreach ( $declaration_list as $declaration_item ) {
			$ROIdeclaration = new WDGROIDeclaration( $declaration_item->id );
			array_push($buffer, $ROIdeclaration);
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne une déclaration ROI par son token de paiement
	 * @param string $token
	 * @return WDGROIDeclaration
	 */
	public static function get_by_payment_token( $token ) {
		$buffer = FALSE;
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROIDeclaration::$table_name;
		$query .= " WHERE payment_token='" .$token. "'";
		
		$declaration_list = $wpdb->get_results( $query );
		foreach ( $declaration_list as $declaration_item ) {
			$buffer = new WDGROIDeclaration( $declaration_item->id );
		}
		
		return $buffer;
	}
}
