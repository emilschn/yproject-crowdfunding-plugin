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
	public static $type_id_2 = 'id_2';
	public static $type_id_2_back = 'id_2_back';

	public static $type_kbis = 'kbis';
	public static $type_status = 'status';
	public static $type_capital_allocation = 'capital_allocation';// TODO : normalement c'est capital-allocation ?
	public static $type_idbis = 'idbis';
	public static $type_idbis_2 = 'idbis_2';
	public static $type_id_3 = 'id_3';
	public static $type_idbis_3 = 'idbis_3';

	// nouveaux types présents sur l'API
	public static $type_passport = 'passport';
	public static $type_tax = 'tax';
	public static $type_welfare = 'welfare';
	public static $type_family = 'family';
	public static $type_birth = 'birth';
	public static $type_driving = 'driving';

	public static $type_person2_doc1 = 'person2-doc1';
	public static $type_person2_doc2 = 'person2-doc2';
	public static $type_person3_doc1 = 'person3-doc1';
	public static $type_person3_doc2 = 'person3-doc2';
	public static $type_person4_doc1 = 'person4-doc1';
	public static $type_person4_doc2 = 'person4-doc2';

	public static $status_uploaded = 'uploaded';
	public static $status_sent = 'sent';

	public static $authorized_format_list = array('pdf', 'jpg', 'jpeg', 'bmp', 'gif', 'tif', 'tiff', 'png');

	public static $gateway_lemonway = 'lemonway';

	public $id;
	public $type;
	public $doc_index = 1;// par défaut à 1, car n'existait pas avat
	public $orga_id;
	public $user_id;
	public $file_extension;
	public $file_name;
	public $file_signature;
	public $status;
	public $date_uploaded;
	public $gateway;
	public $gateway_id;
	public $gateway_user_id;
	public $gateway_organization_id;
	public $metadata;
	public $url;

	public $is_api_file;

	/**
	 * constructeur
	 * @param int $kycfile_id
	 */
	public function __construct($kycfile_id, $is_api_file = FALSE) {
		if ( $is_api_file == FALSE ){
			// TODO : à supprimer après transfert de tous les kyc sur l'API
			// on cherche sur le site
			global $wpdb;
			$table_name = $wpdb->prefix . WDGKYCFile::$table_name;
			$query = 'SELECT * FROM ' .$table_name. ' WHERE id=' .$kycfile_id;
			$kycfile_item = $wpdb->get_row( $query );
			if ( $kycfile_item && $kycfile_item !== FALSE ) {
				$this->id = $kycfile_item->id;
				$this->type = $kycfile_item->type;
				$this->orga_id = $kycfile_item->orga_id;
				$this->user_id = $kycfile_item->user_id;
				$this->file_name = $kycfile_item->file_name;
				$this->status = $kycfile_item->status;
				$this->date_uploaded = $kycfile_item->date_uploaded;
				$this->gateway = $kycfile_item->gateway;
				$this->gateway_id = $kycfile_item->gateway_id;
				$this->is_api_file = FALSE;
			}
		}else{
			// sinon on cherche dans l'API s'il existe un kycfile avec cet id
			$kycfile_item = WDGWPREST_Entity_FileKYC::get( $kycfile_id );
			if( isset($kycfile_item) && $kycfile_item !== FALSE ) {
				$this->id = $kycfile_item->id;
				$this->user_id = $kycfile_item->user_id;
				$this->orga_id = $kycfile_item->organization_id;
				$this->type = $kycfile_item->doc_type;
				$this->doc_index = $kycfile_item->doc_index;
				$this->file_extension = $kycfile_item->file_extension;
				$this->file_name = $kycfile_item->file_name;
				$this->file_signature = $kycfile_item->file_signature;
				$this->date_uploaded = $kycfile_item->update_date;
				$this->status = $kycfile_item->status;
				$this->gateway = $kycfile_item->gateway;
				$this->gateway_user_id = $kycfile_item->gateway_user_id;
				$this->gateway_organization_id = $kycfile_item->gateway_organization_id;
				// TODO : affecter gateway_id en fonction de user_id ou orga_id ?
				$this->metadata = $kycfile_item->metadata;
				$this->url = $kycfile_item->url;
				$this->is_api_file = TRUE;
			}
		}
	}

	/**
	 * Enregistre les modifications sur l'élément
	 * @return int
	 */
	public function save() {
		if ($this->is_api_file ){
			// TODO : gérer les retours d'erreur
			$buffer = WDGWPREST_Entity_FileKYC::update( $this );
		} else {
			// TODO : à supprimer après transfert de tous les kyc sur l'API
			global $wpdb;
			$table_name = $wpdb->prefix . WDGKYCFile::$table_name;
			$result = $wpdb->update($table_name, array(
					'type' => $this->type,
					'orga_id' => $this->orga_id,
					'user_id' => $this->user_id,
					'file_name' => $this->file_name,
					'status' => $this->status,
					'date_uploaded' => $this->date_uploaded,
					'gateway' => $this->gateway,
					'gateway_id' => $this->gateway_id,
				), array(
					'id' => $this->id
				));
			if ($result !== FALSE) {
				return $this->id;
			}
		}
	}

	/**
	 * Retourne le chemin vers le fichier pour un téléchargement
	 * @param boolean $with_id
	 * @return string
	 */
	public function get_public_filepath($with_id = TRUE) {
		$buffer = '';
		if ($this->is_api_file ){
			// url sur l'api : 
			//home_url( '/wp-content/plugins/wdgrestapi/' .$this->get_path(). '/' .$this->loaded_data->file_name );
			$buffer = $this->url;
		} else {
			// TODO : à supprimer après transfert de tous les kyc sur l'API
			if ($with_id) {
				$buffer = admin_url('admin-post.php?action=view_kyc_file&id_kyc=' .$this->id . '&api_kyc=0');
			} else {
				$buffer = site_url('/wp-content/plugins/appthemer-crowdfunding/includes/kyc/' .$this->file_name);
			}
		}
		return $buffer;
	}

	/**
	 * Retourne le tableau de bytes à envoyer à Lemonway
	 * @return mixed
	 */
	public function get_byte_array() {
		if ($this->is_api_file ){
			if (file_exists($this->url)) {
                $byte_array = file_get_contents($this->url);

                return $byte_array;
            }
		} else {
            // TODO : à supprimer après transfert de tous les kyc sur l'API
            if (file_exists(__DIR__ . '/../kyc/' . $this->file_name)) {
                $byte_array = file_get_contents(__DIR__ . '/../kyc/' . $this->file_name);

                return $byte_array;
            }
        }

		return FALSE;
	}

	/**
	 * Retourne le hash md5 du tableau de bytes du fichier
	 * @return mixed
	 */
	public function get_byte_array_md5() {
		if ($this->is_api_file ){
			return $this->file_signature;
		} else {
            $byte_array = $this->get_byte_array();
            if (!empty($byte_array)) {
                return md5($byte_array);
            }
        }

		return FALSE;
	}

	/**
	 * Retourne la date d'upload au format "Y-m-d"
	 * @return string
	 */
	public function get_date_uploaded() {
		return $this->date_uploaded;
	}

	/**
	 * Retourne le content-type lié au header http
	 * @return string
	 */
	public function get_content_type() {
		$file_name_exploded = explode( '.', $this->file_name );
		$extension = end( $file_name_exploded );
		switch ( $extension ) {
			case 'pdf':
				return 'application/' . $extension;
				break;
			case 'jpg':
			case 'jpeg':
				return 'image/jpeg';
				break;
			case 'bmp':
			case 'gif':
			case 'tif':
			case 'tiff':
			case 'png':
				return 'image/' . $extension;
				break;
		}
	}

	/**
	 * Retourne le chemin vers le fichier pour suppression
	 * @return string
	 */
	private function get_filepath() {
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
		if ( file_exists( $file_path)) {
			$is_deleted = unlink( $file_path );
		};
		if ( $is_deleted ) {
			// on le supprime en bdd
			global $wpdb;
			$table_name = $wpdb->prefix . WDGKYCFile::$table_name;
			$result = $wpdb->delete($table_name, array(
					'id' => $this->id,
					'type' => $this->type,
					'orga_id' => $this->orga_id,
					'user_id' => $this->user_id,
					'file_name' => $this->file_name,
					'status' => $this->status,
					'date_uploaded' => $this->date_uploaded,
					'gateway' => $this->gateway,
					'gateway_id' => $this->gateway_id,
				));
		}

		return $result;
	}

	/*******************************************************************************
	 * REQUETES STATIQUES
	 ******************************************************************************/
	/**
	 * Retourne la liste de tous les types possibles
	 *
	 * @return array of string
	 */
	public static function get_list_kyc_type() {
		return array( 
			WDGKYCFile::$type_bank,
			WDGKYCFile::$type_id,
			WDGKYCFile::$type_id_back,
			WDGKYCFile::$type_id_2,
			WDGKYCFile::$type_id_2_back,
			WDGKYCFile::$type_kbis,
			WDGKYCFile::$type_status,
			WDGKYCFile::$type_capital_allocation,
			WDGKYCFile::$type_idbis,
			WDGKYCFile::$type_idbis_2,
			WDGKYCFile::$type_id_3,
			WDGKYCFile::$type_idbis_3,
			WDGKYCFile::$type_passport,
			WDGKYCFile::$type_tax,
			WDGKYCFile::$type_welfare,
			WDGKYCFile::$type_family,
			WDGKYCFile::$type_birth,
			WDGKYCFile::$type_driving,
			WDGKYCFile::$type_person2_doc1,
			WDGKYCFile::$type_person2_doc2,
			WDGKYCFile::$type_person3_doc1,
			WDGKYCFile::$type_person3_doc2,
			WDGKYCFile::$type_person4_doc1,
			WDGKYCFile::$type_person4_doc2
		);
	}
	/**
	 * Retourne un type en "vrai langage" en prenant en compte son type, si c'est une orga ou pas
	 * TODO : à faire évoluer avec le doc index pour préviser verso ou pas sur certains types
	 *
	 * @return string
	 */
	public static function convert_type_id_to_str ( $doc_type, $is_orga = FALSE, $doc_index = 1) {
		// TODO : quand on aura changé les types dans Mon Compte, il faudra les prendre en compte ici, et prendre en compte le doc_index pour le verso
		switch ( $doc_type ) {
			case WDGKYCFile::$type_id:
				$document_type_str = __( 'lemonway.document.type.ID', 'yproject' );
				break;
			case WDGKYCFile::$type_id_back:
				$document_type_str = __( 'lemonway.document.type.MAIN_ID_BACK', 'yproject' );
				break;
			case WDGKYCFile::$type_id_2:
			case WDGKYCFile::$type_idbis:
				if ( $is_orga ) {
					// si orga
					$document_type_str = __( 'lemonway.document.type.ID_SECOND_PERSON', 'yproject' );
				}else{
					// si user : 
					$document_type_str = __( 'lemonway.document.type.SECOND_ID', 'yproject' );
				}
				break;
			case WDGKYCFile::$type_id_2_back:
				$document_type_str = __( 'lemonway.document.type.SECOND_ID_BACK', 'yproject' );
				break;
			case WDGKYCFile::$type_passport:
				$document_type_str = __( 'lemonway.document.type.PASSPORT', 'yproject' );
				break;
			case WDGKYCFile::$type_tax:
				$document_type_str = __( 'lemonway.document.type.TAX', 'yproject' );
				break;
			case WDGKYCFile::$type_welfare:
				$document_type_str = __( 'lemonway.document.type.WELFARE', 'yproject' );
				break;
			case WDGKYCFile::$type_family:
				$document_type_str = __( 'lemonway.document.type.FAMILY', 'yproject' );
				break;
			case WDGKYCFile::$type_birth:
				$document_type_str = __( 'lemonway.document.type.BIRTH', 'yproject' );
				break;
			case WDGKYCFile::$type_driving:
				$document_type_str = __( 'lemonway.document.type.DRIVING_LICENSE', 'yproject' );
				break;
			case WDGKYCFile::$type_kbis:
				$document_type_str = __( 'lemonway.document.type.KBIS', 'yproject' );
				break;
			case WDGKYCFile::$type_status:
				$document_type_str = __( 'lemonway.document.type.ORGA_STATUS', 'yproject' );
				break;
			case WDGKYCFile::$type_capital_allocation:
				$document_type_str = __( 'lemonway.document.type.CAPITAL_ALLOCATION', 'yproject' );
				break;
			case WDGKYCFile::$type_id_2:
			case WDGKYCFile::$type_person2_doc1:
				$document_type_str = __( 'lemonway.document.type.ID_SECOND_PERSON', 'yproject' );
				break;
			case WDGKYCFile::$type_idbis_2:
			case WDGKYCFile::$type_person2_doc2:
				$document_type_str = __( 'lemonway.document.type.ID_SECOND_PERSON_2', 'yproject' );
				break;
			case WDGKYCFile::$type_id_3:
			case WDGKYCFile::$type_person3_doc1:
				$document_type_str = __( 'lemonway.document.type.ID_THIRD_PERSON', 'yproject' );
				break;
			case WDGKYCFile::$type_idbis_3:
			case WDGKYCFile::$type_person3_doc2:
				$document_type_str = __( 'lemonway.document.type.ID_THIRD_PERSON_2', 'yproject' );
				break;
			case WDGKYCFile::$type_person4_doc1:
				$document_type_str = __( 'lemonway.document.type.ID_FOURTH_PERSON', 'yproject' );
				break;
			case WDGKYCFile::$type_person4_doc2:
				$document_type_str = __( 'lemonway.document.type.ID_FOURTH_PERSON_2', 'yproject' );
				break;
		}
		
		// si on n'a pas trouvé, on reste vague (n'arrive pas normalement)
		if (!isset($document_type_str)) {
			$document_type_str = __( 'common.OTHER', 'yproject' );
		}
		return $document_type_str;
	}
	/**
	 * Renvoie un WDGKYCFile à partir de son gateway_id
	 *
	 * @param int $gateway_id
	 * @return WDGKYCFile
	 */
	public static function get_by_gateway_id( $gateway_id ) {
		$buffer = FALSE;
		// TODO : à supprimer après transfert de tous les kyc sur l'API
		// On cherche d'abord sur le site
		global $wpdb;
		$table_name = $wpdb->prefix . WDGKYCFile::$table_name;
		$query = 'SELECT * FROM ' .$table_name. ' WHERE gateway_id=' .$gateway_id;
		$kycfile_item = $wpdb->get_row( $query );

        if (!isset($kycfile_item)) {
			$buffer = new WDGKYCFile( $kycfile_item->id, FALSE );
        }else{	
			// si on ne trouve pas on cherche sur l'API
			$kycfile_item = WDGWPREST_Entity_FileKYC::get_by_gateway_id( $gateway_id );	
			if( isset($kycfile_item)) {
				// TODO : cette façon de faire (identique à WDGROIDeclaration::get_by_payment_token provque un appel inutile à l'API, à améliorer)
				$buffer = new WDGKYCFile( $kycfile_item->id, TRUE );
			} 
		}
		

		return $buffer;

	}

	/**
	 * Transfert d'un fichier présent sur le site vers l'API
	 *
	 * @param WDGKYCFile $file
	 * @param string $type_owner (user ou organization)
	 * @param string $new_type (peut être défini via le select)
	 * @return mixed
	 */
	public static function transfer_file_to_api(WDGKYCFile $file, $type_owner, $new_type = FALSE) {
		// on met à jour les  types si on a d'anciens types
		$doc_type = $file->type;
		$doc_index = 1;
		if ($type_owner === 'organization') {
			if ( $doc_type == self::$type_idbis){
				$doc_type = self::$type_person2_doc1;
			}
			if ( $doc_type == self::$type_idbis_2){
				$doc_type = self::$type_person2_doc2;
			}
			if ( $doc_type == self::$type_id_3){
				$doc_type = self::$type_person3_doc1;
			}
			if ( $doc_type == self::$type_idbis_3){
				$doc_type = self::$type_person3_doc2;
			}
			if ( $doc_type == self::$type_id_2){
				$doc_type = self::$type_person2_doc1;
			}
		} else {
			if ( $doc_type == self::$type_id_back){
				$doc_type = self::$type_id;
				$doc_index = 2;
			}
			if ( $doc_type == self::$type_id_2){
				$doc_type = self::$type_passport;
			}
			if ( $doc_type == self::$type_id_2_back){
				$doc_type = self::$type_passport;
				$doc_index = 2;
			}
		}

		if ($new_type != FALSE){
			// Si cette variable est passée en paramètre,  c'est qu'elle a été définie via le select, c'est donc un "nouveau type"
			// mais on fait d'abord l'algorythme précédent pour définir le doc_index
			$doc_type = $new_type;
		}
		
		$WDGUser = new WDGUser( $file->user_id );
		$user_id = $WDGUser->get_api_id() ;

		$WDGOrganization = new WDGOrganization( $file->organization_id );
		$organization_id = $WDGOrganization->get_api_id();

		$file_name = $file->file_name;
		$file_name_exploded = explode('.', $file_name);
		$ext = $file_name_exploded[ count($file_name_exploded) - 1 ];
		$byte_array = $file->get_byte_array();
		// Envoi du fichier à l'API
		// TODO : gérer les retours d'erreur
		$create_feedback = WDGWPREST_Entity_FileKYC::create($user_id, $organization_id, $doc_type, $doc_index, $ext, base64_encode($byte_array));

		// supprimer le fichier actuel sur le site
		$file->delete();
		return $create_feedback;

	}
	// TODO : à supprimer après transfert de tous les kyc sur l'API ?
	/**
	 * Ajoute un nouveau fichier
	 *
	 * @param string $doc_type
	 * @param int $id_owner
	 * @param string $type_owner
	 * @param string $file_uploaded_data
	 * @param int $doc_index
	 * @return mixed
	 */
	public static function add_file($doc_type, $id_owner, $type_owner, $file_uploaded_data, $doc_index = '', $new_type = FALSE) {
		// Mapping : https://docs.google.com/spreadsheets/d/19i6O3s7f2-MHHXiFuhtG6v0UrBn6KdmzReD0URe_QAI/edit?usp=sharing

		if (!empty($id_owner)) {
			// on défini le doc_index à 1 par défaut s'il n'est pas défini
			if( $doc_index == '' ){
				$doc_index = 1;
			}
			// on met à jour les  types si on a d'anciens types
			if ($type_owner === 'organization') {
				$user_id = 0;
				$WDGOrganization = new WDGOrganization( $id_owner );
				$organization_id = $WDGOrganization->get_api_id();
				if ( $doc_type == self::$type_idbis){
					$doc_type = self::$type_person2_doc1;
				}
				if ( $doc_type == self::$type_idbis_2){
					$doc_type = self::$type_person2_doc2;
				}
				if ( $doc_type == self::$type_id_3){
					$doc_type = self::$type_person3_doc1;
				}
				if ( $doc_type == self::$type_idbis_3){
					$doc_type = self::$type_person3_doc2;
				}
				if ( $doc_type == self::$type_id_2){
					$doc_type = self::$type_person2_doc1;
				}
			} else {
				$organization_id = 0;
				$WDGUser = new WDGUser( $id_owner );
				$user_id = $WDGUser->get_api_id() ;
				if ( $doc_type == self::$type_id_back){
					$doc_type = self::$type_id;
					$doc_index = 2;
				}
				if ( $doc_type == self::$type_id_2){
					$doc_type = self::$type_passport;
				}
				if ( $doc_type == self::$type_id_2_back){
					$doc_type = self::$type_passport;
					$doc_index = 2;
				}
			}
			if ($new_type != FALSE){
				// Si cette variable est passée en paramètre,  c'est qu'elle a été définie via le select, c'est donc un "nouveau type"
				// mais on fait d'abord l'algorythme précédent pour définir le doc_index
				$doc_type = $new_type;
			}
			// on commence par récupérer les éventuels kyc présents dans l'API
			//*******************
			// On commence par changer le statut de l'existant en "supprimé"

			// Récupération de l'identifiant API du fichier existant
			$file_list = self::get_list_by_owner_id($id_owner, $type_owner, $doc_type);
			// Parcourir la liste, vérifier le type et l'index de documents, et si le statut n'est pas déjà "removed"
			foreach ($file_list as $file_item) {
				if ($file_item->doc_type == $doc_type && $file_item->doc_index == $doc_index && $file_item->status != 'removed') {
					// Envoi de la demande de suppression à l'API
					WDGWPREST_Entity_FileKYC::update_status($file_item->id, 'removed');
				}
			}
			//*******************

			//*******************
			// On envoie le nouveau document

			// Récupération du fichier
			$file_name = $file_uploaded_data[ 'name' ];
			$file_name_exploded = explode('.', $file_name);
			$ext = $file_name_exploded[ count($file_name_exploded) - 1 ];
			$byte_array = file_get_contents($file_uploaded_data[ 'tmp_name' ]);

			// Envoi du fichier à l'API
			// TODO : gérer les retours d'erreur
			$create_feedback = WDGWPREST_Entity_FileKYC::create($user_id, $organization_id, $doc_type, $doc_index, $ext, base64_encode($byte_array));
			return $create_feedback;
		
		}
		return 'EMPTY';

	}

	/**
	 * Liste des fichiers par possesseur
	 *
	 * @param int $id_owner
	 * @param string $type_owner
	 * @param string $type
	 * @return array of WDGKYCFile
	 */
	public static function get_list_by_owner_id($id_owner, $type_owner = 'organization', $type = '') {
		$buffer = array();

		if ( !empty( $id_owner ) ) {
			// on commence par récupérer les éventuels kyc présents dans l'API
			// on met à jour les types si on a d'anciens types
			$index_api = 1;
			$type_api = $type;
			if ($type_owner === 'organization') {
				$user_id = 0;
				$WDGOrganization = new WDGOrganization( $id_owner );
				$organization_id = $WDGOrganization->get_api_id();
				if ( $type == self::$type_idbis){
					$type_api = self::$type_person2_doc1;
				}
				if ( $type == self::$type_idbis_2){
					$type_api = self::$type_person2_doc2;
				}
				if ( $type == self::$type_id_3){
					$type_api = self::$type_person3_doc1;
				}
				if ( $type == self::$type_idbis_3){
					$type_api = self::$type_person3_doc2;
				}
				if ( $type == self::$type_id_2){
					$type_api = self::$type_person2_doc1;
				}
			} else {
				$organization_id = 0;
				$WDGUser = new WDGUser( $id_owner );
				$user_id = $WDGUser->get_api_id() ;
				if ( $type == self::$type_id_back){
					$type_api = self::$type_id;
					$index_api = 2;
				}
				if ( $type == self::$type_id_2){
					$type_api = self::$type_passport;
				}
				if ( $type == self::$type_id_2_back){
					$type_api = self::$type_passport;
					$index_api = 2;
				}
			}
			$file_api_list = WDGWPREST_Entity_FileKYC::get_list_by_entity_id( $type_owner, $user_id, $organization_id );
			foreach ( $file_api_list as $kycfile_item ) {
				// Parcourir la liste, vérifier le type s'il est précisé
				if ( $type == '' || ($kycfile_item->doc_type == $type_api  && $kycfile_item->doc_index == $index_api )) {
					$KYCfile = new WDGKYCFile( $kycfile_item->id, TRUE );
					array_push($buffer, $KYCfile);
				}
			}

			// puis on ajoute les fichiers trouvés sur le site
			// TODO : à supprimer après transfert de tous les kyc sur l'API
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
				$KYCfile = new WDGKYCFile( $kycfile_item->id, FALSE );
				array_push($buffer, $KYCfile);
			}
		}

		return $buffer;
	}
}
