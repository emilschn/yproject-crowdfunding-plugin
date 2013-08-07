<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_edit_news() {
    global $campaign, $post, $edd_options;
    $form = '';

    // La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    $author_id = get_the_author_meta('ID');
    if (($current_user_id == $author_id || current_user_can('manage_options')) && isset($_GET['campaign_id']) && isset($_GET['edit_post_id'])) {

	if (isset($_POST['action']) && $_POST['action'] == 'ypcf-campaign-edit-news') {

	    $blog  = array(
		'ID'		=> $_GET['edit_post_id'],
		'post_title'    => $_POST['posttitle'],
		'post_content'  => $_POST['postcontent']
	    );

	    wp_update_post($blog);
	}

	ob_start();
	
	?>
	<div style="padding-top: 10px;">
	    <?php /* Lien ajouter une actu */ $page_add_news = get_page_by_path('ajouter-une-actu'); ?>
	    <a href="<?php echo get_permalink($page_add_news->ID); ?>?campaign_id=<?php echo $_GET['campaign_id']; ?>">&lt;&lt; Retour &agrave; la liste des articles</a>
	</div>
	<div style="padding-top: 10px;">
	    <h2>Editer un article</h2>
	    <form action="" method="post" class="ypcf-edit-news" enctype="multipart/form-data">
		<?php 
		    $currentpost = get_post($_GET['edit_post_id']);
		    do_action( 'ypcf_shortcode_edit_news_fields', $currentpost); 
		?>
		<p class="ypcf-edit-news-p">
		    <input type="hidden" name="action" value="ypcf-campaign-edit-news" /><br />
		    <?php wp_nonce_field('ypcf-campaign-edit-news'); ?>
		    <input type="submit">
		</p>
	    </form>
	</div>
	<?php
    }

    return ob_get_clean();
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
			'teeny'         => true,
			'quicktags'     => false,
			'editor_css'    => '<style>body { background: white; }</style>',
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
	?>
    </div>
    <?php
}
add_action( 'ypcf_shortcode_edit_news_fields', 'ypcf_shortcode_edit_news_field_postcontent', 10, 1);
?>