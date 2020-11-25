<?php
/**
 * Classe de gestion des déclarations de ROI
 */
class WDGKYCFile {
	public static $table_name = 'ypcf_kycfiles';
	
	public static $owner_user = 'user';
	public static $owner_organization = 'organization';
	
	public static $type_bank = 'bank';
	public static $type_id = 'id';
	public static $type_id_back = 'id_back';
	public static $type_idbis = 'idbis';
	public static $type_kbis = 'kbis';
	public static $type_status = 'status';
	public static $type_capital_allocation = 'capital_allocation';
	public static $type_id_2 = 'id_2';
	public static $type_id_2_back = 'id_2_back';
	public static $type_idbis_2 = 'idbis_2';
	public static $type_id_3 = 'id_3';
	public static $type_idbis_3 = 'idbis_3';
	
	public static $status_uploaded = 'uploaded';
	public static $status_sent = 'sent';
	
	public static $authorized_format_list = array('pdf', 'jpg', 'jpeg', 'bmp', 'gif', 'tif', 'tiff', 'png');
	
	public $id;
	public $type;
	public $orga_id;
	public $user_id;
	public $file_name;
	public $status;
	public $date_uploaded;
	
	
	public function __construct( $kycfile_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . WDGKYCFile::$table_name;
		$query = 'SELECT * FROM ' .$table_name. ' WHERE id=' .$kycfile_id;
		$kycfile_item = $wpdb->get_row( $query );
		if ( $kycfile_item ) {
			$this->id = $kycfile_item->id;
			$this->type = $kycfile_item->type;
			$this->orga_id = $kycfile_item->orga_id;
			$this->user_id = $kycfile_item->user_id;
			$this->file_name = $kycfile_item->file_name;
			$this->status = $kycfile_item->status;
			$this->date_uploaded = $kycfile_item->date_uploaded;
		}
	}
	
	/**
	 * Enregistre les modifications sur l'élément
	 * @return int
	 */
	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . WDGKYCFile::$table_name;
		$result = $wpdb->update( 
			$table_name, 
			array( 
				'type' => $this->type,
				'orga_id' => $this->orga_id,
				'user_id' => $this->user_id,
				'file_name' => $this->file_name,
				'status' => $this->status,
				'date_uploaded' => $this->date_uploaded,
			),
			array(
				'id' => $this->id
			)
		);
		if ($result !== FALSE) {
			return $this->id;
		}
	}
	
	/**
	 * Retourne le chemin vers le fichier pour un téléchargement
	 */
	public function get_public_filepath() {
		return home_url() . '/wp-content/plugins/appthemer-crowdfunding/includes/kyc/' . $this->file_name;
	}
	
	/**
	 * Retourne le tableau de bytes à envoyer à Lemonway
	 */
	public function get_byte_array() {
		$byte_array = file_get_contents( __DIR__ . '/../kyc/' . $this->file_name );
		return $byte_array;
	}
	
	/**
	 * Retourne le hash md5 du tableau de bytes du fichier
	 */
	public function get_byte_array_md5() {
		return md5( $this->get_byte_array() );
	}
	
	/**
	 * Retourne la date d'upload au format "Y-m-d"
	 */
	public function get_date_uploaded() {
		return $this->date_uploaded;
	}

	/**
	 * Retourne le chemin vers le fichier pour suppression
	 * @return string
	 */
	private function get_filepath( ) {
		return dirname( __FILE__ ) . '/../../includes/kyc/' .$this->file_name;
	}

	/**
	 * Supprime un document (en base et dans le système de fichier)
	 *
	 * @return void
	 */
	public function delete() {
		$is_deleted = FALSE;
		$result = FALSE;
		// on supprime le fichier dans le système de fichier
		$file_path = $this->get_filepath();
		if( file_exists ( $file_path)) {
			$is_deleted = unlink( $file_path );
		};
		if ( $is_deleted ){
			// on le supprime en bdd
			global $wpdb;
			$table_name = $wpdb->prefix . WDGKYCFile::$table_name;
			$result = $wpdb->delete( 
				$table_name, 
				array( 
					'id' => $this->id,
					'type' => $this->type,
					'orga_id' => $this->orga_id,
					'user_id' => $this->user_id,
					'file_name' => $this->file_name,
					'status' => $this->status,
					'date_uploaded' => $this->date_uploaded,
				)
			);
		}
		return $result;
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
		
		$table_name = $wpdb->prefix . WDGKYCFile::$table_name;
		$sql = "CREATE TABLE " .$table_name. " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			type tinytext,
			orga_id mediumint(9) NOT NULL,
			user_id mediumint(9) NOT NULL,
			file_name tinytext,
			status text,
			date_uploaded date DEFAULT '0000-00-00',
			UNIQUE KEY id (id)
		) $charset_collate;";
		$result = dbDelta( $sql );
	}
	
