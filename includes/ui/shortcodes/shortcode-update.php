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
	$crowdfunding = crowdfunding();
	$campaign = atcf_get_current_campaign();
	global $post_campaign;
	
	if ($campaign->current_user_can_edit()) {
	    
	    if (isset($_POST['action']) && $_POST['action'] == 'atcf-campaign-submit') {
		$post_update = array();
		$post_update['ID'] = $campaign->ID;
		if (isset($_POST['title']) && $_POST['title'] != "") $post_update['post_title'] = $_POST['title'];
		if (isset($_POST['description']) && $_POST['description'] != "") $post_update['post_content'] = $_POST['description'];
		wp_update_post($post_update);
		
		update_post_meta($campaign->ID, 'campaign_video', esc_url($_POST['video']));
		update_post_meta($campaign->ID, 'campaign_summary', $_POST['summary']);
		update_post_meta($campaign->ID, 'campaign_subtitle', $_POST['subtitle']);
		update_post_meta($campaign->ID, 'campaign_added_value', $_POST['added_value']);
		update_post_meta($campaign->ID, 'campaign_societal_challenge', $_POST['societal_challenge']);
		update_post_meta($campaign->ID, 'campaign_economic_model', $_POST['economic_model']);
		update_post_meta($campaign->ID, 'campaign_implementation', $_POST['implementation']);
		$temp_blur = $_POST['image_header_blur'];
		if (empty($temp_blur)) $temp_blur = 'FALSE';
		update_post_meta($campaign->ID, 'campaign_header_blur_active', $temp_blur);
		
		/* Gestion fichiers / images */
		$image_header = $_FILES[ 'image' ];
		$path = $_FILES['image']['name'];
		$ext = pathinfo($path, PATHINFO_EXTENSION);
	
		if (!empty($image_header)) {
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
		    
		    $upload = wp_handle_upload( $image_header, $upload_overrides );
		    if (isset($upload[ 'url' ])) {
			$attachment = array(
				'guid'           => $upload[ 'url' ], 
				'post_mime_type' => $upload[ 'type' ],
				'post_title'     => 'image_header',
				'post_content'   => '',
				'post_status'    => 'inherit'
			);
			
			$is_image_accepted = true;
			switch (strtolower($ext)) {
				case 'png':
					$image_header = imagecreatefrompng($upload[ 'file' ]);
					break;
				case 'jpg':
				case 'jpeg':
					$image_header = imagecreatefromjpeg($upload[ 'file' ]);
					break;
				default:
					$is_image_accepted = false;
					break;
			}
			if($is_image_accepted){
			    for($i=0; $i<10 ; $i++){
				    imagefilter ($image_header, IMG_FILTER_GAUSSIAN_BLUR);
				    imagefilter ($image_header , IMG_FILTER_SELECTIVE_BLUR );
			    }
			    $withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $upload[ 'file' ]);
			    $img_name = $withoutExt.'_blur.jpg';
			    imagejpeg($image_header,$img_name);
			    global $wpdb;
			    $table_posts = $wpdb->prefix . "posts";
			    $campaign_id=$campaign->ID;
			    //Suppression dans la base de données de l'ancienne image
			    $old_attachement_id=$wpdb->get_var( "SELECT * FROM $table_posts WHERE post_parent=$campaign_id and post_title='image_header'" );
			    wp_delete_attachment( $old_attachement_id, true );
			    $attach_id = wp_insert_attachment( $attachment, $img_name, $campaign->ID );		

			    wp_update_attachment_metadata( 
				    $attach_id, 
				    wp_generate_attachment_metadata( $attach_id, $img_name ) 
			    );
			    //Suppression de la position de la couverture
			    delete_post_meta($campaign->ID, 'campaign_cover_position');


			    add_post_meta( $campaign->ID, '_thumbnail_id', absint( $attach_id ) );
			}
		    }
		    
		}
		
		
		$image	    = $_FILES[ 'image_home' ];
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
				'post_title'     => 'image_home',
				'post_content'   => '',
				'post_status'    => 'inherit'
			);
			global $wpdb;
			$table_posts = $wpdb->prefix . "posts";
			$campaign_id=$campaign->ID;
			//Suppression dans la base de données de l'ancienne image
			$old_attachement_id=$wpdb->get_var( "SELECT * FROM $table_posts WHERE post_parent=$campaign_id and post_title='image_home'" );
			wp_delete_attachment( $old_attachement_id, true );

			$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign->ID );		

			wp_update_attachment_metadata( 
				$attach_id, 
				wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
			);
		    }
		    
		}
		/* FIN Gestion fichiers / images */
		
		//Re-select des données en cas de modification
		$post_campaign = get_post($_GET['campaign_id']);
		$campaign = atcf_get_campaign( $post_campaign );
	    }

	    ob_start();

	    wp_enqueue_script( 'jquery-validation', EDD_PLUGIN_URL . 'assets/js/jquery.validate.min.js');
	    wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery', 'jquery-validation' ) );

	    wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
		    'oneReward' => __( 'At least one reward is required.', 'atcf' )
	    ) );
