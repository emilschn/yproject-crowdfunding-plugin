<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class NotificationsAPI {
	private static $custom_field_reply_to = 'email_reply_to';
	private static $custom_field_sender_name = 'email_sender_name';
	private static $custom_field_sender_email = 'email_sender_email';

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
		'subscription-without-investment-not-open' => array(
			'fr-sib-id'		=> '937',
			'description'	=> "Inscription sans investissement - rappel mail pas ouvert",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'subscription-without-investment-not-clicked' => array(
			'fr-sib-id'		=> '938',
			'description'	=> "Inscription sans investissement - rappel mail pas cliqué",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'subscription-without-investment-not-invested' => array(
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
		'project-investment-30percent-with-intention' => array(
			'fr-sib-id'		=> '579',
			'description'	=> "Relance - Investissement 30 % - Avec intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-30percent-without-intention' => array(
			'fr-sib-id'		=> '580',
			'description'	=> "Relance - Investissement 30 % - Sans intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-30percent-follow' => array(
			'fr-sib-id'		=> '650',
			'description'	=> "Relance - Investissement 30 % - Suit le projet",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-with-investment' => array(
			'fr-sib-id'		=> '621',
			'description'	=> "Relance - Investissement 100 % - Avec investissement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-with-pending-investment' => array(
			'fr-sib-id'		=> '652',
			'description'	=> "Relance - Investissement 100 % - Avec investissement en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-with-with-intention' => array(
			'fr-sib-id'		=> '622',
			'description'	=> "Relance - Investissement 100 % - Avec intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-with-without-intention' => array(
			'fr-sib-id'		=> '623',
			'description'	=> "Relance - Investissement 100 % - Sans intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-success-follow' => array(
			'fr-sib-id'		=> '651',
			'description'	=> "Relance - Investissement 100 % - Suit le projet",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-2days-with-intention' => array(
			'fr-sib-id'		=> '581',
			'description'	=> "Relance - Investissement J-2 - Avec intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-2days-without-intention' => array(
			'fr-sib-id'		=> '582',
			'description'	=> "Relance - Investissement J-2 - Sans intention",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-vote-intention-authentication' => array(
			'fr-sib-id'		=> '632',
			'description'	=> "Evaluation avec intention - Demande d'authentification",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-vote-intention-preinvestment' => array(
			'fr-sib-id'		=> '628',
			'description'	=> "Evaluation avec intention - Demande de pré-investissement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-authentication' => array(
			'fr-sib-id'		=> '603',
			'description'	=> "Investissement - Demande d'authentification",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-investment-authentication-reminder' => array(
			'fr-sib-id'		=> '604',
			'description'	=> "Investissement - Demande d'authentification - Rappel",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-authenticated-pending-investment' => array(
			'fr-sib-id'		=> '605',
			'description'	=> "KYC - Wallet validé et investissement en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'kyc-authenticated-pending-investment-reminder' => array(
			'fr-sib-id'		=> '606',
			'description'	=> "KYC - Wallet validé et investissement en attente - Rappel",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-error' => array(
			'fr-sib-id'		=> '175',
			'description'	=> "Erreur d'investissement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'received-wire-without-pending-investment' => array(
			'fr-sib-id'		=> '780',
			'description'	=> "Réception virement bancaire sans investissement en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-check-pending' => array(
			'fr-sib-id'		=> '172',
			'description'	=> "Investissement par chèque en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-wire-pending' => array(
			'fr-sib-id'		=> '177',
			'description'	=> "Investissement par virement en attente",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-project-validated' => array(
			'fr-sib-id'		=> '687',
			'description'	=> "Investissement sur projet validé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investment-positive-savings-validated' => array(
			'fr-sib-id'		=> '688',
			'description'	=> "Investissement sur épargne positive validé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-validated-campaign-public' => array(
			'fr-sib-id'		=> '178',
			'description'	=> "Projet validé - campagne publique",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-validated-campaign-private' => array(
			'fr-sib-id'		=> '629',
			'description'	=> "Projet validé - campagne privée",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-pending-validation' => array(
			'fr-sib-id'		=> '699',
			'description'	=> "Projet en attente d'atteinte du seuil de validation",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'project-failed' => array(
			'fr-sib-id'		=> '179',
			'description'	=> "Projet échoué",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'mandate-to-send-to-bank' => array(
			'fr-sib-id'		=> '1751',
			'description'	=> "Envoi mandat de prélèvement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-9days-with-mandate' => array(
			'fr-sib-id'		=> '114',
			'description'	=> "Déclarations - Rappel J-9 (avec prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-9days-without-mandate' => array(
			'fr-sib-id'		=> '115',
			'description'	=> "Déclarations - Rappel J-9 (sans prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-2days-with-mandate' => array(
			'fr-sib-id'		=> '119',
			'description'	=> "Déclarations - Rappel J-2 (avec prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-2days-without-mandate' => array(
			'fr-sib-id'		=> '116',
			'description'	=> "Déclarations - Rappel J-2 (sans prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-dday-with-mandate' => array(
			'fr-sib-id'		=> '121',
			'description'	=> "Déclarations - Rappel J (avec prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-dday-without-mandate' => array(
			'fr-sib-id'		=> '120',
			'description'	=> "Déclarations - Rappel J (sans prélèvement)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-mandate-payment-warning' => array(
			'fr-sib-id'		=> '595',
			'description'	=> "Déclarations - Avertissement prélèvement",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-done-with-turnover' => array(
			'fr-sib-id'		=> '127',
			'description'	=> "Déclaration faite avec CA",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-done-without-turnover' => array(
			'fr-sib-id'		=> '150',
			'description'	=> "Déclaration faite sans CA",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-extended-warning' => array(
			'fr-sib-id'		=> '692',
			'description'	=> "Déclaration - Avertissement prolongation",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-extended-project-manager' => array(
			'fr-sib-id'		=> '736',
			'description'	=> "Déclaration - Prolongation (porteur de projet)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-extended-investors' => array(
			'fr-sib-id'		=> '694',
			'description'	=> "Déclaration - Prolongation (investisseurs)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-end-project-manager' => array(
			'fr-sib-id'		=> '735',
			'description'	=> "Déclaration - Fin (porteur de projet)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'declaration-end-investors' => array(
			'fr-sib-id'		=> '693',
			'description'	=> "Déclaration - Fin (investisseurs)",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-daily-resume' => array(
			'fr-sib-id'		=> '139',
			'description'	=> "Versement de royalties - résumé quotidien",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-more-200euros' => array(
			'fr-sib-id'		=> '1042',
			'description'	=> "Versement de royalties - plus de 200 euros",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-more-200euros-reminder-not-open' => array(
			'fr-sib-id'		=> '1044',
			'description'	=> "Versement de royalties - plus de 200 euros - rappel pas ouvert",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-more-200euros-reminder-not-clicked' => array(
			'fr-sib-id'		=> '1045',
			'description'	=> "Versement de royalties - plus de 200 euros - rappel pas cliqué",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investors-royalties-more-200euros-project-manager-alert' => array(
			'fr-sib-id'		=> '1268',
			'description'	=> "Versement de royalties - plus de 200 euros - notif entrepreneur",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-with-message' => array(
			'fr-sib-id'		=> '522',
			'description'	=> "Versement de royalties - transfert avec message",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'investor-royalties-max-amount-reached' => array(
			'fr-sib-id'		=> '691',
			'description'	=> "Versement de royalties - montant maximum atteint",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'transfer-money-to-bank-account' => array(
			'fr-sib-id'		=> '779',
			'description'	=> "Versement sur compte bancaire - confirmation",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'password-reset' => array(
			'fr-sib-id'		=> '1075',
			'description'	=> "Réinitialisation de mot de passe",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-draft-list' => array(
			'fr-sib-id'		=> '1316',
			'description'	=> "Test d'éligibilité - Récupération liste de tests",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-draft-started' => array(
			'fr-sib-id'		=> '1374',
			'description'	=> "Test d'éligibilité - Lien test démarré",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-draft-finished' => array(
			'fr-sib-id'		=> '1373',
			'description'	=> "Test d'éligibilité - Projet éligible",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-payment-method-select-wire' => array(
			'fr-sib-id'		=> '2298',
			'description'	=> "Test d'éligibilité - Paiement par virement bancaire choisi",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-payment-method-received-wire' => array(
			'fr-sib-id'		=> '2299',
			'description'	=> "Test d'éligibilité - Paiement par virement bancaire reçu",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-payment-method-received-card' => array(
			'fr-sib-id'		=> '2294',
			'description'	=> "Test d'éligibilité - Paiement par carte bancaire réussi",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-payment-method-error-card' => array(
			'fr-sib-id'		=> '2295',
			'description'	=> "Test d'éligibilité - Paiement par carte bancaire échoué",
			'variables'		=> "",
			'wdg-mail'		=> ""
		),
		'prospect-setup-dashboard-not-created' => array(
			'fr-sib-id'		=> '2297',
			'description'	=> "Test d'éligibilité - Tableau de bord pas encore créé",
			'variables'		=> "",
			'wdg-mail'		=> ""
		)
	);

	/**
	 * Méthode générique d'envoi de mail via l'API
	 */
	private static function send($parameters, $language_to_translate_to = '') {
		if ( $parameters[ 'tool' ] == 'sms' ) {
			// c'est un sms qu'on essaie d'envoyer
			$result = WDGWPRESTLib::call_post_wdg( 'email', $parameters );
			if ( empty( $result->result ) ) {
				NotificationsAsana::notification_api_failed( $parameters, $result );
			}

			return $result;
		} else {
			// On commence par vérifier si un template WordPress a déjà été créé pour remplacer le template existant
			$template_slug = self::get_slug_by_id_template_sib_v2( $parameters['template'] );
			if ( !empty( $template_slug ) ) {
				global $force_language_to_translate_to;
				$force_language_to_translate_to = $language_to_translate_to;
				$template_post = WDGConfigTextsEmails::get_config_text_email_by_name($template_slug, $language_to_translate_to);
				if ( !empty( $template_post ) ) {
					$recipient = $parameters[ 'recipient' ];
					$template_post_name = $template_slug;
					$parameters = $parameters;
					$options_encoded = $parameters[ 'options' ];
					$options_decoded = json_decode( $options_encoded );
					$object = $template_post->post_title;
					$content = $template_post->post_content;

					// Vérification si un reply_to a été défini en back-office
					$post_reply_to = get_post_meta( $template_post->ID, self::$custom_field_reply_to, TRUE );
					if ( !empty( $post_reply_to ) ) {
						$options_decoded->replyto = $post_reply_to;
					}

					// Vérification si un sender_name a été défini en back-office
					$post_sender_name = get_post_meta( $template_post->ID, self::$custom_field_sender_name, TRUE );
					if ( !empty( $post_sender_name ) ) {
						$options_decoded->sender_name = $post_sender_name;
					}

					// Vérification si un sender_email a été défini en back-office
					$post_sender_email = get_post_meta( $template_post->ID, self::$custom_field_sender_email, TRUE );
					if ( !empty( $post_sender_email ) ) {
						$options_decoded->sender_email = $post_sender_email;
					}

					$parameters[ 'options' ] = json_encode( $options_decoded );

					$result = self::send_v3( $recipient, $object, $content, $template_post_name, $parameters );
					if ( empty( $result->result ) ) {
						NotificationsAsana::notification_api_failed( $parameters, $result );
					}

					return $result;
				}
			}
			// Sinon, on envoie une alerte Asana
			NotificationsAsana::notification_api_v2_failed( $parameters );
		}

		return FALSE;
	}

	/**
	 * Méthode d'envoi de mails via l'API avec la v3 de SendInBlue
	 */
	public static function send_v3($recipient, $object, $content, $template_post_name, $parameters = array()) {
		$parameters[ 'tool' ] = 'sendinblue-v3';
		$parameters[ 'template' ] = $template_post_name;
		$parameters[ 'recipient' ] = $recipient;

		$crowdfunding = ATCF_CrowdFunding::instance();

		// Gestion des shortcodes inclus dans les mails
		NotificationsAPIShortcodes::instance();
		add_filter( 'wdg_email_object_filter', 'do_shortcode' );
		$object = apply_filters( 'wdg_email_object_filter', $object );
		$tags = array( '<p>', '</p>' );
		$object = str_replace( $tags, '', $object );
		add_filter( 'wdg_email_content_filter', 'do_shortcode' );
		add_filter( 'wdg_email_content_filter', 'wptexturize' );
		add_filter( 'wdg_email_content_filter', 'wpautop' );
		add_filter( 'wdg_email_content_filter', 'shortcode_unautop' );
		$content = apply_filters( 'wdg_email_content_filter', $content );
		// Ajout de règles inline pour les boutons (nécessaires pour les cas spécifiques, par exemple Hubspot)
		$inline_div_button_container_style = 'text-align: center;';
		$content = str_replace( '<div class="wp-block-button"', '<div style="' .$inline_div_button_container_style. '" class="wp-block-button"', $content );
		$inline_button_style = 'display: inline-block; color: white; background: #EA4F51; padding: 20px 38px; font-size: 18px; line-height: 18px; margin: auto; text-transform: uppercase;';
		$content = str_replace( '<a class="wp-block-button__link', '<a style="' .$inline_button_style. '" class="wp-block-button__link', $content );

		// Gestion CSS
		$crowdfunding->include_control('notifications/notifications-api-css');
		$css = NotificationsAPICSS::get();
		$content_html = '<html><head>' . $css . '</head><body><div class="wdg-email">' . $content . '</div></body></html>';

		$options_encoded = $parameters[ 'options' ];
		$options_decoded = json_decode( $options_encoded );
		$options_decoded->object = $object;
		$options_decoded->content = $content_html;
		$parameters[ 'options' ] = json_encode( $options_decoded );

		$result = WDGWPRESTLib::call_post_wdg( 'email', $parameters );
		if ( empty( $result->result ) ) {
			NotificationsAsana::notification_api_failed( $parameters, $result );
		}

		return $result;
	}

	private static function get_id_fr_by_slug($slug) {
		if ( !empty( self::$description_str_by_template_id[ $slug ] ) && !empty( self::$description_str_by_template_id[ $slug ][ 'fr-sib-id' ] ) ) {
			return self::$description_str_by_template_id[ $slug ][ 'fr-sib-id' ];
		}

		return FALSE;
	}

	private static function get_slug_by_id_template_sib_v2($id_template_sib_v2) {
		foreach ( self::$description_str_by_template_id as $template_slug => $template_object ) {
			if ( $template_object[ 'fr-sib-id' ] == $id_template_sib_v2 ) {
				return $template_slug;
			}
		}

		return FALSE;
	}

	public static function get_description_by_template_id($id_template) {
		$template_slug = $id_template;
		if ( is_numeric( $id_template ) ) {
			$template_slug = self::get_slug_by_id_template_sib_v2( $id_template );
		}
		if ( !empty( $template_slug ) ) {
			return self::$description_str_by_template_id[ $template_slug ][ 'description' ];
		}

		return '';
	}

	//**************************************************************************
	// Campagne
	//**************************************************************************
	//*******************************************************
	// NOUVEAU PROJET PUBLIE
	//*******************************************************
	public static function new_project_published($WDGUser, $campaign) {
		ypcf_debug_log( 'NotificationsAPI::new_project_published > ' . $WDGUser->get_email() );
		$id_template = self::get_id_fr_by_slug( 'new-project' );

		NotificationsAPIShortcodes::set_recipient( $WDGUser );
		NotificationsAPIShortcodes::set_campaign( $campaign );

		$project_link_clean = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $WDGUser->get_firstname(),
			'DASHBOARD_URL'			=> $project_link_clean
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'id_project' => $campaign->get_api_id(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// ENVOI ACTUALITE DE PROJET
	//*******************************************************
	public static function new_project_news($recipients, $replyto_mail, $campaign, $news_name, $news_content) {
		ypcf_debug_log( 'NotificationsAPI::new_project_news > ' . $recipients );
		$id_template = self::get_id_fr_by_slug( 'new-project-news' );

		NotificationsAPIShortcodes::set_campaign( $campaign );
		NotificationsAPIShortcodes::set_campaign_news_title( $news_name );
		$news_content_filtered = apply_filters( 'the_excerpt', $news_content );
		NotificationsAPIShortcodes::set_campaign_news_content( $news_content );

		$project_link_clean = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'replyto'				=> $replyto_mail,
			'NOM_PROJET'			=> $campaign->get_name(),
			'LIEN_PROJET'			=> $project_link_clean,
			'OBJET_ACTU'			=> $news_name,
			'CONTENU_ACTU'			=> $news_content_filtered
		);

		// Le maximum de destinataire est de 99, il faut découper
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
						'id_project'	=> $campaign->get_api_id(),
						'options'		=> json_encode( $options )
					);
					self::send( $parameters );
					$recipients = '';
					$index = 0;
				} elseif ( $i < $recipients_array_count - 1 ) {
					$recipients .= ',';
				}
			}
		}

		// On envoie de toute façon au restant des investisseurs à la fin
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $recipients,
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		// TODO : découper par langue les utilisateurs
		return self::send( $parameters );
	}
	//*******************************************************
	// FIN ENVOI ACTUALITE DE PROJET
	//*******************************************************

	//*******************************************************
	// ENVOI ACTUALITE DE PROJET
	//*******************************************************
	public static function project_mail($recipient, $replyto_mail, $WDGUser, $user_name, $campaign, $project_name, $project_link, $project_api_id, $news_name, $news_content) {
		ypcf_debug_log( 'NotificationsAPI::project_mail > ' . $recipient );
		$id_template = self::get_id_fr_by_slug( 'new-mail-contact-list' );

		NotificationsAPIShortcodes::set_recipient( $WDGUser );
		NotificationsAPIShortcodes::set_campaign( $campaign );
		NotificationsAPIShortcodes::set_campaign_news_title( $news_name );
		NotificationsAPIShortcodes::set_campaign_news_content( $news_content );

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

		return self::send( $parameters, $WDGUser->get_language() );
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
	public static function user_registration($WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'subscription' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'skip_admin'			=> 1,
			'PRENOM'				=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// Inscription sans investissement
	//*******************************************************
	public static function user_registered_without_investment($WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'subscription-without-investment' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'NOM'				=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// Inscription sans investissement - pas ouvert
	//*******************************************************
	public static function user_registered_without_investment_not_open($WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'subscription-without-investment-not-open' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'NOM'				=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// Inscription sans investissement - pas cliqué
	//*******************************************************
	public static function user_registered_without_investment_not_clicked($WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'subscription-without-investment-not-clicked' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'NOM'				=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// Inscription sans investissement - pas investi
	//*******************************************************
	public static function user_registered_without_investment_not_invested($WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'subscription-without-investment-not-invested' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'NOM'				=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// Modification du mot de passe
	//*******************************************************
	public static function user_password_change($WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'password-changed' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'NOM'				=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// Réinitialisation de mot de passe
	//*******************************************************
	public static function password_reinit($WDGUser, $link) {
		$id_template = self::get_id_fr_by_slug( 'password-reset' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);
		NotificationsAPIShortcodes::set_password_reinit_link($link);

		$options = array(
			'skip_admin'		=> 1,
			'NOM'				=> $WDGUser->get_firstname(),
			'LIEN'				=> $link
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}
	//**************************************************************************
	// Entrepreneurs
	//**************************************************************************
	//*******************************************************
	// Demande de relecture
	//*******************************************************
	public static function proofreading_request_received($WDGUser, $replyto_mail, $project_api_id) {
		$id_template = self::get_id_fr_by_slug( 'project-pitch-proofreading' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'personal'		=> 1,
			'replyto'		=> $replyto_mail,
			'NOM'			=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUser->get_email(),
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// Conseils quotidiens
	//*******************************************************
	public static function campaign_advice($replyto_mail, $campaign, $WDGUser, $greetings, $last_24h, $top_actions) {
		$id_template = self::get_id_fr_by_slug( 'project-campaign-advice' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);
		NotificationsAPIShortcodes::set_campaign( $campaign );
		$advice_data = array();
		$advice_data[ 'greetings' ] = $greetings;
		$advice_data[ 'content' ] = $last_24h;
		$advice_data[ 'priority_actions' ] = $top_actions;
		NotificationsAPIShortcodes::set_campaign_advice( $advice_data );

		$campaign_dashboard_url = WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign->ID;
		$campaign_dashboard_url_clean = str_replace( 'https://', '', $campaign_dashboard_url );
		$options = array(
			'personal'					=> 1,
			'replyto'					=> $replyto_mail,
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_TB'					=> $campaign_dashboard_url_clean,
			'NOM_UTILISATEUR'			=> $WDGUser->get_firstname(),
			'SALUTATIONS'				=> $greetings,
			'RESUME_24H'				=> $last_24h,
			'ACTIONS_PRIORITAIRES'		=> $top_actions
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//**************************************************************************
	// KYC
	//**************************************************************************
	//*******************************************************
	// NOTIFICATIONS KYC - RIB VALIDE
	//*******************************************************
	public static function rib_authentified($WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'kyc-iban-validated' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'personal'		=> 1,
			'PRENOM'		=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS KYC - EN COURS DE VALIDATION
	//*******************************************************
	public static function kyc_waiting($WDGUserInterface) {
		$id_template = self::get_id_fr_by_slug( 'kyc-doc-waiting' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);

		$options = array(
			'personal'		=> 1,
			'PRENOM'		=> $WDGUserInterface->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS KYC - REFUSES
	//*******************************************************
	public static function kyc_refused($WDGUserInterface, $authentication_info) {
		$id_template = self::get_id_fr_by_slug( 'kyc-doc-refused' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);

		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $WDGUserInterface->get_firstname(),
			'PRECISIONS'			=> $authentication_info
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function phone_kyc_refused($WDGUser) {
		// TODO : traduire les SMS
		$name = $WDGUser->get_firstname();
		$param_content = "Bonjour " .$name. ", des documents ont été refusés sur votre compte WE DO GOOD, qui n'a pas pu être authentifié. Afin d'en savoir plus : www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $WDGUser->get_email()
		);

		return self::send( $parameters );
	}

	//*******************************************************
	// NOTIFICATIONS KYC - UN SEUL DOC VALIDE
	//*******************************************************
	public static function kyc_single_validated($WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'kyc-doc-single-validation' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'personal'				=> 1,
			'PRENOM'				=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUser->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	public static function phone_kyc_single_validated($WDGUser) {
		// TODO : traduire les SMS
		$name = $WDGUser->get_firstname();
		$param_content = "Bonjour " .$name.", un document a été validé sur WE DO GOOD ! Finalisez l'authentification de votre compte en y déposant le(s) document(s) manquant(s) : www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $WDGUser->get_email()
		);

		return self::send( $parameters );
	}

	//*******************************************************
	// NOTIFICATIONS KYC - VALIDES
	//*******************************************************
	public static function kyc_authentified($WDGUserInterface) {
		$id_template = self::get_id_fr_by_slug( 'kyc-authentified' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);

		$options = array(
			'personal'			=> 1,
			'PRENOM'			=> $WDGUserInterface->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function phone_kyc_authentified($WDGUser) {
		// TODO : traduire les SMS
		$name = $WDGUser->get_firstname();
		$param_content = "Bonjour " .$name.", nous avons le plaisir de vous annoncer que votre compte est désormais authentifié sur WE DO GOOD ! www.wedogood.co/mon-compte - [STOP_CODE]";
		$parameters = array(
			'tool'		=> 'sms',
			'template'	=> $param_content,
			'recipient'	=> $WDGUser->get_email()
		);

		return self::send( $parameters );
	}

	//*******************************************************
	// NOTIFICATIONS KYC - VALIDES ET INVESTISSEMENT EN ATTENTE
	//*******************************************************
	public static function kyc_authentified_and_pending_investment($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'kyc-authenticated-pending-investment' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS KYC - VALIDES ET INVESTISSEMENT EN ATTENTE - RAPPEL
	//*******************************************************
	public static function kyc_authentified_and_pending_investment_reminder($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'kyc-authenticated-pending-investment-reminder' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//**************************************************************************
	// Relances
	//**************************************************************************
	//*******************************************************
	// RELANCE - EVALUATION - AVEC INTENTION
	//*******************************************************
	public static function confirm_vote_invest_intention($WDGUserInterface, $intention_amount, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-vote-confirm-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = $intention_amount;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// RELANCE - EVALUATION - SANS INTENTION
	//*******************************************************
	public static function confirm_vote_invest_no_intention($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-vote-without-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// RELANCE - PRE-LANCEMENT - EVALUATION AVEC INTENTION
	//*******************************************************
	public static function confirm_prelaunch_invest_intention($WDGUserInterface, $intention_amount, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-prelaunch-vote-confirm-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = $intention_amount;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_prelaunch_invest_no_intention($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-prelaunch-vote-without-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_prelaunch_invest_follow($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-prelaunch-follow' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// FIN EVALUATION - EN ATTENTE
	//*******************************************************
	public static function vote_end_pending_campaign($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-end-vote-waiting' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'PRENOM'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// FIN EVALUATION - ANNULATION
	//*******************************************************
	public static function vote_end_canceled_campaign($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-end-vote-canceled' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'PRENOM'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function vote_end_canceled_campaign_refund($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-end-vote-canceled-refund' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'PRENOM'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// RELANCE - INVESTISSEMENT - 30%
	//*******************************************************
	public static function confirm_investment_invest30_intention($WDGUserInterface, $intention_amount, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-30percent-with-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = $intention_amount;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'POURCENT'					=> $campaign->percent_minimum_completed( FALSE ),
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_investment_invest30_no_intention($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-30percent-without-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'				=> 1,
			'NOM_UTILISATEUR'		=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'			=> $campaign->get_name(),
			'URL_PROJET'			=> $project_url,
			'POURCENT'				=> $campaign->percent_minimum_completed( FALSE ),
			'TEMOIGNAGES'			=> $testimony,
			'IMAGE'					=> $image_element,
			'DESCRIPTION_PROJET'	=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_investment_invest30_follow($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-30percent-follow' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$image_element = '<img src="' . $image_url . '" width="590">';
		$options = array(
			'personal'				=> 1,
			'NOM_UTILISATEUR'		=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'			=> $campaign->get_name(),
			'URL_PROJET'			=> $project_url,
			'POURCENT'				=> $campaign->percent_minimum_completed( FALSE ),
			'TEMOIGNAGES'			=> $testimony,
			'IMAGE'					=> $image_element,
			'DESCRIPTION_PROJET'	=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// RELANCE - INVESTISSEMENT - 100%
	//*******************************************************
	public static function confirm_investment_invest100_invested($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-with-investment' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$nb_remaining_days = $campaign->days_remaining();
		$date_hour_end = $campaign->end_date( 'd/m/Y h:i' );
		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'NB_JOURS_RESTANTS'			=> $nb_remaining_days,
			'PLURIEL_JOURS_RESTANTS'	=> ( $nb_remaining_days > 1 ) ? 's' : '',
			'DATE_HEURE_FIN'			=> $date_hour_end
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_investment_invest100_investment_pending($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-with-pending-investment' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'TEMOIGNAGES'				=> $testimony,
			'IMAGE'						=> $image_element,
			'DESCRIPTION_PROJET'		=> $image_description
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_investment_invest100_intention($WDGUserInterface, $intention_amount, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-with-with-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = $intention_amount;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$nb_remaining_days = $campaign->days_remaining();
		$date_hour_end = $campaign->end_date( 'd/m/Y h:i' );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $campaign->get_name(),
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
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_investment_invest100_no_intention($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-with-without-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$nb_remaining_days = $campaign->days_remaining();
		$date_hour_end = $campaign->end_date( 'd/m/Y h:i' );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
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
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_investment_invest100_follow($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-success-follow' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$nb_remaining_days = $campaign->days_remaining();
		$date_hour_end = $campaign->end_date( 'd/m/Y h:i' );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
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
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// RELANCE - INVESTISSEMENT - J-2
	//*******************************************************
	public static function confirm_investment_invest2days_intention($WDGUserInterface, $intention_amount, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-2days-with-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = $intention_amount;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$nb_remaining_days = $campaign->days_remaining();
		$date_hour_end = $campaign->end_date( 'd/m/Y h:i' );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'INTENTION_INVESTISSEMENT'	=> $intention_amount,
			'NOM_PROJET'				=> $campaign->get_name(),
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
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function confirm_investment_invest2days_no_intention($WDGUserInterface, $campaign, $testimony, $image_url, $image_description) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-2days-without-intention' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		$reminder_data = array();
		$reminder_data[ 'amount' ] = 0;
		$reminder_data[ 'testimony' ] = $testimony;
		$image_element = '<img src="' . $image_url . '" width="590">';
		$reminder_data[ 'image' ] = $image_element;
		$reminder_data[ 'description' ] = $image_description;
		NotificationsAPIShortcodes::set_reminder_data($reminder_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$nb_remaining_days = $campaign->days_remaining();
		$date_hour_end = $campaign->end_date( 'd/m/Y h:i' );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
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
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//**************************************************************************
	// Evaluation
	//**************************************************************************
	//*******************************************************
	// NOTIFICATIONS EVALUATION - AVEC INTENTION - PAS AUTHENTIFIE
	//*******************************************************
	public static function vote_authentication_needed_reminder($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-vote-intention-authentication' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS EVALUATION - AVEC INTENTION - AUTHENTIFIE
	//*******************************************************
	public static function vote_authenticated_reminder($WDGUserInterface, $campaign, $intention_amount) {
		$id_template = self::get_id_fr_by_slug( 'project-vote-intention-preinvestment' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_reminder_data_amount($intention_amount);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$options = array(
			'personal'					=> 1,
			'NOM_UTILISATEUR'			=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
			'URL_PROJET'				=> $project_url,
			'INTENTION_INVESTISSEMENT'	=> $intention_amount
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//**************************************************************************
	// Investissement
	//**************************************************************************

	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT PAR CHEQUE - EN ATTENTE
	//*******************************************************
	public static function investment_pending_check($WDGUserInterface, $WDGInvestment, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'investment-check-pending' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_investment_pending($WDGInvestment);

		$amount_total = $WDGInvestment->get_session_amount();
		$campaign_organization = $campaign->get_organization();
		$organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
		$percent_to_reach = round( ( $campaign->current_amount( FALSE ) +  $amount_total ) / $campaign->minimum_goal( FALSE ) * 100 );
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $WDGUserInterface->get_firstname(),
			'MONTANT'				=> $amount_total,
			'NOM_PROJET'			=> $campaign->get_name(),
			'POURCENT_ATTEINT'		=> $percent_to_reach,
			'OBJECTIF'				=> $campaign->minimum_goal( FALSE ),
			'NOM_ORGANISATION'		=> $organization_obj->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT PAR VIREMENT - EN ATTENTE
	//*******************************************************
	public static function investment_pending_wire($WDGUserInterface, $WDGInvestment, $campaign, $viban_iban, $viban_bic, $viban_holder) {
		$id_template = self::get_id_fr_by_slug( 'investment-wire-pending' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_investment_pending($WDGInvestment);
		$investment_pending_data = array(
			'viban_iban'	=> $viban_iban,
			'viban_bic'		=> $viban_bic,
			'viban_holder'	=> $viban_holder
		);
		NotificationsAPIShortcodes::set_investment_pending_data($investment_pending_data);

		$amount_total = $WDGInvestment->get_session_amount();
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $WDGUserInterface->get_firstname(),
			'MONTANT'				=> $amount_total,
			'NOM_PROJET'			=> $campaign->get_name(),
			'IBAN'					=> $viban_iban,
			'BIC'					=> $viban_bic,
			'HOLDER'				=> $viban_holder,
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT - VALIDE
	//*******************************************************
	public static function investment_success_project($WDGUserInterface, $WDGInvestment, $campaign, $text_before, $text_after, $attachment_url) {
		$id_template = self::get_id_fr_by_slug( 'investment-project-validated' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_investment($WDGInvestment);
		$investment_data = array(
			'text_before' => $text_before,
			'text_after' => $text_after
		);
		NotificationsAPIShortcodes::set_investment_success_data($investment_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$amount_total = $WDGInvestment->get_session_amount();
		$options = array(
			'personal'				=> 1,
			'NOM_UTILISATEUR'		=> $WDGUserInterface->get_firstname(),
			'MONTANT'				=> $amount_total,
			'NOM_PROJET'			=> $campaign->get_name(),
			'URL_PROJET'			=> $project_url,
			'DATE'					=> $WDGInvestment->get_saved_date_gmt(),
			'TEXTE_AVANT'			=> $text_before,
			'TEXTE_APRES'			=> $text_after,
		);
		if ( !empty( $attachment_url ) && WP_DEBUG != TRUE) {
			$options[ 'url_attachment' ] = $attachment_url;
		}
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function investment_success_positive_savings($WDGUserInterface, $WDGInvestment, $campaign, $text_before, $text_after, $attachment_url) {
		$id_template = self::get_id_fr_by_slug( 'investment-positive-savings-validated' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_investment($WDGInvestment);
		$investment_data = array(
			'text_before' => $text_before,
			'text_after' => $text_after
		);
		NotificationsAPIShortcodes::set_investment_success_data($investment_data);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$amount_total = $WDGInvestment->get_session_amount();
		$options = array(
			'personal'				=> 1,
			'NOM_UTILISATEUR'		=> $WDGUserInterface->get_firstname(),
			'MONTANT'				=> $amount_total,
			'URL_PROJET'			=> $project_url,
			'DATE'					=> $WDGInvestment->get_saved_date_gmt(),
			'TEXTE_AVANT'			=> $text_before,
			'TEXTE_APRES'			=> $text_after,
		);
		if ( !empty( $attachment_url ) && WP_DEBUG != TRUE ) {
			$options[ 'url_attachment' ] = $attachment_url;
		}
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT - ERREUR - POUR UTILISATEUR
	//*******************************************************
	public static function investment_error($WDGUserInterface, $WDGInvestment, $campaign, $lemonway_reason, $investment_link) {
		$id_template = self::get_id_fr_by_slug( 'investment-error' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_investment($WDGInvestment);
		$investment_error_data = array(
			'reason' => $lemonway_reason,
			'link' => $investment_link
		);
		NotificationsAPIShortcodes::set_investment_error_data($investment_error_data);

		$amount_total = $WDGInvestment->get_session_amount();
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $WDGUserInterface->get_firstname(),
			'MONTANT'				=> $amount_total,
			'NOM_PROJET'			=> $campaign->get_name(),
			'RAISON_LEMONWAY'		=> $lemonway_reason,
			'LIEN_INVESTISSEMENT'	=> $investment_link,
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT - ERREUR - POUR UTILISATEUR
	//*******************************************************
	public static function wire_transfer_received($WDGUserInterface, $amount) {
		$id_template = self::get_id_fr_by_slug( 'received-wire-without-pending-investment' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_amount_wire_received($amount);

		$options = array(
			'personal'				=> 1,
			'NOM'					=> $WDGUserInterface->get_firstname(),
			'MONTANT'				=> $amount
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT - DEMANDE AUTHENTIFICATION
	//*******************************************************
	public static function investment_authentication_needed($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-authentication' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS INVESTISSEMENT - DEMANDE AUTHENTIFICATION - RAPPEL
	//*******************************************************
	public static function investment_authentication_needed_reminder($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-investment-authentication-reminder' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//**************************************************************************
	// Fin de campagne
	//**************************************************************************
	//*******************************************************
	// NOTIFICATIONS SUCCES CAMPAGNE PUBLIQUE
	//*******************************************************
	public static function campaign_end_success_public($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-validated-campaign-public' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$project_date_first_payment_month_str = NotificationsAPIShortcodes::project_date_first_payment( FALSE, FALSE );
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name(),
			'MOIS_ANNEE_DEMARRAGE'		=> $project_date_first_payment_month_str
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS SUCCES CAMPAGNE PRIVEE
	//*******************************************************
	public static function campaign_end_success_private($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-validated-campaign-private' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$project_date_first_payment_month_str = NotificationsAPIShortcodes::project_date_first_payment( FALSE, FALSE );
		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name(),
			'MOIS_ANNEE_DEMARRAGE'		=> $project_date_first_payment_month_str
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS EN ATTENTE DU SEUIL DE VALIDATION
	//*******************************************************
	public static function campaign_end_pending_goal($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-pending-validation' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATIONS ECHEC CAMPAGNE
	//*******************************************************
	public static function campaign_end_failure($WDGUserInterface, $campaign) {
		$id_template = self::get_id_fr_by_slug( 'project-failed' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name()
		);
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//**************************************************************************
	// Déclarations
	//**************************************************************************
	//*******************************************************
	// ENVOI MANDAT PRELEVEMENT
	//*******************************************************
	public static function mandate_to_send_to_bank($WDGUserInterface, $attachment_url, $project_api_id) {
		$id_template = self::get_id_fr_by_slug( 'mandate-to-send-to-bank' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);

		$options = array(
			'personal'		=> 1,
			'NOM'			=> $WDGUserInterface->get_firstname(),
		);
		if ( !empty( $attachment_url ) && WP_DEBUG != TRUE) {
			$options[ 'url_attachment' ] = $attachment_url;
		}
		$parameters = array(
			'tool'			=> 'sendinblue',
			'template'		=> $id_template,
			'recipient'		=> $WDGUserInterface->get_email(),
			'id_project'	=> $project_api_id,
			'options'		=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
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
	public static function declaration_to_do($WDGUser, $recipients, $nb_remaining_days, $has_mandate, $options) {
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
			NotificationsAPIShortcodes::set_recipient($WDGUser);
			$param_recipients = is_array( $recipients ) ? implode( ',', $recipients ) : $recipients;
			$parameters = array(
				'tool'		=> 'sendinblue',
				'template'	=> $param_template,
				'recipient'	=> $param_recipients,
				'options'	=> json_encode( $options )
			);

			return self::send( $parameters, $WDGUser->get_language() );
		}

		return FALSE;
	}

	public static function declaration_to_do_warning($recipient, $WDGUser, $campaign, $declaration, $nb_quarter, $percent_estimation, $amount_estimation_year, $amount_estimation_quarter, $percent_royalties, $amount_royalties, $amount_fees, $amount_total) {
		$id_template = self::get_id_fr_by_slug( 'declaration-mandate-payment-warning' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_declaration($declaration);
		$declaration_estimation = array(
			'quarter_count'		=> $nb_quarter,
			'year_amount'		=> $amount_estimation_year,
			'percent'			=> $percent_royalties,
			'quarter_amount'	=> $amount_estimation_quarter,
			'amount_royalties'	=> $amount_royalties,
			'amount_fees'		=> $amount_fees,
			'amount_total'		=> $amount_total
		);
		NotificationsAPIShortcodes::set_declaration_estimation_data($declaration_estimation);
		$mandate_wire_date = NotificationsAPIShortcodes::declaration_mandate_date(FALSE, FALSE);
		$declaration_direct_url = NotificationsAPIShortcodes::declaration_url(FALSE, FALSE);

		$declaration_direct_url = str_replace( 'https://', '', $declaration_direct_url );
		$options = array(
			'personal'							=> 1,
			'NOM_UTILISATEUR'					=> $WDGUser->get_firstname(),
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

		return self::send( $parameters, $WDGUser->get_language() );
	}
	//*******************************************************
	// FIN - NOTIFICATIONS DECLARATIONS ROI A FAIRE
	//*******************************************************

	//*******************************************************
	// NOTIFICATIONS DECLARATIONS APROUVEES
	//*******************************************************
	public static function declaration_done_with_turnover($WDGOrganization, $WDGUser, $campaign, $declaration, $tax_infos, $payment_certificate_url) {
		$id_template = self::get_id_fr_by_slug( 'declaration-done-with-turnover' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_declaration($declaration);

		$last_three_months = $declaration->get_month_list_str();
		$turnover_amount = $declaration->get_amount_with_adjustment();

		$options = array(
			'personal'				=> 1,
			'NOM'					=> $WDGUser->get_firstname(),
			'NOM_PROJET'			=> $campaign->get_name(),
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
			'recipient'	=> $WDGOrganization->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	public static function declaration_done_without_turnover($WDGOrganization, $WDGUser, $campaign, $declaration) {
		$id_template = self::get_id_fr_by_slug( 'declaration-done-without-turnover' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_declaration($declaration);

		$last_three_months = $declaration->get_month_list_str();
		$options = array(
			'personal'				=> 1,
			'NOM'					=> $WDGUser->get_firstname(),
			'NOM_PROJET'			=> $campaign->get_name(),
			'TROIS_DERNIERS_MOIS'	=> $last_three_months
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGOrganization->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}
	//*******************************************************
	// FIN - NOTIFICATIONS DECLARATIONS APROUVEES
	//*******************************************************

	//*******************************************************
	// NOTIFICATIONS PROLONGATION DECLARATIONS
	//*******************************************************
	public static function declaration_to_be_extended($WDGOrganization, $WDGUser, $campaign, $amount_transferred, $amount_minimum_royalties, $amount_remaining) {
		$id_template = self::get_id_fr_by_slug( 'declaration-extended-warning' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);
		NotificationsAPIShortcodes::set_campaign($campaign);

		$options = array(
			'personal'					=> 1,
			'NOM'						=> $WDGUser->get_firstname(),
			'MONTANT_DEJA_VERSE'		=> $amount_transferred,
			'MONTANT_MINIMUM_A_VERSER'	=> $amount_minimum_royalties,
			'MONTANT_RESTANT_A_VERSER'	=> $amount_remaining
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGOrganization->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	public static function declaration_extended_project_manager($WDGOrganization, $WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'declaration-extended-project-manager' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'personal'		=> 1,
			'NOM'			=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGOrganization->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	public static function declaration_extended_investor($WDGUserInterface, $campaign, $investment_contract) {
		$id_template = self::get_id_fr_by_slug( 'declaration-extended-investors' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_investment_contract($investment_contract);

		$funding_duration = $campaign->funding_duration();
		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$date = NotificationsAPIShortcodes::investment_date( FALSE, FALSE );
		$amount_investment = NotificationsAPIShortcodes::investment_amount( FALSE, FALSE );
		$amount_royalties = NotificationsAPIShortcodes::investment_royalties_received( FALSE, FALSE );
		$amount_remaining = NotificationsAPIShortcodes::investment_royalties_remaining( FALSE, FALSE );
		$options = array(
			'personal'					=> 1,
			'NOM'						=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
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
			'recipient'	=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	public static function declaration_finished_project_manager($WDGOrganization, $WDGUser) {
		$id_template = self::get_id_fr_by_slug( 'declaration-end-project-manager' );

		NotificationsAPIShortcodes::set_recipient($WDGUser);

		$options = array(
			'personal'		=> 1,
			'NOM'			=> $WDGUser->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGOrganization->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUser->get_language() );
	}

	public static function declaration_finished_investor($WDGUserInterface, $campaign, $investment_contract) {
		$id_template = self::get_id_fr_by_slug( 'declaration-end-investors' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_investment_contract($investment_contract);

		$project_url = str_replace( 'https://', '', $campaign->get_public_url() );
		$date = $investment_contract->subscription_date;
		$amount_investment = $investment_contract->subscription_amount;
		$amount_royalties = $investment_contract->amount_received;
		$options = array(
			'personal'					=> 1,
			'NOM'						=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'				=> $campaign->get_name(),
			'DATE'						=> $date,
			'URL_PROJET'				=> $project_url,
			'MONTANT_INVESTI'			=> $amount_investment,
			'MONTANT_ROYALTIES'			=> $amount_royalties
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'id_project'	=> $campaign->get_api_id(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
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
	public static function roi_transfer_daily_resume($WDGUserInterface, $royalties_message, $recipient_email) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-daily-resume' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_user_royalties_details($royalties_message);

		$options = array(
			'personal'			=> 1,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'RESUME_ROYALTIES'	=> $royalties_message,
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient_email,
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// WALLET AVEC PLUS DE 200 EUROS
	//*******************************************************
	public static function wallet_with_more_than_200_euros($WDGUserInterface) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-more-200euros' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);

		$options = array(
			'personal'	=> 1,
			'NOM'		=> $WDGUserInterface->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// WALLET AVEC PLUS DE 200 EUROS - RAPPEL MAIL PAS OUVERT
	//*******************************************************
	public static function wallet_with_more_than_200_euros_reminder_not_open($WDGUserInterface) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-more-200euros-reminder-not-open' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);

		$options = array(
			'personal'	=> 1,
			'NOM'		=> $WDGUserInterface->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// WALLET AVEC PLUS DE 200 EUROS - RAPPEL MAIL PAS CLIQUE
	//*******************************************************
	public static function wallet_with_more_than_200_euros_reminder_not_clicked($WDGUserInterface) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-more-200euros-reminder-not-clicked' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);

		$options = array(
			'personal'	=> 1,
			'NOM'		=> $WDGUserInterface->get_firstname()
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// WALLET AVEC PLUS DE 200 EUROS - NOTIF ENTREPRENEUR
	//*******************************************************
	public static function investors_with_wallet_with_more_than_200_euros($WDGUserInterface, $investors_list_str) {
		$id_template = self::get_id_fr_by_slug( 'investors-royalties-more-200euros-project-manager-alert' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_investors_list_with_more_than_200_euros_str($investors_list_str);

		$options = array(
			'personal'		=> 1,
			'PRENOM'		=> $WDGUserInterface->get_firstname(),
			'INVESTISSEURS'	=> $investors_list_str
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// MESSAGE D'ENTREPRENEUR SUITE VERSEMENT ROYALTIES
	//*******************************************************
	public static function roi_transfer_message($WDGUserInterface, $campaign, $declaration_message, $replyto_mail) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-with-message' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_project_royalties_message($declaration_message);

		$options = array(
			'personal'			=> 1,
			'replyto'			=> $replyto_mail,
			'NOM_UTILISATEUR'	=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name(),
			'CONTENU_MESSAGE'	=> $declaration_message,
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATION VERSEMENT AYANT ATTEINT LE MAXIMUM
	//*******************************************************
	public static function roi_transfer_with_max_reached($WDGUserInterface, $campaign, $WDGInvestment, $amount_received) {
		$id_template = self::get_id_fr_by_slug( 'investor-royalties-max-amount-reached' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_campaign($campaign);
		NotificationsAPIShortcodes::set_investment($WDGInvestment);
		NotificationsAPIShortcodes::set_investment_amount_received($amount_received);

		$max_profit = $campaign->maximum_profit_str();
		$url_project = $campaign->get_public_url();
		$date_investment = $WDGInvestment->get_saved_date();
		$amount_investment = NotificationsAPIShortcodes::investment_amount( FALSE, FALSE );
		$amount_investment_str = UIHelpers::format_number( $amount_investment );
		$amount_royalties = NotificationsAPIShortcodes::investment_royalties_received( FALSE, FALSE );
		$amount_royalties_str = UIHelpers::format_number( $amount_royalties );
		$options = array(
			'personal'			=> 1,
			'NOM'				=> $WDGUserInterface->get_firstname(),
			'NOM_PROJET'		=> $campaign->get_name(),
			'RETOUR_MAXIMUM'	=> $max_profit,
			'DATE'				=> $date_investment,
			'URL_PROJET'		=> $url_project,
			'MONTANT_INVESTI'	=> $amount_investment_str,
			'MONTANT_ROYALTIES'	=> $amount_royalties_str
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//*******************************************************
	// NOTIFICATION VERSEMENT SUR COMPTE BANCAIRE
	//*******************************************************
	public static function transfer_to_bank_account_confirmation($WDGUserInterface, $amount) {
		$id_template = self::get_id_fr_by_slug( 'transfer-money-to-bank-account' );

		NotificationsAPIShortcodes::set_recipient($WDGUserInterface);
		NotificationsAPIShortcodes::set_amount_wire_transfer($amount);

		$options = array(
			'personal'			=> 1,
			'NOM'				=> $WDGUserInterface->get_firstname(),
			'MONTANT'			=> $amount
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $WDGUserInterface->get_email(),
			'options'	=> json_encode( $options )
		);

		return self::send( $parameters, $WDGUserInterface->get_language() );
	}

	//**************************************************************************
	// Interface prospect
	//**************************************************************************
	private static function get_prospect_setup_language( $prospect_setup_draft ) {
		$metadata_decoded = json_decode( $prospect_setup_draft->metadata );
		$language = 'fr';
		if ( !empty( $metadata_decoded->language ) ) {
			$language = $metadata_decoded->language;
		}
		return $language;
	}

	//*******************************************************
	// LISTE DES TESTS DEMARRES
	//*******************************************************
	public static function prospect_setup_draft_list($prospect_setup_draft, $project_list_str) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-draft-list' );

		NotificationsAPIShortcodes::set_prospect_setup_draft($prospect_setup_draft);
		NotificationsAPIShortcodes::set_prospect_setup_draft_list($project_list_str);

		$recipient = NotificationsAPIShortcodes::prospect_setup_recipient_email(FALSE, FALSE);
		$name = NotificationsAPIShortcodes::prospect_setup_recipient_first_name(FALSE, FALSE);
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

		$language = self::get_prospect_setup_language( $prospect_setup_draft );
		return self::send( $parameters, $language );
	}

	//*******************************************************
	// DEMARRAGE DE TEST
	//*******************************************************
	public static function prospect_setup_draft_started($prospect_setup_draft) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-draft-started' );

		NotificationsAPIShortcodes::set_prospect_setup_draft($prospect_setup_draft);

		$recipient = NotificationsAPIShortcodes::prospect_setup_recipient_email(FALSE, FALSE);
		$name = NotificationsAPIShortcodes::prospect_setup_recipient_first_name(FALSE, FALSE);
		$organization_name = NotificationsAPIShortcodes::prospect_setup_draft_organization_name(FALSE, FALSE);
		$draft_url_full = NotificationsAPIShortcodes::prospect_setup_draft_url(FALSE, FALSE);
		$draft_url = str_replace( 'https://', '', $draft_url_full );
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

		$language = self::get_prospect_setup_language( $prospect_setup_draft );
		return self::send( $parameters, $language );
	}

	//*******************************************************
	// FIN DE TEST
	//*******************************************************
	public static function prospect_setup_draft_finished($prospect_setup_draft) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-draft-finished' );

		NotificationsAPIShortcodes::set_prospect_setup_draft($prospect_setup_draft);

		$recipient = NotificationsAPIShortcodes::prospect_setup_recipient_email(FALSE, FALSE);
		$name = NotificationsAPIShortcodes::prospect_setup_recipient_first_name(FALSE, FALSE);
		$organization_name = NotificationsAPIShortcodes::prospect_setup_draft_organization_name(FALSE, FALSE);
		$amount_needed = NotificationsAPIShortcodes::prospect_setup_draft_amount_needed(FALSE, FALSE);
		$royalties_percent = NotificationsAPIShortcodes::prospect_setup_draft_royalties_percent(FALSE, FALSE);
		$formula = NotificationsAPIShortcodes::prospect_setup_draft_formula(FALSE, FALSE);
		$options = NotificationsAPIShortcodes::prospect_setup_draft_option(FALSE, FALSE);
		$draft_url_full = NotificationsAPIShortcodes::prospect_setup_draft_url(FALSE, FALSE);
		$draft_url = str_replace( 'https://', '', $draft_url_full );
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

		$language = self::get_prospect_setup_language( $prospect_setup_draft );
		return self::send( $parameters, $language );
	}

	//*******************************************************
	// SELECTION DE VIREMENT
	//*******************************************************
	public static function prospect_setup_payment_method_select_wire($prospect_setup_draft) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-payment-method-select-wire' );

		NotificationsAPIShortcodes::set_prospect_setup_draft($prospect_setup_draft);

		$recipient = NotificationsAPIShortcodes::prospect_setup_recipient_email(FALSE, FALSE);
		$name = NotificationsAPIShortcodes::prospect_setup_recipient_first_name(FALSE, FALSE);
		$subscription_reference = NotificationsAPIShortcodes::prospect_setup_draft_payment_reference(FALSE, FALSE);
		$amount = NotificationsAPIShortcodes::prospect_setup_draft_payment_amount(FALSE, FALSE);
		$iban = NotificationsAPIShortcodes::prospect_setup_draft_payment_iban(FALSE, FALSE);
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'						=> $name,
			'NOM_ENTREPRISE'			=> $subscription_reference,
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

		$language = self::get_prospect_setup_language( $prospect_setup_draft );
		return self::send( $parameters, $language );
	}

	//*******************************************************
	// VIREMENT RECU
	//*******************************************************
	public static function prospect_setup_payment_method_received_wire($prospect_setup_draft) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-payment-method-received-wire' );

		NotificationsAPIShortcodes::set_prospect_setup_draft($prospect_setup_draft);

		$recipient = NotificationsAPIShortcodes::prospect_setup_recipient_email(FALSE, FALSE);
		$name = NotificationsAPIShortcodes::prospect_setup_recipient_first_name(FALSE, FALSE);
		$amount = NotificationsAPIShortcodes::prospect_setup_draft_payment_amount(FALSE, FALSE);
		$organization_name = NotificationsAPIShortcodes::prospect_setup_draft_organization_name(FALSE, FALSE);
		$date_payment = NotificationsAPIShortcodes::prospect_setup_draft_payment_date(FALSE, FALSE);
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'					=> $name,
			'NOM_ENTREPRISE'		=> $organization_name,
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

		$language = self::get_prospect_setup_language( $prospect_setup_draft );
		return self::send( $parameters, $language );
	}

	//*******************************************************
	// PAIEMENT PAR CARTE RECU
	//*******************************************************
	public static function prospect_setup_payment_method_received_card($prospect_setup_draft) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-payment-method-received-card' );

		NotificationsAPIShortcodes::set_prospect_setup_draft($prospect_setup_draft);

		$recipient = NotificationsAPIShortcodes::prospect_setup_recipient_email(FALSE, FALSE);
		$name = NotificationsAPIShortcodes::prospect_setup_recipient_first_name(FALSE, FALSE);
		$amount = NotificationsAPIShortcodes::prospect_setup_draft_payment_amount(FALSE, FALSE);
		$organization_name = NotificationsAPIShortcodes::prospect_setup_draft_organization_name(FALSE, FALSE);
		$date_payment = NotificationsAPIShortcodes::prospect_setup_draft_payment_date(FALSE, FALSE);
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'					=> $name,
			'NOM_ENTREPRISE'		=> $organization_name,
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

		$language = self::get_prospect_setup_language( $prospect_setup_draft );
		return self::send( $parameters, $language );
	}

	//*******************************************************
	// PAIEMENT PAR CARTE ERREUR
	//*******************************************************
	public static function prospect_setup_payment_method_error_card($prospect_setup_draft) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-payment-method-error-card' );

		NotificationsAPIShortcodes::set_prospect_setup_draft($prospect_setup_draft);

		$recipient = NotificationsAPIShortcodes::prospect_setup_recipient_email(FALSE, FALSE);
		$name = NotificationsAPIShortcodes::prospect_setup_recipient_first_name(FALSE, FALSE);
		$organization_name = NotificationsAPIShortcodes::prospect_setup_draft_organization_name(FALSE, FALSE);
		$draft_url = NotificationsAPIShortcodes::prospect_setup_draft_url(FALSE, FALSE);
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'			=> $name,
			'NOM_ENTREPRISE'	=> $organization_name,
			'URL_DRAFT'		=> $draft_url,
			'personal'		=> 1
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);

		$language = self::get_prospect_setup_language( $prospect_setup_draft );
		return self::send( $parameters, $language );
	}

	//*******************************************************
	// TABLEAU DE BORD PAS ENCORE CREE
	//*******************************************************
	public static function prospect_setup_dashboard_not_created($prospect_setup_draft) {
		$id_template = self::get_id_fr_by_slug( 'prospect-setup-dashboard-not-created' );

		NotificationsAPIShortcodes::set_prospect_setup_draft($prospect_setup_draft);

		$recipient = NotificationsAPIShortcodes::prospect_setup_recipient_email(FALSE, FALSE);
		$name = NotificationsAPIShortcodes::prospect_setup_recipient_first_name(FALSE, FALSE);
		$organization_name = NotificationsAPIShortcodes::prospect_setup_draft_organization_name(FALSE, FALSE);
		$options = array(
			'replyto'		=> 'projets@wedogood.co',
			'NOM'			=> $name,
			'NOM_PROJET'	=> $organization_name,
			'personal'		=> 1
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient . ',projets@wedogood.co',
			'options'	=> json_encode( $options )
		);

		$language = self::get_prospect_setup_language( $prospect_setup_draft );
		return self::send( $parameters, $language );
	}
}
