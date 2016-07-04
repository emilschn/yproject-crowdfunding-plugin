<?php
/**
 * Classe de gestion des ROI
 */
class WDGROI {
	public static $table_name = 'ypcf_roi';
	
	public static $status_transferred = "transferred";
	
	public $id;
	public $id_campaign;
	public $id_orga;
	public $id_user;
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
			$this->id_campaign = $roi_item->id_campaign;
			$this->id_orga = $roi_item->id_orga;
			$this->id_user = $roi_item->id_user;
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
				'id_campaign' => $this->id_campaign,
				'id_orga' => $this->id_orga,
				'id_user' => $this->id_user,
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
			id_campaign mediumint(9) NOT NULL,
			id_orga mediumint(9) NOT NULL,
			id_user mediumint(9) NOT NULL,
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
	public static function insert( $id_campaign, $id_orga, $id_user, $date_transfer, $amount, $id_transfer, $status ) {
		global $wpdb;
		$result = $wpdb->insert( 
			$wpdb->prefix . WDGROI::$table_name, 
			array( 
				'id_campaign'	=> $id_campaign, 
				'id_orga'		=> $id_orga,
				'id_user'		=> $id_user,
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
		$query .= " ORDER BY date_due ASC";
		
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
			$ROI = new WDGROI( $roi_item->id );
			array_push($buffer, $ROI);
		}
		
		return $buffer;
	}
}
