<?php
/**
 * Gestion des appels Ajax en provenance de Mon compte
 */
class WDGAjaxActionsUserAccount {
	/**
	 * Affiche la liste des investissements d'un utilisateur
	 */
	public static function display_user_investments() {
		$buffer = array();

		$user_id = filter_input( INPUT_POST, 'user_id' );
		$user_type = filter_input( INPUT_POST, 'user_type' );

		$WDGUser_current = WDGUser::current();
		$can_access = FALSE;
		$is_authentified = FALSE;
		if ( $user_type == 'user' ) {
			$WDGUserEntity = new WDGUser( $user_id );
			$is_authentified = $WDGUserEntity->is_lemonway_registered();
			$can_access = ( $WDGUser_current->get_wpref() == $WDGUserEntity->get_wpref() ) || ( $WDGUser_current->is_admin() );
		} else {
			$WDGUserEntity = new WDGOrganization( $user_id );
			$is_authentified = $WDGUserEntity->is_registered_lemonway_wallet();
			$can_access = $WDGUser_current->can_edit_organization( $WDGUserEntity );
		}

		if ( !$can_access ) {
			exit( '' );
		}

		$investment_contracts = WDGWPREST_Entity_User::get_investment_contracts( $WDGUserEntity->get_api_id() );

		$today_datetime = new DateTime();
		$payment_status = array( 'publish', 'completed', 'pending' );
		$user_investments = new WDGUserInvestments( $WDGUserEntity );
		$purchases = $user_investments->get_posts_investments( $payment_status );

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

		foreach ( $purchases as $purchase_post ) {
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
				$purchase_status = get_post_status( $purchase_id );
				$downloads = edd_get_payment_meta_downloads( $purchase_id );
				if ( !is_array( $downloads[ 0 ] ) ) {
					$campaign_id = $downloads[ 0 ];
				} else {
					if ( isset( $downloads[ 0 ][ 'id' ] ) ) {
						$campaign_id = $downloads[ 0 ][ 'id' ];
					}
				}
				$campaign = atcf_get_campaign( $campaign_id );
				if ( $campaign->campaign_status() != ATCF_Campaign::$campaign_status_vote && $campaign->campaign_status() != ATCF_Campaign::$campaign_status_collecte && $purchase_status == 'pending' ) {
					continue;
				}
				$payment_amount = edd_get_payment_amount( $purchase_id );
				$purchase_date = get_post_field( 'post_date', $purchase_id );

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
				$exp = dirname( __FILE__ ). '/../../../pdf_files/' .$campaign_id. '_' .$user_id. '_*.pdf';
				$files = glob( $exp );

				if ( !isset( $buffer[ $campaign_id ] ) ) {
					$buffer[ $campaign_id ] = array();
					$buffer[ $campaign_id ][ 'name' ] = $campaign->data->post_title;
					$buffer[ $campaign_id ][ 'status' ] = utf8_encode( $campaign->campaign_status() );
					$buffer[ $campaign_id ][ 'amount' ] = utf8_encode( $campaign->current_amount( false ) );
					$buffer[ $campaign_id ][ 'start_date' ] = '';
					if ( $campaign->contract_start_date_is_undefined() != '1' ) {
						$contract_start_date = new DateTime( $campaign->contract_start_date() );
						$buffer[ $campaign_id ][ 'start_date' ] = date_i18n( 'F Y', strtotime( $campaign->contract_start_date() ) );
					}
					$buffer[ $campaign_id ][ 'funding_duration' ] = utf8_encode( $campaign->funding_duration() );
					$buffer[ $campaign_id ][ 'roi_percent' ] = utf8_encode( $campaign->roi_percent() );
					$buffer[ $campaign_id ][ 'roi_percent_estimated' ] = utf8_encode( $campaign->roi_percent_estimated() );
					$buffer[ $campaign_id ][ 'items' ] = array();
				}

				if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded || $campaign->campaign_status() == ATCF_Campaign::$campaign_status_closed ) {
					$investor_proportion = $payment_amount / $buffer[ $campaign_id ][ 'amount' ];
				} else {
					$investor_proportion = $payment_amount / $campaign->goal( FALSE );
				}
				$roi_percent_full_estimated = ( $buffer[ $campaign_id ][ 'roi_percent_estimated' ] * $payment_amount / $campaign->goal( FALSE ) );
				$roi_percent_full = ( $buffer[ $campaign_id ][ 'roi_percent' ] * $investor_proportion );
				$roi_percent_display = round( $roi_percent_full * 10000 ) / 10000;
				$roi_amount = 0;
				foreach ( $roi_list as $roi_item ) {
					if ( $roi_item->status != WDGROI::$status_canceled && $roi_item->status != WDGROI::$status_waiting_transfer ) {
						$roi_amount += $roi_item->amount;
					}
				}

				$investment_item = array();
				if ( $WDGUser_current->is_admin() ) {
					$investment_item[ 'can_edit' ] = $purchase_post->ID;
				}
				$investment_item[ 'date' ] = date_i18n( 'j F Y', strtotime( $purchase_date ) );
				$investment_item[ 'hour' ] = date_i18n( 'H\hi', strtotime( $purchase_date ) );
				$investment_item[ 'amount' ] = utf8_encode( $payment_amount );
				$investment_item[ 'status' ] = utf8_encode( $purchase_status );
				$investment_item[ 'status_str' ] = '-';
				$investment_item[ 'payment_str' ] = '';
				$investment_item[ 'payment_date' ] = '';

				if ( $purchase_status == 'pending' ) {
					$WDGInvestment = new WDGInvestment( $purchase_post->ID );
					if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_archive ) {
						$investment_item[ 'status_str' ] = __( "Annul&eacute;", 'yproject' );
						$date_end = new DateTime( $campaign->end_date() );
						$date_end->add( new DateInterval( 'P15D' ) );
						if ( $today_datetime < $date_end ) {
							$investment_item[ 'status_str' ] = __( "En suspend", 'yproject' );
						}
					} else {
						$payment_key = $WDGInvestment->get_payment_key();
						if ( strpos( $payment_key, 'wire_' ) !== FALSE || $payment_key == 'check' ) {
							$investment_item[ 'status_str' ] = __( "En attente de paiement", 'yproject' );
						} elseif ( $WDGInvestment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated ) {
							$investment_item[ 'status_str' ] = __( "A valider", 'yproject' );
						}
					}
				} elseif ( $purchase_status == 'publish' ) {
					if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
						$investment_item[ 'status_str' ] = __( "Valid&eacute;", 'yproject' );
					} elseif ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_closed ) {
						$investment_item[ 'status' ] = 'canceled';
						$investment_item[ 'status_str' ] = __( "Versements termin&eacute;s", 'yproject' );
					} elseif ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_archive ) {
						$investment_item[ 'status_str' ] = __( "Annul&eacute;", 'yproject' );
						$date_end = new DateTime( $campaign->end_date() );
						$date_end->add( new DateInterval( 'P15D' ) );
						if ( $today_datetime < $date_end ) {
							$investment_item[ 'status_str' ] = __( "En suspend", 'yproject' );
						}
					} elseif ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded ) {
						$investment_item[ 'status_str' ] = __( 'account.investments.STARTED_CONTRACT', 'yproject' );
						$date_first_payement = new DateTime( $campaign->first_payment_date() );
						if ( $today_datetime > $date_first_payement ) {
							$investment_item[ 'payment_str' ] = __( 'account.investments.NEXT_PAYMENT', 'yproject' );
						} else {
							$investment_item[ 'payment_str' ] = __( 'account.investments.FIRST_PAYMENT', 'yproject' );
							$investment_item[ 'payment_date' ] = date_i18n( 'F Y', strtotime( $campaign->first_payment_date() ) );
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
						$investment_item[ 'conclude-investment-url' ] = WDG_Redirect_Engine::override_get_page_url( 'investir' ) . '?init_with_id=' .$purchase_post->ID. '&campaign_id=' .$campaign_id;

						// On ne garde l'affichage de ces investissements en attente que si il est encore possible de les finaliser (on annule si ce n'est pas le cas)
						if ( $campaign->campaign_status() != ATCF_Campaign::$campaign_status_vote && $campaign->campaign_status() != ATCF_Campaign::$campaign_status_collecte ) {
							$WDGInvestment->cancel();
							continue;
						}
					}
				}
				$investment_item[ 'roi_percent' ] = utf8_encode( $roi_percent_display );
				$investment_item[ 'roi_amount' ] = utf8_encode( round( $roi_amount, 2 ) );
				$investment_item[ 'roi_return' ] = utf8_encode( round( $investment_item[ 'roi_amount' ] / $payment_amount * 100 ) / 100 );

				$investment_item[ 'contract_file_path' ] = '';
				$investment_item[ 'contract_file_name' ] = '';

				// Index du contrat à aller chercher
				// A ce moment là, l'investissement n'est pas encore ajouté au tableau,
				// donc l'index du contrat est bien le count du tableau (= au début, c'est 0)
				$contract_index = count( $buffer[ $campaign_id ][ 'items' ] );
				// Fichier de contrat
				// on commence par regarder si on a un contrat stocké ici  : API\wp-content\plugins\wdgrestapi\files\investment-draft
				// ce sont les photos des contrats et chèques ajoutés par l'admin
				// pour ça, il nous faut retrouver un éventuel post_meta de type 'created-from-draft'
				$created_from_draft = get_post_meta( $purchase_post->ID, 'created-from-draft', TRUE );
				if ($created_from_draft) {
					// si c'est le cas, alors on récupère l'investment-draft, et on vérifie s'il y a une photo de contrat associé
					$investments_drafts_item = WDGWPREST_Entity_InvestmentDraft::get( $created_from_draft );
					$investment_item[ 'contract_file_path' ] = $investments_drafts_item->contract;
					$path_parts = pathinfo($investments_drafts_item->contract);
					$extension = $path_parts['extension'];
					$investment_item[ 'contract_file_name' ] = __( "contrat-investissement-", 'yproject' ) .$campaign->data->post_name. '-'  .($contract_index + 1). '.' .$extension;
				}
				// sinon, on va récupérer le contrat en pdf tel qu'il a été généré
				if ($investment_item[ 'contract_file_path' ] == '' ) {
					$download_filename = __( "contrat-investissement-", 'yproject' ) .$campaign->data->post_name. '-'  .($contract_index + 1). '.pdf';
					$test_file_name = dirname( __FILE__ ). '/../../../../files/contracts/campaigns/' .$campaign_id. '-' .$campaign->get_url(). '/' .$purchase_id. '.pdf';
					if ( file_exists( $test_file_name ) ) {
						$investment_item[ 'contract_file_path' ] = site_url( '/wp-content/plugins/appthemer-crowdfunding/files/contracts/campaigns/' .$campaign_id. '-' .$campaign->get_url(). '/' .$purchase_id. '.pdf' );
						$investment_item[ 'contract_file_name' ] = $download_filename;
					} elseif ( count( $files ) ) {
						$filelist_extract = explode( '/', $files[ $contract_index ] );
						$contract_filename = $filelist_extract[ count( $filelist_extract ) - 1 ];
						$investment_item[ 'contract_file_path' ] = site_url( '/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/' . $contract_filename );
						$investment_item[ 'contract_file_name' ] = $download_filename;
					}
				}

				//*****
				// Echéancier de royalties

				// Création du tableau des prévisionnels par année
				$investment_item[ 'rois_by_year' ] = array();
				$year_end_dates = array();
				$estimated_turnover_list = FALSE;
				$campaign_roi_list = FALSE;
				if ( $campaign->campaign_status() != ATCF_Campaign::$campaign_status_archive ) {
					$estimated_turnover_list = $campaign->estimated_turnover();
					$campaign_roi_list = WDGROIDeclaration::get_list_by_campaign_id( $campaign_id );
				}
				$estimated_turnover_unit = $campaign->estimated_turnover_unit();

				if ( !empty( $estimated_turnover_list ) ) {
					// On démarre de la date de démarrage du contrat
					$contract_start_date = new DateTime( $campaign->contract_start_date() );
					$contract_start_date->setDate( $contract_start_date->format( 'Y' ), $contract_start_date->format( 'm' ), 21 );

					$maximum_profit = $campaign->maximum_profit();
					$estimated_rois_total = $maximum_profit * $payment_amount;
					foreach ( $estimated_turnover_list as $key => $turnover ) {
						$estimated_rois = 0;
						if ( $estimated_turnover_unit == 'percent' ) {
							$estimated_rois = round( $turnover * $payment_amount / 100 );
							$turnover = round( $campaign->current_amount( FALSE ) * $turnover / 100 );
						} else {
							if ( !empty( $roi_percent_full ) ) {
								$estimated_rois = round( $turnover * $roi_percent_full / 100 );
							} else {
								$estimated_rois = round( $turnover * $roi_percent_full_estimated / 100 );
							}
						}
						$estimated_rois = min( $estimated_rois, $estimated_rois_total );
						$estimated_rois_total -= $estimated_rois;

						$year_item = array(
							'amount_turnover_nb'=> 0,
							'amount_turnover'	=> '0 &euro;',
							'estimated_turnover'=> YPUIHelpers::display_number( $turnover, TRUE, 0 ) . ' &euro;',
							'estimated_rois'	=> YPUIHelpers::display_number( $estimated_rois, TRUE ) . ' &euro;',
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
								'amount_turnover_nb'=> 0,
								'amount_turnover'	=> '0 &euro;',
								'estimated_turnover'=> '-',
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
									if ( $investment_item[ 'payment_date' ]  == '') {
										$investment_item[ 'payment_date' ] = $roi_item[ 'date' ];
									}
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
								if ( $investment_item[ 'payment_date' ]  == '') {
									$investment_item[ 'payment_date' ] = $roi_item[ 'date' ];
								}
								break;
						}

						if ( $roi_item[ 'status' ] != 'upcoming' || empty( $first_investment_contract ) || $first_investment_contract->status != 'canceled' ) {
							$has_found_roi = false;

							// Si il y a eu un versement de royalties, on récupère les infos du versement
							if ( $roi_item[ 'status' ] != 'upcoming' && !empty( $roi_list ) ) {
								foreach ( $roi_list as $roi ) {
									if ( $roi->id_declaration == $roi_declaration->id && $roi->status != WDGROI::$status_canceled ) {
										$has_found_roi = true;

										$turnover_list = $roi_declaration->get_turnover();
										foreach ( $turnover_list as $turnover_item ) {
											$investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] += $turnover_item;
										}
										$adjustment_value_as_turnover = $roi_declaration->get_adjustments_amount_as_turnover();
										$investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] += $adjustment_value_as_turnover;
										$investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] = max( 0, $investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] );
										$investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover' ] = YPUIHelpers::display_number( $investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ], TRUE ) . ' &euro;';

										$investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois_nb' ] += $roi->amount;
										$investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois' ] = YPUIHelpers::display_number( $investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois_nb' ], TRUE ) . ' &euro;';
										$roi_item[ 'amount' ] = YPUIHelpers::display_number( $roi->amount, TRUE ) . ' &euro;';
										if ( $roi->amount_taxed_in_cents > 0 ) {
											$roitax_items = WDGWPREST_Entity_ROITax::get_by_id_roi( $roi->id );
											$roi_item[ 'roitax_item' ] = print_r( $roitax_items, true );
											if ( !empty( $roitax_items[ 0 ] ) ) {
												$roi_item[ 'amount' ] .= ' (dont ' .YPUIHelpers::display_number( $roitax_items[ 0 ]->amount_tax_in_cents / 100, TRUE ). ' &euro; de pr&eacute;l&egrave;vements sociaux et imp&ocirc;ts)';
											}
										}
									}
								}
							}

							// Ne pas afficher si il n'y a pas eu de versement pour cet utilisateur et que son contrat est annulé
							$add_roi = true;
							if ( $investment_item[ 'status' ] == 'canceled' && !$has_found_roi ) {
								$add_roi = false;
							}

							if ( $add_roi ) {
								array_push( $investment_item[ 'rois_by_year' ][ $current_year_index ][ 'roi_items' ], $roi_item );

								// A optimiser : ne pas trier à chaque fois qu'on ajoute, mais plutôt à la fin...
								usort( $investment_item[ 'rois_by_year' ][ $current_year_index ][ 'roi_items' ], function ($item1, $item2) {
									$item1_date = new DateTime( $item1[ 'date_db' ] );
									$item2_date = new DateTime( $item2[ 'date_db' ] );

									return ( $item1_date > $item2_date );
								} );
							}
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

	public static function display_user_investments_optimized() {
		$today_datetime = new DateTime();
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$user_type = filter_input( INPUT_POST, 'user_type' );

		$WDGUser_current = WDGUser::current();
		$can_access = FALSE;
		$is_authentified = FALSE;
		if ( $user_type == 'user' ) {
			$WDGUserEntity = new WDGUser( $user_id );
			$is_authentified = $WDGUserEntity->is_lemonway_registered();
			$can_access = ( $WDGUser_current->get_wpref() == $WDGUserEntity->get_wpref() ) || ( $WDGUser_current->is_admin() );
		} else {
			$WDGUserEntity = new WDGOrganization( $user_id );
			$is_authentified = $WDGUserEntity->is_registered_lemonway_wallet();
			$can_access = $WDGUser_current->can_edit_organization( $WDGUserEntity );
		}

		if ( !$can_access ) {
			exit( '' );
		}

		$result = WDGWPREST_Entity_User::get_investments( $WDGUserEntity->get_api_id(), 'project' );

		$buffer = array();
		foreach ( $result as $result_campaign_item ) {
			$buffer_item = array();
			$buffer_item[ 'name' ] = $result_campaign_item->project_name;
			$buffer_item[ 'status' ] = utf8_encode( $result_campaign_item->project_status );
			$buffer_item[ 'funding_duration' ] = utf8_encode( $result_campaign_item->project_funding_duration );
			$contract_start_date = new DateTime( $result_campaign_item->project_contract_start_date );
			$buffer_item[ 'start_date' ] = date_i18n( 'F Y', strtotime( $result_campaign_item->project_contract_start_date ) );

			// Récupération de la liste des contrats passés entre la levée de fonds et l'investisseur
			$exp = dirname( __FILE__ ). '/../../../pdf_files/' .$result_campaign_item->project_wpref. '_' .$user_id. '_*.pdf';
			$files = glob( $exp );

			$buffer_item[ 'items' ] = array();
			foreach ( $result_campaign_item->investments as $result_investment_item ) {
				$buffer_investment_item = array();
				if ( $WDGUser_current->is_admin() ) {
					$buffer_investment_item[ 'can_edit' ] = $result_investment_item->wpref;
				}
				$buffer_investment_item[ 'amount' ] = utf8_encode( $result_investment_item->amount );
				$buffer_investment_item[ 'date' ] = date_i18n( 'j F Y', strtotime( $result_investment_item->invest_datetime ) );
				$buffer_investment_item[ 'hour' ] = date_i18n( 'H\hi', strtotime( $result_investment_item->invest_datetime ) );
				$buffer_investment_item[ 'status' ] = utf8_encode( $result_investment_item->status );

				// Reinit de la date pour les tours de boucle
				$contract_start_date = new DateTime( $result_campaign_item->project_contract_start_date );

				// Création du tableau des prévisionnels par année
				$buffer_investment_item[ 'rois_by_year' ] = array();
				$year_end_dates = array();
				$estimated_turnover_list = FALSE;
				$campaign_declarations_list = FALSE;
				if ( $result_campaign_item->project_status != ATCF_Campaign::$campaign_status_archive ) {
					$estimated_turnover_list = json_decode( $result_campaign_item->project_estimated_turnover );
					$campaign_declarations_list = $result_campaign_item->declarations;
				}

				$estimated_turnover_unit = $result_campaign_item->project_estimated_turnover_unit;
				if ( empty( $estimated_turnover_unit ) ) {
					$estimated_turnover_unit = get_post_meta( $result_campaign_item->project_wpref, ATCF_Campaign::$key_estimated_turnover_unit, TRUE );
				}
				if ( !empty( $estimated_turnover_list ) ) {
					// On démarre de la date de démarrage du contrat
					$contract_start_date->setDate( $contract_start_date->format( 'Y' ), $contract_start_date->format( 'm' ), 21 );

					$maximum_profit = get_post_meta( $result_campaign_item->project_wpref, ATCF_Campaign::$key_maximum_profit, TRUE );
					$estimated_rois_total = $maximum_profit * $result_investment_item->amount;
					foreach ( $estimated_turnover_list as $key => $turnover ) {
						$estimated_rois = 0;
						if ( $estimated_turnover_unit == 'percent' ) {
							$estimated_rois = round( $turnover * $result_investment_item->amount / 100 );
							$turnover = round( $result_campaign_item->project_amount * $turnover / 100 );
						} else {
							if ( !empty( $result_campaign_item->project_roi_percent ) ) {
								if ( $result_campaign_item->project_status == ATCF_Campaign::$campaign_status_funded || $result_campaign_item->project_status == ATCF_Campaign::$campaign_status_closed ) {
									$investor_proportion = $result_investment_item->amount / $result_campaign_item->project_amount;
									$roi_percent_full = ( $result_campaign_item->project_roi_percent * $investor_proportion );
								} else {
									$investor_proportion = $result_investment_item->amount / $result_campaign_item->project_goal_maximum;
									$roi_percent_full = ( $result_campaign_item->project_roi_percent_estimated * $investor_proportion );
								}
								$estimated_rois = round( $turnover * $roi_percent_full / 100 );
							} else {
								$roi_percent_full_estimated = 0;
								if ( $result_campaign_item->project_amount > 0 ) {
									$roi_percent_full_estimated = ( $result_campaign_item->project_roi_percent_estimated * $result_investment_item->amount / $result_campaign_item->project_amount );
								}
								$estimated_rois = round( $turnover * $roi_percent_full_estimated / 100 );
							}
						}
						$estimated_rois = min( $estimated_rois, $estimated_rois_total );
						$estimated_rois_total -= $estimated_rois;

						$buffer_year_item = array();
						$buffer_year_item[ 'amount_turnover_nb' ] = 0;
						$buffer_year_item[ 'amount_turnover' ] = '0 &euro;';
						$buffer_year_item[ 'estimated_turnover' ] = YPUIHelpers::display_number( $turnover, TRUE ) . ' &euro;';
						$buffer_year_item[ 'estimated_rois' ] = YPUIHelpers::display_number( $estimated_rois, TRUE ) . ' &euro;';
						$buffer_year_item[ 'amount_rois_nb' ] = 0;
						$buffer_year_item[ 'amount_rois' ] = '0 &euro;';
						$buffer_year_item[ 'roi_items' ] = array();
						array_push( $buffer_investment_item[ 'rois_by_year' ], $buffer_year_item );

						// Pour trouver toutes les échéances qui ont lieu sur une année, on avance au 21, et d'une année
						$contract_start_date->add( new DateInterval( 'P1Y' ) );
						$temp_date = new DateTime();
						$temp_date->setDate( $contract_start_date->format( 'Y' ), $contract_start_date->format( 'm' ), $contract_start_date->format( 'd' ) );
						array_push( $year_end_dates, $temp_date );
					}
				}

				$buffer_investment_item[ 'status_str' ] = '';
				$buffer_investment_item[ 'payment_str' ] = '';
				$buffer_investment_item[ 'payment_date' ] = '';
				$first_investment_contract_status = FALSE;
				if ( !empty( $result_campaign_item->investments ) ) {
					$first_investment_contract_status = $result_campaign_item->investments[ 0 ]->contract_status;
				}

				if ( $result_investment_item->status == 'pending' ) {
					if ( $result_campaign_item->project_status == ATCF_Campaign::$campaign_status_archive ) {
						$buffer_investment_item[ 'status_str' ] = __( 'account.investments.status.CANCELED', 'yproject' );
						$date_end = new DateTime( $result_campaign_item->project_funding_end_date );
						$date_end->add( new DateInterval( 'P15D' ) );
						if ( $today_datetime < $date_end ) {
							$buffer_investment_item[ 'status_str' ] = __( 'account.investments.status.SUSPENDED', 'yproject' );
						}
					} else {
						if ($result_investment_item->mean_payment == 'wire' || $result_investment_item->mean_payment == 'check') {
							$buffer_investment_item[ 'status_str' ] = __('account.investments.status.PENDING_PAYMENT', 'yproject');
						} else {
							$WDGInvestment = new WDGInvestment($result_investment_item->wpref);
							if ($WDGInvestment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated) {
								$buffer_investment_item[ 'status_str' ] = __('account.investments.status.TO_BE_VALIDATED', 'yproject');
							}
						}
					}
				} elseif ( $result_investment_item->status == 'publish' ) {
					if ( $result_campaign_item->project_status == ATCF_Campaign::$campaign_status_collecte ) {
						$buffer_investment_item[ 'status_str' ] = __( 'account.investments.status.VALIDATED', 'yproject' );
					} elseif ( $result_campaign_item->project_status == ATCF_Campaign::$campaign_status_closed ) {
						$buffer_investment_item[ 'status' ] = 'canceled';
						$buffer_investment_item[ 'status_str' ] = __( 'account.investments.status.ROYALTIES_FINISHED', 'yproject' );
					} elseif ( $result_campaign_item->project_status == ATCF_Campaign::$campaign_status_archive ) {
						$buffer_investment_item[ 'status_str' ] = __( 'account.investments.status.CANCELED', 'yproject' );
						$date_end = new DateTime( $result_campaign_item->project_funding_end_date );
						$date_end->add( new DateInterval( 'P15D' ) );
						if ( $today_datetime < $date_end ) {
							$buffer_investment_item[ 'status_str' ] = __( 'account.investments.status.SUSPENDED', 'yproject' );
						}
					} elseif ( $result_campaign_item->project_status == ATCF_Campaign::$campaign_status_funded ) {
						$buffer_investment_item[ 'status_str' ] = __( 'account.investments.STARTED_CONTRACT', 'yproject' );

						if ( !empty( $first_investment_contract_status ) && $first_investment_contract_status == 'canceled' ) {
							$buffer_investment_item[ 'status' ] = 'canceled';
							$buffer_investment_item[ 'status_str' ] = __( 'account.investments.status.PAYMENTS_FINISHED', 'yproject' );
						} else {
							$first_payment_date = $result_campaign_item->project_first_payment_date;
							if ( empty( $first_payment_date ) ) {
								$first_payment_date = get_post_meta( $result_campaign_item->project_wpref, ATCF_Campaign::$key_first_payment_date, TRUE );
							}
							$date_first_payement = new DateTime( $first_payment_date );
							if ( $today_datetime > $date_first_payement ) {
								$buffer_investment_item[ 'payment_str' ] = __( 'account.investments.NEXT_PAYMENT', 'yproject' );
							} else {
								$buffer_investment_item[ 'payment_str' ] = __( 'account.investments.FIRST_PAYMENT', 'yproject' );
								$buffer_investment_item[ 'payment_date' ] = date_i18n( 'F Y', strtotime( $first_payment_date ) );
							}
						}
					}
				}

				$buffer_investment_item[ 'roi_amount' ] = 0;
				foreach ( $result_investment_item->rois as $roi_item ) {
					if ($roi_item->status == WDGROI::$status_transferred ) {
						$buffer_investment_item[ 'roi_amount' ] += $roi_item->amount;
					}
				}
				$buffer_investment_item[ 'roi_amount' ] = utf8_encode( $buffer_investment_item[ 'roi_amount' ] );
				$buffer_investment_item[ 'roi_return' ] = utf8_encode( round( $buffer_investment_item[ 'roi_amount' ] / $result_investment_item->amount * 100 ) / 100 );

				// Fichier de contrat
				$buffer_investment_item[ 'contract_file_path' ] = '';
				$buffer_investment_item[ 'contract_file_name' ] = '';
				// on commence par regarder si on a un contrat stocké ici  : API\wp-content\plugins\wdgrestapi\files\investment-draft
				// ce sont les photos des contrats et chèques ajoutés par l'admin
				// pour ça, il nous faut retrouver un éventuel post_meta de type 'created-from-draft'
				$created_from_draft = get_post_meta( $result_investment_item->wpref, 'created-from-draft', TRUE );
				if ( $created_from_draft ) {
					// si c'est le cas, alors on récupère l'investment-draft, et on vérifie s'il y a une photo de contrat associé
					$investments_drafts_item = WDGWPREST_Entity_InvestmentDraft::get( $created_from_draft );
					$buffer_investment_item[ 'contract_file_path' ] = $investments_drafts_item->contract;
					$path_parts = pathinfo( $investments_drafts_item->contract );
					$extension = $path_parts[ 'extension' ];
					$buffer_investment_item[ 'contract_file_name' ] = __( 'contrat-investissement-', 'yproject' ) .$result_campaign_item->project_url. '.' .$extension;
				}
				// sinon, on va récupérer le contrat en pdf tel qu'il a été généré
				if ( $buffer_investment_item[ 'contract_file_path' ] == '' ) {
					$contract_index = 0;
					if ( isset( $buffer_item[ 'items' ] ) ) {
						$contract_index = count( $buffer_item[ 'items' ] );
					}
					$download_filename = __( 'contrat-investissement-', 'yproject' ) .$result_campaign_item->project_url. '-'  .($contract_index + 1). '.pdf';
					$test_file_name = dirname( __FILE__ ). '/../../../../files/contracts/campaigns/' .$result_campaign_item->project_wpref. '-' .$result_campaign_item->project_url. '/' .$result_investment_item->wpref. '.pdf';
					if ( file_exists( $test_file_name ) ) {
						$buffer_investment_item[ 'contract_file_path' ] = site_url( '/wp-content/plugins/appthemer-crowdfunding/files/contracts/campaigns/' .$result_campaign_item->project_wpref. '-' .$result_campaign_item->project_url. '/' .$result_investment_item->wpref. '.pdf' );
						$buffer_investment_item[ 'contract_file_name' ] = $download_filename;
					} elseif ( count( $files ) ) {
						$filelist_extract = explode( '/', $files[ $contract_index ] );
						$contract_filename = $filelist_extract[ count( $filelist_extract ) - 1 ];
						$buffer_investment_item[ 'contract_file_path' ] = site_url( '/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/' . $contract_filename );
						$buffer_investment_item[ 'contract_file_name' ] = $download_filename;
					}
				}

				$keep_pushing = TRUE;
				$buffer_investment_item[ 'conclude-investment-url' ] = '';
				if ( $buffer_investment_item[ 'status' ] == 'pending' && $is_authentified ) {
					$WDGInvestment = new WDGInvestment( $result_investment_item->wpref );
					if ( $WDGInvestment->get_contract_status() == WDGInvestment::$contract_status_not_validated ) {
						$buffer_investment_item[ 'conclude-investment-url' ] = WDG_Redirect_Engine::override_get_page_url( 'investir' ) . '?init_with_id=' .$result_investment_item->wpref. '&campaign_id=' .$result_campaign_item->project_wpref;

						// On ne garde l'affichage de ces investissements en attente que si il est encore possible de les finaliser (on annule si ce n'est pas le cas)
						if ( $buffer_item[ 'status' ] != ATCF_Campaign::$campaign_status_vote && $buffer_item[ 'status' ] != ATCF_Campaign::$campaign_status_collecte ) {
							$keep_pushing = FALSE;
							$WDGInvestment->cancel();
						}
					}
				}

				// - Déclarations de royalties liées à la campagne
				if ( $keep_pushing && !empty( $campaign_declarations_list ) ) {
					foreach ( $campaign_declarations_list as $roi_declaration ) {
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
						if ( !isset( $buffer_investment_item[ 'rois_by_year' ][ $current_year_index ] ) ) {
							$buffer_year_item = array();
							$buffer_year_item[ 'amount_turnover_nb' ] = 0;
							$buffer_year_item[ 'amount_turnover' ] = '0 &euro;';
							$buffer_year_item[ 'estimated_turnover' ] = '-';
							$buffer_year_item[ 'estimated_rois' ] = '-';
							$buffer_year_item[ 'amount_rois_nb' ] = 0;
							$buffer_year_item[ 'amount_rois' ] = '0 &euro;';
							$buffer_year_item[ 'roi_items' ] = array();
							array_push( $buffer_investment_item[ 'rois_by_year' ], $buffer_year_item );

							$contract_start_date->add( new DateInterval( 'P1Y' ) );
							$temp_date = new DateTime();
							$temp_date->setDate( $contract_start_date->format( 'Y' ), $contract_start_date->format( 'm' ), $contract_start_date->format( 'd' ) );
							array_push( $year_end_dates, $temp_date );
						}

						// Initialisation de la ligne avec les infos de la déclaration
						$buffer_roi_item = array();
						$buffer_roi_item[ 'date_db' ] = $roi_declaration->date_due;
						$buffer_roi_item[ 'date' ] = date_i18n( 'F Y', strtotime( $roi_declaration->date_due ) );
						$buffer_roi_item[ 'status' ] = $roi_declaration->status;
						$buffer_roi_item[ 'status_str' ] = '';
						$buffer_roi_item[ 'amount' ] = '0 &euro;';
						switch ( $roi_declaration->status ) {
							case WDGROIDeclaration::$status_declaration:
								if ( $decla_datetime < $today_datetime ) {
									$buffer_roi_item[ 'status' ] = 'late';
									$buffer_roi_item[ 'status_str' ] = __( 'En retard', 'yproject' );
								} else {
									$buffer_roi_item[ 'status' ] = 'upcoming';
									$buffer_roi_item[ 'status_str' ] = __( 'A venir', 'yproject' );
									if ( $buffer_investment_item[ 'payment_date' ]  == '') {
										$buffer_investment_item[ 'payment_date' ] = $buffer_roi_item[ 'date' ];
									}
								}
								break;
							case WDGROIDeclaration::$status_finished:
								// Rien
								break;
							case WDGROIDeclaration::$status_failed:
								$buffer_roi_item[ 'status_str' ] = __( 'En d&eacute;faut', 'yproject' );
								break;
							default:
								$buffer_roi_item[ 'status' ] = 'upcoming';
								$buffer_roi_item[ 'status_str' ] = __( 'A venir', 'yproject' );
								if ( $buffer_investment_item[ 'payment_date' ]  == '') {
									$buffer_investment_item[ 'payment_date' ] = $buffer_roi_item[ 'date' ];
								}
								break;
						}

						if ( $buffer_roi_item[ 'status' ] != 'upcoming' || empty( $first_investment_contract_status ) || $first_investment_contract_status != 'canceled' ) {
							$has_found_roi = false;

							// Si il y a eu un versement de royalties, on récupère les infos du versement
							$roi_list = $result_investment_item->rois;
							if ( $buffer_roi_item[ 'status' ] != 'upcoming' && !empty( $roi_list ) ) {
								foreach ( $roi_list as $roi ) {
									if ( $roi->id_declaration == $roi_declaration->id && $roi->status == WDGROI::$status_transferred ) {
										$has_found_roi = true;

										$turnover_list = json_decode( $roi_declaration->turnover );
										foreach ( $turnover_list as $turnover_item ) {
											$buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] += $turnover_item;
										}
										$adjustment_value = 0;
										$adjustment_value_as_turnover = 0;
										foreach ( $roi_declaration->adjustments as $adjustment ) {
											$adjustment_value += $adjustment->amount;
										}
										if ( $result_campaign_item->project_roi_percent > 0 ) {
											$adjustment_value_as_turnover = $adjustment_value * 100 / $result_campaign_item->project_roi_percent;
										}
										$buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] += $adjustment_value_as_turnover;
										$buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] = max( 0, $buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] );
										$buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover' ] = UIHelpers::format_number( $buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_turnover_nb' ] ) . ' &euro;';
										$buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois_nb' ] += $roi->amount;
										$buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois' ] = UIHelpers::format_number( $buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'amount_rois_nb' ], TRUE ) . ' &euro;';
										$buffer_roi_item[ 'amount' ] = UIHelpers::format_number( $roi->amount, TRUE ) . ' &euro;';
										if ( $roi->amount_taxed_in_cents > 0 ) {
											$roitax_items = WDGWPREST_Entity_ROITax::get_by_id_roi( $roi->id );
											if ( !empty( $roitax_items[ 0 ] ) ) {
												$buffer_roi_item[ 'roitax_item' ] = print_r( $roitax_items[ 0 ], true );
												$buffer_roi_item[ 'amount' ] .= ' (dont ' .UIHelpers::format_number( $roitax_items[ 0 ]->amount_tax_in_cents / 100, TRUE ). ' &euro; de pr&eacute;l&egrave;vements sociaux et imp&ocirc;ts)';
											}
										}
									}
								}
							}

							// Ne pas afficher si il n'y a pas eu de versement pour cet utilisateur et que son contrat est annulé
							$add_roi = true;
							if ( $buffer_investment_item[ 'status' ] == 'canceled' && !$has_found_roi ) {
								$add_roi = false;
							}

							if ( $add_roi ) {
								array_push( $buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'roi_items' ], $buffer_roi_item );
							}
						}
					}
				}

				if ( $keep_pushing ) {
					foreach ( $buffer_investment_item[ 'rois_by_year' ] as $current_year_index => $year_item ) {
						usort( $buffer_investment_item[ 'rois_by_year' ][ $current_year_index ][ 'roi_items' ], function ($item1, $item2) {
							$item1_date = new DateTime( $item1[ 'date_db' ] );
							$item2_date = new DateTime( $item2[ 'date_db' ] );

							return ( $item1_date > $item2_date );
						} );
					}
				}

				if ( $keep_pushing ) {
					array_push( $buffer_item[ 'items' ], $buffer_investment_item );
				}
			}

			$buffer[ $result_campaign_item->project_wpref ] = $buffer_item;
		}

		echo json_encode( $buffer );
		exit();
	}

	/**
	 * Récupère le tableau avec les transactions des investisseurs
	 */
	public static function get_transactions_table() {
		ypcf_function_log( 'account_transactions', 'view' );
		$WDGUserCurrent = WDGUser::current();
		$userid = filter_input( INPUT_POST, 'user_id' );

		if ( !$WDGUserCurrent->is_admin() && $WDGUserCurrent->get_wpref() != $userid ) {
			exit( '<div class="align-center">' .__( 'account.wallet.transactions.NONE', 'yproject' ). '</div>' );
		}

		$WDGUser = new WDGUser( $userid );
		$WDGUser_api_id = $WDGUser->get_api_id();
		$transactions = $WDGUser->get_transactions();

		$html_table = __( 'account.wallet.transactions.NONE', 'yproject' );
		if ( !empty( $transactions ) ) {
			$html_table = '<table class="user-transactions">';

			$html_table .= '<thead>';
			$html_table .= '<tr>';
			$html_table .= '<td>' .__( 'common.DATE', 'yproject' ). '</td>';
			$html_table .= '<td>' .__( 'common.TRANSACTION', 'yproject' ). '</td>';
			$html_table .= '<td>' .__( 'common.AMOUNT', 'yproject' ). '</td>';
			$html_table .= '</tr>';
			$html_table .= '</thead>';
			$excel_separator = '<div class="hidden"> - </div>';

			foreach ( $transactions as $transaction_item ) {
				$current_user_is_receiving = ( $WDGUser_api_id == $transaction_item->recipient_id );

				$datetime = new DateTime( $transaction_item->datetime );
				$object = '<div class="date-mobile">' .$datetime->format( 'd/m/Y' ). '</div>';
				$object .= $excel_separator;

				// Affichage des investissements
				if ( $transaction_item->wedogood_entity == 'investment' ) {
					if ( !empty( $transaction_item->project_name ) ) {
						$object .= __( 'account.wallet.transactions.INVESTMENT_ON', 'yproject' ) . ' ' . $transaction_item->project_name;
						if ( !empty( $transaction_item->project_organization_name ) ) {
							$object .= $excel_separator;
							$object .= '<div class="organization-name">' .__( 'account.wallet.transactions.PROJECT_MANAGED_BY', 'yproject' ) . ' ' . $transaction_item->project_organization_name. '</div>';
						}
					} else {
						$object .= __( 'common.INVESTMENT', 'yproject' );
						$object .= '<div class="hidden"> - ' . $transaction_item->recipient_id . ' (' .$transaction_item->recipient_wallet_type. ')</div>';
					}

					// Affichage des versements de royalties
				} else {
					if ( $transaction_item->wedogood_entity == 'roi' ) {
						if ( !empty( $transaction_item->project_name ) ) {
							$object .= __( 'account.wallet.transactions.ROYALTIES_TRANSFER_FROM', 'yproject' ) . ' ' . $transaction_item->project_name;
							if ( !empty( $transaction_item->project_organization_name ) ) {
								$object .= $excel_separator;
								$object .= '<div class="organization-name">' .__( 'account.wallet.transactions.PROJECT_MANAGED_BY', 'yproject' ) . ' ' . $transaction_item->project_organization_name. '</div>';
							}
						} else {
							$object .= __( 'account.wallet.transactions.ROYALTIES_TRANSFER', 'yproject' );
							$object .= '<div class="hidden"> - ' . $transaction_item->recipient_id . ' (' .$transaction_item->recipient_wallet_type. ')</div>';
						}

						// Affichage des rechargements de compte bancaire
					} else {
						if ( $transaction_item->type == 'moneyin' ) {
							$object .= __( 'account.wallet.transactions.TRANSFER_FROM_BANK_ACCOUNT', 'yproject' );

						// Affichage des transferts vers compte bancaire
						} else {
							if ( $transaction_item->type == 'moneyout' ) {
								if ( $transaction_item->recipient_wallet_type == 'society' ) {
									$object .= __( 'account.wallet.transactions.REFUND_INVESTMENT', 'yproject' );
								} else {
									$object .= __( 'account.wallet.transactions.TRANSFER_TO_BANK_ACCOUNT', 'yproject' );
								}

								// Transfert vers le wallet de l'utilisateur
							} else {
								if ( $current_user_is_receiving ) {
									$object = __( 'account.wallet.transactions.REFUND_TO_WALLET_FROM', 'yproject' ) . ' ';
									if ( $transaction_item->sender_id == 0 ) {
										$object .= " de WE DO GOOD";
									} else {
										if ( !empty( $transaction_item->project_name ) ) {
											$object .= " de " . $transaction_item->project_name;
											if ( !empty( $transaction_item->project_organization_name ) ) {
												$object .= $excel_separator;
												$object .= '<div class="organization-name">' .__( 'account.wallet.transactions.PROJECT_MANAGED_BY', 'yproject' ).' '.$transaction_item->project_organization_name. '</div>';
											}
										} else {
											if ( !empty( $transaction_item->project_organization_name ) ) {
												$object .= " de " . $transaction_item->project_organization_name;
											} else {
												$object .= '<div class="hidden"> - ' . $transaction_item->sender_id . ' (' .$transaction_item->sender_wallet_type. ')</div>';
											}
										}
									}
								} else {
									if ( $transaction_item->recipient_id == 0 ) {
										$object .= __( 'account.wallet.transactions.TRANSFER_CORRECTION', 'yproject' );
									} else {
										if ( $transaction_item->recipient_wallet_type == 'campaign' ) {
											if ( !empty( $transaction_item->project_name ) ) {
												$object .= __( 'account.wallet.transactions.INVESTMENT_ON', 'yproject' ) . ' ';
												$object .= $transaction_item->project_name;
												if ( !empty( $transaction_item->project_organization_name ) ) {
													$object .= $excel_separator;
													$object .= '<div class="organization-name">' .__( 'account.wallet.transactions.PROJECT_MANAGED_BY', 'yproject' ).' '.$transaction_item->project_organization_name. '</div>';
												}
											} else {
												$object .= __( 'common.INVESTMENT', 'yproject' );
												$object .= '<div class="hidden"> - ' . $transaction_item->recipient_id . ' (' .$transaction_item->recipient_wallet_type. ')</div>';
											}
										} else {
											$object .= __( 'account.wallet.transactions.UNDEFINED_DEBIT', 'yproject' );
										}
									}
								}
							}
						}
					}
				}

				if ( !empty( $transaction_item->gateway_mean_payment ) || !empty( $transaction_item->gateway_mean_payment_info ) ) {
					$object .= $excel_separator;
					$object .= '<div class="mean-payment-info">';
					if ( !empty( $transaction_item->gateway_mean_payment ) ) {
						switch ( $transaction_item->gateway_mean_payment ) {
							case 'card':
								$object .= __( 'account.wallet.transactions.BANK_CARD', 'yproject' );
								break;
							case 'wire':
								$object .= __( 'account.wallet.transactions.BANK_TRANSFER', 'yproject' );
								break;
							case 'mandate':
								$object .= __( 'account.wallet.transactions.BANK_DIRECT_DEBIT', 'yproject' );
								break;
						}
					}
					if ( !empty( $transaction_item->gateway_mean_payment_info ) ) {
						$object .= ' ' . $transaction_item->gateway_mean_payment_info;
					}
					$object .= '</div>';
				}

				$td_class = 'positive';
				$amount_in_euros = $transaction_item->amount_in_cents / 100;
				if ( !$current_user_is_receiving ) {
					$amount_in_euros *= -1;
					$td_class = 'negative';
				}

				$html_table .= '<tr>';
				$html_table .= '<td data-order="' .$datetime->format( 'YmdHis' ). '">' .$datetime->format( 'd/m/Y' ). '</td>';
				$html_table .= '<td data-order="' .$datetime->format( 'YmdHis' ). '" class="transaction-titre">' .$object. '</td>';
				$html_table .= '<td class="' .$td_class. '">' .UIHelpers::format_number( $amount_in_euros ). ' &euro;</td>';
				$html_table .= '</tr>';
			}
			$html_table .= '</table>';
		}

		exit( $html_table );
	}

	public static function get_viban_info() {
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$entity = FALSE;
		if ( WDGOrganization::is_user_organization( $user_id ) ) {
			$entity = new WDGOrganization( $user_id );
		} else {
			$entity = new WDGUser( $user_id );
		}
		$iban_info = $entity->get_viban();

		$result = array();
		if ( !empty( $iban_info ) ) {
			$result[ 'holder' ] = $iban_info->HOLDER;
			$result[ 'iban' ] = $iban_info->DATA;
			$result[ 'bic' ] = $iban_info->SWIFT;
		} else {
			$result[ 'error' ] = 1;
		}

		echo json_encode( $result );
		exit();
	}
}