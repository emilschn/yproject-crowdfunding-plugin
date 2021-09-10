<?php
/**
 * Gestion de la suppression d'un fichier demandée par Account Authentication
 */
$result = array(
	'status' => ''
);
/*
// L'utilisateur n'est pas connecté
AjaxCommonHelper::exit_if_not_logged_in();

// Récupération de l'ID de l'utilisateur en cours
$current_user_id = get_current_user_id();

// Normalement ça ne devrait pas arriver, mais controle de sécurité si l'utilisateur correspond à une organisation
AjaxCommonHelper::exit_if_current_user_is_organization( $current_user_id );*/
// TODO : à remettre
$current_user_id = 1;

// Si on arrive ici, c'est un compte de personne physique dont on récupère l'ID API
$id_api_user = AjaxCommonHelper::get_user_api_id_by_wpref( $current_user_id );

// Récupération des informations transmises par le formulaire
$user_type = AjaxCommonHelper::get_input_post( 'user_type' );
$id_api_organization = AjaxCommonHelper::get_input_post( 'id_api_organization' );
$doc_type = AjaxCommonHelper::get_input_post( 'doc_type' );
$doc_index = AjaxCommonHelper::get_input_post( 'doc_index' );

// Définition des identifiants utilisateur à lier
$user_id = $id_api_user;
if ( $user_type === 'organization' ) {
	$user_id = 0;
}
$organization_id = $id_api_organization;
if ( empty( $organization_id ) ) {
	$organization_id = 0;
}

// Récupération de l'identifiant API du fichier existant
$filekyc_api_id = FALSE;
$file_list = WDGWPREST_Entity_FileKYC::get_list_by_entity_id( $user_type, $user_id, $organization_id );
// Parcourir la liste, vérifier le type et l'index de documents, et si le statut n'est pas déjà "removed"
foreach ( $file_list as $file_item ) {
	if ( $file_item->doc_type == $doc_type && $file_item->doc_index == $doc_index && $file_item->status != 'removed' ) {
		$filekyc_api_id = $file_item->id;
		break;
	}
}

if ( !empty( $filekyc_api_id ) ) {
	// Envoi de la demande de suppression à l'API
	$create_feedback = WDGWPREST_Entity_FileKYC::update_status( $filekyc_api_id, 'removed' );
	
	// TODO
	$result[ 'status' ] = 'success';
}

exit( json_encode( $result ) );
