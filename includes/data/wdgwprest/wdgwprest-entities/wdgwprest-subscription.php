<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des abonnements côté WDGWPREST
 */
class WDGWPREST_Entity_Subscription {
	
	/**
	 * Récupère un abonnement par son identifiant
	 * @param int $subscription_id
	 * @return object
	 */
	public static function get( $subscription_id ) {
		return WDGWPRESTLib::call_get_wdg( 'subscription/' .$subscription_id );
	}
	

/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGSUBSCRIPTION $subscription
     * @return array
	 */
	public static function set_post_parameters( WDGSUBSCRIPTION $subscription ) {
		$parameters = array(
			'id_subscriber'			    => $subscription->id_subscriber,
			'id_activator'	            => $subscription->id_activator,
            'type_subscriber'			=> $subscription->type_subscriber,
			'id_project'			    => $subscription->id_campaign,
			'amount_type'				=> $subscription->amount_type,
			'amount'		            => $subscription->amount,
			'payment_method'		    => $subscription->payment_method,
			'modality'			        => $subscription->modality,
			'start_date'				=> $subscription->start_date,
			'status'	                => $subscription->status,
			'end_date'			        => $subscription->end_date,
		);
		return $parameters;
	}

	/**
	 * Crée un abonnement sur l'API
	 * @param WDGSUBSCRIPTION $subscription
	 * @return object
	 */
	public static function create( WDGSUBSCRIPTION $subscription) {
        $parameters = WDGWPREST_Entity_Subscription::set_post_parameters( $subscription );
		$result_obj = WDGWPRESTLib::call_post_wdg( 'subscription', $parameters );
        if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }

		return $result_obj;
	}
	
	/**
	 * Edite un abonnement
	 * @param WDGSUBSCRIPTION $subscription
	 * @return object
	 */
	public static function update(WDGSUBSCRIPTION $subscription) {
        $parameters = WDGWPREST_Entity_Subscription::set_post_parameters( $subscription );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'subscription/' . $subscription->id, $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;


	}
	
}