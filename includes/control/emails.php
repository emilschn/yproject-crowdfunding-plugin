<?php
class WDGEmails {
	
	public static function auto_notifications( $campaign_id, $mail_type, $input_testimony_in, $input_image_url, $input_image_description, $input_send_option ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$project_name = $campaign->get_name();
		$project_url = get_permalink( $campaign->ID );
		$project_api_id = $campaign->get_api_id();
		$project_percent = $campaign->percent_minimum_completed( FALSE );
		$project_nb_remaining_days = $campaign->days_remaining();
		$project_date_hour_end = $campaign->end_date( 'd/m/Y h:i' );
		// Gestion des sauts de ligne
		$input_testimony = nl2br( $input_testimony_in );

		// Si on teste, on biaise les données et on arrête de suite
		if ( strpos( strtolower( $input_send_option ), 'test' ) !== FALSE ) {
			$recipient_email = 'communication@wedogood.co';
			$recipient_name = 'Anna';
			$intention_amount = 100;
			switch ( $mail_type ) {
				case 'preinvestment':
					NotificationsAPI::confirm_vote_invest_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_vote_invest_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					break;
				case 'prelaunch':
					NotificationsAPI::confirm_prelaunch_invest_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_prelaunch_invest_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_prelaunch_invest_follow( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					break;
				case 'investment-30':
					NotificationsAPI::confirm_investment_invest30_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $project_percent, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_investment_invest30_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $project_percent, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_investment_invest30_follow( $recipient_email, $recipient_name, $project_name, $project_url, $project_percent, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					break;
				case 'investment-100':
					NotificationsAPI::confirm_investment_invest100_invested( $recipient_email, $recipient_name, $project_name, $project_url, $project_nb_remaining_days, $project_date_hour_end, $project_api_id );
					NotificationsAPI::confirm_investment_invest100_investment_pending( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_investment_invest100_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_nb_remaining_days, $project_date_hour_end, $project_api_id );
					NotificationsAPI::confirm_investment_invest100_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_nb_remaining_days, $project_date_hour_end, $project_api_id );
					NotificationsAPI::confirm_investment_invest100_follow( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_nb_remaining_days, $project_date_hour_end, $project_api_id );
					break;
				case 'investment-2days':
					NotificationsAPI::confirm_investment_invest2days_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $nb_remaining_days, $date_hour_end, $project_api_id );
					NotificationsAPI::confirm_investment_invest2days_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $nb_remaining_days, $date_hour_end, $project_api_id );
					break;
			}
			return;
		}

		$user_list_by_id = array();

		// Récupération des followers
		$followers_list_by_id = array();
		$list_user_followers = $campaign->get_followers();
		foreach ( $list_user_followers as $db_item_follower_user_id ) {
			$followers_list_by_id[ $db_item_follower_user_id ] = 1;
		}

		// Récupération des investisseurs
		$investors_list_by_id = array();
		$list_user_investors = $campaign->payments_data();
		foreach ( $list_user_investors as $item_investment ) {
			$investment_status = $item_investment[ 'status' ];
			$investors_list_by_id[ $item_investment[ 'user' ] ] = $investment_status;
		}

		// On parcourt la liste des évaluateurs
		$list_user_voters = $campaign->get_voters();
		foreach ( $list_user_voters as $db_item_vote ) {
					// On ne prend que des notes d'au moins 3
			if (	$db_item_vote->rate_project >= 3
					// On ne prend que ceux qui suivent toujours le projet
					&& isset( $followers_list_by_id[ $db_item_vote->user_id ] )
					// On ne prend que ceux qui n'ont pas investi
					&& !isset( $investors_list_by_id[ $db_item_vote->user_id ] ) ) {

				if ( !isset( $user_list_by_id[ $db_item_vote->user_id ] ) ) {
					$user_list_by_id[ $db_item_vote->user_id ] = array();
				}
				$user_list_by_id[ $db_item_vote->user_id ][ 'vote_amount' ] = $db_item_vote->invest_sum;
			}
		}

