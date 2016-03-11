<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe de gestion de Lemonway
 */
class LemonwayLibHelpers {
	
/*********************** WALLETS CAMPAIGNS ***********************/
	public static function get_formated_campaign_lemonway_id($wp_campaign_id) {
		$post_campaign = get_post($wp_campaign_id);
		return 'campaign' .$wp_campaign_id. '-user' . $post_campaign->post_author;
	}
    
	public static function get_campaign_wallet_id($wp_campaign_id) {
		$lemonway_id = LemonwayLibHelpers::get_formated_campaign_lemonway_id($wp_campaign_id);
		$lemonway_wallet_id = get_post_meta($wp_campaign_id, 'lemonway_wallet_id', true);
		if (!isset($lemonway_wallet_id) || empty($lemonway_wallet_id)) {
			LemonwayLibHelpers::register_campaign_wallet($wp_campaign_id);
		}
		return $lemonway_id;
	}
	
	public static function register_campaign_wallet($wp_campaign_id) {
		$post_campaign = get_post($wp_campaign_id);
		$user_author = get_userdata($post_campaign->post_author);
		$mobile_phone = OlageUserManager::format_french_phonenumber($user_author->get('user_mobile_phone'));
		$lemonway_wallet_id = LemonwayLib::wallet_register(LemonwayLibHelpers::get_formated_campaign_lemonway_id($wp_campaign_id), $user_author->user_email, $user_author->get('user_gender'), $user_author->user_firstname, $user_author->user_lastname, $user_author->get('user_country'), $mobile_phone);
		update_post_meta($wp_campaign_id, 'lemonway_wallet_id', $lemonway_wallet_id);
	}
	
	
/*********************** WALLETS USERS ***********************/
	public static function get_formated_user_lemonway_id($wp_user_id) {
		return 'user' . $wp_user_id;
	}
	
	public static function get_user_wallet_id($wp_user_id) {
		$success = TRUE;
		$lemonway_id = LemonwayLibHelpers::get_formated_user_lemonway_id($wp_user_id);
		$lemonway_wallet_id = get_user_meta($wp_user_id, 'lemonway_wallet_id', true);
		if (!isset($lemonway_wallet_id) || empty($lemonway_wallet_id)) {
			$success = LemonwayLibHelpers::register_user_wallet($wp_user_id);
		}
		if (!$success) { $lemonway_id = FALSE; }
		return $lemonway_id;
	}
	
	public static function register_user_wallet($wp_user_id) {
		$user_author = get_userdata($wp_user_id);
		$mobile_phone = OlageUserManager::format_french_phonenumber($user_author->get('user_mobile_phone'));
		$lemonway_wallet_id = LemonwayLib::wallet_register(LemonwayLibHelpers::get_formated_user_lemonway_id($wp_user_id), $user_author->user_email, $user_author->get('user_gender'), $user_author->user_firstname, $user_author->user_lastname, $user_author->get('user_country'), $mobile_phone);
		if (isset($lemonway_wallet_id) && !empty($lemonway_wallet_id)) {
		    update_user_meta($wp_user_id, 'lemonway_wallet_id', $lemonway_wallet_id);
		    return TRUE;
		} else {
		    return FALSE;
		}
	}
	
	
/*********************** PAYMENTS ***********************/
	public static function get_moneyin_token() {
		return rand(100000000000, 999999999999);
	}
	
	public static function set_last_transaction_id($wp_user_id, $transaction_id) {
		update_user_meta($wp_user_id, 'last_transaction_id', $transaction_id);
	}
	
	public static function get_last_transaction_id($wp_user_id) {
		return get_user_meta($wp_user_id, 'last_transaction_id', TRUE);
	}
	
	public static function set_user_card_id($wp_user_id, $card_id) {
		update_user_meta($wp_user_id, 'card_id', $card_id);
	}
	
	public static function get_user_card_id($wp_user_id) {
		update_user_meta($wp_user_id, 'card_id', TRUE);
	}
}
