<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

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
	private static $icon_exclamation_red = ':exclamation:';

	public static function send($url, $room, $message, $icon = ':bell:') {
		$message = str_replace( '&', 'and', $message );
		// On note les notifications provenant de nos tests en local
		if ( $_SERVER['SERVER_NAME'] != 'www.wedogood.co' ) {
			$message = 'TEST -- ' . $message;
		}
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

	public static function send_to_notifications($message, $icon, $type = FALSE) {
		if (!defined( 'YP_SLACK_WEBHOOK_URL')) {
			return;
		}

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
	public static function send_update_summary_user_subscribed($users) {
		if ( is_array( $users ) ) {
			$nb_users = count( $users );
			if ( $nb_users > 0 ) {
				$message = $nb_users . ' utilisateurs inscrits hier :';
				NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_hug, self::$notif_type_investors );

				$nb_modulo = 5;
				$count = 0;
				$message = '';
				foreach ( $users as $user ) {
					if ( $message != '' ) {
						$message .= ', ';
					}
					$message .= $user->data->user_email;
					$count++;
					if ( $count % $nb_modulo == 0 ) {
						NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_hug, self::$notif_type_investors );
						$count = 0;
						$message = '';
					}
				}
				if ( $message != '' ) {
					NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_hug, self::$notif_type_investors );
				}
			}
		}
	}

	public static function send_new_wallet_status($wallet_id, $wallet_url, $wallet_name, $status) {
		$message = 'Changement de statut pour porte-monnaie : ' . $wallet_id . ' ('.$wallet_name.' - ' .$wallet_url. ') => ' .$status;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_wallet, self::$notif_type_investors );
	}

	public static function new_purchase_admin_error($user_data, $project_title, $amount) {
		$message = "Tentative d'investissement avec erreur ".$project_title ." : ".$amount. "euros (".$user_data->user_email.")";

		self::send_to_notifications( $message, NotificationsSlack::$icon_exclamation_red, self::$notif_type_investors );
	}

	public static function send_new_investment( $campaign, $amount, $investor_email, $investment_id ) {
		$message = 'Nouvel investissement sur le projet ' . $campaign->get_name() . ' : '.$amount.' € par ' .$investor_email;
		$investment_url = WDGInvestmentContract::get_investment_file_url( $campaign, $investment_id );
		if ( !empty( $investment_url ) ) {
			$message .= "\n";
			$message .= "Lien vers le contrat : " .$investment_url;
		}
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_money, self::$notif_type_investors );
	}

	public static function send_wedogood_delete_order($user_email) {
		$message = "Compte utilisateur supprimé : " .$user_email;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_money, self::$notif_type_investors );
	}

	//*******************************************************
	// FIN DE NOTIFICATIONS SLACK DANS LE CANAL INVESTISSEURS-NOTIFICATIONS
	//*******************************************************

	//*******************************************************
	// NOTIFICATIONS SLACK DANS LE CANAL CLIENTS-NOTIFICATIONS
	//*******************************************************
	public static function send_new_project($campaign_id, $orga_name) {
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

	public static function send_new_project_status($campaign_id, $status) {
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

	public static function send_new_project_mandate($orga_id) {
		$WDGOrganization = new WDGOrganization( $orga_id );

		$message = $WDGOrganization->get_name(). " a signé l'autorisation de prélèvement <!channel>";

		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_sign, self::$notif_type_clients );
	}

	public static function send_update_summary_current_projects($params) {
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
	public static function send_document_uploaded_admin($orga, $nb_document) {
		$message = "L'organisation " .$orga->get_name(). " a uploadé des documents d'authentification. Nombre de fichiers : ".$nb_document.".";
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_card_file_box, self::$notif_type_clients );
	}

	public static function read_project_page($id_campaign) {
		$campaign = new ATCF_Campaign( $id_campaign );
		$message = "Le porteur de projet ".$campaign->get_name()." a cliqué sur le bouton de relecture : " .$campaign->get_public_url();
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function investment_pending_wire($payment_id) {
		$inv = new WDGInvestment( $payment_id );
		$campaign = $inv->get_saved_campaign();

		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];

		$message = "Nouveau virement pour ".$campaign->get_name() ." : ".$payment_amount. "euros (".$email.")";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function new_purchase_pending_check_admin($payment_id, $picture_url) {
		$inv = new WDGInvestment( $payment_id );
		$campaign = $inv->get_saved_campaign();

		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];

		$message = "Nouveau chèque pour ".$campaign->get_name() ." : ".$payment_amount. "euros (".$email."). ";
		if ( $picture_url ) {
			$message .= "Une photo a été envoyée.";
		} else {
			$message .= "Aucune photo n'a été envoyée.";
		}

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function investment_draft_created_admin($campaign_name, $dashboard_url) {
		$message = "Ajout de chèque dans TB par le PP pour le projet " .$campaign_name. " URL du TB : " .$dashboard_url;

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function wire_payment_received($message) {
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function wire_payment_received_not_attributed($message) {
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}

	public static function campaign_url_changed($campaign_name, $old_url, $new_url) {
		$message = 'Le projet ' .$campaign_name. ' a changé son URL => ancienne : ' . $old_url . ' ; nouvelle : ' . $new_url;
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_clients );
	}
	//*******************************************************
	// FIN DE NOTIFICATIONS SLACK DANS LE CANAL CLIENTS-NOTIFICATIONS
	//*******************************************************

	//*******************************************************
	// NOTIFICATIONS SLACK DANS LE CANAL ROYALTIES-NOTIFICATIONS
	//*******************************************************
	public static function send_declaration_document_uploaded($project_name, $document_name) {
		$message = "Le projet " .$project_name. " a uploadé un document justificatif appelé : ".$document_name;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_mag, self::$notif_type_royalties );
	}

	public static function send_declaration_filled($project_name, $turnover_amount, $turnover_details, $royalties_amount, $commission_amount) {
		$array_replace = array( '"', '{',  '}', '[', ']' );
		$turnover_details_cleaned = str_replace( ',', ', ', str_replace( $array_replace, '', $turnover_details ) );
		$message = "Le projet " .$project_name. " a fait sa déclaration de royalties. Montant total du CA : ".$turnover_amount." € (" .$turnover_details_cleaned. "). Montant des royalties (ajustement compris) : " .$royalties_amount. " €. Montant de la commission : " .$commission_amount. " €.";
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_currency_exchange, self::$notif_type_royalties );
	}

	public static function send_auto_transfer_done($project_name) {
		$message = "Le versement du projet " .$project_name. " a été fait automatiquement.";
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_currency_exchange, self::$notif_type_royalties );
	}

	public static function organization_bank_file_changed_admin($organization_name) {
		$message = "L'organisation ".$organization_name ." a changé de RIB";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}
	// DECLARATIONS DE CA
	public static function turnover_declaration_null($declaration_id, $declaration_message) {
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign( FALSE, $declaration->id_campaign );

		$message = "Projet " . $campaign->data->post_title . " - Déclaration de CA à zero ";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function turnover_declaration_not_null($declaration_id, $declaration_message) {
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign( FALSE, $declaration->id_campaign );
		$message = "Projet " . $campaign->data->post_title . " - Déclaration de CA effectuée ";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function send_notification_roi_payment_success_admin($declaration_id) {
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );

		$message = "Projet " . $campaign->data->post_title . " - Paiement ROI effectué : ".$roi_declaration->get_amount_with_commission()." €";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function send_notification_roi_payment_pending_admin($declaration_id) {
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );

		$message = "Projet " . $campaign->data->post_title . " - Paiement ROI en attente : ".$roi_declaration->get_amount_with_commission()." €";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function send_notification_roi_payment_bank_transfer_admin($declaration_id) {
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );

		$message = "Projet " . $campaign->data->post_title . " - Paiement ROI par virement déclaré et en attente : ".$roi_declaration->get_amount_with_commission()." €";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function send_notification_roi_payment_error_admin($declaration_id) {
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );

		$message = "Projet " . $campaign->data->post_title . " -Problème de paiement de ROI : ".$roi_declaration->get_amount_with_commission()." €";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function roi_received_exceed_investment($investor_id, $project_id) {
		$campaign = new ATCF_Campaign( FALSE, $project_id );

		$message = "Projet " . $campaign->get_name() . " - Royalties percues supérieures à l'investissement initial : ( ID API investisseur :".$investor_id.")";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function roi_received_exceed_maximum($investor_id, $project_id) {
		$campaign = new ATCF_Campaign( FALSE, $project_id );
		$message = "Projet " . $campaign->get_name() . " - Royalties percues supérieures à ce que permettait l'investissement de départ (maximum dépassé) : ( ID API investisseur :".$investor_id.")";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function mandate_payment_received($message) {
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function send_notification_roi_transfer_to_come($message) {
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function send_notification_roi_insufficient_funds_admin($project_name) {
		$message = "Projet " . $project_name . " - Versement auto - Fonds insuffisants";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function declarations_close_to_maximum_profit($project_name, $ratio) {
		$message = "Projet " . $project_name . " est proche d'atteindre son versement maximum (ratio de " .$ratio. " %).";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function declaration_bill_failed($campaign_name) {
		$message = "Projet " . $campaign_name . " - Erreur génération facture";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function tax_summaries($campaign_name, $total_tax_in_euros) {
		$message = "Projet " . $campaign_name . " - Taxes à payer aux impots (" . $total_tax_in_euros . ")";

		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}

	public static function send_notification_mandate_canceled($name, $lemonway_posted_id_external, $lemonway_posted_amount) {
		$message = "Prélèvement bancaire annulé : " . $name . " (ID Wallet : " . $lemonway_posted_id_external . " ; Montant : " . $lemonway_posted_amount . ")";
		self::send_to_notifications( $message, NotificationsSlack::$icon_scroll, self::$notif_type_royalties );
	}
	//*******************************************************
    // FIN DE NOTIFICATIONS SLACK DANS LE CANAL ROYALTIES-NOTIFICATIONS
    //*******************************************************
}
