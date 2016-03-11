<?php
/**
 * Plugin Name: YP Crowdfunding
 * Author:      WEDOGOOD
 * Author URI:  http://wedogood.co
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Check if Easy Digital Downloads is active */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/**
 * Main Crowd Funding Class
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */
final class ATCF_CrowdFunding {
	public static $option_name = 'wdg';

	/**
	 * @var crowdfunding The one true AT_CrowdFunding
	 */
	private static $instance;

	/**
	 * Main Crowd Funding Instance
	 *
	 * Ensures that only one instance of Crowd Funding exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return The one true Crowd Funding
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new ATCF_CrowdFunding;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}

		return self::$instance;
	}

	/** Private Methods *******************************************************/

	/**
	 * Set some smart defaults to class variables. Allow some of them to be
	 * filtered to allow for early overriding.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	private function setup_globals() {
		/** Versions **********************************************************/

		$this->version    = '1.3.6';
		$this->db_version = '1';

		/** Paths *************************************************************/

		$this->file         = __FILE__;
		$this->basename     = apply_filters( 'atcf_plugin_basenname', plugin_basename( $this->file ) );
		$this->plugin_dir   = apply_filters( 'atcf_plugin_dir_path',  plugin_dir_path( $this->file ) );
		$this->plugin_url   = apply_filters( 'atcf_plugin_dir_url',   plugin_dir_url ( $this->file ) );

		$this->template_url = apply_filters( 'atcf_plugin_template_url', 'yproject/' );

		// Includes
		$this->includes_dir = apply_filters( 'atcf_includes_dir', trailingslashit( $this->plugin_dir . 'includes'  ) );
		$this->includes_url = apply_filters( 'atcf_includes_url', trailingslashit( $this->plugin_url . 'includes'  ) );

		// Languages
		$this->lang_dir     = apply_filters( 'atcf_lang_dir',     trailingslashit( $this->plugin_dir . 'languages' ) );

		/** Misc **************************************************************/

