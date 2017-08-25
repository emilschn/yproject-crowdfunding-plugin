<?php
/**
 * Classe de gestion des déclarations de ROI
 */
class WDGROIDeclaration {
	public static $table_name = 'ypcf_roideclaration';
	
	public static $status_declaration = 'declaration';
	public static $status_payment = 'payment';
	public static $status_waiting_transfer = 'waiting_transfer';
	public static $status_transfer = 'transfer';
	public static $status_finished = 'finished';
	
	public static $mean_payment_card = 'card';
	public static $mean_payment_wire = 'wire';
	
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
	public $on_api;
	
	
	public function __construct( $declaration_id ) {
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
			
			// Les déclarations sans statut doivent passer en statut "Déclaration"
			if ( empty( $this->status ) || $this->status == null ) {
				$this->status = WDGROIDeclaration::$status_declaration;
			}
			
			// Les déclarations à zero pour les projets en mode "paiement" doivent être marquées comme terminées
			if ( $this->status == WDGROIDeclaration::$status_payment && !empty( $this->turnover ) && $this->get_amount_with_adjustment() == 0 ) {
				$this->status = WDGROIDeclaration::$status_finished;
				$this->save();
			}
		}
	}
	
	public function save() {
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
	 * Retourne le montant additionné l'ajustement
	 * @return number
	 */
	public function get_amount_with_adjustment() {
		return ( $this->amount + $this->get_adjustment_value() );
	}
	
	/**
	 * Retourne le montant additionné avec la commission
	 * @return number
	 */
	public function get_amount_with_commission() {
		return ( $this->get_amount_with_adjustment() + $this->get_commission_to_pay() );
	}
	
	/**
	 * Retourne la commission éventuelle que doit payer le porteur de projet au moment de reverser les fonds
	 * @return number
	 */
	public function get_commission_to_pay() {
		$buffer = 0;
		
		//Si le porteur de projet a déjà payé, on considère qu'on a déjà enregistré la commission
		if ( $this->status == WDGROIDeclaration::$status_transfer || $this->status == WDGROIDeclaration::$status_finished ) {
			$cost = $this->percent_commission;
			
		//Sinon, on la calcule avec les frais enregistrés en rapport avec la campagne
		} else {
			$campaign = new ATCF_Campaign( $this->id_campaign );
			$cost = $campaign->get_costs_to_organization();
		}
		
		if ( $cost > 0 ) {
			$buffer = (round(($this->get_amount_with_adjustment() * $cost / 100) * 100) / 100);
		}
		return $buffer;
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
	 * Retourne le montant total de CA déclaré
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
	
	/**
	 * S'occuper des versements vers les utilisateurs
	 */
	public function make_transfer( $send_notifications = true, $transfer_remaining_amount = false ) {
		$buffer = false;
		$date_now = new DateTime();
		$date_now_formatted = $date_now->format( 'Y-m-d' );
		$campaign = new ATCF_Campaign($this->id_campaign);
		$current_organization = $campaign->get_organization();
		if ( !empty( $current_organization ) ) {
			$organization_obj = new WDGOrganization($current_organization->wpref);
			$organization_obj->register_lemonway();
			$investments_list = $campaign->roi_payments_data( $this, $transfer_remaining_amount );
			$total_fees = 0;
			$remaining_amount = $this->amount;
			if ( $transfer_remaining_amount ) {
				$previous_remaining_amount = $this->get_previous_remaining_amount();
				$remaining_amount += $previous_remaining_amount;
			}
			foreach ($investments_list as $investment_item) {
				
				$saved_roi = WDGROI::get_roi_by_declaration_invest( $this->id, $investment_item['ID'] );
				if ( empty( $saved_roi ) ) {
					$total_fees += $investment_item['roi_fees'];
					$remaining_amount -= $investment_item['roi_fees'];
					$remaining_amount -= $investment_item['roi_amount'];

					//Versement vers organisation
					if (WDGOrganization::is_user_organization( $investment_item['user'] )) {
						$WDGOrga = new WDGOrganization( $investment_item['user'] );
						$WDGOrga->register_lemonway();
						$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGOrga->get_lemonway_id(), $investment_item['roi_amount'] );
						$credit_bank_info = WDGWPREST_Entity_BankInfo::get( $WDGOrga->get_email() );
						if ( $credit_bank_info != FALSE ) {
							$send_notifications = FALSE;
							/*$WDGOrga->set_bank_owner( $credit_bank_info->holdername );
							$WDGOrga->set_bank_iban( $credit_bank_info->iban );
							$WDGOrga->set_bank_bic( $credit_bank_info->bic );
							$WDGOrga->set_bank_address( $credit_bank_info->address1. ' ' .$credit_bank_info->address2 );
							$WDGOrga->save();
							$WDGOrga->submit_transfer_wallet_lemonway();*/
						}

					//Versement vers utilisateur personne physique
					} else {
						$WDGUser = new WDGUser( $investment_item['user'] );
						$WDGUser->register_lemonway();
						$transfer = LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), $WDGUser->get_lemonway_id(), $investment_item['roi_amount'] );
						$credit_bank_info = WDGWPREST_Entity_BankInfo::get( $WDGUser->get_email() );
						if ( $credit_bank_info != FALSE ) {
							$send_notifications = FALSE;
							$WDGUser->save_iban( $credit_bank_info->holdername, $credit_bank_info->iban, $credit_bank_info->bic, $credit_bank_info->address1. ' ' .$credit_bank_info->address2 );
							$WDGUser->transfer_wallet_to_bankaccount( $investment_item['roi_amount'] );
						}
					}

					if ( $transfer != FALSE ) {
						WDGROI::insert($investment_item['ID'], $this->id_campaign, $current_organization->wpref, $investment_item['user'], $this->id, $date_now_formatted, $investment_item['roi_amount'], $transfer->ID, WDGROI::$status_transferred);
						
						if ( $send_notifications ) {
							if ($investment_item['roi_amount'] > 0) {
								NotificationsEmails::roi_transfer_success_user( $this->id, $investment_item['user'], $this->get_message() );
							} else {
								NotificationsEmails::roi_transfer_null_user( $this->id, $investment_item['user'], $this->get_message() );
							}
						}

					} else {
						WDGROI::insert($investment_item['ID'], $this->id_campaign, $current_organization->wpref, $investment_item['user'], $this->id, $date_now_formatted, $investment_item['roi_amount'], 0, WDGROI::$status_error);

					}
					
				}
			}
			if ($total_fees > 0) {
				LemonwayLib::ask_transfer_funds( $organization_obj->get_lemonway_id(), "SC", $total_fees);
			}
			if ( $transfer_remaining_amount ) {
				// Mise à jour de la somme des reliquats précédents qui ont été reversés
				if ( $previous_remaining_amount > $remaining_amount ) {
					$this->transfered_previous_remaining_amount = $previous_remaining_amount - $remaining_amount;
				}
			}
			$this->remaining_amount = $remaining_amount;
			$this->status = WDGROIDeclaration::$status_finished;
			$this->date_transfer = $date_now_formatted;
			$this->save();
			$buffer = true;
		}
		return $buffer;
	}
	
	/**
	 * Répare un versement qui n'a pas eu lieu vers un utilisateur
	 */
	public function redo_transfers() {
		$campaign = new ATCF_Campaign($this->id_campaign);
		$current_organization = $campaign->get_organization();
		if (!empty($current_organization)) {
			$organization_obj = new WDGOrganization($current_organization->wpref);
		}
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROI::$table_name;
		$query .= " WHERE id_declaration=".$this->id;
		$query .= " AND amount>0";
		$query .= " AND id_transfer=0";
		$query .= " AND ( status='" .WDGROI::$status_transferred. "' OR status='" .WDGROI::$status_error. "' )";
		
		$roi_list = $wpdb->get_results( $query );
		foreach ( $roi_list as $roi_item ) {
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
			$ROI->save();
		}
		
	}
	
	/**
	 * Si la déclaration était en attente de virement, on valide que le virement a été reçu
	 */
	public function mark_transfer_received() {
		if ( $this->status == WDGROIDeclaration::$status_waiting_transfer ) {
			$this->status = WDGROIDeclaration::$status_transfer;
			$this->save();
		}
	}
	
	/**
	 * Détermine le nom du fichier d'attestation qui va être créé
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
		
		$campaign = new ATCF_Campaign( $this->id_campaign );
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
			array_push( $project_investors_list, array( "firstname" => $user_data->first_name, "lastname" => $user_data->last_name, "amount" => $investment_item['amount'] ) );
		}
		$project_roi_nb_years = $campaign->funding_duration();
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
	 * Détermine l'URL où le fichier d'attestation peut être téléchargé
	 * @return string
	 */
	public function get_payment_certificate_url() {
		$buffer = home_url() . '/wp-content/plugins/appthemer-crowdfunding/files/certificate-roi-payment/';
		$buffer .= $this->get_payment_certificate_filename();
		return $buffer;
	}
	
	/**
	 * Renvoie true si la déclaration de ROI peut être payée via virement
	 * @return boolean
	 */
	public function can_pay_with_wire() {
		return ($this->get_amount_with_commission() >= WDGROIDeclaration::$min_amount_for_wire_payment);
	}
	
	/**
	 * Renvoie la liste des mois concernés par une déclaration
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
		// Si l'année de déclaration est différente de la première date de déclaration,
			// on recule d'une année
		if ( $declaration_year > $date_due->format( 'Y' ) ) {
			$declaration_year--;
		}
		
		for ($i = 0; $i < $nb_fields; $i++) {
			// Si on est en janvier, et que ce n'est pas la première déclaration,
				// on avance d'une année
			if ( $i > 0 && $date_due->format( 'n' ) == 1 ) {
				$declaration_year++;
			}
			array_push( $buffer, ucfirst( __( $months[ $date_due->format('m') - 1 ] ) ) . ' ' . $declaration_year );
			$date_due->add( new DateInterval( 'P1M' ) );
		}

		return $buffer;
	}
	
	/**
	 * Enregistre les données d'ajustement
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
		$this->save();
	}
	
	/**
	 * Détermine le statut validé ou non
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
	 * Détermine si l'ajustement est obligatoire ou non
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
	 * Détermine la valeur de l'ajustement
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
	 * Détermine la valeur de la différence de chiffre d'affaires
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
	 * Retourne le message enregistré pour les investisseurs pour le porteur de projet
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
	 * Enregistre le reliquat (utile surtout pour les anciennes déclarations à mettre à jour)
	 */
	public function save_remaining_amount() {
		if ( $this->status == WDGROIDeclaration::$status_finished ) {
			$campaign = atcf_get_campaign( $this->id_campaign );
			$investments_list = $campaign->roi_payments_data( $this );
			$remaining_amount = $this->amount;
			foreach ($investments_list as $investment_item) {
				$remaining_amount -= $investment_item['roi_fees'];
				$remaining_amount -= $investment_item['roi_amount'];
			}
			$this->remaining_amount = $remaining_amount;
			$this->save();
		}
	}
	
	/**
	 * Retourne la liste des déclarations qui précèdent celles-ci
	 */
	public function get_previous_declarations() {
		$buffer = array();
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROIDeclaration::$table_name;
		$query .= " WHERE date_due < " .$this->date_due;
		
		$declaration_list = $wpdb->get_results( $query );
		foreach ( $declaration_list as $declaration_item ) {
			$ROIdeclaration = new WDGROIDeclaration( $declaration_item->id );
			array_push($buffer, $ROIdeclaration);
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
	
	
/*******************************************************************************
 * REQUETES STATIQUES
 ******************************************************************************/
	/**
	 * Mise à jour base de données
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
	 * Ajout d'une nouvelle déclaration
	 */
	public static function insert( $id_campaign, $date_due ) {
		global $wpdb;
		$result = $wpdb->insert( 
			$wpdb->prefix . WDGROIDeclaration::$table_name, 
			array( 
				'id_campaign'	=> $id_campaign, 
				'date_due'		=> $date_due
			) 
		);
		if ($result !== FALSE) {
			return $wpdb->insert_id;
		}
	}
	
	/**
	 * Liste des déclarations ROI pour un projet
	 */
	public static function get_list_by_campaign_id( $id_campaign, $status = '' ) {
		$buffer = array();
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROIDeclaration::$table_name;
		$query .= " WHERE id_campaign=".$id_campaign;
		if ( !empty( $status ) ) {
			$query .= " AND ";
			if ( $status == WDGROIDeclaration::$status_declaration ) {
				$query .= "( status='".$status."' OR status='' OR status IS NULL )";
				
			} else {
				$query .= "status='".$status."'";
			}
		}
		$query .= " ORDER BY date_due ASC";
		
		$declaration_list = $wpdb->get_results( $query );
		foreach ( $declaration_list as $declaration_item ) {
			$ROIdeclaration = new WDGROIDeclaration( $declaration_item->id );
			array_push($buffer, $ROIdeclaration);
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne une déclaration ROI par son token de paiement
	 * @param string $token
	 * @return WDGROIDeclaration
	 */
	public static function get_by_payment_token( $token ) {
		$buffer = FALSE;
		
		global $wpdb;
		$query = "SELECT id FROM " .$wpdb->prefix.WDGROIDeclaration::$table_name;
		$query .= " WHERE payment_token='" .$token. "'";
		
		$declaration_list = $wpdb->get_results( $query );
		foreach ( $declaration_list as $declaration_item ) {
			$buffer = new WDGROIDeclaration( $declaration_item->id );
		}
		
		return $buffer;
	}
	
	
	public static function transfer_to_api() {
		global $wpdb;
		$query = "SELECT id, on_api FROM " .$wpdb->prefix.WDGROIDeclaration::$table_name;
		
		$declaration_list = $wpdb->get_results( $query );
		foreach ( $declaration_list as $declaration_item ) {
			if ( !$declaration_item->on_api ) {
				$declaration = new WDGROIDeclaration( $declaration_item->id );
				WDGWPREST_Entity_Declaration::create( $declaration );
				$declaration->on_api = true;
				$declaration->save();
			}
		}
	}
}
