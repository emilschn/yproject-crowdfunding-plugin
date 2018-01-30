<?php
/**
 * Classe de gestion des appels Cron
 */
class WDGCronActions {
	
	public static function send_notifications() {
		
		// Récupération de toutes les déclarations qui sont dues entre maintenant et dans 10 jours
		$current_date = new DateTime();
		$current_date->setTime( 0, 0, 1 );
		$date_in_10_days = new DateTime();
		$date_in_10_days->add( new DateInterval('P9D') );
		$declaration_list = WDGWPREST_Entity_Declaration::get_list_by_date( $current_date->format( 'Y-m-d' ), $date_in_10_days->format( 'Y-m-d' ) );
		if ( $declaration_list ) {
			foreach ( $declaration_list as $declaration_data ) {
				// On n'envoie des notifications que pour les déclarations qui ne sont pas commencées
				if ( $declaration_data->status == WDGROIDeclaration::$status_declaration ) {
					$date_due = new DateTime( $declaration_data->date_due );
					$date_due->setTime( 10, 30, 0 );
					if ( $date_due > $current_date ) {
						$nb_days_diff = $date_due->diff( $current_date )->days;
						$campaign = new ATCF_Campaign( FALSE, $declaration_data->id_project );
						$organization = $campaign->get_organization();
						$wdgorganization = new WDGOrganization( $organization->id );
						$wdguser_author = new WDGUser( $campaign->data->post_author );
						
						// Données qui seront transmises à SiB
						$date_due_previous_day = new DateTime( $declaration_data->date_due );
						$date_due_previous_day->sub( new DateInterval( 'P1D' ) );
						$months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
						$nb_fields = $campaign->get_turnover_per_declaration();
						$date_last_months =  new DateTime( $declaration_data->date_due );
						$date_last_months->sub( new DateInterval( 'P'.$nb_fields.'M' ) );
						$last_months_str = '';
						for ( $i = 0; $i < $nb_fields; $i++ ) {
							$last_months_str .= __( $months[ $date_last_months->format('m') - 1 ] );
							if ( $i < $nb_fields - 2 ) {
								$last_months_str .= ', ';
							}
							if ( $i == $nb_fields - 2 ) {
								$last_months_str .= ' et ';
							}
							$date_last_months->add( new DateInterval( 'P1M' ) );
						}
						$year = $date_due->format( 'Y' );
						if ( $date_due->format( 'n' ) < 4 ) {
							$year--;
						}
						$last_months_str .= ' ' . $year;
						$options = array(
							'NOM'					=> $wdguser_author->get_firstname(),
							'TROIS_DERNIERS_MOIS'	=> $last_months_str,
							'DATE_DUE'				=> $date_due->format( 'd/m/Y' ),
							'VEILLE_DATE_DUE'		=> $date_due_previous_day->format( 'd/m/Y' )
						);
						
						NotificationsAPI::declaration_to_do( $organization->email, $nb_days_diff, $wdgorganization->has_signed_mandate(), $options );
					}
				}

			}
		}
		
	}
	
