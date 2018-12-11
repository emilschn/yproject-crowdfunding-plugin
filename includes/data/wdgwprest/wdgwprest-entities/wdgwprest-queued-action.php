<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des réponses aux sondages côté WDGWPREST
 */
class WDGWPREST_Entity_QueuedAction {
	/**
	 * Récupère des actions mises en queue
	 * @param int $poll_anwser_id
	 * @return object
	 */
	public static function get_list( $limit = FALSE, $entity_id = FALSE, $action = FALSE ) {
		$url = 'queued-actions';
		$params = array();
		if ( !empty( $limit ) ) {
			array_push( $params, 'limit=' . $limit );
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
			'params'			=> $params
		);
		WDGWPRESTLib::unset_cache( 'wdg/v1/queued-actions/?action=' .$action. '&entity_id=' .$entity_id );
		return WDGWPRESTLib::call_post_wdg( 'queued-action', $parameters );
	}
}
