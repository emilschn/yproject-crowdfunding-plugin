<?php

/**
 * Submit Shortcode.
 *
 * [appthemer_crowdfunding_submit] creates a submission form.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



/*********************************************************************************************/
/* FORM GENERATED WITH SHORTCODES */
/*********************************************************************************************/
/**
 * Ouverture du formulaire
 * @param type $is_editing
 * @return string
 */
function ypcf_shortcode_submit_start( $is_editing = false ) {
    if (is_user_logged_in()) {
	$crowdfunding = crowdfunding();
	wp_enqueue_script( 'jquery-validation', EDD_PLUGIN_URL . 'assets/js/jquery.validate.min.js');
	wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery', 'jquery-validation' ) );
	
	$errors = '';
	$error_string = '';
	global $submit_errors;
	if (isset($submit_errors)) {
		foreach ($submit_errors->errors as $submit_error) {
			if (isset($submit_error[0])) $error_string .= $submit_error[0] . '<br />';
		}
	}
	if ($error_string != '') {
		$errors = '<div class="errors padder_more">' . $error_string . '</div>';
	}
	
	return $errors . '<form action="" method="post" class="wdg-forms" enctype="multipart/form-data">';
    } else {
	$page_connexion = get_page_by_path('connexion');
	return 'Attention : <a href="'.get_permalink($page_connexion->ID).'">Vous devez &ecirc;tre connect&eacute; pour proposer un projet</a><br /><br />';
    }
}
add_shortcode( 'yproject_crowdfunding_submit_start', 'ypcf_shortcode_submit_start' );

/**
 * Fermeture du formulaire
 * @param type $is_editing
 * @return string
 */
function ypcf_shortcode_submit_end() {
    if (is_user_logged_in()) {
	return '<p class="atcf-submit-campaign-submit">
			<input type="submit" class="button" value="Enregistrer le projet">
			<input type="hidden" name="action" value="atcf-campaign-submit" />
			'.wp_nonce_field( 'atcf-campaign-submit', '_wpnonce', true, false ).'
		</p>
	</form>';
    } else {
	$page_connexion = get_page_by_path('connexion');
	return 'Attention : <a href="'.get_permalink($page_connexion->ID).'">Vous devez &ecirc;tre connect&eacute; pour proposer un projet</a>';
    }    
}
add_shortcode( 'yproject_crowdfunding_submit_end', 'ypcf_shortcode_submit_end' );

/**
 * Champ texte
 * @param type $atts
 * @param type $content
 * @return type
 */
function ypcf_shortcode_submit_field($atts, $content = '') {
    $atts = shortcode_atts( array(
	'name' => 'title',
	'rows' => 5,
	'cols' => 50
    ), $atts );
    $value = isset($_POST[$atts['name']]) ? stripslashes( $_POST[$atts['name']] ) : '';
    return '<textarea name="'.$atts['name'].'" id="'.$atts['name'].'" rows="'.$atts['rows'].'" cols="'.$atts['cols'].'">'.$value.'</textarea>';
}
add_shortcode('yproject_crowdfunding_field', 'ypcf_shortcode_submit_field');

/**
 * Champ sélection de catégorie wordpress
 * @param type $atts
 * @param type $content
 * @return type
 */
function ypcf_shortcode_submit_field_category($atts, $content = '') {
    $atts = shortcode_atts( array(
	'type' => 'categories'
    ), $atts );
   
    $terms = get_terms( 'download_category', array('slug' => $atts['type'], 'hide_empty' => false));
    $term_id = 0;
    foreach ( $terms as $term ) {
       $term_id = $term->term_id;
    }
    return wp_dropdown_categories( array( 
        'hide_empty'  => 0,
        'taxonomy'    => 'download_category',
        'selected'    => $_POST[$atts['type']],
        'echo'        => 0,
        'child_of'    => $term_id, 
        'name'        => $atts['type']
    ) );
}
add_shortcode('yproject_crowdfunding_field_category', 'ypcf_shortcode_submit_field_category');

/**
 * Champ Fichier
 * @param type $atts
 * @param type $content
 * @return type
 */
function ypcf_shortcode_submit_field_file($atts, $content = '') {
    $atts = shortcode_atts( array(
	'name' => 'image'
    ), $atts );
    return '<input type="file" name="'.$atts['name'].'" id="'.$atts['name'].'" />';
}
add_shortcode('yproject_crowdfunding_field_file', 'ypcf_shortcode_submit_field_file');

