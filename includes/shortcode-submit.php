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
/*********************************************************************************************/
/**
 * Base page/form. All fields are loaded through an action,
 * so the form can be extended for ever, fields can be removed, added, etc.
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return $form
 */
function ypcf_shortcode_submit_start( $is_editing = false ) {
    global $edd_options, $current_campaign, $editing;

    if (is_user_logged_in()) {
	$crowdfunding = crowdfunding();
	$current_campaign = null;
	$editing = $is_editing;

	if ( $editing ) {
		global $post;
		$current_campaign = atcf_get_campaign( $post );
	} else {
		wp_enqueue_script( 'jquery-validation', EDD_PLUGIN_URL . 'assets/js/jquery.validate.min.js');
		wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery', 'jquery-validation' ) );

		wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
			'oneReward' => __( 'At least one reward is required.', 'atcf' )
		) );
	}

	do_action( 'atcf_shortcode_submit_before', $editing, $current_campaign );
	return '<form action="" method="post" class="atcf-submit-campaign" enctype="multipart/form-data">';
    } else {
	$page_connexion = get_page_by_path('connexion');
	return 'Attention : <a href="'.get_permalink($page_connexion->ID).'">Vous devez &ecirc;tre connect&eacute; pour proposer un projet</a>';
    }

}
add_shortcode( 'yproject_crowdfunding_submit_start', 'ypcf_shortcode_submit_start' );


function ypcf_shortcode_submit_end() {
    global $edd_options, $current_campaign, $editing;

    if (is_user_logged_in()) {
	$crowdfunding = crowdfunding();

	return '	<p class="atcf-submit-campaign-submit">
			<input type="submit" value="'. ($editing ? sprintf( _x( 'Update %s', 'edit "campaign"', 'atcf' ), edd_get_label_singular() ) : sprintf( _x( 'Submit %s', 'submit "campaign"', 'atcf' ), edd_get_label_singular() )) .'">
			<input type="hidden" name="action" value="atcf-campaign-'. ($editing ? 'edit' : 'submit') .'" />
			'.wp_nonce_field( 'atcf-campaign-' . ( $editing ? 'edit' : 'submit' ), '_wpnonce', true, false ).'
		</p>

	</form>';
    } else {
	$page_connexion = get_page_by_path('connexion');
	return 'Attention : <a href="'.get_permalink($page_connexion->ID).'">Vous devez &ecirc;tre connect&eacute; pour proposer un projet</a>';
    }    
}
add_shortcode( 'yproject_crowdfunding_submit_end', 'ypcf_shortcode_submit_end' );


function ypcf_shortcode_submit_field($atts, $content = '') {
    global $editing;
    $atts = shortcode_atts( array(
	'name' => 'title',
	'rows' => 5,
	'cols' => 50
    ), $atts );
    return '<textarea name="'.$atts['name'].'" id="'.$atts['name'].'" rows="'.$atts['rows'].'" cols="'.$atts['cols'].'" placeholder="'. ($editing ? apply_filters( 'get_summary', $campaign->data->post_summary ) : $content) .'"></textarea>';
}
add_shortcode('yproject_crowdfunding_field', 'ypcf_shortcode_submit_field');

function ypcf_shortcode_submit_field_category($atts, $content = '') {
    global $editing, $current_campaign;
    $atts = shortcode_atts( array(
	'type' => 'general'
    ), $atts );
    
    $parent_cat_id = get_category_by_path($atts['type']);
    return wp_dropdown_categories( array( 
	    'orderby'	    => 'name', 
	    'hide_empty'    => 0,
	    'taxonomy'	    => 'category',
	    'selected'	    => 0,
	    'echo'	    => 0,
	    'child_of'	    => $parent_cat_id->cat_ID,
	    'name'	    => $atts['type']
    ) );
}
add_shortcode('yproject_crowdfunding_field_category', 'ypcf_shortcode_submit_field_category');

function ypcf_shortcode_submit_field_file($atts, $content = '') {
    $atts = shortcode_atts( array(
	'name' => 'image'
    ), $atts );
    return '<input type="file" name="'.$atts['name'].'" id="'.$atts['name'].'" />';
}
add_shortcode('yproject_crowdfunding_field_file', 'ypcf_shortcode_submit_field_file');

