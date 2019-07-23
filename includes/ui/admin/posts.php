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
class WDG_Admin_Posts {

	public static function add_actions() {
		add_action( 'add_meta_boxes', 'WDG_Admin_Posts::add_meta_boxes' );
		add_action( 'save_post', 'WDG_Admin_Posts::save_meta_boxes', 10, 2 );
	}
	
	public static function add_meta_boxes() {
		global $post;

		if ( ! is_object( $post ) )
			return;
		
		add_meta_box( 'cache_as_html', __( 'Enregistrer en cache HTML', 'yproject' ), 'WDG_Admin_Posts::cache_as_html', 'page', 'side', 'low' );
	}
	
	public static function save_meta_boxes( $id_post, $post ) {
		// Checks if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $id_post ) ) {
            return;
        }
		
		$saved_value = filter_input( INPUT_POST, WDG_File_Cacher::$key_post_is_cached_as_html );
		update_post_meta( $id_post, WDG_File_Cacher::$key_post_is_cached_as_html, $saved_value );
		$WDG_File_Cacher = WDG_File_Cacher::current();
		$WDG_File_Cacher->delete_by_post_id( $id_post );
		if ( $saved_value == '1' ) {
			$WDG_File_Cacher->queue_cache_post( $id_post );
		}
	}
	
	public static function cache_as_html() {
		global $post;
		$str_is_cached = get_post_meta( $post->ID, WDG_File_Cacher::$key_post_is_cached_as_html, TRUE );
		?>
		<select name="<?php echo WDG_File_Cacher::$key_post_is_cached_as_html; ?>">
			<option value="0" <?php echo selected( ( $str_is_cached != '1' ) ); ?>>Non</option>
			<option value="1" <?php echo selected( $str_is_cached, '1' ); ?>>Oui</option>
		</select>
		<?php
		
	}
}
WDG_Admin_Posts::add_actions();