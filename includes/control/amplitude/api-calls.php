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
					'user_id'		=> $amplitude_user_id,
					'device_id'		=> $amplitude_device_id
				)
			)
		);
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

		return $current_user->ID;
	}
}