<?php
class WDG_Form_Subscription extends WDG_Form {
	public static $name = 'user-subscription';

    public static $field_group_basics = 'subscription-basics';
	public static $field_group_hidden = 'subscription-hidden';

    
    public function __construct( $user_id = FALSE ) {
		parent::__construct(self::$name);
        $this->user_id = $user_id;
		$this->initFields();
	}

    protected function initFields() {
		parent::initFields();

        $WDGUser = new WDGUser( $this->user_id );

		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_Subscription::$field_group_hidden,
			$this->user_id
		);

        $this->addField(
            'select',
            'modality',
            __( 'account.subscriptions.MODALITY', 'yproject' ),
            WDG_Form_Subscription::$field_group_basics,
            $WDGUser->get_gender(),
            FALSE,
            [
                'all_royalties'	=> __( 'account.subscriptions.MODALITY_FIRST_CHOICE', 'yproject' ),
                'part_royalties' => __( 'account.subscriptions.MODALITY_SECOND_CHOICE', 'yproject' )
            ]
        );

		$this->addField(
			'text-money',
			'amount',
			__( 'account.subscriptions.AMOUNT', 'yproject' ),
			WDG_Form_Subscription::$field_group_basics,
			( !empty( $adjustment ) ) ? $adjustment->amount : ''
		);
		
		// $this->positive_savings_projects_list = ATCF_Campaign::get_list_positive_savings( 0 );
		// Récupération de la liste, parcours de la liste, je récupère l'identifiant que j'assossie avec son nom
		$projects = ['projet 1' => 'Solaire Rural', 'projet 2' => 'Zéro Pesticide'];


		$this->addField(
            'select',
            'project',
            __( 'account.subscriptions.PROJECT', 'yproject' ),
            WDG_Form_Subscription::$field_group_basics,
            $projects,
            FALSE,
            [
                $projects['projet 1'],
                $projects['projet 2'],
            ]
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
		// Si le montant ne rentré ne dépasse pas 10€ alors message d'erreur
		$amount = $this->getInputTextMoney( 'amount' );				
				if ( !is_numeric( $amount ) || !WDGRESTAPI_Lib_Validator::is_minimum_amount( $amount ) ) {
					$error = array(
						'code'		=> 'amount',
						'text'		=> __( 'form.subscription.error.AMOUNT_MINIMUM', 'yproject' ),
						'element'	=> 'general'
					);
					array_push( $feedback_errors, $error );
				}
		
				

		// Si il n'y a pas d'erreur alors message succès
		if ( empty( $feedback_errors ) ) {
			array_push( $feedback_success, __( 'form.user-details.SAVE_SUCCESS', 'yproject' ) ); 
        }

        $buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		$this->initFields(); // Reinit pour avoir les bonnes valeurs
		var_dump($buffer);
		return $buffer;
    }
}
}