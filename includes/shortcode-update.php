<?php


/**
 * Vote Shortcode.
 *
 * [appthemer_crowdfunding_update] creates a upadate form.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Base page/form. All fields are loaded through an action,
 * so the form can be extended for ever, fields can be removed, added, etc.
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return $form
 */

 
 function atcf_shortcode_update( $editing = false ) {
	global $campaign, $post, $edd_options;
	
	// La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
	$current_user = wp_get_current_user();
	$current_user_id = $current_user->ID;
	$author_id = get_the_author_meta('ID');
	if ($current_user_id == $author_id || current_user_can('manage_options')) {

	    $crowdfunding = crowdfunding();

	    $post = get_post($_GET['campaign_id']);
	    $campaign = atcf_get_campaign( $post );
	
	    if (isset($_POST['action']) && $_POST['action'] == 'atcf-campaign-submit') {
		$post_update = array();
		$post_update['ID'] = $campaign->ID;
		$post_update['post_content'] = $_POST['description'];
		wp_update_post($post_update);
		
		update_post_meta($campaign->ID, 'campaign_impact_area', sanitize_text_field($_POST['impact_area']));
		update_post_meta($campaign->ID, 'campaign_summary', sanitize_text_field($_POST['summary']));
		update_post_meta($campaign->ID, 'campaign_societal_challenge', sanitize_text_field($_POST['societal_challenge']));
		update_post_meta($campaign->ID, 'campaign_added_value', sanitize_text_field($_POST['added_value']));
		update_post_meta($campaign->ID, 'campaign_economic_model', sanitize_text_field($_POST['economic_model']));
		update_post_meta($campaign->ID, 'campaign_implementation', sanitize_text_field($_POST['implementation']));
		update_post_meta($campaign->ID, 'campaign_video', esc_url($_POST['video']));
		
		/* Gestion fichiers / images */
		$image	    = $_FILES[ 'image' ];
		if (!empty($image)) {
		    if (isset($_FILES[ 'files' ])) $files = $_FILES[ 'files' ];
		    $edd_files  = array();
		    $upload_overrides = array( 'test_form' => false );
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
		    
		    $upload = wp_handle_upload( $image, $upload_overrides );
		    if (isset($upload[ 'url' ])) {
			$attachment = array(
				'guid'           => $upload[ 'url' ], 
				'post_mime_type' => $upload[ 'type' ],
				'post_title'     => $upload[ 'file' ],
				'post_content' => '',
				'post_status' => 'inherit'
			);

			$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign->ID );		

			wp_update_attachment_metadata( 
				$attach_id, 
				wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
			);

			add_post_meta( $campaign->ID, '_thumbnail_id', absint( $attach_id ) );
		    }
		    
		}
		/* FIN Gestion fichiers / images */
		
		//Re-select des données en cas de modification
		$post = get_post($_GET['campaign_id']);
		$campaign = atcf_get_campaign( $post );
	    }

	    ob_start();

	    wp_enqueue_script( 'jquery-validation', EDD_PLUGIN_URL . 'assets/js/jquery.validate.min.js');
	    wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery', 'jquery-validation' ) );

	    wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
		    'oneReward' => __( 'At least one reward is required.', 'atcf' )
	    ) );
?>
	    <?php do_action( 'atcf_shortcode_update_before', $editing, $campaign ); ?>
	    <form action="" method="post" class="atcf-update-campaign" enctype="multipart/form-data">
		    <?php do_action( 'atcf_shortcode_update_fields', $editing, $campaign ); ?>

		    <p class="atcf-update-campaign-update">
			    <input type="submit" value="<?php echo $editing ? sprintf( _x( 'Update %s', 'edit "campaign"', 'atcf' ), edd_get_label_singular() ) : sprintf( _x( 'Update %s', 'submit "campaign"', 'atcf' ), edd_get_label_singular() ); ?>">
			    <input type="hidden" name="action" value="atcf-campaign-<?php echo $editing ? 'edit' : 'submit'; ?>" />
			    <?php wp_nonce_field( 'atcf-campaign-edit' ); ?>
		    </p>

	    </form>
	    <?php do_action( 'atcf_shortcode_update_after', $editing, $campaign ); ?>

<?php
	    $form = ob_get_clean();

	    return $form;
	}
}

add_shortcode( 'appthemer_crowdfunding_update', 'atcf_shortcode_update' );

