<?php
class WDG_Form_User_Change_Investment_Owner extends WDG_Form {
	public static $name = 'user-change-investment-owner';

	public function __construct() {
		parent::__construct( self::$name );
	}

	public function postForm() {
		parent::postForm();

		$feedback_success = array();
		$feedback_errors = array();

		$action = filter_input( INPUT_POST, 'action' );
		if ( empty( $action ) || $action != 'change_investment_owner' ) {
			return FALSE;
		}

		$investid = filter_input( INPUT_POST, 'investid' );
		if ( empty( $investid ) ) {
			return FALSE;
		}

		$email = filter_input( INPUT_POST, 'e-mail' );
		if ( empty( $email ) ) {
			$error = array(
				'code'		=> 'new_account_email_empty',
				'text'		=> 'Problème de transmission de mail',
				'element'	=> 'e-mail'
			);
			array_push( $feedback_errors, $error );
		} else {
			$user_by_email = get_user_by( 'email', $email );
			if ( empty( $user_by_email ) ) {
				$error = array(
					'code'		=> 'new_account_email_not_existing',
					'text'		=> 'Aucun compte ne correspond à cette adresse',
					'element'	=> 'e-mail'
				);
				array_push( $feedback_errors, $error );
			}
		}

		if ( empty( $feedback_errors ) ) {
			$feedback_success = 'Compte trouvé et investissement transféré !';
		}

		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);

		return $buffer;
	}
}
