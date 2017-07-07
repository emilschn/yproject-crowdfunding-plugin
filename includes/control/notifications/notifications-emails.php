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
		$content = wpautop( $edd_options['header_global_mail'] ) . $content . wpautop( $edd_options['footer_global_mail'] );
	}

	$buffer = wp_mail( $to, $object, $content, $headers, $attachments );
	ypcf_debug_log('NotificationsEmails::send_mail > ' . $to . ' | ' . $object . ' >> ' . $buffer);
	return $buffer;
    }
    
    
    //*******************************************************
    // ACHATS
    //*******************************************************
    /**
     * Mail pour l'investisseur lors d'un achat avec erreur de création de contrat
     * @param int $payment_id
     * @return bool
     */
    public static function new_purchase_user_error_contract($payment_id) {
	ypcf_debug_log('NotificationsEmails::new_purchase_user_error_contract > ' . $payment_id);
	$particular_content = "<span style=\"color: red;\">Il y a eu un problème durant la génération du contrat. Notre équipe en a été informée.</span>";
	return NotificationsEmails::new_purchase_user($payment_id, $particular_content);
    }
    
    /**
     * Mail pour l'investisseur lors d'un achat avec création de contrat réussie
     * @param int $payment_id
     * @return bool
     */
    public static function new_purchase_user_success($payment_id, $code, $is_card_contribution = true) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user_success > ' . $payment_id);

		$particular_content = "";
		if ( $is_card_contribution ) {
			$particular_content .= NotificationsEmails::new_purchase_lemonway_conditions();
		}

		$particular_content .= "Il vous reste encore à signer le contrat que vous devriez recevoir de la part de notre partenaire Signsquid ";
		$particular_content .= "(<strong>Pensez à vérifier votre courrier indésirable</strong>).<br />";
		$particular_content .= "Votre code personnel pour signer le contrat : <strong>" . $code . "</strong>";
		return NotificationsEmails::new_purchase_user($payment_id, $particular_content);
    }
    
    /**
     * Mail pour l'investisseur lors d'un achat sans nécessité de signer le contrat
     * @param type $payment_id
     * @return type
     */
    public static function new_purchase_user_success_nocontract($payment_id, $new_contract_pdf_file, $is_card_contribution = true) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user_success_nocontract > ' . $payment_id);
		
		$particular_content = "";
		if ( $is_card_contribution ) {
			$particular_content .= NotificationsEmails::new_purchase_lemonway_conditions();
		}
		
		$particular_content .= "Vous trouverez votre contrat d'investissement ci-joint.";
		
		$attachments = array($new_contract_pdf_file);
		return NotificationsEmails::new_purchase_user($payment_id, $particular_content, $attachments);
    }
	
	private static function new_purchase_lemonway_conditions() {
		$buffer = "Sur votre relevé de compte bancaire, vous verrez apparaître le libellé «Lemon Way », dans le détail des opérations Carte Bancaire.<br />";
		$buffer .= "L'acceptation des CGU de Lemon Way, entraine l'ouverture d'un compte de paiement dédié à l'utilisation du site.";
		$buffer .= "Vous pouvez clôturer ce compte à tout moment en suivant la procédure décrite dans les CGU de Lemon Way.<br />";
		return $buffer;
	}
    
    /**
     * Mail pour l'investisseur lors d'un achat
     * @param int $payment_id
     * @param string $particular_content
     * @return bool
     */
    public static function new_purchase_user($payment_id, $particular_content, $attachments = array()) {
		ypcf_debug_log('NotificationsEmails::new_purchase_user > ' . $payment_id);
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);

		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$user_info = maybe_unserialize( $payment_data['user_info'] );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);

		$funding_type = ($campaign->funding_type() == 'fundingdonation') ? 'soutien' : 'investissement';

		$object = "Merci pour votre " . $funding_type;
		$body_content = '';
		$dear_str = ( isset( $user_info['gender'] ) && $user_info['gender'] == "female") ? "Chère" : "Cher";
		$body_content = $dear_str." ".$user_data->first_name . " " . $user_data->last_name.",<br /><br />";
		$body_content .= $post_campaign->post_title . " vous remercie pour votre " . $funding_type . ". Votre compte a été débité mais n'oubliez pas que l'investissement ne sera définitivement validé ";
		$body_content .= "que si le projet atteint son seuil minimal de financement. N'hésitez donc pas à en parler autour de vous et sur les réseaux sociaux !<br/>"
                . "Retrouvez le projet à l'adresse suivante : "
                .'<a href="'.get_permalink($campaign->ID).'">'.get_permalink($campaign->ID).'</a></br>'
                ."<br /><br />";
		$body_content .= $particular_content . "<br /><br />";

		$body_content .= "<strong>Détails concernant votre ".$funding_type."</strong><br />";
		$body_content .= "Projet : " . $post_campaign->post_title . "<br />";
		$body_content .= "Montant : ".$payment_amount."&euro;<br />";
        if ($campaign->funding_type()=="fundingdonation"){
            $reward = get_post_meta( $payment_id, '_edd_payment_reward', true);
            $body_content .= " Contrepartie choisie : Palier de ".$reward['amount']."&euro; - ".$reward['name']."<br/>";
        }
		$body_content .= "Horodatage : ". get_post_field( 'post_date', $payment_id ) ."<br /><br />";

		return NotificationsEmails::send_mail($email, $object, $body_content, true, $attachments);
    }
	
	public static function new_purchase_pending_wire_user( $payment_id ) {
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);
		$WDGUser_current = new WDGUser( $user_data->ID );
		
		$object = "Rappels pour votre virement";
		
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Vous avez demand&eacute; un investissement de ".$payment_amount." &euro; par virement pour le projet " .$campaign->data->post_title. ".<br /><br />";
		
		$body_content .= "Voici le rappel des informations pour proc&eacute;der au virement, si vous ne l'avez pas encore fait :<br />";
		
		$body_content .= "<ul>";
		$body_content .= "	<li><strong>" .__("Titulaire du compte :", 'yproject'). "</strong> LEMON WAY</li>";
		$body_content .= "	<li><strong>IBAN :</strong> FR76 3000 4025 1100 0111 8625 268</li>";
		$body_content .= "	<li><strong>BIC :</strong> BNPAFRPPIFE</li>";
		$body_content .= "	<li>";
		$body_content .= "		<strong>" .__("Code &agrave; indiquer (pour identifier votre paiement) :", 'yproject'). "</strong> wedogood-" .$WDGUser_current->get_lemonway_id(). "<br />";
		$body_content .= "		<ul>";
		$body_content .= "			<li>" .__("Indiquez imp&eacute;rativement ce code comme 'libell&eacute; b&eacute;n&eacute;ficiaire' ou 'code destinataire' au moment du virement !", 'yproject'). "</li>";
		$body_content .= "		</ul>";
		$body_content .= "	</li>";
		$body_content .= "</ul><br /><br />";
		
		$body_content .= "N'h&eacute;sitez pas &agrave; nous contacter si vous avez eu un souci lors de l'envoi des documents.<br /><br />";
		$body_content .= "Toute l'&eacute;quipe de ".ATCF_CrowdFunding::get_platform_name()." vous remercie pour votre investissement !";
		
		
		return NotificationsEmails::send_mail( $email, $object, $body_content, true );
		
	}
	
	public static function new_purchase_pending_check_user( $payment_id, $with_picture ) {
		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		$campaign_organization = $campaign->get_organization();
		$organization_obj = new WDGOrganization( $campaign_organization->wpref );
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);
		$WDGUser_current = new WDGUser( $user_data->ID );
		
		$object = "Votre investissement par chèque est en attente de validation";
		
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Vous avez demand&eacute; un investissement de ".$payment_amount." &euro; par chèque pour le projet " .$campaign->data->post_title. ".<br /><br />";
		
		$body_content .= "Afin de valider votre investissement, nous vous rappelons que vous devez envoyer votre chèque (à l'ordre de ".$organization_obj->get_name().") par courrier à l'adresse suivante :<br />";
		$body_content .= "WE DO GOOD<br />";
		$body_content .= "7 rue Mathurin Brissonneau<br />";
		$body_content .= "44100 Nantes<br /><br />";

		if ( !$with_picture ) {
			$body_content .= "Si vous souhaitez que votre investissement soit pris en compte plus rapidement, envoyez-nous d'abord une photo du chèque à investir@wedogood.co<br /><br />";
		}
		
		$body_content .= "N'h&eacute;sitez pas &agrave; nous contacter si vous avez eu un souci lors de cette procédure à l'adresse investir@wedogood.co.<br /><br />";
		$body_content .= "Toute l'&eacute;quipe de ".ATCF_CrowdFunding::get_platform_name()." vous remercie pour votre investissement !";
		
		
		return NotificationsEmails::send_mail( $email, $object, $body_content, true );
		
	}
    
    /**
     * Mail pour l'équipe projet lors d'un achat
     * @param int $payment_id
     * @return bool
     */
    public static function new_purchase_team_members($payment_id) {
	ypcf_debug_log('NotificationsEmails::new_purchase_members > ' . $payment_id);
	$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
	$campaign = atcf_get_campaign($post_campaign);
	
	$author_data = get_userdata($post_campaign->post_author);
	$emails = $author_data->user_email;
	$emails .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );
	
	$object = "Nouvel investissement";
	
	$payment_data = edd_get_payment_meta( $payment_id );
	$payment_amount = edd_get_payment_amount( $payment_id );
	$email = $payment_data['email'];
	$user_data = get_user_by('email', $email);
	
	$body_content = "Une nouvelle personne a investi sur votre projet ".$post_campaign->post_title.":<br />";
	$body_content .= $user_data->user_firstname . " " . $user_data->user_lastname . " a investi ".$payment_amount." &euro;";
        if ($campaign->funding_type()=="fundingdonation"){
            $reward = get_post_meta( $payment_id, '_edd_payment_reward', true);
            $body_content .= " et a choisi la contrepartie suivante : palier de <br/>".$reward['amount']."&euro; - ".$reward['name'];
        }
        $body_content .= ".<br />";

	$body_content .= "Votre projet a atteint ".$campaign->percent_minimum_completed()." de son objectif, soit ".$campaign->current_amount()." sur ".$campaign->minimum_goal(true).".";
        
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
		$admin_email = get_option('admin_email');
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
	
    public static function new_purchase_admin_error( $user_data, $int_msg, $txt_msg, $project_title, $amount, $ask_restart ) {
		ypcf_debug_log('NotificationsEmails::new_purchase_admin_error > ' . $user_data->user_email);
		$admin_email = 'investir@wedogood.co';
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
		$admin_email = 'investir@wedogood.co';
		
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
		$admin_email = 'investir@wedogood.co';
		
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
	
    //*******************************************************
    // FIN ACHATS
    //*******************************************************
    
    
    //*******************************************************
    // NOUVEL UTILISATEUR
    //*******************************************************
    /**
     * Mail à l'admin lors de l'inscription d'un utilisateur
     * @param int $wp_user_id
     * @return bool
     */
    public static function new_user_admin($wp_user_id) {
	ypcf_debug_log('NotificationsEmails::new_user_admin > ' . $wp_user_id);
	$admin_email = get_option('admin_email');
	$object = 'Nouvel utilisateur';
	
	$user_data = get_userdata($wp_user_id);

	$body_content = 'Nouvel utilisateur inscrit (' . $wp_user_id . ') sur '.ATCF_CrowdFunding::get_platform_name().'<br /><br />';
	$body_content .= "<strong>Détails de l'utilisateur</strong><br />";
	$body_content .= "Identifiant : " . $user_data->user_login . "<br />";
	$body_content .= "E-mail : " . $user_data->user_email . "<br />";
	
	return NotificationsEmails::send_mail($admin_email, $object, $body_content);
    }
    
    /**
     * Mail pour l'utilisateur lors de la création de son compte
     * @param int $wp_user_id
     * @return bool
     */
    public static function new_user_user($wp_user_id) {
	ypcf_debug_log('NotificationsEmails::new_user_user > ' . $wp_user_id);
	
	$user_data = get_userdata($wp_user_id);
	
	$object = "Bienvenue chez ".ATCF_CrowdFunding::get_platform_name()." !";
	
	$name = ($user_data->first_name != '') ? $user_data->first_name : $user_data->user_login;
	$body_content = 'Bonjour ' .$name. ',<br />';
	$body_content .= 'Nous vous souhaitons la bienvenue chez <a href="'.home_url().'">'.ATCF_CrowdFunding::get_platform_name().'</a>';
	$body_content .= ' et esp&eacute;rons vous retrouver bient&ocirc;t pour vous faire d&eacute;couvrir les projets que nous accompagnons !';
        
	return NotificationsEmails::send_mail($user_data->user_email, $object, $body_content, true);
    }
    //*******************************************************
    // FIN NOUVEL UTILISATEUR
    //*******************************************************
    
    
    //*******************************************************
    // NOUVEAU PROJET
    //*******************************************************
    /**
     * Mail à l'admin et aux adresses en copie lors de la création d'un projet
     * @param int $campaign_id
     * @param string $copy_recipient
     * @return bool
     */
    public static function new_project_posted($campaign_id, $orga_name, $copy_recipient) {
		$admin_email = get_option('admin_email');
		$to = $admin_email . ',' . $copy_recipient;

		$post_campaign = get_post($campaign_id);
		$campaign = atcf_get_campaign($post_campaign);
		$project_title = $post_campaign->post_title;
		$object = '[Nouveau Projet] '. $project_title;
		$body_content = "Un nouveau projet vient d'être publié.<br />";
		$body_content .= "Il est accessible depuis le back-office :<br />";
		$body_content .= '<a href="'. get_permalink($campaign_id) .'" target="_blank">'. $project_title .'</a><br /><br />';
		$user_author = get_user_by('id', $post_campaign->post_author);
		$body_content .= "Quelques informations supplémentaires :<br />";
		$body_content .= "- Porteur de projet : ".$user_author->first_name." ".$user_author->last_name." (".$user_author->user_login.")<br />";
		$body_content .= "- Mail : ".$user_author->user_email."<br />";
		$user_phone = get_user_meta( $post_campaign->post_author, 'user_mobile_phone', TRUE );
		$body_content .= "- Téléphone : ".$user_phone."<br /><br />";
		$body_content .= "- Organisation : ".$orga_name."<br />";
		$body_content .= "- Description : ".$campaign->backoffice_summary()."<br />";

		return NotificationsEmails::send_mail($to, $object, $body_content);
    }
	
	public static function new_project_posted_owner($campaign_id) {
		$post_campaign = get_post($campaign_id);
		$user_author = get_user_by('id', $post_campaign->post_author);
		
		$to = $user_author->user_email;
		$object = 'Votre dossier a bien été enregistré sur '.ATCF_CrowdFunding::get_platform_name();
		
		$body_content = 'Bonjour '.$user_author->first_name.',<br />';
		$body_content .= 'Les informations de votre campagne ont bien été enregistrées sur '.ATCF_CrowdFunding::get_platform_name().'. ';
		$body_content .= 'Vous pouvez dès à présent les compléter en accédant à votre <a href="'. home_url('/tableau-de-bord').'?campaign_id='.$campaign_id.'">tableau de bord</a>.<br />';
		$body_content .= 'Toutes les informations communiquées à '.ATCF_CrowdFunding::get_platform_name().' sont gardées confidentielles.<br /><br />';
		$body_content .= 'Notre équipe vous contactera très prochainement pour vous conseiller sur la préparation de votre campagne.<br /><br />';
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
     * Mail à investisseur pour renvoyer le code de signature
     * @param int $payment_id
     * @param WP_User $user
     * @param string $code
     * @return bool
     */
    public static function send_code_user($payment_id, $user, $code) {
	ypcf_debug_log('NotificationsEmails::send_code_user > ' . $payment_id . ' | ' . $user->ID . ' | ' . $code);
	$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
	
	$object = "Code d'investissement";
	$body_content = "Cher ".$user->first_name." ".$user->user_lastname.",<br /><br />";
	$body_content .= "Afin de confirmer votre investissement sur le projet " . $post_campaign->post_title . ", ";
	$body_content .= "voici le code qui vous permettra de signer le contrat chez notre partenaire Signsquid :<br />";
	$body_content .= $code . "<br /><br />";
	$body_content .= "Si vous n'avez fait aucune action pour recevoir ce code, ne tenez pas compte de ce message.<br /><br />";
	
	return NotificationsEmails::send_mail($user->user_email, $object, $body_content, true);
    }
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
	
	get_comment($comment_id);
	$post_parent = get_post($comment_object->comment_parent);
	$post_categories = get_the_category($post_parent->ID);
	if (count($post_categories) == 0 || $post_categories[0]->slug == 'wedogood' || $post_categories[0]->slug == 'revue-de-presse') {
	    return FALSE;
	}
	$post_first_category = $post_categories[0];
	$post_first_category_name = $post_first_category->name;
	$name_exploded = explode('cat', $post_first_category_name);
	if (count($name_exploded) < 2) { return FALSE; }
	$post_campaign = get_post($name_exploded[1]);
	$campaign = new ATCF_Campaign( $post_campaign );
	
	$body_content = "Vous avez reçu un nouveau commentaire sur votre projet ".$post_campaign->post_title." :<br />";
	$body_content .= $comment_object->comment_content . "<br /><br />";
	$body_content .= 'Pour y répondre, suivez ce lien : <a href="'.get_permalink($post_parent->ID).'">'.$post_parent->post_title.'</a>.';
	
	$user = get_userdata($post_campaign->post_author);
	$emails = $user->user_email;
	$emails .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );
		
	return NotificationsEmails::send_mail($emails, $object, $body_content, true);
    }
    //*******************************************************
    // FIN NOUVEAU COMMENTAIRE
    //*******************************************************
    
    //*******************************************************
    // NOUVEAU COMMENTAIRE
    //*******************************************************
    public static function new_topic($topic_id, $forum_id, $anonymous_data, $topic_author) {
		ypcf_debug_log('NotificationsEmails::new_topic > ' . $topic_id);
		$object = 'Nouveau sujet !';

		$post_topic = get_post($topic_id);
		$post_forum = get_post($post_topic->post_parent);
		$post_campaign = get_post($post_forum->post_title);
		$campaign = new ATCF_Campaign( $post_campaign );

		$body_content = "Un nouveau sujet a été ouvert sur votre projet ".$post_campaign->post_title." :<br /><br />";
		$body_content .= 'Pour y répondre, suivez ce lien : <a href="'.get_permalink($topic_id).'">'.$post_topic->post_title.'</a>.';

		$user = get_userdata($post_campaign->post_author);
		$emails = $user->user_email;
		$emails .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );

		return NotificationsEmails::send_mail($emails, $object, $body_content, true);
    }
    //*******************************************************
    // FIN NOUVEAU COMMENTAIRE
    //*******************************************************
    
    //*******************************************************
    // MESSAGE DIRECT PORTEUR DE PROJET
    //*******************************************************

    public static function project_mail($campaign_id, $mail_title, $mail_content, $mail_recipients) {
		ypcf_debug_log('NotificationsEmails::project_mail > ' . $campaign_id . ' > ' . $mail_title);
		$post_campaign = get_post($campaign_id);
		$campaign = new ATCF_Campaign($post_campaign);
		$organization = $campaign->get_organization();
		$organization_obj = new WDGOrganization($organization->wpref);
		$project_title = $post_campaign->post_title;

        $from_data = array();
        $from_data['name'] = $project_title;
        $from_data['email'] = $organization_obj->get_email();

        global $wpdb;
        $table_vote = $wpdb->prefix . "ypcf_project_votes";
        $list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum FROM ".$table_vote." WHERE post_id = ".$campaign_id." AND validate_project = 1", OBJECT_K);

        $list_mail = array();
        $feedback = array();
        foreach ($mail_recipients as $id_user) {
            //TODO : Vérifier si l'utilisateur peut bien envoyer à la personne (vérifier si dans la liste des suiveurs/votants/investisseurs)
            $user = get_userdata(intval($id_user));
            $to = $user->user_email;
            $user_data= array(
                'userfirstname'	=> $user->first_name,
                'userlastname'	=> $user->last_name,
                'investwish'	=> $list_user_voters[$id_user]->invest_sum
            );

            $this_mail_content = WDGFormProjects::build_mail_text($mail_content,$mail_title,$campaign_id, $user_data);
            $list_mail[] = $to;

            $feedback[] = NotificationsEmails::send_mail($to, $this_mail_content['title'], $this_mail_content['body'], true, array());
        }
        return array_combine($list_mail, $feedback);
    }
    //*******************************************************
    // FIN MESSAGE DIRECT PORTEUR DE PROJET
    //*******************************************************
    
    //*******************************************************
    // NOUVELLE ACTUALITE DE PROJET
    //*******************************************************
    /**
     * Mail aux membres qui ont mis "j'y crois" à un projet 
     * lorsque celui-ci publie une nouvelle actualité
     * @param int $campaign_id
     * @param int $post_id ID of the new post
     * @return
     */
    public static function new_project_post_posted($campaign_id, $post_id) {
		ypcf_debug_log('NotificationsEmails::new_project_post_posted > ' . $campaign_id . ' > ' . $post_id);
		$post_campaign = get_post($campaign_id);
		$campaign = new ATCF_Campaign($post_campaign);
		$organization = $campaign->get_organization();
		$organization_obj = new WDGOrganization($organization->wpref);
		$project_title = $post_campaign->post_title;
		
		$from_data = array();
		$from_data['name'] = $project_title;
		$from_data['email'] = $organization_obj->get_email();
		
        $new_post = get_post($post_id);
        $post_title = $new_post->post_title;
		$object = 'Actualité ' .$project_title. ' : ' .$post_title;
        
        $body_content = '<div style="font-family: sans-serif; padding: 10px 5%;">'
                .'<h1 style="text-align: center;">'.$post_title.'</h1>';
        
        $body_content .= $new_post->post_content.'<br/>';
        
        $body_content .= '<div style="text-align: center;">'
                .'<a href="'.get_permalink($post_id).'" style="background-color: rgb(255, 73, 76); margin-bottom:10px; padding: 10px; color: rgb(255, 255, 255); text-decoration: none; display: inline-block;" target="_blank">
                    Voir plus</a><br/>'
                .'Message envoy&eacute; par '
                .'<a style="color: rgb(255, 73, 76);" href="'.get_permalink($campaign_id).'" target="_blank">'
                .$project_title.'</a><br/><br/>'
                .'<em>Vous avez re&ccedil;u ce mail car vous croyez au projet '.$project_title
                .'. Si vous ne souhaitez plus recevoir de mail des actualités de ce projet, rendez-vous sur '
                .'votre page "Mon Compte" '.ATCF_CrowdFunding::get_platform_name().' pour désactiver les notifications de ce projet.</em>'
                . '</div></div>';
        //TODO : Lien vers "Mon compte" personnalisé (sauf s'il existe un général ?)
        
        //Récupère liste d'envoi
        global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";
        $result_jcrois = $wpdb->get_results( "SELECT user_id FROM ".$table_jcrois." WHERE subscribe_news = 1 AND campaign_id = ".$campaign_id);
		$list_mail = array();
        $feedback = array();
        
        foreach ($result_jcrois as $item) {
			$to = get_userdata($item->user_id)->user_email;
			$list_mail[] = $to;
		}
		$admin_email = get_option('admin_email');
		$feedback[] = NotificationsEmails::send_mail( $admin_email, $object, $body_content, true, array(), $from_data, $list_mail );
        return array_combine($list_mail, $feedback);
    }
    //*******************************************************
    // FIN NOUVELLE ACTUALITE DE PROJET
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS PAIEMENTS ROI
    //*******************************************************
	public static function turnover_declaration_null( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::turnover_declaration_null > ' . $declaration_id);
		$declaration = new WDGROIDeclaration($declaration_id);
		$campaign = new ATCF_Campaign( $declaration->id_campaign );
		
		$admin_email = get_option('admin_email');
		$object = "Projet " . $campaign->data->post_title . " - Déclaration de CA à zero";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le projet " .$campaign->data->post_title. " a fait sa déclaration de CA, mais a déclaré 0. :'(<br /><br />";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
    public static function send_notification_roi_payment_success_user( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_success_user > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( $roi_declaration->id_campaign );
		$author = get_user_by( 'id', $campaign->data->post_author );
		
		$object = "Paiement de votre reversement effectué";
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Le paiement de votre reversement de ".$roi_declaration->get_amount_with_commission()." € a bien été pris en compte.<br />";
		$body_content .= "Merci et à bientôt sur ".ATCF_CrowdFunding::get_platform_name()." !<br /><br />";
		
		return NotificationsEmails::send_mail($author->user_email, $object, $body_content, true);
	}
    public static function send_notification_roi_payment_success_admin( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_success_admin > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( $roi_declaration->id_campaign );
		
		$admin_email = get_option('admin_email');
		$object = "Projet " . $campaign->data->post_title . " - Paiement ROI effectué";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le paiement du reversement de ROI pour le projet " .$campaign->data->post_title. " de ".$roi_declaration->get_amount_with_commission()." € a été effectué.<br /><br />";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}

	public static function send_notification_roi_payment_bank_transfer_admin( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_bank_transfer_admin > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( $roi_declaration->id_campaign );
		
		$admin_email = get_option('admin_email');
		$object = "Projet " . $campaign->data->post_title . " - Paiement par virement déclaré";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Le paiement du reversement de ROI pour le projet " .$campaign->data->post_title. " de ".$roi_declaration->get_amount_with_commission()." € est en attente de virement.<br /><br />";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
    public static function send_notification_roi_payment_error_admin( $declaration_id ) {
		ypcf_debug_log('NotificationsEmails::send_notification_roi_payment_error_admin > ' . $declaration_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( $roi_declaration->id_campaign );
		
		$admin_email = get_option('admin_email');
		$object = "Projet " . $campaign->data->post_title . " - Problème de paiement de ROI";
		$body_content = "Hello !<br /><br />";
		$body_content .= "Il y a eu un problème lors du paiement du reversement de ROI pour le projet " .$campaign->data->post_title. " (".$roi_declaration->get_amount_with_commission()." €).<br /><br />";
		
		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
	public static function roi_transfer_success_user( $declaration_id, $user_id, $message = '' ) {
		ypcf_debug_log('NotificationsEmails::roi_transfer_success_user > ' . $declaration_id . ' ; ' . $user_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( $roi_declaration->id_campaign );
		$WDGUser = new WDGUser( $user_id );
		
		$object = "Versement de vos royalties pour le projet " . $campaign->data->post_title;
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Vous avez investi dans le projet " . $campaign->data->post_title . " sur ".ATCF_CrowdFunding::get_platform_name()." et nous avons le plaisir de vous informer que le versement des royalties de ce projet a été effectué !<br /><br />";
		
		if ( !empty( $message ) ) {
			$body_content .= "<b>Le porteur de projet vous adresse le message suivant :</b><br />";
			$body_content .= $message;
			$body_content .= "<br /><br />";
		}
		
		$adjustment_message = $roi_declaration->get_adjustment_message( 'investors' );
		if ( $roi_declaration->get_adjustment_validated() && !empty( $adjustment_message ) ) {
			$body_content .= $adjustment_message;
			$body_content .= "<br /><br />";
		}
		
		
		$body_content .= "Nous vous invitons à vous connecter sur ".ATCF_CrowdFunding::get_platform_name()." afin de consulter votre porte monnaie électronique.<br /><br />";
		$body_content .= "<b>Comment percevoir les royalties de mon investissement ?</b><br />";
		$body_content .= "1. <b>Je me connecte</b> sur <a href=\"". home_url('/mon-compte'). "\">". home_url('/mon-compte'). "</a>";
		$body_content .= " et je consulte le montant des royalties perçues dans la partie <b>Mon porte monnaie électronique</b><br />";
		$body_content .= "2. Je clique sur <b>Reverser sur mon compte bancaire</b> et je saisis mes coordonnées bancaires (mon RIB)";
		$body_content .= " OU <b>Je conserve cette somme pour la réinvestir</b> dans d'autres projets.<br /><br />";
		
		$body_content .= apply_filters( 'the_content', WDGROI::get_parameter( 'info_yearly_certificate' ) ) . "<br /><br />";
		
		$body_content .= "Pour toute demande, vous pouvez joindre l'équipe ".ATCF_CrowdFunding::get_platform_name()." à cette adresse : bonjour@wedogood.co<br /><br />";
		$body_content .= "Toute l'équipe ".ATCF_CrowdFunding::get_platform_name()." vous souhaite une belle journée.";
		
		
		return NotificationsEmails::send_mail($WDGUser->wp_user->user_email, $object, $body_content, true);
	}
	
	public static function roi_transfer_null_user( $declaration_id, $user_id, $message = '' ) {
		ypcf_debug_log('NotificationsEmails::roi_transfer_null_user > ' . $declaration_id . ' ; ' . $user_id);
		$roi_declaration = new WDGROIDeclaration( $declaration_id );
		$campaign = new ATCF_Campaign( $roi_declaration->id_campaign );
		$WDGUser = new WDGUser( $user_id );
		
		$object = "Versement de vos royalties pour le projet " . $campaign->data->post_title;
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Vous avez investi dans le projet " . $campaign->data->post_title . " sur ".ATCF_CrowdFunding::get_platform_name()." et le versement de vos royalties était annoncé pour le ".$roi_declaration->get_formatted_date().". ";
		$body_content .= "La déclaration du porteur de projet a bien été reçue et traitée par ".ATCF_CrowdFunding::get_platform_name().". Cependant, le projet n'a généré aucun chiffre d'affaires sur la période concernée et ne peut donc pas vous verser de royaltiez.<br /><br />";
		if ( !empty( $message ) ) {
			$body_content .= "<b>Le porteur de projet vous adresse le message suivant :</b><br />";
			$body_content .= $message;
			$body_content .= "<br /><br />";
		}
		$body_content .= "Nous vous remercions de votre compréhension et restons joignables à cette adresse : bonjour@wedogood.co.<br /><br />";
		$body_content .= "Nous vous souhaitons une belle journée,<br />";
		$body_content .= "L'équipe ".ATCF_CrowdFunding::get_platform_name();
		return NotificationsEmails::send_mail($WDGUser->wp_user->user_email, $object, $body_content, true);
	}
    //*******************************************************
    // FIN NOTIFICATIONS PAIEMENTS ROI
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS PORTE-MONNAIE ELECTRONIQUE
    //*******************************************************
	public static function wallet_transfer_to_account( $user_id, $amount ) {
		ypcf_debug_log('NotificationsEmails::wallet_transfer_to_account > ' . $user_id . ' ; ' . $amount);
		$WDGUser = new WDGUser( $user_id );
		
		$object = "Transfert d'argent vers votre compte bancaire";
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "La somme de ".$amount." € a bien été virée de votre porte-monnaie électronique ".ATCF_CrowdFunding::get_platform_name()." vers votre compte bancaire.<br />";
		$body_content .= "Toute l’équipe ".ATCF_CrowdFunding::get_platform_name()." vous souhaite une agréable journée.";
		
		return NotificationsEmails::send_mail($WDGUser->wp_user->user_email, $object, $body_content, true);
	}

    //*******************************************************
    // FIN NOTIFICATIONS PORTE-MONNAIE ELECTRONIQUE
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS KYC
    //*******************************************************
	/**
	 * @param WDGOrganization $orga
	 */
	public static function document_uploaded_admin($orga, $nb_document) {
		ypcf_debug_log('NotificationsEmails::document_uploaded_admin > ' . $orga->get_wpref());
		
		$admin_email = get_option('admin_email');
		$object = "Documents ajoutés à une organisation";
		$body_content = "Hello !<br />";
		$body_content .= "L'organisation ".$orga->get_name()." a uploadé ".$nb_document." fichier(s).<br /><br />";

		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
	}
	
    public static function send_notification_kyc_accepted_user($user) {
		ypcf_debug_log('NotificationsEmails::send_notification_kyc_accepted_user > ' . $user->ID);
		
		$object = "Vos documents ont été identifiés";
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Suite à l'envoi de vos documents, votre identification auprès de notre partenaire de paiement Lemonway a été acceptée.<br /><br />";

		return NotificationsEmails::send_mail($user->user_email, $object, $body_content, true);
    }
    public static function send_notification_kyc_accepted_admin($user) {
		ypcf_debug_log('NotificationsEmails::send_notification_kyc_accepted_user > ' . $user->ID);
		
		$admin_email = get_option('admin_email');
		$object = "Nouveaux documents identifiés";
		$body_content = "Hello !<br />";
		$body_content .= "Lemonway a validé l'identification de l'utilisateur ".$user->first_name." ".$user->last_name." (".$user->user_login.").<br /><br />";

		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
    }
	
    public static function send_notification_kyc_rejected_user($user) {
		ypcf_debug_log('NotificationsEmails::send_notification_kyc_rejected_user > ' . $user->ID);
		
		$object = "Vos documents ont été refusés";
		$body_content = "Bonjour,<br /><br />";
		$body_content .= "Suite à l'envoi de vos documents, notre partenaire de paiement Lemonway a refusé votre identification. Merci de nous contacter pour plus d'informations.<br /><br />";

		return NotificationsEmails::send_mail($user->user_email, $object, $body_content, true);
    }
    public static function send_notification_kyc_rejected_admin($user) {
		ypcf_debug_log('NotificationsEmails::send_notification_kyc_accepted_user > ' . $user->ID);
		
		$admin_email = get_option('admin_email');
		$object = "Nouveaux documents refusés";
		$body_content = "Hello !<br />";
		$body_content .= "Lemonway a refusé l'identification de l'utilisateur ".$user->first_name." ".$user->last_name." (".$user->user_login.").<br /><br />";

		return NotificationsEmails::send_mail($admin_email, $object, $body_content, true);
    }
    //*******************************************************
    // FIN NOTIFICATIONS KYC
    //*******************************************************
}
