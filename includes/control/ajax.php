<?php
/**
 * Classe de gestion des appels Ajax
 * TODO : centraliser ici
 */
class WDGAjaxActions {
	private static $class_name = 'WDGAjaxActions';
	
	private static $class_to_filename = array(
		'WDG_Form_Vote'			=> 'vote.php',
		'WDG_Form_User_Details' => 'user-details.php',
		'WDG_Form_Dashboard_Add_Check' => 'dashboard-add-check.php'
	);
    
	/**
	 * Initialise la liste des actions ajax
	 */
	public static function init_actions() {
		WDGAjaxActions::add_action_by_class( 'WDG_Form_Vote' );
		WDGAjaxActions::add_action_by_class( 'WDG_Form_User_Details' );
		WDGAjaxActions::add_action_by_class( 'WDG_Form_Dashboard_Add_Check' );
		
		WDGAjaxActions::add_action('get_connect_to_facebook_url');
		WDGAjaxActions::add_action('get_searchable_projects_list');
		
		WDGAjaxActions::add_action('display_user_investments');
		WDGAjaxActions::add_action('display_roi_user_list');
		WDGAjaxActions::add_action('show_project_money_flow');
		WDGAjaxActions::add_action('check_invest_input');
		WDGAjaxActions::add_action('save_user_docs');
		WDGAjaxActions::add_action('save_image_head');
		WDGAjaxActions::add_action('save_image_url_video');

        //Dashboard
		WDGAjaxActions::add_action('save_project_infos');
		WDGAjaxActions::add_action('save_project_funding');
		WDGAjaxActions::add_action('save_project_communication');
		WDGAjaxActions::add_action('save_project_contract_modification');
		WDGAjaxActions::add_action('save_project_organization');
		WDGAjaxActions::add_action('save_new_organization');
		WDGAjaxActions::add_action('save_edit_organization');
		WDGAjaxActions::add_action('save_project_campaigntab');
		WDGAjaxActions::add_action('save_project_status');
		WDGAjaxActions::add_action('save_project_force_mandate');
		WDGAjaxActions::add_action('save_project_declaration_info');
		WDGAjaxActions::add_action('save_user_infos_dashboard');
		WDGAjaxActions::add_action('save_declaration_adjustment');
		WDGAjaxActions::add_action('pay_with_mandate');
        WDGAjaxActions::add_action('create_contacts_table');
		WDGAjaxActions::add_action('preview_mail_message');
		WDGAjaxActions::add_action('search_user_by_email');
		WDGAjaxActions::add_action('get_current_investment_signature_status');
		WDGAjaxActions::add_action('apply_draft_data');
		WDGAjaxActions::add_action('create_investment_from_draft');
		WDGAjaxActions::add_action('proceed_roi_transfers');
		WDGAjaxActions::add_action('conclude_project');
		WDGAjaxActions::add_action('try_lock_project_edition');
		WDGAjaxActions::add_action('keep_lock_project_edition');
		WDGAjaxActions::add_action('delete_lock_project_edition');
	}
	
	/**
	 * GÃ¨re de maniÃ¨re automatisée les classes de formulaires (standardisées)
	 * @param string $class_name
	 */
	public static function add_action_by_class( $class_name ) {
		require_once( 'forms/' . WDGAjaxActions::$class_to_filename[ $class_name ] );
		$form_object = new $class_name();
		add_action( 'wp_ajax_' .$form_object->getFormID(), array( $form_object, 'postFormAjax' ) );
		add_action( 'wp_ajax_nopriv_' .$form_object->getFormID(), array( $form_object, 'postFormAjax' ) );
	}
    
	/**
	 * Ajoute une action WordPress Ã  exécuter en Ajax
	 * @param string $action_name
	 */
	public static function add_action($action_name) {
		add_action('wp_ajax_' . $action_name, array(WDGAjaxActions::$class_name, $action_name));
		add_action('wp_ajax_nopriv_' . $action_name, array(WDGAjaxActions::$class_name, $action_name));
	}
	
	/**
	 * Retourne une URL de redirection vers la connexion Facebook
	 */
	public static function get_connect_to_facebook_url() {
		ypcf_session_start();
		$posted_redirect = filter_input( INPUT_POST, 'redirect' );
//		ypcf_debug_log( 'AJAX::get_connect_to_facebook_url > $posted_redirect : ' . $posted_redirect );
		$_SESSION[ 'login-fb-referer' ] = ( !empty( $posted_redirect ) ) ? $posted_redirect : wp_get_referer();
//		ypcf_debug_log( 'AJAX::get_connect_to_facebook_url > login-fb-referer : ' . $_SESSION[ 'login-fb-referer' ] );
		
		$fb = new Facebook\Facebook([
			'app_id' => YP_FB_APP_ID,
			'app_secret' => YP_FB_SECRET,
			'default_graph_version' => 'v2.8',
		]);
		$helper = $fb->getRedirectLoginHelper();
		$permissions = ['email'];
		$loginUrl = $helper->getLoginUrl( home_url( '/connexion/?fbcallback=1' ) , $permissions);
		echo $loginUrl;
		
		exit();
	}
	
	/**
	 * Retourne la liste des projets qui peuvent Ãªtre recherchés
	 */
	public static function get_searchable_projects_list() {
		ypcf_debug_log( 'get_searchable_projects_list' );
		$WDG_cache_plugin = new WDG_Cache_Plugin();
		
		$projects_searchable = array();
		$cache_projects_searchable = $WDG_cache_plugin->get_cache( 'ATCF_Campaign::list_projects_searchable_1', 3 );
		if ( $cache_projects_searchable !== FALSE ) {
			$projects_searchable = json_decode( $cache_projects_searchable );
			$index = 2;
			$cache_projects_searchable = $WDG_cache_plugin->get_cache( 'ATCF_Campaign::list_projects_searchable_' .$index, 3 );
			while ( $cache_projects_searchable != FALSE ) {
				$temp_projects_searchable = json_decode( $cache_projects_searchable );
				$projects_searchable = array_merge( $projects_searchable, $temp_projects_searchable );
				$index++;
				$cache_projects_searchable = $WDG_cache_plugin->get_cache( 'ATCF_Campaign::list_projects_searchable_' .$index, 3 );
			}
			$buffer = json_encode( $projects_searchable );
			
		} else {
			$projects_searchable = ATCF_Campaign::list_projects_searchable();
			$count_projects_searchable = count( $projects_searchable );
			$index = 1;
			$list_to_cache = array();
			for ( $i = 0; $i < $count_projects_searchable; $i++ ) {
				array_push( $list_to_cache, $projects_searchable[ $i ] );
				if ( $i % 10 == 0 ) {
					$projects_searchable_encoded = json_encode( $list_to_cache );
					$WDG_cache_plugin->set_cache( 'ATCF_Campaign::list_projects_searchable_' .$index, $projects_searchable_encoded, 60 * 60 * 3, 3 ); //MAJ 3h
					$index++;
					$list_to_cache = array();
				}
			}
			// Sauvegarde des restants
			$projects_searchable_encoded = json_encode( $list_to_cache );
			$WDG_cache_plugin->set_cache( 'ATCF_Campaign::list_projects_searchable_' .$index, $projects_searchable_encoded, 60 * 60 * 3, 3 ); //MAJ 3h
			$buffer = json_encode( $projects_searchable );
		}
		
		echo $buffer;
		exit();
	}
	
	/**
	 * Affiche la liste des investissements d'un utilisateur
	 */
	public static function display_user_investments() {
		$buffer = array();
		
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$user_type = filter_input( INPUT_POST, 'user_type' );
		$is_authentified = FALSE;
		if ( $user_type == 'user' ) {
			$WDGUserEntity = new WDGUser( $user_id );
			$is_authentified = $WDGUserEntity->is_lemonway_registered();
		} else {
			$WDGUserEntity = new WDGOrganization( $user_id );
			$is_authentified = $WDGUserEntity->is_registered_lemonway_wallet();
		}
		$investment_contracts = WDGWPREST_Entity_User::get_investment_contracts( $WDGUserEntity->get_api_id() );
		
		$today_datetime = new DateTime();
		$payment_status = array( 'publish', 'completed', 'pending' );
		$purchases = edd_get_users_purchases( $user_id, -1, false, $payment_status );
		
		// Ajout des contrats qui n'ont pas été liés à un investissement (post-campagne)
		if ( !empty( $investment_contracts ) ) {
			foreach ( $investment_contracts as $investment_contract ) {
				if ( $investment_contract->subscription_id == 0 ) {
					$investment = array(
						'investment_contract'	=> $investment_contract
					);
					array_push( $purchases, $investment );
				}
			}
		}
		
		foreach ( $purchases as $purchase_post ){
			$first_investment_contract = FALSE;
			$payment_key = FALSE;
			if ( is_array( $purchase_post ) && isset( $purchase_post[ 'investment_contract' ] ) ) {
				$first_investment_contract = $purchase_post[ 'investment_contract' ];
				$purchase_status = 'publish';
				$campaign = new ATCF_Campaign( FALSE, $first_investment_contract->project_id );
				$campaign_id = $campaign->ID;
				$payment_amount = $first_investment_contract->subscription_amount;
				$purchase_date = date_i18n( get_option('date_format'), strtotime( $first_investment_contract->subscription_date ) );
				$roi_list = $WDGUserEntity->get_royalties_by_investment_contract_id( $first_investment_contract->id, FALSE );
				$purchase_id = $first_investment_contract->subscription_id;
				
			} else {
				$purchase_id = $purchase_post->ID;
				$purchase_status = $purchase_post->post_status;
				$downloads = edd_get_payment_meta_downloads( $purchase_id );
				if ( !is_array( $downloads[ 0 ] ) ){
					$campaign_id = $downloads[0];
					$campaign = atcf_get_campaign( $campaign_id );
					if ( $campaign->campaign_status() != ATCF_Campaign::$campaign_status_vote && $campaign->campaign_status() != ATCF_Campaign::$campaign_status_collecte && $purchase_status == 'pending' ) {
						continue;
					}
				}
				$payment_amount = edd_get_payment_amount( $purchase_id );
				$purchase_date = date_i18n( get_option('date_format'), strtotime( get_post_field( 'post_date', $purchase_id ) ) );
				
				if ( !empty( $investment_contracts ) ) {
					foreach ( $investment_contracts as $investment_contract ) {
						if ( $investment_contract->subscription_id == $purchase_id ) {
							$first_investment_contract = $investment_contract;
						}
					}
				}
				$roi_list = $WDGUserEntity->get_royalties_by_investment_id( $purchase_id, FALSE );
			}
				
			if ( !empty( $campaign ) ) {
				
				// Récupération de la liste des contrats passés entre la levée de fonds et l'investisseur
				$exp = dirname( __FILE__ ). '/../pdf_files/' .$campaign_id. '_' .$user_id. '_*.pdf';
				$files = glob( $exp );
				
				if ( !isset( $buffer[ $campaign_id ] ) ) {
					$buffer[ $campaign_id ] = array();
					$buffer[ $campaign_id ][ 'name' ] = $campaign->data->post_title;
					$buffer[ $campaign_id ][ 'status' ] = utf8_encode( $campaign->campaign_status() );
					$buffer[ $campaign_id ][ 'amount' ] = utf8_encode( $campaign->current_amount( false ) );
					$contract_start_date = new DateTime( $campaign->contract_start_date() );
					$buffer[ $campaign_id ][ 'start_date' ] = utf8_encode( $contract_start_date->format( 'd/m/Y' ) );
					$buffer[ $campaign_id ][ 'funding_duration' ] = utf8_encode( $campaign->funding_duration() );
					$buffer[ $campaign_id ][ 'roi_percent' ] = utf8_encode( $campaign->roi_percent() );
					$buffer[ $campaign_id ][ 'items' ] = array();
				}
				
				$investor_proportion = $payment_amount / $buffer[ $campaign_id ][ 'amount' ];
				$roi_percent_full = ( $buffer[ $campaign_id ][ 'roi_percent' ] * $investor_proportion );
				$roi_percent_display = round( $roi_percent_full * 10000 ) / 10000;
				$roi_amount = 0;
				foreach ( $roi_list as $roi_item ) {
					if ( $roi_item->status != WDGROI::$status_canceled ) {
						$roi_amount += $roi_item->amount;
					}
				}
				
				$investment_item = array();
				$investment_item[ 'date' ] = $purchase_date;
				$investment_item[ 'amount' ] = utf8_encode( $payment_amount );
				$investment_item[ 'status' ] = utf8_encode( $purchase_status );
				$investment_item[ 'status_str' ] = '-';
				
				if ( $purchase_status == 'pending' ) {
					$WDGInvestment = new WDGInvestment( $purchase_post->ID );
					$payment_key = $WDGInvestment->get_payment_key();
					if ( strpos( $payment_key, 'wire_' ) !== FALSE || $payment_key == 'check' ) {
						$investment_item[ 'status_str' ] = __( "En attente de paiement", 'yproject' );
					} elseif ( $WDGInvestment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated ) {
						$investment_item[ 'status_str' ] = __( "A valider", 'yproject' );
					}
					
				} elseif ( $purchase_status == 'publish' ) {
					if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
						$investment_item[ 'status_str' ] = __( "Valid&eacute;", 'yproject' );
						
					} elseif ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_archive ) {
						$investment_item[ 'status_str' ] = __( "Annul&eacute;", 'yproject' );
						$date_end = new DateTime( $campaign->end_date() );
						$date_end->add( new DateInterval( 'P15D' ) );
						if ( $today_datetime < $date_end ) {
							$investment_item[ 'status_str' ] = __( "En suspend", 'yproject' );
						}
						
					} elseif ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded ) {
						$investment_item[ 'status_str' ] = __( "Versements &agrave; venir", 'yproject' );
						$date_first_payement = new DateTime( $campaign->first_payment_date() );
						if ( $today_datetime > $date_first_payement ) {
							$investment_item[ 'status_str' ] = __( "Versements en cours", 'yproject' );
						}
					
						if ( !empty( $first_investment_contract ) && $first_investment_contract->status == 'canceled' ) {
							$investment_item[ 'status' ] = 'canceled';
							$investment_item[ 'status_str' ] = __( "Versements termin&eacute;s", 'yproject' );
						}
					}
				}
				
				
				
				$investment_item[ 'conclude-investment-url' ] = '';
				if ( $purchase_status == 'pending' && $is_authentified ) {
					$WDGInvestment = new WDGInvestment( $purchase_post->ID );
					if ( $WDGInvestment->get_contract_status() == WDGInvestment::$contract_status_not_validated ) {
						$investment_item[ 'conclude-investment-url' ] = home_url( '/investir/?init_with_id=' .$purchase_post->ID. '&campaign_id=' .$campaign_id );
					}
				}
				$investment_item[ 'roi_percent' ] = utf8_encode( $roi_percent_display );
				$investment_item[ 'roi_amount' ] = utf8_encode( round( $roi_amount, 2 ) );
				$investment_item[ 'roi_return' ] = utf8_encode( round( $investment_item[ 'roi_amount' ] / $payment_amount * 100 ) );
				
				// Fichier de contrat
				$contract_index = count( $buffer[ $campaign_id ][ 'items' ] );
				$test_file_name = dirname( __FILE__ ). '/../../files/contracts/campaigns/' .$campaign_id. '-' .$campaign->get_url(). '/' .$purchase_id. '.pdf';
				if ( file_exists( $test_file_name ) ) {
					$contract_index++;
					$investment_item[ 'contract_file_path' ] = home_url( '/wp-content/plugins/appthemer-crowdfunding/files/contracts/campaigns/' .$campaign_id. '-' .$campaign->get_url(). '/' .$purchase_id. '.pdf' );
					$download_filename = __( "contrat-investissement-", 'yproject' ) .$campaign->data->post_name. '-'  .$contract_index. '.pdf';
					$investment_item[ 'contract_file_name' ] = $download_filename;
					
				} elseif ( count( $files ) ) {
					$filelist_extract = explode( '/', $files[ $contract_index ] );
					$contract_filename = $filelist_extract[ count( $filelist_extract ) - 1 ];
					$contract_index++;
					$investment_item[ 'contract_file_path' ] = home_url( '/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/' . $contract_filename );
					$download_filename = __( "contrat-investissement-", 'yproject' ) .$campaign->data->post_name. '-'  .$contract_index. '.pdf';
					$investment_item[ 'contract_file_name' ] = $download_filename;
					
				} else {
					$investment_item[ 'contract_file_path' ] = '';
					$investment_item[ 'contract_file_name' ] = '';
				}
				
				
				//*****
				// Echéancier de royalties
				
