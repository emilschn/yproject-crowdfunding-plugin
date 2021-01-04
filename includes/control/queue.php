<?php
/**
 * Classe de gestion des queues d'actions
 */
class WDGQueue {
	private static $priority_date = 'date';
	private static $priority_high = 'high';

	public static $status_init = 'init';
	public static $status_complete = 'complete';
	
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
			if ( $queued_action_list[ 0 ]->status != self::$status_complete ) {
				$already_existing_action_id = $queued_action_list[ 0 ]->id;
				$already_existing_action_params = $queued_action_list[ 0 ]->params;
			}
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
/* Helpers */
/******************************************************************************/
	public static function get_next_open_date() {
		$buffer = new DateTime();
		// Si avant 9h, on fait à 9h30
		if ( $buffer->format( 'H' ) < 9 ) {
			$buffer->setTime( 9, 30 );
		}
		// Si après 19h, on fait le lendemain à 9h30
		if ( $buffer->format( 'H' ) >= 19 ) {
			$buffer->setTime( 9, 30 );
			$buffer->add( new DateInterval( 'P1D' ) );
		}
		// Si samedi, on fera un jour plus tard
		if ( $buffer->format( 'N' ) == 6 ) {
			$buffer->add( new DateInterval( 'P1D' ) );
		}
		// Si dimanche, on fera un jour plus tard
		if ( $buffer->format( 'N' ) == 7 ) {
			$buffer->add( new DateInterval( 'P1D' ) );
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
		$priority = self::$priority_date;
		$date_next_dispatch = new DateTime();
		// Les envois se font à 21h
		$date_next_dispatch->setTime( 21, 0 );
		// Si la date est avant le 10 (ou le 10), on envoie le 10
		if ( $date_next_dispatch->format( 'd' ) <= 10 ) {
			$date_next_dispatch->setDate( $date_next_dispatch->format( 'Y' ), $date_next_dispatch->format( 'm' ), 10 );
		}
		// Si la date est entre le 10 et le 15 (compris), on envoie le 15
		if ( $date_next_dispatch->format( 'd' ) > 10 && $date_next_dispatch->format( 'd' ) <= 15 ) {
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
		$id_api_entity = empty( $WDGOrganization ) ? $WDGUser->get_api_id() : $WDGOrganization->get_api_id();
		$investment_contracts = WDGWPREST_Entity_User::get_investment_contracts( $id_api_entity );
		
		// Parcours de la liste des investissements validés sur le site
		foreach ( $validated_investments as $campaign_id => $campaign_investments ) {
			// On vérifie que cet investissement n'a pas été annulé via les enregistrements dans l'API
			$first_investment_contract = FALSE;
			foreach ( $campaign_investments as $investment_id ) {
				if ( !empty( $investment_contracts ) ) {
					foreach ( $investment_contracts as $investment_contract ) {
						if ( $investment_contract->subscription_id == $investment_id ) {
							$first_investment_contract = $investment_contract;
						}
					}
				}
			}
			if ( !empty( $first_investment_contract ) && $first_investment_contract->status == 'canceled' ) {
				continue;
			}

			$campaign = new ATCF_Campaign( $campaign_id );
			$campaign_organization = $campaign->get_organization();
			if ( !empty( $campaign_organization ) ) {
				$campaign_organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
				$campaign_name = $campaign_organization_obj->get_name() . ' (Levée de fonds "' .$campaign->get_name(). '")';
			} else {
				$campaign_name = $campaign->get_name();
			}
			
			if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded ) {
				$amount_royalties = 0;
				$amount_tax_in_cents = 0;
				$has_declared = FALSE;

				$campaign_roi_list = ( empty( $WDGOrganization ) ) ? $WDGUser->get_royalties_by_campaign_id( $campaign_id ) : $WDGOrganization->get_royalties_by_campaign_id( $campaign_id );
				foreach ( $campaign_roi_list as $campaign_roi ) {
					$date_transfer = new DateTime( $campaign_roi->date_transfer );
					if ( $date_transfer->format( 'm' ) == $date_now->format( 'm' ) && $date_transfer->format( 'Y' ) == $date_now->format( 'Y' ) ) {
						$amount_royalties += $campaign_roi->amount;
						// si il y a un montant taxé, on va prendre le montant du prélèvement social
						if ( $campaign_roi->amount_taxed_in_cents > 0 ) {
							$amount_tax_in_cents = $WDGUser->get_tax_amount_in_cents_round( $ROI->amount_taxed_in_cents );
						}
						$has_declared = TRUE;
					}
				}

				if ( $has_declared ) {
					array_push( $message_categories[ 'with_royalties' ], array(
						'campaign_name'			=> $campaign_name,
						'amount_royalties'		=> $amount_royalties,
						'amount_tax_in_cents'	=> $amount_tax_in_cents,
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
				$message .= "- " .$campaign_params[ 'campaign_name' ]. " : " .YPUIHelpers::display_number( $campaign_params[ 'amount_royalties' ] ). " €";
				if ( $campaign_params[ 'amount_tax_in_cents' ] > 0 ) {
					$message .= " (dont prélèvement " .YPUIHelpers::display_number( $campaign_params['amount_tax_in_cents'] ). " €)";
				}
				$message .= "<br>";
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
			$cancel_notification = FALSE;

			// les organisations recoivent systématiquement les notifications de royalties
			if ( $WDGUser != FALSE ) {
				$recipient_notification = $WDGUser->get_royalties_notifications();
				if( $recipient_notification == 'none' ){
					$cancel_notification = TRUE;
				} elseif ( $recipient_notification == 'positive' && empty( $message_categories[ 'with_royalties' ] )) {
					$cancel_notification = TRUE;
				}				
			}

			if (!$cancel_notification ){
				NotificationsAPI::roi_transfer_daily_resume( $recipient_email, $recipient_name, $message );
			}
		}
	}

	/******************************************************************************/
	/* NOTIFS INSCRIPTION J+7 */
	/******************************************************************************/
		public static function add_notification_registered_without_investment( $user_id ) {
			$action = 'registered_without_investment';
			$entity_id = $user_id;
			$priority = self::$priority_date;
			$params = array();

			// Les envois se font dans 7j à 9h
			$date_next_dispatch = new DateTime();
			$date_next_dispatch->setTime( 9, 0 );
			$date_next_dispatch->add( new DateInterval( 'P7D' ) );
			$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
			
			self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
		}
		
		public static function execute_registered_without_investment( $user_id, $queued_action_params, $queued_action_id ) {
			$WDGUser = new WDGUser( $user_id );

			// Recherche si l'utilisateur a fait une activité (éval, investissement, authentification)
			$list_organizations = $WDGUser->get_organizations_list();
			$list_campaigns_followed = $WDGUser->get_campaigns_followed();
			$list_campaigns_voted = $WDGUser->get_campaigns_voted();
			$lw_status = $WDGUser->get_lemonway_status();
			$has_actions = !empty( $list_organizations )
							|| !empty( $list_campaigns_followed )
							|| !empty( $list_campaigns_voted )
							|| $lw_status != LemonwayLib::$status_ready;

			// Si pas d'action : envoi rappel + programmation 2eme rappel
			if ( !$has_actions ) {
				NotificationsAPI::user_registered_without_investment( $WDGUser->get_email(), $WDGUser->get_firstname() );
				self::add_notification_registered_without_investment_reminder( $user_id );
			}
		}

	/******************************************************************************/
	/* NOTIFS RAPPEL INSCRIPTION J+7 */
	/******************************************************************************/
		public static function add_notification_registered_without_investment_reminder( $user_id ) {
			$action = 'registered_without_investment_reminder';
			$entity_id = $user_id;
			$priority = self::$priority_date;
			$params = array();

			// Les envois se font dans 7j à 9h
			$date_next_dispatch = new DateTime();
			$date_next_dispatch->setTime( 9, 0 );
			$date_next_dispatch->add( new DateInterval( 'P7D' ) );
			$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
			
			self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
		}
		
		public static function execute_registered_without_investment_reminder( $user_id, $queued_action_params, $queued_action_id ) {
			$WDGUser = new WDGUser( $user_id );

			// Recherche si l'utilisateur a fait une activité (éval, investissement, authentification)
			$list_organizations = $WDGUser->get_organizations_list();
			$list_campaigns_followed = $WDGUser->get_campaigns_followed();
			$list_campaigns_voted = $WDGUser->get_campaigns_voted();
			$lw_status = $WDGUser->get_lemonway_status();
			$has_actions = !empty( $list_organizations )
							|| !empty( $list_campaigns_followed )
							|| !empty( $list_campaigns_voted )
							|| $lw_status != LemonwayLib::$status_ready;

			// Si pas d'action : envoi rappel selon actions sur le mail
			if ( !$has_actions ) {
			
				$ref_template_id = 932;

				// Récupération mail le plus récent
				$api_email_list = WDGWPRESTLib::call_get_wdg( 'emails?id_template=' .$ref_template_id. '&recipient_email=' .$WDGUser->get_email() );
				if ( count( $api_email_list ) == 0 ) {
					return;
				}

				$api_email = $api_email_list[ 0 ];
				$api_email_result = json_decode( $api_email->result, TRUE );
				if ( empty( $api_email_result[ 'data' ] ) || empty( $api_email_result[ 'data' ][ 'message-id' ] ) ) {
					return;
				}
				$message_id = $api_email_result[ 'data' ][ 'message-id' ];
				
				$data = array( 
					"message_id" => $message_id,
					"template_id" => $ref_template_id
				);
				$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 15000 );
				
				try {
					$mailin_report = $mailin->get_report( $data );
				} catch ( Exception $e ) {
					return;
				}
				if ( empty( $mailin_report[ 'data' ] ) ) {
					return;
				}

				$has_viewed = FALSE;
				$has_clicked = FALSE;
				$mailin_report_data = $mailin_report[ 'data' ];
				foreach ( $mailin_report_data as $mail_event ) {
					if ( !empty( $mail_event[ 'event' ] ) ) {
						if ( $mail_event[ 'event' ] == 'views' ) {
							$has_viewed = TRUE;
						}
						if ( $mail_event[ 'event' ] == 'clicks' ) {
							$has_clicked = TRUE;
						}
					}
				}
				
				if ( !$has_viewed ) {
					NotificationsAPI::user_registered_without_investment_not_open( $WDGUser->get_email(), $WDGUser->get_firstname() );
				} else if ( !$has_clicked ) {
					NotificationsAPI::user_registered_without_investment_not_clicked( $WDGUser->get_email(), $WDGUser->get_firstname() );
				} else {
					NotificationsAPI::user_registered_without_investment_not_invested( $WDGUser->get_email(), $WDGUser->get_firstname() );
				}

			}
		}

	/******************************************************************************/
	/* NOTIFS WALLET A PLUS DE 200 EUROS */
	/******************************************************************************/
		public static function add_notification_wallet_more_200_euros( $user_id ) {
			$action = 'wallet_more_200_euros';
			$entity_id = $user_id;
			$priority = self::$priority_date;
			$params = array();

			// Les envois se font dans un mois à 9h
			$date_next_dispatch = new DateTime();
			$date_next_dispatch->setTime( 9, 0 );
			$date_next_dispatch->add( new DateInterval( 'P1M' ) );
			$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
			
			self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
		}
		
		public static function execute_wallet_more_200_euros( $user_id, $queued_action_params, $queued_action_id ) {
			$WDGUser = new WDGUser( $user_id );
			if ( $WDGUser->get_lemonway_wallet_amount() >= 200 ) {
				NotificationsAPI::wallet_with_more_than_200_euros( $WDGUser->get_email(), $WDGUser->get_firstname() );
				self::add_notification_wallet_more_200_euros_reminder( $user_id );
			}
		}

	/******************************************************************************/
	/* NOTIFS RAPPEL WALLET A PLUS DE 200 EUROS */
	/******************************************************************************/
		public static function add_notification_wallet_more_200_euros_reminder( $user_id ) {
			$action = 'wallet_more_200_euros_reminder';
			$entity_id = $user_id;
			$priority = self::$priority_date;
			$params = array();

			// Les envois se font dans un mois à 9h
			$date_next_dispatch = new DateTime();
			$date_next_dispatch->setTime( 9, 0 );
			$date_next_dispatch->add( new DateInterval( 'P7D' ) );
			$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
			
			self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
		}
		
		public static function execute_wallet_more_200_euros_reminder( $user_id, $queued_action_params, $queued_action_id ) {
			$WDGUser = new WDGUser( $user_id );
			if ( $WDGUser->get_lemonway_wallet_amount() >= 200 ) {
				$ref_template_id = 1042;

				// Récupération mail le plus récent
				$api_email_list = WDGWPRESTLib::call_get_wdg( 'emails?id_template=' .$ref_template_id. '&recipient_email=' .$WDGUser->get_email() );
				if ( count( $api_email_list ) == 0 ) {
					return;
				}

				$api_email = $api_email_list[ 0 ];
				$api_email_result = json_decode( $api_email->result, TRUE );
				if ( empty( $api_email_result[ 'data' ] ) || empty( $api_email_result[ 'data' ][ 'message-id' ] ) ) {
					return;
				}
				$message_id = $api_email_result[ 'data' ][ 'message-id' ];
				
				$data = array( 
					"message_id" => $message_id,
					"template_id" => $ref_template_id
				);
				$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 15000 );
				$mailin_report = $mailin->get_report( $data );
				if ( empty( $mailin_report[ 'data' ] ) ) {
					return;
				}

				$has_viewed = FALSE;
				$has_clicked = FALSE;
				$mailin_report_data = $mailin_report[ 'data' ];
				foreach ( $mailin_report_data as $mail_event ) {
					if ( !empty( $mail_event[ 'event' ] ) ) {
						if ( $mail_event[ 'event' ] == 'views' ) {
							$has_viewed = TRUE;
						}
						if ( $mail_event[ 'event' ] == 'clicks' ) {
							$has_clicked = TRUE;
						}
					}
				}
				
				if ( !$has_viewed ) {
					NotificationsAPI::wallet_with_more_than_200_euros_reminder_not_open( $WDGUser->get_email(), $WDGUser->get_firstname() );
				} else if ( !$has_clicked ) {
					NotificationsAPI::wallet_with_more_than_200_euros_reminder_not_clicked( $WDGUser->get_email(), $WDGUser->get_firstname() );
				}
			}
		}

	/******************************************************************************/
	/* NOTIFS ENTREPRENEURS INVESTISSEURS AVEC WALLET A PLUS DE 200 EUROS */
	/******************************************************************************/
		public static function add_notification_investors_with_more_200_euros( $campaign_id, $user_id ) {
			$action = 'investors_with_more_200_euros';
			$entity_id = $campaign_id;
			$priority = self::$priority_date;
			$params = array(
				'user_id'	=> $user_id
			);

			// Les envois se font dans un mois à 9h
			$date_next_dispatch = new DateTime();
			$date_next_dispatch->setTime( 9, 0 );
			$date_next_dispatch->add( new DateInterval( 'P1M' ) );
			$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
			
			self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
		}

		public static function execute_investors_with_more_200_euros( $campaign_id, $queued_action_params, $queued_action_id ) {
			// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
			WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
			
			$campaign = new ATCF_Campaign( $campaign_id );
			$current_organization = $campaign->get_organization();
			$organization_obj = new WDGOrganization( $current_organization->wpref, $current_organization );
			$WDGUser_author = new WDGUser( $campaign->data->post_author );

			$investors_list_str = '';
			$investors_list = array();
			foreach ( $queued_action_params as $single_param ) {
				$queued_action_param = json_decode( $single_param );
				array_push( $investors_list, $queued_action_param->user_id );
			}

			$investors_list_unique = array_unique( $investors_list );
			foreach ( $investors_list_unique as $investor_id ) {
				$WDGUser = new WDGUser( $investor_id );
				$investors_list_str .= '- ' .$WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname(). '<br>';
			}

			if ( !empty( $investors_list_str ) ) {
				NotificationsAPI::investors_with_wallet_with_more_than_200_euros( $WDGUser_author->get_email(), $WDGUser_author->get_firstname(), $investors_list_str );
			}
		}


/******************************************************************************/
/* PROLONGATION CONTRAT ROYALTIES */
/******************************************************************************/
	public static function add_contract_extension_notifications( $campaign_id ) {
		$action = 'contract_extension_notifications';
		$entity_id = $campaign_id;
		$priority = self::$priority_high;
		
		self::create_or_replace_action( $action, $entity_id, $priority );
	}
	
	public static function execute_contract_extension_notifications( $campaign_id, $queued_action_params, $queued_action_id ) {
		// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
		
		// Envoi de la notification au porteur de projet
		$campaign = new ATCF_Campaign( $campaign_id );
		$current_organization = $campaign->get_organization();
		$organization_obj = new WDGOrganization( $current_organization->wpref, $current_organization );
		$wdguser_author = new WDGUser( $campaign->data->post_author );
		NotificationsAPI::declaration_extended_project_manager( $organization_obj->get_email(), $wdguser_author->get_firstname() );
		
		// Envoi de la notification aux investisseurs
		$project_name = $campaign->get_name();
		$funding_duration = $campaign->funding_duration();
		$project_url = $campaign->get_public_url();
		$investment_contracts = WDGInvestmentContract::get_list( $campaign_id );
		foreach ( $investment_contracts as $investment_contract ) {
			if ( $investment_contract->status == WDGInvestmentContract::$status_active ) {
				$recipient = '';
				$name = '';
				if ( $investment_contract->investor_type == 'user' ) {
					$WDGUser = WDGUser::get_by_api_id( $investment_contract->investor_id );
					$recipient = $WDGUser->get_email();
					$name = $WDGUser->get_firstname();
				} else {
					$WDGOrganization = WDGOrganization::get_by_api_id( $investment_contract->investor_id );
					$recipient = $WDGOrganization->get_email();
					$name = $WDGOrganization->get_name();
				}
				$date = $investment_contract->subscription_date;
				$amount_investment = $investment_contract->subscription_amount;
				$amount_royalties = $investment_contract->amount_received;
				$amount_remaining = $investment_contract->subscription_amount - $investment_contract->amount_received;
				NotificationsAPI::declaration_extended_investor( $recipient, $name, $project_name, $funding_duration, $date, $project_url, $amount_investment, $amount_royalties, $amount_remaining, $campaign->get_api_id() );
			}
		}
		
	}

	
/******************************************************************************/
/* FIN CONTRAT ROYALTIES */
/******************************************************************************/
	public static function add_contract_finished_notifications( $campaign_id ) {
		$action = 'contract_finished_notifications';
		$entity_id = $campaign_id;
		$priority = self::$priority_high;
		
		self::create_or_replace_action( $action, $entity_id, $priority );
	}
	
	public static function execute_contract_finished_notifications( $campaign_id, $queued_action_params, $queued_action_id ) {
		// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
		
		// Envoi de la notification au porteur de projet
		$campaign = new ATCF_Campaign( $campaign_id );
		$current_organization = $campaign->get_organization();
		$organization_obj = new WDGOrganization( $current_organization->wpref, $current_organization );
		$wdguser_author = new WDGUser( $campaign->data->post_author );
		NotificationsAPI::declaration_finished_project_manager( $organization_obj->get_email(), $wdguser_author->get_firstname() );
		
		// Envoi de la notification aux investisseurs
		$project_name = $campaign->get_name();
		$project_url = $campaign->get_public_url();
		$investment_contracts = WDGInvestmentContract::get_list( $campaign_id );
		foreach ( $investment_contracts as $investment_contract ) {
			if ( $investment_contract->status == WDGInvestmentContract::$status_active ) {
				$recipient = '';
				$name = '';
				if ( $investment_contract->investor_type == 'user' ) {
					$WDGUser = WDGUser::get_by_api_id( $investment_contract->investor_id );
					$recipient = $WDGUser->get_email();
					$name = $WDGUser->get_firstname();
				} else {
					$WDGOrganization = WDGOrganization::get_by_api_id( $investment_contract->investor_id );
					$recipient = $WDGOrganization->get_email();
					$name = $WDGOrganization->get_name();
				}
				$date = $investment_contract->subscription_date;
				$amount_investment = $investment_contract->subscription_amount;
				$amount_royalties = $investment_contract->amount_received;
				NotificationsAPI::declaration_finished_investor( $recipient, $name, $project_name, $date, $project_url, $amount_investment, $amount_royalties, $campaign->get_api_id() );
			}
		}
		
	}

	
/******************************************************************************/
/* VALIDATION PREINVESTISSEMENTS */
/******************************************************************************/
	public static function add_preinvestments_validation( $campaign_id ) {
		$action = 'preinvestments_validation';
		$entity_id = $campaign_id;
		$priority = self::$priority_high;
		
		self::create_or_replace_action( $action, $entity_id, $priority );
	}
	
	public static function execute_preinvestments_validation( $campaign_id, $queued_action_params, $queued_action_id ) {
		if ( !empty( $queued_action_id ) ) {
			// Exceptionnellement, on déclare l'action faite au début, pour ne pas envoyer de doublons de mails si coupure au milieu
			WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
		}
		
		// Envoi des notifications de validation ou mise en attente des pré-investissements
		$campaign = new ATCF_Campaign( $campaign_id );
		$contract_has_been_modified = ( $campaign->contract_modifications() != '' );
		$pending_preinvestments = $campaign->pending_preinvestments();
		if ( !empty( $pending_preinvestments ) ) {
			foreach ( $pending_preinvestments as $preinvestment ) {
				$wire_with_received_payments = get_post_meta( $preinvestment->get_id(), 'has_received_wire', TRUE );
				// On n'agit que sur les préinvestissements qui peuvent être validés (pas en attente de paiement, et pas en virement)
				// Il reste à traiter le cas des virements qui sont lancés en évaluation, mais reçus qu'en investissement (ils sont validés automatiquement)
				$payment_key = $preinvestment->get_payment_key();
				if ( 
						$preinvestment->get_contract_status() != WDGInvestment::$contract_status_not_validated 
						&& ( strpos( $payment_key, 'wire_' ) === FALSE || $wire_with_received_payments == '1' )
						) {
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
/* NOTIFICATIONS RELANCE CAMPAGNE */
/******************************************************************************/
	public static function add_campaign_notifications( $campaign_id, $mail_type, $input_testimony_in, $input_image_url, $input_image_description, $user_already_sent_to ) {
		$action = 'campaign_notifications';
		$entity_id = $campaign_id;
		$priority = self::$priority_high;
		
		$params = array(
			'mail_type'				=> $mail_type,
			'testimony_in'			=> $input_testimony_in,
			'image_url'				=> $input_image_url,
			'image_description'		=> $input_image_description,
			'user_already_sent_to'	=> $user_already_sent_to
		);
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params );
	}

	public static function execute_campaign_notifications( $campaign_id, $queued_action_params, $queued_action_id ) {
		$queued_action_param = json_decode( $queued_action_params[ 0 ] );
		// Passage à complete avant, pour pouvoir en ajouter un à la suite
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
		WDGEmails::auto_notifications( $campaign_id, $queued_action_param->mail_type, $queued_action_param->testimony_in, $queued_action_param->image_url, $queued_action_param->image_description, '', $queued_action_param->user_already_sent_to );
	}
	
/******************************************************************************/
/* NOTIFICATIONS FIN EVALUATION */
/******************************************************************************/
	public static function add_campaign_end_vote_notifications( $campaign_id, $mail_type, $user_already_sent_to ) {
		$action = 'campaign_end_vote_notifications';
		$entity_id = $campaign_id;
		$priority = self::$priority_high;
		
		$params = array(
			'mail_type'				=> $mail_type,
			'user_already_sent_to'	=> $user_already_sent_to
		);
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params );
	}

	public static function execute_campaign_end_vote_notifications( $campaign_id, $queued_action_params, $queued_action_id ) {
		$queued_action_param = json_decode( $queued_action_params[ 0 ] );
		// Passage à complete avant, pour pouvoir en ajouter un à la suite
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
		WDGEmails::end_vote_notifications( $campaign_id, $queued_action_param->mail_type, '', $queued_action_param->user_already_sent_to );
	}
	
/******************************************************************************/
/* NOTIFICATIONS ADMIN LORSQUE ERREURS DOCUMENTS LEMON WAY */
/******************************************************************************/
	public static function add_document_refused_admin_notification( $user_id, $lemonway_posted_document_type, $lemonway_posted_document_status ) {
		$action = 'document_refused_notification';
		$entity_id = $user_id;
		$priority = self::$priority_date;
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
				NotificationsSlack::send_notification_kyc_refused_admin( $user_email, $user_name );
			}
		}
		
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
	}
	
/******************************************************************************/
/* NOTIFICATIONS USER LORSQUE ERREURS DOCUMENTS LEMON WAY */
/******************************************************************************/
	public static function add_document_refused_user_notification( $user_id ) {
		$action = 'document_refused_user_notification';
		$entity_id = $user_id;
		$priority = self::$priority_high;
		self::create_or_replace_action( $action, $entity_id, $priority );
	}

	public static function execute_document_refused_user_notification( $user_id, $queued_action_params, $queued_action_id ) {
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
		
		$wallet_details = FALSE;
		$email = '';
		$name = '';
		if ( WDGOrganization::is_user_organization( $user_id ) ) {
			$WDGOrga_wallet = new WDGOrganization( $user_id );
			if ( !$WDGOrga_wallet->is_registered_lemonway_wallet() ) {
				$wallet_details = $WDGOrga_wallet->get_wallet_details();
				$email = $WDGOrga_wallet->get_email();
				$name = $WDGOrga_wallet->get_name();
			}

		} else {
			$WDGUser_wallet = new WDGUser( $user_id );
			if ( !$WDGUser_wallet->is_lemonway_registered() ) {
				$wallet_details = $WDGUser_wallet->get_wallet_details();
				$email = $WDGUser_wallet->get_email();
				$name = $WDGUser_wallet->get_firstname();
			}
		}

		$buffer_returns = LemonwayDocument::build_error_str_from_wallet_details( $wallet_details );


		// Envoi template SIB + SMS décalé
		if ( !empty( $buffer_returns) ) {
			NotificationsAPI::kyc_refused( $email, $name, $buffer_returns );
			if ( isset( $WDGUser_wallet ) && $WDGUser_wallet->has_subscribed_authentication_notification() ) {
				self::add_document_user_phone_notification( $user_id, 'refused' );
			}
		}
	}
	
/******************************************************************************/
/* NOTIFICATIONS USER PAR SMS LORSQUE MAJ DOCUMENTS LEMON WAY */
/******************************************************************************/
	public static function add_document_user_phone_notification( $user_id, $status ) {
		$action = 'document_user_phone_notification';
		$entity_id = $user_id;
		$priority = self::$priority_date;
		$date_next_dispatch = self::get_next_open_date();
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array(
			'status'	=> $status
		);
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}

	public static function execute_document_user_phone_notification( $user_id, $queued_action_params, $queued_action_id ) {
		WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
		
		$queued_action_param = json_decode( $queued_action_params[ 0 ] );
		$wallet_details = FALSE;
		$email = '';
		$name = '';
		// Uniquement pour les personnes physiques
		if ( !WDGOrganization::is_user_organization( $user_id ) ) {
			$WDGUser_wallet = new WDGUser( $user_id );
			$wallet_details = $WDGUser_wallet->get_wallet_details();
			$email = $WDGUser_wallet->get_email();
			$name = $WDGUser_wallet->get_firstname();
		}

		if ( !empty( $email ) && $WDGUser_wallet->has_subscribed_authentication_notification() ) {
			switch ( $queued_action_param->status ) {
				case 'refused':
					// On refait la vérification que le statut du wallet n'a pas changé (avec un éventuel décalage temporel)
					$buffer_returns = LemonwayDocument::build_error_str_from_wallet_details( $wallet_details );
					if ( !empty( $buffer_returns) ) {
						NotificationsAPI::phone_kyc_refused( $email, $name );
					}
					break;
				case 'authentified':
					NotificationsAPI::phone_kyc_authentified( $email, $name );
					break;
				case 'one_doc':
					// Si ils sont tous validés, on enverra une notification plus tard
					if ( LemonwayDocument::has_only_first_doc_validated( $wallet_details ) ) {
						NotificationsAPI::phone_kyc_single_validated( $email, $name );
					}
					break;
			}
		}
	}
	
/******************************************************************************/
/* NOTIFICATIONS ADMIN LORSQUE VALIDATION DOCUMENTS LEMON WAY MAIS PAS WALLET */
/******************************************************************************/
	public static function add_document_validated_but_not_wallet_admin_notification( $user_id ) {
		$action = 'document_validated_but_not_wallet_admin_notification';
		$entity_id = $user_id;
		$priority = self::$priority_date;
		$date_next_dispatch = new DateTime();
		// On programme la vérification 1 jour plus tard
		$date_next_dispatch->add( new DateInterval( 'P1D' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array();
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_document_validated_but_not_wallet_admin_notification( $user_id, $queued_action_params, $queued_action_id ) {
		$is_lemonway_registered = TRUE;
		$wallet_details = FALSE;
		$user_name = FALSE;
		$user_email = FALSE;

		if ( empty( $user_id ) ) {
			return FALSE;
		}
		
		if ( WDGOrganization::is_user_organization( $user_id ) ) {
			$WDGOrga = new WDGOrganization( $user_id );
			$is_lemonway_registered = $WDGOrga->is_registered_lemonway_wallet();
			if ( !$is_lemonway_registered ) {
				$wallet_details = $WDGOrga->get_wallet_details();
				$user_name = $WDGOrga->get_name();
				$user_email = $WDGOrga->get_email();
			}
			
		} else {
			$WDGUser = new WDGUser( $user_id );
			$is_lemonway_registered = $WDGUser->is_lemonway_registered();
			if ( !$is_lemonway_registered ) {
				$wallet_details = $WDGUser->get_wallet_details();
				$user_name = $WDGUser->get_firstname() . ' ' . $WDGUser->get_lastname();
				$user_email = $WDGUser->get_email();
			}
		}
		
		// Vérifie si le statut du document n'a pas changé
		if ( !$is_lemonway_registered && !empty( $wallet_details ) ) {
			$has_all_documents_validated = TRUE;

			if ( !empty( $wallet_details ) && !empty( $wallet_details->DOCS ) && !empty( $wallet_details->DOCS->DOC ) ) {
				foreach ( $wallet_details->DOCS->DOC as $document_object ) {
					if ( !empty( $document_object->S ) && $document_object->S != 2 ) {
						$has_all_documents_validated = FALSE;
					}
				}
			}
			
			if ( $has_all_documents_validated ) {
				//On vérifie si il y'a une action en cours :
				$pending_actions = array();
				// - investissement en attente
				if ( !empty( $WDGOrga ) ) {
					$pending_investments = $WDGOrga->get_pending_investments();
				} else {
					$pending_investments = $WDGUser->get_pending_investments();
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
				if ( !empty( $WDGUser ) ) {
					$votes_with_amount = $WDGUser->get_votes_with_amount();
					foreach ( $votes_with_amount as $vote ) {
						$campaign = new ATCF_Campaign( $vote->post_id );
						if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
							array_push( $pending_actions, 'Evaluation avec intention pour ' .$campaign->get_name(). ' (' .$vote->invest_sum. ' €)' );
						}
					}
				}
				
				if ( !empty( $pending_actions ) ) {
					NotificationsEmails::send_notification_kyc_validated_but_not_wallet_admin( $user_email, $user_name, $pending_actions );
					NotificationsSlack::send_notification_kyc_validated_but_not_wallet_admin( $user_email, $user_name );
				}
			}
		}
	}

	
/******************************************************************************/
/* NOTIFICATIONS CONSEILS PRIORITAIRES CAMPAGNE */
/******************************************************************************/
	public static function add_campaign_advice_notification( $campaign_id ) {
		$action = 'campaign_advice_notification';
		$entity_id = $campaign_id;
		$campaign = new ATCF_Campaign( $campaign_id );
		$priority = self::$priority_date;
		$date_next_dispatch = new DateTime();
		// On programme le prochain envoi 1 jour plus tard
		$date_next_dispatch->add( new DateInterval( 'P' .$campaign->get_advice_notifications_frequency(). 'D' ) );
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
			// ou les projets en statut "vote" mais dont la date n'est pas dépassée
			if ( ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote && $campaign->end_vote_remaining() > 0 ) || $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
				// Envoi des notifications
				WDGCampaignInvestments::advice_notification( $campaign );
				// On continue d'envoyer des notifications
				self::add_campaign_advice_notification( $campaign_id );
			}
			
		}
	}
	
	public static function has_planned_campaign_advice_notification( $campaign_id ) {
		$buffer = FALSE;
		$queued_actions = WDGWPREST_Entity_QueuedAction::get_list( FALSE, FALSE, $campaign_id, 'campaign_advice_notification' );
		if ( !empty( $queued_actions ) && !empty( $queued_actions[0]->id ) ) {
			$buffer = $queued_actions[0]->id;
		}
		return $buffer;
	}

	
/******************************************************************************/
/* NOTIFICATION RAPPEL QUAND EVALUATION AVEC INTENTION EN ATTENTE ET NON AUTHENTIFIE */
/******************************************************************************/
	public static function add_vote_authentication_needed_reminder( $user_id, $user_email, $campaign_name, $campaign_api_id ) {
		$action = 'vote_authentication_needed_reminder';
		$entity_id = $user_id;
		$priority = self::$priority_date;
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
			
			$is_user_authenticated = FALSE;
			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGEntity = new WDGOrganization( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_name();
				$is_user_authenticated = $WDGEntity->is_registered_lemonway_wallet();
			} else {
				$WDGEntity = new WDGUser( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_firstname();
				$is_user_authenticated = $WDGEntity->is_lemonway_registered();
			}
			
			// On vérifie que les documents n'ont toujours pas été envoyés
			if ( !$WDGEntity->has_sent_all_documents() && !$is_user_authenticated ) {
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
		$priority = self::$priority_date;
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
		$priority = self::$priority_date;
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
		$priority = self::$priority_date;
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
				$LW_registered = $WDGEntity->is_registered_lemonway_wallet();
			} else {
				$WDGEntity = new WDGUser( $user_id );
				$user_email = $WDGEntity->get_email();
				$user_name = $WDGEntity->get_firstname();
				$LW_registered = $WDGEntity->is_lemonway_registered();
			}
			
			// On vérifie que les documents n'ont toujours pas été envoyés
			if ( !$WDGEntity->has_sent_all_documents() && !$LW_registered ) {
				$queued_action_param = json_decode( $queued_action_params[ 0 ] );
				NotificationsAPI::investment_authentication_needed_reminder( $user_email, $user_name, $queued_action_param->campaign_name, $queued_action_param->campaign_api_id );
			}
			
		}
	}


	
/******************************************************************************/
/* GENERATION CACHE PAGE STATIQUE */
/******************************************************************************/
	public static function add_cache_post_as_html( $post_id, $input_priority = 'date', $date_interval = 'PT10M' ) {
		$action = 'cache_post_as_html';
		$entity_id = $post_id;
		$priority = $input_priority;
		$date_next_dispatch = new DateTime();
		$date_next_dispatch->add( new DateInterval( $date_interval ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array();
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_cache_post_as_html( $post_id, $queued_action_params, $queued_action_id ) {
		if ( !empty( $post_id ) ) {
			
			$WDG_File_Cacher = WDG_File_Cacher::current();
			$WDG_File_Cacher->build_post( $post_id );

			// Relance 1 jour plus tard au cas où des modifs de dev doivent être prises en compte
			WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
			self::add_cache_post_as_html( $post_id, 'date', 'P1D' );
			
		}
	}


	
/******************************************************************************/
/* TRANSFERT AUTOMATIQUE DE ROYALTIES */
/******************************************************************************/
	public static function add_init_declaration_rois( $declaration_id ) {
		$action = 'init_declaration_rois';
		$entity_id = $declaration_id;
		$priority = self::$priority_high;
		$params = array();
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_init_declaration_rois( $declaration_id, $queued_action_params, $queued_action_id ) {
		if ( !empty( $declaration_id ) ) {
			$roi_declaration = new WDGROIDeclaration( $declaration_id );
			// On le fait avant : init_rois_and_tax est en mesure d'en relancer un autre en parallèle
			WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
			$roi_declaration->init_rois_and_tax();
		}
	}

	public static function add_royalties_auto_transfer_start( $declaration_id, $date = FALSE ) {
		$action = 'royalties_auto_transfer_start';
		$entity_id = $declaration_id;
		$priority = self::$priority_date;
		if ( $date == FALSE ) {
			$date = new DateTime();
		}
		$date_priority = $date->format( 'Y-m-d H:i:s' );
		$params = array();
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_royalties_auto_transfer_start( $declaration_id, $queued_action_params, $queued_action_id ) {
		if ( !empty( $declaration_id ) ) {

			$roi_declaration = new WDGROIDeclaration( $declaration_id );
			$campaign = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );
			$current_organization = $campaign->get_organization();
			if ( !empty( $current_organization ) ) {
				$organization_obj = new WDGOrganization( $current_organization->wpref, $current_organization );
				$amount_wallet = $organization_obj->get_lemonway_balance( 'royalties' );
			}

			// On vérifie qu'il y a toujours l'argent sur le wallet
			$mandate_is_success = TRUE;
			$payment_token = $roi_declaration->payment_token;
			if ( !empty( $payment_token ) && $roi_declaration->mean_payment == WDGROIDeclaration::$mean_payment_mandate ) {
				$payment_result = LemonwayLib::get_transaction_by_id( $payment_token, 'transactionId' );
				if ( $payment_result->STATUS != '3' ) {
					$mandate_is_success = FALSE;
				}
			}
			
			if ( $mandate_is_success && $amount_wallet >= $roi_declaration->get_amount_with_adjustment() ) {
				self::add_royalties_auto_transfer_next( $declaration_id );

			} else {
				// Sinon on prévient qu'il n'y a plus assez
				NotificationsSlack::send_notification_roi_insufficient_funds_admin( $campaign->get_name() );
				NotificationsAsana::send_notification_roi_insufficient_funds_admin( $campaign->get_name() );

			}
		}
	}

	public static function add_royalties_auto_transfer_next( $declaration_id ) {
		$action = 'royalties_auto_transfer_next';
		$entity_id = $declaration_id;
		$priority = self::$priority_high;
		$params = array();
		self::create_or_replace_action( $action, $entity_id, $priority, $params );
	}
	
	public static function execute_royalties_auto_transfer_next( $declaration_id, $queued_action_params, $queued_action_id ) {
		if ( !empty( $declaration_id ) ) {

			$roi_declaration = new WDGROIDeclaration( $declaration_id );
			$result = 100;
			// Contrôle au cas où il y ait eu un plantage précédent
			if ( $roi_declaration->status != WDGROIDeclaration::$status_finished ) {
				$result = $roi_declaration->transfer_pending_rois();
			}
			if ( $result == 100 ) {
				NotificationsSlack::send_auto_transfer_done( $roi_declaration->get_campaign_object()->get_name() );

			} else {
				// Passage à complete avant, pour pouvoir en ajouter un à la suite
				WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
				// On continue au prochain tour
				self::add_royalties_auto_transfer_next( $declaration_id );
			}
			
		}
	}


	
	/******************************************************************************/
	/* ENVOI NOTIF ADMIN MENSUELLE POUR TAXES */
	/******************************************************************************/
	public static function add_tax_monthly_summary( $declaration_id ) {
		$action = 'tax_monthly_summary';
		$entity_id = $declaration_id;
		$priority = self::$priority_date;
		$date_next_dispatch = new DateTime();
		$date_next_dispatch->modify( 'first day of next month' );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array();
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_tax_monthly_summary( $declaration_id, $queued_action_params, $queued_action_id ) {
		if ( !empty( $declaration_id ) ) {
			$buffer_mail = '';
			$total_tax_in_euros = 0;

			$roi_declaration = new WDGROIDeclaration( $declaration_id );
			$list_rois = $roi_declaration->get_rois();
			foreach ( $list_rois as $roi_item ) {
				if ( $roi_item->status == WDGROI::$status_transferred && $roi_item->amount_taxed_in_cents > 0 ) {
					if ( $roi_item->recipient_type == 'orga' ) {
						$WDGOrganization = WDGOrganization::get_by_api_id( $roi_item->id_user );
						$buffer_mail .= '- ' . $WDGOrganization->get_name() . ' est une personne morale et ne paie pas de taxes<br>';

					} else {
						$list_roi_tax = WDGWPREST_Entity_ROITax::get_by_id_roi( $roi_item->id );
						if ( !empty( $list_roi_tax ) ) {
							$WDGUser = WDGUser::get_by_api_id( $roi_item->id_user );
							// Normalement un seul, mais retourné sous forme de liste
							foreach ( $list_roi_tax as $roi_tax_item ) {
								$user_tax_in_euros = ( $roi_tax_item->amount_tax_in_cents / 100 );
								$total_tax_in_euros += $user_tax_in_euros;
								$buffer_mail .= '- ' . $WDGUser->get_firstname() . ' ' . $WDGUser->get_lastname() . ' (' .$WDGUser->get_email(). ') a une taxe de ' . $roi_tax_item->percent_tax . ' % et paie donc ' . $user_tax_in_euros . ' €<br>';
							}
						}
					}
				}
			}

			if ( $buffer_mail != '' ) {
				$campaign_object = new ATCF_Campaign( FALSE, $roi_declaration->id_campaign );

				NotificationsSlack::tax_summaries( $campaign_object->get_name(), $total_tax_in_euros );
				NotificationsAsana::tax_summaries( $campaign_object->get_name(), $total_tax_in_euros );

				// TODO : faire le paiement automatique sur les comptes de WDG
				// Mais attente des premiers tests pour voir la véracité des infos
				// Puis le mettre en place
			}
		}
	}


	/******************************************************************************/
	/* ENVOI NOTIF TB PAS CREE PLUSIEURS JOURS APRES AVOIR PAYE */
	/******************************************************************************/
	public static function add_notifications_dashboard_not_created( $draft_id ) {
		$action = 'notifications_dashboard_not_created';
		$entity_id = $draft_id;
		$priority = self::$priority_date;
		$date_next_dispatch = new DateTime();
		$date_next_dispatch->add( new DateInterval( 'P3D' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array();
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_notifications_dashboard_not_created( $draft_id, $queued_action_params, $queued_action_id ) {
		if ( !empty( $draft_id ) ) {

			// Test si pas encore créé par l'utilisateur lié
			$api_result = WDGWPREST_Entity_Project_Draft::get_by_id( $draft_id );
			$has_created_project = false;
			if ( !empty( $api_result->id_user ) ) {
				$WDGUser = WDGUser::get_by_api_id( $api_result->id_user );
				$project_list = $WDGUser->get_projects_list();
				$has_created_project = !empty( $project_list );
			}
			
			if ( !$has_created_project ) {
				$metadata_decoded = json_decode( $api_result->metadata );
				NotificationsAPI::prospect_setup_dashboard_not_created( $api_result->email, $metadata_decoded->user->name, $metadata_decoded->organization->name );
			}
			
		}
	}

}