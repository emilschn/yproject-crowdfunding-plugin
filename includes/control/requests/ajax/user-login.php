<?php
/**
 * Gestion des appels Ajax liés à l'utilisateur en cours
 */
class WDGAjaxActionsUserLogin {
	public static function get_current_user_info() {
		$buffer = '0';

		if ( is_user_logged_in() ) {
			$response = array();

			$WDGUserCurrent = WDGUser::current();
			$firstname_WDGUserCurrent = $WDGUserCurrent->get_firstname();
			$response[ 'userinfos' ] = array();
			$response[ 'userinfos' ][ 'userid' ] = $WDGUserCurrent->get_wpref();
			$response[ 'userinfos' ][ 'username' ] = ( !empty( $firstname_WDGUserCurrent ) ) ? $firstname_WDGUserCurrent : $WDGUserCurrent->get_login();
			$response[ 'userinfos' ][ 'my_account_txt' ] = __( 'common.MY_ACCOUNT', 'yproject' );
			// $response[ 'userinfos' ][ 'image_dom_element' ] = UIHelpers::get_user_avatar( $WDGUserCurrent->get_wpref(), 'icon' );
			$response[ 'userinfos' ][ 'logout_url' ] = wp_logout_url(). '&page_id=' .get_the_ID();

			$is_project_needing_authentication = FALSE;
			$response[ 'projectlist' ] = array();
			global $WDG_cache_plugin;
			if ( $WDG_cache_plugin == null ) {
				$WDG_cache_plugin = new WDG_Cache_Plugin();
			}
			$cache_project_list = $WDG_cache_plugin->get_cache( 'WDGUser::get_projects_by_id(' .$WDGUserCurrent->wp_user->ID. ', TRUE)', 1 );
			if ( $cache_project_list !== FALSE ) {
				$project_list = json_decode( $cache_project_list );
			} else {
				$project_list = WDGUser::get_projects_by_id( $WDGUserCurrent->wp_user->ID, TRUE );
				$WDG_cache_plugin->set_cache( 'WDGUser::get_projects_by_id(' .$WDGUserCurrent->wp_user->ID. ', TRUE)', json_encode( $project_list ), 60*10, 1 ); //MAJ 10min
			}
			if ( $project_list ) {
				$page_dashboard = WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' );
				foreach ( $project_list as $project_id ) {
					if ( !empty( $project_id ) ) {
						$project_campaign = new ATCF_Campaign( $project_id );
						if ( isset( $project_campaign ) && $project_campaign->get_name() != '' ) {
							$campaign_organization = $project_campaign->get_organization();
							$WDGOrganizationCampaign = new WDGOrganization( $campaign_organization->wpref );

							$campaign_item = array();
							$campaign_item[ 'name' ] = $project_campaign->get_name();
							$campaign_item[ 'url' ] = $page_dashboard. '?campaign_id=' .$project_id;
							$campaign_item[ 'display_need_authentication' ] = '0';
							if ( !$WDGOrganizationCampaign->is_registered_lemonway_wallet() ) {
								$is_project_needing_authentication = TRUE;
								$campaign_item[ 'display_need_authentication' ] = '1';
							}

							array_push( $response[ 'projectlist' ], $campaign_item );
						}
					}
				}
			}

			$response[ 'organizationlist' ] = array();
			$organizations_list = $WDGUserCurrent->get_organizations_list();
			if ($organizations_list) {
				foreach ($organizations_list as $organization_query_item) {
					$organization_item = array();
					$organization_item[ 'wpref' ] = $organization_query_item->wpref;
					$organization_item[ 'name' ] = $organization_query_item->name;
					array_push( $response[ 'organizationlist' ], $organization_item );
				}
			}

			$response[ 'userinfos' ][ 'display_need_authentication' ] = ( !$is_project_needing_authentication && !$WDGUserCurrent->is_lemonway_registered() ) ? '1' : '0';

			$response[ 'scripts' ] = array();
			$response[ 'context' ] = array();
			$input_pageinfo = filter_input( INPUT_POST, 'pageinfo' );
			if ( !empty( $input_pageinfo ) ) {
				$current_campaign = new ATCF_Campaign( $input_pageinfo );
				if ( $current_campaign->current_user_can_edit() ) {
					$project_editor_script_url = dirname( get_bloginfo('stylesheet_url') ). '/_inc/js/wdg-project-editor.js?d=' .time();
					array_push( $response[ 'scripts' ], $project_editor_script_url );
					$response[ 'context' ][ 'dashboard_url' ] = WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' . $current_campaign->ID;
				}
			}

			// Gestion de la source de l'utilisateur
			$init_source = $WDGUserCurrent->source;
			if ( empty( $init_source ) ) {
				$input_source = $WDGUserCurrent->get_source();
				if ( empty( $input_source ) ) {
					$input_source = filter_input( INPUT_POST, 'source' );
				}
				if ( empty( $input_source ) ) {
					ypcf_session_start();
					$input_source = $_SESSION[ 'user_source' ];
				}
				
				if ( !empty( $input_source ) ) {
					if ( $input_source == 'sendinblue' ) {
						$input_source = 'wedogood';
					}
					$WDGUserCurrent->source = $input_source;
					$WDGUserCurrent->update_api();
				}
			}

			$buffer = json_encode( $response );

		// Si l'utilisateur n'est pas connecté, mais qu'une source est transmise, on l'enregistre en variable de session
		} else {
			$input_source = filter_input( INPUT_POST, 'source' );
			if ( !empty( $input_source ) ) {
				ypcf_session_start();
				$_SESSION[ 'user_source' ] = $input_source;
			}
		}

		echo $buffer;
		exit();
	}

	public static function save_user_language() {
		if ( is_user_logged_in() ) {
			$input_language_key = filter_input( INPUT_POST, 'language_key' );
			$WDGuser_current = WDGUser::current();
			$WDGuser_current->set_language( $input_language_key );
			$WDGuser_current->update_api();
		}
		exit();
	}

	/**
	 * Retourne une URL de redirection vers la connexion Facebook
	 */
	public static function get_connect_to_facebook_url() {
		ypcf_session_start();
		$posted_redirect = filter_input( INPUT_POST, 'redirect' );
//		ypcf_debug_log( 'AJAX::get_connect_to_facebook_url > $posted_redirect : ' . $posted_redirect );
		$_SESSION[ 'login-fb-referer' ] = ( !empty( $posted_redirect ) ) ? $posted_redirect : wp_get_referer();
//		ypcf_debug_log( 'AJAX::get_connect_to_facebook_url > login-fb-referer : ' . $_SESSION[ 'login-fb-referer' ] );

		$crowdfunding = ATCF_CrowdFunding::instance();
		$crowdfunding->include_facebook();
		$fb = new Facebook\Facebook([
			'app_id' => YP_FB_APP_ID,
			'app_secret' => YP_FB_SECRET,
			'default_graph_version' => 'v2.8',
		]);
		$helper = $fb->getRedirectLoginHelper();
		$permissions = ['email'];
		$loginUrl = $helper->getLoginUrl( WDG_Redirect_Engine::override_get_page_url( 'connexion' ) . '?fbcallback=1', $permissions);
		echo $loginUrl;

		exit();
	}
}