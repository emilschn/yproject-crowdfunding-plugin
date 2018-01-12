<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsAPI {

	//**************************************************************************
	// Campagne
	//**************************************************************************
    //*******************************************************
    // ENVOI ACTUALITE DE PROJET
    //*******************************************************
	public static function new_project_news( $recipients, $replyto_mail, $project_name, $project_link, $news_name, $news_content ) {
		ypcf_debug_log( 'NotificationsAPI::new_project_news > ' . $recipients );
		$id_template = '156';
		$project_link = str_replace( 'https://', '', $project_link );
		$options = array(
			'replyto'				=> $replyto_mail,
			'NOM_PROJET'			=> $project_name,
			'LIEN_PROJET'			=> $project_link,
			'OBJET_ACTU'			=> $news_name,
			'CONTENU_ACTU'			=> $news_content
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipients,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
    //*******************************************************
    // FIN ENVOI ACTUALITE DE PROJET
    //*******************************************************
	
    //*******************************************************
    // ENVOI ACTUALITE DE PROJET
    //*******************************************************
	public static function project_mail( $recipient, $replyto_mail, $user_name, $project_name, $project_link, $news_name, $news_content ) {
		ypcf_debug_log( 'NotificationsAPI::project_mail > ' . $recipient );
		$id_template = '184';
		$project_link = str_replace( 'https://', '', $project_link );
		$options = array(
			'replyto'				=> $replyto_mail,
			'NOM_UTILISATEUR'		=> $user_name,
			'NOM_PROJET'			=> $project_name,
			'LIEN_PROJET'			=> $project_link,
			'OBJET_ACTU'			=> $news_name,
			'CONTENU_ACTU'			=> $news_content
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
    //*******************************************************
    // FIN ENVOI ACTUALITE DE PROJET
    //*******************************************************


	//**************************************************************************
	// Investissement
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS INVESTISSEMENT - ERREUR - POUR UTILISATEUR
    //*******************************************************
	public static function investment_error( $recipient, $name, $amount, $project_name, $lemonway_reason, $investment_link ) {
		$id_template = '175';
		$options = array(
			'NOM'					=> $name,
			'MONTANT'				=> $amount,
			'NOM_PROJET'			=> $project_name,
			'RAISON_LEMONWAY'		=> $lemonway_reason,
			'LIEN_INVESTISSEMENT'	=> $investment_link,
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
    //*******************************************************
    // FIN NOTIFICATIONS INVESTISSEMENT - ERREUR - POUR UTILISATEUR
    //*******************************************************


	//**************************************************************************
	// Déclarations
	//**************************************************************************
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
			'9-mandate'		=> '114',
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
    // FIN - NOTIFICATIONS DECLARATIONS ROI A FAIRE
    //*******************************************************
	
    //*******************************************************
    // NOTIFICATIONS DECLARATIONS APROUVEES
    //*******************************************************
	public static function declaration_done_with_turnover( $recipient, $name, $last_three_months, $turnover_amount ) {
		$id_template = '127';
		$options = array(
			'NOM'					=> $name,
			'TROIS_DERNIERS_MOIS'	=> $last_three_months,
			'MONTANT_ROYALTIES'		=> $turnover_amount
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	public static function declaration_done_without_turnover( $recipient, $name, $last_three_months ) {
		$id_template = '128';
		$options = array(
			'NOM'					=> $name,
			'TROIS_DERNIERS_MOIS'	=> $last_three_months
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
    //*******************************************************
    // FIN - NOTIFICATIONS DECLARATIONS APROUVEES
    //*******************************************************
	//**************************************************************************
	// FIN - Déclarations
	//**************************************************************************
	
	
	//**************************************************************************
	// Versements
	//**************************************************************************
    //*******************************************************
    // NOTIFICATIONS VERSEMENT AVEC ROYALTIES
    //*******************************************************
	public static function roi_transfer_with_royalties( $recipient, $name, $project_name, $adjustment_message, $declaration_message ) {
		$id_template = '140';
		$options = array(
			'NOM_UTILISATEUR'					=> $name,
			'NOM_PROJET'						=> $project_name,
			'MESSAGE_VERSEMENT_AJUSTEMENT'		=> $adjustment_message,
			'MESSAGE_VERSEMENT_ENTREPRENEUR'	=> $declaration_message,
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
			
    //*******************************************************
    // NOTIFICATIONS VERSEMENT SANS ROYALTIES
    //*******************************************************
	public static function roi_transfer_without_royalties( $recipient, $name, $project_name, $adjustment_message, $declaration_message ) {
		$id_template = '167';
		$options = array(
			'NOM_UTILISATEUR'					=> $name,
			'NOM_PROJET'						=> $project_name,
			'MESSAGE_VERSEMENT_AJUSTEMENT'		=> $adjustment_message,
			'MESSAGE_VERSEMENT_ENTREPRENEUR'	=> $declaration_message,
		);
		$parameters = array(
			'tool'		=> 'sendinblue',
			'template'	=> $id_template,
			'recipient'	=> $recipient,
			'options'	=> json_encode( $options )
		);
		return WDGWPRESTLib::call_post_wdg( 'email', $parameters );
	}
	
	//**************************************************************************
	// FIN - Versements
	//**************************************************************************
	
}
