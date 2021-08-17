<?php
/**
 * Enregistre les informations de l'utilisateur en cours depuis Account Authentication
 */
$result = array(
	'status'			=> ''
);

// L'utilisateur n'est pas connecté
if ( !is_user_logged_in() ) {
	$result[ 'status' ] = 'not-logged-in';

	exit( json_encode( $result ) );
}
$current_user_id = get_current_user_id();

// Normalement ça ne devrait pas arriver, mais controle de sécurité :
// L'identifiant utilisateur correspond à une organisation
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
	$result[ 'status' ] = 'is-user-organization';

	exit( json_encode( $result ) );
}

// Si on arrive ici, c'est un compte de personne physique
// On récupère son ID API
$db_meta_user_api_id = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = 'id_api' LIMIT 1",
		$current_user_id
	)
);
$user_api_id = empty( $db_meta_user_api_id ) ? FALSE : $db_meta_user_api_id->meta_value;

// update
// Récupération des informations de l'utilisateur sur l'API
if ( !empty( $user_api_id ) ) {
	$user_to_update = array();
	$user_to_update[ 'gender' ] = AjaxCommonHelper::get_input_post( 'gender' );
	$birthday_day = AjaxCommonHelper::get_input_post( 'birthday_day' );
	$birthday_month = AjaxCommonHelper::get_input_post( 'birthday_month' );
	$birthday_year = AjaxCommonHelper::get_input_post( 'birthday_year' );
	$user_to_update[ 'birthday_date' ] = $birthday_year . '-' . $birthday_month . '-' . $birthday_day;
	$user_to_update[ 'birthday_city' ] = AjaxCommonHelper::get_input_post( 'birthday_city' );
	$user_to_update[ 'birthday_district' ] = AjaxCommonHelper::get_input_post( 'birthday_district' );
	$user_to_update[ 'birthday_department' ] = AjaxCommonHelper::get_input_post( 'birthday_department' );
	$user_to_update[ 'birthday_country' ] = AjaxCommonHelper::get_input_post( 'birthday_country' );
	$user_to_update[ 'nationality' ] = AjaxCommonHelper::get_input_post( 'nationality' );
	$user_to_update[ 'address_number' ] = AjaxCommonHelper::get_input_post( 'address_number' );
	$user_to_update[ 'address_number_comp' ] = AjaxCommonHelper::get_input_post( 'address_number_comp' );
	$user_to_update[ 'address' ] = AjaxCommonHelper::get_input_post( 'address_street' );
	$user_to_update[ 'postalcode' ] = AjaxCommonHelper::get_input_post( 'address_postalcode' );
	$user_to_update[ 'city' ] = AjaxCommonHelper::get_input_post( 'address_city' );
	$user_to_update[ 'country' ] = AjaxCommonHelper::get_input_post( 'address_country' );
	$user_to_update[ 'tax_country' ] = AjaxCommonHelper::get_input_post( 'tax_country' );
	// TODO : $user_to_update[ 'language' ]
	WDGWPREST_Entity_User::update_from_array( $user_api_id, $user_to_update );
	$result[ 'status' ] = 'saved';
}
exit( json_encode( $result ) );
