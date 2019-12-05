<?php
class WDG_Form_Declaration_Bill extends WDG_Form {
	
	public static $name = 'project-declaration-bill';
	
	public static $field_group_hidden = 'declaration-bill-hidden';
	public static $field_group_file = 'declaration-bill-file';
	
	private $declaration_id;
	
	public function __construct( $declaration_id = FALSE ) {
		parent::__construct( self::$name );
		$this->declaration_id = $declaration_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'declaration_id',
			'',
			self::$field_group_hidden,
			$this->declaration_id
		);
		
		$current_filelist_bill = WDGWPREST_Entity_Declaration::get_bill_file( $this->declaration_id );
		$current_file_bill_url = '';
		$options = array();
		if ( !empty( $current_filelist_bill ) ) {
			$current_file_bill_url = $current_filelist_bill[0];
			if ( !empty( $current_file_bill_url ) ) {
				$options[ 'message_instead_of_field' ] = 'Facture disponible';
				$options[ 'keep_editing_for_admin' ] = TRUE;
			}
		}
		$this->addField(
			'file',
			'bill-' . $this->declaration_id,
			__( "Facture", 'yproject' ),
			self::$field_group_file,
			$current_file_bill_url,
			FALSE,
			$options
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
		
		$WDGUser = WDGUser::current();
		if ( !$WDGUser->is_admin() ) {
			$this->addPostError(
				'user-cant-edit',
				__( "Vous ne pouvez pas ajouter cette facture.", 'yproject' ),
				'general'
			);
			return;
		}
		
		$file_bill = $this->getInputFile( 'bill-' . $this->declaration_id );
		if ( empty( $file_bill ) ) {
			$this->addPostError(
				'bill',
				__( "Le fichier est indispensable", 'yproject' ),
				'bill'
			);
			return;
		}
		
		if ( !$this->hasErrors() ) {
			$file_name = $file_bill[ 'name' ];
			$file_name_exploded = explode( '.', $file_name );
			$ext = $file_name_exploded[ count( $file_name_exploded ) - 1 ];
			$byte_array = file_get_contents( $file_bill[ 'tmp_name' ] );
			$metadata_encoded = '';
			
			$new_file_bill = WDGWPREST_Entity_File::create( $this->declaration_id, 'declaration', 'bill', $ext, base64_encode( $byte_array ), $metadata_encoded );
			
		}
		
		return !$this->hasErrors();
	}
	
}
