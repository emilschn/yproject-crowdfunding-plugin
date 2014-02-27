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
	$days_remaining = $campaign->days_remaining();
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
			<?php echo $days_remaining; ?>
		    </div>
		    <div class="project_preview_item_picto">
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/cible.png" />
			<?php echo $campaign->minimum_goal(true); ?>
		    </div>
		    <div class="project_preview_item_picto">
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/good.png" />
			<?php do_shortcode('[yproject_crowdfunding_count_jcrois]'); ?>
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

function ypcf_fake_sharing_display($text = '', $echo = false ) {
	global $post, $wp_current_filter;

	if ( empty( $post ) )
//		return $text;

	if ( is_preview() ) {
//		return $text;
	}

	// Don't output flair on excerpts
	if ( in_array( 'get_the_excerpt', (array) $wp_current_filter ) ) {
//		return $text;
	}

	// Don't allow flair to be added to the_content more than once (prevent infinite loops)
	$done = false;
	foreach ( $wp_current_filter as $filter ) {
		if ( 'the_content' == $filter ) {
			if ( $done )
				return $text;
			else
				$done = true;
		}
	}

	// check whether we are viewing the front page and whether the front page option is checked
	$options = get_option( 'sharing-options' );
	$display_options = $options['global']['show'];

	if ( is_front_page() && ( is_array( $display_options ) && ! in_array( 'index', $display_options ) ) )
//		return $text;

	if ( is_attachment() && in_array( 'the_excerpt', (array) $wp_current_filter ) ) {
		// Many themes run the_excerpt() conditionally on an attachment page, then run the_content().
		// We only want to output the sharing buttons once.  Let's stick with the_content().
//		return $text;
	}

	$sharer = new Sharing_Service();
	$global = $sharer->get_global_options();

	/*$show = false;
	if ( !is_feed() ) {
		if ( is_singular() && in_array( get_post_type(), $global['show'] ) ) {
			$show = true;
		} elseif ( in_array( 'index', $global['show'] ) && ( is_home() || is_archive() || is_search() ) ) {
			$show = true;
		}
	}

	// Pass through a filter for final say so
	$show = apply_filters( 'sharing_show', $show, $post );*/
	$show = true;

	// Disabled for this post?
	$switched_status = get_post_meta( $post->ID, 'sharing_disabled', false );

	if ( !empty( $switched_status ) )
		$show = false;

	// Allow to be used on P2 ajax requests for latest posts.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && 'get_latest_posts' == $_REQUEST['action'] )
		$show = true;

	$sharing_content = '';

	if ( $show ) {
		$enabled = apply_filters( 'sharing_enabled', $sharer->get_blog_services() );

		if ( count( $enabled['all'] ) > 0 ) {
			global $post;

			$dir = get_option( 'text_direction' );

			// Wrapper
			$sharing_content .= '<div class="sharedaddy sd-sharing-enabled"><div class="robots-nocontent sd-block sd-social sd-social-' . $global['button_style'] . ' sd-sharing">';
			if ( $global['sharing_label'] != '' )
				$sharing_content .= '<h3 class="sd-title">' . $global['sharing_label'] . '</h3>';
			$sharing_content .= '<div class="sd-content"><ul>';

			// Visible items
			$visible = '';
			foreach ( $enabled['visible'] as $id => $service ) {
				// Individual HTML for sharing service
				$visible .= '<li class="share-' . $service->get_class() . '">' . $service->get_display( $post ) . '</li>';
			}

			$parts = array();
			$parts[] = $visible;
			if ( count( $enabled['hidden'] ) > 0 ) {
				if ( count( $enabled['visible'] ) > 0 )
					$expand = __( 'More', 'jetpack' );
				else
					$expand = __( 'Share', 'jetpack' );
				$parts[] = '<li><a href="#" class="sharing-anchor sd-button share-more"><span>'.$expand.'</span></a></li>';
			}

			if ( $dir == 'rtl' )
				$parts = array_reverse( $parts );

			$sharing_content .= implode( '', $parts );
			$sharing_content .= '<li class="share-end"></li></ul>';

			if ( count( $enabled['hidden'] ) > 0 ) {
				$sharing_content .= '<div class="sharing-hidden"><div class="inner" style="display: none;';

				if ( count( $enabled['hidden'] ) == 1 )
					$sharing_content .= 'width:150px;';

				$sharing_content .= '">';

				if ( count( $enabled['hidden'] ) == 1 )
					$sharing_content .= '<ul style="background-image:none;">';
				else
					$sharing_content .= '<ul>';

				$count = 1;
				foreach ( $enabled['hidden'] as $id => $service ) {
					// Individual HTML for sharing service
					$sharing_content .= '<li class="share-'.$service->get_class().'">';
					$sharing_content .= $service->get_display( $post );
					$sharing_content .= '</li>';

					if ( ( $count % 2 ) == 0 )
						$sharing_content .= '<li class="share-end"></li>';

					$count ++;
				}

				// End of wrapper
				$sharing_content .= '<li class="share-end"></li></ul></div></div>';
			}

			$sharing_content .= '</div></div></div>';

			// Register our JS
			wp_register_script( 'sharing-js', plugin_dir_url( __FILE__ ).'sharing.js', array( 'jquery' ), '20121205' );
			add_action( 'wp_footer', 'sharing_add_footer' );
		}
	}

	if ( $echo )
		echo $text.$sharing_content;
	else
		return $text.$sharing_content;
}
?>