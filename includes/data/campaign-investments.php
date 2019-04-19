<?php
/**
 * Lib de gestion des investissements
 */
class WDGCampaignInvestments {
	/**
	 * Retourne la liste des investissements d'un projet
	 * @param int $camp_id
	 * @return array
	 */
	public static function get_list($camp_id, $include_pending = FALSE) {
		$campaign = new ATCF_Campaign( $camp_id );
		$payments_data = $campaign->payments_data();

		$buffer = array(
			'campaign' => $campaign,
			'payments_data' => $payments_data,
			'count_validate_investments' => 0,
			'count_not_validate_investments' => 0,
			'count_validate_investors' => 0,
			'count_age' => 0,
			'count_average_age' => 0,
			'count_female' => 0,
			'count_invest' => 0,
			'amounts_array' => array(),
			'average_age' => 0,
			'percent_female' => 0,
			'percent_male' => 0,
			'average_invest' => 0,
			'median_invest' => 0,
			'min_invest' => 0,
			'max_invest' => 0,
			'amount_check' => $campaign->current_amount_check_meta(),
			'investors_list' => array(),
			'investors_string' => ''
		);
		foreach ( $payments_data as $item ) {
			//Prend en compte ou pas dans les stats les paiements non validés
			if ($item['status'] == 'publish' || ($include_pending && $item['status'] == 'pending')) {

				if (!isset($buffer['investors_list'][$item['user']])) {
					$buffer['count_validate_investors']++;
					$buffer['investors_list'][$item['user']] = $item['user'];
					$WDGUser = FALSE;
					if ( empty( $item['item'] ) ) {
						$WDGUser = new WDGUser( $item['user'] );
					}
					$gender = empty( $item['item'] ) ? $WDGUser->get_gender() : $item['item']->gender;
					if ($gender != "") {
						$age = empty( $item['item'] ) ? $WDGUser->get_age() : $item['item']->age;
						if ($age < 200) {
							$buffer['count_age'] += $age;
							$buffer['count_average_age'] ++;
						}
					}
					if ($gender == "female") $buffer['count_female']++;
					if ($buffer['investors_string'] != '') $buffer['investors_string'] .= ', ';
					$buffer['investors_string'] .= empty( $item['item'] ) ? $WDGUser->get_display_name() : $item['item']->firstname. ' ' .substr( $item['item']->lastname, 0, 1 ). '.';;
				}
				$buffer['count_invest'] += $item['amount'];
				$buffer['amounts_array'][] = $item['amount'];
			}
			if ($item['status'] == 'publish') {
			    $buffer['count_validate_investments']++;
			} else if ($item['status'] == 'pending') {
			    $buffer['count_not_validate_investments']++;
			}
		}

		sort($buffer['amounts_array']);

		if ($buffer['count_validate_investments'] > 0) {
			$buffer['average_age'] = round($buffer['count_age'] / $buffer['count_average_age'], 1);
			$buffer['percent_female'] = round($buffer['count_female'] / $buffer['count_validate_investors'] * 100);
			$buffer['percent_male'] = 100 - $buffer['percent_female'];
			$buffer['average_invest'] = round($buffer['count_invest'] / $buffer['count_validate_investments'], 2);
			$buffer['median_invest'] = $buffer['amounts_array'][0];
			if ($buffer['count_validate_investments'] > 2) {
				$index = round(($buffer['count_validate_investments'] + 1) / 2) - 1;
				$buffer['median_invest'] = $buffer['amounts_array'][$index];
			}
			$buffer['min_invest']= $buffer['amounts_array'][0];
			$buffer['max_invest']= end($buffer['amounts_array']);
		}
		$buffer['investors_string'] = htmlentities( $buffer['investors_string'] );

		return $buffer;
	}
	
