
<?php 
    date_default_timezone_set("Europe/Paris");
    require_once("common.php");
?>
<?php get_header(); ?>

	<div id="content">
		<div class="padder">

			<?php do_action( 'bp_before_blog_single_post' ); ?>

			<div class="page" id="blog-single" role="main">

			<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<div class="author-box">
						<p><?php printf( _x( 'by %s', 'Post written by...', 'buddypress' ), str_replace( '<a href=', '<a rel="author" href=', bp_core_get_userlink( $post->post_author ) ) ); ?></p>
					</div>

					<div class="post-content">
						<h2 class="posttitle"><?php the_title(); ?></h2>

						<p class="date">
							<?php printf( __( '%1$s <span>in %2$s</span>', 'buddypress' ), get_the_date(), get_the_category_list( ', ' ) ); ?>
						</p>

						<div class="entry">
							<?php the_content( __( 'Read the rest of this entry &rarr;', 'buddypress' ) ); ?>
							<div id="projects_current" class="projects_preview">
								<?php printPreviewSingleProject($post_id); ?>
							</div>		

						</div>

						<p class="postmetadata"><?php the_tags( '<span class="tags">' . __( 'Tags: ', 'buddypress' ), ', ', '</span>' ); ?>&nbsp;</p>
						
						<div>
						    <a href="#">[TODO: bouton "J'y crois"] <?php echo __('Jy crois', 'yproject'); ?></a>
						</div>
						<div>
						    <a href="#">[TODO: bouton "Suivre le blog test"] <?php echo __('Suivre le blog', 'yproject'); ?></a>
						    <a href="#">[TODO: lien vers le blog du projet] <?php echo __('Blog', 'yproject'); ?></a>
						</div>
						<div>
						    <a href="#">[TODO: lien vers le forum du projet] <?php echo __('Forum', 'yproject'); ?></a>
						</div>
					</div>

				</div>

			<?php endwhile; else: ?>

				<p><?php _e( 'Sorry, no posts matched your criteria.', 'buddypress' ); ?></p>

			<?php endif; ?>

		</div>

		<?php do_action( 'bp_after_blog_single_post' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->

<?php get_footer(); ?>