<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
	if ( !empty($users[0]->id) ) $show_jycroispas = 1;

	if (isset($_POST['submit_vote'])) { 
	    if (!$show_jycroispas && isset($_POST[ 'validate_project' ]) && $_POST[ 'validate_project' ] == 1) {
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

?>
