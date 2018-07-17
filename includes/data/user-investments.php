<?php
/**
 * Lib de gestion des investissements des utilisateurs
 */
class WDGUserInvestments {
	
	/**
	 * @var WDGUser
	 */
	private $user;
	/**
	 * @var WDGOrganization
	 */
	private $orga;
	private $wp_ref;
	
	private $pending_preinvestments;
	
	public function __construct( $WDGInvestorEntity ) {
		$this->wp_ref = $WDGInvestorEntity->get_wpref();
		if ( WDGOrganization::is_user_organization( $WDGInvestorEntity->get_wpref() ) ) {
			$this->orga = $WDGInvestorEntity;
		} else {
			$this->user = $WDGInvestorEntity;
		}
	}
	
	private function is_lemonway_registered() {
		if ( !empty( $this->user ) ) {
			return $this->user->is_lemonway_registered();
		}
		if ( !empty( $this->orga ) ) {
			return $this->orga->is_registered_lemonway_wallet();
		}
	}
	
	private function get_lemonway_id() {
		if ( !empty( $this->user ) ) {
			return $this->user->get_lemonway_id();
		}
		if ( !empty( $this->orga ) ) {
			return $this->orga->get_lemonway_id();
		}
	}
	
	private function get_lemonway_cardid() {
		if ( !empty( $this->user ) ) {
			return $this->user->get_lemonway_cardid();
		}
		if ( !empty( $this->orga ) ) {
			return $this->orga->get_lemonway_cardid();
		}
	}
	
/*******************************************************************************
 * Récupérations des investissements
*******************************************************************************/
	/**
	 * Retourne les ID d'investissements d'un utilisateur, triés par ID de projets ; filtré selon statut de l'utilisateur
	 */
	public function get_investments( $payment_status ) {
		$buffer = array();
		$purchases = edd_get_users_purchases( $this->wp_ref, -1, false, $payment_status );
		
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
		$id_user = $this->wp_ref;
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
	
	public function get_minimum_investable_amount() {
		return ypcf_get_min_value_to_invest();
	}
	
	public function get_maximum_investable_amount() {
		$buffer = min(
			LemonwayLib::$limit_kyc2_moneyin_day_amount - $this->get_count_invested_during_interval( '1 day' ),
			LemonwayLib::$limit_kyc2_moneyin_month_amount - $this->get_amount_invested_during_interval( '31 days' )
		);
		return $buffer;
	}
	
	public function get_maximum_investable_reason_str() {
		$buffer = '';
		
		if ( LemonwayLib::$limit_kyc2_moneyin_day_amount - $this->get_count_invested_during_interval( '1 day' ) < LemonwayLib::$limit_kyc2_moneyin_month_amount - $this->get_amount_invested_during_interval( '31 days' ) ) {
			$max_for_day = LemonwayLib::$limit_kyc2_moneyin_day_amount - $this->get_count_invested_during_interval( '1 day' );
			$buffer = sprintf( __( 'Vous ne pouvez pas investir plus de %1$s &euro; sur une journ&eacute;e. Vous ne pouvez donc plus investir plus que %2$s &euro; aujourd&apos;hui.', 'yproject' ), LemonwayLib::$limit_kyc2_moneyin_day_amount, $max_for_day );
		} else {
			$max_for_month = LemonwayLib::$limit_kyc2_moneyin_month_amount - $this->get_amount_invested_during_interval( '31 days' );
			$buffer = sprintf( __( 'Vous ne pouvez pas investir plus de %1$s &euro; sur un mois. Vous ne pouvez donc plus investir plus que %2$s &euro; ce mois-ci.', 'yproject' ), LemonwayLib::$limit_kyc2_moneyin_month_amount, $max_for_month );
		}
		
		return $buffer;
	}
	
	public function get_maximum_investable_amount_without_alert() {
		$buffer = LemonwayLib::$limit_kyc2_moneyin_month_amount;
		if ( !$this->is_lemonway_registered() ) {
			$buffer = min(
				LemonwayLib::$limit_kyc1_moneyin_operation_amount,
				LemonwayLib::$limit_kyc1_moneyin_year_amount - $this->get_count_invested_during_interval( '365 days' )
			);
		}
		return $buffer;
	}
	
	public function get_maximum_investable_amount_without_alert_reason_str() {
		$buffer = '';
		if ( !$this->is_lemonway_registered() ) {
			if ( LemonwayLib::$limit_kyc1_moneyin_operation_amount <= LemonwayLib::$limit_kyc1_moneyin_year_amount - $this->get_count_invested_during_interval( '365 days' ) ) {
				$buffer = sprintf( __( 'Vous ne pouvez pas investir plus de %1$s &euro; tant que vous n&apos;&ecirc;tes pas identifi&eacute;(e). Cependant, nous vous proposons de poursuivre votre investissement. Nous vous inviterons ensuite &agrave; renseigner vos documents (pi&egrave;ce d&apos;identit&eacute; et justificatif de domicile) et le reste de l&apos;investissement se fera automatiquement lors de la validation de vos documents par notre prestataire de paiement Lemon Way.', 'yproject' ), LemonwayLib::$limit_kyc1_moneyin_operation_amount );
		
			} else {
				$max_for_year = LemonwayLib::$limit_kyc1_moneyin_year_amount - $this->get_count_invested_during_interval( '365 days' );
				$buffer = sprintf( __( 'Vous ne pouvez pas investir plus de %1$s &euro; sur une ann&eacute;e tant que vous n&apos;&ecirc;tes pas identifi&eacute;(e). Il vous reste la possibilit&eacute; d&apos;investir %2$s &euro; cette ann&eacute;e. Cependant, nous vous proposons de poursuivre votre investissement. Nous vous inviterons ensuite &agrave; renseigner vos documents (pi&egrave;ce d&apos;identit&eacute; et justificatif de domicile) et le reste de l&apos;investissement se fera automatiquement lors de la validation de vos documents par notre prestataire de paiement Lemon Way.', 'yproject' ), LemonwayLib::$limit_kyc1_moneyin_year_amount, $max_for_year );
				
			}
		}
		return $buffer;
	}
	
	/**
	 * Détermine si l'utilisateur a encore le droit d'investir une fois 
	 * @return boolean or string
	 */
	public function can_invest_nb() {
		$buffer = TRUE;
		
		if ( $this->is_lemonway_registered() ) {
			if ( $this->get_count_invested_during_interval( '1 day' ) >= LemonwayLib::$limit_kyc2_moneyin_day_nb ) {
				$buffer = 'limit_kyc2_moneyin_day_nb';
			}
			
		} else {
			if ( $this->get_count_invested_during_interval( '1 day' ) >= LemonwayLib::$limit_kyc1_moneyin_day_nb ) {
				$buffer = 'limit_kyc1_moneyin_day_nb';
			}
			
		}
		
		return $buffer;
	}
	
	/*
	 * Cette fonction reprend l'ensemble des règles mais n'est pas utile en l'état
	 */
	public function can_invest_amount( $amount ) {
		$buffer = TRUE;
		
		if ( $this->is_lemonway_registered() ) {
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
		global $wpdb;

		$query = "SELECT count( {$wpdb->prefix}mb.meta_value ) AS nb
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

		$purchases = $wpdb->get_col( $wpdb->prepare( $query, $this->wp_ref, 'live' ) );

		$buffer = 0;
		if ( $purchases ) {
			$buffer = $purchases[ 0 ];
		}
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

		$purchases = $wpdb->get_col( $wpdb->prepare( $query, $this->wp_ref, 'live' ) );
		$purchases = array_filter( $purchases );

		$buffer = 0;
		if ( $purchases ) {
			$buffer = round( array_sum( $purchases ), 2 );
		}
		return $buffer;
	}
	
/*******************************************************************************
 * Gestion des paiements par carte en attente
*******************************************************************************/
	public function try_pending_card_investments() {
		// Parcourir tous les investissements en attente pour cet utilisateur, déclencher le paiement et valider l'investissement
		$pending_investments = $this->get_pending_investments();
		foreach ( $pending_investments as $campaign_id => $campaign_investments ) {
			$investment_campaign = new ATCF_Campaign( $campaign_id );
			if ( $investment_campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote || $investment_campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
				foreach ( $campaign_investments as $investment_id ) {
					$wdg_investment = new WDGInvestment( $investment_id );
					$investment_key = $wdg_investment->get_payment_key();
					if ( strpos( $investment_key, 'card_TEMP_' ) !== FALSE ) {
						$lemonway_id = $this->get_lemonway_id();
						$lemonway_cardid = $this->get_lemonway_cardid();
						$result = LemonwayLib::ask_payment_registered_card( $lemonway_id, $lemonway_cardid, $wdg_investment->get_saved_amount() );
						if ( $result->TRANS->HPAY->STATUS == '3' ) {
							$purchase_key = $result->TRANS->HPAY->ID;
							$purchase_key .= $wdg_investment->try_payment_wallet( $wdg_investment->get_saved_amount(), FALSE );
							update_post_meta( $investment_id, '_edd_payment_purchase_key', $purchase_key );
							ypcf_get_updated_payment_status( $investment_id, false, false, $wdg_investment );
							
						} else {
							NotificationsEmails::new_purchase_pending_admin_error( ( $this->user ? $this->user : $this->orga ), $result, $investment_id, $wdg_investment->get_saved_amount() );
						}
					}
				}
			}
		}
	}
	
}