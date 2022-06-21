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
	
	private static $posts_investments;
	private $pending_preinvestments;
	private $pending_wire_investments;
	private $pending_not_validated_investments;
	
	public function __construct( $WDGInvestorEntity ) {
		$this->wp_ref = $WDGInvestorEntity->get_wpref();
		if ( get_class( $WDGInvestorEntity ) == 'WDGOrganization' && WDGOrganization::is_user_organization( $WDGInvestorEntity->get_wpref() )) {
			$this->orga = $WDGInvestorEntity;
		} elseif ( get_class( $WDGInvestorEntity ) == 'WDGUser' ) {
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
	
/*******************************************************************************
 * Récupérations des investissements
*******************************************************************************/
	public function get_posts_investments( $payment_status ) {
		if ( empty( $this->wp_ref ) ) {
			return array();
		}
		if ( is_null( self::$posts_investments ) ) {
			self::$posts_investments = array();
		}
		if ( !isset( self::$posts_investments[ $this->wp_ref ] ) ) {
			self::$posts_investments[ $this->wp_ref ] = array();
		}

		$payment_status_key = print_r( $payment_status, TRUE );
		if ( !isset( self::$posts_investments[ $this->wp_ref ][ $payment_status_key ] ) ) {
			self::$posts_investments[ $this->wp_ref ][ $payment_status_key ] = get_posts( array(
				'numberposts'	=> -1,
				'post_type'		=> WDGInvestment::$payment_post_type,
				'post_status'	=> $payment_status,
				'meta_key'		=> WDGInvestment::$payment_meta_key_user_id,
				'meta_value'	=> $this->wp_ref
			) );
		}

		return self::$posts_investments[ $this->wp_ref ][ $payment_status_key ];
	}

	/**
	 * Retourne les ID d'investissements d'un utilisateur, triés par ID de projets ; filtré selon statut de l'utilisateur
	 */
	public function get_investments( $payment_status ) {
		$buffer = array();
		$purchases = $this->get_posts_investments( $payment_status );
		
		if ( !empty($purchases) ) {
			foreach ( $purchases as $purchase_post ) {
				$WDGInvestment = new WDGInvestment( $purchase_post->ID );
				$campaign = $WDGInvestment->get_campaign();
				$download_id = $campaign->ID;
				if ( !isset( $buffer[ $download_id ] ) ) {
					$buffer[ $download_id ] = array();
				}
				array_push( $buffer[ $download_id ], $purchase_post->ID );
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
	
	public function get_first_pending_investment() {
		$buffer = FALSE;
		if ( $this->has_pending_investments() ) {
			$pending_preinvestments = $this->get_pending_investments();
			$buffer = $pending_preinvestments[0];
		}
		return $buffer;
	}
	
	public function has_pending_investments() {
		$pending_preinvestments = $this->get_pending_investments();
		return ( !empty( $pending_preinvestments ) );
	}
	
	/**
	 * Gestion des investissements démarrés mais pas validés
	 */
	public function get_pending_not_validated_investments() {
		if ( !isset( $this->pending_not_validated_investments ) ) {
			$this->pending_not_validated_investments = array();
			$pending_investments = $this->get_pending_investments();
			foreach ( $pending_investments as $campaign_id => $campaign_investments ) {
				$investment_campaign = new ATCF_Campaign( $campaign_id );
				if ( $investment_campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte || $investment_campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
					foreach ( $campaign_investments as $investment_id ) {
						$wdg_investment = new WDGInvestment( $investment_id );
						if ( $wdg_investment->get_contract_status() == WDGInvestment::$contract_status_not_validated ) {
							array_push( $this->pending_not_validated_investments, $wdg_investment );
						}
					}
				}
			}
		}
		return $this->pending_not_validated_investments;
	}
	
	public function get_first_pending_not_validated_investment() {
		$buffer = FALSE;
		if ( $this->has_pending_not_validated_investments() ) {
			$pending_preinvestments = $this->get_pending_not_validated_investments();
			$buffer = $pending_preinvestments[0];
		}
		return $buffer;
	}
	
	public function has_pending_not_validated_investments() {
		$pending_preinvestments = $this->get_pending_not_validated_investments();
		return ( !empty( $pending_preinvestments ) );
	}

	/**
	 * Gestion des virements à 0€
	 */
	public function get_pending_wire_investments() {
		if ( empty( $this->wp_ref ) ) {
			return array();
		}

		if (!isset($this->pending_wire_investments)) {
			$this->pending_wire_investments = array();
			$query_options = array(
				'numberposts'	=> -1,
				'post_type'		=> WDGInvestment::$payment_post_type,
				'post_status'	=> 'pending',
				'meta_query'	=> array(
					'relation' => 'AND',
					array( 'key' => WDGInvestment::$payment_meta_key_user_id, 'value' => $this->wp_ref ),
					array( 'key' => WDGInvestment::$payment_meta_key_purchase_key, 'value' => 'wire_', 'compare' => 'LIKE' )
				)
			);
			$this->pending_wire_investments = get_posts($query_options);
		}
		return $this->pending_wire_investments;

	}

	public function has_pending_wire_investments() {
		$pending_wire_investments = $this->get_pending_wire_investments();
		return ( !empty( $pending_wire_investments ) );

	}


	/**
	 * Gestion des pré-investissements
	 */
	public function get_pending_preinvestments( $force_reload = FALSE ) {
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
					$contract_has_been_modified = ( $investment_campaign->contract_modifications() != '' );
					if ( $investment_campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte && $contract_has_been_modified ) {
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
				$buffer = sprintf( __( 'Vous ne pouvez pas investir plus de %1$s &euro; tant que votre compte n&apos;est pas authentifi&eacute;. Cependant, nous vous proposons de poursuivre votre investissement sans en modifier le montant : vous pourrez alors proc&eacute;der &agrave; un paiement imm&eacute;diat de %2$s &euro;, puis renseigner les documents permettant votre authentification (pi&egrave;ce d&apos;identit&eacute; et justificatif de domicile). Le reste de l&apos;investissement se fera automatiquement de manière s&eacute;curis&eacute;e lors de la validation de vos documents par notre prestataire de paiement Lemon Way.', 'yproject' ), LemonwayLib::$limit_kyc1_moneyin_operation_amount, LemonwayLib::$limit_kyc1_moneyin_operation_amount );
		
			} else {
				$max_for_year = LemonwayLib::$limit_kyc1_moneyin_year_amount - $this->get_count_invested_during_interval( '365 days' );
				$buffer = sprintf( __( 'Vous ne pouvez pas investir plus de %1$s &euro; sur une ann&eacute;e tant que vous n&apos;&ecirc;tes pas authentifi&eacute;(e). Il vous reste la possibilit&eacute; d&apos;investir %2$s &euro; cette ann&eacute;e. Cependant, nous vous proposons de poursuivre votre investissement. Nous vous inviterons ensuite &agrave; renseigner vos documents (pi&egrave;ce d&apos;identit&eacute; et justificatif de domicile) et le reste de l&apos;investissement se fera automatiquement lors de la validation de vos documents par notre prestataire de paiement Lemon Way.', 'yproject' ), LemonwayLib::$limit_kyc1_moneyin_year_amount, $max_for_year );
				
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
				AND {$wpdb->prefix}ma.meta_key = '" .WDGInvestment::$payment_meta_key_user_id. "'
				AND {$wpdb->prefix}ma.meta_value = '%s'
			LEFT JOIN {$wpdb->prefix}postmeta {$wpdb->prefix}mb
				ON {$wpdb->prefix}mb.post_id = {$wpdb->prefix}ma.post_id
				AND {$wpdb->prefix}mb.meta_key = '" .WDGInvestment::$payment_meta_key_payment_total. "'
			INNER JOIN {$wpdb->prefix}posts {$wpdb->prefix}
				ON {$wpdb->prefix}.id = {$wpdb->prefix}m.post_id
				AND {$wpdb->prefix}.post_status = 'publish'
				AND {$wpdb->prefix}.post_date > '" .date( 'Y-m-d', strtotime( '-' .$interval ) ). "'
			WHERE {$wpdb->prefix}m.meta_key = '" .WDGInvestment::$payment_meta_key_payment_mode. "'
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
				AND {$wpdb->prefix}ma.meta_key = '" .WDGInvestment::$payment_meta_key_user_id. "'
				AND {$wpdb->prefix}ma.meta_value = '%s'
			LEFT JOIN {$wpdb->prefix}postmeta {$wpdb->prefix}mb
				ON {$wpdb->prefix}mb.post_id = {$wpdb->prefix}ma.post_id
				AND {$wpdb->prefix}mb.meta_key = '" .WDGInvestment::$payment_meta_key_payment_total. "'
			INNER JOIN {$wpdb->prefix}posts {$wpdb->prefix}
				ON {$wpdb->prefix}.id = {$wpdb->prefix}m.post_id
				AND {$wpdb->prefix}.post_status = 'publish'
				AND {$wpdb->prefix}.post_date > '" .date( 'Y-m-d', strtotime( '-' .$interval ) ). "'
			WHERE {$wpdb->prefix}m.meta_key = '" .WDGInvestment::$payment_meta_key_payment_mode. "'
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
 * Gestion des transferts de royalties en attente
*******************************************************************************/
	public function try_transfer_waiting_roi_to_wallet() {
		$investor_rois = array();
		if ( !empty( $this->user ) ) {
			$investor_rois = $this->user->get_rois();
		}
		if ( !empty( $this->orga ) ) {
			$investor_rois = $this->orga->get_rois();
		}
		
		if ( !empty( $investor_rois ) ) {
			foreach ( $investor_rois as $roi ) {
				if ( $roi->status == WDGROI::$status_waiting_authentication ) {
					$WDGROI = new WDGROI( $roi->id );
					$WDGROI->retry();
				}
			}
		}
	}
	
}
