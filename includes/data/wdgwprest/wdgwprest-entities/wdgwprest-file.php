<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des fichiers côté WDGWPREST
 */
class WDGWPREST_Entity_File {
	
	/**
	 * Crée un fichier sur l'API
	 * @param int $entity_id
	 * @param string $entity_type
	 * @param string $file_type
	 * @param string $file_extension
	 * @param string $file_base64_content
	 * @param string $metadata
	 * @return object
	 */
	public static function create( $entity_id, $entity_type, $file_type, $file_extension, $file_base64_content, $metadata= '' ) {
		$parameters = array(
			'entity_id'			=> $entity_id,
			'entity_type'		=> $entity_type,
			'file_type'			=> $file_type,
			'file_extension'	=> $file_extension,
			'data'				=> $file_base64_content,
			'metadata'			=> $metadata
		);
		return WDGWPRESTLib::call_post_wdg( 'file', $parameters );
	}
	
}
