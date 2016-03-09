<?php
/**
 * Classe de gestion des déclarations de ROI
 */
class WDGROIDeclaration {
	public static $table_name = 'ypcf_roideclaration';
	
	public static $status_declaration = 'declaration';
	public static $status_payment = 'payment';
	public static $status_transfer = 'transfer';
	public static $status_finished = 'finished';
	
	public $id;
	public $id_campaign;
	public $date_due;
	public $date_paid;
	public $date_transfer;
	public $amount;
	public $status;
	public $mean_payment;
	public $file_list;
	
	
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
			$this->status = $declaration_item->status;
			$this->mean_payment = $declaration_item->mean_payment;
			$this->file_list = $declaration_item->file_list;
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
				'status' => $this->status, 
				'mean_payment' => $this->mean_payment, 
				'file_list' => $this->file_list, 
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
			amount mediumint(9),
			status tinytext,
			mean_payment tinytext,
			file_list text,
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

}
