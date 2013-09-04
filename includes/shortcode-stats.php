<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_stats() {
    global $wpdb, $campaign, $post, $edd_options;
    

    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post );
    
    $category_slug = $post->ID . '-statistiques-' . $post->post_title;
    $category_obj = get_category_by_slug($category_slug);
    $campaign_id =  $campaign->ID;

    $investisseurs = $wpdb->get_results( "SELECT ID,post_author,post_name,post_type FROM wp_posts WHERE post_type = 'edd_payment'" );
    

    ob_start();
    print_r($investisseurs);
    echo $campaign_id;

    echo "</br>";
    ?>
    <div id="stat-tab-1">
        <div id="backers">
            <h2>Nombre d'investisseurs</h2>

        </div>

        <div id="invest-moyen">
            <h3>Investissement moyen par personne</h3>

        </div>
          	
        <div id="median">
            <h3>Investissement médian</h3>

        </div> 

        <div id="montant-collecte">
            <h3>Montant de la collecte</h3>
        </div>

        <div id="montant-atteint">
            <h3>Montant atteint</h3>
        </div>

        <div id="montant-collecte">
            <h3>Montant de jours restant</h3>
        </div> 

        <div id="montant-collecte">
            <h3>Cours du kilo de grenades</h3>
        </div>
    </div>

    <div id="stat-tab-2">
         <h3>Liste des investisseurs</h3>
        <?php printPreviewUsersLastInvestors(30) ;?>

    </div>

    <?php
      	
    }

add_shortcode( 'yproject_crowdfunding_stats', 'ypcf_shortcode_stats' );
