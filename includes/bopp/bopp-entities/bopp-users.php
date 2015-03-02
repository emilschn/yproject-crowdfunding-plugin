<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des utilisateurs côté API
 */
class BoppUsers {
	/**
	 * retourne la liste des paramètres
	 * @return string
	 */
	public static function empty_params() {
		$params = array(
			'user_wp_id' => '---',
			'user_gender' => '---',
			'user_name' => '---',
			'user_surname' => '---',
			'user_username' => '---',
			'user_birthday_date' => '---', 'user_birthday_city' => '---',
			'user_address' => '---', 'user_postal_code' => '0', 'user_city' => '---',
			'user_email' => '---',
			'user_linkedin_url' => '---', 'user_twitter_url' => '---', 'user_facebook_url' => '---', 'user_viadeo_url' => '---',
			'user_picture_url' => '---',
			'user_website_url' => '---',
			'user_password' => '---', 'user_activation_key' => '---',
			'user_signup_date' => '---'
		);
		return $params;
	}
    
	/**
	 * Crée un utilisateur sur l'API
	 * @param string $first_name
	 * @param string $last_name
	 * @return object
	 */
	public static function create($wp_user_id, $first_name, $last_name) {
		$default_params = BoppUsers::empty_params();
		$default_params['user_wp_id'] = $wp_user_id;
		$default_params['user_name'] = $first_name;
		$default_params['user_surname'] = $last_name;
		
		$date = new DateTime("NOW");
		$default_params['user_birthday_date'] = array(
			"year" => $date->format('Y'),
			"month" => $date->format('m'),
			"day" => $date->format('d')
		);
		$default_params['user_signup_date'] = array(
			"date" => array(
				"year"	=> $date->format('Y'),
				"month" => $date->format('m'),
				"day"	=> $date->format('d')
			),
			"time" => array(
				"hour"	=> $date->format('H'),
				"minute"=> $date->format('i'),
			)
		);
		
		$request_params = array(
			'user' => $default_params
		);
		
		$result_obj = BoppLib::call_post('users', $request_params);
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Retourne un utilisateur à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get($id) {
		return BoppLib::call_get('users/' . $id);
	}
	
	/**
	 * Mise à jour de l'utilisateur à partir d'un id
	 * @param int $id
	 * @param string $first_name
	 * @param string $last_name
	 * @return object
	 */
	public static function update($id, $first_name, $last_name) {
		$default_params = BoppUsers::empty_params();
		$default_params['user_name'] = $first_name;
		$default_params['user_surname'] = $last_name;
		$request_params = array(
			'user' => $default_params
		);
		
		$result_obj = BoppLib::call_put('users/' . $id, $request_params);
		return $result_obj;
	}
	
	/**
	 * Récupère la liste des projets auxquels est lié un utilisateur par un certain rôle
	 * @param int $id
	 * @param string $role_slug
	 * @return object
	 */
	public static function get_projects_by_role($id, $role_slug) {
		$project_list = BoppLib::call_get('users/' . $id . '/roles/' . $role_slug.'/projects');
		if (isset($project_list->code)) { $project_list = array(); }
		return $project_list;
	}

	/**
	 * Récupère la liste des organisations auxquelles est lié un utilisateur par un certain rôle
	 * @param type $id
	 * @param type $role_slug
	 */
	public static function get_organisations_by_role($id, $role_slug) {
		if (!empty($id) && !empty($role_slug)) {
			$organisation_list = BoppLib::call_get('users/' . $id . '/roles/' . $role_slug.'/organisations');
			if (isset($organisation_list->code)) { $organisation_list = array(); }
		} else {
			$organisation_list = array();
		}
		return $organisation_list;
	}
}
