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
	$post = get_post($_GET['campaign_id']);
	
	// La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
	$current_user = wp_get_current_user();
	$current_user_id = $current_user->ID;
	if ($current_user_id == $post->post_author || current_user_can('manage_options')) {

	    $crowdfunding = crowdfunding();

	    $campaign = atcf_get_campaign( $post );
	    
	    //Si on demande la création d'un groupe d'utilisateurs
	    $investors_group_id = get_post_meta($campaign->ID, 'campaign_investors_group', true);
	    $group_exists = (is_numeric($investors_group_id) && ($investors_group_id > 0));
	    if (isset($_POST['action']) && $_POST['action'] == 'ypcf-campaign-create-investors-group' && !$group_exists) {
		$create_investors_success = false;
		//Si c'est bien l'admin qui demande et qu'il ne reste plus de temps pour investir
		if (current_user_can('manage_options') && $campaign->days_remaining() <= 0) {
		    $create_investors_success = ypcf_campaign_create_investors_group($post, $campaign);
		}
	    }
	
	    if (isset($_POST['action']) && $_POST['action'] == 'atcf-campaign-submit') {
		$post_update = array();
		$post_update['ID'] = $campaign->ID;
		if (isset($_POST['title']) && $_POST['title'] != "") $post_update['post_title'] = $_POST['title'];
		if (isset($_POST['description']) && $_POST['description'] != "") $post_update['post_content'] = $_POST['description'];
		wp_update_post($post_update);
		
		update_post_meta($campaign->ID, 'campaign_video', esc_url($_POST['video']));
		update_post_meta($campaign->ID, 'campaign_summary', $_POST['summary']);
		
		update_post_meta($campaign->ID, 'campaign_added_value', $_POST['added_value']);
		update_post_meta($campaign->ID, 'campaign_societal_challenge', $_POST['societal_challenge']);
		update_post_meta($campaign->ID, 'campaign_economic_model', $_POST['economic_model']);
		update_post_meta($campaign->ID, 'campaign_implementation', $_POST['implementation']);
		
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
				'post_title'     => 'image_header',
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign->ID );		

			wp_update_attachment_metadata( 
				$attach_id, 
				wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
			);

			add_post_meta( $campaign->ID, '_thumbnail_id', absint( $attach_id ) );
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

			$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign->ID );		

			wp_update_attachment_metadata( 
				$attach_id, 
				wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
			);
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

	    <?php 
	    if (isset($create_investors_success)) :
		if ($create_investors_success) : ?>
		    <span class="error">Groupe cr&eacute;&eacute; avec succ&egrave;s !</span><br /><br />
		<?php else: ?>
		    <span class="success">Problème lors de la cr&eacute;ation du groupe...</span><br /><br />
		<?php endif;
	    endif;
	    
	    if (current_user_can('manage_options') && $campaign->days_remaining() <= 0 && $campaign->campaign_status() != 'preview' && $campaign->campaign_status() != 'vote' && !$group_exists) : 
	    ?>
	    <form action="" method="post" class="atcf-update-campaign" enctype="multipart/form-data">
		    <input type="submit" value="Cr&eacute;er le groupe d&apos;investisseurs" />
		    <input type="hidden" name="action" value="ypcf-campaign-create-investors-group" />
	    </form><br /><br />
	    <?php endif; ?>

	    <?php do_action( 'atcf_shortcode_update_before', $editing, $campaign, $post ); ?>
	    <form action="" method="post" class="atcf-update-campaign" enctype="multipart/form-data">
		    <?php do_action( 'atcf_shortcode_update_fields', $editing, $campaign, $post ); ?>

		    <p class="atcf-update-campaign-update">
			    <input type="submit" value="Mettre &agrave; jour le projet">
			    <input type="hidden" name="action" value="atcf-campaign-<?php echo $editing ? 'edit' : 'submit'; ?>" />
			    <?php wp_nonce_field( 'atcf-campaign-edit' ); ?>
		    </p>

	    </form>
	    <?php do_action( 'atcf_shortcode_update_after', $editing, $campaign, $post ); ?>

<?php
	    $form = ob_get_clean();

	    return $form;
	}
}
add_shortcode( 'appthemer_crowdfunding_update', 'atcf_shortcode_update' );

