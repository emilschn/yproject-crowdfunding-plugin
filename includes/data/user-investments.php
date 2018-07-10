<?php
/**
 * Lib de gestion des investissements des utilisateurs
 */
class WDGUserInvestments {
	
	/**
	 * @var WDGUser
	 */
	private $user;
	private $pending_preinvestments;
	
	public function __construct( $WDGUser ) {
		$this->user = $WDGUser;
	}
	
/*******************************************************************************
 * Récupérations des investissements
*******************************************************************************/
	/**
	 * Retourne les ID d'investissements d'un utilisateur, triés par ID de projets ; filtré selon statut de l'utilisateur
	 */
	public function get_investments( $payment_status ) {
		$buffer = array();
		$purchases = edd_get_users_purchases( $this->user->get_wpref(), -1, false, $payment_status );
		
		if ( !empty($purchases) ) {
			foreach ( $purchases as $purchase_post ) { /*setup_postdata( $post );*/
				$downloads = edd_get_payment_meta_downloads( $purchase_post->ID ); 
				$download_id = '';
				if ( !is_array( $downloads[0] ) ){
					$download_id = $downloads[0];
					if ( !isset($buffer[$download_id]) ) {
						$buffer[$download_id] = array();
					}
					array_push( $buffer[$download_id], $purchase_post->ID );
				}
			}
		}
			
		return $buffer;
	}
	
	/**
	 * Retourne les ID d'investissements valides d'un utilisateur, triés par ID de projets
	 */
	public function get_validated_investments() {
		$payment_status = array( 'publish', 'completed' );
		return $this->get_investments( $payment_status );
	}
	
	/**
	 * Retourne les ID d'investissements en attente d'un utilisateur, triés par ID de projets
	 */
	public function get_pending_investments() {
		$payment_status = array( 'pending' );
		return $this->get_investments( $payment_status );
	}
	
	/**
	 * Gestion des pré-investissements
	 */
	public function get_pending_preinvestments( $force_reload = FALSE) {
		$db_cacher = WDG_Cache_Plugin::current();
		$id_user = $this->user->get_wpref();
		$pending_preinv_key = 'user_'.$id_user.'_pending_preinvestments';
		$pending_preinv_duration = 600; //10 minutes
		$pending_preinv_version = 1;
		$investment_id_list = array();

		if ( !isset( $this->pending_preinvestments ) ) {
			$preinv_cache = ( $force_reload ) ? FALSE : $db_cacher->get_cache( $pending_preinv_key, $pending_preinv_version );
			$this->pending_preinvestments = array();
			if ( !$preinv_cache ) {
				$pending_investments = $this->get_pending_investments();
				foreach ( $pending_investments as $campaign_id => $campaign_investments ) {
					$investment_campaign = new ATCF_Campaign( $campaign_id );
					if ( $investment_campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
						foreach ( $campaign_investments as $investment_id ) {
							$wdg_investment = new WDGInvestment( $investment_id );
							if ( $wdg_investment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated ) {
								array_push( $this->pending_preinvestments, $wdg_investment );
								array_push( $investment_id_list, $investment_id );
							}
						}
					}
				}
				$pending_preinv_content = json_encode( $investment_id_list );
				$db_cacher->set_cache( $pending_preinv_key, $pending_preinv_content, $pending_preinv_duration, $pending_preinv_version );
			} else {
				$preinvestment_array = json_decode( $preinv_cache, true );
				foreach ( $preinvestment_array as $investment_id ) {
					$wdg_investment = new WDGInvestment( $investment_id );
					array_push( $this->pending_preinvestments, $wdg_investment );
				}
			}
		}
		return $this->pending_preinvestments;
	}
	
	public function get_first_pending_preinvestment() {
		$buffer = FALSE;
		if ( $this->has_pending_preinvestments() ) {
			$pending_preinvestments = $this->get_pending_preinvestments();
			$buffer = $pending_preinvestments[0];
		}
		return $buffer;
	}
	