/**
 * Champ éditeur de texte (transformé en hidden)
 * @param type $atts
 * @param type $content
 * @return type
 */
function ypcf_shortcode_submit_field_complex($atts, $content = '') {
    global $editing, $current_campaign;
    $atts = shortcode_atts( array(
	'name' => 'description',
	'width' => '350px',
	'height' => '400px'
    ), $atts );
    
    ob_start();
    $value = htmlentities(wp_richedit_pre($content));
    
    /*if (isset($_POST[$atts['name']])) $value = html_entity_decode($_POST[$atts['name']]);
    wp_editor( 
	    $value, 
	    $atts['name'], 
	    apply_filters(  
		'atcf_submit_field_'.$atts['name'].'_editor_args', 
		array( 
		    'media_buttons' => true,
		    'teeny'         => true,
		    'quicktags'     => false,
		    'editor_css'    => '<style>body { background: white; } .wp-editor-wrap {background: white;} .wp-editor-container {width:'.$atts['width'].'; height:'.$atts['height'].';} .wp-editor-area {width:'.$atts['width'].'; height:'.$atts['height'].';} .media-frame-menu{display: none;}  .media-frame-router{left: 0px !important;} .media-frame-title{left: 0px !important;} .media-frame-content{left: 0px !important;} .media-frame-toolbar{left: 0px !important;}</style>',
		    'tinymce'       => array(
			    'theme_advanced_path'     => false,
			    'theme_advanced_buttons1' => 'bold,italic,forecolor,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
			    'plugins'                 => 'paste',
			    'paste_remove_styles'     => true,
			    'theme_advanced_resizing_use_cookie' => false
		    )

		) 
	    ) 
    );
    return ob_get_clean();*/
    return '<input type="hidden" name="'.$atts['name'].'" value="'.$value.'" />';
}
add_shortcode('yproject_crowdfunding_field_complex', 'ypcf_shortcode_submit_field_complex');

/**
 * Champ sélection de localisation
 * @param type $atts
 * @param type $content
 * @return type
 */
