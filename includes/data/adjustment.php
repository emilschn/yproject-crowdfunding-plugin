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
				// TODO : documents et declarations_checked

			} else {
				// Récupération en priorité depuis l'API
				$adjustment_api_item = WDGWPREST_Entity_Declaration::get( $adjustment_id );
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
					// TODO : documents et declarations_checked
				}
				self::$collection_by_id[ $declaration_id ] = $this;

			}
		}
	}
	
	/**
	 * Crée les données dans l'API
	 */
	public function create() {
		$datetime_current = new DateTime();
		$this->date_created = $datetime_current->format( 'Y-m-d H:i:s' );
		WDGWPREST_Entity_Adjustment::create( $this );
		
		// Récupérer ID pour lier documents et declarations_checked
	}
	
	/**
	 * Sauvegarde les données dans l'API
	 */
	public function update() {
		WDGWPREST_Entity_Adjustment::update( $this );
		self::$collection_by_id[ $this->id ] = $this;
		
		// Récupérer ID pour lier documents et declarations_checked
	}
}
