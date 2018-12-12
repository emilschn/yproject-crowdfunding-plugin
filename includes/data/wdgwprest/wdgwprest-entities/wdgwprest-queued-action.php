<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des réponses aux sondages côté WDGWPREST
 */
class WDGWPREST_Entity_QueuedAction {
	private static $last_get_called;
	
	/**
	 * Récupère des actions mises en queue
	 * @param int $poll_anwser_id
	 * @return object
	 */
	public static function get_list( $limit = FALSE, $next_to_execute = FALSE, $entity_id = FALSE, $action = FALSE ) {
		$url = 'queued-actions';
		$params = array();
		if ( !empty( $limit ) ) {
			array_push( $params, 'limit=' . $limit );
		}
		if ( !empty( $next_to_execute ) ) {
			array_push( $params, 'next_to_execute=1' );
		}
		if ( !empty( $entity_id ) ) {
			array_push( $params, 'entity_id=' . $entity_id );
		}
		if ( !empty( $action ) ) {
			array_push( $params, 'action=' . $action );
		}
		if ( !empty( $params ) ) {
			$url .= '?' . implode( '&', $params );
		}
		self::$last_get_called = $url;
		return WDGWPRESTLib::call_get_wdg( $url );
	}
	
	/**
	 * Crée une action mise en queue sur l'API
	 */
	public static function create( $priority, $date_priority, $action, $entity_id, $params ) {
		$parameters = array(
			'priority'			=> $priority,
			'date_priority'		=> $date_priority,
			'action'			=> $action,
			'entity_id'			=> $entity_id,
			'params'			=> json_encode( $params )
		);
		WDGWPRESTLib::unset_cache( 'wdg/v1/queued-actions/?entity_id=' .$entity_id. '&action=' .$action );
		WDGWPRESTLib::unset_cache( 'wdg/v1/' .self::$last_get_called );
		return WDGWPRESTLib::call_post_wdg( 'queued-action', $parameters );
	}
	
	/**
	 * Modifie une action mise en queue existante sur l'API
	 */
	public static function edit( $already_existing_action_id, $status = FALSE, $priority = FALSE, $date_priority = FALSE, $action = FALSE, $entity_id = FALSE, $params = FALSE ) {
		$parameters = array();
		if ( !empty( $status ) ) {
			$parameters[ 'status' ] = $status;
		}
		if ( !empty( $priority ) ) {
			$parameters[ 'priority' ] = $priority;
		}
		if ( !empty( $date_priority ) ) {
			$parameters[ 'date_priority' ] = $date_priority;
		}
		if ( !empty( $action ) ) {
			$parameters[ 'action' ] = $action;
		}
		if ( !empty( $entity_id ) ) {
			$parameters[ 'entity_id' ] = $entity_id;
		}
		if ( !empty( $params ) ) {
			$parameters[ 'params' ] = json_encode( $params );
		}
		WDGWPRESTLib::unset_cache( 'wdg/v1/queued-action/?entity_id=' .$entity_id. '&action=' .$action );
		WDGWPRESTLib::unset_cache( 'wdg/v1/' .self::$last_get_called );
		return WDGWPRESTLib::call_post_wdg( 'queued-action/' .$already_existing_action_id, $parameters );
	}
}
