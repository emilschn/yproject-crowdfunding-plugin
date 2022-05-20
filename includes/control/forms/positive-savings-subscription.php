<?php
class WDG_Form_Subscribe_Positive_Savings extends WDG_Form {
	
	public static $name = 'positive-savings-subscription';
	
	public static $field_group_hidden = 'invest-hidden';
	
	private $campaign_id;
	private $user_id;
	
	public function __construct( $campaign_id, $user_id ) {
		parent::__construct( self::$name );
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
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		
		//**********************************************************************
	}
	
	public function postForm() {
		parent::postForm();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGUser = new WDGUser( $this->user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( 'form.invest-input.error.NOT_LOGGED_IN', 'yproject' ),
				'general'
			);
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( 'form.invest-input.error.NOT_LOGGED_IN', 'yproject' ),
				'general'
			);
		}
		
		if ( !$this->hasErrors() ) {
			if ( $this->getInputText( 'subscribe' ) == 'yes' ) {
				$project_id = $campaign->get_api_id();
				$user_id = $WDGUser->get_api_id();
				$id_activator = $user_id;
				$type_subscriber = 'user';
				$amount_type = WDGSUBSCRIPTION::$amount_type_all_royalties;
				$amount = 0;
				$payment_method = 'wallet';
				$modality = 'quarter';
				$status = WDGSUBSCRIPTION::$type_active;
				WDGSUBSCRIPTION::insert($user_id, $id_activator, $type_subscriber, $project_id, $amount_type, $amount, $payment_method, $modality, $status);
			}
		}
		
		return !$this->hasErrors();
	}
	
}
