<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe d'appels à l'API WDGWPREST
 */
class WDGWPRESTLib {
	private static function call_get( $route ) {
		ypcf_debug_log('WDGWPRESTLib::call_get -- $route : ' . $route);
		
		$headers = array( "Authorization" => "Basic " . base64_encode( YP_WDGWPREST_ID . ':' . YP_WDGWPREST_PWD ) );
		$result = wp_remote_get( YP_WDGWPREST_URL . $route, array( 'headers' => $headers ) );
		
		ypcf_debug_log('WDGWPRESTLib::call_get ----> $buffer : ' . print_r( $result, TRUE ));
		
		$buffer = FALSE;
		if ( !is_wp_error($result) && isset( $result["response"] ) && isset( $result["response"]["code"] ) && $result["response"]["code"] == "200" ) {
			$buffer = json_decode( $result["body"] );
		}
		
		return $buffer;
	}
	
	public static function get_post( $post_id ) {
		if ( !empty($post_id) ) {
			$buffer = false;
			$route = 'wp/v2/pages/';
			$result = WDGWPRESTLib::call_get( $route . $post_id );
			if ( isset( $result->content ) ){
				$buffer = $result->content->rendered;
			}
			return $buffer;
		}
		return FALSE;
	}
	
	public static function get_staticpages_list() {
		global $WDG_cache_plugin;
		$cache_wdgwpapi_version = 1;
		$cache_wdgwpapi_id = 'wdgwpapi_get_static_pages';
		$cache_wdgwpapi_duration = 60*15;
		$cache_wdgwpapi = $WDG_cache_plugin->get_cache( $cache_wdgwpapi_id, $cache_wdgwpapi_version );
		
		if ( $cache_wdgwpapi !== FALSE ) {
			$result = json_decode( $cache_wdgwpapi );
			
		} else {
			$route = 'wdg/v1/staticpages';
			$result = WDGWPRESTLib::call_get( $route );
			$WDG_cache_plugin->set_cache( $cache_wdgwpapi_id, json_encode( $result ), $cache_wdgwpapi_duration, $cache_wdgwpapi_version );

		}
		
		return $result;
	}
}
