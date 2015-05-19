<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Dernière étape : le paiement a été effectué, on revient sur le site
 */
function ypcf_shortcode_invest_share($atts, $content = '') {
    $buffer = '';
    if (isset($_GET['campaign_id'])) {
	$campaign_url  = get_permalink($_GET['campaign_id']);
	
	$buffer .= ypcf_print_invest_breadcrumb(5);
	$buffer .= $content;
	
	$post_campaign = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post_campaign );
	global $post;
	$post = $post_campaign;
	
	ob_start();
	?>
	<div class="projects_preview projects_current projects_current_temp" style="margin-left: 370px;">
	    <div class="preview_item_<?php echo $_GET['campaign_id']; ?> project_preview_item">

		<div class="project_preview_item_part">
		    <div class="project_preview_item_pictos">
		    <div class="project_preview_item_picto">
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/france.png" />
			<?php 
			    $campaign_location = $campaign->location();
			    $exploded = explode(' ', $campaign_location);
			    if (count($exploded) > 1) $campaign_location = $exploded[0];
			    echo (($campaign_location != '') ? $campaign_location : 'France'); 
			?>
		    </div>
		    <div class="project_preview_item_picto">
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/horloge.png" />
			<?php echo $campaign->time_remaining_str(); ?>
		    </div>
		    <div class="project_preview_item_picto">
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/cible.png" />
			<?php echo $campaign->minimum_goal(true); ?>
		    </div>
		    <div class="project_preview_item_picto">
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/good.png" />
			<?php echo $campaign->get_jycrois_nb(); ?>
		    </div>
		    <div style="clear: both"></div>
		    </div>


		    <div class="project_preview_item_progress">
		    <?php
			$percent = min(100, $campaign->percent_minimum_completed(false));
			$width = 150 * $percent / 100;
			$width_min = 0;
			if ($percent >= 100 && $campaign->is_flexible()) {
			    $percent_min = $campaign->percent_minimum_to_total();
			    $width_min = 150 * $percent_min / 100;
			}
			?>
			<a href="<?php the_permalink(); ?>">
			<div class="project_preview_item_progressbg">
			    <div class="project_preview_item_progressbar" style="width:<?php echo $width; ?>px">
				<?php if ($width_min > 0): ?>
				<div style="width: <?php echo $width_min; ?>px; height: 20px; border: 0px; border-right: 1px solid white;">&nbsp;</div>
				<?php else: ?>
				&nbsp;
				<?php endif; ?>
			    </div>
			</div>
			<span class="project_preview_item_progressprint"><?php echo $campaign->percent_minimum_completed(); ?></span>
			</a>
		    </div>
		</div>
	    </div>
	    <div style="clear: both"></div>
	</div>
	<div style="clear: both"></div>
		
	<?php
	$buffer .= ob_get_clean();

	$buffer .= '<center>';
	if (class_exists('Sharing_Service')) {
	    //Liens pour partager
	    $buffer .= ypcf_fake_sharing_display();
	} else {
	    $buffer .= 'Le service de partage est momentan&eacute;ment d&eacute;sactiv&eacute;.';
	}
	$buffer .= '</center>';
	$buffer .= '<br /><br />&lt;&lt; <a href="'.$campaign_url.'">Retour au projet</a>';
	
    } else {
	wp_redirect(site_url());
	exit();
    }
    
    return $buffer;
}
add_shortcode( 'yproject_crowdfunding_invest_share', 'ypcf_shortcode_invest_share' );


?>