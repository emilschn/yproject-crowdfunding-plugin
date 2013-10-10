<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_stats() {
    global $wpdb, $campaign, $post, $edd_options ;
    $table_jcrois = $wpdb->prefix . "yp_jycrois"; 
    $jcrois=false; 
    

    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post );
    
    $category_slug = $post->ID . '-statistiques-' . $post->post_title;
    $category_obj = get_category_by_slug($category_slug);
    $campaign_id =  $campaign->ID;

    $table_name = $wpdb->prefix . "posts";
    $investisseurs = $wpdb->get_results( "SELECT ID,post_author,post_name,post_type FROM $table_name WHERE post_type = 'edd_payment'" );
    

    ob_start();
   // print_r($investisseurs);
   // echo $campaign_id;

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



// FONCTION J'y crois :
function ypcf_jcrois(){
    global $wpdb ;
    $table_jcrois = $wpdb->prefix . "jycrois";

    $campaign      = atcf_get_campaign( $post );
    $campaign_id   =  $campaign->ID;
    $user_id       = wp_get_current_user()->ID;

    
    if(isset($_POST['submit']) )
    {

        if ( is_user_logged_in() )
        { 
            $wpdb->insert( $table_jcrois,
                    array(
                        'user_id'                 => $user_id,
                        'campaign_id'             => $campaign_id,
                        'jcrois'                  => 1
            )); 

        } 
        else
        {
            echo 'Vous n\'êtes pas connecté';
        }

    }
    ?>
    <form name="ypjcrois" action="<?php get_permalink();?>" method="POST" class="ypjcrois-form"> 
        <input type="submit" name="submit" value="J'y crois !" style="background-image:/images/jycrois_gris.png">
    </form>
    <?php   
    

}


function ypcf_jcrois_pas(){
    global $wpdb ;
    $table_jcrois = $wpdb->prefix . "jycrois"; 
    
    $campaign      = atcf_get_campaign( $post );
    $campaign_id   =  $campaign->ID;
    $user_id       = wp_get_current_user()->ID;

    if(isset($_POST['submit']))
    {

        if ( is_user_logged_in() )
        { 

            $wpdb->delete( $table_jcrois,
                    array(
                        'user_id'                 => $user_id,
                        'campaign_id'             => $campaign_id
            )); 

        } 
        else
        {
            echo 'Vous n\'êtes pas connecté';
        }

    }
    ?>
    <form name="ypjcrois" action="<?php get_permalink();?>" method="POST" class="ypjcrois-form"> 
        <input type="submit" name="submit" value="Je n'y crois pas!" style="background-image:/images/jycrois_gris.png">
    </form>
    <?php   
    

}



function ypcf_shortcode_jcrois(){
    global $wpdb ;
    $table_jcrois = $wpdb->prefix . "jycrois";

    $campaign      = atcf_get_campaign( $post );
    $campaign_id   =  $campaign->ID;
    $user_id       = wp_get_current_user()->ID;

    
    $conseils = $wpdb->get_var( "SELECT count(jcrois) FROM $table_jcrois WHERE campaign_id = $campaign_id AND user_id = $user_id " );
    if ( $conseils != 0) {
        ypcf_jcrois_pas();
    }
    else{
      ypcf_jcrois();

    }

}
    
add_shortcode('yproject_crowdfunding_jcrois','ypcf_shortcode_jcrois');
