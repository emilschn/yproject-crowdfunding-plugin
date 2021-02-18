<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) { exit; }

class NotificationsAsana {
	private static $notif_type_admin = 'admin';
	private static $notif_type_support = 'support';

	public static function send( $type, $task_name, $task_content ) {
		$from_name = '';
		$from_email = '';
		$asana_email = '';

		if ( !empty( $type ) ) {
			switch ( $type ) {
				case self::$notif_type_admin:
					$from_name = YP_ASANA_PARAMS_ADMIN_FROM_NAME;
					$from_email = YP_ASANA_PARAMS_ADMIN_FROM_EMAIL;
					$asana_email = YP_ASANA_PARAMS_ADMIN_ASANA_EMAIL;
					break;
				case self::$notif_type_support:
					$from_name = YP_ASANA_PARAMS_SUPPORT_FROM_NAME;
					$from_email = YP_ASANA_PARAMS_SUPPORT_FROM_EMAIL;
					$asana_email = YP_ASANA_PARAMS_SUPPORT_ASANA_EMAIL;
					break;
			}
		}

		if ( empty( $from_name ) ) {
			return FALSE;
		}
		
		// On note les notifications provenant de nos tests en local
		if ( $_SERVER['SERVER_NAME'] == 'wedogood.local' || $_SERVER['SERVER_NAME'] != 'www.wedogood.co' ) {
			$task_name = 'TEST -- ' . $task_name;
		}

		$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		return wp_mail( $asana_email, $task_name, $task_content, $headers );
	}

    //*******************************************************
    // CREATION DE TACHES ASANA DE SUPPORT
    //*******************************************************
	public static function send_new_project( $campaign_id, $orga_name ) {
		$post_campaign = get_post($campaign_id);
		$project_title = $post_campaign->post_title;
		$user_author = get_user_by('id', $post_campaign->post_author);
		$user_phone = get_user_meta( $post_campaign->post_author, 'user_mobile_phone', TRUE );
		
		$object = $project_title. ' /// Nouveau projet !';
		$content = "Nouveau projet ! <!channel>\n";
		$content .= "Nom : " .$project_title. "\n";
		$content .= "URL : " .get_permalink($campaign_id). "\n";
		$content .= "Porté par : ".$user_author->first_name." ".$user_author->last_name." (".$user_author->user_login.")\n";
		$content .= "Mail : ".$user_author->user_email. "\n";
		$content .= "Tel : ".$user_phone. "\n";
		$content .= "Organisation : ".$orga_name. "\n";
		return self::send( self::$notif_type_support, $object, $content );
	}
	
	public static function send_new_project_status( $campaign_id, $status ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$status_str = "évaluation";
		if ( $status == ATCF_Campaign::$campaign_status_collecte ) {
			$status_str = "investissement";
		}
		
		$object = $campaign->data->post_title. ' /// Nouvelle étape !';
		$content = "Un projet change d'étape ! <!channel>\n";
		$content .= "Nom : " .$campaign->data->post_title. "\n";
		$content .= "Nouvelle étape : " .$status_str;

		return self::send( self::$notif_type_support, $object, $content );
	}
	
	public static function send_new_project_mandate( $orga_id ) {
		$WDGOrganization = new WDGOrganization( $orga_id );
		
		$object = $WDGOrganization->get_name(). ' /// Mandat de prélèvement !';
		$content = $WDGOrganization->get_name(). " a signé l'autorisation de prélèvement";
		// TODO : ajouter l'URL vers le tableau de bord sur LW

		return self::send( self::$notif_type_support, $object, $content );
	}

	public static function send_new_project_document_status( $orga_name, $document_type, $document_status ) {
		$object = $orga_name. ' /// Changement de statut de document';
		$content = "Document : " . $document_type . " - Statut : " . $document_status;
		return self::send( self::$notif_type_support, $object, $content );
	}

	public static function read_project_page( $id_campaign ) {
		$campaign = new ATCF_Campaign( $id_campaign );
		$object = $campaign->get_name() . ' /// Présentation à relire !';
		$content = "Le porteur de projet a cliqué sur le bouton de relecture<br>";
		$content .= "URL du projet : " . $campaign->get_public_url();
		return self::send( self::$notif_type_support, $object, $content );
	}

