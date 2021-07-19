<?php
/**
 * Gestion des appels Ajax en provenance du TBPP
 */
class WDGAjaxActionsProjectDashboard {
	/**
	 * Affiche la liste des utilisateurs d'un projet qui doivent récupérer de l'argent de leur investissement
	 */
	public static function display_roi_user_list() {
		$wdgcurrent_user = WDGUser::current();
		if ($wdgcurrent_user->is_admin()) {
			//Récupération des éléments Ã  traiter
			$declaration_id = filter_input(INPUT_POST, 'roideclaration_id');
			$is_refund = filter_input( INPUT_POST, 'is_refund' );
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
			$investments_list = $campaign->roi_payments_data( $declaration, FALSE, $is_refund );
			foreach ($investments_list as $investment_item) {
				$total_amount += $investment_item['amount'];
				$total_fees += $investment_item['roi_fees'];
				$total_roi += $investment_item['roi_amount'];
				$user_data = get_userdata($investment_item['user']);
				//Affichage utilisateur?>
			    <tr>
					<td><?php echo html_entity_decode($user_data->first_name).' '.html_entity_decode($user_data->last_name); ?></td>
					<td><?php echo $investment_item['amount']; ?> &euro;</td>
					<td><?php echo $investment_item['roi_amount']; ?> &euro;</td>
					<td><?php echo $investment_item['roi_fees']; ?> &euro;</td>
				</tr>
				<?php
			}

			//Affichage total?>
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
		} else {
			if ($invest_type != "user") {
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
					"org_employees_count" => $organization->get_employees_count(),
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
	}

	/**
	 * Récupère l'ensemble des infos de template SiB (fonction temporaire)
	 */
	public static function init_sendinblue_templates() {
		$template_index = filter_input( INPUT_POST, 'template_index' );
		$foreach_index = 0;
		foreach ( NotificationsAPI::$description_str_by_template_id as $template_slug => $template_data ) {
			if ( $template_index == $foreach_index ) {
				$SIBv3Helper = SIBv3Helper::instance();
				$template_content = $SIBv3Helper->getTransactionalTemplateInformation( $template_data['fr-sib-id'] );
				ypcf_debug_log( 'WDGAjaxActions::action_project_dashboard::init_sendinblue_templates $template_content : ' . $template_content );
				$foreach_index++;
				break;
			}
			$foreach_index++;
		}

		echo $foreach_index;
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

	/**
	 * Enregistre la petite image et/ou url de la vidéo
	 */
	public static function save_image_url_video() {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$url_video = filter_input( INPUT_POST, 'url_video' );
		$image = $_FILES[ 'image_video_zone' ];

		echo WDGFormProjects::edit_image_url_video( $image, $url_video, $campaign_id );
		exit();
	}

	public static function send_project_notification() {
		$id_campaign = filter_input( INPUT_POST, 'id_campaign' );
		$is_for_project = filter_input( INPUT_POST, 'is_for_project' );

		$buffer = FALSE;
		if ( $is_for_project ) {
			$buffer = WDGCampaignNotifications::send_has_finished_proofreading( $id_campaign );
		} else {
			$buffer = WDGCampaignNotifications::ask_proofreading( $id_campaign );
		}

		if ( $buffer ) {
			exit( '1' );
		} else {
			exit( '0' );
		}
	}

	public static function remove_project_cache() {
		$id_campaign = filter_input( INPUT_POST, 'id_campaign' );
		$campaign = new ATCF_Campaign( $id_campaign );

		$file_cacher = WDG_File_Cacher::current();
		$file_cacher->delete( $campaign->data->post_name );

		$db_cacher = WDG_Cache_Plugin::current();
		$db_cacher->set_cache( 'cache_campaign_' . $id_campaign, '0', 1, 1 );

		WDGQueue::add_cache_post_as_html( $id_campaign, 'date', 'PT50M' );

		exit( '1' );
	}

	public static function remove_project_lang() {
		// Vérification que l'utilisateur peut supprimer la langue
		$id_campaign = filter_input( INPUT_POST, 'id_campaign' );
		$campaign = new ATCF_Campaign( $id_campaign );
		if ( $campaign->current_user_can_edit() ) {
			// Suppression de la langue dans la liste
			$lang = filter_input( INPUT_POST, 'lang' );
			$lang_list = $campaign->get_lang_list();
			foreach ( $lang_list as $key => $lang_item_id ) {
				if ( $lang == $lang_item_id ) {
					array_splice( $lang_list, $key, 1 );
					break;
				}
			}
			update_post_meta( $id_campaign, ATCF_Campaign::$key_meta_lang, json_encode( $lang_list ) );

			// Suppression des meta associées à la langue
			delete_post_meta( $id_campaign, ATCF_Campaign::$key_google_doc . '_' . $lang );
			delete_post_meta( $id_campaign, ATCF_Campaign::$key_logbook_google_doc . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_subtitle' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_summary' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_rewards' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_description' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_added_value' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_development_strategy' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_economic_model' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_measuring_impact' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_implementation' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_impact_area' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_societal_challenge' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_video' . '_' . $lang );
		}
	}

	public static function remove_help_item() {
		$name = filter_input(INPUT_POST, 'name');
		$version = filter_input(INPUT_POST, 'version');
		$WDGUser_current = WDGUser::current();
		$WDGUser_current->set_removed_help_items( $name, $version );
	}

	/**
	 * Enregistre les informations générales du projet
	 */
	public static function save_project_infos() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$current_wdg_user = WDGUser::current();
		$errors = array();
		$success = array();

		//Titre du projet
		$title = sanitize_text_field(filter_input(INPUT_POST, 'new_project_name'));
		if (!empty($title)) {
			$return = wp_update_post(array(
				'ID' => $campaign_id,
				'post_title' => $title
			));
			if ($return != $campaign_id) {
				$errors["new_project_name"]="Le nouveau nom du projet n'est pas valide";
			} else {
				$success["new_project_name"]=1;
			}
		} else {
			$errors["new_project_name"].="Le nom du projet ne peut pas &ecirc;tre vide";
		}

		//Résumé backoffice du projet
		$backoffice_summary = (filter_input(INPUT_POST, 'new_backoffice_summary'));
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
			if ($posts) {
				$errors[ 'new_project_url' ] .= "L'URL est déjà utilisée.";
			} elseif ( sanitize_title( $new_name ) != $new_name ) {
				$errors[ 'new_project_url' ] .= "URL non valide.";
			} else {
				$old_name = $campaign->data->post_name;
				$campaign->set_api_data( 'url', $new_name );
				wp_update_post( array(
					'ID'		=> $campaign_id,
					'post_name' => $new_name
				) );
				$campaign->data->post_name = $new_name;
				$success[ 'new_project_url' ] = 1;
				// Mise à jour de l'URL sur LW
				$campaign_organization = $campaign->get_organization();
				$WDGOrganization = new WDGOrganization( $campaign_organization->wpref );
				LemonwayLib::wallet_update($WDGOrganization->get_lemonway_id(), '', '', '', '', '', '', '', '', get_permalink( $campaign_id ));
				// Notification à l'équipe
				NotificationsSlack::campaign_url_changed( $campaign->get_name(), $old_name, $new_name );
			}
		}

		if ( $current_wdg_user->is_admin() ) {
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

			// Procédure de recouvrement
			$new_legal_procedure = sanitize_text_field(filter_input(INPUT_POST, 'new_legal_procedure'));
			if ( !empty( $new_legal_procedure ) ) {
				if ( $new_legal_procedure == 'no' ) {
					$new_legal_procedure = '';
				}
				$campaign->set_api_data( 'legal_procedure', $new_legal_procedure );
				$success[ "new_legal_procedure" ] = 1;
			}

			// Type de structure
			$new_organization_type = sanitize_text_field(filter_input(INPUT_POST, 'new_organization_type'));
			if ( !empty( $new_organization_type ) ) {
				$campaign->set_api_data( 'organization_type', $new_organization_type );
				$success[ "new_organization_type" ] = 1;
			}

			//Catégories du projet
			$new_project_categories = array();
			if ( isset( $_POST["new_project_categories"] ) ) {
				$new_project_categories = $_POST["new_project_categories"];
			}
			$new_project_activities = array();
			if ( isset( $_POST["new_project_activities"] ) ) {
				$new_project_activities = $_POST["new_project_activities"];
			}
			$new_project_types = array();
			if ( isset( $_POST["new_project_types"] ) ) {
				$new_project_types = $_POST["new_project_types"];
			}
			$new_project_partners = array();
			if ( isset( $_POST["new_project_partners"] ) ) {
				$new_project_partners = $_POST["new_project_partners"];
			}
			$new_project_tousnosprojets = array();
			if ( isset( $_POST["new_project_tousnosprojets"] ) ) {
				$new_project_tousnosprojets = $_POST["new_project_tousnosprojets"];
			}
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

		$new_project_product_type = sanitize_text_field(filter_input(INPUT_POST, 'new_project_product_type'));
		if ( !empty( $new_project_product_type ) ) {
			$campaign->set_api_data( 'product_type', $new_project_product_type );
			$success[ "new_project_product_type" ] = 1;
		}
		$new_project_acquisition = sanitize_text_field(filter_input(INPUT_POST, 'new_project_acquisition'));
		if ( !empty( $new_project_acquisition ) ) {
			$campaign->set_api_data( 'acquisition', $new_project_acquisition );
			$success[ "new_project_acquisition" ] = 1;
		}

		//Localisation du projet
		$location = sanitize_text_field(filter_input(INPUT_POST, 'new_project_location'));
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

		if ( !$campaign->is_remaining_time() ) {
			$new_presentation_visible_only_to_investors = sanitize_text_field( filter_input( INPUT_POST, 'new_presentation_visible_only_to_investors' ) );
			if ( $new_presentation_visible_only_to_investors === true || $new_presentation_visible_only_to_investors === "true" || $new_presentation_visible_only_to_investors === 1 ) {
				update_post_meta( $campaign_id, ATCF_Campaign::$key_campaign_is_presentation_visible_only_to_investors, '1' );
			} else {
				delete_post_meta( $campaign_id, ATCF_Campaign::$key_campaign_is_presentation_visible_only_to_investors );
			}
		}

		if ( $current_wdg_user->is_admin() ) {
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

			$new_advice_notifications_frequency = sanitize_text_field( filter_input( INPUT_POST, 'new_advice_notifications_frequency' ) );
			update_post_meta( $campaign_id, ATCF_Campaign::$key_advice_notifications_frequency, $new_advice_notifications_frequency );

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
			$new_can_invest_until_contract_start_date = sanitize_text_field( filter_input( INPUT_POST, 'new_can_invest_until_contract_start_date' ) );
			if ( $new_can_invest_until_contract_start_date === true || $new_can_invest_until_contract_start_date === "true" || $new_can_invest_until_contract_start_date === 1 ) {
				update_post_meta( $campaign_id, ATCF_Campaign::$key_can_invest_until_contract_start_date, '1' );
			} else {
				delete_post_meta( $campaign_id, ATCF_Campaign::$key_can_invest_until_contract_start_date );
			}
		}

		$new_archive_message = sanitize_text_field( filter_input( INPUT_POST, 'new_archive_message' ) );
		if ( !empty( $new_archive_message ) ) {
			$campaign->__set( ATCF_Campaign::$key_archive_message, $new_archive_message );
			$success[ "new_archive_message" ] = 1;
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
		$new_google_tag_manager_id = filter_input( INPUT_POST, 'new_google_tag_manager_id' );
		if ( !empty( $new_google_tag_manager_id ) ) {
			$campaign->__set( ATCF_Campaign::$key_google_tag_manager_id, $new_google_tag_manager_id );
			$success[ "new_google_tag_manager_id" ] = 1;
		}
		$new_custom_footer_code = filter_input( INPUT_POST, 'new_custom_footer_code' );
		if ( !empty( $new_custom_footer_code ) ) {
			$campaign->__set( ATCF_Campaign::$key_custom_footer_code, $new_custom_footer_code );
			$success[ "new_custom_footer_code" ] = 1;
		}

		if ( $current_wdg_user->is_admin() ) {
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
		}

		$new_fake_url = filter_input( INPUT_POST, 'new_fake_url' );
		if ( !empty( $new_fake_url ) ) {
			$campaign->__set( ATCF_Campaign::$key_fake_url, $new_fake_url );
			$success[ "new_fake_url" ] = 1;
		}

		$new_asset_name_singular = filter_input( INPUT_POST, 'new_asset_name_singular' );
		if ( !empty( $new_asset_name_singular ) ) {
			$campaign->__set( ATCF_Campaign::$key_asset_name_singular, $new_asset_name_singular );
			$success[ "$new_asset_name_singular" ] = 1;
		}

		$new_asset_name_plural = filter_input( INPUT_POST, 'new_asset_name_plural' );
		if ( !empty( $new_asset_name_plural ) ) {
			$campaign->__set( ATCF_Campaign::$key_asset_name_plural, $new_asset_name_plural );
			$success[ "$new_asset_name_plural" ] = 1;
		}

		$new_partner_company_name = filter_input( INPUT_POST, 'new_partner_company_name' );
		if ( !empty( $new_partner_company_name ) ) {
			$campaign->__set( ATCF_Campaign::$key_partner_company_name, $new_partner_company_name );
			$success[ "new_partner_company_name" ] = 1;
		}

		//Champs personnalisés
		$WDGAuthor = new WDGUser( $campaign->data->post_author );
		$nb_custom_fields = $WDGAuthor->wp_user->get('wdg-contract-nb-custom-fields');
		if ( $nb_custom_fields > 0 ) {
			for ( $i = 1; $i <= $nb_custom_fields; $i++ ) {
				$custom_field_value = sanitize_text_field( filter_input( INPUT_POST, 'custom_field_' . $i ) );
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
		if ($gender == "male" || $gender == "female") {
			$success['new_gender']=1;
		}

		$firstname = sanitize_text_field(filter_input(INPUT_POST, 'new_firstname'));
		if (!empty($firstname)) {
			$success['new_firstname']=1;
		} else {
			$errors['new_firstname']= __("Vous devez renseigner votre prénom", 'yproject');
		}

		$lastname = sanitize_text_field(filter_input(INPUT_POST, 'new_lastname'));
		if (!empty($lastname)) {
			$success['new_lastname']=1;
		} else {
			$errors['new_lastname']= __("Vous devez renseigner votre nom", 'yproject');
		}

		$birthday = filter_input(INPUT_POST, 'new_birthday');
		if (!empty($birthday)) {
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
		if (!empty($birthplace)) {
			$success['new_birthplace']=1;
		} else {
			$errors['new_birthplace']= __("Vous devez renseigner votre lieu de naissance", 'yproject');
		}

		$nationality = sanitize_text_field(filter_input(INPUT_POST, 'new_nationality'));
		if (!empty($nationality)) {
			$success['new_nationality']=1;
		} else {
			$errors['new_nationality']= __("Vous devez renseigner votre nationalit&eacute;", 'yproject');
		}

		$address = sanitize_text_field(filter_input(INPUT_POST, 'new_address'));
		if (!empty($address)) {
			$success['new_address']=1;
		} else {
			$errors['new_address']= __("Vous devez renseigner votre adresse", 'yproject');
		}

		$postal_code = sanitize_text_field(filter_input(INPUT_POST, 'new_postal_code'));
		if (!empty($postal_code)) {
			$success['new_postal_code']=1;
		} else {
			$errors['new_postal_code']= __("Vous devez renseigner votre code postal", 'yproject');
		}

		$city = sanitize_text_field(filter_input(INPUT_POST, 'new_city'));
		if (!empty($city)) {
			$success['new_city']=1;
		} else {
			$errors['new_city']= __("Vous devez renseigner votre ville", 'yproject');
		}

		$country = sanitize_text_field(filter_input(INPUT_POST, 'new_country'));
		if (!empty($country)) {
			$success['new_country']=1;
		} else {
			$errors['new_country']= __("Vous devez renseigner votre pays", 'yproject');
		}

		$mobile_phone = sanitize_text_field(filter_input(INPUT_POST, 'new_mobile_phone'));
		if (!empty($mobile_phone)) {
			$success['new_mobile_phone']=1;
		} else {
			$errors['new_mobile_phone']= __("Vous devez renseigner un numéro de téléphone", 'yproject');
		}

		$mail = sanitize_text_field(filter_input(INPUT_POST, 'new_mail'));
		if (is_email($mail)==$mail && !empty($mail)) {
			$success['new_mail']=1;
		} else {
			$errors['new_mail']= __("Adresse mail non valide", 'yproject');
		}

		$use_lastname = '';
		$birthplace_district = '';
		$birthplace_department = '';
		$birthplace_country = '';
		$address_number = '';
		$address_number_complement = '';
		$tax_country = '';
		$current_user->save_data($mail, $gender, $firstname, $lastname, $use_lastname, $new_birthday_date->format('d'), $new_birthday_date->format('n'), $new_birthday_date->format('Y'), $birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality, $address_number, $address_number_complement, $address, $postal_code, $city, $country, $tax_country, $mobile_phone);
		if ( !$current_user->is_major() ) {
			$errors[ 'new_birthday' ] = __( "Le porteur de projet doit &ecirc;tre majeur.", 'yproject' );
			unset( $success[ 'new_birthday' ] );
		}

		$return_values = array(
			"response"	=> 'edit_project',
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
			$errors['pay_with_mandate_amount_for_organization'] = __("Somme non conforme", 'yproject');
		}
		$amount_for_commission = filter_input( INPUT_POST, 'pay_with_mandate_amount_for_commission' );
		if ( $amount_for_commission < 0 || !is_numeric( $amount_for_commission ) ) {
			$errors['pay_with_mandate_amount_for_commission'] = __("Somme non conforme", 'yproject');
		}
		$organization_id = filter_input( INPUT_POST, 'organization_id' );
		if ( empty( $organization_id ) ) {
			$errors['organization_id'] = __("Probl&egrave;me interne", 'yproject');
		}

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
					$errors['pay_with_mandate_amount_for_organization'] = __("Probl&egrave;me Lemon Way", 'yproject');
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
	public static function save_project_funding() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$current_wdg_user = WDGUser::current();
		$errors = array();
		$success = array();
		global $locale;

		//Update required amount
		$new_minimum_goal = WDG_Form::formatInputTextNumber( 'new_minimum_goal', TRUE );
		$new_maximum_goal = WDG_Form::formatInputTextNumber( 'new_maximum_goal', TRUE );
		if ($new_minimum_goal > $new_maximum_goal) {
			$errors['new_minimum_goal']="Le montant maximum ne peut &ecirc;tre inf&eacute;rieur au montant minimum";
			$errors['new_maximum_goal']="Le montant maximum ne peut &ecirc;tre inf&eacute;rieur au montant minimum";
		} else {
			if ($new_minimum_goal<0 || $new_maximum_goal<0) {
				$errors['new_minimum_goal']="Les montants doivent &ecirc;tre positifs";
			} else {
				if ($new_maximum_goal<0) {
					$errors['new_maximum_goal']="Les montants doivent &ecirc;tre positifs";
				} else {
					update_post_meta($campaign_id, ATCF_Campaign::$key_minimum_goal, $new_minimum_goal);
					$campaign->set_api_data( 'goal_minimum', $new_minimum_goal );
					update_post_meta($campaign_id, ATCF_Campaign::$key_goal, $new_maximum_goal);
					$campaign->set_api_data( 'goal_maximum', $new_maximum_goal );
					$success['new_minimum_goal']=1;
					$success['new_maximum_goal']=1;
				}
			}
		}

		$new_project_contract_spendings_description = sanitize_text_field( filter_input( INPUT_POST, 'new_project_contract_spendings_description' ) );
		if ( !empty( $new_project_contract_spendings_description ) ) {
			$key = ATCF_Campaign::$key_contract_spendings_description;
			if ( $locale != 'fr' && $locale != 'fr_FR' ) {
				$key .= '_' . $locale;
			}
			$campaign->__set( $key, $new_project_contract_spendings_description );
			$campaign->set_api_data( 'spendings_description', $new_project_contract_spendings_description );
		}

		//Update funding duration
		$new_duration = intval( sanitize_text_field( filter_input( INPUT_POST, 'new_funding_duration' ) ) );
		if ( $new_duration >= 0 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_funding_duration, $new_duration );
			$campaign->set_api_data( 'funding_duration', $new_duration );
			$success[ 'new_funding_duration' ] = 1;
		} else {
			$errors[ 'new_funding_duration' ] = "Erreur de valeur";
		}

		if ( $current_wdg_user->is_admin() ) {
			$new_platform_commission = WDG_Form::formatInputTextNumber( 'new_platform_commission' );
			if ( $new_platform_commission >= 0 ) {
				update_post_meta( $campaign_id, ATCF_Campaign::$key_platform_commission, $new_platform_commission );
				$success['new_platform_commission'] = 1;
			} else {
				$errors['new_platform_commission'] = "Le pourcentage doit &ecirc;tre positif";
			}

			$new_platform_commission_above_100000 = WDG_Form::formatInputTextNumber( 'new_platform_commission_above_100000' );
			if ( $new_platform_commission_above_100000 >= 0 ) {
				update_post_meta( $campaign_id, ATCF_Campaign::$key_platform_commission_above_100000, $new_platform_commission_above_100000 );
				$success['new_platform_commission_above_100000'] = 1;
			} else {
				$errors['new_platform_commission_above_100000'] = "Le pourcentage doit &ecirc;tre positif";
			}

			$new_common_goods_turnover_percent = WDG_Form::formatInputTextNumber( 'new_common_goods_turnover_percent' );
			if ( $new_common_goods_turnover_percent >= 0 ) {
				$campaign->set_api_data( 'common_goods_turnover_percent', $new_common_goods_turnover_percent );
				$success['new_common_goods_turnover_percent'] = 1;
			} else {
				$errors['new_common_goods_turnover_percent'] = "Le pourcentage doit &ecirc;tre positif";
			}

			$new_maximum_profit = sanitize_text_field( filter_input( INPUT_POST, 'new_maximum_profit' ) );
			$possible_maximum_profit = array_keys( ATCF_Campaign::$maximum_profit_list );
			if ( in_array( $new_maximum_profit, $possible_maximum_profit ) ) {
				update_post_meta( $campaign_id, ATCF_Campaign::$key_maximum_profit, $new_maximum_profit );
				$campaign->set_api_data( ATCF_Campaign::$key_maximum_profit, $new_maximum_profit );
				$success[ 'new_maximum_profit' ] = 1;
			} else {
				$errors[ 'new_maximum_profit' ] = "Le gain maximum n'est pas correct (".$new_maximum_profit.")";
			}

			$new_maximum_profit_precision = sanitize_text_field( filter_input( INPUT_POST, 'new_maximum_profit_precision' ) );
			if ( is_numeric( $new_maximum_profit_precision ) ) {
				update_post_meta( $campaign_id, ATCF_Campaign::$key_maximum_profit_precision, $new_maximum_profit_precision );
				$campaign->set_api_data( ATCF_Campaign::$key_maximum_profit_precision, $new_maximum_profit_precision );
				$success[ 'new_maximum_profit_precision' ] = 1;
			} else {
				$errors[ 'new_maximum_profit_precision' ] = "La précision de gain maximum n'est pas correcte (".$new_maximum_profit.")";
			}
		}

		//Update roi_percent_estimated
		$new_roi_percent_estimated = WDG_Form::formatInputTextNumber( 'new_roi_percent_estimated' );
		if ( $new_roi_percent_estimated >= 0 ) {
			update_post_meta( $campaign_id, ATCF_Campaign::$key_roi_percent_estimated, $new_roi_percent_estimated );
			$campaign->set_api_data( 'roi_percent_estimated', $new_roi_percent_estimated );
			$success['new_roi_percent_estimated'] = 1;
		} else {
			$errors['new_roi_percent_estimated'] = "Le pourcentage de CA reversé doit Ãªtre positif";
		}

		$new_roi_percent = WDG_Form::formatInputTextNumber( 'new_roi_percent' );
		if ( $new_roi_percent >= 0 ) {
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

		if ( $current_wdg_user->is_admin() ) {
			$new_contract_start_date_is_undefined = filter_input(INPUT_POST, 'new_contract_start_date_is_undefined');
			if ( empty( $new_contract_start_date_is_undefined ) ) {
				$new_contract_start_date_is_undefined = '0';
			}
			try {
				$campaign->set_api_data( 'contract_start_date_is_undefined', $new_contract_start_date_is_undefined );
				$success[ 'new_contract_start_date_is_undefined']  = 1;
			} catch (Exception $e) {
				$errors[ 'new_contract_start_date_is_undefined' ] = "Erreur pour date de début indéfinie";
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

			$new_minimum_costs_to_organization = WDG_Form::formatInputTextNumber( 'new_minimum_costs_to_organization', TRUE );
			if ( $new_minimum_costs_to_organization >= 0 ) {
				$campaign->set_api_data( 'minimum_costs_to_organization', $new_minimum_costs_to_organization );
				$success['new_minimum_costs_to_organization'] = 1;
			} else {
				$errors['new_minimum_costs_to_organization'] = "Nombre non valide";
			}

			$new_costs_to_organization = WDG_Form::formatInputTextNumber( 'new_costs_to_organization' );
			if ( $new_costs_to_organization >= 0 ) {
				update_post_meta( $campaign_id, ATCF_Campaign::$key_costs_to_organization, $new_costs_to_organization );
				$campaign->set_api_data( 'costs_to_organization', $new_costs_to_organization );
				$success['new_costs_to_organization'] = 1;
			} else {
				$errors['new_costs_to_organization'] = "Nombre non valide";
			}

			$new_costs_to_investors = WDG_Form::formatInputTextNumber( 'new_costs_to_investors' );
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
				if (empty($new_first_payment_date)) {
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
		}

		//Update list of estimated turnover
		$i = 0;
		$sanitized_list = array();
		$funding_duration = $campaign->funding_duration();
		if ( $funding_duration == 0 ) {
			$funding_duration = 5;
		}
		while ( filter_input( INPUT_POST, 'new_estimated_turnover_' . $i ) != '' && ( $i + 1 <= $funding_duration ) ) {
			$current_val = WDG_Form::formatInputTextNumber( 'new_estimated_turnover_' .$i );

			if ( is_numeric( $current_val ) ) {
				if ( $current_val >= 0 ) {
					$sanitized_list[ $i + 1 ] = $current_val;
					$success[ 'new_estimated_turnover_' . $i ] = 1;
				} else {
					$errors[ 'new_estimated_turnover_' . $i ] = "La valeur doit &ecirc;tre positive";
					$sanitized_list[ $i + 1 ] = 0;
				}
			} else {
				$errors[ 'new_estimated_turnover_' . $i ] = "Valeur invalide";
				$sanitized_list[ $i + 1 ] = 0;
			}

			$i++;
		}
		$campaign->__set( ATCF_Campaign::$key_estimated_turnover, json_encode( $sanitized_list ) );
		$campaign->set_api_data( 'estimated_turnover', json_encode( $sanitized_list ) );
		$campaign->update_api();

		$return_values = array(
			'response'	=> 'edit_funding',
			'errors'	=> $errors,
			'success'	=> $success
		);
		echo json_encode($return_values);
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
	public static function save_project_campaigntab() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$errors = array();
		$success = array();

		$end_vote_date = filter_input(INPUT_POST, 'new_end_vote_date');
		if (!empty($end_vote_date)) {
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
		if (!empty($begin_collecte_date)) {
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
		if (!empty($end_collecte_date)) {
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
	public static function save_project_status() {
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
	public static function create_contacts_table() {
		$campaign_id = filter_input(INPUT_POST, 'id_campaign');
		if ( !is_numeric( $campaign_id ) ) {
			return '<div class="wdg-datatable">Erreur de paramètre</div>';
		}

		$campaign = new ATCF_Campaign ($campaign_id);
		$campaign_poll_answers = $campaign->get_api_data( 'poll_answers' );

		$current_wdg_user = WDGUser::current();
		global $country_list;
		global $wpdb;
		$table_vote = $wpdb->prefix . "ypcf_project_votes";
		$table_jcrois = $wpdb->prefix . "jycrois";

		//Données suiveurs
		$list_user_follow = $wpdb->get_col( "SELECT DISTINCT user_id FROM ".$table_jcrois." WHERE subscribe_news = 1 AND campaign_id = ".$campaign_id. " GROUP BY user_id");

		//Données d'investissement
		$investments_list = (json_decode(filter_input(INPUT_POST, 'data'), true));

		//Données de vote
		$list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum, date, rate_project, advice, more_info_impact, more_info_service, more_info_team, more_info_finance, more_info_other FROM ".$table_vote." WHERE post_id = ".$campaign_id );

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
			$array_contacts[$u_id]["vote_invest_sum"]=$item_vote->invest_sum.' €';

			$array_contacts[$u_id]["vote_advice"]= ( !empty( $item_vote->advice ) ) ? '<i class="infobutton fa fa-comment" aria-hidden="true"></i><div class="tooltiptext">'.$item_vote->advice.'</div>' : '';
			$array_contacts[$u_id]["vote_rate"] = $item_vote->rate_project;

			$list_more_info = array();
			if ( $item_vote->more_info_impact == '1' ) {
				array_push( $list_more_info, 'Impacts' );
			}
			if ( $item_vote->more_info_service == '1' ) {
				array_push( $list_more_info, 'Service' );
			}
			if ( $item_vote->more_info_team == '1' ) {
				array_push( $list_more_info, 'Equipe' );
			}
			if ( $item_vote->more_info_finance == '1' ) {
				array_push( $list_more_info, 'Prévisionnel' );
			}
			if ( $item_vote->more_info_other != '' ) {
				array_push( $list_more_info, $item_vote->more_info_other );
			}
			$more_info_string = implode( ', ', $list_more_info );
			$array_contacts[$u_id]["vote_more_info"] = $more_info_string;
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
			$post_invest_status = $payment_investment->get_saved_status();

			if ( !empty( $item_invest[ 'payment_key' ] ) ) {
				$payment_key = $item_invest[ 'payment_key' ];
			} else {
				$payment_key = $item_invest[ 'lemonway_contribution' ] ? $item_invest[ 'lemonway_contribution' ] : $item_invest[ 'mangopay_contribution' ];
			}

			$u_id = $item_invest['user'];

			$payment_type = 'Carte';
			if (strpos($payment_key, 'wire_') !== FALSE) {
				$payment_type = 'Virement';
			} else {
				if ($payment_key == 'check') {
					$check_file_url = get_post_meta( $item_invest['ID'], 'check_picture', TRUE );
					if ( !empty( $check_file_url ) ) {
						if (parse_url($check_file_url, PHP_URL_SCHEME) != 'http' && parse_url($check_file_url, PHP_URL_SCHEME) != 'https') {
							$check_file_url = site_url() . '/wp-content/plugins/appthemer-crowdfunding/files/investment-check/' . $check_file_url;
						}
					} else {
						$created_from_draft = get_post_meta( $item_invest[ 'ID' ], 'created-from-draft', TRUE );
						if ( $created_from_draft ) {
							// si c'est le cas, alors on récupère l'investment-draft
							$investments_drafts_item = WDGWPREST_Entity_InvestmentDraft::get( $created_from_draft );
							$check_file_url = $investments_drafts_item->check;
						}
					}

					if ( !empty( $check_file_url ) && $current_wdg_user->is_admin() ) {
						$payment_type = '<a href="'.$check_file_url.'" target="_blank">Ch&egrave;que</a>';
					} else {
						$payment_type = 'Ch&egrave;que';
					}

					// Si c'est juste une intention avec dépot de fichiers
				} else {
					if ( $post_invest_status == 'pending' && $contract_status == WDGInvestment::$contract_status_not_validated ) {
						$payment_type = 'Non d&eacute;fini';
					}
				}
			}

			$page_dashboard = get_page_by_path('tableau-de-bord');
			$campaign_id_param = '?campaign_id=' . $campaign->ID;

			// Etat du paiement
			$payment_status_span_class = 'confirm';
			$payment_status = __( "Valid&eacute;", 'yproject' );
			$post_invest_status_span_class = $post_invest_status;
			
			if ($contract_status == WDGInvestment::$contract_status_preinvestment_validated && $campaign->campaign_status() == 'vote') {
				$post_invest_status_span_class = 'waiting';
				if ( strpos($payment_key, 'wire_') !== FALSE ) {
					$wire_with_received_payments = get_post_meta( $item_invest['ID'], 'has_received_wire', TRUE );
					if ( $wire_with_received_payments !== '1' ) {
						$payment_status = __( "En attente de r&eacute;ception par Lemon Way", 'yproject' );
						$payment_status_span_class = 'error';
					}
				}
			} elseif ( $post_invest_status == 'pending' ) {
				if ( strpos($payment_key, 'wire_') !== FALSE ) {
					$wire_with_received_payments = get_post_meta( $item_invest['ID'], 'has_received_wire', TRUE );
					if ( $campaign->campaign_status() != 'vote' || $wire_with_received_payments !== '1' ) {
						$payment_status = __( "En attente de r&eacute;ception par Lemon Way", 'yproject' );
						$payment_status_span_class = 'error';
					}
				} else {
					if ($payment_key == 'check') {
						$payment_status = __( "En attente de validation par WE DO GOOD", 'yproject' );
						$payment_status_span_class = 'error';
						if ( $current_wdg_user->is_admin() && empty( $contract_status ) ) {
							$payment_status .= '<br><a href="' .get_permalink($page_dashboard->ID) . $campaign_id_param. '&approve_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Confirmer]</a>';
							$payment_status .= '<br><br><a href="' .get_permalink($page_dashboard->ID) . $campaign_id_param. '&cancel_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Annuler]</a>';
						}
					} else {
						if ( $contract_status == WDGInvestment::$contract_status_not_validated ) {
							$payment_status = __( "Pas effectu&eacute;", 'yproject' );
							$payment_status_span_class = 'error';
							if ( $current_wdg_user->is_admin() && empty( $contract_status ) ) {
								$payment_status .= '<br><br><a href="' .get_permalink($page_dashboard->ID) . $campaign_id_param. '&try_pending_card='.$item_invest['ID'].'" style="font-size: 10pt;">[Retenter]</a>';
							}
						}
					}
				}
			} else {
				if ( $post_invest_status == 'failed' ) {
					$payment_status = __( "Paiement &eacute;chou&eacute;", 'yproject' );
					$payment_status_span_class = 'error';
					$post_invest_status_span_class = 'failed';
				}
			}

			$payment_status = '<span class="payment-status-' .$payment_status_span_class. '">' .$payment_status. '</span>';

			// Etat de la signature
			$action = '';
			$invest_sign_state = __( "Valid&eacute;", 'yproject' );
			$invest_sign_state_span_class = 'confirm';
			if ( $contract_status == WDGInvestment::$contract_status_preinvestment_validated ) {
				if ( $campaign->campaign_status() == 'vote' ){
					$invest_sign_state = __( "En attente du passage en phase d'investissement", 'yproject' );
					$invest_sign_state_span_class = 'waiting';
				}else{
					$invest_sign_state = __( "En attente de validation du pr&eacute;-investissement", 'yproject' );
					$invest_sign_state_span_class = 'error';
				}
				if ( $current_wdg_user->is_admin() ) {
					$action = '<br><a href="' .WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ). $campaign_id_param. '&approve_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Confirmer]</a>';
					$action .= '<br><br><a href="' .WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ). $campaign_id_param. '&cancel_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Annuler]</a>';
				}
			} else {
				if ( $post_invest_status == 'pending' ) {
					$invest_sign_state = __( "En attente de r&eacute;ception du paiement", 'yproject' );
					$invest_sign_state_span_class = 'error';
					if ( $current_wdg_user->is_admin() ) {
						$action = '<br><a href="' .WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ). $campaign_id_param. '&cancel_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Annuler]</a>';
					}
				} else {
					$WDGInvestmentSignature = new WDGInvestmentSignature( $item_invest[ 'ID' ] );
					if ( $WDGInvestmentSignature->is_waiting_signature() ) {
						$invest_sign_state = __( "En attente de signature électronique", 'yproject' );
						$invest_sign_state_span_class = 'error';
					}
				}
			}
			$invest_sign_state = '<span class="payment-status-' .$invest_sign_state_span_class. '">' .$invest_sign_state. '</span>';
			$invest_sign_state .= $action;

			// Contrats complémentaires
			if ( $post_invest_status == 'publish' ) {
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
								$wdg_contract_status = 'Signsquid désactivé';
							}
						}
					}

					$array_contacts[$u_id]['invest_contract_' .$contract_model_index] = $wdg_contract_status;
					$contract_model_index++;
				}
			}

			$invest_amount = '<span class="payment-status-' . $post_invest_status_span_class . '">' .$item_invest['amount']. '</span>';

			//Si il n'y a pas encore d'info de l'utilisateur pour un investissement, on rajoute une ligne
			if ( !isset($array_contacts[$u_id]) || !isset($array_contacts[$u_id]["invest"]) || $array_contacts[$u_id]["invest"] != 1 ) {
				$array_contacts[$u_id]["invest"] = 1;
				$array_contacts[$u_id]["invest_status"] = ( $post_invest_status == 'publish' ? 'success' : 'error' );
				$array_contacts[$u_id]["post_invest_status"] = $post_invest_status;
				$array_contacts[$u_id]["invest_payment_type"] = $payment_type;
				$array_contacts[$u_id]["invest_payment_status"] = $payment_status;
				$array_contacts[$u_id]["invest_amount"] = $invest_amount.' €';
				$datetime = new DateTime( get_post_field( 'post_date', $item_invest['ID'] ) );
				$datetime->add( new DateInterval( 'PT1H' ) );
				$array_contacts[$u_id]["invest_date"] = $datetime->format( 'Y-m-d H:i:s' );
				$array_contacts[$u_id]["invest_sign"] = $invest_sign_state;
				$array_contacts[$u_id]["invest_id"] = $item_invest['ID'];
				$array_contacts[$u_id]["invest_item"] = $item_invest;
				$array_contacts[$u_id]["more_invest"] = array();
			}

			// Ajout directement dans la liste des investissements
			$more_invest = array();
			$more_invest["invest_payment_type"] = $payment_type;
			$more_invest["invest_payment_status"] = $payment_status;
			$more_invest["post_invest_status"] = $post_invest_status;
			$more_invest["invest_amount"] = $invest_amount.' €';
			$datetime = new DateTime( get_post_field( 'post_date', $item_invest['ID'] ) );
			$datetime->add( new DateInterval( 'PT1H' ) );
			$more_invest["invest_date"] = $datetime->format( 'Y-m-d H:i:s' );
			$more_invest["invest_sign"] = $invest_sign_state;
			$more_invest["invest_id"] = $item_invest['ID'];
			$more_invest["invest_item"] = $item_invest;
			array_push( $array_contacts[$u_id]["more_invest"], $more_invest );
		}

		//Extraction infos utilisateur
		$count_distinct_investors = 0;
		foreach ( $array_contacts as $user_id => $user_item ) {
			//Données si l'investisseur est une organisation
			$array_contacts[$user_id]["user_id"] = $user_id;

			if (WDGOrganization::is_user_organization($user_id)) {
				$orga = new WDGOrganization($user_id);
				$linked_users = $orga->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
				$array_contacts[$user_id]["user_id"] .= ' - contrat : ' . $linked_users[ 0 ]->get_wpref();

				// Etat de l'authentification
				if ( $orga->get_lemonway_status() == LemonwayLib::$status_registered ) {
					$orga_authentication = __( "Valid&eacute;e", 'yproject' );
					$span_class = 'confirm';
					$error_str = '';
				} else {
					$orga_wallet_details = $orga->get_wallet_details();
					$span_class = 'error';
					$error_str = '';
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

						if ( $orga_wallet_details->STATUS != '6' ) {
							$error_str = LemonwayDocument::build_error_str_from_wallet_details( $orga_wallet_details );
						}
					}
				}

				$orga_authentication = '<span class="payment-status-' .$span_class. '">' .$orga_authentication. '</span>';
				if ( !empty( $error_str ) ) {
					$orga_authentication .= '<span class="authentication-more-info"><a href="#">+</a><span class="hidden">' . $error_str . '</span></span>';
				}
				$orga_creator = $orga->get_creator();
				$array_contacts[$user_id]["user_link"] = $orga->get_email();
				$array_contacts[$user_id]["user_email"] = $orga->get_email();
				$array_contacts[$user_id]["user_first_name"] = 'ORGA';
				$array_contacts[$user_id]["user_last_name"] = $orga->get_name();

				//Infos supplémentaires pour les votants
				if ($array_contacts[$user_id]["vote"] == 1 || $array_contacts[$user_id]["invest"] == 1) {
					$array_contacts[$user_id]["user_city"]= $orga->get_city();
					$array_contacts[$user_id]["user_postal_code"]= $orga->get_postal_code();
					$array_contacts[$user_id]["user_nationality"] = ucfirst(strtolower($orga->get_nationality()));
					$array_contacts[$user_id]["user_authentication"] = $orga_authentication;

					//Infos supplémentaires pour les investisseurs
					if ($array_contacts[$user_id]["invest"] == 1) {
						$count_distinct_investors++;
						$array_contacts[$user_id]["user_address"] = $orga->get_full_address_str();
						$array_contacts[$user_id]["user_country"] = ucfirst(strtolower($orga->get_nationality()));
						$array_contacts[$user_id]["user_mobile_phone"] = $orga_creator->get('user_mobile_phone');
						$array_contacts[$user_id]["user_orga_id"] = $orga->get_rcs() .' ('.$orga->get_idnumber().')';
					}
				}

				//Données si l'investisseur est un utilisateur normal
			} else {
				$WDGUser = new WDGUser( $user_id );
				//Infos supplémentaires pour les investisseurs
				if ($array_contacts[$user_id]["invest"] == 1) {
					// Etat de l'authentification
					if ( $WDGUser->get_lemonway_status() == LemonwayLib::$status_registered ) {
						$user_authentication = __( "Valid&eacute;e", 'yproject' );
						$span_class = 'confirm';
						$error_str = '';
					} else {
						$WDGUser_wallet_details = $WDGUser->get_wallet_details();
						$span_class = 'error';
						$error_str = '';
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

							if ( $WDGUser_wallet_details->STATUS != '6' ) {
								$error_str = LemonwayDocument::build_error_str_from_wallet_details( $WDGUser_wallet_details );
							}
						}
					}
					$user_authentication = '<span class="payment-status-' .$span_class. '">' .$user_authentication. '</span>';
					if ( !empty( $error_str ) ) {
						$user_authentication .= '<span class="authentication-more-info"><a href="#">+</a><span class="hidden">' . $error_str . '</span></span>';
					}

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
						$array_contacts[$user_id]["user_gender"] = $user_item[ 'invest_item' ][ 'item' ][ 'gender' ];
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
						$array_contacts[$user_id]["user_gender"] = $WDGUser->get_gender();
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

				if ( !empty( $campaign_poll_answers ) ) {
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
		}

		/*********Intitulés et paramÃ¨tres des colonnes***********/
		$status = $campaign->campaign_status();
		// l'ordre et l'affichage des colonnes dépend de la phase, évaluation ou collecte
		$display_invest_infos = false;
		if ( $status == ATCF_Campaign::$campaign_status_collecte
				|| $status == ATCF_Campaign::$campaign_status_funded
				|| $status == ATCF_Campaign::$campaign_status_closed
				|| $status == ATCF_Campaign::$campaign_status_archive ) {
			$display_invest_infos = true;
		}

		$display_vote_infos = true;
		if ( $status == ATCF_Campaign::$campaign_status_collecte
				|| $status == ATCF_Campaign::$campaign_status_funded
				|| $status == ATCF_Campaign::$campaign_status_closed
				|| $status == ATCF_Campaign::$campaign_status_archive ) {
			$display_vote_infos = false;
		}

		$array_columns = array(
			new ContactColumn('checkbox', '', true, 0, "none"),
			new ContactColumn('user_first_name', 'PRÉNOM', true, 1),
            new ContactColumn('user_last_name', 'NOM', true, 2),

			new ContactColumn('vote', 'A ÉVALUÉ', true, 3, "check", "N'afficher que les contacts ayant évalué"),
			new ContactColumn('vote_invest_sum', 'INTENTION D\'INV.', true, 4),

			new ContactColumn('vote_rate', "NOTE D'ÉVAL.", $display_vote_infos, ($display_vote_infos ? 5 : 23)),
            new ContactColumn('vote_date', "DATE D'ÉVAL.", $display_vote_infos, ($display_vote_infos ? 6 : 24)),
			new ContactColumn('vote_advice', 'CONSEIL', $display_vote_infos, ($display_vote_infos ? 7 : 25)),
			new ContactColumn( 'vote_more_info', '+ INFOS SUR', $display_vote_infos, ($display_vote_infos ? 8 : 26) ),

            new ContactColumn('user_link', 'UTILISATEUR', true, ($display_vote_infos ? 9 : 10)),

			new ContactColumn('invest_amount', 'MONTANT INVESTI', true, ($display_vote_infos ? 10 : 5)),
            new ContactColumn('invest_date', 'DATE D\'INV.', true, ($display_vote_infos ? 11 : 6)),
            new ContactColumn('invest_payment_type', 'MOYEN DE PAIEMENT', true, ($display_vote_infos ? 12 : 7) ),
            new ContactColumn('user_authentication', 'AUTHENTIFICATION', true, ($display_vote_infos ? 13 : 8) ),
			new ContactColumn('invest_payment_status', 'PAIEMENT', true, ($display_vote_infos ? 14 : 9) ),

			new ContactColumn('invest_sign', 'SIGNATURE', $display_vote_infos, ($display_vote_infos ? 15 : 22) ),

			new ContactColumn( 'source-how-known', 'SRC. (CONNU)', true, ($display_vote_infos ? 16 : 11)),
			new ContactColumn( 'source-where-from', 'SRC. (ARRIVÉE)', true, ($display_vote_infos ? 17 : 12) ),
			new ContactColumn('user_mobile_phone', 'TÉLÉPHONE', true, ($display_vote_infos ? 18 : 13)),
            new ContactColumn('user_address', 'ADRESSE', false, ($display_vote_infos ? 19 : 14)),
            new ContactColumn('user_postal_code', 'CODE POSTAL', false, ($display_vote_infos ? 20 : 15)),
            new ContactColumn('user_city', 'VILLE', false, ($display_vote_infos ? 21 : 16)),
            new ContactColumn('user_country', 'PAYS', false, ($display_vote_infos ? 22 : 17)),
            new ContactColumn('user_gender', 'GENRE', false, ($display_vote_infos ? 23 : 18)),
            new ContactColumn('user_birthday', 'DATE DE NAISSANCE', false, ($display_vote_infos ? 24 : 19)),
            new ContactColumn('user_birthplace', 'VILLE DE NAISSANCE', false, ($display_vote_infos ? 25 : 20)),
			new ContactColumn('user_nationality', 'NATIONALITÉ', false, ($display_vote_infos ? 26 : 21)),

			new ContactColumn('follow', 'SUIT LE PROJET', false, 27, "check", "N'afficher que les contacts suivant le projet"),
            new ContactColumn('invest', 'A INVESTI', false, 28, "check", "N'afficher que les contacts ayant investi"),

			new ContactColumn('user_id', '', false, 30), // cette colonne est cachée, mais sert à l'envoi des mails
        );

		if ( $contracts_to_add ) {
			$contract_model_index = 1;
			foreach ( $contracts_to_add as $contract_model ) {
				array_push( $array_columns, new ContactColumn('invest_contract_' .$contract_model_index, 'CONTRAT ' .$contract_model_index, $display_invest_infos, 29) );
				$contract_model_index++;
			}
		}

		$array_contacts = self::clean_failed_investments($array_contacts);

		// on trie le tableau des colonnes suivant l'ordre de priorité déclaré
		usort($array_columns, array("ContactColumn", "cmp_obj"));

		$imgcheck_follow = '<img src="'.get_stylesheet_directory_uri().'/images/check.png" alt="investi" title="A investi" width="25px" style="margin-left:0px;"/>';
		$imgcheck_vote = '<img src="'.get_stylesheet_directory_uri().'/images/check.png" alt="vote" title="A évalué" width="25px" style="margin-left:0px;"/>';
		$imgcheck_invest = '<img src="'.get_stylesheet_directory_uri().'/images/check.png" alt="investi" title="A investi" width="25px" style="margin-left:0px;"/>'; ?>
        <div class="wdg-datatable" >
        <table id="contacts-table" class="display" cellspacing="0">
            <?php //Ecriture des nom des colonnes en haut?>
            <thead>
            <tr>
                <?php foreach ($array_columns as $column) { ?>
                    <th><?php echo $column->columnName; ?></th>
                <?php } ?>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($array_contacts as $id_contact => $data_contact): ?>
				<?php
				$has_more = array();
		if ( $data_contact["more_invest"] ) {
			$has_more = $data_contact["more_invest"];
		} ?>

				<?php if ( empty( $has_more ) ): ?>
				<tr data-DT_RowId="<?php echo $id_contact; ?>" data-investid="<?php echo $data_contact["invest_id"]; ?>">
					<?php foreach ($array_columns as $column): ?>
                	<td>
					<?php if ( $column->columnData == "follow" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imgcheck_follow; ?>

					<?php elseif ( $column->columnData == "vote" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imgcheck_vote; ?>

					<?php elseif ( $column->columnData == "invest" && $data_contact[$column->columnData]==1 ): ?>
						<?php if ( $data_contact["invest_status"] == "success" ): ?>
							<div class="dirty-hide">1</div>
							<?php echo $imgcheck_invest; ?>
						<?php endif; ?>

					<?php else: ?>
						<?php echo $data_contact[$column->columnData]; ?>
					<?php endif; ?>
					</td>
					<?php endforeach; ?>
				</tr>
				
				<?php else: ?>
				<?php //Gestion de plusieurs investissements par la mÃªme personne
				foreach ($has_more as $has_more_item): ?>
				<tr data-DT_RowId="<?php echo $id_contact; ?>" data-investid="<?php echo $has_more_item["invest_id"]; ?>">
					<?php foreach ($array_columns as $column): ?>
                	<td>
					<?php if ( $column->columnData == "follow" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imgcheck_follow; ?>

					<?php elseif ( $column->columnData == "vote" && $data_contact[$column->columnData]==1 ): ?>
						<div class="dirty-hide">1</div>
						<?php echo $imgcheck_vote; ?>

					<?php elseif ( $column->columnData == "invest" && $data_contact[$column->columnData]==1 ): ?>
						<?php if ( $data_contact["invest_status"] == "success" ): ?>
							<div class="dirty-hide">1</div>
							<?php echo $imgcheck_invest; ?>
						<?php endif; ?>
						
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
				<?php endif; ?>
				
			<?php endforeach; ?>
            </tbody>

            <tfoot>
            <tr>
                <?php
				$i = 0;
		foreach ($array_columns as $column) {
			$type_filter = $column->filterClass?>
                    <th class="<?php echo $type_filter; ?>">
						<?php
						switch ($type_filter) {
							case "text":
								echo '<input type="text" class="qtip-element" placeholder="Filtrer " data-index="'.$column->columnPriority.'" title="'.$column->filterQtip.'"/><br/>'.$column->columnName;
								break;
							case "check":
								echo '<input type="checkbox" class="qtip-element" data-index="'.$column->columnPriority.'" title="'.$column->filterQtip.'"/>';
								break;
						}
			$i++; ?>
					</th>
                <?php
		} ?>
            </tr>
            </tfoot>
        </table>
        </div>

        <?php

        //Colonnes Ã  afficher par défaut
        $array_hidden = array();
		$i = 0;
		foreach ($array_columns as $column) {
			if (!$column->defaultDisplay) {
				$array_hidden[]=$i;
			}
			$i++;
		}

		//Identifiants de colonnes par lesquels seront triés les contacts par défaut
		$default_sort=false;
		$i = 0;
		foreach ($array_columns as $column) {
			if ($column->columnData == 'invest_date' && $display_invest_infos) {
				$default_sort=$i;
			} else {
				if ($column->columnData == 'vote_date' && $display_vote_infos) {
					$default_sort=$i;
				}
			}
			$i++;
		}

		$result = array(
            'default_sort' => $default_sort,
            'array_hidden' => $array_hidden,
            'id_column_user_id' => 30,
			'id_column_index' => 3
        ); ?>
        <script type="text/javascript">
            var result_contacts_table = <?php echo(json_encode($result)); ?>
        </script>
        <?php
        exit();
	}

	private static function clean_failed_investments($array_contacts) {
		// on retire l'investissement qui est "failed" si cet investisseur a des investissements plus récents "pending" ou "publish" dont le montant total est supérieur à l'investissement "failed"
		foreach ($array_contacts as $id_contact => &$data_contact) {
			// pour chaque investisseur on regarde s'il y a plusieurs investissements
			if ($data_contact["more_invest"] && !empty($data_contact["more_invest"])) {
				// on trie le tableau des investissements en partant du plus ancien
				usort( $data_contact["more_invest"], function ($item1, $item2) {
					return ( $item1[ 'invest_id' ] > $item2[ 'invest_id' ] );
				} );

				$has_more = $data_contact["more_invest"];
				// s'il y a plusieurs investissements
				$amount_last_failed_investment = FALSE;
				$last_failed_investment_id = array();
				$failed_investments_to_delete = array();
				foreach ($has_more as $has_more_item) {
					// on regarde si un des investissements est failed
					if ( $has_more_item["post_invest_status"] == 'failed' ) {
						// on réinitialise le compteur
						$amount_last_failed_investment = $has_more_item["invest_item"]["amount"];
						// on mémorise l'id de cet investissement
						$last_failed_investment_id[] = $has_more_item["invest_id"];
					} else {
						if ( $amount_last_failed_investment ) {
							// on diminue le compteur du montant de cet investissement
							$amount_last_failed_investment = $amount_last_failed_investment  - $has_more_item["invest_item"]["amount"];
							// si le compteur est égal ou inférieur à 0
							if ($amount_last_failed_investment <= 0) {
								// alors on peut supprimer les derniers investissements failed
								$failed_investments_to_delete = array_merge($failed_investments_to_delete, $last_failed_investment_id);
								// et réinitialiser les variables
								$amount_last_failed_investment = FALSE;
								$last_failed_investment_id = array();
							}
						}
					}
				}

				// on supprime les investissements failed à supprimer
				if ( !empty($failed_investments_to_delete) ) {
					foreach ($failed_investments_to_delete as $failed_investment_id ) {
						$key = array_search($failed_investment_id, array_column($data_contact["more_invest"], 'invest_id'));
						if ($key !== FALSE ) {
							array_splice($data_contact["more_invest"], $key, 1);
						}
					}
				}
			}
		}

		return $array_contacts;
	}

	/**
	 * Crée l'aperÃ§u du mail Ã  confirmer avant de l'envoyer (Tableau de bord)
	 */
	public static function preview_mail_message() {
		$campaign_id = filter_input(INPUT_POST, 'id_campaign');
		$errors = array();

		$title = sanitize_text_field(filter_input(INPUT_POST, 'mail_title'));
		if (empty($title)) {
			$errors[]= "L'objet du mail ne peut Ãªtre vide";
		}
		$content = filter_input(INPUT_POST, 'mail_content');
		$content = WDGFormProjects::build_mail_text($content, $title, $campaign_id);

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
				foreach ( $user_organizations_list as $organization_item ) {
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
		$WDGUser->save_data(FALSE, $gender, $firstname, $lastname, FALSE, $birthday_date_day, $birthday_date_month, $birthday_date_year, $birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality, $address_number, $address_number_complement, $address, $postal_code, $city, $country, FALSE, FALSE, FALSE);

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

			if (is_wp_error($id_linked_user)) {
				exit( 'La validation du chèque a échoué car l\'utilisateur n\'a pas pu être ajouté' );
			}

			try {
				$birthday_date = DateTime::createFromFormat( 'd/m/Y', $investments_drafts_item_data->birthday );
				$birthday_date_day = $birthday_date->format( 'd' );
				$birthday_date_month = $birthday_date->format( 'm' );
				$birthday_date_year = $birthday_date->format( 'Y' );
			} catch (Exception $e) {
				exit( 'La validation du chèque a échoué car la date de naissance est invalide' );
			}

			$WDGUser_new = new WDGUser( $id_linked_user );
			$WDGUser_new->set_language( WDG_Languages_Helpers::get_current_locale_id() );
			$WDGUser_new->update_api();
			$WDGUser_new->save_data(FALSE, $investments_drafts_item_data->gender, $investments_drafts_item_data->firstname, $investments_drafts_item_data->lastname, FALSE, $birthday_date_day, $birthday_date_month, $birthday_date_year, $investments_drafts_item_data->birthplace, $investments_drafts_item_data->birthplace_district, $investments_drafts_item_data->birthplace_department, $investments_drafts_item_data->birthplace_country, $investments_drafts_item_data->nationality, $investments_drafts_item_data->address_number, $investments_drafts_item_data->address_number_complement, $investments_drafts_item_data->address, $investments_drafts_item_data->postal_code, $investments_drafts_item_data->city, $investments_drafts_item_data->country, FALSE, FALSE, FALSE);

			// Notification de création de compte
			NotificationsEmails::investment_draft_validated_new_user( $investments_drafts_item_data->email, $investments_drafts_item_data->firstname, $new_password, $campaign->get_name() );
		}

		// Création compte organisation si non-existant
		if ( $investments_drafts_item_data->user_type == 'orga' && empty( $id_linked_organization ) ) {
			$WDGOrganization = WDGOrganization::createSimpleOrganization( $id_linked_user, $investments_drafts_item_data->orga_name, $investments_drafts_item_data->orga_email );
			if ( empty( $WDGOrganization ) ) {
				exit( 'La validation du chèque a échoué car la création de l\'organisation a échoué' );
			}
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
		$investment_id = $campaign->add_investment('check', $investments_drafts_item_data->email, $investments_drafts_item_data->invest_amount, 'publish', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $investments_drafts_item_data->orga_email);
		if ($investment_id !== FALSE) {
			add_post_meta( $investment_id, 'created-from-draft', $investments_drafts_item->id );
			//  ajouter post meta check_picture avec le lien vers l'image du check qui se trouve dans investment-draft/picture-check
			add_post_meta( $investment_id, 'check_picture', $investments_drafts_item->check );

			// Valider le draft
			WDGWPREST_Entity_InvestmentDraft::edit( $investments_drafts_item->id, 'validated' );

			// Notifications de validation d'investissement
			NotificationsEmails::new_purchase_user_success_check( $investment_id );
			NotificationsEmails::new_purchase_team_members( $investment_id );
			NotificationsSlack::send_new_investment( $campaign->get_name(), $investments_drafts_item_data->invest_amount, $investments_drafts_item_data->email );

			exit( '1' );
		} else {
			exit( 'La validation du chèque a échoué car l\'investissement n\'a pas pu être ajouté' );
		}
	}

	/**
	 * Lance les transferts d'argent vers les différents investisseurs
	 */
	public static function proceed_roi_transfers() {
		$buffer = FALSE;
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$declaration_id = filter_input(INPUT_POST, 'roi_id');
		$is_refund = filter_input(INPUT_POST, 'isrefund');
		if ( !empty( $campaign_id ) && !empty( $declaration_id ) && $WDGUser_current->is_admin() ) {
			$input_send_notifications = filter_input( INPUT_POST, 'send_notifications' );
			$send_notifications = ( $input_send_notifications != 'false' && ( $input_send_notifications === 1 || $input_send_notifications === TRUE || $input_send_notifications === 'true' ) );
			$input_transfer_remaining_amount = filter_input( INPUT_POST, 'transfer_remaining_amount' );
			$transfer_remaining_amount = ( $input_transfer_remaining_amount != 'false' && ( $input_transfer_remaining_amount === 1 || $input_transfer_remaining_amount === TRUE || $input_transfer_remaining_amount === 'true' ) );
			$roi_declaration = new WDGROIDeclaration( $declaration_id );
			$buffer = $roi_declaration->transfer_pending_rois();
		}

		echo json_encode( $buffer );
		exit();
	}

	public static function cancel_pending_investments() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');

		if ( $WDGUser_current->is_admin() && !empty( $campaign_id ) ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded
					|| $campaign->campaign_status() == ATCF_Campaign::$campaign_status_archive
					|| $campaign->campaign_status() == ATCF_Campaign::$campaign_status_closed ) {
				$payments_data = $campaign->payments_data( TRUE );
				foreach ( $payments_data as $payment_item ) {
					if ( $payment_item[ 'status' ] == 'pending' ) {
						$WDGInvestment = new WDGInvestment( $payment_item[ 'ID' ] );
						$WDGInvestment->cancel();
					}
				}
			}
		}

		exit( '1' );
	}

	/**
	 * Lance la duplication d'une campagne
	 */

	public static function campaign_duplicate() {
		$WDGUser_current = WDGUser::current();
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');

		if ( $WDGUser_current->is_admin() && !empty( $campaign_id ) ) {
			$campaign_ref = new ATCF_Campaign( $campaign_id ); // on utilise la campagne existante pour reprendre certains paramètres
			$newcampaign_id = $campaign_ref->duplicate();
		}

		exit('1' );
	}

	/**
	 * Transfert des investissements d'une campagne à une autre
	 */
	public static function campaign_transfer_investments() {
		$from_campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign_ref = new ATCF_Campaign( $from_campaign_id );

		if ($campaign_ref->is_funded()) {
			$WDGUser_current = WDGUser::current();
			$to_campaign_id = filter_input(INPUT_POST, 'duplicated_campaign');

			if ( $WDGUser_current->is_admin() && !empty( $from_campaign_id ) && !empty( $to_campaign_id ) ) {
				WDGCampaignInvestments::transfer_investments( $from_campaign_id, $to_campaign_id );
			}

			exit('1' );
		}
		ypcf_debug_log( 'WDGAjaxActions::action_project_dashboard::campaign_transfer_investments campagne de départ not funded ' );
		exit('0' );
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

		$return_values = array(
			"response" => "done",
			"values" => $property,
			"user" => $name
		);

		if ( empty( $meta_old_value[ 'user' ] ) ) {
			$return_values[ 'response' ] = "error";
			echo json_encode($return_values);
			exit();
		}

		$WDGUser = new WDGUser( $meta_old_value[ 'user' ] );
		$name = $WDGUser->get_firstname()." ".$WDGUser->get_lastname();

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
		echo $property;
		wp_die();

		exit();
	}

	public static function send_test_notifications() {
		$notif_type = filter_input( INPUT_POST, 'notif_type' );

		$result = false;
		if ( $notif_type == 'send_project_notifications' ) {
			$result = WDGPostActions::send_project_notifications( true );
		}
		if ( $notif_type == 'send_project_notifications_end_vote' ) {
			$result = WDGPostActions::send_project_notifications_end_vote( true );
		}
		if ( $notif_type == 'send_project_notifications_end' ) {
			$result = WDGPostActions::send_project_notifications_end( true );
		}

		echo $result ? '1' : '0';
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
	public $columnPriority;

	function __construct($newColumnData, $newColumnName, $newDefaultDisplay=false, $columnPriority, $newFilterClass = "text", $newFilterQtip = "") {
		$this->columnData = $newColumnData;
		$this->columnName = $newColumnName;
		$this->defaultDisplay = $newDefaultDisplay;
		$this->columnPriority = $columnPriority;
		$this->filterClass = $newFilterClass;
		$this->filterQtip = $newFilterQtip;
	}

	/* Ceci est une fonction de comparaison statique */
	static function cmp_obj($a, $b) {
		if ($a->columnPriority == $b->columnPriority) {
			return 0;
		}

		return ($a->columnPriority > $b->columnPriority) ? +1 : -1;
	}
}