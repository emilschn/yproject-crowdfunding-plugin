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
        self::add_action("send_project_mail");
        self::add_action("create_project_form");
        self::add_action("change_project_status");
        self::add_action("organization_sign_mandate");
        self::add_action("upload_information_files");
        self::add_action("upload_contract_files");
        self::add_action("cancel_token_investment");
        self::add_action("post_invest_check");
        self::add_action("post_confirm_check");
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

			require_once( 'sendinblue/mailin.php' );
			$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 5000 );
			$return = $mailin->create_update_user( array(
				"email"		=> $email,
				"listid"	=> array( 5, 6 )
			) );
			$_SESSION['subscribe_newsletter_sendinblue'] = true;
			if (empty( $init_email )) {
				wp_safe_redirect( wp_get_referer() );
				die();
			}
		}
	}
	
	
    public static function send_project_mail(){
        global $wpdb;
        $campaign_id = sanitize_text_field(filter_input(INPUT_POST,'campaign_id'));
        $mail_title = sanitize_text_field(filter_input(INPUT_POST,'mail_title'));
        $mail_content = filter_input(INPUT_POST,'mail_content');
        $mail_recipients = (json_decode("[".filter_input(INPUT_POST,'mail_recipients')."]"));

        NotificationsEmails::project_mail($campaign_id, $mail_title, $mail_content, $mail_recipients);

        wp_safe_redirect( wp_get_referer()."&send_mail_success=1#contacts" );
        die();
    }

    public static function create_project_form(){
        $WDGUser_current = WDGUser::current();
        $WPuserID = $WDGUser_current->wp_user->ID;

        $new_lastname = sanitize_text_field(filter_input(INPUT_POST,'lastname'));
        $new_firstname = sanitize_text_field(filter_input(INPUT_POST,'firstname'));
        $new_email = sanitize_email(filter_input(INPUT_POST,'email'));
        $new_phone = sanitize_text_field(filter_input(INPUT_POST,'phone'));

        $orga_name = sanitize_text_field(filter_input(INPUT_POST,'company-name'));

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
        if (is_email($new_email)==$new_email) {
            wp_update_user( array ( 'ID' => $WPuserID, 'user_email' => $new_email ) );
        }
        if(!empty($new_phone)){
            update_user_meta( $WPuserID, 'user_mobile_phone', $new_phone );
        }

        if (	!empty( $new_firstname ) && !empty( $new_lastname ) && is_email( $new_email ) && !empty( $new_phone )
				&& !empty($orga_name) && !empty($project_name) && !empty($project_desc) && !empty($project_terms) ) {
            //Project data
            $newcampaign_id = atcf_create_campaign($WPuserID, $project_name);
            $newcampaign = atcf_get_campaign($newcampaign_id);

            $newcampaign->__set(ATCF_Campaign::$key_backoffice_summary, $project_desc);
			$newcampaign->__set( 'campaign_contact_phone', $new_phone );
			$newcampaign->set_forced_mandate( 1 );
			$success = true;

			//Si organisation déjà liée à l'utilisateur, on récupère le wpref de l'orga (selcet du formulaire)
			//sinon si aucune organisation, elle est créée à la volée à la création du projet
			if ( is_numeric( $orga_name ) ) {
				$existing_orga = new WDGOrganization($orga_name);
				$newcampaign->link_organization($existing_orga->get_api_id());
			//Si on sélectionne "new_orga", il faut prendre le champ texte qui est apparu
			} else if ( $orga_name == 'new_orga' ) {
				$orga_name = sanitize_text_field( filter_input( INPUT_POST, 'new-company-name' ) );
				if ( !empty( $orga_name ) ) {
					$organization_created = WDGOrganization::createSimpleOrganization( $WPuserID, $orga_name, $WDGUser_current->wp_user->user_email );
					if ( $organization_created != false ) {
						$newcampaign->link_organization( $organization_created->get_api_id() );
					} else {
						$success = false;
					}
				}
			//Sinon, si c'était directement un texte, on crée l'organisation
			} else if ( !empty( $orga_name ) ) {
				$organization_created = WDGOrganization::createSimpleOrganization( $WPuserID, $orga_name, $WDGUser_current->wp_user->user_email );
				if ( $organization_created != false ) {
					$newcampaign->link_organization( $organization_created->get_api_id() );
				} else {
					$success = false;
				}
			//Sinon on arrête la procédure
			} else {
				$success = false;
			}

			if ( $success ) {
				//Mail pour l'équipe
				NotificationsEmails::new_project_posted($newcampaign_id, $orga_name, '');
				NotificationsEmails::new_project_posted_owner($newcampaign_id, '');


				//Redirect then
				$page_dashboard = get_page_by_path('tableau-de-bord');
				$campaign_id_param = '?campaign_id=';
				$campaign_id_param .= $newcampaign_id;

				$redirect_url = get_permalink($page_dashboard->ID) . $campaign_id_param ."&lightbox=newproject#informations" ;
				wp_safe_redirect( $redirect_url);
			} else {
				wp_safe_redirect( home_url( '/financement#newproject' ) );
			}
        } else {
            wp_safe_redirect( home_url( '/financement#newproject' ) );
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
                if ($status==ATCF_Campaign::$campaign_status_validated && ($next_status==1)){
                    //Validé -> Avant-première
                    $campaign->set_status(ATCF_Campaign::$campaign_status_preview);
                    $campaign->set_validation_next_status(0);

                } else if ($status==ATCF_Campaign::$campaign_status_preview
                    || ($status==ATCF_Campaign::$campaign_status_validated &&($next_status==2))){
                    //Validé/Avant-première -> Vote

                    //Vérifiation organisation complète
                    $orga_done=false;
                    $campaign_organization = $campaign->get_organization();

                    //Vérification validation lemonway
                    $organization_obj = new WDGOrganization($campaign_organization->wpref);
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
                        }
                    }


                } else if ($status==ATCF_Campaign::$campaign_status_vote){
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
                        }
                    }
                }
            }
        }

		do_action('wdg_delete_cache', array(
			'home-projects',
			'projectlist-projects-current',
			'projectlist-projects-funded'
		));
        wp_safe_redirect(wp_get_referer());
        die();
    }
	
	/**
	 * Redirige vers la signature de mandat d'autorisation de prélèvement automatique
	 */
	public static function organization_sign_mandate() {
        $organization_id = sanitize_text_field( filter_input( INPUT_POST, 'organization_id' ) );
		$WDGUser_current = WDGUser::current();
		$phone_number = $WDGUser_current->wp_user->get('user_mobile_phone');
		$url_return = wp_get_referer();
		
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
		
		$url_return = wp_get_referer() . "#informations";
		wp_redirect( $url_return );
		die();
	}
	
	public static function upload_contract_files() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		
		
		$file_uploaded_data = $_FILES['new_backoffice_contract_user'];
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
			$campaign->__set( ATCF_Campaign::$key_backoffice_contract_user, $random_filename );
		}
		
		
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
		
		
		$url_return = wp_get_referer() . "#informations";
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
		$current_user = wp_get_current_user();
		$amount_total = $_SESSION['redirect_current_amount_part'];
		$investment_id = $campaign->add_investment( 'check', $current_user->user_email, $amount_total, 'pending' );
		
		
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
			wp_redirect( home_url( '/paiement-cheque' ) . '?campaign_id='.$campaign_id.'&check-return=post_invest_check' );
			
		} else {
			wp_redirect( home_url( '/paiement-cheque' ) . '?campaign_id='.$campaign_id.'&check-return=post_confirm_check&error-upload=1' );
			
		}
		exit();
	}
	
	public static function post_confirm_check() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$current_user = wp_get_current_user();
		$amount_total = $_SESSION['redirect_current_amount_part'];
		$investment_id = $campaign->add_investment( 'check', $current_user->user_email, $amount_total, 'pending' );
		
		NotificationsEmails::new_purchase_pending_check_user( $investment_id, FALSE );
		NotificationsEmails::new_purchase_pending_check_admin( $investment_id, FALSE );
		
		wp_redirect( home_url( '/paiement-cheque' ) . '?campaign_id='.$campaign_id.'&check-return=post_confirm_check' );
	}
}