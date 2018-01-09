<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class UIHelpers {
	/**
	 * 
	 * @global type $WDG_cache_plugin
	 * @global type $facebook_infos
	 * @global type $twitter_infos
	 */
	public static function init_social_infos() {
		global $WDG_cache_plugin, $facebook_infos, $twitter_infos;
	    
		// Récupération des infos Facebook
		$fb_cache_result = $WDG_cache_plugin->get_cache('facebook-count');
		if ($fb_cache_result === FALSE) {
			$facebook = new Facebook(array(
				'appId'  => YP_FB_APP_ID,
				'secret' => YP_FB_SECRET,
			));
			$fb_infos = $facebook->api(YP_FB_URL); 
			if ($fb_infos) $facebook_infos = $fb_infos['likes'];
			$WDG_cache_plugin->set_cache('facebook-count', $facebook_infos, 60*60*24);
		} else {
			$facebook_infos = $fb_cache_result;
		}

		// Récupération des infos Twitter
		$twitter_cache_result = $WDG_cache_plugin->get_cache('twitter-count');
		if ($twitter_cache_result === FALSE) {
			$apiUrl = "https://api.twitter.com/1.1/users/show.json";
			$requestMethod = 'GET';
			$getField = '?screen_name=wedogood_co';
			$settings = array(
				'oauth_access_token'	    => YP_TW_oauth_access_token,
				'oauth_access_token_secret' => YP_TW_oauth_access_token_secret,
				'consumer_key'		    => YP_TW_consumer_key,
				'consumer_secret'	    => YP_TW_consumer_secret
			);

			$twitter = new TwitterAPIExchange($settings);
			$response = $twitter->setGetfield($getField)
					->buildOauth($apiUrl, $requestMethod)
					->performRequest();
			$followers = json_decode($response);
			if ($followers && isset($followers->followers_count)) $twitter_infos = $followers->followers_count;
			$WDG_cache_plugin->set_cache('twitter-count', $twitter_infos, 60*60*24);
		} else {
			$twitter_infos = $twitter_cache_result;
		}
	}
	
	public static function print_user_avatar($user_id, $size = 'normal') {
		echo UIHelpers::get_user_avatar($user_id, $size);
	}
	
	public static function get_user_avatar($user_id, $size = 'normal') {
		switch ($size) {
			case 'normal':
			    $width = 150;
			    break;
			case 'thumb':
			    $width = 50;
			    break;
			case 'icon':
				$width = 40;
				break;
		}

		$avatar_path = '';
		$upload_dir = wp_upload_dir();

		if ( file_exists( $upload_dir['path'] . '/avatars/' . $user_id . '/avatar.jpg' )) {
			$avatar_path = $upload_dir['baseurl'] . '/avatars/' . $user_id . '/avatar.jpg';
			return '<img src="' .$avatar_path . '" width="' . $width . '" height="' . $width . '"/>';

		} elseif (file_exists( $upload_dir['path'] . '/avatars/' . $user_id . '/avatar.png' )) {
			$avatar_path = $upload_dir['baseurl'] . '/avatars/' . $user_id . '/avatar.png';
			return '<img src="' . $avatar_path . '" width="' . $width . '" height="' . $width . '"/>';

		} else {
			$profile_type = "";
			$google_meta = get_user_meta($user_id, 'social_connect_google_id', true);
			if (isset($google_meta) && $google_meta != "") $profile_type = ""; //TODO : Remplir avec "google" quand on gÃƒÂ¨rera correctement
			$facebook_meta = get_user_meta($user_id, 'social_connect_facebook_id', true);
			if (isset($facebook_meta) && $facebook_meta != "") $profile_type = "facebook";

			$url = get_stylesheet_directory_uri() . "/images/navbar/profil-icon-par-defaut.png";
			switch ($profile_type) {
			    case "google":
					$meta_explode = explode("id?id=", $google_meta);
					$social_id = $meta_explode[1];
					$url = "http://plus.google.com/s2/photos/profile/" . $social_id . "?sz=".($width-1);
					return '<img src="' .$url . '" width="'.$width.'"/>';
				break;
			
			    case "facebook":
					if ($size == 'thumb' || $size == 'icon') {
						$size = 'square';
					}
					$url = "https://graph.facebook.com/" . $facebook_meta . "/picture?type=" . $size;
					return '<img src="' .$url . '" width="'.$width.'"/>';
				break;
				
			    default :
					return '<img src="'.$url.'" width="'.$width.'" />';
				break;
			}
		}
	}
	
	public static function format_number( $input, $decimals = 2 ) {
		return number_format( $input, $decimals, ',', ' ' );
	}
}
