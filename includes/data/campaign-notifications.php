<?php
/**
 * Lib de gestion des notifications de relectures
 */
class WDGCampaignNotifications {
	private static $meta_key = 'last_notification_date';
	private static $time_to_delay = 'PT6H';

	/**
	 * Récupération meta
	 */
	private static function get_meta( $id_wp_campaign ) {
		return get_post_meta( $id_wp_campaign, WDGCampaignNotifications::$meta_key, TRUE );
	}

	/**
	 * Mise à jour meta
	 */
	private static function update_meta( $id_wp_campaign ) {
		$date_time = new DateTime();
		update_post_meta( $id_wp_campaign, WDGCampaignNotifications::$meta_key, $date_time->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Suppression meta
	 */
	private static function delete_meta( $id_wp_campaign ) {
		delete_post_meta( $id_wp_campaign, WDGCampaignNotifications::$meta_key );
	}


	/**
	 * Retourne vrai si le PP peut demander une relecture
	 */
	public static function can_ask_proofreading( $id_wp_campaign ) {
		$meta = self::get_meta( $id_wp_campaign );

		// Si la meta n'existe pas, on peut
		if ( empty( $meta ) ) {
			return TRUE;
		}

		// Si la meta a été enregistré il y a plus de N heures, on peut
		$date_time_meta_plus_time = new DateTime( $meta );
		$date_time_meta_plus_time->add( new DateInterval( WDGCampaignNotifications::$time_to_delay ) );
		$date_time_current = new DateTime();
		if ( $date_time_current > $date_time_meta_plus_time ) {
			return TRUE;
		}

		// Sinon, on ne peut pas
		return FALSE;
	}

	/**
	 * Envoi la demande de relecture
	 */
	public static function ask_proofreading( $id_wp_campaign ) {
		if ( self::can_ask_proofreading( $id_wp_campaign ) ) {
			self::update_meta( $id_wp_campaign );

			$campaign = new ATCF_Campaign( $id_wp_campaign );
			$replyto_mail = 'support@wedogood.co';
			$WDGUserAuthor = new WDGUser( $campaign->data->post_author );
			NotificationsAPI::proofreading_request_received( $WDGUserAuthor->get_firstname(), $WDGUserAuthor->get_email(), $replyto_mail, $campaign->get_api_id() );

			NotificationsSlack::read_project_page( $id_wp_campaign );
			return NotificationsAsana::read_project_page( $id_wp_campaign );
		}

		return FALSE;
	}

	/**
	 * Confirme au PP que la relecture a été faite
	 */
	public static function send_has_finished_proofreading( $id_wp_campaign ) {
		self::delete_meta( $id_wp_campaign );
		return NotificationsEmails::send_project_description_notification_to_project( $id_wp_campaign );
	}
}