	public function has_pending_preinvestments() {
		$pending_preinvestments = $this->get_pending_preinvestments();
		return ( !empty( $pending_preinvestments ) );
	}
	
	
/*******************************************************************************
 * Vérifications de sécurité
*******************************************************************************/
	public function can_invest_amount( $amount ) {
		$buffer = TRUE;
		
		if ( $this->user->is_lemonway_registered() ) {
			if ( $this->get_count_invested_during_interval( '1 day' ) >= LemonwayLib::$limit_kyc2_moneyin_day_nb ) {
				$buffer = 'limit_kyc2_moneyin_day_nb';
			} else if ( $this->get_amount_invested_during_interval( '1 day' ) + $amount > LemonwayLib::$limit_kyc2_moneyin_day_amount ) {
				$buffer = 'limit_kyc2_moneyin_day_amount';
			} else if ( $this->get_amount_invested_during_interval( '31 days' ) + $amount > LemonwayLib::$limit_kyc2_moneyin_month_amount ) {
				$buffer = 'limit_kyc2_moneyin_month_amount';
			}
			
		} else {
			if ( $amount > LemonwayLib::$limit_kyc1_moneyin_operation_amount ) {
				$buffer = 'limit_kyc1_moneyin_operation_amount';
			} else if ( $this->get_count_invested_during_interval( '1 day' ) >= LemonwayLib::$limit_kyc1_moneyin_day_nb ) {
				$buffer = 'limit_kyc1_moneyin_day_nb';
			} else if ( $this->get_amount_invested_during_interval( '365 days' ) + $amount > LemonwayLib::$limit_kyc1_moneyin_year_amount ) {
				$buffer = 'limit_kyc1_moneyin_year_amount';
			}
			
		}
		
		return $buffer;
	}

	/**
	 * retourne le nombre d'investissements d'un utilisateur durant une période
	 * @param string $interval (365 days, 31 days, 1 day)
	 * @return int
	 */
	public function get_count_invested_during_interval( $interval ) {
		$buffer = 0;
		return $buffer;
	}

	/**
	 * retourne la somme investie par un utilisateur durant une période
	 * @param string $interval (365 days, 31 days, 1 day)
	 * @return int
	 */
	public function get_amount_invested_during_interval( $interval ) {
		global $wpdb;

		$query = "SELECT {$wpdb->prefix}mb.meta_value AS payment_total
			FROM {$wpdb->prefix}postmeta {$wpdb->prefix}m
			LEFT JOIN {$wpdb->prefix}postmeta {$wpdb->prefix}ma
				ON {$wpdb->prefix}ma.post_id = {$wpdb->prefix}m.post_id
				AND {$wpdb->prefix}ma.meta_key = '_edd_payment_user_id'
				AND {$wpdb->prefix}ma.meta_value = '%s'
			LEFT JOIN {$wpdb->prefix}postmeta {$wpdb->prefix}mb
				ON {$wpdb->prefix}mb.post_id = {$wpdb->prefix}ma.post_id
				AND {$wpdb->prefix}mb.meta_key = '_edd_payment_total'
			INNER JOIN {$wpdb->prefix}posts {$wpdb->prefix}
				ON {$wpdb->prefix}.id = {$wpdb->prefix}m.post_id
				AND {$wpdb->prefix}.post_status = 'publish'
				AND {$wpdb->prefix}.post_date > '" .date( 'Y-m-d', strtotime( '-' .$interval ) ). "'
			WHERE {$wpdb->prefix}m.meta_key = '_edd_payment_mode'
			AND {$wpdb->prefix}m.meta_value = '%s'";

		$purchases = $wpdb->get_col( $wpdb->prepare( $query, $this->user->get_wpref(), 'live' ) );
		$purchases = array_filter( $purchases );

		$buffer = 0;
		if ( $purchases ) {
			$buffer = round( array_sum( $purchases ), 2 );
		}
		return $buffer;
	}
	
}