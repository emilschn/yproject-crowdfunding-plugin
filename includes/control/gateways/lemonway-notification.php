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
			
			if ( !empty( $WDGOrga_wallet ) ) {
				$user_name = $WDGOrga_wallet->get_name();
				$user_fullname = $WDGOrga_wallet->get_name();
				$user_email = $WDGOrga_wallet->get_email();
				if ( $lemonway_posted_wallet_status == 6 ) {
					$WDGUserInvestments = new WDGUserInvestments( $WDGOrga_wallet );
					$WDGUserInvestments->try_pending_card_investments();
				}
				
			} else {
				$user_name = $WDGUser_wallet->get_firstname();
				$user_fullname = $WDGUser_wallet->get_firstname(). ' ' .$WDGUser_wallet->get_lastname();
				$user_email = $WDGUser_wallet->get_email();
				if ( $lemonway_posted_wallet_status == 6 ) {
					$WDGUserInvestments = new WDGUserInvestments( $WDGUser_wallet );
					$WDGUserInvestments->try_pending_card_investments();
				}
			}
			
			if ( $lemonway_posted_wallet_status == 6 ) {
				NotificationsSlack::send_new_wallet_status( $lemonway_posted_id_external, "https://backoffice.lemonway.fr/wedogood/user-" .$lemonway_posted_id_internal, $user_fullname, 'Validé' );
				NotificationsAPI::kyc_authentified( $user_email, $user_name );
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
				$content_slack .= $WDGUser_wallet->get_display_name();
				$user_email = $WDGUser_wallet->get_email();
				$user_firstname = $WDGUser_wallet->get_firstname();
			}
			$content_slack .= "\n";
			
			$content_slack .= "Document : " . LemonwayDocument::get_document_type_str_by_type_id( $lemonway_posted_document_type );
			$content_slack .= "\n";
			
			$content_slack .= "Nouveau statut : " . LemonwayDocument::get_document_status_str_by_status_id( $lemonway_posted_document_status );
			$content_slack .= "\n";
			
			// Si le document n'est ni validé, ni en attente
			if ( $lemonway_posted_document_status > 2 ) {
				// Si c'est une personne physique, on prévient
				if ( empty( $WDGOrga_wallet ) ) {
					NotificationsAPI::kyc_refused( $user_email, $user_firstname );
				}
			}
		
			// On prévient l'équipe par Slack
			NotificationsSlack::send_new_doc_status( $content_slack );
			
			// Si le document est validé et qu'il s'agit du RIB et uniquement pour les personnes physiques, on prévient l'utilisateur
			if ( $lemonway_posted_document_status == 2 && $lemonway_posted_document_type == 2 && empty( $WDGOrga_wallet ) ) {
				NotificationsAPI::rib_authentified( $user_email, $user_firstname );
				$notification_sent = TRUE;
			}
		}
		
		
		// Si jamais la vraie notification n'est pas renvoyé, on envoie quand même la notif admin
		if ( !$notification_sent ) {
			// Mail admin
			$content = "Un document a changé de statut (et le mail normal n'a pas été envoyé) :<br>";
			$content .= '$lemonway_posted_date :' .$lemonway_posted_date. '<br>';
			$content .= '$lemonway_posted_id_internal :' .$lemonway_posted_id_internal. '<br>';
			$content .= '$lemonway_posted_id_external :' .$lemonway_posted_id_external. '<br>';
			$content .= '$lemonway_posted_document_id :' .$lemonway_posted_document_id. '<br>';
			$content .= '$lemonway_posted_document_type :' .$lemonway_posted_document_type. '<br>';
			$content .= '$lemonway_posted_document_status :' .$lemonway_posted_document_status. '<br>';
			NotificationsEmails::send_mail( 'emilien@wedogood.co', 'Notif interne - Changement statut document (données brutes)', $content, true );
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
		NotificationsEmails::send_mail( 'emilien@wedogood.co', 'Notif interne - Virement reçu', $content, true );
		
		// - Trouver l'utilisateur à partir de son identifiant externe
		$WDGUser_invest_author = WDGUser::get_by_lemonway_id( $lemonway_posted_id_external );
		if ( $WDGUser_invest_author !== FALSE ) {
			// - Parcourir ses paiements et trouver un investissement en attente correspondant au montant et de type virement
			$investment_id = FALSE;
			$investment_campaign_id = FALSE;
			$investments_by_campaign = $WDGUser_invest_author->get_pending_investments();
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
			ypcf_debug_log( 'PROCESS -> $trace = ' . $trace );
			ypcf_debug_log( 'PROCESS -> $investment_id = ' . $investment_id .  ' ; $investment_campaign_id = ' . $investment_campaign_id );
			
			if ( $investment_id != FALSE && $investment_campaign_id != FALSE ) {
				// - Faire le transfert vers le porte-monnaie du porteur de projet
				$post_campaign = get_post( $investment_campaign_id );
				$campaign = new ATCF_Campaign( $post_campaign );

				$campaign_organization = $campaign->get_organization();
				ypcf_debug_log( 'PROCESS -> $campaign_organization->wpref = ' . $campaign_organization->wpref );
				$organization_obj = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
				$invest_author = $WDGUser_invest_author;
				ypcf_debug_log( 'PROCESS -> $WDGUser_invest_author->wp_user->ID = ' . $WDGUser_invest_author->wp_user->ID );

				if ( WDGOrganization::is_user_organization($WDGUser_invest_author->wp_user->ID) ) {
					$invest_author = new WDGOrganization( $WDGUser_invest_author->wp_user->ID );
				}
				ypcf_debug_log( 'PROCESS -> $invest_author = ' . $invest_author->wp_user->ID );
				LemonwayLib::ask_transfer_funds( $invest_author->get_lemonway_id(), $organization_obj->get_lemonway_id(), $lemonway_posted_amount );
				
				$postdata = array(
					'ID'			=> $investment_id,
					'post_status'	=> 'publish',
					'edit_date'		=> current_time( 'mysql' )
				);
				wp_update_post($postdata);
				
				// - Créer le contrat pdf
				// - Envoyer validation d'investissement par mail
				if ( $lemonway_posted_amount > 1500 ) {
					$contract_id = ypcf_create_contract( $investment_id, $investment_campaign_id, $WDGUser_invest_author->wp_user->ID );
					if ($contract_id != '') {
						$contract_infos = signsquid_get_contract_infos( $contract_id );
						NotificationsEmails::new_purchase_user_success( $investment_id, $contract_infos->{'signatories'}[0]->{'code'}, FALSE, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
						NotificationsEmails::new_purchase_admin_success( $investment_id );
					} else {
						global $contract_errors;
						$contract_errors = 'contract_failed';
						NotificationsEmails::new_purchase_user_error_contract( $investment_id, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
						NotificationsEmails::new_purchase_admin_error_contract( $investment_id );
					}
				} else {
					$new_contract_pdf_file = getNewPdfToSign( $investment_campaign_id, $investment_id, $WDGUser_invest_author->wp_user->ID );
					NotificationsEmails::new_purchase_user_success_nocontract( $investment_id, $new_contract_pdf_file, FALSE, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
					NotificationsEmails::new_purchase_admin_success_nocontract( $investment_id, $new_contract_pdf_file );
				}
			} else {
				NotificationsEmails::send_mail( 'emilien@wedogood.co', 'Notif interne - Virement reçu - erreur', '$investment_id == FALSE || $investment_campaign_id == FALSE => ' . $trace, true );
			}
		} else {
			NotificationsEmails::send_mail( 'emilien@wedogood.co', 'Notif interne - Virement reçu - erreur', '$WDGUser_invest_author === FALSE', true );
		}
	}
	
}
