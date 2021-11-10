<?php
require_once dirname(__FILE__) . '/account-authentication/account-authentication-autoload.php';
// Chargement des helpers communs
require_once dirname(__FILE__) . '/common/ajax-common-helper.php';

/**
 * Gestion des appels Ajax liés à l'appli vuejs d'authentification'
 */
class WDGAjaxActionsAccountAuthentication {
	/**
	 * Enregistre le numéro de téléphone de l'utilisateur en cours depuis Account Authentication
	 */
	public static function account_authentication_save_current_user_phone() {
		// L'utilisateur n'est pas connecté
		AjaxCommonHelper::exit_if_not_logged_in();

		// Récupération de l'ID de l'utilisateur en cours
		$current_user_id = get_current_user_id();
		$wdgcurrent_user = WDGUser::current();
		
		// Normalement ça ne devrait pas arriver, mais controle de sécurité si l'utilisateur correspond à une organisation
		AjaxCommonHelper::exit_if_current_user_is_organization( $current_user_id );

		// Si on arrive ici, c'est un compte de personne physique dont on récupère l'ID API
		$user_api_id = AjaxCommonHelper::get_user_api_id_by_wpref( $current_user_id );
		
		// update
        if (!empty($user_api_id)) {
            $user_to_update = array();
            $user_to_update[ 'phone_number' ] = AjaxCommonHelper::get_input_post('phone_number');
            WDGWPREST_Entity_User::update_from_array($user_api_id, $user_to_update);
            $result[ 'status' ] = 'saved';			
			$subscribe_authentication_notification = AjaxCommonHelper::get_input_post( 'send_sms' );

			if ( $subscribe_authentication_notification === TRUE || $subscribe_authentication_notification === 'true' ) {
				$wdgcurrent_user->set_subscribe_authentication_notification( TRUE );
			} else {
				$wdgcurrent_user->set_subscribe_authentication_notification( FALSE );
			}
		}
		exit( json_encode( $result ) );
    }
}