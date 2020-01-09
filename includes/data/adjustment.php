<?php
/**
 * Classe de gestion des ajustements
 */
class WDGAdjustment {
	protected static $collection_by_id;
	
	public static $type_turnover_difference = 'turnover_difference';
	public static $type_turnover_difference_remainders = 'turnover_difference_remainders';
	public static $type_fixed_amount = 'fixed_amount';
	public static $type_previous_adjustment_correction = 'previous_adjustment_correction';
	public static $type_royalties_remainders = 'royalties_remainders';
	
	public static $status_upcoming = 'upcoming';
	public static $status_done = 'done';
	
	public static $types_str_by_id = array(
		'turnover_difference'				=> "Diff&eacute;rence de CA",
		'turnover_difference_remainders'	=> "Restes de pr&eacute;c&eacute;dent ajustement",
		'fixed_amount'						=> "Montant fix&eacute;",
		'previous_adjustment_correction'	=> "Correction d'ajustement pr&eacute;c&eacute;dent",
		'royalties_remainders'				=> "Reliquats de royalties de versements pr&eacute;c&eacute;dents"
	);
	
	public $id;
	public $id_api_campaign;
	public $id_declaration;
	public $date_created;
	public $type;
	public $turnover_difference;
	public $amount;
	public $message_organization;
	public $message_investors;
	
	public $documents;
	public $declarations_checked;
	
	/**
	 * @var WDGROIDeclaration 
	 */
	public $declaration;
	public $status;

	private $api_data_declarations;
	private $api_data_files;
	
	
	public function __construct( $adjustment_id = FALSE, $data = FALSE ) {
		if ( !empty( $adjustment_id ) ) {
			// Si déjà chargé précédemment
			if ( isset( self::$collection_by_id[ $adjustment_id ] ) || $data !== FALSE ) {
				$collection_item = isset( self::$collection_by_id[ $adjustment_id ] ) ? self::$collection_by_id[ $adjustment_id ] : $data;
				$this->id = $collection_item->id;
				$this->id_api_campaign = $collection_item->id_project;
				$this->id_declaration = $collection_item->id_declaration;
				$this->date_created = $collection_item->date_created;
				$this->type = $collection_item->type;
				$this->turnover_difference = $collection_item->turnover_difference;
				$this->amount = $collection_item->amount;
				$this->message_organization = $collection_item->message_organization;
				$this->message_investors = $collection_item->message_investors;
				
				$this->api_data_declarations = isset( self::$collection_by_id[ $adjustment_id ] ) ? $collection_item->api_data_declarations : $collection_item->declarations;
				$this->api_data_files = isset( self::$collection_by_id[ $adjustment_id ] ) ? $collection_item->api_data_files : $collection_item->files;

			} else {
				// Récupération en priorité depuis l'API
				$adjustment_api_item = WDGWPREST_Entity_Adjustment::get( $adjustment_id );
				if ( $adjustment_api_item != FALSE ) {
					$this->id = $adjustment_id;
					$this->id_api_campaign = $adjustment_api_item->id_project;
					$this->id_declaration = $adjustment_api_item->id_declaration;
					$this->date_created = $adjustment_api_item->date_created;
					$this->type = $adjustment_api_item->type;
					$this->turnover_difference = $adjustment_api_item->turnover_difference;
					$this->amount = $adjustment_api_item->amount;
					$this->message_organization = $adjustment_api_item->message_organization;
					$this->message_investors = $adjustment_api_item->message_investors;
				}

			}

			if ( !isset( self::$collection_by_id[ $adjustment_id ] ) ) {
				self::$collection_by_id[ $adjustment_id ] = $this;
			}
		}
	}
	
	public function get_declaration() {
		if ( !isset( $this->declaration ) ) {
			$this->declaration = new WDGROIDeclaration( $this->id_declaration );
		}
		return $this->declaration;
	}
	
	public function get_status() {
		if ( !isset( $this->status ) ) {
			$declaration = $this->get_declaration();
			$this->status = ( $declaration->status == WDGROIDeclaration::$status_finished ) ? WDGAdjustment::$status_done :  WDGAdjustment::$status_upcoming;
		}
		return $this->status;
	}
	
	public function get_documents() {
		if ( !isset( $this->documents ) ) {
			if ( !isset( $this->api_data_files ) ) {
				$this->documents = WDGWPREST_Entity_Adjustment::get_linked_files( $this->id );
			} else {
				$this->documents = $this->api_data_files;
			}
		}
		return $this->documents;
	}
	
	public function get_declarations_checked() {
		if ( !isset( $this->declarations_checked ) ) {
			if ( !isset( $this->api_data_declarations ) ) {
				$this->declarations_checked = WDGWPREST_Entity_Adjustment::get_linked_declarations( $this->id );
			} else {
				$this->declarations_checked = $this->api_data_declarations;
			}
		}
		return $this->declarations_checked;
	}
	
	/**
	 * Crée les données dans l'API
	 */
	public function create() {
		$datetime_current = new DateTime();
		$this->date_created = $datetime_current->format( 'Y-m-d H:i:s' );
		$new_api_item = WDGWPREST_Entity_Adjustment::create( $this );
		$this->id = $new_api_item->id;
		
		// Récupérer ID pour lier documents et declarations_checked
		foreach ( $this->documents as $document_id ) {
			WDGWPREST_Entity_Adjustment::link_file( $this->id, $document_id );
		}
		foreach ( $this->declarations_checked as $declaration_id ) {
			WDGWPREST_Entity_Adjustment::link_declaration( $this->id, $declaration_id );
		}
	}
	
	/**
	 * Sauvegarde les données dans l'API
	 */
	public function update() {
		WDGWPREST_Entity_Adjustment::update( $this );
		self::$collection_by_id[ $this->id ] = $this;
		
		$existing_document_list = WDGWPREST_Entity_Adjustment::get_linked_files( $this->id );
		$existing_document_list_ids = array();
		foreach ( $existing_document_list as $document ) {
			array_push( $existing_document_list_ids, $document->id );
		}
		// Ajout des nouveaux documents
		foreach ( $this->documents as $document_id ) {
			if ( !in_array( $document_id, $existing_document_list_ids) ) {
				WDGWPREST_Entity_Adjustment::link_file( $this->id, $document_id );
			}
		}
		// Retrait des anciens documents
		foreach ( $existing_document_list_ids as $document_id ) {
			if ( !in_array( $document_id, $this->documents) ) {
				WDGWPREST_Entity_Adjustment::unlink_file( $this->id, $document_id );
			}
		}
		
		$existing_declaration_list = WDGWPREST_Entity_Adjustment::get_linked_declarations( $this->id );
		$existing_declaration_list_ids = array();
		foreach ( $existing_declaration_list as $declaration ) {
			array_push( $existing_declaration_list_ids, $declaration->id );
		}
		// Ajout des nouvelles déclarations
		foreach ( $this->declarations_checked as $declaration_id ) {
			if ( !in_array( $declaration_id, $existing_declaration_list_ids) ) {
				WDGWPREST_Entity_Adjustment::link_declaration( $this->id, $declaration_id );
			}
		}
		// Retrait des nouvelles déclarations
		foreach ( $existing_declaration_list_ids as $declaration_id ) {
			if ( !in_array( $declaration_id, $this->declarations_checked) ) {
				WDGWPREST_Entity_Adjustment::unlink_declaration( $this->id, $declaration_id );
			}
		}
		
	}
}