				// Création du tableau des prévisionnels par année
				$investment_item[ 'rois_by_year' ] = array();
				$year_end_dates = array();
				$estimated_turnover_list = $campaign->estimated_turnover();
				if ( !empty( $estimated_turnover_list ) ){
					
					// On démarre de la date de démarrage du contrat
					$contract_start_date = new DateTime( $campaign->contract_start_date() );
					$contract_start_date->setDate( $contract_start_date->format( 'Y' ), $contract_start_date->format( 'm' ), 21 );
					
					foreach ( $estimated_turnover_list as $key => $turnover ) {
						$year_item = array(
							'estimated_rois'	=> YPUIHelpers::display_number( round( $turnover * $roi_percent_full / 100 ), TRUE ) . ' &euro;',
							'amount_rois_nb'	=> 0,
							'amount_rois'		=> '0 &euro;',
							'roi_items'			=> array()
						);
						array_push( $investment_item[ 'rois_by_year' ], $year_item );
						
					
						// Pour trouver toutes les échéances qui ont lieu sur une année, on avance au 21, et d'une année
						$contract_start_date->add( new DateInterval( 'P1Y' ) );
						$temp_date = new DateTime();
						$temp_date->setDate( $contract_start_date->format( 'Y' ), $contract_start_date->format( 'm' ), $contract_start_date->format( 'd' ) );
						array_push( $year_end_dates, $temp_date );
					}
					
				}
				
				
				// - Déclarations de royalties liées à la campagne
				$campaign_roi_list = WDGROIDeclaration::get_list_by_campaign_id( $campaign_id );
				if ( !empty( $campaign_roi_list ) ) {
					foreach ( $campaign_roi_list as $roi_declaration ) {
						
						// On détermine sur quelle année ça se situe
						$current_year_index = 0;
						$decla_datetime = new DateTime( $roi_declaration->date_due );
						
						foreach ( $year_end_dates as $year_end_date ) {
							if ( $decla_datetime < $year_end_date ) {
								break;
							}
							
							$current_year_index++;
						}
						// On a dépassé les années prévues par le prévisionnel, on en rajoute une au tableau
						if ( !isset( $investment_item[ 'rois_by_year' ][ $current_year_index ] ) ) {
							$year_item = array(
								'estimated_rois'	=> '-',
								'amount_rois_nb'	=> 0,
								'amount_rois'		=> '0 &euro;',
								'roi_items'			=> array()
							);
							array_push( $investment_item[ 'rois_by_year' ], $year_item );
						
							$contract_start_date->add( new DateInterval( 'P1Y' ) );
							$temp_date = new DateTime();
							$temp_date->setDate( $contract_start_date->format( 'Y' ), $contract_start_date->format( 'm' ), $contract_start_date->format( 'd' ) );
							array_push( $year_end_dates, $temp_date );
						}

						// Initialisation de la ligne avec les infos de la déclaration
						$roi_item = array(
							'date_db'		=> $roi_declaration->date_due,
							'date'			=> date_i18n( 'F Y', strtotime( $roi_declaration->date_due ) ),
							'amount'		=> '0 &euro;',
							'status'		=> $roi_declaration->status,
							'status_str'	=> ''
						);
						switch ( $roi_declaration->status ) {
							case WDGROIDeclaration::$status_declaration:
								if ( $decla_datetime < $today_datetime ) {
									$roi_item[ 'status' ] = 'late';
									$roi_item[ 'status_str' ] = __( "En retard", 'yproject' );
								} else {
									$roi_item[ 'status' ] = 'upcoming';
									$roi_item[ 'status_str' ] = __( "A venir", 'yproject' );
								}
								break;
							case WDGROIDeclaration::$status_finished:
								// Rien
								break;
							case WDGROIDeclaration::$status_failed:
								$roi_item[ 'status_str' ] = __( "En d&eacute;faut", 'yproject' );
								break;
							default:
								$roi_item[ 'status' ] = 'upcoming';
								$roi_item[ 'status_str' ] = __( "A venir", 'yproject' );
								break;
						}
						
						if ( $roi_item[ 'status' ] != 'upcoming' || empty( $first_investment_contract ) || $first_investment_contract->status != 'canceled' ) {
							// Si il y a eu un versement de royalties, on récupère les infos du versement
							if ( $roi_item[ 'status' ] != 'upcoming' && !empty( $roi_list ) ) {
								foreach ( $roi_list as $roi ) {
									if ( $roi->id_declaration == $roi_declaration->id && $roi->status != WDGROI::$status_canceled ) {
										$investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois_nb' ] += $roi->amount;
										$investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois' ] = YPUIHelpers::display_number( $investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois_nb' ], TRUE ) . ' &euro;';
										$roi_item[ 'amount' ] = YPUIHelpers::display_number( $roi->amount, TRUE ) . ' &euro;';
									}
								}
							}
							
							array_push( $investment_item[ 'rois_by_year' ][ $current_year_index ][ 'roi_items' ], $roi_item );
						
							// A optimiser : ne pas trier à chaque fois qu'on ajoute, mais plutôt à la fin...
							usort( $investment_item[ 'rois_by_year' ][ $current_year_index ][ 'roi_items' ], function ( $item1, $item2 ) {
								$item1_date = new DateTime( $item1[ 'date_db' ] );
								$item2_date = new DateTime( $item2[ 'date_db' ] );
								return ( $item1_date > $item2_date );
							} );
						}
					}
				}
				//*****
				
				
				// Ajout au tableau de retour
				array_push( $buffer[ $campaign_id ][ 'items' ], $investment_item );
			}
		}
		
		echo json_encode( $buffer );
		exit();
	}
    
	/**
	 * Affiche la liste des utilisateurs d'un projet qui doivent récupérer de l'argent de leur investissement
	 */
	public static function display_roi_user_list() {
		$wdgcurrent_user = WDGUser::current();
		if ($wdgcurrent_user->is_admin()) {
		    //Récupération des éléments Ã  traiter
		    $declaration_id = filter_input(INPUT_POST, 'roideclaration_id');
			$declaration = new WDGROIDeclaration($declaration_id);
		    $campaign = new ATCF_Campaign( FALSE, $declaration->id_campaign );
			
			// Si il n'y a pas assez sur le wallet, on bloque
			$roi_amount = $declaration->get_amount_with_adjustment();
			$organization = $campaign->get_organization();
			$WDGOrganization = new WDGOrganization( $organization->wpref );
			if ( $WDGOrganization->get_lemonway_balance( 'royalties' ) < $roi_amount ) {
				echo '0';
				exit();
			}
			
		    $total_amount = 0;
		    $total_roi = 0;
		    $total_fees = 0;
		    $investments_list = $campaign->roi_payments_data( $declaration );
		    foreach ($investments_list as $investment_item) {
			    $total_amount += $investment_item['amount'];
			    $total_fees += $investment_item['roi_fees'];
			    $total_roi += $investment_item['roi_amount']; 
			    $user_data = get_userdata($investment_item['user']);
			    //Affichage utilisateur
				?>
			    <tr>
					<td><?php echo $user_data->first_name.' '.$user_data->last_name; ?></td>
					<td><?php echo $investment_item['amount']; ?> &euro;</td>
					<td><?php echo $investment_item['roi_amount']; ?> &euro;</td>
					<td><?php echo $investment_item['roi_fees']; ?> &euro;</td>
				</tr>
				<?php
		    }

		    //Affichage total
			?>
		    <tr>
				<td><strong>Total</strong></td>
				<td><?php echo $total_amount; ?> &euro;</td>
				<td><?php echo $total_roi; ?> &euro;</td>
				<td><?php echo $total_fees; ?> &euro;</td>
			</tr>
			<?php
		}
		exit();
	}
	
	/**
	 * Affiche le tableau de flux monétaires d'un projet
	 */
	public static function show_project_money_flow() {
		if (current_user_can('manage_options')) {
			//Récupération des éléments Ã  traiter
			$campaign_id = filter_input(INPUT_POST, 'campaign_id');
			$campaign_post = get_post($campaign_id);
			$campaign = atcf_get_campaign($campaign_post);
			exit();
		}
	}
	
	/**
	 * Vérifie le passage Ã  l'étape suivante pour les utilisateurs lors de l'investissement
	 */
	public static function check_invest_input() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$invest_type = filter_input(INPUT_POST, 'invest_type');
		$WDGuser_current = WDGUser::current();
		
		//Dans tous les cas, vérifie que l'utilisateur a rempli ses infos pour investir
		if (!$WDGuser_current->has_filled_invest_infos( $campaign->funding_type() )) {
			global $user_can_invest_errors;
			$return_values = array(
				"response" => "edit_user",
				"errors" => $user_can_invest_errors,
				"firstname" => $WDGuser_current->get_firstname(),
				"lastname" => $WDGuser_current->get_lastname(),
				"email" => $WDGuser_current->get_email(),
				"nationality" => $WDGuser_current->get_nationality(),
				"birthday_day" => $WDGuser_current->get_birthday_day(),
				"birthday_month" => $WDGuser_current->get_birthday_month(),
				"birthday_year" => $WDGuser_current->get_birthday_year(),
				"address" => $WDGuser_current->get_address(),
				"postal_code" => $WDGuser_current->get_postal_code(),
				"city" => $WDGuser_current->get_city(),
				"country" => $WDGuser_current->get_country(),
				"birthplace" => $WDGuser_current->get_birthplace(),
				"gender" => $WDGuser_current->get_gender(),
			);
			echo json_encode($return_values);
			exit();
		}
		
		//Vérifie si on crée une organisation
		if ($invest_type == "new_organization") {
			$return_values = array(
				"response" => "new_organization",
				"errors" => array()
			);
			echo json_encode($return_values);
			exit();
			
		//Vérifie si on veut investir en tant qu'organisation (différent de user)
		} else if ($invest_type != "user") {
			//Vérifie si les informations de l'organisation sont bien remplies
			global $organization_can_invest_errors;
			$organization = new WDGOrganization($invest_type);
			if (!$organization->has_filled_invest_infos()) {
				$return_values = array(
					"response" => "edit_organization",
					"errors" => $organization_can_invest_errors,
					"org_name" => $organization->get_name(),
					"org_email" => $organization->get_email(),
					
					"org_legalform" => $organization->get_legalform(),
					"org_idnumber" => $organization->get_idnumber(),
					"org_rcs" => $organization->get_rcs(),
					"org_capital" => $organization->get_capital(),
					"org_ape" => $organization->get_ape(),
					"org_vat" => $organization->get_vat(),
					"org_fiscal_year_end_month" => $organization->get_fiscal_year_end_month(),
					"org_address" => $organization->get_address(),
					"org_postal_code" => $organization->get_postal_code(),
					"org_city" => $organization->get_city(),
					"org_nationality" => $organization->get_nationality()
				);
				echo json_encode($return_values);
				exit();
			}
		}
	}
	
	/**
	 * Enregistre les documents KYC liés Ã  l'utilisateur
	 */
	public static function save_user_docs() {
		ypcf_session_start();
		$user_kyc_errors = array();
		$WDGuser_current = WDGUser::current();
		$user_id = $WDGuser_current->wp_user->ID;
		$owner_type = WDGKYCFile::$owner_user;
		$documents_list = array(
			'user_doc_id'		=> WDGKYCFile::$type_id,
			'user_doc_home'		=> WDGKYCFile::$type_home
		);
		
		if ( $_SESSION['redirect_current_invest_type'] != '' && $_SESSION['redirect_current_invest_type'] != 'user' ) {
			$invest_type = $_SESSION['redirect_current_invest_type'];
			$organization = new WDGOrganization($invest_type);
			$user_id = $organization->get_wpref();
			$owner_type = WDGKYCFile::$owner_organization;
			$documents_list = array(
				'org_doc_id'		=> WDGKYCFile::$type_id,
				'org_doc_home'		=> WDGKYCFile::$type_home,
				'org_doc_kbis'		=> WDGKYCFile::$type_kbis,
				'org_doc_status'	=> WDGKYCFile::$type_status
			);
		}
		
		foreach ($documents_list as $document_key => $document_type) {
			if ( isset( $_FILES[$document_key]['tmp_name'] ) && !empty( $_FILES[$document_key]['tmp_name'] ) ) {
				$result = WDGKYCFile::add_file( $document_type, $user_id, $owner_type, $_FILES[$document_key] );
				if ($result == 'ext') {
					array_push($user_kyc_errors, __("Le format de fichier n'est pas accept&eacute;.", 'yproject'));
				} else if ($result == 'size') {
					array_push($user_kyc_errors, __("Le fichier est trop lourd.", 'yproject'));
				}
			} else {
				array_push($user_kyc_errors, __("Le fichier n'a pas &eacute;t&eacute; renseign&eacute;.", 'yproject'));
			}
		}
		
		if ( $_SESSION['redirect_current_invest_type'] == '' || $_SESSION['redirect_current_invest_type'] == 'user' ) {
			if (!$WDGuser_current->has_sent_all_documents()) {
				$return_values = array(
					"response" => "kyc",
					"errors" => $user_kyc_errors
				);
				echo json_encode($return_values);
				exit();

			} else {
				$WDGuser_current->send_kyc();
			}
		} else {
			if (!$organization->has_sent_all_documents()) {
				$return_values = array(
					"response" => "kyc",
					"errors" => $user_kyc_errors
				);
				echo json_encode($return_values);
				exit();

			} else {
				$organization->send_kyc();
			}
		}
	}


	/**
	 * Enregistre l'image head
	 */
	public static function save_image_head() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$image_header =  $_FILES['image_header'];
		
		WDGFormProjects::edit_image_banniere($image_header, $campaign_id);
		
		exit();
	}
	
	

	/**
	 * Enregistre la petite image et/ou url de la vidéo
	 */
	public static function save_image_url_video() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$url_video = filter_input(INPUT_POST, 'url_video');

		$image = $_FILES[ 'image_video_zone' ];
		if(empty($url_video)){
			$campaign = new ATCF_Campaign($campaign_id);
			if($campaign->video() != '') {
				$url_video = $campaign->video();
			}
		}
		echo WDGFormProjects::edit_image_url_video($image, $url_video, $campaign_id);

		exit();
	}
		
	/**
	 * Enregistre les informations générales du projet
	 */
	public static function save_project_infos(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$current_wdg_user = WDGUser::current();
		$errors = array();
		$success = array();


		//Titre du projet
		$title = sanitize_text_field(filter_input(INPUT_POST,'new_project_name'));
		if (!empty($title)) {
			$return = wp_update_post(array(
				'ID' => $campaign_id,
				'post_title' => $title
			));
			if ($return != $campaign_id){
				$errors["new_project_name"]="Le nouveau nom du projet n'est pas valide";
			} else {
				$success["new_project_name"]=1;
			}
		} else {
			$errors["new_project_name"].="Le nom du projet ne peut pas &ecirc;tre vide";
		}

		//Résumé backoffice du projet
		$backoffice_summary = (filter_input(INPUT_POST,'new_backoffice_summary'));
		if (!empty($backoffice_summary)) {
			$campaign->__set( ATCF_Campaign::$key_backoffice_summary, $backoffice_summary );
			$campaign->set_api_data( 'description', $backoffice_summary );
			$success["new_backoffice_summary"]=1;
		} else {
			$errors['new_backoffice_summary'].="Décrivez votre projet";
		}
	
		// URL du projet
		$new_name = sanitize_text_field( filter_input( INPUT_POST, 'new_project_url') );
		if ( !empty( $new_name ) && $campaign->data->post_name != $new_name ) {
			$posts = get_posts( array(
				'name' => $new_name,
				'post_type' => array( 'post', 'page', 'download' )
			) );
			if ( $posts ) {
				$errors[ 'new_project_url' ] .= "L'URL est déjà utilisée.";

			} else {
				$campaign->set_api_data( 'url', $new_name );
				wp_update_post( array(
					'ID'		=> $campaign_id,
					'post_name' => $new_name
				) );
				$campaign->data->post_name = $new_name;
				$success[ 'new_project_url' ] = 1;
				// Mise Ã  jour de l'URL sur LW
				$campaign_organization = $campaign->get_organization();
				$WDGOrganization = new WDGOrganization( $campaign_organization->wpref );
				LemonwayLib::wallet_update( 
						$WDGOrganization->get_lemonway_id(),
						'', '', '', '', '', '', '', '',
						get_permalink( $campaign_id )
				);
			}
		}
		
		// Masquer au public
		$new_is_hidden = filter_input( INPUT_POST, 'new_is_hidden');
        if ( $new_is_hidden === true || $new_is_hidden === "true" || $new_is_hidden === 1 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_campaign_is_hidden, '1' );
		} else {
			delete_post_meta( $campaign_id, ATCF_Campaign::$key_campaign_is_hidden );
		}
		$success[ 'new_is_hidden' ] = 1;
		
		// Passer la phase de vote
		$new_skip_vote = filter_input( INPUT_POST, 'new_skip_vote');
        if ( $new_skip_vote === true || $new_skip_vote === "true" || $new_skip_vote === 1 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_skip_vote, '1' );
		} else {
			delete_post_meta( $campaign_id, ATCF_Campaign::$key_skip_vote );
		}
		$success[ 'new_skip_vote' ] = 1;
		
		// Ne pas compter dans les stats
		$new_skip_in_stats = filter_input( INPUT_POST, 'new_skip_in_stats' );
        if ( $new_skip_in_stats === true || $new_skip_in_stats === "true" || $new_skip_in_stats === 1 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_skip_in_stats, '1' );
		} else {
			delete_post_meta( $campaign_id, ATCF_Campaign::$key_skip_in_stats );
		}
		$success[ 'new_skip_in_stats' ] = 1;

		if ( $current_wdg_user->is_admin() ) {
			//Catégories du projet
			$new_project_categories = array();
			if ( isset( $_POST["new_project_categories"] ) ) $new_project_categories = $_POST["new_project_categories"];
			$new_project_activities = array();
			if ( isset( $_POST["new_project_activities"] ) ) $new_project_activities = $_POST["new_project_activities"];
			$new_project_types = array();
			if ( isset( $_POST["new_project_types"] ) ) $new_project_types = $_POST["new_project_types"];
			$new_project_partners = array();
			if ( isset( $_POST["new_project_partners"] ) ) $new_project_partners = $_POST["new_project_partners"];
			$new_project_tousnosprojets = array();
			if ( isset( $_POST["new_project_tousnosprojets"] ) ) $new_project_tousnosprojets = $_POST["new_project_tousnosprojets"];
			$cat_ids = array_merge( $new_project_categories, $new_project_activities, $new_project_types, $new_project_partners, $new_project_tousnosprojets );
			$cat_ids = array_map( 'intval', $cat_ids );
			wp_set_object_terms($campaign_id, $cat_ids, 'download_category');
			$campaign->set_api_data( 'type', $campaign->get_categories_by_type( 'types', TRUE ) );
			$campaign->set_api_data( 'category', $campaign->get_categories_by_type( 'activities', TRUE ) );
			$campaign->set_api_data( 'impacts', $campaign->get_categories_by_type( 'categories', TRUE ) );
			$campaign->set_api_data( 'partners', $campaign->get_categories_by_type( 'partners', TRUE ) );
			$campaign->set_api_data( 'tousnosprojets', $campaign->get_categories_by_type( 'tousnosprojets', TRUE ) );
			$success["new_project_categories"] = 1;
			$success["new_project_activities"] = 1;
			$success["new_project_types"] = 1;
			$success["new_project_partners"] = 1;
			$success["new_project_tousnosprojets"] = 1;
		}

		//Localisation du projet
		$location = sanitize_text_field(filter_input(INPUT_POST,'new_project_location'));
		if (is_numeric($location)) {
			update_post_meta($campaign_id, 'campaign_location', $location);
			$success["new_project_location"]=1;
		}

		$campaign->__set(ATCF_Campaign::$key_external_website, (sanitize_text_field(filter_input(INPUT_POST, 'new_website'))));
		$success['new_website']=1;
		$campaign->__set(ATCF_Campaign::$key_facebook_name, (sanitize_text_field(filter_input(INPUT_POST, 'new_facebook'))));
		$success['new_facebook']=1;
		$campaign->__set(ATCF_Campaign::$key_twitter_name, (sanitize_text_field(filter_input(INPUT_POST, 'new_twitter'))));
		$success['new_twitter']=1;
		
		$new_employees_number = sanitize_text_field( filter_input( INPUT_POST, 'new_employees_number' ) );
		if (is_numeric($location)) {
			$campaign->set_api_data( 'employees_number', $new_employees_number );
			$success[ "new_employees_number" ] = 1;
		}
		$new_minimum_goal_display = sanitize_text_field( filter_input( INPUT_POST, 'new_minimum_goal_display' ) );
		if ( $new_minimum_goal_display == ATCF_Campaign::$key_minimum_goal_display_option_minimum_as_max || $new_minimum_goal_display == ATCF_Campaign::$key_minimum_goal_display_option_minimum_as_step ) {
			$campaign->set_api_data( 'minimum_goal_display', $new_minimum_goal_display );
			$success[ "new_minimum_goal_display" ] = 1;
		}
		
		$new_enable_advice_notifications = sanitize_text_field( filter_input( INPUT_POST, 'new_enable_advice_notifications' ) );
		$queued_action_id = $campaign->has_planned_advice_notification();
        if ( $new_enable_advice_notifications === true || $new_enable_advice_notifications === "true" || $new_enable_advice_notifications === 1 ) {
			if ( $queued_action_id == FALSE ) {
				WDGQueue::add_campaign_advice_notification( $campaign->ID );
			}
		} else {
			if ( $queued_action_id != FALSE ) {
				WDGWPREST_Entity_QueuedAction::edit( $queued_action_id, WDGQueue::$status_complete );
			}
		}
		
		$new_show_comments_for_everyone = sanitize_text_field( filter_input( INPUT_POST, 'new_show_comments_for_everyone' ) );
        if ( $new_show_comments_for_everyone === true || $new_show_comments_for_everyone === "true" || $new_show_comments_for_everyone === 1 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_show_comments_for_everyone, '1' );
		} else {
			delete_post_meta( $campaign_id, ATCF_Campaign::$key_show_comments_for_everyone );
		}
		
		$new_hide_investors = sanitize_text_field( filter_input( INPUT_POST, 'new_hide_investors' ) );
        if ( $new_hide_investors === true || $new_hide_investors === "true" || $new_hide_investors === 1 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_hide_investors, '1' );
		} else {
			delete_post_meta( $campaign_id, ATCF_Campaign::$key_hide_investors );
		}
		$new_archive_message = sanitize_text_field( filter_input( INPUT_POST, 'new_archive_message' ) );
		if ( !empty( $new_archive_message ) ) {
			$campaign->__set( ATCF_Campaign::$key_archive_message, $new_archive_message );
			$success[ "new_archive_message" ] = 1;
		}
		$new_can_invest_until_contract_start_date = sanitize_text_field( filter_input( INPUT_POST, 'new_can_invest_until_contract_start_date' ) );
        if ( $new_can_invest_until_contract_start_date === true || $new_can_invest_until_contract_start_date === "true" || $new_can_invest_until_contract_start_date === 1 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_can_invest_until_contract_start_date, '1' );
		} else {
			delete_post_meta( $campaign_id, ATCF_Campaign::$key_can_invest_until_contract_start_date );
		}
		$new_end_vote_pending_message = sanitize_text_field( filter_input( INPUT_POST, 'new_end_vote_pending_message' ) );
		if ( !empty( $new_end_vote_pending_message ) ) {
			$campaign->__set( ATCF_Campaign::$key_end_vote_pending_message, $new_end_vote_pending_message );
			$success[ "new_end_vote_pending_message" ] = 1;
		}
		$new_maximum_complete_message = sanitize_text_field( filter_input( INPUT_POST, 'new_maximum_complete_message' ) );
		if ( !empty( $new_maximum_complete_message) ) {
			$campaign->__set( ATCF_Campaign::$key_maximum_complete_message, $new_maximum_complete_message );
			$success[ "new_maximum_complete_message" ] = 1;
		}
		$new_custom_footer_code = filter_input( INPUT_POST, 'new_custom_footer_code' );
		if ( !empty( $new_custom_footer_code ) ) {
			$campaign->__set( ATCF_Campaign::$key_custom_footer_code, $new_custom_footer_code );
			$success[ "new_custom_footer_code" ] = 1;
		}
		$new_is_check_payment_available = filter_input( INPUT_POST, 'new_is_check_payment_available');
        if ( $new_is_check_payment_available === true || $new_is_check_payment_available === "true" || $new_is_check_payment_available === 1 ) {
			delete_post_meta( $campaign_id, ATCF_Campaign::$key_can_use_check );
		} else {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_can_use_check, '0' );
		}
		$success[ 'new_is_check_payment_available' ] = 1;
		$new_has_overridden_wire_constraints = filter_input( INPUT_POST, 'new_has_overridden_wire_constraints');
        if ( $new_has_overridden_wire_constraints === true || $new_has_overridden_wire_constraints === "true" || $new_has_overridden_wire_constraints === 1 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_has_overridden_wire_constraints, '1' );
		} else {
			delete_post_meta( $campaign_id, ATCF_Campaign::$key_has_overridden_wire_constraints );
		}
		$success[ 'new_has_overridden_wire_constraints' ] = 1;
		
		$new_fake_url = filter_input( INPUT_POST, 'new_fake_url' );
		if ( !empty( $new_fake_url ) ) {
			$campaign->__set( ATCF_Campaign::$key_fake_url, $new_fake_url );
			$success[ "new_fake_url" ] = 1;
		}
		
		//Champs personnalisés
		$WDGAuthor = new WDGUser( $campaign->data->post_author );
		$nb_custom_fields = $WDGAuthor->wp_user->get('wdg-contract-nb-custom-fields');
		if ( $nb_custom_fields > 0 ) {
			for ( $i = 1; $i <= $nb_custom_fields; $i++ ) {
				$custom_field_value = sanitize_text_field( filter_input( INPUT_POST,'custom_field_' . $i ) );
				update_post_meta( $campaign_id, 'custom_field_' . $i, $custom_field_value );
				$success['custom_field_' . $i] = 1;
			}
		}
		$campaign->update_api();
		
		// Mise Ã  jour du cache
		do_action('wdg_delete_cache', array(
			'cache_campaign_' . $campaign_id
		));
		$file_cacher = WDG_File_Cacher::current();
		$file_cacher->build_campaign_page_cache( $campaign_id );

		$return_values = array(
			"response" => "edit_project",
			"errors" => $errors,
			"success" => $success
		);
		echo json_encode($return_values);

		exit();
	}

	/**
	 * Enregistre les informations liées Ã  l'utilisateur
	 */
	public static function save_user_infos_dashboard() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$current_user = WDGUser::current();
		$errors = array();
		$success = array();

		$gender = filter_input(INPUT_POST, 'new_gender');
		if($gender == "male" || $gender == "female"){
			$success['new_gender']=1;
		}

		$firstname = sanitize_text_field(filter_input(INPUT_POST, 'new_firstname'));
		if(!empty($firstname)){
			$success['new_firstname']=1;
		} else {
			$errors['new_firstname']= __("Vous devez renseigner votre prénom",'yproject');
		}

		$lastname = sanitize_text_field(filter_input(INPUT_POST, 'new_lastname'));
		if(!empty($lastname)){
			$success['new_lastname']=1;
		} else {
			$errors['new_lastname']= __("Vous devez renseigner votre nom",'yproject');
		}

		$birthday = filter_input(INPUT_POST, 'new_birthday');
		if(!empty($birthday)){
			try {
				$new_birthday_date = DateTime::createFromFormat( 'd/m/Y', $birthday );
				$success['new_birthday']=1;
			} catch (Exception $e) {
				$errors['new_birthday']="La date est invalide";
			}
		} else {
			$errors['new_birthday']="Vous devez renseigner votre date de naissance";
		}

		$birthplace = sanitize_text_field(filter_input(INPUT_POST, 'new_birthplace'));
		if(!empty($birthplace)){
			$success['new_birthplace']=1;
		} else {
			$errors['new_birthplace']= __("Vous devez renseigner votre lieu de naissance",'yproject');
		}

		$nationality = sanitize_text_field(filter_input(INPUT_POST, 'new_nationality'));
		if(!empty($nationality)){
			$success['new_nationality']=1;
		} else {
			$errors['new_nationality']= __("Vous devez renseigner votre nationalit&eacute;",'yproject');
		}

		$address = sanitize_text_field(filter_input(INPUT_POST, 'new_address'));
		if(!empty($address)){
			$success['new_address']=1;
		} else {
			$errors['new_address']= __("Vous devez renseigner votre adresse",'yproject');
		}

		$postal_code = sanitize_text_field(filter_input(INPUT_POST, 'new_postal_code'));
		if(!empty($postal_code)){
			$success['new_postal_code']=1;
		} else {
			$errors['new_postal_code']= __("Vous devez renseigner votre code postal",'yproject');
		}

		$city = sanitize_text_field(filter_input(INPUT_POST, 'new_city'));
		if(!empty($city)){
			$success['new_city']=1;
		} else {
			$errors['new_city']= __("Vous devez renseigner votre ville",'yproject');
		}

		$country = sanitize_text_field(filter_input(INPUT_POST, 'new_country'));
		if(!empty($country)){
			$success['new_country']=1;
		} else {
			$errors['new_country']= __("Vous devez renseigner votre pays",'yproject');
		}

		$mobile_phone = sanitize_text_field(filter_input(INPUT_POST, 'new_mobile_phone'));
		if(!empty($mobile_phone)){
			$success['new_mobile_phone']=1;
		} else {
			$errors['new_mobile_phone']= __("Vous devez renseigner un numéro de téléphone",'yproject');
		}

		$mail = sanitize_text_field(filter_input(INPUT_POST, 'new_mail'));
		if (is_email($mail)==$mail && !empty($mail)) {
			$success['new_mail']=1;
		} else {
			$errors['new_mail']= __("Adresse mail non valide",'yproject');
		}
		
		$use_lastname = '';
		$birthplace_district = '';
		$birthplace_department = '';
		$birthplace_country = '';
		$address_number = '';
		$address_number_complement = '';
		$tax_country = '';
		$current_user->save_data( 
			$mail, $gender, $firstname, $lastname, $use_lastname,
			$new_birthday_date->format('d'), $new_birthday_date->format('n'), $new_birthday_date->format('Y'), 
			$birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality,
			$address_number, $address_number_complement, $address, $postal_code, $city, $country, $tax_country, $mobile_phone
		);

		$return_values = array(
			"response" => "edit_project",
			"errors" => $errors,
			"success" => $success
		);
		echo json_encode($return_values);

		exit();
	}
	
	/**
	 * Informations d'ajustement de déclaration de ROI
	 */
	public static function save_declaration_adjustment() {
		$declaration_id = filter_input( INPUT_POST, 'declaration_id' );
        $current_wdg_user = WDGUser::current();
		if ( empty( $declaration_id ) || !$current_wdg_user->is_admin() ) {
			exit();
		}
		
		$errors = array();
		$success = array();
		
		$validated = filter_input( INPUT_POST, 'new_declaration_adjustment_validated' );
		if ( $validated != 0 && $validated != 1 ) {
			$errors['new_declaration_adjustment_validated'] = __("Validation non conforme",'yproject');
		}
		
		$needed = filter_input( INPUT_POST, 'new_declaration_adjustment_needed' );
		if ( $needed != 0 && $needed != 1 ) {
			$errors['new_declaration_adjustment_needed'] = __("Obligation non conforme",'yproject');
		}
		
		$turnover_difference = filter_input( INPUT_POST, 'new_declaration_adjustment_turnover_difference' );
		if ( !is_numeric( $turnover_difference ) ) {
			$errors['new_declaration_adjustment_turnover_difference'] = __("Valeur non conforme",'yproject');
		}
		
		$value = filter_input( INPUT_POST, 'new_declaration_adjustment_value' );
		if ( !is_numeric( $value ) ) {
			$errors['new_declaration_adjustment_value'] = __("Valeur non conforme",'yproject');
		}
		
		$message_to_author = htmlentities( filter_input( INPUT_POST, 'new_declaration_adjustment_message_author' ) );
		$message_to_investors = htmlentities( filter_input( INPUT_POST, 'new_declaration_adjustment_message_investors' ) );
		
		if ( empty( $errors ) ) {
			$wdg_declaration = new WDGROIDeclaration( $declaration_id );
			$wdg_declaration->set_adjustment( ( $validated == 1 ), ( $needed == 1 ), $turnover_difference, $value, $message_to_author, $message_to_investors );
			$success[ 'new_declaration_adjustment_validated' ] = 1;
			$success[ 'new_declaration_adjustment_needed' ] = 1;
			$success[ 'new_declaration_adjustment_turnover_difference' ] = 1;
			$success[ 'new_declaration_adjustment_value' ] = 1;
			$success[ 'new_declaration_adjustment_message_author' ] = 1;
			$success[ 'new_declaration_adjustment_message_investors' ] = 1;
		}

		$return_values = array(
			"response"	=> "declaration_adjustment",
			"errors"	=> $errors,
			"success"	=> $success
		);
		echo json_encode($return_values);

		exit();
	}
	
	/**
	 * Déclenchement de paiement via prélÃ¨vement automatique
	 */
	public static function pay_with_mandate() {
        $current_wdg_user = WDGUser::current();
		if ( !$current_wdg_user->is_admin() ) {
			exit();
		}
		
		$errors = array();
		$success = array();
		
		$amount_for_organization = filter_input( INPUT_POST, 'pay_with_mandate_amount_for_organization' );
		if ( $amount_for_organization < 0 || !is_numeric( $amount_for_organization ) ) {
			$errors['pay_with_mandate_amount_for_organization'] = __("Somme non conforme",'yproject');
		}
		$amount_for_commission = filter_input( INPUT_POST, 'pay_with_mandate_amount_for_commission' );
		if ( $amount_for_commission < 0 || !is_numeric( $amount_for_commission ) ) {
			$errors['pay_with_mandate_amount_for_commission'] = __("Somme non conforme",'yproject');
		}
		$organization_id = filter_input( INPUT_POST, 'organization_id' );
		if ( empty( $organization_id ) ) {
			$errors['organization_id'] = __("Probl&egrave;me interne",'yproject');
		}
//		ypcf_debug_log( 'pay_with_mandate : ' .$amount_for_organization. ' + ' .$amount_for_commission. ' for ' .$organization_id );
		
		if ( empty( $errors ) ) {
			$organization_obj = new WDGOrganization( $organization_id );
			$wallet_id = $organization_obj->get_lemonway_id();
			$saved_mandates_list = $organization_obj->get_lemonway_mandates();
			if ( !empty( $saved_mandates_list ) ) {
				$last_mandate = end( $saved_mandates_list );
			}
			$mandate_id = $last_mandate['ID'];
		
			if ( $wallet_id ) {
				$result = LemonwayLib::ask_payment_with_mandate( $wallet_id, $amount_for_organization + $amount_for_commission, $mandate_id, $amount_for_commission );
				$buffer = ($result->TRANS->HPAY->ID) ? "success" : $result->TRANS->HPAY->MSG;

				if ($buffer == "success") {
					// Enregistrement de l'objet Lemon Way
					$withdrawal_post = array(
						'post_author'   => $organization_id,
						'post_title'    => $amount_for_organization . ' + ' . $amount_for_commission,
						'post_content'  => print_r( $result, TRUE ),
						'post_status'   => 'publish',
						'post_type'		=> 'mandate_payment'
					);
					wp_insert_post( $withdrawal_post );

					$success[ 'pay_with_mandate_amount_for_organization' ] = 1;
					$success[ 'pay_with_mandate_amount_for_commission' ] = 1;

				} else {
					$errors['pay_with_mandate_amount_for_organization'] = __("Probl&egrave;me Lemon Way",'yproject');

				}
			}
		}

		$return_values = array(
			"response"	=> "pay_with_mandate",
			"errors"	=> $errors,
			"success"	=> $success
		);
		echo json_encode($return_values);

		exit();
	}

	/**
	 * Enregistre les informations de collecte du projet
	 */
	public static function save_project_funding(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$errors = array();
		$success = array();

		//Update required amount
		$new_minimum_goal = intval(sanitize_text_field(filter_input(INPUT_POST, 'new_minimum_goal')));
		$new_maximum_goal = intval(sanitize_text_field(filter_input(INPUT_POST, 'new_maximum_goal')));
		if($new_minimum_goal > $new_maximum_goal){
			$errors['new_minimum_goal']="Le montant maximum ne peut &ecirc;tre inf&eacute;rieur au montant minimum";
			$errors['new_maximum_goal']="Le montant maximum ne peut &ecirc;tre inf&eacute;rieur au montant minimum";
		} else if($new_minimum_goal<0 || $new_maximum_goal<0) {
			$errors['new_minimum_goal']="Les montants doivent &ecirc;tre positifs";
		} else if($new_maximum_goal<0) {
			$errors['new_maximum_goal']="Les montants doivent &ecirc;tre positifs";
		} else {
			update_post_meta($campaign_id, ATCF_Campaign::$key_minimum_goal, $new_minimum_goal);
			$campaign->set_api_data( 'goal_minimum', $new_minimum_goal );
			update_post_meta($campaign_id, ATCF_Campaign::$key_goal, $new_maximum_goal);
			$campaign->set_api_data( 'goal_maximum', $new_maximum_goal );
			$success['new_minimum_goal']=1;
			$success['new_maximum_goal']=1;
		}
		
		$new_project_contract_spendings_description = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_spendings_description' ) );
		if ( !empty( $new_project_contract_spendings_description ) ) {
			$campaign->__set( ATCF_Campaign::$key_contract_spendings_description, $new_project_contract_spendings_description );
			$campaign->set_api_data( 'spendings_description', $new_project_contract_spendings_description );
		}

		//Update funding duration
		$new_duration = intval( sanitize_text_field( filter_input( INPUT_POST, 'new_funding_duration' ) ) );
		if ( $new_duration >= 0 ){
			update_post_meta( $campaign_id, ATCF_Campaign::$key_funding_duration, $new_duration );
			$campaign->set_api_data( 'funding_duration', $new_duration );
			$success[ 'new_funding_duration' ] = 1;
		} else {
			$errors[ 'new_funding_duration' ] = "Erreur de valeur";
		}
		
		$new_platform_commission = sanitize_text_field( filter_input( INPUT_POST, 'new_platform_commission' ) );
		$new_platform_commission = str_replace( ',', '.', $new_platform_commission );
		if ( $new_platform_commission >= 0 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_platform_commission, $new_platform_commission );
			$success['new_platform_commission'] = 1;
		} else {
			$errors['new_platform_commission'] = "Le pourcentage doit &ecirc;tre positif";
		}
		
		$new_maximum_profit = sanitize_text_field( filter_input( INPUT_POST, 'new_maximum_profit' ) );
		$possible_maximum_profit = array_keys( ATCF_Campaign::$maximum_profit_list );
		if ( in_array( $new_maximum_profit, $possible_maximum_profit ) ){
			update_post_meta( $campaign_id, ATCF_Campaign::$key_maximum_profit, $new_maximum_profit );
			$campaign->set_api_data( ATCF_Campaign::$key_maximum_profit, $new_maximum_profit );
			$success[ 'new_maximum_profit' ] = 1;
		} else {
			$errors[ 'new_maximum_profit' ] = "Le gain maximum n'est pas correct (".$new_maximum_profit.")";
		}
		
		$new_maximum_profit_precision = sanitize_text_field( filter_input( INPUT_POST, 'new_maximum_profit_precision' ) );
		if ( is_numeric( $new_maximum_profit_precision ) ){
			update_post_meta( $campaign_id, ATCF_Campaign::$key_maximum_profit_precision, $new_maximum_profit_precision );
			$campaign->set_api_data( ATCF_Campaign::$key_maximum_profit_precision, $new_maximum_profit_precision );
			$success[ 'new_maximum_profit_precision' ] = 1;
		} else {
			$errors[ 'new_maximum_profit_precision' ] = "La précision de gain maximum n'est pas correcte (".$new_maximum_profit.")";
		}

		//Update roi_percent_estimated
		$new_roi_percent_estimated = floatval( sanitize_text_field( filter_input( INPUT_POST, 'new_roi_percent_estimated' ) ) );
		if ( $new_roi_percent_estimated >= 0 ){
			update_post_meta( $campaign_id, ATCF_Campaign::$key_roi_percent_estimated, $new_roi_percent_estimated );
			$campaign->set_api_data( 'roi_percent_estimated', $new_roi_percent_estimated );
			$success['new_roi_percent_estimated'] = 1;
		} else {
			$errors['new_roi_percent_estimated'] = "Le pourcentage de CA reversé doit Ãªtre positif";
		}
		
		$new_roi_percent = floatval( sanitize_text_field( filter_input( INPUT_POST, 'new_roi_percent' ) ) );
		if( $new_roi_percent >= 0 ){
			update_post_meta( $campaign_id, ATCF_Campaign::$key_roi_percent, $new_roi_percent );
			$campaign->set_api_data( 'roi_percent', $new_roi_percent );
			$success[ 'new_roi_percent' ] = 1;
		} else {
			$errors[ 'new_roi_percent' ] ="Le pourcentage de CA reversé doit Ãªtre positif";
		}

		//Update contract_start_date
		$new_contract_start_date = filter_input(INPUT_POST, 'new_contract_start_date');
		if ( empty( $new_contract_start_date ) ) {
			$errors[ 'new_contract_start_date' ] = "La date est invalide";
		} else {
			try {
				update_post_meta( $campaign_id, ATCF_Campaign::$key_contract_start_date, $new_contract_start_date );
				$dt_contract_start_date = new DateTime( $new_contract_start_date );
				$campaign->set_api_data( 'contract_start_date', $dt_contract_start_date->format( 'Y-m-d' ) );
				$success[ 'new_contract_start_date']  = 1;
			} catch (Exception $e) {
				$errors[ 'new_contract_start_date' ] = "La date est invalide";
			}
		}
		
		$new_turnover_per_declaration = intval( sanitize_text_field( filter_input( INPUT_POST, 'new_turnover_per_declaration') ) );
		if ( $new_turnover_per_declaration >= 0 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_turnover_per_declaration, $new_turnover_per_declaration );
			$success['new_turnover_per_declaration'] = 1;
		} else {
			$errors['new_turnover_per_declaration'] = "Nombre non valide";
		}
		
		$new_declaration_periodicity = sanitize_text_field( filter_input( INPUT_POST, 'new_declaration_periodicity') );
		if ( !empty( $new_declaration_periodicity ) ) {
			$campaign->set_api_data( ATCF_Campaign::$key_declaration_periodicity, $new_declaration_periodicity );
			$success['new_declaration_periodicity'] = 1;
		} else {
			$errors['new_declaration_periodicity'] = "S&eacute;lection non valide";
		}
		
		$new_minimum_costs_to_organization = round( floatval( sanitize_text_field( filter_input( INPUT_POST, 'new_minimum_costs_to_organization') ) ), 2 );
		if ( $new_minimum_costs_to_organization >= 0 ) {
			$campaign->set_api_data( 'minimum_costs_to_organization', $new_minimum_costs_to_organization );
			$success['new_minimum_costs_to_organization'] = 1;
		} else {
			$errors['new_minimum_costs_to_organization'] = "Nombre non valide";
		}
		
		$new_costs_to_organization = round( floatval( sanitize_text_field( filter_input( INPUT_POST, 'new_costs_to_organization') ) ), 2 );
		if ( $new_costs_to_organization >= 0 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_costs_to_organization, $new_costs_to_organization );
			$campaign->set_api_data( 'costs_to_organization', $new_costs_to_organization );
			$success['new_costs_to_organization'] = 1;
		} else {
			$errors['new_costs_to_organization'] = "Nombre non valide";
		}
		
		$new_costs_to_investors = round( floatval( sanitize_text_field( filter_input( INPUT_POST, 'new_costs_to_investors') ) ), 2 );
		if ( $new_costs_to_investors >= 0 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_costs_to_investors, $new_costs_to_investors );
			$campaign->set_api_data( 'costs_to_investors', $new_costs_to_investors );
			$success['new_costs_to_investors'] = 1;
		} else {
			$errors['new_costs_to_investors'] = "Nombre non valide";
		}
		

		//Update first_payment_date
		$old_first_payment_date = $campaign->first_payment_date();
		$new_first_payment_date = filter_input(INPUT_POST, 'new_first_payment');
		if ( empty( $old_first_payment_date ) && empty( $new_first_payment_date ) && !empty( $new_contract_start_date ) ) {
			// Si non défini, on chope le 10 du trimestre suivant le début de contrat pour automatiser un peu !
			$contract_start_date_time = new DateTime( $new_contract_start_date );
			$contract_start_date_time->add( new DateInterval( 'P9D' ) );
			$contract_start_date_time->add( new DateInterval( 'P3M' ) );
			$campaign->set_api_data( 'declarations_start_date', $contract_start_date_time->format( 'Y-m-d' ) );
			update_post_meta( $campaign_id, ATCF_Campaign::$key_first_payment_date, date_format( $contract_start_date_time, 'Y-m-d H:i:s' ) );
			
		} else {
			if(empty($new_first_payment_date)){
				$errors['new_first_payment']= "La date est invalide";
			} else {
				try {
					$new_first_payment_date = DateTime::createFromFormat( 'd/m/Y', filter_input( INPUT_POST, 'new_first_payment' ) );
					$campaign->set_api_data( 'declarations_start_date', $new_first_payment_date->format( 'Y-m-d' ) );
					update_post_meta($campaign_id, ATCF_Campaign::$key_first_payment_date, date_format($new_first_payment_date, 'Y-m-d H:i:s'));
					$success['new_first_payment'] = 1;
				} catch (Exception $e) {
					$errors['new_first_payment'] = "La date est invalide";
				}
			}
		}
		
		$new_estimated_turnover_unit = sanitize_text_field( filter_input( INPUT_POST, 'new_estimated_turnover_unit') );
		if ( $new_estimated_turnover_unit == 'euro' || $new_estimated_turnover_unit == 'percent' ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_estimated_turnover_unit, $new_estimated_turnover_unit );
			$success['new_estimated_turnover_unit'] = 1;
		} else {
			$errors['new_estimated_turnover_unit'] = "Valeur non valide";
		}

		//Update list of estimated turnover
		$i = 0;
		$sanitized_list = array();
		$funding_duration = $campaign->funding_duration();
		if ( $funding_duration == 0 ) {
			$funding_duration = 5;
		}
		while(filter_input(INPUT_POST, 'new_estimated_turnover_'.$i)!='' && ($i+1 <= $funding_duration)){
			$current_val = filter_input(INPUT_POST, 'new_estimated_turnover_'.$i);

			if(is_numeric($current_val)){
				if(intval($current_val)>=0){
					$sanitized_list[$i+1] = strval(intval($current_val));
					$success['new_estimated_turnover_'.$i] = 1;
				} else {
					$errors['new_estimated_turnover_'.$i] = "La valeur doit Ãªtre positive";
					$sanitized_list[$i+1] = strval(abs(intval($current_val)));
				}
			} else {
				$errors['new_estimated_turnover_'.$i] = "Valeur invalide";
				$sanitized_list[$i+1] = 0;
			}

			$i++;
		}
 		$campaign->__set(ATCF_Campaign::$key_estimated_turnover,json_encode($sanitized_list));
		$campaign->set_api_data( 'estimated_turnover', json_encode( $sanitized_list ) );
		
		$campaign->update_api();


		$return_values = array(
			"response" => "edit_funding",
			"errors" => $errors,
			"success" => $success
		);
		echo json_encode($return_values);
		exit();
	}

	/**
	 * Enregistre les informations de l'organisation liée Ã  un projet
	 */
	public static function save_project_organization(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$success = array();

		//Récupération de l'ancienne organisation
		$campaign = new ATCF_Campaign($campaign_id);
		$current_organization = $campaign->get_organization();

		$delete = FALSE;
		$update = FALSE;

		//On met Ã  jour : si une nouvelle organisation est renseignée et différente de celle d'avant
		//On supprime : si la nouvelle organisation renseignée est différente de celle d'avant
		$project_organization = filter_input(INPUT_POST, 'new_project_organization');
		if (!empty($project_organization)) {
			$organization_selected = new WDGOrganization($project_organization);
			if ($current_organization === FALSE || $current_organization->wpref != $organization_selected->get_wpref()) {
				$update = TRUE;
				if ($current_organization !== FALSE) {
					$delete = TRUE;
				}
			} else {
				$success['new_project_organization']=1;
			}

		//On supprime : si rien n'est sélectionné + il y avait quelque chose avant
		} else {
			if ($current_organization !== FALSE) {
				$delete = TRUE;
			}
		}

		if ($delete) {
			$campaign->unlink_organization( $current_organization->id );
		}
                
		if ($update) {
			$campaign->link_organization( $organization_selected->get_api_id() );
			$success['new_project_organization']=1;

			//documents
			$msg_upload = __("T&eacute;l&eacute;charger le fichier envoy&eacute; le ", 'yproject');

			$doc_bank = $organization_selected->get_doc_bank();
			if($doc_bank != null) {
				$bank_path = $doc_bank->get_public_filepath();
				$bank_date_uploaded = $msg_upload.$doc_bank->get_date_uploaded();
			} else {
				$bank_path = $bank_date_uploaded = null;
			}

			$doc_kbis = $organization_selected->get_doc_kbis();
			if($doc_kbis != null) {
				$kbis_path = $doc_kbis->get_public_filepath();
				$kbis_date_uploaded = $msg_upload.$doc_kbis->get_date_uploaded();
			} else {
				$kbis_path = $kbis_date_uploaded = null;
			}

			$doc_status = $organization_selected->get_doc_status();
			if($doc_status != null) {
				$status_path = $doc_status->get_public_filepath();
				$status_date_uploaded = $msg_upload.$doc_status->get_date_uploaded();
			} else {
				$status_path = $status_date_uploaded = null;
			}

			$doc_id = $organization_selected->get_doc_id();
			if($doc_id != null) {
				$id_path = $doc_id->get_public_filepath();
				$id_date_uploaded = $msg_upload.$doc_id->get_date_uploaded();
			} else {
				$id_path = $id_date_uploaded = null;
			}

			$doc_home = $organization_selected->get_doc_home();
			if($doc_home != null) {
				$home_path = $doc_home->get_public_filepath();
				$home_date_uploaded = $msg_upload.$doc_home->get_date_uploaded();
			} else {
				$home_path = $home_date_uploaded = null;
			}

			$return_values = array(
				"response" => "edit_organization",
				"errors" => array(),
				"success" => $success,
				"organization" => array(
					"name"			=> $organization_selected->get_name(),
					"email"			=> $organization_selected->get_email(),
					"representative_function" => $organization_selected->get_representative_function(),
					"description"	=> $organization_selected->get_description(),
					"legalForm"		=> $organization_selected->get_legalform(),
					"idNumber"		=> $organization_selected->get_idnumber(),
					"rcs"			=> $organization_selected->get_rcs(),
					"capital"		=> $organization_selected->get_capital(),
					"ape"			=> $organization_selected->get_ape(),
					"vat"			=> $organization_selected->get_vat(),
					"fiscal_year_end_month" => $organization_selected->get_fiscal_year_end_month(),
					"address_number"		=> $organization_selected->get_address_number(),
					"address_number_comp"	=> $organization_selected->get_address_number_comp(),
					"address"				=> $organization_selected->get_address(),
					"postal_code"			=> $organization_selected->get_postal_code(),
					"city"					=> $organization_selected->get_city(),
					"nationality"			=> $organization_selected->get_nationality(),
					"bankownername"			=> $organization_selected->get_bank_owner(),
					"bankowneraddress"		=> $organization_selected->get_bank_address(),
					"bankowneriban"			=> $organization_selected->get_bank_iban(),
					"bankownerbic"			=> $organization_selected->get_bank_bic(),
					"doc_bank" => array(
						"path" => $bank_path,
						"date_uploaded" => $bank_date_uploaded,
					),
					"doc_kbis" => array(
						"path" => $kbis_path,
						"date_uploaded" => $kbis_date_uploaded,
					),
					"doc_status" => array(
						"path" => $status_path,
						"date_uploaded" => $status_date_uploaded,
					),
					"doc_id" => array(
						"path" => $id_path,
						"date_uploaded" => $id_date_uploaded,
					),
					"doc_home" => array(
						"path" => $home_path,
						"date_uploaded" => $home_date_uploaded,
					),
				),
				"orga_object" => $organization_selected,
			);
			echo json_encode($return_values);
		}
		exit();
	}

	/**
	 * Enregistre les informations du formulaire de création d'une organisation
	 * et lie cette organisation au projet
	 */
	public static function save_new_organization(){
		global $errors_submit_new;

		$campaign_id = filter_input(INPUT_POST, 'campaign_id');

		//validation des données, enregistrement de l'organisation et récupération de l'objet de la nouvelle orga
		$return = WDGOrganization::submit_new( FALSE );
		$org_object = FALSE;
		if ( !empty( $return['org_object'] ) ) {
			$org_object = $return['org_object'];
			$org_api_id = $org_object->get_api_id();
		}

		if ( !empty( $org_object ) && $org_object != null && !empty( $org_api_id ) ) {
			/////////// Liaison de l'organisation au projet ////////////////

			//Récupération de l'ancienne organisation
			$campaign = new ATCF_Campaign($campaign_id);
			$current_organization = $campaign->get_organization();
			$delete = ( empty($current_organization) ) ? FALSE : TRUE;

			//on a déjà une organisation, donc on supprime la liaison
			if ( $delete ) {
				$campaign->unlink_organization( $current_organization->id );
			}
			//on lie l'organisation que l'on vient de créer Ã  partir de la ligthbox dans le TB partie Organisation
			$campaign->link_organization( $org_api_id );

			////////////////////////////////////////////////////////////////
		}

		if ( $return === FALSE ) {//user non connecté
			$buffer = "FALSE";
		} else if ( !empty( $org_object ) && $org_object != null ){
			$return_values = array(
				"response" => "save_new_organization",
				"organization" => array(
					"wpref"			=> $org_object->get_wpref(),
					"name"			=> $org_object->get_name(),
					"email"			=> $org_object->get_email(),
					"representative_function"	=> $org_object->get_representative_function(),
					"description"	=> $org_object->get_description(),
					"legalForm"		=> $org_object->get_legalform(),
					"idNumber"		=> $org_object->get_idnumber(),
					"rcs"			=> $org_object->get_rcs(),
					"capital"		=> $org_object->get_capital(),
					"ape"			=> $org_object->get_ape(),
					"vat"			=> $org_object->get_vat(),
					"fiscal_year_end_month"		=> $org_object->get_fiscal_year_end_month(),
					"address_number"		=> $org_object->get_address_number(),
					"address_number_comp"	=> $org_object->get_address_number_comp(),
					"address"				=> $org_object->get_address(),
					"postal_code"			=> $org_object->get_postal_code(),
					"city"					=> $org_object->get_city(),
					"nationality"			=> $org_object->get_nationality(),
					"bankownername"		=> $org_object->get_bank_owner(),
					"bankowneraddress"	=> $org_object->get_bank_address(),
					"bankowneriban"		=> $org_object->get_bank_iban(),
					"bankownerbic"		=> $org_object->get_bank_bic(),
					),
				"campaign_id" => $campaign_id,
			);
			$buffer = json_encode($return_values);
		}else{
			$return_values = array(
				"errors" => $return['errors_edit'],
			);
			$buffer = json_encode($return_values);
		}
		echo $buffer;
		exit();
	}
	
	/**
	 * Enregistre les informations du formulaire d'édition d'une organisation
	 */
	public static function save_edit_organization(){
		global $errors_edit;
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');

		//Récupération de l'organisation
		$campaign = new ATCF_Campaign($campaign_id);
		$current_organization = $campaign->get_organization();

		// enregistrement des données dans l'organisation
		$org_object = new WDGOrganization( $current_organization->wpref, $current_organization );

		//enregistrement des données avec la fonction edit et récupération des 
		//infos sur les fichiers uploadés
		$files_info = WDGOrganization::edit($org_object);

		if($files_info === FALSE) {//user non connecté
			$buffer = "FALSE";
		} else if($files_info['files_info'] != null) {
			$return_values = array(
				"response" => "edit_organization",
				"organization" => array(
					"wpref"		=> $org_object->get_wpref(),
					"name"		=> $org_object->get_name(),
					"email"		=> $org_object->get_email(),
					"representative_function"	=> $org_object->get_representative_function(),
					"description"	=> $org_object->get_description(),
					"legalForm"		=> $org_object->get_legalform(),
					"idNumber"		=> $org_object->get_idnumber(),
					"rcs"			=> $org_object->get_rcs(),
					"capital"		=> $org_object->get_capital(),
					"ape"			=> $org_object->get_ape(),
					"vat"			=> $org_object->get_vat(),
					"fiscal_year_end_month"		=> $org_object->get_fiscal_year_end_month(),
					"address_number"		=> $org_object->get_address_number(),
					"address_number_comp"	=> $org_object->get_address_number_comp(),
					"address"				=> $org_object->get_address(),
					"postal_code"			=> $org_object->get_postal_code(),
					"city"					=> $org_object->get_city(),
					"nationality"			=> $org_object->get_nationality(),
					"bankownername"			=> $org_object->get_bank_owner(),
					"bankowneraddress"	=> $org_object->get_bank_address(),
					"bankowneriban"		=> $org_object->get_bank_iban(),
					"bankownerbic"		=> $org_object->get_bank_bic(),
					"id_quickbooks"		=> $org_object->get_id_quickbooks(),
				),
				"files_info" => array(
					"org_doc_bank" => $files_info['files_info']["org_doc_bank"],
					"org_doc_kbis" => $files_info['files_info']["org_doc_kbis"],
					"org_doc_status" => $files_info['files_info']["org_doc_status"],
					"org_doc_id" => $files_info['files_info']["org_doc_id"],
					"org_doc_home" => $files_info['files_info']["org_doc_home"],
					"org_doc_capital_allocation" => $files_info['files_info']["org_doc_capital_allocation"],
					"org_doc_id_2" => $files_info['files_info']["org_doc_id_2"],
					"org_doc_home_2" => $files_info['files_info']["org_doc_home_2"],
					"org_doc_id_3" => $files_info['files_info']["org_doc_id_3"],
					"org_doc_home_3" => $files_info['files_info']["org_doc_home_3"]
				),
			);
			$buffer = json_encode($return_values);
		} else{
			$return_values = array(
				"errors" => $files_info['errors_edit'],
			);
			$buffer = json_encode($return_values);
		}
		echo $buffer;
		exit();
	}
	
	public static function save_project_contract_modification() {
		$success = array();
		
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign( $campaign_id );
		$campaign->__set( ATCF_Campaign::$key_backoffice_contract_modifications, filter_input( INPUT_POST, 'new_contract_modification' ) );
		$success[ 'new_contract_modification' ] = 1;
		$campaign->update_api();

		$return_values = array(
			'response'	=> 'edit_contract_modifications',
			'errors'	=> array(),
			'success'	=> $success
		);
		echo json_encode($return_values);
		exit();
	}

	/**
	 * Enregistre les informations de l'onglet "campagne" du projet
	 */
	public static function save_project_campaigntab(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$errors = array();
		$success = array();
		
		$end_vote_date = filter_input(INPUT_POST, 'new_end_vote_date');
		if(!empty($end_vote_date)){
			try {
				$new_end_vote_date = new DateTime(sanitize_text_field(filter_input(INPUT_POST, 'new_end_vote_date')));
				$campaign->set_end_vote_date($new_end_vote_date);
				$success['new_end_vote_date']=1;
			} catch (Exception $e) {
				$errors['new_end_vote_date']="La date est invalide";
			}
		} else {
			$errors['new_end_vote_date']="Il faut une date de fin d'&eacute;valuation !";
		}

		$begin_collecte_date = filter_input(INPUT_POST, 'new_begin_collecte_date');
		if(!empty($begin_collecte_date)){
			try {
				$new_begin_collecte_date = new DateTime(sanitize_text_field(filter_input(INPUT_POST, 'new_begin_collecte_date')));
				$campaign->set_begin_collecte_date($new_begin_collecte_date);
				$success['new_begin_collecte_date']=1;
			} catch (Exception $e) {
				$errors['new_begin_collecte_date']="La date est invalide";
			}
		} else {
			$errors['new_begin_collecte_date']="Il faut une date de d&eacute;but de collecte !";
		}

		$end_collecte_date = filter_input(INPUT_POST, 'new_end_collecte_date');
		if(!empty($end_collecte_date)){
			try {
				$new_end_collecte_date = new DateTime(sanitize_text_field(filter_input(INPUT_POST, 'new_end_collecte_date')));
				$campaign->set_end_date($new_end_collecte_date);
				$success['new_end_collecte_date']=1;
			} catch (Exception $e) {
				$errors['new_end_collecte_date']="La date est invalide";
			}
		} else {
			$errors['new_end_collecte_date']="Il faut une date de fin d'investissement !";
		}
		$campaign->update_api();

		$return_values = array(
			"response" => "save_project_campaigntab",
			"errors" => $errors,
			"success" => $success
		);
		echo json_encode($return_values);
		exit();
	}

	/**
	 * Enregistre les informations d'étape du projet
	 */
	public static function save_project_status(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$errors = array();
		$success = array();

		$new_status = (sanitize_text_field(filter_input(INPUT_POST, 'new_campaign_status')));
		$campaign->set_status($new_status);
		$success['new_campaign_status']=1;

		$new_validation_status = (sanitize_text_field(filter_input(INPUT_POST, 'new_can_go_next_status')));
		$campaign->set_validation_next_status($new_validation_status);
		$success['new_can_go_next_status']=1;
		$campaign->update_api();
		
		// Mise Ã  jour cache
		do_action('wdg_delete_cache', array(
			'cache_campaign_' . $campaign_id
		));
		$file_cacher = WDG_File_Cacher::current();
		$file_cacher->build_campaign_page_cache( $campaign_id );

		$return_values = array(
			"response" => "edit_status",
			"errors" => $errors,
			"success" => $success
		);
		echo json_encode($return_values);
		exit();
	}
	
	/**
	 * Enregistre l'obligation de signer une autorisation de prélèvement
	 */
	public static function save_project_force_mandate() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$errors = array();
		$success = array();

		$new_status = (sanitize_text_field(filter_input(INPUT_POST, 'new_force_mandate')));
		$campaign->set_forced_mandate($new_status);
		$success['new_force_mandate'] = 1;
		
		$new_mandate_conditions = (filter_input(INPUT_POST, 'new_mandate_conditions'));
		$campaign->__set(ATCF_Campaign::$key_mandate_conditions, $new_mandate_conditions);
		$success['new_mandate_conditions'] = 1;
		$campaign->update_api();
		
		$return_values = array(
			"response"	=> "edit_force_mandate",
			"errors"	=> $errors,
			"success"	=> $success
		);
		echo json_encode($return_values);
		exit();
	}
	
	/**
	 * Enregistre les informations concernant la déclaration de royalties
	 */
	public static function save_project_declaration_info() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$errors = array();
		$success = array();
		
		$new_declaration_info = (filter_input(INPUT_POST, 'new_declaration_info'));
		$campaign->__set(ATCF_Campaign::$key_declaration_info, $new_declaration_info);
		$success['new_declaration_info'] = 1;
		$campaign->update_api();
		
		$return_values = array(
			"response"	=> "edit_declaration_info",
			"errors"	=> $errors,
			"success"	=> $success
		);
		echo json_encode($return_values);
		exit();
	}

	/**
	 * Crée la table des contacts
	 */
	public static function create_contacts_table(){
		$campaign_id = filter_input(INPUT_POST, 'id_campaign');
		
		$campaign = new ATCF_Campaign($campaign_id);
		$campaign_poll_answers = $campaign->get_api_data( 'poll_answers' );
		
        $current_wdg_user = WDGUser::current();
        global $country_list;
		global $wpdb;
		$table_vote = $wpdb->prefix . "ypcf_project_votes";
		$table_jcrois = $wpdb->prefix . "jycrois";

		//Données suiveurs
		$list_user_follow = $wpdb->get_col( "SELECT DISTINCT user_id FROM ".$table_jcrois." WHERE subscribe_news = 1 AND campaign_id = ".$campaign_id. " GROUP BY user_id");
		
		//Données d'investissement
		$investments_list = (json_decode(filter_input(INPUT_POST, 'data'),true));

		//Données de vote
		$list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum, date, rate_project, advice FROM ".$table_vote." WHERE post_id = ".$campaign_id );


        /******************Lignes du tableau*********************/
        $array_contacts = array();

		//Extraction infos suiveurs
		foreach ( $list_user_follow as $item_follow ) {
			$array_contacts[$item_follow]["follow"]=1;
			$array_contacts[$item_follow]["invest_id"] = 0;
		}

        //Extraction infos de vote
        foreach ( $list_user_voters as $item_vote ) {
            $u_id = $item_vote->user_id;
            $array_contacts[$u_id]["vote"]=1;
            $array_contacts[$u_id]["vote_date"]=$item_vote->date;
			$array_contacts[$u_id]["invest_id"] = 0;
			$array_contacts[$u_id]["vote_invest_sum"]=$item_vote->invest_sum;


            $array_contacts[$u_id]["vote_advice"]= ( !empty( $item_vote->advice ) ) ? '<i class="infobutton fa fa-comment" aria-hidden="true"></i><div class="tooltiptext">'.$item_vote->advice.'</div>' : '';
			$array_contacts[$u_id]["vote_rate"] = $item_vote->rate_project;
        }
		
		// Contrats complémentaires éventuels
		$contracts_to_add = array();
		$contract_models = WDGWPREST_Entity_Project::get_contract_models( $campaign->get_api_id() );
		foreach ( $contract_models as $contract_model ) {
			if ( $contract_model->status == 'sent' ) {
				array_push( $contracts_to_add, $contract_model );
			}
		}

        //Extraction infos d'investissements
        foreach ( $investments_list['payments_data'] as $item_invest ) {
			$payment_investment = new WDGInvestment( $item_invest[ 'ID' ] );
			$contract_status = $payment_investment->get_contract_status();
            $post_invest = get_post($item_invest['ID']);
			$post_invest_status = $post_invest->post_status;
			
			if ( !empty( $item_invest[ 'payment_key' ] ) ) {
				$payment_key = $item_invest[ 'payment_key' ];
			} else {
				$payment_key = $item_invest[ 'lemonway_contribution' ] ? $item_invest[ 'lemonway_contribution' ] : $item_invest[ 'mangopay_contribution' ];
			}

            $u_id = $item_invest['user'];

            $payment_type = 'Carte';
            if (strpos($payment_key, 'wire_') !== FALSE) {
                $payment_type = 'Virement';
				
            } else if ($payment_key == 'check') {
				$check_file_url = get_post_meta( $item_invest['ID'], 'check_picture', TRUE );
				if ( !empty( $check_file_url ) ) {
					$check_file_url = home_url() . '/wp-content/plugins/appthemer-crowdfunding/files/investment-check/' . $check_file_url;
				}
				if ( !empty( $check_file_url ) && $current_wdg_user->is_admin() ) {
					$payment_type = '<a href="'.$check_file_url.'" target="_blank">Ch&egrave;que</a>';
				} else {
					$payment_type = 'Ch&egrave;que';
				}
				
			// Si c'est juste une intention avec dépot de fichiers
            } else if ( $post_invest_status == 'pending' && $contract_status == WDGInvestment::$contract_status_not_validated ) {
				$payment_type = 'Non d&eacute;fini';
			}

            $page_dashboard = get_page_by_path('tableau-de-bord');
            $campaign_id_param = '?campaign_id=' . $campaign->ID;
			
			// Etat du paiement
			$payment_status_span_class = 'confirm';
			$payment_status = __( "Valid&eacute;", 'yproject' );
			if ( $post_invest_status == 'pending' ) {
				if ( strpos($payment_key, 'wire_') ) {
					$payment_status = __( "En attente de r&eacute;ception par Lemon Way", 'yproject' );
					$payment_status_span_class = 'error';
				} else if ($payment_key == 'check') {
					$payment_status = __( "En attente de validation par WE DO GOOD", 'yproject' );
					$payment_status_span_class = 'error';
					if ( $current_wdg_user->is_admin() && empty( $contract_status ) ) {
						$payment_status .= '<br><a href="' .get_permalink($page_dashboard->ID) . $campaign_id_param. '&approve_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Confirmer]</a>';
						$payment_status .= '<br><br><a href="' .get_permalink($page_dashboard->ID) . $campaign_id_param. '&cancel_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Annuler]</a>';
					}
				
				} else if ( $contract_status == WDGInvestment::$contract_status_not_validated ) {
					$payment_status = __( "Pas effectu&eacute;", 'yproject' );
					$payment_status_span_class = 'error';
					if ( $current_wdg_user->is_admin() && empty( $contract_status ) ) {
						$payment_status .= '<br><br><a href="' .get_permalink($page_dashboard->ID) . $campaign_id_param. '&try_pending_card='.$item_invest['ID'].'" style="font-size: 10pt;">[Retenter]</a>';
					}
				}
			}
			$payment_status = '<span class="payment-status-' .$payment_status_span_class. '">' .$payment_status. '</span>';
			
			// Etat de la signature
			$invest_sign_state = __( "Valid&eacute;", 'yproject' );
			$invest_sign_state_span_class = 'confirm';
			if ( $contract_status == WDGInvestment::$contract_status_preinvestment_validated ) {
				$invest_sign_state = __( "En attente de validation du pr&eacute;-investissement", 'yproject' );
				$invest_sign_state_span_class = 'error';
			} else if ( $post_invest_status == 'pending' ) {
				$invest_sign_state = __( "En attente de r&eacute;ception du paiement", 'yproject' );
				$invest_sign_state_span_class = 'error';
			} else {
				$WDGInvestmentSignature = new WDGInvestmentSignature( $item_invest[ 'ID' ] );
				if ( $WDGInvestmentSignature->is_waiting_signature() ) {
					$invest_sign_state = __( "En attente de signature électronique", 'yproject' );
					$invest_sign_state_span_class = 'error';
				}
			}
			$invest_sign_state = '<span class="payment-status-' .$invest_sign_state_span_class. '">' .$invest_sign_state. '</span>';
			

			// Contrats complémentaires
            if ( $post_invest_status == 'publish' ) {
				$more_invest["invest_contracts"] = array();
				$contract_model_index = 1;
				foreach ( $contracts_to_add as $contract_model ) {
					$wdg_contract_id = get_post_meta( $item_invest[ 'ID' ], 'amendment_contract_' . $contract_model->id, TRUE );
					$wdg_contract_status = 'Pas de contrat';
					
					if ( !empty( $wdg_contract_id ) ) {
						$wdg_contract = WDGWPREST_Entity_Contract::get( $wdg_contract_id );
						if ( $wdg_contract ) {
							
							$wdg_contract_status = 'En attente';
							if ( $wdg_contract->status == 'validated' ) {
								$wdg_contract_status = 'Contrat signé';
							} else {
								/*
								$signsquid_contract = new SignsquidContract( FALSE, $wdg_contract->partner_id );
								$signsquid_status = $signsquid_contract->get_status_code();
								if ( $signsquid_status == 'Agreed' ) {
									WDGWPREST_Entity_Contract::edit( $wdg_contract_id, 'validated' );
								}
								$wdg_contract_status = $signsquid_contract->get_status_str();
								if ( $signsquid_status == 'WaitingForSignatoryAction' && $current_wdg_user->is_admin() ) {
									$wdg_contract_status .= ' - code (admin) : ' . $signsquid_contract->get_signing_code();
								}
								 * 
								 */
								$wdg_contract_status = 'Signsquid désactivé';
							}
						}
					}
					
					$array_contacts[$u_id]['invest_contract_' .$contract_model_index] = $wdg_contract_status;
					$contract_model_index++;
				}
			}
			
			
			$invest_amount = '<span class="payment-status-' .( $post_invest_status == 'publish' ? 'success' : 'error' ). '">' .$item_invest['amount']. '</span>';
			//Si il y a déjà une ligne pour l'investissement, on rajoute une ligne
			if ( isset($array_contacts[$u_id]) && isset($array_contacts[$u_id]["invest"]) && $array_contacts[$u_id]["invest"] == 1 ) {
				$more_invest = array();
				$more_invest["invest_payment_type"] = $payment_type;
				$more_invest["invest_payment_status"] = $payment_status;
				$more_invest["invest_amount"] = $invest_amount;
				$datetime = new DateTime( get_post_field( 'post_date', $item_invest['ID'] ) );
				$datetime->add( new DateInterval( 'PT1H' ) );
				$more_invest["invest_date"] = $datetime->format( 'Y-m-d H:i:s' );
				$more_invest["invest_sign"] = $invest_sign_state;
				$more_invest["invest_id"] = $item_invest['ID'];
				array_push( $array_contacts[$u_id]["more_invest"], $more_invest );
				
			} else {
				$array_contacts[$u_id]["invest"] = 1;
				$array_contacts[$u_id]["more_invest"] = array();
				$array_contacts[$u_id]["invest_payment_type"] = $payment_type;
				$array_contacts[$u_id]["invest_payment_status"] = $payment_status;
				$array_contacts[$u_id]["invest_amount"] = $invest_amount;
				$datetime = new DateTime( get_post_field( 'post_date', $item_invest['ID'] ) );
				$datetime->add( new DateInterval( 'PT1H' ) );
				$array_contacts[$u_id]["invest_date"] = $datetime->format( 'Y-m-d H:i:s' );
				$array_contacts[$u_id]["invest_sign"] = $invest_sign_state;
				$array_contacts[$u_id]["invest_id"] = $item_invest['ID'];
				$array_contacts[$u_id]["invest_item"] = $item_invest;
			}
        }

        //Extraction infos utilisateur
		$count_distinct_investors = 0;
        foreach ( $array_contacts as $user_id => $user_item ){
            //Données si l'investisseur est une organisation
			$array_contacts[$user_id]["user_id"] = $user_id;
			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGOrganization = new WDGOrganization( $user_id );
				$linked_users = $WDGOrganization->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
				$array_contacts[$user_id]["user_id"] .= ' - contrat : ' . $linked_users[ 0 ]->get_wpref();
			}

            if(WDGOrganization::is_user_organization($user_id)){
                $orga = new WDGOrganization($user_id);
				$orga_wallet_details = $orga->get_wallet_details();
				$span_class = 'error';
				$orga_authentication = __( "Pas commenc&eacute;e", 'yproject' );
				if ( isset( $orga_wallet_details->STATUS ) && !empty( $orga_wallet_details->STATUS ) ) {
					switch ( $orga_wallet_details->STATUS ) {
						case '2':
							$orga_authentication = __( "Documents envoy&eacute;s mais incomplets", 'yproject' );
							break;
						case '3':
							$orga_authentication = __( "Documents envoy&eacute;s mais rejet&eacute;s", 'yproject' );
							break;
						case '6':
							$orga_authentication = __( "Valid&eacute;e", 'yproject' );
							$span_class = 'confirm';
							break;
						case '8':
							$orga_authentication = __( "Documents envoy&eacute;s mais expir&eacute;s", 'yproject' );
							break;
						case '10':
						case '12':
							$orga_authentication = __( "Bloqu&eacute;e", 'yproject' );
							break;
						
						case '14':
						case '15':
						case '16':
							$orga_authentication = __( "Erreur", 'yproject' );
							break;

						case '5':
						case '7':
						case '13':
						default:
							$orga_authentication = __( "En attente de documents", 'yproject' );
							break;
					}
				}
				$orga_authentication = '<span class="payment-status-' .$span_class. '">' .$orga_authentication. '</span>';
                $orga_creator = $orga->get_creator();
				$array_contacts[$user_id]["user_link"]= 'ORG - ' . $orga->get_name();
                $array_contacts[$user_id]["user_email"]= $orga->get_email();

				//Infos supplémentaires pour les votants
				if($array_contacts[$user_id]["vote"] == 1 || $array_contacts[$user_id]["invest"] == 1){
					$array_contacts[$user_id]["user_last_name"]=$orga_creator->last_name;
					$array_contacts[$user_id]["user_first_name"]=$orga_creator->first_name;
					$array_contacts[$user_id]["user_city"]= $orga->get_city();
					$array_contacts[$user_id]["user_postal_code"]= $orga->get_postal_code();
					$array_contacts[$user_id]["user_nationality"] = ucfirst(strtolower($orga->get_nationality()));
					$array_contacts[$user_id]["user_authentication"] = $orga_authentication;

					//Infos supplémentaires pour les investisseurs
					if($array_contacts[$user_id]["invest"] == 1){
						$count_distinct_investors++;
						$array_contacts[$user_id]["user_address"] = $orga->get_full_address_str();
						$array_contacts[$user_id]["user_country"] = ucfirst(strtolower($orga->get_nationality()));
						$array_contacts[$user_id]["user_mobile_phone"] = $orga_creator->get('user_mobile_phone');
						$array_contacts[$user_id]["user_orga_id"] = $orga->get_rcs() .' ('.$orga->get_idnumber().')';
					}
				}

            //Données si l'investisseur est un utilisateur normal
            } else {
				// Etat de l'authentification
				$WDGUser = new WDGUser( $user_id );
				$WDGUser_wallet_details = $WDGUser->get_wallet_details();
				$span_class = 'error';
				$user_authentication = __( "Pas commenc&eacute;e", 'yproject' );
				if ( isset( $WDGUser_wallet_details->STATUS ) && !empty( $WDGUser_wallet_details->STATUS ) ) {
					switch ( $WDGUser_wallet_details->STATUS ) {
						case '2':
							$user_authentication = __( "Documents envoy&eacute;s mais incomplets", 'yproject' );
							break;
						case '3':
							$user_authentication = __( "Documents envoy&eacute;s mais rejet&eacute;s", 'yproject' );
							break;
						case '6':
							$user_authentication = __( "Valid&eacute;e", 'yproject' );
							$span_class = 'confirm';
							break;
						case '8':
							$user_authentication = __( "Documents envoy&eacute;s mais expir&eacute;s", 'yproject' );
							break;
						case '10':
						case '12':
							$user_authentication = __( "Bloqu&eacute;e", 'yproject' );
							break;
						
						case '14':
						case '15':
						case '16':
							$user_authentication = __( "Erreur", 'yproject' );
							break;

						case '5':
						case '7':
						case '13':
						default:
							$user_authentication = __( "En attente de documents", 'yproject' );
							break;
					}
				}
				$user_authentication = '<span class="payment-status-' .$span_class. '">' .$user_authentication. '</span>';
				
				//Infos supplémentaires pour les investisseurs
				if($array_contacts[$user_id]["invest"] == 1){
					$count_distinct_investors++;
					if ( !empty( $user_item[ 'invest_item' ][ 'item' ] ) ) {
						$array_contacts[$user_id]["user_link"] = $user_item[ 'invest_item' ][ 'item' ][ 'email' ];
						$array_contacts[$user_id]["user_email"] = $user_item[ 'invest_item' ][ 'item' ][ 'email' ];
						$array_contacts[$user_id]["user_last_name"] = $user_item[ 'invest_item' ][ 'item' ][ 'lastname' ];
						$array_contacts[$user_id]["user_first_name"] = $user_item[ 'invest_item' ][ 'item' ][ 'firstname' ];
						$array_contacts[$user_id]["user_city"] = $user_item[ 'invest_item' ][ 'item' ][ 'city' ];
						$array_contacts[$user_id]["user_postal_code"] = $user_item[ 'invest_item' ][ 'item' ][ 'postalcode' ];
						$array_contacts[$user_id]["user_nationality"] = ucfirst( strtolower( $country_list[ $user_item[ 'invest_item' ][ 'item' ][ 'nationality' ] ] ) );
						$array_contacts[$user_id]["user_birthday"] = $user_item[ 'invest_item' ][ 'item' ][ 'birthday_year' ] .'-'. $user_item[ 'invest_item' ][ 'item' ][ 'birthday_month' ] .'-'. $user_item[ 'invest_item' ][ 'item' ][ 'birthday_day' ];
						$array_contacts[$user_id]["user_birthplace"] = $user_item[ 'invest_item' ][ 'item' ][ 'birthday_city' ];
						$array_contacts[$user_id]["user_address"] = $user_item[ 'invest_item' ][ 'item' ][ 'address' ];
						$array_contacts[$user_id]["user_country"] = $user_item[ 'invest_item' ][ 'item' ][ 'country' ];
						$array_contacts[$user_id]["user_mobile_phone"] = $user_item[ 'invest_item' ][ 'item' ][ 'phone_number' ];
						$array_contacts[$user_id]["user_authentication"] = $user_authentication;
						
					} else {
						$array_contacts[$user_id]["user_link"] = $WDGUser->get_email();
						$array_contacts[$user_id]["user_email"] = $WDGUser->get_email();
						$array_contacts[$user_id]["user_last_name"] = $WDGUser->get_lastname();
						$array_contacts[$user_id]["user_first_name"] = $WDGUser->get_firstname();
						$array_contacts[$user_id]["user_city"] = $WDGUser->get_city();
						$array_contacts[$user_id]["user_postal_code"] = $WDGUser->get_postal_code();
						$array_contacts[$user_id]["user_nationality"] = ucfirst( strtolower( $country_list[ $WDGUser->get_nationality() ] ) );
						$array_contacts[$user_id]["user_birthday"] = $WDGUser->get_birthday_date();
						$array_contacts[$user_id]["user_birthplace"] = $WDGUser->get_birthplace();
						$array_contacts[$user_id]["user_address"] = $WDGUser->get_full_address_str();
						$array_contacts[$user_id]["user_country"] = $WDGUser->get_country( 'full' );
						$array_contacts[$user_id]["user_mobile_phone"] = $WDGUser->get_phone_number();
						$array_contacts[$user_id]["user_authentication"] = $user_authentication;
					}
					
				//Infos supplémentaires pour les évaluateurs
				} else {
					$array_contacts[$user_id]["user_link"] = $WDGUser->get_email();
					$array_contacts[$user_id]["user_email"] = $WDGUser->get_email();
					$array_contacts[$user_id]["user_last_name"] = $WDGUser->get_lastname();
					$array_contacts[$user_id]["user_first_name"] = $WDGUser->get_firstname();
					$array_contacts[$user_id]["user_city"] = $WDGUser->get_city();
					$array_contacts[$user_id]["user_postal_code"] = $WDGUser->get_postal_code();
					$array_contacts[$user_id]["user_nationality"] = ucfirst( strtolower( $country_list[ $WDGUser->get_nationality() ] ) );
				}
				
				foreach ( $campaign_poll_answers as $answer ) {
					if ( $answer->poll_slug == 'source' && $answer->user_email == $array_contacts[ $user_id ][ 'user_email' ] ) {
						$answers_decoded = json_decode( $answer->answers );
						
						$array_contacts[ $user_id ][ 'source-how-known' ] = '';
						if ( !empty( $answers_decoded->{ 'how-the-fundraising-was-known' } ) ) {
							$source_how_known_texts = array(
								'known-by-project-manager'	=> __( "L'entrepreneur", 'yproject' ),
								'known-by-wedogood'			=> __( "WE DO GOOD", 'yproject' ),
								'known-by-other-investor'	=> __( "Un autre investisseur du projet", 'yproject' ),
								'known-by-other-source'		=> __( "Autre (presse...)", 'yproject' )
							);
							$array_contacts[ $user_id ][ 'source-how-known' ] = $source_how_known_texts[ $answers_decoded->{ 'how-the-fundraising-was-known' } ];
						}
						if ( !empty( $answers_decoded->{ 'other-source-to-know-the-fundraising' } ) ) {
							$array_contacts[ $user_id ][ 'source-how-known' ] .= ' (' . $answers_decoded->{ 'other-source-to-know-the-fundraising' } . ')';
						}
						
						$array_contacts[ $user_id ][ 'source-where-from' ] = '';
						if ( !empty( $answers_decoded->{ 'where-user-come-from' } ) ) {
							$source_come_from_texts = array(
								'mail-from-project-manager'			=> __( "Un mail du porteur de projet", 'yproject' ),
								'social-network-private-message'	=> __( "Un message priv&eacute; sur Facebook, LinkedIn, Twitter...", 'yproject' ),
								'social-network-publication'		=> __( "Une publication sur les r&eacute;seaux sociaux", 'yproject' ),
								'wedogood-site-or-newsletter'		=> __( "La newsletter ou le site de WE DO GOOD", 'yproject' ),
								'press-article'						=> __( "Un article de presse", 'yproject' ),
								'other-source'						=> __( "Autre(s) :", 'yproject' )
							);
							$array_contacts[ $user_id ][ 'source-where-from' ] = $source_come_from_texts[ $answers_decoded->{ 'where-user-come-from' } ];
						}
						if ( !empty( $answers_decoded->{ 'other-source-where-the-user-come-from' } ) ) {
							$array_contacts[ $user_id ][ 'source-where-from' ] .= ' (' . $answers_decoded->{ 'other-source-where-the-user-come-from' } . ')';
						}
					}
				}
				
            }
        }

        /*********Intitulés et paramÃ¨tres des colonnes***********/
        $status = $campaign->campaign_status();
        $display_invest_infos = false;
        if ( $status == ATCF_Campaign::$campaign_status_collecte
				|| $status == ATCF_Campaign::$campaign_status_funded
				|| $status == ATCF_Campaign::$campaign_status_closed
				|| $status == ATCF_Campaign::$campaign_status_archive ){
            $display_invest_infos = true;
        }

        $display_vote_infos = true;
        if ( $status == ATCF_Campaign::$campaign_status_collecte
				|| $status == ATCF_Campaign::$campaign_status_funded
				|| $status == ATCF_Campaign::$campaign_status_closed
				|| $status == ATCF_Campaign::$campaign_status_archive ){
            $display_vote_infos = false;
        }

        $imggood = '<img src="'.get_stylesheet_directory_uri().'/images/good.png" alt="suit" title="Suit le projet" width="30px" class="infobutton" style="margin-left:0px;"/>';
		$imggoodvote = '<img src="'.get_stylesheet_directory_uri().'/images/goodvote.png" alt="vote" title="A évalué" width="30px" class="infobutton" style="margin-left:0px;"/>';
		$imggoodmains = '<img src="'.get_stylesheet_directory_uri().'/images/goodmains.png" alt="investi" title="A investi" width="30px" class="infobutton" style="margin-left:0px;"/>';

        $array_columns = array(
        	new ContactColumn('checkbox','',true,"none"),
            new ContactColumn('user_link', 'Utilisateur', true),
			new ContactColumn('follow',$imggood.'<span class="badge-notif">'.count($list_user_follow).'</div>',true,"check","N'afficher que les contacts suivant le projet"),
			new ContactColumn('vote',$imggoodvote.'<span class="badge-notif">'.count($list_user_voters).'</div>',true,"check","N'afficher que les contacts ayant évalué"),
            new ContactColumn('invest',$imggoodmains.'<span class="badge-notif">'.$count_distinct_investors.'</div>',true,"check","N'afficher que les contacts ayant investi"),
			new ContactColumn('user_id','',false),

			new ContactColumn('user_last_name', 'Nom', true),
            new ContactColumn('user_first_name', 'Prénom', true),
            new ContactColumn('user_birthday', 'Date de naissance', false, "date"),
            new ContactColumn('user_birthplace', 'Ville de naissance', false),
            new ContactColumn('user_nationality', 'Nationalité', false),
            new ContactColumn('user_address', 'Adresse', false),
            new ContactColumn('user_city', 'Ville', true),
            new ContactColumn('user_postal_code', 'Code postal', false),
            new ContactColumn('user_country', 'Pays', false),
            new ContactColumn('user_email', 'Mail', true),
            new ContactColumn('user_mobile_phone', 'Téléphone', false),

            new ContactColumn('vote_date',"Date d'éval.",$display_vote_infos, "date"),
            new ContactColumn('vote_rate',"Note d'éval.",true),
            new ContactColumn('vote_invest_sum','Intention d\'inv.',true, "range"),
			new ContactColumn('vote_advice','Conseil',$display_vote_infos),
			new ContactColumn( 'source-how-known', 'Src. (connu)', ( $display_vote_infos || $display_invest_infos ) ),
			new ContactColumn( 'source-where-from', 'Src. (arrivée)', ( $display_vote_infos || $display_invest_infos ) ),

			new ContactColumn('invest_amount', 'Montant investi', ( $display_vote_infos || $display_invest_infos ), "range" ),
            new ContactColumn('invest_date', 'Date d\'inv.', $display_invest_infos, "date"),
            new ContactColumn('invest_payment_type', 'Moyen de paiement', ( $display_vote_infos || $display_invest_infos ) ),
            new ContactColumn('user_authentication', 'Authentification', ( $display_vote_infos || $display_invest_infos ) ),
            new ContactColumn('invest_payment_status', 'Paiement', ( $display_vote_infos || $display_invest_infos ) ),
            new ContactColumn('invest_sign', 'Signature', ( $display_vote_infos || $display_invest_infos ) )
        );
		
		if ( $contracts_to_add ) {
			$contract_model_index = 1;
			foreach ( $contracts_to_add as $contract_model ) {
				array_push( $array_columns, new ContactColumn('invest_contract_' .$contract_model_index, 'Contrat ' .$contract_model_index, $display_invest_infos) );
				$contract_model_index++;
			}
		}

        ?>
        <div class="wdg-datatable" >
        <table id="contacts-table" class="display" cellspacing="0">
            <?php //Ecriture des nom des colonnes en haut ?>
            <thead>
            <tr>
                <?php foreach($array_columns as $column) { ?>
                    <th><?php echo $column->columnName; ?></th>
                <?php }?>
            </tr>
            </thead>

            <tbody>
            <?php foreach($array_contacts as $id_contact => $data_contact): ?>
				<?php
				$has_more = array();
				if ( $data_contact["more_invest"] ){
					$has_more = $data_contact["more_invest"];
				}
				?>
				<tr data-DT_RowId="<?php echo $id_contact; ?>" data-investid="<?php echo $data_contact["invest_id"]; ?>">
					<?php foreach($array_columns as $column): ?>
                	<td>
					<?php if ( $column->columnData == "follow" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imggood; ?>

					<?php elseif ( $column->columnData == "vote" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imggoodvote; ?>

					<?php elseif ( $column->columnData == "invest" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imggoodmains; ?>
						
					<?php else: ?>
						<?php echo $data_contact[$column->columnData]; ?>
					<?php endif; ?>
					</td>
					<?php endforeach; ?>
				</tr>
				
				<?php //Gestion de plusieurs investissements par la mÃªme personne
				foreach ($has_more as $has_more_item): ?>
				<tr data-DT_RowId="<?php echo $id_contact; ?>" data-investid="<?php echo $has_more_item["invest_id"]; ?>">
					<?php foreach($array_columns as $column): ?>
                	<td>
					<?php if ( $column->columnData == "follow" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imggood; ?>

					<?php elseif ( $column->columnData == "vote" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imggoodvote; ?>

					<?php elseif ( $column->columnData == "invest" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imggoodmains; ?>
						
					<?php elseif ( $column->columnData == "invest_payment_type"
										|| $column->columnData == "invest_payment_status"
										|| $column->columnData == "invest_amount"
										|| $column->columnData == "invest_date"
										|| $column->columnData == "invest_sign" ): ?>
						<?php echo $has_more_item[$column->columnData]; ?>
						
					<?php else: ?>
						<?php echo $data_contact[$column->columnData]; ?>
					<?php endif; ?>
					</td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
				
			<?php endforeach; ?>
            </tbody>

            <tfoot>
            <tr>
                <?php
				$i = 0;
				foreach($array_columns as $column) {
                	$type_filter = $column->filterClass?>
                    <th class="<?php echo $type_filter; ?>">
						<?php
						switch ($type_filter){
							case "text":
							case "range" :
							case "date":
								echo '<input type="text" class="qtip-element" placeholder="Filtrer " data-index="'.$i.'" title="'.$column->filterQtip.'"/><br/>'.$column->columnName;
								break;
							case "check":
								echo '<input type="checkbox" class="qtip-element" data-index="'.$i.'" title="'.$column->filterQtip.'"/>';
								break;
							/*case "range":
								echo '<input type="number" placeholder="Min." /><br/><input type="number" placeholder="Max." data-index="'.$i.'"/>';
								break;
							case "date":
								echo '<input type="text" placeholder="Min." /><br/><input type="text" placeholder="Max."  data-index="'.$i.'"/>';
								break;*/
						}
						$i++;
						?>
					</th>
                <?php }?>
            </tr>
            </tfoot>
        </table>
        </div>

        <?php

        //Colonnes Ã  afficher par défaut
        $array_hidden = array();
        $i = 0;
        foreach($array_columns as $column) {
            if(!$column->defaultDisplay){
                $array_hidden[]=$i;
            }
            $i++;
        }

        //Identifiants de colonnes par lesquels seront triés les contacts par défaut
        $default_sort=false;
        $i = 0;
        foreach($array_columns as $column) {
            if($column->columnData == 'invest_date' && $display_invest_infos){
                $default_sort=$i;
            } else if($column->columnData == 'vote_date' && $display_vote_infos){
                $default_sort=$i;
            }
            $i++;
        }

        $result = array(
            'default_sort' => $default_sort,
            'array_hidden' => $array_hidden,
			'id_column_index' => 5
        );
        ?>
        <script type="text/javascript">
            var result_contacts_table = <?php echo(json_encode($result)); ?>
        </script>
        <?php
        exit();
	}

	/**
	 * Crée l'aperÃ§u du mail Ã  confirmer avant de l'envoyer (Tableau de bord)
	 */
	public static function preview_mail_message(){
		$campaign_id = filter_input(INPUT_POST, 'id_campaign');
		$errors = array();

		$title = sanitize_text_field(filter_input(INPUT_POST, 'mail_title'));
		if (empty($title)){
			$errors[]= "L'objet du mail ne peut Ãªtre vide";
		}
		$content = filter_input(INPUT_POST, 'mail_content');
		$content = WDGFormProjects::build_mail_text($content,$title,$campaign_id);

		$return_values = array(
			"response" => "preview_mail_message",
			"content" => $content,
			"errors" => $errors
		);
		echo json_encode($return_values);
		exit();
	}
	
	/**
	 * Recherche un utilisateur par son e-mail
	 */
	public static function search_user_by_email() {
		// Bug structurel à corriger : obligé d'appeler cette fonction en premier
		// Cela permet d'initialiser WDGUser::$_current
		// qui, sinon, bloque la recherche des infos utilisateurs sur l'API
		WDGUser::current();
		
		$email = filter_input(INPUT_POST, 'email');
		$user_by_email = get_user_by( 'email', $email );
		
		$user_type = FALSE;
		$user_data = FALSE;
		if ( $user_by_email != FALSE ) {
			if ( WDGOrganization::is_user_organization( $user_by_email->ID ) ) {
				$user_type = 'orga';
				$organization = new WDGOrganization( $user_by_email->ID );
				$linked_users = $organization->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
				$linked_user = $linked_users[ 0 ];
				$user_data = array(
					'user' => array(
						'firstname'			=> $linked_user->get_firstname(),
						'lastname'			=> $linked_user->get_lastname(),
						'gender'			=> $linked_user->get_gender(),
						'birthday_day'		=> $linked_user->get_birthday_day(),
						'birthday_month'	=> $linked_user->get_birthday_month(),
						'birthday_year'		=> $linked_user->get_birthday_year(),
						'birthplace'		=> $linked_user->get_birthplace(),
						'birthplace_department'		=> $linked_user->get_birthplace_department(),
						'birthplace_district'		=> $linked_user->get_birthplace_district(),
						'birthplace_country'		=> $linked_user->get_birthplace_country(),
						'nationality'				=> $linked_user->get_nationality(),
						'address_number'			=> $linked_user->get_address_number(),
						'address_number_complement'	=> $linked_user->get_address_number_complement(),
						'address'					=> $linked_user->get_address(),
						'postal_code'				=> $linked_user->get_postal_code(),
						'city'						=> $linked_user->get_city(),
						'country'					=> $linked_user->get_country()
					),
					'orga' => array(
						'email'				=> $organization->get_email(),
						'name'				=> $organization->get_name()
					)
				);
			} else {
				$user_type = 'user';
				$user = new WDGUser( $user_by_email->ID );
				$user_data = array(
					'user'		=> array(
						'firstname'			=> $user->get_firstname(),
						'lastname'			=> $user->get_lastname(),
						'gender'			=> $user->get_gender(),
						'birthday_day'		=> $user->get_birthday_day(),
						'birthday_month'	=> $user->get_birthday_month(),
						'birthday_year'		=> $user->get_birthday_year(),
						'birthplace'		=> $user->get_birthplace(),
						'birthplace_department'		=> $user->get_birthplace_department(),
						'birthplace_district'		=> $user->get_birthplace_district(),
						'birthplace_country'		=> $user->get_birthplace_country(),
						'nationality'				=> $user->get_nationality(),
						'address_number'			=> $user->get_address_number(),
						'address_number_complement'	=> $user->get_address_number_complement(),
						'address'					=> $user->get_address(),
						'postal_code'				=> $user->get_postal_code(),
						'city'						=> $user->get_city(),
						'country'					=> $user->get_country()
					),
					'orga'		=> FALSE,
					'orga_list'	=> array()
				);
				$user_organizations_list = $user->get_organizations_list();
				foreach( $user_organizations_list as $organization_item ) {
					$WDGOrganization = new WDGOrganization( $organization_item->wpref );
					$single_orga_data = array(
						'wpref'		=> $WDGOrganization->get_wpref(),
						'name'		=> $WDGOrganization->get_name(),
						'email'		=> $WDGOrganization->get_email(),
						'website'	=> $WDGOrganization->get_website(),
						'legalform'	=> $WDGOrganization->get_legalform(),
						'idnumber'	=> $WDGOrganization->get_idnumber(),
						'rcs'		=> $WDGOrganization->get_rcs(),
						'capital'	=> $WDGOrganization->get_capital(),
						'address_number'		=> $WDGOrganization->get_address_number(),
						'address_number_comp'	=> $WDGOrganization->get_address_number_comp(),
						'address'				=> $WDGOrganization->get_address(),
						'postal_code'			=> $WDGOrganization->get_postal_code(),
						'city'					=> $WDGOrganization->get_city(),
						'nationality'			=> $WDGOrganization->get_nationality()
					);
					array_push( $user_data[ 'orga_list' ], $single_orga_data );
				}
			}
		}
		
		$return_values = array(
			'user_type'	=> $user_type,
			'user_data'	=> $user_data
		);
		
		echo json_encode( $return_values );
		exit();
	}
	
	public static function get_current_investment_signature_status() {
		$posted_paymentid = filter_input( INPUT_POST, 'paymentid' );
		$WDGInvestmentContract = new WDGInvestmentContract( $posted_paymentid );
		if ( $WDGInvestmentContract->get_status_code() == WDGInvestmentContract::$status_code_agreed ) {
			WDGInvestment::unset_session();
			echo '1';
		} else {
			echo '0';
		}
	}
		
	public static function apply_draft_data() {
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$orga_id = filter_input( INPUT_POST, 'orga_id' );
		$draft_id = filter_input( INPUT_POST, 'draft_id' );
		$data_type = filter_input( INPUT_POST, 'data_type' );
		$data_value = filter_input( INPUT_POST, 'data_value' );
		
		$gender = FALSE;
		$firstname = FALSE;
		$lastname = FALSE;
		$birthday_date_day = FALSE;
		$birthday_date_month = FALSE;
		$birthday_date_year = FALSE;
		$birthplace = FALSE;
		$birthplace_district = FALSE;
		$birthplace_department = FALSE;
		$birthplace_country = FALSE;
		$nationality = FALSE;
		$address_number = FALSE;
		$address_number_complement = FALSE;
		$address = FALSE;
		$postal_code = FALSE;
		$city = FALSE;
		$country = FALSE;
		$has_modified_organization = false;
		if ( !empty( $orga_id ) ) {
			$WDGOrganization = new WDGOrganization( $orga_id );
		}
		
		if ( $data_type == 'all' ) {
			$investments_drafts_item = WDGWPREST_Entity_InvestmentDraft::get( $draft_id );
			$investments_drafts_item_data = json_decode( $investments_drafts_item->data );
			$gender = $investments_drafts_item_data->gender;
			$firstname = $investments_drafts_item_data->firstname;
			$lastname = $investments_drafts_item_data->lastname;
			$birthday_date = DateTime::createFromFormat( 'd/m/Y', $investments_drafts_item_data->birthday );
			$birthday_date_day = $birthday_date->format( 'd' );
			$birthday_date_month = $birthday_date->format( 'm' );
			$birthday_date_year = $birthday_date->format( 'Y' );
			$birthplace = $investments_drafts_item_data->birthplace;
			$birthplace_district = $investments_drafts_item_data->birthplace_district;
			$birthplace_department = $investments_drafts_item_data->birthplace_department;
			$birthplace_country = $investments_drafts_item_data->birthplace_country;
			$nationality = $investments_drafts_item_data->nationality;
			$address_number = $investments_drafts_item_data->address_number;
			$address_number_complement = $investments_drafts_item_data->address_number_complement;
			$address = $investments_drafts_item_data->address;
			$postal_code = $investments_drafts_item_data->postal_code;
			$city = $investments_drafts_item_data->city;
			$country = $investments_drafts_item_data->country;
			
			if ( !empty( $orga_id ) ) {
				$has_modified_organization = true;
				$WDGOrganization->set_name( $investments_drafts_item_data->orga_name );
				$WDGOrganization->set_email( $investments_drafts_item_data->orga_email );
				$WDGOrganization->set_website( $investments_drafts_item_data->orga_website );
				$WDGOrganization->set_legalform( $investments_drafts_item_data->orga_legalform );
				$WDGOrganization->set_idnumber( $investments_drafts_item_data->orga_idnumber );
				$WDGOrganization->set_rcs( $investments_drafts_item_data->orga_rcs );
				$WDGOrganization->set_capital( $investments_drafts_item_data->orga_capital );
				$WDGOrganization->set_address_number( $investments_drafts_item_data->orga_address_number );
				$WDGOrganization->set_address_number_comp( $investments_drafts_item_data->orga_address_number_comp );
				$WDGOrganization->set_address( $investments_drafts_item_data->orga_address );
				$WDGOrganization->set_postal_code( $investments_drafts_item_data->orga_postal_code );
				$WDGOrganization->set_city( $investments_drafts_item_data->orga_city );
				$WDGOrganization->set_nationality( $investments_drafts_item_data->orga_nationality );
			}
			
		} else {
			$data_value_decoded = html_entity_decode( $data_value );
			switch ( $data_type ) {
				case 'gender':
					$gender = $data_value_decoded;
					break;
				case 'firstname':
					$firstname = $data_value_decoded;
					break;
				case 'lastname':
					$lastname = $data_value_decoded;
					break;
				case 'birthday':
					$birthday_date = new DateTime( $data_value_decoded );
					$birthday_date_day = $birthday_date->format( 'd' );
					$birthday_date_month = $birthday_date->format( 'm' );
					$birthday_date_year = $birthday_date->format( 'Y' );
					break;
				case 'birthplace':
					$birthplace = $data_value_decoded;
					break;
				case 'birthplace_district':
					$birthplace_district = $data_value_decoded;
					break;
				case 'birthplace_department':
					$birthplace_department = $data_value_decoded;
					break;
				case 'birthplace_country':
					$birthplace_country = $data_value_decoded;
					break;
				case 'nationality':
					$nationality = $data_value_decoded;
					break;
				case 'address_number':
					$address_number = $data_value_decoded;
					break;
				case 'address_number_complement':
					$address_number_complement = $data_value_decoded;
					break;
				case 'address':
					$address = $data_value_decoded;
					break;
				case 'postal_code':
					$postal_code = $data_value_decoded;
					break;
				case 'city':
					$city = $data_value_decoded;
					break;
				case 'country':
					$country = $data_value_decoded;
					break;
				
				case 'orga_name':
					$has_modified_organization = true;
					$WDGOrganization->set_name( $data_value_decoded );
					break;
				case 'orga_email':
					$has_modified_organization = true;
					$WDGOrganization->set_email( $data_value_decoded );
					break;
				case 'orga_website':
					$has_modified_organization = true;
					$WDGOrganization->set_website( $data_value_decoded );
					break;
				case 'orga_legalform':
					$has_modified_organization = true;
					$WDGOrganization->set_legalform( $data_value_decoded );
					break;
				case 'orga_idnumber':
					$has_modified_organization = true;
					$WDGOrganization->set_idnumber( $data_value_decoded );
					break;
				case 'orga_rcs':
					$has_modified_organization = true;
					$WDGOrganization->set_rcs( $data_value_decoded );
					break;
				case 'orga_capital':
					$has_modified_organization = true;
					$WDGOrganization->set_capital( $data_value_decoded );
					break;
				case 'orga_address_number':
					$has_modified_organization = true;
					$WDGOrganization->set_address_number( $data_value_decoded );
					break;
				case 'orga_address_number_comp':
					$has_modified_organization = true;
					$WDGOrganization->set_address_number_comp( $data_value_decoded );
					break;
				case 'orga_address':
					$has_modified_organization = true;
					$WDGOrganization->set_address( $data_value_decoded );
					break;
				case 'orga_postal_code':
					$has_modified_organization = true;
					$WDGOrganization->set_postal_code( $data_value_decoded );
					break;
				case 'orga_city':
					$has_modified_organization = true;
					$WDGOrganization->set_city( $data_value_decoded );
					break;
				case 'orga_nationality':
					$has_modified_organization = true;
					$WDGOrganization->set_nationality( $data_value_decoded );
					break;
			}
		}
		
		$WDGUser = new WDGUser( $user_id );
		$WDGUser->save_data(
			FALSE, $gender, $firstname, $lastname, FALSE,
			$birthday_date_day, $birthday_date_month, $birthday_date_year,
			$birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality,
			$address_number, $address_number_complement, $address, $postal_code, $city, $country,
			FALSE, FALSE, FALSE, FALSE
		);
		
		if ( $has_modified_organization ) {
			$WDGOrganization->save();
		}
		
		_e( "Sauvegard&eacute;" );
		exit();
	}
	
	public static function create_investment_from_draft() {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$campaign = new ATCF_Campaign( $campaign_id );
		$draft_id = filter_input( INPUT_POST, 'draft_id' );
		
		// Création éventuelle des investisseurs / organisations
		$investments_drafts_item = WDGWPREST_Entity_InvestmentDraft::get( $draft_id );
		$investments_drafts_item_data = json_decode( $investments_drafts_item->data );
		
		$user_existing_by_email = get_user_by( 'email', $investments_drafts_item_data->email );
		$id_linked_user = FALSE;
		if ( !empty( $user_existing_by_email ) ) {
			$id_linked_user = $user_existing_by_email->ID;
		}
		$id_linked_organization = FALSE;
		if ( $investments_drafts_item_data->user_type == 'orga' && $investments_drafts_item_data->orga_id != 'new-orga' ) {
			$id_linked_organization = $investments_drafts_item_data->orga_id;
		}
		
		// Création compte investisseur si non-existant
		if ( empty( $id_linked_user ) ) {
			$new_password = wp_generate_password( 8, FALSE );
			$new_display_name = $investments_drafts_item_data->firstname. ' ' .substr( $investments_drafts_item_data->lastname, 0, 1 ). '.';
			$id_linked_user = wp_insert_user( array(
				'user_login'	=> $investments_drafts_item_data->email,
				'user_pass'		=> $new_password,
				'user_email'	=> $investments_drafts_item_data->email,
				'first_name'	=> $investments_drafts_item_data->firstname,
				'last_name'		=> $investments_drafts_item_data->lastname,
				'display_name'	=> $new_display_name,
				'user_nicename' => sanitize_title( $new_display_name )
			) );
			$birthday_date = DateTime::createFromFormat( 'd/m/Y', $investments_drafts_item_data->birthday );
			$birthday_date_day = $birthday_date->format( 'd' );
			$birthday_date_month = $birthday_date->format( 'm' );
			$birthday_date_year = $birthday_date->format( 'Y' );
			$WDGUser_new = new WDGUser( $id_linked_user );
			$WDGUser_new->save_data(
				FALSE, $investments_drafts_item_data->gender, $investments_drafts_item_data->firstname, $investments_drafts_item_data->lastname, FALSE,
				$birthday_date_day, $birthday_date_month, $birthday_date_year,
				$investments_drafts_item_data->birthplace, $investments_drafts_item_data->birthplace_district, $investments_drafts_item_data->birthplace_department, $investments_drafts_item_data->birthplace_country, $investments_drafts_item_data->nationality,
				$investments_drafts_item_data->address_number, $investments_drafts_item_data->address_number_complement, $investments_drafts_item_data->address, $investments_drafts_item_data->postal_code, $investments_drafts_item_data->city, $investments_drafts_item_data->country,
				FALSE, FALSE, FALSE, FALSE
			);
			
			// Notification de création de compte
			NotificationsEmails::investment_draft_validated_new_user( $investments_drafts_item_data->email, $investments_drafts_item_data->firstname, $new_password, $campaign->get_name() );
		}
		
		// Création compte organisation si non-existant
		if ( $investments_drafts_item_data->user_type == 'orga' && empty( $id_linked_organization ) ) {
			$WDGOrganization = WDGOrganization::createSimpleOrganization( $id_linked_user, $investments_drafts_item_data->orga_name, $investments_drafts_item_data->orga_email );
			$WDGOrganization->set_name( $investments_drafts_item_data->orga_name );
			$WDGOrganization->set_email( $investments_drafts_item_data->orga_email );
			$WDGOrganization->set_website( $investments_drafts_item_data->orga_website );
			$WDGOrganization->set_legalform( $investments_drafts_item_data->orga_legalform );
			$WDGOrganization->set_idnumber( $investments_drafts_item_data->orga_idnumber );
			$WDGOrganization->set_rcs( $investments_drafts_item_data->orga_rcs );
			$WDGOrganization->set_capital( $investments_drafts_item_data->orga_capital );
			$WDGOrganization->set_address_number( $investments_drafts_item_data->orga_address_number );
			$WDGOrganization->set_address_number_comp( $investments_drafts_item_data->orga_address_number_comp );
			$WDGOrganization->set_address( $investments_drafts_item_data->orga_address );
			$WDGOrganization->set_postal_code( $investments_drafts_item_data->orga_postal_code );
			$WDGOrganization->set_city( $investments_drafts_item_data->orga_city );
			$WDGOrganization->set_nationality( $investments_drafts_item_data->orga_nationality );
			$WDGOrganization->save();
			$id_linked_organization = $WDGOrganization->get_wpref();
		}
		
		// Enregistrement investissement
		$investment_id = $campaign->add_investment(
			'check', $investments_drafts_item_data->email, $investments_drafts_item_data->invest_amount, 'publish',
			'', '', 
			'', '', '', 
			'', '', '', '', '', 
			'', '', '', '', '', 
			$investments_drafts_item_data->orga_email
		);
		add_post_meta( $investment_id, 'created-from-draft', $investments_drafts_item->id );
			
		// Notifications de validation d'investissement
		NotificationsEmails::new_purchase_user_success_check( $investment_id );
		NotificationsEmails::new_purchase_team_members( $investment_id );
		NotificationsSlack::send_new_investment( $campaign->get_name(), $investments_drafts_item_data->invest_amount, $investments_drafts_item_data->email );
		
		// Valider le draft
		WDGWPREST_Entity_InvestmentDraft::edit( $investments_drafts_item->id, 'validated' );
		
		echo 'ok';
		exit();
	}
	
	/**
	 * Lance les transferts d'argent vers les différents investisseurs
	 */
	public static function proceed_roi_transfers() {
		$buffer = FALSE;
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$declaration_id = filter_input(INPUT_POST, 'roi_id');
		if ( !empty( $campaign_id ) && !empty( $declaration_id ) && $WDGUser_current->is_admin() ) {
			$input_send_notifications = filter_input( INPUT_POST, 'send_notifications' );
			$send_notifications = ( $input_send_notifications != 'false' && ( $input_send_notifications === 1 || $input_send_notifications === TRUE || $input_send_notifications === 'true' ) );
			$input_transfer_remaining_amount = filter_input( INPUT_POST, 'transfer_remaining_amount' );
			$transfer_remaining_amount = ( $input_transfer_remaining_amount != 'false' && ( $input_transfer_remaining_amount === 1 || $input_transfer_remaining_amount === TRUE || $input_transfer_remaining_amount === 'true' ) );
			$roi_declaration = new WDGROIDeclaration( $declaration_id );
			$buffer = $roi_declaration->make_transfer( $send_notifications, $transfer_remaining_amount );
		}
		
		echo json_encode( $buffer );
		exit();
	}
	
	/**
	 * Lance la finalisation du projet (transfert des données d'investissement sur l'API, ...)
	 */
	public static function conclude_project() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		
		if ( $WDGUser_current->is_admin() && !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded
					|| $campaign->campaign_status() == ATCF_Campaign::$campaign_status_archive
					|| $campaign->campaign_status() == ATCF_Campaign::$campaign_status_closed ) {
				
				// Transfert des données d'investissement sur l'API
				if ( !$campaign->has_investments_in_api() ) {
					WDGInvestment::save_campaign_to_api( $campaign );
				}
				
				// Transformer les investissements en contrats d'investissement sur l'API
				WDGInvestmentContract::create_list( $campaign_id );

				// TODO : rassembler les contrats dans un zip
			}
		}
		
		exit( '1' );
	}

	/**
	 * Bloque les autres utilisateurs si édition en cours
	 */
	public static function try_lock_project_edition() {
		$WDGuser_current = WDGUser::current();
		$user_id = $WDGuser_current->wp_user->ID;
		$current_datetime = new DateTime();
		$key_exists = TRUE;

	    $campaign_id = filter_input( INPUT_POST, 'id_campaign' );
	    $content = filter_input( INPUT_POST, 'value' );
	    $property = filter_input( INPUT_POST, 'property' );
	    $lang = filter_input( INPUT_POST, 'lang' );
	    $meta_key = $property.'_add_value_reservation_'.$lang;

	    $meta_value = array( 'user' => $user_id, 'date' => $current_datetime->format('Y-m-d H:i:s') );

	    $reservation_key = get_post_meta( $campaign_id, $meta_key, TRUE );
		if ( empty($reservation_key) ) {
			$key_exists = FALSE;
		} 		
		
		$return_values = array(
					"response" => "done",
					"values" => $property
		);

		$campaign = new ATCF_Campaign( $campaign_id );
		if ( $key_exists ) {
			$activity = $campaign->is_user_editing_meta( $user_id, $meta_key );
			if ( !$activity ) {
				update_post_meta($campaign_id, $meta_key, $meta_value );

				echo json_encode($return_values);
				wp_die();
			} else {
				$WDGUser = new WDGUser( $reservation_key[ 'user' ] );
				$name = $WDGUser->get_firstname()." ".$WDGUser->get_lastname();
					
				$return_values = array(
					"response" => "error",
					"values" => $name
				);
				echo json_encode($return_values);
				wp_die();
			}
		} else {
			$different_content = $campaign->is_different_content( $content, $property, $lang );
			if ( !$different_content ) {
				update_post_meta($campaign_id, $meta_key, $meta_value );

				echo json_encode($return_values);
				wp_die();
			} else {
				$return_values = array(
					"response" => "different_content"
				);
				echo json_encode($return_values);
				wp_die();
			}
		}

		exit();	
	}

	public static function keep_lock_project_edition() {
		$WDGuser_current = WDGUser::current();
		$user_id = $WDGuser_current->wp_user->ID;
		$current_datetime = new DateTime();

	    $campaign_id = filter_input( INPUT_POST, 'id_campaign' );
	    $property = filter_input( INPUT_POST, 'property' );
	    $lang = filter_input( INPUT_POST, 'lang' );

	    $meta_value = array( 'user' => $user_id, 'date' => $current_datetime->format('Y-m-d H:i:s') );
		$meta_key = $property.'_add_value_reservation_'.$lang;
		$meta_old_value = get_post_meta( $campaign_id, $meta_key, TRUE );
		$WDGUser = new WDGUser( $meta_old_value[ 'user' ] );
		$name = $WDGUser->get_firstname()." ".$WDGUser->get_lastname();

		$return_values = array(
			"response" => "done",
			"values" => $property,
			"user" => $name
		);

		if ( !empty($meta_old_value) ) {
		    if ( $meta_old_value[ 'user' ] != $user_id ) {
		    	$return_values[ 'response' ] = "error";
		    	echo json_encode($return_values);
		    	wp_die();
		    } else {
				update_post_meta($campaign_id, $meta_key, $meta_value );
				echo json_encode($return_values);
				wp_die();
		    }
		 }

		exit();
	}

	public static function delete_lock_project_edition() {
		$campaign_id = filter_input( INPUT_POST, 'id_campaign' );
	    $property = filter_input( INPUT_POST, 'property' );
	    $lang = filter_input( INPUT_POST, 'lang' );
	    $meta_key = $property.'_add_value_reservation_'.$lang;

		delete_post_meta( $campaign_id, $meta_key );
		echo $property ;
		wp_die();

		exit();
	}
}

/**
 * Class ContactColumn utilisé pour les colonnes du tableau de contacts
 */
class ContactColumn {
    public $columnData;
    public $columnName;
    public $defaultDisplay = false;
	public $filterClass = "text";
	public $filterQtip = "";

    function ContactColumn ($newColumnData, $newColumnName, $newDefaultDisplay=false, $newFilterClass = "text", $newFilterQtip = "") {
        $this->columnData = $newColumnData;
        $this->columnName = $newColumnName;
        $this->defaultDisplay = $newDefaultDisplay;
		$this->filterClass = $newFilterClass;
		$this->filterQtip = $newFilterQtip;
    }
}