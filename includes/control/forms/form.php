<?php
class WDG_Form {
	
	private $formID;
	protected $fields;
	private $errors;
	
	public function __construct( $formid ) {
		$this->formID = $formid;
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
		
		$field = array(
			'type'			=> $type,
			'name'			=> $name,
			'label'			=> $label,
			'value'			=> $value,
			'description'	=> $description,
			'options'		=> $options
		);
		
		array_push( $this->fields[ $group ], $field );
		
	}
	
	protected function getParamByFileField( $wallet_id, $document_type, $date_upload ) {
		$buffer = array(
			'date_upload'					=> $date_upload,
			'message_instead_of_field'		=> FALSE,
			'display_refused_alert'			=> FALSE
		);
		
		$message_document_validated = __( "Document valid&eacute; par notre prestataire", 'yproject' );
		$message_document_waiting = __( "Document en cours de validation par notre prestataire", 'yproject' );
		
		$lw_document_id = new LemonwayDocument( $wallet_id, $document_type );
		if ( $lw_document_id->get_status() == LemonwayDocument::$document_status_accepted ) {
			$buffer[ 'message_instead_of_field' ] = $message_document_validated;
		} else if ( $lw_document_id->get_status() == LemonwayDocument::$document_status_waiting ) {
			$buffer[ 'message_instead_of_field' ] = $message_document_waiting;
		} else if ( $lw_document_id->get_status() > 2 ) {
			$buffer[ 'display_refused_alert' ] = TRUE;
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
	
	/**
	 * Vérifications standardes sur les champs
	 */
	protected function postForm() {
		$this->errors = array();
		
		$nb_fields = count( $this->fields );
		for( $i = 0; $i < $nb_fields; $i++ ) {
			$this_field = $this->fields[ $i ];
			switch ( $this_field[ 'type' ] ) {
				
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
	public function getInputTextMoney( $name ) {
		$buffer = '';
		$input_result = filter_input( INPUT_POST, $name );

		//Supprime les espaces et arrondit la valeur du capital à l'unité
		if ( !empty( $input_result ) ) {
			if ( preg_match('#\s#', $input_result) ) {
				$input_result = str_replace( ' ', '', $input_result );
			}
			$input_result = round($input_result );
			$buffer = stripslashes( htmlentities( $input_result, ENT_QUOTES | ENT_HTML401 ) );
		} else {
			$buffer = stripslashes( htmlentities( $input_result, ENT_QUOTES | ENT_HTML401 ) );
		}
		return $buffer;
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
	
}
