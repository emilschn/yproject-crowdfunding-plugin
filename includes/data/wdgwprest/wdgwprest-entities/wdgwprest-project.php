<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des projets côté WDGWPREST
 */
class WDGWPREST_Entity_Project {
	
	public static $link_user_type_member = 'team-member';
	
	/**
	 * Retourne un projet à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_wdg( 'project/' . $id );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param ATCF_Campaign $campaign
	 * @return array
	 */
	public static function set_post_parameters( ATCF_Campaign $campaign ) {
		$parameters = array(
			'wpref'				=> $campaign->ID,
			'name'				=> $campaign->data->post_title
		);
		return $parameters;
	}
	
	/**
	 * Crée un projet sur l'API
	 * @param ATCF_Campaign $campaign
	 * @return object
	 */
	public static function create( ATCF_Campaign $campaign ) {
		$parameters = WDGWPREST_Entity_Project::set_post_parameters( $campaign );
		$date = new DateTime("NOW");
		$parameters['creation_date'] = $date->format('Y') .'-'. $date->format('m') .'-'. $date->format('d');
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour du projet à partir d'un id
	 * @param ATCF_Campaign $campaign
	 * @return object
	 */
	public static function update( ATCF_Campaign $campaign ) {
		$parameters = WDGWPREST_Entity_Project::set_post_parameters( $campaign );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project/' . $campaign->get_api_id(), $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Retourne la liste des utilisateurs liés au projet
	 * @param int $project_id
	 * @return array
	 */
	public static function get_users( $project_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/users' );
		return $result_obj;
	}
	
	/**
	 * Retourne la liste des utilisateurs liés au projet, filtrés selon leur rôle
	 * @param int $project_id
	 * @param string $role_slug
	 * @return array
	 */
	public static function get_users_by_role( $project_id, $role_slug ) {
		$buffer = array();
		$user_list = WDGWPREST_Entity_Project::get_users( $project_id );
		foreach ( $user_list as $user ) {
			if ( $user->type == $role_slug ) {
				array_push( $buffer, $user );
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne une chaine avec la liste des e-mails des utilisateurs liés à un projet
	 * @param int $project_id
	 * @param string $role_slug
	 * @return string
	 */
	public static function get_users_mail_list_by_role( $project_id, $role_slug ) {
		$emails = '';
		$user_list = WDGWPREST_Entity_Project::get_users_by_role( $project_id, $role_slug );
		foreach ( $user_list as $user ) {
			$user_data = get_userdata( $user->wpref );
			$emails .= ',' . $user_data->user_email;
		}
		return $emails;
	}

	/**
	 * Lie un utilisateur à un projet en définissant son rôle
	 * @param int $project_id
	 * @param int $user_id
	 * @param string $role_slug
	 * @return object
	 */
	public static function link_user( $project_id, $user_id, $role_slug ) {
		$request_params = array(
			'id_user' => $user_id,
			'type' => $role_slug
		);
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project/' .$project_id. '/users', $request_params );
		return $result_obj;
	}

	/**
	 * Supprime la liaison d'un utilisateur à un projet en définissant son rôle
	 * @param int $project_id
	 * @param int $user_id
	 * @param string $role_slug
	 * @return object
	 */
	public static function unlink_user( $project_id, $user_id, $role_slug ) {
		/*$request_params = array(
			'id_user' => $user_id,
			'type' => $role_slug
		);*/
		$result_obj = WDGWPRESTLib::call_delete_wdg( 'project/' .$project_id. '/user/' .$user_id. '/type/' .$role_slug );
		return $result_obj;
	}
}
