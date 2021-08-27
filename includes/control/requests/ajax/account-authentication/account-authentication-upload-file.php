<?php
/**
 * Gestion de l'upload d'un fichier transmis par Account Authentication
 */
$result = array(
	'status'				=> ''
);

// L'utilisateur n'est pas connecté
AjaxCommonHelper::exit_if_not_logged_in();

// Récupération de l'ID de l'utilisateur en cours
$current_user_id = get_current_user_id();

// Normalement ça ne devrait pas arriver, mais controle de sécurité si l'utilisateur correspond à une organisation
AjaxCommonHelper::exit_if_current_user_is_organization( $current_user_id );

// Si on arrive ici, c'est un compte de personne physique dont on récupère l'ID API
$id_api_user = AjaxCommonHelper::get_user_api_id_by_wpref( $current_user_id );

// Récupération des informations transmises par le formulaire
$user_type = AjaxCommonHelper::get_input_post( 'user_type' );
$id_api = AjaxCommonHelper::get_input_post( 'id_api' );
$file_type = AjaxCommonHelper::get_input_post( 'file_type' );

exit( json_encode( $result ) );