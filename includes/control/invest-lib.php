<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function ypcf_session_start() {
	if (session_id() == '' && !headers_sent()) session_start();
}

/**
 * Se charge de tester les redirections à effectuer
 */
function ypcf_check_redirections() {
    global $post;
    if (isset($post)) {
		$page_name = get_post($post)->post_name;

		switch ($page_name) {
			case 'connexion' :
				//Modification très crade temporaire pour gérer une partie de l'API
				ypcf_check_api_calls();
				//Redirection vers la page d'investissement après login, si on venait de l'investissement
				ypcf_check_is_user_logged_connexion();
			break;

			case 'modifier-mon-compte' :
				//On teste si l'utilisateur vient de remplir ses données pour les enregistrer
				ypcf_check_has_user_filled_infos_and_redirect();
			break;

			case 'investir' :
				$init_result = WDGInvestment::init();
				if ( !$init_result ) {
					$wdginvestment = WDGInvestment::current();
					ypcf_debug_log( 'ypcf_check_redirections > investir > TOKEN ERRORS > ' . print_r( $wdginvestment->get_error(), TRUE ) );
					wp_redirect( $wdginvestment->get_redirection( 'error', 'token-error' ) );
					exit();
				}
				//D'abord on teste si l'utilisateur est bien connecté
				ypcf_check_is_user_logged_invest();
				ypcf_check_is_project_investable();
				$current_step = ypcf_get_current_step();
				if ($current_step == 2) {
					//On vérifie que les données utilisateurs sont valables
					ypcf_check_user_can_invest(true);
					//On vérifie les redirections nécessaires à l'investissement
					ypcf_check_invest_redirections();
				}
			break;

			case 'moyen-de-paiement' :
				ypcf_check_is_user_logged_invest();
				ypcf_check_meanofpayment_redirections();
			break;

			case 'paiement-virement' :
			case 'paiement-cheque' :
			case 'paiement-partager' :
				ypcf_check_is_user_logged_invest();
			break;

			case 'paiement-effectue' :
				ypcf_check_is_user_logged_invest();
				if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);
				if (isset($_SESSION['redirect_current_amount_part'])) unset($_SESSION['redirect_current_amount_part']);
			break;
		}
    }
}
add_action( 'template_redirect', 'ypcf_check_redirections' );

/**
 * DEPRECATED !!
 * Temporaire : sert à gérer certains appels API pour les données qui n'y figurent pas encore
 */
function ypcf_check_api_calls() {
	$input_action = filter_input( INPUT_GET, 'action' );
	$input_param = filter_input( INPUT_GET, 'param' );
	ypcf_debug_log( 'ypcf_check_api_calls > $input_action : ' .$input_action. ' ; $input_param : ' .$input_param );
	switch ( $input_action ) {
		case "get_royalties_by_project":
			if ( !empty( $input_param ) ) {
				$campaign = new ATCF_Campaign( $input_param );
				$result = $campaign->get_roi_declarations();
				exit( json_encode( $result ) );
			}
		break;
	
		case "get_royalties_by_user":
			$buffer = array();
			if ( !empty( $input_param ) ) {
				$query_user = get_user_by( 'email', $input_param );
				if ( $query_user ) {
					$result = WDGROI::get_roi_list_by_user( $query_user->ID );
					foreach ( $result as $roi ) {
						$roi_item = array();
						$roi_item["id"] = $roi->id;
						$roi_item["project"] = $roi->id_campaign;
						$roi_item["date"] = $roi->date_transfer;
						$roi_item["amount"] = $roi->amount;
						array_push( $buffer, $roi_item );
					}
				}
			}
			exit( json_encode( $buffer ) );
		break;
		
		case "update_user_email":
			$buffer = 'error';
			if ( !empty( $input_param ) ) {
				
				// On récupère l'utilisateur à modifier
				$init_email = html_entity_decode( $input_param );
				ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $init_email : ' .$init_email );
				$query_user = get_user_by( 'email', $init_email );
				if ( !empty( $query_user ) ) {
					
					// On vérifie que le nouvel e-mail est renseigné
					$new_email = filter_input( INPUT_POST, 'new_email' );
					ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $new_email : ' .$new_email );
					if ( !empty( $new_email ) ) {
						
						// On vérifie que le nouvel e-mail n'est pas déjà pris
						$find_existing_user = get_user_by( 'email', $new_email );
						if ( empty( $find_existing_user ) ) {
							wp_update_user( array ( 'ID' => $query_user->ID, 'user_email' => $new_email ) );
							$buffer = 'success';
							
						} else {
							ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $find_existing_user : ' .$find_existing_user->ID );
							$buffer = 'E-mail alreay in use';
							
						}
					}
					
				} else {
					$buffer = 'Did not find user with this e-mail';
				}
			}
			ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $buffer : ' .$buffer );
			exit( $buffer );
		break;
	}
}


