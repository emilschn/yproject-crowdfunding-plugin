<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsSlack {
    private static $channel_notifications = "wdg-notifications";
	
	private static $icon_bell = ':bell:';
	private static $icon_doc = ':notebook:';
	private static $icon_wallet = ':moneybag:';
	private static $icon_money = ':euro:';
    
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
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_bell );
	}
	
	public static function send_new_doc_status( $message ) {
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_doc );
	}
	
	public static function send_new_wallet_status( $wallet_id, $wallet_url, $wallet_name, $status ) {
		$message = 'Changement de statut pour porte-monnaie : ' . $wallet_id . ' ('.$wallet_name.' - ' .$wallet_url. ') => ' .$status;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_wallet );
	}
	
	public static function send_new_investment( $project_name, $amount, $investor_email ) {
		$message = 'Nouvel investissement sur le projet ' . $project_name . ' : '.$amount.' € par ' .$investor_email;
		NotificationsSlack::send_to_notifications( $message, NotificationsSlack::$icon_money );
	}
	
    
}
