<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Se charge de tester les redirections à effectuer
 */
function ypcf_check_redirections() {
    //D'abord on teste si l'utilisateur est bien connecté
    ypcf_check_is_user_logged();
    //Ensuite on teste si l'utilisateur vient de remplir ses données pour les enregistrer
    ypcf_check_has_user_filled_infos_and_redirect();
    //Et on reteste si les données sont bel et bien remplies
    ypcf_check_user_can_invest();
    
    //Remise à zero des variables de sessions éventuelles
    global $post;
    $page_name = get_post($post)->post_name;
    if ($page_name == 'investir') {
	if (session_id() == '') session_start();
	if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);
    }
    
    //On a validé la confirmation
    //Il faut donc créer une contribution sur mangopay et rediriger sur la page de paiement récupérée
    if (isset($_GET['campaign_id']) && isset($_POST['amount']) && is_numeric($_POST['amount']) && ctype_digit($_POST['amount']) && isset($_POST['confirmed']) && $_POST['confirmed'] == '1' && isset($_POST['edd_agree_to_terms']) && $_POST['edd_agree_to_terms'] == '1') {
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
	$page_paiement_done_link = get_permalink($page_paiement_done->ID) . '?campaign_id=' . $_GET['campaign_id'];

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
    }
}
add_action( 'template_redirect', 'ypcf_check_redirections' );

/**
 * redirige si nécessaire vers la page d'investissement
 * @param type $redirect_to
 * @param type $request
 * @param type $user
 * @return type
 */
function ypcf_login_redirect_invest( $redirect_to, $request, $user ) {
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
add_filter( 'login_redirect', 'ypcf_login_redirect_invest', 10, 3 );

/**
 * Premier formulaire qui permet de remplir la somme que l'on veut investir
 */
 function ypcf_shortcode_invest_form($atts, $content = '') {
    $form = '';
    
    if (!isset($_POST['amount'])) $form .= ypcf_display_invest_form($content);

    return $form;
}
add_shortcode( 'yproject_crowdfunding_invest_form', 'ypcf_shortcode_invest_form' );

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
	$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Le montant minimum que vous pouvez investir est de ' . $min_value . edd_get_currency() . '.</span>';
	$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Le montant maximum que vous pouvez investir est de ' . $max_value . edd_get_currency() . '.</span>';
	$form .= '<span class="invest_error'. (($error != "interval") ? $hidden : "") .'" id="invest_error_interval">Merci de ne pas laisser moins de ' . $min_value . edd_get_currency() . ' &agrave; investir.</span>';
	$form .= '<span class="invest_error'. (($error != "integer") ? $hidden : "") .'" id="invest_error_integer">Le montant que vous pouvez investir doit &ecirc;tre entier.</span>';
	$form .= '<span class="invest_error'. (($error != "general") ? $hidden : "") .'" id="invest_error_general">Le montant saisi semble comporter une erreur.</span>';
	$form .= '<span class="invest_success hidden" id="invest_success_message">Gr&acirc;ce à vous, nous serons ' . (ypcf_get_backers() + 1) . ' &agrave; soutenir le projet. La somme atteinte sera de <span id="invest_success_amount"></span>'.edd_get_currency().'.</span>';
	$form .= '</form>';
    } else {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	$form = 'Il n&apos;est plus possible d&apos;investir sur ce <a href="'.get_permalink($campaign->ID).'">projet</a> !';
    }
    return $form;
}

