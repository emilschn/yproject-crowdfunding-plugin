<?php
/**
 * Classe de gestion des abonnements
 */
class WDGSUBSCRIPTION {
	public $id;
	public $id_subscriber;
	public $id_activator;
	public $type_subscriber;
	public $id_project;
	public $amount_type;
	public $amount;
	public $payment_method;
	public $modality;
	public $start_date;
	public $status;
	public $end_date;

	private $subscriber;
	private $subscriber_lemonway_amount;
	private $campaign;
	private $campaign_name;
	private $model_contract_url;

	public static $type_active = 'active';
	public static $type_waiting = 'waiting';
	public static $type_cancelled = 'cancelled';
	public static $type_end = 'end';

	public static $amount_type_all_royalties = 'all_royalties';
	public static $amount_type_part_royalties = 'part_royalties';


	public function __construct($subscription_id = FALSE, $data = FALSE) {
		if ( !empty( $subscription_id ) ) {
			// Récupération en priorité depuis l'API
			$subscription_api_item = ( $data !== FALSE ) ? $data : FALSE;
			if ( empty( $subscription_api_item ) ) {
				$subscription_api_item = WDGWPREST_Entity_Subscription::get( $subscription_id );
			}

			// Récupération depuis l'API
			if ( $subscription_api_item != FALSE ) {
				$this->id = $subscription_id;
				$this->id_subscriber = $subscription_api_item->id_subscriber;
				$this->id_activator = $subscription_api_item->id_activator;
				$this->type_subscriber = $subscription_api_item->type_subscriber;
				$this->id_project = $subscription_api_item->id_project;
				$this->amount_type = $subscription_api_item->amount_type;
				$this->amount = $subscription_api_item->amount;
				$this->payment_method = $subscription_api_item->payment_method;
				$this->modality = $subscription_api_item->modality;
				$this->start_date = $subscription_api_item->start_date;
				$this->status = $subscription_api_item->status;
				$this->end_date = $subscription_api_item->end_date;
			}
		}	
	}

	/**
	 * Sauvegarde les données dans l'API
	 */
	public function update() {
		WDGWPREST_Entity_Subscription::update( $this );
	}

	/**
	 * Déclenchement de l'investissement correspondant à l'abonnement
	 */
	public function trigger() {
		// Si l'abonnement est toujours en cours
		if ( $this->status != self::$type_active ) {
			return FALSE;
		}

		// Vérifier que la campagne est encore en cours
		$campaign = $this->get_campaign();
		if ( !$campaign->is_investable() ) {
			return FALSE;
		}

		// Vérifier que la personne a assez sur son wallet
		$wallet_amount = $this->get_entity_subscriber_lemonway_amount();
		$amount_to_invest = $this->amount;
		$min_amount_needed = $this->amount;
		if ( $this->amount_type == WDGSUBSCRIPTION::$amount_type_all_royalties ) {
			$amount_to_invest = floor( $wallet_amount );
			$min_amount_needed = 10;
		}
		if ( $wallet_amount < $min_amount_needed ) {
			return FALSE;
		}

		// Définir les données de l'investissement
		$investment = new WDGInvestment();
		$entity_subscriber = $this->get_entity_subscriber();
		$investment->init_with_subscription_data( $this->id, $entity_subscriber->get_wpref(), $campaign, $amount_to_invest );

		// Déclencher l'investissement
		return $investment->try_payment( WDGInvestment::$meanofpayment_wallet );
	}


/*******************************************************************************
 * RECUPERATION DONNEES
 ******************************************************************************/
	/**
	 * Retourne l'entité qui a souscrit à l'abonnement
	 * @return WDGUserInterface
	 */
	public function get_entity_subscriber() {
		if ( empty( $this->subscriber ) ) {
			if ( $this->type_subscriber == 'user' ) {
				$this->subscriber = WDGUser::get_by_api_id( $this->id_subscriber );
			} else {
				$this->subscriber = WDGOrganization::get_by_api_id( $this->id_subscriber );
			}
		}
		return $this->subscriber;
	}

