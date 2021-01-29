<?php
class WDG_Form_User_Unlink_Facebook extends WDG_Form {
	
	public static $name = 'user-unlink-facebook';
	
	public static $field_group_hidden = 'user-unlink-facebook-hidden';
	public static $field_group_password = 'user-unlink-facebook-password';
	
	private $user_id;
	
	public function __construct( $user_id = FALSE ) {
		parent::__construct( self::$name );
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
			self::$field_group_hidden,
			self::$name
		);
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			self::$field_group_hidden,
			$this->user_id
		);
		
		// $field_group_password : Les champs nécessaires au changement de mot de passe
		$this->addField(
			'password',
			'password_new',
			__( 'form.user-password.PASSWORD_NEW', 'yproject' ),
			self::$field_group_password
		);
		
		$this->addField(
			'password',
			'password_new_confirm',
			__( 'form.user-password.PASSWORD_NEW_CONFIRM', 'yproject' ),
			self::$field_group_password
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
		
		// Un admin peut faire la manip à la place de l'utilisateur, mais c'est tout
		} else if ( ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) && !$WDGUser_current->is_admin() ) {
			
		// Analyse du formulaire
		} else {
				
			$password_new = $this->getInputText( 'password_new' );
			$password_new_confirm = $this->getInputText( 'password_new_confirm' );
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
				
				delete_user_meta( $WDGUser->get_wpref(), 'social_connect_facebook_id' );
				
				WDGWPREST_Entity_User::update( $WDGUser );
			
				array_push( $feedback_success, __( 'form.user-password.PASSWORD_MODIFIED', 'yproject' ) );
			}
			
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		return $buffer;
	}
	
}
