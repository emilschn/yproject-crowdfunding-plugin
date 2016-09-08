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
    }

    /**
     * Ajoute une action WordPress à exécuter en Post/get
     * @param string $action_name
     */
    public static function add_action($action_name) {
        add_action('admin_post_' . $action_name, array(WDGPostActions::$class_name, $action_name));
        add_action('admin_post_nopriv_' . $action_name, array(WDGPostActions::$class_name, $action_name));
    }

    public static function send_project_mail(){
        global $wpdb;
        $campaign_id = sanitize_text_field(filter_input(INPUT_POST,'campaign_id'));
        $mail_title = sanitize_text_field(filter_input(INPUT_POST,'mail_title'));
        $mail_content = filter_input(INPUT_POST,'mail_content');
        $mail_recipients = (json_decode("[".filter_input(INPUT_POST,'mail_recipients')."]"));

        NotificationsEmails::project_mail($campaign_id, $mail_title, $mail_content, $mail_recipients);

        wp_safe_redirect( wp_get_referer()."#page-contacts" );
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
        $project_notoriety = sanitize_text_field(filter_input(INPUT_POST,'project-WDGnotoriety'));

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

        if(!empty($orga_name) && !empty($project_name) && !empty($project_desc) && !empty($project_notoriety)){
            //Project data
            $newcampaign_id = atcf_create_campaign($WPuserID, $project_name);
            $newcampaign = atcf_get_campaign($newcampaign_id);

            $newcampaign->__set(ATCF_Campaign::$key_backoffice_summary, $project_desc);
            $newcampaign->__set(ATCF_Campaign::$key_backoffice_WDG_notoriety, $project_notoriety);


            //Company data
            $organisation_created = YPOrganisation::createSimpleOrganisation($WPuserID,$orga_name,$WDGUser_current->wp_user->user_email);
            $api_organisation_id = $organisation_created->get_bopp_id();
            $api_project_id = BoppLibHelpers::get_api_project_id($newcampaign_id);
            BoppLib::link_organisation_to_project($api_project_id, $api_organisation_id, BoppLibHelpers::$project_organisation_manager_role['slug']);


            //Redirect then
            $page_dashboard = get_page_by_path('tableau-de-bord');
            $campaign_id_param = '?campaign_id=';
            $campaign_id_param .= $newcampaign_id;

            $redirect_url = get_permalink($page_dashboard->ID) . $campaign_id_param ."&lightbox=newproject##informations" ;
            wp_safe_redirect( $redirect_url);
            exit();
        } else {
            echo "0";
            die();
        }

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

            if ( $status == ATCF_Campaign::$campaign_status_preparing && $is_admin ) {
				$save_validation_steps = filter_input( INPUT_POST, 'validation-next-save' );
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
                    $api_project_id = BoppLibHelpers::get_api_project_id($campaign_id);
                    $current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);

                    if (isset($current_organisations) && count($current_organisations) > 0) {
                        $campaign_organisation = $campaign->get_organisation();

                        //Vérification validation lemonway
                        $organization_obj = new YPOrganisation($campaign_organisation->organisation_wpref);
                        if ($organization_obj->is_registered_lemonway_wallet()) { $orga_done = true; }
                    }

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

        wp_safe_redirect(wp_get_referer());
        die();
    }
}