<?php
/**
 * Enregistre les infos de conformité de l'utilisateur en cours
 */
$result = array(
	'status' => ''
);

// L'utilisateur n'est pas connecté
AjaxCommonHelper::exit_if_not_logged_in();

// Récupération de l'ID de l'utilisateur en cours
$current_user_id = get_current_user_id();

// Normalement ça ne devrait pas arriver, mais controle de sécurité si l'utilisateur correspond à une organisation
AjaxCommonHelper::exit_if_current_user_is_organization( $current_user_id );

// Si on arrive ici, c'est un compte de personne physique dont on récupère l'ID API
$user_api_id = AjaxCommonHelper::get_user_api_id_by_wpref( $current_user_id );

// Récupération d'une éventuelle ligne existante
$existing_data = WDGWPREST_Entity_UserConformity::get_by_user_id( $user_api_id, TRUE );
$metadata = filter_input( INPUT_POST, 'metadata' );

if ( !empty( $existing_data ) && !empty( $existing_data->id ) ) {
	$result[ 'status' ] = 'updated';
	WDGWPREST_Entity_UserConformity::update( $existing_data->id, $user_api_id, $metadata );

} else {
	$result[ 'status' ] = 'created';
	WDGWPREST_Entity_UserConformity::create( $user_api_id, $metadata );
}

$api_data = WDGWPREST_Entity_User::get( $user_api_id, TRUE );
if ( !empty( $api_data ) ) {
	function addOrRemoveFromList( $sib_instance, $email, $sib_id, $add ) {
		if ( $add ) {
			$sib_instance->addContactToList( $email, $sib_id );
		} else {
			$sib_instance->removeContactFromList( $email, $sib_id );
		}
	}
	$sib_instance = SIBv3Helper::instance( FALSE );
	$metadata_decoded = json_decode( $metadata );
	addOrRemoveFromList( $sib_instance, $api_data->email, 283, $metadata_decoded->objectives->impactTypes->economic );
	addOrRemoveFromList( $sib_instance, $api_data->email, 281, $metadata_decoded->objectives->impactTypes->social );
	addOrRemoveFromList( $sib_instance, $api_data->email, 280, $metadata_decoded->objectives->impactTypes->environmental );
	addOrRemoveFromList( $sib_instance, $api_data->email, 279, $metadata_decoded->objectives->purposeTypes->local );
}

exit( json_encode( $result ) );
