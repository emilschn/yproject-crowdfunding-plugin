<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe de gestion des notifications envoyées par Lemon Way
 */
class LemonwayNotification {
	/**
	 * NotifCategory : Type de notification :
	- 8 : Changement de statut d'un wallet
	- 9 : Changement de statut d'un document
	- 10 : MoneyIn par virement
	- 11 : MoneyIn par SDD
	- 12 : MoneyIn par chéque
	 * Ex : 10
	 */
	private static $category_wallet_new_status = 8;
	private static $category_document_new_status = 9;
	private static $category_moneyin_wire = 10;
	private static $category_moneyin_mandate = 11;
	
	private $notification_category;
	
	/**
	 * Le constructeur se charge de diriger la bonne exécution de notification
	 * @param int $notification_category
	 */
	public function __construct( $notification_category ) {
		$this->notification_category = $notification_category;
		
		switch ( $this->notification_category ) {
			case LemonwayNotification::$category_wallet_new_status:
				$this->process_wallet_new_status();
				break;
			
			case LemonwayNotification::$category_document_new_status:
				$this->process_document_new_status();
				break;
			
			case LemonwayNotification::$category_moneyin_wire:
				$this->process_moneyin_wire();
				break;
			
			case LemonwayNotification::$category_moneyin_mandate:
				$this->process_moneyin_mandate();
				break;
		}
	}
	
	/**
	 * Exécution de la notification en cours
	 */
	public static function process() {
		$notification_category = filter_input( INPUT_POST, 'NotifCategory' );

		if ( !empty( $notification_category ) ) {
			return new self( $notification_category );
		}
		return FALSE;
	}
	
	/**
	 * Changement de statut d'un wallet
	 */
	private function process_wallet_new_status() {
		/**
		 * NotifDate : Date et heure de la creation de la notification. Heure de Paris. Format ISO8601
		 * Ex : 2015-11-01T16:44:55.883
		 */
		$lemonway_posted_date = filter_input( INPUT_POST, 'NotifDate' );
		/**
		 * IntId : Identifiant interne du wallet
		 * Ex : 32
		 */
		$lemonway_posted_id_internal = filter_input( INPUT_POST, 'IntId' );
		/**
		 * ExtId : Identifiant externe du wallet
		 * Ex : USERW3987
		 */
		$lemonway_posted_id_external = filter_input( INPUT_POST, 'ExtId' );
		/**
		 * Status : Type du document
		 * Ex : 2
		 */
		$lemonway_posted_wallet_status = filter_input( INPUT_POST, 'Status' );
		
		// Trouver l'utilisateur à partir de son identifiant externe
		$WDGOrga_wallet = FALSE;
		$WDGUser_wallet = WDGUser::get_by_lemonway_id( $lemonway_posted_id_external );
		if ( WDGOrganization::is_user_organization( $WDGUser_wallet->get_wpref() ) ) {
			$WDGOrga_wallet = new WDGOrganization( $WDGUser_wallet->get_wpref() );
		}
		if ( $WDGUser_wallet !== FALSE ) {
			
			$pending_not_validated_investment_campaign_name = FALSE;
			$WDGUserInvestments = FALSE;
			
			if ( !empty( $WDGOrga_wallet ) ) {
				$user_name = $WDGOrga_wallet->get_name();
				$user_fullname = $WDGOrga_wallet->get_name();
				$user_email = $WDGOrga_wallet->get_email();
				if ( $lemonway_posted_wallet_status == 6 ) {
					$WDGUserInvestments = new WDGUserInvestments( $WDGOrga_wallet );
				}
				
			} else {
				$user_name = $WDGUser_wallet->get_firstname();
				$user_fullname = $WDGUser_wallet->get_firstname(). ' ' .$WDGUser_wallet->get_lastname();
				$user_email = $WDGUser_wallet->get_email();
				if ( $lemonway_posted_wallet_status == 6 ) {
					$WDGUserInvestments = new WDGUserInvestments( $WDGUser_wallet );
				}
			}
			
			if ( !empty( $WDGUserInvestments ) ) {
				$WDGUserInvestments->try_transfer_waiting_roi_to_wallet();
				if ( $WDGUserInvestments->has_pending_not_validated_investments() ) {
					$pending_not_validated_investment = $WDGUserInvestments->get_first_pending_not_validated_investment();
					$pending_not_validated_investment_campaign_name = $pending_not_validated_investment->get_saved_campaign()->data->post_title;
				}
			}
			
			
			if ( $lemonway_posted_wallet_status == 6 ) {
				NotificationsSlack::send_new_wallet_status( $lemonway_posted_id_external, "https://backoffice.lemonway.fr/wedogood/user-" .$lemonway_posted_id_internal, $user_fullname, 'Validé' );
				if ( !empty( $pending_not_validated_investment_campaign_name ) ) {
					NotificationsAPI::kyc_authentified_and_pending_investment( $user_email, $user_name, $pending_not_validated_investment_campaign_name, $pending_not_validated_investment->get_saved_campaign()->get_api_id() );
					WDGQueue::add_investment_authentified_reminder( $WDGUser_wallet->get_wpref(), $user_email, $user_name, $pending_not_validated_investment_campaign_name, $pending_not_validated_investment->get_saved_campaign()->get_api_id() );
				} else {
					NotificationsAPI::kyc_authentified( $user_email, $user_name );
				}

				if ( $WDGUser_wallet->has_subscribed_authentication_notification() ) {
					WDGQueue::add_document_user_phone_notification( $WDGUser_wallet->get_wpref(), 'authentified' );
				}
				
			} else {
				NotificationsSlack::send_new_wallet_status( $lemonway_posted_id_external, "https://backoffice.lemonway.fr/wedogood/user-" .$lemonway_posted_id_internal, $user_fullname, $lemonway_posted_wallet_status );
			}
			
		}
	}
	