/**
 * Deuxième étape : après saisie de la somme à investir
 * Vérification que la somme correspond bien
 */
 function ypcf_shortcode_invest_confirm($atts, $content = '') {
    $form = '';
    
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();

    if (isset($_GET['campaign_id']) && isset($_POST['amount']) &&  $max_value > 0) {
	//Si la valeur peut être ponctionnée sur l'objectif, et si c'est bien du numérique supérieur à 0
	$amount_interval = $max_value - $_POST['amount'];
	if (is_numeric($_POST['amount']) && intval($_POST['amount']) == $_POST['amount'] && $_POST['amount'] >= $min_value && $_POST['amount'] <= $max_value && ($amount_interval == 0 || $amount_interval >= $min_value)) {

	    $current_user = wp_get_current_user();
	    ypcf_init_mangopay_user($current_user);
	    ypcf_init_mangopay_project();
	    
	    //Procédure modifiée d'ajout au panier (on ajoute x items de 1 euros => le montant se retrouve en tant que quantité)
	    $post = get_post($_GET['campaign_id']);
	    $campaign = atcf_get_campaign( $post );
	    edd_empty_cart();
	    $to_add = array();
	    $to_add[] = apply_filters( 'edd_add_to_cart_item', array( 'id' => $campaign->ID, 'options' => [], 'quantity' => $_POST['amount'] ) );
	    EDD()->session->set( 'edd_cart', $to_add );
	    
	    $form .= $content;
	    
	    // Rappel des informations remplies
	    if (session_id() == '') session_start();
	    $_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
	    $form .= $current_user->user_firstname . ' ' . $current_user->user_lastname . ' (' . $current_user->user_email . ' ; ' . $current_user->get('user_person_type') . ')<br />';
	    $form .= $current_user->get('user_nationality') . ' ; ' . $current_user->get('user_birthday_day') . '/' . $current_user->get('user_birthday_month') . '/' . $current_user->get('user_birthday_year') . '<br />';
	    $page_update = get_page_by_path('modifier-mon-compte');
	    $form .= '<a href="' . get_permalink($page_update->ID) . '">Modifier ces informations</a><br />';
	    
	    // Formulaire de confirmation
	    $form .= '<form action="" method="post" enctype="multipart/form-data">';
	    $form .= '<input name="amount" type="hidden" value="' . $_POST['amount'] . '">';
	    $form .= '<input name="confirmed" type="hidden" value="1">';
	    ob_start();
	    edd_agree_to_terms_js();
	    edd_terms_agreement();
	    $form .= ob_get_clean();
	    $form .= $_POST['amount'] . edd_get_currency() . ';<input type="submit">';
	    $form .= '</form>';
	} else {
	    $error = 'general';
	    if (intval($_POST['amount']) != $_POST['amount']) $error = 'integer';
	    if ($_POST['amount'] >= $min_value) $error = 'min';
	    if ($_POST['amount'] <= $max_value) $error = 'max';
	    if ($amount_interval > 0 && $amount_interval < $min_value) $error = 'interval';
	    unset($_POST['amount']);
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
    require_once(dirname(__FILE__) . '/mangopay/common.php');
    $mangopay_contribution_id = $_REQUEST["ContributionID"];
    $mangopay_contribution = request('contributions/'.$mangopay_contribution_id, 'GET');
    if ($mangopay_contribution->IsCompleted) {
	if ($mangopay_contribution->IsSucceeded) {
	    //On met à jour l'état de la campagne
	    $post = get_post($_GET['campaign_id']);
	    $campaign = atcf_get_campaign( $post );
	    
	    //Création d'un paiement pour edd
	    $current_user = wp_get_current_user();
	    $user_info = array(
		'id'         => $current_user->ID,
		'email'      => $current_user->user_email,
		'first_name' => $current_user->user_firstname,
		'last_name'  => $current_user->user_lastname,
		'discount'   => '',
		'address'    => array()
	    );
	    
	    $cart_details = array(
		array(
			'name'        => get_the_title( $campaign->ID ),
			'id'          => $campaign->ID,
			'item_number' => array(
				'id'      => $campaign->ID,
				'options' => []
			),
			'price'       => 1,
			'quantity'    => $mangopay_contribution->Amount / 100
		)
	    );
	    
	    $payment_data = array( 
		    'price' => $mangopay_contribution->Amount / 100, 
		    'date' => date('Y-m-d H:i:s'), 
		    'user_email' => $current_user->user_email,
		    'purchase_key' => strtolower( md5( uniqid() ) ),
		    'currency' => edd_get_currency(),
		    'downloads' => [$campaign->ID],
		    'user_info' => $user_info,
		    'cart_details' => $cart_details,
		    'status' => 'publish'
	    );
	    $payment = edd_insert_payment( $payment_data );
	    
	    edd_record_sale_in_log($campaign->ID, $payment);
	    edd_empty_cart();
	    
	    //On affiche que tout s'est bien passé
	    $amount = $mangopay_contribution->Amount / 100;
	    $buffer .= $content;
	    $buffer .= 'Merci pour votre don de ' . $amount . edd_get_currency() . '.<br />';
	    $buffer .= 'Nous sommes à pr&eacute;sent ' . ypcf_get_backers() . ' &agrave; soutenir le projet.<br />';
	    $buffer .= 'La somme atteinte est de ' . ypcf_get_current_amount() . edd_get_currency() . '.';
	    
	    //TODO : rajouter partage
	} else {
	    $buffer .= 'Il y a eu une erreur pendant la transacton : ' . $mangopay_contribution->Error->TechnicalDescription;
	}
    } else {
	$buffer .= 'Transaction en cours...';
    }
    return $buffer;
}
add_shortcode( 'yproject_crowdfunding_invest_return', 'ypcf_shortcode_invest_return' );

/**
 * Vérification si l'utilisateur est bien connecté
 * Si il ne l'est pas, on redirige vers la page de connexion
 * ATTENTION : en utilisant ça dans un plugin, la fonction est appelée sur toutes les pages du site. Peut-être plus optimisé dans la template
 */
function ypcf_check_is_user_logged() {
    global $post;
    $page_name = get_post($post)->post_name;

    if ($page_name == 'investir' && !is_user_logged_in()) {
	if (isset($_GET['campaign_id'])) {
	    if (session_id() == '') session_start();
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
 * Enregistre les données saisies par l'utilisateur et redirige vers la page d'investissement si nécessaire
 */
function ypcf_check_has_user_filled_infos_and_redirect() {
    global $post;
    $page_name = get_post($post)->post_name;
    if ($page_name == 'modifier-mon-compte') {
	$current_user = wp_get_current_user();
	if (is_user_logged_in() && isset($_POST["update_user_posted"]) && $_POST["update_user_id"] == $current_user->ID) {
	    if ($_POST["update_firstname"] != "") wp_update_user( array ( 'ID' => $current_user->ID, 'first_name' => $_POST["update_firstname"] ) ) ;
	    if ($_POST["update_lastname"] != "") wp_update_user( array ( 'ID' => $current_user->ID, 'last_name' => $_POST["update_lastname"] ) ) ;
	    if ($_POST["update_birthday_day"] != "") update_user_meta($current_user->ID, 'user_birthday_day', $_POST["update_birthday_day"]);
	    if ($_POST["update_birthday_month"] != "") update_user_meta($current_user->ID, 'user_birthday_month', $_POST["update_birthday_month"]);
	    if ($_POST["update_birthday_year"] != "") update_user_meta($current_user->ID, 'user_birthday_year', $_POST["update_birthday_year"]);
	    if ($_POST["update_nationality"] != "") update_user_meta($current_user->ID, 'user_nationality', $_POST["update_nationality"]);
	    if ($_POST["update_person_type"] != "") update_user_meta($current_user->ID, 'user_person_type', $_POST["update_person_type"]);
	    if ($_POST["update_email"] != "") wp_update_user( array ( 'ID' => $current_user->ID, 'user_email' => $_POST["update_email"] ) ) ;
	    if ($_POST["update_password"] != "" && $_POST["update_password"] == $_POST["update_password_confirm"]) wp_update_user( array ( 'ID' => $current_user->ID, 'user_pass' => $_POST["update_password"] ) );

	    if (session_id() == '') session_start();
	    if (isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "") {
		$page_invest = get_page_by_path('investir');
		$page_invest_link = get_permalink($page_invest->ID);
		$campaign_id_param = '?campaign_id=';
		$redirect_to = $page_invest_link . $campaign_id_param . $_SESSION['redirect_current_campaign_id'];
		unset($_SESSION['redirect_current_campaign_id']);
		wp_redirect($redirect_to);
		exit();
	    }
	}
    }
}

/**
 * Vérification si l'utilisateur a bien rempli toutes ses données
 */
function ypcf_check_user_can_invest() {
    global $post;
    $page_name = get_post($post)->post_name;
    if ($page_name == 'investir') {
	$current_user = wp_get_current_user();
	$can_invest = ($current_user->user_firstname != "") && ($current_user->user_lastname != "");
	$can_invest = $can_invest && ($current_user->get('user_birthday_day') != "") && ($current_user->get('user_birthday_month') != "") && ($current_user->get('user_birthday_year') != "");
	$can_invest = $can_invest && ypcf_is_major($current_user->get('user_birthday_day'), $current_user->get('user_birthday_month'), $current_user->get('user_birthday_year'));
	$can_invest = $can_invest && ($current_user->get('user_nationality') != "") && ($current_user->get('user_person_type') != "") && ($current_user->user_email != "");
	
	if (!$can_invest) {
	    if (session_id() == '') session_start();
	    $_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
	    $page_update = get_page_by_path('modifier-mon-compte');
	    wp_redirect(get_permalink($page_update->ID));
	}
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
				    "Nationality" : "'.$current_user->get('user_nationality').'", 
				    "Birthday" : '.strtotime($current_user->get('user_birthday_year') . "-" . $current_user->get('user_birthday_month') . "-" . $current_user->get('user_birthday_day')).', 
				    "PersonType" : "'.$current_user->get('user_person_type').'",
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