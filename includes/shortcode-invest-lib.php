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
		//Redirection vers la page d'investissement après login, si on venait de l'investissement
		ypcf_check_is_user_logged_connexion();
		break;
	
	    case 'investir' :
		if (isset($_GET['invest_start'])) {
		    ypcf_session_start();
		    if (isset($_SESSION['redirect_current_amount_part'])) unset($_SESSION['redirect_current_amount_part']);
		    if (isset($_SESSION['redirect_current_invest_type'])) unset($_SESSION['redirect_current_invest_type']);
		}
		//D'abord on teste si l'utilisateur est bien connecté
		ypcf_check_is_user_logged_invest();
		ypcf_check_is_project_investable();
		require( crowdfunding()->includes_dir . 'shortcode-invest.php' );
		$current_step = ypcf_get_current_step();
		if ($current_step == 1) {
		    require( crowdfunding()->includes_dir . 'shortcode-invest-step1.php' );
		}
		if ($current_step == 2) {
		    //On vérifie que les données utilisateurs sont valables
		    ypcf_check_user_can_invest(true);
		    //On vérifie les redirections nécessaires à l'investissement
		    ypcf_check_invest_redirections();
		    require( crowdfunding()->includes_dir . 'shortcode-invest-step1.php' );
		    require( crowdfunding()->includes_dir . 'shortcode-invest-step2.php' );
		}
		break;
	
	    case 'modifier-mon-compte' :
		//On teste si l'utilisateur vient de remplir ses données pour les enregistrer
		ypcf_check_has_user_filled_infos_and_redirect();
		break;
	
	    case 'paiement-effectue' :
		//Si on est sur la page paiement-effectue, on en profite pour inclure les fonctions correspondantes
		require( crowdfunding()->includes_dir . 'shortcode-invest.php' );
		require( crowdfunding()->includes_dir . 'shortcode-invest-step3.php' );
		break;
	
	    case 'paiement-partager' :
		//Si on est sur la page paiement-effectue, on en profite pour inclure les fonctions correspondantes
		require( crowdfunding()->includes_dir . 'shortcode-invest.php' );
		require( crowdfunding()->includes_dir . 'shortcode-invest-step4.php' );
		break;
	}
    }
}
add_action( 'template_redirect', 'ypcf_check_redirections' );

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
	if (isset($_GET['campaign_id'])) {
	    $_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
	    $page_connexion = get_page_by_path('connexion');
	    wp_redirect(get_permalink($page_connexion->ID));
	} else {
	    wp_redirect(site_url());
	}
	exit();
    } 
}

function ypcf_check_is_project_investable() {
    $post_camp = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post_camp );
    if (!ypcf_check_user_is_complete($post_camp->post_author) || $campaign->days_remaining() <= 0 || $campaign->campaign_status() != 'collecte') {
	wp_redirect(get_permalink($_GET['campaign_id']));
	exit();
    }
}

function ypcf_check_user_is_complete($user_id) {
    $is_complete = true;
    $current_user = get_user_by('id', $user_id);
    $is_complete = ($current_user->user_firstname != "") && ($current_user->user_lastname != "");
    $is_complete = $is_complete && ($current_user->get('user_birthday_day') != "") && ($current_user->get('user_birthday_month') != "") && ($current_user->get('user_birthday_year') != "");
    $is_complete = $is_complete && ypcf_is_major($current_user->get('user_birthday_day'), $current_user->get('user_birthday_month'), $current_user->get('user_birthday_year'));
    $is_complete = $is_complete && ($current_user->get('user_nationality') != "") && ($current_user->user_email != "");
    $is_complete = $is_complete && ($current_user->get('user_address') != "") && ($current_user->get('user_postal_code') != "") && ($current_user->get('user_city') != "");
    $is_complete = $is_complete && ($current_user->get('user_country') != "") && ($current_user->get('user_mobile_phone') != "");
    $is_complete = $is_complete && ($current_user->get('user_gender') != "") && ($current_user->get('user_birthplace') != "");
//    $is_complete = $is_complete && ($current_user->get('user_person_type') != "");
    return $is_complete;
}

