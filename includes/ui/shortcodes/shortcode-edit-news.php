<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_edit_news() {
    global $campaign, $post, $edd_options;
    $form = '';
    $campaign = atcf_get_current_campaign();

    // La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    $save_post = $post;
    if (isset($_GET['campaign_id'])) $post = get_post($_GET['campaign_id']);
    $author_id = $post->post_author;
    if ($campaign->current_user_can_edit() && isset($_GET['edit_post_id'])) {

	if (isset($_POST['action']) && $_POST['action'] == 'ypcf-campaign-edit-news') {

	    $blog  = array(
		'ID'		=> $_GET['edit_post_id'],
		'post_title'    => $_POST['posttitle'],
		'post_content'  => $_POST['postcontent']
	    );

	    wp_update_post($blog);
	}
	
	?>
	<div style="padding-top: 10px;">
	    <?php 
	    $category_slug = $post->ID . '-blog-' . $post->post_name;
	    $category_obj = get_category_by_slug($category_slug);
	    if (!empty($category_obj)) {
		    $news_link = esc_url(get_category_link($category_obj->cat_ID));
	    } else {
		    $news_link = '';
	    }
	    ?>
	    <a href="<?php echo $news_link; ?>">&lt;&lt; Retour &agrave; la liste des articles</a>
	</div>
	<div style="padding-top: 10px;">
	    <h2>Editer l&apos;actualit&eacute;</h2>
	    <form action="" method="post" class="ypcf-edit-news" enctype="multipart/form-data">
		<?php 
		    $currentpost = get_post($_GET['edit_post_id']);
		    do_action( 'ypcf_shortcode_edit_news_fields', $currentpost); 
		?>
		<p class="ypcf-edit-news-p">
		    <input type="hidden" name="action" value="ypcf-campaign-edit-news" /><br />
		    <?php wp_nonce_field('ypcf-campaign-edit-news'); ?>
		    <input type="submit" value="<?php _e('Mettre &agrave; jour', 'yproject'); ?>" class="button" />
		</p>
	    </form>
	</div>
	<?php
    }
    $post = $save_post;
}
add_shortcode( 'yproject_crowdfunding_edit_news', 'ypcf_shortcode_edit_news' );

function ypcf_shortcode_edit_news_field_posttitle($post) {
    ?>
    <div class="ypcf-edit-news-title">
	<label for="posttitle"><?php _e( 'Titre', 'ypcf' ); ?></label>
	<input type="text" name="posttitle" id="posttitle" style="width: 250px;" value="<?php echo $post->post_title; ?>"><br />
    </div>
    <?php
}
add_action( 'ypcf_shortcode_edit_news_fields', 'ypcf_shortcode_edit_news_field_posttitle', 10, 1);

function ypcf_shortcode_edit_news_field_postcontent($post) {
    ?>

    <div class="ypcf-edit-news-content">
	<label for="postcontent"><?php _e( 'Contenu', 'ypcf' ); ?></label>
	<?php
	    wp_editor( 
		$post->post_content, 
		'postcontent', 
		apply_filters(  
		    'ypcf_submit_field_postcontent_editor_args', 
		    array( 
			'media_buttons' => true,
			'quicktags'     => false,
			'editor_css'    => '<style>body { background: white; }</style>',
			'tinymce'       => array(
				'theme_advanced_path'     => false,
				'paste_remove_styles'     => true,
				'theme_advanced_resizing_use_cookie' => false,
+                               'plugins'=> 'paste, wplink, textcolor'
			)

		    ) 
		) 
	    );
	?>
    </div>
    <?php
}
add_action( 'ypcf_shortcode_edit_news_fields', 'ypcf_shortcode_edit_news_field_postcontent', 10, 1);
?>