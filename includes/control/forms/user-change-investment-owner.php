<?php
class WDG_Form_User_Change_Investment_Owner extends WDG_Form {
	public static $name = 'user-change-investment-owner';

	public function __construct() {
		parent::__construct( self::$name );
	}

	public function postForm() {
		parent::postForm();

		$feedback_success = array();
		$feedback_errors = array();

		$action = filter_input( INPUT_POST, 'action' );
		if ( empty( $action ) || $action != 'change_investment_owner' ) {
			return FALSE;
		}

		$investid = filter_input( INPUT_POST, 'investid' );
		if ( empty( $investid ) ) {
			return FALSE;
		}

		$wpuser_recipient_by_email = FALSE;
		$email = filter_input( INPUT_POST, 'e-mail' );
		if ( empty( $email ) ) {
			$error = array(
				'code'		=> 'new_account_email_empty',
				'text'		=> 'Problème de transmission de mail',
				'element'	=> 'e-mail'
			);
			array_push( $feedback_errors, $error );
		} else {
			$wpuser_recipient_by_email = get_user_by( 'email', $email );
			if ( empty( $wpuser_recipient_by_email ) ) {
				$error = array(
					'code'		=> 'new_account_email_not_existing',
					'text'		=> 'Aucun compte ne correspond à cette adresse',
					'element'	=> 'e-mail'
				);
				array_push( $feedback_errors, $error );
			}
		}

		$log_report = '';

		if ( empty( $feedback_errors ) ) {
			$feedback_success = 'Compte trouvé et investissement transféré !';
			$log_report .= '$investid : ' . $investid . ' --- ';
			$log_report .= '$email : ' . $email . ' --- ';

			// Récupération des listes d'investissement et de contrats d'investissement de l'investisseur d'origine
			$WDGInvestment = new WDGInvestment( $investid );
			$campaign = $WDGInvestment->get_saved_campaign();
			$id_user_sender = $WDGInvestment->get_saved_user_id();
			$id_api_sender = 0;
			$amount_on_sender_wallet = 0;
			$id_lw_sender = '';
			$list_rois = array();
			if ( WDGOrganization::is_user_organization( $id_user_sender ) ) {
				$WDGOrganization_sender = new WDGOrganization( $id_user_sender );
				$id_api_sender = $WDGOrganization_sender->get_api_id();
				$list_investments_user_sender = WDGWPREST_Entity_Organization::get_investments( $id_api_sender );
				$list_investment_contracts_user_sender = WDGWPREST_Entity_Organization::get_investment_contracts( $id_api_sender );
				$amount_on_sender_wallet = $WDGOrganization_sender->get_available_rois_amount();
				$list_rois = WDGWPREST_Entity_Organization::get_rois( $id_api_sender );
				$id_lw_sender = $WDGOrganization_sender->get_lemonway_id();
			} else {
				$WDGUser_sender = new WDGUser( $id_user_sender );
				$id_api_sender = $WDGUser_sender->get_api_id();
				$list_investments_user_sender = WDGWPREST_Entity_User::get_investments( $id_api_sender );
				$list_investment_contracts_user_sender = WDGWPREST_Entity_User::get_investment_contracts( $id_api_sender );
				$amount_on_sender_wallet = $WDGUser_sender->get_lemonway_wallet_amount();
				$list_rois = WDGWPREST_Entity_User::get_rois( $id_api_sender );
				$id_lw_sender = $WDGUser_sender->get_lemonway_id();
			}
			$amount_royalties_received = 0;
			foreach ( $list_rois as $roi_item ) {
				if ( $roi_item->status == WDGROI::$status_transferred ) {
					$amount_royalties_received += $roi_item->amount;
				}
			}
			$log_report .= '$id_user_sender : ' . $id_user_sender . ' --- ';
			$log_report .= '$id_api_sender : ' . $id_api_sender . ' --- ';
			$log_report .= '$amount_on_sender_wallet : ' . $amount_on_sender_wallet . ' --- ';
			$log_report .= '$id_lw_sender : ' . $id_lw_sender . ' --- ';
			$log_report .= '$amount_royalties_received : ' . $amount_royalties_received . ' --- ';

			// Récupération du type du nouvel investisseur (orga / user)
			$id_api_recipient = 0;
			$user_type_recipient = WDGOrganization::is_user_organization( $wpuser_recipient_by_email->ID ) ? 'orga' : 'user';
			$id_lw_recipient = '';
			if ( $user_type_recipient == 'user' ) {
				$WDGUser = new WDGUser( $wpuser_recipient_by_email->ID );
				$id_api_recipient = $WDGUser->get_api_id();
				$id_lw_recipient = $WDGUser->get_lemonway_id();
			} else {
				$WDGOrganization = new WDGOrganization( $wpuser_recipient_by_email->ID );
				$id_api_recipient = $WDGOrganization->get_api_id();
				$id_lw_recipient = $WDGOrganization->get_lemonway_id();
			}
			$log_report .= '$id_api_recipient : ' . $id_api_recipient . ' --- ';
			$log_report .= '$id_lw_recipient : ' . $id_lw_recipient . ' --- ';

			// Changement d'id investisseur sur la donnée d'investissement sur le site
			// post_author
			$postdata = array(
				'ID'			=> $investid,
				'post_author'	=> $wpuser_recipient_by_email->ID
			);
			wp_update_post( $postdata );
			// post_author du post de log
			$log_post_items = get_posts(array(
				'post_type'		=> WDGInvestment::$log_post_type,
				'meta_key'		=> WDGInvestment::$log_meta_key_payment_id,
				'meta_value'	=> $investid
			));
			foreach ( $log_post_items as $log_post_item ) {
				$postdata = array(
					'ID'			=> $log_post_item->ID,
					'post_author'	=> $wpuser_recipient_by_email->ID
				);
				wp_update_post($postdata);
			}

			// Deprecated metas edd_payment id and email
			$WDGInvestment->update_deprecated_meta( WDGInvestment::$payment_meta_key_customer_id, $wpuser_recipient_by_email->ID );
			$WDGInvestment->update_deprecated_meta( WDGInvestment::$payment_meta_key_user_id, $wpuser_recipient_by_email->ID );
			$WDGInvestment->update_deprecated_meta( WDGInvestment::$payment_meta_key_simple_customer_id, $wpuser_recipient_by_email->ID );
			$WDGInvestment->update_deprecated_meta( WDGInvestment::$payment_meta_key_simple_user_id, $wpuser_recipient_by_email->ID );
			
			$current_meta = get_post_meta( $investid, WDGInvestment::$payment_meta_key_meta, TRUE );
			if ( is_array( $current_meta ) ) {
				$current_meta['user_info']['id']  = $wpuser_recipient_by_email->ID;
				update_post_meta( $investid, WDGInvestment::$payment_meta_key_meta, $current_meta );
			}
			$WDGInvestment->update_deprecated_meta( WDGInvestment::$payment_meta_key_user_email, $email );
			$WDGInvestment->update_deprecated_meta( WDGInvestment::$payment_meta_key_simple_user_email, $email );
			$WDGInvestment->update_deprecated_meta( WDGInvestment::$payment_meta_key_simple_email, $email );

			// Changement d'id investisseur sur la donnée d'investissement sur l'API
			$WDGInvestment->save_to_api();

			// Si il y a une donnée "contrat d'investissement" liée à cet investissement, on change l'investisseur
			foreach ( $list_investment_contracts_user_sender as $investment_contract_item ) {
				if ( $investment_contract_item->subscription_id == $investid ) {
					$investment_contract_item->investor_id = $id_api_recipient;
					$investment_contract_item->investor_type = $user_type_recipient;
					$log_report .= '$investment_contract_item->id : ' . $investment_contract_item->id . ' --- ';
					WDGWPREST_Entity_InvestmentContract::update( $investment_contract_item->id, $investment_contract_item );

					// Et on ajoute aussi une ligne d'historique
					$date = new DateTime();
					$data_modified = 'user_id';
					$old_value = $id_api_sender;
					$new_value = $id_api_recipient;
					$list_new_contracts = '';
					$comment = 'Cession contrat';
					WDGWPREST_Entity_InvestmentContractHistory::create($investment_contract_item->id, $date->format('Y-m-d H:i:s'), $data_modified, $old_value, $new_value, $list_new_contracts, $comment );
				}
			}

			// Changement de nom de fichier du contrat (pour le retrouver dans le compte du nouvel investissement)
			// on commence par regarder si on a un contrat stocké ici : API\wp-content\plugins\wdgrestapi\files\investment-draft
			// ce sont les photos des contrats et chèques ajoutés par l'admin
			// pour ça, il nous faut retrouver un éventuel post_meta de type 'created-from-draft'
			// si c'est le cas, alors on ne change rien
			$created_from_draft = get_post_meta( $investid, 'created-from-draft', TRUE );
			if ( empty( $created_from_draft ) ) {
				// On teste la structure de fichiers idéale
				// On ne fait rien si ils sont dans cette structure
				$great_file_name = WDGInvestmentContract::get_investment_file_path( $campaign, $investid );

				if ( !file_exists( $great_file_name ) ) {
					$file_list_expression = WDGInvestmentContract::get_deprecated_file_list_expression( $campaign, $id_user_sender );
					$files = glob( $file_list_expression );
					if ( count( $files ) ) {
						foreach ( $files as $single_file ) {
							// Renommage dans la structure de fichiers idéale
							$log_report .= '$single_file : ' . $single_file . ' --- ';
							$log_report .= '$great_file_name : ' . $great_file_name . ' --- ';
							rename( $single_file, $great_file_name );
							break;
						}
					}
				}
			}

			// Si il n'y a eu qu'un seul investissement
			// Si l'ensemble des fonds liés aux royalties sont toujours là
			if ( count( $list_investments_user_sender ) == 1 && $amount_on_sender_wallet == $amount_royalties_received ) {
				// Changement d'id investisseur sur les données de royalties liées à cet investissement (id_user)
				foreach ( $list_rois as $roi_item ) {
					$log_report .= '$roi_item->id : ' . $roi_item->id . ' --- ';
					$WDGRoi = new WDGROI( $roi_item->id );
					$WDGRoi->id_user = $id_api_recipient;
					$WDGRoi->recipient_type = $user_type_recipient;
					$WDGRoi->save();
				}

				// Transfert de l'argent entre les wallets
				LemonwayLib::ask_transfer_funds( $id_lw_sender, $id_lw_recipient, $amount_on_sender_wallet );
			} else {
				//*****
				// A voir plus tard parce que touchy :
				// Si l'investisseur d'origine a fait plusieurs investissements OU a n'a pas le bon montant sur son porte-monnaie
				// Changement d'id investisseur sur les données de royalties liées à cet investissement (id_user) ?
				// Plus-values / Impots... ?
				// Transférer l'argent ?
				//*****
				// En attendant, on crée une tâche Asana
				// Log de la situation
				//*****
				$log_report .= 'Pas de transfert --- ';
				NotificationsAsana::change_investment_owner_error( $investid, $id_api_sender, $id_api_recipient );
			}

			// Suppression des caches de l'API
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$id_api_sender. '/rois' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$id_api_sender. '/rois' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$id_api_sender. '?with_links=1' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$id_api_sender );
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$id_api_recipient. '/rois' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$id_api_recipient. '/rois' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$id_api_recipient. '?with_links=1' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$id_api_recipient );
			WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$campaign->get_api_id(). '?with_investments=1&with_organization=1&with_poll_answers=1' );
		}

		ypcf_debug_log( $log_report, FALSE );

		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);

		return $buffer;
	}
}
