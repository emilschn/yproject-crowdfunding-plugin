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
		
		
		// Valeur : $field_group_value
		$this->addField(
			'text-money',
			'amount',
			__( "Je souhaite investir", 'yproject' ),
			WDG_Form_Invest_Input::$field_group_amount
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		if ( !is_user_logged_in() ) {
			$error = array(
				'code'		=> 'user-not-logged-in',
				'text'		=> __( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
		}
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		if ( !$campaign->is_investable() ) {
			$error = array(
				'code'		=> 'invest-not-possible',
				'text'		=> __( "Il n'est pas possible d'investir.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
		}
		
		$invest_amount = $this->getInputText( 'amount' );
		$max_part_value = ypcf_get_max_part_value();
		if ( empty( $invest_amount ) ) {
			$error = array(
				'code'		=> 'amount-empty',
				'text'		=> __( "Vous n'avez pas saisi le montant que vous souhaitez investir.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
			
		} elseif ( !is_numeric( $invest_amount ) || !ctype_digit( $invest_amount ) ) {
			$error = array(
				'code'		=> 'amount-not-numeric',
				'text'		=> __( "Vous n'avez pas saisi un nombre correct.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
			
		} elseif ( intval( $invest_amount ) == $invest_amount ) {
			$error = array(
				'code'		=> 'amount-not-integer',
				'text'		=> __( "Vous n'avez pas saisi un nombre entier.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
			
		} elseif ( $invest_amount < 1 ) {
			$error = array(
				'code'		=> 'amount-not-enough',
				'text'		=> __( "Vous n'avez pas saisi un montant suffisant.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
			
		} elseif ( $invest_amount > $max_part_value ) {
			$error = array(
				'code'		=> 'amount-too-big',
				'text'		=> __( "Vous ne pouvez pas investir autant.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
		}
		
		if ( empty( $feedback_errors ) ) {
			$part_value = ypcf_get_part_value();
			$amount = $invest_amount * $part_value;
		    $_SESSION[ 'redirect_current_amount' ] = $amount;
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		echo json_encode( $buffer );
		exit();
	}
	
}
