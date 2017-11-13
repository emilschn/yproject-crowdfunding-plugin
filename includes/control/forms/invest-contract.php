<?php
class WDG_Form_Invest_Contract extends WDG_Form {
	
	public static $name = 'project-invest-contract';
	
	public static $field_group_hidden = 'invest-hidden';
	public static $field_group_contract_validate = 'invest-contract-validate';
	
	private $campaign_id;
	private $user_id;
	
	public function __construct( $campaign_id = FALSE, $user_id = FALSE ) {
		parent::__construct( WDG_Form_Invest_Contract::$name );
		$this->campaign_id = $campaign_id;
		$this->user_id = $user_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGUser = new WDGUser( $this->user_id );
		
		//**********************************************************************
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			self::$field_group_hidden,
			self::$name
		);
		
		//**********************************************************************
		// Validation du contrat : $field_group_contract_validate
		$this->addField(
			'checkboxes',
			'',
			'',
			self::$field_group_contract_validate,
			FALSE,
			FALSE,
			[
				'contract-validate'	=> __( "J'ai bien compris les termes du contrat", 'yproject' )
			]
		);
		
		$this->addField(
			'text',
			'confirm-subscription',
			__( "Ecrire <strong>Bon pour souscription</strong>", 'yproject' ),
			self::$field_group_contract_validate
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGUser = new WDGUser( $this->user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {

		// Analyse du formulaire
		} else {
			$contract_validate = $this->getInputChecked( 'contract-validate' );
			if ( !$contract_validate ) {
				$this->addPostError(
					'contract-not-validated',
					__( "Vous n'avez pas valid&eacute; les termes du contrat.", 'yproject' ),
					'contract-validate'
				);
			}
			
			// Choix du type d'investisseur
			$confirm_subscription = $this->getInputText( 'confirm-subscription' );
			if ( strtolower( $confirm_subscription ) != "bon pour souscription" ) {
				$this->addPostError(
					'subscription-not-confirmed',
					__( "Vous n'avez pas confirm&eacute; la souscription.", 'yproject' ),
					'confirm-subscription'
				);
			}
		}
		
		$this->initFields();
		
		$current_investment = WDGInvestment::current();
		$current_investment->update_session( FALSE, FALSE );
		
		return !$this->hasErrors();
	}
	
}
