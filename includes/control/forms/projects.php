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
		
		$post_title = filter_input( INPUT_POST, 'posttitle' );
		if ( empty( $post_title ) ) {
			$date = new DateTime();
			$post_title = 'Actualité du ' . $date->format( 'd/m/Y' );
		}
		$post_content = filter_input( INPUT_POST, 'postcontent' );
		$page_projects = get_page_by_path( 'les-projets' );
		$post_name = $campaign->data->post_name . '-' . sanitize_title( $post_title );
		$post_title = $campaign->get_name() . ' - ' . $post_title;

		$blog = array(
			'post_title'    => $post_title,
			'post_content'  => $post_content,
			'post_name'		=> $post_name,
			'post_status'   => 'publish',
			'post_author'   => $current_user->ID,
			'post_category' => array( $campaign->get_news_category_id() ),
			'post_parent'	=> $page_projects->ID
		);
		$post_id = wp_insert_post($blog, true);
		do_action('wdg_delete_cache', array(
			'cache_campaign_' . $post_campaign->ID,
			'project-header-menu-' . $post_campaign->ID
		));
		$file_cacher = WDG_File_Cacher::current();
		$file_cacher->delete( $campaign->get_name() );
                
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
			
			$content = $_POST[ 'postcontent' ];

			// Algo pour supprimer les liens qui mènent vers WDG et qui sont automatiquement appliqués aux images par WP
			// (car ce sont des liens qui mènent directement aux images et sont inutiles)
			$content_exploded_by_href = explode( 'href="', $content );
			$count_content_exploded_by_href = count( $content_exploded_by_href );
			if ( $count_content_exploded_by_href > 1 ) {
				for ( $i = 1; $i < $count_content_exploded_by_href; $i++ ) {
					$nodes_to_analyse_exploded = explode( '</a>', $content_exploded_by_href[ $i ] );
					$inside_of_link = $nodes_to_analyse_exploded[ 0 ];
					// Si c'est un lien posé sur une image
					if ( strpos( $inside_of_link, '<img' ) ) {
						$content_without_link_exploded = explode( '"', $inside_of_link );
						// Si c'est un lien menant vers WDG, on le supprime
						if ( strpos( $content_without_link_exploded[ 0 ], 'wedogood.co' ) !== FALSE ) {
							array_shift( $content_without_link_exploded );
							$inside_of_link = implode( '"', $content_without_link_exploded );
							$nodes_to_analyse_exploded[ 0 ] = '#"'. $inside_of_link;
						}
					}
					$content_exploded_by_href[ $i ] = implode( '</a>', $nodes_to_analyse_exploded );
				}
			}
			$content_without_links = implode( 'href="', $content_exploded_by_href );
			
			NotificationsAPI::new_project_news( $recipients_string, $replyto_mail, $post_campaign->post_title, get_permalink( $campaign_id ), $campaign->get_api_id(), $_POST[ 'posttitle' ], $content_without_links );
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
			
			$WDGInvestment = new WDGInvestment( $approve_payment_id );
			if ( $WDGInvestment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated ) {
				$WDGInvestment->set_contract_status( WDGInvestment::$contract_status_investment_validated );
				ypcf_get_updated_payment_status( $WDGInvestment->get_id() );
				
			} else {
				$postdata = array(
					'ID'			=> $approve_payment_id,
					'post_status'	=> 'publish',
					'edit_date'		=> current_time( 'mysql' )
				);
				wp_update_post($postdata);

				// - Créer le contrat pdf
				// - Envoyer validation d'investissement par mail
				$user_info = edd_get_payment_meta_user_info( $approve_payment_id );
				$amount = edd_get_payment_amount( $approve_payment_id );
				$campaign = new ATCF_Campaign( $campaign_id );
				if ( $amount >= WDGInvestmentSignature::$investment_amount_signature_needed_minimum ) {
					$WDGInvestmentSignature = new WDGInvestmentSignature( $approve_payment_id );
					$contract_id = $WDGInvestmentSignature->create_eversign();
					if ( !empty( $contract_id ) ) {
						NotificationsEmails::new_purchase_user_success( $approve_payment_id, FALSE, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );

					} else {
						global $contract_errors;
						$contract_errors = 'contract_failed';
						NotificationsEmails::new_purchase_user_error_contract( $approve_payment_id, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
						NotificationsEmails::new_purchase_admin_error_contract( $approve_payment_id );
					}

				} else {
					$new_contract_pdf_file = getNewPdfToSign( $campaign_id, $approve_payment_id, $user_info['id'] );
					NotificationsEmails::new_purchase_user_success_nocontract( $approve_payment_id, $new_contract_pdf_file, FALSE, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
				}

				NotificationsSlack::send_new_investment( $campaign->get_name(), $amount, $user_info['email'] );
				$WDGInvestment = new WDGInvestment( $approve_payment_id );
				$WDGInvestment->save_to_api( $campaign, 'publish' );
				
			}
			
			do_action('wdg_delete_cache', array(
				'home-projects',
				'projectlist-projects-current'
			));
			$file_cacher = WDG_File_Cacher::current();
			$file_cacher->build_campaign_page_cache( $campaign_id );
			
			wp_redirect( home_url( '/tableau-de-bord/' ) . '?campaign_id=' . $campaign_id . '&success_msg=approvepayment' );
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
			$WDGInvestment = new WDGInvestment( $cancel_payment_id );
			if ( $WDGInvestment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated ) {
				$WDGInvestment->set_contract_status( WDGInvestment::$contract_status_investment_refused );
			}
			
			if ( $WDGInvestment->get_saved_status() != 'pending' || $WDGInvestment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated ) {
				$WDGInvestment->refund();
			}
			
			$WDGInvestment->cancel();
			
			wp_redirect( home_url( '/tableau-de-bord/' ) . '?campaign_id=' . $campaign_id . '&success_msg=cancelpayment#contacts' );
			exit();
		}
	}

	public static function edit_image_url_video( $image, $post_video, $campaign_id ) {
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
				
				$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign_id );	

				wp_update_attachment_metadata( 
					$attach_id, 
					wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
				);
				$buffer .= $upload[ 'url' ] . '|';

				$file = get_attached_file( $attach_id );
				$path = pathinfo( $file );
				$file_name_without_ext = $campaign_id . '-' . time();
				$file_name_final = $file_name_without_ext . '.' . $path[ 'extension' ];
				$newfile = $path['dirname'] . "/" . $file_name_final;
				rename( $file, $newfile );
				update_attached_file( $attach_id, $newfile );
			}
		}
		//ajout de l'url de la vidéo
		if (isset($post_video)) {
			update_post_meta($campaign_id, 'campaign_video', esc_url($post_video));
			$buffer .= $post_video . '|';
		}
		return $buffer;
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
	
	public static function return_lemonway_card() {
		$buffer = FALSE;
		
		$wk_token = filter_input( INPUT_GET, 'response_wkToken' );
		if ( !empty( $wk_token ) && $wk_token != 'error' ) {
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
						
						$campaign = atcf_get_current_campaign();
						$current_organization = $campaign->get_organization();
						$organization = new WDGOrganization( $current_organization->wpref, $current_organization );
						$organization->check_register_royalties_lemonway_wallet();
						LemonwayLib::ask_transfer_funds( $organization->get_lemonway_id(), $organization->get_royalties_lemonway_id(), $declaration->get_amount_with_adjustment() );
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
