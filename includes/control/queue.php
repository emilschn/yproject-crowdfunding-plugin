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
		$buffer = 0;
		$queued_action_list = WDGWPREST_Entity_QueuedAction::get_list( $number, TRUE );
		if ( !empty( $queued_action_list ) ) {
			foreach ( $queued_action_list as $queued_action ) {
				$action_name = 'execute_' . $queued_action->action;
				self::{ $action_name }( $queued_action->entity_id, json_decode( $queued_action->params ) );
				WDGWPREST_Entity_QueuedAction::edit( $queued_action->id, self::$status_complete );
				$buffer++;
			}
		}
		return $buffer;
	}
	
	
	
	
/******************************************************************************/
/* Différentes actions : ajout et exécution */
/******************************************************************************/
	public static function add_notification_royalties( $user_id ) {
		$action = 'roi_transfer_message';
		$entity_id = $user_id;
		$priority = 'date';
		$date_next_dispatch = new DateTime();
		// Les envois se font à 21h
		$date_next_dispatch->setTime( 21, 0 );
		// Si la date est avant le 6, on envoie le 6
		if ( $date_next_dispatch->format( 'd' ) < 6 ) {
			$date_next_dispatch->setDate( $date_next_dispatch->format( 'Y' ), $date_next_dispatch->format( 'm' ), 6 );
		}
		// Si la date est entre le 6 et le 10, on envoie le 10
		if ( $date_next_dispatch->format( 'd' ) > 6 && $date_next_dispatch->format( 'd' ) < 10 ) {
			$date_next_dispatch->setDate( $date_next_dispatch->format( 'Y' ), $date_next_dispatch->format( 'm' ), 10 );
		}
		// Si la date est entre le 10 et le 15, on envoie le 15
		if ( $date_next_dispatch->format( 'd' ) > 10 && $date_next_dispatch->format( 'd' ) < 15 ) {
			$date_next_dispatch->setDate( $date_next_dispatch->format( 'Y' ), $date_next_dispatch->format( 'm' ), 15 );
		}
		// Faut-il décaler sur un jour ouvré si ça tombe le samedi / dimanche ?
		
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array();
		
		self::create_or_replace_action( $action, $entity_id, $priority, $date_priority, $params );
	}
	
	public static function execute_roi_transfer_message( $user_id, $queued_action_params ) {
		$date_now = new DateTime();
		
		// On récupère la liste des investissements triés par projet de l'utilisateur pour les séparer entre :
		// - royalties reçues ce trimestre
		// - pas de royalties reçues
		// - pas de déclarations faites
		// - déclarations pas commencées
		$message_categories = array(
			'with_royalties'	=> array(),
			'without_royalties'	=> array(),
			'not_transfered'	=> array(),
			'not_started'		=> array()
		);
		
		$WDGOrganization = WDGOrganization::is_user_organization( $user_id ) ? new WDGOrganization( $user_id ) : FALSE;
		$WDGUser = empty( $WDGOrganization ) ? new WDGUser( $user_id ) : FALSE;
		$recipient_email = empty( $WDGOrganization ) ? $WDGUser->get_email() : $WDGOrganization->get_email();
		$recipient_name = empty( $WDGOrganization ) ? $WDGUser->get_firstname() : $WDGOrganization->get_name();
		$validated_investments = empty( $WDGOrganization ) ? $WDGUser->get_validated_investments() : $WDGOrganization->get_validated_investments();
		
		foreach ( $validated_investments as $campaign_id => $campaign_investments ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$amount_royalties = 0;
			$has_declared = FALSE;
			
			$campaign_roi_list = ( empty( $WDGOrganization ) ) ? $WDGUser->get_royalties_by_campaign_id( $campaign_id ) : $WDGOrganization->get_royalties_by_campaign_id( $campaign_id );
			foreach ( $campaign_roi_list as $campaign_roi ) {
				$date_transfer = new DateTime( $campaign_roi->date_transfer );
				if ( $date_transfer->format( 'm' ) == $date_now->format( 'm' ) && $date_transfer->format( 'Y' ) == $date_now->format( 'Y' ) ) {
					$amount_royalties += $campaign_roi->amount;
					$has_declared = TRUE;
				}
			}
			
			if ( $has_declared ) {
				if ( $amount_royalties > 0 ) {
					array_push( $message_categories[ 'with_royalties' ], array(
						'campaign_name'		=> $campaign->get_name(),
						'amount_royalties'	=> $amount_royalties
					) );
				} else {
					array_push( $message_categories[ 'without_royalties' ], $campaign->get_name() );
				}
				
			} else {
				$campaign_first_declaration = new DateTime( $campaign->first_payment_date() );
				if ( $date_now < $campaign_first_declaration && ( $date_now->format( 'Y' ) != $campaign_first_declaration->format( 'Y' ) || $date_now->format( 'm' ) != $campaign_first_declaration->format( 'm' ) ) ) {
					array_push( $message_categories[ 'not_started' ], array(
						'campaign_name'	=> $campaign->get_name(),
						'date_start'	=> $campaign_first_declaration->format( 'd/m/Y' )
					) );
					
				} else {
					$campaign_declarations = WDGROIDeclaration::get_list_by_campaign_id( $campaign_id );
					foreach ( $campaign_declarations as $campaign_declaration ) {
						if ( $campaign_declaration->status != WDGROIDeclaration::$status_finished ) {
							$date_due = new DateTime( $campaign_declaration->date_due );
							if ( $date_now->format( 'Y' ) == $date_due->format( 'Y' ) && $date_now->format( 'm' ) == $date_due->format( 'm' ) ) {
								array_push( $message_categories[ 'not_transfered' ], $campaign->get_name() );
							}
						}
					}
				}
				
			}
			
		}
		
		$message = "";
		
		/**
		 * 
		Ces entreprises vous ont versé des royalties :
		- Good Power (0,32 €)
		- Twiza (3,50 €)
		 */
		if ( !empty( $message_categories[ 'with_royalties' ] ) ) {
			$message .= "<b>Ces entreprises vous ont versé des royalties :</b><br>";
			foreach ( $message_categories[ 'with_royalties' ] as $campaign_params ) {
				$message .= "- " .$campaign_params['campaign_name']. " (" .YPUIHelpers::display_number( $campaign_params['amount_royalties'] ). " €)<br>";
			}
			$message .= "<br>";
		}
		
		/**
		 * 
		Ces entreprises ne vous ont pas versé de royalties :
		- DKodes
		- Wattsplan
		 */
		if ( !empty( $message_categories[ 'without_royalties' ] ) ) {
			$message .= "<b>Ces entreprises ne vous ont pas versé de royalties :</b><br>";
			foreach ( $message_categories[ 'without_royalties' ] as $campaign_name ) {
				$message .= "- " .$campaign_name. "<br>";
			}
			$message .= "<br>";
		}
		
		/**
		 * 
		Les versements de ces entreprises sont en attente :
		- Nkita
		- Listo
		 */
		if ( !empty( $message_categories[ 'not_transfered' ] ) ) {
			$message .= "<b>Les versements de ces entreprises sont en attente :</b><br>";
			foreach ( $message_categories[ 'not_transfered' ] as $campaign_name ) {
				$message .= "- " .$campaign_name. "<br>";
			}
			$message .= "<br>";
		}
		
		/**
		 * 
		Ces entreprises ne sont pas encore entrées dans la phase de déclaration :
		- La charette (démarre le 10/04/2019)
		 */
		if ( !empty( $message_categories[ 'not_started' ] ) ) {
			$message .= "<b>Ces entreprises ne sont pas encore entrées dans la phase de déclaration :</b><br>";
			foreach ( $message_categories[ 'not_started' ] as $campaign_params ) {
				$message .= "- " .$campaign_params['campaign_name']. " (démarre le " .$campaign_params['date_start']. ")<br>";
			}
			$message .= "<br>";
		}
		
		if ( !empty( $message ) ) {
			NotificationsAPI::roi_transfer_daily_resume( $recipient_email, $recipient_name, $message );
		}
	}
	
}