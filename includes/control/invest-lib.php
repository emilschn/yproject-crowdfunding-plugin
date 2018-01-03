<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function ypcf_session_start() {
	if (session_id() == '' && !headers_sent()) session_start();
}

/**
 * Détermine si les informations utilisateurs sont complètes (nécessaires pour les porteurs de projet)
 * @param int $user_id
 * @return bool
 */
function ypcf_check_user_is_complete($user_id) {
    $is_complete = true;
    $current_user = get_user_by('id', $user_id);
	$wdg_current_user = new WDGUser($user_id);
    $is_complete = ($current_user->user_firstname != "") && ($current_user->user_lastname != "");
    $is_complete = $is_complete && ($current_user->get('user_birthday_day') != "") && ($current_user->get('user_birthday_month') != "") && ($current_user->get('user_birthday_year') != "");
    $is_complete = $is_complete && $wdg_current_user->is_major();
    $is_complete = $is_complete && ($current_user->get('user_nationality') != "") && ($current_user->user_email != "");
    $is_complete = $is_complete && ($current_user->get('user_address') != "") && ($current_user->get('user_postal_code') != "") && ($current_user->get('user_city') != "");
    $is_complete = $is_complete && ($current_user->get('user_country') != "") && ($current_user->get('user_mobile_phone') != "");
    $is_complete = $is_complete && ($current_user->get('user_gender') != "") && ($current_user->get('user_birthplace') != "");
    return $is_complete;
}

/**
 * Vérification si l'utilisateur a bien rempli les données nécessaires au type de financement qu'il tente
 */
function ypcf_check_user_can_invest($redirect = false) {
    $can_invest = TRUE;
    
    ypcf_session_start();
    
    $current_campaign = atcf_get_current_campaign();
	if (!$current_campaign) {
		$can_invest = FALSE;
	}
	$wdg_current_user = WDGUser::current();
	$wdg_current_user->has_filled_invest_infos($current_campaign->funding_type());
	global $user_can_invest_errors;
    if (!empty($user_can_invest_errors)) {
	    $can_invest = FALSE;
    }
    $_SESSION['error_invest'] = $user_can_invest_errors;

    if ($redirect && !$can_invest) {
		$_SESSION['redirect_current_campaign_id'] = $current_campaign->ID;
		if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
		if (isset($_SESSION['new_orga_just_created']) && !empty($_SESSION['new_orga_just_created'])) {
			$_SESSION['redirect_current_invest_type'] = $_SESSION['new_orga_just_created'];
		} else {
			if (isset($_POST['invest_type'])) $_SESSION['redirect_current_invest_type'] = $_POST['invest_type'];
		}
		if (isset($_POST['selected_reward'])) $_SESSION['redirect_current_selected_reward'] = $_POST['selected_reward'];
		ypcf_debug_log('ypcf_check_user_can_invest > cant invest, redirect !');
		$page_update = get_page_by_path('modifier-mon-compte');
		wp_redirect(get_permalink($page_update->ID));
		exit();
    }
    return $can_invest;
}

/**
 * Vérification si l'organisation peut investir
 */
function ypcf_check_organization_can_invest($organization_user_id) {
    $organization = new WDGOrganization($organization_user_id);
    $can_invest = $organization->has_filled_invest_infos();

    if (!$can_invest) {
		$errors = (isset($_SESSION['error_invest'])) ? $_SESSION['error_invest'] : array();
		array_push($errors, "Certaines des informations de l'organisation manquent ou sont inexactes.");
		$_SESSION['error_invest'] = $errors;
    }
    
    return $can_invest;
}

/**
 * Se charge de tester les redirections à effectuer
 */
