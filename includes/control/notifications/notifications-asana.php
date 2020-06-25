<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) { exit; }

class NotificationsAsana {
	private static $notif_type_admin = 'admin';
	private static $notif_type_support = 'support';

	public static function send( $type, $task_name, $task_content ) {
		$from_name = '';
		$from_email = '';
		$asana_email = '';

		if ( !empty( $type ) ) {
			switch ( $type ) {
				case self::$notif_type_admin:
					$from_name = YP_ASANA_PARAMS_ADMIN_FROM_NAME;
					$from_email = YP_ASANA_PARAMS_ADMIN_FROM_EMAIL;
					$asana_email = YP_ASANA_PARAMS_ADMIN_ASANA_EMAIL;
					break;
				case self::$notif_type_support:
					$from_name = YP_ASANA_PARAMS_SUPPORT_FROM_NAME;
					$from_email = YP_ASANA_PARAMS_SUPPORT_FROM_EMAIL;
					$asana_email = YP_ASANA_PARAMS_SUPPORT_ASANA_EMAIL;
					break;
			}
		}

		if ( empty( $from_name ) ) {
			return FALSE;
		}
		
		$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		return wp_mail( $asana_email, $task_name, $task_content, $headers );
	}

	public static function read_project_page( $id_campaign ) {
		$campaign = new ATCF_Campaign( $id_campaign );
		$object = $campaign->get_name() . ' /// Présentation à relire !';
		$content = "Le porteur de projet a cliqué sur le bouton de relecture<br>";
		$content .= "URL du projet : " . $campaign->get_public_url();
		return self::send( self::$notif_type_support, $object, $content );
	}
}
