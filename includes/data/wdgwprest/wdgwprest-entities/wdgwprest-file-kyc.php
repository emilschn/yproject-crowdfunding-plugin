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
		if ( !in_array( strtolower( $file_extension ), WDGKYCFile::$authorized_format_list ) ) {
			return 'EXT';
		}
		$file_size = strlen( base64_decode( $file_base64_content ) );
		if ( $file_size < 10 ) {
			return 'UPLOAD';
		}
		if ( ( $file_size / 1024) / 1024 > 6 ) {
			return 'SIZE';
		}

		$parameters = array(
			'user_id'			=> $user_id,
			'organization_id'	=> $organization_id,
			'doc_type'			=> $doc_type,
			'doc_index'			=> $doc_index,
			'file_extension'	=> $file_extension,
			'data'				=> $file_base64_content,
			'metadata'			=> $metadata
		);
		$result_obj = WDGWPRESTLib::call_post_wdg( 'file-kyc', $parameters, TRUE );
		if (isset($result_obj->code) && $result_obj->code == 400) { 
			ypcf_debug_log( 'wdgwprest-file-kyc.php :: create PB de création de file-kyc $result_obj = '.var_export($result_obj, TRUE ));
			$result_obj = ''; 
		}
		return $result_obj;
	}

	/**
	 * Retourne un fichier en fonction de son id
	 */
	public static function get( $file_kyc_id ) {
		return WDGWPRESTLib::call_get_wdg( 'file-kyc/' . $file_kyc_id, TRUE );
	}

	/**
	 * Retourne un fichier en fonction de son gateway_id
	 */
	public static function get_by_gateway_id( $gateway_id ) {
		return WDGWPRESTLib::call_get_wdg( 'file-kyc/gateway_id/' . $gateway_id, TRUE );
	}
	/**
	 * Mise à jour du kycfile sur l'API à partir d'un id
	 * @param WDGUser $user
	 * @return object
	 */
	public static function update( WDGKYCFile $filekyc ) {
		$parameters = array(
			'id'				=> $filekyc->id,
			'user_id'			=> $filekyc->user_id,
			'orga_id'			=> $filekyc->orga_id,
			'type'				=> $filekyc->type,
			'doc_index'			=> $filekyc->doc_index,
			'doc_type'			=> $filekyc->type,
			'file_extension'	=> $filekyc->file_extension,
			'file_name'			=> $filekyc->file_name,
			'file_signature'	=> $filekyc->file_signature,
			'update_date'		=> $filekyc->date_uploaded,
			'status'			=> $filekyc->status,
			'gateway'			=> $filekyc->gateway,
			'gateway_user_id'	=> $filekyc->gateway_user_id,
			'gateway_organization_id'=> $filekyc->gateway_organization_id,
			'metadata'			=> $filekyc->metadata
		);
		return WDGWPRESTLib::call_post_wdg( 'file-kyc/' . $filekyc->id, $parameters, TRUE );
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