?>

	    <?php 
	    if (isset($create_investors_success)) :
		if ($create_investors_success) : ?>
		    <span class="error">Groupe cr&eacute;&eacute; avec succ&egrave;s !</span><br /><br />
		<?php else: ?>
		    <span class="success">Problème lors de la cr&eacute;ation du groupe...</span><br /><br />
		<?php endif;
	    endif;
	    
	    if (current_user_can('manage_options') && !$campaign->is_remaining_time() && $campaign->campaign_status() != ATCF_Campaign::$campaign_status_preview && $campaign->campaign_status() != ATCF_Campaign::$campaign_status_vote && !$group_exists) : 
	    ?>
	    <form action="" method="post" class="atcf-update-campaign" enctype="multipart/form-data">
		    <input type="submit" value="Cr&eacute;er le groupe d&apos;investisseurs" class="button" />
		    <input type="hidden" name="action" value="ypcf-campaign-create-investors-group" />
	    </form><br /><br />
	    <?php endif; ?>

	    <?php 
		add_filter('mce_buttons', 'atcf_editor_filter', 10, 2);
		add_filter('mce_buttons_2', 'atcf_editor_filter_2', 10, 2);
	    ?>
	    
	    <?php do_action( 'atcf_shortcode_update_before', $editing, $campaign, $post_campaign ); ?>
	    <form action="" method="post" class="atcf-update-campaign" enctype="multipart/form-data">
		    <?php do_action( 'atcf_shortcode_update_fields', $editing, $campaign, $post_campaign ); ?>

		    <p class="atcf-update-campaign-update">
			    <input type="submit" value="Mettre &agrave; jour le projet" class="button">
			    <input type="hidden" name="action" value="atcf-campaign-<?php echo $editing ? 'edit' : 'submit'; ?>" />
			    <?php wp_nonce_field( 'atcf-campaign-edit' ); ?>
		    </p>

	    </form>
	    <?php do_action( 'atcf_shortcode_update_after', $editing, $campaign, $post_campaign ); ?>
	    
	    <?php 
		remove_filter('mce_buttons', 'atcf_editor_filter');
		remove_filter('mce_buttons_2', 'atcf_editor_filter_2');
	    ?>

<?php
	    $form = ob_get_clean();

	    return $form;
	}
}
add_shortcode( 'appthemer_crowdfunding_update', 'atcf_shortcode_update' );