function ypcf_check_invest_redirections() {
    ypcf_session_start();

    //Si le projet n'est pas défini, on annule et retourne à l'accueil
    $campaign = atcf_get_current_campaign();
    if (!($campaign)) {
		wp_redirect(site_url());
		exit();
    }
    
    //Si le projet n'est pas en collecte ou que le montant de la part est à 0 (donc non-défini), on retourne à la page projet
    if ($campaign->campaign_status() != ATCF_Campaign::$campaign_status_collecte || $campaign->part_value() == 0) {
		wp_redirect(get_permalink($campaign->ID));
		exit();
    }
    
    //En cas d'investissement, et pas de don
    if ($campaign->funding_type() != "fundingdonation") {
	    //Si l'utilisateur veut investir pour une nouvelle organisation, on l'envoie vers "Mon compte" pour qu'il ajoute l'organisation
	    if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] == 'new_organization') {
			$_SESSION['redirect_current_campaign_id'] = $campaign->ID;
			if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
			$page_new_orga = get_page_by_path('creer-une-organisation');
			wp_redirect(get_permalink($page_new_orga->ID));
			exit();
	    }

	    //Si l'utilisateur veut investir pour une organisation existante
	    if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] != 'new_organization' && $_SESSION['redirect_current_invest_type'] != 'user') {
			if (!ypcf_check_organization_can_invest($_SESSION['redirect_current_invest_type'])) {
				$_SESSION['redirect_current_campaign_id'] = $campaign->ID;
				if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
				$page_update = get_page_by_path('modifier-mon-compte');
				wp_redirect(get_permalink($page_update->ID));
				exit();
			}
	    }
    }

    //Si on a validé la confirmation
    //Il faut choisir le moyen de paiement
    $amount_part = FALSE;
    $part_value = ypcf_get_part_value();
    if (isset($_POST['amount_part'])) $amount_part = $_POST['amount_part'];
    $amount = ($amount_part === FALSE) ? 0 : $amount_part * $part_value;
    $max_part_value = ypcf_get_max_part_value();
    
    //Tests de la validité de l'investissement pour tous les types de financement : utilisateur loggé, projet défini, montant correct
    if (is_user_logged_in() && isset($_POST['amount_part']) && is_numeric($_POST['amount_part']) && ctype_digit($_POST['amount_part']) 
			&& intval($_POST['amount_part']) == $_POST['amount_part'] && $_POST['amount_part'] >= 1 && $_POST['amount_part'] <= $max_part_value) {
	
	    //Suite des tests pour les projets 
            //Pour l'investissement : bon pour souscription écrit
            //Pour tous : informations validées par coche
	    if (
				(
					(
						($campaign->funding_type() != 'fundingdonation')
						&& (
							isset($_POST['confirm_power']) 
							&& strtolower($_POST['confirm_power']) == 'bon pour souscription'
							&& ($amount > 1500 || (isset($_POST['confirm_signing']) && $_POST['confirm_signing']))
						)
					)
					|| ($campaign->funding_type() == 'fundingdonation')
				)
                && isset($_POST['information_confirmed']) && $_POST['information_confirmed'] == '1' ) {

		    $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
                    
		    $page_mean_payment = get_page_by_path('moyen-de-paiement');
		    wp_redirect(get_permalink($page_mean_payment->ID) . '?campaign_id=' . $campaign->ID);
		    exit();
	    }
    }
}

/**
 * Enregistre les données saisies par l'utilisateur et redirige vers la page d'investissement si nécessaire
 */
