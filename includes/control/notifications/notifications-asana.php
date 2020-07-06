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
	
	public static function investment_pending_wire( $payment_id ) {

		$post_campaign = atcf_get_campaign_post_by_payment_id($payment_id);
		$campaign = atcf_get_campaign($post_campaign);
		
		$payment_data = edd_get_payment_meta( $payment_id );
		$payment_amount = edd_get_payment_amount( $payment_id );
		$email = $payment_data['email'];
		$user_data = get_user_by('email', $email);
		
		$object = "Un nouveau virement a été enregistré";
		
		$content = "Bonjour,<br /><br />";
		$content .= "Un nouveau virement de ".$payment_amount." &euro; a été enregistré pour le projet " .$campaign->data->post_title. ".<br /><br />";
		$content .= "Utilisateur :<br />";
		$content .= "- login : " .$user_data->user_login. "<br />";
		$content .= "- e-mail : " .$email. "<br />";
		$content .= "- prénom et nom : " .$user_data->first_name . " " . $user_data->last_name. "<br />";
		$content .= "- téléphone : " . get_user_meta($user_data->ID, 'user_mobile_phone', true). "<br />";
		
		return self::send( self::$notif_type_support, $object, $content );
	}
}
