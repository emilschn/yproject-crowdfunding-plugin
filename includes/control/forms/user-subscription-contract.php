<?php
class WDG_Form_Subscription_Contract extends WDG_Form {
	public static $name = 'user-subscription-contract';

	public static $field_group_hidden = 'contract-subscription-hidden';

	private $subscription;
	private $id_subscription;

	public function __construct( $user_id = FALSE ) {
		parent::__construct(self::$name);
		
		$this->user_id = $user_id;
		$this->id_subscription = filter_input(INPUT_GET,'id_subscription');
		$this->subscription = new WDGSUBSCRIPTION($this->id_subscription);
		$this->initFields();
	}

	protected function initFields() {
		parent::initFields();

		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_Subscription_Contract::$field_group_hidden,
			$this->user_id
		);

		$this->addField(
			'hidden',
			'id_subscription',
			'',
			WDG_Form_Subscription_Contract::$field_group_hidden,
			$this->id_subscription
		);
	}

	public function postForm() {
		parent::postForm();

		$feedback_success = array();
		$feedback_errors = array();

		$user_id = filter_input( INPUT_POST, 'user_id' );
		$WDGUser = new WDGUser( $user_id );
		$WDGUser_current = WDGUser::current();

		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( !$this->is_orga && $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {

		// Analyse du formulaire
		} else {

			// Si l'abonnement n'est pas présent en BDD
			if ( empty( $this->subscription->id ) ) {
				$error = array(
					'code'		=> 'subscription',
					'text'		=> __( 'form.user-contract-subscription.SUBSCRIPTION_ERROR', 'yproject' ),
					'element'	=> 'subscription'
				);
				array_push( $feedback_errors, $error );
			}

			// Si l'utilisateur actuel ne correspond pas à l'utilisateur qui a validé le formulaire
			if ( $this->subscription->id_subscriber != $WDGUser_current-> get_api_id() && !$WDGUser_current->is_admin() ) {
				$error = array(
					'code'		=> 'subscription',
					'text'		=> __( 'form.user-contract-subscription.SUBSCRIPTION_ERROR', 'yproject' ),
					'element'	=> 'subscription'
				);
				array_push( $feedback_errors, $error );
			}

			$button_action = filter_input( INPUT_POST, 'contract-action' );
			switch ( $button_action ) {
				// L'utilisateur clique sur "Précédent"
				case 'previous-contract-subscription':
					// On passe le statut de l'abonnement en "annulé" et retour sur son compte
					$this->subscription->status = WDGSUBSCRIPTION::$type_cancelled;
					$this->subscription->update();
					break;

				// L'utilisateur clique sur "Valider"
				case 'validate-contract-subscription':
					// On passe le statut de l'abonnement en "actif"
					array_push( $feedback_success, __( 'form.user-details.SAVE_SUCCESS', 'yproject' ) );
					$this->subscription->status = WDGSUBSCRIPTION::$type_active;
					$this->subscription->update();
					// Envoi d'un mail de confirmation
					NotificationsAPI::subscription_validation( $this->subscription );
					break;

				// Controle de sécurité : aucune action n'a été déclenchée
				default:
					$error = array(
						'code'		=> 'subscription',
						'text'		=> __( 'form.user-contract-subscription.SUBSCRIPTION_ERROR', 'yproject' ),
						'element'	=> 'subscription'
					);
					array_push( $feedback_errors, $error );
					break;
			}
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors,
		);

		return $buffer;
	}
}