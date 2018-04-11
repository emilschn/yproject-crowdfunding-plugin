<?php
/**
 * Lib de gestion des investissements
 */
class WDGCampaignInvestments {
	/**
	 * Retourne la liste des investissements d'un projet
	 * @param int $camp_id
	 * @return array
	 */
	public static function get_list($camp_id, $include_pending = FALSE) {
		$campaign = new ATCF_Campaign( $camp_id );
		$payments_data = $campaign->payments_data();

		$buffer = array(
			'campaign' => $campaign,
			'payments_data' => $payments_data,
			'count_validate_investments' => 0,
			'count_not_validate_investments' => 0,
			'count_validate_investors' => 0,
			'count_age' => 0,
			'count_average_age' => 0,
			'count_female' => 0,
			'count_invest' => 0,
			'amounts_array' => array(),
			'average_age' => 0,
			'percent_female' => 0,
			'percent_male' => 0,
			'average_invest' => 0,
			'median_invest' => 0,
			'min_invest' => 0,
			'max_invest' => 0,
			'amount_check' => $campaign->current_amount_check_meta(),
			'investors_list' => array(),
			'investors_string' => ''
		);
		foreach ( $payments_data as $item ) {
			//Prend en compte ou pas dans les stats les paiements non validés
			if ($item['status'] == 'publish' || ($include_pending && $item['status'] == 'pending')) {

				if (!isset($buffer['investors_list'][$item['user']])) {
					$buffer['count_validate_investors']++;
					$buffer['investors_list'][$item['user']] = $item['user'];
					$WDGUser = FALSE;
					if ( empty( $item['item'] ) ) {
						$WDGUser = new WDGUser( $item['user'] );
					}
					$gender = empty( $item['item'] ) ? $WDGUser->get_gender() : $item['item']->gender;
					if ($gender != "") {
						$age = empty( $item['item'] ) ? $WDGUser->get_age() : $item['item']->age;
						if ($age < 200) {
							$buffer['count_age'] += $age;
							$buffer['count_average_age'] ++;
						}
					}
					if ($gender == "female") $buffer['count_female']++;
					if ($buffer['investors_string'] != '') $buffer['investors_string'] .= ', ';
					$buffer['investors_string'] .= empty( $item['item'] ) ? $WDGUser->get_display_name() : $item['item']->firstname. ' ' .substr( $item['item']->lastname, 0, 1 ). '.';;
				}
				$buffer['count_invest'] += $item['amount'];
				$buffer['amounts_array'][] = $item['amount'];
			}
			if ($item['status'] == 'publish') {
			    $buffer['count_validate_investments']++;
			} else if ($item['status'] == 'pending') {
			    $buffer['count_not_validate_investments']++;
			}
		}

		sort($buffer['amounts_array']);

		if ($buffer['count_validate_investments'] > 0) {
			$buffer['average_age'] = round($buffer['count_age'] / $buffer['count_average_age'], 1);
			$buffer['percent_female'] = round($buffer['count_female'] / $buffer['count_validate_investors'] * 100);
			$buffer['percent_male'] = 100 - $buffer['percent_female'];
			$buffer['average_invest'] = round($buffer['count_invest'] / $buffer['count_validate_investments'], 2);
			$buffer['median_invest'] = $buffer['amounts_array'][0];
			if ($buffer['count_validate_investments'] > 2) {
				$index = round(($buffer['count_validate_investments'] + 1) / 2) - 1;
				$buffer['median_invest'] = $buffer['amounts_array'][$index];
			}
			$buffer['min_invest']= $buffer['amounts_array'][0];
			$buffer['max_invest']= end($buffer['amounts_array']);
		}

		return $buffer;
	}
	
	/**
	 * 
	 * @param type $id_payment
	 */
	public static function cancel($payment_id) {
		$postdata = array(
		    'ID'	    => $payment_id,
		    'post_status'   => 'failed',
		    'edit_date'	    => current_time( 'mysql' )
		);
		wp_update_post($postdata);
	}
}