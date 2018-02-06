<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des contrats côté WDGWPREST
 */
class WDGWPREST_Entity_Contract {
	
	/**
	 * Récupère un contrat par son identifiant
	 * @param int $contract_id
	 * @return object
	 */
	public static function get( $contract_id ) {
		return WDGWPRESTLib::call_get_wdg( 'contract/' .$contract_id );
	}
	
	/**
	 * Crée un contrat sur l'API
	 * @param int $model_id
	 * @param string $entity_type
	 * @param int $entity_id
	 * @param string $partner
	 * @param int $partner_id
	 * @return object
	 */
	public static function create( $model_id, $entity_type, $entity_id, $partner, $partner_id ) {
		$parameters = array(
			'model_id'		=> $model_id,
			'entity_type'	=> $entity_type,
			'entity_id'		=> $entity_id,
			'partner'		=> $partner,
			'partner_id'	=> $partner_id
		);
		return WDGWPRESTLib::call_post_wdg( 'contract', $parameters );
	}
	
	/**
	 * Edite un contrat
	 * @param int $contract_id
	 * @param string $status
	 * @return object
	 */
	public static function edit( $contract_id, $status ) {
		$parameters = array(
			'status'	=> $status
		);
		return WDGWPRESTLib::call_post_wdg( 'contract/' .$contract_id, $parameters );
	}
	
}
