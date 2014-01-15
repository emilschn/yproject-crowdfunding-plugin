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
		$form .= '<input type="text" id="input_invest_amount_part" name="amount_part" placeholder="1"> parts &agrave; '.$part_value.'&euro; soit <span id="input_invest_amount">0</span>&euro;';
		$form .= '&nbsp;&nbsp;<a href="javascript:void(0);" id="link_validate_invest_amount">Valider</a>';
		
		$form .= '<div id="validate_invest_amount_feedback" style="display:none;">';
		$hidden = ' hidden';
		$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Vous devez prendre au moins '.(ceil($min_value / $part_value)).' part.</span>';
		$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Vous ne pouvez pas prendre plus de '.$max_part_value.' parts.</span>';
		$form .= '<span class="invest_error'. (($error != "interval") ? $hidden : "") .'" id="invest_error_interval">Merci de ne pas laisser moins de ' . $min_value . '&euro; &agrave; investir.</span>';
		$form .= '<span class="invest_error'. (($error != "integer") ? $hidden : "") .'" id="invest_error_integer">Le montant que vous pouvez investir doit &ecirc;tre entier.</span>';
		$form .= '<span class="invest_error'. (($error != "general") ? $hidden : "") .'" id="invest_error_general">Le montant saisi semble comporter une erreur.</span>';
		$form .= '<span class="invest_success hidden" id="invest_success_message">Gr&acirc;ce à vous, nous serons ' . (ypcf_get_backers() + 1) . ' &agrave; soutenir le projet. La somme atteinte sera de <span id="invest_success_amount"></span>&euro;.</span>';
		
		$form .= '<div class="invest_step1_conditions">' . wpautop( $edd_options['contract'] ) . '</div>';
		
		$current_user = wp_get_current_user();
		$group_ids = BP_Groups_Member::get_group_ids( $current_user->ID );
		$count_groups = 0;
		$groups = array();
		foreach ($group_ids['groups'] as $group_id) {
		    $group = groups_get_group( array( 'group_id' => $group_id ) );
		    $group_type = groups_get_groupmeta($group_id, 'group_type');
		    if ($group->status == 'private' && $group_type == 'organisation' && BP_Groups_Member::check_is_admin($current_user->ID, $group_id)) {
			$groups[$group_id] = $group;
			$count_groups++;
		    }
		}
		
		$form .= '<center>';
		$form .= '<input type="submit" value="Investir">';
		$form .= '<select name="invest_type">';
		$form .= '<option value="user">En mon nom</option>';
		if ($count_groups > 0) {
		    foreach ($groups as $group_key => $group) {
			$form .= '<option value="'.$group_key.'">Pour '.$group->name.'</option>';
		    }
		    $form .= '<option value="new_organisation">Pour une nouvelle organisation...</option>';
		    
		} else {
		    $form .= '<option value="new_organisation">Pour une organisation...</option>';
		}
		$form .= '</select>';
		$form .= '</center>';
		
		$form .= '</div>';
		
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