	/**
	 * Changement de statut d'un document
	 */
	private function process_document_new_status() {
		/**
		 * NotifDate : Date et heure de la creation de la notification. Heure de Paris. Format ISO8601
		 * Ex : 2015-11-01T16:44:55.883
		 */
		$lemonway_posted_date = filter_input( INPUT_POST, 'NotifDate' );
		/**
		 * IntId : Identifiant interne du wallet
		 * Ex : 32
		 */
		$lemonway_posted_id_internal = filter_input( INPUT_POST, 'IntId' );
		/**
		 * ExtId : Identifiant externe du wallet
		 * Ex : USERW3987
		 */
		$lemonway_posted_id_external = filter_input( INPUT_POST, 'ExtId' );
		/**
		 * DocId : Identifiant du document
		 * Ex : 4646
		 */
		$lemonway_posted_document_id = filter_input( INPUT_POST, 'DocId' );
		/**
		 * DocType : Type du document
		 * Ex : 0
		 */
		$lemonway_posted_document_type = filter_input( INPUT_POST, 'DocType' );
		/**
		 * Status : Type du document
		 * Ex : 2
		 */
		$lemonway_posted_document_status = filter_input( INPUT_POST, 'Status' );
		
		$notification_sent = FALSE;
		
		// Trouver l'utilisateur à partir de son identifiant externe
		$WDGOrga_wallet = FALSE;
		$WDGUser_wallet = WDGUser::get_by_lemonway_id( $lemonway_posted_id_external );
		if ( WDGOrganization::is_user_organization( $WDGUser_wallet->get_wpref() ) ) {
			$WDGOrga_wallet = new WDGOrganization( $WDGUser_wallet->get_wpref() );
		}
		if ( $WDGUser_wallet !== FALSE ) {
			$content_slack = "Nouveau statut de document : ";
			
			$content_slack .= "Wallet " .$lemonway_posted_id_external. " (https://backoffice.lemonway.fr/wedogood/user-" .$lemonway_posted_id_internal."), appartenant à ";
			if ( !empty( $WDGOrga_wallet ) ) {
				$content_slack .= $WDGOrga_wallet->get_name();
			} else {
				$user_email = $WDGUser_wallet->get_email();
				$user_firstname = $WDGUser_wallet->get_firstname();
				$content_slack .= $user_firstname . ' ' . $WDGUser_wallet->get_lastname() . ' (' . $user_email . ')';
			}
			$content_slack .= "\n";
			
			$content_slack .= "Document : " . LemonwayDocument::get_document_type_str_by_type_id( $lemonway_posted_document_type );
			$content_slack .= "\n";
			
			$content_slack .= "Nouveau statut : " . LemonwayDocument::get_document_status_str_by_status_id( $lemonway_posted_document_status );
			$content_slack .= "\n";
			
			// Notifications pour indiquer les documents non-validés
			// Si le document n'est ni validé, ni en attente
			if ( $lemonway_posted_document_status > 2 ) {
				// Seulement si c'est une personne physique
				if ( empty( $WDGOrga_wallet ) ) {
					if ( !$WDGUser_wallet->is_lemonway_registered() ) {
						// On n'envoie des notifications admin que pour les documents qui sont utiles pour l'authentification (pas le RIB)
						if ( $lemonway_posted_document_type != LemonwayDocument::$document_type_bank ) {
							WDGQueue::add_document_refused_user_notification( $WDGUser_wallet->get_wpref() );
							WDGQueue::add_document_refused_admin_notification( $WDGUser_wallet->get_wpref(), $lemonway_posted_document_type, $lemonway_posted_document_status );
						}
					}
				}
			
			// Notifications pour indiquer que les documents sont validés mais que le wallet ne l'est pas
			} else if ( $lemonway_posted_document_status == 2 ) {
				$wallet_details = FALSE;
				$user_wpref = FALSE;

				// Si c'est une organisation pas authentifiée
				if ( !empty( $WDGOrga_wallet ) && !$WDGOrga_wallet->is_registered_lemonway_wallet() ) {
					$wallet_details = $WDGOrga_wallet->get_wallet_details();
					$user_wpref = $WDGOrga_wallet->get_wpref();

				// Si c'est une personne physique pas authentifiée
				} else if ( empty( $WDGOrga_wallet ) && !$WDGUser_wallet->is_lemonway_registered() ) {
					$wallet_details = $WDGUser_wallet->get_wallet_details();
					$user_wpref = $WDGUser_wallet->get_wpref();
				}

				$has_all_documents_validated = TRUE;
				// Flag permettant de savoir si les documents validés ne concernent que la première pièce d'identité ou le RIB
				// On ne fait cette vérification que si il s'agit de la validation du recto ou verso de la première pièce
				$only_first_document = ( $lemonway_posted_document_type == LemonwayDocument::$document_type_id || $lemonway_posted_document_type == LemonwayDocument::$document_type_id_back );

				// On vérifie si tous les documents sont validés
				if ( !empty( $wallet_details ) && !empty( $wallet_details->DOCS ) && !empty( $wallet_details->DOCS->DOC ) ) {
					foreach ( $wallet_details->DOCS->DOC as $document_object ) {
						if ( !empty( $document_object->S ) && $document_object->S != 2 ) {
							$has_all_documents_validated = FALSE;
						}
						// Si le document est validé et que ce n'est pas la première pièce ou le RIB, on n'envoie pas de notif à ce sujet
						if ( $document_object->S == 2 
									&& $document_object->TYPE != LemonwayDocument::$document_type_id
									&& $document_object->TYPE != LemonwayDocument::$document_type_id_back
									&& $document_object->TYPE != LemonwayDocument::$document_type_bank ) {
							$only_first_document = FALSE;
						}
					}
				}
				
				// Si ils sont tous validés, on enverra une notification plus tard
				if ( $has_all_documents_validated && !empty( $user_wpref ) ) {
					if ( $only_first_document && empty( $WDGOrga_wallet ) ) {
						NotificationsAPI::kyc_single_validated( $user_email, $user_firstname );
						if ( $WDGUser_wallet->has_subscribed_authentication_notification() ) {
							WDGQueue::add_document_user_phone_notification( $user_wpref, 'one_doc' );
						}
						
					} else {
						WDGQueue::add_document_validated_but_not_wallet_admin_notification( $user_wpref );
					}
				}
			}
		
			// On prévient l'équipe par Slack
			NotificationsSlack::send_new_doc_status( $content_slack );
			
			// Si le document est validé et qu'il s'agit du RIB et uniquement pour les personnes physiques, on prévient l'utilisateur
			if ( $lemonway_posted_document_status == 2 && $lemonway_posted_document_type == LemonwayDocument::$document_type_bank && empty( $WDGOrga_wallet ) ) {
				NotificationsAPI::rib_authentified( $user_email, $user_firstname );
				$notification_sent = TRUE;
			}
		}
	}
	
