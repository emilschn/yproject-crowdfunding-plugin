<?php
class WDG_Form {
	
	private $formID;
	protected $fields;
	private $errors;
	private $nonce;
	private $create_nonce;
	private $hidden_field_nonce;
	
	public function __construct( $formid, $create_nonce = FALSE ) {
		$this->formID = $formid;
		$this->create_nonce = $create_nonce;
		if ($this->create_nonce) {
			$this->hidden_field_nonce = 'hidden_field_nonce';
			$this->nonce = wp_nonce_field( $this->formID, $this->hidden_field_nonce, true, true );
		}		
	}
	
	public function getFormID() {
		return $this->formID;
	}
	
	protected function initFields() {
		$this->fields = array();
	}
	
	public function reinitFields() {
	}
	
	/**
	 * Ajoute un champ au formulaire
	 * @param string $type (text, textarea, text-money, checkboxes, date, hidden, radio, rate, select)
	 * @param string $name
	 * @param string $label
	 * @param string $group
	 * @param string $value
	 * @param string $description
	 * @param array $options
	 */
	protected function addField( $type, $name, $label, $group = '0', $value = FALSE, $description = FALSE, $options = FALSE ) {
		
		if ( !isset( $this->fields[ $group ] ) ) {
			$this->fields[ $group ] = array();
		}
		$warning = FALSE;
		if ( !empty( $options[ 'warning' ] ) ) {
			$warning = $options[ 'warning' ];
		}
		$admin_theme = FALSE;
		if ( !empty( $options[ 'admin_theme' ] ) ) {
			$admin_theme = $options[ 'admin_theme' ];
		}
		
		$field = array(
			'type'			=> $type,
			'name'			=> $name,
			'label'			=> $label,
			'value'			=> $value,
			'description'	=> $description,
			'warning'		=> $warning,
			'admin_theme'	=> $admin_theme,
			'options'		=> $options
		);
		
		array_push( $this->fields[ $group ], $field );
		
	}
	
