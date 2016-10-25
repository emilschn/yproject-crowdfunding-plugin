<?php
class WDGFormProjects {
	/**
	 * Gère le formulaire d'ajout de langue
	 */
	public static function form_validate_lang_add() {
		global $campaign;
		$posted_lang = filter_input(INPUT_POST, 'selected-language');
		if (empty($posted_lang) || !$campaign->current_user_can_edit()) {
			return FALSE;
		}
		$campaign->add_lang($posted_lang);
	}
	
	/**
	 * Gère le formulaire d'ajout d'actualité
	 */
	public static function form_validate_news_add($campaign_id) {
		$post_campaign = get_post($campaign_id);
		$campaign = atcf_get_campaign($post_campaign);
		if (!$campaign->current_user_can_edit() 
				|| !isset($_POST['action'])
				|| $_POST['action'] != 'ypcf-campaign-add-news') {
			return FALSE;
		}

		$current_user = wp_get_current_user();

		$blog = array(
			'post_title'    => $_POST['posttitle'],
			'post_content'  => $_POST['postcontent'],
			'post_status'   => 'publish',
			'post_author'   => $current_user->ID,
			'post_category' => array( $campaign->get_news_category_id() )
		);

		$post_id = wp_insert_post($blog, true);
		do_action('wdg_delete_cache', array( 'project-header-menu-'.$post_campaign->ID ));
                
		//Envoi de notifications mails
		if (isset($_POST['send_mail']) && ($_POST['send_mail'])=='on'){
			NotificationsEmails::new_project_post_posted($campaign_id, $post_id);
		}
	}
	
	/**
	 * Check si on veut valider un paiement
	 */
	public static function form_approve_payment() {
		$current_wdg_user = WDGUser::current();
		$approve_payment_id = filter_input(INPUT_GET, 'approve_payment');
		$campaign_id = filter_input(INPUT_GET, 'campaign_id');
		if ( !empty( $approve_payment_id ) && !empty( $campaign_id ) && $current_wdg_user->is_admin() ) {
			$postdata = array(
				'ID'			=> $approve_payment_id,
				'post_status'	=> 'publish',
				'edit_date'		=> current_time( 'mysql' )
			);
			wp_update_post($postdata);
			
			do_action('wdg_delete_cache', array(
				'project-header-right-'.$campaign_id,
				'projects-current',
				'project-investments-data-'.$campaign_id
			));
			
			$page_dashboard = get_page_by_path('tableau-de-bord');
			wp_redirect( get_permalink( $page_dashboard->ID ) . '?campaign_id=' . $campaign_id . '&success_msg=approvepayment' );
			exit();
		}
	}
	
	/**
	 * Check si on veut annuler un paiement
	 */
	public static function form_cancel_payment() {
		$current_wdg_user = WDGUser::current();
		$cancel_payment_id = filter_input(INPUT_GET, 'cancel_payment');
		$campaign_id = filter_input(INPUT_GET, 'campaign_id');
		if ( !empty( $cancel_payment_id ) && !empty( $campaign_id ) && $current_wdg_user->is_admin() ) {
			$postdata = array(
				'ID'			=> $cancel_payment_id,
				'post_status'	=> 'failed',
				'edit_date'		=> current_time( 'mysql' )
			);
			wp_update_post($postdata);
			
			$page_dashboard = get_page_by_path('tableau-de-bord');
			wp_redirect( get_permalink( $page_dashboard->ID ) . '?campaign_id=' . $campaign_id . '&success_msg=cancelpayment' );
			exit();
		}
	}
	
