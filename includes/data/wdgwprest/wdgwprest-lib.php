<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe d'appels à l'API WDGWPREST
 */
class WDGWPRESTLib {
	public static $wp_route_standard = 'wp/v2/';
	public static $wp_route_wdg = 'wdg/v1/';
	public static $wp_route_external = 'external/v1/';
	
	private static $cache_by_route;
	
	private static $http_request_timeout = 10;
	
/*******************************************************************************
 * Appels génériques en GET
 ******************************************************************************/
	private static function call_get( $route, $shortcut_call = FALSE ) {
		if ( !isset( self::$cache_by_route ) ) {
			self::$cache_by_route = array();
		}
		
		if ( isset( self::$cache_by_route[ $route ] ) ) {
			$result = self::$cache_by_route[ $route ];
			
		} else {
			$db_cacher = WDG_Cache_Plugin::current();
			$cache_id = 'API::' .$route;
			$cache_version = 1;
			$result_cached = $db_cacher->get_cache( $cache_id, $cache_version );
			$result = unserialize($result_cached);
			
			if ( empty( $result ) ) {
				if ( !$shortcut_call ) {
					ypcf_debug_log( 'WDGWPRESTLib::call_get -- $route : ' . $route );
				}

				$login_pwd = YP_WDGWPREST_ID . ':' . YP_WDGWPREST_PWD;
				if ( !$shortcut_call ) {
					$WDGUser_current = WDGUser::current();
					if ( $WDGUser_current->has_access_to_api() ) {
						$login_pwd = $WDGUser_current->get_api_login() . ':' . $WDGUser_current->get_api_password();
					}
				}

				$headers = array( "Authorization" => "Basic " . base64_encode( $login_pwd ) );
				$result = wp_remote_get(
					YP_WDGWPREST_URL . $route,
					array( 
						'headers' => $headers,
						'timeout' => WDGWPRESTLib::$http_request_timeout
					)
				);

				if ( !$shortcut_call && !is_wp_error($result) && isset( $result['response'] ) ) {
					ypcf_debug_log( 'WDGWPRESTLib::call_get ----> $route : ' . $route.' --> $result[response] : ' . print_r( $result['response'], TRUE ) );
				}
				if ( !$shortcut_call && !is_wp_error($result) && isset( $result['body'] ) ) {
					$traced_body = json_decode( $result["body"] );
					if ( isset( $traced_body->bank_iban ) ) {
						$traced_body->bank_iban = 'UNTRACKED';
					}
					if ( isset( $traced_body->bank_bic ) ) {
						$traced_body->bank_bic = 'UNTRACKED';
					}
					ypcf_debug_log( 'WDGWPRESTLib::call_get ----> $route : ' . $route.' --> $body result : ' . json_encode( $traced_body ) );
				}
				
				$result_save = serialize( $result );
				if ( !empty( $result_save ) ) {
					$db_cacher->set_cache( $cache_id, $result_save, 60*4, $cache_version );
				}
			}
			
			self::$cache_by_route[ $route ] = $result;
		}
		
		$buffer = FALSE;
		if ( !is_wp_error($result) && isset( $result["response"] ) && isset( $result["response"]["code"] ) && $result["response"]["code"] == "200" ) {
			$buffer = json_decode( $result["body"] );
		}
		
		return $buffer;
	}

	public static function call_get_standard( $route ) {
		return WDGWPRESTLib::call_get( WDGWPRESTLib::$wp_route_standard . $route );
	}
	public static function call_get_wdg( $route, $shortcut_call = FALSE ) {
		return WDGWPRESTLib::call_get( WDGWPRESTLib::$wp_route_wdg . $route, $shortcut_call );
	}
	public static function call_get_external( $route ) {
		return WDGWPRESTLib::call_get( WDGWPRESTLib::$wp_route_external . $route );
	}
	
/*******************************************************************************
 * Appels génériques en POST
 ******************************************************************************/
	private static function call_post( $route, $parameters ) {
		$traced_parameters = $parameters;
		if ( isset( $traced_parameters[ 'bank_iban' ] ) ) {
			$traced_parameters[ 'bank_iban' ] = 'UNTRACKED';
		}
		if ( isset( $traced_parameters[ 'bank_bic' ] ) ) {
			$traced_parameters[ 'bank_bic' ] = 'UNTRACKED';
		}
		ypcf_debug_log( 'WDGWPRESTLib::call_post -- $route : ' . $route . ' --- ' . print_r( $traced_parameters, TRUE ) );
		
		$headers = array( "Authorization" => "Basic " . base64_encode( YP_WDGWPREST_ID . ':' . YP_WDGWPREST_PWD ) );
		$result = wp_remote_post( 
			YP_WDGWPREST_URL . $route, 
			array( 
				'headers'	=> $headers,
				'timeout'	=> WDGWPRESTLib::$http_request_timeout, 
				'body'		=> $parameters
			) 
		);
		
		if ( !is_wp_error( $result ) && isset( $result["response"] ) ) {
			ypcf_debug_log( 'WDGWPRESTLib::call_post ----> $result[response] : ' . print_r( $result["response"], TRUE ) );
		} else {
			ypcf_debug_log( 'WDGWPRESTLib::call_post ----> $result[response] : ' . print_r( $result, TRUE ) );
		}
		
		
		if ( isset( self::$cache_by_route[ $route ] ) ) {
			unset( self::$cache_by_route[ $route ] );
		}
		$db_cacher = WDG_Cache_Plugin::current();
		$db_cacher->delete_cache( array( 'API::' .$route ) );
		
		$buffer = FALSE;
		if ( !is_wp_error($result) && isset( $result["response"] ) && isset( $result["response"]["code"] ) && $result["response"]["code"] == "200" ) {
			$buffer = json_decode( $result["body"] );
		}
		
		return $buffer;
	}
	