		// Si le mail est celui de pré-lancement, ou d'investissement à 30% et 100%
		if ( $mail_type == 'prelaunch' || $mail_type == 'investment-30' || $mail_type == 'investment-100' ) {
			// On reprend les followers qui n'ont pas évalué et qui n'ont pas fait d'action d'investissement
			foreach ( $list_user_followers as $db_item_follower_user_id ) {
				if (	!isset( $user_list_by_id[ $db_item_follower_user_id ] )
						&& !isset( $investors_list_by_id[ $db_item_follower_user_id ] ) ) {

					$user_list_by_id[ $db_item_follower_user_id ] = array();
					$user_list_by_id[ $db_item_follower_user_id ][ 'vote_amount' ] = 'follow';
				}
			}
		}
		// Si le mail est celui de validation de la levée de fonds (investissement 100 %)
		if ( $mail_type == 'investment-100' ) {
			// On reprend les investisseurs qui ne sont pas encore dans la liste
			foreach ( $investors_list_by_id as $db_item_investor_user_id => $db_item_investment_status ) {
				if ( !isset( $user_list_by_id[ $db_item_investor_user_id ] ) ) {

					$user_list_by_id[ $db_item_investor_user_id ] = array();
					$user_list_by_id[ $db_item_investor_user_id ][ 'vote_amount' ] = $db_item_investment_status;
				}
			}
		}

		foreach ( $user_list_by_id as $user_id => $vote_data ) {
			if ( empty( $user_id ) ) {
				continue;
			}
			
			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGOrganization = new WDGOrganization( $user_id );
				$recipient_email = $WDGOrganization->get_email();
				$recipient_name = $WDGOrganization->get_name();
			} else {
				$WDGUser = new WDGUser( $user_id );
				$recipient_email = $WDGUser->get_email();
				$recipient_name = $WDGUser->get_firstname();
			}

			$intention_amount = $vote_data[ 'vote_amount' ];

			// Pour les restants, on envoie un template différent selon si ils ont mis une intention ou non.
			switch ( $mail_type ) {
				case 'preinvestment':
					if ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_vote_invest_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );

					} else {
						NotificationsAPI::confirm_vote_invest_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					}
					break;
					
				case 'prelaunch':
					if ( $intention_amount == 'follow' ) {
						NotificationsAPI::confirm_prelaunch_invest_follow( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );

					} elseif ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_prelaunch_invest_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );

					} else {
						NotificationsAPI::confirm_prelaunch_invest_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					}
					break;
					
				case 'investment-30':
					if ( $intention_amount == 'follow' ) {
						NotificationsAPI::confirm_investment_invest30_follow( $recipient_email, $recipient_name, $project_name, $project_url, $project_percent, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					
					} elseif ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_investment_invest30_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $project_percent, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					
					} else {
						NotificationsAPI::confirm_investment_invest30_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $project_percent, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					
					}
					break;
					
				case 'investment-100':
					if ( $intention_amount == 'follow' ) {
						NotificationsAPI::confirm_investment_invest100_follow( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_nb_remaining_days, $project_date_hour_end, $project_api_id );
					
					} elseif ( $intention_amount == 'publish' ) {
						NotificationsAPI::confirm_investment_invest100_invested( $recipient_email, $recipient_name, $project_name, $project_url, $project_nb_remaining_days, $project_date_hour_end, $project_api_id );

					} elseif ( $intention_amount == 'pending' ) {
						NotificationsAPI::confirm_investment_invest100_investment_pending( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					
					} elseif ( is_numeric( $intention_amount ) && $intention_amount > 0 ) {
						NotificationsAPI::confirm_investment_invest100_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_nb_remaining_days, $project_date_hour_end, $project_api_id );
					
					} elseif ( $intention_amount == 0 ) {
						NotificationsAPI::confirm_investment_invest100_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_nb_remaining_days, $project_date_hour_end, $project_api_id );
					
					}
					break;
					
				case 'investment-2days':
					if ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_investment_invest2days_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $nb_remaining_days, $date_hour_end, $project_api_id );
					} else {
						NotificationsAPI::confirm_investment_invest2days_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $nb_remaining_days, $date_hour_end, $project_api_id );
					}
					break;
			}
		}
	}
	
}