	/**
	 * Gère le formulaire de paramètres projets
	 */
	public static function form_validate_edit_parameters() {
		$buffer = TRUE;
	    
		if (!isset($_GET["campaign_id"])) { return FALSE; }
		$campaign_id = $_GET["campaign_id"];
		$post_campaign = get_post($campaign_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		if (!$campaign->current_user_can_edit() 
				|| !isset($_POST['action'])
				|| $_POST['action'] != 'edit-project-parameters') {
			return FALSE;
		}
		
		$title = sanitize_text_field($_POST['project-name']);
		if (!empty($title)) {
			wp_update_post(array(
				'ID' => $campaign_id,
				'post_title' => $title
			));
		} else {
			$buffer = FALSE;
		}
		
		$cat_cat_id = -1; $cat_act_id = -1;
		if (isset($_POST['categories'])) { $cat_cat_id = $_POST['categories']; } else { $buffer = FALSE; }
		if (isset($_POST['activities'])) { $cat_act_id = $_POST['activities']; } else { $buffer = FALSE; }
		if ($cat_cat_id != -1 && $cat_act_id != -1) {
			$cat_ids = array_map( 'intval', array($cat_cat_id, $cat_act_id) );
			wp_set_object_terms($campaign_id, $cat_ids, 'download_category');
		}
		
		if (isset($_POST['project-location'])) {
			update_post_meta($campaign_id, 'campaign_location', $_POST['project-location']);
		} else {
			$buffer = FALSE;
		}
		
		
		/* Gestion fichiers / images */
		$image_header = $_FILES[ 'image_header' ];

		WDGFormProjects::edit_image_banniere($image_header, $campaign_id);
		

		$image = $_FILES[ 'image_home' ];

		WDGFormProjects::edit_image_url_video($image, $_POST['video'], $campaign_id);

		
		$temp_blur = $_POST['image_header_blur'];
		if (empty($temp_blur)) $temp_blur = 'FALSE';
		update_post_meta($campaign_id, 'campaign_header_blur_active', $temp_blur);
		/* FIN Gestion fichiers / images */
		
		
		if (isset($_POST['project-organisation'])) {
			//Récupération de l'ancienne organisation
			$api_project_id = BoppLibHelpers::get_api_project_id($post_campaign->ID);
			$current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
			$current_organisation = FALSE;
			if (count($current_organisations) > 0) {
			    $current_organisation = $current_organisations[0];
			}
			
			$delete = FALSE;
			$update = FALSE;
			
			//On met à jour : si une nouvelle organisation est renseignée et différente de celle d'avant
			//On supprime : si la nouvelle organisation renseignée est différente de celle d'avant
			if (!empty($_POST['project-organisation'])) {
				$organisation_selected = new YPOrganisation($_POST['project-organisation']);
				if ($current_organisation === FALSE || $current_organisation->organisation_wpref != $organisation_selected->get_wpref()) {
					$update = TRUE;
					if ($current_organisation !== FALSE) {
						$delete = TRUE;
					}
				}
				
			//On supprime : si rien n'est sélectionné + il y avait quelque chose avant
			} else {
				if ($current_organisation !== FALSE) {
					$delete = TRUE;
				}
			}
			
			if ($delete) {
				BoppLib::unlink_organisation_from_project($api_project_id, $current_organisation->id);
			}
				
			if ($update) {
				$api_organisation_id = $organisation_selected->get_bopp_id();
				BoppLib::link_organisation_to_project($api_project_id, $api_organisation_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
			}
			
		} else {
			$buffer = FALSE;
		}
		
		if (isset($_POST['fundingtype'])) { 
			if ($_POST['fundingtype'] == 'fundingdevelopment' || $_POST['fundingtype'] == 'fundingproject' || $_POST['fundingtype'] == 'fundingdonation') {
				update_post_meta($campaign_id, 'campaign_funding_type', $_POST['fundingtype']);
				if($_POST['fundingtype'] == 'fundingdonation'){
					update_post_meta($campaign_id, '_variable_pricing', true );
					update_post_meta($campaign_id, 'campaign_part_value', 1 );
				} else {
					update_post_meta($campaign_id, '_variable_pricing', false );
				}
			} else {
				$buffer = FALSE;
			}
		}
		if (isset($_POST['fundingduration'])) { 
			$duration = $_POST['fundingduration'];
			if (is_numeric($duration) && $duration > 0 && (int)$duration == $duration) {
				update_post_meta($campaign_id, 'campaign_funding_duration', $duration); 
			} else {
				$buffer = FALSE;
			}
		}
		if (isset($_POST['minimum_goal'])) {
			$minimum_goal = $_POST['minimum_goal'];
			if (is_numeric($minimum_goal) && $minimum_goal > 0 && (int)$minimum_goal == $minimum_goal) {
				update_post_meta($campaign_id, 'campaign_minimum_goal', $minimum_goal); 
			} else {
				$buffer = FALSE;
			}
		}
		if (isset($_POST['maximum_goal'])) {
			$goal = $_POST['maximum_goal'];
			if (is_numeric($goal) && $goal > 0 && (int)$goal == $goal) {
				if ($goal < $minimum_goal) $goal = $minimum_goal;
				update_post_meta($campaign_id, 'campaign_goal', $goal); 
			} else {
				$buffer = FALSE;
			}
		}
		
		//Gestion des contreparties
		if (isset($_POST['reward-name-0'])){
			$i = 0;
			$new_rewards = array();

			while (isset($_POST['reward-name-'.$i]) 
				&& isset($_POST['reward-amount-'.$i]) 
				&& isset($_POST['reward-limit-'.$i])){

				$new_rewards[$i]['name'] = sanitize_text_field($_POST['reward-name-'.$i]);
				$new_rewards[$i]['amount'] = abs(intval($_POST['reward-amount-'.$i]));
				$new_rewards[$i]['limit'] = abs(intval($_POST['reward-limit-'.$i]));

				if (isset($_POST['reward-id-'.$i])){
						$new_rewards[$i]['id'] = ($_POST['reward-id-'.$i]);
				}
				$i++;
			}
			atcf_get_rewards($campaign_id)->update_rewards_data($new_rewards);
		}
                
		do_action('wdg_delete_cache', array(
			'project-header-image-' . $campaign_id, 
			'project-content-summary-' . $campaign_id,
			'project-content-about-' . $campaign_id,
			'projects-current',
			'projects-others'
		));
		
		if ($buffer && isset($_POST['new_orga'])) {
			$page_new_orga = get_page_by_path('creer-une-organisation');
			header('Location: ' . get_permalink($page_new_orga->ID));
		}
		    
		return $buffer;
	}

	/**
	 * Gère l'édition image bannière
	 */
	public static function edit_image_banniere($image_header, $campaign_id) {
		if (!empty($image_header)) {
			$upload_overrides = array( 'test_form' => false );

			$upload = wp_handle_upload( $image_header, $upload_overrides );
			if (isset($upload[ 'url' ])) {
				$path = $image_header['name'];
				$ext = pathinfo($path, PATHINFO_EXTENSION);
				$is_image_accepted = true;
				switch (strtolower($ext)) {
					case 'png':
						$image_header = imagecreatefrompng($upload[ 'file' ]);
						break;
					case 'jpg':
					case 'jpeg':
						$image_header = imagecreatefromjpeg($upload[ 'file' ]);
						break;
					default:
						$is_image_accepted = false;
						break;
				}
				if ($is_image_accepted) {
					/*for ($i = 0; $i < 10; $i++) {
						imagefilter($image_header, IMG_FILTER_GAUSSIAN_BLUR);
						imagefilter($image_header, IMG_FILTER_SELECTIVE_BLUR);
					}*/
					$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $upload[ 'file' ]);
					$img_name = $withoutExt.'_noblur.jpg';
					imagejpeg($image_header,$img_name);

					//Suppression dans la base de données de l'ancienne image
					global $wpdb;
					$table_posts = $wpdb->prefix . "posts";
					$old_attachement_id = $wpdb->get_var( "SELECT * FROM ".$table_posts." WHERE post_parent=".$campaign_id." and post_title='image_header'" );
					wp_delete_attachment( $old_attachement_id, true );
					
					$attachment = array(
						'guid'           => $upload[ 'url' ], 
						'post_mime_type' => $upload[ 'type' ],
						'post_title'     => 'image_header',
						'post_content'   => '',
						'post_status'    => 'inherit'
					);
					$attach_id = wp_insert_attachment( $attachment, $img_name, $campaign_id );		

					wp_update_attachment_metadata( 
						$attach_id, 
						wp_generate_attachment_metadata( $attach_id, $img_name ) 
					);
					//Suppression de la position de la couverture
					delete_post_meta($campaign_id, 'campaign_cover_position');

					add_post_meta( $campaign_id, '_thumbnail_id', absint( $attach_id ) );
				}
			}
		}
	}


	public static function edit_image_url_video($image, $post_video, $campaign_id) {
		//ajout de l'image
		if (!empty($image)) {
			$upload_overrides = array( 'test_form' => false );
			$upload = wp_handle_upload( $image, $upload_overrides );
			if (isset($upload[ 'url' ])) {
				$attachment = array(
					'guid'           => $upload[ 'url' ], 
					'post_mime_type' => $upload[ 'type' ],
					'post_title'     => 'image_home',
					'post_content'   => '',
					'post_status'    => 'inherit'
				);

				//Suppression dans la base de données de l'ancienne image
				global $wpdb;
				$table_posts = $wpdb->prefix . "posts";
				$old_attachement_id = $wpdb->get_var( "SELECT * FROM ".$table_posts." WHERE post_parent=".$campaign_id." and post_title='image_home'" );
				wp_delete_attachment($old_attachement_id, true);

				$attach_id = wp_insert_attachment($attachment, $upload[ 'file' ], $campaign_id);		

				wp_update_attachment_metadata( 
					$attach_id, 
					wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
				);
			}
		}
		//ajout de l'url de la vidéo
		if (isset($post_video)) {
			update_post_meta($campaign_id, 'campaign_video', esc_url($post_video));
		}
	}

	public static function form_submit_turnover() {
		if (!isset($_GET["campaign_id"]) || !isset($_POST["action"]) || $_POST["action"] != 'save-turnover-declaration') { return FALSE; }
		$declaration_id = filter_input( INPUT_POST, 'declaration-id' );
		if (empty($declaration_id)) { return FALSE; }
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign($_GET["campaign_id"]);
		
		$turnover_declaration = filter_input( INPUT_POST, 'turnover-total' );
		$saved_declaration = array();
		$total_turnover = 0;
		if (!empty($turnover_declaration)) {
			$total_turnover += $turnover_declaration;
			array_push($saved_declaration, $turnover_declaration);
		} else {
			$nb_turnover = $campaign->get_turnover_per_declaration();
			for ($i = 0; $i < $nb_turnover; $i++) {
				$turnover_declaration = filter_input( INPUT_POST, 'turnover-' . $i );
				$total_turnover += $turnover_declaration;
				array_push($saved_declaration, $turnover_declaration);
			}
		}
		$declaration->set_turnover($saved_declaration);
		$declaration->amount = round( ($total_turnover * $campaign->roi_percent() / 100) * 100 ) / 100;
		if ($declaration->amount == 0) {
			NotificationsEmails::turnover_declaration_null( $declaration_id );
		}
		$declaration->status = WDGROIDeclaration::$status_payment;
		$declaration->save();
	}
	
	/**
	 * Gère les fichiers de comptes annuels
	 */
	public static function form_submit_account_files() {
		if (!isset($_GET["campaign_id"])) { return FALSE; }
		$campaign_id = filter_input(INPUT_GET, 'campaign_id');
		
		$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $campaign_id );
		foreach ( $declaration_list as $declaration ) {
			$file = $_FILES[ 'accounts_file_' . $declaration->id ];
			if ( !empty( $file ) ) {
				$declaration->add_file($file);
			}
		}
	}
	
