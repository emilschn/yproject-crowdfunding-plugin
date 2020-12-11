<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsAPI {
	
	public static $description_str_by_template_id = array(
		'new-project' => array(
			'fr-sib-id'		=> '1143',
			'description'	=> "Nouveau projet créé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'new-project-news' => array(
			'fr-sib-id'		=> '156',
			'description'	=> "Actualité",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'new-mail-contact-list' => array(
			'fr-sib-id'		=> '184',
			'description'	=> "Mail via liste de contacts",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'subscription' => array(
			'fr-sib-id'		=> '181',
			'description'	=> "Inscription",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'subscription-without-investment' => array(
			'fr-sib-id'		=> '932',
			'description'	=> "Inscription sans investissement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'subscription-without-investment-not-open' => array (
			'fr-sib-id'		=> '937',
			'description'	=> "Inscription sans investissement - rappel mail pas ouvert",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'subscription-without-investment-not-clicked' => array (
			'fr-sib-id'		=> '938',
			'description'	=> "Inscription sans investissement - rappel mail pas cliqué",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'subscription-without-investment-not-invested' => array (
			'fr-sib-id'		=> '939',
			'description'	=> "Inscription sans investissement - rappel",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'password-changed' => array(
			'fr-sib-id'		=> '2237',
			'description'	=> "Mise à jour du mot de passe",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-iban-validated' => array(
			'fr-sib-id'		=> '311',
			'description'	=> "KYC - RIB validé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-doc-waiting' => array(
			'fr-sib-id'		=> '322',
			'description'	=> "KYC - Doc en cours de validation",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-doc-refused' => array(
			'fr-sib-id'		=> '749',
			'description'	=> "KYC - Doc refusé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-doc-single-validation' => array(
			'fr-sib-id'		=> '777',
			'description'	=> "KYC - Un seul doc validé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-authentified' => array(
			'fr-sib-id'		=> '324',
			'description'	=> "KYC - Wallet validé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-pitch-proofreading' => array(
			'fr-sib-id'		=> '2344',
			'description'	=> "Demande de relecture reçue",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-campaign-advice' => array(
			'fr-sib-id'		=> '641',
			'description'	=> "Conseils quotidiens",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-vote-confirm-intention' => array(
			'fr-sib-id'		=> '573',
			'description'	=> "Relance - Evaluation - Avec intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-vote-without-intention' => array(
			'fr-sib-id'		=> '575',
			'description'	=> "Relance - Evaluation - Sans intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-prelaunch-vote-confirm-intention' => array(
			'fr-sib-id'		=> '576',
			'description'	=> "Relance - Pré-lancement - Evaluation avec intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-prelaunch-vote-without-intention' => array(
			'fr-sib-id'		=> '577',
			'description'	=> "Relance - Pré-lancement - Evaluation sans intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-prelaunch-follow' => array(
			'fr-sib-id'		=> '578',
			'description'	=> "Relance - Pré-lancement - Suit le projet",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-end-vote-waiting' => array(
			'fr-sib-id'		=> '2249',
			'description'	=> "Fin évaluation - En attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-end-vote-canceled' => array(
			'fr-sib-id'		=> '2246',
			'description'	=> "Fin évaluation - Annulé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-end-vote-canceled-refund' => array(
			'fr-sib-id'		=> '2247',
			'description'	=> "Fin évaluation - Annulé - Remboursement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-30percent-with-intention' => array (
			'fr-sib-id'		=> '579',
			'description'	=> "Relance - Investissement 30 % - Avec intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-30percent-without-intention' => array (
			'fr-sib-id'		=> '580',
			'description'	=> "Relance - Investissement 30 % - Sans intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-30percent-follow' => array (
			'fr-sib-id'		=> '650',
			'description'	=> "Relance - Investissement 30 % - Suit le projet",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-with-investment' => array (
			'fr-sib-id'		=> '621',
			'description'	=> "Relance - Investissement 100 % - Avec investissement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-with-pending-investment' => array (
			'fr-sib-id'		=> '652',
			'description'	=> "Relance - Investissement 100 % - Avec investissement en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-with-with-intention' => array (
			'fr-sib-id'		=> '622',
			'description'	=> "Relance - Investissement 100 % - Avec intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-with-without-intention' => array (
			'fr-sib-id'		=> '623',
			'description'	=> "Relance - Investissement 100 % - Sans intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-follow' => array (
			'fr-sib-id'		=> '651',
			'description'	=> "Relance - Investissement 100 % - Suit le projet",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-2days-with-intention' => array (
			'fr-sib-id'		=> '581',
			'description'	=> "Relance - Investissement J-2 - Avec intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-2days-without-intention' => array (
			'fr-sib-id'		=> '582',
			'description'	=> "Relance - Investissement J-2 - Sans intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-vote-intention-authentication' => array (
			'fr-sib-id'		=> '632',
			'description'	=> "Evaluation avec intention - Demande d'authentification",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-vote-intention-preinvestment' => array (
			'fr-sib-id'		=> '628',
			'description'	=> "Evaluation avec intention - Demande de pré-investissement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-authentication' => array (
			'fr-sib-id'		=> '603',
			'description'	=> "Investissement - Demande d'authentification",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-authentication-reminder' => array (
			'fr-sib-id'		=> '604',
			'description'	=> "Investissement - Demande d'authentification - Rappel",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-authenticated-pending-investment' => array (
			'fr-sib-id'		=> '605',
			'description'	=> "KYC - Wallet validé et investissement en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-authenticated-pending-investment-reminder' => array (
			'fr-sib-id'		=> '606',
			'description'	=> "KYC - Wallet validé et investissement en attente - Rappel",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-error' => array (
			'fr-sib-id'		=> '175',
			'description'	=> "Erreur d'investissement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'received-wire-without-pending-investment' => array (
			'fr-sib-id'		=> '780',
			'description'	=> "Réception virement bancaire sans investissement en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-check-pending' => array (
			'fr-sib-id'		=> '172',
			'description'	=> "Investissement par chèque en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-wire-pending' => array (
			'fr-sib-id'		=> '177',
			'description'	=> "Investissement par virement en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-project-validated' => array (
			'fr-sib-id'		=> '687',
			'description'	=> "Investissement sur projet validé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-positive-savings-validated' => array (
			'fr-sib-id'		=> '688',
			'description'	=> "Investissement sur épargne positive validé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-validated-campaign-public' => array (
			'fr-sib-id'		=> '178',
			'description'	=> "Projet validé - campagne publique",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-validated-campaign-private' => array (
			'fr-sib-id'		=> '629',
			'description'	=> "Projet validé - campagne privée",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-pending-validation' => array (
			'fr-sib-id'		=> '699',
			'description'	=> "Projet en attente d'atteinte du seuil de validation",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-failed' => array (
			'fr-sib-id'		=> '179',
			'description'	=> "Projet échoué",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'mandate-to-send-to-bank' => array (
			'fr-sib-id'		=> '1751',
			'description'	=> "Envoi mandat de prélèvement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-9days-with-mandate' => array (
			'fr-sib-id'		=> '114',
			'description'	=> "Déclarations - Rappel J-9 (avec prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-9days-without-mandate' => array (
			'fr-sib-id'		=> '115',
			'description'	=> "Déclarations - Rappel J-9 (sans prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-2days-with-mandate' => array (
			'fr-sib-id'		=> '119',
			'description'	=> "Déclarations - Rappel J-2 (avec prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-9days-without-mandate' => array (
			'fr-sib-id'		=> '116',
			'description'	=> "Déclarations - Rappel J-2 (sans prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-dday-with-mandate' => array (
			'fr-sib-id'		=> '121',
			'description'	=> "Déclarations - Rappel J (avec prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-dday-without-mandate' => array (
			'fr-sib-id'		=> '120',
			'description'	=> "Déclarations - Rappel J (sans prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-mandate-payment-warning' => array (
			'fr-sib-id'		=> '595',
			'description'	=> "Déclarations - Avertissement prélèvement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-done-with-turnover' => array (
			'fr-sib-id'		=> '127',
			'description'	=> "Déclaration faite avec CA",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-done-without-turnover' => array (
			'fr-sib-id'		=> '150',
			'description'	=> "Déclaration faite sans CA",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-extended-warning' => array (
			'fr-sib-id'		=> '692',
			'description'	=> "Déclaration - Avertissement prolongation",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-extended-project-manager' => array (
			'fr-sib-id'		=> '736',
			'description'	=> "Déclaration - Prolongation (porteur de projet)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-extended-investors' => array (
			'fr-sib-id'		=> '694',
			'description'	=> "Déclaration - Prolongation (investisseurs)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-end-project-manager' => array (
			'fr-sib-id'		=> '735',
			'description'	=> "Déclaration - Fin (porteur de projet)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-end-investors' => array (
			'fr-sib-id'		=> '693',
			'description'	=> "Déclaration - Fin (investisseurs)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-daily-resume' => array (
			'fr-sib-id'		=> '139',
			'description'	=> "Versement de royalties - résumé quotidien",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-more-200euros' => array (
			'fr-sib-id'		=> '1042',
			'description'	=> "Versement de royalties - plus de 200 euros",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-more-200euros-reminder-not-open' => array (
			'fr-sib-id'		=> '1044',
			'description'	=> "Versement de royalties - plus de 200 euros - rappel pas ouvert",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-more-200euros-reminder-not-clicked' => array (
			'fr-sib-id'		=> '1045',
			'description'	=> "Versement de royalties - plus de 200 euros - rappel pas cliqué",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investors-royalties-more-200euros-project-manager-alert' => array (
			'fr-sib-id'		=> '1268',
			'description'	=> "Versement de royalties - plus de 200 euros - notif entrepreneur",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-with-message' => array (
			'fr-sib-id'		=> '522',
			'description'	=> "Versement de royalties - transfert avec message",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-max-amount-reached' => array (
			'fr-sib-id'		=> '691',
			'description'	=> "Versement de royalties - montant maximum atteint",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'transfer-money-to-bank-account' => array (
			'fr-sib-id'		=> '779',
			'description'	=> "Versement sur compte bancaire - confirmation",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'password-reset' => array (
			'fr-sib-id'		=> '1075',
			'description'	=> "Réinitialisation de mot de passe",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-draft-list' => array (
			'fr-sib-id'		=> '1316',
			'description'	=> "Test d'éligibilité - Récupération liste de tests",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-draft-started' => array (
			'fr-sib-id'		=> '1374',
			'description'	=> "Test d'éligibilité - Lien test démarré",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-draft-finished' => array (
			'fr-sib-id'		=> '1373',
			'description'	=> "Test d'éligibilité - Projet éligible",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-payment-method-select-wire' => array (
			'fr-sib-id'		=> '2298',
			'description'	=> "Test d'éligibilité - Paiement par virement bancaire choisi",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-payment-method-received-wire' => array (
			'fr-sib-id'		=> '2299',
			'description'	=> "Test d'éligibilité - Paiement par virement bancaire reçu",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-payment-method-received-card' => array (
			'fr-sib-id'		=> '2294',
			'description'	=> "Test d'éligibilité - Paiement par carte bancaire réussi",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-payment-method-error-card' => array (
			'fr-sib-id'		=> '2295',
			'description'	=> "Test d'éligibilité - Paiement par carte bancaire échoué",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-dashboard-not-created' => array (
			'fr-sib-id'		=> '2297',
			'description'	=> "Test d'éligibilité - Tableau de bord pas encore créé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		)
	);

	/**
	 * Méthode générique d'appel à l'API pour attrapper les erreurs
	 */
	private static function send( $parameters ) {
		$result = WDGWPRESTLib::call_post_wdg( 'email', $parameters );
		if ( empty( $result->result ) ) {
			NotificationsAsana::notification_api_failed( $result );
		}
		return $result;
	}

	private static function get_id_fr_by_slug( $slug ) {
		if ( !empty( self::$description_str_by_template_id[ $slug ] ) && !empty( self::$description_str_by_template_id[ $slug ][ 'fr-sib-id' ] ) ) {
			return self::$description_str_by_template_id[ $slug ][ 'fr-sib-id' ];
		}
		return FALSE;
	}
	

	//**************************************************************************
	// Campagne
	//**************************************************************************
    //*******************************************************
    // NOUVEAU PROJET PUBLIE
    //*******************************************************
	public static function new_project_published( $recipient, $name, $project_link, $project_api_id ) {
		ypcf_debug_log( 'NotificationsAPI::new_project_published > ' . $recipient );
		$id_template = self::get_id_fr_by_slug( 'new-project' );
		$project_link_clean = str_replace( 'https://', '', $project_link );
		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $name,
			'DASHBOARD_URL'			=> $project_link_clean
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

    //*******************************************************
    // ENVOI ACTUALITE DE PROJET
    //*******************************************************
	public static function new_project_news( $recipients, $replyto_mail, $project_name, $project_link, $project_api_id, $news_name, $news_content ) {
		ypcf_debug_log( 'NotificationsAPI::new_project_news > ' . $recipients );
		$id_template = self::get_id_fr_by_slug( 'new-project-news' );
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
					self::send( $parameters );
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
		
		return self::send( $parameters );
	}
    //*******************************************************
    // FIN ENVOI ACTUALITE DE PROJET
    //*******************************************************
	
    //*******************************************************
    // ENVOI ACTUALITE DE PROJET
    //*******************************************************
	public static function project_mail( $recipient, $replyto_mail, $user_name, $project_name, $project_link, $project_api_id, $news_name, $news_content ) {
		ypcf_debug_log( 'NotificationsAPI::project_mail > ' . $recipient );
		$id_template = self::get_id_fr_by_slug( 'new-mail-contact-list' );
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
		return self::send( $parameters );
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
		$id_template = self::get_id_fr_by_slug( 'subscription' );
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
		return self::send( $parameters );
	}

	//*******************************************************
	// Inscription sans investissement
	//*******************************************************
	public static function user_registered_without_investment( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'subscription-without-investment' );
		$options = array(
			'NOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// Inscription sans investissement - pas ouvert
	//*******************************************************
	public static function user_registered_without_investment_not_open( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'subscription-without-investment-not-open' );
		$options = array(
			'NOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// Inscription sans investissement - pas cliqué
	//*******************************************************
	public static function user_registered_without_investment_not_clicked( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'subscription-without-investment-not-clicked' );
		$options = array(
			'NOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

    //*******************************************************
    // Inscription sans investissement - pas investi
    //*******************************************************
	public static function user_registered_without_investment_not_invested( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'subscription-without-investment-not-invested' );
		$options = array(
			'NOM'				=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// Modification du mot de passe
	//*******************************************************
	public static function user_password_change( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'password-changed' );
		$options = array(
			'NOM'				=> $name
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
    // Réinitialisation de mot de passe
    //*******************************************************
	public static function password_reinit( $recipient, $name, $link ) {
		$id_template = self::get_id_fr_by_slug( 'password-reset' );
		$options = array(
			'skip_admin'			=> 1,
			'NOM'				=> $name,
			'LIEN'				=> $link
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}
	//**************************************************************************
	// Entrepreneurs
	//**************************************************************************
    //*******************************************************
    // Demande de relecture
    //*******************************************************
	public static function proofreading_request_received( $recipient_name, $recipient_mail, $replyto_mail, $id_api ) {
		$id_template = self::get_id_fr_by_slug( 'project-pitch-proofreading' );
		$options = array(
			'personal'					=> 1,
			'replyto'					=> $replyto_mail,
			'NOM'						=> $recipient_name,
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient_mail,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

    //*******************************************************
    // Conseils quotidiens
    //*******************************************************
	public static function campaign_advice( $recipient, $replyto_mail, $campaign_name, $campaign_dashboard_url, $user_name, $greetings, $last_24h, $top_actions ) {
		$id_template = self::get_id_fr_by_slug( 'project-campaign-advice' );
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
		return self::send( $parameters );
	}


	//**************************************************************************
	// KYC
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS KYC - RIB VALIDE
    //*******************************************************
	public static function rib_authentified( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'kyc-iban-validated' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - EN COURS DE VALIDATION
    //*******************************************************
	public static function kyc_waiting( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'kyc-doc-waiting' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - REFUSES
    //*******************************************************
	public static function kyc_refused( $recipient, $name, $authentication_info ) {
		$id_template = self::get_id_fr_by_slug( 'kyc-doc-refused' );
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
		return self::send( $parameters );
	}

	public static function phone_kyc_refused( $recipient, $name ) {
		$param_content = "Bonjour " .$name.", des documents ont été refusés sur votre compte WE DO GOOD, qui n'a pas pu être authentifié. Afin d'en savoir plus : www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $recipient
		);
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - UN SEUL DOC VALIDE
    //*******************************************************
	public static function kyc_single_validated( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'kyc-doc-single-validation' );
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
		return self::send( $parameters );
	}

	public static function phone_kyc_single_validated( $recipient, $name ) {
		$param_content = "Bonjour " .$name.", un document a été validé sur WE DO GOOD ! Finalisez l'authentification de votre compte en y déposant le(s) document(s) manquant(s) : www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $recipient
		);
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - VALIDES
    //*******************************************************
	public static function kyc_authentified( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'kyc-authentified' );
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
		return self::send( $parameters );
	}

	public static function phone_kyc_authentified( $recipient, $name ) {
		$param_content = "Bonjour " .$name.", nous avons le plaisir de vous annoncer que votre compte est désormais authentifié sur WE DO GOOD ! www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $recipient
		);
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - VALIDES ET INVESTISSEMENT EN ATTENTE
    //*******************************************************
	public static function kyc_authentified_and_pending_investment( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'kyc-authenticated-pending-investment' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS KYC - VALIDES ET INVESTISSEMENT EN ATTENTE - RAPPEL
    //*******************************************************
	public static function kyc_authentified_and_pending_investment_reminder( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'kyc-authenticated-pending-investment-reminder' );
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
		return self::send( $parameters );
	}


	//**************************************************************************
	// Relances
	//**************************************************************************
    //*******************************************************
    // RELANCE - EVALUATION - AVEC INTENTION
    //*******************************************************
	public static function confirm_vote_invest_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-vote-confirm-intention' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // RELANCE - EVALUATION - SANS INTENTION
    //*******************************************************
	public static function confirm_vote_invest_no_intention( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-vote-without-intention' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // RELANCE - PRE-LANCEMENT - EVALUATION AVEC INTENTION
    //*******************************************************
	public static function confirm_prelaunch_invest_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-prelaunch-vote-confirm-intention' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_prelaunch_invest_no_intention( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-prelaunch-vote-without-intention' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_prelaunch_invest_follow( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-prelaunch-follow' );
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
		return self::send( $parameters );
	}

	//*******************************************************
	// FIN EVALUATION - EN ATTENTE
	//*******************************************************
	public static function vote_end_pending_campaign( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-end-vote-waiting' );
		$options = array(
			'personal'					=> 1,
			'PRENOM'					=> $name,
			'NOM_PROJET'				=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// FIN EVALUATION - ANNULATION
	//*******************************************************
	public static function vote_end_canceled_campaign( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-end-vote-canceled' );
		$options = array(
			'personal'					=> 1,
			'PRENOM'					=> $name,
			'NOM_PROJET'				=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	public static function vote_end_canceled_campaign_refund( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-end-vote-canceled-refund' );
		$options = array(
			'personal'					=> 1,
			'PRENOM'					=> $name,
			'NOM_PROJET'				=> $project_name
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipient,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return self::send( $parameters );
	}
	
    //*******************************************************
    // RELANCE - INVESTISSEMENT - 30%
    //*******************************************************
	public static function confirm_investment_invest30_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $project_percent, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-30percent-with-intention' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_investment_invest30_no_intention( $recipient, $name, $project_name, $project_url, $project_percent, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-30percent-without-intention' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_investment_invest30_follow( $recipient, $name, $project_name, $project_url, $project_percent, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-30percent-follow' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // RELANCE - INVESTISSEMENT - 100%
    //*******************************************************
	public static function confirm_investment_invest100_invested( $recipient, $name, $project_name, $project_url, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-with-investment' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_investment_invest100_investment_pending( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-with-pending-investment' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_investment_invest100_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-with-with-intention' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_investment_invest100_no_intention( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-with-without-intention' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_investment_invest100_follow( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-follow' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // RELANCE - INVESTISSEMENT - J-2
    //*******************************************************
	public static function confirm_investment_invest2days_intention( $recipient, $name, $intention_amount, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-2days-with-intention' );
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
		return self::send( $parameters );
	}
	
	public static function confirm_investment_invest2days_no_intention( $recipient, $name, $project_name, $project_url, $testimony, $image_url, $image_description, $nb_remaining_days, $date_hour_end, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-2days-without-intention' );
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
		return self::send( $parameters );
	}
	
	//**************************************************************************
	// Evaluation
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS EVALUATION - AVEC INTENTION - PAS AUTHENTIFIE
    //*******************************************************
	public static function vote_authentication_needed_reminder( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-vote-intention-authentication' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS EVALUATION - AVEC INTENTION - AUTHENTIFIE
    //*******************************************************
	public static function vote_authenticated_reminder( $recipient, $name, $project_name, $project_url, $project_api_id, $intention_amount ) {
		$id_template = self::get_id_fr_by_slug( 'project-vote-intention-preinvestment' );
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
		return self::send( $parameters );
	}
	
	

	//**************************************************************************
	// Investissement
	//**************************************************************************
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT PAR CHEQUE - EN ATTENTE
    //*******************************************************
	public static function investment_pending_check( $recipient, $name, $amount, $project_name, $percent_to_reach, $minimum_goal, $organization_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'investment-check-pending' );
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
		return self::send( $parameters );
	}
	
	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT PAR VIREMENT - EN ATTENTE
	//*******************************************************
	public static function investment_pending_wire( $recipient, $name, $amount, $project_name, $user_lw_wallet_id, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'investment-wire-pending' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - VALIDE
    //*******************************************************
	public static function investment_success_project( $recipient, $name, $amount, $project_name, $project_url, $date, $text_before, $text_after, $attachment_url, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'investment-project-validated' );
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
		return self::send( $parameters );
	}
	
	public static function investment_success_positive_savings( $recipient, $name, $amount, $project_url, $date, $text_before, $text_after, $attachment_url, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'investment-positive-savings-validated' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - ERREUR - POUR UTILISATEUR
    //*******************************************************
	public static function investment_error( $recipient, $name, $amount, $project_name, $project_api_id, $lemonway_reason, $investment_link ) {
		$id_template = self::get_id_fr_by_slug( 'investment-error' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - ERREUR - POUR UTILISATEUR
    //*******************************************************
	public static function wire_transfer_received( $recipient, $name, $amount ) {
		$id_template = self::get_id_fr_by_slug( 'received-wire-without-pending-investment' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - DEMANDE AUTHENTIFICATION
    //*******************************************************
	public static function investment_authentication_needed( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-authentication' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - DEMANDE AUTHENTIFICATION - RAPPEL
    //*******************************************************
	public static function investment_authentication_needed_reminder( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-authentication-reminder' );
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
		return self::send( $parameters );
	}


	//**************************************************************************
	// Fin de campagne
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS SUCCES CAMPAGNE PUBLIQUE
	//*******************************************************
	public static function campaign_end_success_public( $recipient, $name, $project_name, $project_date_first_payment, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-validated-campaign-public' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS SUCCES CAMPAGNE PRIVEE
	//*******************************************************
	public static function campaign_end_success_private( $recipient, $name, $project_name, $project_date_first_payment, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-validated-campaign-private' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS EN ATTENTE DU SEUIL DE VALIDATION
	//*******************************************************
	public static function campaign_end_pending_goal( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-pending-validation' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATIONS ECHEC CAMPAGNE
    //*******************************************************
	public static function campaign_end_failure( $recipient, $name, $project_name, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'project-failed' );
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
		return self::send( $parameters );
	}


	//**************************************************************************
	// Déclarations
	//**************************************************************************
    //*******************************************************
    // ENVOI MANDAT PRELEVEMENT
    //*******************************************************
	public static function mandate_to_send_to_bank( $recipients, $user_name, $attachment_url, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'mandate-to-send-to-bank' );
		$options = array(
			'personal'		=> 1,
			'NOM'			=> $user_name
		);
		if ( !empty( $attachment_url ) && WP_DEBUG != TRUE) {
			$options[ 'url_attachment' ] = $attachment_url;
		}
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipients,
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);
		return self::send( $parameters );
	}


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
			'9-mandate'		=> self::get_id_fr_by_slug( 'declaration-9days-with-mandate' ),
			'9-nomandate'	=> self::get_id_fr_by_slug( 'declaration-9days-without-mandate' ),
			'2-mandate'		=> self::get_id_fr_by_slug( 'declaration-2days-with-mandate' ),
			'2-nomandate'	=> self::get_id_fr_by_slug( 'declaration-2days-without-mandate' ),
			'0-mandate'		=> self::get_id_fr_by_slug( 'declaration-dday-with-mandate' ),
			'0-nomandate'	=> self::get_id_fr_by_slug( 'declaration-dday-without-mandate' )
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
			return self::send( $parameters );
		}
		
		return FALSE;
	}
	
	public static function declaration_to_do_warning( $recipient, $user_name, $nb_quarter, $percent_estimation, $amount_estimation_year, $amount_estimation_quarter, $percent_royalties, $amount_royalties, $amount_fees, $amount_total, $mandate_wire_date, $declaration_direct_url ) {
		$id_template = self::get_id_fr_by_slug( 'declaration-mandate-payment-warning' );
		$declaration_direct_url = str_replace( 'https://', '', $declaration_direct_url );
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
			'DATE_PRELEVEMENT'					=> $mandate_wire_date,
			'DECLARATION_DIRECT_URL'			=> $declaration_direct_url
								
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}
    //*******************************************************
    // FIN - NOTIFICATIONS DECLARATIONS ROI A FAIRE
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS DECLARATIONS APROUVEES
    //*******************************************************
	public static function declaration_done_with_turnover( $recipient, $name, $project_name, $last_three_months, $turnover_amount, $tax_infos, $payment_certificate_url ) {
		$id_template = self::get_id_fr_by_slug( 'declaration-done-with-turnover' );
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $name,
			'NOM_PROJET'			=> $project_name,
			'TROIS_DERNIERS_MOIS'	=> $last_three_months,
			'MONTANT_ROYALTIES'		=> $turnover_amount,
			'INFOS_FISCALITE'		=> $tax_infos
		);
		if ( !empty( $attachment_url ) && WP_DEBUG != TRUE) {
			$options[ 'url_attachment' ] = $payment_certificate_url;
		}
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}
	
	public static function declaration_done_without_turnover( $recipient, $name, $project_name, $last_three_months ) {
		$id_template = self::get_id_fr_by_slug( 'declaration-done-without-turnover' );
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
		return self::send( $parameters );
	}
    //*******************************************************
    // FIN - NOTIFICATIONS DECLARATIONS APROUVEES
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS PROLONGATION DECLARATIONS
    //*******************************************************
	public static function declaration_to_be_extended( $recipient, $name, $amount_transferred, $amount_minimum_royalties, $amount_remaining ) {
		$id_template = self::get_id_fr_by_slug( 'declaration-extended-warning' );
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
		return self::send( $parameters );
	}
	
	public static function declaration_extended_project_manager( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'declaration-extended-project-manager' );
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
		return self::send( $parameters );
	}
	
	public static function declaration_extended_investor( $recipient, $name, $project_name, $funding_duration, $date, $project_url, $amount_investment, $amount_royalties, $amount_remaining, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'declaration-extended-investors' );
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
		return self::send( $parameters );
	}
	
	public static function declaration_finished_project_manager( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'declaration-end-project-manager' );
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
		return self::send( $parameters );
	}
	
	public static function declaration_finished_investor( $recipient, $name, $project_name, $date, $project_url, $amount_investment, $amount_royalties, $project_api_id ) {
		$id_template = self::get_id_fr_by_slug( 'declaration-end-investors' );
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
		return self::send( $parameters );
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
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-daily-resume' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // WALLET AVEC PLUS DE 200 EUROS
    //*******************************************************
	public static function wallet_with_more_than_200_euros( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-more-200euros' );
		$options = array(
			'personal'	=> 1,
			'NOM'		=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}
	
    //*******************************************************
    // WALLET AVEC PLUS DE 200 EUROS - RAPPEL MAIL PAS OUVERT
    //*******************************************************
	public static function wallet_with_more_than_200_euros_reminder_not_open( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-more-200euros-reminder-not-open' );
		$options = array(
			'personal'	=> 1,
			'NOM'		=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}
	
    //*******************************************************
    // WALLET AVEC PLUS DE 200 EUROS - RAPPEL MAIL PAS CLIQUE
    //*******************************************************
	public static function wallet_with_more_than_200_euros_reminder_not_clicked( $recipient, $name ) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-more-200euros-reminder-not-clicked' );
		$options = array(
			'personal'	=> 1,
			'NOM'		=> $name
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}
	
    //*******************************************************
    // WALLET AVEC PLUS DE 200 EUROS - NOTIF ENTREPRENEUR
    //*******************************************************
	public static function investors_with_wallet_with_more_than_200_euros( $recipient, $name, $investors_list_str ) {
		$id_template = self::get_id_fr_by_slug( 'investors-royalties-more-200euros-project-manager-alert' );
		$options = array(
			'personal'		=> 1,
			'PRENOM'		=> $name,
			'INVESTISSEURS'	=> $investors_list_str
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}
	
    //*******************************************************
    // MESSAGE D'ENTREPRENEUR SUITE VERSEMENT ROYALTIES
    //*******************************************************
	public static function roi_transfer_message( $recipient, $name, $project_name, $declaration_message, $replyto_mail ) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-with-message' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATION VERSEMENT AYANT ATTEINT LE MAXIMUM
    //*******************************************************
	public static function roi_transfer_with_max_reached( $recipient, $name, $project_name, $max_profit, $date_investment, $url_project, $amount_investment, $amount_royalties ) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-max-amount-reached' );
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
		return self::send( $parameters );
	}
	
    //*******************************************************
    // NOTIFICATION VERSEMENT SUR COMPTE BANCAIRE
    //*******************************************************
	public static function transfer_to_bank_account_confirmation( $recipient, $name, $amount ) {
		$id_template = self::get_id_fr_by_slug( 'transfer-money-to-bank-account' );
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
		return self::send( $parameters );
	}
	

	//**************************************************************************
	// Interface prospect
	//**************************************************************************
	//*******************************************************
	// LISTE DES TESTS DEMARRES
	//*******************************************************
	public static function prospect_setup_draft_list( $recipient, $name, $project_list_str ) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-draft-list' );
		$options = array(
			'personal'			=> 1,
			'NOM'				=> $name,
			'EMAIL'				=> $recipient,
			'LISTE_PROJETS'		=> $project_list_str
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// DEMARRAGE DE TEST
	//*******************************************************
	public static function prospect_setup_draft_started( $recipient, $name, $organization_name, $draft_url_full ) {
		$draft_url = str_replace( 'https://', '', $draft_url_full );
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-draft-started' );
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'personal'		=> 1,
			'NOM'			=> $name,
			'EMAIL'			=> $recipient,
			'NOM_ENTREPRISE'	=> $organization_name,
			'URL_DRAFT'		=> $draft_url,
			'URL_DRAFT_FULL'=> $draft_url_full
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// FIN DE TEST
	//*******************************************************
	public static function prospect_setup_draft_finished( $recipient, $name, $draft_url_full, $organization_name, $amount_needed, $royalties_percent, $formula, $options ) {
		$draft_url = str_replace( 'https://', '', $draft_url_full );
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-draft-finished' );
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'personal'		=> 1,
			'NOM'			=> $name,
			'EMAIL'			=> $recipient,
			'URL_DRAFT'		=> $draft_url,
			'NOM_ENTREPRISE'		=> $organization_name,
			'MONTANT_RECHERCHE'		=> $amount_needed,
			'POURCENT_ROYALTIES'	=> $royalties_percent,
			'FORMULE'		=> $formula,
			'OPTION'		=> $options
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// SELECTION DE VIREMENT
	//*******************************************************
	public static function prospect_setup_payment_method_select_wire( $recipient, $name, $amount, $iban, $subscription_reference ) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-payment-method-select-wire' );
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'						=> $name,
			'MONTANT'					=> $amount,
			'IBAN_WDG'					=> $iban,
			'REFERENCE_SOUSCRIPTION'	=> $subscription_reference,
			'personal'		=> 1
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// VIREMENT RECU
	//*******************************************************
	public static function prospect_setup_payment_method_received_wire( $recipient, $name, $amount, $date_payment ) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-payment-method-received-wire' );
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'					=> $name,
			'MONTANT'				=> $amount,
			'DATE_PAIEMENT_RECU'	=> $date_payment,
			'personal'		=> 1
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// PAIEMENT PAR CARTE RECU
	//*******************************************************
	public static function prospect_setup_payment_method_received_card( $recipient, $name, $amount, $date_payment, $orga_name ) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-payment-method-received-card' );
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'					=> $name,
			'NOM_ENTREPRISE'			=> $orga_name,
			'MONTANT'				=> $amount,
			'DATE_PAIEMENT_RECU'	=> $date_payment,
			'personal'		=> 1
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// PAIEMENT PAR CARTE ERREUR
	//*******************************************************
	public static function prospect_setup_payment_method_error_card( $recipient, $name, $draft_url ) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-payment-method-error-card' );
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'			=> $name,
			'URL_DRAFT'		=> $draft_url,
			'personal'		=> 1
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}

	//*******************************************************
	// TABLEAU DE BORD PAS ENCORE CREE
	//*******************************************************
	public static function prospect_setup_dashboard_not_created( $recipient, $name, $orga_name ) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-dashboard-not-created' );
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'			=> $name,
			'NOM_PROJET'	=> $orga_name,
			'personal'		=> 1
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);
		return self::send( $parameters );
	}
	
}
