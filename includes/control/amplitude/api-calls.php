<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe de connexion à Amplitude
 */
class WDGAmplitude {
	private static $url_amplitude_api = 'https://api2.amplitude.com/2/httpapi';

	/**
	 * Ajout une trace de log dans Amplitude
	 */
	public static function logEvent($event, $amplitude_device_id) {
		$amplitude_user_id = self::getAmplitudeUserId();

		$parameters = array(
			'api_key'	=> AMPLITUDE_API_KEY,
			'events'	=> array(
				array(
					'event_type'	=> $event,
					'device_id'		=> $amplitude_device_id
				)
			)
		);
		if ( !empty( $amplitude_user_id ) ) {
			$amplitude_user_id = str_pad( $amplitude_user_id, 5, 0, STR_PAD_LEFT );
			$parameters[ 'events' ][ 0 ][ 'user_id' ] = $amplitude_user_id;
		}
		
		$body = json_encode( $parameters );
		$data_to_post = array(
			'body'		=> $body
		);
		wp_remote_post( self::$url_amplitude_api, $data_to_post );
	}

	/**
	 * Récupère l'identifiant d'utilisateur à transmetter à Amplitude
	 * Si l'utilisateur est identifié, c'est l'ID WP de l'utilisateur
	 */
	private static function getAmplitudeUserId() {
		global $current_user;
		if ( empty( $current_user ) ) {
			$current_user = wp_get_current_user();
		}
		if ( !empty( $current_user ) ) {
			return $current_user->ID;
		}
		return FALSE;
	}
}