<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsAPI {
	
	public static $description_str_by_template_id = array(
		'156' => "Actualité",
		'184' => "Mail via liste de contacts",
		'181' => "Inscription",
		'311' => "KYC - RIB validé",
		'322' => "KYC - Doc en cours de validation",
		'749' => "KYC - Doc refusé",
		'777' => "KYC - Un seul doc validé",
		'324' => "KYC - Wallet validé",
		'641' => "Conseils quotidiens",
		'573' => "Relance - Evaluation - Avec intention",
		'575' => "Relance - Evaluation - Sans intention",
		'576' => "Relance - Pré-lancement - Evaluation avec intention",
		'577' => "Relance - Pré-lancement - Evaluation sans intention",
		'578' => "Relance - Pré-lancement - Suit le projet",
		'579' => "Relance - Investissement 30 % - Avec intention",
		'580' => "Relance - Investissement 30 % - Sans intention",
		'650' => "Relance - Investissement 30 % - Suit le projet",
		'621' => "Relance - Investissement 100 % - Avec investissement",
		'652' => "Relance - Investissement 100 % - Avec investissement en attente",
		'622' => "Relance - Investissement 100 % - Avec intention",
		'623' => "Relance - Investissement 100 % - Sans intention",
		'651' => "Relance - Investissement 100 % - Suit le projet",
		'581' => "Relance - Investissement J-2 - Avec intention",
		'582' => "Relance - Investissement J-2 - Sans intention",
		'632' => "Evaluation avec intention - Demande d'authentification",
		'628' => "Evaluation avec intention - Demande de pré-investissement",
		'603' => "Investissement - Demande d'authentification",
		'604' => "Investissement - Demande d'authentification - Rappel",
		'605' => "KYC - Wallet validé et investissement en attente",
		'606' => "KYC - Wallet validé et investissement en attente - Rappel",
		'175' => "Erreur d'investissement",
		'780' => "Réception virement bancaire sans investissement en attente",
		'172' => "Investissement par chèque en attente",
		'177' => "Investissement par virement en attente",
		'687' => "Investissement sur projet validé",
		'688' => "Investissement sur épargne positive validé",
		'178' => "Projet validé - campagne publique",
		'629' => "Projet validé - campagne privée",
		'699' => "Projet en attente d'atteinte du seuil de validation",
		'179' => "Projet échoué",
		'114' => "Déclarations - Rappel J-9 (avec prélèvement)",
		'115' => "Déclarations - Rappel J-9 (sans prélèvement)",
		'119' => "Déclarations - Rappel J-2 (avec prélèvement)",
		'116' => "Déclarations - Rappel J-2 (sans prélèvement)",
		'121' => "Déclarations - Rappel J (avec prélèvement)",
		'120' => "Déclarations - Rappel J (sans prélèvement)",
		'595' => "Déclarations - Avertissement prélèvement",
		'127' => "Déclaration faite avec CA",
		'150' => "Déclaration faite sans CA",
		'692' => "Déclaration - Avertissement prolongation",
		'736' => "Déclaration - Prolongation (porteur de projet)",
		'694' => "Déclaration - Prolongation (investisseurs)",
		'735' => "Déclaration - Fin (porteur de projet)",
		'693' => "Déclaration - Fin (investisseurs)",
		'139' => "Versement de royalties - résumé quotidien",
		'522' => "Versement de royalties - transfert avec message",
		'691' => "Versement de royalties - montant maximum atteint",
		'779' => "Versement sur compte bancaire - confirmation"
	);
	

	//**************************************************************************
	// Campagne
	//**************************************************************************
    //*******************************************************
    // ENVOI ACTUALITE DE PROJET
    //*******************************************************
	public static function new_project_news( $recipients, $replyto_mail, $project_name, $project_link, $project_api_id, $news_name, $news_content ) {
		ypcf_debug_log( 'NotificationsAPI::new_project_news > ' . $recipients );
		$id_template = '156';
		$project_link_clean = str_replace( 'https://', '', $project_link );
		$news_content_filtered = apply_filters( 'the_excerpt', $news_content );
		$options = array(
			'replyto'				=> $replyto_mail,
			'NOM_PROJET'			=> $project_name,
			'LIEN_PROJET'			=> $project_link_clean,
			'OBJET_ACTU'			=> $news_name,
			'CONTENU_ACTU'			=> $news_content_filtered
		);
		
		// Le maximum de destinataire est de 99, il faut découper
		$buffer = FALSE;
		$recipients_array = explode( ',', $recipients );
		$recipients_array_count = count( $recipients_array );
		if ( $recipients_array_count > 90 ) {
			// On envoie par troupeaux de 99 investisseurs
			$recipients = '';
			$index = 0;
			for ( $i = 0; $i < $recipients_array_count; $i++ ) {
				$recipients .= $recipients_array[ $i ];
				$index++;
				if ( $index == 90 ) {
					$parameters = array(
						'tool'			=> 'sendinblue',
						'template'		=> $id_template,
						'recipient'		=> $recipients,
						'id_project'	=> $project_api_id,
						'options'		=> json_encode( $options )
					);
					$buffer = WDGWPRESTLib::call_post_wdg( 'email', $parameters );
					$recipients = '';
					$index = 0;
					
				} elseif( $i < $recipients_array_count - 1 ) {
					$recipients .= ',';
				}
			}
		}
		
		// On envoie de toute façon au restant des investisseurs à la fin
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipients,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		$buffer = WDGWPRESTLib::call_post_wdg( 'email', $parameters );
		
		return $buffer;
	}
    //*******************************************************
    // FIN ENVOI ACTUALITE DE PROJET
    //*******************************************************
	
    //*******************************************************
    // ENVOI ACTUALITE DE PROJET
    //*******************************************************
	public static function project_mail( $recipient, $replyto_mail, $user_name, $project_name, $project_link, $project_api_id, $news_name, $news_content ) {
		ypcf_debug_log( 'NotificationsAPI::project_mail > ' . $recipient );
		$id_template = '184';
		$project_link = str_replace( 'https://', '', $project_link );
		$options = array(
			'replyto'				=> $replyto_mail,
			'NOM_UTILISATEUR'		=> $user_name,
			'NOM_PROJET'			=> $project_name,
			'LIEN_PROJET'			=> $project_link,
			'OBJET_ACTU'			=> $news_name,
			'CONTENU_ACTU'			=> $news_content
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
    //*******************************************************
    // FIN ENVOI ACTUALITE DE PROJET
    //*******************************************************


	//**************************************************************************
	// Utilisateurs
	//**************************************************************************
    //*******************************************************
    // Inscription
    //*******************************************************
	public static function user_registration( $recipient, $name ) {
		$id_template = '181';
		$options = array(
			'skip_admin'			=> 1,
			'PRENOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}


	//**************************************************************************
	// Entrepreneurs
	//**************************************************************************
    //*******************************************************
    // Conseils quotidiens
    //*******************************************************
	public static function campaign_advice( $recipient, $replyto_mail, $campaign_name, $campaign_dashboard_url, $user_name, $greetings, $last_24h, $top_actions ) {
		$id_template = '641';
		$campaign_dashboard_url_clean = str_replace( 'https://', '', $campaign_dashboard_url );
		$options = array(
			'personal'					=> 1,
			'replyto'					=> $replyto_mail,
			'NOM_PROJET'				=> $campaign_name,
			'URL_TB'					=> $campaign_dashboard_url_clean,
			'NOM_UTILISATEUR'			=> $user_name,
			'SALUTATIONS'				=> $greetings,
			'RESUME_24H'				=> $last_24h,
			'ACTIONS_PRIORITAIRES'		=> $top_actions
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}


	//**************************************************************************
	// KYC
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS KYC - RIB VALIDE
    //*******************************************************
	public static function rib_authentified( $recipient, $name ) {
		$id_template = '311';
		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - EN COURS DE VALIDATION
    //*******************************************************
	public static function kyc_waiting( $recipient, $name ) {
		$id_template = '322';
		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - REFUSES
    //*******************************************************
	public static function kyc_refused( $recipient, $name, $authentication_info ) {
		$id_template = '749';
		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $name,
			'PRECISIONS'			=> $authentication_info
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}

	public static function phone_kyc_refused( $recipient, $name ) {
		$param_content = "Bonjour " .$name.", un ou plusieurs documents ont été refusés sur votre compte WE DO GOOD, qui n'a pas pu être authentifié. Ceci bloque votre authentification. Connectez-vous afin d'en savoir plus : www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $recipient
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - UN SEUL DOC VALIDE
    //*******************************************************
	public static function kyc_single_validated( $recipient, $name ) {
		$id_template = '777';
		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}

	public static function phone_kyc_single_validated( $recipient, $name ) {
		$param_content = "Bonjour " .$name.", un document a été validé sur WE DO GOOD ! Finalisez l'authentification de votre compte en y déposant le(s) document(s) manquant(s) : www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $recipient
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - VALIDES
    //*******************************************************
	public static function kyc_authentified( $recipient, $name ) {
		$id_template = '324';
		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}

	public static function phone_kyc_authentified( $recipient, $name ) {
		$param_content = "Bonjour " .$name.", nous avons le plaisir de vous annoncer que votre compte est désormais authentifié sur WE DO GOOD ! www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $recipient
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - VALIDES ET INVESTISSEMENT EN ATTENTE
    //*******************************************************
	public static function kyc_authentified_and_pending_investment( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = '605';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - VALIDES ET INVESTISSEMENT EN ATTENTE - RAPPEL
    //*******************************************************
	public static function kyc_authentified_and_pending_investment_reminder( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = '606';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}


	//**************************************************************************
	// Relances
	//**************************************************************************
    //*******************************************************
    // RELANCE - EVALUATION - AVEC INTENTION
    //*******************************************************
	public static function confirm_vote_invest_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '573';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // RELANCE - EVALUATION - SANS INTENTION
    //*******************************************************
	public static function confirm_vote_invest_no_intention( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '575';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // RELANCE - PRE-LANCEMENT - EVALUATION AVEC INTENTION
    //*******************************************************
	public static function confirm_prelaunch_invest_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '576';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_prelaunch_invest_no_intention( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '577';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_prelaunch_invest_follow( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '578';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // RELANCE - INVESTISSEMENT - 30%
    //*******************************************************
	public static function confirm_investment_invest30_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $project_percent, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '579';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'POURCENT'					=> $project_percent,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_investment_invest30_no_intention( $recipient, $name, $project_name, $project_url, $project_percent, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '580';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'POURCENT'					=> $project_percent,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_investment_invest30_follow( $recipient, $name, $project_name, $project_url, $project_percent, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '650';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'POURCENT'					=> $project_percent,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // RELANCE - INVESTISSEMENT - 100%
    //*******************************************************
	public static function confirm_investment_invest100_invested( $recipient, $name, $project_name, $project_url, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = '621';
		$project_url = str_replace( 'https://', '', $project_url );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'NB_JOURS_RESTANTS'			=> $nb_remaining_days,
			'PLURIEL_JOURS_RESTANTS'	=> ( $nb_remaining_days > 1 ) ? 's' : '',
			'DATE_HEURE_FIN'			=> $date_hour_end
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_investment_invest100_investment_pending( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = '652';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_investment_invest100_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = '622';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description,
			'NB_JOURS_RESTANTS'			=> $nb_remaining_days,
			'PLURIEL_JOURS_RESTANTS'	=> ( $nb_remaining_days > 1 ) ? 's' : '',
			'DATE_HEURE_FIN'			=> $date_hour_end
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_investment_invest100_no_intention( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = '623';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description,
			'NB_JOURS_RESTANTS'			=> $nb_remaining_days,
			'PLURIEL_JOURS_RESTANTS'	=> ( $nb_remaining_days > 1 ) ? 's' : '',
			'DATE_HEURE_FIN'			=> $date_hour_end
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_investment_invest100_follow( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = '651';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description,
			'NB_JOURS_RESTANTS'			=> $nb_remaining_days,
			'PLURIEL_JOURS_RESTANTS'	=> ( $nb_remaining_days > 1 ) ? 's' : '',
			'DATE_HEURE_FIN'			=> $date_hour_end
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // RELANCE - INVESTISSEMENT - J-2
    //*******************************************************
	public static function confirm_investment_invest2days_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = '581';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description,
			'NB_JOURS_RESTANTS'			=> $nb_remaining_days,
			'DATE_HEURE_FIN'			=> $date_hour_end
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function confirm_investment_invest2days_no_intention( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = '582';
		$project_url = str_replace( 'https://', '', $project_url );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description,
			'NB_JOURS_RESTANTS'			=> $nb_remaining_days,
			'DATE_HEURE_FIN'			=> $date_hour_end
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	//**************************************************************************
	// Evaluation
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS EVALUATION - AVEC INTENTION - PAS AUTHENTIFIE
    //*******************************************************
	public static function vote_authentication_needed_reminder( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = '632';
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS EVALUATION - AVEC INTENTION - AUTHENTIFIE
    //*******************************************************
	public static function vote_authenticated_reminder( $recipient, $name, $project_name, $project_url, $project_api_id, $intention_amount ) {
		$id_template = '628';
		$project_url = str_replace( 'https://', '', $project_url );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $name,
			'NOM_PROJET'				=> $project_name,
			'URL_PROJET'				=> $project_url,
			'INTENTION_INVESTISSEMENT'	=> $intention_amount
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	

	//**************************************************************************
	// Investissement
	//**************************************************************************
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT PAR CHEQUE - EN ATTENTE
    //*******************************************************
	public static function investment_pending_check( $recipient, $name, $amount, $project_name, $percent_to_reach, $minimum_goal, $organization_name, $project_api_id ) {
		$id_template = '172';
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $name,
			'MONTANT'				=> $amount,
			'NOM_PROJET'			=> $project_name,
			'POURCENT_ATTEINT'		=> $percent_to_reach,
			'OBJECTIF'				=> $minimum_goal,
			'NOM_ORGANISATION'		=> $organization_name,
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT PAR VIREMENT - EN ATTENTE
	//*******************************************************
	public static function investment_pending_wire( $recipient, $name, $amount, $project_name, $user_lw_wallet_id, $project_api_id ) {
			$id_template = '177';
			$options = array(
				'personal'				=> 1,
				'NOM'					=> $name,
				'MONTANT'				=> $amount,
				'NOM_PROJET'			=> $project_name,
				'ID_WALLET_LEMONWAY'	=> $user_lw_wallet_id,
			);
			$parameters = array(
				'tool'			=> 'sendinblue',
				'template'		=> $id_template,
				'recipient'		=> $recipient,
				'id_project'	=> $project_api_id,
				'options'		=> json_encode( $options )
			);
			return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - VALIDE
    //*******************************************************
	public static function investment_success_project( $recipient, $name, $amount, $project_name, $project_url, $date, $text_before, $text_after, $attachment_url, $project_api_id ) {
		$id_template = '687';
		$project_url = str_replace( 'https://', '', $project_url );
		$options = array(
			'personal'				=> 1,
			'NOM_UTILISATEUR'		=> $name,
			'MONTANT'				=> $amount,
			'NOM_PROJET'			=> $project_name,
			'URL_PROJET'			=> $project_url,
			'DATE'					=> $date,
			'TEXTE_AVANT'			=> $text_before,
			'TEXTE_APRES'			=> $text_after,
		);
		if ( !empty( $attachment_url ) && WP_DEBUG != TRUE) {
			$options[ 'url_attachment' ] = $attachment_url;
		}
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function investment_success_positive_savings( $recipient, $name, $amount, $project_url, $date, $text_before, $text_after, $attachment_url, $project_api_id ) {
		$id_template = '688';
		$project_url = str_replace( 'https://', '', $project_url );
		$options = array(
			'personal'				=> 1,
			'NOM_UTILISATEUR'		=> $name,
			'MONTANT'				=> $amount,
			'URL_PROJET'			=> $project_url,
			'DATE'					=> $date,
			'TEXTE_AVANT'			=> $text_before,
			'TEXTE_APRES'			=> $text_after,
		);
		if ( !empty( $attachment_url ) && WP_DEBUG != TRUE ) {
			$options[ 'url_attachment' ] = $attachment_url;
		}
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - ERREUR - POUR UTILISATEUR
    //*******************************************************
	public static function investment_error( $recipient, $name, $amount, $project_name, $project_api_id, $lemonway_reason, $investment_link ) {
		$id_template = '175';
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $name,
			'MONTANT'				=> $amount,
			'NOM_PROJET'			=> $project_name,
			'RAISON_LEMONWAY'		=> $lemonway_reason,
			'LIEN_INVESTISSEMENT'	=> $investment_link,
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - ERREUR - POUR UTILISATEUR
    //*******************************************************
	public static function wire_transfer_received( $recipient, $name, $amount ) {
		$id_template = '780';
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $name,
			'MONTANT'				=> $amount
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - DEMANDE AUTHENTIFICATION
    //*******************************************************
	public static function investment_authentication_needed( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = '603';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - DEMANDE AUTHENTIFICATION - RAPPEL
    //*******************************************************
	public static function investment_authentication_needed_reminder( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = '604';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}


	//**************************************************************************
	// Fin de campagne
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS SUCCES CAMPAGNE PUBLIQUE
	//*******************************************************
	public static function campaign_end_success_public( $recipient, $name, $project_name, $project_date_first_payment, $project_api_id ) {
		$id_template = '178';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name,
			'MOIS_ANNEE_DEMARRAGE'		=> $project_date_first_payment
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS SUCCES CAMPAGNE PRIVEE
	//*******************************************************
	public static function campaign_end_success_private( $recipient, $name, $project_name, $project_date_first_payment, $project_api_id ) {
		$id_template = '629';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name,
			'MOIS_ANNEE_DEMARRAGE'		=> $project_date_first_payment
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS EN ATTENTE DU SEUIL DE VALIDATION
	//*******************************************************
	public static function campaign_end_pending_goal( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = '699';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS ECHEC CAMPAGNE
    //*******************************************************
	public static function campaign_end_failure( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = '179';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}


	//**************************************************************************
	// Déclarations
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS DECLARATIONS ROI A FAIRE
    //*******************************************************
	/**
	 * Envoie la notification de déclaration à faire aux porteurs de projet
	 * @param string or array $recipients
	 * @param int $nb_remaining_days
	 * @param boolean $has_mandate
	 * @return boolean
	 */
	public static function declaration_to_do( $recipients, $nb_remaining_days, $has_mandate, $options ) {
		$param_template_by_remaining_days = array(
			'9-mandate'		=> '114',
			'9-nomandate'	=> '115',
			'2-mandate'		=> '119',
			'2-nomandate'	=> '116',
			'0-mandate'		=> '121',
			'0-nomandate'	=> '120'
		);
		$index = $nb_remaining_days;
		if ( $has_mandate ) {
			$index .= '-mandate';
		} else {
			$index .= '-nomandate';
		}
		$param_template = isset( $param_template_by_remaining_days[ $index ] ) ? $param_template_by_remaining_days[ $index ] : FALSE;
		
		if ( !empty( $param_template ) ) {
			$param_recipients = is_array( $recipients ) ? implode( ',', $recipients ) : $recipients;
			$parameters = array(
				'tool'		=> 'sendinblue',
				'template'	=> $param_template,
				'recipient'	=> $param_recipients,
				'options'	=> json_encode( $options )
			);
			return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
		}
		
		return FALSE;
	}
	
	public static function declaration_to_do_warning( $recipient, $user_name, $nb_quarter, $percent_estimation, $amount_estimation_year, $amount_estimation_quarter, $percent_royalties, $amount_royalties, $amount_fees, $amount_total, $mandate_wire_date ) {
		$id_template = '595';
		$options = array(
			'personal'							=> 1,
			'NOM_UTILISATEUR'					=> $user_name,
			'NB_TRIMESTRE'						=> $nb_quarter,
			'POURCENT_PREVISIONNEL'				=> $percent_estimation,
			'MONTANT_PREVISIONNEL_ANNEE'		=> $amount_estimation_year,
			'MONTANT_PREVISIONNEL_TRIMESTRE'	=> $amount_estimation_quarter,
			'POURCENT_ROYALTIES'				=> $percent_royalties,
			'MONTANT_ROYALTIES'					=> $amount_royalties,
			'MONTANT_COMMISSION'				=> $amount_fees,
			'MONTANT_TOTAL'						=> $amount_total,
			'DATE_PRELEVEMENT'					=> $mandate_wire_date
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
    //*******************************************************
    // FIN - NOTIFICATIONS DECLARATIONS ROI A FAIRE
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS DECLARATIONS APROUVEES
    //*******************************************************
	public static function declaration_done_with_turnover( $recipient, $name, $project_name, $last_three_months, $turnover_amount ) {
		$id_template = '127';
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $name,
			'NOM_PROJET'			=> $project_name,
			'TROIS_DERNIERS_MOIS'	=> $last_three_months,
			'MONTANT_ROYALTIES'		=> $turnover_amount
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function declaration_done_without_turnover( $recipient, $name, $project_name, $last_three_months ) {
		$id_template = '150';
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $name,
			'NOM_PROJET'			=> $project_name,
			'TROIS_DERNIERS_MOIS'	=> $last_three_months
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
    //*******************************************************
    // FIN - NOTIFICATIONS DECLARATIONS APROUVEES
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS PROLONGATION DECLARATIONS
    //*******************************************************
	public static function declaration_to_be_extended( $recipient, $name, $amount_transferred, $amount_minimum_royalties, $amount_remaining ) {
		$id_template = '692';
		$options = array(
			'personal'					=> 1,
			'NOM'						=> $name,
			'MONTANT_DEJA_VERSE'		=> $amount_transferred,
			'MONTANT_MINIMUM_A_VERSER'	=> $amount_minimum_royalties,
			'MONTANT_RESTANT_A_VERSER'	=> $amount_remaining
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function declaration_extended_project_manager( $recipient, $name ) {
		$id_template = '736';
		$options = array(
			'personal'					=> 1,
			'NOM'						=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function declaration_extended_investor( $recipient, $name, $project_name, $funding_duration, $date, $project_url, $amount_investment, $amount_royalties, $amount_remaining, $project_api_id ) {
		$id_template = '694';
		$project_url = str_replace( 'https://', '', $project_url );
		$options = array(
			'personal'					=> 1,
			'NOM'						=> $name,
			'NOM_PROJET'				=> $project_name,
			'DUREE_FINANCEMENT'			=> $funding_duration,
			'DATE'						=> $date,
			'URL_PROJET'				=> $project_url,
			'MONTANT_INVESTI'			=> $amount_investment,
			'MONTANT_ROYALTIES'			=> $amount_royalties,
			'MONTANT_RESTANT'			=> $amount_remaining
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'id_project'	=> $project_api_id,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function declaration_finished_project_manager( $recipient, $name ) {
		$id_template = '735';
		$options = array(
			'personal'					=> 1,
			'NOM'						=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function declaration_finished_investor( $recipient, $name, $project_name, $date, $project_url, $amount_investment, $amount_royalties, $project_api_id ) {
		$id_template = '693';
		$project_url = str_replace( 'https://', '', $project_url );
		$options = array(
			'personal'					=> 1,
			'NOM'						=> $name,
			'NOM_PROJET'				=> $project_name,
			'DATE'						=> $date,
			'URL_PROJET'				=> $project_url,
			'MONTANT_INVESTI'			=> $amount_investment,
			'MONTANT_ROYALTIES'			=> $amount_royalties
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'id_project'	=> $project_api_id,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
    //*******************************************************
    // NOTIFICATIONS PROLONGATION DECLARATIONS
    //*******************************************************
	
	
	//**************************************************************************
	// Versements
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS VERSEMENT AVEC ROYALTIES PLUSIEURS PROJETS
    //*******************************************************
	public static function roi_transfer_daily_resume( $recipient, $name, $royalties_message ) {
		$id_template = '139';
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $name,
			'RESUME_ROYALTIES'	=> $royalties_message,
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // MESSAGE D'ENTREPRENEUR SUITE VERSEMENT ROYALTIES
    //*******************************************************
	public static function roi_transfer_message( $recipient, $name, $project_name, $declaration_message, $replyto_mail ) {
		$id_template = '522';
		$options = array(
			'personal'			=> 1,
			'replyto'			=> $replyto_mail,
			'NOM_UTILISATEUR'	=> $name,
			'NOM_PROJET'		=> $project_name,
			'CONTENU_MESSAGE'	=> $declaration_message,
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATION VERSEMENT AYANT ATTEINT LE MAXIMUM
    //*******************************************************
	public static function roi_transfer_with_max_reached( $recipient, $name, $project_name, $max_profit, $date_investment, $url_project, $amount_investment, $amount_royalties ) {
		$id_template = '691';
		$options = array(
			'personal'			=> 1,
			'NOM'				=> $name,
			'NOM_PROJET'		=> $project_name,
			'RETOUR_MAXIMUM'	=> $max_profit,
			'DATE'				=> $date_investment,
			'URL_PROJET'		=> $url_project,
			'MONTANT_INVESTI'	=> $amount_investment,
			'MONTANT_ROYALTIES'	=> $amount_royalties
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
    //*******************************************************
    // NOTIFICATION VERSEMENT SUR COMPTE BANCAIRE
    //*******************************************************
	public static function transfer_to_bank_account_confirmation( $recipient, $name, $amount ) {
		$id_template = '779';
		$options = array(
			'personal'			=> 1,
			'NOM'				=> $name,
			'MONTANT'			=> $amount
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	
}
