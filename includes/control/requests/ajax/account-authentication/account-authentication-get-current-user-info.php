<?php
/**
 * Donne les informations à Account Authentication en fonction de l'utilisateur connecté
 */
function account_autenthication_get_current_user_info() {
	$result = array(
		'status'			=> '',
		'firstname'			=> '',
		'lastname'			=> ''
	);

	// L'utilisateur n'est pas connecté
	if ( !is_user_logged_in() ) {
		$result[ 'status' ] = 'not-logged-in';

		return $result;
	}
	$current_user_id = get_current_user_id();

	// Normalement ça ne devrait pas arriver, mais controle de sécurité :
	// L'identifiant de l'utilisateur en cours correspond à une organisation
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

		return $result;
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
	return $result;
}

$result = account_autenthication_get_current_user_info();
exit( json_encode( $result ) );