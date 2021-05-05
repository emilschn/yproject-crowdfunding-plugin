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

		$user_by_email = FALSE;
		$email = filter_input( INPUT_POST, 'e-mail' );
		if ( empty( $email ) ) {
			$error = array(
				'code'		=> 'new_account_email_empty',
				'text'		=> 'Problème de transmission de mail',
				'element'	=> 'e-mail'
			);
			array_push( $feedback_errors, $error );
		} else {
			$user_by_email = get_user_by( 'email', $email );
			if ( empty( $user_by_email ) ) {
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
			$lwid_sender = '';
			$list_rois = array();
			if ( WDGOrganization::is_user_organization( $id_user_sender ) ) {
				$WDGOrganization_sender = new WDGOrganization( $id_user_sender );
				$id_api_sender = $WDGOrganization_sender->get_api_id();
				$list_investments_user_sender = WDGWPREST_Entity_Organization::get_investments( $id_api_sender );
				$list_investment_contracts_user_sender = WDGWPREST_Entity_Organization::get_investment_contracts( $id_api_sender );
				$amount_on_sender_wallet = $WDGOrganization_sender->get_available_rois_amount();
				$list_rois = WDGWPREST_Entity_Organization::get_rois( $id_api_sender );
				$lwid_sender = $WDGOrganization_sender->get_lemonway_id();
			} else {
				$WDGUser_sender = new WDGUser( $id_user_sender );
				$id_api_sender = $WDGUser_sender->get_api_id();
				$list_investments_user_sender = WDGWPREST_Entity_User::get_investments( $id_api_sender );
				$list_investment_contracts_user_sender = WDGWPREST_Entity_User::get_investment_contracts( $id_api_sender );
				$amount_on_sender_wallet = $WDGUser_sender->get_lemonway_wallet_amount();
				$list_rois = WDGWPREST_Entity_User::get_rois( $id_api_sender );
				$lwid_sender = $WDGUser_sender->get_lemonway_id();
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
			$log_report .= '$lwid_sender : ' . $lwid_sender . ' --- ';
			$log_report .= '$amount_royalties_received : ' . $amount_royalties_received . ' --- ';

			// Récupération du type du nouvel investisseur (orga / user)
			$new_user_api_id = 0;
			$new_user_type = WDGOrganization::is_user_organization( $user_by_email->ID ) ? 'orga' : 'user';
			$lwid_new = '';
			if ( $new_user_type == 'user' ) {
				$WDGUser = new WDGUser( $user_by_email->ID );
				$new_user_api_id = $WDGUser->get_api_id();
				$lwid_new = $WDGUser->get_lemonway_id();
			} else {
				$WDGOrganization = new WDGOrganization( $user_by_email->ID );
				$new_user_api_id = $WDGOrganization->get_api_id();
				$lwid_new = $WDGOrganization->get_lemonway_id();
			}
			$log_report .= '$new_user_api_id : ' . $new_user_api_id . ' --- ';
			$log_report .= '$lwid_new : ' . $lwid_new . ' --- ';

			// Changement d'id investisseur sur la donnée d'investissement sur le site
			// post_author
			$postdata = array(
				'ID'			=> $investid,
				'post_author'	=> $user_by_email->ID
			);
			wp_update_post( $postdata );

			// Metas edd_payment id et email
			edd_update_payment_meta( $investid, 'user_id', $user_by_email->ID );
			edd_update_payment_meta( $investid, 'user_email', $email );
			edd_update_payment_meta( $investid, 'email', $email );

			// Changement d'id investisseur sur la donnée d'investissement sur l'API
			// TODO : vérifier que save_to_api suffit pour MAJ id, email, prénom et nom
			$WDGInvestment->save_to_api();

			// Si il y a une donnée "contrat d'investissement" liée à cet investissement, on change l'investisseur
			foreach ( $list_investment_contracts_user_sender as $investment_contract_item ) {
				if ( $investment_contract_item->subscription_id == $investid ) {
					$investment_contract_item->investor_id = $new_user_api_id;
					$investment_contract_item->investor_type = $new_user_type;
					$log_report .= '$investment_contract_item->id : ' . $investment_contract_item->id . ' --- ';
					WDGWPREST_Entity_InvestmentContract::update( $investment_contract_item->id, $investment_contract_item );

					// Et on ajoute aussi une ligne d'historique
					$date = new DateTime();
					$data_modified = 'user_id';
					$old_value = $id_api_sender;
					$new_value = $new_user_api_id;
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
				$id_campaign = $campaign->ID;
				$url_campaign = $campaign->get_url();
				$great_file_name = dirname( __FILE__ ). '/../../../files/contracts/campaigns/' .$id_campaign. '-' .$url_campaign. '/' .$investid. '.pdf';

				if ( !file_exists( $great_file_name ) ) {
					$exp = dirname( __FILE__ ). '/../../pdf_files/' .$id_campaign. '_' .$id_user_sender. '_*.pdf';
					$files = glob( $exp );
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
					$WDGRoi->id_user = $new_user_api_id;
					$WDGRoi->recipient_type = $new_user_type;
					$WDGRoi->save();
				}

				// Transfert de l'argent entre les wallets
				LemonwayLib::ask_transfer_funds( $lwid_sender, $lwid_new, $amount_on_sender_wallet );
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
			}

			// Suppression des caches de l'API
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$id_api_sender. '/rois' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$id_api_sender. '/rois' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$id_api_sender. '?with_links=1' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$id_api_sender );
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$new_user_api_id. '/rois' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$new_user_api_id. '/rois' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$new_user_api_id. '?with_links=1' );
			WDGWPRESTLib::unset_cache( 'wdg/v1/organization/' .$new_user_api_id );
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