/**
 * Après le login, si on venait de l'investissement, il faut y retourner
 */
function ypcf_check_is_user_logged_connexion() {
    ypcf_session_start();

    if (is_user_logged_in() && isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "") {
		wp_redirect(ypcf_login_gobackinvest_url());
		exit();
    }
}


/**
 * Si l'utilisateur n'est pas connecté, on redirige vers la page de connexion en enregistrant la page d'investissement pour y revenir
 */
function ypcf_check_is_user_logged_invest() {
    ypcf_session_start();

    if (!is_user_logged_in()) {
		$wdginvestment = WDGInvestment::current();
		if ( isset( $wdginvestment->get_campaign()->ID ) ) {
			$_SESSION['redirect_current_campaign_id'] = $wdginvestment->get_campaign()->ID;
			$page_connexion = get_page_by_path('connexion');
			wp_redirect(get_permalink($page_connexion->ID));
		} else {
			wp_redirect(site_url());
		}
		exit();
    } 
}

function ypcf_check_is_project_investable() {
	$wdginvestment = WDGInvestment::current();
    if ( !$wdginvestment->get_campaign()->is_investable() ) {
		wp_redirect( get_permalink( $wdginvestment->get_campaign()->ID ) );
		exit();
    }
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
//    $is_complete = $is_complete && ($current_user->get('user_person_type') != "");
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

function ypcf_check_meanofpayment_redirections() {
    ypcf_session_start();
    ypcf_debug_log('ypcf_check_meanofpayment_redirections --- SESSION :: ' . $_SESSION['redirect_current_amount_part'] . ' ; meanofpayment :: ' . $_GET['meanofpayment']);
	
	//Si il n'y a plus rien dans la session, il faut l'indiquer
	if (empty($_SESSION['redirect_current_amount_part'])) {
		global $error_session;
		$error_session = '1';
		return;
	}
	$campaign = atcf_get_current_campaign();
    
    //Si on a choisi le moyen de paiement
    //Il faut donc créer une contribution sur LW et rediriger sur la page de paiement récupérée
    if (is_user_logged_in() && $campaign && isset($_SESSION['redirect_current_amount_part']) && isset($_GET['meanofpayment'])) {
		ypcf_debug_log('ypcf_check_meanofpayment_redirections --- A');
	    $amount_part = $_SESSION['redirect_current_amount_part'];
	    $current_user = wp_get_current_user();
	    $amount = $amount_part * $campaign->part_value();

	    switch ($_GET['meanofpayment']) {
		    //Paiement par carte
		    case 'card':
				ypcf_debug_log('ypcf_check_meanofpayment_redirections --- card');
			    //Récupération de l'url de la page qui indique que le paiement est bien effectué
			    $page_payment_done = get_page_by_path('paiement-effectue');
				
				if ($campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway) {
					$organization = $campaign->get_organization();
					$organization_obj = new WDGOrganization($organization->wpref);
					$WDGuser_current = WDGUser::current();
					$current_token_id = 'U'.$WDGuser_current->wp_user->ID .'C'. $campaign->ID;
					$wk_token = LemonwayLib::make_token($current_token_id);
					$return_url = get_permalink($page_payment_done) . '?campaign_id='. $campaign->ID;
					$error_url = $return_url . '&error=1';
					$cancel_url = $return_url . '&cancel=1';
					$return = LemonwayLib::ask_payment_webkit( $organization_obj->get_lemonway_id(), $amount, 0, $wk_token, $return_url, $error_url, $cancel_url );
					if ( !empty($return->MONEYINWEB->TOKEN) ) {
						$url_css = 'https://www.wedogood.co/wp-content/themes/yproject/_inc/css/lemonway.css';
						$url_css_encoded = urlencode($url_css);
						wp_redirect(YP_LW_WEBKIT_URL . '?moneyInToken=' . $return->MONEYINWEB->TOKEN . '&lang=fr&p=' . $url_css_encoded);
						exit();
					}
				}
		    break;
			
		    case 'cardwallet':
				if ($campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway) {
					$page_payment_done = get_page_by_path('paiement-effectue');
					$organization = $campaign->get_organization();
					$organization_obj = new WDGOrganization($organization->wpref);
					$WDGuser_current = WDGUser::current();
					$current_token_id = 'U'.$WDGuser_current->wp_user->ID .'C'. $campaign->ID;
					$wk_token = LemonwayLib::make_token($current_token_id);
					$return_url = get_permalink($page_payment_done) . '?meanofpayment=cardwallet&campaign_id='. $campaign->ID;
					$error_url = $return_url . '&error=1';
					$cancel_url = $return_url . '&cancel=1';
					$WDGuser_current_wallet_amount = $WDGuser_current->get_lemonway_wallet_amount();
					$amount -= $WDGuser_current_wallet_amount;
					$_SESSION['need_wallet_completion'] = $WDGuser_current_wallet_amount;
					$return = LemonwayLib::ask_payment_webkit( $organization_obj->get_lemonway_id(), $amount, 0, $wk_token, $return_url, $error_url, $cancel_url );
					if ( !empty($return->MONEYINWEB->TOKEN) ) {
						$url_css = 'https://www.wedogood.co/wp-content/themes/yproject/_inc/css/lemonway.css';
						$url_css_encoded = urlencode($url_css);
						wp_redirect(YP_LW_WEBKIT_URL . '?moneyInToken=' . $return->MONEYINWEB->TOKEN . '&lang=fr&p=' . $url_css_encoded);
						exit();
					}
				}
		    break;
			
			//Paiement par wallet
			case 'wallet':
				$WDGUser_current = WDGUser::current();
				
				$can_use_wallet = false;
				if ($_SESSION['redirect_current_invest_type'] == 'user') {
					$can_use_wallet = $WDGUser_current->can_pay_with_wallet($amount, $campaign);
				} else {
					$invest_type = $_SESSION['redirect_current_invest_type'];
					$organization = new WDGOrganization($invest_type);
					$can_use_wallet = $organization->can_pay_with_wallet($amount, $campaign);
				}
				if ($can_use_wallet) {
					$_SESSION['amount_to_save'] = $amount;
					if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);
					if (isset($_SESSION['redirect_current_amount_part'])) unset($_SESSION['redirect_current_amount_part']);
				    $page_payment_done = get_page_by_path('paiement-effectue');
					wp_redirect(get_permalink($page_payment_done->ID) . '?meanofpayment=wallet&campaign_id=' . $campaign->ID);
				}
			break;

		    //Paiement par virement
		    case 'wire':
			    if ($campaign->can_use_wire($_SESSION['redirect_current_amount_part'])) {
				    //Récupération de l'url pour permettre le paiement
				    $page_payment = get_page_by_path('paiement-virement');
					
					if ($campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway) {
						$_SESSION['amount_to_save'] = $amount;
						if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);
						if (isset($_SESSION['redirect_current_amount_part'])) unset($_SESSION['redirect_current_amount_part']);
						wp_redirect(get_permalink($page_payment->ID) . '?meanofpayment=wire&campaign_id=' . $campaign->ID);
						exit();
					}
			    }
		    break;
		    
			//Paiement par chèque
		    case 'check':
			    if ($campaign->can_use_check($_SESSION['redirect_current_amount_part'])) {
				    //Récupération de l'url pour permettre le paiement
				    $page_payment = get_page_by_path('paiement-cheque');
				    wp_redirect(get_permalink($page_payment->ID) . '?meanofpayment=check&campaign_id=' . $campaign->ID);
				    exit();
				    
			    }
		    break;

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
			if (wp_check_password( $_POST["update_password_current"], $current_user->data->user_pass, $current_user->ID)) :
				if (($_POST["update_email"] != "" && $_POST["update_email"] != $current_user->user_email)) {
				if (is_email( $_POST["update_email"] )) {
					wp_update_user( array ( 'ID' => $current_user->ID, 'user_email' => $_POST["update_email"] ) );
					$current_user->user_email = $_POST["update_email"];
				}
				}
				if ($_POST["update_password"] != "" && $_POST["update_password"] == $_POST["update_password_confirm"]) wp_update_user( array ( 'ID' => $current_user->ID, 'user_pass' => $_POST["update_password"] ) );
			endif;
			}
		}

		$errors = (isset($_SESSION['error_invest'])) ? $_SESSION['error_invest'] : array();
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
    $payment_post = get_post($payment_id);
	$downloads = edd_get_payment_meta_downloads($payment_id);
	$download_id = '';
	if (is_array($downloads[0])) $download_id = $downloads[0]["id"]; 
	else $download_id = $downloads[0];
	$post_campaign = get_post($download_id);
	$campaign = atcf_get_campaign($post_campaign);
	
    $init_payment_status = $payment_post->post_status;
    $buffer = $init_payment_status;
    
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
							$contract_id = ypcf_create_contract($payment_id, $download_id, $current_user->ID);
							if ($contract_id != '') {
								$contract_infos = signsquid_get_contract_infos($contract_id);
								if ( empty( $wdginvestment ) || !$wdginvestment->has_token() ) {
									NotificationsEmails::new_purchase_user_success($payment_id, $contract_infos->{'signatories'}[0]->{'code'}, $is_card_contribution);
								}
								NotificationsEmails::new_purchase_admin_success($payment_id);
							} else {
								global $contract_errors;
								$contract_errors = 'contract_failed';
								if ( empty( $wdginvestment ) || !$wdginvestment->has_token() ) {
									NotificationsEmails::new_purchase_user_error_contract($payment_id);
								}
								NotificationsEmails::new_purchase_admin_error_contract($payment_id);
							}
						} else {
							$new_contract_pdf_file = getNewPdfToSign($download_id, $payment_id, $current_user->ID);
							if ( empty( $wdginvestment ) || !$wdginvestment->has_token() ) {
								NotificationsEmails::new_purchase_user_success_nocontract($payment_id, $new_contract_pdf_file, $is_card_contribution);
							}
							NotificationsEmails::new_purchase_admin_success_nocontract($payment_id, $new_contract_pdf_file);
						}
					} else {
						if ( empty( $wdginvestment ) || !$wdginvestment->has_token() ) {
							NotificationsEmails::new_purchase_user($payment_id, '');
						}
						NotificationsEmails::new_purchase_admin_success($payment_id);
					}
					NotificationsSlack::send_to_dev('Nouvel achat !');
					NotificationsEmails::new_purchase_team_members($payment_id);

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
								$contract_id = ypcf_create_contract($payment_id, $download_id, $current_user->ID);
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