/**
 * Vérification si l'utilisateur a bien rempli toutes ses données
 */
function ypcf_check_user_can_invest($redirect = false) {
    $can_invest = true;
    $current_user = wp_get_current_user();
    $can_invest = ($current_user->user_firstname != "") && ($current_user->user_lastname != "");
    $can_invest = $can_invest && ($current_user->get('user_birthday_day') != "") && ($current_user->get('user_birthday_month') != "") && ($current_user->get('user_birthday_year') != "");
    $can_invest = $can_invest && ypcf_is_major($current_user->get('user_birthday_day'), $current_user->get('user_birthday_month'), $current_user->get('user_birthday_year'));
    $can_invest = $can_invest && ($current_user->get('user_nationality') != "") && ($current_user->user_email != "");
    $can_invest = $can_invest && ($current_user->get('user_address') != "") && ($current_user->get('user_postal_code') != "") && ($current_user->get('user_city') != "");
    $can_invest = $can_invest && ($current_user->get('user_country') != "") && ($current_user->get('user_mobile_phone') != "");
    $can_invest = $can_invest && ($current_user->get('user_gender') != "") && ($current_user->get('user_birthplace') != "");
//    $can_invest = $can_invest && ($current_user->get('user_person_type') == "NATURAL_PERSON");

    if ($redirect && !$can_invest) {
	ypcf_session_start();
	$_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
	if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
	if (isset($_POST['invest_type'])) $_SESSION['redirect_current_invest_type'] = $_POST['invest_type'];
	$errors = (isset($_SESSION['error_invest'])) ? $_SESSION['error_invest'] : array();
	array_push($errors, 'Certaines des informations manquent ou sont inexactes.');
//	if ($current_user->get('user_person_type') == "LEGAL_PERSONALITY") array_push($errors, 'Seules les personnes physiques peuvent investir pour l&apos;instant.');
	if (!ypcf_is_major($current_user->get('user_birthday_day'), $current_user->get('user_birthday_month'), $current_user->get('user_birthday_year'))) array_push($errors, 'Seules les personnes majeures peuvent investir.');
	$_SESSION['error_invest'] = $errors;
	$page_update = get_page_by_path('modifier-mon-compte');
	wp_redirect(get_permalink($page_update->ID));
	exit();
    }
    return $can_invest;
}

/**
 * Vérification si l'organisation peut investir
 */