function ypcf_check_has_user_filled_infos_and_redirect() {
    global $validate_email;
    ypcf_session_start();
    $validate_email = true;
    $current_user = wp_get_current_user();
    ypcf_debug_log("ypcf_check_has_user_filled_infos_and_redirect --- ".$current_user->ID);
    if (is_user_logged_in() && isset($_POST["update_user_posted"]) && $_POST["update_user_id"] == $current_user->ID) {
		$errors = (isset($_SESSION['error_invest'])) ? $_SESSION['error_invest'] : array();
		
		//Infos utilisateurs
		if ($_POST["update_gender"] != "") update_user_meta($current_user->ID, 'user_gender', $_POST["update_gender"]);
		if ($_POST["update_firstname"] != "") wp_update_user( array ( 'ID' => $current_user->ID, 'first_name' => $_POST["update_firstname"] ) ) ;
		if ($_POST["update_lastname"] != "") wp_update_user( array ( 'ID' => $current_user->ID, 'last_name' => $_POST["update_lastname"] ) ) ;
		if ($_POST["update_publicname"] != "") {
			wp_update_user( array ( 'ID' => $current_user->ID, 'display_name' => $_POST["update_publicname"] ) ) ;
			$current_user->display_name = $_POST["update_publicname"];
		}
		if ($_POST["update_birthday_day"] != "") update_user_meta($current_user->ID, 'user_birthday_day', $_POST["update_birthday_day"]);
		if ($_POST["update_birthday_month"] != "") update_user_meta($current_user->ID, 'user_birthday_month', $_POST["update_birthday_month"]);
		if ($_POST["update_birthday_year"] != "") update_user_meta($current_user->ID, 'user_birthday_year', $_POST["update_birthday_year"]);
		if ($_POST["update_birthplace"] != "") update_user_meta($current_user->ID, 'user_birthplace', $_POST["update_birthplace"]);
		if ($_POST["update_nationality"] != "") update_user_meta($current_user->ID, 'user_nationality', $_POST["update_nationality"]);
		if ($_POST["update_address"] != "") update_user_meta($current_user->ID, 'user_address', $_POST["update_address"]);
		if ($_POST["update_postal_code"] != "") update_user_meta($current_user->ID, 'user_postal_code', $_POST["update_postal_code"]);
		if ($_POST["update_city"] != "") update_user_meta($current_user->ID, 'user_city', $_POST["update_city"]);
		if ($_POST["update_country"] != "") update_user_meta($current_user->ID, 'user_country', $_POST["update_country"]);
		if ($_POST["update_mobile_phone"] != "") update_user_meta($current_user->ID, 'user_mobile_phone', $_POST["update_mobile_phone"]);
		if ($_POST["user_description"] != "") update_user_meta($current_user->ID, 'description', $_POST["user_description"]);
		if (isset($_POST["update_email_contact"])) {
			if (($_POST["update_email_contact"] != "" && $_POST["update_email_contact"] != $current_user->user_email)) {
				if (is_email( $_POST["update_email_contact"] )) {
					wp_update_user( array ( 'ID' => $current_user->ID, 'user_email' => $_POST["update_email_contact"] ) );
					$current_user->user_email = $_POST["update_email_contact"];
				}
			}

		} else {
			if (isset($_POST["update_password_current"])) {
				if (!isset($_POST["update_email"])) $validate_email = true;
				if (wp_check_password( $_POST["update_password_current"], $current_user->data->user_pass, $current_user->ID)) {
					$update_email = filter_input( INPUT_POST, 'update_email' );
					if ( !empty( $update_email ) && $update_email != $current_user->user_email && is_email( $update_email ) ) {
						$user_existing_with_email = get_user_by( 'email', $update_email );
						if ( empty( $user_existing_with_email ) ) {
							wp_update_user( array ( 'ID' => $current_user->ID, 'user_email' => $update_email ) );
							$current_user->user_email = $update_email;
						} else {
							array_push( $errors, "L'adresse e-mail est déjà utilisée." );
						}
					}
					if ($_POST["update_password"] != "" && $_POST["update_password"] == $_POST["update_password_confirm"]) wp_update_user( array ( 'ID' => $current_user->ID, 'user_pass' => $_POST["update_password"] ) );
				}
			}
		}

		//Nouvelle organisation
		if (isset($_POST['new_organization'])) {
			if ($_POST['new_org_name'] != '' && isset($_POST['new_organization_capable']) && $_POST['new_organization_capable'] && is_email( $_POST["new_org_email"] )) {
			//Création d'un utilisateur pour l'organisation
			$username = 'org_' . sanitize_title_with_dashes($_POST['new_org_name']);
			$password = wp_generate_password();
			$organization_user_id = wp_create_user($username, $password, $_POST['new_org_email']);
			if (!isset($organization_user_id->errors) || count($organization_user_id->errors) == 0) {
				wp_update_user( array ( 'ID' => $organization_user_id, 'first_name' => $_POST['new_org_name'] ) ) ;
				wp_update_user( array ( 'ID' => $organization_user_id, 'last_name' => $_POST['new_org_name'] ) ) ;
				wp_update_user( array ( 'ID' => $organization_user_id, 'display_name' => $_POST['new_org_name'] ) ) ;
				update_user_meta($organization_user_id, 'user_type', 'organisation');
				update_user_meta($organization_user_id, 'user_address', $_POST['new_org_address']);
				update_user_meta($organization_user_id, 'user_nationality', $_POST['new_org_nationality']);
				update_user_meta($organization_user_id, 'user_postal_code', $_POST['new_org_postal_code']);
				update_user_meta($organization_user_id, 'user_city', $_POST['new_org_city']);
				update_user_meta($organization_user_id, 'organisation_type', 'society');
				update_user_meta($organization_user_id, 'organisation_legalform', $_POST['new_org_legalform']);
				update_user_meta($organization_user_id, 'organisation_capital', $_POST['new_org_capital']);
				update_user_meta($organization_user_id, 'organisation_idnumber', $_POST['new_org_idnumber']);
				update_user_meta($organization_user_id, 'organisation_rcs', $_POST['new_org_rcs']);

				$_SESSION['redirect_current_invest_type'] = $organization_user_id;

				$organization_user = get_user_by('id', $organization_user_id);
			} else {
				foreach ($organization_user_id->errors as $error) {
					array_push($errors, $error[0]);
				}
			}
			} else {
				if ($_POST['new_org_name'] == '') array_push($errors, 'Merci de renseigner une dénomination sociale.');
				if (!isset($_POST['new_organization_capable']) || !$_POST['new_organization_capable']) array_push($errors, 'Merci de valider que vous êtes en capacité de représenter cette organisation.');
				if (!$validate_email) array_push($errors, 'L\'adresse e-mail de l\'organisation n\'est pas correcte.');
			}

		//Mise à jour d'une organisation
		} elseif (isset($_POST['update_organization'])) {
			//Parcourir toutes les organisations
			$wdg_current_user = new WDGUser( $current_user->ID );
			$organizations_list = $wdg_current_user->get_organizations_list();
			foreach ($organizations_list as $organization_item) {
				$organization = new WDGOrganization($organization_item->wpref);
				$name_suffix = '_' . $group_id;
				if (isset($_POST['update_organization' . $name_suffix]) && $_POST['new_org_name'.$name_suffix] != '') {
					$organization->set_address($_POST['new_org_address'.$name_suffix]);
					$organization->set_nationality($_POST['new_org_nationality'.$name_suffix]);
					$organization->set_postal_code($_POST['new_org_postal_code'.$name_suffix]);
					$organization->set_city($_POST['new_org_city'.$name_suffix]);
					$organization->set_legalform($_POST['new_org_legalform'.$name_suffix]);
					$organization->set_capital($_POST['new_org_capital'.$name_suffix]);
					$organization->set_idnumber($_POST['new_org_idnumber'.$name_suffix]);
					$organization->set_rcs($_POST['new_org_rcs'.$name_suffix]);

					$organization_user = $organization->get_creator();
				}
			}
		}
		$_SESSION['error_invest'] = $errors;

		if (isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "") {
			if (!isset($_SESSION['error_invest']) || count($_SESSION['error_invest']) == 0) {
				if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
				if (isset($_POST['invest_type'])) $_SESSION['redirect_current_invest_type'] = $_POST['invest_type'];
				ypcf_debug_log("ypcf_check_has_user_filled_infos_and_redirect > redirect with ".$_SESSION['redirect_current_amount_part']. " and " . $_SESSION['redirect_current_invest_type']);
				wp_redirect(ypcf_login_gobackinvest_url());
				exit();
			}
		}
    }
}

