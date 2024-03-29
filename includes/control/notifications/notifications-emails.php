<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class NotificationsEmails {
	/**
	 * Fonction générale d'envoi de mail
	 * @param string $to
	 * @param string $object
	 * @param string $content
	 * @param bool $decorate Inclure ou non le header et footer définis dans le back-office (projets-> réglages-> e-mails)
	 * @param array $attachments
	 * @param array $from_data
	 * @return bool
	 */
	public static function send_mail($to, $object, $content, $decorate = false, $attachments = array(), $from_data = array(), $bcc = array()) {
		// On note les notifications provenant de nos tests en local
		if ( $_SERVER['SERVER_NAME'] != 'www.wedogood.co' ) {
			$object = 'TEST -- ' . $object;
		}
		ypcf_debug_log('NotificationsEmails::send_mail > ' . $to . ' > ' . $object);
		if ( empty( $from_data ) ) {
			$from_name = get_bloginfo('name');
			$from_email = get_option('admin_email');
		} else {
			$from_name = $from_data['name'];
			$from_email = $from_data['email'];
		}
		$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$headers .= "Reply-To: ". $from_email . "\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		if ( !empty($bcc) ) {
			$bcc_list = '';
			foreach ($bcc as $bcc_mail) {
				if ( !empty($bcc_mail) ) {
					$bcc_list .= $bcc_mail . ',';
				}
			}
			$bcc_list = substr( $bcc_list, 0, -1 );
			$headers .= "Bcc: ".$bcc_list.";\r\n";
			ypcf_debug_log('NotificationsEmails::send_mail > Bcc list : ' . $bcc_list);
		}

		ypcf_debug_log('NotificationsEmails::send_mail > ' . $content);
		if ($decorate) {
			global $edd_options;
			$content = wpautop( $edd_options['header_global_mail'] ) .'<br /><br />'. $content .'<br /><br />'. wpautop( $edd_options['footer_global_mail'] );
		}

		// Log des erreurs de mails
		add_action( 'wp_mail_failed', 'NotificationsEmails::log_mail_error', 10, 1 );

		$buffer = wp_mail( $to, $object, $content, $headers, $attachments );
		ypcf_debug_log('NotificationsEmails::send_mail > ' . $to . ' | ' . $object . ' >> ' . $buffer);

		return $buffer;
	}

	/**
	 * Log des erreurs de mails
	 */
	public static function log_mail_error($wp_error) {
		ypcf_debug_log( 'NotificationsEmails::log_mail_error > wp_error : ' . print_r( $wp_error, TRUE ), FALSE );
	}

	//*******************************************************
	// RELECTURE
	//*******************************************************
	public static function send_project_description_notification_to_project($id_campaign) {
		$campaign = new ATCF_Campaign( $id_campaign );

		$campaign_author = $campaign->post_author();
		$author_user = get_user_by( 'ID', $campaign_author );
		$recipient = $author_user->user_email;
		$campaign_organization = $campaign->get_organization();
		$WDGOrganizationCampaign = new WDGOrganization( $campaign_organization->wpref );
		$recipient .= ',' . $WDGOrganizationCampaign->get_email();

		$object = $campaign->get_name() . ' /// Relecture de votre présentation';
		$content = "Bonjour,<br>";
		$content .= "WE DO GOOD a bien relu la présentation de votre projet et a mis quelques annotations sur votre page projet.<br>";
		$content .= "Vous pouvez vous rendre sur la page de présentation de votre projet pour apporter les corrections demandées : " . $campaign->get_public_url() . "<br>";
		$content .= "Bon courage, et bonne journée !<br>";
		$content .= "L'équipe WE DO GOOD";

		return NotificationsEmails::send_mail( $recipient, $object, $content, TRUE );
	}
	//*******************************************************
	// FIN RELECTURE
	//*******************************************************

	//*******************************************************
	// ACHATS
	//*******************************************************
	/**
	 * Mise à jour de la langue en cours en fonction de l'identifiant de paiement
	 */
	public static function set_current_locale_id_by_payment_id( $payment_id ) {
		$WDGUser = WDGUser::get_by_payment_id( $payment_id );
		if ( !empty( $WDGUser ) ) {
			WDG_Languages_Helpers::set_current_locale_id( $WDGUser->get_language() );
		}
	}

	/**
	 * Mail pour l'investisseur lors d'un investissement réussi avec problème de création de signature électronique
	 * @param int $payment_id
	 * @return bool
	 */
	public static function new_purchase_user_error_contract($payment_id, $preinvestment = FALSE, $is_only_wallet_contribution = FALSE) {
		self::set_current_locale_id_by_payment_id( $payment_id );
		ypcf_debug_log('NotificationsEmails::new_purchase_user_error_contract > ' . $payment_id);
		$particular_content = '<span style="color: red;">' . __( 'invest.email.THERE_WAS_A_PROBLEM_WITH_CONTRACT_GENERATION', 'yproject' ) . '</span>';

		return NotificationsEmails::new_purchase_user( $payment_id, $particular_content, $preinvestment, $is_only_wallet_contribution );
	}

	/**
	 * Mail pour l'investisseur lors d'un investissement réussi avec signature électronique (pas de pièce jointe)
	 * @param int $payment_id
	 * @return bool
	 */
	public static function new_purchase_user_success($payment_id, $is_card_contribution = TRUE, $preinvestment = FALSE, $is_only_wallet_contribution = FALSE) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user_success > ' . $payment_id);

		self::set_current_locale_id_by_payment_id( $payment_id );

		$particular_content = "";
		if ( $is_card_contribution && !$is_only_wallet_contribution ) {
			$particular_content .= __( 'invest.email.BANK_ACCOUNT_SUMMARY_LEMONWAY', 'yproject' );
		}

		$particular_content .= __( 'invest.email.YOU_NEED_TO_SIGN_THE_CONTRACT', 'yproject' );
		$particular_content .= ' ';
		$particular_content .= '(<strong>' .__( 'invest.email.CHECK_SPAM', 'yproject' ). '</strong>).<br />';
		$attachments = FALSE;

		return NotificationsEmails::new_purchase_user( $payment_id, $particular_content, $attachments, $preinvestment, $is_only_wallet_contribution );
	}

	/**
	 * Mail pour l'investisseur lors d'un investissement réussi sans signature électronique nécessaire
	 * @param type $payment_id
	 * @return type
	 */
	public static function new_purchase_user_success_nocontract($payment_id, $new_contract_pdf_file, $is_card_contribution = TRUE, $preinvestment = FALSE, $is_only_wallet_contribution = FALSE) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user_success_nocontract > ' . $payment_id);

		$particular_content = "";
		if ( $is_card_contribution && !$is_only_wallet_contribution) {
			self::set_current_locale_id_by_payment_id( $payment_id );
			$particular_content .= __( 'invest.email.BANK_ACCOUNT_SUMMARY_LEMONWAY', 'yproject' );
		}

		$attachments = array($new_contract_pdf_file);

		return NotificationsEmails::new_purchase_user( $payment_id, $particular_content, $attachments, $preinvestment, $is_only_wallet_contribution );
	}

	public static function new_purchase_user_success_check($payment_id) {
		return NotificationsEmails::new_purchase_user( $payment_id, '' );
	}

	/**
	 * Mail pour l'investisseur lors d'un achat
	 * @param int $payment_id
	 * @param string $particular_content
	 * @return bool
	 */
	public static function new_purchase_user($payment_id, $particular_content, $attachments = array(), $preinvestment = FALSE, $is_only_wallet_contribution = FALSE) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user > ' . $payment_id);
		$WDGInvestment = new WDGInvestment($payment_id);
		$campaign = $WDGInvestment->get_saved_campaign();

		$email = $WDGInvestment->get_saved_user_email();
		$user_data = get_user_by('email', $email);
		$WDGUser = new WDGUser($user_data->ID);
		$payment_key = $WDGInvestment->get_payment_key();

		$attachment_url = '';
		$text_before = '';
		$text_after = '';

		self::set_current_locale_id_by_payment_id( $payment_id );
		if ( $payment_key != 'check' && !$is_only_wallet_contribution ) {
			if ( strpos( $payment_key, 'TRANSID' ) !== FALSE ) {
				$text_before .= __( 'invest.email.ACCOUNT_REGISTERED_DEBIT', 'yproject' ) . '<br>';
			} else {
				$text_before .= __( 'invest.email.ACCOUNT_DEBIT', 'yproject' ) . '<br>';
			}
		}

		if ( !$campaign->is_funded() ) {
			$text_before .= __( 'invest.email.INVESTMENT_VALIDATED_WHEN_GOAL_REACHED', 'yproject' ) . '<br>';
		}

		if ( !empty( $particular_content ) ) {
			$text_before .= "<br>" .$particular_content. "<br>";
		}

		if ( !empty( $preinvestment ) ) {
			$text_before .= '<br>' . __( 'invest.email.PREINVESTMENT_CONDITIONS_1', 'yproject' ) . '<br>'
						. __( 'invest.email.PREINVESTMENT_CONDITIONS_2', 'yproject' ) . '<br>'
						. __( 'invest.email.PREINVESTMENT_CONDITIONS_3', 'yproject' ) . '<br>';
		}

		if ( !empty( $attachments ) ) {
			$attachment_url = WDGInvestmentContract::get_investment_file_url( $campaign, $payment_id );
			$text_after = __( 'invest.email.WHERE_TO_FIND_CONTRACT', 'yproject' ) . " <a href=\"". home_url( '/mon-compte/' ) ."\">" . __( 'invest.email.PERSONAL_ACCOUNT', 'yproject' ) . "</a>.<br><br>";
		}

		if ( $campaign->is_positive_savings() ) {
			NotificationsAPI::investment_success_positive_savings( $WDGUser, $WDGInvestment, $campaign, $text_before, $text_after, $attachment_url );
		} else {
			NotificationsAPI::investment_success_project( $WDGUser, $WDGInvestment, $campaign, $text_before, $text_after, $attachment_url );
		}
	}

	/**
	 * Mail pour l'équipe projet lors d'un achat
	 * @param int $payment_id
	 * @return bool
	 */
	public static function new_purchase_team_members($payment_id) {
		ypcf_debug_log('NotificationsEmails::new_purchase_members > ' . $payment_id);
		$inv = new WDGInvestment( $payment_id );
		$campaign = $inv->get_saved_campaign();

		$author_data = get_userdata( $campaign->post_author() );
		$emails = $author_data->user_email;
		$emails .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );

		$object = "Nouvel investissement";

		$payment_amount = $inv->get_saved_amount();
		$email = $inv->get_saved_user_email();
		$user_data = get_user_by( 'email', $email );

		if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
			$body_content = "Une nouvelle personne a pré-investi sur votre projet ".$campaign->get_name().":<br />";
		} else {
			$body_content = "Une nouvelle personne a investi sur votre projet ".$campaign->get_name().":<br />";
		}

		$body_content .= $user_data->user_firstname . " " . $user_data->user_lastname . " a investi ".$payment_amount." &euro;";
		$body_content .= ".<br />";

		if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
			$body_content .= "Bravo, continuez à inciter au pré-investissement (notamment auprès de ceux qui ont déjà voté), afin que votre levée de fonds démarre avec une belle dynamique déjà en place !";
		} else {
			$campaign->reload_cache();
			$body_content .= "Votre projet a atteint ".$campaign->percent_minimum_completed()." de son objectif, soit ".$campaign->current_amount()." sur ".$campaign->minimum_goal(true).".";
		}

		return NotificationsEmails::send_mail($emails, $object, $body_content, true);
	}

	public static function investment_draft_validated_new_user($user_email, $user_firstname, $user_password, $campaign_name) {
		$object = "Création d'un compte sur WE DO GOOD";

		$body_content = "Bonjour " .$user_firstname. ",<br><br>";
		$body_content .= "Suite à votre investissement par chèque sur le projet ".$campaign_name.", un compte a été créé pour vous sur WE DO GOOD.<br><br>";
		$body_content .= "Vous pouvez y accéder avec les informations suivantes :.<br>";
		$body_content .= "- votre e-mail : " .$user_email. "<br>";
		$body_content .= "- votre mot de passe généré automatiquement : " .$user_password. "<br><br>";
		$body_content .= "Nous vous invitons fortement à vous connecter au plus vite sur <a href=\"".home_url( '/mon-compte/' )."\">votre compte</a> pour y modifier votre mot de passe.<br>";
		$body_content .= "Vous pourrez aussi en profiter pour vous authentifier auprès de notre prestataire de paiement, ce qui vous permettra de récupérer les royalties versées par le projet à l'avenir.<br>";

		$body_content .= "Merci encore pour votre investissement et à bientôt sur WE DO GOOD !<br>";

		return NotificationsEmails::send_mail( $user_email, $object, $body_content, true );
	}
	//*******************************************************
	// FIN ACHATS
	//*******************************************************

	//*******************************************************
	// NOUVEAU PROJET
	//*******************************************************
	public static function new_project_posted_error_admin($project_name, $error_content) {
		$to = 'admin@wedogood.co';
		$object = 'Erreur lors de la création du projet ' . $project_name;
		$body_content = 'Bonjour,<br>';
		$body_content .= 'Il y a eu une erreur lors de la création du projet ' .$project_name. ' :<br>';
		$body_content .= $error_content;

		return NotificationsEmails::send_mail( $to, $object, $body_content );
	}
	//*******************************************************
	// FIN NOUVEAU PROJET
	//*******************************************************

	//*******************************************************
	// NOUVEAU COMMENTAIRE
	//*******************************************************
	/**
	 * Mail lors de la publication d'un nouveau commentaire
	 * @param int $comment_id
	 * @param WP_Comment_Query $comment_object
	 * @return bool
	 */
	public static function new_comment($comment_id, $comment_object) {
		ypcf_debug_log('NotificationsEmails::new_comment > ' . $comment_id);
		$object = 'Nouveau commentaire !';

		get_comment( $comment_id );
		$post_categories = get_the_category( $comment_object->comment_post_ID );
		if ( count($post_categories) > 0 && ( $post_categories[0]->slug == 'wedogood' || $post_categories[0]->slug == 'revue-de-presse' ) ) {
			return FALSE;
		}

		$campaign = new ATCF_Campaign( $comment_object->comment_post_ID );

		$body_content = "Vous avez reçu un nouveau commentaire sur votre projet ".$campaign->data->post_title." :<br />";
		$body_content .= $comment_object->comment_content . "<br /><br />";
		$body_content .= 'Pour y répondre, suivez ce lien : <a href="'.get_permalink( $comment_object->comment_post_ID ).'">'.$campaign->data->post_title.'</a>.';

		$user = get_userdata( $campaign->data->post_author );
		$organization = $campaign->get_organization();
		$wdgorganization = new WDGOrganization( $organization->id, $organization );
		$emails = $user->user_email . ',' . $wdgorganization->get_email();
		$emails .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );

		return NotificationsEmails::send_mail($emails, $object, $body_content, true);
	}
	//*******************************************************
	// FIN NOUVEAU COMMENTAIRE
	//*******************************************************

	//*******************************************************
	// NOTIFICATIONS PAIEMENTS ROI
	//*******************************************************
	public static function turnover_declaration_adjustment_file_sent($declaration_id) {
		ypcf_debug_log('NotificationsEmails::turnover_declaration_adjustment_file_sent > ' . $declaration_id);
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign( FALSE, $declaration->id_campaign );

		$admin_email = 'administratif@wedogood.co';
		$object = "Projet " . $campaign->data->post_title . " - Envoi de fichier d'ajustement";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le projet " .$campaign->data->post_title. " a envoyé un document d'ajustement pour une déclaration de royalties à venir.<br>";

		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}

	public static function send_notification_roi_payment_success_user($declaration_id) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_success_user > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );
		$author = get_user_by( 'id', $campaign->data->post_author );

		$object = "Paiement de votre reversement effectué";
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Le paiement de votre versement de ".$roi_declaration->get_amount_with_commission()." € a bien été pris en compte.<br />";
		$body_content .= "Merci et à bientôt sur ".ATCF_CrowdFunding::get_platform_name()." !<br /><br />";

		return NotificationsEmails::send_mail($author->user_email, $object, $body_content, true);
	}

	//*******************************************************
	// FIN NOTIFICATIONS PAIEMENTS ROI
	//*******************************************************

	//*******************************************************
	// SUPPRESSION COMPTE UTILISATEUR
	//*******************************************************
	public static function send_wedogood_delete_order($user_email) {
		ypcf_debug_log('NotificationsEmails::send_wedogood_delete_order > ' . $user_email);

		$admin_email = 'investir@wedogood.co';
		$object = "Utilisateur supprimé";

		$body_content = "Salut,<br>";
		$body_content .= "le compte utilisateur suivant a été supprimé, il faut maintenant faire la demande de suppression à LemonWay<br>";
		$body_content .= "Son adresse e-mail est la suivante : " .$user_email. "<br>";
		$body_content .= "Merci de prévenir l'utilisateur";

		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, TRUE );
	}
	//*******************************************************
    // FIN SUPPRESSION COMPTE UTILISATEUR
	//*******************************************************
}