	public static function form_submit_roi_payment() {
		if (!isset($_POST['action']) || $_POST['action'] != 'proceed_roi' || !isset($_POST['proceed_roi_id']) || !isset($_GET['campaign_id'])) {
			return FALSE;
		}
		
		$roi_id = filter_input( INPUT_POST, 'proceed_roi_id' );
		$roi_declaration = new WDGROIDeclaration( $roi_id );
		$campaign = atcf_get_current_campaign();
		$api_project_id = BoppLibHelpers::get_api_project_id($campaign->ID);
		$current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
		$current_organisation = $current_organisations[0];
		$organisation = new YPOrganisation($current_organisation->organisation_wpref);
		
		if (isset($_POST['payment_card'])) {
			//$wallet_id, $amount, $amount_com, $wk_token, $return_url, $error_url, $cancel_url
			$page_wallet = get_page_by_path('gestion-financiere');	// Gestion financière
			$campaign_id_param = '?campaign_id=' . $campaign->ID;
			$return_url = get_permalink($page_wallet->ID) . $campaign_id_param;
			$wk_token = LemonwayLib::make_token('', $roi_id);
			$roi_declaration->payment_token = $wk_token;
			$roi_declaration->save();
			$organisation->register_lemonway();
			$return = LemonwayLib::ask_payment_webkit($organisation->get_lemonway_id(), $roi_declaration->get_amount_with_commission(), $roi_declaration->get_commission_to_pay(), $wk_token, $return_url, $return_url, $return_url);
			if ( !empty($return->MONEYINWEB->TOKEN) ) {
				wp_redirect(YP_LW_WEBKIT_URL . '?moneyInToken=' . $return->MONEYINWEB->TOKEN);
			} else {
				return "error_lw_payment";
			}
			
		} elseif (isset($_POST['payment_wire'])) {
			
		}
	}
	
