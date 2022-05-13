<?php

/**
 * Classe de gestion des appels Post et Get
 *
 * Ex d'utilisation dans un formulaire:
 * <form ..... action="<?php echo admin_url( 'admin-post.php?action=create_project_form'); ?>">
 */
class WDGPostActions {
	private static $class_name = 'WDGPostActions';

	/**
	 * Initialise la liste des actions post
	 */
	public static function init_actions() {
		self::add_action("subscribe_newsletter_sendinblue");
		self::add_action("send_project_mail");
		self::add_action("create_project_form");
		self::add_action("change_project_status");
		self::add_action("organization_sign_mandate");
		self::add_action("organization_remove_mandate");
		self::add_action("upload_information_files");
		self::add_action("add_contract_model");
		self::add_action("edit_contract_model");
		self::add_action("send_contract_model");
		self::add_action("generate_campaign_funded_certificate");
		self::add_action("generate_campaign_bill");
		self::add_action("generate_campaign_contracts_archive");
		self::add_action("generate_contract_files");
		self::add_action( 'generate_yearly_fiscal_documents' );
		self::add_action("upload_contract_files");
		self::add_action( 'send_project_contract_modification_notification' );
		self::add_action( 'send_project_notifications' );
		self::add_action( 'send_project_notifications_end_vote' );
		self::add_action( 'send_project_notifications_end' );
		self::add_action("cancel_token_investment");
		self::add_action("post_invest_check");
		self::add_action("post_confirm_check");
		self::add_action( 'declaration_auto_generate' );
		self::add_action( 'add_declaration_document' );
		self::add_action( 'add_adjustment' );
		self::add_action( 'edit_adjustment' );
		self::add_action("roi_mark_transfer_received");
		self::add_action("roi_cancel_transfer");
		self::add_action( 'generate_royalties_bill' );
		self::add_action( 'save_declaration_bill' );
		self::add_action( 'refund_investors' );
		self::add_action( 'mandate_b2b_admin_update' );

		self::add_action( 'user_account_organization_details' );
		self::add_action( 'user_account_organization_identitydocs' );
		self::add_action( 'user_account_organization_bank' );
		self::add_action( 'user_account_add_subscription' );
		self::add_action( 'user_account_validate_contract_subscription' );
		self::add_action( 'user_account_end_subscription' );
		self::add_action( 'remove_user_registered_card' );

		self::add_action( 'view_kyc_file' );
	}

	/**
	 * Ajoute une action WordPress à exécuter en Post/get
	 * @param string $action_name
	 */
	public static function add_action($action_name) {
		add_action( 'admin_post_' .$action_name, array( self::$class_name, $action_name ) );
		add_action( 'admin_post_nopriv_' .$action_name, array( self::$class_name, $action_name ) );
	}

	/**
	 * Formulaire d'ajout d'e-mail dans la NL
	 */
	public static function subscribe_newsletter_sendinblue($init_email = '') {
		$action = filter_input( INPUT_POST, 'action' );
		if ( ( !empty( $action ) && ( $action == 'subscribe_newsletter_sendinblue' ) ) || !empty( $init_email ) ) {
			$email = $init_email;
			if ( empty( $init_email ) ) {
				$email = sanitize_text_field( filter_input( INPUT_POST, 'subscribe-nl-mail' ) );
			}

			try {
				$sib_instance = SIBv3Helper::instance();
				$sib_instance->addContactToList( $email, 5 );
				$sib_instance->addContactToList( $email, 6 );
				$_SESSION['subscribe_newsletter_sendinblue'] = true;
			} catch ( Exception $e ) {
				ypcf_debug_log( "subscribe_newsletter_sendinblue > erreur d'inscription à la NL" );
			}

			if ( !empty( $action ) && ( $action == 'subscribe_newsletter_sendinblue' ) ) {
				wp_safe_redirect( wp_get_referer() );
				die();
			}
		}
	}

	public static function send_project_mail() {
		ypcf_debug_log( 'WDGPostActions::send_project_mail' );
		ypcf_debug_log( 'WDGPostActions::send_project_mail > mail_recipients : ' .filter_input( INPUT_POST, 'mail_recipients' ) );
		global $wpdb;
		$campaign_id = sanitize_text_field( filter_input( INPUT_POST, 'campaign_id' ) );
		$post_campaign = get_post( $campaign_id );
		$campaign = new ATCF_Campaign( $campaign_id );
		$mail_title = sanitize_text_field( filter_input( INPUT_POST, 'mail_title' ) );
		$mail_content = nl2br( filter_input( INPUT_POST, 'mail_content' ) );
		$mail_recipients = explode( ',', filter_input( INPUT_POST, 'mail_recipients' ) );

		$author_user = get_user_by( 'ID', $post_campaign->post_author );
		$reply_to_email = $author_user->user_email;
		$current_user = WDGUser::current();
		if ( $current_user->is_admin() ) {
			$reply_to_email = 'bonjour@wedogood.co';
		}

		global $wpdb;
		$table_vote = $wpdb->prefix . "ypcf_project_votes";
		$list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum FROM ".$table_vote." WHERE post_id = ".$campaign_id." AND validate_project = 1", OBJECT_K);

		foreach ( $mail_recipients as $id_user ) {
			if ( is_numeric( $id_user ) ) {
				//TODO : Re-vérifier si l'utilisateur peut bien envoyer à la personne (vérifier si dans la liste des suiveurs/votants/investisseurs)
				$user = get_userdata( intval( $id_user ) );
				$WDGUser = new WDGUser($id_user);
				$to = $user->user_email;
				$user_data = array(
					'userfirstname'	=> $user->first_name,
					'userlastname'	=> $user->last_name,
					'investwish'	=> 0
				);
				if ( isset( $list_user_voters[ $id_user ] ) && isset( $list_user_voters[ $id_user ]->invest_sum ) ) {
					$user_data[ 'investwish' ] = $list_user_voters[ $id_user ]->invest_sum;
				}

				$this_mail_content = WDGFormProjects::build_mail_text( $mail_content, $mail_title, $campaign_id, $user_data );

				NotificationsAPI::project_mail( $to, $reply_to_email, $WDGUser, $user->first_name, $campaign, $post_campaign->post_title, get_permalink( $campaign_id ), $campaign->get_api_id(), $mail_title, $this_mail_content['body'] );
			}
		}

		wp_safe_redirect( wp_get_referer()."&send_mail_success=1#contacts" );
		die();
	}

