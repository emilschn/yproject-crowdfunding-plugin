<?php
/**
 * Classe de gestion des déclarations de ROI
 */
class WDGROIDeclaration {
	public static $table_name = 'ypcf_roideclaration';
	protected static $collection_by_id;
	
	public static $status_declaration = 'declaration';
	public static $status_payment = 'payment';
	public static $status_waiting_transfer = 'waiting_transfer';
	public static $status_transfer = 'transfer';
	public static $status_finished = 'finished';
	
	public static $mean_payment_card = 'card';
	public static $mean_payment_wire = 'wire';
	public static $mean_payment_mandate = 'mandate';
	
	public static $min_amount_for_wire_payment = 1000;
	
	public $id;
	public $id_campaign;
	public $date_due;
	public $date_paid;
	public $date_transfer;
	public $amount;
	public $remaining_amount;
	public $percent_commission;
	public $status;
	public $mean_payment;
	public $payment_token;
	public $file_list;
	public $turnover;
	public $message;
	public $adjustment;
	public $transfered_previous_remaining_amount;
	
	public $employees_number;
	public $other_fundings;
	
	public $on_api;
	
	
	public function __construct( $declaration_id = FALSE, $local = FALSE, $data = FALSE ) {
		if ( !empty( $declaration_id ) ) {
			// Si déjà chargé précédemment
			if ( isset( self::$collection_by_id[ $declaration_id ] ) || $data !== FALSE ) {
				$collection_item = isset( self::$collection_by_id[ $declaration_id ] ) ? self::$collection_by_id[ $declaration_id ] : $data;
				$this->id = $collection_item->id;
				$this->id_campaign = isset( $collection_item->id_campaign ) ? $collection_item->id_campaign : $collection_item->id_project;
				$this->date_due = $collection_item->date_due;
				$this->date_paid = $collection_item->date_paid;
				$this->date_transfer = $collection_item->date_transfer;
				$this->amount = $collection_item->amount;
				$this->remaining_amount = $collection_item->remaining_amount;
				$this->percent_commission = $collection_item->percent_commission;
				$this->status = $collection_item->status;
				$this->mean_payment = $collection_item->mean_payment;
				$this->payment_token = $collection_item->payment_token;
				$this->file_list = $collection_item->file_list;
				$this->turnover = $collection_item->turnover;
				$this->message = $collection_item->message;
				$this->adjustment = $collection_item->adjustment;
				$this->transfered_previous_remaining_amount = $collection_item->transfered_previous_remaining_amount;
				$this->employees_number = $collection_item->employees_number;
				$this->other_fundings = $collection_item->other_fundings;
				if ( isset( $collection_item->on_api ) ) {
					$this->on_api = $collection_item->on_api;
				}

			} else {
				// Récupération en priorité depuis l'API
				$declaration_api_item = ( !$local ) ? WDGWPREST_Entity_Declaration::get( $declaration_id ) : FALSE;
				if ( $declaration_api_item != FALSE ) {

					$this->id = $declaration_id;
					$this->id_campaign = $declaration_api_item->id_project;
					$this->date_due = $declaration_api_item->date_due;
					$this->date_paid = $declaration_api_item->date_paid;
					$this->date_transfer = $declaration_api_item->date_transfer;
					$this->amount = $declaration_api_item->amount;
					$this->remaining_amount = $declaration_api_item->remaining_amount;
					if ( !is_numeric( $this->remaining_amount ) ) {
						$this->remaining_amount = 0;
					}
					$this->percent_commission = $declaration_api_item->percent_commission;
					if ( !is_numeric( $this->percent_commission ) ) {
						$this->percent_commission = 0;
					}
					$this->status = $declaration_api_item->status;
					$this->mean_payment = $declaration_api_item->mean_payment;
					$this->payment_token = $declaration_api_item->payment_token;
					$this->file_list = $declaration_api_item->file_list;
					$this->turnover = $declaration_api_item->turnover;
					$this->message = $declaration_api_item->message;
					$this->adjustment = $declaration_api_item->adjustment;
					if ( is_null( $this->adjustment ) ) {
						$this->adjustment = '';
					}
					$this->transfered_previous_remaining_amount = $declaration_api_item->transfered_previous_remaining_amount;
					if ( !is_numeric( $this->transfered_previous_remaining_amount ) ) {
						$this->transfered_previous_remaining_amount = 0;
					}

					$this->employees_number = $declaration_api_item->employees_number;
					$this->other_fundings = $declaration_api_item->other_fundings;

					$this->on_api = TRUE;

					// Les déclarations sans statut doivent passer en statut "Déclaration"
					if ( empty( $this->status ) || $this->status == null ) {
						$this->status = WDGROIDeclaration::$status_declaration;
					}

					// Les déclarations à zero pour les projets en mode "paiement" doivent être marquées comme terminées
					if ( $this->status == WDGROIDeclaration::$status_payment && !empty( $this->turnover ) && $this->get_amount_with_adjustment() == 0 ) {
						$this->status = WDGROIDeclaration::$status_transfer;
						$this->save();
					}

				// Sinon récupération sur la bdd locale (deprecated)
				} else {

					global $wpdb;
					$table_name = $wpdb->prefix . WDGROIDeclaration::$table_name;
					$query = 'SELECT * FROM ' .$table_name. ' WHERE id=' .$declaration_id;
					$declaration_item = $wpdb->get_row( $query );
					if ( $declaration_item ) {
						$this->id = $declaration_item->id;
						$this->id_campaign = $declaration_item->id_campaign;
						$this->date_due = $declaration_item->date_due;
						$this->date_paid = $declaration_item->date_paid;
						$this->date_transfer = $declaration_item->date_transfer;
						$this->amount = $declaration_item->amount;
						$this->remaining_amount = $declaration_item->remaining_amount;
						if ( !is_numeric( $this->remaining_amount ) ) {
							$this->remaining_amount = 0;
						}
						$this->percent_commission = $declaration_item->percent_commission;
						if ( !is_numeric( $this->percent_commission ) ) {
							$this->percent_commission = 0;
						}
						$this->status = $declaration_item->status;
						$this->mean_payment = $declaration_item->mean_payment;
						$this->payment_token = $declaration_item->payment_token;
						$this->file_list = $declaration_item->file_list;
						$this->turnover = $declaration_item->turnover;
						$this->message = $declaration_item->message;
						$this->adjustment = $declaration_item->adjustment;
						if ( is_null( $this->adjustment ) ) {
							$this->adjustment = '';
						}
						$this->transfered_previous_remaining_amount = $declaration_item->transfered_previous_remaining_amount;
						if ( !is_numeric( $this->transfered_previous_remaining_amount ) ) {
							$this->transfered_previous_remaining_amount = 0;
						}
						$this->on_api = ( $declaration_item->on_api == 1 );
						$this->employees_number = 0;
						$this->other_fundings = '';

						// Les déclarations sans statut doivent passer en statut "Déclaration"
						if ( empty( $this->status ) || $this->status == null ) {
							$this->status = WDGROIDeclaration::$status_declaration;
						}

						// Les déclarations à zero pour les projets en mode "paiement" doivent être marquées comme terminées
						if ( $this->status == WDGROIDeclaration::$status_payment && !empty( $this->turnover ) && $this->get_amount_with_adjustment() == 0 ) {
							$this->status = WDGROIDeclaration::$status_transfer;
							$this->save();
						}
					}

				}
				self::$collection_by_id[ $declaration_id ] = $this;

			}
		}
	}
	