	public static function return_lemonway_card() {
		$buffer = FALSE;
		
		$wk_token = filter_input( INPUT_GET, 'response_wkToken' );
		if ( !empty( $wk_token ) ) {
			$declaration = WDGROIDeclaration::get_by_payment_token( $wk_token );
			if ($declaration->status == WDGROIDeclaration::$status_payment) {

				//Si le paiement est réussi
				$transaction_result = LemonwayLib::get_transaction_by_id( $wk_token );
				if ( $transaction_result->STATUS == 3 ) {
						$date_now = new DateTime();
						$declaration->date_paid = $date_now->format( 'Y-m-d' );
						$declaration->mean_payment = WDGROIDeclaration::$mean_payment_card;
						$campaign = new ATCF_Campaign( $declaration->id_campaign );
						$declaration->amount_commission = $campaign->get_costs_to_organization();
						$declaration->status = WDGROIDeclaration::$status_transfer;
						$declaration->save();
						NotificationsEmails::send_notification_roi_payment_success_admin( $declaration->id );
						NotificationsEmails::send_notification_roi_payment_success_user( $declaration->id );
						$buffer = TRUE;

				} else {
					NotificationsEmails::send_notification_roi_payment_error_admin( $declaration->id );
					$buffer = $transaction_result->INT_MSG;

				}
			}
		}
		
		return $buffer;
	}
	/**
	 * Lance les transferts d'argent vers les différents investisseurs
	 */
	public static function form_proceed_roi_transfers() {
		if (!isset($_POST['action']) || $_POST['action'] != 'proceed_roi_transfers' || !isset($_POST['roi_id']) || !isset($_GET['campaign_id'])) {
			return FALSE;
		}
		
		$campaign_id = filter_input(INPUT_GET, 'campaign_id');
		$declaration_id = filter_input(INPUT_POST, 'roi_id');
		if ( current_user_can('manage_options') && !empty( $campaign_id ) && !empty( $declaration_id ) ) {
			$roi_declaration = new WDGROIDeclaration( $declaration_id );
			$roi_declaration->make_transfer();
		}
	}

