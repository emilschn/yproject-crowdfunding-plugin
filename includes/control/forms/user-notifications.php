<?php
class WDG_Form_User_Notifications extends WDG_Form {
	
	public static $name = 'user-notifications';
	
	public static $field_group_hidden = 'user-notifications-hidden';
	public static $field_group_newsletters = 'user-notifications-newsletters';
	public static $field_group_projects = 'user-notifications-projects';
	public static $field_group_transactions = 'user-notifications-transactions';
	
	private static $sendinblue_nl_list = array( 
		5	=> 'form.user-notifications.newsletters.WEDOGOOD',
		6	=> 'form.user-notifications.newsletters.PROJECT_NEWS',
		283 => 'form.user-notifications.newsletters.PROJECTS_ECONOMIC',
		281 => 'form.user-notifications.newsletters.PROJECTS_SOCIAL',
		280 => 'form.user-notifications.newsletters.PROJECTS_ENVIRONMENT',
		279 => 'form.user-notifications.newsletters.PROJECTS_AROUND_ME'
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
			$result = FALSE;
			try {
				$sib_instance = SIBv3Helper::instance();
				$result = $sib_instance->getContactInfo( $user_email );
			
			} catch ( Exception $e ) {
				ypcf_debug_log( "WDGUser::set_subscribe_authentication_notification > erreur sendinblue" );
			}

			if ( !empty( $result ) ) {
				$listIds = $result->getListIds();
				$lists_is_in = array();
				foreach( $listIds as $list_id ) {
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

		$checkboxes_ids_labels = array();
		foreach ( self::$sendinblue_nl_list as $sib_id => $sib_label ) {
			$checkboxes_ids_labels[ 'subscribe_newsletter_' .$sib_id ] = __( $sib_label, 'yproject' );
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
		
		// $field_group_transactions : les notifications liées aux transactions
		// '' ou 0 on est inscrit à toutes les notifications
		// 'none' on est inscrit à aucune notification
		// 'positive' on est inscrits aux notifications quand les royalties sont supérieures à 0€

		$royalties_notifications_labels = [
			0 			=> __( 'common.ALWAYS', 'yproject' ),
			'none' 		=> __( 'common.NONE.F', 'yproject' ),
			'positive' 	=> __( 'form.user-notifications.royalties.ONLY_POSITIVE', 'yproject' )
		];

		$this->addField(
			'select',
			'royalties',
			__( 'common.ROYALTIES', 'yproject' ),
			WDG_Form_User_Notifications::$field_group_transactions,
			$WDGUser->get_royalties_notifications(),
			FALSE,
			$royalties_notifications_labels
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
			$user_email = $WDGUser->get_email();
			$sib_instance = SIBv3Helper::instance();
			foreach ( self::$sendinblue_nl_list as $sib_id => $sib_label ) {
				$subscribe_newsletter = $this->getInputChecked( 'subscribe_newsletter_' .$sib_id );
				if ( empty( $subscribe_newsletter ) ) {
					$sib_instance->removeContactFromList( $user_email, $sib_id );
				} else {
					$sib_instance->addContactToList( $user_email, $sib_id );
				}
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

			// suivi des notifications de transactions
			$WDGUser->set_royalties_notifications($this->getInputText('royalties'));
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		$this->initFields(); // Reinit pour avoir les bonnes valeurs
		
		return $buffer;
	}
	
}