/*
function add_meta_boxes() {
	global $post;

	if ( ! is_object( $post ) )
		return;

	$campaign = atcf_get_campaign( $post );

	if ( ! $campaign->is_collected() && ( 'flexible' == $campaign->type() || $campaign->is_funded() ) && atcf_has_preapproval_gateway() )
		add_meta_box( 'atcf_campaign_funds', __( 'Campaign Funds', 'atcf' ), '_atcf_metabox_campaign_funds', 'download', 'side', 'high' );

	add_meta_box( 'atcf_campaign_stats', __( 'Campaign Stats', 'atcf' ), '_atcf_metabox_campaign_stats', 'download', 'side', 'high' );
	add_meta_box( 'atcf_campaign_updates', __( 'Campaign Updates', 'atcf' ), '_atcf_metabox_campaign_updates', 'download', 'normal', 'high' );
	add_meta_box( 'atcf_campaign_video', __( 'Campaign Video', 'atcf' ), '_atcf_metabox_campaign_video', 'download', 'normal', 'high' );
	add_meta_box( 'atcf_campaign_summary', __( 'Campaign summary', 'atcf' ), '_atcf_metabox_campaign_summary', 'download', 'normal', 'high' );
	add_meta_box( 'atcf_campaign_societal_challenge', __( 'Campaign societal challenge', 'atcf' ), '_atcf_metabox_campaign_societal_challenge', 'download', 'normal', 'high' );
	add_meta_box( 'atcf_campaign_added_value', __( 'Campaign added value', 'atcf' ), '_atcf_metabox_campaign_added_value', 'download', 'normal', 'high' );
	add_meta_box( 'atcf_campaign_development_strategy', __( 'Campaign development test strategy', 'atcf' ), '_atcf_metabox_campaign_development_strategy', 'download', 'normal', 'high' );
	add_meta_box( 'atcf_campaign_economic_model', __( 'Campaign economic model', 'atcf' ), '_atcf_metabox_campaign_economic_model', 'download', 'normal', 'high' );
	add_meta_box( 'atcf_campaign_measuring_impact', __( 'Campaign measuring impact', 'atcf' ), '_atcf_metabox_campaign_measuring_impact', 'download', 'normal', 'high' );
	add_meta_box( 'atcf_campaign_implementation', __( 'Campaign implementation', 'atcf' ), '_atcf_metabox_campaign_implementation', 'download', 'normal', 'high' );

	add_action( 'edd_meta_box_fields', '_atcf_metabox_campaign_info', 5 );
}*/


/**
 * Campaign summary
 * Ce champs repr�sente le Resum�
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_description( $editing, $campaign ) {
?>
	<p class="atcf-update-campaign-summary">
		<label for="description"><?php _e( 'Description', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign ? html_entity_decode( $campaign->data->post_content ) : '', 'description', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_description', 10, 2 );




/**
 * Campaign Zone d'impact
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_impact_area ($editing, $campaign ) {
?>
	<p class="atcf-update-campaign-impact_area">
		<label for="impact_area"><?php _e( 'Impact area', 'atcf' ); ?></label>
		<textarea name="impact_area" id="impact_area"><?php echo $campaign->impact_area(); ?></textarea>
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_impact_area', 10, 2 );


function atcf_shortcode_update_field_summary( $editing, $campaign ) {
?>
	<p class="atcf-update-campaign-summary">
		<label for="summary"><?php _e( 'Summary', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign ? html_entity_decode( $campaign->summary () ) : '', 'summary', apply_filters( 'atcf_submit_field_summary_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_summary', 10, 2 );



/**
 * Campaign Defi sociatal
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_societal_challenge( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign-societal_challenge">
		<label for="societal_challenge"><?php _e( 'Societal challenge', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->societal_challenge()) : '', 'societal_challenge', apply_filters( 'atcf_submit_field_summary_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: red; width: 200 px; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_societal_challenge', 11, 2 );


/**
 * Campaign Valeur ajout�e
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
 function atcf_shortcode_update_field_added_value( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign_value_added">
		<label for="value_added"><?php _e( 'Added value', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->added_value()) : '', 'added_value', apply_filters( 'atcf_submit_field_value_added_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_added_value', 11, 2 );


/**
 * Campaign Strat�gie de d�veloppement
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */

 
 function atcf_shortcode_update_field_development_strategy( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign-developement_strategy">
		<label for="developement_strategy"><?php _e( 'Strategy of development', 'atcf' ); ?></label>
		<?php
			wp_editor( $campaign ? html_entity_decode($campaign->development_strategy()) : '', 'development_strategy', apply_filters( 'atcf_submit_field_development_strategy_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
//add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_development_strategy', 11, 2 );



/**
 * Campaign Modele economique
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
  function atcf_shortcode_update_field_economic_model( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign_economic_model">
		<label for="economic_model"><?php _e( 'Economic model', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->economic_model()) : '', 'economic_model', apply_filters( 'atcf_submit_field_economic_model_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_economic_model', 11, 2 );


/**
 * Campaign Mesure d�impact
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
   function atcf_shortcode_update_field_measuring_impact( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign_measuring_impact">
		<label for="measuring_impact"><?php _e( 'Measuring Impact', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->measuring_impact()) : '', 'measuring_impact', apply_filters( 'atcf_submit_field_measuring_impact_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
//add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_measuring_impact', 11, 2 );

/**
 * Campaign Mise en oeuvre
 *implementation
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
   function atcf_shortcode_update_field_implementation( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign-implementation">
		<label for="implementation"><?php _e( 'Implementation', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->implementation()) : '', 'implementation', apply_filters( 'atcf_submit_field_implementation_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_implementation', 11, 2 );

 

/**
 * Campaign Updates
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_updates( $editing, $campaign ) {
	if ( ! $editing )
		return;
?>
	<div class="atcf-update-campaign-updates">
		<label for="description"><?php _e( 'Updates', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign->updates(), 'updates', apply_filters( 'atcf_submit_field_updates_editor_args', array( 
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_updates', 55, 2 );


/**
 * Campaign Images
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_images( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<p class="atcf-update-campaign-images">
		<label for="excerpt"><?php _e( 'Preview Image', 'atcf' ); ?></label>
		<input type="file" name="image" id="image" />
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_images', 12, 2 );

/**
 * Campaign Video
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_video( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<p class="atcf-update-campaign-video">
		<label for="length"><?php _e( 'Video URL', 'atcf' ); ?></label>
		<input type="text" name="video" id="video" value="<?php echo $campaign->video(); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_video', 12, 2 );


