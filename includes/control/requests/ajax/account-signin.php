<?php
/**
 * Gestion des appels Ajax liés à l'appli vuejs de connexion / inscription
 */
class WDGAjaxActionsAccountSignin
{
	/**
	 * Donne les informations à Account Signin en fonction de l'adresse e-mail
	 */
	public static function account_signin_get_email_info() {
		$input_email = filter_input( INPUT_POST, 'email-address' );
		$result = WDGFormUsers::get_user_type_by_email_address( $input_email );
		exit( json_encode( $result ) );
	}

	/**
	 * Vérifie si l'identification fonctionne entre une adresse e-mail et un mot de passe
	 */
	public static function account_signin_check_password() {
		$input_email = filter_input( INPUT_POST, 'email-address' );
		// On re-vérifie le type d'adresse en fonction de la saisie
		$result = WDGFormUsers::get_user_type_by_email_address( $input_email );

		// Si c'est bien un utilisateur existant (et pas lié à Facebook)
		if ( $result[ 'status' ] == 'existing-account' ) {
			$result[ 'signin_status' ] = 'success';
			$result[ 'signin_errors' ] = array();

			// On fait la comparaison pour voir si on arrive à conclure le signin
			$input_password = filter_input( INPUT_POST, 'password' );
			$signin_return = WDGFormUsers::get_signin_return( $input_email, $input_password );

			// Si il y a une erreur, on retourne les codes d'erreur
			if ( is_wp_error( $signin_return ) ) {
				$result[ 'signin_status' ] = 'error';
				$result[ 'signin_errors' ] = $signin_return->get_error_codes();

			// Sinon, c'est ok, on vérifie juste si on se souvient de la personne
			} else {
				$rememberme = filter_input( INPUT_POST, 'rememberme' );
				if ( $rememberme === 'true' ) {
					wp_set_auth_cookie( $signin_return->ID, true, is_ssl() );
				}
			}
		}
		exit( json_encode( $result ) );
	}
}