<?php
class WDG_Form_User_Notifications extends WDG_Form {
	
	public static $name = 'user-notifications';
	
	public static $field_group_hidden = 'user-notifications-hidden';
	public static $field_group_newsletters = 'user-notifications-newsletters';
	public static $field_group_projects = 'user-notifications-projects';
	
	private static $sendinblue_nl_list = array( 
		5	=> "Newsletter WE DO GOOD",
		6	=> "Actualit&eacute;s des projets",
		283 => "Projets &agrave; impact &eacute;conomique",
		281 => "Projets &agrave; impact social",
		280 => "Projets &agrave; impact environnemental",
		279 => "Projets autour de chez moi"
	);
	
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
			WDG_Form_User_Notifications::$field_group_hidden,
			WDG_Form_User_Notifications::$name
		);
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_User_Notifications::$field_group_hidden,
			$this->user_id
		);
		
		// $field_group_notifications : Les champs informations
		$is_subscribed_to_newsletter = array();
		$user_email = $WDGUser->get_email();
		if ( !empty( $user_email ) ) {
			$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 5000 );
			$return = $mailin->get_user( array(
				"email"		=> $user_email
			) );
			if ( isset( $return[ 'code' ] ) && $return[ 'code' ] != 'failure' ) {
				if ( isset( $return[ 'data' ] ) && isset( $return[ 'data' ][ 'listid' ] ) ) {
					$lists_is_in = array();
					foreach( $return[ 'data' ][ 'listid' ] as $list_id ) {
						$lists_is_in[ $list_id ] = TRUE;
					}
					
					foreach ( self::$sendinblue_nl_list as $sib_id => $sib_label ) {
						if ( !empty( $lists_is_in[ $sib_id ] ) ) {
							array_push( $is_subscribed_to_newsletter, TRUE );
						} else {
							array_push( $is_subscribed_to_newsletter, FALSE );
						}
					}
				}
			}
		}

		$checkboxes_ids_labels = array();
		foreach ( self::$sendinblue_nl_list as $sib_id => $sib_label ) {
			$checkboxes_ids_labels[ 'subscribe_newsletter_' .$sib_id ] = $sib_label;
		}
		
		$this->addField(
			'checkboxes',
			'',
			'',
			WDG_Form_User_Notifications::$field_group_newsletters,
			$is_subscribed_to_newsletter,
			FALSE,
			$checkboxes_ids_labels
		);
		
		// $field_group_projects : les projets suivis
		$campaigns_followed = $WDGUser->get_campaigns_followed();
		$campaign_list_values = array();
		$campaign_list_labels = array();
		foreach ( $campaigns_followed as $campaign_id => $campaign_name ) {
			array_push( $campaign_list_values, TRUE );
			$campaign_list_labels[ 'campaign_followed_' . $campaign_id ] = $campaign_name;
		}

		$this->addField(
			'checkboxes',
			'',
			'',
			WDG_Form_User_Notifications::$field_group_projects,
			$campaign_list_values,
			FALSE,
			$campaign_list_labels
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
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {

		// Analyse du formulaire
		} else {
			
			// (Des)Inscription des NL WDG
			$listid_link = array();
			$listid_unlink = array();
			foreach ( self::$sendinblue_nl_list as $sib_id => $sib_label ) {
				$subscribe_newsletter = $this->getInputChecked( 'subscribe_newsletter_' .$sib_id );
				if ( empty( $subscribe_newsletter ) ) {
					array_push( $listid_unlink, $sib_id );
				} else {
					array_push( $listid_link, $sib_id );
				}
			}
			
			try {
				$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 5000 );
				if ( !empty( $listid_unlink ) ) {
					$return = $mailin->create_update_user( array(
						"email"			=> $WDGUser->get_email(),
						"listid_unlink"	=> $listid_unlink
					) );
				}
				if ( !empty( $listid_link ) ) {
					$return = $mailin->create_update_user( array(
						"email"			=> $WDGUser->get_email(),
						"listid"	=> $listid_link
					) );
				}
			} catch ( Exception $e ) {
				ypcf_debug_log( "WDG_Form_User_Notifications::postForm > erreur de connexion à SendInBlue -- " . print_r( $e, TRUE ) );
			}
			
			// Suivi des projets
			global $wpdb;
			$table_jcrois = $wpdb->prefix . "jycrois";
			$campaigns_followed = $WDGUser->get_campaigns_followed();
			foreach ( $campaigns_followed as $campaign_id => $campaign_name ) {
				$form_campaign_followed = $this->getInputChecked( 'campaign_followed_' . $campaign_id );
				if ( empty( $form_campaign_followed ) ) {
					$wpdb->delete( 
						$table_jcrois,
						array(
							'user_id'      => $WDGUser->get_wpref(),
							'campaign_id'  => $campaign_id
						)
					);
				}
			}
			
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		$this->initFields(); // Reinit pour avoir les bonnes valeurs
		
		return $buffer;
	}
	
}
