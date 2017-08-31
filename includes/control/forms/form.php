<?php
class WDG_Form {
	
	protected $fields;
	
	public function __construct() {
	}
	
	protected function initFields() {
		
		$this->fields = array();
		
	}
	
	protected function addField( $type, $name, $label, $group = '0', $decription = FALSE, $options = FALSE ) {
		
		if ( !isset( $this->fields[ $group ] ) ) {
			$this->fields[ $group ] = array();
		}
		
		$field = array(
			'type'			=> $type,
			'name'			=> $name,
			'label'			=> $label,
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
	
}
