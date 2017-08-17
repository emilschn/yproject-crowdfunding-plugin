<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des utilisateurs côté WDGWPREST
 */
class WDGWPREST_Entity_User {
	
	/**
	 * Retourne un utilisateur à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'user/' . $id );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGUser $user
	 * @return array
	 */
	public static function set_post_parameters( WDGUser $user ) {
		$parameters = array(
			'wpref'				=> $user->get_wpref(),
			'gender'			=> $user->get_gender(),
			'name'				=> $user->get_firstname(),
			'surname'			=> $user->get_lastname(),
			'username'			=> $user->get_login(),
			'birthday_date'		=> $user->get_birthday_date(),
			'birthday_city'		=> '---',
			'address'			=> '---',
			'postalcode'		=> '---',
			'city'				=> '---',
			'email'				=> '---',
			'picture_url'		=> '---',
			'website_url'		=> '---',
			'twitter_url'		=> '---',
			'facebook_url'		=> '---',
			'linkedin_url'		=> '---',
			'viadeo_url'		=> '---',
			'activation_key'	=> '---',
			'password'			=> '---',
			'signup_date'		=> '---'
		);
		return $parameters;
	}
	
	/**
	 * Crée un utilisateur sur l'API
	 * @param WDGUser $user
	 * @return object
	 */
	public static function create( WDGUser $user ) {
		$parameters = WDGWPREST_Entity_User::set_post_parameters( $user );
		$date = new DateTime("NOW");
		$parameters['signup_date'] = $date->format('Y') .'-'. $date->format('m') .'-'. $date->format('d');
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'user', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour de l'utilisateur à partir d'un id
	 * @param WDGUser $user
	 * @return object
	 */
	public static function update( WDGUser $user ) {
		$parameters = WDGWPREST_Entity_User::set_post_parameters( $user );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'user/' . $user->get_api_id(), $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Retourne la liste des organisations d'un utilisateur
	 * @param int $user_id
	 * @return array
	 */
	public static function get_organizations( $user_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'user/' .$user_id. '/organizations' );
		return $result_obj;
	}
	
	/**
	 * Retourne la liste des organisations d'un utilisateur, filtrées par un role
	 * @param int $user_id
	 * @param string $role_slug
	 * @return array
	 */
	public static function get_organizations_by_role( $user_id, $role_slug ) {
		$buffer = array();
		$organization_list = WDGWPREST_Entity_User::get_organizations( $user_id );
		if ( $organization_list ) {
			foreach ( $organization_list as $organization ) {
				if ( $organization->type == $role_slug ) {
					array_push( $buffer, $organization );
				}
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne la liste des projets d'un utilisateur
	 * @param int $user_id
	 * @return array
	 */
	public static function get_projects( $user_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'user/' .$user_id. '/projects' );
		return $result_obj;
	}
	
	/**
	 * Retourne la liste des projets d'un utilisateur, filtrées par un role
	 * @param int $user_id
	 * @param string $role_slug
	 * @return array
	 */
	public static function get_projects_by_role( $user_id, $role_slug ) {
		$buffer = array();
		$project_list = WDGWPREST_Entity_User::get_projects( $user_id );
		if ( $project_list ) {
			foreach ( $project_list as $project ) {
				if ( $project->type == $role_slug ) {
					array_push( $buffer, $project );
				}
			}
		}
		return $buffer;
	}
}
