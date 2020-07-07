<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsSlack {
    private static $channel_notifications = "wdg-notifications";
    private static $channel_notifications_investors = "investisseurs-notifications";
    private static $channel_notifications_royalties = "royalties-notifications";
	private static $channel_notifications_clients = "clients-notifications";
	
	private static $notif_type_investors = 'investors';
	private static $notif_type_royalties = 'royalties';
	private static $notif_type_clients = 'clients';
	
	private static $icon_bell = ':bell:';
	private static $icon_hug = ':hugging_face:';
	private static $icon_doc = ':notebook:';
	private static $icon_wallet = ':moneybag:';
	private static $icon_money = ':euro:';
	private static $icon_fireworks = ':fireworks:';
	private static $icon_rocket = ':rocket:';
	private static $icon_sign = ':black_nib:';
	private static $icon_robot = ':robot_face:';
	private static $icon_card_file_box = ':card_file_box:';
	private static $icon_mag = ':mag:';
	private static $icon_currency_exchange = ':currency_exchange:';
	private static $icon_scroll = ':scroll:';
    
    public static function send($url, $room, $message, $icon = ':bell:') {
		$message = str_replace( '&', 'and', $message );
	    $data = "payload=" . json_encode(array(
		    "channel"       =>  "#{$room}",
		    "text"          =>  $message,
		    "icon_emoji"    =>  $icon
		));

	    $ch = curl_init($url);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $result = curl_exec($ch);
	    $error = curl_error($ch);
	    $errorno = curl_errno($ch);
		//ypcf_debug_log( 'NotificationsSlack::send > ' . print_r( $result, true ) . ' ; ' . print_r( $error, true ) . ' ; ' . print_r( $errorno, true ) );
	    curl_close($ch);
    }
	
	public static function send_to_notifications( $message, $icon, $type = FALSE ) {
	    if (!defined( 'YP_SLACK_WEBHOOK_URL')) { return; }
	    
		$webhook_url = YP_SLACK_WEBHOOK_URL;
		$channel = self::$channel_notifications;
		if ( !empty( $type ) ) {
			switch ( $type ) {
				case 'investors':
					$webhook_url = YP_SLACK_WEBHOOK_URL_INVESTORS;
					$channel = self::$channel_notifications_investors;
					break;
				case 'royalties':
					$webhook_url = YP_SLACK_WEBHOOK_URL_ROYALTIES;
					$channel = self::$channel_notifications_royalties;
					break;
				case 'clients':
					$webhook_url = YP_SLACK_WEBHOOK_URL_CLIENTS;
					$channel = self::$channel_notifications_clients;
					break;
			}
		}
	    self::send( $webhook_url, $channel, $message, $icon );
	}
	

	//*******************************************************
    // NOTIFICATIONS SLACK DANS LE CANAL INVESTISSEURS-NOTIFICATIONS
    //*******************************************************
	public static function send_new_user( $wp_user_id ) {
		$user_data = get_userdata( $wp_user_id );
		$message = "Nouvel utilisateur : " . $user_data->user_login . ' (' . $wp_user_id . ') => ' . $user_data->user_email;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_hug, self::$notif_type_investors );
	}
	
	public static function send_new_doc_status( $message ) {
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_doc, self::$notif_type_investors );
	}
	
	public static function send_new_wallet_status( $wallet_id, $wallet_url, $wallet_name, $status ) {
		$message = 'Changement de statut pour porte-monnaie : ' . $wallet_id . ' ('.$wallet_name.' - ' .$wallet_url. ') => ' .$status;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_wallet, self::$notif_type_investors );
	}
	
	public static function send_new_investment( $project_name, $amount, $investor_email ) {
		global $new_pdf_file_name;
		$message = 'Nouvel investissement sur le projet ' . $project_name . ' : '.$amount.' € par ' .$investor_email;
		if ( !empty( $new_pdf_file_name ) ) {
			$message .= "\n";
			$message .= "Lien vers le contrat : " .home_url( '/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/' .$new_pdf_file_name );
		}
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_money, self::$notif_type_investors );
	}
	
	//*******************************************************
    // FIN DE NOTIFICATIONS SLACK DANS LE CANAL INVESTISSEURS-NOTIFICATIONS
    //*******************************************************

	//*******************************************************
    // NOTIFICATIONS SLACK DANS LE CANAL CLIENTS-NOTIFICATIONS
    //*******************************************************
	public static function send_new_project( $campaign_id, $orga_name ) {
		$post_campaign = get_post($campaign_id);
		$project_title = $post_campaign->post_title;
		$user_author = get_user_by('id', $post_campaign->post_author);
		$user_phone = get_user_meta( $post_campaign->post_author, 'user_mobile_phone', TRUE );
		
		$message = "Nouveau projet ! <!channel>\n";
		$message .= "Nom : " .$project_title. "\n";
		$message .= "URL : " .get_permalink($campaign_id). "\n";
		$message .= "Porté par : ".$user_author->first_name." ".$user_author->last_name." (".$user_author->user_login.")\n";
		$message .= "Mail : ".$user_author->user_email. "\n";
		$message .= "Tel : ".$user_phone. "\n";
		$message .= "Organisation : ".$orga_name. "\n";
		
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_fireworks, self::$notif_type_clients );
	}
	
	public static function send_new_project_status( $campaign_id, $status ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$status_str = "évaluation";
		if ( $status == ATCF_Campaign::$campaign_status_collecte ) {
			$status_str = "investissement";
		}
		
		$message = "Un projet change d'étape ! <!channel>\n";
		$message .= "Nom : " .$campaign->data->post_title. "\n";
		$message .= "Nouvelle étape : " .$status_str;
		
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_rocket, self::$notif_type_clients );
	}
	
	public static function send_new_project_mandate( $orga_id ) {
		$WDGOrganization = new WDGOrganization( $orga_id );
		
		$message = $WDGOrganization->get_name(). " a signé l'autorisation de prélèvement <!channel>";
		
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_sign, self::$notif_type_clients );
	}
	
	public static function send_update_summary_current_projects( $params ) {
		$message = "Résumé des projets en cours";
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_robot, self::$notif_type_clients );
		
		if ( !empty( $params[ 'vote' ] ) ) {
			$message = "Projets en évaluation :";
			NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_robot, self::$notif_type_clients );
			foreach ( $params[ 'vote' ] as $project_info ) {
				$message = "- " .$project_info[ 'name' ]. " (" .$project_info[ 'time_remaining' ]. ") : " .$project_info[ 'nb_votes' ]. " évaluations et " .$project_info[ 'value_intent' ]. " € d'intentions d'investissement (Objectif minimum : " .$project_info[ 'min_goal' ]. " €). " .$project_info[ 'nb_preinvestment' ]. " pré-investissements, pour un total de " .$project_info[ 'value_preinvestment' ]. " €. " .$project_info[ 'nb_not_validated_preinvestment' ]. " pré-investissements non-validés, pour un total de " .$project_info[ 'value_not_validated_preinvestment' ]. " €.";
				NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_robot, self::$notif_type_clients );
			}
			
		}
		
		if ( !empty( $params[ 'funding' ] ) ) {
			$message = "Projets en levée de fonds :";
			NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_robot, self::$notif_type_clients );
			foreach ( $params[ 'funding' ] as $project_info ) {
				$message = "- " .$project_info[ 'name' ]. " (" .$project_info[ 'time_remaining' ]. ") : " .$project_info[ 'nb_invest' ]. " investissements pour " .$project_info[ 'value_invest' ]. " € (Objectif minimum : " .$project_info[ 'min_goal' ]. " €). " .$project_info[ 'nb_not_validated' ]. " investissements non-validés pour " .$project_info[ 'value_not_validated' ]. " €.";
				NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_robot, self::$notif_type_clients );
			}
		}
		
		if ( !empty( $params[ 'hidden' ] ) ) {
			$message = "Projets en levée de fonds privée :";
			NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_robot, self::$notif_type_clients );
			foreach ( $params[ 'hidden' ] as $project_info ) {
				$message = "- " .$project_info[ 'name' ]. " (" .$project_info[ 'time_remaining' ]. ") : " .$project_info[ 'nb_invest' ]. " investissements pour " .$project_info[ 'value_invest' ]. " € (Objectif minimum : " .$project_info[ 'min_goal' ]. " €). " .$project_info[ 'nb_not_validated' ]. " investissements non-validés pour " .$project_info[ 'value_not_validated' ]. " €.";
				NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_robot, self::$notif_type_clients );
			}
		}		
	}
	
	/**
	 * @param WDGOrganization $orga
	 * @param int $nb_document
	 */
	public static function send_document_uploaded_admin( $orga, $nb_document ) {
		$message = "L'organisation " .$orga->get_name(). " a uploadé des documents d'authentification. Nombre de fichiers : ".$nb_document.".";
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_card_file_box, self::$notif_type_clients );
	}

	public static function read_project_page( $id_campaign ) {
		$campaign = new ATCF_Campaign( $id_campaign );
		$message = "Le porteur de projet ".$campaign->get_name()." a cliqué sur le bouton de relecture : " .$campaign->get_public_url();
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function investment_pending_wire( $payment_id ) {		
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		
		$message = "Nouveau virement pour ".$campaign->data->post_title ." : ".$payment_amount. "euros (".$email.")";
		
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function new_purchase_pending_check_admin( $payment_id, $picture_url ) {		
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		
		$message = "Nouveau chèque pour ".$campaign->data->post_title ." : ".$payment_amount. "euros (".$email."). ";
		if ( $picture_url ) {
			$message .= "Une photo a été envoyée.";
		} else {
			$message .= "Aucune photo n'a été envoyée.";
		}
		
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function investment_draft_created_admin( $campaign_name, $dashboard_url ) {		
		$message = "Ajout de chèque dans TB par le PP pour le projet " .$campaign_name. " URL du TB : <a href=\"" .$dashboard_url. "\" target=\"_blank\">" .$dashboard_url. "</a>";
		
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function new_purchase_admin_error( $user_data, $project_title, $amount ) {		
		$message = "Tentative d'investissement avec erreur ".$project_title ." : ".$amount. "euros (".$user_data->user_email.")";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	//*******************************************************
    // FIN DE NOTIFICATIONS SLACK DANS LE CANAL CLIENTS-NOTIFICATIONS
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS SLACK DANS LE CANAL ROYALTIES-NOTIFICATIONS
	//*******************************************************
	public static function send_declaration_document_uploaded( $project_name, $document_name ) {
		$message = "Le projet " .$project_name. " a uploadé un document justificatif appelé : ".$document_name;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_mag, self::$notif_type_royalties );
	}
	
	public static function send_declaration_filled( $project_name, $turnover_amount, $royalties_amount, $commission_amount ) {
		$message = "Le projet " .$project_name. " a fait sa déclaration de royalties. Montant total du CA : ".$turnover_amount." €. Montant des royalties (ajustement compris) : " .$royalties_amount. " €. Montant de la commission : " .$commission_amount. " €.";
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_currency_exchange, self::$notif_type_royalties );
	}
	
	public static function send_auto_transfer_done( $project_name ) {
		$message = "Le versement du projet " .$project_name. " a été fait automatiquement.";
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_currency_exchange, self::$notif_type_royalties );
	}

	public static function organization_bank_file_changed_admin( $organization_name ) {		
		$message = "L'organisation ".$organization_name ." a changé de RIB";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}
	// DECLARATIONS DE CA	
	public static function turnover_declaration_null( $declaration_id, $declaration_message ) {
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign( FALSE, $declaration->id_campaign );
		
		$message = "Projet " . $campaign->data->post_title . " - Déclaration de CA à zero ".$declaration_message;

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}



	//*******************************************************
    // FIN DE NOTIFICATIONS SLACK DANS LE CANAL ROYALTIES-NOTIFICATIONS
    //*******************************************************
}
