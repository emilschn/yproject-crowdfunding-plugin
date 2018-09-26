<?php
/**
 * Lib de gestion des votes
 */
class WDGCampaignVotes {
	public static $table_name_votes = 'ypcf_project_votes';
    
	/**
	 * Retourne les résultats de vote d'un projet
	 * @param int $camp_id
	 * @return array
	 */
	public static function get_results($camp_id) {
		if (!isset($camp_id)) return FALSE;
		
		global $wpdb;
		$table_name = $wpdb->prefix . WDGCampaignVotes::$table_name_votes;

		$post_camp = get_post($camp_id);
		$campaign = atcf_get_campaign( $post_camp );
		$campaign_id =  $campaign->ID;

		$buffer = array(
			'count_voters' => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id ),
			'count_preinvestments' => 0,
			'amount_preinvestments' => 0,
			'average_impact_economy' => 0,
			'average_impact_environment' => 0,
			'average_impact_social' => 0,
			'list_impact_others_string' => '',
			'count_project_validated' => 0,
			'percent_project_validated' => 0,
			'percent_project_not_validated' => 0,
			'rate_project_list' => array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0),
			'rate_project_average' => 0,
			'count_invest_ready' => 0,
			'sum_invest_ready' => 0,
			'average_invest_ready' => 0,
			'median_invest_ready' => 0,
			'show_risk' => ($campaign->funding_type() != 'fundingdonation'),
			'average_risk' => 0,
			'risk_list' => array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0),
			'count_more_info_impact' => 0,
			'count_more_info_service' => 0,
			'count_more_info_team' => 0,
			'count_more_info_finance' => 0,
			'count_more_info_other' => 0,
			'string_more_info_other' => '',
			'list_more_info_other' => array(),
			'objective' => $campaign->minimum_goal(),
			'list_advice' => array(),
			'list_date' => array(),
			'list_sum_by_date' => array(),
			'list_votes' => array(),
			'list_cumul' => array(),
			'list_cumul_pos' => array(),
			'list_cumul_neg' => array(),
			'list_evo_pos' => array(),
			'list_evo_neg' => array(),
			'list_preinvestments' => array(),
			'list_investments' => array()
		);

		if ($buffer['count_voters'] > 0) {
			$payments_data = $campaign->payments_data();
			foreach ( $payments_data as $item_invest ) {
				$payment_investment = new WDGInvestment( $item_invest[ 'ID' ] );
				$contract_status = $payment_investment->get_contract_status();
				
				$investment_item = array();
				$investment_item[ 'date' ] = get_post_field( 'post_date', $payment_investment->get_id() );
				$investment_item[ 'sum' ] = $payment_investment->get_saved_amount();
					
				if ( $contract_status == WDGInvestment::$contract_status_investment_validated || $contract_status == WDGInvestment::$contract_status_preinvestment_validated ) {
					$buffer[ 'count_preinvestments' ]++;
					$buffer[ 'amount_preinvestments' ] += $payment_investment->get_saved_amount();
					array_push( $buffer[ 'list_preinvestments' ], $investment_item );
				} else {
					array_push( $buffer[ 'list_investments' ], $investment_item );
				}
			}
			
			
			$buffer['total_impact_economy'] = $wpdb->get_var( "SELECT sum(impact_economy) FROM ".$table_name." WHERE post_id = ".$campaign_id );
			$buffer['average_impact_economy'] = $buffer['total_impact_economy'] / $buffer['count_voters'];
			$buffer['total_impact_environment'] = $wpdb->get_var( "SELECT sum(impact_environment) FROM ".$table_name." WHERE post_id = ".$campaign_id );
			$buffer['average_impact_environment'] = $buffer['total_impact_environment'] / $buffer['count_voters'];
			$buffer['total_impact_social'] = $wpdb->get_var( "SELECT sum(impact_social) FROM ".$table_name." WHERE post_id = ".$campaign_id );
			$buffer['average_impact_social'] = $buffer['total_impact_social'] / $buffer['count_voters'];
			$buffer['list_impact_others'] = $wpdb->get_results( "SELECT impact_other FROM ".$table_name." WHERE post_id = ".$campaign_id." AND impact_other <> ''" );
			foreach ($buffer['list_impact_others'] as $impact_others) { 
				if ($buffer['list_impact_others_string'] != '') $buffer['list_impact_others_string'] .= ', ';
				$buffer['list_impact_others_string'] .= html_entity_decode($impact_others->impact_other, ENT_QUOTES | ENT_HTML401);
			}


			$buffer['count_project_validated'] = $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND validate_project = 1" );
			$buffer['percent_project_validated'] = round($buffer['count_project_validated'] / $buffer['count_voters'], 2) * 100;
			$buffer['percent_project_not_validated'] = round(($buffer['count_voters'] - $buffer['count_project_validated']) / $buffer['count_voters'], 2) * 100;
			
			$buffer[ 'rate_project_list' ] = array(
				1 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id. " AND rate_project=1" ), 
				2 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id. " AND rate_project=2" ), 
				3 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id. " AND rate_project=3" ), 
				4 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id. " AND rate_project=4" ), 
				5 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id. " AND rate_project=5" )
			);
			$sum_rate_project = $wpdb->get_var( "SELECT sum(rate_project) FROM ".$table_name." WHERE post_id = ".$campaign_id );
			$buffer[ 'rate_project_average' ] = round( $sum_rate_project / $buffer[ 'count_voters' ], 2 );

			$buffer['count_invest_ready'] = $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND invest_sum > 0" );
			if ($buffer['count_invest_ready'] > 0) {
			    $buffer['sum_invest_ready'] = $wpdb->get_var( "SELECT sum(invest_sum) FROM ".$table_name." WHERE post_id = ".$campaign_id );
			    $buffer['average_invest_ready'] = $buffer['sum_invest_ready'] / $buffer['count_invest_ready'];
			    if ($buffer['count_invest_ready'] == 1) {
				$buffer['median_invest_ready'] = $buffer['average_invest_ready'];
			    } else {
				$median = 0;
				if ($buffer['count_invest_ready'] > 2) $median = round(($buffer['count_invest_ready'] + 1) / 2);
				$buffer['median_invest_ready'] = $wpdb->get_var( "SELECT invest_sum FROM ".$table_name." WHERE post_id = ".$campaign_id." AND invest_sum > 0 ORDER BY `invest_sum` LIMIT ".$median.", 1" );
			    }
			}

			$buffer['count_risk'] = $wpdb->get_var( "SELECT sum(invest_risk) FROM ".$table_name." WHERE post_id = ".$campaign_id );
			$buffer['average_risk'] = $buffer['count_risk'] / $buffer['count_voters'];

			$buffer['risk_list'] = array(
			    1 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND invest_risk = 1" ),
			    2 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND invest_risk = 2" ),
			    3 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND invest_risk = 3" ),
			    4 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND invest_risk = 4" ),
			    5 => $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND invest_risk = 5" ),
			);

			$buffer['count_more_info_impact'] = $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND more_info_impact = 1" );
			$buffer['count_more_info_service'] = $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND more_info_service = 1" );
			$buffer['count_more_info_team'] = $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND more_info_team = 1" );
			$buffer['count_more_info_finance'] = $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND more_info_finance = 1" );
			$buffer['count_more_info_other'] = $wpdb->get_var( "SELECT count(user_id) FROM ".$table_name." WHERE post_id = ".$campaign_id." AND more_info_other <> ''" );
			$buffer['more_info_other'] = $wpdb->get_results( "SELECT user_id, more_info_other FROM ".$table_name." WHERE post_id = ".$campaign_id." AND more_info_other <> ''" );
			foreach ($buffer['more_info_other'] as $more_info_other_item) { 
				if ($buffer['string_more_info_other'] != '') $buffer['string_more_info_other'] .= ', <br/>';
				$buffer['string_more_info_other'] .= html_entity_decode($more_info_other_item->more_info_other, ENT_QUOTES | ENT_HTML401);
				$more_info_other_item = array(
					'user_id' => $more_info_other_item->user_id,
					'text' => $more_info_other_item->more_info_other
				);
				array_push( $buffer[ 'list_more_info_other' ], $more_info_other_item );
			}

			$buffer['list_advice'] = $wpdb->get_results( "SELECT user_id, advice FROM ".$table_name." WHERE post_id = ".$campaign_id." AND advice <> ''" );

			$dates_votes = $wpdb->get_results( "SELECT validate_project, date, invest_sum FROM ".$table_name." WHERE post_id = ".$campaign_id." ORDER BY `date` ASC" );

			//Parcours des votes par date :
			foreach ( $dates_votes as $vote ) {
			    if (end($buffer['list_date']) != $vote->date){
					//Si on est sur un nouveau jour
					$buffer['list_date'][]= $vote->date;
					
					$buffer[ 'list_sum_by_date' ][] = array(
						'date' => $vote->date,
						'sum' => $vote->invest_sum
					);
					
					$buffer['list_evo_pos'][]=0;
					$buffer['list_evo_neg'][]=0;

					if(end($buffer['list_cumul'])===false){
						$buffer['list_cumul'][]=0;

					} else {
						$buffer['list_cumul'][]=end($buffer['list_cumul']);
					}
			    }
				array_push( $buffer[ 'list_votes' ], $vote );

			    if ($vote->validate_project==1){
					$buffer['list_cumul'][count($buffer['list_cumul'])-1]++;
			    } else {
					$buffer['list_cumul'][count($buffer['list_cumul'])-1]++;
			    }
			}
		} else {
			$buffer['count_voters'] = 0;
		}

		return $buffer;
	}
	
	public static function upgrade_db() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$table_name = $wpdb->prefix . WDGCampaignVotes::$table_name_votes;
		$sql = "CREATE TABLE " .$table_name. " (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			post_id bigint(20) NOT NULL,
			impact_economy smallint(2) NOT NULL,
			impact_environment smallint(2) NOT NULL,
			impact_social smallint(2) NOT NULL,
			impact_other text NOT NULL,
			validate_project tinyint(1) NOT NULL,
			rate_project smallint(2) NOT NULL,
			invest_sum bigint(20) NOT NULL,
			invest_risk smallint(2) NOT NULL,
			more_info_impact tinyint(1) NOT NULL,
			more_info_service tinyint(1) NOT NULL,
			more_info_team tinyint(1) NOT NULL,
			more_info_finance tinyint(1) NOT NULL,
			more_info_other text NOT NULL,
			advice text NOT NULL,
			date date NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
		$result = dbDelta( $sql );
	}
	
	public static function move_validate_to_rate() {
		global $wpdb;
		$table_name = $wpdb->prefix . WDGCampaignVotes::$table_name_votes;
		$wpdb->update(
			$table_name,
			array(
				'rate_project' => '5'
			),
			array(
				'validate_project' => '1'
			)
		);
		$wpdb->update(
			$table_name,
			array(
				'rate_project' => '1'
			),
			array(
				'validate_project' => '0'
			)
		);
	}
}