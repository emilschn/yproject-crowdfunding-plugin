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
 * @param type $user_name
 * @param type $project_name
 * @return string
 */
function fillPDFHTMLDefaultContent($user_name, $project_name) {
    $buffer = '';
    
    $buffer .= '<p>Merci de participer &agrave; ce projet.</p>';
    $buffer .= '<p>Le projet s\'appelle ' . $project_name . '</p>';
    $buffer .= '<p>Vous signez en tant que ' . $user_name . '</p>';
    $buffer .= '<p>Attention, &ccedil;a va signer !</p>';
    
    return $buffer;
}

/**
 * Returns the pdf created with a project_id and a user_id
 * @param type $project_id
 */
function getNewPdfToSign($project_id) {
    $post_camp = get_post($project_id);
    $campaign = atcf_get_campaign( $post_camp );
    $project_name = get_the_title( $campaign->ID );
    
    $current_user = wp_get_current_user();
    $user_name = $current_user->first_name . ' ' . $current_user->last_name;
    
    $html_content = fillPDFHTMLDefaultContent($user_name, $project_name);
    $filename = dirname ( __FILE__ ) . '/pdf_files/' . $campaign->ID . '_' . $current_user->ID . '_' . time() . '.pdf';
    
    if (generatePDF($html_content, $filename)) return $filename;
    else return false;
}

?>