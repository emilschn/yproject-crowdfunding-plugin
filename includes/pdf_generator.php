<?php 
/**
 * Creates a pdf file with the content
 * @param type $html_content
 * @param type $filename
 * @return boolean
 */
function generatePDF($html_content, $filename) {
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
//function fillPDFHTMLDefaultContent($user_name, $project_name) {
function fillPDFHTMLDefaultContent($user_obj, $campaign_obj, $payment_id) {
    $buffer = '';
    
    require_once("country_list.php");
    global $country_list;
    $nationality = $country_list[$user_obj->get('user_nationality')];
    $user_name = $user_obj->first_name . ' ' . $user_obj->last_name;
    
    $project_name = get_the_title( $campaign_obj->ID );
    
    $buffer .= '<div style="border: 1px solid black; width:100%; padding:5px 0px 5px 0px; text-align:center;"><h1>POUVOIR EN VUE DE LA CONSTITUTION DE LA SOCIÉTÉ "!TODO:'.$project_name.'!"</h1></div>';
    
    $buffer .= '<p>';
    $buffer .= '<h2>LE SOUSSIGNÉ</h2>';
    $buffer .= '<strong>!TODO:genre! '.$user_name.'</strong><br />';
    $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $birthday_month = strtoupper(__($months[$user_obj->get('user_birthday_month')]));
    $buffer .= 'né le '.$user_obj->get('user_birthday_day').' '.$birthday_month.' '.$user_obj->get('user_birthday_year').' à !TODO:villenaissance!<br />';
    $buffer .= 'de nationalité '.$nationality.'<br />';
    $buffer .= 'demeurant à '.$user_obj->get('user_city').' ('.$user_obj->get('user_postal_code').') - ' . $user_obj->get('user_address');
    $buffer .= '</p>';
    
    $buffer .= '<p>';
    $buffer .= '<h2>DONNE TOUS POUVOIRS à</h2>';
    $buffer .= '<strong>La société WE DO GOOD</strong><br />';
    $buffer .= 'Société par actions simplifiée au capital variable de 14 390 Euros<br />';
    $buffer .= 'dont le siège social est à RENNES (35) - 51 rue Saint Hélier<br />';
    $buffer .= 'immatriculée au R.C.S. de RENNES sous le numéro 797 519 105<br />';
    $buffer .= 'représentée par Monsieur Jean-David BAR';
    $buffer .= '</p>';
    
    $buffer .= '<p>';
    $buffer .= '<strong>à l\'effet, en son nom et pour son compte de :</strong><br />';
    $buffer .= '<p>';
    $buffer .= '- Souscrire !TODO:nb! parts sur les !TODO:nb! parts de la société "!TODO:'.$project_name.'!", société en participation en cours de constitution, dont les principales caractéristiques seront les suivantes :<br />';
    $buffer .= '<ul>';
    $buffer .= '<li>Objet : la société a pour objet, directement ou indirectement, en FRANCE ou à l\'étranger :';
    $buffer .= '<ul><li>La fourniture de prestations de conseils aux organisations pour les affaires et la communication ;</li></ul>';
    $buffer .= '</li>';
    if (true) {
	$buffer .= '<li>Apports : !TODO:nb! euros, divisés en !TODO:nb! parts de !TODO:nb! Euro chacune.</li>';
	$buffer .= '<li>Domicile : !TODO:Adresse!,</li>';
	$buffer .= '<li>Déclarée au tribunal de commerce de !TODO:Rennes!,</li>';
	$buffer .= '<li>Gérant : !TODO:Gérant! ;</li>';
    } else {
	$buffer .= '<li>Capital : !TODO:nb! euros, divisé en !TODO:nb! actions de !TODO:nb! Euro de valeur nominale chacune.</li>';
	$buffer .= '<li>Siège social : !TODO:Adresse!,</li>';
	$buffer .= '<li>Immatriculation au registre du commerce et des sociétés de !TODO:Rennes!,</li>';
	$buffer .= '<li>Président : !TODO:Président!,</li>';
	$buffer .= '<li>Directeur général : !TODO:DG! ;</li>';
    }
    $buffer .= '</ul>';
    $buffer .= '</p>';
    
    $buffer .= '<p>';
    $buffer .= '- Déposer entre les mains du futur gérant le montant de son apport en vue de la réalisation de l\'objet social ;';
    $buffer .= '</p>';
    
    $buffer .= '<p>';
    $buffer .= '- Signer les statuts de la société "!TODO:'.$project_name.'!" et plus généralement tous actes et documents constitutifs de la société "!TODO:'.$project_name.'!" ;';
    $buffer .= '</p>';
    
    $buffer .= '<p>';
    $buffer .= '- Plus généralement, faire tout ce qui sera utile et nécessaire à la constitution et l\'immatriculation de la société "!TODO:'.$project_name.'!" ;';
    $buffer .= '</p>';
    
    $buffer .= '</p>';
    
    $buffer .= '<table style="border:0px;"><tr><td style="width: 350px;">';
    $buffer .= 'Fait à !TODO:Rennes!<br />';
    $buffer .= 'Le !TODO:Date!<br />';
    $buffer .= '(!TODO:Pour la société X!)<br />';
    $buffer .= '!TODO:genre! '.$user_name.'<br />';
    $buffer .= '(1)';
    $buffer .= '</td>';
    
    $buffer .= '<td>';
    $buffer .= 'Fait à <br />';
    $buffer .= 'Le <br />';
    $buffer .= 'Pour la société "WE DO GOOD"<br />';
    $buffer .= 'Monsieur Jean-David BAR<br />';
    $buffer .= '(2)';
    $buffer .= '</td></tr></table>';
    
    $buffer .= '<div style="padding-top: 100px;">';
    $buffer .= '(1) signature accompagnée de la mention "Bon pour pouvoir"<br />';
    $buffer .= '(2) signature accompagnée de la mention "Bon pour acceptation de pouvoir"';
    $buffer .= '</div>';
    
    $buffer .= '<div style="padding-top: 100px;"></div>';
    $buffer .= '<div style="border: 1px solid black; width:100%; padding:5px 0px 5px 0px; text-align:center;"><h1>MODALITES DE LA CONSTITUTION</h1></div>';
    
    $buffer .= '<p>Il est envisagé de procéder à la constitution d\'une société en participation, régie par les articles 871 à 1873 du code civil.</p>';
    
    $buffer .= '<p>';
    $buffer .= '<strong>Conditions de la constitution</strong>';
    $buffer .= '<p>Pour que la constitution de la société "!TODO:'.$project_name.'!" soit effective, les associés devront avoir apporté au moins !TODO:nb! euros en numéraire entre le !TODO:date! et le !TODO:date!.</p>';
    $buffer .= '<p>Les statuts seront signés par les associés, soit en personne, soit par mandataire justifiant d\'un pouvoir spécial.</p>';
    $buffer .= '</p>';
    
    $buffer .= '<p>';
    $buffer .= '<strong>Caractéristiques de la société</strong>';
    $buffer .= '<p>Les statuts de la société sont rendus accessibles aux associés avant sa constitution via le lien suivant !TODO:lien!.</p>';
    $buffer .= '</p>';
   
    
    return $buffer;
}

/**
 * Returns the pdf created with a project_id and a user_id
 * @param type $project_id
 */
function getNewPdfToSign($project_id, $payment_id) {
    $post_camp = get_post($project_id);
    $campaign = atcf_get_campaign( $post_camp );
    
    $current_user = wp_get_current_user();
    
    $html_content = fillPDFHTMLDefaultContent($current_user, $campaign, $payment_id);
    $filename = dirname ( __FILE__ ) . '/pdf_files/' . $campaign->ID . '_' . $current_user->ID . '_' . time() . '.pdf';
    
    if (generatePDF($html_content, $filename)) return $filename;
    else return false;
}

?>