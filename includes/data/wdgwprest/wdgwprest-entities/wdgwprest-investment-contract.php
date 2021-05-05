<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestion des contrats d'investissement côté WDGWPREST
 */
class WDGWPREST_Entity_InvestmentContract {
	/**
	 * Retourne un contrat d'investissement à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get($id) {
		return WDGWPRESTLib::call_get_wdg( 'investment-contract/' . $id );
	}

	/**
	 * Détermine les données à envoyer à l'API
	 * @param WDGInvestmentContract $investment_contract
	 */
	public static function set_post_parameters($investment_contract) {
		$parameters = array(
			'investor_id'			=> $investment_contract->investor_id,
			'investor_type'			=> $investment_contract->investor_type,
			'project_id'			=> $investment_contract->project_id,
			'organization_id'		=> $investment_contract->organization_id,
			'subscription_id'		=> $investment_contract->subscription_id,
			'subscription_date'		=> $investment_contract->subscription_date,
			'subscription_amount'	=> $investment_contract->subscription_amount,
			'status'				=> $investment_contract->status,
			'start_date'			=> $investment_contract->start_date,
			'end_date'				=> $investment_contract->end_date,
			'frequency'				=> $investment_contract->frequency,
			'turnover_type'			=> $investment_contract->turnover_type,
			'turnover_percent'		=> $investment_contract->turnover_percent,
			'amount_received'		=> $investment_contract->amount_received,
			'minimum_to_receive'	=> $investment_contract->minimum_to_receive,
			'maximum_to_receive'	=> $investment_contract->maximum_to_receive
		);

		return $parameters;
	}

	/**
	 * Crée une ligne de données sur l'API
	 * @param WDGInvestmentContract $investment_contract
	 */
	public static function create($investment_contract) {
		$buffer = FALSE;

		$parameters = WDGWPREST_Entity_InvestmentContract::set_post_parameters( $investment_contract );
		if ( !empty( $parameters ) ) {
			$buffer = WDGWPRESTLib::call_post_wdg( 'investment-contract', $parameters );
			if ( isset( $buffer->code ) && $buffer->code == 400 ) {
				$buffer = FALSE;
			}
		}

		return $buffer;
	}

	/**
	 * Edite un contrat d'investissement
	 * @param int $investment_contract_id
	 * @param float $amount_received
	 * @return object
	 */
	public static function edit($investment_contract_id, $amount_received) {
		$parameters = array(
			'amount_received'	=> $amount_received
		);

		return WDGWPRESTLib::call_post_wdg( 'investment-contract/' .$investment_contract_id, $parameters );
	}

	/**
	 * Met à jour un contrat d'investissement
	 */
	public static function update($investment_contract_id, $investment_contract) {
		$parameters = WDGWPREST_Entity_InvestmentContract::set_post_parameters( $investment_contract );

		return WDGWPRESTLib::call_post_wdg( 'investment-contract/' .$investment_contract_id, $parameters );
	}

	public static function get_list_by_subscription_id($id_investment) {
		return WDGWPRESTLib::call_get_wdg( 'investment-contracts/?id_subscription=' . $id_investment );
	}
}