/**
 * Après le login, si on venait de l'investissement, il faut y retourner
 * @param type $redirect_to
 * @param type $request
 * @param type $user
 * @return type
 */
function ypcf_login_redirect_invest( $redirect_to, $request, $user ) {
    $goback_url = ypcf_login_gobackinvest_url();
    if ($goback_url == '') $goback_url = site_url();
    return $goback_url;
}
add_filter( 'login_redirect', 'ypcf_login_redirect_invest', 10, 3 );

/**
 * retourne la page d'investissement si définie en variable de session
 * @return string
 */
function ypcf_login_gobackinvest_url() {
    $redirect_to = '';
    ypcf_session_start();
    if (isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "") {
		$page_invest = get_page_by_path('investir');
		$page_invest_link = get_permalink($page_invest->ID);
		$campaign_id_param = '?campaign_id=';
		$redirect_to = $page_invest_link . $campaign_id_param . $_SESSION['redirect_current_campaign_id'];
		unset($_SESSION['redirect_current_campaign_id']);
    }
	ypcf_debug_log('ypcf_login_gobackinvest_url > redirect to : ' . $redirect_to);
    return $redirect_to;
}


/**
 * Met à jour le statut edd en fonction du statut du paiement sur LW
 * @param int $payment_id
 * @param type $mangopay_contribution
 * @param type $lemonway_transaction
 * @param WDGInvestment $wdginvestment
 * @return string
 */
