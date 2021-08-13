<?php
/**
 * Classe de gestion des appels Ajax
 * TODO : centraliser ici
 */
class WDGAjaxActions {
	private static $class_name = 'WDGAjaxActions';
	private static $class_name_user_login = 'WDGAjaxActionsUserLogin';
	private static $class_name_user_account = 'WDGAjaxActionsUserAccount';
	private static $class_name_project_page = 'WDGAjaxActionsProjectPage';
	private static $class_name_project_dashboard = 'WDGAjaxActionsProjectDashboard';
	private static $class_name_vuejs = 'WDGAjaxActionsVue';
	private static $class_name_prospect_setup = 'WDGAjaxActionsProspectSetup';
	private static $class_name_account_signin = 'WDGAjaxActionsAccountSignin';

	private static $class_to_filename = array(
		'WDG_Form_Vote'			=> 'vote',
		'WDG_Form_User_Details' => 'user-details',
		'WDG_Form_Dashboard_Add_Check' => 'dashboard-add-check'
	);

	/**
	 * Initialise la liste des actions ajax
	 */
	public static function init_actions() {
		WDGAjaxActions::add_action_by_class( 'WDG_Form_Vote' );
		WDGAjaxActions::add_action_by_class( 'WDG_Form_User_Details' );
		WDGAjaxActions::add_action_by_class( 'WDG_Form_Dashboard_Add_Check' );

		WDGAjaxActions::add_action( 'get_searchable_projects_list' );
		WDGAjaxActions::add_action( 'init_sendinblue_templates' );

		// Login
		WDGAjaxActions::add_action_user_login( 'get_current_user_info' );
		WDGAjaxActions::add_action_user_login( 'save_user_language' );
		WDGAjaxActions::add_action_user_login( 'get_connect_to_facebook_url' );

		// Mon compte
		WDGAjaxActions::add_action_user_account( 'display_user_investments' ); // deprecated
		WDGAjaxActions::add_action_user_account( 'display_user_investments_optimized' );
		WDGAjaxActions::add_action_user_account( 'get_transactions_table' );
		WDGAjaxActions::add_action_user_account( 'get_viban_info' );

		// Page projet
		WDGAjaxActions::add_action_project_page( 'save_image_url_video' );
		WDGAjaxActions::add_action_project_page( 'send_project_notification' );
		WDGAjaxActions::add_action_project_page( 'remove_project_cache' );
		WDGAjaxActions::add_action_project_page( 'remove_project_lang' );

		// TBPP
		WDGAjaxActions::add_action_project_dashboard( 'display_roi_user_list' );
		WDGAjaxActions::add_action_project_dashboard( 'show_project_money_flow' );
		WDGAjaxActions::add_action_project_dashboard( 'check_invest_input' );
		WDGAjaxActions::add_action_project_dashboard( 'remove_help_item' );
		WDGAjaxActions::add_action_project_dashboard( 'save_project_infos' );
		WDGAjaxActions::add_action_project_dashboard( 'save_project_funding' );
		WDGAjaxActions::add_action_project_dashboard( 'save_project_communication' );
		WDGAjaxActions::add_action_project_dashboard( 'save_project_contract_modification' );
		WDGAjaxActions::add_action_project_dashboard( 'save_project_campaigntab' );
		WDGAjaxActions::add_action_project_dashboard( 'save_project_status' );
		WDGAjaxActions::add_action_project_dashboard( 'save_project_force_mandate' );
		WDGAjaxActions::add_action_project_dashboard( 'save_project_declaration_info' );
		WDGAjaxActions::add_action_project_dashboard( 'save_user_infos_dashboard' );
		WDGAjaxActions::add_action_project_dashboard( 'pay_with_mandate' );
		WDGAjaxActions::add_action_project_dashboard( 'create_contacts_table' );
		WDGAjaxActions::add_action_project_dashboard( 'preview_mail_message' );
		WDGAjaxActions::add_action_project_dashboard( 'search_user_by_email' );
		WDGAjaxActions::add_action_project_dashboard( 'apply_draft_data' );
		WDGAjaxActions::add_action_project_dashboard( 'create_investment_from_draft' );
		WDGAjaxActions::add_action_project_dashboard( 'proceed_roi_transfers' );
		WDGAjaxActions::add_action_project_dashboard( 'cancel_pending_investments' );
		WDGAjaxActions::add_action_project_dashboard( 'campaign_duplicate' );
		WDGAjaxActions::add_action_project_dashboard( 'campaign_transfer_investments' );
		WDGAjaxActions::add_action_project_dashboard( 'conclude_project' );
		WDGAjaxActions::add_action_project_dashboard( 'try_lock_project_edition' );
		WDGAjaxActions::add_action_project_dashboard( 'keep_lock_project_edition' );
		WDGAjaxActions::add_action_project_dashboard( 'delete_lock_project_edition' );
		WDGAjaxActions::add_action_project_dashboard( 'send_test_notifications' );

		// Vuejs
		WDGAjaxActions::add_action_vuejs( 'vuejs_error_catcher' );
		WDGAjaxActions::add_action_vuejs( 'create_project_form' );

		// Prospect setup - interface prospect
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_save' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_save_files' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_get_by_guid' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_load_capacities' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_send_mail_user_project_drafts' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_send_mail_user_draft_started' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_send_mail_user_draft_finished' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_ask_card_payment' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_send_mail_payment_method_select_wire' );
		WDGAjaxActions::add_action_prospect_setup( 'prospect_setup_send_mail_payment_method_received_wire' );

		self::init_actions_account_signin();
	}

