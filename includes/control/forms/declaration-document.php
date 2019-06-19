<?php
class WDG_Form_Declaration_Document extends WDG_Form {
	
	public static $name = 'project-declaration-document';
	
	public static $field_group_hidden = 'document-hidden';
	public static $field_group_document = 'document-data';
	
	private $campaign_id;
	
	public function __construct( $campaign_id = FALSE ) {
		parent::__construct( self::$name );
		$this->campaign_id = $campaign_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();

		
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'campaign_id',
			'',
			self::$field_group_hidden,
			$this->campaign_id
		);
		
		// Champs affichés : $field_group_document
		$this->addField(
			'file',
			'adjustment_document',
			__( "Document justificatif", 'yproject' ),
			self::$field_group_document
		);
		
		$this->addField(
			'text',
			'name',
			__( "Nom du document", 'yproject' ),
			self::$field_group_document
		);
		
		$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $this->campaign_id );
		$declaration_list_by_id = array();
		foreach ( $declaration_list as $WDGROIDeclaration ) {
			$declaration_list_by_id[ $WDGROIDeclaration->id ] = $WDGROIDeclaration->date_due;
		}
		$this->addField(
			'select',
			'first_declaration',
			__( "Premi&egrave;re &eacute;ch&eacute;ance concern&eacute;e", 'yproject' ),
			self::$field_group_document,
			FALSE,
			FALSE,
			$declaration_list_by_id
		);
		
		$this->addField(
			'select',
			'last_declaration',
			__( "Derni&egrave;re &eacute;ch&eacute;ance concern&eacute;e", 'yproject' ),
			self::$field_group_document,
			FALSE,
			FALSE,
			$declaration_list_by_id
		);
		
		$this->addField(
			'textarea',
			'details',
			__( "D&eacute;tails du document", 'yproject' ),
			self::$field_group_document
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
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		if ( !$campaign->current_user_can_edit() ) {
			$this->addPostError(
				'user-cant-edit',
				__( "Vous ne pouvez pas faire cette d&eacute;claration.", 'yproject' ),
				'general'
			);
			return;
		}
		
		$file_document = $this->getInputFile( 'adjustment_document' );
		if ( empty( $file_document ) ) {
			$this->addPostError(
				'adjustment_document',
				__( "Le fichier est indispensable", 'yproject' ),
				'adjustment_document'
			);
			return;
			
		} else {
			$file_name = $file_document[ 'name' ];
			$file_name_exploded = explode( '.', $file_name );
			$ext = $file_name_exploded[ count( $file_name_exploded ) - 1 ];
			$byte_array = file_get_contents( $file_document[ 'tmp_name' ] );
			
			$document_name = $this->getInputText( 'name' );
			$document_first_declaration = $this->getInputText( 'first_declaration' );
			$document_last_declaration = $this->getInputText( 'last_declaration' );
			$document_details = $this->getInputText( 'details' );
			$metadata = array(
				'name'				=> $document_name,
				'first_declaration'	=> $document_first_declaration,
				'last_declaration'	=> $document_last_declaration,
				'details'			=> $document_details,
			);
			$metadata_encoded = json_encode( $metadata );
			
			$file_document = WDGWPREST_Entity_File::create( $campaign->get_api_id(), 'project', 'project_document', $ext, base64_encode( $byte_array ), $metadata_encoded );
			
			NotificationsSlack::send_declaration_document_uploaded( $campaign->get_name(), $document_name );
		}
		
		
		return !$this->hasErrors();
	}
	
}