function ypcf_shortcode_submit_field_location($atts, $content = '') {
    $location_list = array(
	'01' => 'Ain',
	'02' => 'Aisne',
	'03' => 'Allier',
	'04' => 'Alpes-de-Haute-Provence',
	'05' => 'Hautes-Alpes',
	'06' => 'Alpes-Maritimes',
	'07' => 'Ard&egrave;che',
	'08' => 'Ardennes',
	'09' => 'Ari&egrave;ge',
	'10' => 'Aube',
	'11' => 'Aude',
	'12' => 'Aveyron',
	'13' => 'Bouches-du-Rh&ocirc;ne',
	'14' => 'Calvados',
	'15' => 'Cantal',
	'16' => 'Charente',
	'17' => 'Charente-Maritime',
	'18' => 'Cher',
	'19' => 'Corr&egrave;ze',
	'2A' => 'Corse-du-Sud',
	'2B' => 'Haute-Corse',
	'21' => 'C&ocirc;te-d\'Or',
	'22' => 'C&ocirc;tes d\'Armor',
	'23' => 'Creuse',
	'24' => 'Dordogne',
	'25' => 'Doubs',
	'26' => 'Dr&ocirc;me',
	'27' => 'Eure',
	'28' => 'Eure-et-Loir',
	'29' => 'Finist&egravere',
	'30' => 'Gard',
	'31' => 'Haute-Garonne',
	'32' => 'Gers',
	'33' => 'Gironde',
	'34' => 'H&eacute;rault',
	'35' => 'Ille-et-Vilaine',
	'36' => 'Indre',
	'37' => 'Indre-et-Loire',
	'38' => 'Is&egrave;re',
	'39' => 'Jura',
	'40' => 'Landes',
	'41' => 'Loir-et-Cher',
	'42' => 'Loire',
	'43' => 'Haute-Loire',
	'44' => 'Loire-Atlantique',
	'45' => 'Loiret',
	'46' => 'Lot',
	'47' => 'Lot-et-Garonne',
	'48' => 'Loz&egrave;re',
	'49' => 'Maine-et-Loire',
	'50' => 'Manche',
	'51' => 'Marne',
	'52' => 'Haute-Marne',
	'53' => 'Mayenne',
	'54' => 'Meurthe-et-Moselle',
	'55' => 'Meuse',
	'56' => 'Morbihan',
	'57' => 'Moselle',
	'58' => 'Ni&egrave;vre',
	'59' => 'Nord',
	'60' => 'Oise',
	'61' => 'Orne',
	'62' => 'Pas-de-Calais',
	'63' => 'Puy-de-D&ocirc;me',
	'64' => 'Pyr&eacute;n&eacute;es-Atlantiques',
	'65' => 'Hautes-Pyr&eacuten&eacute;es',
	'66' => 'Pyr&eacute;n&eacute;es-Orientales',
	'67' => 'Bas-Rhin',
	'68' => 'Haut-Rhin',
	'69' => 'Rh&ocirc;ne',
	'70' => 'Haute-Sa&ocirc;ne',
	'71' => 'Sa&ocirc;ne-et-Loire',
	'72' => 'Sarthe',
	'73' => 'Savoie',
	'74' => 'Haute-Savoie',
	'75' => 'Paris',
	'76' => 'Seine-Maritime',
	'77' => 'Seine-et-Marne',
	'78' => 'Yvelines',
	'79' => 'Deux-S&egrave;vres',
	'80' => 'Somme',
	'81' => 'Tarn',
	'82' => 'Tarn-et-Garonne',
	'83' => 'Var',
	'84' => 'Vaucluse',
	'85' => 'Vend&eacute;e',
	'86' => 'Vienne',
	'87' => 'Haute-Vienne',
	'88' => 'Vosges',
	'89' => 'Yonne',
	'90' => 'Territoire de Belfort',
	'91' => 'Essonne',
	'92' => 'Hauts-de-Seine',
	'93' => 'Seine-Saint-Denis',
	'94' => 'Val-de-Marne',
	'95' => 'Val-d\'Oise',
	'971' => 'Guadeloupe',
	'972' => 'Martinique',
	'973' => 'Guyane',
	'974' => 'La R&eacute;union',
	'976' => 'Mayotte',
    );
    $buffer = '<select id="location" name="location">';
    foreach ($location_list as $location_key => $location_name) {
	$location_str = $location_key.' '.$location_name;
	$selection_str = (isset($_POST['location']) && $_POST['location'] == $location_str) ? ' selected="selected"' : '';
	$buffer .= '<option'.$selection_str.'>'.$location_str.'</option>';
    }
    $buffer .= '</select>';
    return $buffer;
}
add_shortcode('yproject_crowdfunding_field_location', 'ypcf_shortcode_submit_field_location');

/**
 * Gestion de la partie Type de financement
 * @param type $atts
 * @param type $content
 * @return type
 */
function ypcf_shortcode_submit_field_fundingtype($atts, $content = '') {
    $atts = shortcode_atts( array(
	'option1' => 'Financement d&apos;un projet',
	'option2' => 'Financement du d&eacute;veloppement (fonds propres)',
	'option2duration' => 'Dur&eacute;e du financement (en ann&eacute;es) : ',
	'option3' => 'Don avec contrepartie'
    ), $atts );
    
    $fundingproject = '';
    $fundingdevelopment = '';
    $fundingdonation = '';
    $hide_duration = ' style="display:none;"';
    if (isset($_POST['fundingtype'])) {
	switch($_POST['fundingtype']) {
	    case 'fundingdevelopment': $fundingdevelopment = ' checked="checked"'; $hide_duration = ''; break;
	    case 'fundingdonation': $fundingdonation = ' checked="checked"'; break;
	    default: $fundingproject = ' checked="checked"'; $hide_duration = ''; break;
	}
    }
    
    $fundingduration = '';
    if (isset($_POST['fundingduration'])) $fundingduration = $_POST['fundingduration'];
    
    return  '<input type="radio" name="fundingtype" class="radiofundingtype" id="fundingproject" value="fundingproject"'.$fundingproject.'>' . $atts['option1'] . '<br />
	    <input type="radio" name="fundingtype" class="radiofundingtype" id="fundingdevelopment" value="fundingdevelopment"'.$fundingdevelopment.'>' . $atts['option2'] . '<br />
	    <span id="fundingdevelopment_param"'.$hide_duration.'>' . $atts['option2duration'] . '<input type="text" name="fundingduration" value="'.$fundingduration.'"></span>';
}
add_shortcode('yproject_crowdfunding_field_fundingtype', 'ypcf_shortcode_submit_field_fundingtype');

