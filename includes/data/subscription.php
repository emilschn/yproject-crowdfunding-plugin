<?php
/**
 * Classe de gestion des abonnements
 */
class WDGSUBSCRIPTION {

    public $id;
	public $id_subscriber;
	public $id_activator;
    public $type_subscriber;
	public $id_campaign;
	public $amount_type;
	public $amount;
	public $payment_method;
	public $modality;
    public $start_date;
	public $status;
    public $end_date;


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
				$this->id_campaign = $subscription_api_item->id_campaign;
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
	
/*******************************************************************************
 * REQUETES STATIQUES
 ******************************************************************************/

	/**
	 * Ajout d'un nouveau Abonnement
	 */
	public static function insert($id_subscriber, $id_activator, $type_subscriber, $id_campaign, $amount_type, $amount, $payment_method, $modality, $status) {
        $subscribtion = new WDGSUBSCRIPTION();
        $subscribtion->id_subscriber = $id_subscriber; 		//user_id du compte
        $subscribtion->id_activator = $id_activator; 		//user_id du compte qui est connecté
        $subscribtion->type_subscriber = $type_subscriber; 	//user ou organization
        $subscribtion->id_campaign = $id_campaign; 			// ID du projet
        $subscribtion->amount_type = $amount_type; 			// modalité
        $subscribtion->amount = $amount; 					// montant
        $subscribtion->payment_method = $payment_method;	// Wallet du compte utilisateur
        $subscribtion->modality = $modality;				// Trimestrielle
		$start_date = new DateTime();
        $subscribtion->start_date = $start_date->format("Y-m-d H:i:s");			// Date de la souscription
        $subscribtion->status = $status;
		// var_dump ($type_subscriber); 
		// exit('');					// Active ou End 
        WDGWPREST_Entity_Subscription::create( $subscribtion );
	}
	
}