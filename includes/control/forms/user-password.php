<?php
class WDG_Form_User_Password extends WDG_Form {
	
	public static $name = 'user-password';
	
	public static $field_group_hidden = 'user-details-hidden';
	public static $field_group_password = 'user-details-password';
	
	private $user_id;
	
	public function __construct( $user_id = FALSE ) {
		parent::__construct( WDG_Form_User_Password::$name );
		$this->user_id = $user_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		$WDGUser = new WDGUser( $this->user_id );
		
		// $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			WDG_Form_User_Password::$field_group_hidden,
			WDG_Form_User_Password::$name
		);
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_User_Password::$field_group_hidden,
			$this->user_id
		);
		
		// $field_group_password : Les champs nécessaires au changement de mot de passe
		$this->addField(
			'password',
			'password_current',
			__( 'form.user-password.PASSWORD_CURRENT', 'yproject' ),
			WDG_Form_User_Password::$field_group_password
		);
		
		$this->addField(
			'password',
			'password_new',
			__( 'form.user-password.PASSWORD_NEW', 'yproject' ),
			WDG_Form_User_Password::$field_group_password
		);
		
		$this->addField(
			'password',
			'password_new_confirm',
			__( 'form.user-password.PASSWORD_NEW_CONFIRM', 'yproject' ),
			WDG_Form_User_Password::$field_group_password
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
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {

		// Analyse du formulaire
		} else {
			
			// Informations de base
			$password_current = filter_input( INPUT_POST, 'password_current' );
			if ( wp_check_password( $password_current, $WDGUser->wp_user->data->user_pass, $WDGUser->get_wpref() ) ) {
				
				$password_new = filter_input( INPUT_POST, 'password_new' );
				$password_new_confirm = filter_input( INPUT_POST, 'password_new_confirm' );
				if ( empty( $password_new ) ) {
					$error = array(
						'code'		=> 'password_new',
						'text'		=> __( 'form.user-password.error.PASSWORD_EMPTY', 'yproject' ),
						'element'	=> 'password_new'
					);
					array_push( $feedback_errors, $error );
				}
				if ( $password_new != $password_new_confirm ) {
					$error = array(
						'code'		=> 'password_new_confirm',
						'text'		=> __( 'form.user-password.error.PASSWORD_DOESNT_MATCH', 'yproject' ),
						'element'	=> 'password_new_confirm'
					);
					array_push( $feedback_errors, $error );
				}
				
				if ( empty( $feedback_errors ) ) {
					wp_update_user( array (
						'ID'		=> $WDGUser->get_wpref(),
						'user_pass' => $password_new
					) );
					
					array_push( $feedback_success, __( 'form.user-password.PASSWORD_MODIFIED', 'yproject' ) );
					NotificationsAPI::user_password_change( $WDGUser->get_email(),  $WDGUser->get_firstname() );
				}
				
			} else {
				$error = array(
					'code'		=> 'password_current',
					'text'		=> __( 'form.user-password.error.PASSWORD_CURRENT_WRONG', 'yproject' ),
					'element'	=> 'password_current'
				);
				array_push( $feedback_errors, $error );
			}
			
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		return $buffer;
	}
	
}
