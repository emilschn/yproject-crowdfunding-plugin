<?php
class WDG_Form_User_Notifications extends WDG_Form {
	
	public static $name = 'user-notifications';
	
	public static $field_group_hidden = 'user-notifications-hidden';
	public static $field_group_newsletters = 'user-notifications-newsletters';
	public static $field_group_projects = 'user-notifications-projects';
	
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
		$is_subscribed_to_newsletter_wdg = FALSE;
		$is_subscribed_to_newsletter_projects = FALSE;
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
					if ( !empty( $lists_is_in[ 5 ] ) ) {
						$is_subscribed_to_newsletter_wdg = TRUE;
					}
					if ( !empty( $lists_is_in[ 6 ] ) ) {
						$is_subscribed_to_newsletter_projects = TRUE;
					}
				}
			}
		}

		$this->addField(
			'checkboxes',
			'',
			'',
			WDG_Form_User_Notifications::$field_group_newsletters,
			[
				$is_subscribed_to_newsletter_wdg,
				$is_subscribed_to_newsletter_projects
			],
			FALSE,
			[
				'subscribe_newsletter_wdg' => __( "Newsletter WE DO GOOD" ),
				'subscribe_newsletter_projects' => __( "Actualit&eacute;s des projets" )
			]
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
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {

		// Analyse du formulaire
		} else {
			
			// (Des)Inscription des NL WDG
			$listid_link = array();
			$listid_unlink = array();
			$subscribe_newsletter_wdg = $this->getInputChecked( 'subscribe_newsletter_wdg' );
			if ( empty( $subscribe_newsletter_wdg ) ) {
				array_push( $listid_unlink, 5 );
			} else {
				array_push( $listid_link, 5 );
			}
			$subscribe_newsletter_projects = $this->getInputChecked( 'subscribe_newsletter_projects' );
			if ( empty( $subscribe_newsletter_projects ) ) {
				array_push( $listid_unlink, 6 );
			} else {
				array_push( $listid_link, 6 );
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