	/**
	 * 
	 * @param type $id_payment
	 */
	public static function cancel($payment_id) {
		$postdata = array(
		    'ID'	    => $payment_id,
		    'post_status'   => 'failed',
		    'edit_date'	    => current_time( 'mysql' )
		);
		wp_update_post($postdata);
	}
	
	
	/**
	 * 
	 * @param ATCF_Campaign $campaign
	 */
	public static function advice_notification( $campaign ) {
		// Formules d'introduction
		$list_greetings = array(
			"Comment allez-vous aujourd'hui ? Prévoyez des pauses de temps en temps, cela permet de rester efficace plus longtemps ;-)",
			"Quoi de prévu en cette belle journée ? Pensez à prévoir des moments de détente avec votre famille et vos amis, le travail n’est pas tout ;-)",
			"Avez-vous fait le plein de vitamines ? N’oubliez pas de manger vos 5 fruits et légumes par jour, il faut rester en forme pour cette levée de fonds ;-)",
			"En forme pour cette journée ? Pensez à bouger de temps en temps, une petite balade ça fait toujours du bien, au corps… et à l’esprit ! ;-)",
			"Comment se passe la journée ? Pourquoi pas une sieste de 15 min ou une petite balade ? Cela vous permettra de rester zen malgré l’intensité de cette aventure ;-)",
			"Tout va bien aujourd'hui ? Chaque jour est un nouveau jour, avec de nouvelles opportunités, il suffit de garder l’esprit ouvert ;-)"
		);
		$greetings = $list_greetings[ mt_rand( 0, count( $list_greetings ) - 1 ) ];
		
		$count_new_votes = 0;
		$count_new_votes_with_intention = 0;
		$count_new_votes_with_intention_amount = 0;
		$count_votes = 0;
		$count_votes_with_intention = 0;
		$count_votes_with_intention_amount = 0;
		$count_new_preinvestments = 0;
		$count_new_preinvestments_amount = 0;
		
		$list_new_investments = array();
		$count_new_investments = 0;
		$count_new_investments_amount = 0;
		$count_preinvestments_to_validate = 0;
		$count_preinvestments_to_validate_amount = 0;
		$count_investments_to_validate = 0;
		$count_investments_to_validate_amount = 0;

		// Données utiles tout le long
		$list_priorities = array();
		$contact_list = array();
		$preinvestments_to_validate = array();
		$investments_to_complete = array();
		
		$interval_date = new DateTime();
		$interval_date->sub( new DateInterval( 'P3D' ) );
		$interval_date->setTime( 0, 0, 1 );

		// Parcours des évaluations pour sauvegarder par utilisateurs
		global $wpdb;
		$table_vote = $wpdb->prefix . "ypcf_project_votes";
		$payments_data = $campaign->payments_data();
		$list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum, date FROM ".$table_vote." WHERE post_id = ".$campaign->ID );
		foreach ( $list_user_voters as $item_vote ) {
			$entity_str = '';
			if ( WDGOrganization::is_user_organization( $item_vote->user_id ) ) {
				$WDGOrganization = new WDGOrganization( $item_vote->user_id );
				$entity_str = $WDGOrganization->get_name(). ' (' .$WDGOrganization->get_email(). ')';
				$entity_is_registered = $WDGOrganization->is_registered_lemonway_wallet();
			} else {
				$WDGUser = new WDGUser( $item_vote->user_id );
				$entity_str = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname(). ' (' .$WDGUser->get_email(). ')';
				$entity_is_registered = $WDGUser->is_lemonway_registered();
			}
			
			$contact_list[ $item_vote->user_id ] = array(
				'entity_id'		=> $item_vote->user_id,
				'entity_str'	=> $entity_str,
				'entity_is_registered'	=> $entity_is_registered,
				'vote_sum'		=> $item_vote->invest_sum,
				'invest_sum'	=> 0,
				'skip_contact'	=> FALSE
			);
			
			$count_votes++;
			if ( $item_vote->invest_sum > 0 ) {
				$count_votes_with_intention++;
				$count_votes_with_intention_amount += $item_vote->invest_sum;
			}
			$date_vote = new DateTime( $item_vote->date );
			if ( $date_vote > $interval_date ) {
				$count_new_votes++;
				if ( $item_vote->invest_sum > 0 ) {
					$count_new_votes_with_intention++;
					$count_new_votes_with_intention_amount += $item_vote->invest_sum;
				}
			}
		}

		// Parcours des investissements pour voir ce qu'on peut en faire
		foreach ( $payments_data as $item_invest ) {
			$entity_str = '';
			$entity_is_registered = FALSE;
			if ( WDGOrganization::is_user_organization( $item_invest[ 'user' ] ) ) {
				$WDGOrganization = new WDGOrganization( $item_invest[ 'user' ] );
				$entity_str = $WDGOrganization->get_name(). ' (' .$WDGOrganization->get_email(). ')';
				$entity_is_registered = $WDGOrganization->is_registered_lemonway_wallet();
			} else {
				$WDGUser = new WDGUser( $item_invest[ 'user' ] );
				$entity_str = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname(). ' (' .$WDGUser->get_email(). ')';
				$entity_is_registered = $WDGUser->is_lemonway_registered();
			}
			$payment_investment = new WDGInvestment( $item_invest[ 'ID' ] );
			$contract_status = $payment_investment->get_contract_status();

			// Pré-investissements à valider
			if ( $item_invest[ 'status' ] == 'pending' && $contract_status == WDGInvestment::$contract_status_preinvestment_validated ) {
				$count_preinvestments_to_validate++;
				$count_preinvestments_to_validate_amount += $item_invest[ 'amount' ];
				array_push( $preinvestments_to_validate, $entity_str );
				if ( isset( $contact_list[ $item_invest[ 'user' ] ] ) ) {
					$contact_list[ $item_invest[ 'user' ] ][ 'skip_contact' ] = TRUE;
				}
				
				if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
					if ( !isset( $contact_list[ $item_invest[ 'user' ] ] ) ) {
						$contact_list[ $item_invest[ 'user' ] ] = array(
							'entity_id'		=> $item_invest[ 'user' ],
							'entity_str'	=> $entity_str,
							'entity_is_registered'	=> $entity_is_registered,
							'vote_sum'		=> 0,
							'invest_sum'	=> $item_invest[ 'amount' ],
							'skip_contact'	=> FALSE
						);

					} else {
						$contact_list[ $item_invest[ 'user' ] ][ 'invest_sum' ] += $item_invest[ 'amount' ];

					}

					$item_invest_date = new DateTime( $item_invest[ 'date' ] );
					$item_invest_date->setTime( 1, 1, 1 );
					if ( $item_invest_date > $interval_date ) {
						$count_new_preinvestments++;
						$count_new_preinvestments_amount += $item_invest[ 'amount' ];
						array_push( $list_new_investments, $entity_str. ' - ' .$item_invest[ 'amount' ]. ' €' );
					}
				}
			}

			// Investissements dont l'investisseur s'est authentifié
			if ( $item_invest[ 'status' ] == 'pending' && $contract_status == WDGInvestment::$contract_status_not_validated && $entity_is_registered ) {
				$count_investments_to_validate++;
				$count_investments_to_validate_amount += $item_invest[ 'amount' ];
				array_push( $investments_to_complete, $entity_str );
				if ( isset( $contact_list[ $item_invest[ 'user' ] ] ) ) {
					$contact_list[ $item_invest[ 'user' ] ][ 'skip_contact' ] = TRUE;
				}
			}
			
			// Investissements validés
			if ( $item_invest[ 'status' ] == 'publish' ) {
				if ( !isset( $contact_list[ $item_invest[ 'user' ] ] ) ) {
					$contact_list[ $item_invest[ 'user' ] ] = array(
						'entity_id'		=> $item_invest[ 'user' ],
						'entity_str'	=> $entity_str,
						'entity_is_registered'	=> $entity_is_registered,
						'vote_sum'		=> 0,
						'invest_sum'	=> $item_invest[ 'amount' ],
						'skip_contact'	=> FALSE
					);
					
				} else {
					$contact_list[ $item_invest[ 'user' ] ][ 'invest_sum' ] += $item_invest[ 'amount' ];
					
				}
				
				$item_invest_date = new DateTime( $item_invest[ 'date' ] );
				if ( $item_invest_date > $interval_date ) {
					if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
						$count_new_investments++;
						$count_new_investments_amount += $item_invest[ 'amount' ];
					}
					if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
						$count_new_preinvestments++;
						$count_new_preinvestments_amount += $item_invest[ 'amount' ];
					}
					array_push( $list_new_investments, $entity_str. ' - ' .$item_invest[ 'amount' ]. ' €' );
				}
			}
		}

		// Tri de la liste de contacts par différence plus forte entre intention et investissement
		// Attention : perte du système clé => valeur pour un tableau ordonné classique
		usort( $contact_list, function ( $item1, $item2 ) {
			$item1_diff = $item1[ 'vote_sum' ] - $item1[ 'invest_sum' ];
			$item2_diff = $item2[ 'vote_sum' ] - $item2[ 'invest_sum' ];
			return ( $item1_diff < $item2_diff );
		} );


		// Priorité numéro 1 en investissement : valider les pré-investissements qui peuvent l'être
		if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
			if ( !empty( $preinvestments_to_validate ) ) {
				foreach ( $preinvestments_to_validate as $preinvestment_str ) {
					array_push( $list_priorities, "faire valider le pré-investissement suivant : " .$preinvestment_str. ".<br>Il suffit de venir se reconnecter sur la plateforme. Sinon, l'investisseur peut aussi nous valider son pré-investissement par mail." );
				}
			}
		}

		// Priorité numéro 2 : faire venir les investissements en attente dont les documents sont validés
		if ( !empty( $investments_to_complete ) ) {
			foreach ( $investments_to_complete as $investment_str ) {
				if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
					array_push( $list_priorities, "faire finaliser l'investissement suivant : " .$investment_str." (l'investisseur est authentifi&eacute;)" );
				} else {
					array_push( $list_priorities, "faire finaliser le pré-investissement suivant : " .$investment_str." (l'investisseur est authentifi&eacute;)" );
				}
			}
		}


		// Priorité numéro 3 : faire venir les évaluateurs avec une grosse intention d'investissement mais ayant investi moins
		if ( !empty( $contact_list ) ) {
			foreach ( $contact_list as $contact_info ) {
				if ( $contact_info[ 'vote_sum' ] > $contact_info[ 'invest_sum' ] && !$contact_info[ 'skip_contact' ] ) {
					$entity_str = $contact_info[ 'entity_str' ];
					$entity_is_registered = $contact_info[ 'entity_is_registered' ];
					if ( empty( $entity_str ) ) {
						$contact_id = $contact_info[ 'entity_id' ];
						if ( WDGOrganization::is_user_organization( $contact_id ) ) {
							$WDGOrganization = new WDGOrganization( $contact_id );
							$entity_str = $WDGOrganization->get_name(). ' (' .$WDGOrganization->get_email(). ')';
							$entity_is_registered = $WDGOrganization->is_registered_lemonway_wallet();
						} else {
							$WDGUser = new WDGUser( $contact_id );
							$entity_str = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname(). ' (' .$WDGUser->get_email(). ')';
							$entity_is_registered = $WDGUser->is_lemonway_registered();
						}
					}
					$registration_str = ( $entity_is_registered ) ? "Déjà authentifié" : "Pas encore authentifié";
					array_push( $list_priorities, "faire investir autant que l'intention : " . $entity_str. "<br>Intention de " .$contact_info[ 'vote_sum' ]." € et investissement de " .$contact_info[ 'invest_sum' ]." € (" .$registration_str. ")" );
				}
			}
		}
		
		
		$send_mail = FALSE;
		
		// Résumé
		if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
			if ( $count_new_votes > 1 ) {
				$last_24h = "- " .$count_new_votes. " nouvelles &eacute;valuations<br>";
			} else {
				$last_24h = "- " .$count_new_votes. " nouvelle &eacute;valuation<br>";
			}
			
			if ( $count_new_votes_with_intention > 1 ) {
				$last_24h .= "- " .$count_new_votes_with_intention. " nouvelles intentions d'investissement, pour un montant de ".$count_new_votes_with_intention_amount." €<br>";
			} elseif ( $count_new_votes_with_intention == 1 ) {
				$last_24h .= "- " .$count_new_votes_with_intention. " nouvelle intention d'investissement, pour un montant de ".$count_new_votes_with_intention_amount." €<br>";
			}
			
			if ( $count_new_preinvestments > 1 ) {
				$last_24h .= "- " .$count_new_preinvestments. " nouveaux pr&eacute;-investissements, pour un montant de ".$count_new_preinvestments_amount." €<br>";
			} elseif ( $count_new_preinvestments == 1 ) {
				$last_24h .= "- " .$count_new_preinvestments. " nouveau pr&eacute;-investissement, pour un montant de ".$count_new_preinvestments_amount." €<br>";
			}
			
			$last_24h .= "<strong>Total des évaluations :</strong> " .$count_votes. " (dont " .$count_votes_with_intention. " avec une intention d'investissement, pour un montant de " .$count_votes_with_intention_amount. " €)<br>";
			
			$percent_preinvestment = round( $count_preinvestments_to_validate_amount / $campaign->minimum_goal( false ) * 100 );
			$last_24h .= "<strong>Total des pr&eacute;-investissements validés :</strong> " .$count_preinvestments_to_validate. ", pour un montant de " .$count_preinvestments_to_validate_amount. " € (soit " .$percent_preinvestment. " % de l'objectif minimum)<br>";
			
			// Les nouveaux investisseurs à remercier
			if ( count( $list_new_investments ) > 0 ) {
				$send_mail = TRUE;
				$last_24h .= "<br><strong>Commencez par remercier personnellement chaque nouveau pr&eacute;-investisseur :</strong><br>";
				foreach ( $list_new_investments as $new_investment ) {
					$last_24h .= "- " .$new_investment. "<br>";
				}
			}
		}
		
		if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
			if ( $count_new_investments > 1 ) {
				$last_24h = "- " .$count_new_investments. " nouveaux investissements validés, pour un montant de " .$count_new_investments_amount. " €<br>";
			} else {
				$last_24h = "- " .$count_new_investments. " nouvel investissement validé, pour un montant de " .$count_new_investments_amount. " €<br>";
			}
			
			if ( $count_preinvestments_to_validate > 0 ) {
				$last_24h .= "- " .$count_preinvestments_to_validate. " pré-investissements en attente de validation, pour un montant de " .$count_preinvestments_to_validate_amount. " €<br>";
			}
			
			if ( $count_investments_to_validate > 1 ) {
				$last_24h .= "- " .$count_investments_to_validate. " investissements en attente de validation, pour un montant de " .$count_investments_to_validate_amount. " €<br>";
			} else {
				$last_24h .= "- " .$count_investments_to_validate. " investissement en attente de validation, pour un montant de " .$count_investments_to_validate_amount. " €<br>";
			}
			
			$last_24h .= "<strong>Total des investissements validés et comptabilisés :</strong> " .$campaign->current_amount(). " (" .$campaign->percent_minimum_completed(). ")<br>";
			
			// Les nouveaux investisseurs à remercier
			if ( count( $list_new_investments ) > 0 ) {
				$send_mail = TRUE;
				$last_24h .= "<br><strong>Commencez par remercier personnellement chaque nouvel investisseur :</strong><br>";
				foreach ( $list_new_investments as $new_investment ) {
					$last_24h .= "- " .$new_investment. "<br>";
				}
			}
		}
		
		
		
		
		// Les autres priorités du jour
		$top_actions = '';
		for ( $i = 0; $i <= 10; $i++ ) {
			if ( isset( $list_priorities[ $i ] ) ) {
				$send_mail = TRUE;
				$top_actions .= "- " .$list_priorities[ $i ]. "<br>";
			}
		}


		if ( $send_mail ) {
			$url_dashboard = home_url( '/tableau-de-bord/?campaign_id=' .$campaign->ID );
			
			$replyto_mail = 'support@wedogood.co';
			NotificationsAPI::campaign_advice( 'communication@wedogood.co', $replyto_mail, $campaign->get_name(), $url_dashboard, 'WE DO GOOD', $greetings, $last_24h, $top_actions );
			
			$WDGUserAuthor = new WDGUser( $campaign->data->post_author );
			NotificationsAPI::campaign_advice( $WDGUserAuthor->get_email(), $replyto_mail, $campaign->get_name(), $url_dashboard, $WDGUserAuthor->get_firstname(), $greetings, $last_24h, $top_actions );

			$team_member_list = WDGWPREST_Entity_Project::get_users_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );
			if ( count( $team_member_list ) > 0 ) {
				foreach ( $team_member_list as $team_member ) {
					$WDGUserTeam = new WDGUser( $team_member->wpref );
					NotificationsAPI::campaign_advice( $WDGUserTeam->get_email(), $replyto_mail, $campaign->get_name(), $url_dashboard, $WDGUserTeam->get_firstname(), $greetings, $last_24h, $top_actions );
				}
			}
		}
		
	}
}