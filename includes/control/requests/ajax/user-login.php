<?php
/**
 * Gestion des appels Ajax liés à l'utilisateur en cours
 */
class WDGAjaxActionsUserLogin {
	public static function get_current_user_id() {
        $buffer = '0';

        if (is_user_logged_in()) {
			$response = array();

			$WDGUserCurrent = WDGUser::current();
			$response[ 'userinfos' ] = array();
			$response[ 'userinfos' ][ 'userid' ] = $WDGUserCurrent->get_wpref();
			$response[ 'userinfos' ][ 'username' ] = ( !empty( $firstname_WDGUserCurrent ) ) ? $firstname_WDGUserCurrent : $WDGUserCurrent->get_login();
			$response[ 'userinfos' ][ 'my_account_txt' ] = __( 'common.MY_ACCOUNT', 'yproject' );
			// TODO : il faudrait que display_need_authentication prenne en compte l'état d'authentification des projets de l'utilisateur le cas échéant, mais je trouve que ça ralentirait cette fonction
			$response[ 'userinfos' ][ 'display_need_authentication' ] =  !$WDGUserCurrent->is_lemonway_registered()  ? '1' : '0';
			$buffer = json_encode( $response );

        }
		echo $buffer;
		exit();
    }
	
	public static function get_current_user_info() {
		$buffer = '0';

		if ( is_user_logged_in() ) {
			$response = array();

			$WDGUserCurrent = WDGUser::current();
			$firstname_WDGUserCurrent = $WDGUserCurrent->get_firstname();
			$response[ 'userinfos' ] = array();
			$response[ 'userinfos' ][ 'username' ] = ( !empty( $firstname_WDGUserCurrent ) ) ? $firstname_WDGUserCurrent : $WDGUserCurrent->get_login();
			$response[ 'userinfos' ][ 'my_account_txt' ] = __( 'common.MY_ACCOUNT', 'yproject' );
			$response[ 'userinfos' ][ 'image_dom_element' ] = UIHelpers::get_user_avatar( $WDGUserCurrent->get_wpref(), 'icon' );
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

			$response[ 'userinfos' ][ 'display_need_authentication' ] = ( !$is_project_needing_authentication && !$WDGUserCurrent->is_lemonway_registered() ) ? '1' : '0';

			$buffer = json_encode( $response );
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