<?php
class WDG_Form_Invest_Input extends WDG_Form {
	
	public static $name = 'project-invest-input';
	
	public static $field_group_hidden = 'invest-hidden';
	public static $field_group_amount = 'invest-amount';
	
	private $campaign_id;
	private $input_value;
	
	public function __construct( $campaign_id = FALSE, $input_value = FALSE ) {
		parent::__construct( WDG_Form_Invest_Input::$name );
		$this->campaign_id = $campaign_id;
		$this->input_value = $input_value;
		if ( empty( $this->input_value ) ) {
			$this->invest_amount = $this->getInputText( 'amount' );
		}
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGCurrent_User = WDGUser::current();
		$WDGUserInvestments = new WDGUserInvestments( $WDGCurrent_User );
		
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			WDG_Form_Invest_Input::$name
		);
		
		$this->addField(
			'hidden',
			'input_invest_user_max_value',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			$WDGUserInvestments->get_maximum_investable_amount()
		);
		
		$this->addField(
			'hidden',
			'input_invest_user_max_reason',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			$WDGUserInvestments->get_maximum_investable_reason_str()
		);
		
		$this->addField(
			'hidden',
			'input_invest_min_value',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			$WDGUserInvestments->get_minimum_investable_amount()
		);
		
		$this->addField(
			'hidden',
			'input_invest_max_value',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			ypcf_get_max_value_to_invest()
		);
		
		$this->addField(
			'hidden',
			'input_invest_part_value',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			ypcf_get_part_value()
		);
		
		$this->addField(
			'hidden',
			'input_invest_max_part_value',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			ypcf_get_max_part_value()
		);
		
		$this->addField(
			'hidden',
			'input_invest_amount_total',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			ypcf_get_current_amount()
		);
		
		$this->addField(
			'hidden',
			'roi_percent_project',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			$campaign->roi_percent_estimated()
		);
		
		$this->addField(
			'hidden',
			'roi_goal_project',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			$campaign->goal( false )
		);
		
		// Valeur : $field_group_value
		$this->addField(
			'text-money',
			'amount',
			__( 'form.invest-input.I_WISH_TO_INVEST', 'yproject' ),
			WDG_Form_Invest_Input::$field_group_amount,
			$this->input_value
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		if ( !is_user_logged_in() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( 'form.invest-input.error.NOT_LOGGED_IN', 'yproject' ),
				'general'
			);
		}
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		if ( !$campaign->is_investable() ) {
			$this->addPostError(
				'invest-not-possible',
				__( 'form.invest-input.error.INVESTMENT_NOT_POSSIBLE', 'yproject' ),
				'general'
			);
		}
		
		$invest_amount = $this->getInputTextMoney( 'amount' );
		$min_value = ypcf_get_min_value_to_invest();
		$max_part_value = ypcf_get_max_part_value();
		if ( empty( $invest_amount ) ) {
			$this->addPostError(
				'amount-empty',
				__( 'form.invest-input.error.AMOUNT_EMPTY', 'yproject' ),
				'general'
			);
			
		} elseif ( !is_numeric( $invest_amount ) || !ctype_digit( $invest_amount ) ) {
			$this->addPostError(
				'amount-not-numeric',
				__( 'form.invest-input.error.AMOUNT_NOT_NUMERIC', 'yproject' ),
				'general'
			);
			
		} elseif ( intval( $invest_amount ) != $invest_amount ) {
			$this->addPostError(
				'amount-not-integer',
				__( 'form.invest-input.error.AMOUNT_NOT_INTEGER', 'yproject' ),
				'general'
			);
			
		} elseif ( $invest_amount < $min_value ) {
			$this->addPostError(
				'amount-not-enough',
				__( 'form.invest-input.error.AMOUNT_NOT_ENOUGH', 'yproject' ),
				'general'
			);
			
		} elseif ( $invest_amount > $max_part_value ) {
			$this->addPostError(
				'amount-too-big',
				__( 'form.invest-input.error.AMOUNT_TOO_MUCH', 'yproject' ),
				'general'
			);
			
		}
		
		if ( !$this->hasErrors() ) {
			$part_value = ypcf_get_part_value();
			$amount = $invest_amount * $part_value;
			$current_investment = WDGInvestment::current();
			$current_investment->update_session( $amount );
			$WDGCurrent_User = WDGUser::current();
			$WDGCurrent_User->init_risk_validation_time();
			$WDGCurrent_User->update_api();
		}
		
		return !$this->hasErrors();
	}
	
}
