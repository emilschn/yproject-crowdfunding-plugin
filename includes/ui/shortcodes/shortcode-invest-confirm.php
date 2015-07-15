<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 
 * @global type $country_list
 */
function ypcf_display_invest_confirm($content) {
    $form = '';
    ypcf_session_start();
    
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();
    $part_value = ypcf_get_part_value();
    $max_part_value = ypcf_get_max_part_value();

    if (isset($_GET['campaign_id']) && $max_part_value > 0) {
        //Si la valeur peut être ponctionnée sur l'objectif, et si c'est bien du numérique supérieur à 0
        $amount_part = FALSE;
        if (isset($_POST['amount_part'])) $_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
        if (isset($_SESSION['redirect_current_amount_part'])) $amount_part = $_SESSION['redirect_current_amount_part'];
        
        if (isset($_POST['selected_reward'])) $_SESSION['redirect_current_selected_reward'] = $_POST['selected_reward'];
                    
        $amount = ($amount_part === FALSE) ? 0 : $amount_part * $part_value;
        $remaining_amount = $max_value - $amount;
        if (is_numeric($amount_part) && intval($amount_part) == $amount_part && $amount_part >= 1 && $amount >= $min_value && $amount_part <= $max_part_value && ($remaining_amount == 0 || $remaining_amount >= $part_value)) {

            $current_user = wp_get_current_user();
            ypcf_init_mangopay_user($current_user);
            $current_user_organisation = false;
            $organisation = false;
            $invest_type = '';
            if (isset($_POST['invest_type'])) $_SESSION['redirect_current_invest_type'] = $_POST['invest_type'];
            if (isset($_SESSION['redirect_current_invest_type'])) $invest_type = $_SESSION['redirect_current_invest_type'];
            if ($invest_type != 'user') {
                $organisation = new YPOrganisation($invest_type);
                $current_user_organisation = $organisation->get_creator();
                ypcf_init_mangopay_user($current_user_organisation, true);
            }

            if (isset($_POST['document_submited'])) {
                $url_request = ypcf_init_mangopay_user_strongauthentification($current_user);
                $curl_result = ypcf_mangopay_send_strong_authentication($url_request, 'StrongValidationDtoPicture');
                if ($curl_result) ypcf_mangopay_set_user_strong_authentication_doc_transmitted($current_user->ID);
                else $form .= 'Il y a eu une erreur pendant l&apos;envoi';
            }

            //Si le montant transmis est supérieur à ce que mangopay accepte sans identification
            $test_user = $current_user;
            if ($invest_type != 'user') $test_user = $current_user_organisation;
            $annual_amount = $amount + ypcf_get_annual_amount_invested($test_user->ID);
            if ($annual_amount > YP_STRONGAUTH_AMOUNT_LIMIT && !ypcf_mangopay_is_user_strong_authenticated($test_user->ID)) {
                if (ypcf_mangopay_is_user_strong_authentication_sent($test_user->ID)) {
                    $form .= 'Votre pi&egrave;ce d&apos;identit&eacute; est en cours de validation. Un d&eacute;lai maximum de 24h est n&eacute;cessaire &agrave; cette validation.<br />Merci de votre compr&eacute;hension.';

                } else {
                    if ($invest_type != 'user') {
                        $page_update = get_page_by_path('modifier-mon-compte');
                        $form .= '<br />Pour investir une somme sup&eacute;rieure &agrave; '.YP_STRONGAUTH_AMOUNT_LIMIT.'&euro; sur une ann&eacute;e, merci de vous rendre dans <a href="'.get_permalink($page_update->ID).'">l&apos;administration de votre entreprise</a>.<br />';

                    } else {
                        $post = get_post($_GET['campaign_id']);
                        $campaign = atcf_get_campaign( $post );
                        $form .= '<br />Pour investir une somme sup&eacute;rieure &agrave; '.YP_STRONGAUTH_AMOUNT_LIMIT.'&euro; sur une ann&eacute;e, vous devez fournir une pi&egrave;ce d&apos;identit&eacute;.<br />';
                        $form .= 'Le fichier doit &ecirc;tre de type jpeg, gif, png ou pdf.<br />';
                        $form .= 'Son poids doit &ecirc;tre inf&eacute;rieur &agrave; 2 Mo.<br />';
                        $form .= '<form id="mangopay_strongauth_form" action="" method="post" enctype="multipart/form-data">';
                        $form .= '<input type="hidden" name="document_submited" value="1" />';
                        $form .= '<input type="hidden" name="amount_part" value='.$amount_part.' />';
                        $form .= '<input type="file" name="StrongValidationDtoPicture" />';
                        $form .= '<input type="submit" value="Envoyer"/>';
                        $form .= '</form><br /><br />';
                    }
                }

            } else {
                $mangopay_project_id = ypcf_init_mangopay_project();
                if ($mangopay_project_id === FALSE)  {
                    return 'Problème de création de projet';
                }

                //Procédure modifiée d'ajout au panier (on ajoute x items de 1 euros => le montant se retrouve en tant que quantité)
                $post = get_post($_GET['campaign_id']);
                $campaign = atcf_get_campaign( $post );
                edd_empty_cart();
                
                $options_cart = array();
                if($campaign->funding_type()=='fundingdonation'){
                //Gestion contreparties : ajoute la contrepartie
                    $rewards = atcf_get_rewards($campaign->ID);
                    $data_reward = $rewards->get_reward_from_ID($_SESSION['redirect_current_selected_reward']);

                    $save_reward=array(
                        'id'    => intval($data_reward['id']),
                        'amount'=> intval($data_reward['amount']),
                        'name'  => $data_reward['name'],
                    );
                    $options_cart['reward']=$save_reward;
                }
                
                $to_add = array();
                $to_add[] = apply_filters( 'edd_add_to_cart_item', array( 'id' => $campaign->ID, 'options' => $options_cart, 'quantity' => $amount ) );
                EDD()->session->set( 'edd_cart', $to_add );

                // Rappel des informations remplies
                require_once('country_list.php');
                global $country_list;
                ypcf_session_start();
                $_SESSION['redirect_current_campaign_id'] = $_GET['campaign_id'];

                $text_to_type = ($campaign->funding_type() == 'fundingproject') ? 'pouvoir' : 'souscription';

                $form .= ypcf_print_invest_breadcrumb(2);
                if (isset($_POST['confirmed']) && !isset($_POST['information_confirmed'])) $form .= '<span class="errors">Merci de valider vos informations.</span><br />';
                
                if(($campaign->funding_type() != 'fundingdonation')){
                    if (isset($_POST['confirmed']) && (!isset($_POST['confirm_power']) || (isset($_POST['confirm_power']) && (strtolower($_POST['confirm_power'])) != 'bon pour '.$text_to_type))) $form .= '<span class="errors">Merci de saisir "Bon pour '.$text_to_type.'".</span><br />';
                    if (isset($_POST['confirmed']) && ($amount <= 1500) && (!isset($_POST['confirm_signing']) || !$_POST['confirm_signing'])) $form .= '<span class="errors">Merci de cocher la case de validation de contrat.</span><br />';
                }
                
                $page_invest = get_page_by_path('investir');
                $page_invest_link = get_permalink($page_invest->ID);
                $page_invest_link .= '?campaign_id=' . $_GET['campaign_id'];
                $plurial = '';
                if ($amount_part > 1) $plurial = 's';
                switch ($campaign->funding_type()) {
                    case 'fundingproject':
                        $form .= '<br />Vous vous appr&ecirc;tez &agrave; investir <strong>'.$amount.'&euro;</strong> sur le projet <strong>' . $post->post_title . '</strong>. <a href="'.$page_invest_link.'&invest_start=1">Modifier mon investissement</a><br /><br />';
                        break;
                    case 'fundingdonation':
                        $form .= '<br />Vous vous appr&ecirc;tez &agrave; donner <strong>'.$amount.'&euro;</strong> pour le projet <strong>' . $post->post_title . '</strong>.<br/>'
                            . 'En &eacute;change de ce don, vous avez choisi la contrepartie suivante : <strong>'.$data_reward['name'].'</strong>.<br/>'
                            . '<a href="'.$page_invest_link.'&invest_start=1">Modifier mon don</a><br /><br />';
                        break;
                    case 'fundingdevelopment':
                    default:
                        $form .= '<br />Vous vous appr&ecirc;tez &agrave; investir <strong>'.$amount.'&euro; ('.$amount_part . ' part'.$plurial.')</strong> sur le projet <strong>' . $post->post_title . '</strong>. <a href="'.$page_invest_link.'&invest_start=1">Modifier mon investissement</a><br /><br />';
                        break;
                }

                $form .= '<form action="'.$page_invest_link.'" method="post" enctype="multipart/form-data">';
                $form .= '<div class="invest_part">';
                $form .= 'Veuillez v&eacute;rifier ces informations avant de passer &agrave; l&apos;&eacute;tape suivante :<br /><br />';

                $form .= '<strong>Informations personnelles</strong><br />';
                $user_title = "";
                if ($current_user->get('user_gender') == "male") $user_title = "MONSIEUR";
                if ($current_user->get('user_gender') == "female") $user_title = "MADAME";
                $user_name = $user_title . ' ' . $current_user->first_name . ' ' . $current_user->last_name;
                $form .= '<span class="label">Identit&eacute; :</span>' . $user_name . '<br />';
                $form .= '<span class="label">e-mail :</span>' . $current_user->user_email . '<br /><br />';
                $form .= '<span class="label">Date et lieu de naissance :</span>le ' . $current_user->get('user_birthday_day') . '/' . $current_user->get('user_birthday_month') . '/' . $current_user->get('user_birthday_year');
                $form .= ' &agrave; ' . $current_user->get('user_birthplace') . '<br />';
                $form .= '<span class="label">Nationalit&eacute; :</span>' . $country_list[$current_user->get('user_nationality')] . '<br /><br />';
                if ($campaign->funding_type() != 'fundingdonation'){
                    $form .= '<div class="label left">Adresse :</div>';
                    $form .= '<div class="left">' . $current_user->get('user_address') . '<br />' . $current_user->get('user_postal_code') . ' ' . $current_user->get('user_city') . '<br />' . $current_user->get('user_country') . '</div>';
                    $form .= '<div style="clear: both;"></div>';
                    $form .= '<br />';
                    $form .= '<span class="label">Num&eacute;ro de t&eacute;l&eacute;phone :</span>' . $current_user->get('user_mobile_phone');
                    if (!ypcf_check_user_phone_format($current_user->get('user_mobile_phone'))) $form .= ' <span class="errors">Le num&eacute;ro de t&eacute;l&eacute;phone ne correspond pas &agrave; un num&eacute;ro français.</span>';
                }
                $form .= '<br /><br /><br />';

                if ($invest_type != 'user') {
                    $form .= '<hr />';
                    $form .= '<strong>Informations de l&apos;organisation <em>'.$organisation->get_name().'</em></strong><br />';
                    $form .= '<span class="label">Num&eacute;ro d&apos;immatriculation :</span>' . $organisation->get_idnumber() . '<br />';
                    $form .= '<span class="label">RCS :</span>' . $organisation->get_rcs() . '<br />';
                    $form .= '<span class="label">Forme juridique :</span>' . $organisation->get_legalform() . '<br />';
                    $form .= '<span class="label">Capital social :</span>' . $organisation->get_capital() . '<br /><br />';

                    $form .= '<div class="left label">Adresse :</div>';
                    $form .= '<div class="left">'. $organisation->get_address() . '<br />';
                    $form .= $organisation->get_postal_code() . ' ' . $organisation->get_city() . '<br />';
                    $form .= $country_list[$organisation->get_nationality()] . '</div>';
                    $form .= '<div style="clear: both;"></div>';
                    $form .= '<br /><br />';
                }

                $page_update = get_page_by_path('modifier-mon-compte');
                $form .= '<a href="' . get_permalink($page_update->ID) . '">Modifier ces informations</a><br /><br />';

                $information_confirmed = '';
                if (isset($_POST["information_confirmed"]) && $_POST["information_confirmed"] == "1") $information_confirmed = 'checked="checked" ';
                $form .= '<label><input type="checkbox" name="information_confirmed" value="1" '.$information_confirmed.'/> Je d&eacute;clare que ces informations sont exactes.</label><br />';
                $form .= '</div>';

                // Formulaire de confirmation
                $form .= '<input type="hidden" name="amount_part" value="' . $amount_part . '">';
                $form .= '<input type="hidden" name="confirmed" value="1">';

                if(($campaign->funding_type() != 'fundingdonation')){
                    if ($amount <= 1500) {
                            $form .= '<h3>Merci de prendre connaissance du contrat que vous allez accepter :</h3>';
                    } else {
                            $form .= '<h3>Voici le pouvoir que vous allez signer pour valider l&apos;investissement :</h3>';
                    }
                    $invest_data = array("amount_part" => $amount_part, "amount" => $amount, "total_parts_company" => $campaign->total_parts(), "total_minimum_parts_company" => $campaign->total_minimum_parts(), "ip" => $_SERVER['REMOTE_ADDR']);
                    $form .= '<div style="padding: 10px; border: 1px solid grey; height: 400px; overflow: scroll;">'.  fillPDFHTMLDefaultContent($current_user, $campaign, $invest_data, $organisation).'</div>';

                    $form .= '<br />Je donne pouvoir à la société WE DO GOOD :<br />';
                    $form .= 'Ecrire "<strong>Bon pour '.$text_to_type.'</strong>" dans la zone de texte ci-contre :';
                    $confirm_power = '';
                    if (isset($_POST["confirm_power"])) $confirm_power = $_POST["confirm_power"];
                    $form .= '&nbsp;<input type="text" name="confirm_power" value="'.$confirm_power.'" /><br /><br />';

                    //Si investissement <= 1500, pas besoin de signature, donc on fait cocher une case
                    if ($amount <= 1500) {
                            $form .= '<br /><label for="confirm_signing"><input type="checkbox" name="confirm_signing" /> J&apos;ai bien compris les termes du contrat, que je valide.</label><br /><br />';
                    }
                }
                switch ($campaign->funding_type()) {
                    case 'fundingdonation':
                        $button_title = "Confirmer le don";
                        break;
                    default:
                        $button_title = "Investir";
                        break;
                }
                $form .= '<center><input type="submit" value="'.$button_title.'" class="button"></center>';
                $form .= '</form><br /><br />';

                $form .= '<div style="text-align: center;"><img src="'.get_stylesheet_directory_uri() . '/images/powered_by_mangopay.png" /></div>';
            }

        } else {
            $error = 'general';
            if (intval($amount_part) != $amount_part) $error = 'integer';
            if ($amount_part < 1 || $amount < $min_value) $error = 'min';
            if ($amount > $max_value) $error = 'max';
            if ($remaining_amount > 0 && $remaining_amount < $part_value) $error = 'interval';
            unset($amount_part);
            $form .= ypcf_display_invest_form($error);
        }
    }
    
    return $form;
}


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
?>