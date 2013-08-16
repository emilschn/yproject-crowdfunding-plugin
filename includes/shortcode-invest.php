<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * On a validé la confirmation
 * Il faut donc créer une contribution sur mangopay et rediriger sur la page récupérée
 */
function ypcf_shortcode_invest_confirmed() {
    if (!is_user_logged_in() && isset($_GET['campaign_id'])) {
	if (session_id() == '') session_start();
	$_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
	$page_connexion = get_page_by_path('connexion');
	wp_redirect(get_permalink($page_connexion->ID));
	exit();
    }
    
    if (isset($_GET['campaign_id']) && isset($_POST['amount']) && is_numeric($_POST['amount']) && is_int($_POST['amount']) && isset($_POST['confirmed']) && $_POST['confirmed'] == '1') {
	require_once(dirname(__FILE__) . '/mangopay/common.php');

	//Récupération du walletid de la campagne
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	$currentpost_mangopayid = get_post_meta($campaign->ID, 'mangopay_wallet_id', true);

	//Récupération du walletid de l'utilisateur
	$author_id = $campaign->data->post_author;
	$current_user = get_userdata($author_id);
	$currentuser_mangopayid = get_user_meta($current_user->ID, 'mangopay_user_id', true);

	//Conversion de la somme saisie en cents
	$cent_amount = $_POST['amount'] * 100;

	//Récupération de l'url de la page qui indique que le paiement est bien effectué
	$page_paiement_done = get_page_by_path('paiement-effectue');
	$page_paiement_done_link = get_permalink($page_paiement_done->ID);

	//Création de la contribution en elle-même
	$mangopay_newcontribution = request('contributions', 'POST', '{ 
						"UserID" : '.$currentuser_mangopayid.', 
						"WalletID" : '.$currentpost_mangopayid.',
						"Amount" : '.$cent_amount.',
						"ReturnURL" : "'. $page_paiement_done_link .'"
					    }');

	//Analyse de la contribution pour récupérer l'url de paiement
	if (isset($mangopay_newcontribution->ID)) {
	    wp_redirect($mangopay_newcontribution->PaymentURL);
	    exit();
	}
    } else {
	return "error";
    }
}
add_action( 'template_redirect', 'ypcf_shortcode_invest_confirmed' );

/**
 * redirige si nécessaire vers la page d'investissement
 * @param type $redirect_to
 * @param type $request
 * @param type $user
 * @return type
 */
