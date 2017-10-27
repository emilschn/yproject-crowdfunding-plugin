<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Se charge de tester les redirections à effectuer
 */
function ypcf_check_redirections() {
    global $post;
    if (isset($post)) {
		$page_name = get_post($post)->post_name;

		switch ($page_name) {
			case 'connexion' :
				//Modification très crade temporaire pour gérer une partie de l'API
				new WDGAPICalls();
				//Redirection vers la page d'investissement après login, si on venait de l'investissement
				WDGRoutes::redirect_to_invest_if_logged_in();
			break;

			case 'modifier-mon-compte' :
				//On teste si l'utilisateur vient de remplir ses données pour les enregistrer
				ypcf_check_has_user_filled_infos_and_redirect();
			break;

			case 'paiement-effectue':
				WDGRoutes::redirect_invest_if_not_logged_in();
				if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);
				if (isset($_SESSION['redirect_current_amount_part'])) unset($_SESSION['redirect_current_amount_part']);
			break;
		}
    }
}
add_action( 'template_redirect', 'ypcf_check_redirections' );

class WDGRoutes {
	/**
	 * Après le login, si on venait de l'investissement, il faut y retourner
	 */
	public static function redirect_to_invest_if_logged_in() {
		ypcf_session_start();

		if ( is_user_logged_in() && isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "" ) {
			wp_redirect( ypcf_login_gobackinvest_url() );
			exit();
		}
	}
	
	/**
	 * Redirige vers la page de connexion si utilisateur pas connecté
	 */
	public static function redirect_invest_if_not_logged_in() {
		ypcf_session_start();

		if (!is_user_logged_in()) {
			$wdginvestment = WDGInvestment::current();
			if ( isset( $wdginvestment->get_campaign()->ID ) ) {
				$_SESSION['redirect_current_campaign_id'] = $wdginvestment->get_campaign()->ID;
				$page_connexion = get_page_by_path('connexion');
				wp_redirect(get_permalink($page_connexion->ID));
			} else {
				wp_redirect(site_url());
			}
			exit();
		}
	}
	
	/**
	 * Redirige si il n'est plus possible d'investir sur le projet
	 */
	public static function redirect_invest_if_project_not_investable() {
		$wdginvestment = WDGInvestment::current();
		if ( !$wdginvestment->get_campaign()->is_investable() ) {
			wp_redirect( get_permalink( $wdginvestment->get_campaign()->ID ) );
			exit();
		}
	}
	
	/**
	 * Redirige si il n'est plus possible d'investir sur le projet
	 */
	public static function redirect_invest_if_investment_not_initialized() {
		$init_result = WDGInvestment::init();
		if ( !$init_result ) {
			$wdginvestment = WDGInvestment::current();
			wp_redirect( $wdginvestment->get_redirection( 'error', 'token-error' ) );
			exit();
		}
	}
	
}