	/**
	 * Retourne le montant disponible dans le wallet de l'investisseur
	 */
	public function get_entity_subscriber_lemonway_amount() {
		if ( empty( $this->subscriber_lemonway_amount ) ) {
			$subscriber = $this->get_entity_subscriber();
			$this->subscriber_lemonway_amount = $subscriber->get_lemonway_wallet_amount();
		}
		return $this->subscriber_lemonway_amount;
	}

	/**
	 * Renvoie l'objet de campagne associé
	 */
	public function get_campaign() {
		if ( empty( $this->campaign ) ) {
			$this->campaign = new ATCF_Campaign( FALSE, $this->id_project );
		}
		return $this->campaign;
	}

	/**
	 * Renvoie le nom de la campagne / thématique auquel l'abonnement est rattaché
	 */
	public function get_campaign_name() {
		if ( empty( $this->campaign_name ) ) {
			$campaign = $this->get_campaign();
			$this->campaign_name = $campaign->get_name();
		}
		return $this->campaign_name;
	}

	/**
	 * Retourne l'URL du contrat type
	 */
	public function get_model_contract_url() {
		if ( empty( $this->model_contract_url ) ) {
			$campaign = $this->get_campaign();
			$this->model_contract_url = site_url( '/wp-content/plugins/appthemer-crowdfunding/includes/contracts/' . $campaign->backoffice_contract_orga() );
		}
		return $this->model_contract_url;
	}

	/**
	 * Renvoie le montant à investir en fonction du type choisi
	 */
	public function get_amount_str() {
		switch ($this->amount_type) {
			case WDGSUBSCRIPTION::$amount_type_all_royalties:
				return __( 'form.user-contract-subscription.ALL_ROYALTIES', 'yproject' );
				break;

			default:
				return sprintf( __( 'form.user-contract-subscription.PART_ROYALTIES', 'yproject' ), $this->amount );
				break;
		}
	}

	/**
	 * Renvoie la modalité d'investissement sous forme textuelle
	 */
	public function get_modality_str() {
		switch ( $this->modality ) {
			case 'quarter':
				return __( 'account.subscriptions.item.MODALITY_QUARTER', 'yproject' );
				break;
		}
	}

	/**
	 * Renvoie la date du prochain paiement sous forme textuelle
	 */
	public function get_next_payment_date_str() {
		if ( $this->status != 'active' ) {
			return __( 'account.subscriptions.item.INACTIVE_STATUS', 'yproject' );
		}

		$date_time = new DateTime();
		switch ( $date_time->format( 'm' ) ) {
			case 11:
			case 12:
			case 1:
				return __( 'account.subscriptions.item.FEBRUARY_1ST', 'yproject' );
				break;
			case 2:
			case 3:
			case 4:
				return __( 'account.subscriptions.item.MAY_1ST', 'yproject' );
				break;
			case 5:
			case 6:
			case 7:
				return __( 'account.subscriptions.item.AUGUST_1ST', 'yproject' );
				break;
			case 8:
			case 9:
			case 10:
				return __( 'account.subscriptions.item.NOVEMBER_1ST', 'yproject' );
				break;
		}
	}

/*******************************************************************************
 * REQUETES STATIQUES
 ******************************************************************************/
	/**
	 * Ajout d'un nouvel Abonnement
	 */
	public static function insert($id_subscriber, $id_activator, $type_subscriber, $id_project, $amount_type, $amount, $payment_method, $modality, $status) {
		$subscribtion = new WDGSUBSCRIPTION();
		$subscribtion->id_subscriber = $id_subscriber; 		
		$subscribtion->id_activator = $id_activator; 		
		$subscribtion->type_subscriber = $type_subscriber; 	
		$subscribtion->id_project = $id_project; 			
		$subscribtion->amount_type = $amount_type; 			
		$subscribtion->amount = $amount; 					
		$subscribtion->payment_method = $payment_method;	
		$subscribtion->modality = $modality;				
		$start_date = new DateTime();
		$subscribtion->start_date = $start_date->format("Y-m-d H:i:s");			
		$subscribtion->status = $status;
		
		return WDGWPREST_Entity_Subscription::create( $subscribtion );
		
	}
}