	protected function getParamByFileField( $wallet_id, $document_type, $date_upload, $type, $isOrga = FALSE, $api_type = FALSE, $kycfile_id = FALSE, $is_api_file = FALSE, $is_authentified = FALSE, $is_file_sent = TRUE ) {
		$secondary = FALSE;
		if ( $type == WDGKYCFile::$type_id_back || $type == WDGKYCFile::$type_id_2_back){
			$secondary = TRUE;
		}

		$buffer = array(
			'date_upload'					=> $date_upload,
			'display_upload'				=> !$is_authentified || empty( $document_type ),
			'message_instead_of_field'		=> FALSE,
			'display_refused_alert'			=> FALSE,
			'secondary'						=> $secondary
		);
		
		$message_document_validated = __( 'forms.file.DOCUMENT_ACCEPTED_BY_PROVIDER', 'yproject' );
		$message_document_waiting = __( 'forms.file.DOCUMENT_UNDER_VALIDATION', 'yproject' );
		
		// Récupération du statut en provenance de LW si nécessaire
		if ( isset( $document_type ) ) {
			// TODO : vérifier s'il faut faire correspondre les anciens et nouveaux documents types
			$lw_document = new LemonwayDocument( $wallet_id, $document_type );
			if ( $lw_document->get_status() == LemonwayDocument::$document_status_accepted && !empty( $date_upload ) ) {
				$buffer[ 'message_instead_of_field' ] = $message_document_validated;
			} else if ( !$is_file_sent || $lw_document->get_status() === LemonwayDocument::$document_status_waiting_verification || $lw_document->get_status() == LemonwayDocument::$document_status_waiting ) {
				$buffer[ 'message_instead_of_field' ] = $message_document_waiting;
				if ( !$is_file_sent ) {
					$buffer[ 'message_instead_of_field' ] .= '.';
				}
			} else if ( $lw_document->get_status() > 2 && !empty( $date_upload ) ) {
				$buffer[ 'display_refused_alert' ] = TRUE;
				$lw_error_str = $lw_document->get_error_str();
				if ( !empty( $lw_error_str ) ) {
					$buffer[ 'display_refused_alert' ] = $lw_error_str;
				}
			}
		}
		

		// on modifie la liste des nouveaux types possibles en fonction du type du document
		// attention WDGKYCFile::$type_id_2 correspond à l'ancien type de 2è pièce d'identité pour un utilisateur, mais possiblement aussi à l'ancien type de première pièce d'identité de la deuxième personne pour une orga
		if ( $type == WDGKYCFile::$type_id || $type == WDGKYCFile::$type_id_back || $type == WDGKYCFile::$type_passport
			|| ($isOrga && $type == WDGKYCFile::$type_idbis) ){
			$type_list = array( 
				WDGKYCFile::$type_id => __( "lemonway.document.type.CARD_ID", 'yproject' ), 
				WDGKYCFile::$type_passport => __( "lemonway.document.type.PASSPORT", 'yproject' ), 
			);
		} elseif ( (!$isOrga && $type == WDGKYCFile::$type_id_2) || (!$isOrga && $type == WDGKYCFile::$type_id_2_back)
			|| $type == WDGKYCFile::$type_tax || $type == WDGKYCFile::$type_welfare || $type == WDGKYCFile::$type_family || $type == WDGKYCFile::$type_birth || $type == WDGKYCFile::$type_driving){
			$type_list = array(
				WDGKYCFile::$type_id => __( "lemonway.document.type.CARD_ID", 'yproject' ),
				WDGKYCFile::$type_passport => __( "lemonway.document.type.PASSPORT", 'yproject' ),
				WDGKYCFile::$type_tax => __( "lemonway.document.type.TAX", 'yproject' ),
				WDGKYCFile::$type_family => __( "lemonway.document.type.FAMILY", 'yproject' ),
				WDGKYCFile::$type_birth => __( "lemonway.document.type.BIRTH", 'yproject' ),
				WDGKYCFile::$type_driving  => __( "lemonway.document.type.DRIVING_LICENSE", 'yproject' )
			);
			// Si c'est un type du passé et que c'était une carte vitale, on la rajoute à la liste (sinon, on ne propose plus)
			if ( $type == WDGKYCFile::$type_welfare ) {
				$type_list[ WDGKYCFile::$type_welfare ] = __( "lemonway.document.type.WELFARE", 'yproject' );
			}
		} 
		// ne mettre un select que si le document n'est pas validé,  et le compte non-authentifié
		// sinon, on affichera juste l'api_type en texte (si on l'a)
		if ( isset( $document_type ) && $lw_document->get_status() != LemonwayDocument::$document_status_accepted && $is_authentified == FALSE ){
			if( $type_list && count( $type_list ) > 1 ){
				$buffer[ 'list_select' ] = $type_list;
			}
		}
		if( $api_type != FALSE ){
			$buffer[ 'api_type' ] = $api_type;
            if ( isset( $document_type ) ) {
                $buffer[ 'string_type' ] = WDGKYCFile::convert_type_id_to_str($api_type, $isOrga);
            }
		}
		if( $kycfile_id != FALSE ){
			$buffer[ 'kycfile_id' ] = $kycfile_id;
		}
		if( $is_api_file != FALSE ){
			$buffer[ 'is_api_file' ] = $is_api_file;
		}
		
		return $buffer;
	}
	
	public function getFields( $group = '0' ) {
		
		$buffer = FALSE;
		
		if ( isset( $this->fields[ $group ] ) ) {
			$buffer = $this->fields[ $group ];
		}
		
		return $buffer;
		
	}
	
	public function isPosted() {
		$input_action = $this->getInputText( 'action' );
		return ( !empty( $input_action ) && $input_action == $this->formID );
	}

	public function getNonce() {

		return $this->nonce;
	}
	
	/**
	 * Vérifications standardes sur les champs
	 */
	protected function postForm() {
		$this->errors = array();
		
		if ($this->create_nonce) {
			if ( ! wp_verify_nonce( $_POST[$this->hidden_field_nonce], $this->formID ) ) {
				 // traitement à effectuer si le nonce n'est pas valide
				 $this->addPostError(
					 'nonce-not-valid',
					 __( "Cette action n'est pas autoris&eacute;e.", 'yproject' ),
					 'general'
				 );
			}
		}	

		
        if (is_array($this->fields)) {
            $nb_fields = count($this->fields);
			for ($i = 0; $i < $nb_fields; $i++) {
				if (empty($this->fields[ $i ])) {
					continue;
				}
				$this_field = $this->fields[ $i ];
				switch ($this_field[ 'type' ]) {				
				case 'text':
				case 'textarea':
					break;				
				case 'text-money':
					break;				
				case 'rate':
					break;				
				case 'radio':
					break;				
				case 'checkboxes':
					break;				
				}
			}
		}		
	}
	
