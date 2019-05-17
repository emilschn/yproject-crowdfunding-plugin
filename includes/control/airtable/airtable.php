<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe d'appels à l'API WDGWPREST
 */
class WDGAirtable {
	private static $cache_by_route;
	private static $http_request_timeout = 30;
	
	private static $airtable_route_base = 'https://api.airtable.com/v0/';
	
	private static $language_default = 'fr';
	
/*******************************************************************************
 * Appels génériques en GET
 ******************************************************************************/
	private static function call_get( $route, $parameters = FALSE ) {
		if ( !isset( self::$cache_by_route ) ) {
			self::$cache_by_route = array();
		}
		
		if ( isset( self::$cache_by_route[ $route ] ) ) {
			$result = self::$cache_by_route[ $route ];
			
		} else {
			$route_to_get = self::$airtable_route_base. AIRTABLE_URL. '/Table%201/'. $route;
			if ( !empty( $parameters ) ) {
				$route_to_get .= '&' .$parameters;
			}
			
			$headers = array( "Authorization" => "Bearer " . AIRTABLE_API_KEY );
			$result = wp_remote_get(
				$route_to_get,
				array(
					'headers' => $headers,
					'timeout' => self::$http_request_timeout
				)
			);
		}
		ypcf_debug_log( 'WDGAirtable > call_get [' .$route_to_get. '] > ' .print_r( $result, TRUE ), FALSE );

		$buffer = FALSE;
		if ( !is_wp_error($result) && isset( $result["response"] ) && isset( $result["response"]["code"] ) && $result["response"]["code"] == "200" ) {
			$result = json_decode( $result["body"] );
			$buffer = $result;
			if ( isset( $result->error ) && isset( $result->error->type ) ) {
				if ( $result->error->type == 'no_result' ) {
					$buffer = array();
				}
			}
		}
		
		return $buffer;
	}
	
/*******************************************************************************
 * Appels génériques en POST
 ******************************************************************************/
	private static function call_post( $route, $method_type, $parameters, $parameters_get = FALSE ) {
		$route_to_post = self::$airtable_route_base. AIRTABLE_URL. '/Table%201';
		if ( !empty( $route ) ) {
			$route_to_post .= '/' . $route;
		}
		if ( !empty( $parameters_get ) ) {
			$route_to_post .= '&' .$parameters_get;
		}
		
		$headers = array(
			"Authorization" => "Bearer " . AIRTABLE_API_KEY,
			"Content-Type"	=> "application/json"
		);
		$body = json_encode( $parameters );

		$result = wp_remote_request( 
			$route_to_post,
			array(
				'method'	=> $method_type,
				'headers'	=> $headers,
				'timeout'	=> self::$http_request_timeout, 
				'body'		=> $body
			)
		);
		ypcf_debug_log( 'WDGAirtable > call_post [' .$route_to_post. '][ ' .print_r( $parameters, TRUE ). ' ] > ' .print_r( $result, TRUE ), FALSE );
		
		self::unset_cache( $route );
		
		$buffer = FALSE;
		if ( !is_wp_error($result) && isset( $result["response"] ) && isset( $result["response"]["code"] ) && $result["response"]["code"] == "200" ) {
			$buffer = json_decode( $result["body"] );
		}
		
		return $buffer;
	}
	
/*******************************************************************************
 * Gestion du cache
 ******************************************************************************/
	public static function unset_cache( $route ) {
		if ( isset( self::$cache_by_route[ $route ] ) ) {
			unset( self::$cache_by_route[ $route ] );
		}
	}
	
/*******************************************************************************
 * Lire des données
 ******************************************************************************/
	public static function get_complete_data() {
		return self::call_get( '' );
	}
	
	public static function get_line_by_airtable_id( $airtable_id ) {
		return self::call_get( $airtable_id );
	}
	
	public static function get_line_by_wdg_data( $wdg_key, $wdg_value ) {
		$buffer = FALSE;
		$data_list = self::get_complete_data();
		foreach ( $data_list->records as $data_item ) {
			if ( isset( $data_item->fields->{ $wdg_key } ) && $data_item->fields->{ $wdg_key } == $wdg_value ) {
				$buffer = $data_item;
				break;
			}
		}
		return $buffer;
	}
	
/*******************************************************************************
 * Ecrire une donnée
 ******************************************************************************/
	public static function create_line( $fields_list ) {
		$parameters = array(
			'fields'	=> $fields_list,
			'typecast'	=> true
		);
		return self::call_post( '', 'POST', $parameters );
	}
	
	public static function update_line_by_airtable_id( $airtable_id, $fields_list ) {
		$parameters = array(
			'fields'	=> $fields_list,
			'typecast'	=> true
		);
		return self::call_post( $airtable_id, 'PUT', $parameters );
	}
	
	public static function create_or_update_line( $wdg_key, $wdg_value, $parameters ) {
		$update_item = self::get_line_by_wdg_data( $wdg_key, $wdg_value );
		$update_id = $update_item->id;
		
		if ( !empty( $update_id ) ) {
			self::update_line_by_airtable_id( $update_id, $parameters );
		} else {
			self::create_line( $parameters );
		}
	}
	
}
