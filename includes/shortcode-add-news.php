<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_add_news() {
    global $campaign, $post, $edd_options;
    $form = '';

    // La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    $author_id = get_the_author_meta('ID');
    if (($current_user_id == $author_id || current_user_can('manage_options')) && isset($_GET['campaign_id'])) {

	$crowdfunding = crowdfunding();

	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	
	$category_slug = $post->ID . '-blog-' . $post->post_title;
	$category_obj = get_category_by_slug($category_slug);

	if (isset($_POST['action']) && $_POST['action'] == 'ypcf-campaign-add-news') {

	    $blog  = array(
		'post_title'    => $_POST['posttitle'],
		'post_content'  => $_POST['postcontent'],
		'post_status'   => 'publish',
		'post_author'   => $current_user_id,
		'post_category' => array($category_obj->cat_ID)
	    );

	    wp_insert_post($blog, true);
	}

	ob_start();
	
	?>
	<div style="padding-top: 10px;">
	    <h2>Liste des articles du blog :</h2>
	    <ul>
	    <?php 
		/* Lien ajouter une actu */ 
		$page_edit_news = get_page_by_path('editer-une-actu');
		
		$args = array( 'category' => $category_obj->cat_ID);
		$posts_array = get_posts( $args );
		if (empty($posts_array)) {
		    ?>
		    <li>Aucun article...</li>
		    <?php
		    
		} else {
		    
		    foreach ( $posts_array as $catpost ) :
		    ?> 
		    <li><?php echo $catpost->post_title; ?> [<a href="<?php echo get_permalink($page_edit_news->ID); ?>?campaign_id=<?php echo $_GET['campaign_id']; ?>&edit_post_id=<?php echo $catpost->ID; ?>">Editer</a>]</li>
		    <?php
		    endforeach;
		}
	    ?>
	    </ul>
	</div>
	<div style="padding-top: 10px;">
	    <h2>Ajouter un article</h2>
	    <form action="" method="post" class="ypcf-add-news" enctype="multipart/form-data">
		<?php do_action( 'ypcf_shortcode_add_news_fields'); ?>
		<p class="ypcf-add-news-p">
		    <input type="hidden" name="action" value="ypcf-campaign-add-news" /><br />
		    <?php wp_nonce_field('ypcf-campaign-add-news'); ?>
		    <input type="submit">
		</p>
	    </form>
	</div>
	<?php
    }

    return ob_get_clean();
}
add_shortcode( 'yproject_crowdfunding_add_news', 'ypcf_shortcode_add_news' );

function ypcf_shortcode_add_news_field_posttitle() {
    ?>
    <div class="ypcf-add-news-title">
	<label for="posttitle"><?php _e( 'Titre', 'ypcf' ); ?></label>
	<input type="text" name="posttitle" id="posttitle" style="width: 250px;"><br />
    </div>
    <?php
}
add_action( 'ypcf_shortcode_add_news_fields', 'ypcf_shortcode_add_news_field_posttitle', 10, 0);

function ypcf_shortcode_add_news_field_postcontent() {
    ?>

    <div class="ypcf-add-news-content">
	<label for="postcontent"><?php _e( 'Contenu', 'ypcf' ); ?></label>
	<?php
	    wp_editor( 
		'', 
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
add_action( 'ypcf_shortcode_add_news_fields', 'ypcf_shortcode_add_news_field_postcontent', 10, 0);
?>