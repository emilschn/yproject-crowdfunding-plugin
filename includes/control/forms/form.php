<?php
class WDG_Form {
	
	private $formID;
	protected $fields;
	
	public function __construct( $formid ) {
		$this->formID = $formid;
	}
	
	public function getFormID() {
		return $this->formID;
	}
	
	protected function initFields() {
		
		$this->fields = array();
		
	}
	
	protected function addField( $type, $name, $label, $group = '0', $value = FALSE, $decription = FALSE, $options = FALSE ) {
		
		if ( !isset( $this->fields[ $group ] ) ) {
			$this->fields[ $group ] = array();
		}
		
		$field = array(
			'type'			=> $type,
			'name'			=> $name,
			'label'			=> $label,
			'value'			=> $value,
			'description'	=> $decription,
			'options'		=> $options
		);
		
		array_push( $this->fields[ $group ], $field );
		
	}
	
	public function getFields( $group = '0' ) {
		
		$buffer = FALSE;
		
		if ( isset( $this->fields[ $group ] ) ) {
			$buffer = $this->fields[ $group ];
		}
		
		return $buffer;
		
	}
	
	/**
	 * Vérifications standardes sur les champs
	 */
	protected function postForm() {
		
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
	
	/**
	 * Récupération de la note qui a été donnée pour un élément particulier
	 * @param string $name
	 * @return int
	 */
	public function getInputRate( $name, $max = 10 ) {
		$buffer = 0;
		
		// 15 est un nombre arbitraire pour être sûr de parcourir toutes les notes
		// (de toutes façons, on est censé atteindre le break avant)
		for ( $i = 1; $i < $max; $i++ ) {
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
