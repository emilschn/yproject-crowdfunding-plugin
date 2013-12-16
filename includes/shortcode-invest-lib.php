<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
		//D'abord on teste si l'utilisateur est bien connecté
		ypcf_check_is_user_logged_invest();
		//Et on reteste si les données sont bel et bien remplies
		ypcf_check_user_can_invest(true);
		//On vérifie les redirections nécessaires à l'investissement
		ypcf_check_invest_redirections();
		require( crowdfunding()->includes_dir . 'shortcode-invest.php' );
		if (ypcf_get_current_step() == 1) {
		    require( crowdfunding()->includes_dir . 'shortcode-invest-step1.php' );
		}
		if (ypcf_get_current_step() == 2) {
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
	}
    }
}
add_action( 'template_redirect', 'ypcf_check_redirections' );

/**
 * Après le login, si on venait de l'investissement, il faut y retourner
 */
function ypcf_check_is_user_logged_connexion() {
    if (session_id() == '') session_start();

    if (is_user_logged_in() && isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "") {
	wp_redirect(ypcf_login_gobackinvest_url());
	exit();
    }
}


/**
 * Si l'utilisateur n'est pas connecté, on redirige vers la page de connexion en enregistrant la page d'investissement pour y revenir
 */
function ypcf_check_is_user_logged_invest() {
    if (session_id() == '') session_start();

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

/**
 * Vérification si l'utilisateur a bien rempli toutes ses données
 */
function ypcf_check_user_can_invest($redirect = false) {
    $can_invest = true;
    $current_user = wp_get_current_user();
    $can_invest = ($current_user->user_firstname != "") && ($current_user->user_lastname != "");
    $can_invest = $can_invest && ($current_user->get('user_birthday_day') != "") && ($current_user->get('user_birthday_month') != "") && ($current_user->get('user_birthday_year') != "");
    $can_invest = $can_invest && ypcf_is_major($current_user->get('user_birthday_day'), $current_user->get('user_birthday_month'), $current_user->get('user_birthday_year'));
    $can_invest = $can_invest && ($current_user->get('user_nationality') != "") && ($current_user->get('user_person_type') != "") && ($current_user->user_email != "");
    $can_invest = $can_invest && ($current_user->get('user_address') != "") && ($current_user->get('user_postal_code') != "") && ($current_user->get('user_city') != "");
    $can_invest = $can_invest && ($current_user->get('user_country') != "") && ($current_user->get('user_mobile_phone') != "");

    if ($redirect && !$can_invest) {
	if (session_id() == '') session_start();
	$_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
	$page_update = get_page_by_path('modifier-mon-compte');
	wp_redirect(get_permalink($page_update->ID));
	exit();
    }
    return $can_invest;
}

/**
 * Se charge de tester les redirections à effectuer
 */
function ypcf_check_invest_redirections() {
    if (session_id() == '') session_start();
    if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);

    $post_camp = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post_camp );
    if (!isset($campaign)) {
	wp_redirect(site_url());
	exit();
    }
    
    if ($campaign->vote() == 'vote' || $campaign->part_value() == 0) {
	wp_redirect(get_permalink($post_camp->ID));
	exit();
    }

    //On a validé la confirmation
    //Il faut donc créer une contribution sur mangopay et rediriger sur la page de paiement récupérée
    $max_part_value = ypcf_get_max_part_value();
    if (isset($_GET['campaign_id']) && isset($_POST['amount_part']) && is_numeric($_POST['amount_part']) && ctype_digit($_POST['amount_part']) 
	    && intval($_POST['amount_part']) == $_POST['amount_part'] && $_POST['amount_part'] >= 1 && $_POST['amount_part'] <= $max_part_value 
	    && isset($_POST['confirmed']) && $_POST['confirmed'] == '1' && isset($_POST['edd_agree_to_terms']) && $_POST['edd_agree_to_terms'] == '1') {
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
    $current_user = wp_get_current_user();
    if (is_user_logged_in() && isset($_POST["update_user_posted"]) && $_POST["update_user_id"] == $current_user->ID) {
	if ($_POST["update_firstname"] != "") wp_update_user( array ( 'ID' => $current_user->ID, 'first_name' => $_POST["update_firstname"] ) ) ;
	if ($_POST["update_lastname"] != "") wp_update_user( array ( 'ID' => $current_user->ID, 'last_name' => $_POST["update_lastname"] ) ) ;
	if ($_POST["update_birthday_day"] != "") update_user_meta($current_user->ID, 'user_birthday_day', $_POST["update_birthday_day"]);
	if ($_POST["update_birthday_month"] != "") update_user_meta($current_user->ID, 'user_birthday_month', $_POST["update_birthday_month"]);
	if ($_POST["update_birthday_year"] != "") update_user_meta($current_user->ID, 'user_birthday_year', $_POST["update_birthday_year"]);
	if ($_POST["update_nationality"] != "") update_user_meta($current_user->ID, 'user_nationality', $_POST["update_nationality"]);
	if ($_POST["update_person_type"] != "") update_user_meta($current_user->ID, 'user_person_type', $_POST["update_person_type"]);
	if ($_POST["update_address"] != "") update_user_meta($current_user->ID, 'user_address', $_POST["update_address"]);
	if ($_POST["update_postal_code"] != "") update_user_meta($current_user->ID, 'user_postal_code', $_POST["update_postal_code"]);
	if ($_POST["update_city"] != "") update_user_meta($current_user->ID, 'user_city', $_POST["update_city"]);
	if ($_POST["update_country"] != "") update_user_meta($current_user->ID, 'user_country', $_POST["update_country"]);
	if ($_POST["update_mobile_phone"] != "") update_user_meta($current_user->ID, 'user_mobile_phone', $_POST["update_mobile_phone"]);
	if (!isset($_POST["update_email"])) $validate_email = true;
	if (wp_check_password( $_POST["update_password_current"], $current_user->data->user_pass, $current_user->ID)) :
	    $validate_email = bp_core_validate_email_address($_POST["update_email"]);
	    if (($_POST["update_email"] != "") && ($validate_email === true)) {
		wp_update_user( array ( 'ID' => $current_user->ID, 'user_email' => $_POST["update_email"] ) );
		$current_user->user_email = $_POST["update_email"];
	    }
	    if ($_POST["update_password"] != "" && $_POST["update_password"] == $_POST["update_password_confirm"]) wp_update_user( array ( 'ID' => $current_user->ID, 'user_pass' => $_POST["update_password"] ) );
	endif;

	if (session_id() == '') session_start();
	if (isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "") {
	    wp_redirect(ypcf_login_gobackinvest_url());
	    exit();
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
    if (session_id() == '') session_start();
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
 * Met à jour le statut edd en fonction du statut du paiement sur mangopay
 * @param type $payment_id
 * @return string
 */
function ypcf_get_updated_payment_status($payment_id) {
    $payment_post = get_post($payment_id);
    $init_payment_status = $payment_post->post_status;
    $buffer = false;
    if (isset($payment_id) && $payment_id != '') {
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
				$current_user = wp_get_current_user();
				$downloads = edd_get_payment_meta_downloads($payment_id); 
				$download_id = '';
				if (is_array($downloads[0])) $download_id = $downloads[0]["id"]; 
				else $download_id = $downloads[0];
				$contract_id = ypcf_create_contract($payment_id, $download_id, $current_user->ID);
				if ($contract_id != '') {
				    $contract_infos = signsquid_get_contract_infos($contract_id);
				    // $contract_infos->{'signatories'}[0]->{'code'}
				    edd_email_purchase_receipt($payment_id, true); //Envoyer mail de confirmation avec le code
				} else {
				    global $contract_errors;
				    $contract_errors = 'contract_failed';
				    edd_email_purchase_receipt($payment_id, true); //Envoyer mail de confirmation sans le code et avec un message d'attente
				    //Envoyer un message à admin pour regarder le souci
				}
			    }
			} else {
			    $buffer = 'failed';
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
    if ($contract_infos != '') {
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
	}
    }
    return $buffer;
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
    
    if (isset($post, $campaign, $user)) {
	//Nom du contrat = "NOM_PROJET - Investissement de INV€ de NOM_UTILISATEUR (MAIL_UTILISATEUR) - Le DATE"
	$project_name = get_the_title( $campaign->ID );
	$amount = edd_get_payment_amount($payment_id);
	$user_name = $user->user_firstname . ' ' . $user->user_lastname . ' (' . $user->user_email . ')';
	$date_payment = date_i18n( get_option('date_format'), strtotime( get_post_field( 'post_date', $payment_id ) ) );
	$contract_name = $project_name .' - Investissement de ' .$amount. '€ de ' . $user_name . ' - Le ' . $date_payment;
	
	$contract_id = signsquid_create_contract($contract_name);
	if ($contract_id != '') {
	    global $contract_errors;
	    update_post_meta($payment_id, 'signsquid_contract_id', $contract_id);
	    $mobile_phone = '';
	    if (ypcf_check_user_phone_format($user->get('user_mobile_phone'))) $mobile_phone = $user->get('user_mobile_phone');
	    if (!signsquid_add_signatory($contract_id, $user->user_firstname . ' ' . $user->user_lastname, $user->user_email)) $contract_errors = 'contract_addsignatories_failed';
	    $contract_filename = getNewPdfToSign($campaign_id, $payment_id);
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
    
    return $contract_id;
}

/**
 * Test pour vérifier que le numéro de téléphone est conforme
 * @param type $mobile_phone
 */
function ypcf_check_user_phone_format($mobile_phone) {
    $buffer = false;
    
    return $buffer;
}
?>