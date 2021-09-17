<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des fichiers KYC côté WDGWPREST
 */
class WDGWPREST_Entity_FileKYC {
	
	/**
	 * Crée un fichier KYC sur l'API
	 * @param int $user_id
	 * @param int $organization_id
	 * @param string $doc_type
	 * @param int $doc_index
	 * @param string $file_extension
	 * @param string $file_base64_content
	 * @param string $metadata
	 * @return object
	 */
	public static function create( $user_id, $organization_id, $doc_type, $doc_index, $file_extension, $file_base64_content, $metadata= '' ) {
		$parameters = array(
			'user_id'			=> $user_id,
			'organization_id'	=> $organization_id,
			'doc_type'			=> $doc_type,
			'doc_index'			=> $doc_index,
			'file_extension'	=> $file_extension,
			'data'				=> $file_base64_content,
			'metadata'			=> $metadata
		);
		return WDGWPRESTLib::call_post_wdg( 'file-kyc', $parameters, TRUE );
	}

	/**
	 * Demande à l'API d'envoyer le fichier à LW
	 */
	public static function send_to_lemonway( $file_kyc_id ) {
		return WDGWPRESTLib::call_get_wdg( 'file-kyc/' . $file_kyc_id . '/send-to-lemonway' );
	}
	
	/**
	 * Met à jour le statut d'un document
	 * Pour supprimer un document, passer en statut 'removed'
	 */
	public static function update_status( $filekyc_api_id, $status ) {
		$parameters = array(
			'status'	=> $status
		);
		return WDGWPRESTLib::call_post_wdg( 'file-kyc/' . $filekyc_api_id, $parameters, TRUE );
	}

	/**
	 * Retourne la liste des fichiers en fonction d'un type d'entité et de son identifiant
	 */
	public static function get_list_by_entity_id( $entity_type, $user_id, $organization_id ) {
		return WDGWPRESTLib::call_get_wdg( 'files-kyc/?entity_type=' . $entity_type . '&user_id=' . $user_id . '&organization_id=' . $organization_id, TRUE );
	}
}
