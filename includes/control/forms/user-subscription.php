<?php
class WDG_Form_Subscription extends WDG_Form {
	public static $name = 'user-subscription';

    public static $field_group_basics = 'subscription-basics';
	public static $field_group_hidden = 'subscription-hidden';

	private $positive_savings_projects = [];
	private $amount_types = [];

	public function __construct( $user_id = FALSE ) {
		parent::__construct(self::$name);
		$this->user_id = $user_id;
		$this->initFields();
	}

	protected function initFields() {
		parent::initFields();

		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_Subscription::$field_group_hidden,
			$this->user_id
		);

		$this->amount_types = ['all_royalties'	=> __( 'form.subscription.MODALITY_ALL_ROYALTIES', 'yproject' ),'part_royalties' => __( 'form.subscription.MODALITY_FIXED_AMOUNT', 'yproject' )];
		$this->addField(
			'select',
			'amount_type',
			__( 'form.subscription.MODALITY', 'yproject' ),
			WDG_Form_Subscription::$field_group_basics,
			FALSE,
			FALSE,
			$this->amount_types
		);

		$this->addField(
			'text-money',
			'amount',
			__( 'form.subscription.AMOUNT', 'yproject' ),
			WDG_Form_Subscription::$field_group_basics,
			( !empty( $amount ) ) ? $amount->amount : '0',
			__( 'form.subscription.AMOUNT_DESCRIPTION', 'yproject' )
		);

		$positive_savings_projects_lists = ATCF_Campaign::get_list_positive_savings( 0 ); 
		$this->positive_savings_projects = [];
		foreach ($positive_savings_projects_lists as $positive_savings_projects_list) {
			$this->positive_savings_projects[$positive_savings_projects_list->ID] = $positive_savings_projects_list->post_title;
		}

		$this->addField(
			'select',
			'project',
			__( 'form.subscription.PROJECT', 'yproject' ),
			WDG_Form_Subscription::$field_group_basics,
			FALSE,
			FALSE,
			$this->positive_savings_projects
		);
    }

    public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		$amount_type = $this->amount_types;

		$user_id = filter_input( INPUT_POST, 'user_id' );
		$WDGUser = new WDGUser( $user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( !$this->is_orga && $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {

		// Analyse du formulaire
		} else {

			$amount_type = $this->getInputText( 'amount_type' );
			// Si le montant saisi ne dépasse pas 10€
			$amount = $this->getInputTextMoney( 'amount' );
			if ( $amount_type == "part_royalties" && ( !is_numeric( $amount ) || !WDGRESTAPI_Lib_Validator::is_minimum_amount( $amount ) ) ) {
				$error = array(
					'code'		=> 'amount',
					'text'		=> __( 'form.subscription.error.AMOUNT_MINIMUM', 'yproject' ),
					'element'	=> 'amount'
				);
				array_push( $feedback_errors, $error );
			}

			// Si le montant saisi n'est pas un entier
			if ( $amount_type == "part_royalties" && ( !is_numeric( $amount ) || !WDGRESTAPI_Lib_Validator::is_number_positive_integer( $amount ) ) ) {
				$error = array(
					'code'		=> 'amount',
					'text'		=> __( 'form.subscription.error.AMOUNT_INTEGER', 'yproject' ),
					'element'	=> 'general'
				);
				array_push( $feedback_errors, $error );
			}

			// Si il manque un élément dans le select de modalité
			if ( empty( $amount_type ) || empty( $this->amount_types[$amount_type] ) ) {
				$error = array(
					'code'		=> 'amount_type',
					'text'		=> __( 'form.user-details.MODALITY_EMPTY', 'yproject' ),
					'element'	=> 'amount_type'
				);
				array_push( $feedback_errors, $error );
			}

			// Si il manque un élément dans le select des projets
			$project = $this->getInputText( 'project' );
			if ( empty( $project ) || empty( $this->positive_savings_projects[$project] ) ) {
				$error = array(
					'code'		=> 'project',
					'text'		=> __( 'form.user-details.PROJECT_EMPTY', 'yproject' ),
					'element'	=> 'project'
				);
				array_push( $feedback_errors, $error );
			}
		
			// Si il n'y a pas d'erreur alors message succès + enregistrement
			$id_subcription = FALSE;
			if ( empty( $feedback_errors ) ) {
			
				$WDGUser_subscriber = new WDGUser( $this->user_id );
				$id_subscriber = $WDGUser_subscriber -> get_api_id();
				$WDGActivator_subscriber = new WDGUser( get_current_user_id() );
				$id_activator = $WDGActivator_subscriber-> get_api_id();

				if ( WDGOrganization::is_user_organization( $this->user_id ) ) {
					$type_subscriber = "organization";
				}
				else {
					$type_subscriber = "user";
				}

				$WDGCampaing_subscriber = new ATCF_Campaign( $project );
				$id_campaign = $WDGCampaing_subscriber->get_api_id();
				$payment_method = "wallet";
				$modality = "quarter";

				// Quand la méthode de suppression sera créée, on pourra alors gérer la partie "END" dans la BDD
				$status = WDGSUBSCRIPTION::$type_waiting;

				$subscription = WDGSUBSCRIPTION::insert($id_subscriber, $id_activator, $type_subscriber, $id_campaign, $amount_type, $amount, $payment_method, $modality, $status);
				$id_subcription = $subscription->id;
			}
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors,
			'id_subscription' => $id_subcription
		);
		
		$this->initFields(); // Reinit pour avoir les bonnes valeurs
		return $buffer;
		
	}
}