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
    ypcf_check_user_can_invest(true);
    
    //Remise à zero des variables de sessions éventuelles
    global $post;
    if (isset($post)) {
	$page_name = get_post($post)->post_name;
	if ($page_name == 'investir') {
	    if (session_id() == '') session_start();
	    if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);

	    $post_camp = get_post($_GET['campaign_id']);
	    $campaign = atcf_get_campaign( $post_camp );
	    if ($campaign->vote() == 'vote' || $campaign->part_value() == 0) {
		wp_redirect(get_permalink($post_camp->ID));
		exit();
	    }
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
    
    if (!isset($_POST['amount_part'])) $form .= ypcf_display_invest_form($content);

    return $form;
}
add_shortcode( 'yproject_crowdfunding_invest_form', 'ypcf_shortcode_invest_form' );

/**
 * Formulaire de saisie d'investissement
 */
function ypcf_display_invest_form($error = '') {
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();
    $part_value = ypcf_get_part_value();
    $max_part_value = ypcf_get_max_part_value();
    
    if (isset($_GET['campaign_id']) && $max_part_value > 0) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	$form = '';
	$form .= '<br />'.$campaign->investment_terms();
	$form .= '<br /><form id="invest_form" action="" method="post" enctype="multipart/form-data">';
	$form .= '<input id="input_invest_amount_part" name="amount_part" type="text" placeholder="1"> parts &agrave; '.$part_value.'&euro; soit <span id="input_invest_amount">0</span>&euro;';
	$form .= '<input id="input_invest_min_value" name="old_min_value" type="hidden" value="' . $min_value . '">';
	$form .= '<input id="input_invest_max_value" name="old_max_value" type="hidden" value="' . $max_value . '">';
	$form .= '<input id="input_invest_part_value" name="part_value" type="hidden" value="' . $part_value . '">';
	$form .= '<input id="input_invest_max_part_value" name="part_value" type="hidden" value="' . $max_part_value . '">';
	$form .= '<input id="input_invest_amount_total" type="hidden" value="' . ypcf_get_current_amount() . '">';
	$form .= '<input type="submit">';
	$hidden = ' hidden';
	$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Vous devez prendre au moins une part.</span>';
	$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Vous ne pouvez pas prendre plus de '.$max_part_value.' parts.</span>';
	$form .= '<span class="invest_error'. (($error != "interval") ? $hidden : "") .'" id="invest_error_interval">Merci de ne pas laisser moins de ' . $min_value . edd_get_currency() . ' &agrave; investir.</span>';
	$form .= '<span class="invest_error'. (($error != "integer") ? $hidden : "") .'" id="invest_error_integer">Le montant que vous pouvez investir doit &ecirc;tre entier.</span>';
	$form .= '<span class="invest_error'. (($error != "general") ? $hidden : "") .'" id="invest_error_general">Le montant saisi semble comporter une erreur.</span>';
	$form .= '<span class="invest_success hidden" id="invest_success_message">Gr&acirc;ce à vous, nous serons ' . (ypcf_get_backers() + 1) . ' &agrave; soutenir le projet. La somme atteinte sera de <span id="invest_success_amount"></span>'.edd_get_currency().'.</span>';
	$form .= '</form><br /><br />';
	$form .= '<center><img src="'.get_stylesheet_directory_uri() . '/images/powered_by_mangopay.png" /></center>';
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
    $part_value = ypcf_get_part_value();
    $max_part_value = ypcf_get_max_part_value();

    if (isset($_GET['campaign_id']) && isset($_POST['amount_part']) &&  $max_part_value > 0) {
	//Si la valeur peut être ponctionnée sur l'objectif, et si c'est bien du numérique supérieur à 0
	$amount = $_POST['amount_part'] * $part_value;
	$amount_interval = $max_value - $amount;
	if (is_numeric($_POST['amount_part']) && intval($_POST['amount_part']) == $_POST['amount_part'] && $_POST['amount_part'] >= 1 && $_POST['amount_part'] <= $max_part_value && ($amount_interval == 0 || $amount_interval >= $min_value)) {

	    $current_user = wp_get_current_user();
	    ypcf_init_mangopay_user($current_user);
	    
	    if (isset($_POST['document_submited'])) ypcf_mangopay_set_user_strong_authentication_doc_transmitted($current_user->ID);
	    
	    //Si le montant transmis est supérieur à ce que mangopay accepte sans identification
	    if ($amount > YP_STRONGAUTH_AMOUNT_LIMIT && !ypcf_mangopay_is_user_strong_authenticated($current_user->ID)) {
		$url_request = ypcf_init_mangopay_user_strongauthentification($current_user);
		$post = get_post($_GET['campaign_id']);
		$campaign = atcf_get_campaign( $post );
		$form .= '<br />'.$campaign->investment_terms();
		$form .= '<br />Pour investir une somme sup&eacute;rieure &agrave; '.YP_STRONGAUTH_AMOUNT_LIMIT.'&euro;, vous devez fournir une pi&egrave;ce d&apos;identit&eacute;.<br />';
		$form .= 'Le fichier doit &ecirc;tre de type jpeg, gif, png ou pdf.<br />';
		$form .= 'Son poids doit &ecirc;tre inf&eacute;rieur &agrave; 2 Mo.<br />';
		$form .= '<form id="mangopay_strongauth_form_confirm" action="" method="post" enctype="multipart/form-data" style="display: none;">';
		$form .= '<input type="hidden" name="document_submited" value="1" />';
		$form .= '<input type="hidden" name="amount_part" value='.$_POST['amount_part'].' />';
		$form .= 'Merci de valider votre envoi en cliquant sur ';
		$form .= '<input type="submit" value="Valider"/>';
		$form .= '</form>';
		$form .= '<form id="mangopay_strongauth_form" action="'.$url_request.'" method="post" enctype="multipart/form-data">';
		$form .= '<input type="file" name="StrongValidationDto.Picture" />';
		$form .= '<input type="submit" value="Envoyer"/>';
		$form .= '</form><br /><br />';
		
	    } else {
		ypcf_init_mangopay_project();

		//Procédure modifiée d'ajout au panier (on ajoute x items de 1 euros => le montant se retrouve en tant que quantité)
		$post = get_post($_GET['campaign_id']);
		$campaign = atcf_get_campaign( $post );
		edd_empty_cart();
		$to_add = array();
		$to_add[] = apply_filters( 'edd_add_to_cart_item', array( 'id' => $campaign->ID, 'options' => array(), 'quantity' => $amount ) );
		EDD()->session->set( 'edd_cart', $to_add );

		$form .= '<br />'.$campaign->investment_terms();
		$form .= '<br />'.$content;

		// Rappel des informations remplies
		require_once('country_list.php');
		global $country_list;
		if (session_id() == '') session_start();
		$_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
		$form .= '<br /><br />Rappel de vos informations :<br />';
		$form .= 'Pr&eacute;nom : ' . $current_user->user_firstname . '<br />';
		$form .= 'Nom : ' . $current_user->user_lastname . '<br />';
		$form .= 'e-mail : ' . $current_user->user_email . '<br />';
		$form .= 'Type de personne : ' . (($current_user->get('user_person_type') == 'NATURAL_PERSON') ? "physique" : "morale") . '<br />';
		$form .= 'Nationalit&eacute; : ' . $country_list[$current_user->get('user_nationality')] . '<br />';
		$form .= 'Date de naissance : ' . $current_user->get('user_birthday_day') . '/' . $current_user->get('user_birthday_month') . '/' . $current_user->get('user_birthday_year') . '<br />';
		$page_update = get_page_by_path('modifier-mon-compte');
		$form .= '<a href="' . get_permalink($page_update->ID) . '">Modifier ces informations</a><br /><br />';

		// Formulaire de confirmation
		$form .= '<form action="" method="post" enctype="multipart/form-data">';
		$form .= '<input name="amount_part" type="hidden" value="' . $_POST['amount_part'] . '">';
		$form .= '<input name="confirmed" type="hidden" value="1">';
		ob_start();
		edd_agree_to_terms_js();
		ypcf_terms_agreement();
		$form .= ob_get_clean();
		$form .= $_POST['amount_part'] . ' part soit '.$amount.'&euro;<input type="submit">';
		$form .= '</form><br /><br />';
		$form .= '<center><img src="'.get_stylesheet_directory_uri() . '/images/powered_by_mangopay.png" /></center>';
	    }
	    
	} else {
	    $error = 'general';
	    if (intval($_POST['amount_part']) != $_POST['amount_part']) $error = 'integer';
	    if ($_POST['amount_part'] < 1) $error = 'min';
	    if ($amount > $max_value) $error = 'max';
	    if ($amount_interval > 0 && $amount_interval < $min_value) $error = 'interval';
	    unset($_POST['amount_part']);
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
    $mangopay_contribution = ypcf_mangopay_get_contribution_by_id($_REQUEST["ContributionID"]);
    
    // GESTION DU PAIEMENT COTE EDD
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
    
    $amount = $mangopay_contribution->Amount / 100;

    $cart_details = array(
	array(
		'name'        => get_the_title( $campaign->ID ),
		'id'          => $campaign->ID,
		'item_number' => array(
			'id'      => $campaign->ID,
			'options' => array()
		),
		'price'       => 1,
		'quantity'    => $amount
	)
    );

    $payment_data = array( 
	    'price' => $amount, 
	    'date' => date('Y-m-d H:i:s'), 
	    'user_email' => $current_user->user_email,
	    'purchase_key' => $_REQUEST["ContributionID"],
	    'currency' => edd_get_currency(),
	    'downloads' => array($campaign->ID),
	    'user_info' => $user_info,
	    'cart_details' => $cart_details,
	    'status' => 'pending'
    );
    $payment_id = edd_insert_payment( $payment_data );
    
    edd_record_sale_in_log($campaign->ID, $payment_id);
    // FIN GESTION DU PAIEMENT COTE EDD
    
    $payment_status = ypcf_get_updated_payment_status($payment_id);
    switch ($payment_status) {
	case 'pending' :
	    $buffer .= 'Transaction en cours';
	    break;
	case 'publish' :
	    //On affiche que tout s'est bien passé
	    $buffer .= $content;
	    $buffer .= 'Merci pour votre investissement de ' . $amount . edd_get_currency() . '.<br />';
	    $buffer .= 'Nous sommes &agrave; pr&eacute;sent ' . ypcf_get_backers() . ' &agrave; soutenir le projet.<br />';
	    $buffer .= 'La somme atteinte est de ' . ypcf_get_current_amount() . edd_get_currency() . '.<br />';
	    $buffer .= 'Vous allez recevoir un mail de confirmation d&apos;achat (pensez &agrave; v&eacute;rifier votre dossier de courrier indésirable).<br /><br/>';
	    //$buffer .= 'Retourner &agrave; la <a href="'.get_permalink($_GET['campaign_id']).'">page projet</a>.<br /><br />';
	    $buffer .='<strong>Il ne vous reste plus qu\'à signer</strong>  '.do_shortcode('[yproject_crowdfunding_signs]').'</br>'; 
	    //Liens pour partager
	    $buffer .= '<iframe src="http://www.facebook.com/plugins/like.php?href='.urlencode(get_permalink($_GET['campaign_id'])).'&amp;layout=button_count&amp;show_faces=true&amp;width=450&amp;action=like&amp;colorscheme=light&amp;height=30" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:80px; height:20px; text-align: center" allowTransparency="true"></iframe>';
	    $buffer .= '<a href="http://www.facebook.com/sharer.php?u='.urlencode(get_permalink($_GET['campaign_id'])).'" target="_blank">'. __('Partager sur Facebook', 'yproject') . '</a>';
	    $buffer .= '<br />';
	    /*$buffer .= "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";
	    $buffer .= '<a href="https://twitter.com/share" class="twitter-share-button" data-via="yproject_co" data-lang="fr">' . __('Partager sur Twitter', 'yproject') . '</a>';
	    $buffer .= '<br />';*/
	    break;
	case 'failed' :
	    $buffer .= 'Il y a eu une erreur pendant la transacton : ' . $mangopay_contribution->AnswerMessage . ' (' . $mangopay_contribution->AnswerCode . ')';
	    break;
    }
    
    edd_empty_cart();
    
    return $buffer;
}
add_shortcode( 'yproject_crowdfunding_invest_return', 'ypcf_shortcode_invest_return' );

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
			    if ($buffer !== $init_payment_status) edd_email_purchase_receipt($payment_id, true);
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
 * Vérification si l'utilisateur est bien connecté
 * Si il ne l'est pas, on redirige vers la page de connexion
 * ATTENTION : en utilisant ça dans un plugin, la fonction est appelée sur toutes les pages du site. Peut-être plus optimisé dans la template
 */
function ypcf_check_is_user_logged() {
    global $post;
    if (isset($post)) {
	$page_name = get_post($post)->post_name;
	if (session_id() == '') session_start();

	if ($page_name == 'investir' && !is_user_logged_in()) {
	    if (isset($_GET['campaign_id'])) {
		$_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];
		$page_connexion = get_page_by_path('connexion');
		wp_redirect(get_permalink($page_connexion->ID));
	    } else {
		wp_redirect(site_url());
	    }
	    exit();
	} else if ($page_name == 'connexion' && is_user_logged_in()) {
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
 * Enregistre les données saisies par l'utilisateur et redirige vers la page d'investissement si nécessaire
 */
function ypcf_check_has_user_filled_infos_and_redirect() {
    global $post, $validate_email;
    if (isset($post)) {
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
}

/**
 * Vérification si l'utilisateur a bien rempli toutes ses données
 */
function ypcf_check_user_can_invest($redirect = false) {
    global $post;
    $can_invest = true;
    if (isset($post)) {
	$page_name = get_post($post)->post_name;
	if ($page_name == 'investir') {
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
	}
    }
    return $can_invest;
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

/*
 * Ajout aux réglages d'edd
 */
function ypcf_register_settings() {
    add_settings_field(
	'edd_settings_misc[contract_label]',
	'Libelles du contrat d&apos;investissement',
	function_exists( 'edd_text_callback' ) ? 'edd_text_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'contract_label',
	    'desc' => '',
	    'name' => 'contract_label',
	    'section' => 'misc',
	    'size' => 'regular' ,
	    'options' => '',
	    'std' => ''
	)
    );
    
    add_settings_field(
	'edd_settings_misc[contract]',
	'Contrat d&apos;investissement',
	function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'contract',
	    'desc' => '',
	    'name' => 'contract',
	    'section' => 'misc',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
}

add_action('admin_init', 'ypcf_register_settings', 11);


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

// Fonction de signature du contrat
 function ypcf_contract_sign() {
 	 if (isset( $_POST['action'])=='signer') {
         // appel signsquid

        header('Authorization: Basic MT9M49EHieWFAnaL7gcqBLKmTuNOz2HT');
       $test = getNewPdfToSign($post_camp->ID); //DEBUG

    }
 }
?>