/**
 * Gestion de la partie somme à récolter
 * @param type $atts
 * @param type $content
 * @return type
 */
function ypcf_shortcode_submit_field_goal($atts, $content = '') {
    $atts = shortcode_atts( array(
	'option1' => 'Somme fixe',
	'option2' => 'Fourchette',
	'multiplier_tax' => '1.196',
	'option1_search' => 'Montant recherch&eacute;',
	'option1_campaign' => 'Montant de la collecte',
	'multiplier_campaign' => '1.1',
	'min_amount_project' => '500',
	'min_amount_development' => '5000',
	'min_amount_donation' => '100'
    ), $atts );
    $minimum_goal = isset($_POST['minimum_goal']) ? $_POST['minimum_goal'] : '';
    $minimum_goal_search = isset($_POST['minimum_goal_search']) ? $_POST['minimum_goal_search'] : '';
    $maximum_goal = isset($_POST['maximum_goal']) ? $_POST['maximum_goal'] : '';
    $maximum_goal_search = isset($_POST['maximum_goal_search']) ? $_POST['maximum_goal_search'] : '';
    
    $minimum_amount = $atts['min_amount_project'];
    if (isset($_POST['fundingtype']) && $_POST['fundingtype'] == 'fundingdevelopment') {
	$minimum_amount = $atts['min_amount_development'];
    }
    
    return  '<input type="hidden" name="goalsum" id="goalsum_flexible" value="flexible">' . $atts['option2'] . '
		<span id="goalsum_flexible_param">- Minimum : <input type="text" id="minimum_goal_search" name="minimum_goal_search" size="10" value="'.$minimum_goal_search.'"> (Min. <span class="min_amount_value">'.$minimum_amount.'</span>)<br />
		- Maximum : <input type="text" id="maximum_goal_search" name="maximum_goal_search" size="10" value="'.$maximum_goal_search.'"></span><br />
		- ' . $atts['option1_campaign'] . ' entre <span id="goalsum_min_campaign_multi">'.$minimum_goal.'&euro;</span> et <span id="goalsum_max_campaign_multi">'.$maximum_goal.'&euro;</span>
	    <input type="hidden" name="length" id="length" value="90">
	    <input type="hidden" name="vote_length" id="vote_length" value="9">
	    <input type="hidden" name="monney" id="monney" value="&euro;">
	    <input type="hidden" name="campaign_multiplier" id="campaign_multiplier" value="' . $atts['multiplier_campaign'] . '">
	    <input type="hidden" name="min_amount_project" id="min_amount_project" value="' . $atts['min_amount_project'] . '">
	    <input type="hidden" name="min_amount_development" id="min_amount_development" value="' . $atts['min_amount_development'] . '">
	    <input type="hidden" name="min_amount_donation" id="min_amount_donation" value="' . $atts['min_amount_donation'] . '">
	    <input type="hidden" name="minimum_goal" id="minimum_goal" value="'.$minimum_goal.'">
	    <input type="hidden" name="maximum_goal" id="maximum_goal" value="'.$maximum_goal.'">';
}
add_shortcode('yproject_crowdfunding_field_goal', 'ypcf_shortcode_submit_field_goal');

function ypcf_shortcode_submit_field_confirm($atts, $content = '') {
    ob_start();
    edd_agree_to_terms_js();
    edd_terms_agreement();
    return ob_get_clean();
}
add_shortcode('yproject_crowdfunding_field_confirm', 'ypcf_shortcode_submit_field_confirm');




