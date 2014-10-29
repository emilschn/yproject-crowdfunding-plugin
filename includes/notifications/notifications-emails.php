<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsEmails {
    /**
     * Fonction générale d'envoi de mail
     * @param string $to
     * @param string $object
     * @param string $content
     * @return bool
     */
    public static function send_mail($to, $object, $content) {
	$from_name = get_bloginfo('name');
	$from_email = get_option('admin_email');
	$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";
	
	$content = edd_get_email_body_header() . $content . edd_get_email_body_footer();

	return wp_mail( $to, $object, $content, $headers );
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
    public static function new_purchase_user_success($payment_id, $code) {
	ypcf_debug_log('NotificationsEmails::new_purchase_user_success > ' . $payment_id);
	$particular_content = "Il vous reste encore à signer le contrat que vous devriez recevoir de la part de notre partenaire Signsquid ";
	$particular_content .= "(<strong>Pensez à vérifier votre courrier indésirable</strong>).<br />";
	$particular_content .= "Votre code personnel pour signer le contrat : <strong>" . $code . "</strong>";
	return NotificationsEmails::new_purchase_user($payment_id, $particular_content);
    }
    
    /**
     * Mail pour l'investisseur lors d'un achat
     * @param int $payment_id
     * @param string $particular_content
     * @return bool
     */
    public static function new_purchase_user($payment_id, $particular_content) {
	ypcf_debug_log('NotificationsEmails::new_purchase_user > ' . $payment_id);
	$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);

	$payment_data = edd_get_payment_meta( $payment_id );
	$payment_amount = edd_get_payment_amount( $payment_id );
	$user_info = maybe_unserialize( $payment_data['user_info'] );
	$email = $payment_data['email'];
	$user_data = get_user_by('email', $email);

	$object = "Merci pour votre investissement";
	$body_content = '';
	$dear_str = ( isset( $user_info['gender'] ) && $user_info['gender'] == "female") ? "Chère" : "Cher";
	$body_content = $dear_str." ".$user_data->first_name . " " . $user_data->last_name.",<br /><br />";
	$body_content .= $post_campaign->post_title . " vous remercie pour votre investissement. N'oubliez pas qu'il ne sera définitivement validé ";
	$body_content .= "que si le projet atteint son seuil minimal de financement. N'hésitez donc pas à en parler autour de vous et sur les réseaux sociaux !<br /><br />";
	$body_content .= $particular_content . "<br /><br />";
	
	$body_content .= "<strong>Détails de l'investissement</strong><br />";
	$body_content .= "Projet : " . $post_campaign->post_title . "<br />";
	$body_content .= "Montant investi : ".$payment_amount."€<br />";
	$body_content .= "Horodatage : ". get_post_field( 'post_date', $payment_id ) ."<br /><br />";
	
	return NotificationsEmails::send_mail($email, $object, $body_content);
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
	$emails .= BoppLibHelpers::get_project_members_mail_list($post_campaign->ID);
	
	$object = "Nouvel investissement";
	
	$payment_data = edd_get_payment_meta( $payment_id );
	$payment_amount = edd_get_payment_amount( $payment_id );
	$email = $payment_data['email'];
	$user_data = get_user_by('email', $email);
	
	$body_content = "Une nouvelle personne a investi sur votre projet ".$post_campaign->post_title.":<br />";
	$body_content .= $user_data->user_firstname . " " . $user_data->user_lastname . " a investi ".$payment_amount." €.<br />";
	$body_content .= "Votre projet a atteint ".$campaign->percent_minimum_completed()." de son objectif, soit ".$campaign->current_amount()." sur ".$campaign->minimum_goal(true).".";
	
	return NotificationsEmails::send_mail($emails, $object, $body_content);
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
     * Mail à l'admin lors d'un achat réussi
     * @param int $payment_id
     * @param string $complement_object
     * @param string $complement_content
     * @return bool
     */
    public static function new_purchase_admin_success($payment_id, $complement_object = '', $complement_content = '') {
	ypcf_debug_log('NotificationsEmails::new_purchase_admin_success > ' . $payment_id);
	$admin_email = get_option('admin_email');
	$object = 'Nouvel achat' . $complement_object;
	
	$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
	$payment_amount = edd_get_payment_amount( $payment_id );
	$user_id = edd_get_payment_user_id( $payment_id );
	$user_data = get_userdata($user_id);
	$payment_date = get_post_field( 'post_date', $payment_id );

	$body_content = 'Nouvel investissement avec l\'identifiant de paiement ' . $payment_id . '<br /><br />';
	$body_content .= "<strong>Détails de l'investissement</strong><br />";
	$body_content .= "Utilisateur : " . $user_data->user_login . "<br />";
	$body_content .= "Projet : " . $post_campaign->post_title . "<br />";
	$body_content .= "Montant investi : ".$payment_amount."€<br />";
	$body_content .= "Horodatage : ". $payment_date ."<br /><br />";
	$body_content .= $complement_content;
	
	return NotificationsEmails::send_mail($admin_email, $object, $body_content);
    }
    //*******************************************************
    // FIN ACHATS
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
    public static function new_project_posted($campaign_id, $copy_recipient) {
	$admin_email = get_option('admin_email');
	$to = $admin_email . ',' . $copy_recipient;
	
	$post_campaign = get_post($campaign_id);
	$project_title = $post_campaign->post_title;
	$object = '[Nouveau Projet] '. $project_title;
	$body_content = "Un nouveau projet viens d'être publié.<br />";
	$body_content .= "Il est accessible depuis le back-office :<br />";
	$body_content .= '<a href="'. get_permalink($campaign_id) .'" target="_blank">'. $project_title .'</a>';
	
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
	
	return NotificationsEmails::send_mail($user->user_email, $object, $body_content);
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
	
	$body_content = "Vous avez reçu un nouveau commentaire sur votre projet ".$post_campaign->post_title." :<br />";
	$body_content .= $comment_object->comment_content . "<br /><br />";
	$body_content .= 'Pour y répondre, suivez ce lien : <a href="'.get_permalink($post_parent->ID).'">'.$post_parent->post_title.'</a>.';
	
	$user = get_userdata($post_campaign->post_author);
		
	return NotificationsEmails::send_mail($user->user_email, $object, $body_content);
    }
    //*******************************************************
    // FIN NOUVEAU COMMENTAIRE
    //*******************************************************
}
