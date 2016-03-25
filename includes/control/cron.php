<?php
/**
 * Classe de gestion des appels Cron
 */
class WDGCronActions {
	/**
	 * Initialise la liste des actions ajax
	 */
	public static function init_actions() {
		$init_date_daily = new DateTime("2016-04-01 10:15:00");
		add_action( 'wdg_daily_actions_action', array( 'WDGCronActions', 'daily_actions' ) );
		wp_schedule_event( $init_date_daily->getTimestamp(), 'daily', 'wdg_daily_actions_action' );
		
		$init_date_twicedaily = new DateTime("2016-04-01 07:15:00");
		add_action( 'wdg_twicedaily_actions_action', array( 'WDGCronActions', 'twicedaily_actions' ) );
		wp_schedule_event( $init_date_twicedaily->getTimestamp(), 'daily', 'wdg_twicedaily_actions_action' );
	}
	
	public static function daily_actions() {
		
	}
	
	public static function twicedaily_actions() {
		WDGCronActions::check_kycs();
	}
	
	public static function check_kycs() {
		//Parcours de tous les utilisateurs
		$users = get_users();
		foreach ($users as $user) {
			if ( YPOrganisation::is_user_organisation( $user->ID ) ) {
				$organisation = new YPOrganisation( $user->ID );
				$init_kyc_status = $organisation->get_lemonway_status( FALSE );
				if ( $init_kyc_status == YPOrganisation::$lemonway_status_waiting ) {
					$new_kyc_status = $organisation->get_lemonway_status();
					switch ( $new_kyc_status ) {
						case YPOrganisation::$lemonway_status_rejected:
							NotificationsEmails::send_notification_kyc_rejected_admin($user);
							break;
						case YPOrganisation::$lemonway_status_registered:
							NotificationsEmails::send_notification_kyc_accepted_admin($user);
							break;
					}
				}
			}
		}
	}
}