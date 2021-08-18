<?php
/**
 * Enregistre les informations de l'utilisateur en cours depuis Account Authentication
 */
$result = array(
	'status'			=> ''
);

// L'utilisateur n'est pas connecté
AjaxCommonHelper::exit_if_not_logged_in();

// Récupération de l'ID de l'utilisateur en cours
$current_user_id = get_current_user_id();

// Normalement ça ne devrait pas arriver, mais controle de sécurité si l'utilisateur correspond à une organisation
AjaxCommonHelper::exit_if_current_user_is_organization( $current_user_id );

// Si on arrive ici, c'est un compte de personne physique dont on récupère l'ID API
$user_api_id = AjaxCommonHelper::get_user_api_id_by_wpref( $current_user_id );

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
