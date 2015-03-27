<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Dernière étape : le paiement a été effectué, on revient sur le site
 */
function ypcf_shortcode_invest_return($atts, $content = '') {
    $buffer = '';
    ypcf_session_start();
    ypcf_check_is_project_investable();
    if (isset($_REQUEST["ContributionID"]) && isset($_GET['campaign_id']) && is_user_logged_in()) {
	
	if (isset($_GET['meanofpayment']) && $_GET['meanofpayment'] == 'wire') $mangopay_contribution = ypcf_mangopay_get_withdrawalcontribution_by_id($_REQUEST["ContributionID"]);
	else $mangopay_contribution = ypcf_mangopay_get_contribution_by_id($_REQUEST["ContributionID"]);
	
	$purchase_key = $_REQUEST["ContributionID"];
	if (isset($_GET['meanofpayment']) && $_GET['meanofpayment'] == 'wire') $purchase_key = 'wire_' . $purchase_key;

	$page_investments = get_page_by_path('mes-investissements');
	$paymentlist = edd_get_payments();
	foreach ($paymentlist as $payment) {
	    if (edd_get_payment_key($payment->ID) == $purchase_key) {
		$buffer .= 'Le paiement a déjà été pris en compte. Merci de vous rendre sur la page <a href="'.get_permalink($page_investments->ID).'">Mes investissements</a>.';
		break;
	    }
	}

	if ($buffer == '') {
	    // GESTION DU PAIEMENT COTE EDD
	    //On met à jour l'état de la campagne
	    $post = get_post($_GET['campaign_id']);
	    $campaign = atcf_get_campaign( $post );

	    //Récupération du bon utilisateur
	    $current_user = wp_get_current_user();
	    $save_user_id = $current_user->ID;
	    $save_display_name = $current_user->display_name;
	    if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] != "user") {
		$invest_type = $_SESSION['redirect_current_invest_type'];
		$organisation = new YPOrganisation($invest_type);
		if ($organisation) {
		    $current_user_organisation = $organisation->get_creator();
		    ypcf_init_mangopay_user($current_user_organisation, true);
		    $save_user_id = $current_user_organisation->ID;
		    $save_display_name = $organisation->get_name();
		}
	    }

	    //Création d'un paiement pour edd
	    $user_info = array(
		'id'		=> $save_user_id,
		'gender'	=> $current_user->get('user_gender'),
		'email'		=> $current_user->user_email,
		'first_name'	=> $current_user->user_firstname,
		'last_name'	=> $current_user->user_lastname,
		'discount'	=> '',
		'address'	=> array()
	    );

	    if (isset($_GET['meanofpayment']) && $_GET['meanofpayment'] == 'wire') $amount = $mangopay_contribution->AmountDeclared / 100;
	    else $amount = $mangopay_contribution->Amount / 100;

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
		    'purchase_key' => $purchase_key,
		    'currency' => edd_get_currency(),
		    'downloads' => array($campaign->ID),
		    'user_info' => $user_info,
		    'cart_details' => $cart_details,
		    'status' => 'pending'
	    );
	    $payment_id = edd_insert_payment( $payment_data );
	    update_post_meta( $payment_id, '_edd_payment_ip', $_SERVER['REMOTE_ADDR'] );

	    edd_record_sale_in_log($campaign->ID, $payment_id);
	    // FIN GESTION DU PAIEMENT COTE EDD

	    // Vérifie le statut du paiement, envoie un mail de confirmation et crée un contrat si on est ok
	    $payment_status = ypcf_get_updated_payment_status($payment_id);

	    // Affichage en fonction du statut du paiement
	    switch ($payment_status) {
		case 'pending' :
		    $buffer .= ypcf_print_invest_breadcrumb(4);
		    if (isset($_GET['meanofpayment']) && $_GET['meanofpayment'] == 'wire') {
			    $buffer .= 'Nous attendons votre virement.<br /><br />';
			    if ($amount > 1500) {
				    $buffer .= 'Une fois validé, vous recevrez deux e-mails :<br /><br />';
				    $buffer .= '- un e-mail envoyé par WEDOGOOD pour la confirmation de votre paiement. Cet e-mail contient votre code pour signer le pouvoir<br /><br />';
				    $buffer .= '- un e-mail envoyé par notre partenaire Signsquid. Cet e-mail contient un lien vous permettant de signer le pouvoir pour le contrat d&apos;investissement<br /><br />'; 
			    } else {
				    $buffer .= 'Une fois validé, vous recevrez un e-mail confirmant votre paiement. Votre contrat d&apos;investissement sera joint &agrave; cet e-mail.<br /><br />';
			    }
		    } else {
			    $buffer .= 'Transaction en cours.<br />';
		    }
		    $invest_page = get_page_by_path('mes_investissements');
		    $buffer .= 'Merci de vous rendre sur la page de <a href="'. get_permalink($invest_page->ID) .'">vos investissements</a> pour suivre l&apos;&eacute;volution de votre paiement.<br /><br />';
		    $share_page = get_page_by_path('paiement-partager');
		    $buffer .= '<center><a class="button" href="'. get_permalink($share_page->ID) .'?campaign_id='.$_GET['campaign_id'].'">Suivant</a></center><br /><br />';
		    break;

		case 'publish' :
		    do_action('wdg_delete_cache', array(
						    'project-header-right-'.$campaign->ID,
						    'projects-current'
						    ));
		    
		    //On affiche que tout s'est bien passé
		    $buffer .= ypcf_print_invest_breadcrumb(4);
		    $buffer .= $content;
		    $campaign_url  = get_permalink($_GET['campaign_id']);
		    $share_page = get_page_by_path('paiement-partager');

		    if ($amount > 1500) {
			global $contract_errors, $wpdb;
			if (!isset($contract_errors) || $contract_errors == '') {
			    $buffer .= 'Vous allez recevoir deux e-mails cons&eacute;cutifs &agrave; l&apos;adresse '.$current_user->user_email.' (pensez &agrave; v&eacute;rifier votre dossier de courrier ind&eacute;sirable) :<br /><br />';
			    $buffer .= '- un e-mail envoyé par WEDOGOOD pour la confirmation de votre paiement. Cet e-mail contient votre code pour signer le pouvoir<br /><br />';
			    $buffer .= '- un e-mail envoyé par notre partenaire Signsquid. Cet e-mail contient un lien vous permettant de signer le pouvoir pour le contrat d&apos;investissement<br /><br />'; 
			    $buffer .= '<center><img src="'. get_stylesheet_directory_uri() .'/images/signsquid.png" width="168" height="64" /></center><br />';
			    if (ypcf_check_user_phone_format($current_user->get('user_mobile_phone'))) {
				$buffer .= 'Vous allez aussi recevoir un sms contenant le code au num&eacute;ro que vous nous avez indiqu&eacute; : '.$current_user->get('user_mobile_phone').'<br /><br />'; 
			    }

			} else {
			    ypcf_debug_log("ypcf_shortcode_invest_return --- ERROR :: contract :: ".$contract_errors);
			    $buffer .= 'Vous allez recevoir un e-mail de confirmation de paiement.<br />';
			    $buffer .= '<span class="errors">Cependant, il y a eu un problème lors de la génération du contrat. Nos &eacute;quipes travaillent &agrave; la r&eacute;solution de ce probl&egrave;me.</span><br /><br />';
			}
		    } else {
			    $buffer .= 'Vous allez recevoir un e-mail &agrave; l&apos;adresse '.$current_user->user_email.' (pensez &agrave; v&eacute;rifier votre dossier de courrier ind&eacute;sirable).';
			    $buffer .= 'Votre contrat d&apos;investissement sera joint &agrave; cet e-mail.<br /><br />';
		    }
		    $buffer .= '<center><a class="button" href="'. get_permalink($share_page->ID) .'?campaign_id='.$_GET['campaign_id'].'">Suivant</a></center><br /><br />';

		    //Si un utilisateur investit, il croit au projet
		    $table_jcrois = $wpdb->prefix . "jycrois";
		    $users = $wpdb->get_results( "SELECT user_id FROM $table_jcrois WHERE campaign_id = ". $_GET['campaign_id']. " AND user_id = " .$current_user->ID );
		    $found_jcrois = false;
		    foreach ( $users as $user ) { 
			if ( $user->user_id == $current_user->ID) {
			    $found_jcrois = true;
			    break;
			}
		    }
		    if (!$found_jcrois) {
			$wpdb->insert( $table_jcrois,
			    array(
				'user_id'	=> $current_user->ID,
				'campaign_id'   => $_GET['campaign_id']
			    )
			);
		    }

		    // Construction des urls utilisés dans les liens du fil d'actualité
		    // url d'une campagne précisée par son nom 
		    $post_title = $post->post_title;
		    $url_campaign = '<a href="'.$campaign_url.'">'.$post_title.'</a>';
		    //url d'un utilisateur précis
		    $url_profile = '<a href="' . bp_core_get_userlink($save_user_id, false, true) . '">' . $save_display_name . '</a>';

		    bp_activity_add(array (
			'component' => 'profile',
			'type'      => 'invested',
			'action'    => $url_profile.' a investi sur le projet '.$url_campaign
		    ));
		    break;

		case 'failed' :
		    $buffer .= 'Il y a eu une erreur pendant la transacton : ' . $mangopay_contribution->AnswerMessage . ' (' . $mangopay_contribution->AnswerCode . ')';
		    break;
	    }

	    edd_empty_cart();
	    
	} else {
	    $buffer .= 'Il y a eu une erreur pendant la transacton.';
	}
	
    }
    
    return $buffer;
}
add_shortcode( 'yproject_crowdfunding_invest_return', 'ypcf_shortcode_invest_return' );
?>