	public function getPostErrors() {
		return $this->errors;
	}
	
	public function hasErrors() {
		return ( !empty( $this->errors ) );
	}
	
	protected function addPostError( $code, $text, $element ) {
		$error = array(
			'code'		=> $code,
			'text'		=> $text,
			'element'	=> $element
		);
		array_push( $this->errors, $error );
	}
	
	/**
	 * Récupération de la note qui a été donnée pour un élément particulier
	 * @param string $name
	 * @return int
	 */
	public function getInputRate( $name, $max = 10 ) {
		$buffer = 0;
		
		// 15 est un nombre arbitraire pour être sûr de parcourir toutes les notes
		// (de toutes façons, on est censé atteindre le break avant)
		for ( $i = 1; $i <= $max; $i++ ) {
			$input_rate = filter_input( INPUT_POST, $name. '-' . $i );
			if ( !empty( $input_rate ) && $input_rate == $i ) {
				$buffer = $i;
				
			} else {
				break;
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Récupération de la valeur d'un texte
	 * @param string $name
	 * @return string
	 */
	public function getInputText( $name ) {
		$buffer = '';
		$input_result = filter_input( INPUT_POST, $name );

		if ( !empty( $input_result ) ) {
			$buffer = stripslashes( htmlentities( $input_result, ENT_QUOTES | ENT_HTML401 ) );
		}
		return $buffer;
	}

	/**
	 * Récupération de la valeur d'une somme d'argent
	 * @param string $name
	 * @return string
	 */
	public function getInputTextMoney( $name, $round_value = TRUE ) {
		$buffer = '';
		$input_result = trim( filter_input( INPUT_POST, $name ) );

		//Supprime les espaces et arrondit la valeur du capital à l'unité
		if ( !is_numeric( $input_result ) ) {
			if ( preg_match('#\s#', $input_result) ) {
				$input_result = str_replace( ' ', '', $input_result );
			}
			$input_result = str_replace( ',', '.', $input_result );
			if ( is_numeric( $input_result ) && $round_value ) {
				$input_result = round( $input_result );
			}
			$buffer = stripslashes( htmlentities( $input_result, ENT_QUOTES | ENT_HTML401 ) );
		} else {
			$buffer = stripslashes( htmlentities( $input_result, ENT_QUOTES | ENT_HTML401 ) );
		}
		return $buffer;
	}
	
	/**
	 * Applique le format nombre à un champ de formulaire
	 * @param string $name
	 * @return string
	 */
	public static function formatInputTextNumber( $name, $do_round = FALSE ) {
		$buffer = '';
		$input_result = filter_input( INPUT_POST, $name );

		//Supprime les espaces et arrondit la valeur du capital à l'unité
		if ( !empty( $input_result ) ) {
			$buffer = self::clean_input_number( $input_result );
			if ( $do_round ) {
				$buffer = round( $buffer );
			}
		}
		return $buffer;
	}

	public static function clean_input_number( $input ) {
		if ( preg_match('#\s#', $input) ) {
			$input = str_replace( ' ', '', $input );
		}
		$input = str_replace( ',', '.', $input );
		return stripslashes( htmlentities( $input, ENT_QUOTES | ENT_HTML401 ) );
	}
	
	/**
	 * Récupération d'un booléen
	 * @param string $name
	 * @param boolean $force
	 * @return boolean or int
	 */
	public function getInputBoolean( $name, $force = false ) {
		$buffer = false;
		$input_result = filter_input( INPUT_POST, $name );
		
		if ( !empty( $input_result ) && $input_result == '1' ) {
			$buffer = true;
			
		} else {
			if ( $input_result !== '0' && $force ) {
				$buffer = -1;
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Récupération d'une checkbox
	 * @param string $name
	 * @return boolean
	 */
	public function getInputChecked( $name ) {
		$buffer = false;
		$input_result = filter_input( INPUT_POST, $name );
		
		if ( !empty( $input_result ) && $input_result == $name ) {
			$buffer = true;
			
		}
		
		return $buffer;
	}
	
	/**
	 * Récupération d'un fichier
	 * @param string $name
	 * @return boolean or uploaded_data
	 */
	public function getInputFile( $name ) {
		$buffer = false;
		
		if ( isset( $_FILES[ $name ][ 'tmp_name' ] ) && !empty( $_FILES[ $name ][ 'tmp_name' ] ) ) {
			$buffer = $_FILES[ $name ];
		}
		
		return $buffer;
	}
	
}
