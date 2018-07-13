<?php
class WDG_Form_User_Identity_Docs extends WDG_Form {
	
	public static $name = 'user-identity-docs';
	
	public static $field_group_hidden = 'user-user-identity-docs-hidden';
	public static $field_group_files = 'user-user-identity-docs-files';
	public static $field_group_files_orga = 'user-user-identity-docs-files-orga';
	
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
		
		// $field_group_hidden
		if ( !$this->is_orga ) {
			$this->addField(
				'hidden',
				'action',
				'',
				WDG_Form_User_Identity_Docs::$field_group_hidden,
				WDG_Form_User_Identity_Docs::$name
			);
		}
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_User_Identity_Docs::$field_group_hidden,
			$this->user_id
		);
		
		$wallet_id = FALSE;
		if ( $this->is_orga ) {
			$WDGOrganization = new WDGOrganization( $this->user_id );
			$wallet_id = $WDGOrganization->get_lemonway_id();
		} else {
			$WDGUser = new WDGUser( $this->user_id );
			$wallet_id = $WDGUser->get_lemonway_id();
		}
		
		// $field_group_files : Les champs fichiers
		$suffix = ( $this->is_orga ) ? '-orga-' . $WDGOrganization->get_wpref() : '';
		
		$current_filelist_id = WDGKYCFile::get_list_by_owner_id( $this->user_id, ( $this->is_orga ) ? WDGKYCFile::$owner_organization : WDGKYCFile::$owner_user, WDGKYCFile::$type_id );
		$current_file_id = $current_filelist_id[0];
		$id_file_path = ( empty( $current_file_id ) ) ? '' : $current_file_id->get_public_filepath();
		$id_label = ( $this->is_orga ) ? __( "Justificatif d'identit&eacute; du g&eacute;rant ou du pr&eacute;sident *", 'yproject' ) : __( "Justificatif d'identit&eacute; *", 'yproject' );
		$field_id_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_id, $current_file_id->date_uploaded );
		$this->addField(
			'file',
			'identity' .$suffix,
			$id_label,
			WDG_Form_User_Identity_Docs::$field_group_files,
			$id_file_path,
			__( "Pour une personne fran&ccedil;aise : carte d'identit&eacute; recto-verso ou passeport fran&ccedil;ais. Sinon : le titre de s&eacute;jour et le passeport d'origine.", 'yproject' ),
			$field_id_params
		);
		
		$current_filelist_home = WDGKYCFile::get_list_by_owner_id( $this->user_id, ( $this->is_orga ) ? WDGKYCFile::$owner_organization : WDGKYCFile::$owner_user, WDGKYCFile::$type_home );
		$current_file_home = $current_filelist_home[0];
		$home_file_path = ( empty( $current_file_home ) ) ? '' : $current_file_home->get_public_filepath();
		$home_label = ( $this->is_orga ) ? __( "Justificatif de domicile du g&eacute;rant ou du pr&eacute;sident *", 'yproject' ) : __( "Justificatif de domicile *", 'yproject' );
		$field_home_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_home, $current_file_home->date_uploaded );
		$this->addField(
			'file',
			'home' .$suffix,
			$home_label,
			WDG_Form_User_Identity_Docs::$field_group_files,
			$home_file_path,
			__( "Datant de moins de 3 mois, provenant d'un fournisseur d'&eacute;nergie (&eacute;lectricit&eacute;, gaz, eau) ou d'un bailleur, ou un relev&eacute; d'imp&ocirc;t datant de moins de 3 mois.", 'yproject' ),
			$field_home_params
		);
		
		if ( $this->is_orga ) {
		
			$current_filelist_kbis = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_kbis );
			$current_file_kbis = $current_filelist_kbis[0];
			$kbis_file_path = ( empty( $current_file_kbis ) ) ? '' : $current_file_kbis->get_public_filepath();
			$field_kbis_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_kbis, $current_file_kbis->date_uploaded );
			$this->addField(
				'file',
				'kbis' .$suffix,
				__( "K-BIS ou &eacute;quivalent &agrave; un registre du commerce *", 'yproject' ),
				WDG_Form_User_Identity_Docs::$field_group_files_orga,
				$kbis_file_path,
				__( "Datant de moins de 3 mois", 'yproject' ),
				$field_kbis_params
			);
		
			$current_filelist_status = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_status );
			$current_file_status = $current_filelist_status[0];
			$status_file_path = ( empty( $current_file_status ) ) ? '' : $current_file_status->get_public_filepath();
			$field_status_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_status, $current_file_status->date_uploaded );
			$this->addField(
				'file',
				'status' .$suffix,
				__( "Statuts de la soci&eacute;t&eacute;, certifi&eacute;s conformes à l'original par le g&eacute;rant *", 'yproject' ),
				WDG_Form_User_Identity_Docs::$field_group_files_orga,
				$status_file_path,
				'',
				$field_status_params
			);
		
			$current_filelist_capital_allocation = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_capital_allocation );
			$current_file_capital_allocation = $current_filelist_capital_allocation[0];
			$capital_allocation_file_path = ( empty( $current_file_capital_allocation ) ) ? '' : $current_file_capital_allocation->get_public_filepath();
			$field_status_capital_allocation = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_capital_allocation, $current_file_capital_allocation->date_uploaded );
			$this->addField(
				'file',
				'capital_allocation' .$suffix,
				__( "Attestation de r&eacute;partition du capital (facultatif)", 'yproject' ),
				WDG_Form_User_Identity_Docs::$field_group_files_orga,
				$capital_allocation_file_path,
				__( "Si la r&eacute;partition du capital n'est pas exprim&eacute;e clairement dans les statuts", 'yproject' ),
				$field_status_capital_allocation
			);
		
			$current_filelist_id_2 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_id_2 );
			$current_file_id_2 = $current_filelist_id_2[0];
			$id2_file_path = ( empty( $current_file_id_2 ) ) ? '' : $current_file_id_2->get_public_filepath();
			$field_status_id_2 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_id2, $current_file_id_2->date_uploaded );
			$this->addField(
				'file',
				'identity2' .$suffix,
				__( "Justificatif d'identit&eacute; de la deuxi&egrave;me personne (facultatif)", 'yproject' ),
				WDG_Form_User_Identity_Docs::$field_group_files_orga,
				$id2_file_path,
				__( "Si une deuxi&egrave;me personne physique d&eacute;tient au moins 25% du capital", 'yproject' ),
				$field_status_id_2
			);
		
			$current_filelist_home_2 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_home_2 );
			$current_file_home_2 = $current_filelist_home_2[0];
			$home2_file_path = ( empty( $current_file_home_2 ) ) ? '' : $current_file_home_2->get_public_filepath();
			$field_status_home_2 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_home2, $current_file_home_2->date_uploaded );
			$this->addField(
				'file',
				'home2' .$suffix,
				__( "Justificatif de domicile de la deuxi&egrave;me personne (facultatif)", 'yproject' ),
				WDG_Form_User_Identity_Docs::$field_group_files_orga,
				$home2_file_path,
				__( "Si une deuxi&egrave;me personne physique d&eacute;tient au moins 25% du capital", 'yproject' ),
				$field_status_home_2
			);
		
			$current_filelist_id_3 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_id_3 );
			$current_file_id_3 = $current_filelist_id_3[0];
			$id3_file_path = ( empty( $current_file_id_3 ) ) ? '' : $current_file_id_3->get_public_filepath();
			$field_status_id_3 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_id3, $current_file_id_3->date_uploaded );
			$this->addField(
				'file',
				'identity3' .$suffix,
				__( "Justificatif d'identit&eacute; de la troisi&egrave;me personne (facultatif)", 'yproject' ),
				WDG_Form_User_Identity_Docs::$field_group_files_orga,
				$id3_file_path,
				__( "Si une troisi&egrave;me personne physique d&eacute;tient au moins 25% du capital", 'yproject' ),
				$field_status_id_3
			);
		
			$current_filelist_home_3 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_home_3 );
			$current_file_home_3 = $current_filelist_home_3[0];
			$home3_file_path = ( empty( $current_file_home_3 ) ) ? '' : $current_file_home_3->get_public_filepath();
			$field_status_home_3 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_home3, $current_file_home_3->date_uploaded );
			$this->addField(
				'file',
				'home3' .$suffix,
				__( "Justificatif de domicile de la troisi&egrave;me personne (facultatif)", 'yproject' ),
				WDG_Form_User_Identity_Docs::$field_group_files_orga,
				$home3_file_path,
				__( "Si une troisi&egrave;me personne physique d&eacute;tient au moins 25% du capital", 'yproject' ),
				$field_status_home_3
			);
			
		} else {
		
			$current_filelist_id2 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_user, WDGKYCFile::$type_id_2 );
			$current_file_id2 = $current_filelist_id2[0];
			$id2_file_path = ( empty( $current_file_id2 ) ) ? '' : $current_file_id2->get_public_filepath();
			$field_status_id2 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_id2, $current_file_id2->date_uploaded );
			$this->addField(
				'file',
				'identity2',
				__( "Deuxi&egrave;me justificatif d'identit&eacute;", 'yproject' ),
				WDG_Form_User_Identity_Docs::$field_group_files,
				$id2_file_path,
				__( "Facultatif, selon le document que vous avez envoy&eacute; dans le premier champ.", 'yproject' ),
				$field_status_id2
			);
		}
		
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
		} else if ( !$this->is_orga && $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {

		// Analyse du formulaire
		} else {
			
			if ( $this->is_orga && $WDGUser_current->can_edit_organization( $user_id ) ) {
				$send_notification_validation = FALSE;
				$WDGOrganization = new WDGOrganization( $user_id );
				$file_suffix = '-orga-' . $WDGOrganization->get_wpref();
				if ( $WDGOrganization->can_register_lemonway() ) {
					$WDGOrganization->register_lemonway();
				}
			
				if ( isset( $_FILES[ 'identity' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity' .$file_suffix ][ 'tmp_name' ] ) ) {
					$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'identity' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id, $WDGFile->get_byte_array() );
					$send_notification_validation = TRUE;
				}
				if ( isset( $_FILES[ 'home' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'home' .$file_suffix ][ 'tmp_name' ] ) ) {
					WDGKYCFile::add_file( WDGKYCFile::$type_home, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'home' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_home, $WDGFile->get_byte_array() );
					$send_notification_validation = TRUE;
				}
				if ( isset( $_FILES[ 'status' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'status' .$file_suffix ][ 'tmp_name' ] ) ) {
					WDGKYCFile::add_file( WDGKYCFile::$type_status, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'status' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_status, $WDGFile->get_byte_array() );
					$send_notification_validation = TRUE;
				}
				if ( isset( $_FILES[ 'kbis' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'kbis' .$file_suffix ][ 'tmp_name' ] ) ) {
					WDGKYCFile::add_file( WDGKYCFile::$type_kbis, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'kbis' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_kbis, $WDGFile->get_byte_array() );
					$send_notification_validation = TRUE;
				}
				if ( isset( $_FILES[ 'capital_allocation' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'capital_allocation' .$file_suffix ][ 'tmp_name' ] ) ) {
					WDGKYCFile::add_file( WDGKYCFile::$type_id_2, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'capital_allocation' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_capital_allocation, $WDGFile->get_byte_array() );
				}
				if ( isset( $_FILES[ 'identity2' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity2' .$file_suffix ][ 'tmp_name' ] ) ) {
					WDGKYCFile::add_file( WDGKYCFile::$type_id_2, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'identity2' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id2, $WDGFile->get_byte_array() );
				}
				if ( isset( $_FILES[ 'home2' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'home2' .$file_suffix ][ 'tmp_name' ] ) ) {
					WDGKYCFile::add_file( WDGKYCFile::$type_home_2, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'home2' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_home2, $WDGFile->get_byte_array() );
				}
				if ( isset( $_FILES[ 'identity3' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity3' .$file_suffix ][ 'tmp_name' ] ) ) {
					WDGKYCFile::add_file( WDGKYCFile::$type_id_3, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'identity3' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id3, $WDGFile->get_byte_array() );
				}
				if ( isset( $_FILES[ 'home3' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'home3' .$file_suffix ][ 'tmp_name' ] ) ) {
					WDGKYCFile::add_file( WDGKYCFile::$type_home_3, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'home3' .$file_suffix ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_home3, $WDGFile->get_byte_array() );
				}
				if ( $send_notification_validation ) {
					NotificationsAPI::kyc_waiting( $WDGOrganization->get_email(), $WDGOrganization->get_name() );
				}
				
			} else {
				if ( $WDGUser->can_register_lemonway() ) {
					$WDGUser->register_lemonway();
				}
				
				$send_notification_validation = FALSE;
				if ( isset( $_FILES[ 'identity' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity' ][ 'tmp_name' ] ) ) {
					$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'identity' ] );
					$WDGFile = new WDGKYCFile( $file_id );
					LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id, $WDGFile->get_byte_array() );
					$send_notification_validation = TRUE;
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
					$send_notification_validation = TRUE;
				}
				if ( $send_notification_validation ) {
					NotificationsAPI::kyc_waiting( $WDGUser->get_email(), $WDGUser->get_firstname() );
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