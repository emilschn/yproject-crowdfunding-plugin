<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des modèles de contrat côté WDGWPREST
 */
class WDGWPREST_Entity_ContractModel {
	
	/**
	 * Crée un modèle de contrat sur l'API
	 * @param int $entity_id
	 * @param string $entity_type
	 * @param string $model_type
	 * @param string $model_name
	 * @param string $model_content
	 * @return object
	 */
	public static function create( $entity_id, $entity_type, $model_type, $model_name, $model_content ) {
		$parameters = array(
			'entity_id'		=> $entity_id,
			'entity_type'	=> $entity_type,
			'model_type'	=> $model_type,
			'model_name'	=> $model_name,
			'model_content'	=> $model_content
		);
		return WDGWPRESTLib::call_post_wdg( 'contract-model', $parameters );
	}
	
	/**
	 * Edite un modèle de contrat
	 * @param int $contract_model_id
	 * @param string $model_name
	 * @param string $model_content
	 * @return object
	 */
	public static function edit( $contract_model_id, $model_name, $model_content ) {
		$parameters = array(
			'model_name'	=> $model_name,
			'model_content'	=> $model_content
		);
		return WDGWPRESTLib::call_post_wdg( 'contract-model/' .$contract_model_id, $parameters );
	}
	
}