	public static $account_signin_actions = array(
		'account_signin_get_email_info',
		'account_signin_check_password',
		'account_signin_create_account',
		'account_signin_send_reinit_pass',
		'account_signin_send_validation_email',
		'account_signin_change_account_email'
	);
	public static function init_actions_account_signin() {
		// Account signin - Interface de connexion / inscription
		foreach ( self::$account_signin_actions as $single_action ) {
			WDGAjaxActions::add_action_account_signin( $single_action );
		}
	}

	/**
	 * GÃ¨re de maniÃ¨re automatisée les classes de formulaires (standardisées)
	 * @param string $class_name
	 */
	public static function add_action_by_class($class_name) {
		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_control( 'forms/' . self::$class_to_filename[ $class_name ] );
		$form_object = new $class_name();
		add_action( 'wp_ajax_' .$form_object->getFormID(), array( $form_object, 'postFormAjax' ) );
		add_action( 'wp_ajax_nopriv_' .$form_object->getFormID(), array( $form_object, 'postFormAjax' ) );
	}

	/**
	 * Ajoute une action WordPress Ã  exécuter en Ajax
	 * @param string $action_name
	 */
	public static function add_action($action_name) {
		add_action('wp_ajax_' . $action_name, array(WDGAjaxActions::$class_name, $action_name));
		add_action('wp_ajax_nopriv_' . $action_name, array(WDGAjaxActions::$class_name, $action_name));
	}

	/**
	 * Retourne la liste des projets qui peuvent Ãªtre recherchés
	 */
	public static function get_searchable_projects_list() {
		ypcf_function_log( 'get_searchable_projects_list', 'view' );
		$WDG_cache_plugin = new WDG_Cache_Plugin();

		$projects_searchable = array();
		$cache_projects_searchable = $WDG_cache_plugin->get_cache( 'ATCF_Campaign::list_projects_searchable_1', 3 );
		if ( $cache_projects_searchable !== FALSE ) {
			$projects_searchable = json_decode( $cache_projects_searchable );
			$index = 2;
			$cache_projects_searchable = $WDG_cache_plugin->get_cache( 'ATCF_Campaign::list_projects_searchable_' .$index, 3 );
			while ( $cache_projects_searchable != FALSE ) {
				$temp_projects_searchable = json_decode( $cache_projects_searchable );
				$projects_searchable = array_merge( $projects_searchable, $temp_projects_searchable );
				$index++;
				$cache_projects_searchable = $WDG_cache_plugin->get_cache( 'ATCF_Campaign::list_projects_searchable_' .$index, 3 );
			}
		} else {
			$projects_searchable = ATCF_Campaign::list_projects_searchable();
			$count_projects_searchable = count( $projects_searchable );
			$index = 1;
			$list_to_cache = array();
			for ( $i = 0; $i < $count_projects_searchable; $i++ ) {
				array_push( $list_to_cache, $projects_searchable[ $i ] );
				if ( $i % 10 == 0 ) {
					$projects_searchable_encoded = json_encode( $list_to_cache );
					$WDG_cache_plugin->set_cache( 'ATCF_Campaign::list_projects_searchable_' .$index, $projects_searchable_encoded, 60 * 60 * 3, 3 ); //MAJ 3h
					$index++;
					$list_to_cache = array();
				}
			}
			// Sauvegarde des restants
			$projects_searchable_encoded = json_encode( $list_to_cache );
			$WDG_cache_plugin->set_cache( 'ATCF_Campaign::list_projects_searchable_' .$index, $projects_searchable_encoded, 60 * 60 * 3, 3 ); //MAJ 3h
		}
		$buffer = array('home_url' => esc_url( home_url( '/' ) ) , 'projects' => $projects_searchable);
		$buffer_json = json_encode( $buffer );
		echo $buffer_json;
		exit();
	}

	public static function init_sendinblue_templates() {
		$template_index = filter_input( INPUT_POST, 'template_index' );
		$foreach_index = 0;
		foreach ( NotificationsAPI::$description_str_by_template_id as $template_slug => $template_data ) {
			if ( $template_index == $foreach_index ) {
				WDGWPREST_Entity_SendinblueTemplate::update_template( $template_slug );
				$foreach_index++;
				break;
			}
			$foreach_index++;
		}

		echo $foreach_index;
		exit();
	}

