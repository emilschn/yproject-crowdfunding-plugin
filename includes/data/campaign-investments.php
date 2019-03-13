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
			
		// Décorations du mail
		$buffer_email_object = "BETA - Conseils quotidiens pour la levée de fonds de " . $campaign->get_name();
		
		// Formules d'introduction
		$list_introductions_1 = array(
			"Bonjour bonjour !",
			"Bonjour l'&eacute;quipe !",
			"Hello l'&eacute;quipe de " .$campaign->get_name(). " !",
			"Salut !",
			"Hello l'&eacute;quipe !"
		);
		$list_introductions_2 = array(
			"Comment se passe la journée ?",
			"Tout va bien aujourd'hui ?",
			"J'esp&egrave;re que tout se passe bien !",
			"Comment allez-vous aujourd'hui ?",
			"En forme pour cette journ&eacute;e ?"
		);
		$list_introductions_3 = array(
			"Voici vos actions prioritaires du jour !",
			"Ci-dessous vos actions prioritaires du jour !",
			"Vous trouverez ci-dessous vos top priorit&eacute;s pour la journ&eacute;e !",
			"Voici les actions quotidiennes prioritaires !",
			"Ci-dessous les actions du jour &agrave; effectuer en priorit&eacute; !"
		);
		$buffer_email_content = $list_introductions_1[ mt_rand( 0, count( $list_introductions_1 ) - 1 ) ] . "<br><br>";
		$buffer_email_content .= $list_introductions_2[ mt_rand( 0, count( $list_introductions_2 ) - 1 ) ] . "<br>";
		$buffer_email_content .= $list_introductions_3[ mt_rand( 0, count( $list_introductions_3 ) - 1 ) ] . "<br><br>";

		// Données utiles tout le long
		$list_priorities = array();
		$contact_list = array();
		$preinvestments_to_validate = array();
		$investments_to_complete = array();

		// Parcours des évaluations pour sauvegarder par utilisateurs
		global $wpdb;
		$table_vote = $wpdb->prefix . "ypcf_project_votes";
		$payments_data = $campaign->payments_data();
		$list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum FROM ".$table_vote." WHERE post_id = ".$campaign->ID );
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
				array_push( $preinvestments_to_validate, $entity_str );
				if ( isset( $contact_list[ $item_invest[ 'user' ] ] ) ) {
					$contact_list[ $item_invest[ 'user' ] ][ 'skip_contact' ] = TRUE;
				}
			}

			// Investissements dont l'investisseur s'est authentifié
			if ( $item_invest[ 'status' ] == 'pending' && $contract_status == WDGInvestment::$contract_status_not_validated && $entity_is_registered ) {
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
			}
		}

		// Tri de la liste de contacts par différence plus forte entre intention et investissement
		// Attention : perte du système clé => valeur pour un tableau ordonné classique
		usort( $contact_list, function ( $item1, $item2 ) {
			$item1_diff = $item1[ 'vote_sum' ] - $item1[ 'invest_sum' ];
			$item2_diff = $item2[ 'vote_sum' ] - $item2[ 'invest_sum' ];
			return ( $item1_diff < $item2_diff );
		} );


		// Priorité numéro 1 : valider les pré-investissements qui peuvent l'être
		if ( !empty( $preinvestments_to_validate ) ) {
//			$buffer_email_content .= "<b>Priorité 1 : valider les pré-investissements suivants</b><br>";
			foreach ( $preinvestments_to_validate as $preinvestment_str ) {
				array_push( $list_priorities, "faire valider le pr&eacute;investissement suivant : " . $preinvestment_str );
			}
		}

		// Priorité numéro 2 : faire venir les investissements en attente dont les documents sont validés
		if ( !empty( $investments_to_complete ) ) {
//			$buffer_email_content .= "<b>Priorité 2 : ces investissements sont en attente d'authentification, et les investisseurs sont authentifiés</b><br>";
			foreach ( $investments_to_complete as $investment_str ) {
				array_push( $list_priorities, "faire finaliser l'investissement suivant (l'investisseur est authentifi&eacute;) : " . $investment_str );
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
					array_push( $list_priorities, "faire investir autant que l'intention : " . $entity_str. " - Intention de " .$contact_info[ 'vote_sum' ]." € et investissement de " .$contact_info[ 'invest_sum' ]." € (" .$registration_str. ")" );
				}
			}
		}
		
		$send_mail = FALSE;
		$date = new DateTime();
		$day = $date->format( 'j' );
		$day_modulo = $day % 3;
		for ( $i = $day_modulo; $i <= 15; $i += 3 ) {
			if ( isset( $list_priorities[ $i ] ) ) {
				$send_mail = TRUE;
				$buffer_email_content .= "- " .$list_priorities[ $i ]. "<br>";
			}
		}


		if ( $send_mail ) {
			$list_outro = array(
				"Bonne journ&eacute;e !",
				"A bient&ocirc;t !",
				"Belle journ&eacute;e !",
				"Je vous souhaite une bonne journ&eacute;e !",
				"Bon courage !"
			);
			$buffer_email_content .= "<br><br><br>" . $list_outro[ mt_rand( 0, count( $list_outro ) - 1 ) ];


			NotificationsEmails::send_mail( 'admin@wedogood.co', $buffer_email_object, $buffer_email_content );
		}
		
	}
}