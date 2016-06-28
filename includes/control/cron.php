<?php
/**
 * Classe de gestion des appels Cron
 */
class WDGCronActions {
	/**
	 * Initialise la liste des actions ajax
	 */
	public static function init_actions() {
//		ypcf_debug_log('WDGCronActions::init_actions');
		$wdg_current_user = WDGUser::current();
		$force_cron = filter_input(INPUT_GET, 'force_cron');
		if ( $force_cron == '1' && $wdg_current_user->is_admin() ) {
			WDGCronActions::daily_actions();
			
		} else {
			$date_now = new DateTime();
			$last_daily_call = get_option( 'last_daily_call' );
			$saved_date = new DateTime( $last_daily_call );
			
			if ($last_daily_call == FALSE || $saved_date->diff($date_now)->days >= 1) {
				update_option( 'last_daily_call', $date_now->format('Y-m-d H:i:s') );
				WDGCronActions::daily_actions();
			}
		}
	}
	
	public static function daily_actions() {
//		WDGCronActions::check_kycs();
//		WDGCronActions::check_bank_transfers();
		WDGCronActions::make_projects_rss();
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
	
	public static function check_bank_transfers() {
		$date = new DateTime();
		$date->sub( new DateInterval('P10D') );
		$transactions_list = LemonwayLib::get_transactions_wire_since( $date->getTimestamp() );
		NotificationsEmails::send_mail('emilien@wedogood.co', 'Derniers transferts', print_r($transactions_list, true), true);
	}
	
	public static function make_projects_rss() {
		$date = new DateTime();
		$current_date = $date->format('Y-m-d');
		
		
		$buffer_rss = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
		$buffer_rss .= '<rss version="2.0">' . "\n";
		$buffer_rss .= '<channel>' . "\n";
		$buffer_rss .= '<title><![CDATA[Les projets de WE DO GOOD]]></title>' . "\n";
		$buffer_rss .= '<description><![CDATA[Tous les projets en cours de collecte sur WE DO GOOD]]></description>' . "\n";
		$buffer_rss .= '<lastBuildDate>'.$date->format(DateTime::RFC822).'</lastBuildDate>' . "\n";
		$buffer_rss .= '<link>http://www.wedogood.co</link>' . "\n";
		
		$buffer_partners = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
		$buffer_partners .= '<partenaire>' . "\n";
		
		//Parcours des projets en cours de collecte
		ATCF_Campaigns::list_projects_funding();
		while (have_posts()): the_post();
			global $post;
			$campaign = atcf_get_campaign( $post );
			
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
			$buffer_partners .= '<mode_financement>OBL</mode_financement>' . "\n"; //TNP :: Mode de financement (DON, DOC, PRE, PRR, ACT, OBL)
			$buffer_partners .= '<type_porteur_projet>ENT</type_porteur_projet>' . "\n"; //TNP :: Statut du PP (ENT, ASS, PAR, COL)
			$buffer_partners .= '<qualif_ESS>non</qualif_ESS>' . "\n"; //TNP :: Qualification ESS du porteur projet
			$buffer_partners .= '<code_postal></code_postal>' . "\n"; //TNP :: Code postal TODO
			$buffer_partners .= '<ville></ville>' . "\n"; //TNP :: Ville TODO
			
			$buffer_partners .= '<titre><![CDATA['.$campaign->data->post_title.']]></titre>' . "\n"; //TNP
			$buffer_partners .= '<description><![CDATA['.html_entity_decode($campaign->summary()).']]></description>' . "\n"; //TNP
			$buffer_partners .= '<url>'.get_permalink($campaign->ID).'</url>' . "\n"; //TNP
			$buffer_partners .= '<url_photo>'.$campaign->get_home_picture_src().'</url_photo>' . "\n"; //TNP
			$buffer_partners .= '<date_debut_collecte>'.$campaign->begin_collecte_date('Y-m-d').'</date_debut_collecte>' . "\n"; //TNP :: YYYY-MM-DD 
			$buffer_partners .= '<date_fin_collecte>'.$campaign->end_date('Y-m-d').'</date_fin_collecte>' . "\n"; //TNP :: YYYY-MM-DD 
			$buffer_partners .= '<montant_recherche>'.$campaign->minimum_goal(false).'</montant_recherche>' . "\n"; //TNP :: Somme recherchée
			$buffer_partners .= '<montant_collecte>'.$campaign->current_amount(false).'</montant_collecte>' . "\n"; //TNP :: Somme collectée
			
			//Données complémentaires pour BeCrowd
			$buffer_partners .= '<type>royalty</type>' . "\n";
			$buffer_partners .= '<pourcentage>'.$campaign->percent_completed(false).'</pourcentage>' . "\n";
			$buffer_partners .= '<nb>'.$campaign->backers_count().'</nb>' . "\n";
			$buffer_partners .= '<url_video>'.$campaign->video().'</url_video>' . "\n";
		
			$buffer_partners .= '</projet>' . "\n";
			//*****************
		endwhile;
		wp_reset_query();
		
		$buffer_partners .= '</partenaire>';
		$buffer_rss .= '</channel>';
		$buffer_rss .= '</rss>';
		
		$filename = dirname ( __FILE__ ) . '/../../../../../current-projects.xml';
		$file_handle = fopen($filename, 'w');
		fwrite($file_handle, $buffer_partners);
		fclose($file_handle);
		
		$filename_rss = dirname ( __FILE__ ) . '/../../../../../rss.xml';
		$file_handle_rss = fopen($filename_rss, 'w');
		fwrite($file_handle_rss, $buffer_rss);
		fclose($file_handle_rss);
	}
}