<?php
/**
 * Enregistre les informations de l'organisation en cours depuis Account Authentication
 */
$result = array(
	'status'				=> '',
	'id_api_organization'	=> ''
);

// L'utilisateur n'est pas connecté
AjaxCommonHelper::exit_if_not_logged_in();

// Récupération de l'ID de l'utilisateur en cours
$current_user_id = get_current_user_id();

// Normalement ça ne devrait pas arriver, mais controle de sécurité si l'utilisateur correspond à une organisation
AjaxCommonHelper::exit_if_current_user_is_organization( $current_user_id );

// Si on arrive ici, c'est un compte de personne physique dont on récupère l'ID API
$id_api_user = AjaxCommonHelper::get_user_api_id_by_wpref( $current_user_id );

// Récupération des informations transmises qui concernent l'organisation
$id_api_organization = AjaxCommonHelper::get_input_post( 'id_api' );
$name = AjaxCommonHelper::get_input_post( 'name' );
$activity = AjaxCommonHelper::get_input_post( 'activity' );
$idnumber = AjaxCommonHelper::get_input_post( 'idnumber' );
$email = AjaxCommonHelper::get_input_post( 'email' );
$website = AjaxCommonHelper::get_input_post( 'website' );
$legalform = AjaxCommonHelper::get_input_post( 'legalform' );
$apecode = AjaxCommonHelper::get_input_post( 'apecode' );
$capital = AjaxCommonHelper::get_input_post( 'capital' );
$legaltown = AjaxCommonHelper::get_input_post( 'legaltown' );
$representativefunction = AjaxCommonHelper::get_input_post( 'representativefunction' );
$address_number = AjaxCommonHelper::get_input_post( 'address_number' );
$address_number_comp = AjaxCommonHelper::get_input_post( 'address_number_comp' );
$address_street = AjaxCommonHelper::get_input_post( 'address_street' );
$address_postal_code = AjaxCommonHelper::get_input_post( 'address_postal_code' );
$address_city = AjaxCommonHelper::get_input_post( 'address_city' );
$address_country = AjaxCommonHelper::get_input_post( 'address_country' );
$organization_data = array(
	'name'						=> $name,
	'email'						=> $email,
	'representative_function'	=> $representativefunction,
	'description'				=> $activity,
	'website_url'				=> $website,
	'legalform'					=> $legalform,
	'idnumber'					=> $idnumber,
	'rcs'						=> $legaltown,
	'ape'						=> $apecode,
	'capital'					=> $capital,
	'address_number'			=> $address_number,
	'address_number_comp'		=> $address_number_comp,
	'address'					=> $address_street,
	'postalcode'				=> $address_postal_code,
	'city'						=> $address_city,
	'country'					=> $address_country
);

// Vérification si un identifiant API d'organisation a été transmis
// Si c'est le cas, vérification si l'utilisateur en cours a le droit de la modifier
if ( !empty( $id_api_organization ) ) {
	// Récupération des organisations de l'utilisateur et parcours pour voir si elle en fait partie
	$list_organizations = WDGWPREST_Entity_User::get_organizations_by_role( $id_api_user, WDGWPREST_Entity_Organization::$link_user_type_creator );
	$can_edit_organization = FALSE;
	foreach ( $list_organizations as $organization_item ) {
		if ( $id_api_organization == $organization_item->id ) {
			$can_edit_organization = TRUE;
		}
	}
	
	if ( !$can_edit_organization ) {
		$result = array(
			'status'				=> 'cant-edit-organization'
		);
		exit( json_encode( $result ) );

	} else {
		// Mise à jour de l'organisation avec les données
		$return_obj = WDGWPREST_Entity_Organization::update_from_array( $id_api_organization, $organization_data );
		$result = array(
			'status'				=> 'organization-saved',
			'id_api_organization'	=> $return_obj->id
		);
		exit( json_encode( $result ) );
	}

// Si ce n'est pas le cas, on crée une organisation à lier à l'utilisateur en cours
} else {
	// Vérification si l'adresse e-mail existe dans la base de données
	if ( email_exists( $email ) ) { // user
		$result[ 'status' ] = 'organization-email-exists';
		exit( json_encode( $result ) );
	}

	// Création de l'utilisateur sur WDG correspondant à l'organisation
	$username = 'org_' . sanitize_title_with_dashes( $name ); // formatting
	$password = wp_generate_password(); // pluggable
	$organization_user_id = wp_create_user( $username, $password, $email ); // user
	if ( is_wp_error( $organization_user_id ) || empty( $organization_user_id ) ) {
		$result[ 'status' ] = 'organization-linked-user-creation-error';
		exit( json_encode( $result ) );
	}
	update_user_meta( $organization_user_id, 'orga_contact_email', $email ); // user
	$organization_data[ 'wpref' ] = $organization_user_id;
	$organization_data[ 'type' ] = 'society';

	// Création sur l'API
	$return_obj = WDGWPREST_Entity_Organization::create_from_array( $organization_data );
	$id_api_organization = $return_obj->id;
	// Vérification si on reçoit bien un entier pour identifiant
	if ( filter_var( $id_api_organization, FILTER_VALIDATE_INT ) === FALSE ) {
		$result[ 'status' ] = 'organization-api-creation-error';
		exit( json_encode( $result ) );
	}

	// Mise à jour meta avec l'ID API
	$WDGOrganization_key_api_id = 'organisation_bopp_id';
	update_user_meta( $organization_user_id, $WDGOrganization_key_api_id, $id_api_organization );
	
	// Liaison API avec l'utilisateur en cours
	WDGWPREST_Entity_Organization::link_user( $id_api_organization, $id_api_user, WDGWPREST_Entity_Organization::$link_user_type_creator, TRUE );

	$result = array(
		'status'				=> 'organization-saved',
		'id_api_organization'	=> $id_api_organization
	);
	exit( json_encode( $result ) );
}

