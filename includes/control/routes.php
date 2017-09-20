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
				ypcf_check_is_user_logged_connexion();
			break;

			case 'modifier-mon-compte' :
				//On teste si l'utilisateur vient de remplir ses données pour les enregistrer
				ypcf_check_has_user_filled_infos_and_redirect();
			break;

			case 'investir' :
				$init_result = WDGInvestment::init();
				if ( !$init_result ) {
					$wdginvestment = WDGInvestment::current();
					ypcf_debug_log( 'ypcf_check_redirections > investir > TOKEN ERRORS > ' . print_r( $wdginvestment->get_error(), TRUE ) );
					wp_redirect( $wdginvestment->get_redirection( 'error', 'token-error' ) );
					exit();
				}
				//D'abord on teste si l'utilisateur est bien connecté
				ypcf_check_is_user_logged_invest();
				ypcf_check_is_project_investable();
				$current_step = ypcf_get_current_step();
				if ($current_step == 2) {
					//On vérifie que les données utilisateurs sont valables
					ypcf_check_user_can_invest(true);
					//On vérifie les redirections nécessaires à l'investissement
					ypcf_check_invest_redirections();
				}
			break;

			case 'moyen-de-paiement' :
				ypcf_check_is_user_logged_invest();
				ypcf_check_meanofpayment_redirections();
			break;

			case 'paiement-virement' :
			case 'paiement-cheque' :
			case 'paiement-partager' :
				ypcf_check_is_user_logged_invest();
			break;

			case 'paiement-effectue' :
				ypcf_check_is_user_logged_invest();
				if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);
				if (isset($_SESSION['redirect_current_amount_part'])) unset($_SESSION['redirect_current_amount_part']);
			break;
		}
    }
}
add_action( 'template_redirect', 'ypcf_check_redirections' );