    /**
     * Crée le contenu du mail envoyé via le dashboard
     * @param $initial_content Texte brut entré par l'utilisateur
     * @param $mail_title Titre brut
     * @param $campaign_id
     * @param array $variables Données pour remplacer les éléments de texte
     *          ['investwish'] Valeur pour remplacer %investwish%
     *          ['username'] Valeur pour remplacer %username%
     * @return array
     *          ['title'] Titre transformé
     *          ['body'] Contenu du mail transformé
     */
    public static function build_mail_text($initial_content, $mail_title, $campaign_id, $variables = array()){
        $post_campaign = get_post($campaign_id);

        $author = get_userdata($post_campaign->post_author);
        $campaign_author_str = $author->first_name . ' ' . $author->last_name;
        if (empty($campaign_author_str)) { $campaign_author_str = $author->user_login; }

        $username = $variables['username'];
        if(empty($username)){
            $username = "<i>(Nom du destinataire)</i>";
        }

        $investwish = $variables['investwish'];
        if(empty($investwish)){
            $investwish = "<i>(Intention d'investissement)</i>";
        }


        $body_content = '<div style="font-family: sans-serif; padding: 10px 5%;">'
            .'<h1 style="text-align: center;">'.$mail_title.'</h1>';

        $body_content .= $initial_content.'<br/>';

        $body_content .= '<div style="text-align: center;">'
            .'<a href="'.get_permalink($post_campaign->ID).'" style="background-color: rgb(255, 73, 76); margin-bottom:10px; padding: 10px; color: rgb(255, 255, 255); text-decoration: none; display: inline-block;" target="_blank">
                    Voir le projet</a><br/>'
            .'Message envoy&eacute; par '
            .'<a style="color: rgb(255, 73, 76);" href="'.get_permalink($campaign_id).'" target="_blank">'
            .$post_campaign->post_title.'</a><br/><br/>'
            .'<em>Vous avez re&ccedil;u ce mail car vous croyez au projet %projectname%
            . Si vous ne souhaitez plus recevoir de mail des actualités de ce projet, rendez-vous sur '
            .'votre page "Mon Compte" WE DO GOOD pour désactiver les notifications de ce projet.</em>'
            . '</div></div>';

        $body_content = str_replace('%projectname%', $post_campaign->post_title, $body_content);
        $body_content = str_replace('%projecturl%', '<a target="_blank" href="'.get_permalink($post_campaign->ID).'">'.get_permalink($post_campaign->ID).'</a>', $body_content);
        $body_content = str_replace('%projectauthor%', $campaign_author_str, $body_content);
        $body_content = str_replace('%username%', $username, $body_content);
        $body_content = str_replace('%investwish%', $investwish, $body_content);

        $transformed_title = $post_campaign->post_title.' : '.$mail_title;
        $transformed_title = str_replace('%projectname%', $post_campaign->post_title, $transformed_title);
        $transformed_title = str_replace('%projecturl%', '<a target="_blank" href="'.get_permalink($post_campaign->ID).'">'.get_permalink($post_campaign->ID).'</a>', $transformed_title);
        $transformed_title = str_replace('%projectauthor%', $campaign_author_str, $transformed_title);
        $transformed_title = str_replace('%username%', $username, $transformed_title);
        $transformed_title = str_replace('%investwish%', $investwish, $transformed_title);

        return array(
            'title'=>$transformed_title,
            'body'=>$body_content
        );
    }
}
