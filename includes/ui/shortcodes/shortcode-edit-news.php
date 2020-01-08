<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_edit_news() {
    global $campaign, $post, $edd_options;
    $campaign = atcf_get_current_campaign();
    
    //Test pour vérifier que le post de blog appartient à la campagne
    $posts_blog = get_posts( array(
		'category' => $campaign->get_news_category_id(),
		'numberposts' => -1
	));
    if (isset($_GET['edit_post_id'])) $edit_post_id = ($_GET['edit_post_id']);
    $post_belong_campaign = false;
    foreach ($posts_blog as $post_blog) {
        if ($post_blog->ID == $edit_post_id){
            $post_belong_campaign = true;
        }
    }
    ypcf_debug_log( 'shortcode-edit-news.php :: ypcf_shortcode_edit_news()  $post_belong_campaign = '.$post_belong_campaign." edit_post_id = ".$_GET['edit_post_id']);
    ypcf_debug_log( 'shortcode-edit-news.php :: ypcf_shortcode_edit_news()  $campaign->current_user_can_edit() = '.$campaign->current_user_can_edit()." action = ".$_POST['action']);
    if ($campaign->current_user_can_edit() && isset($_GET['edit_post_id']) && $post_belong_campaign) {
	if (isset($_POST['action']) && $_POST['action'] == 'ypcf-campaign-edit-news') {
	    $blog  = array(
			'ID'		=> $_GET['edit_post_id'],
			'post_title'    => $_POST['posttitle'],
			'post_content'  => $_POST['postcontent']
	    );

	    wp_update_post($blog);
	}
	
	$news_link = esc_url(get_category_link($campaign->get_news_category_id()));
	?>
	<div style="padding-top: 80px;">
		<a href="<?php echo home_url( 'tableau-de-bord' ); ?>?campaign_id=<?php echo $_GET['campaign_id']; ?>#news">&lt;&lt; Retour au tableau de bord</a>
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
				<input type="submit" value="<?php _e('Mettre &agrave; jour', 'yproject'); ?>" class="button" style="background-color: #EA4F51; border: 0px none !important;"/>
			</p>
			<br/>
			<p>
				<a class="button" href="<?php echo $news_link."?delete_post_id=".($_GET['edit_post_id']) ?>">Supprimer l'article</a>
			</p>
	    </form>
	</div>
	<?php
    }
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
                                'plugins'		    => 'wordpress, paste, wplink, textcolor'
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