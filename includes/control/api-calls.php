<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe temporaire de gestion des appels de l'API (pour les données qui lui manquent)
 */
class WDGAPICalls {
	
	private $action;
	private $param;
	
	public function __construct( $action = '', $param = '' ) {
		if ( empty( $action ) ) {
			$this->action = filter_input( INPUT_GET, 'action' );
			$this->param = filter_input( INPUT_GET, 'param' );
			
		} else {
			$this->action = $action;
			$this->param = $param;
		}
		
		if ( !empty( $this->action ) ) {
			ypcf_debug_log( 'WDGAPICalls::__construct > $this->action : ' .$this->action. ' ; $this->param : ' . $this->param );
			$this->{ $this->action }( $this->param );
		}
	}
	
	private function get_royalties_by_project( $project_id ) {
		if ( !empty( $project_id ) ) {
			$campaign = new ATCF_Campaign( $project_id );
			$result = $campaign->get_roi_declarations();
			exit( json_encode( $result ) );
		}
	}
	
	private function get_royalties_by_user( $email ) {
		$buffer = array();
		if ( !empty( $email ) ) {
			$query_user = get_user_by( 'email', $email );
			if ( $query_user ) {
				$user = new WDGUser( $query_user->ID );
				$result = $user->get_rois();
				foreach ( $result as $roi ) {
					$roi_item = array();
					$roi_item["id"] = $roi->id;
					$roi_item["project"] = $roi->id_campaign;
					$roi_item["date"] = $roi->date_transfer;
					$roi_item["amount"] = $roi->amount;
					array_push( $buffer, $roi_item );
				}
			}
		}
		exit( json_encode( $buffer ) );
	}
		
	private function update_user_email( $input_email ) {
		$buffer = 'error';
		if ( !empty( $input_email ) ) {

			// On récupère l'utilisateur à modifier
			$init_email = html_entity_decode( $input_email );
			ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $init_email : ' .$init_email );
			$query_user = get_user_by( 'email', $init_email );
			if ( !empty( $query_user ) ) {

				// On vérifie que le nouvel e-mail est renseigné
				$new_email = filter_input( INPUT_POST, 'new_email' );
				ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $new_email : ' .$new_email );
				if ( !empty( $new_email ) ) {

					// On vérifie que le nouvel e-mail n'est pas déjà pris
					$find_existing_user = get_user_by( 'email', $new_email );
					if ( empty( $find_existing_user ) ) {
						wp_update_user( array ( 'ID' => $query_user->ID, 'user_email' => $new_email ) );
						$buffer = 'success';

					} else {
						ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $find_existing_user : ' .$find_existing_user->ID );
						$buffer = 'E-mail alreay in use';

					}
				}

			} else {
				$buffer = 'Did not find user with this e-mail';
			}
		}
		ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $buffer : ' .$buffer );
		exit( $buffer );
	}
	
