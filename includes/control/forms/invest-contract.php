<?php
class WDG_Form_Invest_Contract extends WDG_Form {
	
	public static $name = 'project-invest-contract';
	
	public static $field_group_hidden = 'invest-hidden';
	
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
		
		//**********************************************************************
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			self::$field_group_hidden,
			self::$name
		);
	}
	
	public function postForm() {
		parent::postForm();
		
		$WDGUser = new WDGUser( $this->user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {

		}
		
		$this->initFields();
		
		$current_investment = WDGInvestment::current();
		$current_investment->update_session( FALSE, FALSE );
		
		return !$this->hasErrors();
	}
	
}
