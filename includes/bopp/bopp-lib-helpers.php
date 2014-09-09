<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe de gestion de Lemonway
 */
class BoppLibHelpers {
	public static $meta_key = 'id_api';
    
//******************************************************************************************//
//GESTION UTILISATEURS
	/**
	 * Teste si un utilisateur correspondant à cet id existe déjà dans l'API
	 * @param int $wp_user_id
	 * @return int
	 */
	public static function get_api_user_id($wp_user_id) {
		$api_user_id = get_user_meta($wp_user_id, BoppLibHelpers::$meta_key, TRUE);
		if (!isset($api_user_id) || empty($api_user_id)) {
			$user_data = get_userdata($wp_user_id);
			$api_user_id = BoppLib::create_user($wp_user_id, $user_data->first_name, $user_data->last_name);
			update_user_meta($wp_user_id, BoppLibHelpers::$meta_key, $api_user_id);
		}
		return $api_user_id;
	}
//******************************************************************************************//
	
//******************************************************************************************//
//GESTION PROJETS
	/**
	 * Teste si un projet correspondant à cet id existe déjà dans l'API
	 * @param int $wp_project_id
	 * @return int
	 */
	public static function get_api_project_id($wp_project_id) {
		$api_project_id = get_post_meta($wp_project_id, BoppLibHelpers::$meta_key, TRUE);
		if (!isset($api_project_id) || empty($api_project_id)) {
			$campaign_post = get_post($wp_project_id);
			$api_project_id = BoppLib::create_project($wp_project_id, $campaign_post->post_title);
			update_post_meta($wp_project_id, BoppLibHelpers::$meta_key, $api_project_id);
		}
		return $api_project_id;
	}
//******************************************************************************************//
}
