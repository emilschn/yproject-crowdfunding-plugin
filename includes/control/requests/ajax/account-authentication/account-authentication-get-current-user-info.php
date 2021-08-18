<?php
/**
 * Donne les informations à Account Authentication en fonction de l'utilisateur connecté
 */
$result = array(
	'status'			=> '',
	'firstname'			=> '',
	'lastname'			=> ''
);

// L'utilisateur n'est pas connecté
AjaxCommonHelper::exit_if_not_logged_in();

// Récupération de l'ID de l'utilisateur en cours
$current_user_id = get_current_user_id();

// Normalement ça ne devrait pas arriver, mais controle de sécurité si l'utilisateur correspond à une organisation
AjaxCommonHelper::exit_if_current_user_is_organization( $current_user_id );

// Si on arrive ici, c'est un compte de personne physique dont on récupère l'ID API
$user_api_id = AjaxCommonHelper::get_user_api_id_by_wpref( $current_user_id );

// Récupération des informations de l'utilisateur sur l'API
if ( !empty( $user_api_id ) ) {
	$api_data = WDGWPREST_Entity_User::get( $user_api_id, TRUE );
	if ( !empty( $api_data ) ) {
		$result[ 'firstname' ] = $api_data->name;
		$result[ 'lastname' ] = $api_data->surname;
		$result[ 'gender' ] = $api_data->gender;
		$datetime_birthday = new DateTime( $api_data->birthday_date );
		$result[ 'birthday_day' ] = $datetime_birthday->format( 'd' );
		$result[ 'birthday_month' ] = $datetime_birthday->format( 'm' );
		$result[ 'birthday_year' ] = $datetime_birthday->format( 'Y' );
		$result[ 'birthday_city' ] = html_entity_decode( $api_data->birthday_city, ENT_QUOTES | ENT_HTML401 );
		$result[ 'birthday_district' ] = $api_data->birthday_district;
		$result[ 'birthday_department' ] = $api_data->birthday_department;
		$result[ 'birthday_country' ] = $api_data->birthday_country;
		$result[ 'nationality' ] = $api_data->nationality;
		$result[ 'address_number' ] = $api_data->address_number;
		$result[ 'address_number_comp' ] = $api_data->address_number_comp;
		$result[ 'address_street' ] = html_entity_decode( $api_data->address, ENT_QUOTES | ENT_HTML401 );
		$result[ 'address_postalcode' ] = $api_data->postalcode;
		$result[ 'address_city' ] = $api_data->city;
		$result[ 'address_country' ] = $api_data->country;
		$result[ 'tax_country' ] = $api_data->tax_country;
	}
}
exit( json_encode( $result ) );