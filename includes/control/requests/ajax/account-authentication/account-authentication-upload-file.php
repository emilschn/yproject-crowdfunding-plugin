<?php
/**
 * Gestion de l'upload d'un fichier transmis par Account Authentication
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
$organization_id = $id_api_organization;
if ( empty( $organization_id ) ) {
	$organization_id = 0;
}

// Récupération du fichier
$file_document = AjaxCommonHelper::get_input_file( 'document' );
$file_name = $file_document[ 'name' ];
$file_name_exploded = explode( '.', $file_name );
$ext = $file_name_exploded[ count( $file_name_exploded ) - 1 ];
$byte_array = file_get_contents( $file_document[ 'tmp_name' ] );

// Envoi du fichier à l'API
$create_feedback = WDGWPREST_Entity_FileKYC::create( $user_id, $organization_id, $doc_type, $doc_index, $ext, base64_encode( $byte_array ) );

// TODO
$result[ 'status' ] = 'success';

exit( json_encode( $result ) );
