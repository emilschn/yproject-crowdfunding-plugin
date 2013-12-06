<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Formulaire de saisie d'investissement
 */
function ypcf_display_invest_form($error = '') {
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();
    $part_value = ypcf_get_part_value();
    $max_part_value = ypcf_get_max_part_value();
    
    $form = '';
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	if (isset($campaign)) {
	    if ($max_part_value > 0) {
		$form .= '<br />'.$campaign->investment_terms();
		$form .= '<br /><form id="invest_form" action="" method="post" enctype="multipart/form-data">';
		$form .= '<input id="input_invest_amount_part" name="amount_part" type="text" placeholder="1"> parts &agrave; '.$part_value.'&euro; soit <span id="input_invest_amount">0</span>&euro;';
		$form .= '<input id="input_invest_min_value" name="old_min_value" type="hidden" value="' . $min_value . '">';
		$form .= '<input id="input_invest_max_value" name="old_max_value" type="hidden" value="' . $max_value . '">';
		$form .= '<input id="input_invest_part_value" name="part_value" type="hidden" value="' . $part_value . '">';
		$form .= '<input id="input_invest_max_part_value" name="part_value" type="hidden" value="' . $max_part_value . '">';
		$form .= '<input id="input_invest_amount_total" type="hidden" value="' . ypcf_get_current_amount() . '">';
		$form .= '&nbsp;&nbsp;<input type="submit" value="Investir">&nbsp;&nbsp;';
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
		$form = 'Il n&apos;est plus possible d&apos;investir sur ce <a href="'.get_permalink($campaign->ID).'">projet</a> !';
	    }
	}
    }
    
    return $form;
}
?>