	public static function call_post_standard( $route, $parameters ) {
		return WDGWPRESTLib::call_post( WDGWPRESTLib::$wp_route_standard . $route, $parameters );
	}
	
	public static function call_post_wdg( $route, $parameters ) {
		return WDGWPRESTLib::call_post( WDGWPRESTLib::$wp_route_wdg . $route, $parameters );
	}
	
	public static function call_post_external( $route, $parameters ) {
		return WDGWPRESTLib::call_post( WDGWPRESTLib::$wp_route_external . $route, $parameters );
	}
	
	public static function unset_cache( $route ) {
//		ypcf_debug_log('unset_cache > ' . print_r( self::$cache_by_route, true ) );
		if ( isset( self::$cache_by_route[ $route ] ) ) {
			unset( self::$cache_by_route[ $route ] );
		}
		$db_cacher = WDG_Cache_Plugin::current();
		$db_cacher->delete_cache( array( 'API::' .$route ) );
	}
	
/*******************************************************************************
 * Appels générique en DELETE
 ******************************************************************************/
	private static function call_delete( $route, $parameters ) {
		ypcf_debug_log( 'WDGWPRESTLib::call_delete -- $route : ' . $route . ' --- ' . print_r( $parameters, TRUE ) );
		
		$headers = array( "Authorization" => "Basic " . base64_encode( YP_WDGWPREST_ID . ':' . YP_WDGWPREST_PWD ) );
		$result = wp_remote_post( 
			YP_WDGWPREST_URL . $route, 
			array(
				'method'	=> 'DELETE',
				'headers'	=> $headers,
				'timeout'	=> WDGWPRESTLib::$http_request_timeout, 
				'body'		=> $parameters
			) 
		);
		
		ypcf_debug_log( 'WDGWPRESTLib::call_delete ----> $buffer : ' . print_r( $result, TRUE ) );
		
		$buffer = FALSE;
		if ( !is_wp_error($result) && isset( $result["response"] ) && isset( $result["response"]["code"] ) && $result["response"]["code"] == "200" ) {
			$buffer = json_decode( $result["body"] );
		}
		
		return $buffer;
	}
	
	public static function call_delete_wdg( $route, $parameters = array() ) {
		return WDGWPRESTLib::call_delete( WDGWPRESTLib::$wp_route_wdg . $route, $parameters );
	}
	
/*******************************************************************************
 * Récupération d'une page en particulier
 ******************************************************************************/
	public static function get_post( $post_id ) {
		if ( !empty($post_id) ) {
			$buffer = false;
			$route = 'pages/';
			$result = WDGWPRESTLib::call_get_standard( $route . $post_id );
			if ( isset( $result->content ) ){
				$buffer = $result->content->rendered;
			}
			return $buffer;
		}
		return FALSE;
	}
	
/*******************************************************************************
 * Récupération de la liste des pages notées comme statiques
 ******************************************************************************/
	public static function get_staticpages_list() {
		global $WDG_cache_plugin;
		if ($WDG_cache_plugin == null) {
			$WDG_cache_plugin = new WDG_Cache_Plugin();
		}
		$cache_wdgwpapi_version = 1;
		$cache_wdgwpapi_id = 'wdgwpapi_get_static_pages';
		$cache_wdgwpapi_duration = 60*15;
		$cache_wdgwpapi = $WDG_cache_plugin->get_cache( $cache_wdgwpapi_id, $cache_wdgwpapi_version );
		
		if ( $cache_wdgwpapi !== FALSE ) {
			$result = json_decode( $cache_wdgwpapi );
			
		} else {
			$route = 'staticpages';
			$result = WDGWPRESTLib::call_get_wdg( $route );
			$WDG_cache_plugin->set_cache( $cache_wdgwpapi_id, json_encode( $result ), $cache_wdgwpapi_duration, $cache_wdgwpapi_version );

		}
		
		return $result;
	}
}
