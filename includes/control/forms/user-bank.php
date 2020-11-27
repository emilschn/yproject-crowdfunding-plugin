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
		if ( !empty( $iban_value ) && !WDGRESTAPI_Lib_Validator::is_iban( $iban_value ) ) {
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
		if ( !empty( $bic_value ) && !WDGRESTAPI_Lib_Validator::is_bic( $bic_value ) ) {
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
				$was_registered = $WDGOrganization->has_lemonway_wallet();
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
						if ( !$was_registered && $WDGOrganization->has_lemonway_wallet() ) {
							ypcf_debug_log( 'WDG_Form_User_Bank::postForm > $WDGOrganization->send_kyc();' );
							$WDGOrganization->send_kyc();
						}
						$test_kyc = TRUE;
					}
				}

				if ( isset( $_FILES[ 'bank-file' .$bank_file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'bank-file' .$bank_file_suffix ][ 'tmp_name' ] ) ) {
					$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_bank, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'bank-file' .$bank_file_suffix ] );
					
					if ( is_int( $file_id ) ) {
						$WDGFile = new WDGKYCFile( $file_id );
						if ( $WDGOrganization->can_register_lemonway() ) {
							$WDGOrganization->register_lemonway();
							if ( !$was_registered && $WDGOrganization->has_lemonway_wallet() ) {
								ypcf_debug_log( 'WDG_Form_User_Bank::postForm > $WDGOrganization->send_kyc();' );
								$WDGOrganization->send_kyc();
							}
							LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
							// Si c'est une organisation qui gère des projets, on envoie une alerte admin
							$list_campaign_orga = $WDGOrganization->get_campaigns();
							if ( !empty( $list_campaign_orga ) ) {
								NotificationsSlack::organization_bank_file_changed_admin( $WDGOrganization->get_name() );
								// TODO : ne faire la notif Asana que si c'est un projet en cours de versement ?
								NotificationsAsana::organization_bank_file_changed_admin( $WDGOrganization->get_name() );
							}
						}
					}
					
				} elseif ( $test_kyc ) {
					$existing_bank_kyc_list = WDGKYCFile::get_list_by_owner_id( $user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_bank );
					$WDGFile = $existing_bank_kyc_list[0];
					if ( !empty( $WDGFile ) ) {
						LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
					}
				}
				
			} else {
				$test_kyc = FALSE;
				$was_registered = $WDGUser->has_lemonway_wallet();
				if ( !empty( $bank_holdername ) ) {
					$WDGUser->save_iban( $bank_holdername, $bank_iban, $bank_bic, $bank_address, $bank_address2 );
					$WDGUser->update_api();
					if ( $WDGUser->can_register_lemonway() ) {
						$WDGUser->register_lemonway();
						if ( !$was_registered && $WDGUser->has_lemonway_wallet() ) {
							ypcf_debug_log( 'WDG_Form_User_Bank::postForm > $WDGUser->send_kyc();' );
							$WDGUser->send_kyc();
						}
						LemonwayLib::wallet_unregister_iban( $WDGUser->get_lemonway_id(), $WDGUser->get_lemonway_iban()->ID );
						LemonwayLib::wallet_register_iban( $WDGUser->get_lemonway_id(), $bank_holdername, $bank_iban, $bank_bic, $bank_address, $bank_address2 );
						$test_kyc = TRUE;
					}
				}

				if ( isset( $_FILES[ 'bank-file' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'bank-file' ][ 'tmp_name' ] ) ) {
					$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_bank, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'bank-file' ] );
					
					if ( is_int( $file_id ) ) {
						$WDGFile = new WDGKYCFile( $file_id );
						if ( $WDGUser->can_register_lemonway() ) {
							$WDGUser->register_lemonway();
							if ( !$was_registered && $WDGUser->has_lemonway_wallet() ) {
								ypcf_debug_log( 'WDG_Form_User_Bank::postForm > $WDGUser->send_kyc();' );
								$WDGUser->send_kyc();
							}
							LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
						}
					}
					
				} elseif ( $test_kyc ) {
					$existing_bank_kyc_list = WDGKYCFile::get_list_by_owner_id( $user_id, WDGKYCFile::$owner_user, WDGKYCFile::$type_bank );
					$WDGFile = $existing_bank_kyc_list[0];
					if ( !empty( $WDGFile ) ) {
						LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_bank, $WDGFile->get_byte_array() );
					}
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