function ypcf_get_updated_payment_status( $payment_id, $mangopay_contribution = FALSE, $lemonway_transaction = FALSE, $wdginvestment = FALSE ) {
	$payment_investment = new WDGInvestment( $payment_id );
    $payment_post = get_post($payment_id);
	$downloads = edd_get_payment_meta_downloads($payment_id);
	$download_id = '';
	if (is_array($downloads[0])) $download_id = $downloads[0]["id"]; 
	else $download_id = $downloads[0];
	$post_campaign = get_post($download_id);
	$campaign = atcf_get_campaign($post_campaign);
	
    $init_payment_status = $payment_post->post_status;
    $buffer = $init_payment_status;
	
	$contract_status = $payment_investment->get_contract_status();
	if ( $contract_status == WDGInvestment::$contract_status_preinvestment_validated || $contract_status == WDGInvestment::$contract_status_investment_refused ) {
		return $buffer;
	}
    
	if (isset($payment_id) && $payment_id != '' && $init_payment_status != 'failed' && $init_payment_status != 'publish') {

		//On teste d'abord si ça a été refunded
		$refund_transfer_id = get_post_meta($payment_id, 'refund_transfer_id', true);
		if (($init_payment_status == 'refunded') || (isset($refund_transfer_id) && $refund_transfer_id != '')) {
			$buffer = 'refunded';
			$postdata = array(
				'ID'		=> $payment_id,
				'post_status'	=> $buffer,
				'edit_date'	=> current_time( 'mysql' )
			);
			wp_update_post($postdata);
			
			
		} else {
			$contribution_id = edd_get_payment_key($payment_id);
			if (strpos($contribution_id, '_wallet_') !== FALSE) {
				$split_contribution_id = explode('_wallet_', $contribution_id);
				$contribution_id = $split_contribution_id[0];
			}
			
			if (isset($contribution_id) && $contribution_id != '' && $contribution_id != 'check') {
				$update_post = FALSE;
				$is_card_contribution = TRUE;

				//Si la clé de contribution contient "wire", il s'agissait d'un paiement par virement, il faut découper la clé
				if (strpos($contribution_id, 'wire_') !== FALSE) {
					$is_card_contribution = FALSE;
					$contribution_id = substr($contribution_id, 5);
					if ($campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway) {
						//TODO
					}

				//Paiement par wallet uniquement
				} else if (strpos($contribution_id, 'wallet_') !== FALSE && strpos($contribution_id, '_wallet_') === FALSE) {
					$buffer = 'publish';
					$update_post = TRUE;

				//On teste une contribution classique
				} else {

					if ($campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway) {
						if ($lemonway_transaction === FALSE) {
							$lw_transaction_result = LemonwayLib::get_transaction_by_id( $contribution_id );
						}
						if ($lw_transaction_result) {
							switch ($lw_transaction_result->STATUS) {
								case 3:
									$buffer = 'publish';
									break;
								case 4:
									$buffer = 'failed';
									$wdginvestment->set_status( WDGInvestment::$status_error );
									break;
								case 0:
								default:
									$buffer = 'pending';
									break;
							}
							$update_post = TRUE;
						}
					}
				}


				//Le paiement vient d'être validé
				if ($buffer == 'publish' && $buffer !== $init_payment_status) {
					//Mise à jour du statut du paiement pour être comptabilisé correctement dans le décompte
					$postdata = array(
						'ID'			=> $payment_id,
						'post_status'	=> $buffer,
						'edit_date'		=> current_time( 'mysql' )
					);
					wp_update_post($postdata);

					$amount = edd_get_payment_amount($payment_id);
					$current_user = get_user_by('id', $payment_post->post_author);
					if ($campaign->funding_type() != 'fundingdonation') {
						if ($amount > 1500) {
							//Création du contrat à signer
							$contract_id = WDGInvestment::create_contract( $payment_id, $download_id, $current_user->ID );
							if ($contract_id != '') {
								$contract_infos = signsquid_get_contract_infos( $contract_id );
								NotificationsEmails::new_purchase_user_success( $payment_id, $contract_infos->{'signatories'}[0]->{'code'}, $is_card_contribution, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
								NotificationsEmails::new_purchase_admin_success( $payment_id );
								if ( !empty( $wdginvestment ) && $wdginvestment->has_token() ) {
									global $contract_filename;
									$new_contract_pdf_filename = basename( $contract_filename );
									$new_contract_pdf_url = home_url('/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/') . $new_contract_pdf_filename;
									$wdginvestment->update_contract_url( $new_contract_pdf_url );
								}
							} else {
								global $contract_errors;
								$contract_errors = 'contract_failed';
								NotificationsEmails::new_purchase_user_error_contract( $payment_id, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
								NotificationsEmails::new_purchase_admin_error_contract( $payment_id );
							}
						} else {
							$new_contract_pdf_file = getNewPdfToSign($download_id, $payment_id, $current_user->ID);
							NotificationsEmails::new_purchase_user_success_nocontract( $payment_id, $new_contract_pdf_file, $is_card_contribution, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
							NotificationsEmails::new_purchase_admin_success_nocontract( $payment_id, $new_contract_pdf_file );
							if ( !empty( $wdginvestment ) && $wdginvestment->has_token() ) {
								$new_contract_pdf_filename = basename( $new_contract_pdf_file );
								$new_contract_pdf_url = home_url('/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/') . $new_contract_pdf_filename;
								$wdginvestment->update_contract_url( $new_contract_pdf_url );
							}
						}
					} else {
						NotificationsEmails::new_purchase_user( $payment_id, '', array(), ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) );
						NotificationsEmails::new_purchase_admin_success( $payment_id );
					}
					NotificationsEmails::new_purchase_team_members( $payment_id );

				//Le paiement vient d'échouer
				} else if ($buffer == 'failed' && $buffer !== $init_payment_status) {
					$post_items = get_posts(array(
						'post_type' => 'edd_log',
						'meta_key' => '_edd_log_payment_id',
						'meta_value' => $payment_id
					));
					foreach ($post_items as $post_item) {
						$postdata = array(
						'ID' => $post_item->ID,
						'post_status' => $buffer
						);
						wp_update_post($postdata);
					}

				//Le paiement est validé, mais aucun contrat n'existe
				} else if ($buffer == 'publish') {
					$amount = edd_get_payment_amount($payment_id);
					if ($amount > 1500) {
						$contract_id = get_post_meta($payment_id, 'signsquid_contract_id', TRUE);
						if (!isset($contract_id) || empty($contract_id)) {
							$current_user = get_user_by('id', $payment_post->post_author);
							$downloads = edd_get_payment_meta_downloads($payment_id); 
							$download_id = '';
							if (is_array($downloads[0])) $download_id = $downloads[0]["id"]; 
							else $download_id = $downloads[0];
							$post_campaign = get_post($download_id);
							$campaign = atcf_get_campaign($post_campaign);
							if ($campaign->funding_type() != 'fundingdonation') {
								$contract_id = WDGInvestment::create_contract( $payment_id, $download_id, $current_user->ID );
							}
						}
					}
				}

				if ($update_post) {
					$postdata = array(
						'ID'		=> $payment_id,
						'post_status'	=> $buffer,
						'edit_date'	=> current_time( 'mysql' )
					);
					wp_update_post($postdata);

					if (isset($download_id) && !empty($download_id)) {
						do_action('wdg_delete_cache', array(
							'home-projects',
							'projectlist-projects-current'
						));
					}
				}
			}

		}
    }
    return $buffer;
}

/**
 * 
 */
function ypcf_get_updated_transfer_status($transfer_post) {
	$transfer_post_obj = get_post($transfer_post);
	return $transfer_post_obj->post_status;
}

/**
 * Renvoie l'identifiant de contrat à partir d'un investissement donné
 * @param type $payment_id
 */
function ypcf_get_signsquidcontractid_from_invest($payment_id) {
    $contract_id = '';
    if (isset($payment_id) && $payment_id != '') {
	$contract_id = get_post_meta($payment_id, 'signsquid_contract_id', true);
    }
    return $contract_id;
}

/**
 * Analyse les infos de contrat retournées par signsquid
 * @param type $contract_infos
 */
function ypcf_get_signsquidstatus_from_infos($contract_infos, $amount) {
    if ($amount <= 1500) {
	    $buffer = 'Investissement valid&eacute;';
    } else {
	    $buffer = '- Pas de contrat -';
	    if ($contract_infos != '' && is_object($contract_infos)) {
		switch($contract_infos->{'status'}) {
		    case 'NotPublished':
			$buffer = 'Contrat non-cr&eacute;&eacute;';
			break;
		    case 'WaitingForSignatoryAction':
			$buffer = 'En attente de signature';
			break;
		    case 'Refused':
			$buffer = 'Contrat refus&eacute;';
			break;
		    case 'Agreed':
			$buffer = 'Contrat sign&eacute;';
			break;
		    case 'NewVersionAvailable':
			$buffer = 'Contrat mis &agrave; jour';
			break;
		}
	    }
    }
    return $buffer;
}

/**
 * retourne la valeur d'une part
 * @return type
 */
function ypcf_get_part_value() {
    $buffer = 0;
	$current_campaign = atcf_get_current_campaign();
    if ( $current_campaign ) {
		$buffer = $current_campaign->part_value();
    }
    return $buffer;
}

function ypcf_get_max_part_value() {
    $max_value = ypcf_get_max_value_to_invest();
    $part_value = ypcf_get_part_value();
    if ($part_value > 0) $remaining_parts = floor($max_value / $part_value);
    else $remaining_parts = 0;
    return $remaining_parts;
}

/**
 * retourne la valeur maximale que l'on peut investir
 * @return int
 */
function ypcf_get_max_value_to_invest() {
    $buffer = 0;
	$current_campaign = atcf_get_current_campaign();
    if ( $current_campaign ) {
		//Récupérer la valeur maximale possible : la valeur totale du projet moins le montant déjà atteint
		$buffer = $current_campaign->goal(false) - $current_campaign->current_amount(false, true);
    }
    return $buffer;
}

function ypcf_get_current_step() {
    ypcf_session_start();
    $buffer = 1;
    $max_part_value = ypcf_get_max_part_value();
	$wdginvestment = WDGInvestment::current();
    
    $amount_part = FALSE;
    $invest_type = FALSE;
    
	if ( $wdginvestment->has_token() ) {
		$amount_part = $wdginvestment->get_amount();
		$_SESSION['redirect_current_amount_part'] = $amount_part;
		$invest_type = 'user';
		$_SESSION['redirect_current_invest_type'] = $invest_type;
		
	} else {
		if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
		if (isset($_SESSION['new_orga_just_created']) && !empty($_SESSION['new_orga_just_created'])) {
			$_SESSION['redirect_current_invest_type'] = $_SESSION['new_orga_just_created'];
		} else {
			if (isset($_POST['invest_type'])) $_SESSION['redirect_current_invest_type'] = $_POST['invest_type'];
		}
	}
	if (isset($_SESSION['redirect_current_amount_part'])) $amount_part = $_SESSION['redirect_current_amount_part'];
	if (isset($_SESSION['redirect_current_invest_type'])) $invest_type = $_SESSION['redirect_current_invest_type'];
	$_SESSION['error_own_organization'] = FALSE;
	
	$current_campaign = atcf_get_current_campaign();
	$organization = $current_campaign->get_organization();
	$organization_id = $organization->wpref;
	if ( $invest_type == $organization_id ) {
		$_SESSION['error_own_organization'] = '1';
	}
    
    if ($invest_type != FALSE && $invest_type != $organization_id && $amount_part !== FALSE && is_numeric($amount_part) && ctype_digit($amount_part) 
			&& intval($amount_part) == $amount_part && $amount_part >= 1 && $amount_part <= $max_part_value ) {
		$buffer = 2;
    }
    
    return $buffer;
}

/**
 * retourne une valeur minimale arbitraire à investir
 * @return int
 */
function ypcf_get_min_value_to_invest() {
    return YP_MIN_INVEST_VALUE;
}

/**
 * retourne la somme investie par un utilisateur durant une même année
 */
function ypcf_get_annual_amount_invested($wp_user_id) {
    global $wpdb;
    
    $query = "SELECT {$wpdb->prefix}mb.meta_value AS payment_total
	    FROM {$wpdb->prefix}postmeta {$wpdb->prefix}m
	    LEFT JOIN {$wpdb->prefix}postmeta {$wpdb->prefix}ma
		    ON {$wpdb->prefix}ma.post_id = {$wpdb->prefix}m.post_id
		    AND {$wpdb->prefix}ma.meta_key = '_edd_payment_user_id'
		    AND {$wpdb->prefix}ma.meta_value = '%s'
	    LEFT JOIN {$wpdb->prefix}postmeta {$wpdb->prefix}mb
		    ON {$wpdb->prefix}mb.post_id = {$wpdb->prefix}ma.post_id
		    AND {$wpdb->prefix}mb.meta_key = '_edd_payment_total'
	    INNER JOIN {$wpdb->prefix}posts {$wpdb->prefix}
		    ON {$wpdb->prefix}.id = {$wpdb->prefix}m.post_id
		    AND {$wpdb->prefix}.post_status = 'publish'
		    AND {$wpdb->prefix}.post_date > '".date('Y-m-d', strtotime('-365 days'))."'
	    WHERE {$wpdb->prefix}m.meta_key = '_edd_payment_mode'
	    AND {$wpdb->prefix}m.meta_value = '%s'";

    $purchases = $wpdb->get_col( $wpdb->prepare( $query, $wp_user_id, 'live' ) );
    $purchases = array_filter( $purchases );

    $buffer = 0;
    if( $purchases ) {
	$buffer = round( array_sum( $purchases ), 2 );
    }
    return $buffer;
}

/**
 * retourne la somme déjà atteinte
 * @return type
 */
function ypcf_get_current_amount() {
    $buffer = 0;
	$current_campaign = atcf_get_current_campaign();
    if ( $current_campaign ) {
		//Récupérer la valeur maximale possible : la valeur totale du projet moins le montant déjà atteint
		$buffer = $current_campaign->current_amount(false);
    }
    return $buffer;
}