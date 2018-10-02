<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsSlack {
    private static $channel_notifications = "wdg-notifications";
	
	private static $icon_bell = ':bell:';
	private static $icon_hug = ':hugging_face:';
	private static $icon_doc = ':notebook:';
	private static $icon_wallet = ':moneybag:';
	private static $icon_money = ':euro:';
	private static $icon_fireworks = ':fireworks:';
	private static $icon_rocket = ':rocket:';
	private static $icon_sign = ':black_nib:';
    
    public static function send($url, $room, $message, $icon = ':bell:') {
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
		ypcf_debug_log( 'NotificationsSlack::send > ' . print_r( $result, true ) . ' ; ' . print_r( $error, true ) . ' ; ' . print_r( $errorno, true ) );
	    curl_close($ch);
    }
	
	public static function send_to_notifications( $message, $icon ) {
	    if (!defined( 'YP_SLACK_WEBHOOK_URL')) { return; }
	    
	    NotificationsSlack::send( YP_SLACK_WEBHOOK_URL, NotificationsSlack::$channel_notifications, $message, $icon );
	}
	
	public static function send_new_user( $wp_user_id ) {
		$user_data = get_userdata( $wp_user_id );
		$message = "Nouvel utilisateur : " . $user_data->user_login . ' (' . $wp_user_id . ') => ' . $user_data->user_email;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_hug );
	}
	
	public static function send_new_doc_status( $message ) {
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_doc );
	}
	
	public static function send_new_wallet_status( $wallet_id, $wallet_url, $wallet_name, $status ) {
		$message = 'Changement de statut pour porte-monnaie : ' . $wallet_id . ' ('.$wallet_name.' - ' .$wallet_url. ') => ' .$status;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_wallet );
	}
	
	public static function send_new_investment( $project_name, $amount, $investor_email ) {
		global $contract_filename;
		$message = 'Nouvel investissement sur le projet ' . $project_name . ' : '.$amount.' € par ' .$investor_email;
		if ( !empty( $contract_filename ) ) {
			$message .= "\n";
			$message .= "Lien vers le contrat : " .$contract_filename;
		}
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_money );
	}
	
	public static function send_new_project( $campaign_id, $orga_name ) {
		$post_campaign = get_post($campaign_id);
		$project_title = $post_campaign->post_title;
		$user_author = get_user_by('id', $post_campaign->post_author);
		$user_phone = get_user_meta( $post_campaign->post_author, 'user_mobile_phone', TRUE );
		
		$message = "Nouveau projet ! @channel\n";
		$message .= "Nom : " .$project_title. "\n";
		$message .= "URL : " .get_permalink($campaign_id). "\n";
		$message .= "Porté par : ".$user_author->first_name." ".$user_author->last_name." (".$user_author->user_login.")\n";
		$message .= "Mail : ".$user_author->user_email. "\n";
		$message .= "Tel : ".$user_phone. "\n";
		$message .= "Organisation : ".$orga_name. "\n";
		
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_fireworks );
	}
	
	public static function send_new_project_status( $campaign_id, $status ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$status_str = "évaluation";
		if ( $status == ATCF_Campaign::$campaign_status_collecte ) {
			$status_str = "collecte";
		}
		
		$message = "Un projet change d'étape ! @channel\n";
		$message .= "Nom : " .$campaign->data->post_title. "\n";
		$message .= "Nouvelle étape : " .$status_str;
		
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_rocket );
	}
	
	public static function send_new_project_mandate( $orga_id ) {
		$WDGOrganization = new WDGOrganization( $orga_id );
		
		$message = $WDGOrganization->get_name(). " a signé l'autorisation de prélèvement @channel";
		
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_sign );
	}
	
    
}
