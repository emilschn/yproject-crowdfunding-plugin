<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsAPI {
	
    //*******************************************************
    // NOTIFICATIONS DECLARATIONS ROI A FAIRE
    //*******************************************************
	public static function declaration_to_do( $recipients, $nb_remaining_days ) {
		$param_template_by_remaining_days = array(
			'10' => '99',
			'5' => '99',
			'1' => '99'
		);
		$param_template = isset( $param_template_by_remaining_days[ $nb_remaining_days ] ) ? $param_template_by_remaining_days[ $nb_remaining_days ] : FALSE;
		
		if ( !empty( $param_template ) ) {
			$param_recipients = is_array( $recipients ) ? implode( ',', $recipients ) : $recipients;
			$parameters = array(
				'tool'		=> 'sendinblue',
				'template'	=> $param_template,
				'recipient'	=> $param_recipients
			);
			//return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
		}
		
		return FALSE;
	}
    //*******************************************************
    // FIN NOTIFICATIONS DECLARATIONS ROI A FAIRE
    //*******************************************************
	
}
