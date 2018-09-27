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
        self::add_action("generate_campaign_bill");
        self::add_action("generate_contract_files");
        self::add_action("upload_contract_files");
        self::add_action("send_project_contract_modification_notification");
        self::add_action("cancel_token_investment");
        self::add_action("post_invest_check");
        self::add_action("post_confirm_check");
        self::add_action("declaration_auto_generate");
        self::add_action("roi_mark_transfer_received");
        self::add_action("generate_royalties_bill");
        self::add_action("refund_investors");
        self::add_action( 'user_account_organization_details' );
        self::add_action( 'user_account_organization_identitydocs' );
        self::add_action( 'user_account_organization_bank' );
    }

    /**
     * Ajoute une action WordPress à exécuter en Post/get
     * @param string $action_name
     */
    public static function add_action($action_name) {
        add_action('admin_post_' . $action_name, array(WDGPostActions::$class_name, $action_name));
        add_action('admin_post_nopriv_' . $action_name, array(WDGPostActions::$class_name, $action_name));
    }

	/**
	 * Formulaire d'ajout d'e-mail dans la NL
	 */
	public static function subscribe_newsletter_sendinblue( $init_email = '' ) {
		$action = filter_input( INPUT_POST, 'action' );
		if ( ( !empty( $action ) && ( $action == 'subscribe_newsletter_sendinblue' ) ) || !empty( $init_email ) ) {
			$email = $init_email;
			if ( empty( $init_email ) ) {
				$email = sanitize_text_field( filter_input( INPUT_POST, 'subscribe-nl-mail' ) );
			}

			try {
				require_once( 'sendinblue/mailin.php' );
				$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 5000 );
				$return = $mailin->create_update_user( array(
					"email"		=> $email,
					"listid"	=> array( 5, 6 )
				) );
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
	
	
    public static function send_project_mail(){
		ypcf_debug_log( 'WDGPostActions::send_project_mail' );
		ypcf_debug_log( 'WDGPostActions::send_project_mail > mail_recipients : ' .filter_input( INPUT_POST, 'mail_recipients' ) );
        global $wpdb;
        $campaign_id = sanitize_text_field( filter_input( INPUT_POST, 'campaign_id' ) );
		$post_campaign = get_post( $campaign_id );
		$author_user = get_user_by( 'ID', $post_campaign->post_author );
        $mail_title = sanitize_text_field( filter_input( INPUT_POST, 'mail_title' ) );
        $mail_content = nl2br( filter_input( INPUT_POST, 'mail_content' ) );
		$mail_recipients = explode( ',', filter_input( INPUT_POST, 'mail_recipients' ) );
		
		global $wpdb;
        $table_vote = $wpdb->prefix . "ypcf_project_votes";
        $list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum FROM ".$table_vote." WHERE post_id = ".$campaign_id." AND validate_project = 1", OBJECT_K);
		
        foreach ( $mail_recipients as $id_user ) {
			if ( is_numeric( $id_user ) ) {
				//TODO : Re-vérifier si l'utilisateur peut bien envoyer à la personne (vérifier si dans la liste des suiveurs/votants/investisseurs)
				$user = get_userdata( intval( $id_user ) );
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

				NotificationsAPI::project_mail( $to, $author_user->user_email, $user->first_name, $post_campaign->post_title, get_permalink( $campaign_id ), $mail_title, $this_mail_content['body'] );
			}
        }

        wp_safe_redirect( wp_get_referer()."&send_mail_success=1#contacts" );
        die();
    }

    public static function create_project_form(){
		ypcf_debug_log( 'create_project_form > $_POST > ' . print_r($_POST, true), TRUE );
        $WDGUser_current = WDGUser::current();
        $WPuserID = $WDGUser_current->wp_user->ID;

        $new_lastname = sanitize_text_field(filter_input(INPUT_POST,'lastname'));
        $new_firstname = sanitize_text_field(filter_input(INPUT_POST,'firstname'));
        $new_phone = sanitize_text_field(filter_input(INPUT_POST,'phone'));

        $orga_name = sanitize_text_field(filter_input(INPUT_POST,'company-name'));
		$orga_email = sanitize_text_field( filter_input( INPUT_POST, 'email-organization' ) );
        $project_name = sanitize_text_field(filter_input(INPUT_POST,'project-name'));
        $project_desc = sanitize_text_field(filter_input(INPUT_POST,'project-description'));
        $project_terms = filter_input( INPUT_POST, 'project-terms' );

        //User data
        if(!empty($new_firstname)){
            wp_update_user( array ( 'ID' => $WPuserID, 'first_name' => $new_firstname ) ) ;
        }
        if(!empty($new_lastname)){
            wp_update_user( array ( 'ID' => $WPuserID, 'last_name' => $new_lastname ) ) ;
        }
        if(!empty($new_phone)){
            update_user_meta( $WPuserID, 'user_mobile_phone', $new_phone );
        }

        if (	!empty( $new_firstname ) && !empty( $new_lastname ) && is_email( $orga_email ) && !empty( $new_phone )
				&& !empty($orga_name) && !empty($project_name) && !empty($project_desc) && !empty($project_terms) ) {

			//On commence par essayer de créer l'organisation d'abord
			//Si organisation déjà liée à l'utilisateur, on récupère le wpref de l'orga (selcet du formulaire)
			//sinon si aucune organisation, elle est créée à la volée à la création du projet
			$success = true;
			$orga_api_id = FALSE;
			
			if ( is_numeric( $orga_name ) ) {
				$existing_orga = new WDGOrganization($orga_name);
				$orga_api_id = $existing_orga->get_api_id();
				
			//Si on sélectionne "new_orga", il faut prendre le champ texte qui est apparu
			} else if ( $orga_name == 'new_orga' ) {
				$orga_name = sanitize_text_field( filter_input( INPUT_POST, 'new-company-name' ) );
				if ( !empty( $orga_name ) ) {
					$organization_created = WDGOrganization::createSimpleOrganization( $WPuserID, $orga_name, $orga_email );
					if ( $organization_created != false ) {
						$orga_api_id = $organization_created->get_api_id();
						
					} else {
						$success = false;
					}
				}
				
			//Sinon, si c'était directement un texte, on crée l'organisation
			} else if ( !empty( $orga_name ) ) {
				$organization_created = WDGOrganization::createSimpleOrganization( $WPuserID, $orga_name, $orga_email );
				if ( $organization_created != false ) {
					$orga_api_id = $organization_created->get_api_id();
				} else {
					$success = false;
				}
				
			//Sinon on arrête la procédure
			} else {
				$success = false;
			}

			if ( $success && !empty( $orga_api_id ) ) {
				//Project data
				$_SESSION[ 'newproject-errors' ] = FALSE;
				$newcampaign_id = atcf_create_campaign($WPuserID, $project_name);
				$newcampaign = atcf_get_campaign($newcampaign_id);

				$newcampaign->__set(ATCF_Campaign::$key_backoffice_summary, $project_desc);
				$newcampaign->set_api_data( 'description', $project_desc );
				$newcampaign->__set( 'campaign_contact_phone', $new_phone );
				$newcampaign->set_forced_mandate( 1 );
				$newcampaign->link_organization( $orga_api_id );
				$newcampaign->update_api();
			
				//Mail pour l'équipe
				NotificationsSlack::send_new_project( $newcampaign_id, $orga_name );
				NotificationsEmails::new_project_posted_owner($newcampaign_id, '');


				//Redirect then
				$page_dashboard = get_page_by_path('tableau-de-bord');
				$campaign_id_param = '?campaign_id=';
				$campaign_id_param .= $newcampaign_id;

				$redirect_url = get_permalink($page_dashboard->ID) . $campaign_id_param ."&lightbox=newproject" ;
				wp_safe_redirect( $redirect_url);
			} else {
				global $errors_submit_new, $errors_create_orga;
				ypcf_debug_log( 'create_project_form > error > ' . print_r($errors_submit_new, true) );
				ypcf_debug_log( 'create_project_form > error > ' . print_r($errors_create_orga, true) );
				$_SESSION[ 'newproject-errors-submit' ] = $errors_submit_new;
				$_SESSION[ 'newproject-errors-orga' ] = $errors_create_orga;
				wp_safe_redirect( home_url( '/lancement/?error=creation#newproject' ) );
			}
        } else {
			global $errors_submit_new, $errors_create_orga;
			$_SESSION[ 'newproject-errors-submit' ] = $errors_submit_new;
			$_SESSION[ 'newproject-errors-orga' ] = $errors_create_orga;
            wp_safe_redirect( home_url( '/lancement/?error=field_empty#newproject' ) );
        }
		exit();
    }

    public static function change_project_status(){
        $campaign_id = sanitize_text_field(filter_input(INPUT_POST,'campaign_id'));
        $campaign = atcf_get_campaign($campaign_id);
        $status = $campaign->campaign_status();
        $can_modify = $campaign->current_user_can_edit();
        $is_admin = WDGUser::current()->is_admin();

        $next_status = filter_input(INPUT_POST,'next_status');

        if ($can_modify
            && !empty($next_status)
            && ($next_status==1 || $next_status==2)){
			
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
				} else if ( $validate_next_step == '1' ) {
					$campaign->set_status(ATCF_Campaign::$campaign_status_validated);
					$campaign->set_validation_next_status(0);
				}
			
			//Enregistrement avant passage en vote
			} else if ( $status == ATCF_Campaign::$campaign_status_validated && $save_validation_steps == '1' ) {
				$has_filled_presentation = filter_input( INPUT_POST, 'validation-step-has-filled-presentation' );
				$campaign->set_validation_step_status( 'has_filled_presentation', $has_filled_presentation );

            } else if ($campaign->can_go_next_status()){
				
                if ( $status == ATCF_Campaign::$campaign_status_validated && $next_status == 1 ) {
                    //Validé -> Avant-première
                    $campaign->set_status(ATCF_Campaign::$campaign_status_preview);
                    $campaign->set_validation_next_status(0);

					
                } else if (
					$status == ATCF_Campaign::$campaign_status_preview
                    || ( $status == ATCF_Campaign::$campaign_status_validated && !$campaign->skip_vote() && $next_status == 2 ) ) {
                    //Validé/Avant-première -> Vote

                    //Vérifiation organisation complète
                    $orga_done=false;
                    $campaign_organization = $campaign->get_organization();

                    //Vérification validation lemonway
                    $organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
                    if ($organization_obj->is_registered_lemonway_wallet()) { $orga_done = true; }

                    //Validation données
                    if($orga_done && ypcf_check_user_is_complete($campaign->post_author())&& isset($_POST['innbdayvote'])){
                        $vote_time = $_POST['innbdayvote'];
                        if(10<=$vote_time && $vote_time<=30){
                            //Fixe date fin de vote
                            $diffVoteDay = new DateInterval('P'.$vote_time.'D');
                            $VoteEndDate = (new DateTime())->add($diffVoteDay);
                            //$VoteEndDate->setTime(23,59);
                            $campaign->set_end_vote_date($VoteEndDate);

                            $campaign->set_status(ATCF_Campaign::$campaign_status_vote);
                            $campaign->set_validation_next_status(0);
							NotificationsSlack::send_new_project_status( $campaign_id, ATCF_Campaign::$campaign_status_vote );
		
							// Mise à jour cache
							do_action('wdg_delete_cache', array(
								'cache_campaign_' . $campaign_id
							));
							$file_cacher = WDG_File_Cacher::current();
							$file_cacher->build_campaign_page_cache( $campaign_id );
                        }
                    }


                } else if (
					( $status == ATCF_Campaign::$campaign_status_validated && $campaign->skip_vote() && $next_status == 2 )
					|| $status == ATCF_Campaign::$campaign_status_vote ) {
                    //Vote -> Collecte
                    if(isset($_POST['innbdaycollecte'])
                        && isset($_POST['inendh'])
                        && isset($_POST['inendm'])){
                        //Recupere nombre de jours et heure de fin de la collecte
                        $collecte_time = $_POST['innbdaycollecte'];
                        $collecte_fin_heure = $_POST['inendh'];
                        $collecte_fin_minute = $_POST['inendm'];

                        if( 1<=$collecte_time && $collecte_time<=60
                            && 0<=$collecte_fin_heure && $collecte_fin_heure<=23
                            && 0<=$collecte_fin_minute && $collecte_fin_minute<=59){
                            //Fixe la date de fin de collecte
                            $diffCollectDay = new DateInterval('P'.$collecte_time.'D');
                            $CollectEndDate = (new DateTime())->add($diffCollectDay);
                            $CollectEndDate->setTime($collecte_fin_heure,$collecte_fin_minute);
                            $campaign->set_end_date($CollectEndDate);
                            $campaign->set_begin_collecte_date(new DateTime());

                            $campaign->set_status(ATCF_Campaign::$campaign_status_collecte);
                            $campaign->set_validation_next_status(0);
							NotificationsSlack::send_new_project_status( $campaign_id, ATCF_Campaign::$campaign_status_collecte );
		
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
		
		// Récupération de l'organisation
		$organization_obj = new WDGOrganization( $organization_id );
		$token = $organization_obj->get_sign_mandate_token( $phone_number, $url_return, $url_return );
		
		if ( $token != FALSE ) {
			// Redirection vers la page de signature de document
			wp_redirect( YP_LW_WEBKIT_URL .'?signingToken='. $token->SIGNDOCUMENT->TOKEN );
			die();
		}
		
		wp_redirect( $url_return );
		die();
	}
	
	public static function organization_remove_mandate() {
        $organization_id = sanitize_text_field( filter_input( INPUT_POST, 'organization_id' ) );
        $mandate_id = sanitize_text_field( filter_input( INPUT_POST, 'mandate_id' ) );
		$WDGOrganization = new WDGOrganization( $organization_id );
		$WDGOrganization->remove_lemonway_mandate( $mandate_id );
		$WDGOrganization->add_lemonway_mandate();
		
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
		for( $i = 0; $i < 15; $i++ ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		while ( file_exists( __DIR__ . '/../kyc/' . $random_filename . '.' . $ext ) ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		$random_filename .= '.' . $ext;
		move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../kyc/' . $random_filename );
		
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
						$buffer = __DIR__. '/../pdf_files/tmp';
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
					
					// Si le contrat n'existe pas sur Signsquid, créer un contrat electronique sur Signsquid dans meta amendment_signsquid_ID
					$meta_payment_amendment_signsquid = get_post_meta( $payment_id, 'amendment_signsquid_' . $contract_model_id, TRUE );
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
					}
				}
			}
			
			WDGWPREST_Entity_ContractModel::update_status( $contract_model_id, 'sent' );
		}
		
		$url_return = wp_get_referer() . "#contracts";
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
	
	public static function generate_contract_files() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$campaign->generate_contract_pdf_blank_organization();
		$url_return = wp_get_referer() . "#contracts";
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
			for( $i = 0; $i < 15; $i++ ) {
				$random_filename .= $chars[ rand( 0, $size - 1 ) ];
			}
			while ( file_exists( __DIR__ . '/../contracts/' . $random_filename . '.' . $ext ) ) {
				$random_filename .= $chars[ rand( 0, $size - 1 ) ];
			}
			$random_filename .= '.' . $ext;
			move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../contracts/' . $random_filename );
			$campaign->__set( ATCF_Campaign::$key_backoffice_contract_orga, $random_filename );
		}
		
		$new_project_contract_earnings_description = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_earnings_description' ) );
		if ( !empty( $new_project_contract_earnings_description ) ) {
			$campaign->__set( ATCF_Campaign::$key_contract_earnings_description, $new_project_contract_earnings_description );
			$campaign->set_api_data( 'earnings_description', $new_project_contract_earnings_description );
		}
		$new_project_contract_spendings_description = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_spendings_description' ) );
		if ( !empty( $new_project_contract_spendings_description ) ) {
			$campaign->__set( ATCF_Campaign::$key_contract_spendings_description, $new_project_contract_spendings_description );
			$campaign->set_api_data( 'spendings_description', $new_project_contract_spendings_description );
		}
		$new_project_contract_simple_info = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_simple_info' ) );
		if ( !empty( $new_project_contract_simple_info ) ) {
			$campaign->__set( ATCF_Campaign::$key_contract_simple_info, $new_project_contract_simple_info );
			$campaign->set_api_data( 'simple_info', $new_project_contract_simple_info );
		}
		$new_project_contract_detailed_info = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_detailed_info' ) );
		if ( !empty( $new_project_contract_detailed_info ) ) {
			$campaign->__set( ATCF_Campaign::$key_contract_detailed_info, $new_project_contract_detailed_info );
			$campaign->set_api_data( 'detailed_info', $new_project_contract_detailed_info );
		}
		
		$new_contract_premium = filter_input( INPUT_POST, 'new_contract_premium' );
		$campaign->__set( ATCF_Campaign::$key_contract_premium, $new_contract_premium );
		
		$new_contract_warranty = filter_input( INPUT_POST, 'new_contract_warranty' );
		$campaign->__set( ATCF_Campaign::$key_contract_warranty, $new_contract_warranty );
		
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
			$campaign = new ATCF_Campaign( $campaign_id );
			$contract_has_been_modified = ( $campaign->contract_modifications() != '' );
			$pending_preinvestments = $campaign->pending_preinvestments();
			foreach ( $pending_preinvestments as $preinvestment ) {
				$user_info = edd_get_payment_meta_user_info( $preinvestment->get_id() );
				if ( $contract_has_been_modified ) {
					NotificationsEmails::preinvestment_to_validate( $user_info['email'], $campaign );
					
				} else {
					NotificationsEmails::preinvestment_auto_validated( $user_info['email'], $campaign );
					$preinvestment->set_contract_status( WDGInvestment::$contract_status_investment_validated );
				}
			}
		}
		
		$url_return = wp_get_referer() . "#contracts";
		wp_redirect( $url_return );
		die();
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
		$current_user = wp_get_current_user();
		$invest_email = $current_user->user_email;
		if ( !empty( $user_type_session ) && $user_type_session != 'user' ) {
			$orga_creator = get_user_by( 'id', $user_type_session );
			$orga_email = $orga_creator->user_email;
			$investment_id = $campaign->add_investment(
				'check', $invest_email, $amount_total, 'pending',
				'', '', 
				'', '', '', 
				'', '', '', '', '', 
				'', '', '', '', '', 
				$orga_email
			);
		} else {
			$investment_id = $campaign->add_investment( 'check', $invest_email, $amount_total, 'pending' );
		}
		
		
		$file_uploaded_data = $_FILES['check_picture'];
		$file_name = $file_uploaded_data['name'];
		$file_name_exploded = explode('.', $file_name);
		$ext = $file_name_exploded[ count( $file_name_exploded ) - 1];
		
		$random_filename = '';
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$size = strlen( $chars );
		for( $i = 0; $i < 15; $i++ ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		while ( file_exists( __DIR__ . '/../../files/investment-check/' . $random_filename . '.' . $ext ) ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		$random_filename .= '.' . $ext;
		$has_moved = move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../../files/investment-check/' . $random_filename );
		$picture_url = home_url() . '/wp-content/plugins/appthemer-crowdfunding/files/investment-check/' . $random_filename;
		
		NotificationsEmails::new_purchase_pending_check_user( $investment_id, TRUE );
		NotificationsEmails::new_purchase_pending_check_admin( $investment_id, $picture_url );
		
		if ( $has_moved ) {
			update_post_meta( $investment_id, 'check_picture', $random_filename );
			wp_redirect( home_url( '/moyen-de-paiement/' ) . '?campaign_id='.$campaign_id.'&meanofpayment=check&check-return=post_invest_check' );
			
		} else {
			wp_redirect( home_url( '/moyen-de-paiement/' ) . '?campaign_id='.$campaign_id.'&meanofpayment=check&check-return=post_confirm_check&error-upload=1' );
			
		}
		exit();
	}
	
	public static function post_confirm_check() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$WDGInvestment = WDGInvestment::current();
		$amount_total = $WDGInvestment->get_session_amount();
		$user_type_session = $WDGInvestment->get_session_user_type();
		$current_user = wp_get_current_user();
		$invest_email = $current_user->user_email;
		if ( !empty( $user_type_session ) && $user_type_session != 'user' ) {
			$orga_creator = get_user_by( 'id', $user_type_session );
			$orga_email = $orga_creator->user_email;
			$investment_id = $campaign->add_investment(
				'check', $invest_email, $amount_total, 'pending',
				'', '', 
				'', '', '', 
				'', '', '', '', '', 
				'', '', '', '', '', 
				$orga_email
			);
		} else {
			$investment_id = $campaign->add_investment( 'check', $invest_email, $amount_total, 'pending' );
		}
		
		NotificationsEmails::new_purchase_pending_check_user( $investment_id, FALSE );
		NotificationsEmails::new_purchase_pending_check_admin( $investment_id, FALSE );
		
		wp_redirect( home_url( '/moyen-de-paiement/' ) . '?campaign_id='.$campaign_id.'&meanofpayment=check&check-return=post_confirm_check' );
	}
	
	public static function declaration_auto_generate() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$result = 'error';
		
		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() ) {
			$campaign = new ATCF_Campaign($campaign_id);
			$month_count = filter_input( INPUT_POST, 'month_count' );
			if ( empty( $month_count ) ) {
				$month_count = 3;
			}
			$campaign->generate_missing_declarations( $month_count );
			$result = 'success';
		
			wp_redirect( home_url( '/tableau-de-bord/' ) . '?campaign_id=' .$campaign_id. '&result=' .$result. '#royalties' );
			exit();
			
		} else {
			wp_redirect( home_url() );
			exit();
			
		}
		
	}
	
	public static function roi_mark_transfer_received() {
		$WDGUser_current = WDGUser::current();
		$roi_declaration_id = filter_input( INPUT_POST, 'roi_declaration_id' );
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		
		if ( $WDGUser_current != FALSE && $WDGUser_current->is_admin() && !empty( $roi_declaration_id ) && !empty( $campaign_id ) ) {
			$roi_declaration = new WDGROIDeclaration( $roi_declaration_id );
			$roi_declaration->mark_transfer_received();
		
			wp_redirect( home_url( '/tableau-de-bord/' ) . '?campaign_id=' .$campaign_id. '#royalties' );
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
		
			wp_redirect( home_url( '/tableau-de-bord/' ) . '?campaign_id=' .$campaign_id. '#royalties' );
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
			wp_redirect( home_url( '/tableau-de-bord/' ) . '?campaign_id=' .$campaign_id );
			exit();
			
		} else {
			wp_redirect( home_url() );
			exit();
			
		}
	}
	
	public static function user_account_organization_details() {
		$organization_id = filter_input( INPUT_POST, 'organization_id' );
		if ( !empty( $organization_id ) ){
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'organization-details' );
			$WDGOrganizationDetailsForm = new WDG_Form_Organization_Details( $organization_id );
			$WDGOrganizationDetailsForm->postForm();
			wp_redirect( home_url( '/mon-compte/#orga-parameters-' . $organization_id ) );
			exit();
		}
	}
	
	public static function user_account_organization_identitydocs() {
		$organization_id = filter_input( INPUT_POST, 'user_id' );
		if ( !empty( $organization_id ) ){
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'user-identitydocs' );
			$WDGFormIdentityDocs = new WDG_Form_User_Identity_Docs( $organization_id, TRUE );
			$WDGFormIdentityDocs->postForm();
			wp_redirect( home_url( '/mon-compte/#orga-identitydocs-' . $organization_id ) );
			exit();
		}
	}
	
	public static function user_account_organization_bank() {
		$organization_id = filter_input( INPUT_POST, 'user_id' );
		if ( !empty( $organization_id ) ){
			$core = ATCF_CrowdFunding::instance();
			$core->include_form( 'user-bank' );
			$WDGFormBank = new WDG_Form_User_Bank( $organization_id, TRUE );
			$WDGFormBank->postForm();
			wp_redirect( home_url( '/mon-compte/#orga-bank-' . $organization_id ) );
			exit();
		}
	}
}