	/**********************************************/
	/**
	 * Référence les actions liées à l'utilisateur en cours
	 */
	private static function add_action_user_login($action_name) {
		add_action( 'wp_ajax_' . $action_name, self::$class_name . '::user_login_actions' );
		add_action( 'wp_ajax_nopriv_' . $action_name, self::$class_name . '::user_login_actions' );
	}

	/**
	 * Exécute les actions liées à l'utilisateur en cours
	 */
	public static function user_login_actions() {
		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_control( 'requests/ajax/user-login' );
		$action = filter_input( INPUT_POST, 'action' );
		call_user_func( self::$class_name_user_login . '::' . $action );
	}

	/**********************************************/
	/**
	 * Référence les actions liées à Mon compte
	 */
	private static function add_action_user_account($action_name) {
		add_action( 'wp_ajax_' . $action_name, self::$class_name . '::user_account_actions' );
		add_action( 'wp_ajax_nopriv_' . $action_name, self::$class_name . '::user_account_actions' );
	}

	/**
	 * Exécute les actions liées à Mon compte
	 */
	public static function user_account_actions() {
		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_control( 'requests/ajax/user-account' );
		$action = filter_input( INPUT_POST, 'action' );
		call_user_func( self::$class_name_user_account . '::' . $action );
	}

	/**********************************************/
	/**
	 * Référence les actions liées à la page projet
	 */
	private static function add_action_project_page($action_name) {
		add_action( 'wp_ajax_' . $action_name, self::$class_name . '::project_page_actions' );
		add_action( 'wp_ajax_nopriv_' . $action_name, self::$class_name . '::project_page_actions' );
	}

	/**
	 * Exécute les actions liées à la page projet
	 */
	public static function project_page_actions() {
		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_control( 'requests/ajax/project-page' );
		$action = filter_input( INPUT_POST, 'action' );
		call_user_func( self::$class_name_project_page . '::' . $action );
	}

	/**********************************************/
	/**
	 * Référence les actions liées au TBPP
	 */
	private static function add_action_project_dashboard($action_name) {
		add_action( 'wp_ajax_' . $action_name, self::$class_name . '::project_dashboard_actions' );
		add_action( 'wp_ajax_nopriv_' . $action_name, self::$class_name . '::project_dashboard_actions' );
	}

	/**
	 * Exécute les actions liées au TBPP
	 */
	public static function project_dashboard_actions() {
		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_control( 'requests/ajax/project-dashboard' );
		$action = filter_input( INPUT_POST, 'action' );
		call_user_func( self::$class_name_project_dashboard . '::' . $action );
	}

	/**********************************************/
	/**
	 * Référence les actions liées aux logiciels VueJS
	 */
	private static function add_action_vuejs($action_name) {
		add_action( 'wp_ajax_' . $action_name, self::$class_name . '::vuejs_actions' );
		add_action( 'wp_ajax_nopriv_' . $action_name, self::$class_name . '::vuejs_actions' );
	}

	/**
	 * Exécute les actions liées aux logiciels VueJS
	 */
	public static function vuejs_actions() {
		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_control( 'requests/ajax/vuejs' );
		$action = filter_input( INPUT_POST, 'action' );
		call_user_func( self::$class_name_vuejs . '::' . $action );
	}

	/**********************************************/
	/**
	 * Référence les actions liées à l'interface prospect
	 */
	private static function add_action_prospect_setup($action_name) {
		add_action( 'wp_ajax_' . $action_name, self::$class_name . '::prospect_setup_actions' );
		add_action( 'wp_ajax_nopriv_' . $action_name, self::$class_name . '::prospect_setup_actions' );
	}

	/**
	 * Exécute les actions liées à l'interface prospect
	 */
	public static function prospect_setup_actions() {
		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_control( 'requests/ajax/prospect-setup' );
		$action = filter_input( INPUT_POST, 'action' );
		call_user_func( self::$class_name_prospect_setup . '::' . $action );
	}

	/**********************************************/
	/**
	 * Référence les actions liées à l'interface de connexion / inscription
	 */
	private static function add_action_account_signin($action_name) {
		add_action( 'wp_ajax_' . $action_name, self::$class_name . '::account_signin_actions' );
		add_action( 'wp_ajax_nopriv_' . $action_name, self::$class_name . '::account_signin_actions' );
	}

	/**
	 * Exécute les actions liées à l'interface de connexion / inscription
	 */
	public static function account_signin_actions() {
		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_control( 'requests/ajax/account-signin' );
		$crowdfunding->include_control( 'amplitude/api-calls' );
		$action = filter_input( INPUT_POST, 'action' );
		$sessionUID = filter_input( INPUT_POST, 'sessionUID' );
		WDGAmplitude::logEvent( $action, $sessionUID );
		call_user_func( self::$class_name_account_signin . '::' . $action );
	}
}