	public static function investment_pending_wire( $payment_id ) {
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);
		
		$object = $campaign->get_name() . ' /// Nouveau virement !';
		
		$content = "Un nouveau virement de ".$payment_amount." &euro; a été enregistré pour le projet " .$campaign->data->post_title. ".<br /><br />";
		$content .= "Utilisateur :<br />";
		$content .= "- login : " .$user_data->user_login. "<br />";
		$content .= "- e-mail : " .$email. "<br />";
		$content .= "- prénom et nom : " .$user_data->first_name . " " . $user_data->last_name. "<br />";
		$content .= "- téléphone : " . get_user_meta($user_data->ID, 'user_mobile_phone', true). "<br />";
		
		return self::send( self::$notif_type_support, $object, $content );
	}
	
	public static function new_purchase_pending_check_admin( $payment_id, $picture_url ) {
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);
		
		$object = $campaign->get_name() . ' /// Nouveau chèque !';
		
		$content = "Un nouveau chèque de ".$payment_amount." &euro; a été enregistré pour le projet " .$campaign->data->post_title. ".<br /><br />";
		$content .= "Utilisateur :<br />";
		$content .= "- login : " .$user_data->user_login. "<br />";
		$content .= "- e-mail : " .$email. "<br />";
		$content .= "- prénom et nom : " .$user_data->first_name . " " . $user_data->last_name. "<br />";
		$content .= "- téléphone : " . get_user_meta($user_data->ID, 'user_mobile_phone', true). "<br />";
		if ( $picture_url ) {
			$content .= "Une photo a été envoyée :<br />";
			$content .= "<img src='".$picture_url."' /><br />";
		} else {
			$content .= "Aucune photo n'a été envoyée.<br />";
		}
		
		return self::send( self::$notif_type_support, $object, $content );
	}
	
	public static function investment_draft_created_admin( $campaign_name, $dashboard_url ) {
		$object = $campaign_name . ' /// Nouveau chèque ajouté dans TB par le PP';
		
		$content = "L'équipe du projet " .$campaign_name. " vient d'ajouter un chèque qu'il faudrait valider.<br>";
		$content .= "URL du TB : <a href=\"" .$dashboard_url. "\" target=\"_blank\">" .$dashboard_url. "</a><br><br>";
		$content .= "Bon courage !";
		
		return self::send( self::$notif_type_support, $object, $content );
	}
	
	public static function new_purchase_admin_error( $user_data, $int_msg, $txt_msg, $project_title, $amount, $ask_restart ) {
		$object = $project_title . ' /// Erreur investissement !';
		$content = "Tentative d'investissement avec erreur :<br />";
		$content .= "Login : " .$user_data->user_login. "<br />";
		$content .= "e-mail : " .$user_data->user_email. "<br />";
		if ( !empty( $project_title ) ) {
			$content .= "Projet : " .$project_title. "<br />";
		}
		if ( !empty( $amount ) ) {
			$content .= "Montant : " .$amount. "<br />";
		}
		$content .= "Erreur LW : " .$int_msg. "<br />";
		$content .= "Texte d'erreur pour l'utilisateur : " .$txt_msg. "<br />";
		if ($ask_restart) {
			$content .= "A proposé de recommencer<br />";
		} else {
			$content .= "N'a pas proposé de recommencer<br />";
		}

		return self::send( self::$notif_type_support, $object, $content );
	}
	
	public static function organization_bank_file_changed_admin( $organization_name ) {
		$object = "RIB d'organisation modifié - " . $organization_name;

		$content = "L'organisation ".$organization_name." a changé de RIB.<br>";
		$content .= "Si c'était un projet en versement, il faudrait refaire signer l'autorisation de prélèvement.<br>";

		return self::send( self::$notif_type_support, $object, $content );
	}
		
	/**
     * Achat avec erreur de génération de contrat
     * @param int $payment_id
     * @return bool
     */
	public static function new_purchase_admin_error_contract($payment_id) {
		$object = 'Problème de création de contrat';
		$content = "Il y a eu un problème durant la génération du contrat. Id du paiement : ".$payment_id;
		return self::send( self::$notif_type_support, $object, $content );
	}
	

	public static function send_declaration_document_uploaded( $campaign_name, $document_name ) {
		$object = $campaign_name . ' /// Upload de justificatif';
		$content = "Le projet " .$campaign_name. " a uploadé un document justificatif appelé : ".$document_name;
		return self::send( self::$notif_type_support, $object, $content );
	}

	
	public static function send_declaration_filled( $campaign_name, $turnover_amount, $royalties_amount, $commission_amount ) {
		$object = $campaign_name . ' /// Déclaration de royalties effectuée';
		$content = "Le projet " .$campaign_name. " a fait sa déclaration de royalties. Montant total du CA : ".$turnover_amount." €. Montant des royalties (ajustement compris) : " .$royalties_amount. " €. Montant de la commission : " .$commission_amount. " €.";
		return self::send( self::$notif_type_support, $object, $content );
	}

	
	public static function roi_received_exceed_maximum( $investor_id, $investor_type, $project_id ) {
		$campaign = new ATCF_Campaign( FALSE, $project_id );
		$investor_entity = ( $investor_type == 'orga' ) ? WDGOrganization::get_by_api_id( $investor_id ) : WDGUser::get_by_api_id( $investor_id );
		
		$object = "URGENT - Royalties percues supérieures au maximum pouvant être reçu";
		$content = "Un investisseur a reçu plus de royalties que son investissement de départ ne le permettait (maximum dépassé).<br>";
		$content .= "Sur le projet : " .$campaign->get_name(). "<br>";
		$content .= "Type d'investisseur : " .( $investor_type == 'orga' ) ? 'Organisation' : 'Utilisateur'. "<br>";
		$content .= "ID API investisseur : " .$investor_id. "<br>";
		$content .= "ID WP investisseur : " .$investor_entity->get_wpref();	

		return self::send( self::$notif_type_support, $object, $content );
	}
	
	public static function tax_summaries( $campaign_name, $total_tax_in_euros ) {
		$object = 'Taxes à payer aux impots /// ' . $campaign_name;
		$content = 'Le projet ' . $campaign_name . ' a versé des plus-values. Il faut les déclarer aux impots !<br><br>';
		$content .= 'Au total, cela devrait faire un versement de ' . $total_tax_in_euros . ' € aux impots de notre part.';
		return self::send( self::$notif_type_support, $object, $content );
	}

    public static function send_notification_kyc_validated_but_not_wallet_admin( $user_email, $user_name, $pending_actions ) {
		$object = "Wallet à vérifier - " . $user_email;
		$content = "Hello !<br>";
		$content .= "Lemon Way a validé tous les documents du wallet, mais le wallet n'est pas authentifié.<br>";
		$content .= "Il s'agit de " .$user_name. ".<br>";
		$content .= "Son adresse e-mail est la suivante : " .$user_email. "<br><br>";
		
		$content .= "Voici ses actions sur le site :<br>";
		foreach ( $pending_actions as $pending_action ) {
			$content .= "- " .$pending_action. "<br>";
		}

		return self::send( self::$notif_type_support, $object, $content );
	}

	public static function send_notification_kyc_refused_admin( $user_email, $user_name, $pending_actions, $campaign_name ) {

		$object = "Investisseur à relancer - " . $user_email . '///' .$campaign_name;
		
		$content = "Hello !<br>";
		$content .= "Lemon Way a refusé des documents depuis quelques jours, et l'utilisateur a quelques actions en attente.<br>";
		$content .= "Il s'agit de " .$user_name. ".<br>";
		$content .= "Son adresse e-mail est la suivante : " .$user_email. "<br><br>";
		
		$content .= "Voici ses actions sur le site :<br>";
		foreach ( $pending_actions as $pending_action ) {
			$content .= "- " .$pending_action. "<br>";
		}

		return self::send( self::$notif_type_support, $object, $content );
	}
    //*******************************************************
    // FIN DE CREATION DE TACHES ASANA DE SUPPORT
	//*******************************************************
	
    //*******************************************************
    // CREATION DE TACHES ASANA D'ADMIN
    //*******************************************************

	public static function new_purchase_admin_error_wallet( $WDGUser_current, $project_title, $amount ) {
		$object = 'Erreur transfert wallet';
		$content = "Il y a un souci pour un transfert de wallet :<br />";
		$content .= "Login : " .$WDGUser_current->get_firstname(). "<br />";
		$content .= "e-mail : " .$WDGUser_current->get_email(). "<br />";
		$content .= "Projet : " .$project_title. "<br />";
		$content .= "Montant total : " .$amount. "<br />";
		return self::send( self::$notif_type_admin, $object, $content );
	}
	
	public static function investment_to_api_error_admin( $edd_payment_item ) {
		$object = "Erreur d'ajout d'investissement sur l'API ";
		$content = "Problème d'ajout d'un investissement sur l'API, avec l'identifiant suivant : " . $edd_payment_item->ID;

		return self::send( self::$notif_type_admin, $object, $content );
	}

	public static function roi_received_exceed_investment( $investor_id, $investor_type, $project_id ) {
		$campaign = new ATCF_Campaign( FALSE, $project_id );
		$investor_entity = ( $investor_type == 'orga' ) ? WDGOrganization::get_by_api_id( $investor_id ) : WDGUser::get_by_api_id( $investor_id );
		$investor_entity_wpref = 'indefini';
		if ( !empty( $investor_entity ) ) {
			$investor_entity_wpref = $investor_entity->get_wpref();
		}

		$object = "Royalties percues supérieures à l'investissement initial";
		$content = "Un investisseur a reçu plus de royalties que son investissement de départ.<br>";
		$content .= "Sur le projet : " .$campaign->get_name(). "<br>";
		$content .= "Type d'investisseur : " .( $investor_type == 'orga' ) ? 'Organisation' : 'Utilisateur'. "<br>";
		$content .= "ID API investisseur : " .$investor_id. "<br>";
		$content .= "ID WP investisseur : " .$investor_entity_wpref;		

		return self::send( self::$notif_type_admin, $object, $content );
	}
	
	public static function send_notification_roi_insufficient_funds_admin( $project_name ) {
		$object = "Versement auto - Fonds insuffisants";
		$content = "Il n'y a pas assez d'argent dans le wallet de royalties pour faire le versement trimestriel de " . $project_name;
		return self::send( self::$notif_type_admin, $object, $content );	
	}
	
	public static function declaration_bill_failed( $campaign_name ) {
		$object = "Erreur génération facture - " . $campaign_name;
		$content = "La facture automatique de la dernière déclaration de royalties pour le projet " .$campaign_name. " n'a pas pu être créée.";

		return self::send( self::$notif_type_admin, $object, $content );
	}

	public static function wire_payment_received_not_attributed( $message ) {
		$object = "Versement reçu - non automatisé";
		return self::send( self::$notif_type_admin, $object, $message );
	}
	
	public static function notification_api_failed( $parameters, $result ) {
		$object = "Erreur mail sendinblue";
		$content = "Erreur de mail envoyé par SendInBlue :<br>";
		$content .= "Paramètres :<br>";
		$content .= print_r( $parameters, true );
		$content .= "Résultat :<br>";
		$content .= print_r( $result, true );
		return self::send( self::$notif_type_admin, $object, $content );
	}

	public static function send_notification_mandate_canceled( $name, $lemonway_posted_id_external, $lemonway_posted_amount ) {
		$object = "Prélèvement bancaire annulé";
		$content = "Infos : " . $name . " (ID Wallet : " . $lemonway_posted_id_external . " ; Montant : " . $lemonway_posted_amount . ")";
		return self::send( self::$notif_type_admin, $object, $content );
	}

    //*******************************************************
    // FIN DE CREATION DE TACHES ASANA D'ADMIN
    //*******************************************************
    //*******************************************************
    // CREATION DE TACHES ASANA DE SUIVI CLIENT
    //*******************************************************
    //*******************************************************
    // FIN DE CREATION DE TACHES ASANA DE SUIVI CLIENT
    //*******************************************************
}
