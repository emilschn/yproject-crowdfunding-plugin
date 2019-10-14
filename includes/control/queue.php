<?php
/**
 * Classe de gestion des queues d'actions
 */
class WDGQueue {
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
/* PROLONGATION CONTRAT ROYALTIES */
/******************************************************************************/
	public static function add_contract_extension_notifications( $campaign_id ) {
		$action = 'contract_extension_notifications';
		$entity_id = $campaign_id;
		$priority = 'high';
		
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
		$priority = 'high';
		
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
		$priority = 'high';
		
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
/* NOTIFICATIONS USER LORSQUE ERREURS DOCUMENTS LEMON WAY */
/******************************************************************************/
	public static function add_document_refused_user_notification( $user_id ) {
		$action = 'document_refused_user_notification';
		$entity_id = $user_id;
		$priority = 'high';
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

		$buffer_returns = '';
		if ( !empty( $wallet_details ) && !empty( $wallet_details->DOCS ) && !empty( $wallet_details->DOCS->DOC ) ) {
			foreach ( $wallet_details->DOCS->DOC as $document_object ) {
				// Type de document au format écrit pour l'utilisateur
				$document_type = '';
				if ( !empty( $document_object->TYPE ) ) {
					switch ( $document_object->TYPE ) {
						case LemonwayDocument::$document_type_id:
							$document_type = "La pièce d'identité principale";
							break;
						case LemonwayDocument::$document_type_home:
							$document_type = "Le justificatif de domicile";
							break;
						case LemonwayDocument::$document_type_bank:
							// Rien, le RIB ne bloque pas l'authentification
							break;
						case LemonwayDocument::$document_type_idbis:
							$document_type = "La deuxième pièce d'identité";
							break;
						case LemonwayDocument::$document_type_id_back:
							$document_type = "Le verso de la pièce d'identité principale";
							break;
						case LemonwayDocument::$document_type_residence_permit:
							$document_type = "Le permis de résidence";
							break;
						case LemonwayDocument::$document_type_kbis:
							$document_type = "Le KBIS de l'organisation";
							break;
						case LemonwayDocument::$document_type_status:
							$document_type = "Les statuts de l'organisation";
							break;
						case LemonwayDocument::$document_type_idbis_back:
							$document_type = "Le verso de la deuxième pièce d'identité";
							break;
						case LemonwayDocument::$document_type_selfie:
							$document_type = "Le selfie (Type 13)";
							break;
						case LemonwayDocument::$document_type_id2:
							$document_type = "La pièce d'identité de la deuxième personne (Type 16)";
							break;
						case LemonwayDocument::$document_type_home2:
							$document_type = "Le justificatif de domicile de la deuxième personne (Type 17)";
							break;
						case LemonwayDocument::$document_type_id3:
							$document_type = "La pièce d'identité de la troisième personne (Type 18)";
							break;
						case LemonwayDocument::$document_type_home3:
							$document_type = "Le justificatif de domicile de la troisième personne (Type 19)";
							break;
						case LemonwayDocument::$document_type_capital_allocation:
							$document_type = "Le document de répartition du capital (Type 20)";
							break;
					}
				}

				// Statut de document au format écrit pour l'utilisateur
				$document_status = '';
				if ( !empty( $document_object->S ) && $document_object->S > 2 ) {
					switch ( $document_object->S ) {
						case LemonwayDocument::$document_status_refused:
							$document_status = "refusé";
							break;
						case LemonwayDocument::$document_status_refused_unreadable:
							$document_status = "considéré illisible";
							break;
						case LemonwayDocument::$document_status_refused_expired:
							$document_status = "considéré expiré";
							break;
						case LemonwayDocument::$document_status_refused_wrong_type:
							$document_status = "considéré du mauvais type";
							break;
						case LemonwayDocument::$document_status_refused_wrong_person:
							$document_status = "considéré comme lié à une personne qui ne correspond pas";
							break;
					}
				}

				if ( !empty( $document_type ) && !empty( $document_status ) ) {
					$buffer_returns .= $document_type. " bloque l'authentification. Le document a été " .$document_status. ".";
					if ( !empty( $document_object->C ) ) {
						$buffer_returns .= " Commentaire complémentaire de Lemon Way : \"" .$document_object->C. "\"";
					}
					$buffer_returns .= '<br>';
				}
			}
		}


		// Temporairement on envoie la notification à admin ; à remplacer par template SIB
		if ( !empty( $buffer_returns) ) {
			NotificationsAPI::kyc_refused( $email, $name, $buffer_returns );
			/*$buffer_message = "-- MESSAGE TEST ADMIN --<br>";
			$buffer_message .= "(serait envoyé à " .$email. ")<br>";
			$buffer_message .= "Bonjour " . $name . ",<br>";
			$buffer_message .= "Notre prestataire a effectué des vérifications sur vos documents d'authentification.<br>";
			$buffer_message .= "Vous trouverez la liste des retours ci-dessous.<br>";
			$buffer_message .= "Il arrive que ces retours soient contestables. Dans ce cas, n'hésitez pas à nous contacter sur le chat en ligne ou à l'adresse investir@wedogood.co.<br><br>";
			$buffer_message .= $buffer_returns;
			$buffer_message .= "<br>";
			NotificationsEmails::send_mail( 'admin@wedogood.co', 'TEMP - Mail de retour de document', $buffer_message );*/
		}
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
			if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote || $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
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


	
/******************************************************************************/
/* GENERATION CACHE PAGE STATIQUE */
/******************************************************************************/
	public static function add_cache_post_as_html( $post_id, $input_priority = 'date' ) {
		$action = 'cache_post_as_html';
		$entity_id = $post_id;
		$priority = $input_priority;
		$date_next_dispatch = new DateTime();
		// On programme le prochain envoi 10 minutes plus tard
		$date_next_dispatch->add( new DateInterval( 'PT10M' ) );
		$date_priority = $date_next_dispatch->format( 'Y-m-d H:i:s' );
		$params = array();
		
		self::create_or_replace_action( $action, $entity_id, $priority, $params, $date_priority );
	}
	
	public static function execute_cache_post_as_html( $post_id, $queued_action_params, $queued_action_id ) {
		if ( !empty( $post_id ) ) {
			
			$WDG_File_Cacher = WDG_File_Cacher::current();
			$WDG_File_Cacher->build_post( $post_id );
			
		}
	}


	
	/******************************************************************************/
	/* TRANSFERT AUTOMATIQUE DE ROYALTIES */
	/******************************************************************************/
		public static function add_royalties_auto_transfer_start( $declaration_id, $date ) {
			$action = 'royalties_auto_transfer_start';
			$entity_id = $declaration_id;
			$priority = 'date';
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
				if ( $amount_wallet >= $roi_declaration->get_amount_with_adjustment() ) {
					self::add_royalties_auto_transfer_next( $declaration_id );

				} else {
					// Sinon on prévient qu'il n'y a plus assez
					$content_mail = "Il n'y a pas assez d'argent dans le wallet de royalties pour faire le versement trimestriel de " . $campaign->get_name();
					NotificationsEmails::send_mail( 'administratif@wedogood.co', 'Notif interne - Versement auto - Fonds insuffisants', $content_mail );

				}
			}
		}

		public static function add_royalties_auto_transfer_next( $declaration_id ) {
			$action = 'royalties_auto_transfer_next';
			$entity_id = $declaration_id;
			$priority = 'high';
			$params = array();
			self::create_or_replace_action( $action, $entity_id, $priority, $params );
		}
		
		public static function execute_royalties_auto_transfer_next( $declaration_id, $queued_action_params, $queued_action_id ) {
			if ( !empty( $declaration_id ) ) {

				$roi_declaration = new WDGROIDeclaration( $declaration_id );
				$result = $roi_declaration->make_transfer();
				if ( $result == 100 ) {
					$content_mail = "Transferts de royalties terminés pour le versement trimestriel de " . $campaign->get_name();
					NotificationsEmails::send_mail( 'administratif@wedogood.co', 'Notif interne - Versement auto - Terminé', $content_mail );
					NotificationsSlack::send_auto_transfer_done( $campaign->get_name() );

				} else {
					// Passage à complete avant, pour pouvoir en ajouter un à la suite
					WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, self::$status_complete );
					// On continue au prochain tour
					self::add_royalties_auto_transfer_next( $declaration_id );
				}
				
			}
		}

	
	
}