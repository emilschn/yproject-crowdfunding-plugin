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
				wp_set_auth_cookie( $signin_return->ID, ( $rememberme === 'true' ), is_ssl() );
			}
		}
		exit( json_encode( $result ) );
	}

	/**
	 * Essaie de créer un compte sur la plateforme à partir d'un e-mail, mot de passe, prénom et nom de famille
	 */
	public static function account_signin_create_account() {
		$input_email = filter_input( INPUT_POST, 'email-address' );
		$input_password = filter_input( INPUT_POST, 'password' );
		$input_firstname = filter_input( INPUT_POST, 'first-name' );
		$input_lastname = filter_input( INPUT_POST, 'last-name' );

		$result[ 'status' ] = '';
		// Normalement, on ne passe pas ici
		if ( empty( $input_email ) || empty( $input_password ) || empty( $input_firstname ) || empty( $input_lastname ) ) {
			$result[ 'status' ] = 'empty';
		} else {
			// On le fait par sécurité :
			// On re-vérifie le type d'adresse en fonction de la saisie
			$result = WDGFormUsers::get_user_type_by_email_address( $input_email );
		}
		ypcf_debug_log( 'account_signin_create_account >> ' . print_r($result, true), false );

		// Cas normal
		// Si le compte n'existe pas
		if ( $result[ 'status' ] == 'not-existing-account' ) {
			$result[ 'signin_status' ] = 'success';
			$result[ 'signin_errors' ] = array();

			$user_email = rtrim( $input_email );
			$user_name = $user_email;
			$user_firstname = mb_convert_case( $input_firstname, MB_CASE_TITLE );
			$user_lastname = mb_convert_case( $input_lastname, MB_CASE_TITLE );
			$display_name = $user_firstname. ' ' .substr( $user_lastname, 0, 1 ). '.';

			$wp_user_id = wp_insert_user( array(
				'user_login'	=> $user_name,
				'user_pass'		=> $input_password,
				'user_email'	=> $user_email,
				'first_name'	=> $user_firstname,
				'last_name'		=> $user_lastname,
				'display_name'	=> $display_name,
				'user_nicename' => sanitize_title( $display_name )
			) );
			ypcf_debug_log( 'account_signin_create_account >> ' . print_r($wp_user_id, true), false );

			// Si il y a une erreur d'insertion
			if ( is_wp_error( $wp_user_id ) ) {
				$result[ 'signin_status' ] = 'error';
				$result[ 'signin_errors' ] = $wp_user_id->get_error_codes();

			// Sinon, on connecte l'utilisateur
			} else {
				global $wpdb, $edd_options;
				$wpdb->update( $wpdb->users, array( sanitize_key( 'user_status' ) => 0 ), array( 'ID' => $wp_user_id ) );
				update_user_meta($wp_user_id, WDGUser::$key_validated_general_terms_version, $edd_options[WDGUser::$edd_general_terms_version]);
				// NotificationsAPI::user_registration( $user_email, $user_firstname );
				WDGQueue::add_notification_registered_without_investment( $wp_user_id );
				wp_set_auth_cookie( $wp_user_id, false, is_ssl() );
			}
		}

		// Normalement, on ne passe pas ici
		// Si le compte existe déjà
		// Mais si il y a eu une coupure pendant une précédente requête de création
		// Peut-être qu'on peut identifier l'utilisateur avec son mot de passe ?
		if ( $result[ 'status' ] == 'existing-account' ) {
			$result[ 'signin_status' ] = 'success';
			$result[ 'signin_errors' ] = array();

			// On fait la comparaison pour voir si on arrive à conclure le signin
			$signin_return = WDGFormUsers::get_signin_return( $input_email, $input_password );

			// Si il y a une erreur, on retourne les codes d'erreur
			if ( is_wp_error( $signin_return ) ) {
				$result[ 'signin_status' ] = 'error';
				$result[ 'signin_errors' ] = $signin_return->get_error_codes();
			} else {
				wp_set_auth_cookie( $wp_user_id, false, is_ssl() );
			}
		}

		exit( json_encode( $result ) );
	}
}