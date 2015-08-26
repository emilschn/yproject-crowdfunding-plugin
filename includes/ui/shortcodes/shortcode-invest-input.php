<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Formulaire de saisie d'investissement
 */
function ypcf_display_invest_form($error = '') {
    $campaign = atcf_get_current_campaign();
    $form = '<a name="invest-start"></a>';
    
    if (isset($campaign)) {
	    $min_value = ypcf_get_min_value_to_invest();
	    $max_value = ypcf_get_max_value_to_invest();
	    $part_value = ypcf_get_part_value();
	    $max_part_value = ypcf_get_max_part_value();
    
	    if ($max_part_value > 0) {
		global $edd_options;
		$page_invest = get_page_by_path('investir');
		$page_invest_link = get_permalink($page_invest->ID);
		$page_invest_link .= '?campaign_id=' . $_GET['campaign_id'];
		$form .= ypcf_print_invest_breadcrumb(1, $campaign->funding_type());
		
		$form .= '<div class="invest_step1_generalities">';
		switch ($campaign->funding_type()) {
		    case "fundingdonation":
			$form .= wpautop( $edd_options['donation_generalities'] );
			break;
		    default:
			$form .= wpautop( $edd_options['investment_generalities'] );
			break;
		}
		$form .= '</div>';
		
		$form .= '<div class="invest_step1_currentproject">' . html_entity_decode($campaign->investment_terms()) . '</div>';
		$form .= '<form id="invest_form" action="'.$page_invest_link.'" method="post" enctype="multipart/form-data">';
		$form .= '<input type="hidden" id="input_invest_min_value" name="old_min_value" value="' . $min_value . '">';
		$form .= '<input type="hidden" id="input_invest_max_value" name="old_max_value" value="' . $max_value . '">';
		$form .= '<input type="hidden" id="input_invest_part_value" name="part_value" value="' . $part_value . '">';
		$form .= '<input type="hidden" id="input_invest_max_part_value" name="part_value" value="' . $max_part_value . '">';
		$form .= '<input type="hidden" id="input_invest_amount_total" value="' . ypcf_get_current_amount() . '">';
		switch ($campaign->funding_type()) {
		    case 'fundingdonation':
			$form .= '<span style="display:none;">(<span id="input_invest_amount">0</span> &euro;)</span><br />';
                        $rewards = atcf_get_rewards($campaign->ID);

			if (isset($rewards->rewards_list)) {
                            $form .= '<p>Choisissez votre contrepartie :</p>';
			    $form .= '<ul id="reward-selector">';
			    $form .= '<label><li><input type="radio" name="selected_reward" data-amount="0" value="-1" checked="checked"> Je ne souhaite <span class="reward-name">pas de contrepartie</span>.</li></label>';
                        
			    foreach ($rewards->rewards_list as $reward) {
				$form .= '<label><li';
				if(!$rewards->is_available_reward($reward['id'])){
				    $form .= ' class="unavailable-reward"';
				}
				$form .= '>';

				$form .= '<div><input type="radio" name="selected_reward" value="'.$reward['id'].'"';
				if(!$rewards->is_available_reward($reward['id'])){
				    $form .= 'disabled="disabled"';
				}
				$form .= '>';

				$form .= '<span class="reward-amount">'.intval($reward['amount']).'</span>&euro; ou plus </div> '
					.'<div class="reward-name reward-not-null">'.$reward['name'].'</div>';

				if($rewards->is_limited_reward($reward['id'])){
				    $remaining = (intval($reward['limit'])-intval($reward['bought'])); 
				    $form .= '<div><span class="detail">Contrepartie limit&eacute;e : </span>'
					    .'<span class="reward-remaining">'. $remaining.'</span>'
					    . ' restant';
				    if($remaining>1){ $form .='s'; }
				    $form .=' sur '
					    .intval($reward['limit']).'</div>';
				}

				$form .= '</li></label>';
			    }
			    $form .= '</ul>';
                            $form .= 'Je souhaite donner <input type="text" id="input_invest_amount_part" name="amount_part" placeholder="'.$min_value.'"> &euro; <br />';
			}
                        
			break;
		    case 'fundingproject':
		    case 'fundingdevelopment':
			$form .= '<input type="text" id="input_invest_amount_part" name="amount_part" placeholder="1"> parts &agrave; '.$part_value.'&euro; soit <span id="input_invest_amount">0</span>&euro;<br>';
			break;
		}
		$form .= '&nbsp;&nbsp;<center><a href="javascript:void(0);" id="link_validate_invest_amount" class="button">Valider</a></center><br /><br />';
		
		$form .= '<div id="validate_invest_amount_feedback" style="display:none;">';
		$hidden = ' hidden';
		$temp_min_part = ceil($min_value / $part_value);
		switch ($campaign->funding_type()) {
		    case 'fundingproject':
			$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Vous devez investir au moins '.$temp_min_part.' &euro;.</span>';
			$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Vous ne pouvez pas investir plus de '.$max_part_value.' &euro;.</span>';
			break;
		    
		    case 'fundingdonation':
			$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Le montant minimal de soutien est de '.$temp_min_part.' &euro;.</span>';
			$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Vous ne pouvez pas soutenir avec plus de '.$max_part_value.' &euro;.</span>';
			$form .= '<span class="invest_error'. (($error != "reward_remaining") ? $hidden : "") .'" id="invest_error_reward_remaining">La contrepartie que vous avez choisi n\'est plus disponible.</span>';
			$form .= '<span class="invest_error'. (($error != "reward_insufficient") ? $hidden : "") .'" id="invest_error_reward_insufficient">Vous devez donner plus pour obtenir cette contrepartie.</span>';                        
                        break;
		    
		    case 'fundingdevelopment':
		    default:
			$temp_min_plural = ($temp_min_part > 1) ? 's' : '';
			$temp_max_plural = ($max_part_value > 1) ? 's' : '';
			$form .= '<span class="invest_error'. (($error != "min") ? $hidden : "") .'" id="invest_error_min">Vous devez prendre au moins '.$temp_min_part.' part'.$temp_min_plural.'.</span>';
			$form .= '<span class="invest_error'. (($error != "max") ? $hidden : "") .'" id="invest_error_max">Vous ne pouvez pas prendre plus de '.$max_part_value.' part'.$temp_max_plural.'.</span>';
			break;
		}
		$form .= '<span class="invest_error'. (($error != "interval") ? $hidden : "") .'" id="invest_error_interval">Merci de ne pas laisser moins de ' . $min_value . '&euro; &agrave; investir. </span>';
		$form .= '<span class="invest_error'. (($error != "integer") ? $hidden : "") .'" id="invest_error_integer">Le montant que vous pouvez investir doit &ecirc;tre entier. </span>';
		$form .= '<span class="invest_error'. (($error != "general") ? $hidden : "") .'" id="invest_error_general">Le montant saisi semble comporter une erreur. </span>';
		$form .= '<span class="invest_success hidden" id="invest_success_message" class="button">';
                if ($campaign->funding_type()=="fundingdonation"){
                    $form .= 'Vous vous appr&ecirc;tez &agrave; donner <strong><span id="invest_show_amount"></span>&euro;</strong> en &eacute;change de : <strong><span id="invest_show_reward"></span></strong>.<br/><br/>';
                }
                $form .= 'Gr&acirc;ce à vous, nous serons ' . (ypcf_get_backers() + 1) . ' &agrave; soutenir le projet. La somme atteinte sera de <span id="invest_success_amount"></span>&euro;. </span>';
		
		$form .= '<div class="invest_step1_conditions">';
		switch ($campaign->funding_type()) {
		    case "fundingdonation":
			$form .= wpautop( $edd_options['message_before_donation'] );
			break;
		    default:
			$form .= wpautop( $edd_options['contract'] );
			break;
		}
		$form .= '</div>';
		
		$form .= '<br /><center>';
		
		
		switch ($campaign->funding_type()) {
		    case "fundingdonation":
			$form .= '<input type="hidden" name="invest_type" value="user" />';
			$form .= '<input type="submit" value="Confirmer mon don" class="button" />';
			break;
		    default:
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
			break;
		}
		
		$form .= '</center>';
		$form .= '</div>';
		
		$form .= '</form><br /><br />';
		$form .= '<div class="align-center mangopay-image"><img src="'.get_stylesheet_directory_uri() . '/images/powered_by_mangopay.png" alt="Bandeau Mangopay" /></div>';
	    } else {
		$form .= 'Il n&apos;est plus possible d&apos;investir sur ce <a href="'.get_permalink($campaign->ID).'">projet</a> !';
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