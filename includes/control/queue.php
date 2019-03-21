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
	private static function create_or_replace_action( $action, $entity_id, $priority, $params_input = array(), $date_priority = '' ) {
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
				self::{ $action_name }( $queued_action->entity_id, json_decode( $queued_action->params ), $queued_action->id );
				WDGWPREST_Entity_QueuedAction::edit( $queued_action->id, self::$status_complete );
				$buffer++;
			}
		}
		return $buffer;
	}
	
	
	
	
/******************************************************************************/
/* Différentes actions : ajout et exécution */
/******************************************************************************/

	
/******************************************************************************/
/* NOTIFS ROYALTIES */
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
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_roi_transfer_message( $user_id, $queued_action_params, $queued_action_id ) {
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
			$campaign_organization = $campaign->get_organization();
			if ( !empty( $campaign_organization ) ) {
				$campaign_organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
				$campaign_name = $campaign_organization_obj->get_name() . ' (Levée de fonds "' .$campaign->get_name(). '")';
			} else {
				$campaign_name = $campaign->get_name();
			}
			
			if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded || $campaign->campaign_status() == ATCF_Campaign::$campaign_status_closed ) {
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
					array_push( $message_categories[ 'with_royalties' ], array(
						'campaign_name'		=> $campaign_name,
						'amount_royalties'	=> $amount_royalties
					) );

				} else {
					$campaign_first_declaration = new DateTime( $campaign->first_payment_date() );
					if ( $date_now < $campaign_first_declaration && ( $date_now->format( 'Y' ) != $campaign_first_declaration->format( 'Y' ) || $date_now->format( 'm' ) != $campaign_first_declaration->format( 'm' ) ) ) {
						array_push( $message_categories[ 'not_started' ], array(
							'campaign_name'	=> $campaign_name,
							'date_start'	=> $campaign_first_declaration->format( 'd/m/Y' )
						) );

					} else {
						$campaign_declarations = WDGROIDeclaration::get_list_by_campaign_id( $campaign_id );
						foreach ( $campaign_declarations as $campaign_declaration ) {
							if ( $campaign_declaration->status != WDGROIDeclaration::$status_finished ) {
								$date_due = new DateTime( $campaign_declaration->date_due );
								if ( $date_now->format( 'Y' ) == $date_due->format( 'Y' ) && $date_now->format( 'm' ) == $date_due->format( 'm' ) ) {
									array_push( $message_categories[ 'not_transfered' ], $campaign_name );
								}
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
				$message .= "- " .$campaign_params['campaign_name']. " : " .YPUIHelpers::display_number( $campaign_params['amount_royalties'] ). " €<br>";
			}
			$message .= "<br>";
		}
		
		/**
		 * 
		Ces entreprises ne vous ont pas versé de royalties :
		- DKodes
		- Wattsplan
		if ( !empty( $message_categories[ 'without_royalties' ] ) ) {
			$message .= "<b>Ces entreprises ne vous ont pas versé de royalties :</b><br>";
			foreach ( $message_categories[ 'without_royalties' ] as $campaign_name ) {
				$message .= "- " .$campaign_name. "<br>";
			}
			$message .= "<br>";
		}
		 */
		
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

	
/******************************************************************************/
/* VALIDATION PREINVESTISSEMENTS */
/******************************************************************************/
	public static function add_preinvestments_validation( $campaign_id ) {
		$action = 'preinvestments_validation';
		$entity_id = $campaign_id;
		$priority = 'high';
		
		self::create_or_replace_action( $action, $entity_id, $priority );
	}
	
	public static function execute_preinvestments_validation( $campaign_id, $queued_action_params, $queued_action_id ) {
		// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
		
		// Envoi des notifications de validation ou mise en attente des pré-investissements
		$campaign = new ATCF_Campaign( $campaign_id );
		$contract_has_been_modified = ( $campaign->contract_modifications() != '' );
		$pending_preinvestments = $campaign->pending_preinvestments();
		if ( !empty( $pending_preinvestments ) ) {
			foreach ( $pending_preinvestments as $preinvestment ) {
				// On n'agit que sur les préinvestissements qui peuvent être validés (pas en attente de paiement, et pas en virement)
				$payment_key = $preinvestment->get_payment_key();
				if ( $preinvestment->get_contract_status() != WDGInvestment::$contract_status_not_validated && strpos( $payment_key, 'wire_' ) === FALSE ) {
					$user_info = edd_get_payment_meta_user_info( $preinvestment->get_id() );
					if ( $contract_has_been_modified ) {
						NotificationsEmails::preinvestment_to_validate( $user_info['email'], $campaign );

					} else {
						NotificationsEmails::preinvestment_auto_validated( $user_info['email'], $campaign );
						$preinvestment->set_contract_status( WDGInvestment::$contract_status_investment_validated );
					}
				}
			}
		}
	}

	
/******************************************************************************/
/* NOTIFICATIONS ADMIN LORSQUE ERREURS DOCUMENTS LEMON WAY */
/******************************************************************************/
	public static function add_document_refused_admin_notification( $user_id, $lemonway_posted_document_type, $lemonway_posted_document_status) {
		$action = 'document_refused_notification';
		$entity_id = $user_id;
		$priority = 'date';
		$date_next_dispatch = new DateTime();
		// On programme la vérification 3 jours plus tard
		$date_next_dispatch->add( new DateInterval( 'P3D' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array(
			'document_type'		=> $lemonway_posted_document_type,
			'document_status'	=> $lemonway_posted_document_status
		);
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_document_refused_notification( $user_id, $queued_action_params, $queued_action_id ) {
		$lemonway_document = FALSE;
		$user_name = FALSE;
		$user_email = FALSE;
		$queued_action_param = json_decode( $queued_action_params[ 0 ] );
		
		if ( WDGOrganization::is_user_organization( $user_id ) ) {
			$WDGOrga_wallet = new WDGOrganization( $user_id );
			$user_name = $WDGOrga_wallet->get_name();
			$user_email = $WDGOrga_wallet->get_email();
			$lemonway_document = LemonwayDocument::get_by_id_and_type( $WDGOrga_wallet->get_lemonway_id(), $queued_action_param->document_type );
		} else {
			$WDGUser_wallet = new WDGUser( $user_id );
			$user_name = $WDGUser_wallet->get_firstname(). ' ' .$WDGUser_wallet->get_lastname();
			$user_email = $WDGUser_wallet->get_email();
			$lemonway_document = LemonwayDocument::get_by_id_and_type( $WDGUser_wallet->get_lemonway_id(), $queued_action_param->document_type );
		}
		
		// Vérifie si le statut du document n'a pas changé
		if ( $lemonway_document != FALSE && $lemonway_document->get_status() == $queued_action_param->document_status ) {
			
			//On vérifie si il y'a une action en cours :
			$pending_actions = array();
			// - investissement en attente
			if ( !empty( $WDGOrga_wallet ) ) {
				$pending_investments = $WDGOrga_wallet->get_pending_investments();
			} else {
				$pending_investments = $WDGUser_wallet->get_pending_investments();
			}
			if ( !empty( $pending_investments ) ) {
				foreach ( $pending_investments as $campaign_id => $campaign_investments ) {
					$campaign = new ATCF_Campaign( $campaign_id );
					if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
						foreach ( $campaign_investments as $campaign_investment_id ) {
							$payment_amount = edd_get_payment_amount( $campaign_investment_id );
							array_push( $pending_actions, 'Investissement en attente pour ' .$campaign->get_name(). ' (' .$payment_amount. ' €)' );
						}
					}
				}
			}
			// - évaluation avec intention d'investissement
			if ( !empty( $WDGUser_wallet ) ) {
				$votes_with_amount = $WDGUser_wallet->get_votes_with_amount();
				foreach ( $votes_with_amount as $vote ) {
					$campaign = new ATCF_Campaign( $vote->post_id );
					if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
						array_push( $pending_actions, 'Evaluation avec intention pour ' .$campaign->get_name(). ' (' .$vote->invest_sum. ' €)' );
					}
				}
			}
			
			if ( !empty( $pending_actions ) ) {
				NotificationsEmails::send_notification_kyc_refused_admin( $user_email, $user_name, $pending_actions );
			}
		}
		
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
	}

	
/******************************************************************************/
/* NOTIFICATIONS CONSEILS PRIORITAIRES CAMPAGNE */
/******************************************************************************/
	public static function add_campaign_advice_notification( $campaign_id ) {
		$action = 'campaign_advice_notification';
		$entity_id = $campaign_id;
		$priority = 'date';
		$date_next_dispatch = new DateTime();
		// On programme le prochain envoi 1 jour plus tard
		$date_next_dispatch->add( new DateInterval( 'P3D' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array();
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_campaign_advice_notification( $campaign_id, $queued_action_params, $queued_action_id ) {
		// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );

		if ( !empty( $campaign_id ) ) {
			
			$campaign = new ATCF_Campaign( $campaign_id );
			// Pour l'instant, on gère que les campagnes en collecte
			if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
				// Envoi des notifications
				WDGCampaignInvestments::advice_notification( $campaign );
				// On continue d'envoyer des notifications
				self::add_campaign_advice_notification( $campaign_id );
			}
			
		}
	}

	
/******************************************************************************/
/* NOTIFICATION RAPPEL QUAND EVALUATION AVEC INTENTION EN ATTENTE ET NON AUTHENTIFIE */
/******************************************************************************/
	public static function add_vote_authentication_needed_reminder( $user_id, $user_email, $campaign_name, $campaign_api_id ) {
		$action = 'vote_authentication_needed_reminder';
		$entity_id = $user_id;
		$priority = 'date';
		$date_next_dispatch = new DateTime();
		// On programme le prochain envoi 3 jours plus tard
		$date_next_dispatch->add( new DateInterval( 'P3D' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array(
			'user_email'		=> $user_email,
			'campaign_name'		=> $campaign_name,
			'campaign_api_id'	=> $campaign_api_id
		);
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_vote_authentication_needed_reminder( $user_id, $queued_action_params, $queued_action_id ) {
		// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );

		if ( !empty( $user_id ) ) {
			
			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGEntity = new WDGOrganization( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_name();
			} else {
				$WDGEntity = new WDGUser( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_firstname();
			}
			
			// On vérifie que les documents n'ont toujours pas été envoyés
			if ( !$WDGEntity->has_sent_all_documents() ) {
				$queued_action_param = json_decode( $queued_action_params[ 0 ] );
				NotificationsAPI::vote_authentication_needed_reminder( $user_email, $user_name, $queued_action_param->campaign_name, $queued_action_param->campaign_api_id );
			}
			
		}
	}

	
/******************************************************************************/
/* NOTIFICATION RAPPEL QUAND EVALUATION AVEC INTENTION EN ATTENTE ET AUTHENTIFIE */
/******************************************************************************/
	public static function add_vote_authenticated_reminder( $user_id, $user_email, $campaign_name, $campaign_url, $campaign_id, $campaign_api_id, $vote_amount ) {
		$action = 'vote_authenticated_reminder';
		$entity_id = $user_id;
		$priority = 'date';
		$date_next_dispatch = new DateTime();
		// On programme le prochain envoi 3 jours plus tard
		$date_next_dispatch->add( new DateInterval( 'P3D' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array(
			'user_email'		=> $user_email,
			'campaign_name'		=> $campaign_name,
			'campaign_url'		=> $campaign_url,
			'campaign_id'		=> $campaign_id,
			'campaign_api_id'	=> $campaign_api_id,
			'vote_amount'		=> $vote_amount
		);
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_vote_authenticated_reminder( $user_id, $queued_action_params, $queued_action_id ) {
		// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );

		if ( !empty( $user_id ) ) {
			
			if ( !WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGEntity = new WDGUser( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_firstname();
				$queued_action_param = json_decode( $queued_action_params[ 0 ] );
				if ( !$WDGEntity->has_invested_on_campaign( $queued_action_param->campaign_id ) ) {
					NotificationsAPI::vote_authenticated_reminder( $user_email, $user_name, $queued_action_param->campaign_name, $queued_action_param->campaign_url, $queued_action_param->campaign_api_id, $queued_action_param->vote_amount );
				}
			}
			
		}
	}

	
/******************************************************************************/
/* NOTIFICATION RAPPEL QUAND INVESTISSEMENT EN ATTENTE ET AUTHENTIFIE */
/******************************************************************************/
	public static function add_investment_authentified_reminder( $user_id, $user_email, $user_name, $campaign_name, $campaign_api_id ) {
		$action = 'investment_authentified_reminder';
		$entity_id = $user_id;
		$priority = 'date';
		$date_next_dispatch = new DateTime();
		// On programme le prochain envoi 3 jours plus tard
		$date_next_dispatch->add( new DateInterval( 'P3D' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array(
			'user_email'		=> $user_email,
			'user_name'			=> $user_name,
			'campaign_name'		=> $campaign_name,
			'campaign_api_id'	=> $campaign_api_id
		);
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_investment_authentified_reminder( $user_id, $queued_action_params, $queued_action_id ) {
		// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );

		if ( !empty( $user_id ) ) {
			
			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGEntity = new WDGOrganization( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_name();
			} else {
				$WDGEntity = new WDGUser( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_firstname();
			}
			
			// On vérifie qu'il y a bien toujours des investissements en attente
			$WDGUserInvestments = new WDGUserInvestments( $WDGEntity );
			if ( $WDGUserInvestments->has_pending_not_validated_investments() ) {
				$pending_not_validated_investment = $WDGUserInvestments->get_first_pending_not_validated_investment();
				$pending_not_validated_investment_campaign_name = $pending_not_validated_investment->get_saved_campaign()->data->post_title;
				NotificationsAPI::kyc_authentified_and_pending_investment_reminder( $user_email, $user_name, $pending_not_validated_investment_campaign_name, $pending_not_validated_investment->get_saved_campaign()->get_api_id() );
			}
			
		}
	}


	
/******************************************************************************/
/* NOTIFICATION RAPPEL QUAND INVESTISSEMENT EN ATTENTE ET PAS AUTHENTIFIE */
/******************************************************************************/
	public static function add_investment_authentication_needed_reminder( $user_id, $user_email, $user_name, $campaign_name, $campaign_api_id ) {
		$action = 'investment_authentication_needed_reminder';
		$entity_id = $user_id;
		$priority = 'date';
		$date_next_dispatch = new DateTime();
		// On programme le prochain envoi 3 jours plus tard
		$date_next_dispatch->add( new DateInterval( 'P3D' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array(
			'user_email'		=> $user_email,
			'user_name'			=> $user_name,
			'campaign_name'		=> $campaign_name,
			'campaign_api_id'	=> $campaign_api_id
		);
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_investment_authentication_needed_reminder( $user_id, $queued_action_params, $queued_action_id ) {
		// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );

		if ( !empty( $user_id ) ) {
			
			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGEntity = new WDGOrganization( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_name();
			} else {
				$WDGEntity = new WDGUser( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_firstname();
			}
			
			// On vérifie que les documents n'ont toujours pas été envoyés
			if ( !$WDGEntity->has_sent_all_documents() ) {
				$queued_action_param = json_decode( $queued_action_params[ 0 ] );
				NotificationsAPI::investment_authentication_needed_reminder( $user_email, $user_name, $queued_action_param->campaign_name, $queued_action_param->campaign_api_id );
			}
			
		}
	}


	
	
}