/**
 * Ajoute un nouveau fichier
 */
	public static function add_file( $type, $id_owner, $type_owner, $file_uploaded_data ) {
		$file_name = $file_uploaded_data['name'];
		$file_name_exploded = explode('.', $file_name);
		$ext = $file_name_exploded[count($file_name_exploded) - 1];
		
		if ( !in_array( strtolower( $ext ), WDGKYCFile::$authorized_format_list ) ) {
			return 'ext';
		}
		if ( ($file_uploaded_data['size'] / 1024) / 1024 > 6 ) {
			return 'size';
		}
		
		$random_filename = '';
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$size = strlen( $chars );
		for( $i = 0; $i < 15; $i++ ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		while ( file_exists( __DIR__ . '/../kyc/' . $random_filename . '.' . $ext ) ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		$random_filename = $random_filename . '.' . $ext;
		move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../kyc/' . $random_filename );
		
		global $wpdb;
		$date_now = new DateTime();
		$more_orga = false;
		
		if ( $type_owner == WDGKYCFile::$owner_user ){
			$user_id = $id_owner;
			$WDGUser = new WDGUser( $id_owner );
			$orga_id = 0;
			$orga_list = $WDGUser->get_organizations_list();
			
			if( is_array($orga_list) && count($orga_list) == 1 ){
				$orga_id = $orga_list[0]->wpref;
			} elseif ( is_array($orga_list) && count($orga_list) > 1 ) {
				$more_orga = true;
			}
		} elseif ($type_owner == WDGKYCFile::$owner_organization){
			$orga_id = $id_owner;
			$user_id = 0;
		}
			
		if ( $more_orga ) {
			foreach ( $orga_list as $orga ) {
				$result = $wpdb->insert( 
					$wpdb->prefix . WDGKYCFile::$table_name, 
					array( 
						'type'			=> $type,
						'orga_id'		=> $orga->wpref,
						'user_id'		=> $user_id,
						'file_name'		=> $random_filename, 
						'status'		=> WDGKYCFile::$status_uploaded, 
						'date_uploaded'	=> $date_now->format("Y-m-d")
					) 
				);
			}
		} else {
			$result = $wpdb->insert( 
				$wpdb->prefix . WDGKYCFile::$table_name, 
				array( 
					'type'			=> $type,
					'orga_id'		=> $orga_id,
					'user_id'		=> $user_id,
					'file_name'		=> $random_filename, 
					'status'		=> WDGKYCFile::$status_uploaded, 
					'date_uploaded'	=> $date_now->format("Y-m-d")
				) 
			);
		}


		if ($result !== FALSE) {
			return $wpdb->insert_id;
		}
	}
	
/**
 * Liste des fichiers par possesseur
 */
	public static function get_list_by_owner_id( $id_owner, $type_owner = 'organization', $type = '' ) {
		$buffer = array();
		
		if ( !empty( $id_owner ) ) {
			global $wpdb;
			$query = "SELECT id FROM " .$wpdb->prefix . WDGKYCFile::$table_name;
			if ($type_owner == WDGKYCFile::$owner_organization) {
				$query .= " WHERE orga_id=".$id_owner;
			} else {
				$query .= " WHERE user_id=".$id_owner;
			}
			if ( !empty( $type ) ) {
				$query .= " AND type='" . $type . "'";
			}
			$query .= " ORDER BY date_uploaded DESC, id DESC";
			
			$kycfile_list = $wpdb->get_results( $query );
			foreach ( $kycfile_list as $kycfile_item ) {
				$KYCfile = new WDGKYCFile( $kycfile_item->id );
				array_push($buffer, $KYCfile);
			}
		}
		
		return $buffer;
	}

}
