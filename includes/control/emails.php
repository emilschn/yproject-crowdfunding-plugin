<?php
class WDGEmails {
	private static $batch_of_notifications = 20; // Nombre arbitraire de notifications envoyées d'un coup

	public static function auto_notifications($campaign_id, $mail_type, $input_testimony_in, $input_image_url, $input_image_description, $input_send_option, $user_already_sent_to = array()) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$project_api_id = $campaign->get_api_id();
		// Gestion des sauts de ligne
		$input_testimony = nl2br( $input_testimony_in );

		// Si on teste, on biaise les données et on arrête de suite
		if ( strpos( strtolower( $input_send_option ), 'test' ) !== FALSE ) {
			$WDGUserOrOrganization = new WDGUser( 68 );
			$intention_amount = 100;
			$list_language = array( 'fr', 'en' );
			foreach ( $list_language as $language ) {
				$WDGUserOrOrganization->set_language( $language );
				switch ( $mail_type ) {
					case 'preinvestment':
						NotificationsAPI::confirm_vote_invest_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_vote_invest_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						break;
					case 'prelaunch':
						NotificationsAPI::confirm_prelaunch_invest_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_prelaunch_invest_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_prelaunch_invest_follow( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						break;
					case 'investment-30':
						NotificationsAPI::confirm_investment_invest30_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_investment_invest30_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_investment_invest30_follow( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						break;
					case 'investment-100':
						NotificationsAPI::confirm_investment_invest100_invested( $WDGUserOrOrganization, $campaign );
						NotificationsAPI::confirm_investment_invest100_investment_pending( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_investment_invest100_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_investment_invest100_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_investment_invest100_follow( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						break;
					case 'investment-2days':
						NotificationsAPI::confirm_investment_invest2days_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
						NotificationsAPI::confirm_investment_invest2days_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						break;
					case 'investment-3days-post-cloture':
						NotificationsAPI::confirm_investment_3days_post_cloture( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
						break;
				}
			}

			return true;
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

		$count_to_batch_limit = 0;
		foreach ( $user_list_by_id as $user_id => $vote_data ) {
			if ( empty( $user_id ) || in_array( $user_id, $user_already_sent_to ) ) {
				continue;
			}

			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGOrganization = new WDGOrganization( $user_id );
				$WDGUserOrOrganization = $WDGOrganization;
			} else {
				$WDGUser = new WDGUser( $user_id );
				$WDGUserOrOrganization = $WDGUser;
			}

			$intention_amount = $vote_data[ 'vote_amount' ];

			// Pour les restants, on envoie un template différent selon si ils ont mis une intention ou non.
			switch ( $mail_type ) {
				case 'preinvestment':
					if ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_vote_invest_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} else {
						NotificationsAPI::confirm_vote_invest_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					}
					break;

				case 'prelaunch':
					if ( $intention_amount == 'follow' ) {
						NotificationsAPI::confirm_prelaunch_invest_follow( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} elseif ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_prelaunch_invest_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} else {
						NotificationsAPI::confirm_prelaunch_invest_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					}
					break;

				case 'investment-30':
					if ( $intention_amount == 'follow' ) {
						NotificationsAPI::confirm_investment_invest30_follow( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} elseif ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_investment_invest30_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} else {
						NotificationsAPI::confirm_investment_invest30_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					}
					break;

				case 'investment-100':
					if ( $intention_amount == 'follow' ) {
						NotificationsAPI::confirm_investment_invest100_follow( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} elseif ( $intention_amount == 'publish' ) {
						NotificationsAPI::confirm_investment_invest100_invested( $WDGUserOrOrganization, $campaign );
					} elseif ( $intention_amount == 'pending' ) {
						NotificationsAPI::confirm_investment_invest100_investment_pending( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} elseif ( is_numeric( $intention_amount ) && $intention_amount > 0 ) {
						NotificationsAPI::confirm_investment_invest100_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} elseif ( $intention_amount == 0 ) {
						NotificationsAPI::confirm_investment_invest100_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					}
					break;

				case 'investment-2days':
					if ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_investment_invest2days_intention( $WDGUserOrOrganization, $intention_amount, $campaign, $input_testimony, $input_image_url, $input_image_description );
					} else {
						NotificationsAPI::confirm_investment_invest2days_no_intention( $WDGUserOrOrganization, $campaign, $input_testimony, $input_image_url, $input_image_description );
					}
					break;
			}

			array_push( $user_already_sent_to, $user_id );
			$count_to_batch_limit++;
			if ( $count_to_batch_limit >= self::$batch_of_notifications ) {
				break;
			}
		}

		if ( count( $user_already_sent_to ) < count( $user_list_by_id ) ) {
			WDGQueue::add_campaign_notifications( $campaign_id, $mail_type, $input_testimony_in, $input_image_url, $input_image_description, $user_already_sent_to );
		}

		return true;
	}

	public static function end_vote_notifications($campaign_id, $mail_type, $input_send_option, $user_already_sent_to = array()) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$project_name = $campaign->get_name();
		$project_api_id = $campaign->get_api_id();

		if ( strpos( strtolower( $input_send_option ), 'test' ) !== FALSE ) {
			$WDGUserOrOrganization = new WDGUser( 60 );
			$list_language = array( 'fr', 'en' );
			foreach ( $list_language as $language ) {
				$WDGUserOrOrganization->set_language( $language );
				switch ( $mail_type ) {
					case 'vote-end-pending-campaign':
						NotificationsAPI::vote_end_pending_campaign( $WDGUserOrOrganization, $campaign );
						break;
					case 'vote-end-canceled-campaign':
						NotificationsAPI::vote_end_canceled_campaign( $WDGUserOrOrganization, $campaign );
						NotificationsAPI::vote_end_canceled_campaign_refund( $WDGUserOrOrganization, $campaign );
						break;
				}
			}

			return true;
		}

		$user_list_by_id = array();

		// Récupération des followers
		$followers_list_by_id = array();
		$list_user_followers = $campaign->get_followers();
		foreach ( $list_user_followers as $db_item_follower_user_id ) {
			$followers_list_by_id[ $db_item_follower_user_id ] = 1;
		}

		// Récupération des investisseurs
		$list_user_investors = $campaign->payments_data();
		foreach ( $list_user_investors as $item_investment ) {
			if ( !isset( $user_list_by_id[ $item_investment[ 'user' ] ] ) ) {
				$user_list_by_id[ $item_investment[ 'user' ] ] = array();
			}
			$user_list_by_id[ $item_investment[ 'user' ] ][ 'invest_amount' ] = $item_investment[ 'amount' ];
		}

		// On parcourt la liste des évaluateurs
		$list_user_voters = $campaign->get_voters();
		foreach ( $list_user_voters as $db_item_vote ) {
			// On ne prend que des notes d'au moins 3
			if (	$db_item_vote->rate_project >= 3
					// On ne prend que ceux qui suivent toujours le projet
					&& isset( $followers_list_by_id[ $db_item_vote->user_id ] ) ) {
				if ( !isset( $user_list_by_id[ $db_item_vote->user_id ] ) ) {
					$user_list_by_id[ $db_item_vote->user_id ] = array();
				}
				$user_list_by_id[ $db_item_vote->user_id ][ 'vote_amount' ] = $db_item_vote->invest_sum;
			}
		}

		$count_to_batch_limit = 0;
		foreach ( $user_list_by_id as $user_id => $vote_data ) {
			if ( empty( $user_id ) || in_array( $user_id, $user_already_sent_to ) ) {
				continue;
			}

			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGOrganization = new WDGOrganization( $user_id );
				$WDGUserOrOrganization = $WDGOrganization;
			} else {
				$WDGUser = new WDGUser( $user_id );
				$WDGUserOrOrganization = $WDGUser;
			}

			switch ( $mail_type ) {
				case 'vote-end-pending-campaign':
					NotificationsAPI::vote_end_pending_campaign( $WDGUserOrOrganization, $campaign );
					break;
				case 'vote-end-canceled-campaign':
					if ( isset( $vote_data[ 'invest_amount' ] ) && $vote_data[ 'invest_amount' ] > 0 ) {
						NotificationsAPI::vote_end_canceled_campaign_refund( $WDGUserOrOrganization, $campaign );
					} else {
						NotificationsAPI::vote_end_canceled_campaign( $WDGUserOrOrganization, $campaign );
					}
					break;
			}

			array_push( $user_already_sent_to, $user_id );
			$count_to_batch_limit++;
			if ( $count_to_batch_limit >= self::$batch_of_notifications ) {
				break;
			}
		}

		if ( count( $user_already_sent_to ) < count( $user_list_by_id ) ) {
			WDGQueue::add_campaign_end_vote_notifications( $campaign_id, $mail_type, $user_already_sent_to );
		}

		return true;
	}

	public static function end_notifications($campaign_id, $mail_type, $input_send_option) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$project_api_id = $campaign->get_api_id();

		if ( strpos( strtolower( $input_send_option ), 'test' ) !== FALSE ) {
			$WDGUserOrOrganization = new WDGUser( 60 );
			$list_language = array( 'fr', 'en' );
			foreach ( $list_language as $language ) {
				$WDGUserOrOrganization->set_language( $language );
				switch ( $mail_type ) {
					case 'end-success-public':
						NotificationsAPI::campaign_end_success_public( $WDGUserOrOrganization, $campaign );
						break;
					case 'end-success-private':
						NotificationsAPI::campaign_end_success_private( $WDGUserOrOrganization, $campaign );
						break;
					case 'end-pending-goal':
						NotificationsAPI::campaign_end_pending_goal( $WDGUserOrOrganization, $campaign );
						break;
					case 'end-failure':
						NotificationsAPI::campaign_end_failure( $WDGUserOrOrganization, $campaign );
						break;
				}
			}

			return true;
		}

		$list_user_investors = $campaign->payments_data();
		foreach ( $list_user_investors as $item_investment ) {
			if ( $item_investment[ 'status' ] == 'publish' ) {
				$user_id = $item_investment[ 'user' ];
				if ( empty( $user_id ) ) {
					continue;
				}

				if ( WDGOrganization::is_user_organization( $user_id ) ) {
					$WDGOrganization = new WDGOrganization( $user_id );
					$WDGUserOrOrganization = $WDGOrganization;
				} else {
					$WDGUser = new WDGUser( $user_id );
					$WDGUserOrOrganization = $WDGUser;
				}

				if ( !empty( $WDGUserOrOrganization ) ) {
					switch ( $mail_type ) {
						case 'end-success-public':
							NotificationsAPI::campaign_end_success_public( $WDGUserOrOrganization, $campaign );
							break;
						case 'end-success-private':
							NotificationsAPI::campaign_end_success_private( $WDGUserOrOrganization, $campaign );
							break;
						case 'end-pending-goal':
							NotificationsAPI::campaign_end_pending_goal( $WDGUserOrOrganization, $campaign );
							break;
						case 'end-failure':
							NotificationsAPI::campaign_end_failure( $WDGUserOrOrganization, $campaign );
							break;
					}
				}
			}
		}

		return true;
	}
}