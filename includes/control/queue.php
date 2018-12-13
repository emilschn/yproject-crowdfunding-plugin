<?php
/**
 * Classe de gestion des queues d'actions
 */
class WDGQueue {
	private static $status_init = 'init';
	private static $status_complete = 'complete';
	
/******************************************************************************/
/* Fonctions globales nécessaires à la gestion des queues */
/******************************************************************************/
	/**
	 * Fonction globale servant à factoriser les actions pour éviter qu'elles ne se dupliquent
	 * @param string $action
	 * @param int $entity_id
	 * @param string $priority
	 * @param string $date_priority
	 * @param array $params_input
	 */
	private static function create_or_replace_action( $action, $entity_id, $priority, $date_priority, $params_input ) {
		$already_existing_action_id = FALSE;
		
		$queued_action_list = WDGWPREST_Entity_QueuedAction::get_list( FALSE, FALSE, $entity_id, $action );
		if ( !empty( $queued_action_list ) ) {
			$already_existing_action_id = $queued_action_list[ 0 ]->id;
			$already_existing_action_params = $queued_action_list[ 0 ]->params;
		}
		
		if ( empty( $already_existing_action_id ) ) {
			$params = array();
			array_push( $params, json_encode( $params_input ) );
			WDGWPREST_Entity_QueuedAction::create( $priority, $date_priority, $action, $entity_id, $params );
			
		} else {
			$params = json_decode( $already_existing_action_params );
			array_push( $params, json_encode( $params_input ) );
			WDGWPREST_Entity_QueuedAction::edit( $already_existing_action_id, FALSE, $priority, $date_priority, $action, $entity_id, $params );
		}
	}
	
	/**
	 * Fonction qui récupère les prochaines actions à exécuter et les lancent
	 * @param int $number
	 */
	public static function execute_next( $number = 5 ) {
		$queued_action_list = WDGWPREST_Entity_QueuedAction::get_list( $number, TRUE );
		if ( !empty( $queued_action_list ) ) {
			foreach ( $queued_action_list as $queued_action ) {
				$action_name = 'execute_' . $queued_action->action;
				self::{ $action_name }( json_decode( $queued_action->params ) );
				WDGWPREST_Entity_QueuedAction::edit( $queued_action->id, self::$status_complete );
			}
		}
	}
	
	
	
	
/******************************************************************************/
/* Différentes actions : ajout et exécution */
/******************************************************************************/
	public static function add_notification_royalties( $user_id, $user_email, $user_firstname, $campaign_name, $adjustment_message, $declaration_message, $replyto_mail ) {
		$action = 'notification_royalties';
		$entity_id = $user_id;
		$priority = 'date';
		$date_tonight = new DateTime();
		$date_tonight->setTime( 21, 0 );
		$date_priority = $date_tonight->format( 'Y-m-d H:i:s' );
		
		$params = array(
			'user_email'			=> $user_email,
			'user_firstname'		=> $user_firstname,
			'campaign_name'			=> $campaign_name,
			'adjustment_message'	=> $adjustment_message,
			'declaration_message'	=> $declaration_message,
			'replyto_mail'			=> $replyto_mail,
		);
		self::create_or_replace_action( $action, $entity_id, $priority, $date_priority, $params );
	}
	
	private static function execute_notification_royalties( $queued_action_params ) {
		NotificationsAPI::roi_transfer_with_royalties(
			$queued_action_params[ 'user_email' ], $queued_action_params[ 'user_firstname' ], $queued_action_params[ 'campaign_name' ], $queued_action_params[ 'adjustment_message' ], $queued_action_params[ 'declaration_message' ], $queued_action_params[ 'replyto_mail' ]
		);
	}
	
	public static function add_notification_no_royalties( $user_id, $user_email, $user_firstname, $campaign_name, $adjustment_message, $declaration_message, $replyto_mail ) {
		$action = 'notification_no_royalties';
		$entity_id = $user_id;
		$priority = 'date';
		$date_tonight = new DateTime();
		$date_tonight->setTime( 21, 0 );
		$date_priority = $date_tonight->format( 'Y-m-d H:i:s' );
		
		$params = array(
			'user_email'			=> $user_email,
			'user_firstname'		=> $user_firstname,
			'campaign_name'			=> $campaign_name,
			'adjustment_message'	=> $adjustment_message,
			'declaration_message'	=> $declaration_message,
			'replyto_mail'			=> $replyto_mail,
		);
		self::create_or_replace_action( $action, $entity_id, $priority, $date_priority, $params );
	}
	
	private static function execute_notification_no_royalties( $queued_action_params ) {
		NotificationsAPI::roi_transfer_without_royalties(
			$queued_action_params[ 'user_email' ], $queued_action_params[ 'user_firstname' ], $queued_action_params[ 'campaign_name' ], $queued_action_params[ 'adjustment_message' ], $queued_action_params[ 'declaration_message' ], $queued_action_params[ 'replyto_mail' ]
		);
	}
	
}