function ypcf_login_redirect_invest( $redirect_to, $request, $user ) {
    if (session_id() == '') session_start();
    if ($_SESSION['redirect_current_campaign_id'] != "") {
	$page_invest = get_page_by_path('investir');
	$page_invest_link = get_permalink($page_invest->ID);
	$campaign_id_param = '?campaign_id=';
	$redirect_to = $page_invest_link . $campaign_id_param . $_SESSION['redirect_current_campaign_id'];
	unset($_SESSION['redirect_current_campaign_id']);
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'ypcf_login_redirect_invest', 10, 3 );

/**
 * Premier formulaire qui permet de remplir la somme que l'on veut investir
 * Doit au préalable vérifier que l'utilisateur est connecté et que ses informations sont complètes
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
 * Deuxième étape : après saisie de la somme à investir
 * Vérification que la somme correspond bien
 */
 function ypcf_shortcode_invest_confirm($atts, $content = '') {
    $form = '';
    
    //TODO : tester si l'utilisateur est connecté (sinon, renvoyer sur la page de connexion)
    //TODO : tester si les informations suivantes ne manquent pas : Email, FirstName, Lastname, IP, Birthday, Nationality, Persontype (natural, legal)
    
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();

    if (isset($_GET['campaign_id']) && isset($_POST['amount']) &&  $max_value > 0) {
	//Si la valeur peut être ponctionnée sur l'objectif, et si c'est bien du numérique supérieur à 0
	if (is_numeric($_POST['amount']) && intval($_POST['amount']) == $_POST['amount'] && $_POST['amount'] >= $min_value && $_POST['amount'] <= $max_value) {

	    ypcf_init_mangopay_user(wp_get_current_user());
	    ypcf_init_mangopay_project();

	    //TODO : Réserver dans le panier
	    $form .= $content;
	    $form .= '<form action="" method="post" enctype="multipart/form-data">';
	    $form .= '<input name="amount" type="hidden" value="' . $_POST['amount'] . '">';
	    $form .= '<input name="confirmed" type="hidden" value="1">';
	    $form .= $_POST['amount'] . '&euro;<input type="submit">';
	    $form .= '</form>';
	} else {
	    $error = 'general';
	    if (intval($_POST['amount']) != $_POST['amount']) $error = 'integer';
	    if ($_POST['amount'] >= $min_value) $error = 'min';
	    if ($_POST['amount'] <= $max_value) $error = 'max';
	    $form .= ypcf_display_invest_form($error);
	}

    }
    
    return $form;
 }
add_shortcode( 'yproject_crowdfunding_invest_confirm', 'ypcf_shortcode_invest_confirm' );

/**
 * Dernière étape : le paiement a été effectué, on revient sur le site
 */
function ypcf_shortcode_invest_return($atts, $content = '') {
    $buffer = '';
    $mangopay_contribution_id = $_REQUEST["ContributionID"];
    $buffer = '$mangopay_contribution_id : ' . $mangopay_contribution_id;
    //TODO : afficher des choses
    return $buffer;
}
add_shortcode( 'yproject_crowdfunding_invest_return', 'ypcf_shortcode_invest_return' );

/**
 * Formulaire de saisie d'investissement
 */
function ypcf_display_invest_form($error = '') {
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();
    
    if (isset($_GET['campaign_id']) && $max_value > 0) {
	$form = '';
	$form .= '<form id="invest_form" action="" method="post" enctype="multipart/form-data">';
	$form .= '<input id="input_invest_amount" name="amount" type="text" placeholder="' . $min_value . '">';
	$form .= '<input id="input_invest_min_value" name="old_min_value" type="hidden" value="' . $min_value . '">';
	$form .= '<input id="input_invest_max_value" name="old_max_value" type="hidden" value="' . $max_value . '">';
	$form .= '<input id="input_invest_amount_total" type="hidden" value="' . ypcf_get_current_amount() . '">';
	$form .= '<input type="submit">';
	$hidden = ' hidden';
	$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Le montant minimum que vous pouvez investir est de ' . $min_value . '&euro;.</span>';
	$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Le montant maximum que vous pouvez investir est de ' . $max_value . '&euro;.</span>';
	$form .= '<span class="invest_error'. (($error != "integer") ? $hidden : "") .'" id="invest_error_integer">Le montant que vous pouvez investir doit &ecirc;tre entier.</span>';
	$form .= '<span class="invest_error'. (($error != "general") ? $hidden : "") .'" id="invest_error_general">Le montant saisi semble comporter une erreur.</span>';
	$form .= '<span class="invest_success hidden" id="invest_success_message">Gr&acirc;ce à vous, nous serons ' . (ypcf_get_backers() + 1) . ' &agrave; soutenir le projet. La somme atteinte sera de <span id="invest_success_amount"></span>&euro;.</span>';
	$form .= '</form>';
    } else {
	$form = '';
    }
    return $form;
}

/**
 * retourne une valeur minimale arbitraire à investir
 * @return int
 */
function ypcf_get_min_value_to_invest() {
    return YP_MIN_INVEST_VALUE;
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
 * retourne la somme déjà atteinte
 * @return type
 */
function ypcf_get_current_amount() {
    $buffer = 0;
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	//Récupérer la valeur maximale possible : la valeur totale du projet moins le montant déjà atteint
	$buffer = $campaign->current_amount(false);
    }
    return $buffer;
}

/**
 * retourne le nombre d'investisseurs
 * @return type
 */
function ypcf_get_backers() {
    $buffer = 0;
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	//Récupérer la valeur maximale possible : la valeur totale du projet moins le montant déjà atteint
	$buffer = $campaign->backers_count();
    }
    return $buffer;
}

/**
 * Vérifie que l'utilisateur connecté a une correspondance dans mangopay pour un id utilisateur et un id porte-monnaie
 * @param type $current_user
 * @return type
 */
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
	    $currentuser_mangopayid = $mangopay_new_user->ID;
	    update_user_meta($current_user->ID, 'mangopay_user_id', $currentuser_mangopayid);
	}
    }
    //De même, on vérifie si le currentuser a un wallet sur mangopay. Si il n'en a pas, on le crée.
    $currentuser_wallet_mangopayid = get_user_meta($current_user->ID, 'mangopay_wallet_id', true);
    if ($currentuser_wallet_mangopayid == "") {
	$mangopay_new_wallet = request('wallets', 'POST', '{ 
					"Owners" : ['.$currentuser_mangopayid.'.], 
					"Name" : "Wallet of '.$current_user->display_name.'",
					"Tag" : "Wallet of '.$current_user->display_name.'",
					"Description" : "Wallet of '.$current_user->display_name.'"
				    }');
	if (isset($mangopay_new_wallet->ID)) update_user_meta($current_user->ID, 'mangopay_wallet_id', $mangopay_new_wallet->ID);
    }
    return $currentuser_mangopayid;
}

/**
 * Initialise le créateur du projet sur mangopay si nécessaire puis le porte-monnaie du projet.
 */
function ypcf_init_mangopay_project() {
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	
	$currentpost_mangopayid = get_post_meta($campaign->ID, 'mangopay_wallet_id', true);
	//Si le projet n'existe pas encore
	if ($currentpost_mangopayid == "") {
	    //On va chercher l'identifiant mangopay du porteur de projet
	    $author_id = $campaign->data->post_author;
	    $current_user = get_userdata($author_id);
	    $mangopay_new_user_id = ypcf_init_mangopay_user($current_user);
	    
	    //On crée le poret-monnaie du projet
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