/**
 * Success Message
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_before_success() {
	echo '<div class="errors padder_more">';
	global $submit_errors;
	if (isset($submit_errors)) {
		foreach ($submit_errors->errors as $submit_error) {
			if (isset($submit_error[0])) echo $submit_error[0] . '<br />';
		}
	}
	echo '</div>';
	    
	if ( ! isset ( $_GET[ 'success' ] ) )
		return;

	$message = apply_filters( 'atcf_shortcode_submit_success', 'Votre proposition a &eacute;t&eacute; soumise avec succ&egrave;s, nous vous recontactons bient&ocirc;t.' );
?>
	<p class="edd_success"><?php echo esc_attr( $message ); ?></p>	
<?php
}
add_action( 'atcf_shortcode_submit_before', 'atcf_shortcode_submit_before_success', 1 );

/**
 * Process shortcode submission.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_process() {
	global $edd_options, $post;
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'atcf-campaign-submit' !== $_POST[ 'action' ] ) )
		return;
	
	if (!is_user_logged_in())
	    return;

	if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'atcf-campaign-submit' ) )
		return;

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	}

	$errors           = new WP_Error();
	$edd_files        = array();
	$upload_overrides = array( 'test_form' => false );

	$terms     	= isset ( $_POST[ 'edd_agree_to_terms' ] ) ? $_POST[ 'edd_agree_to_terms' ] : 0;
	if (isset($_POST[ 'title' ]))		$title		= $_POST[ 'title' ];
	if (isset($_POST[ 'subtitle' ]))	$subtitle	= $_POST[ 'subtitle' ];
	if (isset($_POST[ 'summary' ]))		$summary     	= $_POST[ 'summary' ];
	if (isset($_POST[ 'owner' ]))		$owner		= $_POST[ 'owner' ];
        if (isset($_POST[ 'phone' ]))		$phone		= $_POST[ 'phone' ];
	if (isset($_POST[ 'location' ]))	$location	= $_POST[ 'location' ];
	if (isset($_POST[ 'impact_area' ]))	$impact_area	= $_POST[ 'impact_area' ];
	if (isset($_POST[ 'goalsum' ]))		$goalsum	= $_POST[ 'goalsum' ]; // "fixe" ou "flexible"
	if (isset($_POST[ 'goal' ]))		$goal		= $_POST[ 'maximum_goal' ];
	if (isset($_POST[ 'minimum_goal' ]))	$minimum_goal   = $_POST[ 'minimum_goal' ];
	if (isset($_POST[ 'maximum_goal' ]))	$maximum_goal   = $_POST[ 'maximum_goal' ];
	if (isset($_POST[ 'length' ]))		$length    	= $_POST[ 'length' ];
	if (isset($_POST[ 'vote_length' ]))	$vote_length    = $_POST[ 'vote_length' ];
	if (isset($_POST[ 'campaign_type' ]))	$type      	= $_POST[ 'campaign_type' ];
	if (isset($_POST[ 'categories' ]))	$category  	= isset ( $_POST[ 'categories' ] ) ? $_POST[ 'categories' ] : 0;
	if (isset($_POST[ 'activities' ]))	$activity  	= isset ( $_POST[ 'activities' ] ) ? $_POST[ 'activities' ] : 0;
	if (isset($_POST[ 'excerpt' ]))		$excerpt   	= $_POST[ 'excerpt' ];
	
	if (isset($_POST[ 'description' ]))	$content   	= html_entity_decode($_POST[ 'description' ]);
	if (isset($_POST[ 'societal_challenge' ]))  $societal_challenge	= html_entity_decode($_POST[ 'societal_challenge' ]);
	if (isset($_POST[ 'added_value' ]))	$added_value	= html_entity_decode($_POST[ 'added_value' ]);
	if (isset($_POST[ 'economic_model' ]))	$economic_model	= html_entity_decode($_POST[ 'economic_model' ]);
	if (isset($_POST[ 'implementation' ]))	$implementation	= html_entity_decode($_POST[ 'implementation' ]);
	
	if (isset($_POST[ 'init_capital' ]))	$init_capital	= $_POST[ 'init_capital' ];
	if (isset($_POST[ 'fundingtype' ]))	$fundingtype = $_POST['fundingtype']; // fundingproject ou fundingdevelopment
	if (isset($_POST[ 'fundingduration' ]))	$fundingduration = $_POST['fundingduration'];
	switch ($fundingtype) {
	    case "fundingproject":
		if (isset($_POST[ 'min_amount_project' ]))	$min_amount_collect = $_POST['min_amount_project'];
		break;
	    case "fundingdevelopment":
		if (isset($_POST[ 'min_amount_development' ]))	$min_amount_collect = $_POST['min_amount_development'];
		break;
	}

	
	if (isset($_POST[ 'name' ]))		$author    	= $_POST[ 'name' ];
	if (isset($_POST[ 'shipping' ]))	$shipping  	= $_POST[ 'shipping' ];

	if (isset($_FILES[ 'image' ]))		$image     = $_FILES[ 'image' ];
	if (isset($_FILES[ 'image_home' ]))	$image_home     = $_FILES[ 'image_home' ];
	if (isset($_POST[ 'video' ]))		$video     = $_POST[ 'video' ];
	if (isset($_FILES[ 'files' ]))		$files     = $_FILES[ 'files' ];
	
	
	if ( isset ( $_POST[ 'contact-email' ] ) )
		$c_email = $_POST[ 'contact-email' ];
	else {
		$current_user = wp_get_current_user();
		$c_email = $current_user->user_email;
	}

	if ( isset( $edd_options[ 'show_agree_to_terms' ] ) && ! $terms )
		$errors->add( 'terms', 'Merci d&apos;accepter les conditions d&apos;utilisation' );

	/** Check Title */
	if ( empty( $title ) )
		$errors->add( 'invalid-title', 'Merci de pr&eacute;ciser le nom de ce projet.' );
        
        /** Check Phone */
	if ( isset($phone) && empty( $phone ) )
		$errors->add( 'invalid-phone', 'Merci de pr&eacute;ciser un num&eacute;ro de contact.' );

	/** Check Goal */
	
	switch ($goalsum) {
	    case "fixe":
		$goal = edd_sanitize_amount( $goal );
		if ( ! is_numeric( $goal ) )
		    $errors->add( 'invalid-goal', 'Le montant recherch&eacute; n&apos;est pas un nombre.' );
		if ($min_amount_collect > $goal) 
		    $errors->add( 'invalid-goal', 'Le montant recherch&eacute; est inférieur au minimum requis.' );
		break;
		
	    case "flexible":
		$minimum_goal = edd_sanitize_amount( $minimum_goal );
		$maximum_goal = edd_sanitize_amount( $maximum_goal );
		if ( ! is_numeric( $minimum_goal ) )
		    $errors->add( 'invalid-goal', 'Le montant minimum recherch&eacute; n&apos;est pas un nombre.' );
		if ( ! is_numeric( $maximum_goal ) )
		    $errors->add( 'invalid-goal', 'Le montant maximum recherch&eacute; n&apos;est pas un nombre.' );
		if ($minimum_goal >= $maximum_goal)
		    $errors->add( 'invalid-goal', 'Le montant minimum recherch&eacute; est plus grand que le montant maximum recherch&eacute;.' );
		if ($min_amount_collect > $minimum_goal) 
		    $errors->add( 'invalid-goal', 'Le montant recherch&eacute; est inférieur au minimum requis.' );
		
		$goal = $maximum_goal;
		break;
	} 

	
	/** Check Length */
	$length = absint( $length );

	$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
	$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 42;

	if ( $length < $min )
		$length = $min;
	else if ( $length > $max )
		$length = $max;

	$end_date = strtotime( sprintf( '+%d day', $length ) );
	$end_date = get_gmt_from_date( date( 'Y-m-d H:i:s', $end_date ) );

	/** Check vote Length */
	$vote_length = absint( $vote_length );

	$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
	$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 42;

	if ( $vote_length < $min )
		$vote_length = $min;
	else if ( $vote_length > $max )
		$vote_length = $max;

	$end_date_vote = strtotime( sprintf( '+%d day', $vote_length ) );
	$end_date_vote = get_gmt_from_date( date( 'Y-m-d H:i:s', $end_date_vote

 ) );

	/** Check Category */
	if (isset($category)) $category = absint( $category );
	if (isset($activity)) $activity = absint( $activity );

	/** Check Content */