	/**
	 * Arrivée d'un nouveau virement
	 */
	private function process_moneyin_wire() {
		/**
		 * NotifDate : Date et heure de la creation de la notification. Heure de Paris. Format ISO8601
		 * Ex : 2015-11-01T16:44:55.883
		 */
		$lemonway_posted_date = filter_input( INPUT_POST, 'NotifDate' );
		/**
		 * IntId : Identifiant interne du wallet
		 * Ex : 32
		 */
		$lemonway_posted_id_internal = filter_input( INPUT_POST, 'IntId' );
		/**
		 * ExtId : Identifiant externe du wallet
		 * Ex : USERW3987
		 */
		$lemonway_posted_id_external = filter_input( INPUT_POST, 'ExtId' );
		/**
		 * IdTransaction : Identifiant de la transaction
		 * Ex : 204
		 */
		$lemonway_posted_id_transaction = filter_input( INPUT_POST, 'IdTransaction' );
		/**
		 * Amount : Montant à créditer au wallet (total moins la commission)
		 * Ex : 10.00
		 */
		$lemonway_posted_amount = filter_input( INPUT_POST, 'Amount' );
		/**
		 * Status : Statut de la transaction
		 * Ex : 0
		 */
		$lemonway_posted_status = filter_input( INPUT_POST, 'Status' );

	
		$content = 'Un virement a été reçu avec les infos suivantes :<br />';
		$content .= '$lemonway_posted_date :' .$lemonway_posted_date. '<br />';
		$content .= '$lemonway_posted_id_internal :' .$lemonway_posted_id_internal. '<br />';
		$content .= '$lemonway_posted_id_external :' .$lemonway_posted_id_external. '<br />';
		$content .= '$lemonway_posted_id_transaction :' .$lemonway_posted_id_transaction. '<br />';
		$content .= '$lemonway_posted_amount :' .$lemonway_posted_amount. '<br />';
		$content .= '$lemonway_posted_status :' .$lemonway_posted_status. '<br />';
		NotificationsEmails::send_mail( 'administratif@wedogood.co', 'Notif interne - Virement reçu', $content, true );
		
		// - Trouver l'utilisateur à partir de son identifiant externe
		$WDGUser_invest_author = WDGUser::get_by_lemonway_id( $lemonway_posted_id_external );
		$WDGOrga_invest_author = false;
		if ( WDGOrganization::is_user_organization( $WDGUser_invest_author->get_wpref() ) ) {
			$WDGOrga_invest_author = new WDGOrganization( $WDGUser_invest_author->get_wpref() );
			$linked_users_creator = $WDGOrga_invest_author->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			if ( !empty( $linked_users_creator ) ) {
				$WDGUser_invest_author = $linked_users_creator[ 0 ];
			}
		}
		if ( $WDGUser_invest_author !== FALSE ) {
			// - Parcourir ses paiements et trouver un investissement en attente correspondant au montant et de type virement
			$investment_id = FALSE;
			$investment_campaign_id = FALSE;
			if ( !empty( $WDGOrga_invest_author ) ) {
				$investments_by_campaign = $WDGOrga_invest_author->get_pending_investments();
			} else {
				$investments_by_campaign = $WDGUser_invest_author->get_pending_investments();
			}
			
			$trace = '';
			foreach ( $investments_by_campaign as $campaign_id => $campaign_investments ) {
				$trace .= 'A';
				foreach ($campaign_investments as $campaign_investment_id) {
					$trace .= 'B';
					$payment_key = edd_get_payment_key( $campaign_investment_id );
					if ( strpos( $payment_key, 'wire_' ) !== FALSE ) {
						$trace .= 'C';
						$payment_amount = edd_get_payment_amount( $campaign_investment_id );
						if ( $payment_amount == $lemonway_posted_amount ) {
							$trace .= 'D';
							$investment_campaign_id = $campaign_id;
							$investment_id = $campaign_investment_id;
						}
					}
				}
			}
			ypcf_debug_log( 'PROCESS -> $trace = ' . $trace, FALSE );
			ypcf_debug_log( 'PROCESS -> $investment_id = ' . $investment_id .  ' ; $investment_campaign_id = ' . $investment_campaign_id, FALSE );
			
			if ( $investment_id != FALSE && $investment_campaign_id != FALSE ) {
				// - Faire le transfert vers le porte-monnaie du porteur de projet
				$post_campaign = get_post( $investment_campaign_id );
				$campaign = new ATCF_Campaign( $post_campaign );

				$campaign_organization = $campaign->get_organization();
				ypcf_debug_log( 'PROCESS -> $campaign_organization->wpref = ' . $campaign_organization->wpref, FALSE );
				$organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
				$invest_author = $WDGUser_invest_author;
				ypcf_debug_log( 'PROCESS -> $WDGUser_invest_author->wp_user->ID = ' . $WDGUser_invest_author->wp_user->ID, FALSE );
				ypcf_debug_log( 'PROCESS -> $invest_author = ' . $invest_author->wp_user->ID, FALSE );
				$lemonway_id = $WDGUser_invest_author->get_lemonway_id();
				if ( !empty( $WDGOrga_invest_author ) ) {
					$lemonway_id = $WDGOrga_invest_author->get_lemonway_id();
				}
				$organization_obj->check_register_campaign_lemonway_wallet();
				LemonwayLib::ask_transfer_funds( $lemonway_id, $organization_obj->get_campaign_lemonway_id(), $lemonway_posted_amount );
				
				// Si la campagne n'est pas en cours d'évaluation, on peut valider l'investissement
				if ( $campaign->campaign_status() != ATCF_Campaign::$campaign_status_vote ) {
					$postdata = array(
						'ID'			=> $investment_id,
						'post_status'	=> 'publish',
						'edit_date'		=> current_time( 'mysql' )
					);
					wp_update_post($postdata);

				} else {
					add_post_meta( $investment_id, 'has_received_wire', '1' );
				}
				
				// - Créer le contrat pdf
				// - Envoyer validation d'investissement par mail
				if ( $lemonway_posted_amount >= WDGInvestmentSignature::$investment_amount_signature_needed_minimum ) {
					$WDGInvestmentSignature = new WDGInvestmentSignature( $investment_id );
					$contract_id = $WDGInvestmentSignature->create_eversign();
					if ( !empty( $contract_id ) ) {
						NotificationsEmails::new_purchase_user_success( $investment_id, FALSE, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
						
					} else {
						global $contract_errors;
						$contract_errors = 'contract_failed';
						NotificationsEmails::new_purchase_user_error_contract( $investment_id, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
						NotificationsEmails::new_purchase_admin_error_contract( $investment_id );
					}
					
				} else {
					$new_contract_pdf_file = getNewPdfToSign( $investment_campaign_id, $investment_id, $WDGUser_invest_author->wp_user->ID );
					NotificationsEmails::new_purchase_user_success_nocontract( $investment_id, $new_contract_pdf_file, FALSE, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
				}
				
				NotificationsSlack::send_new_investment( $campaign->get_name(), $lemonway_posted_amount, $invest_author->get_email() );
				NotificationsEmails::new_purchase_team_members( $investment_id );
				if ( $campaign->campaign_status() != ATCF_Campaign::$campaign_status_vote ) {
					$WDGInvestment = new WDGInvestment( $investment_id );
					$WDGInvestment->save_to_api();
				}

			} else {
				if ( empty( $WDGOrga_invest_author ) ) {
					$recipient_email = $WDGUser_invest_author->get_email();
					$recipient_name = $WDGUser_invest_author->get_firstname();
					$wallet_details = $WDGUser_invest_author->get_wallet_details();
					$amount = $wallet_details->BAL;
					NotificationsAPI::wire_transfer_received( $recipient_email, $recipient_name, $amount );
				} else {
					NotificationsEmails::send_mail( 'administratif@wedogood.co', 'Notif interne - Virement reçu - ORGA - erreur', '$investment_id == FALSE || $investment_campaign_id == FALSE => ' . $trace, true );
				}
			}
		} else {
			NotificationsEmails::send_mail( 'administratif@wedogood.co', 'Notif interne - Virement reçu - erreur', '$WDGUser_invest_author === FALSE', true );
		}
	}
	
	private function process_moneyin_mandate() {
		/**
		 * NotifDate : Date et heure de la creation de la notification. Heure de Paris. Format ISO8601
		 * Ex : 2015-11-01T16:44:55.883
		 */
		$lemonway_posted_date = filter_input( INPUT_POST, 'NotifDate' );
		/**
		 * IntId : Identifiant interne du wallet
		 * Ex : 32
		 */
		$lemonway_posted_id_internal = filter_input( INPUT_POST, 'IntId' );
		/**
		 * ExtId : Identifiant externe du wallet
		 * Ex : USERW3987
		 */
		$lemonway_posted_id_external = filter_input( INPUT_POST, 'ExtId' );
		/**
		 * IdTransaction : Identifiant de la transaction
		 * Ex : 204
		 */
		$lemonway_posted_id_transaction = filter_input( INPUT_POST, 'IdTransaction' );
		/**
		 * Amount : Montant à créditer au wallet (total moins la commission)
		 * Ex : 10.00
		 */
		$lemonway_posted_amount = filter_input( INPUT_POST, 'Amount' );
		/**
		 * Status : Statut de la transaction
		 * Ex : 0
		 */
		$lemonway_posted_status = filter_input( INPUT_POST, 'Status' );

		// Préparation du mail de notification
		$content = 'Un prélèvement a été reçu avec les infos suivantes :<br>';
		$content .= '$lemonway_posted_date :' .$lemonway_posted_date. '<br>';
		$content .= '$lemonway_posted_id_internal :' .$lemonway_posted_id_internal. '<br>';
		$content .= '$lemonway_posted_id_external :' .$lemonway_posted_id_external. '<br>';
		$content .= '$lemonway_posted_id_transaction :' .$lemonway_posted_id_transaction. '<br>';
		$content .= '$lemonway_posted_amount :' .$lemonway_posted_amount. '<br>';
		$content .= '$lemonway_posted_status :' .$lemonway_posted_status. '<br>';
		NotificationsEmails::send_mail( 'administratif@wedogood.co', 'Notif interne - Prélèvement reçu', $content );
		
		$content_mail_auto_royalties = '';

		$WDGUser_wallet = WDGUser::get_by_lemonway_id( $lemonway_posted_id_external );
		if ( WDGOrganization::is_user_organization( $WDGUser_wallet->get_wpref() ) ) {
			
			// Transfert vers le wallet de séquestre de royalties
			$WDGOrga_wallet = new WDGOrganization( $WDGUser_wallet->get_wpref() );
			$WDGOrga_wallet->check_register_royalties_lemonway_wallet();
			$transaction_details = LemonwayLib::get_transaction_by_id( $lemonway_posted_id_transaction, 'transactionId' );
			$transfer_amount = $transaction_details->CRED;
			LemonwayLib::ask_transfer_funds( $WDGOrga_wallet->get_lemonway_id(), $WDGOrga_wallet->get_royalties_lemonway_id(), $transfer_amount );

			// Récupération des projets pour voir les versements de royalties en attente
			$list_campaign_orga = $WDGOrga_wallet->get_campaigns();
			if ( !empty( $list_campaign_orga ) ) {
				foreach ( $list_campaign_orga as $project ) {
					$campaign = new ATCF_Campaign( $project->wpref );
					$list_declarations_campaign = WDGROIDeclaration::get_list_by_campaign_id( $project->wpref, WDGROIDeclaration::$status_waiting_transfer );
					if ( !empty( $list_declarations_campaign ) ) {
						foreach( $list_declarations_campaign as $declaration ) {
							$list_investments = $campaign->roi_payments_data( $declaration );
							$total_roi = 0;
							foreach ($list_investments as $investment_item) {
								$total_roi += $investment_item[ 'roi_amount' ];
							}

							// Calcul de la date à laquelle on fera le versement auto
							$date_of_royalties_transfer = new DateTime();
							$date_of_royalties_transfer->add( new DateInterval( 'P3D' ) );
							// Si lundi, on fera un jour plus tard
							if ( $date_of_royalties_transfer->format( 'N' ) == 1 ) {
								$date_of_royalties_transfer->add( new DateInterval( 'P1D' ) );
							}
							// Si samedi, on fera un jour plus tard
							if ( $date_of_royalties_transfer->format( 'N' ) == 6 ) {
								$date_of_royalties_transfer->add( new DateInterval( 'P1D' ) );
							}
							// Si dimanche, on fera un jour plus tard
							if ( $date_of_royalties_transfer->format( 'N' ) == 7 ) {
								$date_of_royalties_transfer->add( new DateInterval( 'P1D' ) );
							}
							$date_of_royalties_transfer->setTime( 15, 30, 0 );

							$content_mail_auto_royalties .= 'Versement pour ' . $campaign->get_name() . '<br>';
							$content_mail_auto_royalties .= 'Declaration du ' . $declaration->get_formatted_date() . '<br>';
							$content_mail_auto_royalties .= 'Programmé pour ' . $date_of_royalties_transfer->format( 'd/m/Y H:i:s' ) . '<br>';
							$content_mail_auto_royalties .= 'Montant avec ajustement : ' . $declaration->get_amount_with_adjustment() . ' €<br>';
							$content_mail_auto_royalties .= 'Montant versé aux investisseurs : ' . $total_roi . ' €<br><br>';

							// Programmer versement auto
							WDGQueue::add_royalties_auto_transfer_start( $declaration->id, $date_of_royalties_transfer );
						}
					}
				}
			}
		}

		if ( !empty( $content_mail_auto_royalties ) ) {
			NotificationsEmails::send_mail( 'administratif@wedogood.co', 'Notif interne - Versement auto à venir', $content_mail_auto_royalties );
		}

	}
	
}
