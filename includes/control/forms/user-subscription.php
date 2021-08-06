<?php
class WDG_Form_Subscription extends WDG_Form {
	public static $name = 'user-subscription';

    public static $field_group_basics = 'subscription-basics';
	public static $field_group_hidden = 'subscription-hidden';

	private $positive_savings_projects = [];
	private $modalities = [];

    
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

		$this->modalities = ['all_royalties'	=> __( 'form.subscriptions.MODALITY_FIRST_CHOICE', 'yproject' ),'part_royalties' => __( 'form.subscriptions.MODALITY_SECOND_CHOICE', 'yproject' )];
        $this->addField(
            'select',
            'modality',
            __( 'form.subscriptions.MODALITY', 'yproject' ),
            WDG_Form_Subscription::$field_group_basics,
			FALSE,
            FALSE,
            $this->modalities
        );

		$this->addField(
			'text-money',
			'amount',
			__( 'form.subscriptions.AMOUNT', 'yproject' ),
			WDG_Form_Subscription::$field_group_basics,
			( !empty( $amount ) ) ? $amount->amount : '0',
			'Le montant doit être un nombre entier supérieur à 10€.'
		);

		$positive_savings_projects_lists = ATCF_Campaign::get_list_positive_savings( 0 ); 

		$this->positive_savings_projects = [];

		foreach ($positive_savings_projects_lists as $positive_savings_projects_list) {
			$this->positive_savings_projects[$positive_savings_projects_list->ID] = $positive_savings_projects_list->post_title;
		}

		$this->addField(
            'select',
            'project',
            __( 'form.subscriptions.PROJECT', 'yproject' ),
            WDG_Form_Subscription::$field_group_basics,
            FALSE,
			FALSE,
			$this->positive_savings_projects,
        );
    }

    public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		$modality = $this->modalities;
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$WDGUser = new WDGUser( $user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
        } else if ( !$this->is_orga && $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {

		
	    // Analyse du formulaire
		} else {

		$modality = $this->getInputText( 'modality' );

		// Si le montant ne rentré ne dépasse pas 10€ 
		$amount = $this->getInputTextMoney( 'amount' );				
				if ( $modality == "part_royalties" && ( !is_numeric( $amount ) || !WDGRESTAPI_Lib_Validator::is_minimum_amount( $amount ) ) ) {
					$error = array(
						'code'		=> 'amount',
						'text'		=> __( 'form.subscription.error.AMOUNT_MINIMUM', 'yproject' ),
						'element'	=> 'amount'
					);
					array_push( $feedback_errors, $error );
				}

		// Si le montant ne rentré n'est pas un entier
				if ( $modality == "part_royalties" && ( !is_numeric( $amount ) || !WDGRESTAPI_Lib_Validator::is_number_positive_integer( $amount ) ) ) {
					$error = array(
						'code'		=> 'amount',
						'text'		=> __( 'form.subscription.error.AMOUNT_INTEGER', 'yproject' ),
						'element'	=> 'general'
					);
					array_push( $feedback_errors, $error );
				}

		// Si il manque un élément dans le select de modalité
		if ( empty( $modality ) || empty( $this->modalities[$modality] ) ) {
			$error = array(
				'code'		=> 'modality',
				'text'		=> __( 'form.user-details.MODALITY_EMPTY', 'yproject' ),
				'element'	=> 'modality'
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