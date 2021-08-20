<?php
class AjaxCommonHelper {
	/**
	 * Récupère une donnée de formulaire de type POST en faisant les transformations adéquates
	 */
	public static function get_input_post( $label ) {
		$input_result = filter_input( INPUT_POST, $label );
		return stripslashes( htmlentities( $input_result, ENT_QUOTES | ENT_HTML401 ) );
	}

	/**
	 * Récupère l'ID API d'un utilisateur via son ID WP
	 */
	public static function get_user_api_id_by_wpref( $wpref ) {
		global $wpdb;
		$db_meta_user_api_id = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = 'id_api' LIMIT 1",
				$wpref
			)
		);
		$user_api_id = empty( $db_meta_user_api_id ) ? FALSE : $db_meta_user_api_id->meta_value;
		return $user_api_id;
	}

	/**
	 * Arrête l'exécution et retourne une erreur si l'utilisateur n'est pas connecté
	 */
	public static function exit_if_not_logged_in() {
		if ( !is_user_logged_in() ) {
			$result = array();
			$result[ 'status' ] = 'not-logged-in';
			$result[ 'redirectUrl' ] = home_url( '/connexion/' );
			exit( json_encode( $result ) );
		}
	}

	/**
	 * Arrête l'exécution et retourne une erreur si l'utilisateur connecté est une organisation
	 */
	public static function exit_if_current_user_is_organization( $current_user_id ) {
		global $wpdb;
		$db_meta_user_organization_id = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = 'organisation_bopp_id' LIMIT 1",
				$current_user_id
			)
		);
		$user_organization_api_id = empty( $db_meta_user_organization_id ) ? FALSE : $db_meta_user_organization_id->meta_value;
		$is_user_organization = !empty( $user_organization_api_id );
		if ( $is_user_organization ) {
			$result = array();
			$result[ 'status' ] = 'is-user-organization';
			exit( json_encode( $result ) );
		}
	}
}
