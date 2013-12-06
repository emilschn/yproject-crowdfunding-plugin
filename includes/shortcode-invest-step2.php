<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 
 * @global type $country_list
 */
function ypcf_display_invest_confirm($content) {
    $form = '';
    
    $min_value = ypcf_get_min_value_to_invest();
    $max_value = ypcf_get_max_value_to_invest();
    $part_value = ypcf_get_part_value();
    $max_part_value = ypcf_get_max_part_value();

    if (isset($_GET['campaign_id']) && $max_part_value > 0) {
	//Si la valeur peut être ponctionnée sur l'objectif, et si c'est bien du numérique supérieur à 0
	$amount = $_POST['amount_part'] * $part_value;
	$amount_interval = $max_value - $amount;
	if (is_numeric($_POST['amount_part']) && intval($_POST['amount_part']) == $_POST['amount_part'] && $_POST['amount_part'] >= 1 && $_POST['amount_part'] <= $max_part_value && ($amount_interval == 0 || $amount_interval >= $min_value)) {

	    $current_user = wp_get_current_user();
	    ypcf_init_mangopay_user($current_user);
	    
	    if (isset($_POST['document_submited'])) {
		$url_request = ypcf_init_mangopay_user_strongauthentification($current_user);
		$curl_result = ypcf_mangopay_send_strong_authentication($url_request);
		if ($curl_result) ypcf_mangopay_set_user_strong_authentication_doc_transmitted($current_user->ID);
		else $form .= 'Il y a eu une erreur pendant l&apos;envoi';
	    }
	    
	    //Si le montant transmis est supérieur à ce que mangopay accepte sans identification
	    $annual_amount = $amount + ypcf_get_annual_amount_invested($current_user->ID);
	    if ($annual_amount > YP_STRONGAUTH_AMOUNT_LIMIT && !ypcf_mangopay_is_user_strong_authenticated($current_user->ID)) {
		if (ypcf_mangopay_is_user_strong_authentication_sent($current_user->ID)) {
		    $form .= 'Votre pi&egrave;ce d&apos;identit&eacute; est en cours de validation. Un d&eacute;lai maximum de 24h est n&eacute;cessaire &agrave; cette validation.<br />Merci de votre compr&eacute;hension.';
		    
		} else {
		    $post = get_post($_GET['campaign_id']);
		    $campaign = atcf_get_campaign( $post );
		    $form .= '<br />'.$campaign->investment_terms();
		    $form .= '<br />Pour investir une somme sup&eacute;rieure &agrave; '.YP_STRONGAUTH_AMOUNT_LIMIT.'&euro; sur une ann&eacute;e, vous devez fournir une pi&egrave;ce d&apos;identit&eacute;.<br />';
		    $form .= 'Le fichier doit &ecirc;tre de type jpeg, gif, png ou pdf.<br />';
		    $form .= 'Son poids doit &ecirc;tre inf&eacute;rieur &agrave; 2 Mo.<br />';
		    $form .= '<form id="mangopay_strongauth_form" action="" method="post" enctype="multipart/form-data">';
		    $form .= '<input type="hidden" name="document_submited" value="1" />';
		    $form .= '<input type="hidden" name="amount_part" value='.$_POST['amount_part'].' />';
		    $form .= '<input type="file" name="StrongValidationDtoPicture" />';
		    $form .= '<input type="submit" value="Envoyer"/>';
		    $form .= '</form><br /><br />';
		}
		
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
		$form .= $_POST['amount_part'] . ' part soit '.$amount.'&euro;<input type="submit" value="Investir">';
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
?>