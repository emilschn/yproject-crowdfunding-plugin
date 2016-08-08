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

		$category_slug = $post_campaign->ID . '-blog-' . $post_campaign->post_name;
		$category_obj = get_category_by_slug($category_slug);

		$blog = array(
			'post_title'    => $_POST['posttitle'],
			'post_content'  => $_POST['postcontent'],
			'post_status'   => 'publish',
			'post_author'   => $current_user->ID,
			'post_category' => array($category_obj->cat_ID)
		);

		$post_id = wp_insert_post($blog, true);
		do_action('wdg_delete_cache', array( 'project-header-menu-'.$post_campaign->ID ));
                
		//Envoi de notifications mails
		if (isset($_POST['send_mail']) && ($_POST['send_mail'])=='on'){
			NotificationsEmails::new_project_post_posted($campaign_id, $post_id);
		}
	}
		
	/**
	 * Vérifie si l'utilisateur essaie d'envoyer des mails via la lightbox dashboard-mail
	 */
	public static function form_validate_send_mail() {
		if (isset($_POST['send_mail'])){
			global $campaign_id, $feedback, $preview;
			$feedback = "";
			if ((isset($_POST['mail_title']) && isset($_POST['mail_content']))
					&&($_POST['mail_title']!='' && $_POST['mail_content']!='')){
				$jycrois = isset($_POST['jycrois']) && ($_POST['jycrois']=='on');
				$voted = isset($_POST['voted']) && ($_POST['voted']=='on');
				$invested = isset($_POST['invested']) && ($_POST['invested']=='on');
				$id_investors_list = explode(",", $_POST['investors_id']);

					if ($_POST['send_mail']=='send'){
						//Au moins une catégorie sélectionnée
						if ($jycrois || $voted || $invested){
							$feedback_email = NotificationsEmails::project_mail($campaign_id, 
								$_POST['mail_title'], 
								$_POST['mail_content'], 
								$jycrois, 
								$voted, 
								$invested,
								$id_investors_list);

							$nb_try = count($feedback_email);
							$nb_errors = $nb_try - count(array_filter($feedback_email));
							if ($nb_errors <= 0){
								$feedback .= "Les messages ont &eacute;t&eacute; correctement envoy&eacute;s !";
							} else {
								$feedback .= "Les messages ont &eacute;t&eacute; envoy&eacute;s mais des erreurs ont eu lieu.";
							}

						} else {
							$feedback .= "Vous n'avez pas s&eacute;lectionn&eacute; de groupe &agrave; qui envoyer le message. ";
						}
					}
					else if ($_POST['send_mail']=='preview'){
						unset($feedback);
						$preview = NotificationsEmails::project_mail($campaign_id, 
								$_POST['mail_title'], 
								$_POST['mail_content'], 
								false, 
								false, 
								false,
								array(),
								true);
					}

			} else {
				$feedback .= "Il faut donner un objet et un contenu &agrave; votre mail. ";
			}
		}
		return $feedback;
	}
	
	/**
	 * Formulaire d'envoi des mails automatiques
	 */
	public static function form_validate_send_automail() {
		$send_automail = filter_input(INPUT_POST, 'send_automail');
		if ($send_automail != 'send') { return FALSE; }
		
		$automailvoters_object = filter_input(INPUT_POST, 'automailvoters_object');
		$automailvoters_content = filter_input(INPUT_POST, 'automailvoters_content');
		
		if (empty($automailvoters_object) || empty($automailvoters_content)) {
			$feedback = __("L'objet et le contenu du mail doivent &ecirc;tre renseign&eacute;s", 'yproject');
			
		} else {
			$automailvoters_minwish = filter_input(INPUT_POST, 'automailvoters_minwish');
			if (!($automailvoters_minwish > 0)) { $automailvoters_minwish = 0; }
			
			global $wpdb;
			$campaign_id = filter_input(INPUT_GET, 'campaign_id');
			$post_campaign = get_post($campaign_id);
			$object = $post_campaign->post_title. ' : ' .$automailvoters_object;
			$campaign_url_str = '<a href="'.get_permalink($post_campaign->ID).'">'.get_permalink($post_campaign->ID).'</a>';
			$author = get_userdata($post_campaign->post_author);
			$campaign_author_str = $author->first_name . ' ' . $author->last_name;
			if (empty($campaign_author_str)) { $campaign_author_str = $author->user_login; }

			$table_vote = $wpdb->prefix . "ypcf_project_votes";
			$list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum FROM ".$table_vote." WHERE post_id = ".$campaign_id." AND validate_project = 1 AND invest_sum >= ". $automailvoters_minwish );

			$nb_mail = count($list_user_voters);
			foreach ($list_user_voters as $vote) {
				$user = get_userdata(intval($vote->user_id));
				$to = $user->user_email;
				$user_str = $user->first_name . ' ' . $user->last_name;
				if (empty($user_str)) { $user_str = $user->user_login; }
				$automailvoters_content = str_replace('%projectname%', $post_campaign->post_title, $automailvoters_content);
				$automailvoters_content = str_replace('%projecturl%', $campaign_url_str, $automailvoters_content);
				$automailvoters_content = str_replace('%projectauthor%', $campaign_author_str, $automailvoters_content);
				$automailvoters_content = str_replace('%username%', $user_str, $automailvoters_content);
				$automailvoters_content = str_replace('%investwish%', $vote->invest_sum, $automailvoters_content);
				NotificationsEmails::send_mail($to, $object, $automailvoters_content, true);
			}
			$feedback = __("Votre message a &eacute;t&eacute; envoy&eacute; &agrave; ", 'yproject') . $nb_mail . __(" utilisateur(s).", 'yproject');
		}
		
		return $feedback;
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
                
                if (isset($_POST['phone'])) {
			update_post_meta($campaign_id, 'campaign_contact_phone', sanitize_text_field($_POST['phone']));
		} else {
			$buffer = FALSE;
		}
		
		if (isset($_POST['project-location'])) {
			update_post_meta($campaign_id, 'campaign_location', $_POST['project-location']);
		} else {
			$buffer = FALSE;
		}
		
		if (isset($_POST['video'])) {
			update_post_meta($campaign_id, 'campaign_video', esc_url($_POST['video']));
		}
		
		/* Gestion fichiers / images */
		$image_header = $_FILES[ 'image_header' ];

		edit_image_banniere($image_header, $campaign_id);
		

		$image = $_FILES[ 'image_home' ];
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
					for ($i = 0; $i < 10; $i++) {
						imagefilter($image_header, IMG_FILTER_GAUSSIAN_BLUR);
						imagefilter($image_header, IMG_FILTER_SELECTIVE_BLUR);
					}
					$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $upload[ 'file' ]);
					$img_name = $withoutExt.'_blur.jpg';
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
}
