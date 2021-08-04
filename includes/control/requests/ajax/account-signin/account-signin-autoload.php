<?php
// Classes WP nécessaires pour les appels HTTP
require_once ABSPATH . WPINC . '/class-wp-http-proxy.php';
require_once ABSPATH . WPINC . '/Requests/Hooker.php';
require_once ABSPATH . WPINC . '/Requests/Hooks.php';
require_once ABSPATH . WPINC . '/class-wp-http-requests-hooks.php';
require_once ABSPATH . WPINC . '/link-template.php';
require_once ABSPATH . WPINC . '/general-template.php';
require_once ABSPATH . WPINC . '/http.php';
require_once ABSPATH . WPINC . '/class-http.php';
// Classes WP nécessaires pour les retours d'appels HTTP
require_once ABSPATH . WPINC . '/class-wp-http-response.php';
require_once ABSPATH . WPINC . '/class-wp-http-requests-response.php';
// Classes WDG nécessaires (divers)
require_once dirname(__FILE__) . '/../../../lib/validator.php';
// Classes WDG nécessaires aux appels à l'API
require_once dirname(__FILE__) . '/../../../cache/db-cacher.php';
require_once dirname(__FILE__) . '/../../../../data/wdgwprest/wdgwprest-lib.php';
require_once dirname(__FILE__) . '/../../../../data/wdgwprest/wdgwprest-entities/wdgwprest-organization.php';
require_once dirname(__FILE__) . '/../../../../data/wdgwprest/wdgwprest-entities/wdgwprest-user.php';

class AccountSigninHelper {
	/**
	 * Retourne les informations de compte utilisateur en fonction d'une adresse e-mail
	 */
	public static function get_user_type_by_email_address($input_email) {
		$result = array(
			'status'			=> '',
			'firstname'			=> '',
			'lastname'			=> '',
			'url_redirect'		=> '',
			'organizationname'	=> ''
		);
	
		// La chaine transmise est vide
		if ( empty( $input_email ) ) {
			$result[ 'status' ] = 'empty-email';
	
			return $result;
		}
	
		// La chaine transmise n'est pas une adresse mail
		if ( !WDGRESTAPI_Lib_Validator::is_email( $input_email ) ) {
			$result[ 'status' ] = 'bad-email';
	
			return $result;
		}
	
		// Récupération du compte utilisateur à partir de l'adresse e-mail dans la base de données
		global $wpdb;
		$user_by_email = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->users WHERE user_email = %s LIMIT 1",
				$input_email
			)
		);
		// L'adresse mail transmise n'existe pas sur la plateforme
		if ( empty( $user_by_email ) ) {
			$result[ 'status' ] = 'not-existing-account';
	
			return $result;
		}
	
		// L'adresse mail transmise correspond à une organisation
		$db_meta_user_organization_id = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = 'organisation_bopp_id' LIMIT 1",
				$user_by_email->ID
			)
		);
		$user_organization_api_id = empty( $db_meta_user_organization_id ) ? FALSE : $db_meta_user_organization_id->meta_value;
		$is_user_organization = !empty( $user_organization_api_id );
		if ( $is_user_organization ) {
			$result[ 'status' ] = 'orga-account';
			$api_data_organization = WDGWPREST_Entity_Organization::get( $user_organization_api_id, TRUE );
			$result[ 'organizationname' ] = $api_data_organization->name;
			$result[ 'team_members' ] = array();
			$api_data_organization_list_linked_users = WDGWPREST_Entity_Organization::get_linked_users( $user_organization_api_id, TRUE );
			if ( !empty( $api_data_organization_list_linked_users ) ) {
				// Récupérer prénom, nom, email et méthode de connexion
				foreach ( $api_data_organization_list_linked_users as $user_item_linked ) {
					if ( $user_item_linked->type == WDGWPREST_Entity_Organization::$link_user_type_creator ) {
						$user_api = WDGWPREST_Entity_User::get( $user_item_linked->id_user, TRUE );
						
						$is_logged_in_with_facebook = ( $user_api->authentification_mode == 'facebook' );
						if ( !$is_logged_in_with_facebook ) {
							$db_meta_user_facebook_id = $wpdb->get_row(
								$wpdb->prepare(
									"SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = 'social_connect_facebook_id' LIMIT 1",
									$user_by_email->ID
								)
							);
							$is_logged_in_with_facebook = !empty( $db_meta_user_facebook_id );
						}

						$user_item = array(
							'email'			=> $user_api->email,
							'firstname'		=> $user_api->name,
							'lastname'		=> $user_api->surname,
							'status'		=> $is_logged_in_with_facebook ? 'facebook-account' : 'existing-account'
						);
						array_push( $result[ 'team_members' ], $user_item );
					}
				}
			}

			// Récupérer prénom, nom, email et méthode de connexion
			return $result;
		}
	
		// Si on arrive ici, c'est un compte de personne physique
		// On récupère son ID API
		$db_meta_user_api_id = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = 'id_api' LIMIT 1",
				$user_by_email->ID
			)
		);
		$user_api_id = empty( $db_meta_user_api_id ) ? FALSE : $db_meta_user_api_id->meta_value;
		// Récupération des informations de l'utilisateur sur l'API
		$is_logged_in_with_facebook = FALSE;
		if ( !empty( $user_api_id ) ) {
			$api_data = WDGWPREST_Entity_User::get( $user_api_id, TRUE );
			if ( !empty( $api_data ) ) {
				$result[ 'firstname' ] = $api_data->name;
				$result[ 'lastname' ] = $api_data->surname;
				$is_email_validated = ( !empty( $api_data->email_is_validated ) && $api_data->email_is_validated === '1' );
				$result[ 'url_redirect' ] = $is_email_validated ? 'redirect' : 'email-validation';
				$is_logged_in_with_facebook = ( $api_data->authentification_mode == 'facebook' );
			}
		}

		// Vérification de la connexion via Facebook dans la base de données si on n'a pas eu l'info dans l'API
		if ( !$is_logged_in_with_facebook ) {
			$db_meta_user_facebook_id = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = 'social_connect_facebook_id' LIMIT 1",
					$user_by_email->ID
				)
			);
			$is_logged_in_with_facebook = !empty( $db_meta_user_facebook_id );
		}

		// L'adresse mail transmise correspond à un compte connecté avec Facebook
		if ( $is_logged_in_with_facebook ) {
			$result[ 'status' ] = 'facebook-account';
		} else {
			$result[ 'status' ] = 'existing-account';
		}
	
		return $result;
	}
}
