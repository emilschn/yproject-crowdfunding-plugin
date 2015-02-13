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
		global $edd_options;
		$page_invest = get_page_by_path('investir');
		$page_invest_link = get_permalink($page_invest->ID);
		$page_invest_link .= '?campaign_id=' . $_GET['campaign_id'];
		$form .= ypcf_print_invest_breadcrumb(1);
		$form .= '<div class="invest_step1_generalities">' . wpautop( $edd_options['investment_generalities'] ) . '</div>';
		$form .= '<div class="invest_step1_currentproject">' . html_entity_decode($campaign->investment_terms()) . '</div>';
		$form .= '<form id="invest_form" action="'.$page_invest_link.'" method="post" enctype="multipart/form-data">';
		$form .= '<input type="hidden" id="input_invest_min_value" name="old_min_value" value="' . $min_value . '">';
		$form .= '<input type="hidden" id="input_invest_max_value" name="old_max_value" value="' . $max_value . '">';
		$form .= '<input type="hidden" id="input_invest_part_value" name="part_value" value="' . $part_value . '">';
		$form .= '<input type="hidden" id="input_invest_max_part_value" name="part_value" value="' . $max_part_value . '">';
		$form .= '<input type="hidden" id="input_invest_amount_total" value="' . ypcf_get_current_amount() . '">';
		switch ($campaign->funding_type()) {
		    case 'fundingproject':
			$form .= '<span style="color:#FFFFFF;">(<span id="input_invest_amount">0</span> &euro;)</span><br />';
			$form .= '<input type="text" id="input_invest_amount_part" name="amount_part" placeholder="1"> &euro; ';
			break;
		    
		    case 'fundingdevelopment':
		    default:
			$form .= '<input type="text" id="input_invest_amount_part" name="amount_part" placeholder="1"> parts &agrave; '.$part_value.'&euro; soit <span id="input_invest_amount">0</span>&euro;';
			break;
		}
		$form .= '&nbsp;&nbsp;<a href="javascript:void(0);" id="link_validate_invest_amount" class="button">Valider</a><br /><br />';
		
		$form .= '<div id="validate_invest_amount_feedback" style="display:none;">';
		$hidden = ' hidden';
		$temp_min_part = ceil($min_value / $part_value);
		switch ($campaign->funding_type()) {
		    case 'fundingproject':
			$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Vous devez investir au moins '.$temp_min_part.' &euro;.</span>';
			$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Vous ne pouvez pas investir plus de '.$max_part_value.' &euro;.</span>';
			break;
		    
		    case 'fundingdevelopment':
		    default:
			$temp_min_plural = ($temp_min_part > 1) ? 's' : '';
			$temp_max_plural = ($max_part_value > 1) ? 's' : '';
			$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Vous devez prendre au moins '.$temp_min_part.' part'.$temp_min_plural.'.</span>';
			$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Vous ne pouvez pas prendre plus de '.$max_part_value.' part'.$temp_max_plural.'.</span>';
			break;
		}
		$form .= '<span class="invest_error'. (($error != "interval") ? $hidden : "") .'" id="invest_error_interval">Merci de ne pas laisser moins de ' . $min_value . '&euro; &agrave; investir.</span>';
		$form .= '<span class="invest_error'. (($error != "integer") ? $hidden : "") .'" id="invest_error_integer">Le montant que vous pouvez investir doit &ecirc;tre entier.</span>';
		$form .= '<span class="invest_error'. (($error != "general") ? $hidden : "") .'" id="invest_error_general">Le montant saisi semble comporter une erreur.</span>';
		$form .= '<span class="invest_success hidden" id="invest_success_message" class="button">Gr&acirc;ce à vous, nous serons ' . (ypcf_get_backers() + 1) . ' &agrave; soutenir le projet. La somme atteinte sera de <span id="invest_success_amount"></span>&euro;.</span>';
		
		$form .= '<div class="invest_step1_conditions">' . wpautop( $edd_options['contract'] ) . '</div>';
		
		$form .= '<br /><center>';
		$form .= '<input type="submit" value="Investir" class="button" />';
		$form .= '<select name="invest_type">';
		$form .= '<option value="user">En mon nom (personne physique)</option>';
		$current_user = wp_get_current_user();
		$api_user_id = BoppLibHelpers::get_api_user_id($current_user->ID);
		$organisations_list = BoppUsers::get_organisations_by_role($api_user_id, BoppLibHelpers::$organisation_creator_role['slug']);
		if (count($organisations_list) > 0) {
		    foreach ($organisations_list as $organisation_item) {
			$form .= '<option value="'.$organisation_item->organisation_wpref.'">Pour '.$organisation_item->organisation_name.'</option>';
		    }
		    $form .= '<option value="new_organisation">Pour une nouvelle organisation (personne morale)...</option>';
		} else {
		    $form .= '<option value="new_organisation">Pour une organisation (personne morale)...</option>';
		}
		$form .= '</select>';
		$form .= '</center>';
		
		$form .= '</div>';
		
		$form .= '</form><br /><br />';
		$form .= '<div style="text-align: center;"><img src="'.get_stylesheet_directory_uri() . '/images/powered_by_mangopay.png" alt="Bandeau Mangopay" /></div>';
	    } else {
		$form = 'Il n&apos;est plus possible d&apos;investir sur ce <a href="'.get_permalink($campaign->ID).'">projet</a> !';
	    }
	}
    }
    
    return $form;
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
?>