	/**
	 * Sauvegarde les donnÃ©es dans l'API
	 */
	public function update() {
		WDGWPREST_Entity_Declaration::update( $this );
	}
	
	/**
	 * Sauvegarde dans la BDD locale
	 * @deprecated
	 * @return integer
	 */
	public function save( $local = FALSE ) {
		if ( $this->on_api && !$local ) {
			$this->update();
			
		} else {
			global $wpdb;
			$table_name = $wpdb->prefix . WDGROIDeclaration::$table_name;
			$result = $wpdb->update( 
				$table_name, 
				array( 
					'id_campaign' => $this->id_campaign,
					'date_due' => $this->date_due,
					'date_paid' => $this->date_paid,
					'date_transfer' => $this->date_transfer,
					'amount' => $this->amount,
					'remaining_amount' => $this->remaining_amount,
					'percent_commission' => $this->percent_commission,
					'status' => $this->status,
					'mean_payment' => $this->mean_payment,
					'payment_token' => $this->payment_token,
					'file_list' => $this->file_list,
					'turnover' => $this->turnover,
					'message' => $this->message,
					'adjustment' => $this->adjustment,
					'transfered_previous_remaining_amount' => $this->transfered_previous_remaining_amount,
					'on_api' => ( $this->on_api ? 1 : 0 )
				),
				array(
					'id' => $this->id
				)
			);
			if ($result !== FALSE) {
				return $this->id;
			}
		}
	}
	
	public function get_formatted_date( $type = 'due' ) {
		$buffer = '';
		$temp_date = '';
		switch ($type) {
			case 'due':
				$temp_date = $this->date_due;
				break;
			case 'paid':
				$temp_date = $this->date_paid;
				break;
			case 'transfer':
				$temp_date = $this->date_transfer;
				break;
		}
		if ( !empty($temp_date) ) {
			$exploded_date = explode('-', $temp_date);
			$buffer = $exploded_date[2] .'/'. $exploded_date[1] .'/'. $exploded_date[0];
		}
		return $buffer;
	}
	
	public function get_status() {
		return $this->status;
	}
	
	/**
	 * Retourne le montant additionnÃ© l'ajustement
	 * @return number
	 */
	public function get_amount_with_adjustment() {
		return ( $this->amount + $this->get_adjustment_value() );
	}
	
	/**
	 * Retourne le montant additionnÃ© avec la commission
	 * @return number
	 */
	public function get_amount_with_commission() {
		return ( $this->get_amount_with_adjustment() + $this->get_commission_to_pay() );
	}
	