/**
 * Création du contrat à signer
 * @param type $payment_id
 * @param type $campaign_id
 * @param type $user_id
 */
function ypcf_create_contract($payment_id, $campaign_id, $user_id) {
    $post = get_post($campaign_id);
    $campaign = atcf_get_campaign( $post );
    $user = get_userdata($user_id);
    $contract_id = 0;
    
    ypcf_debug_log('ypcf_create_contract --- START');
    if (isset($post, $campaign, $user)) {
	//Nom du contrat = "NOM_PROJET - Investissement de INV€ de NOM_UTILISATEUR (MAIL_UTILISATEUR) - Le DATE"
	$project_name = get_the_title( $campaign->ID );
	$amount = edd_get_payment_amount($payment_id);
	$user_name = $user->user_firstname . ' ' . $user->user_lastname . ' (' . $user->user_email . ')';
	$date_payment = date_i18n( get_option('date_format'), strtotime( get_post_field( 'post_date', $payment_id ) ) );
	$contract_name = $project_name .' - Investissement de ' .$amount. '€ de ' . $user_name . ' - Le ' . $date_payment;
	
	ypcf_debug_log('ypcf_create_contract --- CALL signsquid_create_contract');
	$contract_id = signsquid_create_contract($contract_name);
	if ($contract_id != '') {
	    global $contract_errors;
	    update_post_meta($payment_id, 'signsquid_contract_id', $contract_id);
	    $mobile_phone = '';
	    if (ypcf_check_user_phone_format($user->get('user_mobile_phone'))) $mobile_phone = ypcf_format_french_phonenumber($user->get('user_mobile_phone'));
	    if (!signsquid_add_signatory($contract_id, $user->user_firstname . ' ' . $user->user_lastname, $user->user_email, $mobile_phone)) $contract_errors = 'contract_addsignatories_failed';
	    $contract_filename = getNewPdfToSign($campaign_id, $payment_id, $user_id);
	    if (!signsquid_add_file($contract_id, $contract_filename)) $contract_errors = 'contract_addfile_failed';
	    if (!signsquid_send_invite($contract_id)) $contract_errors = 'contract_sendinvite_failed';
	} else {
	    global $contract_errors;
	    $contract_errors = 'contract_creation_failed';
	}
    } else {
	global $contract_errors;
	$contract_errors = 'contract_creation_failed';
    }
    ypcf_debug_log('ypcf_create_contract --- $contract_errors : ' . $contract_errors);
    ypcf_debug_log('ypcf_create_contract --- END');
    
    return $contract_id;
}

