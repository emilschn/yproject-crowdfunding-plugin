<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_stats() {
    global $wpdb, $campaign, $post, $edd_options;
    
  

    // La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    $author_id = get_the_author_meta('ID');
    if (($current_user_id == $author_id || current_user_can('manage_options')) && isset($_GET['campaign_id'])) {

    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post );
    
    $category_slug = $post->ID . '-statistiques-' . $post->post_title;
    $category_obj = get_category_by_slug($category_slug);
}



    ob_start();

    echo "</br></br>Les statistiques du projet";
  	
  	

  	


}

add_shortcode( 'yproject_crowdfunding_stats', 'ypcf_shortcode_stats' );
