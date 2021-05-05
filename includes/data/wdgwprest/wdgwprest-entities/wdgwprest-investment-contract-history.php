<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestion de l'historique des modifications d'un contrat d'investissement
 */
class WDGWPREST_Entity_InvestmentContractHistory {
	public static function set_post_parameters($investment_contract_id, $date, $data_modified, $old_value, $new_value, $list_new_contracts, $comment) {
		$parameters = array(
			'investment_contract_id'	=> $investment_contract_id,
			'date'						=> $date,
			'data_modified'				=> $data_modified,
			'old_value'					=> $old_value,
			'new_value'					=> $new_value,
			'list_new_contracts'		=> $list_new_contracts,
			'comment'					=> $comment
		);

		return $parameters;
	}

	/**
	 * Crée une ligne de donnée sur l'API
	 */
	public static function create($investment_contract_id, $date, $data_modified, $old_value, $new_value, $list_new_contracts, $comment) {
		$buffer = FALSE;

		$parameters = self::set_post_parameters( $investment_contract_id, $date, $data_modified, $old_value, $new_value, $list_new_contracts, $comment );
		if ( !empty( $parameters ) ) {
			$buffer = WDGWPRESTLib::call_post_wdg( 'investment-contract-history', $parameters );
			if ( isset( $buffer->code ) && $buffer->code == 400 ) {
				$buffer = FALSE;
			}
		}

		return $buffer;
	}
}
