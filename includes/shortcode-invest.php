<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function ypcf_get_current_step() {
    if (session_id() == '') session_start();
    $buffer = 1;
    $max_part_value = ypcf_get_max_part_value();
    
    $amount_part = FALSE;
    $invest_type = FALSE;
    
    if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
    if (isset($_SESSION['redirect_current_amount_part'])) $amount_part = $_SESSION['redirect_current_amount_part'];
    if (isset($_POST['invest_type'])) $_SESSION['redirect_current_invest_type'] = $_POST['invest_type'];
    if (isset($_SESSION['redirect_current_invest_type'])) $invest_type = $_SESSION['redirect_current_invest_type'];
//    echo '$invest_type : ' . $invest_type . ' ; $amount_part : ' . $amount_part;
    
    if ($invest_type != FALSE && $amount_part !== FALSE && is_numeric($amount_part) && ctype_digit($amount_part) 
	    && intval($amount_part) == $amount_part && $amount_part >= 1 && $amount_part <= $max_part_value ) {
	 $buffer = 2;
    }
    
    return $buffer;
}

/**
 * Premier formulaire qui permet de remplir la somme que l'on veut investir
 */
 function ypcf_shortcode_invest_form($atts, $content = '') {
    $form = '';
    
    if (ypcf_get_current_step() == 1) $form .= ypcf_display_invest_form($content);

    return $form;
}
add_shortcode( 'yproject_crowdfunding_invest_form', 'ypcf_shortcode_invest_form' );

/**
 * Deuxième étape : après saisie de la somme à investir
 * Vérification que la somme correspond bien
 */
 function ypcf_shortcode_invest_confirm($atts, $content = '') {
    $form = '';
    
    if (ypcf_get_current_step() == 2) $form .= ypcf_display_invest_confirm($content);
    
    return $form;
 }
add_shortcode( 'yproject_crowdfunding_invest_confirm', 'ypcf_shortcode_invest_confirm' );

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
?>