<?php
// Blocks direct access
if ( ! function_exists( 'is_admin' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Modifie les posts admin
 */
class WDGRESTAPI_Admin_Posts {

	public static function add_actions() {
		add_action( 'add_meta_boxes', 'WDGRESTAPI_Admin_Posts::add_meta_boxes' );
		add_action( 'save_post', 'WDGRESTAPI_Admin_Posts::save_meta_boxes', 10, 2 );
	}
	
	public static function add_meta_boxes() {
		global $post;

		if ( ! is_object( $post ) )
			return;
		
		add_meta_box( 'wdgrestapi_posts_export_static', __( 'Contenu statique', 'wdgrestapi' ), 'WDGRESTAPI_Admin_Posts::wdgrestapi_posts_export_static', 'page', 'side', 'low' );
	}
	
	public static function save_meta_boxes( $post_id, $post ) {
		// Checks if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
		
		$saved_value = filter_input( INPUT_POST, WDGStaticPage::$key_static_content_api_post_id );
		update_post_meta( $post_id, WDGStaticPage::$key_static_content_api_post_id, $saved_value );
	}
	
	public static function wdgrestapi_posts_export_static() {
		global $post;
		$staticpage = new WDGStaticPage( $post->ID );
		$selected_post_id = $staticpage->get_content_post_id();
		$staticpage_list = WDGStaticPage::get_list();
		?>
		<select name="<?php echo WDGStaticPage::$key_static_content_api_post_id; ?>">
			<option value=""></option>
			<?php foreach ( $staticpage_list as $staticpage ): ?>
				<option value="<?php echo $staticpage->ID; ?>" <?php echo selected( $selected_post_id, $staticpage->ID ); ?>><?php echo $staticpage->title; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
}
WDGRESTAPI_Admin_Posts::add_actions();