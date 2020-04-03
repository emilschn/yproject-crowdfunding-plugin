<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

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

		$buffer = wp_mail( $to, $object, $content, $headers, $attachments );
		ypcf_debug_log('NotificationsEmails::send_mail > ' . $to . ' | ' . $object . ' >> ' . $buffer);
		return $buffer;
    }
    
    //*******************************************************
    // RELECTURE
	//*******************************************************
	public static function send_project_description_notification_to_project( $id_campaign ) {
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

	public static function send_project_description_notification_to_wdg( $id_campaign ) {
		$campaign = new ATCF_Campaign( $id_campaign );
		$recipient = 'support@wedogood.co';
		$object = $campaign->get_name() . ' /// Présentation à relire !';
		$content = "Le porteur de projet a cliqué sur le bouton de relecture<br>";
		$content .= "URL du projet : " . $campaign->get_public_url();
		return NotificationsEmails::send_mail( $recipient, $object, $content );
	}
    //*******************************************************
    // FIN RELECTURE
	//*******************************************************
	
    
    //*******************************************************
    // ACHATS
    //*******************************************************
    /**
     * Mail pour l'investisseur lors d'un achat avec erreur de création de contrat
     * @param int $payment_id
     * @return bool
     */
    public static function new_purchase_user_error_contract( $payment_id, $preinvestment = FALSE, $is_only_wallet_contribution = FALSE ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user_error_contract > ' . $payment_id);
		$particular_content = "<span style=\"color: red;\">Il y a eu un problème durant la génération du contrat. Notre équipe en a été informée.</span>";
		return NotificationsEmails::new_purchase_user( $payment_id, $particular_content, $preinvestment, $is_only_wallet_contribution );
    }
    
	private static $alert_lemonway_card = "Sur votre relevé de compte bancaire, vous verrez apparaître le libellé «Lemon Way », le nom de notre prestataire de paiement, dans le détail des opérations.<br>";
    /**
     * Mail pour l'investisseur lors d'un achat avec création de contrat réussie
     * @param int $payment_id
     * @return bool
     */
    public static function new_purchase_user_success( $payment_id, $is_card_contribution = TRUE, $preinvestment = FALSE, $is_only_wallet_contribution = FALSE ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user_success > ' . $payment_id);

		$particular_content = "";
		if ( $is_card_contribution && !$is_only_wallet_contribution ) {
			$particular_content .= self::$alert_lemonway_card;
		}

		$particular_content .= "Il vous reste encore à signer le contrat que vous devriez recevoir de la part de notre partenaire Eversign ";
		$particular_content .= "(<strong>Pensez à vérifier votre courrier indésirable</strong>).<br />";
		$attachments = FALSE;
		return NotificationsEmails::new_purchase_user( $payment_id, $particular_content, $attachments, $preinvestment, $is_only_wallet_contribution );
    }
    
    /**
     * Mail pour l'investisseur lors d'un achat sans nécessité de signer le contrat
     * @param type $payment_id
     * @return type
     */
    public static function new_purchase_user_success_nocontract( $payment_id, $new_contract_pdf_file, $is_card_contribution = TRUE, $preinvestment = FALSE, $is_only_wallet_contribution = FALSE ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user_success_nocontract > ' . $payment_id);
		
		$particular_content = "";
		if ( $is_card_contribution && !$is_only_wallet_contribution) {
			$particular_content .= self::$alert_lemonway_card;
		}
		
		$attachments = array($new_contract_pdf_file);
		return NotificationsEmails::new_purchase_user( $payment_id, $particular_content, $attachments, $preinvestment, $is_only_wallet_contribution );
    }
	
	public static function new_purchase_user_success_check( $payment_id ) {
		return NotificationsEmails::new_purchase_user( $payment_id, '' );
	}
    
    /**
     * Mail pour l'investisseur lors d'un achat
     * @param int $payment_id
     * @param string $particular_content
     * @return bool
     */
    public static function new_purchase_user( $payment_id, $particular_content, $attachments = array(), $preinvestment = FALSE, $is_only_wallet_contribution = FALSE ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user > ' . $payment_id);
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);

		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$user_info = maybe_unserialize( $payment_data['user_info'] );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);
		$payment_key = edd_get_payment_key( $payment_id );

		$attachment_url = '';
		$text_before = '';
		$text_after = '';
		
		if ( $payment_key != 'check' && !$is_only_wallet_contribution ) {
			if ( strpos( $payment_key, 'TRANSID' ) !== FALSE ) {
				$text_before .= 'Le compte bancaire de votre carte enregistrée a été débité.<br>';
			} else {
				$text_before .= 'Votre compte a été débité.<br>';
			}
		}

		if ( !$campaign->is_funded() ){
			$text_before .= "L'investissement ne sera définitivement validé que si le projet atteint son seuil minimal de financement.<br>";
		}
		
		if ( !empty( $particular_content ) ) {
			$text_before .= "<br>" .$particular_content. "<br>";
		}
		
		if ( !empty( $preinvestment ) ) {
			$text_before .= "<br>Nous vous rappelons que les conditions que vous avez accept&eacute;es sont "
						. "susceptibles d'&ecirc;tre modifi&eacutes;es &agrave; l'issue de la phase d'&eacute;valuation.<br>"
						. "Si aucun changement ne survient, votre investissement sera valid&eacute; automatiquement.<br>"
						. "Si un changement devait survenir, vous devrez confirmer ou infirmer votre investissement.<br>";
		}
		
		if ( !empty( $attachments ) ) {
			$attachment_url_filename = basename( $attachments[ 0 ] );
			$attachment_url = home_url( '/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/' . $attachment_url_filename );
			$text_after = "Vous trouverez votre contrat d'investissement en pi&egrave;ce jointe et pouvez suivre vos versements de royalties en vous connectant sur votre <a href=\"". home_url( '/mon-compte/' ) ."\">compte personnel</a>.<br><br>";
		}
		
		if ( $campaign->is_positive_savings() ) {
			NotificationsAPI::investment_success_positive_savings( $email, $user_data->first_name, $payment_amount, $campaign->get_public_url(), get_post_field( 'post_date', $payment_id ), $text_before, $text_after, $attachment_url, $campaign->get_api_id() );
			
		} else {
			NotificationsAPI::investment_success_project( $email, $user_data->first_name, $payment_amount, $post_campaign->post_title, $campaign->get_public_url(), get_post_field( 'post_date', $payment_id ), $text_before, $text_after, $attachment_url, $campaign->get_api_id() );
		}
		
    }
    
    /**
     * Mail pour l'équipe projet lors d'un achat
     * @param int $payment_id
     * @return bool
     */
    public static function new_purchase_team_members($payment_id) {
		ypcf_debug_log('NotificationsEmails::new_purchase_members > ' . $payment_id);
		$post_campaign = atcf_get_campaign_post_by_payment_id( $payment_id );
		$campaign = atcf_get_campaign( $post_campaign );

		$author_data = get_userdata( $post_campaign->post_author );
		$emails = $author_data->user_email;
		$emails .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );

		$object = "Nouvel investissement";

		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data[ 'email' ];
		$user_data = get_user_by( 'email', $email );

		if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
			$body_content = "Une nouvelle personne a pré-investi sur votre projet ".$post_campaign->post_title.":<br />";
		} else {
			$body_content = "Une nouvelle personne a investi sur votre projet ".$post_campaign->post_title.":<br />";
		}
		
		$body_content .= $user_data->user_firstname . " " . $user_data->user_lastname . " a investi ".$payment_amount." &euro;";
		$body_content .= ".<br />";

		if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
			$body_content .= "Bravo, continuez à inciter au pré-investissement (notamment auprès de ceux qui ont déjà voté), afin que votre levée de fonds démarre avec une belle dynamique déjà en place !";
		} else {
			$body_content .= "Votre projet a atteint ".$campaign->percent_minimum_completed()." de son objectif, soit ".$campaign->current_amount()." sur ".$campaign->minimum_goal(true).".";
		}

		return NotificationsEmails::send_mail($emails, $object, $body_content, true);
    }
    
    /**
     * Mail à l'admin lors d'un achat avec erreur de génération de contrat
     * @param int $payment_id
     * @return bool
     */
    public static function new_purchase_admin_error_contract($payment_id) {
	ypcf_debug_log('NotificationsEmails::new_purchase_admin_error_contract > ' . $payment_id);
	$object = ' - Problème de création de contrat';
	$body_content = "<span style=\"color: red;\">Il y a eu un problème durant la génération du contrat. Id du paiement : ".$payment_id."</span>";
	return NotificationsEmails::new_purchase_admin_success($payment_id, $object, $body_content);
    }
    
    /**
     * Mail à l'admin lors d'un achat sans nécessité de signer un contrat
     * @param int $payment_id
     * @return bool
     */
    public static function new_purchase_admin_success_nocontract($payment_id, $new_contract_pdf_file) {
	$attachments = array($new_contract_pdf_file);
	return NotificationsEmails::new_purchase_admin_success($payment_id, '', '', $attachments);
    }
    
    /**
     * Mail à l'admin lors d'un achat réussi
     * @param int $payment_id
     * @param string $complement_object
     * @param string $complement_content
     * @return bool
     */
    public static function new_purchase_admin_success($payment_id, $complement_object = '', $complement_content = '', $attachments = array()) {
		ypcf_debug_log('NotificationsEmails::new_purchase_admin_success > ' . $payment_id);
		$admin_email = 'admin@wedogood.co';
		$object = 'Nouvel achat' . $complement_object;

		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		$payment_amount = edd_get_payment_amount( $payment_id );
		$user_id = edd_get_payment_user_id( $payment_id );
		$user_data = get_userdata($user_id);
		$payment_date = get_post_field( 'post_date', $payment_id );

		$body_content = 'Nouvel investissement avec l\'identifiant de paiement ' . $payment_id . '<br /><br />';
		$body_content .= "<strong>Détails de l'investissement</strong><br />";
		$body_content .= "Utilisateur : " . $user_data->user_login . "<br />";
		$body_content .= "Projet : " . $post_campaign->post_title . "<br />";
		$body_content .= "Montant investi : ".$payment_amount."&euro;<br />";
		if ($campaign->funding_type()=="fundingdonation"){
			$reward = get_post_meta( $payment_id, '_edd_payment_reward', true);
			$body_content .= " Contrepartie choisie : Palier de ".$reward['amount']."&euro; - ".$reward['name']."<br/>";
		}
		$body_content .= "Horodatage : ". $payment_date ."<br /><br />";
		$body_content .= $complement_content;

		return NotificationsEmails::send_mail($admin_email, $object, $body_content, false, $attachments);
    }
	
    public static function new_purchase_admin_error_wallet( $user_data, $project_title, $amount ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_admin_error_wallet > ' . $user_data->user_email);
		$admin_email = 'admin@wedogood.co';
		$object = 'Erreur transfert wallet';
		$body_content = "Salut !<br />";
		$body_content .= "Il y a un souci pour un transfert de wallet :<br />";
		$body_content .= "Login : " .$user_data->user_login. "<br />";
		$body_content .= "e-mail : " .$user_data->user_email. "<br />";
		$body_content .= "Projet : " .$project_title. "<br />";
		$body_content .= "Montant total : " .$amount. "<br />";
		return NotificationsEmails::send_mail( $admin_email, $object, $body_content );
	}
	
    public static function new_purchase_admin_error_card_wallet( $user_data, $project_title, $amount, $amount_wallet ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_admin_error_card_wallet > ' . $user_data->user_email);
		$admin_email = 'admin@wedogood.co';
		$object = 'Erreur transfert wallet après carte';
		$body_content = "Salut !<br />";
		$body_content .= "Il y a un souci pour un transfert de wallet en complément d'un paiement par carte :<br />";
		$body_content .= "Login : " .$user_data->user_login. "<br />";
		$body_content .= "e-mail : " .$user_data->user_email. "<br />";
		$body_content .= "Projet : " .$project_title. "<br />";
		$body_content .= "Montant total : " .$amount. "<br />";
		$body_content .= "dont montant wallet : " .$amount_wallet. "<br />";
		return NotificationsEmails::send_mail( $admin_email, $object, $body_content );
	}
	
    public static function new_purchase_pending_admin_error( $user_data, $lw_msg, $invest_id, $amount ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_pending_admin_error > ');
		$admin_email = 'admin@wedogood.co';
		$object = 'Erreur paiement par carte en attente';
		$body_content = "Tentative d'investissement avec erreur :<br />";
		$body_content .= "user_data : " .print_r( $user_data, true ). "<br />";
		$body_content .= "ID Invest : " .$invest_id. "<br />";
		$body_content .= "Montant : " .$amount. "<br />";
		$body_content .= "Retour LW : " .print_r( $lw_msg, true ). "<br />";
		return NotificationsEmails::send_mail($admin_email, $object, $body_content);
	}
	
    public static function new_purchase_admin_error( $user_data, $int_msg, $txt_msg, $project_title, $amount, $ask_restart ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_admin_error > ' . $user_data->user_email);
		$admin_email = 'admin@wedogood.co';
		$object = 'Erreur investissement';
		$body_content = "Tentative d'investissement avec erreur :<br />";
		$body_content .= "Login : " .$user_data->user_login. "<br />";
		$body_content .= "e-mail : " .$user_data->user_email. "<br />";
		if ( !empty( $project_title ) ) {
			$body_content .= "Projet : " .$project_title. "<br />";
		}
		if ( !empty( $amount ) ) {
			$body_content .= "Montant : " .$amount. "<br />";
		}
		$body_content .= "Erreur LW : " .$int_msg. "<br />";
		$body_content .= "Texte d'erreur pour l'utilisateur : " .$txt_msg. "<br />";
		if ($ask_restart) {
			$body_content .= "A proposé de recommencer<br />";
		} else {
			$body_content .= "N'a pas proposé de recommencer<br />";
		}
		return NotificationsEmails::send_mail($admin_email, $object, $body_content);
	}
	
	public static function new_purchase_pending_wire_admin( $payment_id ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_pending_wire_admin > ' . $payment_id);
		$admin_email = 'support@wedogood.co';
		
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);
		
		$object = "Un nouveau virement a été enregistré";
		
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Un nouveau virement de ".$payment_amount." &euro; a été enregistré pour le projet " .$campaign->data->post_title. ".<br /><br />";
		$body_content .= "Utilisateur :<br />";
		$body_content .= "- login : " .$user_data->user_login. "<br />";
		$body_content .= "- e-mail : " .$email. "<br />";
		$body_content .= "- prénom et nom : " .$user_data->first_name . " " . $user_data->last_name. "<br />";
		$body_content .= "- téléphone : " . get_user_meta($user_data->ID, 'user_mobile_phone', true). "<br />";
		
		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, true );
	}
	
	public static function new_purchase_pending_check_admin( $payment_id, $picture_url ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_pending_check_admin > ' . $payment_id);
		$admin_email = 'support@wedogood.co';
		
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);
		
		$object = "Un nouveau chèque a été enregistré";
		
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Un nouveau chèque de ".$payment_amount." &euro; a été enregistré pour le projet " .$campaign->data->post_title. ".<br /><br />";
		$body_content .= "Utilisateur :<br />";
		$body_content .= "- login : " .$user_data->user_login. "<br />";
		$body_content .= "- e-mail : " .$email. "<br />";
		$body_content .= "- prénom et nom : " .$user_data->first_name . " " . $user_data->last_name. "<br />";
		$body_content .= "- téléphone : " . get_user_meta($user_data->ID, 'user_mobile_phone', true). "<br />";
		if ( $picture_url ) {
			$body_content .= "Une photo a été envoyée :<br />";
			$body_content .= "<img src='".$picture_url."' /><br />";
		} else {
			$body_content .= "Aucune photo n'a été envoyée.<br />";
		}
		
		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, true );
	}
	
	public static function preinvestment_auto_validated( $user_email, $campaign ) {
		$object = "Votre pré-investissement est validé";
		
		$body_content = "Bonjour,<br><br>";
		$body_content .= "Le pré-investissement que vous avez effectué pour le projet ".$campaign->data->post_title." a été validé automatiquement.<br>";
		$body_content .= "Aucune modification n'ayant été apportée au contrat, les conditions auxquelles vous avez souscrit restent les mêmes.<br><br>";
		
		$body_content .= "Merci encore pour votre investissement et à bientôt sur WE DO GOOD !<br>";
		
		return NotificationsEmails::send_mail( $user_email, $object, $body_content, true );
	}
	
	public static function preinvestment_to_validate( $user_email, $campaign ) {
		$object = "Votre pré-investissement doit être validé";
		
		$body_content = "Bonjour,<br><br>";
		$body_content .= "Suite à la phase d'&eacute;valuation, des modifications ont été apportées sur les conditions d'investissement pour le projet ".$campaign->data->post_title.".";
		$body_content .= "Le pré-investissement que vous avez effectué doit donc être à nouveau validé.<br>";
		$body_content .= "Merci de vous rendre sur la plateforme pour vous identifier et suivre le processus de validation qui sera affiché.<br><br>";
		
		$body_content .= "Cliquez sur <a href=\"" .home_url( '/mon-compte/' ). "\">Mon compte</a> pour vous identifier.<br><br>";
		
		$body_content .= "Merci encore pour votre investissement et à bientôt sur WE DO GOOD !<br>";
		
		return NotificationsEmails::send_mail( $user_email, $object, $body_content, true );
	}
	
	public static function preinvestment_canceled( $user_email, $campaign ) {
		$object = "Votre pré-investissement est annulé";
		
		$body_content = "Bonjour,<br><br>";
		$body_content .= "Suite à votre demande, le pré-investissement que vous aviez effectué sur le projet ".$campaign->data->post_title." a été annulé.<br>";
		$body_content .= "Si vous aviez payé par carte, la somme vous est directement remboursée sur votre compte bancaire.<br>";
		$body_content .= "Si vous aviez payé par porte-monnaie WE DO GOOD, la somme est versée sur votre porte-monnaie.<br>";
		$body_content .= "Si vous aviez payé par virement, la somme est versée sur votre porte-monnaie WE DO GOOD (rendez-vous sur votre compte).<br>";
		$body_content .= "Si vous aviez payé par chèque, celui-ci ne sera pas encaissé.<br><br>";
		
		$body_content .= "A bientôt sur WE DO GOOD !<br>";
		
		return NotificationsEmails::send_mail( $user_email, $object, $body_content, true );
	}
	
	public static function investment_draft_created_admin( $campaign_name, $dashboard_url ) {
		$user_email = "support@wedogood.co";
		
		$object = "Ajout de chèque dans TB par le PP pour le projet " . $campaign_name;
		
		$body_content = "Salut,<br><br>";
		$body_content .= "L'équipe du projet " .$campaign_name. " vient d'ajouter un chèque qu'il faudrait valider.<br>";
		$body_content .= "URL du TB : <a href=\"" .$dashboard_url. "\" target=\"_blank\">" .$dashboard_url. "</a><br><br>";
		$body_content .= "Bon courage !";
		
		return NotificationsEmails::send_mail( $user_email, $object, $body_content, true );
	}
	
	public static function investment_draft_validated_new_user( $user_email, $user_firstname, $user_password, $campaign_name ) {
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
	public static function new_project_posted_error_admin( $project_name, $error_content ) {
		$to = 'admin@wedogood.co';
		$object = 'Erreur lors de la création du projet ' . $project_name;
		$body_content = 'Bonjour,<br>';
		$body_content .= 'Il y a eu une erreur lors de la création du projet ' .$project_name. ' :<br>';
		$body_content .= $error_content;
		return NotificationsEmails::send_mail( $to, $object, $body_content );
		
	}
	
	public static function new_project_posted_owner($campaign_id) {
		$post_campaign = get_post($campaign_id);
		$user_author = get_user_by('id', $post_campaign->post_author);
		
		$to = $user_author->user_email;
		$object = 'Votre dossier a bien été enregistré sur '.ATCF_CrowdFunding::get_platform_name();
		
		$body_content = 'Bonjour '.$user_author->first_name.',<br />';
		$body_content .= 'Les informations de votre levée de fonds ont bien été enregistrées sur '.ATCF_CrowdFunding::get_platform_name().'. ';
		$body_content .= 'Vous pouvez dès à présent les compléter en accédant à votre <a href="'. home_url('/tableau-de-bord/').'?campaign_id='.$campaign_id.'">tableau de bord</a>.<br />';
		$body_content .= 'Toutes les informations communiquées à '.ATCF_CrowdFunding::get_platform_name().' sont gardées confidentielles.<br /><br />';
		$body_content .= 'Notre équipe vous contactera très prochainement pour vous conseiller sur la préparation de votre levée de fonds.<br /><br />';
		$body_content .= 'Bien à vous,<br />';
		$body_content .= "L'équipe de ".ATCF_CrowdFunding::get_platform_name();

		return NotificationsEmails::send_mail($to, $object, $body_content);
	}
    //*******************************************************
    // FIN NOUVEAU PROJET
    //*******************************************************
    
    //*******************************************************
    // CODE SIGNATURE
    //*******************************************************
    /**
     * Mail à investisseur pour envoyer code nouvelle signature
     * @param string $user_name
     * @param string $user_email
     * @param string $code
     * @return bool
    public static function send_new_contract_code_user( $user_name, $user_email, $contract_title, $code ) {
		ypcf_debug_log('NotificationsEmails::send_new_contract_code_user > ' . $user_name . ' | ' . $user_email . ' | ' . $contract_title . ' | ' . $code);

		$object = "Votre code de signature";
		$body_content = "Bonjour ".$user_name.",<br><br>";
		$body_content .= "Afin de signer le contrat " .$contract_title. " chez notre partenaire Eversign, ";
		$body_content .= "voici le code qu'il vous faudra entrer pour le valider :<br>";
		$body_content .= $code . "<br><br>";
		$body_content .= "Nous vous remercions par avance,<br>";
		$body_content .= "Bien cordialement,<br>";
		$body_content .= "L'équipe WE DO GOOD<br>";

		return NotificationsEmails::send_mail( $user_email, $object, $body_content, true );
    }
     */
    //*******************************************************
    // FIN CODE SIGNATURE
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
		$emails = $user->user_email;
		$emails .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );

		return NotificationsEmails::send_mail($emails, $object, $body_content, true);
    }
    //*******************************************************
    // FIN NOUVEAU COMMENTAIRE
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS PAIEMENTS ROI
    //*******************************************************
	public static function turnover_declaration_adjustment_file_sent( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::turnover_declaration_adjustment_file_sent > ' . $declaration_id);
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign( FALSE, $declaration->id_campaign );
		
		$admin_email = 'administratif@wedogood.co';
		$object = "Projet " . $campaign->data->post_title . " - Envoi de fichier d'ajustement";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le projet " .$campaign->data->post_title. " a envoyé un document d'ajustement pour une déclaration de royalties à venir.<br>";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
	public static function turnover_declaration_null( $declaration_id, $declaration_message ) {
		ypcf_debug_log('NotificationsEmails::turnover_declaration_null > ' . $declaration_id);
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign( FALSE, $declaration->id_campaign );
		
		$admin_email = 'administratif@wedogood.co';
		$object = "Projet " . $campaign->data->post_title . " - Déclaration de CA à zero";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le projet " .$campaign->data->post_title. " a fait sa déclaration de CA, mais a déclaré 0. :'(<br /><br />";
		$body_content .= "Message du PP :";
		$body_content .= $declaration_message;
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
	public static function turnover_declaration_not_null( $declaration_id, $declaration_message ) {
		ypcf_debug_log('NotificationsEmails::turnover_declaration_not_null > ' . $declaration_id);
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign( FALSE, $declaration->id_campaign );
		
		$admin_email = 'administratif@wedogood.co';
		$object = "Projet " . $campaign->data->post_title . " - Déclaration de CA effectuée";
		$body_content = "Hello !<br><br>";
		$body_content .= "Le projet " .$campaign->data->post_title. " a fait sa déclaration de CA ! :)<br><br>";
		$body_content .= "Message du PP :";
		$body_content .= $declaration_message;
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
    public static function send_notification_roi_payment_success_user( $declaration_id ) {
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
	
    public static function send_notification_roi_payment_success_admin( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_success_admin > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );
		
		$admin_email = 'administratif@wedogood.co';
		$object = "Projet " . $campaign->data->post_title . " - Paiement ROI effectué";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le paiement du reversement de ROI pour le projet " .$campaign->data->post_title. " de ".$roi_declaration->get_amount_with_commission()." € a été effectué.<br /><br />";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
    public static function send_notification_roi_payment_pending_admin( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_pending_admin > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );
		
		$admin_email = 'administratif@wedogood.co';
		$object = "Projet " . $campaign->data->post_title . " - Paiement ROI en attente";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le paiement du reversement de ROI pour le projet " .$campaign->data->post_title. " de ".$roi_declaration->get_amount_with_commission()." € est déclenché et en attente.<br /><br />";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}

	public static function send_notification_roi_payment_bank_transfer_admin( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_bank_transfer_admin > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );
		
		$admin_email = 'administratif@wedogood.co';
		$object = "Projet " . $campaign->data->post_title . " - Paiement par virement déclaré";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le paiement du reversement de ROI pour le projet " .$campaign->data->post_title. " de ".$roi_declaration->get_amount_with_commission()." € est en attente de virement.<br /><br />";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
    public static function send_notification_roi_payment_error_admin( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_error_admin > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );
		
		$admin_email = 'administratif@wedogood.co';
		$object = "Projet " . $campaign->data->post_title . " - Problème de paiement de ROI";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Il y a eu un problème lors du paiement du reversement de ROI pour le projet " .$campaign->data->post_title. " (".$roi_declaration->get_amount_with_commission()." €).<br /><br />";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
    public static function roi_received_exceed_investment( $investor_id, $investor_type, $project_id ) {
		ypcf_debug_log( 'NotificationsEmails::roi_received_exceed_investment > ' .$investor_id. ' | ' .$investor_type. ' | ' .$project_id );
		$campaign = new ATCF_Campaign( FALSE, $project_id );
		$investor_entity = ( $investor_type == 'orga' ) ? WDGOrganization::get_by_api_id( $investor_id ) : WDGUser::get_by_api_id( $investor_id );
		
		$object = "Royalties percues supérieures à l'investissement initial";
		$body_content = "Coucou !<br><br>";
		$body_content .= "Un investisseur a reçu plus de royalties que son investissement de départ.<br>";
		$body_content .= "Sur le projet : " .$campaign->get_name(). "<br>";
		$body_content .= "Type d'investisseur : " .( $investor_type == 'orga' ) ? 'Organisation' : 'Utilisateur'. "<br>";
		$body_content .= "ID API investisseur : " .$investor_id. "<br>";
		$body_content .= "ID WP investisseur : " .$investor_entity->get_wpref();
		
		$admin_email = 'administratif@wedogood.co';
		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, true );
	}
	
    public static function roi_received_exceed_maximum( $investor_id, $investor_type, $project_id ) {
		ypcf_debug_log( 'NotificationsEmails::roi_received_exceed_maximum > ' .$investor_id. ' | ' .$investor_type. ' | ' .$project_id );
		$campaign = new ATCF_Campaign( FALSE, $project_id );
		$investor_entity = ( $investor_type == 'orga' ) ? WDGOrganization::get_by_api_id( $investor_id ) : WDGUser::get_by_api_id( $investor_id );
		
		$object = "URGENT - Royalties percues supérieures au maximum pouvant être reçu";
		$body_content = "Coucou !<br><br>";
		$body_content .= "Un investisseur a reçu plus de royalties que son investissement de départ ne le permettait (maximum dépassé).<br>";
		$body_content .= "Sur le projet : " .$campaign->get_name(). "<br>";
		$body_content .= "Type d'investisseur : " .( $investor_type == 'orga' ) ? 'Organisation' : 'Utilisateur'. "<br>";
		$body_content .= "ID API investisseur : " .$investor_id. "<br>";
		$body_content .= "ID WP investisseur : " .$investor_entity->get_wpref();
		
		$admin_email = 'administratif@wedogood.co';
		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, true );
	}
	
	public static function declarations_close_to_maximum_profit( $project_name, $ratio ) {
		$object = "Projet proche du versement complet de royalties";
		$body_content = "Coucou !<br><br>";
		$body_content .= "Le projet " .$project_name. " est proche d'atteindre son versement maximum (ratio de " .$ratio. " %).";
		
		$admin_email = 'administratif@wedogood.co';
		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, true );
	}

	public static function declaration_bill_failed( $campaign_name ) {
		ypcf_debug_log('NotificationsEmails::declaration_bill_failed > ' . $campaign_name, false);
		$object = "Erreur génération facture - " . $campaign_name;
		$body_content = "Hello !<br><br>";
		$body_content .= "La facture automatique de la dernière déclaration de royalties pour le projet " .$campaign_name. " n'a pas pu être créée.";
		
		$admin_email = 'administratif@wedogood.co';
		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, true );
	}
    //*******************************************************
    // FIN NOTIFICATIONS PAIEMENTS ROI
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS KYC
    //*******************************************************
    public static function send_notification_kyc_refused_admin( $user_email, $user_name, $pending_actions ) {
		ypcf_debug_log('NotificationsEmails::send_notification_kyc_refused_admin > ' . $user_email);
		
		$admin_email = 'support@wedogood.co';
		$object = "Investisseur à relancer !";
		
		$body_content = "Hello !<br>";
		$body_content .= "Lemon Way a refusé des documents depuis quelques jours, et l'utilisateur a quelques actions en attente.<br>";
		$body_content .= "Il s'agit de " .$user_name. ".<br>";
		$body_content .= "Son adresse e-mail est la suivante : " .$user_email. "<br><br>";
		
		$body_content .= "Voici ses actions sur le site :<br>";
		foreach ( $pending_actions as $pending_action ) {
			$body_content .= "- " .$pending_action. "<br>";
		}

		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, TRUE );
	}
	
    public static function send_notification_kyc_validated_but_not_wallet_admin( $user_email, $user_name, $pending_actions ) {
		ypcf_debug_log('NotificationsEmails::send_notification_kyc_validated_but_not_wallet_admin > ' . $user_email);
		
		$admin_email = 'support@wedogood.co';
		$object = "Wallet à vérifier !";
		
		$body_content = "Hello !<br>";
		$body_content .= "Lemon Way a validé tous les documents du wallet, mais le wallet n'est pas authentifié.<br>";
		$body_content .= "Il s'agit de " .$user_name. ".<br>";
		$body_content .= "Son adresse e-mail est la suivante : " .$user_email. "<br><br>";
		
		$body_content .= "Voici ses actions sur le site :<br>";
		foreach ( $pending_actions as $pending_action ) {
			$body_content .= "- " .$pending_action. "<br>";
		}

		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, TRUE );
	}
	
	public static function organization_bank_file_changed_admin( $organization_name ) {
		ypcf_debug_log('NotificationsEmails::organization_bank_file_changed_admin > ' . $organization_name);
		
		$admin_email = 'support@wedogood.co';
		$object = "RIB d'organisation modifié - " . $organization_name;

		$body_content = "Hello !<br>";
		$body_content .= "L'organisation ".$organization_name." a changé de RIB.<br>";
		$body_content .= "Si c'était un projet en versement, il faudrait refaire signer l'autorisation de prélèvement.<br>";

		return NotificationsEmails::send_mail( $admin_email, $object, $body_content, TRUE );
	}
    //*******************************************************
    // FIN NOTIFICATIONS KYC
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS INTERFACE PROSPECT
    //*******************************************************
    public static function prospect_setup_user_project_drafts( $user_email, $project_list ) {
		$object = "Reprenez votre test d'éligibilité aux royalties";

		$body_content = "Bonjour,<br>";
		$body_content .= "Suite à votre demande, vous trouverez ci-dessous ";
		if ( count( $project_list > 1 ) ) {
			$body_content .= "la liste de vos tests ";
		} else {
			$body_content .= "le lien de votre test ";
		}
		$body_content .= "d'éligibilité aux royalties :";
		$body_content .= "<br><br>";

		$body_content .= "<ul>";
		foreach ( $project_list as $project_item ) {
			$body_content .= "<li>";
			$body_content .= "<a href=\"" . home_url( '/test-royalties/?guid=' . $project_item[ 'guid' ] ) . "\">". $project_item[ 'name' ] ."</a>";
			$body_content .= "</li>";
		}
		$body_content .= "</ul>";
		$body_content .= "<br><br>";

		$body_content .= "A bientôt sur WE DO GOOD !";

		return NotificationsEmails::send_mail( $user_email, $object, $body_content, true );
	}
    //*******************************************************
    // FIN NOTIFICATIONS INTERFACE PROSPECT
    //*******************************************************
	
	
    //*******************************************************
    // NOTIFICATIONS ERREURS ADMIN
    //*******************************************************
    public static function investment_to_api_error_admin( $edd_payment_item ) {
		$admin_email = 'admin@wedogood.co';
		
		$object = "Erreur d'ajout d'investissement sur l'API";
		$body_content = "Salut !!<br>";
		$body_content .= "Problème d'ajout d'un investissement sur l'API, avec l'identifiant suivant : " . $edd_payment_item->ID;

		return NotificationsEmails::send_mail( $admin_email, $object, $body_content );
    }
    //*******************************************************
    // END NOTIFICATIONS ERREURS ADMIN
    //*******************************************************
}