/**
 * Test pour vérifier que le numéro de téléphone est conforme
 * @param type $mobile_phone
 */
function ypcf_check_user_phone_format($mobile_phone) {
    $buffer = false;
    
    //Numéro de téléphone type que doit renvoyer ypcf_format_french_phonenumber
    $classic_phone_number = '+33612345678';
    if ($mobile_phone != '' && strlen(ypcf_format_french_phonenumber($mobile_phone)) == strlen($classic_phone_number)) {
	$buffer = true;
    }
    
    return $buffer;
}

/**
 * Retourne le bon numéro de téléphone
 * @param type $phoneNumber
 * @return type
 */
function ypcf_format_french_phonenumber($phoneNumber){ 
    //Supprimer tous les caractères qui ne sont pas des chiffres 
    $phoneNumber = preg_replace('/[^0-9]+/', '', $phoneNumber); 
    //Garder les 9 derniers chiffres 
    $phoneNumber = substr($phoneNumber, -9); 
    //On ajoute +33 
    $motif = '+33\1\2\3\4\5';
    $phoneNumber = preg_replace('/(\d{1})(\d{2})(\d{2})(\d{2})(\d{2})/', $motif, $phoneNumber); 
    return $phoneNumber; 
}


function ypcf_fake_sharing_display($text = '', $echo = false ) {
	global $post, $wp_current_filter;

	if ( empty( $post ) )
//		return $text;

	if ( is_preview() ) {
//		return $text;
	}

	// Don't output flair on excerpts
	if ( in_array( 'get_the_excerpt', (array) $wp_current_filter ) ) {
//		return $text;
	}

	// Don't allow flair to be added to the_content more than once (prevent infinite loops)
	$done = false;
	foreach ( $wp_current_filter as $filter ) {
		if ( 'the_content' == $filter ) {
			if ( $done )
				return $text;
			else
				$done = true;
		}
	}

	// check whether we are viewing the front page and whether the front page option is checked
	$options = get_option( 'sharing-options' );
	$display_options = $options['global']['show'];

	if ( is_front_page() && ( is_array( $display_options ) && ! in_array( 'index', $display_options ) ) )
//		return $text;

	if ( is_attachment() && in_array( 'the_excerpt', (array) $wp_current_filter ) ) {
		// Many themes run the_excerpt() conditionally on an attachment page, then run the_content().
		// We only want to output the sharing buttons once.  Let's stick with the_content().
//		return $text;
	}

	$sharer = new Sharing_Service();
	$global = $sharer->get_global_options();

	/*$show = false;
	if ( !is_feed() ) {
		if ( is_singular() && in_array( get_post_type(), $global['show'] ) ) {
			$show = true;
		} elseif ( in_array( 'index', $global['show'] ) && ( is_home() || is_archive() || is_search() ) ) {
			$show = true;
		}
	}

	// Pass through a filter for final say so
	$show = apply_filters( 'sharing_show', $show, $post );*/
	$show = true;

	// Disabled for this post?
	$switched_status = get_post_meta( $post->ID, 'sharing_disabled', false );

	if ( !empty( $switched_status ) )
		$show = false;

	// Allow to be used on P2 ajax requests for latest posts.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && 'get_latest_posts' == $_REQUEST['action'] )
		$show = true;

	$sharing_content = '';

	if ( $show ) {
		$enabled = apply_filters( 'sharing_enabled', $sharer->get_blog_services() );

		if ( count( $enabled['all'] ) > 0 ) {
			global $post;

			$dir = get_option( 'text_direction' );

			// Wrapper
			$sharing_content .= '<div class="sharedaddy sd-sharing-enabled"><div class="robots-nocontent sd-block sd-social sd-social-' . $global['button_style'] . ' sd-sharing">';
			if ( $global['sharing_label'] != '' )
				$sharing_content .= '<h3 class="sd-title">' . $global['sharing_label'] . '</h3>';
			$sharing_content .= '<div class="sd-content"><ul>';

			// Visible items
			$visible = '';
			foreach ( $enabled['visible'] as $id => $service ) {
				// Individual HTML for sharing service
				$visible .= '<li class="share-' . $service->get_class() . '">' . $service->get_display( $post ) . '</li>';
			}

			$parts = array();
			$parts[] = $visible;
			if ( count( $enabled['hidden'] ) > 0 ) {
				if ( count( $enabled['visible'] ) > 0 )
					$expand = __( 'More', 'jetpack' );
				else
					$expand = __( 'Share', 'jetpack' );
				$parts[] = '<li><a href="#" class="sharing-anchor sd-button share-more"><span>'.$expand.'</span></a></li>';
			}

			if ( $dir == 'rtl' )
				$parts = array_reverse( $parts );

			$sharing_content .= implode( '', $parts );
			$sharing_content .= '<li class="share-end"></li></ul>';

			if ( count( $enabled['hidden'] ) > 0 ) {
				$sharing_content .= '<div class="sharing-hidden"><div class="inner" style="display: none;';

				if ( count( $enabled['hidden'] ) == 1 )
					$sharing_content .= 'width:150px;';

				$sharing_content .= '">';

				if ( count( $enabled['hidden'] ) == 1 )
					$sharing_content .= '<ul style="background-image:none;">';
				else
					$sharing_content .= '<ul>';

				$count = 1;
				foreach ( $enabled['hidden'] as $id => $service ) {
					// Individual HTML for sharing service
					$sharing_content .= '<li class="share-'.$service->get_class().'">';
					$sharing_content .= $service->get_display( $post );
					$sharing_content .= '</li>';

					if ( ( $count % 2 ) == 0 )
						$sharing_content .= '<li class="share-end"></li>';

					$count ++;
				}

				// End of wrapper
				$sharing_content .= '<li class="share-end"></li></ul></div></div>';
			}

			$sharing_content .= '</div></div></div>';

			// Register our JS
//			wp_register_script( 'sharing-js', plugin_dir_url( __FILE__ ).'sharing.js', array( 'jquery' ), '20121205' );
			add_action( 'wp_footer', 'sharing_add_footer' );
		}
	}

	if ( $echo )
		echo $text.$sharing_content;
	else
		return $text.$sharing_content;
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
    
    if ($invest_type != FALSE && $amount_part !== FALSE && is_numeric($amount_part) && ctype_digit($amount_part) 
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

/**
 * retourne le nombre d'investisseurs
 * @return type
 */
function ypcf_get_backers() {
    $buffer = 0;
	$current_campaign = atcf_get_current_campaign();
    if ( $current_campaign ) {
		//Récupérer la valeur maximale possible : la valeur totale du projet moins le montant déjà atteint
		$buffer = $current_campaign->backers_count();
    }
    return $buffer;
}


function ypcf_terms_agreement() {
    global $edd_options;
    if ( isset( $edd_options['show_agree_to_terms'] ) ) {
?>
	<fieldset id="edd_terms_agreement">
	    <div id="edd_terms" style="display:none;">
		<?php
		    do_action( 'edd_before_terms' );
		    echo wpautop( $edd_options['contract'] );
		    do_action( 'edd_after_terms' );
		?>
	    </div>
	    <div id="edd_show_terms">
		<a href="#" class="edd_terms_links"><?php _e( 'Show Terms', 'edd' ); ?></a>
		<a href="#" class="edd_terms_links" style="display:none;"><?php _e( 'Hide Terms', 'edd' ); ?></a>
	    </div>
	    <label for="edd_agree_to_terms"><?php echo isset( $edd_options['contract_label'] ) ? $edd_options['contract_label'] : __( 'Agree to Terms?', 'edd' ); ?></label>
	    <input name="edd_agree_to_terms" class="required" type="checkbox" id="edd_agree_to_terms" value="1"/>
	</fieldset>
<?php
    }
}