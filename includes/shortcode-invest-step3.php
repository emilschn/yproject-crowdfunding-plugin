<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Dernière étape : le paiement a été effectué, on revient sur le site
 */
function ypcf_shortcode_invest_return($atts, $content = '') {
    $buffer = '';
    if (session_id() == '') session_start();
    unset($_SESSION['redirect_current_campaign_id']); // Suppression de la demande de redirection automatique
    $mangopay_contribution = ypcf_mangopay_get_contribution_by_id($_REQUEST["ContributionID"]);
    
    $page_investments = get_page_by_path('mes-investissements');
    $paymentlist = edd_get_payments();
    foreach ($paymentlist as $payment) {
	if (edd_get_payment_key($payment->ID) == $_REQUEST["ContributionID"]) {
	    $buffer .= 'Le paiement a déjà été pris en compte. Merci de vous rendre sur la page <a href="'.get_permalink($page_investments->ID).'">Mes investissements</a>.';
	    break;
	}
    }
    
    if ($buffer == '') {
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
		    'id'	    => $campaign->ID,
		    'options'   => array()
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

	// Vérifie le statut du paiement, envoie un mail de confirmation et crée un contrat si on est ok
	$payment_status = ypcf_get_updated_payment_status($payment_id);
	// Affichage en fonction du statut du paiement
	switch ($payment_status) {
	    case 'pending' :
		$buffer .= 'Transaction en cours.<br />';
		$invest_page = get_page_by_path('mes_investissements');
		$buffer .= 'Merci de vous rendre sur la page de <a href="'. get_permalink($invest_page->ID) .'">vos investissements</a> pour suivre l&apos;&eacute;volution de votre paiement.<br />';
		break;

	    case 'publish' :
		//On affiche que tout s'est bien passé
		$buffer .= ypcf_print_invest_breadcrumb(4);
		$buffer .= $content;
		$buffer .= 'Merci pour votre investissement de ' . $amount . '&euro;.<br />';
		$buffer .= 'Nous sommes &agrave; pr&eacute;sent ' . ypcf_get_backers() . ' &agrave; soutenir le projet.<br />';
		$buffer .= 'La somme atteinte est de ' . ypcf_get_current_amount() . '&euro;.<br /><br />';

		global $contract_errors;
		if (!isset($contract_errors) || $contract_errors == '') {
		    $buffer .= 'Vous allez recevoir deux e-mails cons&eacute;cutifs &agrave; l&apos;adresse '.$current_user->user_email.' (pensez &agrave; v&eacute;rifier votre dossier de courrier ind&eacute;sirable) :<br />';
		    $buffer .= '- un e-mail de confirmation de paiement ; cet e-mail contient votre code pour signer le pouvoir<br />';
		    $buffer .= '- un e-mail qui contient un lien vous permettant de signer le pouvoir pour le contrat d&apos;investissement<br /><br />'; 
		    if (ypcf_check_user_phone_format($current_user->get('user_mobile_phone'))) {
			$buffer .= 'Vous devriez aussi recevoir un sms contenant le code au num&eacute;ro que vous nous avez indiqu&eacute; : '.$current_user->get('user_mobile_phone').'<br /><br />'; 
		    }

		    //Liens pour partager
    //		$buffer .= '<iframe src="http://www.facebook.com/plugins/like.php?href='.urlencode(get_permalink($_GET['campaign_id'])).'&amp;layout=button_count&amp;show_faces=true&amp;width=450&amp;action=like&amp;colorscheme=light&amp;height=30" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:80px; height:20px; text-align: center" allowTransparency="true"></iframe>';
		    $buffer .= '<a href="http://www.facebook.com/sharer.php?u='.urlencode(get_permalink($_GET['campaign_id'])).'" target="_blank">'. __('Partager sur Facebook', 'yproject') . '</a>';
		    $buffer .= '<br />';
    //		$buffer .= "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";
    //		$buffer .= '<a href="https://twitter.com/share" class="twitter-share-button" data-via="yproject_co" data-lang="fr">' . __('Partager sur Twitter', 'yproject') . '</a>';
    //		$buffer .= '<br />';
		} else {
		    ypcf_debug_log("ypcf_shortcode_invest_return --- ERROR :: contract :: ".$contract_errors);
		    $buffer .= 'Vous allez recevoir un e-mail de confirmation de paiement.<br />';
		    $buffer .= '<span class="errors">Cependant, il y a eu un problème lors de la génération du contrat. Nos &eacute;quipes travaillent &agrave; la r&eacute;solution de ce probl&egrave;me.</span>';
		}

		break;

	    case 'failed' :
		$buffer .= 'Il y a eu une erreur pendant la transacton : ' . $mangopay_contribution->AnswerMessage . ' (' . $mangopay_contribution->AnswerCode . ')';
		break;
	}

	edd_empty_cart();
    }
    
    return $buffer;
}
add_shortcode( 'yproject_crowdfunding_invest_return', 'ypcf_shortcode_invest_return' );
?>