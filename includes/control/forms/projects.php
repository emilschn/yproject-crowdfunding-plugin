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
		$send_mail = filter_input( INPUT_POST, 'send_mail' );
		if ( $send_mail == 'on' ) {
			$campaign_author = $campaign->post_author();
			$author_user = get_user_by( 'ID', $campaign_author );
			$replyto_mail = $author_user->user_email;
			global $wpdb;
			$table_jcrois = $wpdb->prefix . "jycrois";
			$result_jcrois = $wpdb->get_results( "SELECT user_id FROM ".$table_jcrois." WHERE subscribe_news = 1 AND campaign_id = ".$campaign_id);
			$recipients = array();
			foreach ($result_jcrois as $item) {
				array_push( $recipients, get_userdata( $item->user_id )->user_email );
			}
			$recipients_string = implode( ',', $recipients );
			NotificationsAPI::new_project_news( $recipients_string, $replyto_mail, $post_campaign->post_title, get_permalink( $campaign_id ), $_POST[ 'posttitle' ], $_POST[ 'postcontent' ] );
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
				
			// - Créer le contrat pdf
			// - Envoyer validation d'investissement par mail
			$user_info = edd_get_payment_meta_user_info( $approve_payment_id );
			$campaign = new ATCF_Campaign( $campaign_id );
			
			$new_contract_pdf_file = getNewPdfToSign( $campaign_id, $approve_payment_id, $user_info['id'] );
			NotificationsEmails::new_purchase_user_success_nocontract( $approve_payment_id, $new_contract_pdf_file, FALSE, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
			NotificationsEmails::new_purchase_admin_success_nocontract( $approve_payment_id, $new_contract_pdf_file );
			
			do_action('wdg_delete_cache', array(
				'home-projects',
				'projectlist-projects-current'
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
		$buffer = '';
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
				$buffer .= $upload[ 'url' ] . '|';
			}
		}
		//ajout de l'url de la vidéo
		if (isset($post_video)) {
			update_post_meta($campaign_id, 'campaign_video', esc_url($post_video));
			$buffer .= $post_video . '|';
		}
		return $buffer;
	}

	public static function form_submit_turnover() {
		if (!isset($_GET["campaign_id"]) || !isset($_POST["action"]) || $_POST["action"] != 'save-turnover-declaration') { return FALSE; }
		$declaration_id = filter_input( INPUT_POST, 'declaration-id' );
		if (empty($declaration_id)) { return FALSE; }
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign($_GET["campaign_id"]);
		
		$declaration_message = filter_input( INPUT_POST, 'declaration-message' );
		$employees_number = filter_input( INPUT_POST, 'employees-number' );
		if ( !is_numeric( $employees_number ) ) {
			$employees_number = 0;
		} else {
			if ( !is_int( $employees_number ) ) {
				$employees_number = round( $employees_number );
			}
			if ( $employees_number < 0 ) {
				$employees_number = 0;
			}
		}
		$other_fundings = filter_input( INPUT_POST, 'other-fundings' );
		
		$has_error = false;
		$saved_declaration = array();
		$total_turnover = 0;
		$turnover_declaration = filter_input( INPUT_POST, 'turnover-total' );
		if (!empty($turnover_declaration)) {
			$turnover_declaration = str_replace( ',', '.', $turnover_declaration );
			if ( is_numeric( $turnover_declaration ) ) {
				$total_turnover += $turnover_declaration;
				array_push($saved_declaration, $turnover_declaration);
			} else {
				$has_error = true;
			}
			
		} else {
			$nb_turnover = $campaign->get_turnover_per_declaration();
			for ($i = 0; $i < $nb_turnover; $i++) {
				$turnover_declaration = filter_input( INPUT_POST, 'turnover-' . $i );
				$turnover_declaration = str_replace( ',', '.', $turnover_declaration );
				if ( is_numeric( $turnover_declaration ) ) {
					$total_turnover += $turnover_declaration;
					array_push($saved_declaration, $turnover_declaration);
				} else {
					$has_error = true;
				}
			}
		}
		
		if ( !$has_error ) {
			$declaration->set_turnover($saved_declaration);
			$declaration->percent_commission = $campaign->get_costs_to_organization();
			$declaration->amount = round( ($total_turnover * $campaign->roi_percent() / 100) * 100 ) / 100;
			if ($declaration->amount == 0) {
				NotificationsEmails::turnover_declaration_null( $declaration_id );
				$declaration->status = WDGROIDeclaration::$status_transfer;
			} else {
				$declaration->status = WDGROIDeclaration::$status_payment;
			}
			$declaration->employees_number = $employees_number;
			$declaration->set_other_fundings( $other_fundings );
			$declaration->set_message( $declaration_message );
			$declaration->save();
		}
	}
	
	/**
	 * Gère les fichiers de comptes annuels
	 */
	public static function form_submit_account_files() {
		if (!isset($_GET["campaign_id"])) { return FALSE; }
		$campaign_id = filter_input(INPUT_GET, 'campaign_id');
		
		$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $campaign_id );
		foreach ( $declaration_list as $declaration ) {
			$file = $_FILES[ 'accounts_file_' .$declaration->id ];
			if ( !empty( $file ) ) {
				$file_description = htmlentities( filter_input( INPUT_POST, 'info_file_' .$declaration->id ) );
				$declaration->add_file( $file, $file_description );
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
		$current_organization = $campaign->get_organization();
		$organization = new WDGOrganization($current_organization->wpref);
		
		if (isset($_POST['payment_card'])) {
			//$wallet_id, $amount, $amount_com, $wk_token, $return_url, $error_url, $cancel_url
			$page_wallet = get_page_by_path('tableau-de-bord');	// Tableau de bord
			$campaign_id_param = '?campaign_id=' . $campaign->ID;
			$return_url = get_permalink($page_wallet->ID) . $campaign_id_param;
			$wk_token = LemonwayLib::make_token('', $roi_id);
			$roi_declaration->payment_token = $wk_token;
			$roi_declaration->save();
			$organization->register_lemonway();
			$return = LemonwayLib::ask_payment_webkit($organization->get_lemonway_id(), $roi_declaration->get_amount_with_commission(), $roi_declaration->get_commission_to_pay(), $wk_token, $return_url, $return_url, $return_url);
			if ( !empty($return->MONEYINWEB->TOKEN) ) {
				wp_redirect(YP_LW_WEBKIT_URL . '?moneyInToken=' . $return->MONEYINWEB->TOKEN);
				exit();
			} else {
				return "error_lw_payment";
			}
			
		} elseif (isset($_POST['payment_bank_transfer'])) {
			$date_now = new DateTime();
			$roi_declaration->date_paid = $date_now->format( 'Y-m-d' );
			$roi_declaration->mean_payment = WDGROIDeclaration::$mean_payment_wire;
			$roi_declaration->status = WDGROIDeclaration::$status_waiting_transfer;
			$roi_declaration->save();
			NotificationsEmails::send_notification_roi_payment_bank_transfer_admin( $roi_declaration->id );
			
		} elseif ( isset( $_POST[ 'payment_mandate' ] ) ) {
			$wallet_id = $organization->get_lemonway_id();
			$saved_mandates_list = $organization->get_lemonway_mandates();
			if ( !empty( $saved_mandates_list ) ) {
				$last_mandate = end( $saved_mandates_list );
			}
			$mandate_id = $last_mandate['ID'];

			if ( !empty( $wallet_id ) && !empty( $mandate_id ) ) {
				$result = LemonwayLib::ask_payment_with_mandate( $wallet_id, $roi_declaration->get_amount_with_commission(), $mandate_id, $roi_declaration->get_commission_to_pay() );
				$buffer = ($result->TRANS->HPAY->ID) ? "success" : $result->TRANS->HPAY->MSG;

				if ($buffer == "success") {
					// Enregistrement de l'objet Lemon Way
					$withdrawal_post = array(
						'post_author'   => $current_organization->wpref,
						'post_title'    => $roi_declaration->get_amount_with_commission() . ' - ' . $roi_declaration->get_commission_to_pay(),
						'post_content'  => print_r( $result, TRUE ),
						'post_status'   => 'publish',
						'post_type'		=> 'mandate_payment'
					);
					wp_insert_post( $withdrawal_post );
					
					// Enregistrement de la déclaration
					$date_now = new DateTime();
					$roi_declaration->date_paid = $date_now->format( 'Y-m-d' );
					$roi_declaration->mean_payment = WDGROIDeclaration::$mean_payment_mandate;
					$roi_declaration->status = WDGROIDeclaration::$status_transfer;
					$roi_declaration->save();

				}
			}
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
		$send_notifications = filter_input( INPUT_POST, 'send_notifications' );
		$transfer_remaining_amount = filter_input( INPUT_POST, 'transfer_remaining_amount' );
		if ( current_user_can('manage_options') && !empty( $campaign_id ) && !empty( $declaration_id ) ) {
			$roi_declaration = new WDGROIDeclaration( $declaration_id );
			$roi_declaration->make_transfer( ($send_notifications == 1), ($transfer_remaining_amount == 1) );
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

        $userfirstname = $variables[ 'userfirstname' ];
        if ( empty( $userfirstname ) ){
            $userfirstname = "<i>(Nom du destinataire)</i>";
        }
        $userlastname = $variables[ 'userlastname' ];
        if ( empty( $userlastname ) ){
            $userlastname = "<i>(Nom du destinataire)</i>";
        }

        $investwish = $variables['investwish'];
        if(empty($investwish)){
            $investwish = "<i>(Intention d'investissement)</i>";
        }


        $body_content = '<div style="font-family: sans-serif; padding: 10px 5%;">';

        $body_content .= $initial_content.'<br />';

        $body_content .= '</div>';

        $body_content = str_replace('%userfirstname%', $userfirstname, $body_content);
        $body_content = str_replace('%userlastname%', $userlastname, $body_content);
        $body_content = str_replace('%investwish%', $investwish, $body_content);

        $transformed_title = $post_campaign->post_title.' : '.$mail_title;
        $transformed_title = str_replace('%userfirstname%', $userfirstname, $transformed_title);
        $transformed_title = str_replace('%userlastname%', $userlastname, $transformed_title);
        $transformed_title = str_replace('%investwish%', $investwish, $transformed_title);

        return array(
            'title'=>$transformed_title,
            'body'=>$body_content
        );
    }
}
