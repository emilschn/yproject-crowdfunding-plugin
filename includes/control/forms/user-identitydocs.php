<?php
class WDG_Form_User_Identity_Docs extends WDG_Form {
	
	public static $name = 'user-identity-docs';
	
	public static $field_group_hidden = 'user-user-identity-docs-hidden';
	public static $field_group_files = 'user-user-identity-docs-files';
	
	private $user_id;
	
	public function __construct( $user_id = FALSE ) {
		parent::__construct( self::$name );
		$this->user_id = $user_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		$WDGUser = new WDGUser( $this->user_id );
		
		// $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			WDG_Form_User_Identity_Docs::$field_group_hidden,
			WDG_Form_User_Identity_Docs::$name
		);
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_User_Identity_Docs::$field_group_hidden,
			$this->user_id
		);
		
		// $field_group_files : Les champs fichiers
		$current_filelist_id = WDGKYCFile::get_list_by_owner_id( $WDGUser->get_wpref(), WDGKYCFile::$owner_user, WDGKYCFile::$type_id );
		$current_file_id = $current_filelist_id[0];
		$id_file_path = ( empty( $current_file_id ) ) ? '' : $current_file_id->get_public_filepath();
		$this->addField(
			'file',
			'identity',
			__( "Justificatif d'identit&eacute; *", 'yproject' ),
			WDG_Form_User_Identity_Docs::$field_group_files,
			$id_file_path,
			__( "Pour une personne fran&ccedil;aise : carte d'identit&eacute; recto-verso ou passeport fran&ccedil;ais. Sinon : le titre de s&eacute;jour et le passeport d'origine.", 'yproject' ),
			$current_file_id->date_uploaded
		);
		
		$current_filelist_id2 = WDGKYCFile::get_list_by_owner_id( $WDGUser->get_wpref(), WDGKYCFile::$owner_user, WDGKYCFile::$type_id_2 );
		$current_file_id2 = $current_filelist_id2[0];
		$id2_file_path = ( empty( $current_file_id2 ) ) ? '' : $current_file_id2->get_public_filepath();
		$this->addField(
			'file',
			'identity2',
			__( "Deuxi&egrave;me justificatif d'identit&eacute;", 'yproject' ),
			WDG_Form_User_Identity_Docs::$field_group_files,
			$id2_file_path,
			__( "Facultatif, selon le document que vous avez envoy&eacute; dans le premier champ.", 'yproject' ),
			$current_file_id->date_uploaded
		);
		
		$current_filelist_home = WDGKYCFile::get_list_by_owner_id( $WDGUser->get_wpref(), WDGKYCFile::$owner_user, WDGKYCFile::$type_home );
		$current_file_home = $current_filelist_home[0];
		$home_file_path = ( empty( $current_file_home ) ) ? '' : $current_file_home->get_public_filepath();
		$this->addField(
			'file',
			'home',
			__( "Justificatif de domicile *", 'yproject' ),
			WDG_Form_User_Identity_Docs::$field_group_files,
			$home_file_path,
			__( "Datant de moins de 3 mois, provenant d'un fournisseur d'&eacute;nergie (&eacute;lectricit&eacute;, gaz, eau) ou d'un bailleur, ou un relev&eacute; d'imp&ocirc;t datant de moins de 3 mois.", 'yproject' ),
			$current_file_home->date_uploaded
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$WDGUser = new WDGUser( $user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {

		// Analyse du formulaire
		} else {
			
			if ( isset( $_FILES[ 'identity' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity' ][ 'tmp_name' ] ) ) {
				$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'identity' ] );
				$WDGFile = new WDGKYCFile( $file_id );
				LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id, $WDGFile->get_byte_array() );
			}
			if ( isset( $_FILES[ 'identity2' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity2' ][ 'tmp_name' ] ) ) {
				$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id_2, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'identity2' ] );
				$WDGFile = new WDGKYCFile( $file_id );
				LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_passport_euro, $WDGFile->get_byte_array() );
			}
			if ( isset( $_FILES[ 'home' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'home' ][ 'tmp_name' ] ) ) {
				$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_home, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'home' ] );
				$WDGFile = new WDGKYCFile( $file_id );
				LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_home, $WDGFile->get_byte_array() );
			}
			
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		$this->initFields(); // Reinit pour avoir les bonnes valeurs
		
		return $buffer;
	}
	
}