	public static function create_project_form() {
		ypcf_debug_log( 'create_project_form > $_POST > ' . print_r($_POST, true), TRUE );
		$WDGUser_current = WDGUser::current();
		$WPuserID = $WDGUser_current->wp_user->ID;

		$new_lastname = sanitize_text_field(filter_input(INPUT_POST, 'lastname'));
		$new_firstname = sanitize_text_field(filter_input(INPUT_POST, 'firstname'));
		$new_phone = sanitize_text_field(filter_input(INPUT_POST, 'phone'));

		$orga_name = sanitize_text_field(filter_input(INPUT_POST, 'company-name'));
		$orga_email = sanitize_text_field( filter_input( INPUT_POST, 'email-organization' ) );
		$project_name = sanitize_text_field(filter_input(INPUT_POST, 'project-name'));
		$project_terms = filter_input( INPUT_POST, 'project-terms' );

		$result = array(
			'user_display_name'	=> '0',
			'has_error'	=> '0',
			'error_str'	=> ''
		);

		//User data
		if (!empty($new_firstname)) {
			wp_update_user( array( 'ID' => $WPuserID, 'first_name' => $new_firstname ) );
		}
		if (!empty($new_lastname)) {
			wp_update_user( array( 'ID' => $WPuserID, 'last_name' => $new_lastname ) );
		}
		if (!empty($new_phone)) {
			update_user_meta( $WPuserID, 'user_mobile_phone', $new_phone );
		}

		if ( !empty( $new_firstname ) && !empty( $new_lastname ) && !empty( $new_phone )
				&& !empty($orga_name) && !empty($project_name) && !empty($project_terms)  && (is_email( $orga_email ) || is_numeric( $orga_name )) ) {
			//On commence par essayer de créer l'organisation d'abord
			//Si organisation déjà liée à l'utilisateur, on récupère le wpref de l'orga (selcet du formulaire)
			//sinon si aucune organisation, elle est créée à la volée à la création du projet
			$success = true;
			$orga_api_id = FALSE;

			if ( is_numeric( $orga_name ) ) {
				$existing_orga = new WDGOrganization($orga_name);
				$orga_api_id = $existing_orga->get_api_id();

			//Si on sélectionne "new_orga", il faut prendre le champ texte qui est apparu
			} else {
				if ( $orga_name == 'new_orga' ) {
					$orga_name = sanitize_text_field( filter_input( INPUT_POST, 'new-company-name' ) );
					if ( !empty( $orga_name ) ) {
						$organization_created = WDGOrganization::createSimpleOrganization( $WPuserID, $orga_name, $orga_email );
						if ( $organization_created != false ) {
							$orga_api_id = $organization_created->get_api_id();
						} else {
							$success = false;
							$result['has_error'] = '1';
							$result['error_str'] = 'existing_orga_error';
						}
					}

					//Sinon, si c'était directement un texte, on crée l'organisation
				} else {
					if ( !empty( $orga_name ) ) {
						$organization_created = WDGOrganization::createSimpleOrganization( $WPuserID, $orga_name, $orga_email );
						if ( $organization_created != false ) {
							$orga_api_id = $organization_created->get_api_id();
						} else {
							$success = false;
							$result['has_error'] = '1';
							$result['error_str'] = 'errors_create_orga';
						}

						//Sinon on arrête la procédure
					} else {
						$success = false;
						$result['has_error'] = '1';
						$result['error_str'] = 'orga_error';
					}
				}
			}

			if ( $success && !empty( $orga_api_id ) ) {
				//Project data
				$_SESSION[ 'newproject-errors' ] = FALSE;
				$newcampaign_id = atcf_create_campaign($WPuserID, $project_name);
				$newcampaign = atcf_get_campaign($newcampaign_id);

				$newcampaign->__set( 'campaign_contact_phone', $new_phone );
				$newcampaign->set_api_data( 'minimum_profit', 1.15 );
				$newcampaign->set_forced_mandate( 1 );
				$contract_mandate = WDGConfigTexts::get_config_text_by_name( WDGConfigTexts::$type_term_mandate, 'contract_mandate' );
				$newcampaign->__set( ATCF_Campaign::$key_mandate_conditions, $contract_mandate );
				$newcampaign->link_organization( $orga_api_id );
				$newcampaign->update_api();

				//Mail pour l'équipe
				NotificationsSlack::send_new_project( $newcampaign_id, $orga_name );
				NotificationsAsana::send_new_project( $newcampaign_id, $orga_name );

				//Redirect then
				$dashboard_url = WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$newcampaign_id;

				//Mail pour le PP
				NotificationsAPI::new_project_published( $WDGUser_current, $newcampaign );

				WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$newcampaign->get_api_id(). '?with_investments=1&with_organization=1&with_poll_answers=1' );
				$test_campaign = new ATCF_Campaign( $newcampaign_id );
				$test_organization = $test_campaign->get_organization();
				if ( empty( $test_organization ) ) {
					$error_content = 'Aucune organisation liée au projet';
					NotificationsEmails::new_project_posted_error_admin( $project_name, $error_content );
					$result['has_error'] = '1';
					$result['error_str'] = 'project_no_orga_linked';
				}

				$redirect_url = $dashboard_url ."&lightbox=newproject";
				$result['url_redirect'] = $redirect_url;
			} else {
				global $errors_submit_new, $errors_create_orga;
				$_SESSION[ 'newproject-errors-submit' ] = $errors_submit_new;
				$_SESSION[ 'newproject-errors-orga' ] = $errors_create_orga;
				$result['has_error'] = '1';
				if ($errors_submit_new) {
					$result['errors_submit_new'] = array();
					foreach ( $errors_submit_new as $error) {
						$result['errors_submit_new'][] = html_entity_decode($error);
					}
				}
				if ($errors_create_orga) {
					$result['errors_create_orga'] = array();
					foreach ( $errors_create_orga as $error) {
						$result['errors_create_orga'][] = html_entity_decode($error);
					}
				}
			}
		} else {
			$result['has_error'] = '1';
			$result['error_str'] = 'empty_or_wrong_format_field';
		}

