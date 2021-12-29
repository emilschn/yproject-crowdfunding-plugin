<?php
class WDG_Form_User_Identity_Docs extends WDG_Form {
	public static $name = 'user-identity-docs';

	public static $field_group_hidden = 'user-user-identity-docs-hidden';
	public static $field_group_files = 'user-user-identity-docs-files';
	public static $field_group_files_orga = 'user-user-identity-docs-files-orga';
	public static $field_group_phone_notification = 'user-user-identity-docs-phone-notification';
	public static $field_group_phone_number = 'user-user-identity-docs-phone-number';

	private $user_id;
	private $is_orga;
	private $invest_campaign_id;
	private $nb_file_sent;
	private $files_by_md5;
	private $duplicates;

	public function __construct($user_id = FALSE, $is_orga = FALSE, $invest_campaign_id = FALSE) {
		parent::__construct( self::$name );
		$this->user_id = $user_id;
		$this->is_orga = $is_orga;
		$this->invest_campaign_id = $invest_campaign_id;
		$this->nb_file_sent = 0;
		$this->initFields();
	}

	protected function initFields() {
		parent::initFields();

		$this->files_by_md5 = array();

		// $field_group_hidden
		if ( !$this->is_orga || !empty( $this->invest_campaign_id ) ) {
			$this->addField('hidden', 'action', '', WDG_Form_User_Identity_Docs::$field_group_hidden, WDG_Form_User_Identity_Docs::$name);
		}

		$this->addField('hidden', 'user_id', '', WDG_Form_User_Identity_Docs::$field_group_hidden, $this->user_id);

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
		$id_file_path = '';
		$id_file_date_uploaded = '';
		if ( !empty( $current_filelist_id ) ) {
			$current_file_id = $current_filelist_id[0];
			$id_file_path = ( empty( $current_file_id ) ) ? '' : $current_file_id->get_public_filepath();
			$id_file_date_uploaded = $current_file_id->date_uploaded;
			$this->addToMD5Array( 'identity', $current_file_id->get_byte_array_md5() );
		}
		$id_label = ( $this->is_orga ) ? __( 'form.user-identitydocs.ID_OF_PRESIDENT', 'yproject' ) : __( 'form.user-identitydocs.ID', 'yproject' );
		$field_id_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_id, $id_file_date_uploaded );
		$this->addField('file', 'identity' .$suffix, $id_label . ' *', WDG_Form_User_Identity_Docs::$field_group_files, $id_file_path, __( 'form.user-identitydocs.ID_DESCRIPTION', 'yproject' ), $field_id_params);