	private function get_projects_stats() {
		
		$buffer = array();
		
		$query_options = array(
			'numberposts'	=> -1,
			'post_type'		=> 'download',
			'post_status'	=> 'publish'
		);
		$project_list = get_posts( $query_options );
		
		$buffer[ 'total' ] = count( $project_list );
		$buffer[ 'funded_amount' ] = 0;
		$buffer[ 'royalties_amount' ] = 0;
		
		$buffer[ 'statuses' ] = array();
		$status_list = array( 'posted', 'preparing', 'vote', 'funding', 'declaring', 'declaring_late', 'funded', 'archive' );
		foreach ( $status_list as $status ) {
			$buffer[ 'statuses' ][ $status ] = array(
				'count'		=> 0,
				'percent'	=> 0
			);
		}
		
		foreach ( $project_list as $project ) {
			$campaign = new ATCF_Campaign( $project->ID );
			$campaign_status = $campaign->campaign_status();
			if ( $campaign_status == ATCF_Campaign::$campaign_status_funded || $campaign_status == ATCF_Campaign::$campaign_status_closed ) {
				$buffer[ 'funded_amount' ] += $campaign->current_amount( false );
				$buffer[ 'royalties_amount' ] += $campaign->get_roi_declarations_total_roi_amount();
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_preparing ) {
				$buffer[ 'statuses' ][ 'posted' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_validated ) {
				$buffer[ 'statuses' ][ 'preparing' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_vote ) {
				$buffer[ 'statuses' ][ 'vote' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_collecte ) {
				$buffer[ 'statuses' ][ 'funding' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_funded ) {
				$buffer[ 'statuses' ][ 'declaring' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_funded ) {
				$current_declarations = $campaign->get_current_roi_declarations();
				if ( !empty( $current_declarations ) ) {
					$buffer[ 'statuses' ][ 'declaring_late' ][ 'count' ]++;
				}
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_funded || $campaign_status == ATCF_Campaign::$campaign_status_closed ) {
				$buffer[ 'statuses' ][ 'funded' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_archive ) {
				$buffer[ 'statuses' ][ 'archive' ][ 'count' ]++;
			}
		}
		
		foreach ( $status_list as $status ) {
			$buffer[ 'statuses' ][ $status ][ 'percent' ] = $buffer[ 'statuses' ][ $status ][ 'count' ] / $buffer[ 'total' ] * 100;
			$buffer[ 'statuses' ][ $status ][ 'percent' ] = round( $buffer[ 'statuses' ][ $status ][ 'percent' ] * 100 ) / 100;
		}
		
		
		exit( json_encode( $buffer ) );
		
	}
	
	private function get_users_stats() {
		
		$buffer = array();
		
		$result = count_users();
		
		$buffer[ 'total' ] = $result['total_users'];
		$buffer[ 'merchant_wallet' ] = 0;
		
		try {
			$wallet_details = LemonwayLib::wallet_get_details( 'SC' );
			if ( $wallet_details ) {
				$buffer[ 'merchant_wallet' ] = $wallet_details->BAL;
			}
		} catch (Exception $exc) {
		}
		
		
		$query_options = array(
			'numberposts' => -1,
			'post_type' => 'download',
			'post_status' => 'publish',
			'meta_query' => array (
				array (
					'key' => 'campaign_vote',
					'value' => array( ATCF_Campaign::$campaign_status_funded, ATCF_Campaign::$campaign_status_closed )
				)
			)
		);
		$project_list_funded = get_posts( $query_options );
		$people_list = array();
		foreach ( $project_list_funded as $project_post ) {
			$campaign = atcf_get_campaign( $project_post->ID );
			$backers_id_list = $campaign->backers_id_list();
			$people_list = array_merge( $people_list, $backers_id_list );
		}
		$people_list_unique = array_unique( $people_list );
		$buffer[ 'investors_count' ] = count( $people_list_unique );
		$count_values_people_list = array_count_values( $people_list );
		$buffer[ 'investors_multi_count' ] = 0;
		foreach ( $count_values_people_list as $user_id => $nb_invest ) {
			if ( $nb_invest > 1 ) {
				$buffer[ 'investors_multi_count' ]++;
			}
		}
		
		exit( json_encode( $buffer ) );
		
	}
	
	private function get_investments_stats() {
		
		$buffer = array();
		
		// Totaux
		$query_options = array(
			'numberposts'	=> -1,
			'post_type'		=> 'edd_payment',
			'post_status'	=> 'publish'
		);
		$investments_list = get_posts( $query_options );
		$buffer[ 'total' ] = count( $investments_list );
		
		$query_options[ 'post_status' ] = 'failed';
		$investments_list = get_posts( $query_options );
		$buffer[ 'payment_errors' ] = count( $investments_list );
		
		
		// Totaux 30 derniers jours
		$query_options = array(
			'numberposts'	=> -1,
			'post_type'		=> 'edd_payment',
			'post_status'	=> 'publish',
			'date_query'	=> array(
				array(
					'after'		=> '-30 days',
					'column'	=> 'post_date',
				)
			)
		);
		$investments_monthly_list = get_posts( $query_options );
		$buffer[ 'total_last_30_days' ] = count( $investments_monthly_list );
		
		$query_options[ 'post_status' ] = 'failed';
		$investments_monthly_list = get_posts( $query_options );
		$buffer[ 'payment_errors_monthly' ] = count( $investments_monthly_list );
		
		
		// Totaux par mois
		$date_now = new DateTime();
		$buffer[ 'total_by_month' ] = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$query_options = array(
				'numberposts'	=> -1,
				'post_type'		=> 'edd_payment',
				'post_status'	=> 'publish',
				'date_query'	=> array(
					array(
						'year'	=> $date_now->format( 'Y' ),
						'month' => $i
					)
				)
			);
			$investments_monthly_list = get_posts( $query_options );
			$buffer[ 'total_by_month' ][ $i ][ 'success' ] = count( $investments_monthly_list );
			
			$query_options[ 'post_status' ] = 'failed';
			$investments_monthly_list = get_posts( $query_options );
			$buffer[ 'total_by_month' ][ $i ][ 'failed' ] = count( $investments_monthly_list );
		}
		
		
		// Totaux par jour ce mois-ci
		$buffer[ 'total_by_day_this_month' ] = array();
		for ( $i = 1; $i <= 31; $i++ ) {
			$query_options = array(
				'numberposts'	=> -1,
				'post_type'		=> 'edd_payment',
				'post_status'	=> 'publish',
				'date_query'	=> array(
					array(
						'year'	=> $date_now->format( 'Y' ),
						'month'	=> $date_now->format( 'm' ),
						'day'	=> $i
					)
				)
			);
			$investments_monthly_list = get_posts( $query_options );
			$buffer[ 'total_by_day_this_month' ][ $i ][ 'success' ] = count( $investments_monthly_list );
			
			$query_options[ 'post_status' ] = 'failed';
			$investments_monthly_list = get_posts( $query_options );
			$buffer[ 'total_by_day_this_month' ][ $i ][ 'failed' ] = count( $investments_monthly_list );
		}
		
		
		$query_options = array(
			'numberposts' => -1,
			'post_type' => 'edd_payment',
			'post_status' => 'publish',
			'meta_query' => array (
				array (
					'key'		=> '_edd_payment_purchase_key',
					'value'		=> 'wallet',
					'compare'	=> 'LIKE'
				)
			)
		);
		$investments_wallet_list = get_posts( $query_options );

		
		$buffer[ 'total_with_royalties' ] = count( $investments_wallet_list );
		$buffer[ 'amount_with_royalties' ] = 0;
		foreach ( $investments_wallet_list as $investment_post ) {
			$buffer[ 'amount_with_royalties' ] += edd_get_payment_amount( $investment_post->ID );
		}
		
		exit( json_encode( $buffer ) );
		
	}
	
}