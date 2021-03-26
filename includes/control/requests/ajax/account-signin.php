<?php
/**
 * Gestion des appels Ajax liés à l'appli vuejs de connexion / inscription
 */
class WDGAjaxActionsAccountSignin {
	public static function account_signin_get_email_info() {
		$input_email = filter_input( INPUT_POST, 'email-address' );
		$result = array(
			'status' => '',
			'firstname' => '',
			'lastname' => '',
			'organizationname' => ''
		);

		// La chaine transmise est vide
		if ( empty( $input_email ) ) {
			$result[ 'status' ] = 'empty-email';
			exit( json_encode( $result ) );
		}

		// La chaine transmise n'est pas une adresse mail
		if ( !WDGRESTAPI_Lib_Validator::is_email( $input_email ) ) {
			$result[ 'status' ] = 'bad-email';
			exit( json_encode( $result ) );
		}

		// L'adresse mail transmise n'existe pas sur la plateforme
		$user_by_email = get_user_by( 'email', $input_email );
		if ( empty( $user_by_email ) ) {
			$result[ 'status' ] = 'not-existing-account';
			exit( json_encode( $result ) );
		}

		// L'adresse mail transmise correspond à une organisation
		if ( WDGOrganization::is_user_organization( $user_by_email->ID ) ) {
			$WDGOrganization = new WDGOrganization( $user_by_email->ID );
			$result[ 'status' ] = 'orga-account';
			$result[ 'organizationname' ] = $WDGOrganization->get_name();
			$result[ 'team_members' ] = array();
			$list_linked_users = $WDGOrganization->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
			if ( !empty( $list_linked_users ) ) {
				foreach ( $list_linked_users as $WDGUser_linked ) {
					$user_item = array(
						'email'		=> $WDGUser_linked->get_email(),
						'firstname'	=> $WDGUser_linked->get_firstname(),
						'lastname'	=> $WDGUser_linked->get_lastname(),
						'status'	=> $WDGUser_linked->is_logged_in_with_facebook() ? 'facebook-account' : 'existing-account'
					);
					array_push( $result[ 'team_members' ], $user_item );
				}
			}
			// Récupérer prénom, nom, email et méthode de connexion
			exit( json_encode( $result ) );
		}

		// Si on arrive ici, c'est un compte de personne physique
		$WDGUser = new WDGUser( $user_by_email->ID );
		$result[ 'firstname' ] = $WDGUser->get_firstname();
		$result[ 'lastname' ] = $WDGUser->get_lastname();

		// L'adresse mail transmise correspond à un compte connecté avec Facebook
		if ( $WDGUser->is_logged_in_with_facebook() ) {
			$result[ 'status' ] = 'facebook-account';
		} else {
			$result[ 'status' ] = 'existing-account';
		}

		exit( json_encode( $result ) );
	}
}