function atcf_shortcode_update_field_title($editing, $campaign, $post_campaign) {
    ?>
    <div class="update_field atcf-update-campaign-title">
	<label class="update_field_label" for="title">Nom du projet</label><br />
	<textarea name="title" id="title" rows="1" cols="40"><?php echo $post_campaign->post_title; ?></textarea>
    </div><br />
    <?php
}
//add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_title', 10, 3);
function atcf_shortcode_update_field_subtitle($editing, $campaign, $post_campaign) {
	 ?>
    <div class="update_field atcf-update-campaign-title">
	<label class="update_field_label" for="title">Description de 3 à 4 mots du projet</label><br />
	<textarea name="subtitle" rows="1" cols="30"><?php echo html_entity_decode($campaign->subtitle()); ?></textarea>
    </div><br />
    <?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_subtitle', 10, 3);

function atcf_shortcode_update_field_summary( $editing, $campaign, $post_campaign ) {
?>
    <div class="update_field atcf-update-campaign-summary">
	<label class="update_field_label" for="summary">R&eacute;sum&eacute;</label><br />
	<textarea name="summary" id="summary" rows="5" cols="40"><?php echo strip_tags(html_entity_decode($campaign->summary())); ?></textarea>
    </div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_summary', 10, 3);


function atcf_shortcode_update_field_images( $editing, $campaign, $post_campaign ) {
    $image_src_header = $campaign->get_header_picture_src(false);
    $image_src_home = $campaign->get_home_picture_src(false);
?>
    <div class="update_field atcf-update-campaign-image-home">
	<label class="update_field_label" for="image_home">Image d&apos;aper&ccedil;u (Max. 2Mo ; id&eacute;alement 610px de largeur * 330px de hauteur)</label><br />
	<?php if ($image_src_home != '') { ?><div class="update-field-img-home"><img src="<?php echo $image_src_home; ?>" /></div><br /><?php } ?>
	<input type="file" name="image_home" id="image_home" />
    </div><br />
    <div class="update_field atcf-update-campaign-images">
	<label class="update_field_label" for="image">Image du bandeau (Max. 2Mo ; id&eacute;alement 1366px de largeur * 370px de hauteur)</label><br />
	<?php if ($image_src_header != '') { ?><div class="update-field-img-header"><img src="<?php echo $image_src_header; ?>" /></div><br /><?php } ?>
	<input type="file" name="image" id="image" /><br />
	<input type="checkbox" name="image_header_blur" <?php if ($campaign->is_header_blur()) { echo 'checked="checked"'; } ?> /> Appliquer un flou artistique
    </div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_images', 10, 3);

function atcf_shortcode_update_field_video( $editing, $campaign, $post_campaign ) {
?>
    <div class="update_field atcf-update-campaign-video">
	<label class="update_field_label" for="video">Vid&eacute;o de pr&eacute;sentation</label><br />
	<textarea name="video" id="video" rows="1" cols="40" placeholder="URL de la vidéo"><?php echo $campaign->video(); ?></textarea>
    </div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_video', 10, 3);

function atcf_shortcode_update_field_description( $editing, $campaign, $post_campaign ) {
	global $post_ID, $post;
	$post_ID = $post = 0;
?>
	<div class="update_field atcf-update-campaign-description">
		<label class="update_field_label" for="description">En quoi consiste le projet ?</label><br />
		<?php 
		    $content = apply_filters( 'the_content', $campaign->data->post_content );
		    $content = str_replace( ']]>', ']]&gt;', $content );
		    if ($campaign) {
			wp_editor( 
			    $content, 
			    'description', 
			    array( 
				'media_buttons' => true,
				'quicktags'     => false,
				'tinymce'       => array(
				    'plugins'		    => 'paste',
				    'paste_remove_styles'   => true
				)
			    )
			); 
		    }
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_description', 10, 3);


function atcf_shortcode_update_field_societal_challenge( $editing, $campaign, $post_campaign ) {
	global $post_ID, $post;
	$post_ID = $post = 0;
?>
	<div class="update_field atcf-update-campaign-societal_challenge">
		<label class="update_field_label" for="societal_challenge">Quelle est l&apos;utilit&eacute; soci&eacute;tale du projet ?</label><br />
		<?php 
		    if ($campaign) {
			wp_editor( 
			    html_entity_decode( $campaign->societal_challenge() ), 
			    'societal_challenge', 
			    array( 
				'media_buttons' => true,
				'quicktags'     => false,
				'tinymce'       => array(
				    'plugins'		    => 'paste',
				    'paste_remove_styles'   => true
				)
			    )
			); 
		    }
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_societal_challenge', 10, 3);


function atcf_shortcode_update_field_added_value( $editing, $campaign, $post_campaign ) {
	global $post_ID, $post;
	$post_ID = $post = 0;
?>
	<div class="update_field atcf-update-campaign_added_value">
		<label class="update_field_label" for="added_value">Quelle est l&apos;opportunit&eacute; &eacute;conomique du projet ?</label><br />
		<?php 
		    if ($campaign) {
			wp_editor( 
			    html_entity_decode( $campaign->added_value() ), 
			    'added_value', 
			    array( 
				'media_buttons' => true,
				'quicktags'     => false,
				'tinymce'       => array(
				    'plugins'		    => 'paste',
				    'paste_remove_styles'   => true
				)
			    )
			); 
		    }
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_added_value', 10, 3);

function atcf_shortcode_update_field_economic_model( $editing, $campaign, $post_campaign ) {
	global $post_ID, $post;
	$post_ID = $post = 0;
?>
	<div class="update_field atcf-update-campaign_economic_model">
		<label class="update_field_label" for="economic_model">Quel est le mod&egrave;le &eacute;conomique du projet ?</label><br />
		<?php 
		    if ($campaign) {
			wp_editor( 
			    html_entity_decode( $campaign->economic_model() ), 
			    'economic_model', 
			    array( 
				'media_buttons' => true,
				'quicktags'     => false,
				'tinymce'       => array(
				    'plugins'		    => 'paste',
				    'paste_remove_styles'   => true
				)
			    )
			); 
		    }
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_economic_model', 10, 3 );

function atcf_shortcode_update_field_implementation( $editing, $campaign, $post_campaign ) {
	global $post_ID, $post;
	$post_ID = $post = 0;
?>
	<div class="update_field atcf-update-campaign-implementation">
		<label class="update_field_label" for="implementation">Qui porte le projet ?</label><br />
		<?php 
		    if ($campaign) {
			wp_editor( 
			    html_entity_decode( $campaign->implementation() ), 
			    'implementation', 
			    array( 
				'media_buttons' => true,
				'quicktags'     => false,
				'tinymce'       => array(
				    'plugins'		    => 'paste',
				    'paste_remove_styles'   => true
				)
			    )
			); 
		    }
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_implementation', 10, 3);


function atcf_editor_filter($buttons) {
    array_splice($buttons, 2, 1, 'underline');
    array_push($buttons, 'alignjustify');
    array_push($buttons, 'undo');
    array_push($buttons, 'redo');
    return $buttons;
}
function atcf_editor_filter_2($buttons) {
    return array();
}