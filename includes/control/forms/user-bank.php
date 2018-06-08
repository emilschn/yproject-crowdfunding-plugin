<?php
class WDG_Form_User_Bank extends WDG_Form {
	
	public static $name = 'user-bank';
	
	public static $field_group_hidden = 'user-user-bank-hidden';
	public static $field_group_iban = 'user-user-bank-iban';
	public static $field_group_file = 'user-user-bank-file';
	
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
			WDG_Form_User_Bank::$field_group_hidden,
			WDG_Form_User_Bank::$name
		);
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_User_Bank::$field_group_hidden,
			$this->user_id
		);
		
		// $field_group_iban : Les champs informations
		$this->addField(
			'text',
			'bank-holdername',
			__( "Nom du propri&eacute;taire du compte *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			$WDGUser->get_bank_holdername()
		);
		
		$this->addField(
			'text',
			'bank-address',
			__( "Adresse du compte *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			$WDGUser->get_bank_address()
		);
		
		$this->addField(
			'text',
			'bank-address2',
			__( "Pays *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			$WDGUser->get_bank_address2()
		);
		
		$this->addField(
			'text',
			'bank-iban',
			__( "IBAN *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			$WDGUser->get_bank_iban()
		);
		
		$this->addField(
			'text',
			'bank-bic',
			__( "BIC *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			$WDGUser->get_bank_bic()
		);

		// $field_group_files : Les champs fichiers
		$current_filelist_bank = WDGKYCFile::get_list_by_owner_id( $WDGUser->get_wpref(), WDGKYCFile::$owner_user, WDGKYCFile::$type_bank );
		$current_file_bank = $current_filelist_bank[0];
		$bank_file_path = ( empty( $current_file_bank ) ) ? '' : $current_file_bank->get_public_filepath();
		$this->addField(
			'file',
			'bank-file',
			__( "RIB *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_file,
			$bank_file_path,
			'',
			$current_file_bank->date_uploaded
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
			
			$bank_holdername = $this->getInputText( 'bank-holdername' );
			$bank_address = filter_input( INPUT_POST, 'bank-address' );
			$bank_address2 = filter_input( INPUT_POST, 'bank-address2' );
			$bank_iban = filter_input( INPUT_POST, 'bank-iban' );
			$bank_bic = filter_input( INPUT_POST, 'bank-bic' );
			$WDGUser->save_iban( $bank_holdername, $bank_iban, $bank_bic, $bank_address, $bank_address2 );
			$WDGUser->update_api();
			
			if ( isset( $_FILES[ 'bank-file' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'bank-file' ][ 'tmp_name' ] ) ) {
				$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_bank, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'bank-file' ] );
				$WDGFile = new WDGKYCFile( $file_id );
				LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
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
