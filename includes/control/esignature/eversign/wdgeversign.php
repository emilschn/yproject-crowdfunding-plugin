<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe d'appels à l'API WDGWPREST
 */
class WDGEversign {
	private static $cache_by_route;
	private static $http_request_timeout = 30;
	
	private static $eversign_route_base = 'https://api.eversign.com/api/';
	
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
			$access_key = WDG_EVERSIGN_ACCESS_KEY;
			$route_to_get = self::$eversign_route_base .$route. 
					'&access_key=' .$access_key.
					'&business_id=' .WDG_EVERSIGN_BUSINESS_ID;
			if ( !empty( $parameters ) ) {
				$route_to_get .= '&' .$parameters;
			}
			
			$result = wp_remote_get(
				$route_to_get,
				array(
					'timeout' => self::$http_request_timeout
				)
			);
		}
		ypcf_debug_log( 'WDGEversign > call_get [' .$route_to_get. '] > ' .print_r( $result, TRUE ), FALSE );

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
	private static function call_post( $route, $parameters, $parameters_get = FALSE ) {
		$access_key = WDG_EVERSIGN_ACCESS_KEY;
		$route_to_post = self::$eversign_route_base .$route. 
				'&access_key=' .$access_key.
				'&business_id=' .WDG_EVERSIGN_BUSINESS_ID;
		if ( !empty( $parameters_get ) ) {
			$route_to_post .= '&' .$parameters_get;
		}
		
		$body = json_encode( $parameters );

		$result = wp_remote_post(
			$route_to_post, 
			array(
				'timeout'	=> self::$http_request_timeout, 
				'body'		=> $body
			) 
		);
		ypcf_debug_log( 'WDGEversign > call_post [' .$route_to_post. '][ ' .print_r( $parameters, TRUE ). ' ] > ' .print_r( $result, TRUE ), FALSE );
		
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
 * Documents
 ******************************************************************************/
	public static function get_document_list() {
		return self::call_get( 'document', 'type=all' );
	}
	
	public static function get_document( $document_id ) {
		return self::call_get( 'document', 'document_hash=' .$document_id );
	}
	
	public static function create_document( $title, $message, $wdg_signature_id, $file_name, $file_url, $signer_id, $signer_name, $signer_email, $campaign_id ) {
		// General
		$sandbox = WDG_EVERSIGN_SANDBOX;
		$reminders = 1;
		$require_all_signers = 1;
		$custom_requester_name = 'WE DO GOOD';
		$custom_requester_email = 'bonjour@wedogood.co';
		$redirect_url_accept = home_url( '/paiement-partager/?campaign_id=' .$campaign_id. '&return_eversign=1' );
		$redirect_url_decline = home_url( '/paiement-partager/?campaign_id=' .$campaign_id. '&return_eversign=2' );
		$client_id = $wdg_signature_id;
		
		// Signers
		$language = self::$language_default;
		
		$parameters = array(
			'sandbox'				=> $sandbox,
			'title'					=> $title,
			'message'				=> $message,
			'reminders'				=> $reminders,
			'require_all_signers'	=> $require_all_signers,
			'custom_requester_name'	=> $custom_requester_name,
			'custom_requester_email'	=> $custom_requester_email,
			'redirect'				=> $redirect_url_accept,
			'redirect_decline'		=> $redirect_url_decline,
			'client'				=> $client_id,
			'files'					=> array(
				array(
					'name'			=> $file_name,
					'file_url'		=> $file_url
				)
			),
			'signers'				=> array(
				array(
					'id'			=> $signer_id,
					'name'			=> $signer_name,
					'email'			=> $signer_email,
					'language'		=> $language,
				)
			),
		);
		
		return self::call_post( 'document', $parameters );
	}
	
}
