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
		'323' => "KYC - Doc refusé",
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
		'687' => "Investissement sur projet validé",
		'688' => "Investissement sur épargne positive validé",
		'114' => "Déclarations - Rappel J-9 (avec prélèvement)",
		'115' => "Déclarations - Rappel J-9 (sans prélèvement)",
		'119' => "Déclarations - Rappel J-2 (avec prélèvement)",
		'116' => "Déclarations - Rappel J-2 (sans prélèvement)",
		'121' => "Déclarations - Rappel J (avec prélèvement)",
		'120' => "Déclarations - Rappel J (sans prélèvement)",
		'595' => "Déclarations - Avertissement prélèvement",
		'127' => "Déclaration faite avec CA",
		'150' => "Déclaration faite sans CA",
		'139' => "Versement de royalties - résumé quotidien",
		'522' => "Versement de royalties - transfert avec message"
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
	public static function kyc_refused( $recipient, $name ) {
		$id_template = '323';
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
		if ( !empty( $attachment_url ) ) {
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
		if ( !empty( $attachment_url ) ) {
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
	
	
	public static function declaration_to_do_sms( $recipients, $nb_remaining_days, $date_due_previous_day ) {
		if ( $nb_remaining_days == 10 ) {
		
			$param_content = "Bonjour, les déclarations sont ouvertes ! Déclarez votre chiffre d'affaires trimestriel avant le ".$date_due_previous_day->format( 'd/m' )." sur www.wedogood.co. A bientôt !";
			$param_recipients = is_array( $recipients ) ? implode( ',', $recipients ) : $recipients;
			$parameters = array(
				'tool'		=> 'sms',
				'template'	=> $param_content,
				'recipient'	=> $param_recipients
			);
			if ( WP_IS_DEV_SITE ) {
				return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
			}
			
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
	
}
