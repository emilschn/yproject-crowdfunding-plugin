<?php
class WDG_Form_Adjustement extends WDG_Form {
	
	public static $name = 'project-adjustment';
	
	public static $field_group_hidden = 'adjustment-hidden';
	public static $field_group_adjustment = 'adjustment-data';
	
	private $campaign_id;
	private $adjustment_id;
	
	public function __construct( $campaign_id = FALSE, $adjustment_id = FALSE ) {
		parent::__construct( self::$name );
		$this->campaign_id = $campaign_id;
		$this->adjustment_id = $adjustment_id;
		if ( empty( $this->adjustment_id ) ) {
			$this->adjustment_id = 0;
		}
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$adjustment = FALSE;
		if ( !empty( $this->adjustment_id ) ) {
			$adjustment = new WDGAdjustement( $this->adjustment_id );
		}

		
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'campaign_id',
			'',
			self::$field_group_hidden,
			$this->campaign_id
		);
		
		$this->addField(
			'hidden',
			'adjustment_id',
			'',
			self::$field_group_hidden,
			$this->adjustment_id
		);
		
		// Champs affichés : $field_group_adjustment
		$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $this->campaign_id );
		$declaration_list_by_id = array( ''	=> '' );
		foreach ( $declaration_list as $WDGROIDeclaration ) {
			$declaration_list_by_id[ $WDGROIDeclaration->id ] = $WDGROIDeclaration->date_due;
		}
		$this->addField(
			'select',
			'declaration',
			__( "Versement au moment duquel l'ajustement s'applique *", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->id_declaration : '',
			FALSE,
			$declaration_list_by_id
		);
		
		$adjustment_type_str_by_id = array( '' => '' );
		foreach ( WDGAdjustment::$types_str_by_id as $key => $value ) {
			$adjustment_type_str_by_id[ $key ] = $value;
		}
		$this->addField(
			'select',
			'type',
			__( "Type d'ajustement *", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->type : '',
			FALSE,
			$adjustment_type_str_by_id
		);
		
		$this->addField(
			'text-money',
			'turnover_difference',
			__( "Diff&eacute;rentiel de CA", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->turnover_difference : ''
		);
		
		$this->addField(
			'text-money',
			'amount',
			__( "Montant de l'ajustement *", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->amount : ''
		);
		
		$files = WDGWPREST_Entity_Project::get_files( $campaign->get_api_id(), 'project_document' );
		$documents_by_id = array();
		foreach ( $files as $file_item ) {
			$file_item_metadata = json_decode( $file_item->metadata );
			$documents_by_id[ $file_item->id ] = $file_item_metadata->name;
		}
		$this->addField(
			'select-multiple',
			'documents',
			__( "Documents justificatifs li&eacute;s", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->documents : '',
			FALSE,
			$documents_by_id
		);
		
		unset( $declaration_list_by_id[ '' ] );
		$this->addField(
			'select-multiple',
			'declarations_checked',
			__( "Versements &agrave; marquer comme v&eacute;rifi&eacute;s", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->declarations_checked : '',
			FALSE,
			$declaration_list_by_id
		);
		
		$this->addField(
			'textarea',
			'message_organization',
			__( "Message pour l'entrepreneur", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->message_organization : ''
		);
		
		$this->addField(
			'textarea',
			'message_investors',
			__( "Message pour les investisseurs", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->message_investors : ''
		);
	}
	
	public function postForm() {
		parent::postForm();
		
		if ( !is_user_logged_in() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ),
				'general'
			);
			return;
		}
		
		$WDGUser_current = WDGUser::current();
		if ( !$WDGUser_current->is_admin() ) {
			$this->addPostError(
				'user-cant-edit',
				__( "Vous ne pouvez pas &eacute;diter cet ajustement.", 'yproject' ),
				'general'
			);
			return;
		}
		
		$campaign_id = $this->getInputText( 'campaign_id' );
		if ( empty( $campaign_id ) ) {
			$this->addPostError(
				'campaign_id',
				__( "Erreur de validation du formulaire.", 'yproject' ),
				'general'
			);
		}
		
		
		
		$declaration = $this->getInputText( 'declaration' );
		if ( empty( $declaration ) ) {
			$this->addPostError(
				'declaration',
				__( "Une d&eacute;claration doit &ecirc;tre s&eacute;lectionn&eacute;e.", 'yproject' ),
				'general'
			);
		}
		
		$type = $this->getInputText( 'type' );
		if ( empty( $type ) ) {
			$this->addPostError(
				'type',
				__( "Un type de d&eacute;claration doit &ecirc;tre s&eacute;lectionn&eacute;e.", 'yproject' ),
				'general'
			);
		}
		
		$turnover_difference = $this->getInputText( 'turnover_difference' );
		if ( empty( $turnover_difference ) ) {
			$turnover_difference = 0;
		}
		if ( !is_numeric( $turnover_difference ) ) {
			$this->addPostError(
				'turnover_difference',
				__( "Erreur de saisie de la diff&eacute;rence de CA.", 'yproject' ),
				'general'
			);
		}
		
		$amount = $this->getInputText( 'amount' );
		if ( !is_numeric( $amount ) || empty( $amount ) ) {
			$this->addPostError(
				'amount',
				__( "Erreur de saisie du montant (ne peut pas &ecirc;tre &eacute;gal &agrave; zero).", 'yproject' ),
				'general'
			);
		}
		
		// TODO : vérifier le type de documents et declarations_checked
		
		
		if ( !$this->hasErrors() ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$message_organization = $this->getInputText( 'message_organization' );
			$message_investors = $this->getInputText( 'message_investors' );
			$documents = array();
			$declarations_checked = array();
		
			$adjustment = FALSE;
			if ( !empty( $this->adjustment_id ) ) {
				$adjustment = new WDGAdjustment( $this->adjustment_id );
			} else {
				$adjustment = new WDGAdjustment();
			}
			
			$adjustment->id_api_campaign = $campaign->get_api_id();
			$adjustment->id_declaration = $declaration;
			$adjustment->type = $type;
			$adjustment->turnover_difference = $turnover_difference;
			$adjustment->amount = $amount;
			$adjustment->documents = $documents;
			$adjustment->declarations_checked = $declarations_checked;
			$adjustment->message_organization = $message_organization;
			$adjustment->message_investors = $message_investors;
			
			
			if ( !empty( $this->adjustment_id ) ) {
				$adjustment->update();
			} else {
				$adjustment->create();
			}
		}
		
		return !$this->hasErrors();
	}
	
}
