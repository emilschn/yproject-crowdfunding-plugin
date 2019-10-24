<?php
class WDG_Form_User_Bank extends WDG_Form {
	
	public static $name = 'user-bank';
	
	public static $field_group_hidden = 'user-user-bank-hidden';
	public static $field_group_iban = 'user-user-bank-iban';
	public static $field_group_file = 'user-user-bank-file';
	
	private $user_id;
	private $is_orga;
	
	public function __construct( $user_id = FALSE, $is_orga = FALSE ) {
		parent::__construct( self::$name );
		$this->user_id = $user_id;
		$this->is_orga = $is_orga;
		$this->initFields();
	}

	public static function is_iban( $input ) {
		$iban = strtolower( str_replace( ' ', '', $input ) );
		$countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
		$chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);

		if ( strlen($iban) == $countries[ substr( $iban, 0, 2 ) ] ) {
			$moved_char = substr( $iban, 4 ).substr( $iban, 0, 4 );
			$moved_char_array = str_split( $moved_char );
			$new_string = "";

			foreach ( $moved_char_array AS $key => $value ) {
				if ( !is_numeric( $moved_char_array[ $key ] ) ) {
					$moved_char_array[ $key ] = $chars[ $moved_char_array[ $key ] ];
				}
				$new_string .= $moved_char_array[ $key ];
			}

			if ( bcmod( $new_string, '97' ) == 1 ) {
				return TRUE;
			} else{
				return FALSE;
			}
			
		} else {
			return FALSE;
		}   
	}
	
	public static function is_bic( $input ) {
		return preg_match( '/^[a-z]{6}[2-9a-z][0-9a-np-z]([a-z0-9]{3}|x{3})?$/i', $input );
	}
	
	protected function initFields() {
		parent::initFields();
		
		if ( $this->is_orga ) {
			$WDGOrganization = new WDGOrganization( $this->user_id );
		} else {
			$WDGUser = new WDGUser( $this->user_id );
		}
		
		// $field_group_hidden
		if ( !$this->is_orga ) {
			$this->addField(
				'hidden',
				'action',
				'',
				WDG_Form_User_Bank::$field_group_hidden,
				WDG_Form_User_Bank::$name
			);
		}
		
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
			( $this->is_orga ) ? $WDGOrganization->get_bank_owner() : $WDGUser->get_bank_holdername()
		);
		
		$this->addField(
			'text',
			'bank-address',
			__( "Adresse du compte *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			( $this->is_orga ) ? $WDGOrganization->get_bank_address() : $WDGUser->get_bank_address()
		);
		
		$this->addField(
			'text',
			'bank-address2',
			__( "Pays *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			( $this->is_orga ) ? $WDGOrganization->get_bank_address2() : $WDGUser->get_bank_address2()
		);
		
		$iban_value = ( $this->is_orga ) ? $WDGOrganization->get_bank_iban() : $WDGUser->get_bank_iban();
		$iban_options = array();
		if ( !empty( $iban_value ) && !self::is_iban( $iban_value ) ) {
			$iban_error = __( "Cette valeur ne correspond pas &agrave; un IBAN.", 'yproject' );
			$iban_options[ 'warning' ] = $iban_error;
		}
		$this->addField(
			'text',
			'bank-iban',
			__( "IBAN *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			$iban_value,
			FALSE,
			$iban_options
		);
		
		$bic_value = ( $this->is_orga ) ? $WDGOrganization->get_bank_bic() : $WDGUser->get_bank_bic();
		$bic_options = array();
		if ( !empty( $bic_value ) && !self::is_bic( $bic_value ) ) {
			$bic_error = __( "Cette valeur ne correspond pas &agrave; un BIC.", 'yproject' );
			$bic_options[ 'warning' ] = $bic_error;
		}
		$this->addField(
			'text',
			'bank-bic',
			__( "BIC *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_iban,
			$bic_value,
			FALSE,
			$bic_options
		);

		// $field_group_files : Les champs fichiers
		if ( $this->is_orga ) {
			$current_filelist_bank = WDGKYCFile::get_list_by_owner_id( $WDGOrganization->get_wpref(), WDGKYCFile::$owner_organization, WDGKYCFile::$type_bank );
			$wallet_id = $WDGOrganization->get_lemonway_id();
		} else {
			$current_filelist_bank = WDGKYCFile::get_list_by_owner_id( $WDGUser->get_wpref(), WDGKYCFile::$owner_user, WDGKYCFile::$type_bank );
			$wallet_id = $WDGUser->get_lemonway_id();
		}
		$current_file_bank = ( empty( $current_filelist_bank ) ) ? FALSE : $current_filelist_bank[0];
		$bank_file_path = ( empty( $current_file_bank ) ) ? '' : $current_file_bank->get_public_filepath();
		$field_bank_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_bank, ( empty( $current_file_bank ) ) ? '' : $current_file_bank->date_uploaded );
		unset( $field_bank_params[ 'message_instead_of_field' ] );
		$suffix = ( $this->is_orga ) ? '-orga-' . $WDGOrganization->get_wpref() : '';
		$this->addField(
			'file',
			'bank-file' .$suffix,
			__( "RIB *", 'yproject' ),
			WDG_Form_User_Bank::$field_group_file,
			$bank_file_path,
			'',
			$field_bank_params
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
		} else if ( !$this->is_orga && $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {

		// Analyse du formulaire
		} else {
			$bank_holdername = $this->getInputText( 'bank-holdername' );
			$bank_address = $this->getInputText( 'bank-address' );
			$bank_address2 = $this->getInputText( 'bank-address2' );
			$bank_iban = $this->getInputText( 'bank-iban' );
			$bank_bic = $this->getInputText( 'bank-bic' );
			
			if ( $this->is_orga && $WDGUser_current->can_edit_organization( $user_id ) ) {
				$test_kyc = FALSE;
				$WDGOrganization = new WDGOrganization( $user_id );
				$bank_file_suffix = '-orga-' . $WDGOrganization->get_wpref();
				if ( !empty( $bank_holdername ) ) {
					$WDGOrganization->set_bank_owner( $bank_holdername );
					$WDGOrganization->set_bank_address( $bank_address );
					$WDGOrganization->set_bank_address2( $bank_address2 );
					$WDGOrganization->set_bank_iban( $bank_iban );
					$WDGOrganization->set_bank_bic( $bank_bic );
					$WDGOrganization->save();
					if ( $WDGOrganization->can_register_lemonway() ) {
						$WDGOrganization->register_lemonway();
						// TODO : faire un unregister
						LemonwayLib::wallet_register_iban( $WDGOrganization->get_lemonway_id(), $bank_holdername, $bank_iban, $bank_bic, $bank_address, $bank_address2 );
						$test_kyc = TRUE;
					}
				}

				if ( isset( $_FILES[ 'bank-file' .$bank_file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'bank-file' .$bank_file_suffix ][ 'tmp_name' ] ) ) {
					$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_bank, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'bank-file' .$bank_file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					if ( $WDGOrganization->can_register_lemonway() ) {
						$WDGOrganization->register_lemonway();
						LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
					}
					
				} elseif ( $test_kyc ) {
					$existing_bank_kyc_list = WDGKYCFile::get_list_by_owner_id( $user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_bank );
					$WDGFile = $existing_bank_kyc_list[0];
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
				}
				
			} else {
				$test_kyc = FALSE;
				if ( !empty( $bank_holdername ) ) {
					$WDGUser->save_iban( $bank_holdername, $bank_iban, $bank_bic, $bank_address, $bank_address2 );
					$WDGUser->update_api();
					if ( $WDGUser->can_register_lemonway() ) {
						$WDGUser->register_lemonway();
						LemonwayLib::wallet_unregister_iban( $WDGUser->get_lemonway_id(), $WDGUser->get_lemonway_iban()->ID );
						LemonwayLib::wallet_register_iban( $WDGUser->get_lemonway_id(), $bank_holdername, $bank_iban, $bank_bic, $bank_address, $bank_address2 );
						$test_kyc = TRUE;
					}
				}

				if ( isset( $_FILES[ 'bank-file' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'bank-file' ][ 'tmp_name' ] ) ) {
					$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_bank, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'bank-file' ] );
					$WDGFile = new WDGKYCFile( $file_id );
					if ( $WDGUser->can_register_lemonway() ) {
						$WDGUser->register_lemonway();
						LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
					}
					
				} elseif ( $test_kyc ) {
					$existing_bank_kyc_list = WDGKYCFile::get_list_by_owner_id( $user_id, WDGKYCFile::$owner_user, WDGKYCFile::$type_bank );
					$WDGFile = $existing_bank_kyc_list[0];
					LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
				}
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
