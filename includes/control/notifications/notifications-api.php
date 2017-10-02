<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsAPI {
	
    //*******************************************************
    // NOTIFICATIONS DECLARATIONS ROI A FAIRE
    //*******************************************************
	/**
	 * Envoie la notification de déclaration à faire aux porteurs de projet
	 * @param string or array $recipients
	 * @param int $nb_remaining_days
	 * @param boolean $has_mandate
	 * @return boolean
	 */
	public static function declaration_to_do( $recipients, $nb_remaining_days, $has_mandate, $options ) {
		$param_template_by_remaining_days = array(
			'9-mandate'	=> '114',
			'9-nomandate'	=> '115',
			'2-mandate'		=> '119',
			'2-nomandate'	=> '116',
			'0-mandate'		=> '121',
			'0-nomandate'	=> '120'
		);
		$index = $nb_remaining_days;
		if ( $has_mandate ) {
			$index .= '-mandate';
		} else {
			$index .= '-nomandate';
		}
		$param_template = isset( $param_template_by_remaining_days[ $index ] ) ? $param_template_by_remaining_days[ $index ] : FALSE;
		
		if ( !empty( $param_template ) ) {
			$param_recipients = is_array( $recipients ) ? implode( ',', $recipients ) : $recipients;
			$parameters = array(
				'tool'		=> 'sendinblue',
				'template'	=> $param_template,
				'recipient'	=> $param_recipients,
				'options'	=> json_encode( $options )
			);
			return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
		}
		
		return FALSE;
	}
	
	
	public static function declaration_to_do_sms( $recipients, $nb_remaining_days, $date_due_previous_day ) {
		if ( $nb_remaining_days == 10 ) {
		
			$param_content = "Bonjour, les déclarations sont ouvertes ! Déclarez votre chiffre d'affaires trimestriel avant le ".$date_due_previous_day->format( 'd/m' )." sur www.wedogood.co. A bientôt !";
			$param_recipients = is_array( $recipients ) ? implode( ',', $recipients ) : $recipients;
			$parameters = array(
				'tool'		=> 'sms',
				'template'	=> $param_content,
				'recipient'	=> $param_recipients
			);
			if ( WP_IS_DEV_SITE ) {
				return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
			}
			
		}
		return FALSE;
	}
    //*******************************************************
    // FIN NOTIFICATIONS DECLARATIONS ROI A FAIRE
    //*******************************************************
	
}