	public static function make_projects_rss( $funding_project = TRUE ) {
		$date = new DateTime();
		$current_date = $date->format('Y-m-d');
		
		
		$buffer_rss = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
		$buffer_rss .= '<rss version="2.0">' . "\n";
		$buffer_rss .= '<channel>' . "\n";
		$buffer_rss .= '<title><![CDATA[Les projets de '.ATCF_CrowdFunding::get_platform_name().']]></title>' . "\n";
		$buffer_rss .= '<description><![CDATA[Tous les projets en cours de collecte sur '.ATCF_CrowdFunding::get_platform_name().']]></description>' . "\n";
		$buffer_rss .= '<lastBuildDate>'.$date->format(DateTime::RFC822).'</lastBuildDate>' . "\n";
		$buffer_rss .= '<link>' .home_url(). '</link>' . "\n";
		
		$buffer_partners = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
		$buffer_partners .= '<partenaire>' . "\n";
		
		//Parcours des projets en cours de collecte
		if ( $funding_project ) {
			ATCF_Campaign::list_projects_funding();
		} else {
			ATCF_Campaign::list_projects_funded();
		}
		
		while (have_posts()): the_post();
			global $post;
			$campaign = atcf_get_campaign( $post );
			$organization = $campaign->get_organization();
			$organization_obj = new WDGOrganization( $organization->wpref );
			
			//*****************
			//Formatage pour RSS
			$buffer_rss .= '<item>' . "\n";
			$buffer_rss .= '<title><![CDATA['.$campaign->data->post_title.']]></title>' . "\n";
			$buffer_rss .= '<description><![CDATA['.html_entity_decode($campaign->summary()).']]></description>' . "\n";
			$buffer_rss .= '<pubDate>'.$campaign->begin_collecte_date('Y-m-d').'</pubDate>' . "\n";
			$buffer_rss .= '<link>'.get_permalink($campaign->ID).'</link>' . "\n";
			$buffer_rss .= '</item>' . "\n";
			//*****************
			
			
			//*****************
			//Formatage pour partenaires
			$buffer_partners .= '<projet>' . "\n";
			
			//Toutes les données pour TNP
			$buffer_partners .= '<reference_partenaire>099</reference_partenaire>' . "\n"; //TNP :: (010,011 => 039 pour le DON, 040,041 => 069 pour le prêt, 070, 071 => 099 pour l'investissement SANS rentrer dans la subdivision)
			$buffer_partners .= '<date_export>'.$current_date.'</date_export>' . "\n"; //TNP :: YYYY-MM-DD
			$buffer_partners .= '<reference_projet>'.$campaign->ID.'</reference_projet>' . "\n"; //TNP :: ref unique interne
			
			//TNP :: impacts : min 1, max 2
			$buffer_partners .= '<impact_social>non</impact_social>' . "\n";
			$buffer_partners .= '<impact_environnemental>non</impact_environnemental>' . "\n";
			$buffer_partners .= '<impact_culturel>non</impact_culturel>' . "\n";
			$buffer_partners .= '<impact_eco>oui</impact_eco>' . "\n";
			
			$buffer_partners .= '<mots_cles_nomenclature_operateur></mots_cles_nomenclature_operateur>' . "\n"; //TNP :: Mots-clés TODO
			$buffer_partners .= '<mode_financement>ROY</mode_financement>' . "\n"; //TNP :: Mode de financement (DON, DOC, PRE, PRR, ACT, OBL) - invention ROY
			$buffer_partners .= '<type_porteur_projet>ENT</type_porteur_projet>' . "\n"; //TNP :: Statut du PP (ENT, ASS, PAR, COL)
			$buffer_partners .= '<qualif_ESS>non</qualif_ESS>' . "\n"; //TNP :: Qualification ESS du porteur projet
			$buffer_partners .= '<code_postal>' .$organization_obj->get_postal_code(). '</code_postal>' . "\n";
			$buffer_partners .= '<ville>' .$organization_obj->get_city(). '</ville>' . "\n";
			
			$buffer_partners .= '<titre><![CDATA['.$campaign->data->post_title.']]></titre>' . "\n"; //TNP
			$buffer_partners .= '<description><![CDATA['.html_entity_decode($campaign->summary()).']]></description>' . "\n"; //TNP
			$description_complete = html_entity_decode( $campaign->description() );
			$buffer_partners .= '<description_complete><![CDATA['.apply_filters( 'the_content', $description_complete ).']]></description_complete>' . "\n"; //Info durable
			$buffer_partners .= '<url>'.get_permalink($campaign->ID).'</url>' . "\n"; //TNP
			$buffer_partners .= '<url_photo>'.$campaign->get_home_picture_src().'</url_photo>' . "\n"; //TNP
			$buffer_partners .= '<date_debut_collecte>'.$campaign->begin_collecte_date('Y-m-d').'</date_debut_collecte>' . "\n"; //TNP :: YYYY-MM-DD 
			$buffer_partners .= '<date_fin_collecte>'.$campaign->end_date('Y-m-d').'</date_fin_collecte>' . "\n"; //TNP :: YYYY-MM-DD 
			$buffer_partners .= '<nb_jours_restants>'.$campaign->days_remaining().'</nb_jours_restants>' . "\n";
			$buffer_partners .= '<montant_recherche>'.$campaign->minimum_goal(false).'</montant_recherche>' . "\n"; //TNP :: Somme recherchée
			$buffer_partners .= '<montant_collecte>'.$campaign->current_amount(false).'</montant_collecte>' . "\n"; //TNP :: Somme collectée
			
			//Données complémentaires pour BeCrowd
			$buffer_partners .= '<type>royalty</type>' . "\n";
			$buffer_partners .= '<pourcentage>'.$campaign->percent_completed(false).'</pourcentage>' . "\n";
			$buffer_partners .= '<nb>'.$campaign->backers_count().'</nb>' . "\n";
			$buffer_partners .= '<url_video><![CDATA['.$campaign->video().']]></url_video>' . "\n";

			//Données complémentaires pour mon petit voisinage
			$buffer_partners .= '<latitude>'.$organization_obj->get_latitude().'</latitude>' . "\n";
			$buffer_partners .= '<longitude>'.$organization_obj->get_longitude().'</longitude>' . "\n";

		
			$buffer_partners .= '</projet>' . "\n";
			//*****************
		endwhile;
		wp_reset_query();
		
		$buffer_partners .= '</partenaire>';
		$buffer_rss .= '</channel>';
		$buffer_rss .= '</rss>';
		
		$filename = dirname ( __FILE__ ) . '/../../../../../current-projects.xml';
		if ( !$funding_project ) {
			$filename = dirname ( __FILE__ ) . '/../../../../../finished-projects.xml';
		}
		$file_handle = fopen($filename, 'w');
		fwrite($file_handle, $buffer_partners);
		fclose($file_handle);
		
		$filename_rss = dirname ( __FILE__ ) . '/../../../../../rss.xml';
		$file_handle_rss = fopen($filename_rss, 'w');
		fwrite($file_handle_rss, $buffer_rss);
		fclose($file_handle_rss);
	}
}