function ypcf_check_organisation_can_invest($organisation_user_id) {
    $organisation_user = get_user_by('id', $organisation_user_id);
    $can_invest = ($organisation_user->user_email != '');
    $can_invest = $can_invest && ($organisation_user->get('user_type') == 'organisation');
    $can_invest = $can_invest && ($organisation_user->get('organisation_type') == 'society');
    $can_invest = $can_invest && ($organisation_user->get('user_nationality') != '');
    $can_invest = $can_invest && ($organisation_user->get('organisation_legalform') != '');
    $can_invest = $can_invest && ($organisation_user->get('organisation_idnumber') != '');
    $can_invest = $can_invest && ($organisation_user->get('organisation_rcs') != '');
    $can_invest = $can_invest && ($organisation_user->get('organisation_capital') != '');
    $can_invest = $can_invest && ($organisation_user->get('user_address') != '');
    $can_invest = $can_invest && ($organisation_user->get('user_postal_code') != '');
    $can_invest = $can_invest && ($organisation_user->get('user_city') != '');

    if (!$can_invest) {
	$errors = (isset($_SESSION['error_invest'])) ? $_SESSION['error_invest'] : array();
	array_push($errors, 'Certaines des informations de l\'entreprise manquent ou sont inexactes.');
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
    $post_camp = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post_camp );
    if (!isset($campaign)) {
	wp_redirect(site_url());
	exit();
    }
    
    //Si le projet est en court de vote ou que le montant de la part est à 0 (donc non-défini), on retourne à la page projet
    if ($campaign->vote() == 'vote' || $campaign->part_value() == 0) {
	wp_redirect(get_permalink($post_camp->ID));
	exit();
    }
    
    //Si l'utilisateur veut investir pour une nouvelle organisation, on l'envoie vers "Mon compte" pour qu'il ajoute l'organisation
    if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] == 'new_organisation') {
	$_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
	if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
	$page_update = get_page_by_path('modifier-mon-compte');
	wp_redirect(get_permalink($page_update->ID));
	exit();
    }
    
    //Si l'utilisateur veut investir pour une organisation existante
    if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] != 'new_organisation' && $_SESSION['redirect_current_invest_type'] != 'user') {
	$group = groups_get_group( array( 'group_id' => $_SESSION['redirect_current_invest_type'] ) );
	if (!ypcf_check_organisation_can_invest($group->creator_id)) {
	    $_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
	    if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
	    $page_update = get_page_by_path('modifier-mon-compte');
	    wp_redirect(get_permalink($page_update->ID));
	    exit();
	}
    }

    //Si on a validé la confirmation
    //Il faut donc créer une contribution sur mangopay et rediriger sur la page de paiement récupérée
    $max_part_value = ypcf_get_max_part_value();
    //Tests de la validité de l'investissement : utilisateur loggé, projet défini, montant correct, informations validées par coche, bon pour pouvoir écrit
    if (is_user_logged_in() && isset($_GET['campaign_id']) && isset($_POST['amount_part']) && is_numeric($_POST['amount_part']) && ctype_digit($_POST['amount_part']) 
	    && intval($_POST['amount_part']) == $_POST['amount_part'] && $_POST['amount_part'] >= 1 && $_POST['amount_part'] <= $max_part_value 
	    && isset($_POST['information_confirmed']) && $_POST['information_confirmed'] == '1' && isset($_POST['confirm_power']) && strtolower($_POST['confirm_power']) == 'bon pour pouvoir') {
	
	if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);
	if (isset($_SESSION['redirect_current_amount_part'])) unset($_SESSION['redirect_current_amount_part']);
	
	//Récupération de l'url de la page qui indique que le paiement est bien effectué
	$current_user = wp_get_current_user();
	$amount = $_POST['amount_part'] * ypcf_get_part_value();
	$page_paiement_done = get_page_by_path('paiement-effectue');
	$mangopay_newcontribution = ypcf_mangopay_contribution_user_to_project($current_user, $_GET['campaign_id'], $amount, $page_paiement_done);

	//Analyse de la contribution pour récupérer l'url de paiement
	if (isset($mangopay_newcontribution->ID)) {
	    wp_redirect($mangopay_newcontribution->PaymentURL);
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
	if (isset($_POST["update_email_contact"])) {
	    if (($_POST["update_email_contact"] != "" && $_POST["update_email_contact"] != $current_user->user_email)) {
		$validate_email = bp_core_validate_email_address($_POST["update_email_contact"]);
		if ($validate_email === true) {
		    wp_update_user( array ( 'ID' => $current_user->ID, 'user_email' => $_POST["update_email_contact"] ) );
		    $current_user->user_email = $_POST["update_email_contact"];
		}
	    }
	    
	} else {
	    if (isset($_POST["update_password_current"])) {
		if (!isset($_POST["update_email"])) $validate_email = true;
		if (wp_check_password( $_POST["update_password_current"], $current_user->data->user_pass, $current_user->ID)) :
		    if (($_POST["update_email"] != "" && $_POST["update_email"] != $current_user->user_email)) {
			$validate_email = bp_core_validate_email_address($_POST["update_email"]);
			if ($validate_email === true) {
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
	if (isset($_POST['new_organisation'])) {
	    $validate_email = bp_core_validate_email_address($_POST["new_org_email"]);
	    if ($_POST['new_org_name'] != '' && isset($_POST['new_organisation_capable']) && $_POST['new_organisation_capable'] && $validate_email) {
		//Création d'un utilisateur pour l'organisation
		$username = 'org_' . sanitize_title_with_dashes($_POST['new_org_name']);
		$password = wp_generate_password();
		$organisation_user_id = wp_create_user($username, $password, $_POST['new_org_email']);
		if (!isset($organisation_user_id->errors) || count($organisation_user_id->errors) == 0) {
		    wp_update_user( array ( 'ID' => $organisation_user_id, 'first_name' => $_POST['new_org_name'] ) ) ;
		    wp_update_user( array ( 'ID' => $organisation_user_id, 'last_name' => $_POST['new_org_name'] ) ) ;
		    wp_update_user( array ( 'ID' => $organisation_user_id, 'display_name' => $_POST['new_org_name'] ) ) ;
		    update_user_meta($organisation_user_id, 'user_type', 'organisation');
		    update_user_meta($organisation_user_id, 'user_address', $_POST['new_org_address']);
		    update_user_meta($organisation_user_id, 'user_nationality', $_POST['new_org_nationality']);
		    update_user_meta($organisation_user_id, 'user_postal_code', $_POST['new_org_postal_code']);
		    update_user_meta($organisation_user_id, 'user_city', $_POST['new_org_city']);
		    update_user_meta($organisation_user_id, 'organisation_type', 'society');
		    update_user_meta($organisation_user_id, 'organisation_legalform', $_POST['new_org_legalform']);
		    update_user_meta($organisation_user_id, 'organisation_capital', $_POST['new_org_capital']);
		    update_user_meta($organisation_user_id, 'organisation_idnumber', $_POST['new_org_idnumber']);
		    update_user_meta($organisation_user_id, 'organisation_rcs', $_POST['new_org_rcs']);

		    //Création d'un groupe pour l'organisation
		    $new_group_id = groups_create_group( array( 
			'creator_id' => $organisation_user_id,
			'name' => $_POST['new_org_name'],
			'description' => $_POST['new_org_name'],
			'slug' => groups_check_slug( sanitize_title( esc_attr( $_POST['new_org_name'] ) ) ), 
			'date_created' => bp_core_current_time(), 
			'enable_forum' => 0,
			'status' => 'private' ) );
		    groups_update_groupmeta( $new_group_id, 'group_type', 'organisation');

		    //Ajout de l'utilisateur créé et de l'utilisateur en cours dans le groupe (et on les passe admin)
		    groups_accept_invite( $organisation_user_id, $new_group_id);
		    $org_group_member = new BP_Groups_Member($organisation_user_id, $new_group_id);
		    $org_group_member->promote('admin');
		    groups_accept_invite( $current_user->ID, $new_group_id);
		    $current_group_member = new BP_Groups_Member($current_user->ID, $new_group_id);
		    $current_group_member->promote('admin');
		    $_SESSION['redirect_current_invest_type'] = $new_group_id;

		    $organisation_user = get_user_by('id', $organisation_user_id);
		    $url_request = ypcf_init_mangopay_user_strongauthentification($organisation_user);
		    $curl_result_cni = (isset($_FILES['new_org_file_cni']['tmp_name'])) ? ypcf_mangopay_send_strong_authentication($url_request, 'new_org_file_cni') : false;
		    $curl_result_status = (isset($_FILES['new_org_file_status']['tmp_name'])) ? ypcf_mangopay_send_strong_authentication($url_request, 'new_org_file_status') : false;
		    $curl_result_extract = (isset($_FILES['new_org_file_extract']['tmp_name'])) ? ypcf_mangopay_send_strong_authentication($url_request, 'new_org_file_extract') : false;
		    if (isset($_FILES['new_org_file_declaration']['tmp_name'])) ypcf_mangopay_send_strong_authentication($url_request, 'new_org_file_declaration');
		    if ($curl_result_cni && $curl_result_status && $curl_result_extract) ypcf_mangopay_set_user_strong_authentication_doc_transmitted($current_user->ID);
		} else {
		    foreach ($organisation_user_id->errors as $error) {
			array_push($errors, $error[0]);
		    }
		}
	    } else {
		if ($_POST['new_org_name'] == '') array_push($errors, 'Merci de renseigner une dénomination sociale.');
		if (!isset($_POST['new_organisation_capable']) || !$_POST['new_organisation_capable']) array_push($errors, 'Merci de valider que vous êtes en capacité de représenter cette organisation.');
		if (!$validate_email) array_push($errors, 'L\'adresse e-mail de l\'organisation n\'est pas correcte.');
	    }
	//Mise à jour d'une organisation
	} elseif (isset($_POST['update_organisation'])) {
	    //Parcourir toutes les organisations
	    $group_ids = BP_Groups_Member::get_group_ids( $current_user->ID );
	    foreach ($group_ids['groups'] as $group_id) {
		$group = groups_get_group( array( 'group_id' => $group_id ) );
		$group_type = groups_get_groupmeta($group_id, 'group_type');
		$name_suffix = '_' . $group_id;
		if (isset($_POST['update_organisation' . $name_suffix]) && $group->status == 'private' && $group_type == 'organisation' && BP_Groups_Member::check_is_admin($current_user->ID, $group_id) && $_POST['new_org_name'.$name_suffix] != '') {
	    
		    $group->name = $_POST['new_org_name'.$name_suffix];
		    $group->description = $_POST['new_org_name'.$name_suffix];
		    $group->save();

		    $organisation_user_id = $group->creator_id;
		    wp_update_user( array ( 'ID' => $organisation_user_id, 'first_name' => $_POST['new_org_name'.$name_suffix] ) ) ;
		    wp_update_user( array ( 'ID' => $organisation_user_id, 'last_name' => $_POST['new_org_name'.$name_suffix] ) ) ;
		    wp_update_user( array ( 'ID' => $organisation_user_id, 'display_name' => $_POST['new_org_name'.$name_suffix] ) ) ;
		    wp_update_user( array ( 'ID' => $organisation_user_id, 'user_email' => $_POST['new_org_email'.$name_suffix] ) );
		    update_user_meta($organisation_user_id, 'user_address', $_POST['new_org_address'.$name_suffix]);
		    update_user_meta($organisation_user_id, 'user_nationality', $_POST['new_org_nationality'.$name_suffix]);
		    update_user_meta($organisation_user_id, 'user_postal_code', $_POST['new_org_postal_code'.$name_suffix]);
		    update_user_meta($organisation_user_id, 'user_city', $_POST['new_org_city'.$name_suffix]);
		    update_user_meta($organisation_user_id, 'organisation_legalform', $_POST['new_org_legalform'.$name_suffix]);
		    update_user_meta($organisation_user_id, 'organisation_capital', $_POST['new_org_capital'.$name_suffix]);
		    update_user_meta($organisation_user_id, 'organisation_idnumber', $_POST['new_org_idnumber'.$name_suffix]);
		    update_user_meta($organisation_user_id, 'organisation_rcs', $_POST['new_org_rcs'.$name_suffix]);
		
		    $organisation_user = get_user_by('id', $organisation_user_id);
		    $url_request = ypcf_init_mangopay_user_strongauthentification($organisation_user);
		    $curl_result_cni = (isset($_FILES['new_org_file_cni'.$name_suffix]['tmp_name'])) ? ypcf_mangopay_send_strong_authentication($url_request, 'new_org_file_cni'.$name_suffix) : false;
		    $curl_result_status = (isset($_FILES['new_org_file_status'.$name_suffix]['tmp_name'])) ? ypcf_mangopay_send_strong_authentication($url_request, 'new_org_file_status'.$name_suffix) : false;
		    $curl_result_extract = (isset($_FILES['new_org_file_extract'.$name_suffix]['tmp_name'])) ? ypcf_mangopay_send_strong_authentication($url_request, 'new_org_file_extract'.$name_suffix) : false;
		    if (isset($_FILES['new_org_file_declaration'.$name_suffix]['tmp_name'])) ypcf_mangopay_send_strong_authentication($url_request, 'new_org_file_declaration'.$name_suffix);
		    if ($curl_result_cni && $curl_result_status && $curl_result_extract) ypcf_mangopay_set_user_strong_authentication_doc_transmitted($organisation_user->ID);
		}
	    }
	}
	$_SESSION['error_invest'] = $errors;

	if (isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "") {
	    if (!isset($_SESSION['error_invest']) || count($_SESSION['error_invest']) == 0) {
		if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
		if (isset($_POST['invest_type'])) $_SESSION['redirect_current_invest_type'] = $_POST['invest_type'];
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
    return $redirect_to;
}

/**
 * Affiche les étapes lors de l'investissement
 * @param type $current_step
 * @return string
 */
function ypcf_print_invest_breadcrumb($current_step) {
    $buffer = '<div id="invest-breadcrumb"><img src="'. get_stylesheet_directory_uri() .'/images/paiement_'.$current_step.'.jpg" width="600" height="100" alt="Parcours d&apos;investissement" /></div>';
    return $buffer;
}


/**
 * Met à jour le statut edd en fonction du statut du paiement sur mangopay
 * @param type $payment_id
 * @return string
 */
function ypcf_get_updated_payment_status($payment_id) {
    $payment_post = get_post($payment_id);
    $init_payment_status = $payment_post->post_status;
    $buffer = false;
    if (isset($payment_id) && $payment_id != '') {
	/* Nécessaire pour regénérer un contrat :
	 * if ($payment_id == 1498) {
	    $downloads = edd_get_payment_meta_downloads($payment_id); 
	    $download_id = '';
	    if (is_array($downloads[0])) $download_id = $downloads[0]["id"]; 
	    else $download_id = $downloads[0];
	    getNewPdfToSign($download_id, $payment_id, $payment_post->post_author);
	}*/
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
	    if (isset($contribution_id) && $contribution_id != '') {
		$mangopay_contribution = ypcf_mangopay_get_contribution_by_id($contribution_id);
		if ($mangopay_contribution && $mangopay_contribution->Type != 'UserError') {
		    if ($mangopay_contribution->IsCompleted) {
			if ($mangopay_contribution->IsSucceeded) {
			    $buffer = 'publish';
			    if ($buffer !== $init_payment_status) {
				//Création du contrat à signer
				$current_user = get_user_by('id', $payment_post->post_author);
				$downloads = edd_get_payment_meta_downloads($payment_id); 
				$download_id = '';
				if (is_array($downloads[0])) $download_id = $downloads[0]["id"]; 
				else $download_id = $downloads[0];
				$contract_id = ypcf_create_contract($payment_id, $download_id, $current_user->ID);
				if ($contract_id != '') {
				    $contract_infos = signsquid_get_contract_infos($contract_id);
				    ypcf_send_mail_purchase($payment_id, 'full', $contract_infos->{'signatories'}[0]->{'code'});
				    ypcf_send_mail_admin($payment_id, "purchase_complete");
				} else {
				    global $contract_errors;
				    $contract_errors = 'contract_failed';
				    ypcf_send_mail_purchase($payment_id, $contract_errors);
				    ypcf_send_mail_admin($payment_id, $contract_errors);
				}
			    }
			} else {
			    $buffer = 'failed';
			    $post_items = get_posts(array(
				'post_type' => 'edd_log',
				'meta_key' => '_edd_log_payment_id',
				'meta_value' => $payment_id
			    ));
			    foreach ($post_items as $post_item) {
				$postdata = array(
				    'ID'	=> $post_item->ID,
				    'post_status'=> $buffer
				);
				wp_update_post($postdata);
			    }
			}
		    } else {
			$buffer = 'pending';
		    }
		    $postdata = array(
			'ID'		=> $payment_id,
			'post_status'	=> $buffer,
			'edit_date'	=> current_time( 'mysql' )
		    );
		    wp_update_post($postdata);
		}
	    }
	}
    }
    return $buffer;
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
function ypcf_get_signsquidstatus_from_infos($contract_infos) {
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
    return $buffer;
}

/**
 * 
 * @param type $payment_id
 * @param type $type
 * @param type $code
 */
function ypcf_send_mail_purchase($payment_id, $type, $code = '', $force_email = '') {
    $downloads = edd_get_payment_meta_downloads($payment_id); 
    $download_id = '';
    if (is_array($downloads[0])) $download_id = $downloads[0]["id"]; 
    else $download_id = $downloads[0];
    $post_campaign = get_post($download_id);
				
    $payment_data = edd_get_payment_meta( $payment_id );
    $payment_amount = edd_get_payment_amount( $payment_id );
    $user_id      = edd_get_payment_user_id( $payment_id );
    $user_info    = maybe_unserialize( $payment_data['user_info'] );
    $email        = (isset($force_email) && $force_email != '') ? $force_email : edd_get_payment_user_email( $payment_id );

    if ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
	$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
    } elseif ( isset( $user_id ) && $user_id > 0 ) {
	$user_data = get_userdata($user_id);
	$name = $user_data->display_name;
    } else {
	$name = $email;
    }
    
    $subject = '';
    $body_content = '';
    switch ($type) {
	case "full":
	case "contract_failed":
	    $subject = "Merci pour votre investissement";
	    $dear_str = "Cher";
	    if ( isset( $user_info['gender'] ) && $user_info['gender'] == "female") $dear_str = "Chère";
	    $body_content = $dear_str." ".$name.",<br /><br />";
	    $body_content .= $post_campaign->post_title . " vous remercie pour votre investissement. N'oubliez pas qu'il ne sera définitivement validé ";
	    $body_content .= "que si le projet atteint son seuil minimal de financement. N'hésitez donc pas à en parler autour de vous et sur les réseaux sociaux !<br /><br />";
	    switch ($type) {
		case "full":
		    $body_content .= "Il vous reste encore à signer le contrat que vous devriez recevoir de la part de notre partenaire Signsquid ";
		    $body_content .= "(<strong>Pensez à vérifier votre courrier indésirable</strong>).<br />";
		    $body_content .= "Votre code personnel pour signer le contrat : <strong>" . $code . "</strong><br /><br />";
		    break;
		case "contract_failed":
		    $body_content .= "<span style=\"color: red;\">Il y a eu un problème durant la génération du contrat. Notre équipe en a été informée.</span><br /><br />";
		    break;
	    }
	    $body_content .= "<strong>Détails de l'investissement</strong><br />";
	    $body_content .= "Projet : " . $post_campaign->post_title . "<br />";
	    $body_content .= "Montant investi : ".$payment_amount."€<br />";
	    $body_content .= "Horodatage : ". get_post_field( 'post_date', $payment_id ) ."<br /><br />";
	    break;
	case "send_code":
	    $subject = "Code d'investissement";
	    $body_content = "Cher ".$name.",<br /><br />";
	    $body_content .= "Afin de confirmer votre investissement sur le projet " . $post_campaign->post_title . ", ";
	    $body_content .= "voici le code qui vous permettra de signer le contrat chez notre partenaire Signsquid :<br />";
	    $body_content .= $code . "<br /><br />";
	    $body_content .= "Si vous n'avez fait aucune action pour recevoir ce code, ne tenez pas compte de ce message.<br /><br />";
	    break;
    }

    $message = edd_get_email_body_header();
    $message .= $body_content;
    $message .= edd_get_email_body_footer();

    $from_name = get_bloginfo('name');
    $from_email = get_option('admin_email');
    $headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
    $headers .= "Reply-To: ". $from_email . "\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    $headers = apply_filters( 'edd_receipt_headers', $headers, $payment_id, $payment_data );

    return wp_mail( $email, $subject, $message, $headers );
}

/**
 * @param type $payment_id
 */
function ypcf_send_mail_admin($payment_id, $type) {
    switch ($type) {
	case "contract_failed":
	    $subject = 'Problème de création de contrat';
	    $message = 'Il y a eu un problème de création de contrat sur signsquid lors du paiement ' . $payment_id;
	    break;
	case "purchase_complete":
	    $subject = 'Nouvel achat';
	    $downloads = edd_get_payment_meta_downloads($payment_id);
	    $download_id = (is_array($downloads[0])) ? $downloads[0]["id"] : $downloads[0];
	    $post_campaign = get_post($download_id);
	    $payment_data = edd_get_payment_meta( $payment_id );
	    $payment_amount = edd_get_payment_amount( $payment_id );
	    $user_id      = edd_get_payment_user_id( $payment_id );
	    $user_info    = maybe_unserialize( $payment_data['user_info'] );
	    $email        = edd_get_payment_user_email( $payment_id );

	    if ( isset( $user_id ) && $user_id > 0 ) {
		$user_data = get_userdata($user_id);
		$name = $user_data->display_name;
	    } elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	    } else {
		$name = $email;
	    }
	    $message = 'Nouvel investissement avec l\'identifiant de paiement ' . $payment_id . '<br /><br />';
	    $message .= "<strong>Détails de l'investissement</strong><br />";
	    $message .= "Utilisateur : " . $name . "<br />";
	    $message .= "Projet : " . $post_campaign->post_title . "<br />";
	    $message .= "Montant investi : ".$payment_amount."€<br />";
	    $message .= "Horodatage : ". get_post_field( 'post_date', $payment_id ) ."<br /><br />";
	    break;
	case "project_posted":
	    $subject = '[Nouveau Projet]'.$_POST[ 'title' ];
	    $message = 'Un nouveau projet viens d\'être publié . <br />';
	    $message.= 'Il est accessible depuis le back-office.';
	    break;
    }
    
    $from_name = get_bloginfo('name') . ' admin bot';
    $from_email = 'admin@wedogood.co';
    $headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
    $headers .= "Reply-To: ". $from_email . "\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    
    ypcf_debug_log("ypcf_send_mail_admin --- MAIL :: payment_id :: ".$payment_id." ; type :: ".$type . " ; sent to : " . $from_email);
    if (!wp_mail( $from_email, $subject, $message, $headers )) {
	ypcf_debug_log("ypcf_send_mail_admin --- ERROR :: mail admin :: payment_id :: ".$payment_id);
    }
}

/**
 * retourne l'age en fonction du jour, mois et année
 * @param type $day
 * @param type $month
 * @param type $year
 * @return type
 */
function ypcf_get_age($day, $month, $year) {
    $today_day = date('j');
    $today_month = date('n');
    $today_year = date('Y');
    $years_diff = $today_year - $year;
    if ($today_month <= $month) {
	if ($month == $today_month) {
	    if ($day > $today_day) $years_diff--;
	} else {
	    $years_diff--;
	}
    }
    return $years_diff;
}

/**
 * retourne si l'utilisateur est majeur (en france)
 * @param type $day
 * @param type $month
 * @param type $year
 * @return type
 */
function ypcf_is_major($day, $month, $year) {
    return (ypcf_get_age($day, $month, $year) >= 18);
}

/**
 * retourne la valeur d'une part
 * @return type
 */
function ypcf_get_part_value() {
    $buffer = 0;
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	$buffer = $campaign->part_value();
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
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	//Récupérer la valeur maximale possible : la valeur totale du projet moins le montant déjà atteint
	$buffer = $campaign->goal(false) - $campaign->current_amount(false);
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
			wp_register_script( 'sharing-js', plugin_dir_url( __FILE__ ).'sharing.js', array( 'jquery' ), '20121205' );
			add_action( 'wp_footer', 'sharing_add_footer' );
		}
	}

	if ( $echo )
		echo $text.$sharing_content;
	else
		return $text.$sharing_content;
}
?>