		$this->domain       = 'atcf'; 
	}

	/**
	 * Include required files.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	private function includes() {
		
		require( $this->includes_dir . 'data/language_list.php' );
		require( $this->includes_dir . 'data/campaign.php' );
		require( $this->includes_dir . 'data/campaigns.php' );
		require( $this->includes_dir . 'data/campaign-votes.php' );
		require( $this->includes_dir . 'data/campaign-investments.php' );
		require( $this->includes_dir . 'data/roi-declaration.php' );
		require( $this->includes_dir . 'data/kyc-file.php' );
		require( $this->includes_dir . 'data/rewards.php');
		require( $this->includes_dir . 'data/organisation.php' );
		require( $this->includes_dir . 'data/user.php' );
		require( $this->includes_dir . 'data/bopp/bopp-lib.php' );
		require( $this->includes_dir . 'data/bopp/bopp-lib-helpers.php' );
		require( $this->includes_dir . 'data/bopp/bopp-entities/bopp-users.php' );
		require( $this->includes_dir . 'data/bopp/bopp-entities/bopp-organisations.php' );
		
		require( $this->includes_dir . '../../social-connect/facebook/facebook.php' );
		require( $this->includes_dir . 'control/social/twitter/TwitterAPIExchange.php' );
		
		require( $this->includes_dir . 'control/ajax.php' );
		require( $this->includes_dir . 'control/settings.php' );
		require( $this->includes_dir . 'control/logs.php' );
		require( $this->includes_dir . 'control/permalinks.php' );
		require( $this->includes_dir . 'control/invest-lib.php' );
		require( $this->includes_dir . 'control/mangopay/common.php' );
		require( $this->includes_dir . 'control/mangopay/utils.php' );
		require( $this->includes_dir . 'control/signsquid/common.php' );
		require( $this->includes_dir . 'control/html2pdf/html2pdf.class.php' );
		require( $this->includes_dir . 'control/pdf_generator.php' );
		require( $this->includes_dir . 'control/notifications/notifications-emails.php' );
		require( $this->includes_dir . 'control/notifications/notifications-slack.php' );
		require( $this->includes_dir . 'control/forms/projects.php' );
		require( $this->includes_dir . 'control/gateways/lemonway-lib.php' );
		require( $this->includes_dir . 'control/gateways/lemonway-lib-helpers.php' );
		
		require( $this->includes_dir . 'ui/shortcodes/shortcodes-lib.php' );
		require( $this->includes_dir . 'ui/shortcodes/shortcode-submit.php' );
		require( $this->includes_dir . 'ui/shortcodes/shortcode-vote-results.php' );
		require( $this->includes_dir . 'ui/shortcodes/shortcode-update.php' );
		require( $this->includes_dir . 'ui/shortcodes/shortcode-edit-news.php' );
		require( $this->includes_dir . 'ui/ui-helpers.php' );
		

		do_action( 'atcf_include_files' );

		if ( ! is_admin() )
			return ;

		do_action( 'atcf_include_admin_files' );
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	private function setup_actions() {
		WDGAjaxActions::init_actions();
		if (get_option('wdg_version') != $this->version) {
			WDGROIDeclaration::upgrade_db();
			WDGKYCFile::upgrade_db();
			update_option('wdg_version', $this->version);
		}
		
		
		add_action( 'init', array( $this, 'is_edd_activated' ), 1 );
		add_filter( 'template_include', array( $this, 'template_loader' ) );
		
		add_filter( 'locale', array( $this, 'set_locale' ) );
		add_action( 'init', array( $this, 'save_locale' ), 1 );
		
		do_action( 'atcf_setup_actions' );

		$this->load_textdomain();
	}

	
	/**
	 * Easy Digital Downloads
	 *
	 * @since Appthemer CrowdFunding 0.2-alpha
	 *
	 * @return void
	 */
	function is_edd_activated() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			if ( is_plugin_active( $this->basename ) ) {
				deactivate_plugins( $this->basename );
				unset ($_GET[ 'activate' ] ); // Ghetto

				add_action( 'admin_notices', array( $this, 'edd_notice' ) );
			}
		}
	}
	
	/**
	 * Définition de la langue en cours
	 * @global string $locale
	 */
	function set_locale( $locale ) {
		$input_get_lang = filter_input(INPUT_GET, 'lang');
		if ( empty ( $input_get_lang ) ) {
			$input_get_lang = filter_input(INPUT_POST, 'lang');
		}
		if ( !empty( $input_get_lang ) ) {
			$locale = $input_get_lang;
			global $save_locale;
			$save_locale = $locale;
		} else {
			if ( isset( $_COOKIE['locale'] ) ) {
				$locale = $_COOKIE['locale'];
			}
		}
		
		return $locale;
	}
	
	function save_locale() {
		global $save_locale;
		if ( isset($save_locale) ) {
			setcookie( 'locale', $save_locale, 10 * DAYS_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		}
	}
	
	public static function get_translated_setting( $setting_id, $locale = '' ) {
		if ($locale == '') {
			$locale = get_locale();
		}
		
		$options_saved = get_option(ATCF_CrowdFunding::$option_name .'_'. $locale);
		if ( !empty( $options_saved[$setting_id] ) ) {
			$buffer = $options_saved[$setting_id];
		} else {
			global $edd_options;
			$buffer = $edd_options[$setting_id];
		}
		
		return $buffer;
	}

	/**
	 * Admin notice.
	 *
	 * @since Appthemer CrowdFunding 0.2-alpha
	 *
	 * @return void
	 */
	function edd_notice() {
?>
		<div class="updated">
			<p><?php printf( 
						__( '<strong>Notice:</strong> Crowdfunding by Astoundify requires <a href="%s">Easy Digital Downloads</a> in order to function properly.', 'atcf' ), 
						wp_nonce_url( network_admin_url( 'update.php?action=install-plugin&plugin=easy-digital-downloads' ), 'install-plugin_easy-digital-downloads' )
				); ?></p>
		</div>
<?php
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. AT_CrowdFunding looks for theme
	 * overides in /theme_directory/crowdfunding/ by default
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/master/woocommerce.php
	 *
	 * @access public
	 * @param mixed $template
	 * @return string $template The path of the file to include
	 */
	public function template_loader( $template ) {
		global $wp_query;
		
		$find    = array();
		$files   = array();

		/** Check if we are editing */
		if ( isset ( $wp_query->query_vars[ 'edit' ] ) && 
			 is_singular( 'download' ) && 
			 ( $wp_query->queried_object->post_author == get_current_user_id() || current_user_can( 'manage_options' ) ) &&
			 atcf_theme_supports( 'campaign-edit' )
		) {
			do_action( 'atcf_found_edit' );

			$files = apply_filters( 'atcf_crowdfunding_templates_edit', array( 'single-campaign-edit.php' ) );
		} 

		/** Check if viewing a widget */
		else if ( isset ( $wp_query->query_vars[ 'widget' ] ) && 
			 is_singular( 'download' ) &&
			 atcf_theme_supports( 'campaign-widget' )
		) {
			do_action( 'atcf_found_widget' );

			$files = apply_filters( 'atcf_crowdfunding_templates_widget', array( 'campaign-widget.php' ) );
		}

		/** Check if viewing standard campaign */
		else if ( is_singular( 'download' ) ) {
			do_action( 'atcf_found_single' );

			$files = apply_filters( 'atcf_crowdfunding_templates_campaign', array( 'single-campaign.php', 'single-download.php', 'single.php' ) );
		} 

		/** Check if viewing archives */
		else if ( is_post_type_archive( 'download' ) || is_tax( 'download_category' ) ) {
			do_action( 'atcf_found_archive' );

			$files = apply_filters( 'atcf_crowdfunding_templates_archive', array( 'archive-campaigns.php', 'archive-download.php', 'archive.php' ) );
		}

		$files = apply_filters( 'atcf_template_loader', $files );

		foreach ( $files as $file ) {
			$find[] = $file;
			$find[] = $this->template_url . $file;
		}

		if ( ! empty( $files ) ) {
			$template = locate_template( $find );

			if ( ! $template ) 
				$template = $this->plugin_dir . 'templates/' . $file;
		}

		return $template;
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

		// Look in global /wp-content/languages/atcf folder
		if ( file_exists( $mofile_global ) ) {
			return load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/appthemer-crowdfunding/languages/ folder
		} elseif ( file_exists( $mofile_local ) ) {
			return load_textdomain( $this->domain, $mofile_local );
		}

		return false;
	}
}

/**
 * Does the current theme support certain functionality?
 *
 * @since AppThemer Crowdfunding 1.3
 *
 * @param string $feature The name of the feature to check.
 * @return boolean If the feature is supported or not.
 */
function atcf_theme_supports( $feature ) {
	$supports = get_theme_support( 'appthemer-crowdfunding' );
	$supports = $supports[0];

	return isset ( $supports[ $feature ] );
}

/**
 * The main function responsible for returning the one true Crowd Funding Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $crowdfunding = crowdfunding(); ?>
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return The one true Crowd Funding Instance
 */
function crowdfunding() {
	return ATCF_CrowdFunding::instance();
}

crowdfunding();