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
		
		$this->addField(
            'select',
            'project',
            __( 'account.subscriptions.PROJECT', 'yproject' ),
            WDG_Form_Subscription::$field_group_basics,
            $projects = ['projet 1' => 'Solaire Rural', 'projet 2' => 'Zéro Pesticide'],
            
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

        // $amount = $this->getInputTextMoney( 'amount', FALSE );
		// if ( !is_numeric( $amount ) ) {
		// 	$this->addPostError(
		// 		'amount',
		// 		__( "Erreur de saisie du montant (ne peut pas &ecirc;tre &eacute;gal &agrave; zero).", 'yproject' ),
		// 		'general'
		// 	);
		// }
            
        }

        $buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		$this->initFields(); // Reinit pour avoir les bonnes valeurs
		
		return $buffer;
    }
}