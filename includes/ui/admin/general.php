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
class WDG_Admin_General {
	
	private static $tabs = array(
		'general' => "G&eacute;n&eacute;ral",
		'terms' => "CGU"
	);

	public static function add_actions() {
		add_action( 'admin_init', 'WDG_Admin_General::save' );
		add_action( 'admin_menu', 'WDG_Admin_General::set_menu' );
	}
	
	public static function set_menu() {
		add_options_page( 'WE DO GOOD', 'WE DO GOOD', 'manage_options', 'wdg-settings', 'WDG_Admin_General::display' );
	}
	
	public static function save() {
		//Sauvegarde toutes les données
		$need_save = filter_input(INPUT_POST, 'save-wdg');
		if (!empty($need_save)) {
			$active_tab = filter_input( INPUT_GET, 'tab' );
			if ( empty( $active_tab ) ) {
				$active_tab = 'general';
			}
			$display_function = 'save_' . $active_tab;
			WDG_Admin_General::{ $display_function }();
		}
	}
	
	public static function save_general() {
		// Données de la plateforme
		$option_platform = array();
		$option_platform[ "context" ] = filter_input( INPUT_POST, 'platform_context' );
		update_option( 'wdg_platform', $option_platform );
	}
	
	public static function save_terms() {
		$edd_settings = get_option( 'edd_settings' );
		$edd_settings[ 'terms_general_version' ] = filter_input( INPUT_POST, 'terms_general_version' );
		update_option( 'edd_settings', $edd_settings );
	}
	
	public static function display() {
		if (!current_user_can('manage_options')) { wp_die( __('You do not have sufficient permissions to access this page.') ); }
		$active_tab = filter_input( INPUT_GET, 'tab' );
		if ( empty( $active_tab ) ) {
			$active_tab = 'general';
		}
		?>

		<form method="post">
			<h2>Paramétrage plateforme</h2>
			
			<h2 class="nav-tab-wrapper">
				<?php foreach( WDG_Admin_General::$tabs as $tab_id => $tab_name ): ?>
					<?php
					$tab_url = add_query_arg( array(
						'settings-updated' => false,
						'tab' => $tab_id
					) );

					$active = $active_tab == $tab_id ? ' nav-tab-active' : '';
					?>

					<a href="<?php echo esc_url( $tab_url ); ?>" title="<?php echo esc_attr( $tab_name ); ?>" class="nav-tab<?php echo $active; ?>"><?php echo esc_html( $tab_name ); ?></a>
				<?php endforeach; ?>
			</h2>

			<?php
			$display_function = 'display_' . $active_tab;
			WDG_Admin_General::{ $display_function }();
			?>

			<input type="hidden" name="save-wdg" value="1" />
			<input type="hidden" name="save-tab" value="<?php echo $active_tab; ?>" />
			<p class="submit"><input type="submit" name="Submit" class="button-primary" value="Enregistrer" /></p>
		</form>
		<?php
	}
	
	public static function display_general() {
		?>
		<h3>Contexte de la plateforme</h3>
		<label for="platform_context">
			<select id="platform_context" name="platform_context">
				<option value="wedogood" <?php selected( ATCF_CrowdFunding::get_platform_context(), "wedogood" ); ?>>WE DO GOOD</option>
				<option value="royaltycrowdfunding" <?php selected( ATCF_CrowdFunding::get_platform_context(), "royaltycrowdfunding" ); ?>>royaltycrowdfunding.fr</option>
			</select>
		</label>
		<?php
	}
	
	public static function display_terms() {
		$edd_settings = get_option( 'edd_settings' );
		?>
		<h3>Version des conditions générales</h3>
		<label for="terms_general_version">
			<input type="text" value="<?php echo $edd_settings[ 'terms_general_version' ]; ?>" name="terms_general_version" />
		</label>
		<br /><br />
		<?php
	}
}
WDG_Admin_General::add_actions();