//	if ( empty($content) || empty($summary) || empty($added_value)  || empty($economic_model) || empty($implementation) || empty($societal_challenge) )
//		$errors->add( 'invalid-content', 'Certains champs n&apos;ont pas &eacute;t&eacute; remplis.' );
	

	/** Check Excerpt */
	if ( empty( $excerpt ) )
		$excerpt = null;

	/** Check Image */
//	if ( empty( $image ) || empty($image_home) )
//		$errors->add( 'invalid-previews', 'Merci de proposer deux images pour votre projet.' );

	if ( ! isset ( $current_user ) )
		$errors->add( 'invalid-connection', 'Vous devez &ecirc;tre connect&eacute; pour proposer un projet.' );		

	do_action( 'atcf_campaign_submit_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) { // Not sure how to avoid empty instantiated WP_Error
//	    wp_die( $errors );
	    global $submit_errors;
	    $submit_errors = $errors;
	    
	} else {
	    if ( ! $type )
		    $type = atcf_campaign_type_default();

	    $user_id = $current_user->ID;

	    $args = apply_filters( 'atcf_campaign_submit_data', array(
		    'post_type'   		 	=> 'download',
		    'post_status'  		 	=> 'publish',
		    'post_title'   		 	=> $title,
		    'post_content' 		 	=> $content,
		    'post_excerpt' 			=> $excerpt,
		    'post_author'  			=> $user_id,

	    ), $_POST );

	    $campaign = wp_insert_post( $args, true );

	    if ($category != 0 && $activity != 0) {
		    wp_set_object_terms( $campaign, array( $category, $activity ), 'download_category' );
	    }


	    // Create category for blog
	    $id_category = wp_insert_category( array('cat_name' => 'cat'.$campaign, 'category_parent' => $parent, 'category_nicename' => sanitize_title($campaign . '-blog-' . $title)) );


	    // Create forum for campaign
	    $forum_post = array(
			'post_title'    => $campaign,
			'post_name'     => $campaign,
			'post_status'   => 'publish',
			'post_type'     => 'forum'
		);
 
	 	wp_insert_post( $forum_post, $wp_error ); 

	    // Extra Campaign Information
	    add_post_meta( $campaign, 'campaign_vote', 'preparing' );
            add_post_meta( $campaign, 'campaign_validated_next_step', 0);
	    add_post_meta( $campaign, 'campaign_goal', apply_filters( 'edd_metabox_save_edd_price', $goal ) );
	    add_post_meta( $campaign, 'campaign_minimum_goal', apply_filters( 'edd_metabox_save_edd_price', $minimum_goal ) );
	    add_post_meta( $campaign, 'campaign_part_value', 1 );
	    add_post_meta( $campaign, 'campaign_type', sanitize_text_field( $type ) );
	    add_post_meta( $campaign, 'campaign_owner', sanitize_text_field( $owner ) );
            add_post_meta( $campaign, 'campaign_contact_phone', $phone);
	    add_post_meta( $campaign, 'campaign_contact_email', sanitize_text_field( $c_email ) );
	    add_post_meta( $campaign, 'campaign_end_date', sanitize_text_field( $end_date ) );
            add_post_meta( $campaign, 'campaign_begin_collecte_date', sanitize_text_field( $end_date_vote ) );
	    add_post_meta( $campaign, 'campaign_end_vote', sanitize_text_field( $end_date_vote ) );
	    add_post_meta( $campaign, 'campaign_location', sanitize_text_field( $location ) );
	    add_post_meta( $campaign, 'campaign_author', sanitize_text_field( $author ) );
	    add_post_meta( $campaign, 'campaign_video', esc_url( $video ) );
	    add_post_meta( $campaign, '_campaign_physical', sanitize_text_field( $shipping ) );
	    add_post_meta( $campaign, 'campaign_summary', $summary);
	    add_post_meta( $campaign, 'campaign_subtitle', sanitize_text_field($subtitle));
	    add_post_meta( $campaign, 'campaign_impact_area', sanitize_text_field( $impact_area ) );
	    add_post_meta( $campaign, 'campaign_added_value', $added_value);
	    add_post_meta( $campaign, 'campaign_development_strategy', sanitize_text_field( $development_strategy ) );
	    add_post_meta( $campaign, 'campaign_economic_model', $economic_model);
	    add_post_meta( $campaign, 'campaign_measuring_impact', sanitize_text_field( $measuring_impact ) );
	    add_post_meta( $campaign, 'campaign_implementation', $implementation);
	    add_post_meta( $campaign, 'campaign_societal_challenge', $societal_challenge);
	    add_post_meta( $campaign, 'campaign_init_capital', sanitize_text_field( $init_capital ) );
	    add_post_meta( $campaign, 'campaign_funding_type', sanitize_text_field( $fundingtype ) );
	    add_post_meta( $campaign, 'campaign_funding_duration', sanitize_text_field( $fundingduration ) );




	    if ( ! empty( $files ) ) {
		    foreach ( $files[ 'name' ] as $key => $value ) {
			    if ( $files[ 'name' ][$key] ) {
				    $file = array(
					    'name'     => $files[ 'name' ][$key],
					    'type'     => $files[ 'type' ][$key],
					    'tmp_name' => $files[ 'tmp_name' ][$key],
					    'error'    => $files[ 'error' ][$key],
					    'size'     => $files[ 'size' ][$key]
				    );

				    $upload = wp_handle_upload( $file, $upload_overrides );

				    if ( isset( $upload[ 'url' ] ) )
					    $edd_files[$key]['file'] = $upload[ 'url' ];
				    else
					    unset($files[$key]);
			    }
		    }
	    }
	    if ( $image[ 'name' ] != '' ) {
		    $path = $_FILES['image']['name'];
		    $ext = pathinfo($path, PATHINFO_EXTENSION);

		    $upload = wp_handle_upload( $image, $upload_overrides );
		    $attachment = array(
			    'guid'           => $upload[ 'url' ], 
			    'post_mime_type' => $upload[ 'type' ],
			    'post_title'     => 'image_header',
			    'post_content'   => '',
			    'post_status'    => 'inherit'
		    );
		    $true_image=true;
		    switch ($ext) {
			case 'png':
			  $image=imagecreatefrompng($upload[ 'file' ]);
			  break;
			case 'jpg':
			  $image=imagecreatefromjpeg($upload[ 'file' ]);
			  break;
			default:
			  $true_image=false;
			  break;
		    }
		    if($true_image){
			for($i=0; $i<10 ; $i++){
			    imagefilter ($image, IMG_FILTER_GAUSSIAN_BLUR);
			    imagefilter ($image , IMG_FILTER_SELECTIVE_BLUR );
			}
			$fichier=explode('.',$upload[ 'file' ]);
			$img_name=$fichier[0].'_blur.'.'jpg';
			imagejpeg($image,$img_name);
			$attach_id = wp_insert_attachment( $attachment, $img_name, $campaign );   

			wp_update_attachment_metadata( 
			    $attach_id, 
			    wp_generate_attachment_metadata( $attach_id, $img_name ) 
			);
		    }

		    add_post_meta( $campaign, '_thumbnail_id', absint( $attach_id ) );
	    }
	    if ( $image_home[ 'name' ] != '' ) {
		    $upload = wp_handle_upload( $image_home, $upload_overrides );
		    $attachment = array(
			    'guid'           => $upload[ 'url' ], 
			    'post_mime_type' => $upload[ 'type' ],
			    'post_title'     => 'image_home',
			    'post_content'   => '',
			    'post_status'    => 'inherit'
		    );
		    $attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign );		

		    wp_update_attachment_metadata( 
			    $attach_id, 
			    wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
		    );
	    }
	    

	    // EDD Stuff 
	    add_post_meta( $campaign, '_variable_pricing', 0 );
	    add_post_meta( $campaign, '_edd_price_options_mode', 1 );
	    add_post_meta( $campaign, '_edd_hide_purchase_link', 'on' );

	    $prices = array(1);
	    add_post_meta( $campaign, 'edd_variable_prices', $prices );

	    if ( ! empty( $files ) ) {
		    add_post_meta( $campaign, 'edd_download_files', $edd_files );
	    }

	    do_action( 'atcf_submit_process_after', $campaign, $_POST );
	    
	    $copy_recipient = '';
	    if (!WP_IS_DEV_SITE) $copy_recipient = 'communication@wedogood.co';
	    NotificationsEmails::new_project_posted($campaign, $copy_recipient);

	    $url = isset ( $edd_options[ 'submit_page' ] ) ? get_permalink( $edd_options[ 'submit_page' ] ) : get_permalink();

            
            $page_dashboard = get_page_by_path('tableau-de-bord');
            $campaign_id_param = '?campaign_id=';
            $campaign_id_param .= $campaign;
            
	    $redirect_url = get_permalink($page_dashboard->ID) . $campaign_id_param;
	    wp_safe_redirect( $redirect_url );
	    exit();
	}

}
add_action( 'template_redirect', 'atcf_shortcode_submit_process' );

/**
 * Redirect submit page if needed.
 *
 * @since Appthemer CrowdFunding 1.1
 *
 * @return void
 */
function atcf_shortcode_submit_redirect() {
	global $edd_options, $post;

	if ( ! is_a( $post, 'WP_Post' ) )
		return;

	if ( ! is_user_logged_in() && ( !empty($edd_options[ 'submit_page' ]) && $post->ID == $edd_options[ 'submit_page' ] ) && isset ( $edd_options[ 'atcf_settings_require_account' ] ) ) {
		$redirect = apply_filters( 'atcf_require_account_redirect', isset ( $edd_options[ 'login_page' ] ) ? get_permalink( $edd_options[ 'login_page' ] ) : home_url() );
			
		wp_safe_redirect( $redirect );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_shortcode_submit_redirect', 1 );