function ypcf_shortcode_submit_field_complex($atts, $content = '') {
    global $editing, $current_campaign;
    $atts = shortcode_atts( array(
	'name' => 'description',
	'width' => '350px',
	'height' => '150px'
    ), $atts );
    
    ob_start();
    $text_to_edit = '';
    if ($editing) {
	switch ($atts['name']) {
	    case 'description':
		$text_to_edit = $current_campaign->data->post_content;
		break;
	    case 'added_value': 		
		$text_to_edit = $current_campaign->added_value(); 		
		break; 		
	    case 'societal_challenge': 		
		$text_to_edit = $current_campaign->societal_challenge(); 		
		break; 		
	    case 'economic_model': 		
		$text_to_edit = $current_campaign->economic_model(); 		
		break; 		
	    case 'implementation': 		
		$text_to_edit = $current_campaign->implementation(); 		
		break;
	}
    }
    wp_editor( 
	    $editing ? wp_richedit_pre($text_to_edit) : wp_richedit_pre($content), 
	    $atts['name'], 
	    apply_filters(  
		'atcf_submit_field_'.$atts['name'].'_editor_args', 
		array( 
		    'media_buttons' => true,
		    'teeny'         => true,
		    'quicktags'     => false,
		    'editor_css'    => '<style>body { background: white; } .wp-editor-container {width:'.$atts['width'].'; height:'.$atts['height'].';} .wp-editor-area {width:'.$atts['width'].'; height:'.$atts['height'].';}</style>',
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
    return ob_get_clean();
}
add_shortcode('yproject_crowdfunding_field_complex', 'ypcf_shortcode_submit_field_complex');

function ypcf_shortcode_submit_field_location($atts, $content = '') {
    return '<select id="location" name="location">
	<option>Alsace</option>
	<option>Aquitaine</option>
	<option>Auvergne</option>
	<option>Basse-Normandie</option>
	<option>Bourgogne</option>
	<option>Bretagne</option>
	<option>Centre</option>
	<option>Champagne-Ardenne</option>
	<option>Bourgogne</option>
	<option>Corse</option>
	<option>Franche-Comt&eacute;</option>
	<option>Guadeloupe</option>
	<option>Guyane</option>
	<option>Haute-Normandie</option>
	<option>&Icirc;le-de-France</option>
	<option>La R&eacute;union</option>
	<option>Languedoc-Roussillon</option>
	<option>Limousin</option>
	<option>Lorraine</option>
	<option>Martinique</option>
	<option>Mayotte</option>
	<option>Midi-Pyr&eacute;n&eacute;es</option>
	<option>Nord-Pas-de-Calais</option>
	<option>Pays de la Loire</option>
	<option>Picardie</option>
	<option>Poitou-Charentes</option>
	<option>Provence-Alpes-C&ocirc;te d&apos;Azur</option>
	<option>Rh&ocirc;ne-Alpes</option>
    </select>';
}
add_shortcode('yproject_crowdfunding_field_location', 'ypcf_shortcode_submit_field_location');

function ypcf_shortcode_submit_field_fundingtype($atts, $content = '') {
    $atts = shortcode_atts( array(
	'option1' => 'Financement d&apos;un projet',
	'option2' => 'Financement du d&eacute;veloppement (fonds propres)',
	'option2duration' => 'Dur&eacute;e du financement (en ann&eacute;es) : '
    ), $atts );
    return  '<input type="radio" name="fundingtype" class="radiofundingtype" id="fundingproject" value="fundingproject" checked="checked">' . $atts['option1'] . '<br />
	    <input type="radio" name="fundingtype" class="radiofundingtype" id="fundingdevelopment" value="fundingdevelopment">' . $atts['option2'] . '
		<span id="fundingdevelopment_param" style="display: none">- ' . $atts['option2duration'] . '<input type="text" name="fundingduration"></span>';
}
add_shortcode('yproject_crowdfunding_field_fundingtype', 'ypcf_shortcode_submit_field_fundingtype');

function ypcf_shortcode_submit_field_goal($atts, $content = '') {
    $atts = shortcode_atts( array(
	'option1' => 'Somme fixe',
	'option2' => 'Fourchette',
	'multiplier_tax' => '1.196',
	'option1_search' => 'Montant recherch&eacute;',
	'option1_campaign' => 'Montant de la collecte',
	'multiplier_campaign' => '1.1',
	'min_amount_project' => '500',
	'min_amount_development' => '5000'
    ), $atts );
    return  '<input type="radio" name="goalsum" id="goalsum_fixe" value="fixe" checked="checked">' . $atts['option1'] . '
		<span id="goalsum_fixe_param">- ' . $atts['option1_search'] . '<input type="text" id="goal_search" name="goal_search" size="10"> (Min. <span class="min_amount_value">'.$atts['min_amount_project'].'</span>) - ' . $atts['option1_campaign'] . ' <span id="goalsum_campaign_multi"></span></span><br />
	    <input type="radio" name="goalsum" id="goalsum_flexible" value="flexible">' . $atts['option2'] . '
		<span id="goalsum_flexible_param" style="display:none">- Minimum : <input type="text" id="minimum_goal" name="minimum_goal" size="10"> (Min. <span class="min_amount_value">'.$atts['min_amount_project'].'</span>)
		- Maximum : <input type="text" id="maximum_goal" name="maximum_goal" size="10"></span>
	    <input type="hidden" name="length" id="length" value="90">
	    <input type="hidden" name="monney" id="monney" value="&euro;">
	    <input type="hidden" name="campaign_multiplier" id="campaign_multiplier" value="' . $atts['multiplier_campaign'] . '">
	    <input type="hidden" name="min_amount_project" id="min_amount_project" value="' . $atts['min_amount_project'] . '">
	    <input type="hidden" name="min_amount_development" id="min_amount_development" value="' . $atts['min_amount_development'] . '">
	    <input type="hidden" name="goal" id="goal">';
}
add_shortcode('yproject_crowdfunding_field_goal', 'ypcf_shortcode_submit_field_goal');

function ypcf_shortcode_submit_field_length($atts, $content = '') {
    $atts = shortcode_atts( array(
	'min' => '15',
	'max' => '90'
    ), $atts );
    return '<input type="number" min="'.$atts['min'].'" max="'.$atts['max'].'" step="1" name="length" id="length" value="'.$atts['min'].'">';
}
add_shortcode('yproject_crowdfunding_field_length', 'ypcf_shortcode_submit_field_length');

function ypcf_shortcode_submit_field_status($atts, $content = '') {
    $atts = shortcode_atts( array(
	'other_text' => 'Pr&eacute;cisez :'
    ), $atts );
    $buffer = '<select id="company_status" name="company_status">';
    $buffer .= '<option>SARL</option>
	<option>SAS</option>
	<option>SA</option>
	<option>SCA</option>
	<option>Autre</option>';
    $buffer .= '</select>';
    $buffer .= '<span id="company_status_other_zone" style="display:none">'.$atts['other_text'].'<input type="text" name="company_status_other"></span>';
    return $buffer;
}
add_shortcode('yproject_crowdfunding_field_status', 'ypcf_shortcode_submit_field_status');

function ypcf_shortcode_submit_field_init_capital($atts, $content = '') {
    return '<input type="text" name="init_capital" size="10">';
}
add_shortcode('yproject_crowdfunding_field_init_capital', 'ypcf_shortcode_submit_field_init_capital');

function ypcf_shortcode_submit_field_confirm($atts, $content = '') {
    ob_start();
    edd_agree_to_terms_js();
    edd_terms_agreement();
    return ob_get_clean();
}
add_shortcode('yproject_crowdfunding_field_confirm', 'ypcf_shortcode_submit_field_confirm');

/*********************************************************************************************/
/* END FORM GENERATED WITH SHORTCODES */
/*********************************************************************************************/
/*********************************************************************************************/




/**
 * Success Message
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_before_success() {
	if ( ! isset ( $_GET[ 'success' ] ) )
		return;

	$message = apply_filters( 'atcf_shortcode_submit_success', __( 'Success! Your campaign has been received. It will be reviewed shortly.', 'atcf' ) );
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
	if (isset($_POST[ 'summary' ]))		$summary     	= $_POST[ 'summary' ];
	if (isset($_POST[ 'owner' ]))		$owner		= $_POST[ 'owner' ];
	if (isset($_POST[ 'location' ]))	$location		= $_POST[ 'location' ];
	if (isset($_POST[ 'impact_area' ]))	$impact_area	= $_POST[ 'impact_area' ];
	if (isset($_POST[ 'goalsum' ]))		$goalsum		= $_POST[ 'goalsum' ]; // "fixe" ou "flexible"
	if (isset($_POST[ 'goal' ]))		$goal		= $_POST[ 'goal' ];
	if (isset($_POST[ 'minimum_goal' ]))	$minimum_goal   = $_POST[ 'minimum_goal' ];
	if (isset($_POST[ 'maximum_goal' ]))	$maximum_goal   = $_POST[ 'maximum_goal' ];
	if (isset($_POST[ 'length' ]))		$length    	= $_POST[ 'length' ];
	if (isset($_POST[ 'campaign_type' ]))	$type      	= $_POST[ 'campaign_type' ];
	if (isset($_POST[ 'general' ]))		$category  	= isset ( $_POST[ 'general' ] ) ? $_POST[ 'general' ] : 0;
	if (isset($_POST[ 'activity' ]))	$activity  	= isset ( $_POST[ 'activity' ] ) ? $_POST[ 'activity' ] : 0;
	if (isset($_POST[ 'description' ]))	$content   	= $_POST[ 'description' ];
	if (isset($_POST[ 'excerpt' ]))		$excerpt   	= $_POST[ 'excerpt' ];
	if (isset($_POST[ 'societal_challenge' ]))  $societal_challenge	= $_POST[ 'societal_challenge' ];
	if (isset($_POST[ 'added_value' ]))	$added_value	= $_POST[ 'added_value' ];
	if (isset($_POST[ 'company_status' ]))	$company_status	= $_POST[ 'company_status' ];
	if (isset($_POST[ 'company_status_other' ]))	$company_status_other	= $_POST[ 'company_status_other' ];
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

	if (isset($_POST[ 'development_strategy' ]))	$development_strategy	= $_POST[ 'development_strategy' ];
	if (isset($_POST[ 'economic_model' ]))	$economic_model	    = $_POST[ 'economic_model' ];
	if (isset($_POST[ 'measuring_impact' ]))$measuring_impact   = $_POST[ 'measuring_impact' ];
	if (isset($_POST[ 'implementation' ]))	$implementation	    = $_POST[ 'implementation' ];
	
	if (isset($_POST[ 'name' ]))		$author    	= $_POST[ 'name' ];
	if (isset($_POST[ 'shipping' ]))	$shipping  	= $_POST[ 'shipping' ];

	if (isset($_FILES[ 'image' ]))		$image     = $_FILES[ 'image' ];
	if (isset($_POST[ 'video' ]))		$video     = $_POST[ 'video' ];
	if (isset($_FILES[ 'files' ]))		$files     = $_FILES[ 'files' ];
	
	
	if ( isset ( $_POST[ 'contact-email' ] ) )
		$c_email = $_POST[ 'contact-email' ];
	else {
		$current_user = wp_get_current_user();
		$c_email = $current_user->user_email;
	}

	if ( isset( $edd_options[ 'show_agree_to_terms' ] ) && ! $terms )
		$errors->add( 'terms', __( 'Please agree to the Terms and Conditions', 'atcf' ) );

	/** Check Title */
	if ( empty( $title ) )
		$errors->add( 'invalid-title', __( 'Please add a title to this campaign.', 'atcf' ) );

	/** Check Goal */
	
	switch ($goalsum) {
	    case "fixe":
		$goal = edd_sanitize_amount( $goal );
		if ( ! is_numeric( $goal ) )
		    $errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );
		if ($min_amount_collect > $goal) 
		    $errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );
		break;
		
	    case "flexible":
		$minimum_goal = edd_sanitize_amount( $minimum_goal );
		$maximum_goal = edd_sanitize_amount( $maximum_goal );
		if ( ! is_numeric( $minimum_goal ) )
		    $errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );
		if ( ! is_numeric( $maximum_goal ) )
		    $errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );
		if ($minimum_goal >= $maximum_goal)
		    $errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );
		if ($min_amount_collect > $minimum_goal) 
		    $errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );
		
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

	/** Check Category */
	if (isset($category)) $category = absint( $category );
	if (isset($activity)) $activity = absint( $activity );

	/** Check Content */
	if ( empty($content) || empty($summary) || empty($impact_area) || empty($added_value) || empty($development_strategy) || empty($economic_model) || empty($measuring_impact) || empty($implementation) || empty($societal_challenge) )
		$errors->add( 'invalid-content', __( 'Please add content to this campaign.', 'atcf' ) );
	

	/** Check Excerpt */
	if ( empty( $excerpt ) )
		$excerpt = null;

	/** Check Image */
	if ( empty( $image ) )
		$errors->add( 'invalid-previews', __( 'Please add a campaign image.', 'atcf' ) );

	/** Check Rewards */
	/* if ( empty( $rewards ) )
		$errors->add( 'invalid-rewards', __( 'Please add at least one reward to the campaign.', 'atcf' ) ); */

	if ( email_exists( $c_email ) && ! isset ( $current_user ) )
		$errors->add( 'invalid-c-email', __( 'That contact email address already exists.', 'atcf' ) );		

	do_action( 'atcf_campaign_submit_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) { // Not sure how to avoid empty instantiated WP_Error
	    wp_die( $errors );
	    
	} else {
	    if ( ! $type )
		    $type = atcf_campaign_type_default();

	    $user_id = $current_user->ID;

	    $args = apply_filters( 'atcf_campaign_submit_data', array(
		    'post_type'   		 	=> 'download',
		    'post_status'  		 	=> 'pending',
		    'post_title'   		 	=> $title,
		    'post_content' 		 	=> $content,
		    'post_excerpt' 			=> $excerpt,
		    'post_author'  			=> $user_id,

	    ), $_POST );

	    $campaign = wp_insert_post( $args, true );

	    wp_set_object_terms( $campaign, array( $category ), 'category' );
	    wp_set_object_terms( $campaign, array( $activity ), 'category' );


	    // Create category for blog
	    $id_category = wp_insert_category( array('cat_name' => 'cat'.$campaign, 'category_parent' => $parent, 'category_nicename' => sanitize_title($campaign . '-blog-' . $title)) );



	    // Extra Campaign Information
	    add_post_meta( $campaign, 'campaign_goal', apply_filters( 'edd_metabox_save_edd_price', $goal ) );
	    add_post_meta( $campaign, 'campaign_minimum_goal', apply_filters( 'edd_metabox_save_edd_price', $minimum_goal ) );
	    add_post_meta( $campaign, 'campaign_type', sanitize_text_field( $type ) );
	    add_post_meta( $campaign, 'campaign_owner', sanitize_text_field( $owner ) );
	    add_post_meta( $campaign, 'campaign_contact_email', sanitize_text_field( $c_email ) );
	    add_post_meta( $campaign, 'campaign_end_date', sanitize_text_field( $end_date ) );
	    add_post_meta( $campaign, 'campaign_location', sanitize_text_field( $location ) );
	    add_post_meta( $campaign, 'campaign_author', sanitize_text_field( $author ) );
	    add_post_meta( $campaign, 'campaign_video', esc_url( $video ) );
	    add_post_meta( $campaign, '_campaign_physical', sanitize_text_field( $shipping ) );
	    add_post_meta( $campaign, 'campaign_summary', sanitize_text_field( $summary ) );
	    add_post_meta( $campaign, 'campaign_impact_area', sanitize_text_field( $impact_area ) );
	    add_post_meta( $campaign, 'campaign_added_value', sanitize_text_field( $added_value ) );
	    add_post_meta( $campaign, 'campaign_development_strategy', sanitize_text_field( $development_strategy ) );
	    add_post_meta( $campaign, 'campaign_economic_model', sanitize_text_field( $economic_model ) );
	    add_post_meta( $campaign, 'campaign_measuring_impact', sanitize_text_field( $measuring_impact ) );
	    add_post_meta( $campaign, 'campaign_implementation', sanitize_text_field( $implementation ) );
	    add_post_meta( $campaign, 'campaign_societal_challenge', sanitize_text_field( $societal_challenge ) );
	    add_post_meta( $campaign, 'campaign_company_status', sanitize_text_field( $company_status ) );
	    add_post_meta( $campaign, 'campaign_company_status_other', sanitize_text_field( $company_status_other ) );
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

	    if ( '' != $image[ 'name' ] ) {
		    $upload = wp_handle_upload( $image, $upload_overrides );
		    $attachment = array(
			    'guid'           => $upload[ 'url' ], 
			    'post_mime_type' => $upload[ 'type' ],
			    'post_title'     => $upload[ 'file' ],
			    'post_content' => '',
			    'post_status' => 'inherit'
		    );

		    $attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign );		

		    wp_update_attachment_metadata( 
			    $attach_id, 
			    wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
		    );

		    add_post_meta( $campaign, '_thumbnail_id', absint( $attach_id ) );
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

	    $url = isset ( $edd_options[ 'submit_page' ] ) ? get_permalink( $edd_options[ 'submit_page' ] ) : get_permalink();

	    $redirect = apply_filters( 'atcf_submit_campaign_success_redirect', add_query_arg( array( 'success' => 'true' ), $url ) );
	    wp_safe_redirect( $redirect );
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