	/**
	 * Retourne la commission Ã©ventuelle que doit payer le porteur de projet au moment de reverser les fonds
	 * @return number
	 */
	public function get_commission_to_pay() {
		$buffer = 0;
		
		//Si le porteur de projet a dÃ©jÃ  payÃ©, on considÃ¨re qu'on a dÃ©jÃ  enregistrÃ© la commission
		if ( $this->status == WDGROIDeclaration::$status_transfer || $this->status == WDGROIDeclaration::$status_finished ) {
			$cost = $this->percent_commission;
			
		//Sinon, on la calcule avec les frais enregistrÃ©s en rapport avec la campagne
		} else {
			$campaign = new ATCF_Campaign( FALSE, $this->id_campaign );
			$cost = $campaign->get_costs_to_organization();
		}
		
		if ( $cost > 0 ) {
			$buffer = (round(($this->get_amount_with_adjustment() * $cost / 100) * 100) / 100);
		}
		return $buffer;
	}
	
	/**
	 * Traite un fichier uploadÃ© qui doit Ãªtre ajoutÃ© Ã  la liste
	 * @param array $file_uploaded_data
	 */
	public function add_file( $file_uploaded_data, $file_description ) {
		$file_name = $file_uploaded_data['name'];
		$file_name_exploded = explode('.', $file_name);
		$ext = $file_name_exploded[count($file_name_exploded) - 1];
		
		$random_filename = '';
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$size = strlen( $chars );
		for( $i = 0; $i < 15; $i++ ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		while ( file_exists( __DIR__ . '/../accounts/' . $random_filename . '.' . $ext ) ) {
			$random_filename .= $chars[ rand( 0, $size - 1 ) ];
		}
		$random_filename = $random_filename . '.' . $ext;
		move_uploaded_file( $file_uploaded_data['tmp_name'], __DIR__ . '/../accounts/' . $random_filename );
		
		$file_item = array(
			'file'	=> $random_filename,
			'text'	=> $file_description
		);
		$file_list = $this->get_file_list();
		array_push( $file_list, $file_item );
		
		$this->file_list = json_encode( $file_list );
		
		$this->update();
		$this->save();
	}
	
	/**
	 * Renvoie la liste des fichiers avec leur bonne url
	 * @return array
	 */
	public function get_file_list() {
		$buffer = array();
		if ( !empty( $this->file_list ) ) {
			$buffer = json_decode( $this->file_list );
		}
		return $buffer;
	}
	
	/**
	 * Renvoie le chemin de fichier pour les comptes
	 * @return string
	 */
	public function get_file_path() {
		return home_url() . '/wp-content/plugins/appthemer-crowdfunding/includes/accounts/';
	}
	
	/**
	 * Retourne le CA sous forme de tableau
	 * @return array
	 */
	public function get_turnover() {
		$buffer = json_decode($this->turnover);
		return $buffer;
	}
	/**
	 * Enregistre le CA en json
	 * @param array $turnover_array
	 */
	public function set_turnover($turnover_array) {
		$saved_turnover = json_encode($turnover_array);
		$this->turnover = $saved_turnover;
	}
	/**
	 * Retourne le montant total de CA dÃ©clarÃ©
	 * @return int
	 */
	public function get_turnover_total() {
		$buffer = 0;
		$turnover_array = $this->get_turnover();
		if ( is_array( $turnover_array ) ) {
			foreach ($turnover_array as $turnover_amount) {
				$buffer += $turnover_amount;
			}
		}
		return $buffer;
	}
	
	public function get_message() {
		return nl2br( $this->message, ENT_HTML5 );
	}
	public function set_message( $message ) {
		$this->message = htmlentities( $message );
	}
	
	public function get_other_fundings() {
		return nl2br( $this->other_fundings, ENT_HTML5 );
	}
	public function set_other_fundings( $other_fundings ) {
		$this->other_fundings = htmlentities( $other_fundings );
	}
	
	/**
	 * S'occuper des versements vers les utilisateurs
	 */
	public function make_transfer( $send_notifications = true, $transfer_remaining_amount = false ) {
		$buffer = false;
		$date_now = new DateTime();
		$date_now_formatted = $date_now->format( 'Y-m-d' );
		$campaign = new ATCF_Campaign( FALSE, $this->id_campaign );
		$current_organization = $campaign->get_organization();
		if ( !empty( $current_organization ) ) {
			$organization_obj = new WDGOrganization($current_organization->wpref);
			$organization_obj->register_lemonway();
			$investments_list = $campaign->roi_payments_data( $this, $transfer_remaining_amount );
			$total_fees = 0;
			
			// Initialisation du montant restant pour que ce soit toujours la variable de classe qui soit mise à jour
			if ( $this->remaining_amount == 0 ) {
				$this->remaining_amount = $this->get_amount_with_adjustment();
				if ( $transfer_remaining_amount ) {
					$previous_remaining_amount = $this->get_previous_remaining_amount();
					$this->remaining_amount += $previous_remaining_amount;
				}
			}
			
			// On différencie $count et $count_done
			// Le premier sert à compter le nombre total (pour donner un pourcentage en retour)
			// Le second sert à déterminer quand on s'arrête lors de ce passage
			$count = 0;
			$count_done = 0;
			foreach ($investments_list as $investment_item) {
				$count++;
				$saved_roi = $this->get_roi_by_investment( $investment_item['ID'] );
				if ( empty( $saved_roi ) ) {
					$count_done++;
					$total_fees += $investment_item['roi_fees'];
					$this->remaining_amount -= $investment_item['roi_fees'];
					$this->remaining_amount -= $investment_item['roi_amount'];

					//Versement vers organisation
					$recipient_api_id = FALSE;
					$transfer = FALSE;
					if (WDGOrganization::is_user_organization( $investment_item['user'] )) {
						$WDGOrga = new WDGOrganization( $investment_item['user'] );
						$WDGOrga->register_lemonway();
						$recipient_api_id = $WDGOrga->get_api_id();
						if ( $investment_item['roi_amount'] > 0 ) {
							$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGOrga->get_lemonway_id(), $investment_item['roi_amount'] );
							/*$credit_bank_info = WDGWPREST_Entity_BankInfo::get( $WDGOrga->get_email() );
							if ( $credit_bank_info != FALSE ) {
								$send_notifications = FALSE;
								$WDGOrga->set_bank_owner( $credit_bank_info->holdername );
								$WDGOrga->set_bank_iban( $credit_bank_info->iban );
								$WDGOrga->set_bank_bic( $credit_bank_info->bic );
								$WDGOrga->set_bank_address( $credit_bank_info->address1. ' ' .$credit_bank_info->address2 );
								$WDGOrga->save();
								$WDGOrga->submit_transfer_wallet_lemonway();
							}*/
						}

					//Versement vers utilisateur personne physique
					} else {
						$WDGUser = new WDGUser( $investment_item['user'] );
						$WDGUser->register_lemonway();
						$recipient_api_id = $WDGUser->get_api_id();
						if ( $investment_item['roi_amount'] > 0 ) {
							$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGUser->get_lemonway_id(), $investment_item['roi_amount'] );
							/*$credit_bank_info = WDGWPREST_Entity_BankInfo::get( $WDGUser->get_email() );
							if ( $credit_bank_info != FALSE ) {
								$send_notifications = FALSE;
								$WDGUser->save_iban( $credit_bank_info->holdername, $credit_bank_info->iban, $credit_bank_info->bic, $credit_bank_info->address1. ' ' .$credit_bank_info->address2 );
								$WDGUser->transfer_wallet_to_bankaccount( $investment_item['roi_amount'] );
							}*/
						}
					}

					if ( $transfer != FALSE ) {
						WDGROI::insert($investment_item['ID'], $this->id_campaign, $organization_obj->get_api_id(), $recipient_api_id, $this->id, $date_now_formatted, $investment_item['roi_amount'], $transfer->ID, WDGROI::$status_transferred);
						
						if ( $send_notifications ) {
							if ($investment_item['roi_amount'] > 0) {
								$campaign = new ATCF_Campaign( FALSE, $this->id_campaign );
								$campaign_author = $campaign->post_author();
								$author_user = get_user_by( 'ID', $campaign_author );
								$replyto_mail = $author_user->user_email;
								$WDGUser = new WDGUser( $investment_item['user'] );
								$adjustment_message_param = $this->get_adjustment_message( 'investors' );
								if ( $this->get_adjustment_validated() && !empty( $adjustment_message_param ) ) {
									$adjustment_message = $adjustment_message_param;
								}
								$declaration_message = $this->get_message();
								if ( empty( $declaration_message ) ) {
									$declaration_message = "Aucun message";
								}
								NotificationsAPI::roi_transfer_with_royalties( $WDGUser->get_email(), $WDGUser->get_firstname(), $campaign->data->post_title, $adjustment_message, $declaration_message, $replyto_mail );
							}
						}

					} else {
						if ( $investment_item['roi_amount'] == 0 ) {
							WDGROI::insert($investment_item['ID'], $this->id_campaign, $organization_obj->get_api_id(), $recipient_api_id, $this->id, $date_now_formatted, $investment_item['roi_amount'], 0, WDGROI::$status_transferred);
							if ( $send_notifications ) {
								$campaign = new ATCF_Campaign( FALSE, $this->id_campaign );
								$campaign_author = $campaign->post_author();
								$author_user = get_user_by( 'ID', $campaign_author );
								$replyto_mail = $author_user->user_email;
								$WDGUser = new WDGUser( $investment_item['user'] );
								$adjustment_message_param = $this->get_adjustment_message( 'investors' );
								if ( $this->get_adjustment_validated() && !empty( $adjustment_message_param ) ) {
									$adjustment_message = $adjustment_message_param;
								}
								$declaration_message = $this->get_message();
								if ( empty( $declaration_message ) ) {
									$declaration_message = "Aucun message";
								}
								NotificationsAPI::roi_transfer_without_royalties( $WDGUser->get_email(), $WDGUser->get_firstname(), $campaign->data->post_title, $adjustment_message, $declaration_message, $replyto_mail );
							}
						} else {
							WDGROI::insert($investment_item['ID'], $this->id_campaign, $organization_obj->get_api_id(), $recipient_api_id, $this->id, $date_now_formatted, $investment_item['roi_amount'], 0, WDGROI::$status_error);
						}
					}
					
					// Nombre arbitraire de versements avant de faire un retour au site
					$max_transfer_per_try = 10;
					if ( $count_done >= $max_transfer_per_try ) {
						break;
					}
				}
				
			}
			
			// En retour, on veut le pourcentage d'avancement
			$buffer = $count / count( $investments_list ) * 100;
			
			// Si on a terminé, on finalise la déclaration
			if ( $buffer == 100 ) {
				if ( $transfer_remaining_amount ) {
					// Mise à jour de la somme des reliquats précédents qui ont été reversés
					if ( $previous_remaining_amount > $this->remaining_amount ) {
						$this->transfered_previous_remaining_amount = $previous_remaining_amount - $this->remaining_amount;
					}
				}

				if ($total_fees > 0) {
					LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), "SC", $total_fees);
				}
				$wdguser_author = new WDGUser( $campaign->data->post_author );
				if ( $this->get_amount_with_adjustment() > 0 ) {
					NotificationsAPI::declaration_done_with_turnover( $organization_obj->get_email(), $wdguser_author->get_firstname(), $this->get_month_list_str(), $this->get_amount_with_adjustment() );
				} else {
					NotificationsAPI::declaration_done_without_turnover( $organization_obj->get_email(), $wdguser_author->get_firstname(), $this->get_month_list_str() );
				}
				$this->status = WDGROIDeclaration::$status_finished;
				$this->date_transfer = $date_now_formatted;
			}
			
			// On met à jour de toute façon pour mettre à jour le reliquat
			$this->update();
		}
		return $buffer;
	}
	
	/**
	 * Répare un versement qui n'a pas eu lieu vers un utilisateur
	 */
	public function redo_transfers() {
		$campaign = new ATCF_Campaign( FALSE, $this->id_campaign );
		$current_organization = $campaign->get_organization();
		if (!empty($current_organization)) {
			$organization_obj = new WDGOrganization($current_organization->wpref);
		}
		
		$roi_list = $this->get_rois();
		foreach ( $roi_list as $roi_item ) {
			if ( $roi_item->amount > 0 && $roi_item->id_transfer == 0 && ( $roi_item->status == WDGROI::$status_transferred || $roi_item->status == WDGROI::$status_error ) ) {
				$ROI = new WDGROI( $roi_item->id );

				//Gestion versement vers organisation
				if (WDGOrganization::is_user_organization( $ROI->id_user )) {
					$WDGOrga = new WDGOrganization( $ROI->id_user );
					$WDGOrga->register_lemonway();
					$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGOrga->get_lemonway_id(), $ROI->amount );

				//Versement vers utilisateur personne physique
				} else {
					$WDGUser = new WDGUser( $ROI->id_user );
					$WDGUser->register_lemonway();
					$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGUser->get_lemonway_id(), $ROI->amount );
				}

				if ( $transfer != FALSE ) {
					$ROI->status = WDGROI::$status_transferred;
				}
				$ROI->id_transfer = $transfer->ID;
				$ROI->update();
			}
		}
		
	}
	
	/**
	 * Si la dÃ©claration Ã©tait en attente de virement, on valide que le virement a Ã©tÃ© reÃ§u
	 */
	public function mark_transfer_received() {
		if ( $this->status == WDGROIDeclaration::$status_waiting_transfer ) {
			$this->status = WDGROIDeclaration::$status_transfer;
			$this->update();
			$this->save();
		}
	}
	
	/**
	 * DÃ©termine le nom du fichier d'attestation qui va Ãªtre crÃ©Ã©
	 * @return string
	 */
	private function get_payment_certificate_filename() {
		$buffer = 'roi-declaration-' .$this->id_campaign. '-' .$this->id. '.pdf';
		return $buffer;
	}
	
	/**
	 * CrÃ©e le fichier pdf d'attestation
	 * @param boolean $force Si $force est Ã  true, on crÃ©e mÃªme si le fichier existe dÃ©jÃ 
	 */
	public function make_payment_certificate( $force = false ) {
		$filename = $this->get_payment_certificate_filename();
		$filepath = __DIR__ . '/../../files/certificate-roi-payment/' . $filename;
		if ( !$force && file_exists( $filepath ) ) {
			return;
		}
		
		$date_due = new DateTime( $this->date_due );
		$certificate_date = $date_due->format( 'd/m/Y' );
		
		$campaign = new ATCF_Campaign( FALSE, $this->id_campaign );
		$current_organization = $campaign->get_organization();
		if ( !empty( $current_organization ) ) {
			$organization_obj = new WDGOrganization( $current_organization->wpref );
		}
		$project_roi_percent = $campaign->roi_percent();
		$project_amount_collected = $campaign->current_amount( false );
		$date_first_payment = new DateTime( $campaign->first_payment_date() );
		$project_roi_start_date = $date_first_payment->format( "d/m/Y" );
		$project_investors_list = array();
		$investments_list = $campaign->roi_payments_data( $this );
		foreach ($investments_list as $investment_item) {
			$user_data = get_userdata($investment_item['user']);
			array_push( $project_investors_list, array( "firstname" => $user_data->first_name, "lastname" => $user_data->last_name, "amount" => $investment_item['amount'], "roi_amount" => $investment_item['roi_amount'] ) );
		}
		$project_roi_nb_years = $campaign->funding_duration_str();
		$organization_name = $organization_obj->get_name();
		$organization_address = $organization_obj->get_address();
		$organization_postalcode = $organization_obj->get_postal_code();
		$organization_city = $organization_obj->get_city();
		$declaration_date = $this->get_formatted_date();
		$declaration_date_object = new DateTime( $this->date_due );
		$declaration_month_num = $declaration_date_object->format( 'n' );
		$declaration_year = $declaration_date_object->format( 'Y' );
		$declaration_trimester = 4;
		switch ( $declaration_month_num ) {
			case 1:
				$declaration_year--;
				break;
			case 4:
			case 5:
			case 6:
				$declaration_trimester = 1;
				break;
			case 7:
			case 8:
			case 9:
				$declaration_trimester = 2;
				break;
			case 10:
			case 11:
			case 12:
				$declaration_trimester = 3;
				break;
		}
		$declaration_declared_turnover = $this->get_turnover_total();
		$declaration_amount = $this->amount;
		$declaration_percent_commission = $this->percent_commission;
		$declaration_amount_commission = $this->get_commission_to_pay();
		$declaration_amount_and_commission = $this->get_amount_with_commission();
		$declaration_adjustment_value = $this->get_adjustment_value();
		$declaration_remaining_amount_transfered = $this->transfered_previous_remaining_amount;
		
		
		require __DIR__. '/../control/templates/pdf/certificate-roi-payment.php';
		$html_content = WDG_Template_PDF_Certificate_ROI_Payment::get(
			$certificate_date,
			$project_roi_percent,
			$project_amount_collected,
			$project_roi_start_date,
			$project_investors_list,
			$project_roi_nb_years,
			$organization_name,
			$organization_address,
			$organization_postalcode,
			$organization_city,
			$declaration_date,
			$declaration_trimester,
			$declaration_year,
			$declaration_declared_turnover,
			$declaration_amount,
			$declaration_percent_commission,
			$declaration_amount_commission,
			$declaration_amount_and_commission,
			$declaration_adjustment_value,
			$declaration_remaining_amount_transfered
		);
		
		$html2pdf = new HTML2PDF( 'P', 'A4', 'fr', true, 'UTF-8', array(12, 5, 15, 8) );
		$html2pdf->WriteHTML( urldecode( $html_content ) );
		$html2pdf->Output( $filepath, 'F' );
	}
	
	/**
	 * DÃ©termine l'URL oÃ¹ le fichier d'attestation peut Ãªtre tÃ©lÃ©chargÃ©
	 * @return string
	 */
	public function get_payment_certificate_url() {
		$buffer = home_url() . '/wp-content/plugins/appthemer-crowdfunding/files/certificate-roi-payment/';
		$buffer .= $this->get_payment_certificate_filename();
		return $buffer;
	}
	
	/**
	 * Renvoie true si la dÃ©claration de ROI peut Ãªtre payÃ©e via virement
	 * @return boolean
	 */
	public function can_pay_with_wire() {
		return ($this->get_amount_with_commission() >= WDGROIDeclaration::$min_amount_for_wire_payment);
	}
	
	/**
	 * Renvoie la liste des mois concernÃ©s par une dÃ©claration
	 * @return array
	 */
	public function get_month_list() {
		$buffer = array();
		
		$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		$campaign = atcf_get_campaign( $this->id_campaign );
		$nb_fields = $campaign->get_turnover_per_declaration();

		$date_due = new DateTime( $this->date_due );
		$declaration_year = $date_due->format( 'Y' );
		$date_due->sub( new DateInterval( 'P'.$nb_fields.'M' ) );
		// Si l'annÃ©e de dÃ©claration est diffÃ©rente de la premiÃ¨re date de dÃ©claration,
			// on recule d'une annÃ©e
		if ( $declaration_year > $date_due->format( 'Y' ) ) {
			$declaration_year--;
		}
		
		for ($i = 0; $i < $nb_fields; $i++) {
			// Si on est en janvier, et que ce n'est pas la premiÃ¨re dÃ©claration,
				// on avance d'une annÃ©e
			if ( $i > 0 && $date_due->format( 'n' ) == 1 ) {
				$declaration_year++;
			}
			array_push( $buffer, ucfirst( __( $months[ $date_due->format('m') - 1 ] ) ) . ' ' . $declaration_year );
			$date_due->add( new DateInterval( 'P1M' ) );
		}

		return $buffer;
	}
	
	public function get_month_list_str() {
		$buffer = '';
		$month_list = $this->get_month_list();
		$count_months = count( $month_list );
		for ( $i = 0; $i < $count_months; $i++ ) {
			$buffer .= strtolower( $month_list[ $i ] );
			if ( $i < $count_months - 2 ) {
				$buffer .= ', ';
			}
			if ( $i == $count_months - 2 ) {
				$buffer .= ' et ';
			}
		}
		return $buffer;
	}
	
	/**
	 * Enregistre les donnÃ©es d'ajustement
	 * @param boolean $is_validated
	 * @param boolean $is_needed
	 * @param number $turnover_difference
	 * @param number $value
	 * @param string $message_to_author
	 * @param string $message_to_investors
	 */
	public function set_adjustment( $is_validated, $is_needed, $turnover_difference, $value, $message_to_author, $message_to_investors ) {
		$buffer = array(
			'validated'			=> ( $is_validated ) ? 1 : 0,
			'needed'			=> ( $is_needed ) ? 1 : 0,
			'turnover_difference' => $turnover_difference,
			'value'				=> $value,
			'msg_to_author'		=> $message_to_author,
			'msg_to_investors'	=> $message_to_investors
		);
		$this->adjustment = json_encode( $buffer );
		$this->update();
		$this->save();
	}
	
	/**
	 * DÃ©termine le statut validÃ© ou non
	 * @return boolean
	 */
	public function get_adjustment_validated() {
		$buffer = false;
		if ( !empty( $this->adjustment ) ) {
			$temp = json_decode( $this->adjustment );
			$buffer = ( $temp->validated == 1 );
		}
		return $buffer;
	}
	
	/**
	 * DÃ©termine si l'ajustement est obligatoire ou non
	 * @return boolean
	 */
	public function get_adjustment_needed() {
		$buffer = false;
		if ( !empty( $this->adjustment ) ) {
			$temp = json_decode( $this->adjustment );
			$buffer = ( isset( $temp->needed ) && $temp->needed == 1 );
		}
		return $buffer;
	}
	
	/**
	 * DÃ©termine la valeur de l'ajustement
	 * @return number
	 */
	public function get_adjustment_value() {
		$buffer = 0;
		if ( !empty( $this->adjustment ) ) {
			$temp = json_decode( $this->adjustment );
			$temp_value = $temp->value;
			if ( is_numeric( $temp_value ) ) {
				$buffer = $temp_value;
			}
		}
		return $buffer;
	}
	
	/**
	 * DÃ©termine la valeur de la diffÃ©rence de chiffre d'affaires
	 * @return number
	 */
	public function get_adjustment_turnover_difference() {
		$buffer = 0;
		if ( !empty( $this->adjustment ) ) {
			$temp = json_decode( $this->adjustment );
			$temp_value = $temp->turnover_difference;
			if ( is_numeric( $temp_value ) ) {
				$buffer = $temp_value;
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne le message enregistrÃ© pour les investisseurs pour le porteur de projet
	 * @param string $type
	 * @return string
	 */
	public function get_adjustment_message( $type ) {
		$buffer = '';
		if ( !empty( $this->adjustment ) ) {
			$temp = json_decode( $this->adjustment );
			$var_type = 'msg_to_' .$type;
			$buffer = $temp->$var_type;
		}
		return $buffer;
	}
	
	/**
	 * Enregistre le reliquat (utile surtout pour les anciennes dÃ©clarations Ã  mettre Ã  jour)
	 */
	public function save_remaining_amount() {
		if ( $this->status == WDGROIDeclaration::$status_finished ) {
			$campaign = new ATCF_Campaign( FALSE, $this->id_campaign );
			$investments_list = $campaign->roi_payments_data( $this );
			$remaining_amount = $this->get_amount_with_adjustment();
			foreach ($investments_list as $investment_item) {
				$remaining_amount -= $investment_item['roi_fees'];
				$remaining_amount -= $investment_item['roi_amount'];
			}
			$this->remaining_amount = $remaining_amount;
			$this->update();
			$this->save();
		}
	}
	
	/**
	 * Retourne la liste des dÃ©clarations qui prÃ©cÃ¨dent celles-ci
	 */
	public function get_previous_declarations() {
		$buffer = array();
		
		$declarations = WDGROIDeclaration::get_list_by_campaign_id( $this->id_campaign );
		foreach ( $declarations as $declaration_item ) {
			$declaration_date_due = new DateTime( $declaration_item->date_due );
			$this_date_due = new DateTime( $this->date_due );
			if ( $declaration_date_due < $this_date_due ) {
				array_push( $buffer, $declaration_item );
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne la valeur des reliquats qui n'ont pas été versés aux investisseurs sur les déclarations précédentes
	 */
	public function get_previous_remaining_amount() {
		$buffer = 0;
		
		$previous_declarations = $this->get_previous_declarations();
		foreach ( $previous_declarations as $declaration_item ) {
			$buffer += $declaration_item->remaining_amount;
			$buffer -= $declaration_item->transfered_previous_remaining_amount;
		}
		
		return $buffer;
	}
	
	/**
	 * Récupère la liste de tous les ROIs liés à cette déclaration
	 */
	private $roi_list;
	public function get_rois() {
		if ( !isset( $this->roi_list ) ) {
			$this->roi_list = WDGWPREST_Entity_Declaration::get_roi_list( $this->id );
		}
		return $this->roi_list;
	}
	
	/**
	 * Récupère le ROI qui concerne cette déclaration et un id d'investissement
	 * @param int $investment_id
	 */
	public function get_roi_by_investment( $investment_id ) {
		$buffer = array();
		
		$roi_list = $this->get_rois();
		foreach ( $roi_list as $roi_item ) {
			if ( $roi_item->id_investment == $investment_id ) {
				$ROI = new WDGROI( $roi_item->id, FALSE, $roi_item );
				array_push($buffer, $ROI);
			}
		}
		
		return $buffer;
	}
	
	
/*******************************************************************************
 * REQUETES STATIQUES
 ******************************************************************************/
	/**
	 * Mise Ã  jour base de donnÃ©es
	 */
	public static function upgrade_db() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$table_name = $wpdb->prefix . WDGROIDeclaration::$table_name;
		$sql = "CREATE TABLE " .$table_name. " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			id_campaign mediumint(9) NOT NULL,
			date_due date DEFAULT '0000-00-00',
			date_paid date DEFAULT '0000-00-00',
			date_transfer date DEFAULT '0000-00-00',
			amount float,
			remaining_amount float,
			percent_commission float,
			status tinytext,
			mean_payment tinytext,
			payment_token tinytext,
			file_list text,
			turnover text,
			message text,
			adjustment text,
			transfered_previous_remaining_amount float,
			on_api tinyint DEFAULT 0,
			UNIQUE KEY id (id)
		) $charset_collate;";
		$result = dbDelta( $sql );
	}
	
	/**
	 * Ajout d'une nouvelle dÃ©claration
	 */
	public static function insert( $id_campaign, $date_due ) {
		$declaration = new WDGROIDeclaration();
		$declaration->id_campaign = $id_campaign;
		$declaration->date_due = $date_due;
		$declaration->status = WDGROIDeclaration::$status_declaration;
		WDGWPREST_Entity_Declaration::create( $declaration );
	}
	
	/**
	 * Liste des dÃ©clarations ROI pour un projet
	 */
	public static function get_list_by_campaign_id( $idwp_campaign, $status = '' ) {
		$buffer = array();
		
		$campaign = new ATCF_Campaign( $idwp_campaign );
		$declarations = WDGWPREST_Entity_Project::get_declarations( $campaign->get_api_id() );
		if ( $declarations ) {
			foreach ( $declarations as $declaration_item ) {
				$add = TRUE;

				if ( !empty( $status ) ) {
					if ( $status == WDGROIDeclaration::$status_declaration ) {
						if ( $declaration_item->status != WDGROIDeclaration::$status_declaration && !empty( $declaration_item->status ) ) {
							$add = FALSE;
						}

					} else {
						if ( $declaration_item->status != $status ) {
							$add = FALSE;
						}
					}
				}

				if ( $add ) {
					$ROIdeclaration = new WDGROIDeclaration( $declaration_item->id, false, $declaration_item );
					array_push($buffer, $ROIdeclaration);
				}
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne une dÃ©claration ROI par son token de paiement
	 * @param string $token
	 * @return WDGROIDeclaration
	 */
	public static function get_by_payment_token( $token ) {
		$buffer = FALSE;
		
		$declaration = WDGWPREST_Entity_Declaration::get_by_payment_token( $token );
		if ( $declaration ) {
			$buffer = new WDGROIDeclaration( $declaration->id );
		}
		
		return $buffer;
	}
	
	/**
	 * TransfÃ¨re les donnÃ©es de la dÃ©claration vers l'API
	 */
	public static function transfer_to_api() {
		global $wpdb;
		$campaign_wpref_to_api = array();
		
		$query = "SELECT id, on_api FROM " .$wpdb->prefix.WDGROIDeclaration::$table_name;
		$declaration_list = $wpdb->get_results( $query );
		foreach ( $declaration_list as $declaration_item ) {
			if ( !$declaration_item->on_api ) {
				$declaration = new WDGROIDeclaration( $declaration_item->id, TRUE );
				if ( empty( $campaign_wpref_to_api[ $declaration->id_campaign ] ) ) {
					$campaign = new ATCF_Campaign( $declaration->id_campaign );
					$campaign_wpref_to_api[ $declaration->id_campaign ] = $campaign->get_api_id();
				}
				$temp_campaign_id = $declaration->id_campaign;
				$declaration->id_campaign = $campaign_wpref_to_api[ $declaration->id_campaign ];
				$created_declaration = WDGWPREST_Entity_Declaration::create( $declaration );
				WDGROI::transfer_to_api( $declaration_item->id, $created_declaration->id );
				$declaration->on_api = true;
				$declaration->id_campaign = $temp_campaign_id;
				$declaration->save( TRUE );
			}
		}
	}
}
