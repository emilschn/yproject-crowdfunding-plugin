<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function ypcf_shortcode_invest_confirmed() {
    if (isset($_GET['campaign_id']) && isset($_POST['amount']) && isset($_POST['confirmed']) && $_POST['confirmed'] == '1') {
	require_once(dirname(__FILE__) . '/mangopay/common.php');

	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	$currentpost_mangopayid = get_post_meta($campaign->ID, 'mangopay_wallet_id', true);

	$author_id = $campaign->data->post_author;
	$current_user = get_userdata($author_id);
	$currentuser_mangopayid = get_user_meta($current_user->ID, 'mangopay_user_id', true);

	$cent_amount = $_POST['amount'] * 100;

	$page_paiement_done = get_page_by_path('paiement-effectue');
	$page_paiement_done_link = get_permalink($page_paiement_done->ID);

	$mangopay_newcontribution = request('contributions', 'POST', '{ 
						"UserID" : '.$currentuser_mangopayid.', 
						"WalletID" : '.$currentpost_mangopayid.',
						"Amount" : '.$cent_amount.',
						"ReturnURL" : "'. $page_paiement_done_link .'"
					    }');

	echo '$mangopay_newcontribution->ID : ' . $mangopay_newcontribution->ID . ' , $mangopay_newcontribution->PaymentURL : ' . $mangopay_newcontribution->PaymentURL;
	if (isset($mangopay_newcontribution->ID)) {
	    wp_redirect($mangopay_newcontribution->PaymentURL);
	    exit();
	}
    }
}
add_action( 'template_redirect', 'ypcf_shortcode_invest_confirmed' );

/**
 */
 function ypcf_shortcode_invest_form($atts, $content = '') {
    $form = '';
    
    //TODO : tester si l'utilisateur est connecté (sinon, renvoyer sur la page de connexion)
    //TODO : tester si les informations suivantes ne manquent pas : Email, FirstName, Lastname, IP, Birthday, Nationality, Persontype (natural, legal)
    if (!isset($_POST['amount'])) $form .= ypcf_display_invest_form($content);

    return $form;
}
add_shortcode( 'yproject_crowdfunding_invest_form', 'ypcf_shortcode_invest_form' );

/**
 */
 function ypcf_shortcode_invest_confirm($atts, $content = '') {
    $form = '';
    
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();

    if (isset($_GET['campaign_id']) && isset($_POST['amount']) &&  $max_value > 0) {
	//Si la valeur peut être ponctionnée sur l'objectif, et si c'est bien du numérique supérieur à 0
	if (is_numeric($_POST['amount']) && $_POST['amount'] >= $min_value && $_POST['amount'] <= $max_value) {

	    ypcf_init_mangopay_user(wp_get_current_user());
	    ypcf_init_mangopay_wallet();

	    //TODO : Réserver dans le panier
	    $form .= $content;
	    $form .= '<form action="" method="post" enctype="multipart/form-data">';
	    $form .= '<input name="amount" type="hidden" value="' . $_POST['amount'] . '">';
	    $form .= '<input name="confirmed" type="hidden" value="1">';
	    $form .= $_POST['amount'] . '&euro;<input type="submit">';
	    $form .= '</form>';
	} else {
	    //TODO : gérer les erreurs
	    $error = 'Error (' . $_POST['amount'] . ')'; 
	    $form .= ypcf_display_invest_form($error);
	}

    }
    
    return $form;
 }
add_shortcode( 'yproject_crowdfunding_invest_confirm', 'ypcf_shortcode_invest_confirm' );

/**
 */
function ypcf_shortcode_invest_return($atts, $content = '') {
    $buffer = '';
    $mangopay_contribution_id = $_REQUEST["ContributionID"];
    $buffer = '$mangopay_contribution_id : ' . $mangopay_contribution_id;
    return $buffer;
}
add_shortcode( 'yproject_crowdfunding_invest_return', 'ypcf_shortcode_invest_return' );

/**
 * 
 */
function ypcf_display_invest_form($content = '') {
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();
    
    if (isset($_GET['campaign_id']) && $max_value > 0) {
	$form = $content;
	$form .= '<form id="invest_form" action="" method="post" enctype="multipart/form-data">';
	$form .= '<input id="input_invest_amount" name="amount" type="text" placeholder="' . $min_value . '">';
	$form .= '<input id="input_invest_min_value" name="old_min_value" type="hidden" value="' . $min_value . '">';
	$form .= '<input id="input_invest_max_value" name="old_max_value" type="hidden" value="' . $max_value . '">';
	$form .= '<input type="submit">';
	$form .= '</form>';
    } else {
	$form = '';
    }
    return $form;
}

/**
 * retourne une valeur minimale arbitraire
 * @return int
 */
function ypcf_get_min_value_to_invest() {
    return 1;
}

/**
 * 
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

function ypcf_init_mangopay_user($current_user) {
    require_once(dirname(__FILE__) . '/mangopay/common.php');
    
    //On s'apprête à confirmer, donc on vérifie si le currentuser a un compte sur mangopay. Si il n'en a pas, on le crée directement.
    $currentuser_mangopayid = get_user_meta($current_user->ID, 'mangopay_user_id', true);
    if ($currentuser_mangopayid == "") {
	$mangopay_new_user = request('users', 'POST', '{ 
				    "FirstName" : "'.$current_user->user_firstname.'", 
				    "LastName" : "'.$current_user->user_lastname.'", 
				    "Email" : "'.$current_user->user_email.'", 
				    "Nationality" : "FR", 
				    "Birthday" : 1300186358, 
				    "PersonType" : "NATURAL_PERSON",
				    "Tag" : "'.$current_user->user_login.'"
				}');
	if (isset($mangopay_new_user->ID)) {
	    update_user_meta($current_user->ID, 'mangopay_user_id', $mangopay_new_user->ID);
	    $mangopay_new_wallet = request('wallets', 'POST', '{ 
					"Owners" : ['.$mangopay_new_user->ID.'.], 
					"Name" : "Wallet of '.$current_user->display_name.'",
					"Tag" : "Wallet of '.$current_user->display_name.'",
					"Description" : "Wallet of '.$current_user->display_name.'"
				    }');
	    if (isset($mangopay_new_wallet->ID)) update_user_meta($current_user->ID, 'mangopay_wallet_id', $mangopay_new_wallet->ID);
	    
	    return $mangopay_new_user->ID;
	}
    }
    return $currentuser_mangopayid;
}

function ypcf_init_mangopay_wallet() {
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	
	$currentpost_mangopayid = get_post_meta($campaign->ID, 'mangopay_wallet_id', true);
	if ($currentpost_mangopayid == "") {
	    $author_id = $campaign->data->post_author;
	    $current_user = get_userdata($author_id);
	    $mangopay_new_user_id = ypcf_init_mangopay_user($current_user);
	    
	    if ($mangopay_new_user_id != "") {
		$mangopay_new_wallet = request('wallets', 'POST', '{ 
					    "Owners" : ['.$mangopay_new_user_id.'], 
					    "Name" : "Wallet for '.$campaign->data->post_title.'",
					    "Tag" : "Wallet for '.$campaign->data->post_title.'",
					    "Description" : "Wallet for '.$campaign->data->post_title.'",
					    "RaisingGoalAmount" : '.$campaign->goal(false).'
					}');
		if (isset($mangopay_new_wallet->ID)) update_post_meta($campaign->ID, 'mangopay_wallet_id', $mangopay_new_wallet->ID);
	    }
	}
    }
}
?>