function atcf_shortcode_update_field_title($editing, $campaign, $post) {
    ?>
    <div class="update_field atcf-update-campaign-title">
	<label class="update_field_label" for="title">Nom du projet</label><br />
	<textarea name="title" id="title" rows="1" cols="40"><?php echo $post->post_title; ?></textarea>
    </div><br />
    <?php
}
//add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_title', 10, 3);



function atcf_shortcode_update_field_summary( $editing, $campaign, $post ) {
?>
    <div class="update_field atcf-update-campaign-summary">
	<label class="update_field_label" for="summary">R&eacute;sum&eacute;</label><br />
	<textarea name="summary" id="summary" rows="5" cols="40"><?php echo strip_tags(html_entity_decode($campaign->summary())); ?></textarea>
    </div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_summary', 10, 3);


function atcf_shortcode_update_field_images( $editing, $campaign, $post ) {
    $attachments = get_posts( array(
					'post_type' => 'attachment',
					'post_parent' => $post->ID,
					'post_mime_type' => 'image'
		    ));
    $image_obj_home = '';
    $image_obj_header = '';
    $image_src_home = '';
    $image_src_header = '';
    //Si on en trouve bien une avec le titre "image_home" on prend celle-là
    foreach ($attachments as $attachment) {
	if ($attachment->post_title == 'image_home') $image_obj_home = wp_get_attachment_image_src($attachment->ID, "full");
	if ($attachment->post_title == 'image_header') $image_obj_header = wp_get_attachment_image_src($attachment->ID, "full");
    }
    //Sinon on prend la première image rattachée à l'article
    if ($image_obj_home != '') $image_src_home = $image_obj_home[0];
    if ($image_obj_header != '') $image_src_header = $image_obj_header[0];
?>
    <div class="update_field atcf-update-campaign-image-home">
	<label class="update_field_label" for="image_home">Image d&apos;aper&ccedil;u (Max. 2Mo ; id&eacute;alement 610px de largeur * 330px de hauteur)</label><br />
	<?php if ($image_src_home != '') { ?><div class="update-field-img-home"><img src="<?php echo $image_src_home; ?>" /></div><br /><?php } ?>
	<input type="file" name="image_home" id="image_home" />
    </div><br />
    <div class="update_field atcf-update-campaign-images">
	<label class="update_field_label" for="image">Image du bandeau (Max. 2Mo ; id&eacute;alement 960px de largeur * 240px de hauteur)</label><br />
	<?php if ($image_src_header != '') { ?><div class="update-field-img-header"><img src="<?php echo $image_src_header; ?>" /></div><br /><?php } ?>
	<input type="file" name="image" id="image" />
    </div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_images', 10, 3);

function atcf_shortcode_update_field_video( $editing, $campaign, $post ) {
?>
    <div class="update_field atcf-update-campaign-video">
	<label class="update_field_label" for="video">Vid&eacute;o de pr&eacute;sentation</label><br />
	<textarea name="video" id="video" rows="1" cols="40" placeholder="URL de la vidéo"><?php echo $campaign->video(); ?></textarea>
    </div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_video', 10, 3);

function atcf_shortcode_update_field_description( $editing, $campaign, $post ) {
?>
	<div class="update_field atcf-update-campaign-description">
		<label class="update_field_label" for="description">En quoi consiste le projet ?</label><br />
		<?php 
			wp_editor( $campaign ? html_entity_decode( $campaign->data->post_content ) : '', 'description', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,justifyfull,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_description', 10, 3);


function atcf_shortcode_update_field_added_value( $editing, $campaign, $post ) {
?>
	<div class="update_field atcf-update-campaign_added_value">
		<label class="update_field_label" for="added_value">Quelle est l&apos;opportunit&eacute; &eacute;conomique du projet ?</label><br />
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->added_value()) : '', 'added_value', apply_filters( 'atcf_submit_field_value_added_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,justifyfull,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_added_value', 10, 3);

function atcf_shortcode_update_field_societal_challenge( $editing, $campaign, $post ) {
?>
	<div class="update_field atcf-update-campaign-societal_challenge">
		<label class="update_field_label" for="societal_challenge">Quelle est l&apos;utilit&eacute; soci&eacute;tale du projet ?</label><br />
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->societal_challenge()) : '', 'societal_challenge', apply_filters( 'atcf_submit_field_societal_challenge_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: red; width: 200 px; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,justifyfull,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_societal_challenge', 10, 3);

function atcf_shortcode_update_field_economic_model( $editing, $campaign, $post ) {
?>
	<div class="update_field atcf-update-campaign_economic_model">
		<label class="update_field_label" for="economic_model">Quel est le mod&egrave;le &eacute;conomique du projet ?</label><br />
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->economic_model()) : '', 'economic_model', apply_filters( 'atcf_submit_field_economic_model_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,justifyfull,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_economic_model', 10, 3 );

function atcf_shortcode_update_field_implementation( $editing, $campaign, $post ) {
?>
	<div class="update_field atcf-update-campaign-implementation">
		<label class="update_field_label" for="implementation">Qui porte le projet ?</label><br />
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->implementation()) : '', 'implementation', apply_filters( 'atcf_submit_field_implementation_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,justifyfull,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_implementation', 10, 3);

/**
 * Permet de créer le groupe d'utilisateurs
 * @param type $campaign
 */
function ypcf_campaign_create_investors_group($post_campaign, $campaign) {
    //Création du groupe
    $name = 'Investisseurs du projet ' . $post_campaign->post_title;
    $description = 'Groupe de discussion et d&apos;information pour le projet ' . $post_campaign->post_title;
    $create_group_args = array(
	'name' => $name,
	'description' => $description,
	'slug' => sanitize_title( esc_attr( $name ) ),
	'date_created' => bp_core_current_time(), 
	'enable_forum' => 1,
	'status' => 'hidden'
    );
    $new_group_id = groups_create_group($create_group_args);
    update_post_meta($campaign->ID, 'campaign_investors_group', $new_group_id);
    groups_update_groupmeta($new_group_id, 'campaign_id', $post_campaign->ID);
    
    //Création d'un forum
    $new_forum_id = bbp_insert_forum( $forum_data = array('post_title' => 'Forum pour le groupe du projet ' . $post_campaign->post_title ) );
    groups_update_groupmeta( $new_group_id, 'forum_id', $new_forum_id );
    
    //Ajout des utilisateurs à ce groupe
    $invite_users_args = array(
	'user_id'       => false,
	'group_id'      => $new_group_id,
	'inviter_id'    => bp_loggedin_user_id(),
	'date_modified' => bp_core_current_time(),
	'is_confirmed'  => 1
    );
    //Le porteur de projet
    $invite_users_args['user_id'] = $post_campaign->post_author;
    groups_invite_user( $invite_users_args );
    //L'admin
    $invite_users_args['user_id'] = 1;
    groups_invite_user( $invite_users_args );
    //Les investisseurs
    $payments_data = get_payments_data($post_campaign->ID);
    foreach ( $payments_data as $item ) {
	if ($item['signsquid_status'] == 'Agreed') {
	    $payment_status = ypcf_get_updated_payment_status($item['ID']);
	    if ($payment_status == 'publish') {
		$mangopay_id = edd_get_payment_key($item['ID']);
		$mangopay_contribution = ypcf_mangopay_get_contribution_by_id($mangopay_id);
		$mangopay_is_succeeded = (isset($mangopay_contribution->IsSucceeded) && $mangopay_contribution->IsSucceeded);
		if ($mangopay_is_succeeded) {
		    $invite_users_args['user_id'] = $item['user'];
		    groups_invite_user( $invite_users_args );
		}
	    }
	}
    }
    //Envoi des invitations à venir voir le groupe
    global $send_invite_message;
    $user_author = get_user_by('id', $post_campaign->post_author);
    $group_obj = groups_get_group(array('group_id' => $investors_group_id));
    $group_link = bp_get_group_permalink($group_obj);
    $send_invite_message = 'Bonjour '.$user_author->first_name.' '.$user_author->last_name.',
Afin de communiquer entre vous, un groupe privé regroupant les investisseurs du projet '.$post_campaign->post_title.' a été créé.
Vous pouvez y accéder en cliquant sur ce <a href="'.$group_link.'" target="_blank">lien</a> ou en vous connectant à votre compte, puis en vous rendant sur la page "Mes investissements".
Encore merci pour votre investissement et à bientôt sur <a href="http://www.wedogood.co" target="_blank">WEDOGOOD.co</a> !';
    groups_send_invites( $post_campaign->post_author, $new_group_id );
    unset($GLOBALS['send_invite_message']);
    
    return true;
}