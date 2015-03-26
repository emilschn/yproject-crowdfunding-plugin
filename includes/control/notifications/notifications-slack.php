<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsSlack {
    private static $channel_dev = "breeze-dev-en-cours";
    
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
	    /*$result = curl_exec($ch);
	    $error = curl_error($ch);
	    $errorno = curl_errno($ch);
	    print_r($data);
	    print_r($result);
	    print_r($error);
	    print_r($errorno);*/
	    curl_close($ch);
    }
    
    public static function send_to_dev($message, $icon = ':bell:') {
	    if (!defined( 'YP_SLACK_DEV_WEBHOOK_URL')) { return; }
	    
	    NotificationsSlack::send(YP_SLACK_DEV_WEBHOOK_URL, NotificationsSlack::$channel_dev, $message, $icon);
    }
    
}
