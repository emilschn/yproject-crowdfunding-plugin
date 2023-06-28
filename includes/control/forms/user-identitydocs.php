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
	private $send_notification_validation;
	private $files_by_md5;
	private $duplicates;
	private $current_filelist;
	// Sauvegarde du type de la première pièce d'identité pour se prémunir de problèmes d'affichage
	private $user_first_slot_type;

	public function __construct($user_id = FALSE, $is_orga = FALSE, $invest_campaign_id = FALSE) {
		parent::__construct( self::$name );
		$this->user_id = $user_id;
		$this->is_orga = $is_orga;
		$this->invest_campaign_id = $invest_campaign_id;
		$this->nb_file_sent = 0;
		$this->initFields();
	}

	protected function initOneField($wallet_id, $WDGUserOrOrganization, $owner_type, $field_group, $type, $lw_type, $label, $description, $index_api = 1, $for_admin = FALSE) {
		$suffix = ( $this->is_orga ) ? '-orga-' . $WDGUserOrOrganization->get_wpref() : '';
		
		$file_path = FALSE;
		$file_date_uploaded = FALSE;
		$is_api_file = FALSE;
		$is_file_sent = TRUE;
		$is_orga_with_full_info = TRUE;
		
		if ( $this->is_orga ){
			$is_authentified = $WDGUserOrOrganization->is_registered_lemonway_wallet();
			$is_orga_with_full_info = $WDGUserOrOrganization->can_register_lemonway();
		}else{
			$is_authentified = $WDGUserOrOrganization->is_lemonway_registered();
		}
		if ( !empty( $this->current_filelist ) ) {
			// on cherche dans la liste de tous les fichiers de l'utilisateur un fichier correspondant
			$types_api = array( $type );
			if ( $owner_type === 'organization' ) {
				if ( $type == WDGKYCFile::$type_id ){
					$types_api[] = WDGKYCFile::$type_id;
					$types_api[] = WDGKYCFile::$type_passport;
				}
				if ( $type == WDGKYCFile::$type_idbis ){
					$types_api[] = WDGKYCFile::$type_id;
					$types_api[] = WDGKYCFile::$type_passport;
					$types_api[] = WDGKYCFile::$type_tax;
					$types_api[] = WDGKYCFile::$type_welfare;
					$types_api[] = WDGKYCFile::$type_family;
					$types_api[] = WDGKYCFile::$type_birth;
					$types_api[] = WDGKYCFile::$type_driving;
				}
				if ( $type == WDGKYCFile::$type_id_2 ){
					$types_api[] = WDGKYCFile::$type_person2_doc1;
				}
				if ( $type == WDGKYCFile::$type_idbis_2 ){
					$types_api[] = WDGKYCFile::$type_person2_doc2;
				}
				if ( $type == WDGKYCFile::$type_id_3 ){
					$types_api[] = WDGKYCFile::$type_person3_doc1;
				}
				if ( $type == WDGKYCFile::$type_idbis_3 ){
					$types_api[] = WDGKYCFile::$type_person3_doc2;
				}

			} else {
				$types_api[] = WDGKYCFile::$type_id;
				$types_api[] = WDGKYCFile::$type_passport;
				if ( $type == WDGKYCFile::$type_id_back ){
					$index_api = 2;
				}

				if ( $type == WDGKYCFile::$type_id_2 ){
					$types_api[] = WDGKYCFile::$type_tax;
					$types_api[] = WDGKYCFile::$type_welfare;
					$types_api[] = WDGKYCFile::$type_family;
					$types_api[] = WDGKYCFile::$type_birth;
					$types_api[] = WDGKYCFile::$type_driving;
				}

				if ( $type == WDGKYCFile::$type_id_2_back ){
					$types_api[] = WDGKYCFile::$type_tax;
					$types_api[] = WDGKYCFile::$type_welfare;
					$types_api[] = WDGKYCFile::$type_family;
					$types_api[] = WDGKYCFile::$type_birth;
					$types_api[] = WDGKYCFile::$type_driving;
					$index_api = 2;
				}

				if ( $type == WDGKYCFile::$type_criminal_record ) {
					$types_api[] = WDGKYCFile::$type_criminal_record;
				}
			}

			// Parcourir la liste, vérifier le type s'il est précisé
			foreach ( $this->current_filelist as $key => $kycfile_item ) {
				// un fichier sur le site a exactement le même type
				if ( $kycfile_item->is_api_file == FALSE && $kycfile_item->type == $type ) {
					$current_file = $kycfile_item;
					// supprimer $current_file de la liste pour ne pas afficher 2 fois le même KYC
					unset( $this->current_filelist[ $key ] );
					break;

				// pour un fichier sur l'API, on doit regarder parmi une liste de type et également le doc_index
				} else if ( $kycfile_item->is_api_file && ( in_array( $kycfile_item->type, $types_api ) && $kycfile_item->doc_index == $index_api ) ) {
					// Si il y a un ordre de placement à respecter (personne physique)
					$confirm_add_field = true;
					if ( !$this->is_orga ) {
						if ( empty( $this->user_first_slot_type ) ) {
							$this->user_first_slot_type = $kycfile_item->type;
						} else {
							if ( $index_api == 2 && $this->user_first_slot_type != $kycfile_item->type && $type == WDGKYCFile::$type_id_back ) {
								$confirm_add_field = false;
							}
						}
					}

					if ( $confirm_add_field ) {
						$current_file = $kycfile_item;
						unset( $this->current_filelist[ $key ] );
						break;
					}
				}
			}

			if ( isset( $current_file ) ) {
				$file_path = $current_file->get_public_filepath();
				$file_date_uploaded = $current_file->date_uploaded;
				$this->addToMD5Array( $type, $current_file->get_byte_array_md5() );
				// si c'est un fichier sur l'API, le "vrai type" est enregistré, et on veut le montrer
				if ( $current_file->is_api_file == TRUE ) {
					$api_type = $current_file->type;
				}
				// enregistrer l'id du fichier en hidden dans le field pour le récupérer facilement en postForm
				$kycfile_id = $current_file->id;
				$is_api_file = $current_file->is_api_file;
				$is_file_sent = !empty( $current_file->gateway_user_id ) || !empty( $current_file->gateway_organization_id );
				// Reessaie d'envoyer le fichier
				if ($is_api_file && !$is_file_sent) {
					$current_file->send_and_reload();
					$is_file_sent = !empty( $current_file->gateway_user_id ) || !empty( $current_file->gateway_organization_id );
				}
			}
		}

		if ($is_api_file) {
			$lw_type = LemonwayDocument::get_lw_document_id_from_document_type($api_type, $index_api);
		}

		$field_id_params = $this->getParamByFileField( $wallet_id, $lw_type, $file_date_uploaded, $type, $this->is_orga, $api_type, $kycfile_id, $is_api_file, $is_authentified, $is_file_sent, $is_orga_with_full_info );
		if ( $for_admin == FALSE ){
			$this->addField( 'file', $type . $suffix, $label, $field_group, $file_path, $description, $field_id_params );
		} else {
			// si l'utilisateur courant est un admin, et qu'il y a un fichier fusionné, on le montre
			$WDGUser_current = WDGUser::current();
			if ( $WDGUser_current->is_admin() ) {
				// on rajoute le thème admin
				$field_id_params[ 'admin_theme' ] = 1;
				// si c'est un fichier fusionné, on ne le fait apparaitre que si le fichier existe
				if ( $index_api != 0 || ($index_api == 0 && isset( $current_file ) && $is_api_file)){
					$field_id_params[ 'display_upload' ] = FALSE;
					$this->addField('file', $type . $suffix, $label, $field_group, $file_path, $description, $field_id_params);
				}				
			}
		}	
	}

	protected function initFields() {
		parent::initFields();

		$this->files_by_md5 = array();

		if ( !$this->is_orga || !empty( $this->invest_campaign_id ) ) {
			$this->addField('hidden', 'action', '', WDG_Form_User_Identity_Docs::$field_group_hidden, WDG_Form_User_Identity_Docs::$name);
		}

		$this->addField('hidden', 'user_id', '', WDG_Form_User_Identity_Docs::$field_group_hidden, $this->user_id);

		$wallet_id = FALSE;

		if ( $this->is_orga ) {
			$this->current_filelist = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_organization );	
		
			$WDGOrganization = new WDGOrganization( $this->user_id );
			$wallet_id = $WDGOrganization->get_lemonway_id();
			// initialisation du champ "première pièce d'identité"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_id, LemonwayDocument::$document_type_id, __( 'form.user-identitydocs.ID_OF_PRESIDENT', 'yproject' ) . ' *', __( 'form.user-identitydocs.ID_DESCRIPTION', 'yproject' ));

			// initialisation du champ "deuxième pièce d'identité"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_idbis, LemonwayDocument::$document_type_idbis, __( 'form.user-identitydocs.SECOND_ID_ORGA', 'yproject' ) . ' *', __( 'form.user-identitydocs.SECOND_ID_ORGA_DESCRIPTION', 'yproject' ));

			// initialisation du champ "kbis"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_kbis, LemonwayDocument::$document_type_kbis, __( 'form.user-identitydocs.KBIS', 'yproject' ) . ' *', __( 'form.user-identitydocs.KBIS_DESCRIPTION', 'yproject' ));

			// initialisation du champ "status"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_status, LemonwayDocument::$document_type_status, __( 'form.user-identitydocs.STATUS', 'yproject' ) . ' *', '');

			// initialisation du champ "capital allocation"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_capital_allocation, LemonwayDocument::$document_type_capital_allocation, __( 'form.user-identitydocs.CAPITAL_ALLOCATION', 'yproject' ), __( 'form.user-identitydocs.CAPITAL_ALLOCATION_DESCRIPTION', 'yproject' ));

			// initialisation du champ "première pièce d'identité de la deuxième personne"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_id_2, LemonwayDocument::$document_type_id2, __( 'form.user-identitydocs.ID_SECOND_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), __( 'form.user-identitydocs.ID_SECOND_PERSON_DESCRIPTION', 'yproject' ));

			// initialisation du champ "deuxième pièce d'identité de la deuxième personne"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_idbis_2, LemonwayDocument::$document_type_idbis2, __( 'form.user-identitydocs.SECOND_ID_SECOND_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), __( 'form.user-identitydocs.SECOND_ID_SECOND_PERSON_DESCRIPTION', 'yproject' ));

			// initialisation du champ "première pièce d'identité de la troisième personne"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_id_3, LemonwayDocument::$document_type_id3, __( 'form.user-identitydocs.ID_THIRD_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), __( 'form.user-identitydocs.ID_THIRD_PERSON_DESCRIPTION', 'yproject' ));

			// initialisation du champ "deuxième pièce d'identité de la troisième personne"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_idbis_3, LemonwayDocument::$document_type_idbis3, __( 'form.user-identitydocs.SECOND_ID_THIRD_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), __( 'form.user-identitydocs.SECOND_ID_THIRD_PERSON_DESCRIPTION', 'yproject' ));

			// initialisation du champ "première pièce d'identité de la quatrième personne"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_person4_doc1, LemonwayDocument::$document_type_person4_doc1, __( 'form.user-identitydocs.ID_FOURTH_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), __( 'form.user-identitydocs.ID_THIRD_PERSON_DESCRIPTION', 'yproject' ));

			// initialisation du champ "deuxième pièce d'identité de la quatrième personne"
			$this->initOneField($wallet_id, $WDGOrganization, WDGKYCFile::$owner_organization, WDG_Form_User_Identity_Docs::$field_group_files_orga, WDGKYCFile::$type_person4_doc2, LemonwayDocument::$document_type_person4_doc2, __( 'form.user-identitydocs.SECOND_ID_FOURTH_PERSON', 'yproject' ).' '.__( 'form.user-identitydocs.OPTIONAL', 'yproject' ), __( 'form.user-identitydocs.SECOND_ID_THIRD_PERSON_DESCRIPTION', 'yproject' ));

		} else {
			$this->current_filelist = WDGKYCFile::get_list_by_owner_id( $this->user_id, WDGKYCFile::$owner_user );
			$this->current_slots = array(
				array( 1 => FALSE, 2 => FALSE ),
				array( 1 => FALSE, 2 => FALSE )
			);

			$WDGUser = new WDGUser( $this->user_id );
			$wallet_id = $WDGUser->get_lemonway_id();

			// initialisation du champ "première pièce d'identité"
			$this->initOneField($wallet_id, $WDGUser, WDGKYCFile::$owner_user, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_id, LemonwayDocument::$document_type_id, __( 'form.user-identitydocs.ID', 'yproject' ) . ' *', __( 'form.user-identitydocs.ID_DESCRIPTION', 'yproject' ) );

			// initialisation du champ "verso de la première pièce d'identité"
			$this->initOneField($wallet_id, $WDGUser, WDGKYCFile::$owner_user, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_id_back, LemonwayDocument::$document_type_id_back, __( 'form.user-identitydocs.ID_BACK', 'yproject' ), __( 'form.user-identitydocs.ID_BACK_DESCRIPTION', 'yproject' ) );

			// initialisation du champ administrateur "première pièce d'identité, version fusionnée"
			$this->initOneField($wallet_id, $WDGUser, WDGKYCFile::$owner_user, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_id, LemonwayDocument::$document_type_id, 'Première pièce d\'identité fusionnée', 'Les recto et verso fusionnés de la première pièce d\'identité', 0, TRUE );

			// initialisation du champ "deuxième pièce d'identité"
			$this->initOneField($wallet_id, $WDGUser, WDGKYCFile::$owner_user, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_id_2, LemonwayDocument::$document_type_idbis, __( 'form.user-identitydocs.SECOND_ID', 'yproject' ) . ' *', __( 'form.user-identitydocs.SECOND_ID_DESCRIPTION_1', 'yproject' ). '<br>'
			. __( 'form.user-identitydocs.SECOND_ID_DESCRIPTION_2', 'yproject' ). '<br>'
			. __( 'form.user-identitydocs.SECOND_ID_DESCRIPTION_3', 'yproject' ));

			// initialisation du champ "verso de la deuxième pièce d'identité"
			$this->initOneField($wallet_id, $WDGUser, WDGKYCFile::$owner_user, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_id_2_back, LemonwayDocument::$document_type_idbis_back, __( 'form.user-identitydocs.SECOND_ID_BACK', 'yproject' ), __( 'form.user-identitydocs.SECOND_ID_BACK_DESCRIPTION', 'yproject' ) );
			
			// initialisation du champ administrateur "deuxième pièce d'identité, version fusionnée"
			$this->initOneField($wallet_id, $WDGUser, WDGKYCFile::$owner_user, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_id_2, LemonwayDocument::$document_type_idbis, 'Deuxième pièce d\'identité fusionnée', 'Les recto et verso fusionnés de la deuxième pièce d\'identité', 0, TRUE );

			// casier judiciaire si porteur de projet
			if ( $WDGUser->is_project_owner() ) {
				$this->initOneField($wallet_id, $WDGUser, WDGKYCFile::$owner_user, WDG_Form_User_Identity_Docs::$field_group_files, WDGKYCFile::$type_criminal_record, FALSE, __( 'form.user-identitydocs.CRIMINAL_RECORD', 'yproject' ), __( 'form.user-identitydocs.CRIMINAL_RECORD_DESCRIPTION', 'yproject' ) );
			}

			// Activation des notifications par téléphone
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

	protected function addOneField( $WDGUserOrOrganization, $type, $owner_type ) {
		$file_suffix = ( $this->is_orga ) ? '-orga-' . $WDGUserOrOrganization->get_wpref() : '';
		$select_value = $this->getInputText( 'select-' . $type . $file_suffix );
		$preview_value = $this->getInputText( 'hidden-preview-' . $type . $file_suffix );
		$kycfile_id = $this->getInputText( 'hidden-id-' . $type . $file_suffix );
		$api_file = $this->getInputText( 'hidden-api-file-' . $type . $file_suffix );
		
		// s'il y a un nouveau fichier à envoyer, on l'envoie
		if ( isset( $_FILES[ $type .$file_suffix ][ 'tmp_name' ] ) && !empty( $_FILES[ $type .$file_suffix ][ 'tmp_name' ] ) ) {
			$list_accepted_types = array( 'image/jpeg', 'image/gif', 'image/png', 'application/pdf' );
			if ( in_array( $_FILES[ $type .$file_suffix ][ 'type' ], $list_accepted_types ) && ( $_FILES[ $type .$file_suffix ][ 'size' ] / 1024) / 1024 < 8 ) {
				$this->nb_file_sent++;
				$api_type = $type;
				if ( $select_value !== null && $select_value != '' ) {
					$api_type = $select_value;
				} else {
					$select_value = FALSE;
				}
	
				$file_id = WDGKYCFile::add_file( $type, $WDGUserOrOrganization->get_wpref(), $owner_type, $_FILES[ $type .$file_suffix ] , '', $select_value );
	
				if ( is_int( $file_id ) ) {
					if ( $WDGUserOrOrganization->can_register_lemonway() ) {
						$this->send_notification_validation = TRUE;
					}
				} else {
					$this->addError( $file_id, $api_type );
				}

			} else {
				$this->addError( 'EXT', $type );
			}
		
		// s'il n'y a pas un nouveau fichier à envoyer, mais que le type du select n'est pas "l'ancien type de base" ni indéfini, ni vide évidemment
		// et qu'il existe un fichier (c'est à dire une preview) (forcément non-authentifié puisqu'on a une valeur de select)
		// on considère qu'on a changé le type du fichier pour le définir
		} else if ( $select_value !== null && $select_value != '' && $select_value != 'undefined' && $select_value != $type 
				&&  $preview_value !== null && $preview_value != '') {
			
			// on récupère le fichier concerné grâce à son id
			$KYCfile = new WDGKYCFile( $kycfile_id, $api_file );
			// on vérifie si c'est un fichier déjà sur l'API ou pas
			if ( $KYCfile->is_api_file == TRUE || $KYCfile->is_api_file == '1' ) {
				// si c'est un fichier déjà sur l'API, alors on change juste son type (devrait rarement arriver)
				$KYCfile->type = $select_value;
				$KYCfile->save();
			} else {
				// si c'est un fichier sur site, on le transfère sur l'API, en enregistrant son type
				WDGKYCFile::transfer_file_to_api( $KYCfile, $owner_type, $select_value );
			}
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
		} else {
			if ( !$this->is_orga && $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {
				// Analyse du formulaire
			} else {
				$this->send_notification_validation = FALSE;
				if ( $this->is_orga && $WDGUser_current->can_edit_organization( $user_id ) ) {
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

					$this->addOneField($WDGOrganization, WDGKYCFile::$type_id, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_idbis, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_status, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_kbis, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_capital_allocation, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_id_2, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_idbis_2, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_id_3, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_idbis_3, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_person4_doc1, WDGKYCFile::$owner_organization);
					$this->addOneField($WDGOrganization, WDGKYCFile::$type_person4_doc2, WDGKYCFile::$owner_organization);

					if ( $this->send_notification_validation && $WDGOrganization->is_registered_lemonway_wallet() ) {
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

					$this->addOneField($WDGUser, WDGKYCFile::$type_id, WDGKYCFile::$owner_user);
					$this->addOneField($WDGUser, WDGKYCFile::$type_id_back, WDGKYCFile::$owner_user);
					$this->addOneField($WDGUser, WDGKYCFile::$type_id_2, WDGKYCFile::$owner_user);
					$this->addOneField($WDGUser, WDGKYCFile::$type_id_2_back, WDGKYCFile::$owner_user);
					$this->addOneField($WDGUser, WDGKYCFile::$type_criminal_record, WDGKYCFile::$owner_user);

					if ( $this->send_notification_validation && $WDGUser->is_lemonway_registered() ) {
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
			'errors'	=> $this->getPostErrors()
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
					$str_duplicate .= strtolower(WDGKYCFile::convert_type_id_to_str ($doc_type, $this->is_orga));
					$nb++;
				}
				array_push( $this->duplicates, $str_duplicate );
			}
		}
	}

	public function getDuplicates() {
		return $this->duplicates;
	}

	private function addError( $result, $element ) {
		switch ( $result ) {
			case 'EXT':
				$this->addPostError( 'forms.file.ERROR_EXT', __( 'forms.file.ERROR_EXT', 'yproject' ), $element );
				break;
			case 'SERVER':
				$this->addPostError( 'forms.file.ERROR_SERVER', __( 'forms.file.ERROR_SERVER', 'yproject' ), $element );
				break;
			case 'UPLOAD':
				$this->addPostError( 'forms.file.ERROR_UPLOAD', __( 'forms.file.ERROR_UPLOAD', 'yproject' ), $element );
				break;
			case 'SIZE':
				$this->addPostError( 'forms.file.ERROR_SIZE', __( 'forms.file.ERROR_SIZE', 'yproject' ), $element );
				break;
		}
	}
}
