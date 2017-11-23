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
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		$campaign = new ATCF_Campaign( $this->campaign_id );
		
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
			'input_invest_min_value',
			'',
			WDG_Form_Invest_Input::$field_group_hidden,
			ypcf_get_min_value_to_invest()
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
		$invest_amount = $this->getInputText( 'amount' );
		if ( empty( $invest_amount ) ) {
			$invest_amount = $this->input_value;
		}
		$this->addField(
			'text-money',
			'amount',
			__( "Je souhaite investir", 'yproject' ),
			WDG_Form_Invest_Input::$field_group_amount,
			$invest_amount
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		if ( !is_user_logged_in() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ),
				'general'
			);
		}
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		if ( !$campaign->is_investable() ) {
			$this->addPostError(
				'invest-not-possible',
				__( "Il n'est pas possible d'investir.", 'yproject' ),
				'general'
			);
		}
		
		$invest_amount = $this->getInputText( 'amount' );
		$min_value = ypcf_get_min_value_to_invest();
		$max_part_value = ypcf_get_max_part_value();
		if ( empty( $invest_amount ) ) {
			$this->addPostError(
				'amount-empty',
				__( "Vous n'avez pas saisi le montant que vous souhaitez investir.", 'yproject' ),
				'general'
			);
			
		} elseif ( !is_numeric( $invest_amount ) || !ctype_digit( $invest_amount ) ) {
			$this->addPostError(
				'amount-not-numeric',
				__( "Vous n'avez pas saisi un nombre correct.", 'yproject' ),
				'general'
			);
			
		} elseif ( intval( $invest_amount ) != $invest_amount ) {
			$this->addPostError(
				'amount-not-integer',
				__( "Vous n'avez pas saisi un nombre entier.", 'yproject' ),
				'general'
			);
			
		} elseif ( $invest_amount < $min_value ) {
			$this->addPostError(
				'amount-not-enough',
				__( "Vous n'avez pas saisi un montant suffisant.", 'yproject' ),
				'general'
			);
			
		} elseif ( $invest_amount > $max_part_value ) {
			$this->addPostError(
				'amount-too-big',
				__( "Vous ne pouvez pas investir autant.", 'yproject' ),
				'general'
			);
		}
		
		if ( !$this->hasErrors() ) {
			$part_value = ypcf_get_part_value();
			$amount = $invest_amount * $part_value;
			$current_investment = WDGInvestment::current();
			$current_investment->update_session( $amount );
		}
		
		return !$this->hasErrors();
	}
	
}
