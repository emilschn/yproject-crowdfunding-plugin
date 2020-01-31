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
	 * Crée un brouillon de projet sur l'API
	 * @param WDGUser $user
	 * @param String $status
	 * @param String $step
	 * @param String $authorization
	 * @param String $metadata
	 * @return object
	 */
	public static function create( WDGUser $user, $status, $step, $authorization, $metadata ) {
		if ( $user->get_wpref() == '' ) {
			return FALSE;
		}
		
		$parameters = array(
			'guid'				=> wp_generate_uuid4(),
			'id_user'			=> $user->get_wpref(),
			'email'				=> $user->get_email(),
			'status'			=> $status,
			'step'				=> $step,
			'authorization'		=> $authorization,
			'metadata'			=> $metadata,
		);
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project-draft', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
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
	public static function set_post_parameters( WDGUser $user, $guid, $status, $step, $authorization, $metadata ) {		
		$parameters = array(
			'guid'				=> $guid,
			'id_user'			=> $user->get_wpref(),
			'email'				=> $user->get_email(),
			'status'			=> $status,
			'step'				=> $step,
			'authorization'		=> $authorization,
			'metadata'			=> $metadata,
		);
		return $parameters;
	}
	
	/**
	 * Mise à jour du brouillon de projet à partir d'un guid
	 * @param WDGUser $user
	 * @param String $guid
	 * @param String $status
	 * @param String $step
	 * @param String $authorization
	 * @param String $metadata
	 * @return object
	 */
	public static function update( WDGUser $user, $guid, $status, $step, $authorization, $metadata ) {
		$buffer = FALSE;
		
		if ( !empty( $guid ) ) {
			$parameters = WDGWPREST_Entity_Project_Draft::set_post_parameters($user, $guid, $status, $step, $authorization, $metadata );

			$buffer = WDGWPRESTLib::call_post_wdg( 'project-draft/' . $guid, $parameters );
			WDGWPRESTLib::unset_cache( 'wdg/v1/project-draft/' .$guid);
			if ( isset( $buffer->code ) && $buffer->code == 400 ) { $buffer = FALSE; }
		}
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
