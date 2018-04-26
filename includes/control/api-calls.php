<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe temporaire de gestion des appels de l'API (pour les données qui lui manquent)
 */
class WDGAPICalls {
	
	private $action;
	private $param;
	
	public function __construct( $action = '', $param = '' ) {
		if ( empty( $action ) ) {
			$this->action = filter_input( INPUT_GET, 'action' );
			$this->param = filter_input( INPUT_GET, 'param' );
			
		} else {
			$this->action = $action;
			$this->param = $param;
		}
		
		if ( !empty( $this->action ) ) {
			ypcf_debug_log( 'WDGAPICalls::__construct > $this->action : ' .$this->action. ' ; $this->param : ' . $this->param );
			$this->{ $this->action }( $this->param );
		}
	}
	
	private function get_projects_categories() {
		$buffer = array();
		
		$categories_types = array( 'types', 'categories', 'activities', 'partners', 'tousnosprojets' );
		foreach ( $categories_types as $category_name ) {
			$buffer[ $category_name ] = array();
			$terms_from_category = get_terms( 'download_category', array( 'slug' => $category_name, 'hide_empty' => false ) );
			$term_category_id = $terms_from_category[0]->term_id;
			$subterms_list = (array) get_terms( 'download_category', array( 'child_of' => $term_category_id, 'hierarchical' => 0, 'hide_empty' => 0 ) );
			foreach ( $subterms_list as $term_item ) {
				array_push( $buffer[ $category_name ], array( 'id' => $term_item->term_id, 'slug' => $term_item->slug, 'name' => $term_item->name ) );
			}
		}
		
		exit( json_encode( $buffer ) );
	}
	
	private function get_status_by_project( $campaign_id ) {
		if ( !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$vote_results = WDGCampaignVotes::get_results( $campaign_id );
			$buffer = array(
				'status'				=> $campaign->campaign_status(),
				'vote_count'			=> $campaign->nb_voters(),
				'vote_invest_amount'	=> $vote_results[ 'sum_invest_ready' ],
				'vote_end_date'			=> $campaign->end_vote_date(),
				'invest_count'			=> $campaign->backers_count(),
				'invest_amount'			=> $campaign->current_amount( FALSE ),
				'invest_percent'		=> $campaign->percent_minimum_completed( FALSE ),
				'invest_end_date'		=> $campaign->end_date(),
				'goal_minimum'			=> $campaign->minimum_goal(),
				'goal_maximum'			=> $campaign->goal( FALSE )
			);
			exit( json_encode( $buffer ) );
		}
	}
	
	private function get_royalties_by_project( $project_id ) {
		if ( !empty( $project_id ) ) {
			$campaign = new ATCF_Campaign( $project_id );
			$result = $campaign->get_roi_declarations();
			exit( json_encode( $result ) );
		}
	}
	
