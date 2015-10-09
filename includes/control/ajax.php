<?php
/**
 * Classe de gestion des appels Ajax
 * TODO : centraliser ici
 */
class WDGAjaxActions {
	private static $class_name = 'WDGAjaxActions';
    
	/**
	 * Initialise la liste des actions ajax
	 */
	public static function init_actions() {
		WDGAjaxActions::add_action('display_roi_user_list');
	}
    
	/**
	 * Ajoute une action WordPress à exécuter en Ajax
	 * @param string $action_name
	 */
	public static function add_action($action_name) {
		add_action('wp_ajax_' . $action_name, array(WDGAjaxActions::$class_name, $action_name));
		add_action('wp_ajax_nopriv_' . $action_name, array(WDGAjaxActions::$class_name, $action_name));
	}
    
	/**
	 * Affiche la liste des utilisateurs d'un projet qui doivent récupérer de l'argent de leur investissement
	 */
	public static function display_roi_user_list() {
		if (current_user_can('manage_options')) {
		    //Récupération des éléments à traiter
		    $campaign_id = filter_input(INPUT_POST, 'campaign_id');
		    $payment_item_id = filter_input(INPUT_POST, 'payment_item');
		    $campaign = new ATCF_Campaign($campaign_id);
		    $total_roi = 0;
		    $total_fees = 0;
		    $investments_list = $campaign->roi_payments_data($payment_item_id);
		    foreach ($investments_list as $investment_item) {
			    $total_fees += $investment_item['roi_fees'];
			    $total_roi += $investment_item['roi_amount']; 
			    $user_data = get_userdata($investment_item['user']);
			    //Affichage utilisateur
			    echo '<tr><td>'.$user_data->first_name.' '.$user_data->last_name.'</td><td>'.$investment_item['amount'].'&euro;</td><td>'.$investment_item['roi_amount'].'&euro;</td><td>'.$investment_item['roi_fees'].'&euro;</td></tr>';
		    }

		    //Affichage total
		    echo '<tr><td><strong>Total</strong></td><td>'.$campaign->current_amount(FALSE).'&euro;</td><td>'.$total_roi.'&euro;</td><td>'.$total_fees.'&euro;</td></tr>';
		}
		exit();
	}
}