<?php
class WDGFormProjects {
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
		if (!empty($image_header)) {
			$upload_overrides = array( 'test_form' => false );

			$upload = wp_handle_upload( $image_header, $upload_overrides );
			if (isset($upload[ 'url' ])) {
				$path = $_FILES['image_header']['name'];
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
	 * Gère les fichiers de comptes annuels
	 */
	public static function form_submit_yearly_account($year) {
		if (!isset($_GET["campaign_id"])) { return FALSE; }
		$campaign_id = $_GET["campaign_id"];
		
		$file = $_FILES[ 'accounts_year_' . $year ];
		if (!empty($file)) {
			$upload_overrides = array( 'test_form' => false );
			$upload = wp_handle_upload( $file, $upload_overrides );
			$file_name = $file['name'];
			if (isset($upload[ 'url' ])) {
				$attachment = array(
					'guid'           => $upload[ 'url' ], 
					'post_mime_type' => $upload[ 'type' ],
					'post_title'     => 'Yearly Accounts ' . $year,
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
				wp_insert_attachment( $attachment, $file_name, $campaign_id );
			}
		}
	}
	
	/**
	 * Teste si le formulaire de ROI est posté
	 * @param type $campaign
	 * @return boolean
	 */
	public static function form_proceed_roi_list($campaign) {
		if (!isset($_POST['action']) || $_POST['action'] != 'proceed_roi') {
			return FALSE;
		}
		
		$fp_date = $campaign->first_payment_date();
		$fp_yy = mysql2date( 'Y', $fp_date, false );
		for ($i = $fp_yy; $i < $campaign->funding_duration() + $fp_yy; $i++) {
		    WDGFormProjects::form_proceed_roi($campaign, $i);
		}
	}
	
	/**
	 * Lance la redirection vers la page de paiement
	 * @param type $year
	 */
	public static function form_proceed_roi($campaign, $year) {
		//Il faut avoir un id de projet et que l'année soit bien renseignée
		if (!isset($_GET["campaign_id"])) { return FALSE; }
		if (!isset($_POST["proceed_roi_" . $year ])) { return FALSE; }
		
		//Si il y a bien un montant à reverser
		$payment_amount = $campaign->payment_amount_for_year($year);
		if ($payment_amount > 0) {
		    
			//Récupération de l'organisation
			$api_project_id = BoppLibHelpers::get_api_project_id($campaign->ID);
			$current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
			if (isset($current_organisations) && count($current_organisations) > 0) {
				$current_organisation = $current_organisations[0];
			}
			
			//Si il y a bien une organisation
			if (isset($current_organisation)) {
				$page_wallet_management = get_page_by_path('gestion-financiere');
				$page_return = get_permalink($page_wallet_management->ID) . '?campaign_id=' . $campaign->ID . '&roi_date='.$_POST["proceed_roi_" . $year ].'&roi_year='.$year;
				$mangopay_newcontribution = ypcf_mangopay_contribution_user_to_account($campaign->ID, $current_organisation->organisation_wpref, $payment_amount, $page_return);
				if (isset($mangopay_newcontribution->ID)) {
					wp_redirect($mangopay_newcontribution->PaymentURL);
					exit();
				} else {
					return FALSE;
				}
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Valide le transfert d'argent vers le compte de l'organisation
	 */
	public static function form_proceed_roi_return() {
		//Vérification qu'on a les bonnes données pour procéder aux transferts
		if (isset($_GET['ContributionID']) && (!isset($_POST['action']))) {
			if (!isset($_GET["campaign_id"])) { return FALSE; }
			$contribution_id = $_GET['ContributionID'];
			if (!isset($contribution_id)) { return FALSE; }
			if (!isset($_GET["roi_date"])) { return FALSE; }
			if (!isset($_GET["roi_year"])) { return FALSE; }
			$campaign_id = $_GET["campaign_id"];
			$post_campaign = get_post($campaign_id);
			$campaign = atcf_get_campaign($post_campaign);

			//Si la contribution est validée
			$contribution_obj = ypcf_mangopay_get_contribution_by_id($contribution_id);
			if ($contribution_obj->IsSucceeded && $contribution_obj->IsCompleted) {

				//Récupération de l'organisation
				$api_project_id = BoppLibHelpers::get_api_project_id($campaign_id);
				$current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
				if (isset($current_organisations) && count($current_organisations) > 0) {
					$current_organisation = $current_organisations[0];
				}

				//On enregistre le versement comme effectué (le virement est au moins effectif jusqu'au compte utilisateur
				if (isset($current_organisation)) {
					//Enregistrement de la contribution
					$roi_post = array(
					    'post_author'   => $current_organisation->organisation_wpref,
					    'post_title'    => 'ROI ' . $contribution_obj->Amount,
					    'post_content'  => $contribution_obj->ID,
					    'post_status'   => 'pending',
					    'post_type'	    => 'roi_process'
					);
					$new_post_id = wp_insert_post( $roi_post );
					//Liaison du versement avec la contribution
					$campaign->update_payment_status($_GET["roi_date"], $_GET["roi_year"], $new_post_id);
				
				} else {
					return FALSE;
				}


			} else {
				return FALSE;
			}
		}
	}
	
	/**
	 * Lance les transferts d'argent vers les différents investisseurs
	 */
	public static function form_proceed_roi_transfers() {
		$campaign_id = filter_input(INPUT_GET, 'campaign_id');
		$payment_item_id = filter_input(INPUT_POST, 'roi_id');
		if (current_user_can('manage_options') && !empty($campaign_id) && !empty($payment_item_id)) {
			//Récupération des éléments à traiter
			$campaign = new ATCF_Campaign($campaign_id);
			$investments_list = $campaign->roi_payments_data($payment_item_id);
			//Récupération de l'organisation du projet
			$api_project_id = BoppLibHelpers::get_api_project_id($campaign_id);
			$current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
			if (isset($current_organisations) && count($current_organisations) > 0) {
				$current_organisation = $current_organisations[0];
			}
			//Transfert à tous les utilisateurs
			if (isset($current_organisation)) {
				foreach ($investments_list as $investment_item) {
					ypcf_mangopay_transfer_user_to_user($current_organisation->organisation_wpref, $investment_item['user'], $investment_item['roi_amount'], $investment_item['roi_fees']);
				}
			}
			$payment_post_id = $campaign->payment_status_for_year($payment_item_id);
			wp_update_post( array(
				'ID'		=> $payment_post_id,
				'post_status'	=> 'published'
			));
		}
	}
}
