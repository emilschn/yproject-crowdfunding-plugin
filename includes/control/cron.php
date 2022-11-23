<?php
/**
 * Classe de gestion des appels Cron
 */
class WDGCronActions {
	public static function send_notifications() {
		$current_date = new DateTime();
		$current_date->setTime( 0, 0, 1 );

		// Récupération de toutes les déclarations qui sont dues entre maintenant et dans 19 jours (pour ceux dont la déclaration est au 20)
		$current_date_day = $current_date->format( 'd' );
		if ( $current_date_day == 1 || $current_date_day == 4 || $current_date_day == 8 || $current_date_day == 10 ) {
			$date_in_10_days = new DateTime();
			$date_in_10_days->add( new DateInterval('P19D') );
			$declaration_list = WDGWPREST_Entity_Declaration::get_list_by_date( $current_date->format( 'Y-m-d' ), $date_in_10_days->format( 'Y-m-d' ) );

			if ( $declaration_list ) {
				foreach ( $declaration_list as $declaration_data ) {
					// On n'envoie des notifications que pour les déclarations qui ne sont pas commencées
					if ( $declaration_data->status == WDGROIDeclaration::$status_declaration ) {
						$date_due = new DateTime( $declaration_data->date_due );
						$date_due->setTime( 10, 30, 0 );
						if ( $date_due > $current_date ) {
							switch ( $current_date_day ) {
								case 1:
								case 4:
									$nb_days_diff = 9;
									break;
								case 8:
									$nb_days_diff = 2;
									break;
								case 10:
									$nb_days_diff = 0;
									break;
							}

							$campaign = new ATCF_Campaign( FALSE, $declaration_data->id_project );
							if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded ) {
								$organization = $campaign->get_organization();
								$wdgorganization = new WDGOrganization( $organization->wpref, $organization );
								$wdguser_author = new WDGUser( $campaign->data->post_author );

								// Données qui seront transmises à SiB
								NotificationsAPIShortcodes::set_recipient($wdguser_author);
								NotificationsAPIShortcodes::set_declaration($declaration_data);

								$recipients = $wdgorganization->get_email(). ',' .$wdguser_author->get_email();
								$recipients .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );

								NotificationsAPI::declaration_to_do( $wdguser_author, $recipients, $nb_days_diff, $wdgorganization->has_signed_mandate() );
							}
						}
					}
				}
			}
		}

		// Si on est le 15, il faut envoyer les avertissements de prélèvement
		if ( $current_date->format( 'd' ) == 15 ) {
			$date_in_5_days = new DateTime();
			$date_in_5_days->add( new DateInterval('P5D') );
			$date_5_days_ago = new DateTime();
			$date_5_days_ago->sub( new DateInterval('P5D') );
			$declaration_list = WDGWPREST_Entity_Declaration::get_list_by_date( $date_5_days_ago->format( 'Y-m-d' ), $date_in_5_days->format( 'Y-m-d' ) );
			if ( $declaration_list ) {
				foreach ( $declaration_list as $declaration_data ) {
					// On n'envoie des notifications que pour les déclarations qui ne sont pas commencées
					if ( $declaration_data->status == WDGROIDeclaration::$status_declaration ) {
						$campaign = new ATCF_Campaign( FALSE, $declaration_data->id_project );
						$organization = $campaign->get_organization();
						$wdgorganization = new WDGOrganization( $organization->wpref, $organization );

						if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded && $wdgorganization->has_signed_mandate() ) {
							$wdguser_author = new WDGUser( $campaign->data->post_author );
							$recipients = $wdgorganization->get_email(). ',' .$wdguser_author->get_email();
							$recipients .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );

							$quarter_str_list = array( "premier", "deuxième", "troisième", "quatrième" );
							$quarter_percent_list = array( 10, 20, 30, 40 );
							$nb_quarter = 0;
							$estimated_turnover = $campaign->estimated_turnover();
							$nb_year = array_key_first( $estimated_turnover );

							// Parcours des déclarations de royalties pour savoir à quelle année et quel trimestre on est dans les échéances
							// TODO : les trier dans l'ordre par sécurité
							$existing_roi_declarations = $campaign->get_roi_declarations();
							foreach ( $existing_roi_declarations as $declaration_object ) {
								$date_declaration = new DateTime( $declaration_object[ 'date_due' ] );

								if ( $date_declaration->format( 'm' ) == $current_date->format( 'm' ) && $date_declaration->format( 'Y' ) == $current_date->format( 'Y' ) ) {
									break;
								} else {
									$nb_quarter++;
									if ( $nb_quarter >= $campaign->get_declarations_count_per_year() ) {
										$nb_quarter = 0;
										$nb_year++;
									}
								}
							}

							// Test pour corriger les décalages dans le CA prévisionnel
							// Pour éviter d'avoir zero, il faut soit le premier de la liste, soit le dernier
							if ( !isset( $estimated_turnover[ $nb_year ] ) ) {
								if ( $nb_year < 1 ) {
									$nb_year = array_key_first( $estimated_turnover );
								} else {
									$nb_year = array_key_last( $estimated_turnover );
								}
							}

							// Calculs des éléments à afficher
							$amount_estimation_year = $estimated_turnover[ $nb_year ];
							$percent_estimation = $quarter_percent_list[ $nb_quarter ];
							$amount_estimation_quarter = $amount_estimation_year * $percent_estimation / 100;
							$percent_royalties = $campaign->roi_percent();
							$amount_royalties = round( $amount_estimation_quarter * $campaign->roi_percent() / 100, 2 );
							$amount_fees = round( $amount_royalties * $campaign->get_costs_to_organization() / 100, 2 );
							$minimum_costs = $campaign->get_minimum_costs_to_organization();
							if ( $minimum_costs > 0 ) {
								$amount_fees = max( $amount_fees, $minimum_costs );
							}
							$amount_total = $amount_royalties + $amount_fees;

							NotificationsAPI::declaration_to_do_warning( $recipients, $wdguser_author, $campaign, $declaration_data, $quarter_str_list[ $nb_quarter ], $percent_estimation, $amount_estimation_year, $amount_estimation_quarter, $percent_royalties, $amount_royalties, $amount_fees, $amount_total );
						}
					}
				}
			}
		}

		// on récupère la liste de toutes les déclarations au status "payment"
		$declaration_list = WDGWPREST_Entity_Declaration::get_list_by_status( 'payment' );
		if ( $declaration_list ) {
			foreach ($declaration_list as $declaration_data) {
				// envoi d'une notification après 3J quand déclaré mais pas de paiement enclenché
				$date_declaration = new DateTime( $declaration_data->date_declaration );
				$date_declaration->setTime( 0, 0, 1 );
				// on calcule la différence entre aujourd'hui et la date de déclaration
				$interval = $date_declaration->diff($current_date);
				// si cela fait 3 jours que la déclaration a été faite, mais pas payée (car on est au status payment)
				if ( $interval->days == 3 ) {
					// on envoie une notification au porteur de projet
					$campaign = new ATCF_Campaign( FALSE, $declaration_data->id_project );
					$organization = $campaign->get_organization();
					$wdgorganization = new WDGOrganization( $organization->wpref, $organization );
					$wdguser_author = new WDGUser( $campaign->data->post_author );
					$recipients = $wdgorganization->get_email(). ',' .$wdguser_author->get_email();
					$recipients .= WDGWPREST_Entity_Project::get_users_mail_list_by_role( $campaign->get_api_id(), WDGWPREST_Entity_Project::$link_user_type_team );

					NotificationsAPI::declaration_done_not_paid( $recipients, $wdguser_author, $campaign, $declaration_data );
				}
            }
		}
	}

	public static function make_projects_rss($funding_project = TRUE) {
		$date = new DateTime();
		$current_date = $date->format('Y-m-d');

		$buffer_rss = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
		$buffer_rss .= '<rss version="2.0">' . "\n";
		$buffer_rss .= '<channel>' . "\n";
		$buffer_rss .= '<title><![CDATA[Les projets de '.ATCF_CrowdFunding::get_platform_name().']]></title>' . "\n";
		$buffer_rss .= '<description><![CDATA[Tous les projets en investissement sur '.ATCF_CrowdFunding::get_platform_name().']]></description>' . "\n";
		$buffer_rss .= '<lastBuildDate>'.$date->format(DateTime::RFC822).'</lastBuildDate>' . "\n";
		$buffer_rss .= '<link>' .site_url(). '</link>' . "\n";

		$buffer_partners = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
		$buffer_partners .= '<partenaire>' . "\n";

		// création d'un xml et d'un json sur les projets d'épargne positive
		$buffer_positive_savings = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
		$buffer_positive_savings .= '<positive_saving>' . "\n";

		//Parcours des projets en cours de collecte
		if ( $funding_project ) {
			// Récupération des projets en cours
			ATCF_Campaign::list_projects_funding( -1 );
			while (have_posts()): the_post();
			global $post;
			$campaign = atcf_get_campaign( $post );
			if ( !$campaign->is_hidden() ) {
				$result = WDGCronActions::make_single_project_rss( $campaign, $current_date );
				$buffer_rss .= $result[ 'rss' ];
				$buffer_partners .= $result[ 'partners' ];
			}
			endwhile;
			wp_reset_query();

			// Récupération des projets finis depuis moins d'un mois
			ATCF_Campaign::list_projects_funded( 80 );
			while (have_posts()): the_post();
			global $post;
			$campaign = atcf_get_campaign( $post );
			$campaign_end_date_limit = new DateTime( $campaign->end_date( 'Y-m-d' ) );
			$campaign_end_date_limit->add( new DateInterval( 'P1M' ) );
			if ( !$campaign->is_hidden() && $campaign_end_date_limit > $date ) {
				$result = WDGCronActions::make_single_project_rss( $campaign, $current_date );
				$buffer_rss .= $result[ 'rss' ];
				$buffer_partners .= $result[ 'partners' ];
			}
			endwhile;
			wp_reset_query();

			// Récupération des projets EP en cours
			$project_list_positive_savings = ATCF_Campaign::get_list_positive_savings( 0 );

			foreach ( $project_list_positive_savings as $project_post ) {
				$campaign = atcf_get_campaign( $project_post );
				// TODO : si on veut des infos différentes pour les projets en EP, il faudra refaire une autre fonction
				$result = WDGCronActions::make_single_project_rss( $campaign, $current_date );
				$buffer_positive_savings .= $result[ 'partners' ];
			}
		} else {
			ATCF_Campaign::list_projects_funded( 80 );
			while (have_posts()): the_post();
			global $post;
			$campaign = atcf_get_campaign( $post );
			if ( !$campaign->is_hidden() ) {
				$result = WDGCronActions::make_single_project_rss( $campaign, $current_date );
				$buffer_rss .= $result[ 'rss' ];
				$buffer_partners .= $result[ 'partners' ];
			}
			endwhile;
			wp_reset_query();
		}

		$buffer_partners .= '</partenaire>';
		$buffer_rss .= '</channel>';
		$buffer_rss .= '</rss>';

		$buffer_positive_savings .= '</positive_saving>';

		$filename = dirname( __FILE__ ) . '/../../../../../current-projects.xml';
		if ( !$funding_project ) {
			$filename = dirname( __FILE__ ) . '/../../../../../finished-projects.xml';
		}
		$file_handle = fopen($filename, 'w');
		fwrite($file_handle, $buffer_partners);
		fclose($file_handle);

		$filename_rss = dirname( __FILE__ ) . '/../../../../../rss.xml';
		$file_handle_rss = fopen($filename_rss, 'w');
		fwrite($file_handle_rss, $buffer_rss);
		fclose($file_handle_rss);

		// transformation du xml de projets en json
		$xml_partners     = simplexml_load_string($buffer_partners, 'SimpleXMLElement', LIBXML_NOCDATA);
		$json_partners    = json_encode($xml_partners);

		$filename_json = dirname( __FILE__ ) . '/../../../../../current-projects.json';
		if ( !$funding_project ) {
			$filename_json = dirname( __FILE__ ) . '/../../../../../finished-projects.json';
		}
		$file_handle_json = fopen($filename_json, 'w');
		fwrite($file_handle_json, $json_partners);
		fclose($file_handle_json);

		$filename_positive_savings = dirname( __FILE__ ) . '/../../../../../current-projects-positive-savings.xml';
		$file_handle_positive_savings = fopen($filename_positive_savings, 'w');
		fwrite($file_handle_positive_savings, $buffer_positive_savings);
		fclose($file_handle_positive_savings);

		$xml_positive_savings    = simplexml_load_string($buffer_positive_savings, 'SimpleXMLElement', LIBXML_NOCDATA);
		$json_positive_savings    = json_encode($xml_positive_savings);

		$filename_positive_savings_json = dirname( __FILE__ ) . '/../../../../../current-projects-positive-savings.json';
		$file_handle_positive_savings_json = fopen($filename_positive_savings_json, 'w');
		fwrite($file_handle_positive_savings_json, $json_positive_savings);
		fclose($file_handle_positive_savings_json);
	}

	public static function make_campaign_xml($campaign_id) {
		$date = new DateTime();
		$current_date = $date->format('Y-m-d');

		$buffer_xml = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
		$buffer_xml .= '<partenaire>';
		$buffer_xml .= '<mise_a_jour date="'.$date->format('Y-m-d H:i:s').'" />' . "\n";
		$campaign = atcf_get_campaign( $campaign_id );
		$result = WDGCronActions::make_single_project_rss( $campaign, $current_date, TRUE );
		$buffer_xml .= $result[ 'partners' ];
		$buffer_xml .= '</partenaire>';

		$filename = dirname( __FILE__ ) . '/../../../../../current-project-' .$campaign->get_url(). '.xml';
		$file_handle = fopen( $filename, 'w' );
		fwrite( $file_handle, $buffer_xml );
		fclose( $file_handle );
	}

	public static function make_single_project_rss($campaign, $current_date, $add_list_investments = FALSE) {
		$buffer_rss = '';
		$buffer_partners = '';

		$organization = $campaign->get_organization();
		$organization_obj = new WDGOrganization( $organization->wpref, $organization );

		//*****************
		//Formatage pour RSS
		$buffer_rss .= '<item>' . "\n";
		$buffer_rss .= '<title><![CDATA['.$campaign->data->post_title.']]></title>' . "\n";
		$buffer_rss .= '<description><![CDATA['.html_entity_decode($campaign->summary()).']]></description>' . "\n";
		$buffer_rss .= '<pubDate>'.$campaign->begin_collecte_date('Y-m-d').'</pubDate>' . "\n";
		$buffer_rss .= '<link>'.$campaign->get_public_url().'</link>' . "\n";
		$buffer_rss .= '</item>' . "\n";
		//*****************

		//*****************
		//Formatage pour partenaires
		$buffer_partners .= '<projet>' . "\n";

		//Toutes les données pour TNP
		$buffer_partners .= '<reference_partenaire>098</reference_partenaire>' . "\n"; //TNP :: (010,011 => 039 pour le DON, 040,041 => 069 pour le prêt, 070, 071 => 099 pour l'investissement SANS rentrer dans la subdivision)
		$buffer_partners .= '<date_export>'.$current_date.'</date_export>' . "\n"; //TNP :: YYYY-MM-DD
		$buffer_partners .= '<reference_projet>'.$campaign->ID.'</reference_projet>' . "\n"; //TNP :: ref unique interne

		//TNP :: impacts : min 1, max 2
		$categories =  $campaign->get_categories_by_type( 'categories', TRUE );

		if (strrpos($categories, "Social") === false) {
			$social = 'non';
		} else {
			$social = 'oui';
		}
		$buffer_partners .= '<impact_social>'.$social.'</impact_social>' . "\n";
		if (strrpos($categories, "Environnemental") === false) {
			$environnemental = 'non';
		} else {
			$environnemental = 'oui';
		}
		$buffer_partners .= '<impact_environnemental>'.$environnemental.'</impact_environnemental>' . "\n";
		if (strrpos($categories, "Collaboratif") === false) {
			$collaboratif = 'non';
		} else {
			$collaboratif = 'oui';
		}
		$buffer_partners .= '<impact_culturel>'.$collaboratif.'</impact_culturel>' . "\n"; // ???
		if (strrpos($categories, "Economique") === false) {
			$eco = 'non';
		} else {
			$eco = 'oui';
		}
		$buffer_partners .= '<impact_eco>'.$eco.'</impact_eco>' . "\n";

		//TNP : catégories, 2 max
		$tnp_categories = WDGCronActions::get_single_project_rss_tousnosprojet_categories( $campaign );
		$buffer_partners .= '<categorie>' . "\n";
		$index = 1;
		foreach ( $tnp_categories as $tnp_category ) {
			$buffer_partners .= '<categorie'.$index.'>'.$tnp_category.'</categorie'.$index.'>' . "\n";
		}
		$buffer_partners .= '</categorie>' . "\n";

		$buffer_partners .= '<mots_cles_nomenclature_operateur></mots_cles_nomenclature_operateur>' . "\n"; //TNP :: Mots-clés TODO
		$buffer_partners .= '<mode_financement>ROY</mode_financement>' . "\n"; //TNP :: Mode de financement (DON, DOC, PRE, PRR, ACT, OBL) - invention ROY
		$buffer_partners .= '<type_porteur_projet>ENT</type_porteur_projet>' . "\n"; //TNP :: Statut du PP (ENT, ASS, PAR, COL)
		$buffer_partners .= '<qualif_ESS>non</qualif_ESS>' . "\n"; //TNP :: Qualification ESS du porteur projet
		$buffer_partners .= '<code_postal>' .$organization_obj->get_postal_code( true ). '</code_postal>' . "\n";
		$buffer_partners .= '<ville><![CDATA[' .$organization_obj->get_city(). ']]></ville>' . "\n";

		$buffer_partners .= '<titre><![CDATA['.$campaign->data->post_title.']]></titre>' . "\n"; //TNP
		$buffer_partners .= '<description><![CDATA['.html_entity_decode($campaign->summary()).']]></description>' . "\n"; //TNP
		$buffer_partners .= '<description_complete><![CDATA['.$campaign->data->post_content.']]></description_complete>' . "\n"; //Info durable
		$buffer_partners .= '<url><![CDATA['.$campaign->get_public_url().']]></url>' . "\n"; //TNP
		$buffer_partners .= '<url_photo><![CDATA['.$campaign->get_home_picture_src().']]></url_photo>' . "\n"; //TNP
		$buffer_partners .= '<date_debut_collecte>'.$campaign->begin_collecte_date('Y-m-d').'</date_debut_collecte>' . "\n"; //TNP :: YYYY-MM-DD
		$buffer_partners .= '<date_fin_collecte>'.$campaign->end_date('Y-m-d').'</date_fin_collecte>' . "\n"; //TNP :: YYYY-MM-DD
		$buffer_partners .= '<nb_jours_restants>'.$campaign->days_remaining().'</nb_jours_restants>' . "\n";
		$buffer_partners .= '<montant_recherche>'.$campaign->minimum_goal(false).'</montant_recherche>' . "\n"; //TNP :: Somme recherchée
		$buffer_partners .= '<montant_collecte>'.$campaign->current_amount(false).'</montant_collecte>' . "\n"; //TNP :: Somme collectée
		$buffer_partners .= '<nb_contributeurs>'.$campaign->backers_count().'</nb_contributeurs>' . "\n"; //TNP :: Nombre de contributeurs

		//TNP :: Succès
		$success = ( $campaign->is_funded() ) ? 'oui' : 'non';
		$buffer_partners .= '<succes>'.$success.'</succes>' . "\n";

		//Données complémentaires pour BeCrowd
		$buffer_partners .= '<type>royalty</type>' . "\n";
		$buffer_partners .= '<pourcentage>'.$campaign->percent_minimum_completed(false).'</pourcentage>' . "\n";
		$buffer_partners .= '<nb>'.$campaign->backers_count().'</nb>' . "\n";
		$buffer_partners .= '<url_video><![CDATA['.$campaign->video().']]></url_video>' . "\n";

		//Données complémentaires pour mon petit voisinage
		$buffer_partners .= '<latitude>'.$organization_obj->get_latitude().'</latitude>' . "\n";
		$buffer_partners .= '<longitude>'.$organization_obj->get_longitude().'</longitude>' . "\n";

		//Données complémentaires pour Eazinvest
		$buffer_partners .= '<periodicite><![CDATA[trimestriel]]></periodicite>' . "\n";
		$buffer_partners .= '<rendement_pourcent>'.$campaign->yield_for_investors().'</rendement_pourcent>' . "\n";

		if ( $add_list_investments ) {
			$buffer_partners .= '<investissements>' . "\n";
			$list_investments = $campaign->payments_data( TRUE );
			$campaign_status = $campaign->campaign_status();
			foreach ( $list_investments as $investment_item ) {
				$can_count_investment = FALSE;
				// En phase d'investissement, on ne compte que les investissements validés
				if ( $campaign_status == ATCF_Campaign::$campaign_status_collecte ) {
					$can_count_investment = ( $investment_item[ 'status' ] == 'publish' );

				// En phase d'évaluation
				} else {
					if ( $campaign_status == ATCF_Campaign::$campaign_status_vote ) {
						$WDGInvestment = new WDGInvestment( $investment_item[ 'ID' ] );
						$payment_key = $investment_item[ 'payment_key' ];
						$contract_status = $WDGInvestment->get_contract_status();
						// On ne compte pas les virements en attente, ni les paiements non effectués
						$can_count_investment = ( strpos( $payment_key, 'TEMP' ) == FALSE && $contract_status != WDGInvestment::$contract_status_not_validated );
					}
				}

				if ( $can_count_investment ) {
					if ( WDGOrganization::is_user_organization( $investment_item[ 'user' ] ) ) {
						$WDGOrganization = new WDGOrganization( $investment_item[ 'user' ] );
						$id_investor_xml = $investment_item[ 'user' ] . '-' . $WDGOrganization->get_api_id();
						$initials = substr( $WDGOrganization->get_name(), 0, 1 );
					} else {
						$WDGUser = new WDGUser( $investment_item[ 'user' ] );
						$id_investor_xml = $investment_item[ 'user' ] . '-' . $WDGUser->get_api_id();
						$initials = substr( $WDGUser->get_firstname(), 0, 1 ) . substr( $WDGUser->get_lastname(), 0, 1 );
					}
					$buffer_partners .= '<investissement dateheure="' .$investment_item[ 'date' ]. '" idinvestisseur="' .$id_investor_xml. '" initiales="' .$initials. '" montant="' .$investment_item[ 'amount' ]. '" />' . "\n";
				}
			}
			$buffer_partners .= '</investissements>' . "\n";
		}

		$buffer_partners .= '</projet>' . "\n";
		//*****************

		$buffer = array(
			'rss'		=> $buffer_rss,
			'partners'	=> $buffer_partners
		);

		return $buffer;
	}

	public static function get_single_project_rss_tousnosprojet_categories($campaign) {
		$buffer = array();

		$categories_list_association = array(
			'enfance-education'				=> '01',
			'dependance-et-exclusion'		=> '02',
			'sante-bien-etre-sport'			=> '03',
			'solidarite'					=> '04',
			'technologie'					=> '21',
			'commerce-service-de-proximite'	=> '22',
			'industrie'						=> '23',
			'immobilier'					=> '24',
			'agriculture-alimentation'		=> '41',
			'biodiversite'					=> '42',
			'energies-renouvelables'		=> '43',
			'transport-ville-durable'		=> '44',
			'musique'						=> '61',
			'video-cinema-photo'			=> '62',
			'multimedia-jeux'				=> '63',
			'spectacle-vivant'				=> '64',
			'mode-design'					=> '65',
			'edition-journalisme'			=> '66',
			'cuisine'						=> '67',
			'beaux-arts-et-patrimoine'		=> '68',
		);

		$campaign_categories = $campaign->get_categories_by_type( 'tousnosprojets' );
		$i = 0;
		foreach ( $campaign_categories as $campaign_category_term ) {
			if ( $i < 2 ) {
				array_push( $buffer, $categories_list_association[ $campaign_category_term->slug ] );
				$i++;
			}
		}

		return $buffer;
	}

	public static function init_quarterly_subscriptions() {
		$list_subscriptions = array();
		$list_active_subscriptions = WDGWPREST_Entity_Subscription::get_list( 'active' );
		if ( !empty( $list_active_subscriptions ) ) {
			foreach ( $list_active_subscriptions as $subscription_item ) {
				array_push( $list_subscriptions, $subscription_item->id );
			}
			WDGQueue::add_make_investments_from_subscriptions_list( $list_subscriptions );
		}
	}
}