	private function get_royalties_by_user( $email ) {
		$buffer = array();
		if ( !empty( $email ) ) {
			$query_user = get_user_by( 'email', $email );
			if ( $query_user ) {
				$user = new WDGUser( $query_user->ID );
				$result = $user->get_rois();
				foreach ( $result as $roi ) {
					$roi_item = array();
					$roi_item["id"] = $roi->id;
					$roi_item["project"] = $roi->id_campaign;
					$roi_item["date"] = $roi->date_transfer;
					$roi_item["amount"] = $roi->amount;
					array_push( $buffer, $roi_item );
				}
			}
		}
		exit( json_encode( $buffer ) );
	}

/*******************************************************************************
 RECUPERATIONS STATISTIQUES
*******************************************************************************/
	private function get_projects_stats() {
		
		$buffer = array();
		
		$query_options = array(
			'numberposts'	=> -1,
			'post_type'		=> 'download',
			'post_status'	=> 'publish'
		);
		$project_list = get_posts( $query_options );
		
		$buffer[ 'total' ] = count( $project_list );
		$buffer[ 'funded_amount' ] = 0;
		$buffer[ 'royalties_amount' ] = 0;
		
		$buffer[ 'statuses' ] = array();
		$status_list = array( 'posted', 'preparing', 'vote', 'funding', 'declaring', 'declaring_late', 'funded', 'archive' );
		foreach ( $status_list as $status ) {
			$buffer[ 'statuses' ][ $status ] = array(
				'count'		=> 0,
				'percent'	=> 0
			);
		}
		
		foreach ( $project_list as $project ) {
			$campaign = new ATCF_Campaign( $project->ID );
			$campaign_status = $campaign->campaign_status();
			if ( $campaign_status == ATCF_Campaign::$campaign_status_preparing ) {
				$buffer[ 'statuses' ][ 'posted' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_validated ) {
				$buffer[ 'statuses' ][ 'preparing' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_vote ) {
				$buffer[ 'statuses' ][ 'vote' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_collecte ) {
				$buffer[ 'statuses' ][ 'funding' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_funded ) {
				$buffer[ 'statuses' ][ 'declaring' ][ 'count' ]++;
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_funded ) {
//				$current_declarations = $campaign->get_current_roi_declarations();
//				if ( !empty( $current_declarations ) ) {
//					$buffer[ 'statuses' ][ 'declaring_late' ][ 'count' ]++;
//				}
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_funded || $campaign_status == ATCF_Campaign::$campaign_status_closed ) {
				$buffer[ 'funded_amount' ] += $campaign->current_amount( false );
				$buffer[ 'statuses' ][ 'funded' ][ 'count' ]++;
//				$buffer[ 'royalties_amount' ] += $campaign->get_roi_declarations_total_roi_amount();
			}
			if ( $campaign_status == ATCF_Campaign::$campaign_status_archive ) {
				$buffer[ 'statuses' ][ 'archive' ][ 'count' ]++;
			}
		}
		
		foreach ( $status_list as $status ) {
			$buffer[ 'statuses' ][ $status ][ 'percent' ] = $buffer[ 'statuses' ][ $status ][ 'count' ] / $buffer[ 'total' ] * 100;
			$buffer[ 'statuses' ][ $status ][ 'percent' ] = round( $buffer[ 'statuses' ][ $status ][ 'percent' ] * 100 ) / 100;
		}
		
		
		exit( json_encode( $buffer ) );
		
	}
	
	private function get_users_stats() {
		
		$buffer = array();
		
		$result = count_users();
		
		$buffer[ 'total' ] = $result['total_users'];
		$buffer[ 'merchant_wallet' ] = 0;
		
		try {
			$wallet_details = LemonwayLib::wallet_get_details( 'SC' );
			if ( $wallet_details ) {
				$buffer[ 'merchant_wallet' ] = $wallet_details->BAL;
			}
		} catch (Exception $exc) {
		}
		
		exit( json_encode( $buffer ) );
		
	}

/*******************************************************************************
 EDITION DEPUIS L'API
*******************************************************************************/
	private function update_user_email( $input_email ) {
		$buffer = 'error';
		if ( !empty( $input_email ) ) {

			// On récupère l'utilisateur à modifier
			$init_email = html_entity_decode( $input_email );
			ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $init_email : ' .$init_email );
			$query_user = get_user_by( 'email', $init_email );
			if ( !empty( $query_user ) ) {

				// On vérifie que le nouvel e-mail est renseigné
				$new_email = filter_input( INPUT_POST, 'new_email' );
				ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $new_email : ' .$new_email );
				if ( !empty( $new_email ) ) {

					// On vérifie que le nouvel e-mail n'est pas déjà pris
					$find_existing_user = get_user_by( 'email', $new_email );
					if ( empty( $find_existing_user ) ) {
						wp_update_user( array ( 'ID' => $query_user->ID, 'user_email' => $new_email ) );
						$buffer = 'success';

					} else {
						ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $find_existing_user : ' .$find_existing_user->ID );
						$buffer = 'E-mail alreay in use';

					}
				}

			} else {
				$buffer = 'Did not find user with this e-mail';
			}
		}
		ypcf_debug_log( 'ypcf_check_api_calls > update_user_email > $buffer : ' .$buffer );
		exit( $buffer );
	}
	
	private function set_project_url( $campaign_id ) {
		/*ypcf_debug_log( 'ypcf_check_api_calls > set_project_url > $campaign_id : ' .$campaign_id );
		$campaign = new ATCF_Campaign( $campaign_id );
		$new_name = sanitize_text_field( filter_input( INPUT_POST, 'new_url') );
		if ( !empty( $new_name ) && $campaign->data->post_name != $new_name ) {
			$posts = get_posts( array(
				'name' => $new_name,
				'post_type' => array( 'post', 'page', 'download' )
			) );
			
			if ( $posts ) {
				$buffer = "L'URL est déjà utilisée.";

			} else {
				wp_update_post( array(
					'ID'		=> $campaign_id,
					'post_name' => $new_name
				) );
				$buffer = '1';
			}
		}
		ypcf_debug_log( 'ypcf_check_api_calls > set_project_url > $buffer : ' .$buffer );
		exit( $buffer );*/
		exit( '1' );
	}
	
	private function post_project_equitearly() {
		ypcf_debug_log( 'ypcf_check_api_calls > post_project_equitearly' );
		
		$buffer = "";
		
		$user_login = filter_input( INPUT_POST, 'user_login' );
		$user_password = filter_input( INPUT_POST, 'user_password' );
		$user_email = filter_input( INPUT_POST, 'user_email' );
		$user_firstname = filter_input( INPUT_POST, 'user_firstname' );
		$user_lastname = filter_input( INPUT_POST, 'user_lastname' );
		if ( empty( $user_login ) || empty( $user_password ) || empty( $user_email ) || empty( $user_firstname ) || empty( $user_lastname ) ) {
			$buffer = "Problème de données d'utilisateur";
			
		} else {
			
			if ( !validate_username( $user_login ) ) {
				$buffer =  __( "Les identifiants peuvent uniquement contenir des lettres sans caract&egrave;res sp&eacute;ciaux, des chiffres, ., -, ou @", 'yproject' );
			}
			if ( strlen( $user_login ) < 4 ) {
				$buffer = __( "L'identifiant doit contenir au moins 4 caract&egrave;res", 'yproject' );
			}
			if ( false !== strpos( ' ' . $user_login, '_' ) ) {
				$buffer = __( "Le caract&egrave;re _ ne peut pas &ecirc;tre utilis&eacute;.", 'yproject' );
			}
			$match = array();
			preg_match( '/[0-9]*/', $user_login, $match );
			if ( $match[0] == $user_login ) {
				$buffer = __( "Les identifiants ne peuvent pas contenir uniquement des chiffres.", 'yproject' );
			}
			if ( username_exists( $user_login ) ) {
				$buffer = __( "Cet identifiant est d&eacute;j&agrave; utilis&eacute;.", 'yproject' );
			}
			
			if ( email_exists( $user_email ) ) {
				$buffer = __( "Cette adresse e-mail est d&eacute;j&agrave; utilis&eacute;e.", 'yproject' );
			}
			if ( !is_email( $user_email ) ) {
				$buffer = __( "Cette adresse e-mail n'est pas valide.", 'yproject' );
			}
			
			if ( empty( $buffer ) ) {
				$user_id = wp_create_user( $user_login, $user_password, $user_email );
				wp_update_user( array ( 'ID' => $user_id, 'first_name' => $user_firstname ) ) ;
				wp_update_user( array ( 'ID' => $user_id, 'last_name' => $user_lastname ) ) ;
			}
		}
		
		
		if ( empty( $buffer ) ) {
			$orga_name = sanitize_text_field( filter_input( INPUT_POST, 'organization_name' ) );
			$orga_email = sanitize_text_field( filter_input( INPUT_POST, 'organization_email' ) );
			$organization_created = WDGOrganization::createSimpleOrganization( $user_id, $orga_name, $orga_email );
			if ( $organization_created != false ) {
				$orga_api_id = $organization_created->get_api_id();
				
			} else {
				$buffer = "Problème de données d'organisation";
			}
			if ( empty( $orga_api_id ) ) {
				$buffer = "Erreur récupération ID API de l'organisation";
			}
		}
		
		if ( empty( $buffer ) ) {
			$campaign_name = sanitize_text_field( filter_input( INPUT_POST, 'campaign_name' ) );
			$equitearly_investment = sanitize_text_field( filter_input( INPUT_POST, 'equitearly_investment' ) );
			$equitearly_charges = sanitize_text_field( filter_input( INPUT_POST, 'equitearly_charges' ) );
			
			$newcampaign_id = atcf_create_campaign( $user_id, $campaign_name );
			$newcampaign = atcf_get_campaign( $newcampaign_id );
			$newcampaign->set_forced_mandate( 1 );
			$newcampaign->link_organization( $orga_api_id );
			add_post_meta( $newcampaign_id, 'equitearly_investment', $equitearly_investment );
			add_post_meta( $newcampaign_id, 'equitearly_charges', $equitearly_charges );

			//Mail pour l'équipe
			NotificationsEmails::new_project_posted($newcampaign_id, $orga_name, '');
			NotificationsEmails::new_project_posted_owner($newcampaign_id, '');
		}
		
		
		ypcf_debug_log( 'ypcf_check_api_calls > post_project_equitearly > $buffer : ' .$buffer );
		exit( $buffer );
	}
	
}