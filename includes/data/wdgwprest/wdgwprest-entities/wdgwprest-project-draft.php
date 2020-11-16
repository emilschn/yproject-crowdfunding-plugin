<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des brouillons de projets côté WDGWPREST
 */
class WDGWPREST_Entity_Project_Draft {

	/**
	 * Retourne un brouillon de projet à partir d'un guid
	 * @param string $guid
	 * @return object
	 */
	public static function get( $guid ) {
		if ( empty( $guid ) ) {
			return FALSE;
		}
		return WDGWPRESTLib::call_get_wdg( 'project-draft/' .$guid );
	}

	/**
	 * Retourne un brouillon de projet à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get_by_id( $id ) {
		if ( empty( $id ) ) {
			return FALSE;
		}
		return WDGWPRESTLib::call_get_wdg( 'project-draft/id/' .$id );
	}
		
	/**
	 * Retourne la liste de brouillons de projets créés par un e-mail
	 * @param string $email
	 * @return object
	 */
	public static function get_list_by_email( $email ) {
		if ( empty( $email ) ) {
			return FALSE;
		}
		return WDGWPRESTLib::call_get_wdg( 'project-drafts/' .$email );
	}

	/**
	 * Retourne les brouillons de projet associés à un e-mail
	 */
	public static function get_by_user_email( $user_email ) {
		ypcf_debug_log( 'get_by_user_email > user_email : ' . $user_email, false );
		if ( empty( $user_email ) ) {
			return FALSE;
		}
		return WDGWPRESTLib::call_get_wdg( 'project-drafts/' .$user_email );
	}

	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGUser $user
	 * @param String $guid
	 * @param String $status
	 * @param String $step
	 * @param String $authorization
	 * @param String $metadata
	 * @return array
	 */
	public static function set_post_parameters( $guid, $user_id, $user_email, $status, $step, $authorization, $metadata ) {
		if ( empty( $guid ) ) {
			$guid = wp_generate_uuid4();
		}
		if ( !empty( $user_email ) && empty( $user_id ) ) {
			$wp_user = get_user_by( 'email', $user_email );
			$WDGUser = new WDGUser( $wp_user->ID );
			$user_id = $WDGUser->get_api_id();
		}

		$parameters = array(
			'guid'				=> $guid,
			'id_user'			=> $user_id,
			'email'				=> $user_email,
			'status'			=> $status,
			'step'				=> $step,
			'authorization'		=> $authorization,
			'metadata'			=> $metadata
		);
		return $parameters;
	}
	
	/**
	 * Crée un brouillon de projet sur l'API
	 * @param WDGUser $user
	 * @param String $status
	 * @param String $step
	 * @param String $authorization
	 * @param String $metadata
	 * @return object
	 */
	public static function create( $user_id, $user_email, $status, $step, $authorization, $metadata ) {
		if ( empty( $user_email ) ) {
			return FALSE;
		}

		$parameters = WDGWPREST_Entity_Project_Draft::set_post_parameters( FALSE, $user_id, $user_email, $status, $step, $authorization, $metadata );
		$buffer = WDGWPRESTLib::call_post_wdg( 'project-draft', $parameters );
		if ( isset( $buffer->code ) && $buffer->code == 400 ) { $buffer = FALSE; }

		return $buffer;
	}
	
	/**
	 * Mise à jour du brouillon de projet à partir d'un guid
	 * @param String $guid
	 * @param String $user_id
	 * @param String $user_email
	 * @param String $status
	 * @param String $step
	 * @param String $authorization
	 * @param String $metadata
	 * @return object
	 */
	public static function update( $guid, $user_id, $user_email, $status, $step, $authorization, $metadata ) {
		if ( empty( $guid ) || empty( $user_email ) ) {
			return FALSE;
		}

		$parameters = WDGWPREST_Entity_Project_Draft::set_post_parameters( $guid, $user_id, $user_email, $status, $step, $authorization, $metadata );
		$buffer = WDGWPRESTLib::call_post_wdg( 'project-draft/' . $guid, $parameters );
		WDGWPRESTLib::unset_cache( 'wdg/v1/project-draft/' . $guid );
		if ( isset( $buffer->code ) && $buffer->code == 400 ) { $buffer = FALSE; }
			
		return $buffer;
	}

	/**
	 * Mise à jour d'une donnée particulière d'un projet
	 * @param string $guid
	 * @param string $data_name
	 * @param string $data_value
	 * @return string
	 */
	public static function update_data( $guid, $data_name, $data_value ) {
		$parameters = array();
		$parameters[ $data_name ] = $data_value;
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project-draft/' . $guid, $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	public static function get_status( $guid ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project-draft/' .$guid. '/status' );
		return $result_obj;
	}
}