		return $result;
	}

	public static function change_project_status() {
		$campaign_id = sanitize_text_field(filter_input(INPUT_POST, 'campaign_id'));
		$campaign = atcf_get_campaign($campaign_id);
		$status = $campaign->campaign_status();
		$can_modify = $campaign->current_user_can_edit();
		$is_admin = WDGUser::current()->is_admin();

		$next_status = filter_input(INPUT_POST, 'next_status');

		if ($can_modify
            && !empty($next_status)
            && ($next_status==1 || $next_status==2)) {
			$save_validation_steps = filter_input( INPUT_POST, 'validation-next-save' );

			if ( $status == ATCF_Campaign::$campaign_status_preparing && $is_admin ) {
				$validate_next_step = filter_input( INPUT_POST, 'validation-next-validate' );
				//Préparation -> sauvegarde coches
				if ( $save_validation_steps == '1' ) {
					$has_filled_desc = filter_input( INPUT_POST, 'validation-step-has-filled-desc' );
					$campaign->set_validation_step_status( 'has_filled_desc', $has_filled_desc );
					$has_filled_finance = filter_input( INPUT_POST, 'validation-step-has-filled-finance' );
					$campaign->set_validation_step_status( 'has_filled_finance', $has_filled_finance );
					$has_filled_parameters = filter_input( INPUT_POST, 'validation-step-has-filled-parameters' );
					$campaign->set_validation_step_status( 'has_filled_parameters', $has_filled_parameters );
					$has_signed_order = filter_input( INPUT_POST, 'validation-step-has-signed-order' );
					$campaign->set_validation_step_status( 'has_signed_order', $has_signed_order );

				//Préparation -> Validé (pour les admin seulement)
				} else {
					if ( $validate_next_step == '1' ) {
						$campaign->set_status(ATCF_Campaign::$campaign_status_validated);
						$campaign->set_validation_next_status(0);
					}
				}

				//Enregistrement avant passage en vote
			} else {
				if ( $status == ATCF_Campaign::$campaign_status_validated && $save_validation_steps == '1' ) {
					$has_filled_presentation = filter_input( INPUT_POST, 'validation-step-has-filled-presentation' );
					$campaign->set_validation_step_status( 'has_filled_presentation', $has_filled_presentation );
				} else {
					if ($campaign->can_go_next_status()) {
						if ( $status == ATCF_Campaign::$campaign_status_validated && $next_status == 1 ) {
							//Validé -> Avant-première
							$campaign->set_status(ATCF_Campaign::$campaign_status_preview);
							$campaign->set_validation_next_status(0);
						} else {
							if (
					$status == ATCF_Campaign::$campaign_status_preview
                    || ( $status == ATCF_Campaign::$campaign_status_validated && !$campaign->skip_vote() && $next_status == 2 ) ) {
								//Validé/Avant-première -> Vote

								//Vérifiation organisation complète
								$orga_done=false;
								$campaign_organization = $campaign->get_organization();

								//Vérification validation lemonway
								$organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
								if ($organization_obj->is_registered_lemonway_wallet()) {
									$orga_done = true;
								}

								//Validation données
								if ($orga_done && ypcf_check_user_is_complete($campaign->post_author())&& isset($_POST['innbdayvote'])) {
									$vote_time = $_POST['innbdayvote'];
									if (10<=$vote_time && $vote_time<=45) {
										//Fixe date fin de vote
										$diffVoteDay = new DateInterval('P'.$vote_time.'D');
										$VoteEndDate = (new DateTime())->add($diffVoteDay);
										$campaign->set_end_vote_date($VoteEndDate);
										$campaign->set_status(ATCF_Campaign::$campaign_status_vote);
										$campaign->set_validation_next_status(0);
										$organization_obj->check_register_campaign_lemonway_wallet();
										$campaign->copy_default_contract_if_empty();
										NotificationsSlack::send_new_project_status( $campaign_id, ATCF_Campaign::$campaign_status_vote );
										NotificationsAsana::send_new_project_status( $campaign_id, ATCF_Campaign::$campaign_status_vote );
										//Activation des conseils pour 3 jours après le passage en évaluation
										WDGQueue::add_campaign_advice_notification( $campaign_id );

										// Mise à jour cache
										do_action('wdg_delete_cache', array(
								'cache_campaign_' . $campaign_id
							));
										$file_cacher = WDG_File_Cacher::current();
										$file_cacher->build_campaign_page_cache( $campaign_id );
									}
								}
							} else {
								if (
					( $status == ATCF_Campaign::$campaign_status_validated && $campaign->skip_vote() && $next_status == 2 )
					|| $status == ATCF_Campaign::$campaign_status_vote ) {
									//Vote -> Collecte
									if (isset($_POST['innbdaycollecte'])
                        && isset($_POST['inendh'])
                        && isset($_POST['inendm'])) {
										//Recupere nombre de jours et heure de fin de la collecte
										$collecte_time = $_POST['innbdaycollecte'];
										$collecte_fin_heure = $_POST['inendh'];
										$collecte_fin_minute = $_POST['inendm'];

										if ( 1<=$collecte_time && $collecte_time<=60
                            && 0<=$collecte_fin_heure && $collecte_fin_heure<=23
                            && 0<=$collecte_fin_minute && $collecte_fin_minute<=59) {
											//Fixe la date de fin de collecte
											$diffCollectDay = new DateInterval('P'.$collecte_time.'D');
											$CollectEndDate = (new DateTime())->add($diffCollectDay);
											$CollectEndDate->setTime($collecte_fin_heure, $collecte_fin_minute);
											$campaign->set_end_date($CollectEndDate);
											// Si on n'est pas passé par la phase d'évaluation, on met à jour la date de fin d'évaluation pour ne pas faire bugger les stats
											if ( $status == ATCF_Campaign::$campaign_status_validated && $campaign->skip_vote() ) {
												$campaign->set_end_vote_date( new DateTime() );
											}
											//Activation des conseils pour 3 jours après le passage en investissement si on n'est pas passé par l'évaluation, ou si les conseils d'évaluation se sont arrêtés
											$queued_action_id = $campaign->has_planned_advice_notification();
											if ( $queued_action_id == FALSE ) {
												WDGQueue::add_campaign_advice_notification( $campaign_id );
											}
											$campaign->set_begin_collecte_date(new DateTime());
											$campaign->set_status(ATCF_Campaign::$campaign_status_collecte);
											$campaign->set_validation_next_status(0);
											$campaign->copy_default_contract_if_empty();

											$campaign_organization = $campaign->get_organization();
											$organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
											$organization_obj->check_register_campaign_lemonway_wallet();

											NotificationsSlack::send_new_project_status( $campaign_id, ATCF_Campaign::$campaign_status_collecte );
											NotificationsAsana::send_new_project_status( $campaign_id, ATCF_Campaign::$campaign_status_collecte );
											WDGQueue::add_preinvestments_validation( $campaign_id );

											// Mise à jour cache
											do_action('wdg_delete_cache', array(
								'cache_campaign_' . $campaign_id
							));
											$file_cacher = WDG_File_Cacher::current();
											$file_cacher->build_campaign_page_cache( $campaign_id );
										}
									}
								}
							}
						}
					}
				}
			}
		}
		$campaign->update_api();

		do_action('wdg_delete_cache', array(
			'home-projects',
			'projectlist-projects-current',
			'projectlist-projects-funded'
		));
		$file_cacher = WDG_File_Cacher::current();
		$file_cacher->build_campaign_page_cache( $campaign->ID );
		wp_safe_redirect(wp_get_referer());
		die();
	}

	/**
	 * Redirige vers la signature de mandat d'autorisation de prélèvement automatique
	 */
	public static function organization_sign_mandate() {
		$organization_id = sanitize_text_field( filter_input( INPUT_POST, 'organization_id' ) );
		$WDGUser_current = WDGUser::current();
		$phone_number = $WDGUser_current->get_phone_number();
		$url_return = wp_get_referer() . '&has_signed_mandate=1';
		$url_error = wp_get_referer() . '&has_signed_mandate=0';

		// Récupération de l'organisation
		$organization_obj = new WDGOrganization( $organization_id );
		$token = $organization_obj->get_sign_mandate_token( $phone_number, $url_return, $url_error );

		if ( $token != FALSE ) {
			// Redirection vers la page de signature de document
			wp_redirect( YP_LW_WEBKIT_URL .'?signingToken='. $token->SIGNDOCUMENT->TOKEN );
			die();
		}

		wp_redirect( $url_return );
		die();
	}

	public static function organization_remove_mandate() {
		$WDGUser_current = WDGUser::current();
		if ( $WDGUser_current->is_admin() ) {
			$organization_id = sanitize_text_field( filter_input( INPUT_POST, 'organization_id' ) );
			$mandate_id = sanitize_text_field( filter_input( INPUT_POST, 'mandate_id' ) );
			$WDGOrganization = new WDGOrganization( $organization_id );
			$WDGOrganization->remove_lemonway_mandate( $mandate_id );
			$WDGOrganization->add_lemonway_mandate();
		}

		wp_redirect( wp_get_referer() );
		die();
	}

	public static function upload_information_files() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);

		$file_uploaded_data = $_FILES['new_backoffice_businessplan'];
		$file_name = $file_uploaded_data['name'];
		$file_name_exploded = explode('.', $file_name);
		$ext = $file_name_exploded[count($file_name_exploded) - 1];

		$random_filename = '';
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$size = strlen( $chars );
		for ( $i = 0; $i < 15; $i++ ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		while ( file_exists( __DIR__ . '/../../kyc/' . $random_filename . '.' . $ext ) ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		$random_filename .= '.' . $ext;
		move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../../kyc/' . $random_filename );

		$campaign->__set( ATCF_Campaign::$key_backoffice_businessplan, $random_filename );

		$url_return = wp_get_referer() . "#campaign";
		wp_redirect( $url_return );
		die();
	}

	public static function add_contract_model() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$model_name = filter_input( INPUT_POST, 'contract_model_name' );
			$model_content = filter_input( INPUT_POST, 'contract_model_content' );
			WDGWPREST_Entity_ContractModel::create( $campaign->get_api_id(), 'project', 'investment_amendment', $model_name, $model_content );
		}

		$url_return = wp_get_referer() . "#contracts";
		wp_redirect( $url_return );
		die();
	}

	public static function edit_contract_model() {
		$WDGUser_current = WDGUser::current();
		$contract_model_id = filter_input( INPUT_POST, 'contract_edit_model_id' );
		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $contract_model_id ) ) {
			$model_name = filter_input( INPUT_POST, 'contract_edit_model_name' );
			$model_content = filter_input( INPUT_POST, 'contract_edit_model_content' );
			WDGWPREST_Entity_ContractModel::edit( $contract_model_id, $model_name, $model_content );
		}

		$url_return = wp_get_referer() . "#contracts";
		wp_redirect( $url_return );
		die();
	}
	public static function send_contract_model() {
		$WDGUser_current = WDGUser::current();
		$contract_model_id = filter_input( INPUT_GET, 'model' );
		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $contract_model_id ) ) {
			global $shortcode_campaign_obj, $shortcode_organization_obj, $shortcode_organization_creator;
			// On récupère l'objet modèle, pour récupérer la campagne correspondante
			$contract_model = WDGWPREST_Entity_ContractModel::get( $contract_model_id );
			$campaign_api_id = $contract_model->entity_id;
			$campaign = new ATCF_Campaign( FALSE, $campaign_api_id );
			$shortcode_campaign_obj = $campaign;
			$campaign_orga = $campaign->get_organization();
			$shortcode_organization_obj = new WDGOrganization( $campaign_orga->wpref, $campaign_orga );
			$campaign_orga_linked_users = $shortcode_organization_obj->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			$shortcode_organization_creator = $campaign_orga_linked_users[0];

			// On récupère la liste des investissements
			$payment_list = $campaign->payments_data();
			foreach ( $payment_list as $payment_item ) {
				if ( $payment_item[ 'status' ] == 'publish' ) {
					$payment_id = $payment_item[ 'ID' ];
					ypcf_debug_log( 'send_contract_model > ' . $payment_id );
					// Si le fichier n'existe pas, créer un fichier et sauvegarder dans meta amendment_file_ID
					$meta_payment_amendment_file = get_post_meta( $payment_id, 'amendment_file_' . $contract_model_id, TRUE );
					if ( empty( $meta_payment_amendment_file ) ) {
						ypcf_debug_log( 'send_contract_model > $meta_payment_amendment_file : ' . $meta_payment_amendment_file );
						$buffer = __DIR__. '/../../pdf_files/tmp';
						if ( !is_dir( $buffer ) ) {
							mkdir( $buffer, 0777, true );
						}
						$filepath = $buffer. '/' .$contract_model_id. '-' .$payment_id. '.pdf';
						ypcf_debug_log( 'send_contract_model > $filepath : ' . $filepath );

						global $shortcode_investor_user_obj, $shortcode_investor_orga_obj;
						$shortcode_investor_user_obj = new WDGUser( $payment_item['user'] );
						$shortcode_investor_orga_obj = FALSE;
						if ( WDGOrganization::is_user_organization( $payment_item['user'] ) ) {
							$shortcode_investor_orga_obj = new WDGOrganization( $payment_item['user'] );
							$user_by_email = get_user_by( 'email', $payment_item['email'] );
							$shortcode_investor_user_obj = new WDGUser( $user_by_email->ID );
						}
						WDG_PDF_Generator::add_shortcodes();
						add_filter( 'WDG_PDF_Generator_filter', 'wptexturize' );
						add_filter( 'WDG_PDF_Generator_filter', 'wpautop' );
						add_filter( 'WDG_PDF_Generator_filter', 'shortcode_unautop' );
						add_filter( 'WDG_PDF_Generator_filter', 'do_shortcode' );
						$html_content = apply_filters( 'WDG_PDF_Generator_filter', $contract_model->model_content );
						ypcf_debug_log( 'send_contract_model > $html_content : ' . $html_content );

						generatePDF( $html_content, $filepath );
						$byte_array = file_get_contents( $filepath );
						$file_create_item = WDGWPREST_Entity_File::create( $payment_id, 'investment', 'amendment', 'pdf', base64_encode( $byte_array ) );
						update_post_meta( $payment_id, 'amendment_file_' . $contract_model_id, $file_create_item->id );
					}

					// TODO : remplacer par Eversign ?
					// Si le contrat n'existe pas sur Signsquid, créer un contrat electronique sur Signsquid dans meta amendment_signsquid_ID
					/*$meta_payment_amendment_signsquid = get_post_meta( $payment_id, 'amendment_signsquid_' . $contract_model_id, TRUE );
					if ( empty( $meta_payment_amendment_signsquid ) ) {
						ypcf_debug_log( 'send_contract_model > $meta_payment_amendment_signsquid : ' . $meta_payment_amendment_signsquid );
						ypcf_debug_log( 'send_contract_model > $payment_item[user] : ' . $payment_item['user'] );
						$WDGUser = new WDGUser( $payment_item['user'] );
						$user_name = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname();
						$user_email = $WDGUser->get_email();
						if ( WDGOrganization::is_user_organization( $WDGUser->get_wpref() ) ) {
							$WDGOrganization = new WDGOrganization( $WDGUser->get_wpref() );
							$user_name = $WDGOrganization->get_name();
						}
						$contract_name = $contract_model->model_name;
						$mobile_phone = null;
						if ( ypcf_check_user_phone_format( $WDGUser->get_phone_number() ) ) {
							$mobile_phone = ypcf_format_french_phonenumber( $WDGUser->get_phone_number() );
						}
						$meta_payment_amendment_signsquid = signsquid_create_contract( $contract_name );
						signsquid_add_signatory( $meta_payment_amendment_signsquid, $user_name, $user_email, $mobile_phone );
						signsquid_add_file( $meta_payment_amendment_signsquid, $filepath );
						signsquid_send_invite( $meta_payment_amendment_signsquid );
						update_post_meta( $payment_id, 'amendment_signsquid_' . $contract_model_id, $meta_payment_amendment_signsquid );

						$new_contract_infos = signsquid_get_contract_infos( $meta_payment_amendment_signsquid );
						if ( isset( $new_contract_infos ) && isset( $new_contract_infos->{'signatories'}[0]->{'code'} ) ) {
							NotificationsEmails::send_new_contract_code_user( $user_name, $user_email, $contract_name, $new_contract_infos->{'signatories'}[0]->{'code'} );
						}
					}

					// Si le contrat n'existe pas sur l'API, créer le contrat correspondant sur l'API et sauvegarder dans meta amendment_contract_ID
					$meta_payment_amendment_contract = get_post_meta( $payment_id, 'amendment_contract_' . $contract_model_id, TRUE );
					if ( empty( $meta_payment_amendment_contract ) && !empty( $meta_payment_amendment_signsquid ) ) {
						ypcf_debug_log( 'send_contract_model > $meta_payment_amendment_contract : ' . $meta_payment_amendment_contract );
						$api_contract_item = WDGWPREST_Entity_Contract::create( $contract_model_id, 'investment', $payment_id, 'Signsquid', $meta_payment_amendment_signsquid );
						update_post_meta( $payment_id, 'amendment_contract_' . $contract_model_id, $api_contract_item->id );
					}*/
				}
			}

			WDGWPREST_Entity_ContractModel::update_status( $contract_model_id, 'sent' );
		}

		$url_return = wp_get_referer() . "#contracts";
		wp_redirect( $url_return );
		die();
	}

	public static function generate_campaign_funded_certificate() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$date_end = filter_input( INPUT_POST, 'date_end' );
		$free_field = filter_input( INPUT_POST, 'free_field' );
		$additionnal_fees = filter_input( INPUT_POST, 'additionnal_fees' );
		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			if ( empty( $additionnal_fees ) ) {
				$additionnal_fees = 0;
			}
			$campaign->make_funded_certificate( TRUE, $date_end, $free_field, $additionnal_fees );
		}

		$url_return = wp_get_referer() . "#documents";
		wp_redirect( $url_return );
		die();
	}

	public static function generate_campaign_bill() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$campaign_bill = new WDGCampaignBill( $campaign, WDGCampaignBill::$tool_name_quickbooks, WDGCampaignBill::$bill_type_crowdfunding_commission );
			if ( $campaign_bill->can_generate() ) {
				$campaign_bill->generate();
			}
		}

		$url_return = wp_get_referer() . "#documents";
		wp_redirect( $url_return );
		die();
	}

	public static function generate_campaign_contracts_archive() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
		}

		$zip = new ZipArchive;
		$zip_path = dirname( __FILE__ ). '/../../../files/contracts/' .$campaign_id. '-' .$campaign->data->post_name. '.zip';
		if ( file_exists( $zip_path ) ) {
			unlink( $zip_path );
		}
		$res = $zip->open( $zip_path, ZipArchive::CREATE );
		if ( $res === TRUE ) {
			$exp = dirname( __FILE__ ). '/../../pdf_files/' .$campaign_id. '_*.pdf';
			$files = glob( $exp );
			foreach ( $files as $file ) {
				$file_path_exploded = explode( '/', $file );
				$contract_filename = $file_path_exploded[ count( $file_path_exploded ) - 1 ];
				$res_addFile = $zip->addFile( $file, $contract_filename );
				if ( $res_addFile !== TRUE ) {
					ypcf_debug_log( 'post.php :: generate_campaign_contracts_archive > Error: Unable to add file '.$file.' $contract_filename = '.$contract_filename);
				}
			}
			$zip->close();
		} else {
			ypcf_debug_log( 'post.php :: generate_campaign_contracts_archive > Error: Unable to create zip file '.$zip_path.' $res = '.$res);
		}

		$url_return = wp_get_referer() . "#documents";
		wp_redirect( $url_return );
		die();
	}

	public static function generate_contract_files() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign_locale = filter_input(INPUT_POST, 'campaign_locale');
		if ( !empty( $campaign_locale ) && $campaign_locale != 'fr' && $campaign_locale != 'fr_FR' ) {
			WDG_Languages_Helpers::switch_to_temp_language( $campaign_locale );
		}
		$campaign = new ATCF_Campaign($campaign_id);
		$campaign->generate_contract_pdf_blank_organization();
		$url_return = wp_get_referer() . "#contracts";
		wp_redirect( $url_return );
		die();
	}

	public static function generate_yearly_fiscal_documents() {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );

		if ( !empty( $campaign_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_control( 'fiscal/documents' );
			$fiscal_year = filter_input( INPUT_POST, 'fiscal_year' );
			$init = filter_input( INPUT_POST, 'init' );
			if ( empty( $init ) ) {
				$init = 1;
			}
			$campaign_year = array(
				$campaign_id => $fiscal_year
			);
			WDG_FiscalDocuments::generate( $campaign_year, $init );
			$url_return = wp_get_referer() . "#documents";
		} else {
			$url_return = wp_get_referer() . "#error";
		}

		wp_redirect( $url_return );
		die();
	}

	public static function upload_contract_files() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);

		$file_uploaded_data = $_FILES['new_backoffice_contract_orga'];
		$file_name = $file_uploaded_data['name'];
		if (!empty($file_name)) {
			$file_name_exploded = explode('.', $file_name);
			$ext = $file_name_exploded[count($file_name_exploded) - 1];
			$random_filename = '';
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			$size = strlen( $chars );
			for ( $i = 0; $i < 15; $i++ ) {
				$random_filename .= $chars[ rand( 0, $size - 1 ) ];
			}
			while ( file_exists( __DIR__ . '/../../contracts/' . $random_filename . '.' . $ext ) ) {
				$random_filename .= $chars[ rand( 0, $size - 1 ) ];
			}
			$random_filename .= '.' . $ext;
			move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../../contracts/' . $random_filename );
			$campaign->__set( ATCF_Campaign::$key_backoffice_contract_orga, $random_filename );
		}

		$new_project_agreement_bundle = filter_input( INPUT_POST, 'new_project_agreement_bundle' );
		if ( !empty( $new_project_agreement_bundle ) ) {
			$campaign->__set( ATCF_Campaign::$key_agreement_bundle, $new_project_agreement_bundle );
		}

		// Enregistrement description des revenus
		// On commence par enregistrer l'id de référence si il y en a
		$new_project_contract_earnings_description_configtext_post_id = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_earnings_description_configtext_post_id' ) );
		$campaign->__set( ATCF_Campaign::$key_contract_earnings_description_configtext_post_id, $new_project_contract_earnings_description_configtext_post_id );
		// Si on a choisi un texte personnalisé (pour garder d'éventuelles anciennes versions), on prend le texte
		if ( $new_project_contract_earnings_description_configtext_post_id == 'custom' ) {
			$new_project_contract_earnings_description = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_earnings_description' ) );
			if ( !empty( $new_project_contract_earnings_description ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_earnings_description, $new_project_contract_earnings_description );
				$campaign->set_api_data( 'earnings_description', $new_project_contract_earnings_description );
			}
			// Si on a choisi un texte de configuration, on enregistre le titre si jamais on y accède par ailleurs
		} else {
			$post_contract_earnings_description = get_post( $new_project_contract_earnings_description_configtext_post_id );
			if ( !empty( $post_contract_earnings_description ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_earnings_description, 'config::' . $post_contract_earnings_description->post_title );
				$campaign->set_api_data( 'earnings_description', 'config::' . $post_contract_earnings_description->post_title );
			}
		}

		// Enregistrement informations simples
		// On commence par enregistrer l'id de référence si il y en a
		$new_project_contract_simple_info_configtext_post_id = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_simple_info_configtext_post_id' ) );
		$campaign->__set( ATCF_Campaign::$key_contract_simple_info_configtext_post_id, $new_project_contract_simple_info_configtext_post_id );
		// Si on a choisi un texte personnalisé (pour garder d'éventuelles anciennes versions), on prend le texte
		if ( $new_project_contract_simple_info_configtext_post_id == 'custom' ) {
			$new_project_contract_simple_info = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_simple_info' ) );
			if ( !empty( $new_project_contract_simple_info ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_simple_info, $new_project_contract_simple_info );
				$campaign->set_api_data( 'simple_info', $new_project_contract_simple_info );
			}
			// Si on a choisi un texte de configuration, on enregistre le titre si jamais on y accède par ailleurs
		} else {
			$post_contract_simple_info = get_post( $new_project_contract_simple_info_configtext_post_id );
			if ( !empty( $post_contract_simple_info ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_simple_info, 'config::' . $post_contract_simple_info->post_title );
				$campaign->set_api_data( 'simple_info', 'config::' . $post_contract_simple_info->post_title );
			}
		}

		// Enregistrement informations détaillées
		// On commence par enregistrer l'id de référence si il y en a
		$new_project_contract_detailed_info_configtext_post_id = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_detailed_info_configtext_post_id' ) );
		$campaign->__set( ATCF_Campaign::$key_contract_detailed_info_configtext_post_id, $new_project_contract_detailed_info_configtext_post_id );
		// Si on a choisi un texte personnalisé (pour garder d'éventuelles anciennes versions), on prend le texte
		if ( $new_project_contract_detailed_info_configtext_post_id == 'custom' ) {
			$new_project_contract_detailed_info = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_detailed_info' ) );
			if ( !empty( $new_project_contract_detailed_info ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_detailed_info, $new_project_contract_detailed_info );
				$campaign->set_api_data( 'detailed_info', $new_project_contract_detailed_info );
			}
			// Si on a choisi un texte de configuration, on enregistre le titre si jamais on y accède par ailleurs
		} else {
			$post_contract_detailed_info = get_post( $new_project_contract_detailed_info_configtext_post_id );
			if ( !empty( $post_contract_detailed_info ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_detailed_info, 'config::' . $post_contract_detailed_info->post_title );
				$campaign->set_api_data( 'detailed_info', 'config::' . $post_contract_detailed_info->post_title );
			}
		}

		// Enregistrement prime
		// On commence par enregistrer l'id de référence si il y en a
		$new_project_contract_premium_configtext_post_id = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_premium_configtext_post_id' ) );
		$campaign->__set( ATCF_Campaign::$key_contract_premium_configtext_post_id, $new_project_contract_premium_configtext_post_id );
		// Si on a choisi un texte personnalisé (pour garder d'éventuelles anciennes versions), on prend le texte
		if ( $new_project_contract_premium_configtext_post_id == 'custom' ) {
			$new_project_contract_premium = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_premium' ) );
			if ( !empty( $new_project_contract_premium ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_premium, $new_project_contract_premium );
			}
			// Si on a choisi un texte de configuration, on enregistre le titre si jamais on y accède par ailleurs
		} else {
			$post_contract_premium = get_post( $new_project_contract_premium_configtext_post_id );
			if ( !empty( $post_contract_premium ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_premium, 'config::' . $post_contract_premium->post_title );
			}
		}

		// Enregistrement garantie
		// On commence par enregistrer l'id de référence si il y en a
		$new_project_contract_warranty_configtext_post_id = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_warranty_configtext_post_id' ) );
		$campaign->__set( ATCF_Campaign::$key_contract_warranty_configtext_post_id, $new_project_contract_warranty_configtext_post_id );
		// Si on a choisi un texte personnalisé (pour garder d'éventuelles anciennes versions), on prend le texte
		if ( $new_project_contract_warranty_configtext_post_id == 'custom' ) {
			$new_project_contract_warranty = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_warranty' ) );
			if ( !empty( $new_project_contract_warranty ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_warranty, $new_project_contract_warranty );
			}
			// Si on a choisi un texte de configuration, on enregistre le titre si jamais on y accède par ailleurs
		} else {
			$post_contract_warranty = get_post( $new_project_contract_warranty_configtext_post_id );
			if ( !empty( $post_contract_warranty ) ) {
				$campaign->__set( ATCF_Campaign::$key_contract_warranty, 'config::' . $post_contract_warranty->post_title );
			}
		}

		$new_contract_budget_type = filter_input( INPUT_POST, 'new_contract_budget_type' );
		$campaign->__set( ATCF_Campaign::$key_contract_budget_type, $new_contract_budget_type );

		$new_contract_maximum_type = filter_input( INPUT_POST, 'new_contract_maximum_type' );
		$campaign->__set( ATCF_Campaign::$key_contract_maximum_type, $new_contract_maximum_type );

		$new_quarter_earnings_estimation_type = filter_input( INPUT_POST, 'new_quarter_earnings_estimation_type' );
		$campaign->__set( ATCF_Campaign::$key_quarter_earnings_estimation_type, $new_quarter_earnings_estimation_type );

		$new_override_contract = filter_input( INPUT_POST, 'new_override_contract' );
		$campaign->__set( ATCF_Campaign::$key_override_contract, $new_override_contract );

		$campaign->update_api();

		$url_return = wp_get_referer() . "#contracts";
		wp_redirect( $url_return );
		die();
	}

	public static function send_project_contract_modification_notification() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');

		if ( !empty( $campaign_id ) ) {
			WDGQueue::execute_preinvestments_validation( $campaign_id, FALSE, FALSE );
		}

		$url_return = wp_get_referer() . "#contracts";
		wp_redirect( $url_return );
		die();
	}

	public static function send_project_notifications($skip_redirect = false) {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$mail_type = filter_input( INPUT_POST, 'mail_type' );
		$input_testimony = filter_input( INPUT_POST, 'testimony' );
		$input_image_url = filter_input( INPUT_POST, 'image_url' );
		$input_image_description = filter_input( INPUT_POST, 'image_description' );
		$input_send_option = filter_input( INPUT_POST, 'send_option' );

		$result = false;
		if ( !empty( $campaign_id ) && !empty( $input_image_url ) && !empty( $input_image_description ) ) {
			if ( $mail_type == "investment-3days-post-cloture" || ( !empty( $input_testimony ) ) ){
				$result = WDGEmails::auto_notifications($campaign_id, $mail_type, $input_testimony, $input_image_url, $input_image_description, $input_send_option);
			}
		}

		if ( !$skip_redirect ) {
			$url_return = wp_get_referer() . "#contacts";
			wp_redirect( $url_return );
			die();
		} else {
			return $result;
		}
	}

	public static function send_project_notifications_end_vote($skip_redirect = false) {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$mail_type = filter_input( INPUT_POST, 'mail_type' );
		$input_send_option = filter_input( INPUT_POST, 'send_option' );

		$result = false;
		if ( !empty( $campaign_id ) && !empty( $mail_type ) ) {
			$result = WDGEmails::end_vote_notifications($campaign_id, $mail_type, $input_send_option);
		}

		if ( !$skip_redirect ) {
			$url_return = wp_get_referer() . "#contacts";
			wp_redirect( $url_return );
			die();
		} else {
			return $result;
		}
	}

	public static function send_project_notifications_end($skip_redirect = false) {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$mail_type = filter_input( INPUT_POST, 'mail_type' );
		$input_send_option = filter_input( INPUT_POST, 'send_option' );

		$result = false;
		if ( !empty( $campaign_id ) && !empty( $mail_type ) ) {
			$result = WDGEmails::end_notifications($campaign_id, $mail_type, $input_send_option);
		}

		if ( !$skip_redirect ) {
			$url_return = wp_get_referer() . "#contacts";
			wp_redirect( $url_return );
			die();
		} else {
			return $result;
		}
	}

	public static function cancel_token_investment() {
		$wdginvestment = WDGInvestment::current();
		$redirect_url = home_url();

		if ( $wdginvestment->has_token() ) {
			$wdginvestment->set_status( WDGInvestment::$status_canceled );
			$wdginvestment->post_token_notification();
			$reason = 'canceled';
			$post_reason = filter_input( INPUT_POST, 'reason' );
			if ( !empty( $post_reason ) ) {
				$reason = $post_reason;
			}
			$redirect_url = $wdginvestment->get_redirection( 'error', $reason );
		} else {
			$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
			if ( !empty( $campaign_id ) ) {
				$redirect_url = get_permalink( $campaign_id );
			}
		}

		wp_redirect( $redirect_url );
		exit();
	}

	public static function post_invest_check() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$WDGInvestment = WDGInvestment::current();
		$amount_total = $WDGInvestment->get_session_amount();
		$user_type_session = $WDGInvestment->get_session_user_type();
		$WDGUser_current = WDGUser::current();
		$WDGUserOrOrganization = $WDGUser_current;
		$invest_email = $WDGUser_current->get_email();
		if ( !empty( $user_type_session ) && $user_type_session != 'user' ) {
			$orga_creator = get_user_by( 'id', $user_type_session );
			$orga_email = $orga_creator->user_email;
			$investment_id = $campaign->add_investment('check', $invest_email, $amount_total, 'pending', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $orga_email);
			$WDGOrganization = new WDGOrganization( $user_type_session );
			$mail_name = $WDGUser_current->get_firstname();
			$WDGUserOrOrganization = $WDGOrganization;
		} else {
			$investment_id = $campaign->add_investment( 'check', $invest_email, $amount_total, 'pending' );
			$mail_name = $WDGUser_current->get_firstname();
		}

		$file_uploaded_data = $_FILES['check_picture'];
		$file_name = $file_uploaded_data['name'];
		$file_name_exploded = explode('.', $file_name);
		$ext = $file_name_exploded[ count( $file_name_exploded ) - 1];

		$random_filename = '';
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$size = strlen( $chars );
		for ( $i = 0; $i < 15; $i++ ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		while ( file_exists( __DIR__ . '/../../../files/investment-check/' . $random_filename . '.' . $ext ) ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		$random_filename .= '.' . $ext;
		$has_moved = move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../../../files/investment-check/' . $random_filename );
		$picture_url = site_url() . '/wp-content/plugins/appthemer-crowdfunding/files/investment-check/' . $random_filename;

		$WDGInvestment = new WDGInvestment( $investment_id );
		NotificationsAPI::investment_pending_check( $WDGUserOrOrganization, $WDGInvestment, $campaign );
		NotificationsSlack::new_purchase_pending_check_admin( $investment_id, $picture_url );
		NotificationsAsana::new_purchase_pending_check_admin( $investment_id, $picture_url );

		// Annulation des investissements non-démarrés du même investisseur
		$pending_not_validated_investments = array();
		if ( !empty( $user_type_session ) && $user_type_session != 'user' ) {
			$pending_not_validated_investments = $WDGOrganization->get_pending_not_validated_investments();
		} else {
			$pending_not_validated_investments = $WDGUser_current->get_pending_not_validated_investments();
		}
		if ( !empty( $pending_not_validated_investments ) ) {
			foreach ( $pending_not_validated_investments as $pending_not_validated_investment_item ) {
				$pending_not_validated_investment_item->cancel();
			}
		}

		if ( $has_moved ) {
			update_post_meta( $investment_id, 'check_picture', $random_filename );
			wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'moyen-de-paiement' ) . '?campaign_id='.$campaign_id.'&meanofpayment=check&check-return=post_invest_check' );
		} else {
			wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'moyen-de-paiement' ) . '?campaign_id='.$campaign_id.'&meanofpayment=check&check-return=post_confirm_check&error-upload=1' );
		}
		exit();
	}

	public static function post_confirm_check() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$WDGInvestment = WDGInvestment::current();
		$amount_total = $WDGInvestment->get_session_amount();
		$user_type_session = $WDGInvestment->get_session_user_type();
		$WDGUser_current = WDGUser::current();
		$WDGUserOrOrganization = $WDGUser_current;
		$invest_email = $WDGUser_current->get_email();
		if ( !empty( $user_type_session ) && $user_type_session != 'user' ) {
			$orga_creator = get_user_by( 'id', $user_type_session );
			$orga_email = $orga_creator->user_email;
			$investment_id = $campaign->add_investment('check', $invest_email, $amount_total, 'pending', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $orga_email);
			$WDGOrganization = new WDGOrganization( $user_type_session );
			$WDGUserOrOrganization = $WDGOrganization;
			$mail_name = $WDGUser_current->get_firstname();
		} else {
			$investment_id = $campaign->add_investment( 'check', $invest_email, $amount_total, 'pending' );
			$WDGUser_current = WDGUser::current();
			$mail_name = $WDGUser_current->get_firstname();
		}

		$campaign_organization = $campaign->get_organization();
		$organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
		$percent_to_reach = round( ( $campaign->current_amount( FALSE ) +  $amount_total ) / $campaign->minimum_goal( FALSE ) * 100 );

		$WDGInvestment = new WDGInvestment( $investment_id );
		NotificationsAPI::investment_pending_check( $WDGUserOrOrganization, $WDGInvestment, $campaign );
		NotificationsSlack::new_purchase_pending_check_admin( $investment_id, FALSE );
		NotificationsAsana::new_purchase_pending_check_admin( $investment_id, FALSE );

		// Annulation des investissements non-démarrés du même investisseur
		$pending_not_validated_investments = array();
		if ( !empty( $user_type_session ) && $user_type_session != 'user' ) {
			$pending_not_validated_investments = $WDGOrganization->get_pending_not_validated_investments();
		} else {
			$pending_not_validated_investments = $WDGUser_current->get_pending_not_validated_investments();
		}
		if ( !empty( $pending_not_validated_investments ) ) {
			foreach ( $pending_not_validated_investments as $pending_not_validated_investment_item ) {
				$pending_not_validated_investment_item->cancel();
			}
		}

		wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'moyen-de-paiement' ) . '?campaign_id='.$campaign_id.'&meanofpayment=check&check-return=post_confirm_check' );
	}

	public static function declaration_auto_generate() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$result = 'error';

		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() ) {
			$campaign = new ATCF_Campaign($campaign_id);
			$month_count = filter_input( INPUT_POST, 'month_count' );
			if ( empty( $month_count ) ) {
				$month_count = 12 / $campaign->get_declarations_count_per_year();
			}
			$declarations_count = filter_input( INPUT_POST, 'declarations_count' );
			if ( empty( $declarations_count ) || !is_numeric( $declarations_count ) ) {
				$declarations_count = FALSE;
			}
			$campaign->generate_missing_declarations( $month_count, $declarations_count );
			$result = 'success';

			wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id. '&result=' .$result. '#royalties' );
			exit();
		} else {
			wp_redirect( home_url() );
			exit();
		}
	}

	public static function add_declaration_document() {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );

		$core = ATCF_CrowdFunding::instance();
		$core->include_form( 'declaration-document' );
		$form_document = new WDG_Form_Declaration_Document( $campaign_id );
		$form_return = $form_document->postForm();

		$success = ( $form_return != FALSE ) ? '1' : '100';
		wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id. '&add_declaration_document_success=' .$success. '#royalties' );
		exit();
	}

	public static function add_adjustment() {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$success = '100';

		if ( !empty( $campaign_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'adjustment' );
			$form_adjustment = new WDG_Form_Adjustement( $campaign_id );
			$form_return = $form_adjustment->postForm();

			$success = ( $form_return != FALSE ) ? '1' : '100';
		}

		wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id. '&add_adjustement_success=' .$success. '#royalties' );
		exit();
	}

	public static function edit_adjustment() {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$adjustment_id = filter_input( INPUT_POST, 'adjustment_id' );
		$success = '100';

		if ( !empty( $campaign_id ) && !empty( $adjustment_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'adjustment' );
			$adjustment = WDGWPREST_Entity_Adjustment::get( $adjustment_id );
			$form_adjustment = new WDG_Form_Adjustement( $campaign_id, $adjustment );
			$form_return = $form_adjustment->postForm();

			$success = ( $form_return != FALSE ) ? '1' : '100';
		}

		wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id. '&add_adjustement_success=' .$success. '#royalties' );
		exit();
	}

	public static function roi_mark_transfer_received() {
		$WDGUser_current = WDGUser::current();
		$roi_declaration_id = filter_input( INPUT_POST, 'roi_declaration_id' );
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );

		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $roi_declaration_id ) && !empty( $campaign_id ) ) {
			$roi_declaration = new WDGROIDeclaration( $roi_declaration_id );
			$roi_declaration->mark_transfer_received();

			wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id. '#royalties' );
			exit();
		} else {
			wp_redirect( home_url() );
			exit();
		}
	}

	public static function roi_cancel_transfer() {
		$WDGUser_current = WDGUser::current();
		$roi_declaration_id = filter_input( INPUT_POST, 'roi_declaration_id' );
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );

		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $roi_declaration_id ) && !empty( $campaign_id ) ) {
			$roi_declaration = new WDGROIDeclaration( $roi_declaration_id );
			$roi_declaration->roi_cancel_transfer();

			wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id. '#royalties' );
			exit();
		} else {
			wp_redirect( home_url() );
			exit();
		}
	}	

	public static function generate_royalties_bill() {
		$WDGUser_current = WDGUser::current();
		$roi_declaration_id = filter_input( INPUT_POST, 'roi_declaration_id' );
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );

		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $roi_declaration_id ) && !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$roi_declaration = new WDGROIDeclaration( $roi_declaration_id );
			$campaign_bill = new WDGCampaignBill( $campaign, WDGCampaignBill::$tool_name_quickbooks, WDGCampaignBill::$bill_type_royalties_commission );
			$campaign_bill->set_declaration( $roi_declaration );
			$campaign_bill->generate();

			wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id. '#royalties' );
			exit();
		} else {
			wp_redirect( home_url() );
			exit();
		}
	}

	public static function save_declaration_bill() {
		$WDGUser_current = WDGUser::current();
		$roi_declaration_id = filter_input( INPUT_POST, 'declaration_id' );

		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $roi_declaration_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'declaration-bill' );
			$new_form = new WDG_Form_Declaration_Bill( $roi_declaration_id );
			$new_form->postForm();

			wp_redirect( wp_get_referer() );
			exit();
		} else {
			wp_redirect( home_url() );
			exit();
		}
	}

	public static function refund_investors() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );

		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$campaign->refund();
			wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id );
			exit();
		} else {
			wp_redirect( home_url() );
			exit();
		}
	}

	public static function mandate_b2b_admin_update() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$organization_id = filter_input( INPUT_POST, 'organization_id' );
		if ( empty( $WDGUser_current ) || !$WDGUser_current->is_admin() || empty( $organization_id ) || empty( $campaign_id ) ) {
			wp_redirect( home_url() );
			exit();
		}

		$WDGOrganization = new WDGOrganization( $organization_id );

		$mandate_b2b_is_approved_by_bank = filter_input( INPUT_POST, 'mandate_b2b_is_approved_by_bank' );
		$WDGOrganization->update_mandate_info( 'lemonway', FALSE, FALSE, $mandate_b2b_is_approved_by_bank );

		$file_mandate_b2b = $_FILES[ 'mandate_b2b_file' ];
		if ( !empty( $file_mandate_b2b ) && !empty( $file_mandate_b2b[ 'name' ] ) ) {
			$file_name = $file_mandate_b2b[ 'name' ];
			$file_name_exploded = explode( '.', $file_name );
			$ext = $file_name_exploded[ count( $file_name_exploded ) - 1];
			$byte_array = file_get_contents( $file_mandate_b2b[ 'tmp_name' ] );
			$file_create_item = WDGWPREST_Entity_File::create( $WDGOrganization->get_api_id(), 'organization', 'mandate', $ext, base64_encode( $byte_array ) );

			// Préparation notification automatique au porteur de projet
			// $user_name = '';
			// $linked_users_creator = $WDGOrganization->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			// if ( !empty( $linked_users_creator ) ) {
				// $WDGUser_creator = $linked_users_creator[ 0 ];
				// $user_name = $WDGUser_creator->wp_user->user_firstname;
			// }
			$campaign = new ATCF_Campaign( $campaign_id );
			//Suppression cache organisation pour récupérer nouvelle version
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$WDGOrganization->get_api_id() );
			$WDGOrganizationUpdated = new WDGOrganization( $organization_id );
			NotificationsAPI::mandate_to_send_to_bank( $WDGOrganization, $WDGOrganizationUpdated->get_mandate_file_url(), $campaign->get_api_id() );
		}

		wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id. '#contracts' );
		exit();
	}

	public static function user_account_organization_details() {
		$organization_id = filter_input( INPUT_POST, 'organization_id' );
		if ( !empty( $organization_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'organization-details' );
			$WDGOrganizationDetailsForm = new WDG_Form_Organization_Details( $organization_id );
			
			ypcf_session_start();
			$_SESSION[ 'account_organization_form_feedback_' . $organization_id ] = $WDGOrganizationDetailsForm->postForm();

			// on ne redirige pas vers Mon Compte quand on est dans le Tableau De Bord
			if ( stristr( wp_get_referer(), 'tableau-de-bord' ) === FALSE && stristr(wp_get_referer(), 'dashboard')  === FALSE ) {
				wp_redirect( wp_get_referer() . '#orga-parameters-' . $organization_id );
			} else {
				wp_redirect( wp_get_referer() . '#organization' );
			}
			exit();
		}
	}

	public static function user_account_organization_identitydocs() {
		$organization_id = filter_input( INPUT_POST, 'user_id' );
		if ( !empty( $organization_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'user-identitydocs' );
			WDG_Languages_Helpers::load_languages();
			$WDGFormIdentityDocs = new WDG_Form_User_Identity_Docs( $organization_id, TRUE );

			ypcf_session_start();
			$_SESSION[ 'account_organization_identitydocs_form_feedback_' . $organization_id ] = $WDGFormIdentityDocs->postForm();

			// on ne redirige pas vers Mon Compte quand on est dans le Tableau De Bord
			if ( stristr(wp_get_referer(), 'tableau-de-bord')  === FALSE && stristr(wp_get_referer(), 'dashboard')  === FALSE ) {
				wp_redirect( wp_get_referer() . '#orga-identitydocs-' . $organization_id );
			} else {
				wp_redirect( wp_get_referer() . '#organization' );
			}
			exit();
		}
	}

	public static function user_account_organization_bank() {
		$organization_id = filter_input( INPUT_POST, 'user_id' );
		if ( !empty( $organization_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'user-bank' );
			$WDGFormBank = new WDG_Form_User_Bank( $organization_id, TRUE );
			$WDGFormBank->postForm();
			// on ne redirige pas vers Mon Compte quand on est dans le Tableau De Bord
			if ( stristr(wp_get_referer(), 'tableau-de-bord')  === FALSE && stristr(wp_get_referer(), 'dashboard')  === FALSE) {
				wp_redirect( wp_get_referer() . '#orga-bank-' . $organization_id );
			} else {
				wp_redirect( wp_get_referer() . '#organization' );
			}
			exit();
		}
	}

	public static function user_account_add_subscription() {
		$user_id = filter_input( INPUT_POST, 'user_id' );
		if ( !empty( $user_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'user-subscription' );
			WDG_Languages_Helpers::load_languages();
			$WDGFormSubscription = new WDG_Form_Subscription( $user_id, TRUE );
			ypcf_session_start();
			$_SESSION[ 'account_organization_form_subscription_feedback_' . $user_id ] = $WDGFormSubscription->postForm();
			wp_redirect( home_url('contrat-abonnement'.'?id_subscription='. $_SESSION[ 'account_organization_form_subscription_feedback_' . $user_id ]['id_subscription']) );
			exit();
		}
	}

	public static function user_account_validate_contract_subscription() {
		$user_id = filter_input( INPUT_POST, 'user_id' );
		if ( !empty( $user_id ) ) {
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'user-subscription-contract' );
			WDG_Languages_Helpers::load_languages();
			$WDGFormSubscriptionContract = new WDG_Form_Subscription_Contract( $user_id );
			ypcf_session_start();
			$_SESSION[ 'account_organization_form_subscription_feedback_' . $user_id ] = $WDGFormSubscriptionContract->postForm();
			$url_redirect = WDG_Redirect_Engine::override_get_page_url( 'mon-compte' ). '#subscriptions';
			wp_redirect( $url_redirect );
			exit();
		}
	}

	public static function user_account_end_subscription() {
		$id_subscription = filter_input( INPUT_GET, 'id_subscription' );
		if ( !empty( $id_subscription ) ) {
			$subscription = new WDGSUBSCRIPTION( $id_subscription );
			// Vérification de la personne connectée : a-t-elle le droit ?
			$WDGUser_current = WDGUser::current();
			if ( $WDGUser_current->is_admin() || $subscription->id_subscriber == $WDGUser_current->get_api_id() ) {
				// Si ok, on passe la souscription en annulée
				$subscription->status = WDGSUBSCRIPTION::$type_end;
				$date_today = new DateTime();
				$subscription->end_date = $date_today->format( 'Y-m-d H:i:s' );
				$subscription->update();
			}
		}

		$url_redirect = WDG_Redirect_Engine::override_get_page_url( 'mon-compte' ). '#subscriptions';
		wp_redirect( $url_redirect );
		exit();
	}

	public static function remove_user_registered_card() {
		$WDGUser_current = WDGUser::current();
		$card_id = filter_input( INPUT_POST, 'card_id' );

		$user_id = filter_input( INPUT_POST, 'user_id' );
		if ( !empty( $user_id ) ) {
			if ( $WDGUser_current->get_wpref() == $user_id || $WDGUser_current->is_admin() ) {
				$WDGUser_displayed = new WDGUser( $user_id );
				$WDGUser_displayed->unregister_card( $card_id );
			}
		} else {
			$orga_id = filter_input( INPUT_POST, 'orga_id' );
			if ( !empty( $orga_id ) ) {
				if ( $WDGUser_current->can_edit_organization( $orga_id ) || $WDGUser_current->is_admin() ) {
					$WDGOrganization_displayed = new WDGOrganization( $user_id );
					$WDGOrganization_displayed->unregister_card( $card_id );
				}

				wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'mon-compte' ) . '#orga-bank-' . $orga_id );
				exit();
			}
		}

		wp_redirect( WDG_Redirect_Engine::override_get_page_url( 'mon-compte' ) . '#bank' );
		exit();
	}

	public static function view_kyc_file() {
		$WDGUser_current = WDGUser::current();

		$id_kyc = filter_input( INPUT_GET, 'id_kyc' );
		$file_kyc = new WDGKYCFile( $id_kyc );

		$id_user_kyc = FALSE;
		if ( !empty( $file_kyc->user_id ) ) {
			$id_user_kyc = $file_kyc->user_id;
		}
		$id_orga_kyc = FALSE;
		if ( !empty( $file_kyc->orga_id ) ) {
			$id_orga_kyc = $file_kyc->orga_id;
		}

		$can_see_file = $WDGUser_current->is_admin()
							|| $id_user_kyc == $WDGUser_current->get_wpref()
							|| $WDGUser_current->can_edit_organization( $id_orga_kyc );

		if ( $can_see_file ) {
			header( 'Content-Type: ' . $file_kyc->get_content_type() );
			header( 'Content-Disposition: inline; filename="' .$file_kyc->file_name. '";' );
			readfile( $file_kyc->get_public_filepath( false ) );
			exit();
		} else {
			exit( 'Access denied' );
		}
	}
}