<?php 
/**
 * Creates a pdf file with the content
 * @param type $html_content
 * @param type $filename
 * @return boolean
 */
function generatePDF($html_content, $filename) {
    ypcf_debug_log('generatePDF > ' . $filename);
    $buffer = false;
    if (isset($html_content) && isset($filename) && ($filename != "") && !file_exists($filename)) {
	$html2pdf = new HTML2PDF('P','A4','fr');
	$html2pdf->WriteHTML(urldecode($html_content));
	$html2pdf->Output($filename, 'F');
	$buffer = true;
    }
    return $buffer;
}

/**
 * Fill the pdf default content with infos
 * @return string
 */
function fillPDFHTMLDefaultContent($user_obj, $campaign_obj, $payment_data, $organisation = false) {
    ypcf_debug_log('fillPDFHTMLDefaultContent > ' . $payment_data["amount"]);
    $buffer = '';
    
    setlocale( LC_CTYPE, 'fr_FR' );
    require_once("country_list.php");
    $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    
    global $country_list;
    $nationality = $country_list[$user_obj->get('user_nationality')];
    $user_title = "";
    if ($user_obj->get('user_gender') == "male") $user_title = "Monsieur";
    if ($user_obj->get('user_gender') == "female") $user_title = "Madame";
    $user_name = mb_strtoupper($user_title . ' ' . $user_obj->first_name . ' ' . $user_obj->last_name);
    
    $buffer .= '<div style="border: 1px solid black; width:100%; padding:5px 0px 5px 0px; text-align:center;"><h1>'.$campaign_obj->contract_title().'</h1></div>';
    
    $buffer .= '<p>';
    $buffer .= '<h2>LE SOUSSIGNÉ</h2>';
    if (is_object($organisation) && $organisation !== false) {
	$buffer .= '<strong>'.$organisation->get_name().', '.$organisation->get_legalform().' au capital '.$organisation->get_capital().'&euro;</strong><br />';
	$buffer .= 'dont le siège social est à '.$organisation->get_city().' ('.$organisation->get_postal_code().') - '.$organisation->get_address().'<br />';
	$buffer .= 'immatriculée sous le numéro '.$organisation->get_idnumber().' au RCS de '.$organisation->get_rcs().'<br />';
	$buffer .= 'représentée par ';
    }
    $buffer .= '<strong>'.$user_name.'</strong><br />';
    $birthday_month = mb_strtoupper(__($months[$user_obj->get('user_birthday_month') - 1]));
    $suffix_born = ($user_obj->get('user_gender') == "female") ? 'e' : '';
    $buffer .= 'né'.$suffix_born.' le '.$user_obj->get('user_birthday_day').' '.$birthday_month.' '.$user_obj->get('user_birthday_year').' à '.$user_obj->get('user_birthplace').'<br />';
    $buffer .= 'de nationalité '.$nationality.'<br />';
    $buffer .= 'demeurant à '.$user_obj->get('user_city').' ('.$user_obj->get('user_postal_code').') - ' . $user_obj->get('user_address');
    $buffer .= '</p>';
    
    switch ($campaign_obj->funding_type()) {
	    case 'fundingproject':
		    $buffer .= '<p>';
		    $buffer .= '<h2>DONNE TOUS POUVOIRS à</h2>';
		    $buffer .= '<strong>La société WE DO GOOD</strong><br />';
		    $buffer .= 'Société par actions simplifiée au capital minimum de 1 239 Euros<br />';
		    $buffer .= 'dont le siège social est à RENNES (35) - 51 rue Saint Hélier<br />';
		    $buffer .= 'immatriculée au R.C.S. de RENNES sous le numéro 797 519 105<br />';
		    $buffer .= 'représentée par Monsieur Jean-David BAR';
		    $buffer .= '</p>';
		    
		    $buffer .= '<p>';
		    $buffer .= '<strong>à l\'effet, en son nom et pour son compte de :</strong><br />';
		    $buffer .= '</p>';
		break;
		
	    case 'fundingdevelopment':
	    default:
		$buffer .= '<p>';
		$buffer .= '<h2>DECLARE</h2>';
		$buffer .= '</p>';
		break;
    }
    
    $buffer .= '<p>';
    switch ($campaign_obj->funding_type()) {
	    case 'fundingproject':
		$buffer .= '- Signer en tant qu\'Investisseur le contrat à terme ferme annexé au présent pouvoir pour le montant de '.$payment_data["amount"].' euros auprès du Porteur de Projet identifié par les caractéristiques suivantes :<br />';
		break;
	    case 'fundingdevelopment':
		$plurial = '';
		if ($payment_data["amount_part"] > 1) $plurial = 's';
		$buffer .= '- Souscrire ' . $payment_data["amount_part"] . ' part'.$plurial.' de la société dont les principales caractéristiques sont les suivantes :<br />';
		break;
    }
    
    $buffer .= html_entity_decode($campaign_obj->subscription_params());
    $buffer .= '</p>';
    
    $buffer .= '<p>';
    $buffer .= html_entity_decode($campaign_obj->powers_params());
    $buffer .= '</p>';
    
    $buffer .= '<table style="border:0px;"><tr><td>';
    $buffer .= 'Fait avec l\'adresse IP '.$_SERVER['REMOTE_ADDR'].'<br />';
    $day = date("d");
    $month = mb_strtoupper(__($months[date("m") - 1]));
    $year = date("Y");
    $buffer .= 'Le '.$day.' '.$month.' '.$year.'<br />';
    if (is_object($organisation) && $organisation !== false) {
	$buffer .= 'LA '.$organisation->get_legalform().' '.$organisation->get_name().'<br />';
	$buffer .= 'représentée par ';
    }
    $buffer .= $user_name.'<br />';
    $text_to_type = ($campaign_obj->funding_type() == 'fundingproject') ? 'pouvoir' : 'souscription';
    $buffer .= '(1)<br />';
    $buffer .= 'Bon pour '.$text_to_type;
    $buffer .= '</td>';
    
    $buffer .= '<td></td></tr></table>';
    
    $buffer .= '<div style="padding-top: 100px;">';
    $buffer .= '(1) signature accompagnée de la mention "Bon pour '.$text_to_type.'"<br /><br />';
    $buffer .= '</div>';
    
    $buffer .= '<div style="padding-top: 100px;"></div>';
    $buffer .= '<div style="border: 1px solid black; width:100%; padding:5px 0px 5px 0px; text-align:center;"><h1>ANNEXE</h1></div>';
    
    $buffer .= html_entity_decode($campaign_obj->constitution_terms());
   
    
    return $buffer;
}

/**
 * Returns the pdf created with a project_id and a user_id
 * @param type $project_id
 */
function getNewPdfToSign($project_id, $payment_id, $user_id) {
    ypcf_debug_log('getNewPdfToSign > ' . $payment_id);
    $post_camp = get_post($project_id);
    $campaign = atcf_get_campaign( $post_camp );
    
    $current_user = get_userdata($user_id);
    if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] != "user") {
	$group_id = $_SESSION['redirect_current_invest_type'];
	$organisation = new YPOrganisation($group_id);
    }
    $amount = edd_get_payment_amount($payment_id);
    $amount_part = $amount / $campaign->part_value();
    
    $invest_data = array("amount_part" => $amount_part, "amount" => $amount, "total_parts_company" => $campaign->total_parts(), "total_minimum_parts_company" => $campaign->total_minimum_parts());
    $html_content = fillPDFHTMLDefaultContent($current_user, $campaign, $invest_data, $organisation);
    $filename = dirname ( __FILE__ ) . '/../pdf_files/' . $campaign->ID . '_' . $current_user->ID . '_' . time() . '.pdf';
    
    if (generatePDF($html_content, $filename)) return $filename;
    else return false;
}

?>