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
	public static $status_failed = 'failed';
	
	public static $mean_payment_card = 'card';
	public static $mean_payment_wire = 'wire';
	public static $mean_payment_mandate = 'mandate';
	
	public static $min_amount_for_wire_payment = 1000;
	public static $tax_without_exemption = 30;
	public static $tax_with_exemption = 17.2;
	public static $default_transfer_delay = 10;
	
	public $id;
	public $id_campaign;
	public $date_due;
	public $date_paid;
	public $date_transfer;
	public $amount;
	public $remaining_amount;
	public $percent_commission_without_tax;
	public $percent_commission;
	public $status;
	public $mean_payment;
	public $payment_token;
	public $file_list;
	public $turnover;
	public $message;
	public $adjustment;
	public $adjustments;
	public $transfered_previous_remaining_amount;
	
	public $employees_number;
	public $other_fundings;
	public $transfer_delay;
	public $declared_by;
	
	public $on_api;
	private $campaign_object;

	public $api_data_adjustments;
	public $api_data_rois;
	public $api_data_files;
	
	
	public function __construct( $declaration_id = FALSE, $data = FALSE ) {
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
				$this->percent_commission_without_tax = $collection_item->percent_commission_without_tax;
				$this->percent_commission = $collection_item->percent_commission;
				$this->status = $collection_item->status;
				$this->mean_payment = $collection_item->mean_payment;
				if ( !empty( $collection_item->payment_token ) ) {
					$this->payment_token = $collection_item->payment_token;
				}
				$this->file_list = $collection_item->file_list;
				$this->turnover = $collection_item->turnover;
				$this->message = $collection_item->message;
				$this->adjustment = $collection_item->adjustment;
				$this->transfered_previous_remaining_amount = $collection_item->transfered_previous_remaining_amount;
				$this->employees_number = $collection_item->employees_number;
				$this->other_fundings = $collection_item->other_fundings;
				$this->transfer_delay = $collection_item->transfer_delay;
				if ( !empty( $collection_item->declared_by ) ) {
					$this->declared_by = $collection_item->declared_by;
				}
				$this->on_api = TRUE;
				$this->api_data_adjustments = isset( self::$collection_by_id[ $declaration_id ] ) ? $collection_item->api_data_adjustments : $collection_item->adjustments;
				$this->api_data_rois = isset( self::$collection_by_id[ $declaration_id ] ) ? $collection_item->api_data_rois : $collection_item->rois;
				$this->api_data_files = isset( self::$collection_by_id[ $declaration_id ] ) ? $collection_item->api_data_files : $collection_item->files;

			} else {
				// Récupération depuis l'API
				$declaration_api_item = WDGWPREST_Entity_Declaration::get( $declaration_id );
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
					$this->percent_commission_without_tax = $declaration_api_item->percent_commission_without_tax;
					if ( !is_numeric( $this->percent_commission_without_tax ) ) {
						$this->percent_commission_without_tax = 0;
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
					$this->transfer_delay = $declaration_api_item->transfer_delay;
					$this->declared_by = $declaration_api_item->declared_by;

					$this->on_api = TRUE;
					$this->api_data_adjustments = $declaration_api_item->adjustments;
					$this->api_data_rois = $declaration_api_item->rois;
					$this->api_data_files = $declaration_api_item->files;

					// Les déclarations sans statut doivent passer en statut "Déclaration"
					if ( empty( $this->status ) || $this->status == null ) {
						$this->status = WDGROIDeclaration::$status_declaration;
					}

					// Les déclarations à zero pour les projets en mode "paiement" doivent être marquées comme terminées
					if ( $this->status == WDGROIDeclaration::$status_payment && !empty( $this->turnover ) && $this->get_amount_with_commission() == 0 ) {
						$this->status = WDGROIDeclaration::$status_transfer;
						$this->save();
					}

				}

			}

			if ( !isset( self::$collection_by_id[ $declaration_id ] ) ) {
				self::$collection_by_id[ $declaration_id ] = $this;
			}
		}
	}
	
	/**
	 * Sauvegarde les donnÃ©es dans l'API
	 */
	public function update() {
		WDGWPREST_Entity_Declaration::update( $this );
		self::$collection_by_id[ $this->id ] = $this;
	}
	
	/**
	 * Sauvegarde dans l'API
	 * @deprecated
	 * @return integer
	 */
	public function save() {
		$this->update();
	}

	private function get_campaign_object() {
		if ( !isset( $this->campaign_object ) ) {
			$this->campaign_object = new ATCF_Campaign( FALSE, $this->id_campaign );
		}
		return $this->campaign_object;
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
	
	public function get_amount_royalties() {
		return $this->amount;
	}
	
	/**
	 * Retourne le montant additionnÃ© l'ajustement
	 * @return number
	 */
	public function get_amount_with_adjustment() {
		return max( 0, $this->get_amount_royalties() + $this->get_adjustment_value() );
	}
	
	/**
	 * Retourne le montant additionnÃ© avec la commission
	 * @return number
	 */
	public function get_amount_with_commission() {
		return ( $this->get_amount_with_adjustment() + $this->get_commission_to_pay() );
	}

	/**
	 * Retourne le pourcentage de commission associée à la déclaration
	 */
	public function get_percent_commission_without_tax() {
		if ( !empty( $this->percent_commission_without_tax ) ) {
			return $this->percent_commission_without_tax;

		} else if ( !empty( $this->percent_commission ) ) {
			return $this->percent_commission / 1.2;
			
		} else {
			$campaign = $this->get_campaign_object();
			return $campaign->get_costs_to_organization() / 1.2;
		}
	}
	
	/**
	 * Retourne la commission TTC que doit payer le porteur de projet au moment de reverser les fonds
	 * @return number
	 */
	public function get_commission_to_pay() {
		$buffer = 0;
		
		// Ancienne méthode avec erreur : si percent_commission est défini, on calcule directement en TTC
		if ( !empty( $this->percent_commission ) && $this->status == WDGROIDeclaration::$status_finished ) {
			$cost_with_tax = $this->percent_commission;
			$buffer = round( ( $this->get_amount_with_adjustment() * $cost_with_tax / 100 ) * 100 ) / 100;
		
			// Si il y a un coût minimal par déclaration
			$campaign = $this->get_campaign_object();
			$minimum_costs = $campaign->get_minimum_costs_to_organization();
			if ( $minimum_costs > 0 ) {
				$buffer = max( $buffer, $minimum_costs );
			}
			
		// Nouvelle méthode : on calcule à partir du HT
		} else {
			$buffer = round( ( $this->get_commission_to_pay_without_tax() * 1.2) * 100 ) / 100;
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne la commission HT que doit payer le porteur de projet au moment de reverser les fonds
	 * @return number
	 */
	public function get_commission_to_pay_without_tax( $force_new_method = FALSE ) {
		// Ancienne méthode avec erreur : si percent_commission est défini, on calcule à partir du TTC
		if ( !$force_new_method && !empty( $this->percent_commission ) && $this->status == WDGROIDeclaration::$status_finished ) {
			$buffer = round( $this->get_commission_to_pay() / 1.2, 2 );
			
		// Nouvelle méthode : on calcule directement depuis les données HT
		} else {
			$cost_without_tax = $this->get_percent_commission_without_tax();
			$buffer = round( ( $this->get_amount_with_adjustment() * $cost_without_tax / 100 ) * 100 ) / 100;

			// Si il y a un coût minimal par déclaration
			$campaign = $this->get_campaign_object();
			$minimum_costs = $campaign->get_minimum_costs_to_organization() / 1.2;
			if ( $minimum_costs > 0 ) {
				$buffer = max( $buffer, $minimum_costs );
			}
		}
		
		return $buffer;
	}
	
	public function get_commission_tax() {
		return $this->get_commission_to_pay() - $this->get_commission_to_pay_without_tax();
	}

	/**
	 * Définir si il y a des plus-values
	 */
	public function has_paid_gain() {
		// Parcours des rois
		$roi_list = $this->get_rois();
		foreach ( $roi_list as $roi_item ) {
			if ( $roi_item->amount_taxed_in_cents > 0 ) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Traite un fichier uploadé qui doit être ajouté à la liste
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
		
		NotificationsEmails::turnover_declaration_adjustment_file_sent( $this->id );
		$this->update();
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

	public function get_bill_file() {
		if ( !isset( $this->api_data_files ) ) {
			return WDGWPREST_Entity_Declaration::get_bill_file( $this->declaration_id );
		} else {
			return $this->api_data_files;
		}
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
	
	private $estimated_turnover;
	public function get_estimated_turnover() {
		if ( !isset( $this->estimated_turnover ) ) {
			$campaign = $this->get_campaign_object();
			$declaration_date_due = new DateTime( $this->date_due );
			$quarter_percent_list = array( 10, 20, 30, 40 );
			$nb_quarter = 0;
			$estimated_turnover = $campaign->estimated_turnover();
			$nb_year = array_key_first( $estimated_turnover );

			// Parcours des déclarations de royalties pour savoir à quelle année et quel trimestre on est dans les échéances
			// TODO : les trier dans l'ordre par sécurité
			$existing_roi_declarations = $campaign->get_roi_declarations();
			foreach ( $existing_roi_declarations as $declaration_object ) {
				$date_declaration = new DateTime( $declaration_object[ 'date_due' ] );

				if ( $date_declaration->format( 'm' ) == $declaration_date_due->format( 'm' ) && $date_declaration->format( 'Y' ) == $declaration_date_due->format( 'Y' ) ) {
					break;

				} else {
					$nb_quarter++;
					if ( $nb_quarter >= $campaign->get_declararations_count_per_year() ) {
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
			$this->estimated_turnover = $amount_estimation_year * $percent_estimation / 100;
		}
		return $this->estimated_turnover;
	}
	
	public function get_estimated_amount() {
		$campaign = $this->get_campaign_object();
		
		$estimated_turnover = $this->get_estimated_turnover();
		$amount_royalties = $estimated_turnover * $campaign->roi_percent_remaining() / 100;
		$amount_with_adjustment = $amount_royalties + $this->get_adjustment_value();
		$buffer = $amount_with_adjustment;
		
		$cost = $campaign->get_costs_to_organization();
		if ( $cost > 0 ) {
			$buffer += ( round( ( $amount_with_adjustment * $cost / 100 ) * 100) / 100 );
		}
		
		// Si il y a un coût minimal par déclaration
		$minimum_costs = $campaign->get_minimum_costs_to_organization();
		if ( $minimum_costs > 0 ) {
			$buffer = max( $buffer, $minimum_costs );
		}
		
		return $buffer;
	}
	
	public function get_message() {
		return html_entity_decode( nl2br( $this->message, ENT_HTML5 ), ENT_QUOTES | ENT_HTML401 );
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

	public function get_transfer_delay() {
		if ( empty( $this->transfer_delay ) ) {
			return self::$default_transfer_delay;
		}
		return nl2br( $this->transfer_delay, ENT_HTML5 );
	}
	public function set_transfer_delay( $transfer_delay ) {
		if ( is_numeric( $transfer_delay ) ) {
			$this->transfer_delay = htmlentities( $transfer_delay );
		} else {
			$this->transfer_delay = self::$default_transfer_delay;
		}
	}

	/**
	 * Retourne la date à laquelle on fera le versement auto
	 */
	public function get_transfer_date() {
		$date_of_royalties_transfer = new DateTime();
		$date_of_royalties_transfer->setTime( 15, 30, 0 );
		$transfer_delay = $this->get_transfer_delay();
		if ( empty( $transfer_delay ) ) {
			$transfer_delay = WDGROIDeclaration::$default_transfer_delay;
		}

		$date_of_royalties_transfer->add( new DateInterval( 'P' .$transfer_delay. 'D' ) );
		// Si samedi, on fera un jour plus tard
		if ( $date_of_royalties_transfer->format( 'N' ) == 6 ) {
			$date_of_royalties_transfer->add( new DateInterval( 'P1D' ) );
		}
		// Si dimanche, on fera un jour plus tard
		if ( $date_of_royalties_transfer->format( 'N' ) == 7 ) {
			$date_of_royalties_transfer->add( new DateInterval( 'P1D' ) );
		}
		// Si lundi, on fera un jour plus tard
		if ( $date_of_royalties_transfer->format( 'N' ) == 1 ) {
			$date_of_royalties_transfer->add( new DateInterval( 'P1D' ) );
		}

		return $date_of_royalties_transfer;
	}
	
	public function get_declared_by() {
		return json_decode( $this->declared_by );
	}
	public function set_declared_by( $declared_by_apiid, $declared_by_name, $declared_by_email, $declared_by_status ) {
		$declared_by_data = array(
			'apiid'		=> $declared_by_apiid,
			'name'		=> $declared_by_name,
			'email'		=> $declared_by_email,
			'status'	=> $declared_by_status
		);
		$this->declared_by = json_encode( $declared_by_data );
	}
	
	/**
	 * Transfert de tous les ROIs qui ont été préparés dans la fonction d'initialisation
	 */
	public function transfer_pending_rois() {
		$campaign = $this->get_campaign_object();
		$campaign_organization = $campaign->get_organization();
		$investment_contracts = WDGInvestmentContract::get_list( $campaign->ID );
		if ( !empty( $campaign_organization ) ) {
			$WDGOrganization_campaign = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
		}
		
		// Nombre arbitraire de versements avant de faire un retour au site
		$max_transfer_per_try = 20;
		// On différencie $count et $count_done
		// Le premier sert à compter le nombre total (pour donner un pourcentage en retour)
		// Le second sert à déterminer quand on s'arrête lors de ce passage
		$count = 0;
		$count_done = 0;

		$date_now = new DateTime();

		$roi_list = $this->get_rois();
		foreach ( $roi_list as $roi_item ) {
			$count++;
			if ( $roi_item->status == WDGROI::$status_waiting_transfer ) {
				$count_done++;
				$ROI = new WDGROI( $roi_item->id );

				if ( $ROI->id_user == 0 ) {
					continue;
				}

				$transfer = FALSE;
				$recipient_name = '';
				$recipient_email = '';
				$wdguser_wpref = 0;

				//Gestion versement vers organisation
				if ( $ROI->recipient_type == 'orga' ) {
					$WDGOrga = WDGOrganization::get_by_api_id( $ROI->id_user );
					$wdguser_wpref = $WDGOrga->get_wpref();
					$WDGOrga->register_lemonway();
					$recipient_name = $WDGOrga->get_name();
					$recipient_email = $WDGOrga->get_email();
					if ( $ROI->amount > 0 ) {
						if ( $WDGOrga->is_registered_lemonway_wallet() ) {
							$transfer = LemonwayLib::ask_transfer_funds( $WDGOrganization_campaign->get_royalties_lemonway_id(), $WDGOrga->get_lemonway_id(), $ROI->amount );
							$status = WDGROI::$status_transferred;

							// Enregistrement des données de taxe
							if ( $ROI->amount_taxed_in_cents > 0 ) {
								WDGROITax::insert( $ROI->id, $ROI->id_user, 'orga', $date_now->format( 'Y-m-d' ), $ROI->amount_taxed_in_cents, 0, 0, $WDGOrga->get_country(), '0' );
							}
	
						} else {
							$status = WDGROI::$status_waiting_authentication;
						}
						$this->update_investment_contract_amount_received( $investment_contracts, $ROI->id_investment, $ROI->amount );

					} else {
						$transfer = TRUE;
						$status = WDGROI::$status_transferred;
					}

				//Versement vers utilisateur personne physique
				} else {
					$WDGUser = WDGUser::get_by_api_id( $ROI->id_user );
					$wdguser_wpref = $WDGUser->get_wpref();
					$WDGUser->register_lemonway();
					$recipient_name = $WDGUser->get_firstname();
					$recipient_email = $WDGUser->get_email();
					if ( $ROI->amount > 0 ) {
						if ( $WDGUser->is_lemonway_registered() ) {
							// Transfert sur le wallet de séquestre d'impots de l'organisation
							$amount_tax_in_cents = 0;
							if ( $ROI->amount_taxed_in_cents > 0 ) {
								$amount_tax_in_cents = $WDGUser->get_tax_amount_in_cents_round( $ROI->amount_taxed_in_cents );
								if ( $amount_tax_in_cents > 0 ) {
									$WDGOrganization_campaign->check_register_tax_lemonway_wallet();
									LemonwayLib::ask_transfer_funds( $WDGOrganization_campaign->get_royalties_lemonway_id(), $WDGOrganization_campaign->get_tax_lemonway_id(), $amount_tax_in_cents / 100 );
									$percent_tax = $WDGUser->get_tax_percent();
									WDGROITax::insert( $ROI->id, $ROI->id_user, 'user', $date_now->format( 'Y-m-d' ), $ROI->amount_taxed_in_cents, $amount_tax_in_cents, $percent_tax, $WDGUser->get_tax_country(), $WDGUser->has_tax_exemption_for_year( $date_now->format( 'Y' ) ) );
									WDGQueue::add_tax_monthly_summary( $this->id );
								}
							}

							$transfer = LemonwayLib::ask_transfer_funds( $WDGOrganization_campaign->get_royalties_lemonway_id(), $WDGUser->get_lemonway_id(), $ROI->amount - $amount_tax_in_cents / 100 );
							$status = WDGROI::$status_transferred;

						} else {
							$status = WDGROI::$status_waiting_authentication;
						}
						$this->update_investment_contract_amount_received( $investment_contracts, $ROI->id_investment, $ROI->amount );

					} else {
						$transfer = TRUE;
						$status = WDGROI::$status_transferred;
					}
				}

				if ( $transfer != FALSE ) {
					$ROI->date_transfer = $date_now->format( 'Y-m-d' );
					$ROI->status = $status;
					if ( $transfer !== TRUE ) {
						$ROI->id_transfer = $transfer->ID;
					}
				} else {
					if ( $status == WDGROI::$status_waiting_authentication ) {
						$ROI->status = WDGROI::$status_waiting_authentication;
					} else {
						$ROI->status = WDGROI::$status_error;
					}
				}
				$ROI->update();

				
				$cancel_notification = FALSE;
				if ( $WDGUser ) {
					$recipient_notification = $WDGUser->get_royalties_notifications();
					if ( $recipient_notification == 'none' ) {
						$cancel_notification = TRUE;
					} elseif ( $recipient_notification == 'positive' && $ROI->amount == 0 ) {
						$cancel_notification = TRUE;
					}
				}

				if ( !$cancel_notification ) {
					if ( !empty( $wdguser_wpref ) ) {
						WDGQueue::add_notification_royalties( $wdguser_wpref );
					}
					
					$declaration_message = $this->get_message();
					if ( !empty( $declaration_message ) ) {
						$campaign_author = $campaign->post_author();
						$author_user = get_user_by( 'ID', $campaign_author );
						$replyto_mail = $author_user->user_email;
						$declaration_message_decoded = $declaration_message;
						NotificationsAPI::roi_transfer_message( $recipient_email, $recipient_name, $campaign->data->post_title, $declaration_message_decoded, $replyto_mail );
					}
				}

				if ( $count_done >= $max_transfer_per_try ) {
					break;
				}
			}
		}

		WDGWPRESTLib::unset_cache( 'wdg/v1/declaration/' .$this->id. '/rois' );

		
		// En retour, on veut le pourcentage d'avancement
		$buffer = $count / count( $roi_list ) * 100;
		
		// Si on a terminé, on finalise la déclaration
		if ( $buffer == 100 ) {
			// On envoie le message en copie au PP
			$declaration_message = $this->get_message();
			if ( !empty( $declaration_message ) ) {
				$campaign_author = $campaign->post_author();
				$WDGUser_author = new WDGUser( $campaign_author );
				$recipient_name = $WDGUser_author->get_firstname();
				$replyto_mail = $WDGUser_author->get_email();
				$declaration_message_decoded = '(vous êtes en copie de ce message en tant que porteur de projet)';
				$declaration_message_decoded .= '<br><br>';
				$declaration_message_decoded .= $declaration_message;
				NotificationsAPI::roi_transfer_message( $replyto_mail, $recipient_name, $campaign->data->post_title, $declaration_message_decoded, $replyto_mail );
			}

			$wdguser_author = new WDGUser( $campaign->data->post_author );
			if ( $this->get_amount_with_adjustment() > 0 ) {
				$tax_infos = '';
				if ( $this->has_paid_gain() ) {
					$tax_infos = "<br><br>Vos investisseurs ont réalisé une plus-value sur leur investissement.";
					$tax_infos .= "Ceux et celles dont le foyer fiscal est en France et qui sont soumis à l’impôt sur le revenu ";
					$tax_infos .= "verront donc 30 % de leur plus-value prélevés à la source (Prélèvement Forfaitaire Unique - flat tax), sauf en cas de demande de dispense de leur part. ";
					$tax_infos .= '<a href="https://support.wedogood.co/investir-et-suivre-mes-investissements/fiscalit%C3%A9-et-comptabilit%C3%A9/quelle-est-la-comptabilit%C3%A9-et-la-fiscalit%C3%A9-de-mon-investissement">En savoir plus sur la fiscalité des investissements</a>.';
				}
				NotificationsAPI::declaration_done_with_turnover( $WDGOrganization_campaign->get_email(), $wdguser_author->get_firstname(), $campaign->data->post_title, $this->get_month_list_str(), $this->get_amount_with_adjustment(), $tax_infos );
			
			} else {
				NotificationsAPI::declaration_done_without_turnover( $WDGOrganization_campaign->get_email(), $wdguser_author->get_firstname(), $campaign->data->post_title, $this->get_month_list_str() );
			}

			$this->status = WDGROIDeclaration::$status_finished;
			$this->date_transfer = $date_now_formatted;

			if ( $this->get_commission_to_pay() > 0 ) {
				// Envoi de la facture
				$campaign_bill = new WDGCampaignBill( $campaign, WDGCampaignBill::$tool_name_quickbooks, WDGCampaignBill::$bill_type_royalties_commission );
				$campaign_bill->set_declaration( $this );
				if ( $campaign_bill->generate() ) {
					// Transfert vers le compte bancaire de WDG
					$transfer_message = 'ROYALTIES ' . $WDGOrganization_campaign->get_name() . ' - D' . $this->id;
					LemonwayLib::ask_transfer_to_iban( 'SC', $this->get_commission_to_pay(), 0, 0, $transfer_message );

				} else {
					NotificationsEmails::declaration_bill_failed( $campaign->data->post_title );
				}
			}
		}
		
		// On met à jour de toute façon pour mettre à jour le reliquat
		$this->update();
		
		// A la toute fin, on vérifie les notifications à envoyer
		if ( $buffer == 100 ) {
			$this->check_notifications( $campaign, $WDGOrganization_campaign->get_email(), $wdguser_author->get_firstname() );
		}

		return $buffer;
	}
	
	/**
	 * 
	 * @param ATCF_campaign $campaign
	 * @param string $recipient
	 * @param string $name
	 */
	private function check_notifications( $campaign, $recipient, $name ) {
		// **************
		// NOTIFICATION 1
		// Doit-on envoyer une notification au PP pour dire que la prochaine déclaration est la dernière ?
		$send_notification_extend = FALSE;
		
		// La notification ne sera envoyée que si il reste une seule déclaration à venir
		$nb_declarations_waiting = 0;
		$amount_transferred = 0;
		$existing_roi_declarations = $campaign->get_roi_declarations();
		foreach ( $existing_roi_declarations as $declaration_object ) {
			if ( $declaration_object[ 'status' ] == WDGROIDeclaration::$status_declaration ) {
				$nb_declarations_waiting++;
			} else {
				$amount_transferred += $declaration_object[ 'total_roi' ];
			}
		}
		if ( $nb_declarations_waiting == 1 ) {
			$send_notification_extend = TRUE;
		}
		
		// La notification ne sera envoyée que si le montant minimum de versement n'a pas été atteint
		if ( $send_notification_extend && $campaign->funding_duration() > 0 ) {
			$amount_minimum_royalties = $campaign->current_amount( FALSE ) * $campaign->minimum_profit();
			if ( $amount_transferred >= $amount_minimum_royalties ) {
				$send_notification_extend = FALSE;
			}
		}
			
		if ( $send_notification_extend ) {
			$amount_remaining = $amount_minimum_royalties - $amount_transferred;
			
			$amount_transferred_str = UIHelpers::format_number( $amount_transferred );
			$amount_minimum_royalties_str = UIHelpers::format_number( $amount_minimum_royalties );
			$amount_remaining_str = UIHelpers::format_number( $amount_remaining );
			NotificationsAPI::declaration_to_be_extended( $recipient, $name, $amount_transferred_str, $amount_minimum_royalties_str, $amount_remaining_str );
		}
		
		
		// **************
		// NOTIFICATION 2
		// Si on approche du maximum à verser pour un projet, on prévient le service administratif
		if ( $campaign->maximum_profit() != 'infinite' && $amount_transferred / $campaign->maximum_profit_amount() > 0.8 ) {
			$ratio = floor( $amount_transferred / $campaign->maximum_profit_amount() * 100 );
			NotificationsSlack::declarations_close_to_maximum_profit( $campaign->get_name(), $ratio );
		}
		
		
		// **************
		// NOTIFICATION 3
		// Doit-on envoyer une notification au porteur de projet et aux investisseurs pour dire que c'était le dernier versement ?
		$amount_minimum_royalties = $campaign->current_amount( FALSE ) * $campaign->minimum_profit();
		// Si la durée du financement n'est pas indéterminée
		if ( $campaign->funding_duration() > 0
			// Si le nombre actuel de déclaration est au moins égal au nombre de déclarations par année * le nombre d'années
			&& count( $existing_roi_declarations ) >= $campaign->funding_duration() * $campaign->get_declararations_count_per_year()
			// Si le minimum à reverser a été atteint
			&& $amount_transferred >= $amount_minimum_royalties ) {
				
				// Alors c'est la première fois qu'on va ajouter une déclaration, donc on notifie tout le monde
				WDGQueue::add_contract_finished_notifications( $campaign->ID );
		}
		
	}
	
	private function update_investment_contract_amount_received( $investment_contracts, $investment_id, $roi_amount ) {
		if ( !empty( $investment_contracts ) ) {
			foreach ( $investment_contracts as $investment_contract_item ) {
				if ( $investment_contract_item->subscription_id == $investment_id ) {
					$amount_received = $investment_contract_item->amount_received + $roi_amount;
					$investment_contract = new WDGInvestmentContract( $investment_contract_item->id, $investment_contract_item );
					$investment_contract->check_amount_received( $amount_received, $roi_amount );
					WDGWPREST_Entity_InvestmentContract::edit( $investment_contract_item->id, $amount_received );
					break;
				}
			}
		}
	}
	
	/**
	 * Répare un versement qui n'a pas eu lieu vers un utilisateur
	 */
	public function redo_transfers() {
		$campaign = $this->get_campaign_object();
		$current_organization = $campaign->get_organization();
		if (!empty($current_organization)) {
			$organization_obj = new WDGOrganization( $current_organization->wpref, $current_organization );
		}
		$date_now = new DateTime();
		
		$roi_list = $this->get_rois();
		foreach ( $roi_list as $roi_item ) {
			if ( $roi_item->amount > 0 && $roi_item->id_transfer == 0 && ( $roi_item->status == WDGROI::$status_transferred || $roi_item->status == WDGROI::$status_error ) ) {
				$ROI = new WDGROI( $roi_item->id );

				if ( $ROI->id_user > 0 ) {
					$transfer = FALSE;
					//Gestion versement vers organisation
					if ( $ROI->recipient_type == 'orga' ) {
						$WDGOrga = WDGOrganization::get_by_api_id( $ROI->id_user );
						$WDGOrga->register_lemonway();
						if ( $WDGOrga->is_registered_lemonway_wallet() ) {
							$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_royalties_lemonway_id(), $WDGOrga->get_lemonway_id(), $ROI->amount );
							$status = WDGROI::$status_transferred;

							// Enregistrement des données de taxe
							if ( $ROI->amount_taxed_in_cents > 0 ) {
								WDGROITax::insert( $ROI->id, $ROI->id_user, 'orga', $date_now->format( 'Y-m-d' ), $ROI->amount_taxed_in_cents, 0, 0, $WDGOrga->get_country(), '0' );
							}

						} else {
							$status = WDGROI::$status_waiting_authentication;
						}

					//Versement vers utilisateur personne physique
					} else {
						$WDGUser = WDGUser::get_by_api_id( $ROI->id_user );
						$WDGUser->register_lemonway();
						if ( $WDGUser->is_lemonway_registered() ) {
							// Transfert sur le wallet de séquestre d'impots de l'organisation
							$amount_tax_in_cents = 0;
							if ( $ROI->amount_taxed_in_cents > 0 ) {
								$amount_tax_in_cents = $WDGUser->get_tax_amount_in_cents_round( $ROI->amount_taxed_in_cents );
								if ( $amount_tax_in_cents > 0 ) {
									$WDGOrganization_campaign->check_register_tax_lemonway_wallet();
									LemonwayLib::ask_transfer_funds( $WDGOrganization_campaign->get_royalties_lemonway_id(), $WDGOrganization_campaign->get_tax_lemonway_id(), $amount_tax_in_cents / 100 );
									$percent_tax = $WDGUser->get_tax_percent();
									WDGROITax::insert( $ROI->id, $ROI->id_user, 'user', $date_now->format( 'Y-m-d' ), $ROI->amount_taxed_in_cents, $amount_tax_in_cents, $percent_tax, $WDGUser->get_tax_country(), $WDGUser->has_tax_exemption_for_year( $date_now->format( 'Y' ) ) );
									WDGQueue::add_tax_monthly_summary( $this->id );
								}
							}

							$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_royalties_lemonway_id(), $WDGUser->get_lemonway_id(), $ROI->amount - $amount_tax_in_cents / 100 );
							$status = WDGROI::$status_transferred;

						} else {
							$status = WDGROI::$status_waiting_authentication;
						}
					}

					if ( $transfer != FALSE ) {
						$ROI->status = $status;
						$ROI->id_transfer = $transfer->ID;
						$ROI->update();
					}
				}
			}
		}
			
		WDGWPRESTLib::unset_cache( 'wdg/v1/declaration/' .$this->id. '/rois' );
		
	}
	
	/**
	 * Si la dÃ©claration Ã©tait en attente de virement, on valide que le virement a Ã©tÃ© reÃ§u
	 */
	public function mark_transfer_received() {
		if ( $this->status == WDGROIDeclaration::$status_waiting_transfer ) {
			$this->status = WDGROIDeclaration::$status_transfer;
			$this->update();
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
	 * Crée le fichier pdf d'attestation
	 * @param boolean $force Si $force est à true, on crée même si le fichier existe déjà
	 */
	public function make_payment_certificate( $force = false ) {
		$filename = $this->get_payment_certificate_filename();
		$filepath = __DIR__ . '/../../files/certificate-roi-payment/' . $filename;
		if ( !$force && file_exists( $filepath ) ) {
			return;
		}
		
		$date_due = new DateTime( $this->date_due );
		$certificate_date = $date_due->format( 'd/m/Y' );
		
		$campaign = $this->get_campaign_object();
		$current_organization = $campaign->get_organization();
		if ( !empty( $current_organization ) ) {
			$organization_obj = new WDGOrganization( $current_organization->wpref, $current_organization );
		}
		$project_roi_percent = $campaign->roi_percent_remaining();
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
		$organization_address = $organization_obj->get_full_address_str();
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
	 * Se charge 
	 * - de vérifier si des investisseurs vont toucher une plus-value
	 * - de vérifier si ils vont devoir payer des impots dessus et à quel taux 
	 * (personne physique, dont la résidence fiscale est en France, dispense ou non)
	 * - d'envoyer le résumé par mail à admin si il y a des infos à transmettre
	 */
	public function init_rois_and_tax() {
		if ( $this->remaining_amount == 0 ) {
			$this->remaining_amount = $this->amount;
		}

		//********************** */
		$campaign = $this->get_campaign_object();
		$campaign_organization = $campaign->get_organization();
		if ( empty( $campaign_organization ) ) {
			return;
		}
		$WDGOrganization_campaign = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
		$investment_contracts = WDGInvestmentContract::get_list_sorted_by_subscription_id( $campaign->ID );

		$investments_list = $campaign->roi_payments_data( $this );
		$count_done = 0;
		$max_items_to_do_now = 30;
		$count_done_now = 0;
		foreach ( $investments_list as $investment_item ) {
			$count_done++;
			$saved_roi = $this->get_roi_by_investment( $investment_item['ID'] );
			if ( empty( $saved_roi ) ) {
				$count_done_now++;
				$recipient_type = 'user';
				$this->remaining_amount -= $investment_item[ 'roi_amount' ];

				//Versement vers utilisateur personne morale
				if ( WDGOrganization::is_user_organization( $investment_item[ 'user' ] ) ) {
					$WDGOrganization = new WDGOrganization( $investment_item[ 'user' ] );
					$recipient_api_id = $WDGOrganization->get_api_id();
					$recipient_type = 'orga';

				//Versement vers utilisateur personne physique
				} else {
					$WDGUser = new WDGUser( $investment_item[ 'user' ] );
					$recipient_api_id = $WDGUser->get_api_id();
				}
				
				// Contrôle sur les taxes pour enregistrer la part des royalties qui serait taxée
				$user_taxed_amount_in_cents = 0;
				if ( $investment_item[ 'roi_amount' ] > 0 && isset( $investment_contracts[ $investment_item['ID'] ] ) ) {
					$investment_contract_item = $investment_contracts[ $investment_item['ID'] ];
					$user_amount_updated = $investment_contract_item->amount_received + $investment_item[ 'roi_amount' ];
					if ( $user_amount_updated > $investment_contract_item->subscription_amount ) {
						$user_taxed_amount = min( $investment_item[ 'roi_amount' ], $user_amount_updated - $investment_contract_item->subscription_amount );
						$user_taxed_amount_in_cents = floor( $user_taxed_amount * 100 );
					}
				}

				// Enregistrer en tant que transfert à venir
				$status = WDGROI::$status_waiting_transfer;
				$id_investment_contract = FALSE;
				if ( !empty( $investment_item[ 'contract_id' ] ) ) {
					$id_investment_contract = $investment_item[ 'contract_id' ];
				}
				WDGROI::insert( $investment_item['ID'], $this->id_campaign, $WDGOrganization_campaign->get_api_id(), $recipient_api_id, $recipient_type, $this->id, '0000-00-00', $investment_item['roi_amount'], 0, $status, $id_investment_contract, $user_taxed_amount_in_cents );

				if ( $count_done_now >= $max_items_to_do_now ) {
					break;
				}
			}
		}
		WDGWPRESTLib::unset_cache( 'wdg/v1/declaration/' .$this->id. '/rois' );

		// On a fini l'initialisation, on déclenche la suite
		if ( $count_done >= count( $investments_list ) ) {
			// On met à jour de toute façon
			$this->status = WDGROIDeclaration::$status_transfer;
			$this->update();
		
			// Calcul de la date à laquelle on fera le versement auto (on décale si c'est un prélèvement)
			$date_of_royalties_transfer = FALSE;
			if ( $this->mean_payment === WDGROIDeclaration::$mean_payment_mandate ) {
				$date_of_royalties_transfer = $this->get_transfer_date();
			}

			// Programmer versement auto
			WDGQueue::add_royalties_auto_transfer_start( $this->id, $date_of_royalties_transfer );

		// Pas fini, on continue l'initialisation
		} else {
			$this->update();
			WDGQueue::add_init_declaration_rois( $this->id );
		}
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
	 * Détermine la valeur de l'ajustement
	 * @return number
	 */
	public function get_adjustment_value() {
		$buffer = 0;
		
		$adjustments = $this->get_adjustments();
		
		if ( empty( $adjustments ) ) {
			if ( !empty( $this->adjustment ) ) {
				$temp = json_decode( $this->adjustment );
				$temp_value = $temp->value;
				if ( is_numeric( $temp_value ) ) {
					$buffer = $temp_value;
				}
				
				$this->move_adjustment_to_api();
			}
			
		} else {
			$buffer = $this->get_adjustments_amount();
		}
		
		return $buffer;
	}
	
	public function move_adjustment_to_api() {
		$adjustments = $this->get_adjustments();
		
		if ( !empty( $this->adjustment ) && empty( $adjustments ) ) {
			$temp = json_decode( $this->adjustment );
			if ( !isset( $temp->on_api ) ) {
				$temp_value = $temp->value;
				$temp_turnover_difference = $temp->turnover_difference;
				$temp_msg_to_author = $temp->msg_to_author;
				$temp_msg_to_investors = $temp->msg_to_investors;

				$campaign = $this->get_campaign_object();

				$adjustment = new WDGAdjustment();
				$adjustment->id_api_campaign = $campaign->get_api_id();
				$adjustment->id_declaration = $this->id;
				$adjustment->type = WDGAdjustment::$type_turnover_difference;
				$adjustment->turnover_difference = $temp_turnover_difference;
				$adjustment->amount = $temp_value;
				$adjustment->message_organization = $temp_msg_to_author;
				$adjustment->message_investors = $temp_msg_to_investors;
				$adjustment->create();

				$temp->on_api = 1;
				$this->adjustment = json_encode( $temp );
				$this->update();
			}
		}
	}
	
	/**
	 * Retourne la liste des ajustements qui s'appliquent sur cette déclaration
	 * @return array
	 */
	public function get_adjustments() {
		if ( !isset( $this->adjustments ) ) {
			if ( !isset( $this->api_data_adjustments ) ) {
				$this->adjustments = WDGWPREST_Entity_Adjustment::get_list_by_declaration_id( $this->id );
			} else {
				$this->adjustments = $this->api_data_adjustments;
			}
		}
		return $this->adjustments;
	}
	
	/**
	 * Retourne le montant des ajustements liés à une déclaration
	 * @return float
	 */
	public function get_adjustments_amount() {
		$buffer = 0;
		$this->get_adjustments(); // Initialisation de la liste
		if ( !empty( $this->adjustments ) ) {
			foreach ( $this->adjustments as $adjustment_item ) {
				$buffer += $adjustment_item->amount;
			}
		}
		return $buffer;
	}

	public function get_adjustments_amount_as_turnover() {
		$buffer = 0;
		$this->get_adjustments(); // Initialisation de la liste
		if ( !empty( $this->adjustments ) ) {
			foreach ( $this->adjustments as $adjustment_item ) {
				$buffer += $adjustment_item->amount;
			}
		}
		$campaign_object = $this->get_campaign_object();
		$buffer *= 100 / $campaign_object->roi_percent();
		return $buffer;
	}
	
	/**
	 * Retourne vrai si un ajustement concernait cette déclaration
	 */
	public function is_checked_by_adjustments() {
		$buffer = FALSE;
		
		if ( $this->status == WDGROIDeclaration::$status_finished ) {
			$linked_adjustments = WDGWPREST_Entity_Adjustment::get_list_linked_to_declaration_id( $this->id );
			if ( !empty( $linked_adjustments ) ) {
				$buffer = TRUE;
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Enregistre le reliquat (utile surtout pour les anciennes dÃ©clarations Ã  mettre Ã  jour)
	 */
	public function save_remaining_amount() {
		if ( $this->status == WDGROIDeclaration::$status_finished ) {
			$campaign = $this->get_campaign_object();
			$investments_list = $campaign->roi_payments_data( $this );
			$remaining_amount = $this->get_amount_with_adjustment();
			foreach ($investments_list as $investment_item) {
				$remaining_amount -= $investment_item['roi_fees'];
				$remaining_amount -= $investment_item['roi_amount'];
			}
			$this->remaining_amount = $remaining_amount;
			$this->update();
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
			if ( !isset( $this->api_data_rois ) ) {
				$this->roi_list = WDGWPREST_Entity_Declaration::get_roi_list( $this->id );
			} else {
				$this->roi_list = $this->api_data_rois;
			}
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
	public static function get_list_by_campaign_id( $idwp_campaign, $status = '', $with_links = TRUE ) {
		$buffer = array();
		
		$campaign = new ATCF_Campaign( $idwp_campaign );
		$declarations = WDGWPREST_Entity_Project::get_declarations( $campaign->get_api_id(), $with_links );
		if ( !empty( $declarations ) ) {
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
					$ROIdeclaration = new WDGROIDeclaration( $declaration_item->id, $declaration_item );
					array_push( $buffer, $ROIdeclaration );
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
}
