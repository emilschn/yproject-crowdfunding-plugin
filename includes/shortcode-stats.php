<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_stats() {
    global $wpdb, $campaign, $post, $edd_options ;
    $table_jcrois = $wpdb->prefix . "yp_jycrois"; 
    $table_activity = $wpdb->prefix .'bp_activity'; 
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



// FONCTION J'y crois
function ypcf_shortcode_jcrois(){
    $show_jycroispas = false;
    ?>
    <div>
    <?php

    if ( is_user_logged_in() ) { 
	
	global $wpdb, $post;
	$table_jcrois = $wpdb->prefix . "jycrois";
	
	if (isset($_GET['campaign_id'])) $post = get_post($_GET['campaign_id']);
	$campaign               = atcf_get_campaign( $post );
	$campaign_id            = $campaign->ID;
	
	// Construction des urls utilisés dans les liens du fil d'actualité
	// url d'une campagne précisée par son nom 
	$campaign_url  = get_permalink($post->ID);
	$post_title = $post->post_title;
	$url_campaign = '<a href="'.$campaign_url.'">'.$post_title.'</a>';
	//url d'un utilisateur précis
	$user_id                = wp_get_current_user()->ID;
	$user_display_name      = wp_get_current_user()->display_name;
	$url_profile = '<a href="' . bp_core_get_userlink($user_id, false, true) . '">' . $user_display_name . '</a>';

	
	if(isset($_POST['submit_jycroispas'])) {
	    $wpdb->delete( $table_jcrois,
		array(
		    'user_id'      => $user_id,
		    'campaign_id'  => $campaign_id
		)
	    );

	    // Inserer l'information dans la table du fil d'activité  de la BDD wp_bp_activity 
	    bp_activity_delete(array (
		'user_id'   => $user_id,
		'component' => 'profile',
		'type'      => 'jycrois',
		'action'    => $url_profile.' croit au projet '.$url_campaign
	    ));

	    
	} else if(isset($_POST['submit_jycrois']) )  {
            $wpdb->insert( $table_jcrois,
		array(
		    'user_id'	    => $user_id,
		    'campaign_id'   => $campaign_id
		)
	    ); 
	    bp_activity_add(array (
		'component' => 'profile',
		'type'      => 'jycrois',
		'action'    => $url_profile.' croit au projet '.$url_campaign
	    ));
        }
	
	$users = $wpdb->get_results( "SELECT user_id FROM $table_jcrois WHERE campaign_id = $campaign_id" );
	foreach ( $users as $user ) { 
	    if ( $user->user_id == $user_id) {
		$show_jycroispas = true;
		break;
	    }
	}

	if (isset($_POST['submit_vote'])) { 
	    if (!$show_jycroispas && isset($_POST[ 'impact' ]) && $_POST[ 'impact' ] == "positif" && isset($_POST[ 'maturite' ]) && $_POST[ 'maturite' ] == "pret") {
		$wpdb->insert( $table_jcrois,
		    array(
			'user_id'	=> $user_id,
			'campaign_id'   => $campaign_id
		    )
		); 
		bp_activity_add(array (
		    'component' => 'profile',
		    'type'      => 'jycrois',
		    'action'    => $url_profile.' croit au projet '.$url_campaign
		));
		$show_jycroispas = true;
	    }
	}
	
    } else {
	if (isset($_POST['submit_jycroispas']) || isset($_POST['submit_jycrois'])) {
	    $page_connexion = get_page_by_path('connexion'); ?>
            <a href="<?php echo get_permalink($page_connexion->ID); ?>">Connectez-vous !</a>
	    <?php
	}    
    }
    
    if ($show_jycroispas == true) {
	?>
	<form name="ypjcrois_pas" action="<?php get_permalink();?>" method="POST" class="jycrois"> 
	    <input id="jcrois_pas" type="submit" name="submit_jycroispas" value="<?php do_shortcode('[yproject_crowdfunding_count_jcrois]'); ?>" class="bouton_jcrois" >
	</form>
	<?php
    } else {
	?>
	<form name="ypjycrois" action="<?php get_permalink();?>" method="POST" > 
	    <input id="jcrois" type="submit" name="submit_jycrois" value="<?php do_shortcode('[yproject_crowdfunding_count_jcrois]'); ?>" class="bouton_jcrois">
	</form>
	<?php
    }
    ?>
    </div>
    <?php
}  
add_shortcode('yproject_crowdfunding_jcrois','ypcf_shortcode_jcrois');



function ypcf_shortcode_count_jcrois(){
    global $wpdb, $post;
    $table_jcrois = $wpdb->prefix . "jycrois";

    $campaign      = atcf_get_campaign( $post );
    $campaign_id   = $campaign->ID;
    
    $cont = $wpdb->get_var( "SELECT count(campaign_id) FROM $table_jcrois WHERE campaign_id = $campaign_id" );
    echo $cont;
}
add_shortcode('yproject_crowdfunding_count_jcrois','ypcf_shortcode_count_jcrois');