		if ( $this->is_orga ) {
			$current_filelist_home = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_idbis );
			$home_file_path = '';
			$home_file_date_uploaded = '';
			if ( !empty( $current_filelist_home ) ) {
				$current_file_home = $current_filelist_home[0];
				$home_file_path = ( empty( $current_file_home ) ) ? '' : $current_file_home->get_public_filepath();
				$home_file_date_uploaded = $current_file_home->date_uploaded;
				$this->addToMD5Array( 'home', $current_file_home->get_byte_array_md5() );
			}
			$home_label = __( 'form.user-identitydocs.SECOND_ID_ORGA', 'yproject' );
			$field_home_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_idbis, $home_file_date_uploaded );
			$this->addField('file', 'home' .$suffix, $home_label . ' *', WDG_Form_User_Identity_Docs::$field_group_files, $home_file_path, __( 'form.user-identitydocs.SECOND_ID_ORGA_DESCRIPTION', 'yproject' ), $field_home_params);

			$current_filelist_kbis = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_kbis );
			$kbis_file_path = '';
			$kbis_file_date_uploaded = '';
			if ( !empty( $current_filelist_kbis ) ) {
				$current_file_kbis = $current_filelist_kbis[0];
				$kbis_file_path = ( empty( $current_file_kbis ) ) ? '' : $current_file_kbis->get_public_filepath();
				$kbis_file_date_uploaded = $current_file_kbis->date_uploaded;
				$this->addToMD5Array( 'kbis', $current_file_kbis->get_byte_array_md5() );
			}
			$field_kbis_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_kbis, $kbis_file_date_uploaded );
			$this->addField('file', 'kbis' .$suffix, __( 'form.user-identitydocs.KBIS', 'yproject' ) . ' *', WDG_Form_User_Identity_Docs::$field_group_files_orga, $kbis_file_path, __( 'form.user-identitydocs.KBIS_DESCRIPTION', 'yproject' ), $field_kbis_params);

			$current_filelist_status = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_status );
			$status_file_path = '';
			$status_file_date_uploaded = '';
			if ( !empty( $current_filelist_status ) ) {
				$current_file_status = $current_filelist_status[0];
				$status_file_path = ( empty( $current_file_status ) ) ? '' : $current_file_status->get_public_filepath();
				$status_file_date_uploaded = $current_file_status->date_uploaded;
				$this->addToMD5Array( 'status', $current_file_status->get_byte_array_md5() );
			}
			$field_status_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_status, $status_file_date_uploaded );
			$this->addField('file', 'status' .$suffix, __( 'form.user-identitydocs.STATUS', 'yproject' ) . ' *', WDG_Form_User_Identity_Docs::$field_group_files_orga, $status_file_path, '', $field_status_params);

			$current_filelist_capital_allocation = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_capital_allocation );
			$capital_allocation_file_path = '';
			$capital_allocation_file_date_uploaded = '';
			if ( !empty( $current_filelist_capital_allocation ) ) {
				$current_file_capital_allocation = $current_filelist_capital_allocation[0];
				$capital_allocation_file_path = ( empty( $current_file_capital_allocation ) ) ? '' : $current_file_capital_allocation->get_public_filepath();
				$capital_allocation_file_date_uploaded = $current_file_capital_allocation->date_uploaded;
				$this->addToMD5Array( 'capital_allocation', $current_file_capital_allocation->get_byte_array_md5() );
			}
			$field_status_capital_allocation = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_capital_allocation, $capital_allocation_file_date_uploaded );
			$this->addField('file', 'capital_allocation' .$suffix, __( 'form.user-identitydocs.CAPITAL_ALLOCATION', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), WDG_Form_User_Identity_Docs::$field_group_files_orga, $capital_allocation_file_path, __( 'form.user-identitydocs.CAPITAL_ALLOCATION_DESCRIPTION', 'yproject' ), $field_status_capital_allocation);

			$current_filelist_id_2 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_id_2 );
			$id2_file_path = '';
			$id2_file_date_uploaded = '';
			if ( !empty( $current_filelist_id_2 ) ) {
				$current_file_id_2 = $current_filelist_id_2[0];
				$id2_file_path = ( empty( $current_file_id_2 ) ) ? '' : $current_file_id_2->get_public_filepath();
				$id2_file_date_uploaded = $current_file_id_2->date_uploaded;
				$this->addToMD5Array( 'identity2', $current_file_id_2->get_byte_array_md5() );
			}
			$field_status_id_2 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_id2, $id2_file_date_uploaded );
			$this->addField('file', 'identity2' .$suffix, __( 'form.user-identitydocs.ID_SECOND_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), WDG_Form_User_Identity_Docs::$field_group_files_orga, $id2_file_path, __( 'form.user-identitydocs.ID_SECOND_PERSON_DESCRIPTION', 'yproject' ), $field_status_id_2);

			$current_filelist_home_2 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_idbis_2 );
			$home2_file_path = '';
			$home2_file_date_uploaded = '';
			if ( !empty( $current_filelist_home_2 ) ) {
				$current_file_home_2 = $current_filelist_home_2[0];
				$home2_file_path = ( empty( $current_file_home_2 ) ) ? '' : $current_file_home_2->get_public_filepath();
				$home2_file_date_uploaded = $current_file_home_2->date_uploaded;
				$this->addToMD5Array( 'home2', $current_file_home_2->get_byte_array_md5() );
			}
			$field_status_home_2 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_idbis2, $home2_file_date_uploaded );
			$this->addField('file', 'home2' .$suffix, __( 'form.user-identitydocs.SECOND_ID_SECOND_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), WDG_Form_User_Identity_Docs::$field_group_files_orga, $home2_file_path, __( 'form.user-identitydocs.SECOND_ID_SECOND_PERSON_DESCRIPTION', 'yproject' ), $field_status_home_2);

			$current_filelist_id_3 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_id_3 );
			$id3_file_path = '';
			$id3_file_date_uploaded = '';
			if ( !empty( $current_filelist_id_3 ) ) {
				$current_file_id_3 = $current_filelist_id_3[0];
				$id3_file_path = ( empty( $current_file_id_3 ) ) ? '' : $current_file_id_3->get_public_filepath();
				$id3_file_date_uploaded = $current_file_id_3->date_uploaded;
				$this->addToMD5Array( 'identity3', $current_file_id_3->get_byte_array_md5() );
			}
			$field_status_id_3 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_id3, $id3_file_date_uploaded );
			$this->addField('file', 'identity3' .$suffix, __( 'form.user-identitydocs.ID_THIRD_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), WDG_Form_User_Identity_Docs::$field_group_files_orga, $id3_file_path, __( 'form.user-identitydocs.ID_THIRD_PERSON_DESCRIPTION', 'yproject' ), $field_status_id_3);

			$current_filelist_home_3 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization, WDGKYCFile::$type_idbis_3 );
			$home3_file_path = '';
			$home3_file_date_uploaded = '';
			if ( !empty( $current_filelist_home_3 ) ) {
				$current_file_home_3 = $current_filelist_home_3[0];
				$home3_file_path = ( empty( $current_file_home_3 ) ) ? '' : $current_file_home_3->get_public_filepath();
				$home3_file_date_uploaded = $current_file_home_3->date_uploaded;
				$this->addToMD5Array( 'home3', $current_file_home_3->get_byte_array_md5() );
			}
			$field_status_home_3 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_idbis3, $home3_file_date_uploaded );
			$this->addField('file', 'home3' .$suffix, __( 'form.user-identitydocs.SECOND_ID_THIRD_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), WDG_Form_User_Identity_Docs::$field_group_files_orga, $home3_file_path, __( 'form.user-identitydocs.SECOND_ID_THIRD_PERSON_DESCRIPTION', 'yproject' ), $field_status_home_3);
		} else {
			$current_filelist_id_back = WDGKYCFile::get_list_by_owner_id( $this->user_id, ( $this->is_orga ) ? WDGKYCFile::$owner_organization : WDGKYCFile::$owner_user, WDGKYCFile::$type_id_back );
			$id_back_file_path = '';
			$id_back_file_date_uploaded = '';
			if ( !empty( $current_filelist_id_back ) ) {
				$current_file_id_back = $current_filelist_id_back[0];
				$id_back_file_path = ( empty( $current_file_id_back ) ) ? '' : $current_file_id_back->get_public_filepath();
				$id_back_file_date_uploaded = $current_file_id_back->date_uploaded;
				$this->addToMD5Array( 'identity_back', $current_file_id_back->get_byte_array_md5() );
			}
			$id_back_label = __( 'form.user-identitydocs.ID_BACK', 'yproject' );
			$field_id_back_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_id_back, $id_back_file_date_uploaded, TRUE );
			$this->addField('file', 'identity_back' .$suffix, $id_back_label, WDG_Form_User_Identity_Docs::$field_group_files, $id_back_file_path, __( 'form.user-identitydocs.ID_BACK_DESCRIPTION', 'yproject' ), $field_id_back_params);

			$current_filelist_id2 = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_user, WDGKYCFile::$type_id_2 );
			$id2_file_path = '';
			$id2_file_date_uploaded = '';
			if ( !empty( $current_filelist_id2 ) ) {
				$current_file_id2 = $current_filelist_id2[0];
				$id2_file_path = ( empty( $current_file_id2 ) ) ? '' : $current_file_id2->get_public_filepath();
				$id2_file_date_uploaded = $current_file_id2->date_uploaded;
				$this->addToMD5Array( 'identity2_user', $current_file_id2->get_byte_array_md5() );
			}
			$id2_label = __( 'form.user-identitydocs.SECOND_ID', 'yproject' );
			$field_status_id2 = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_idbis, $id2_file_date_uploaded );
			$this->addField('file', 'identity2', $id2_label . ' *', WDG_Form_User_Identity_Docs::$field_group_files, $id2_file_path, __( 'form.user-identitydocs.SECOND_ID_DESCRIPTION_1', 'yproject' ). '<br>'
					. __( 'form.user-identitydocs.SECOND_ID_DESCRIPTION_2', 'yproject' ). '<br>'
					. __( 'form.user-identitydocs.SECOND_ID_DESCRIPTION_3', 'yproject' ), $field_status_id2);

			$current_filelist_id2_back = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_user, WDGKYCFile::$type_id_2_back );
			$id2_back_file_path = '';
			$id2_back_file_date_uploaded = '';
			if ( !empty( $current_filelist_id2_back ) ) {
				$current_file_id2_back = $current_filelist_id2_back[0];
				$id2_back_file_path = ( empty( $current_file_id2_back ) ) ? '' : $current_file_id2_back->get_public_filepath();
				$id2_back_file_date_uploaded = $current_file_id2_back->date_uploaded;
				$this->addToMD5Array( 'identity2_back', $current_file_id2_back->get_byte_array_md5() );
			}
			$id2_back_label = __( 'form.user-identitydocs.SECOND_ID_BACK', 'yproject' );
			$field_status_id2_back = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_idbis_back, $id2_back_file_date_uploaded, TRUE );
			$this->addField('file', 'identity2_back', $id2_back_label, WDG_Form_User_Identity_Docs::$field_group_files, $id2_back_file_path, __( 'form.user-identitydocs.SECOND_ID_BACK_DESCRIPTION', 'yproject' ), $field_status_id2_back);

			$current_filelist_home = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_user, WDGKYCFile::$type_idbis );
			if ( !empty( $current_filelist_home ) ) {
				$current_file_home = $current_filelist_home[0];
			}
			if ( !empty( $current_file_home ) ) {
				$home_file_path = ( empty( $current_file_home ) ) ? '' : $current_file_home->get_public_filepath();
				$home_label = __( 'form.user-identitydocs.PROOF_ADDRESS', 'yproject' );
				$this->addToMD5Array( 'home_old', $current_file_home->get_byte_array_md5() );
				$field_home_params = $this->getParamByFileField( $wallet_id, LemonwayDocument::$document_type_home, $current_file_home->date_uploaded );
				if ( empty( $field_home_params[ 'message_instead_of_field' ] ) ) {
					$field_home_params[ 'message_instead_of_field' ] = __( 'form.user-identitydocs.PROOF_ADDRESS_ALERT', 'yproject' );
				}
				$this->addField('file', 'home' .$suffix, $home_label, WDG_Form_User_Identity_Docs::$field_group_files, $home_file_path, __( 'form.user-identitydocs.PROOF_ADDRESS_DESCRIPTION_1', 'yproject' ). '<br>'
						. __( 'form.user-identitydocs.PROOF_ADDRESS_DESCRIPTION_2', 'yproject' ), $field_home_params);
			}

			// Activation des notifications par téléphone
			$WDGUser = new WDGUser( $this->user_id );
			$values_has_checked_notification = $WDGUser->has_subscribed_authentication_notification();
			if ( $values_has_checked_notification ) {
				$values_has_checked_notification = array( '1' );
			}
			$this->addField('checkboxes', '', '', WDG_Form_User_Identity_Docs::$field_group_phone_notification, $values_has_checked_notification, FALSE, [
					'phone-notification' => __( 'form.user-identitydocs.ALERT_BY_SMS', 'yproject' )
				]);

			$this->addField('text', 'phone_number', __( 'form.user-identitydocs.PHONE_NUMBER', 'yproject' ), WDG_Form_User_Identity_Docs::$field_group_phone_number, $WDGUser->get_phone_number());
		}

		// Vérifications des doublons de fichiers
		$this->initDuplicates();
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
		} else {
			if ( !$this->is_orga && $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {
				// Analyse du formulaire
			} else {
				if ( $this->is_orga && $WDGUser_current->can_edit_organization( $user_id ) ) {
					$send_notification_validation = FALSE;
					$WDGOrganization = new WDGOrganization( $user_id );
					$file_suffix = '-orga-' . $WDGOrganization->get_wpref();
					$was_registered = $WDGOrganization->has_lemonway_wallet();
					if ( $WDGOrganization->can_register_lemonway() ) {
						$WDGOrganization->register_lemonway();
						// Si il n'était enregistré sur LW et qu'on vient de l'enregistrer, on envoie les documents si certains étaient déjà remplis
						if ( !$was_registered && $WDGOrganization->has_lemonway_wallet() ) {
							ypcf_debug_log( 'WDG_Form_User_Identity_Docs::postForm > $WDGOrganization->send_kyc();' );
							$WDGOrganization->send_kyc();
						}
					}
					if ( isset( $_FILES[ 'identity' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'identity' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGOrganization->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
								$send_notification_validation = TRUE;
							}
						}
					}
					if ( isset( $_FILES[ 'home' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'home' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_idbis, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'home' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGOrganization->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_idbis, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
								$send_notification_validation = TRUE;
							}
						}
					}
					if ( isset( $_FILES[ 'status' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'status' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_status, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'status' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGOrganization->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_status, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
								$send_notification_validation = TRUE;
							}
						}
					}
					if ( isset( $_FILES[ 'kbis' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'kbis' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_kbis, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'kbis' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGOrganization->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_kbis, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
								$send_notification_validation = TRUE;
							}
						}
					}
					if ( isset( $_FILES[ 'capital_allocation' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'capital_allocation' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id_2, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'capital_allocation' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_capital_allocation, $WDGFile->get_byte_array() );
							if ( !empty( $lw_id ) ) {
								$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
							}
						}
					}
					if ( isset( $_FILES[ 'identity2' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity2' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id_2, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'identity2' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGOrganization->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id2, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
							}
						}
					}
					if ( isset( $_FILES[ 'home2' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'home2' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_idbis_2, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'home2' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGOrganization->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_idbis2, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
							}
						}
					}
					if ( isset( $_FILES[ 'identity3' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity3' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id_3, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'identity3' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGOrganization->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id3, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
							}
						}
					}
					if ( isset( $_FILES[ 'home3' .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ 'home3' .$file_suffix ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_idbis_3, $user_id, WDGKYCFile::$owner_organization, $_FILES[ 'home3' .$file_suffix ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGOrganization->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGOrganization->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_idbis3, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
							}
						}
					}
					if ( $send_notification_validation && $WDGOrganization->is_registered_lemonway_wallet() ) {
						NotificationsAPI::kyc_waiting( $WDGOrganization );
					}

					if ( $this->nb_file_sent > 0 ) {
						$campaign_list = $WDGOrganization->get_campaigns();
						if ( !empty( $campaign_list ) ) {
							NotificationsSlack::send_document_uploaded_admin( $WDGOrganization, $this->nb_file_sent );
						}
					}
				} else {
					$was_registered = $WDGUser->has_lemonway_wallet();
					if ( $WDGUser->can_register_lemonway() ) {
						$WDGUser->register_lemonway();
						// Si il n'était enregistré sur LW et qu'on vient de l'enregistrer, on envoie les documents si certains étaient déjà remplis
						if ( !$was_registered && $WDGUser->has_lemonway_wallet() ) {
							ypcf_debug_log( 'WDG_Form_User_Identity_Docs::postForm > $WDGUser->send_kyc();' );
							$WDGUser->send_kyc();
						}
					}

					$send_notification_validation = FALSE;
					if ( isset( $_FILES[ 'identity' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity' ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id = WDGKYCFile::add_file( WDGKYCFile::$type_id, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'identity' ] );

						if ( is_int( $file_id ) ) {
							$WDGFile = new WDGKYCFile( $file_id );
							if ( $WDGUser->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
								$send_notification_validation = TRUE;
							}
						}
					}
					if ( isset( $_FILES[ 'identity_back' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity_back' ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id_back = WDGKYCFile::add_file( WDGKYCFile::$type_id_back, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'identity_back' ] );
						if ( is_int( $file_id_back ) ) {
							$WDGFile = new WDGKYCFile( $file_id_back );
							if ( $WDGUser->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_id_back, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
								$send_notification_validation = TRUE;
							}
						}
					}
					if ( isset( $_FILES[ 'identity2' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity2' ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id2 = WDGKYCFile::add_file( WDGKYCFile::$type_id_2, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'identity2' ] );
						if ( is_int( $file_id2 ) ) {
							$WDGFile = new WDGKYCFile( $file_id2 );
							if ( $WDGUser->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_idbis, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
							}
						}
					}
					if ( isset( $_FILES[ 'identity2_back' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'identity2_back' ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_id2_back = WDGKYCFile::add_file( WDGKYCFile::$type_id_2_back, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'identity2_back' ] );
						if ( is_int( $file_id2_back ) ) {
							$WDGFile = new WDGKYCFile( $file_id2_back );
							if ( $WDGUser->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_idbis_back, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
							}
						}
					}
					if ( isset( $_FILES[ 'home' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'home' ][ 'tmp_name' ] ) ) {
						$this->nb_file_sent++;
						$file_home = WDGKYCFile::add_file( WDGKYCFile::$type_idbis, $user_id, WDGKYCFile::$owner_user, $_FILES[ 'home' ] );
						if ( is_int( $file_home ) ) {
							$WDGFile = new WDGKYCFile( $file_home );
							if ( $WDGUser->can_register_lemonway() ) {
								$lw_id = LemonwayLib::wallet_upload_file( $WDGUser->get_lemonway_id(), $WDGFile->file_name, LemonwayDocument::$document_type_idbis, $WDGFile->get_byte_array() );
								if ( !empty( $lw_id ) ) {
									$WDGFile->set_gateway_id( WDGKYCFile::$gateway_lemonway, $lw_id );
								}
								$send_notification_validation = TRUE;
							}
						}
					}

					// Si l'utilisateur a des organisations,
					// on envoie les kycs qui viennent peut-être d'être mis à jour avec les pièces d'identité
					$orga_list = $WDGUser->get_organizations_list();
					if ( count( $orga_list ) > 0 ) {
						foreach ( $orga_list as $orga_item ) {
							$wdg_orga_item = new WDGOrganization( $orga_item->wpref );
							$wdg_orga_item->send_kyc();
						}
					}

					if ( $send_notification_validation && $WDGUser->is_lemonway_registered() ) {
						NotificationsAPI::kyc_waiting( $WDGUser );
					}

					$subscribe_authentication_notification = $this->getInputChecked( 'phone-notification' );
					$WDGUser->set_subscribe_authentication_notification( $subscribe_authentication_notification );
					if ( $WDGUser->has_subscribed_authentication_notification() ) {
						$phone_number = $this->getInputText( 'phone_number' );
						$WDGUser->save_phone_number( $phone_number );
					}
				}
			}
		}

		// Si il n'y a pas d'erreur et qu'on est dans le contexte d'investissement
		if ( empty( $feedback_errors ) && !empty( $this->invest_campaign_id ) ) {
			$WDGInvestment_current = WDGInvestment::current();
			$WDGInvestment_current->payment_return( WDGInvestment::$meanofpayment_unset );
		}

		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);

		$this->initFields(); // Reinit pour avoir les bonnes valeurs

		return $buffer;
	}

	public function getNbFileSent() {
		return $this->nb_file_sent;
	}

	/**
	 * Ajoute un type de fichier en correspondance à un md5
	 */
	private function addToMD5Array($type, $md5) {
		if ( !isset( $this->files_by_md5[ $md5 ] ) ) {
			$this->files_by_md5[ $md5 ] = array();
		}
		array_push( $this->files_by_md5[ $md5 ], $type );
	}

	/**
	 * Parcourt les données des fichiers pour déterminer les doublons
	 */
	private function initDuplicates() {
		$this->duplicates = array();
		foreach ( $this->files_by_md5 as $md5 => $type_list ) {
			if ( count( $type_list ) > 1 ) {
				$str_duplicate = '';
				$nb = 0;
				foreach ( $type_list as $doc_type ) {
					if ( $nb > 0 ) {
						$str_duplicate .= __( ' et ', 'yproject' );
					}
					switch ( $doc_type ) {
						case 'identity':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.ID', 'yproject' ));
							break;
						case 'identity_back':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.ID_BACK', 'yproject' ));
							break;
						case 'home':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.SECOND_ID', 'yproject' ));
							break;
						case 'kbis':
							$str_duplicate .= strtolower(__( 'lemonway.document.type.KBIS', 'yproject' ));
							break;
						case 'status':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.STATUS_SHORT', 'yproject' ));
							break;
						case 'capital_allocation':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.CAPITAL_ALLOCATION', 'yproject' ));
							break;
						case 'identity2':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.ID_SECOND_PERSON', 'yproject' ));
							break;
						case 'home2':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.SECOND_ID_SECOND_PERSON', 'yproject' ));
							break;
						case 'identity3':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.ID_THIRD_PERSON', 'yproject' ));
							break;
						case 'home3':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.SECOND_ID_THIRD_PERSON', 'yproject' ));
							break;
						case 'identity2_user':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.SECOND_ID', 'yproject' ));
							break;
						case 'identity2_back':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.SECOND_ID_BACK', 'yproject' ));
							break;
						case 'home_old':
							$str_duplicate .= strtolower(__( 'form.user-identitydocs.PROOF_ADDRESS', 'yproject' ));
							break;
					}
					$nb++;
				}
				array_push( $this->duplicates, $str_duplicate );
			}
		}
	}

	public function getDuplicates() {
		return $this->duplicates;
	}
}
