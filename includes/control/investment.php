<?php
/**
 * Classe de gestion du processus d'investissement
 */
class WDGInvestment {
	private $id;
	private $token;
	private $token_info;
	private $error;
	private $campaign;
	
	public function __construct( $post_id = FALSE, $invest_token = FALSE ) {
		if ( !empty( $post_id ) ) {
			$this->id = $post_id;
		}
		if ( !empty( $invest_token ) ) {
			$this->token = $invest_token;
			$this->token_info = WDGWPREST_Entity_Investment::get( $this->token );
		}
	}
	
	protected static $_current = null;
	/**
	 * @return WDGInvestment
	 */
	public static function current() {
		if ( is_null( self::$_current ) ) {
			self::$_current = new self();
		}
		return self::$_current;
	}
	
	/**
	 * Retourne la campagne en cours
	 * @return ATCF_Campaign
	 */
	public function get_campaign() {
		$buffer = FALSE;
		if ( isset( $this->campaign ) ) {
			$buffer = $this->campaign;
			
		} elseif ( isset( $this->token_info->project ) ) {
			$this->campaign = new ATCF_Campaign( $this->token_info->project );
			$buffer = $this->campaign;
			
		} elseif ( isset( $_GET['campaign_id'] ) ) {
			$this->campaign = new ATCF_Campaign( $_GET['campaign_id'] );
			$buffer = $this->campaign;
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne le tableau d'erreurs d'investissements
	 * @return array
	 */
	public function get_error() {
		if ( !isset( $this->error ) ) {
			$this->error = array();
		}
		return $this->error;
	}
	
	/**
	 * Analyse les informations reçues au démarrage du processus d'investissement
	 */
	public static function init() {
		$buffer = true;
		
		$invest_start = filter_input( INPUT_GET, 'invest_start' );
		$token_start = filter_input( INPUT_GET, 'token' );
		if ( !empty( $invest_start ) || !empty( $token_start ) ) {
			ypcf_session_start();
			WDGInvestment::unset_session();
			
			if ( !empty( $token_start ) ) {
				$wdg_investment = new WDGInvestment( FALSE, $token_start );
				self::$_current = $wdg_investment;
				$_SESSION[ 'investment_token' ] = $token_start;
				$buffer = $wdg_investment->start_with_token();
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Remet la session à zero
	 */
	public static function unset_session() {
		$session_vars_list = array(
			'redirect_current_amount_part',
			'redirect_current_invest_type',
			'new_orga_just_created',
			'error_invest',
			'redirect_current_selected_reward',
			'investment_token'
		);
		foreach ( $session_vars_list as $session_var_key ) {
			if ( isset( $_SESSION[ $session_var_key ] ) ) {
				unset( $_SESSION[ $session_var_key ] );
			}
		}
	}
	
	/**
	 * Retourne TRUE si un token a été défini
	 * @return boolean
	 */
	public function has_token() {
		return ( !empty( $this->token ) );
	}
	
	/**
	 * Démarre l'investissement avec les données reçues via le token
	 */
	public function start_with_token() {
		$this->error = array();
		$buffer = TRUE;
		
		// Déconnecter l'utilisateur en cours, au cas où
		wp_destroy_current_session();
		wp_clear_auth_cookie();
		
		// Vérifier que le statut du token est valide
		if ( $this->token_info->status != 'init' ) {
			array_push( $this->error, __( "Le statut du jeton d'investissement n'est pas valable.", 'yproject' ) );
			return FALSE;
		}
		
		// Vérifier que la date d'expiration du token n'est pas passée
	    date_default_timezone_set('Europe/Paris');
		$date_now = new DateTime();
		$date_expiration = new DateTime( $this->token_info->token_expiration );
		if ( $date_now > $date_expiration ) {
			array_push( $this->error, __( "Le jeton d'investissement a expir&eacute;.", 'yproject' ) );
			return FALSE;
		}
		
		// Vérifier qu'il est possible d'investir sur le projet concerné
		$campaign = $this->get_campaign();
		if ( !$campaign->is_investable() ) {
			array_push( $this->error, __( "Impossible d'investir sur ce projet", 'yproject' ) );
			return FALSE;
		}
		
		// Vérifier que le montant est valide
		if ( !$this->is_amount_valid() ) {
			array_push( $this->error, __( "Le montant d'investissement n'est pas une valeur accept&eacute;e.", 'yproject' ) );
			return FALSE;
		}
		
		// Vérifier le compte utilisateur
		// Est-ce qu'il existe ?
		$wdg_user_by_email = get_user_by( 'email', $this->token_info->email );
		// Si il n'existe pas, il faut le créer
		if ( !$wdg_user_by_email ) {
			$wp_user_id = wp_insert_user( array(
				'user_login'	=> $this->token_info->email,
				'user_pass'		=> md5( $this->token_info->email ),
				'display_name'	=> sanitize_title( $this->token_info->firstname ) . ' ' . $this->token_info->lastname,
				'user_email'	=> $this->token_info->email
			) );
			
			
			if ( is_wp_error( $wp_user_id ) ) {
				array_push( $this->error, __( "Probl&egrave;me de cr&eacute;ation d'utilisateur", 'yproject' ) );
				return FALSE;
			}
			global $wpdb, $edd_options;
			$wpdb->update( $wpdb->users, array( sanitize_key( 'user_status' ) => 0 ), array( 'ID' => $wp_user_id ) );
			update_user_meta( $wp_user_id, WDGUser::$key_validated_general_terms_version, $edd_options[WDGUser::$edd_general_terms_version] );
			NotificationsEmails::new_user_admin( $wp_user_id ); //Envoi mail à l'admin
			
		} else {
			$wp_user_id = $wdg_user_by_email->ID;
		}
		// On connecte l'utilisateur
		wp_set_auth_cookie( $wp_user_id, false, is_ssl() );
		
		// On enregistre les informations
		$wdg_current_user = WDGUser::current();
		$wdg_current_user->save_data(
			$this->token_info->email,
			$this->token_info->gender,
			$this->token_info->firstname,
			$this->token_info->lastname,
			$this->token_info->birthday_day,
			$this->token_info->birthday_month,
			$this->token_info->birthday_year,
			$this->token_info->birthday_city,
			$this->token_info->nationality,
			$this->token_info->address,
			$this->token_info->postal_code,
			$this->token_info->city,
			$this->token_info->country,
			''
		);
		// On vérifie les informations de l'utilisateur
		if ( !$wdg_current_user->has_filled_invest_infos( $campaign->funding_type() ) ) {
			global $user_can_invest_errors;
			$this->error = $user_can_invest_errors;
			return FALSE;
		}
		
		// Vérifier les infos d'une organisation
		if ( $this->token_info->is_legal_entity ) {
			/*
			 * Gérer plus tard :
			 * - vérifier si l'organisation existe
			 * - sinon la créer
			 * - enregistrer les données
			 * - lier à l'utilisateur
			'legal_entity_form'			=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_id'			=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_rcs'			=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_capital'		=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_address'		=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_postalcode'	=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_city'			=> array( 'type' => 'varchar', 'other' => '' ),
			'legal_entity_nationality'	=> array( 'type' => 'varchar', 'other' => '' ),
			 */
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne TRUE si le montant renseigné est ok
	 * @return boolean
	 */
	public function is_amount_valid() {
		$amount = $this->token_info->amount;
		return isset( $amount ) && is_numeric( $amount ) && ctype_digit( $amount ) 
			&& intval( $amount ) == $amount && $amount >= 1 && $amount <= $this->get_max_value_to_invest();
	}
	
	/**
	 * Retourne la valeur maximum qui peut être investie
	 * @return int
	 */
	public function get_max_value_to_invest() {
		return ( $this->campaign->goal( FALSE ) - $this->campaign->current_amount( FALSE ) );
	}
}