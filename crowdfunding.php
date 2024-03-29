<?php
/**
 * Plugin Name: YP Crowdfunding
 * Author:      WEDOGOOD
 * Author URI:  http://wedogood.co
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/** Check if Easy Digital Downloads is active */
include_once ABSPATH . 'wp-admin/includes/plugin.php';

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
	 * @return ATCF_CrowdFunding
	 */
	public static function instance() {
		if ( !isset( self::$instance ) ) {
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

		$this->version    = '1.821';
		$this->db_version = '1';

		/** Paths *************************************************************/

		$this->file         = __FILE__;
		$this->basename     = apply_filters( 'atcf_plugin_basenname', plugin_basename( $this->file ) );
		$this->plugin_dir   = apply_filters( 'atcf_plugin_dir_path', plugin_dir_path( $this->file ) );
		$this->plugin_url   = apply_filters( 'atcf_plugin_dir_url', plugin_dir_url( $this->file ) );

		$this->template_url = apply_filters( 'atcf_plugin_template_url', 'yproject/' );

		// Includes
		$this->includes_dir = apply_filters( 'atcf_includes_dir', trailingslashit( $this->plugin_dir . 'includes'  ) );
		$this->includes_url = apply_filters( 'atcf_includes_url', trailingslashit( $this->plugin_url . 'includes'  ) );

		// Languages
		$this->lang_dir     = apply_filters( 'atcf_lang_dir', trailingslashit( $this->plugin_dir . 'languages' ) );

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
		require $this->includes_dir . 'control/cache/db-cacher.php';
		require $this->includes_dir . 'control/cache/file-cacher.php';

		require $this->includes_dir . 'data/language_list.php';
		require $this->includes_dir . 'data/campaign.php';
		require $this->includes_dir . 'data/campaigns.php';
		require $this->includes_dir . 'data/campaign-votes.php';
		require $this->includes_dir . 'data/campaign-investments.php';
		require $this->includes_dir . 'data/campaign-bill.php';
		require $this->includes_dir . 'data/campaign-debt-files.php';
		require $this->includes_dir . 'data/campaign-notifications.php';
		require $this->includes_dir . 'data/config-texts.php';
		require $this->includes_dir . 'data/config-texts-emails.php';
		require $this->includes_dir . 'data/roi-declaration.php';
		require $this->includes_dir . 'data/adjustment.php';
		require $this->includes_dir . 'data/roi.php';
		require $this->includes_dir . 'data/roi-tax.php';
		require $this->includes_dir . 'data/kyc-file.php';
		require $this->includes_dir . 'data/user-interface.php';
		require $this->includes_dir . 'data/organization.php';
		require $this->includes_dir . 'data/user.php';
		require $this->includes_dir . 'data/user-investments.php';
		require $this->includes_dir . 'data/subscription.php';
		require $this->includes_dir . 'data/investment-contract.php';
		require $this->includes_dir . 'data/investment-signature.php';
		require $this->includes_dir . 'data/staticpage.php';
		require $this->includes_dir . 'data/geolocation/country_list.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-lib.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-organization.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-user.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-user-conformity.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-project.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-project-draft.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-investment.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-investment-draft.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-investment-contract.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-investment-contract-history.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-declaration.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-adjustment.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-roi.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-roi-tax.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-bankinfo.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-poll-answer.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-contract-model.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-contract.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-file.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-file-kyc.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-queued-action.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-sendinblue-template.php';
		require $this->includes_dir . 'data/wdgwprest/wdgwprest-entities/wdgwprest-subscription.php';

		require $this->includes_dir . 'control/api-calls.php';
		require $this->includes_dir . 'control/cron.php';
		require $this->includes_dir . 'control/emails.php';
		require $this->includes_dir . 'control/invest-lib.php';
		require $this->includes_dir . 'control/investment.php';
		require $this->includes_dir . 'control/logs.php';
		require $this->includes_dir . 'control/pdf_generator.php';
		require $this->includes_dir . 'control/permalinks.php';
		require $this->includes_dir . 'control/queue.php';
		require $this->includes_dir . 'control/routes.php';
		require $this->includes_dir . 'control/settings.php';
		require $this->includes_dir . 'control/forms/form.php';
		require $this->includes_dir . 'control/forms/projects.php';
		require $this->includes_dir . 'control/forms/users.php';
		require $this->includes_dir . 'control/forms/user-details.php';
		require $this->includes_dir . 'control/forms/user-subscription-contract.php';
		require $this->includes_dir . 'control/forms/vote.php';
		require $this->includes_dir . 'control/gateways/lemonway-lib.php';
		require $this->includes_dir . 'control/gateways/lemonway-lib-errors.php';
		require $this->includes_dir . 'control/gateways/lemonway-document.php';
		require $this->includes_dir . 'control/gateways/lemonway-notification.php';
		require $this->includes_dir . 'control/lib/validator.php';
		require $this->includes_dir . 'control/notifications/notifications-emails.php';
		require $this->includes_dir . 'control/notifications/notifications-api-shortcodes.php';
		require $this->includes_dir . 'control/notifications/notifications-api.php';
		require $this->includes_dir . 'control/notifications/notifications-slack.php';
		require $this->includes_dir . 'control/notifications/notifications-asana.php';
		require $this->includes_dir . 'control/notifications/notifications-zapier.php';
		require $this->includes_dir . 'control/requests/ajax.php';
		require $this->includes_dir . 'control/requests/post.php';
		require $this->includes_dir . 'control/sendinblue/sendinblue-v3-helper.php';

		require $this->includes_dir . 'ui/shortcodes/shortcodes-lib.php';
		require $this->includes_dir . 'ui/shortcodes/shortcode-edit-news.php';
		require $this->includes_dir . 'ui/ui-helpers.php';

		if ( is_admin() ) {
			require $this->includes_dir . 'ui/admin/general.php';
			require $this->includes_dir . 'ui/admin/posts.php';
		}

		do_action( 'atcf_include_files' );

		if ( !is_admin() ) {
			return;
		}
		do_action( 'atcf_include_admin_files' );
	}

	public function include_control($control_name) {
		require_once $this->includes_dir . 'control/'.$control_name.'.php';
	}

	public function include_facebook() {
		$this->include_control( 'social/FacebookApp/autoload' );
	}

	public function include_html2pdf() {
		$this->include_control( 'html2pdf/html2pdf-v5-helper' );
    }

	public function include_form($form_name) {
		require_once $this->includes_dir . 'control/forms/'.$form_name.'.php';
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
		WDGPostActions::init_actions();

		if (get_option('wdg_version') != $this->version) {
			WDG_Cache_Plugin::upgrade_db();
			WDGCampaignVotes::upgrade_db();
			update_option('wdg_version', $this->version);
		}

		add_filter( 'template_include', array( $this, 'template_loader' ) );

		add_filter( 'locale', array( $this, 'set_locale' ) );
		add_action( 'init', array( $this, 'save_locale' ), 1 );
		add_action( 'wpml_language_cookie_added', array( $this, 'update_locale' ), 10, 1 );

		do_action( 'atcf_setup_actions' );

		add_filter( 'override_load_textdomain', 'ATCF_CrowdFunding::override_load_textdomain', 10, 3 );
		$this->load_textdomain();
	}

	public static function override_load_textdomain($override, $domain, $mofile) {
		if ( $domain == 'easy-digital-downloads' ) {
			return true;
		}

		return $override;
	}

	/**
	 * Définition de la langue en cours
	 * @global string $locale
	 */
	function set_locale($locale_input) {
		$input_get_lang = filter_input( INPUT_GET, 'lang' );
		if ( empty( $input_get_lang ) ) {
			$input_get_lang = filter_input( INPUT_POST, 'lang' );
		}

		if ( !empty( $input_get_lang ) ) {
			$locale_input = $input_get_lang;
		} else {
			if ( isset( $_COOKIE[ 'locale' ] ) ) {
				$locale_input = $_COOKIE[ 'locale' ];
			}
		}

		if ( !empty( $locale_input ) ) {
			global $locale;
			switch ( $locale_input ) {
				case 'fr':
					$locale = 'fr_FR';
					break;

				case 'en':
					$locale = 'en_US';
					break;

				default:
					$locale = $locale_input;
					break;
			}
		}

		return $locale_input;
	}

	function save_locale() {
		$input_get_lang = filter_input(INPUT_GET, 'lang');
		if ( !empty( $input_get_lang ) ) {
			setcookie( 'locale', $input_get_lang, time() + 10 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	public function update_locale($lang_code) {
		if ( !empty( $lang_code ) ) {
			global $locale;
			$locale = $lang_code;
			if ( !headers_sent() ) {
				setcookie( 'locale', $lang_code, time() + 10 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
			}
		}
	}

	public static function get_platform_setting($setting_key) {
		$option_platform = get_option('wdg_platform');

		return $option_platform[ $setting_key ];
	}

	public static function get_translated_setting($setting_id, $input_locale = '') {
		if ($input_locale == '') {
			global $locale;
			$input_locale = $locale;
		}

		$options_saved = get_option(ATCF_CrowdFunding::$option_name .'_'. $input_locale);
		if ( !empty( $options_saved[$setting_id] ) ) {
			$buffer = $options_saved[$setting_id];
		} else {
			global $edd_options;
			$buffer = $edd_options[$setting_id];
		}

		return $buffer;
	}

	public static function get_platform_context() {
		$buffer = ATCF_CrowdFunding::get_platform_setting( "context" );
		if ( empty( $buffer ) ) {
			$buffer = "wedogood";
		}

		return $buffer;
	}

	public static function get_platform_name() {
		$buffer = "WE DO GOOD";
		$platform_context = ATCF_CrowdFunding::get_platform_context();
		switch ( $platform_context ) {
			case "royaltycrowdfunding":
				$buffer = "royaltycrowdfunding.fr";
				break;
		}

		return $buffer;
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
	public function template_loader($template) {
		global $wp_query;

		$find    = array();
		$files   = array();

		/** Check if viewing standard campaign */
		if ( is_singular( 'download' ) ) {
			do_action( 'atcf_found_single' );

			$files = apply_filters( 'atcf_crowdfunding_templates_campaign', array( 'single-campaign.php', 'single-download.php', 'single.php' ) );
		}

		/** Check if viewing archives */
		else {
			if ( is_post_type_archive( 'download' ) || is_tax( 'download_category' ) ) {
				do_action( 'atcf_found_archive' );

				$files = apply_filters( 'atcf_crowdfunding_templates_archive', array( 'archive-campaigns.php', 'archive-download.php', 'archive.php' ) );
			}
		}

		$files = apply_filters( 'atcf_template_loader', $files );

		foreach ( $files as $file ) {
			$find[] = $file;
			$find[] = $this->template_url . $file;
		}

		if ( !empty( $files ) ) {
			$template = locate_template( $find );

			if ( !$template ) {
				$template = $this->plugin_dir . 'templates/' . $file;
			}
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
function atcf_theme_supports($feature) {
	$supports = get_theme_support( 'appthemer-crowdfunding' );
	$supports = $supports[0];

	return isset( $supports[ $feature ] );
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

