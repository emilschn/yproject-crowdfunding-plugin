<?php
/**
 * Classe de gestion des appels Ajax
 * TODO : centraliser ici
 */
class WDGAjaxActions {
	private static $class_name = 'WDGAjaxActions';
    
	/**
	 * Initialise la liste des actions ajax
	 */
	public static function init_actions() {
		WDGAjaxActions::add_action('display_roi_user_list');
		WDGAjaxActions::add_action('show_project_money_flow');
		WDGAjaxActions::add_action('check_invest_input');
		WDGAjaxActions::add_action('save_user_infos');
		WDGAjaxActions::add_action('save_orga_infos');
		WDGAjaxActions::add_action('save_user_docs');
	}
    
	/**
	 * Ajoute une action WordPress à exécuter en Ajax
	 * @param string $action_name
	 */
	public static function add_action($action_name) {
		add_action('wp_ajax_' . $action_name, array(WDGAjaxActions::$class_name, $action_name));
		add_action('wp_ajax_nopriv_' . $action_name, array(WDGAjaxActions::$class_name, $action_name));
	}
    
	/**
	 * Affiche la liste des utilisateurs d'un projet qui doivent récupérer de l'argent de leur investissement
	 */
	public static function display_roi_user_list() {
		$wdgcurrent_user = WDGUser::current();
		if ($wdgcurrent_user->is_admin()) {
		    //Récupération des éléments à traiter
		    $declaration_id = filter_input(INPUT_POST, 'roideclaration_id');
			$declaration = new WDGROIDeclaration($declaration_id);
		    $campaign = new ATCF_Campaign($declaration->id_campaign);
		    $total_roi = 0;
		    $total_fees = 0;
		    $investments_list = $campaign->roi_payments_data($declaration);
		    foreach ($investments_list as $investment_item) {
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
				<td><?php echo $campaign->current_amount(FALSE); ?> &euro;</td>
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
			//Récupération des éléments à traiter
			$campaign_id = filter_input(INPUT_POST, 'campaign_id');
			$campaign_post = get_post($campaign_id);
			$campaign = atcf_get_campaign($campaign_post);
			if ($campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_mangopay):
			$campaign_organisation = $campaign->get_organisation();
				$mp_wallet_campaign_id = ypcf_mangopay_get_mp_campaign_wallet_id($campaign_id);
				$mp_wallet_campaign_infos = ypcf_mangopay_get_wallet_by_id($mp_wallet_campaign_id);
				$organisation_obj = new YPOrganisation($campaign_organisation->organisation_wpref);
				$mp_operations_campaign = ypcf_mangopay_get_operations_by_wallet_id($mp_wallet_campaign_id);
				$mp_operations_organisation = $organisation_obj->get_operations();
			?>

			Montant collect&eacute; : <?php echo $campaign->current_amount(FALSE); ?><br />
			Montant actuel sur le porte-monnaie du projet : <?php echo ($mp_wallet_campaign_infos->Amount / 100); ?><br />
			Montant actuel sur le porte-monnaie de l'organisation : <?php echo $organisation_obj->get_wallet_amount(); ?><br /><br />
			
			<strong>Liste des transactions sur le porte-monnaie projet :</strong><br />
			<div class="wdg-datatable">
			    <table cellspacing="0" width="100%">
				<thead><tr><td>Date</td><td>Objet</td><td>Débit</td><td>Crédit</td></tr></thead>
				<tfoot><tr><td>Date</td><td>Objet</td><td>Débit</td><td>Crédit</td></tr></tfoot>
				<tbody>
				<?php 
				//Tri des doublons renvoyés par MP
				$operation_list = array();
				foreach($mp_operations_campaign as $operation_item) {
					$operation_list[$operation_item->TransactionID] = $operation_item;
				}

				foreach($operation_list as $operation_item): ?>
				    <?php
				    $operation_date = new DateTime();
				    $operation_date->setTimestamp($operation_item->CreationDate);
				    $object = '';
				    $credit = '';
				    $debit = '';
				    switch ($operation_item->TransactionType) {
					    case 'Contribution':
						    $user_list = get_users(array('meta_key' => 'mangopay_user_id', 'meta_value' => $operation_item->UserID));
						    $object = 'Investissement utilisateur ' . $user_list[0]->data->user_nicename;
						    $credit = $operation_item->Amount / 100;
						    break;
					    case 'Transfer':
						    $operation_infos = ypcf_mangopay_get_transfer_by_id($operation_item->TransactionID);
						    $beneficiary_infos = ypcf_mangopay_get_user_by_id($operation_infos->BeneficiaryID);
						    if ($beneficiary_infos->FirstName != $beneficiary_infos->LastName) {
							    $object = 'Transfert vers ' .$beneficiary_infos->FirstName. ' ' .$beneficiary_infos->LastName. ' (' .$beneficiary_infos->ID. ')';
						    } else {
							    $object = 'Transfert vers ' .$beneficiary_infos->FirstName. ' (' .$beneficiary_infos->ID. ')';
						    }
						    $debit = $operation_item->Amount / 100;
						    break;
					    case 'Withdrawal':
						    $object = 'Retrait';
						    $debit = $operation_item->Amount / 100;
						    break;

				    }
				    ?>
				    <tr data-transaction="<?php echo $operation_item->TransactionID; ?>">
					<td><?php echo $operation_date->format('Y-m-d H:i:s'); ?></td>
					<td><?php echo $object; ?></td>
					<td><?php echo $debit; ?></td>
					<td><?php echo $credit; ?></td>
				    </tr>
				<?php endforeach; ?>
				</tbody>
			    </table>
			</div><br /><br />
			
			<strong>Liste des transactions sur le porte-monnaie organisation :</strong><br />
			<div class="wdg-datatable">
			    <table cellspacing="0" width="100%">
				<thead><tr><td>Date</td><td>Objet</td><td>Débit</td><td>Crédit</td></tr></thead>
				<tfoot><tr><td>Date</td><td>Objet</td><td>Débit</td><td>Crédit</td></tr></tfoot>
				<tbody>
				<?php 
				//Tri des doublons renvoyés par MP
				$operation_list = array();
				foreach($mp_operations_organisation as $operation_item) {
					$operation_list[$operation_item->TransactionID] = $operation_item;
				}

				foreach($operation_list as $operation_item): ?>
				    <?php
				    $operation_date = new DateTime();
				    $operation_date->setTimestamp($operation_item->CreationDate);
				    $object = '';
				    $credit = '';
				    $debit = '';
				    switch ($operation_item->TransactionType) {
					    case 'Contribution':
						    $user_list = get_users(array('meta_key' => 'mangopay_user_id', 'meta_value' => $operation_item->UserID));
						    $object = 'Investissement utilisateur ' . $user_list[0]->data->user_nicename;
						    if ($organisation_obj->get_wpref() == $user_list[0]->data->ID) {
							    $object = 'Paiement pour reversement';
						    }
						    
						    $credit = $operation_item->Amount / 100;
						    break;
					    case 'Transfer':
						    $operation_infos = ypcf_mangopay_get_transfer_by_id($operation_item->TransactionID);
						    $beneficiary_infos = ypcf_mangopay_get_user_by_id($operation_infos->BeneficiaryID);
						    if ($beneficiary_infos->FirstName != $beneficiary_infos->LastName) {
							    $object = 'Transfert vers ' .$beneficiary_infos->FirstName. ' ' .$beneficiary_infos->LastName. ' (' .$beneficiary_infos->ID. ')';
						    } else {
							    $object = 'Transfert vers ' .$beneficiary_infos->FirstName. ' (' .$beneficiary_infos->ID. ')';
						    }
						    $debit = $operation_item->Amount / 100;
						    break;
					    case 'Withdrawal':
						    $object = 'Retrait';
						    $debit = $operation_item->Amount / 100;
						    break;

				    }
				    ?>
				    <tr data-transaction="<?php echo $operation_item->TransactionID; ?>">
					<td><?php echo $operation_date->format('Y-m-d H:i:s'); ?></td>
					<td><?php echo $object; ?></td>
					<td><?php echo $debit; ?></td>
					<td><?php echo $credit; ?></td>
				    </tr>
				<?php endforeach; ?>
				</tbody>
			    </table>
			</div>
			
			<?php
			endif;
			exit();
		}
	}
	
	/**
	 * Vérifie le passage à l'étape suivante pour les utilisateurs lors de l'investissement
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
				"firstname" => $WDGuser_current->wp_user->user_firstname,
				"lastname" => $WDGuser_current->wp_user->user_lastname,
				"email" => $WDGuser_current->wp_user->user_email,
				"nationality" => $WDGuser_current->wp_user->get('user_nationality'),
				"birthday_day" => $WDGuser_current->wp_user->get('user_birthday_day'),
				"birthday_month" => $WDGuser_current->wp_user->get('user_birthday_month'),
				"birthday_year" => $WDGuser_current->wp_user->get('user_birthday_year'),
				"address" => $WDGuser_current->wp_user->get('user_address'),
				"postal_code" => $WDGuser_current->wp_user->get('user_postal_code'),
				"city" => $WDGuser_current->wp_user->get('user_city'),
				"country" => $WDGuser_current->wp_user->get('user_country'),
				"birthplace" => $WDGuser_current->wp_user->get('user_birthplace'),
				"gender" => $WDGuser_current->wp_user->get('user_gender'),
			);
			echo json_encode($return_values);
			exit();
		}
		
		//Vérifie si on crée une organisation
		if ($invest_type == "new_organisation") {
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
			$organisation = new YPOrganisation($invest_type);
			if (!$organisation->has_filled_invest_infos()) {
				$return_values = array(
					"response" => "edit_organization",
					"errors" => $organization_can_invest_errors,
					"org_name" => $organisation->get_name(),
					"org_email" => $organisation->get_email(),
					
					"org_legalform" => $organisation->get_legalform(),
					"org_idnumber" => $organisation->get_idnumber(),
					"org_rcs" => $organisation->get_rcs(),
					"org_capital" => $organisation->get_capital(),
					"org_ape" => $organisation->get_ape(),
					"org_address" => $organisation->get_address(),
					"org_postal_code" => $organisation->get_postal_code(),
					"org_city" => $organisation->get_city(),
					"org_nationality" => $organisation->get_nationality()
				);
				echo json_encode($return_values);
				exit();
			}
		}
		
		/*
		//Vérifie, selon le prestataire de paiement, que les kyc sont remplis 
		//Si Lemonway, il faut les KYC pour les paiements supérieurs à 250€, et pour un montant annuel supérieur à 2500€
		if ($campaign->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway && $invest_value > YP_LW_STRONGAUTH_MIN) {
			//Vérifie si les documents LW sont déjà envoyés
			//Si c'est au nom de la personne
			if ($invest_type == "user" && $WDGuser_current->get_lemonway_status() == LemonwayLib::$status_ready) {
				$return_values = array(
					"response" => "kyc",
					"errors" => array()
				);
				echo json_encode($return_values);
				exit();
			}
		}
		 * 
		 */
	}
	
	/**
	 * Enregistre les informations liées à l'utilisateur
	 */
	public static function save_user_infos() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$current_user = WDGUser::current();
		$gender = filter_input(INPUT_POST, 'gender');
		$firstname = filter_input(INPUT_POST, 'firstname');
		$lastname = filter_input(INPUT_POST, 'lastname');
		$birthday_day = filter_input(INPUT_POST, 'birthday_day');
		$birthday_month = filter_input(INPUT_POST, 'birthday_month');
		$birthday_year = filter_input(INPUT_POST, 'birthday_year');
		$birthplace = filter_input(INPUT_POST, 'birthplace');
		$nationality = filter_input(INPUT_POST, 'nationality');
		$address = filter_input(INPUT_POST, 'address');
		$postal_code = filter_input(INPUT_POST, 'postal_code');
		$city = filter_input(INPUT_POST, 'city');
		$country = filter_input(INPUT_POST, 'country');
		$telephone = filter_input(INPUT_POST, 'telephone');
		$current_user->save_data($gender, $firstname, $lastname, $birthday_day, $birthday_month, $birthday_year, $birthplace, $nationality, $address, $postal_code, $city, $country, $telephone);
		
		if ($current_user->has_filled_invest_infos($campaign->funding_type())) {
			WDGAjaxActions::check_invest_input();
		} else {
			global $user_can_invest_errors;
			$return_values = array(
				"response" => "edit_user",
				"errors" => $user_can_invest_errors
			);
			echo json_encode($return_values);
		}
		exit();
	}
	
	public static function save_orga_infos() {
		$invest_type = filter_input(INPUT_POST, 'invest_type');
		if ($invest_type == "new_organisation") {
			$current_user = WDGUser::current();

			global $errors_create_orga;
			$errors_create_orga = array();

			$new_orga_id = FALSE;
			$orga_capable = filter_input( INPUT_POST, 'org_capable' );
			if ($orga_capable == '1') {
				$new_orga = new YPOrganisation();
				$new_orga->set_name( filter_input( INPUT_POST, 'org_name' ) );
				$new_orga->set_email( filter_input( INPUT_POST, 'org_email' ) );
				$new_orga->set_type('society');
				$new_orga->set_legalform( filter_input( INPUT_POST, 'org_legalform' ) );
				$new_orga->set_idnumber( filter_input( INPUT_POST, 'org_idnumber' ) );
				$new_orga->set_rcs( filter_input( INPUT_POST, 'org_rcs' ) );
				$new_orga->set_capital( filter_input( INPUT_POST, 'org_capital' ) );
				$new_orga->set_ape( filter_input( INPUT_POST, 'org_ape' ) );
				$new_orga->set_address( filter_input( INPUT_POST, 'org_address' ) );
				$new_orga->set_postal_code( filter_input( INPUT_POST, 'org_postal_code' ) );
				$new_orga->set_city( filter_input( INPUT_POST, 'org_city' ) );
				$new_orga->set_nationality( filter_input( INPUT_POST, 'org_nationality' ) );
				$new_orga_id = $new_orga->create();
			} else {
				array_push($errors_create_orga, __("Merci de confirmer que vous pouvez repr&eacute;senter cette organisation.", 'yproject'));
			}

			if ($new_orga_id != FALSE) {
				$new_orga->set_creator($current_user->wp_user->ID);
				ypcf_session_start();
				$_SESSION['new_orga_just_created'] = $new_orga_id;

				WDGAjaxActions::check_invest_input();

			} else {
				global $errors_submit_new;
				$error_messages = $errors_submit_new->get_error_messages();
				foreach ($error_messages as $error_message) {
					array_push($errors_create_orga, $error_message);
				}
				$return_values = array(
					"response" => "new_organization",
					"errors" => $errors_create_orga
				);
				echo json_encode($return_values);
			}
			
		} else {
			$edit_orga = new YPOrganisation($invest_type);
			$edit_orga->set_legalform( filter_input( INPUT_POST, 'org_legalform' ) );
			$edit_orga->set_idnumber( filter_input( INPUT_POST, 'org_idnumber' ) );
			$edit_orga->set_rcs( filter_input( INPUT_POST, 'org_rcs' ) );
			$edit_orga->set_capital( filter_input( INPUT_POST, 'org_capital' ) );
			$edit_orga->set_ape( filter_input( INPUT_POST, 'org_ape' ) );
			$edit_orga->set_address( filter_input( INPUT_POST, 'org_address' ) );
			$edit_orga->set_postal_code( filter_input( INPUT_POST, 'org_postal_code' ) );
			$edit_orga->set_city( filter_input( INPUT_POST, 'org_city' ) );
			$edit_orga->set_nationality( filter_input( INPUT_POST, 'org_nationality' ) );
			$edit_orga->save();
			
			if (!$edit_orga->has_filled_invest_infos()) {
				global $organization_can_invest_errors;
				$return_values = array(
					"response" => "edit_organization",
					"errors" => $organization_can_invest_errors
				);
				echo json_encode($return_values);
				
			} else {
				WDGAjaxActions::check_invest_input();
			}
		}
		exit();
	}
	
	/**
	 * Enregistre les documents KYC liés à l'utilisateur
	 */
	public static function save_user_docs() {
		$WDGuser_current = WDGUser::current();
		$user_kyc_errors = array();
		$documents_list = array(
			'user_doc_bank'		=> WDGKYCFile::$type_bank,
			'user_doc_id'		=> WDGKYCFile::$type_id,
			'user_doc_home'		=> WDGKYCFile::$type_home
		);
		
		foreach ($documents_list as $document_key => $document_type) {
			if ( isset( $_FILES[$document_key]['tmp_name'] ) && !empty( $_FILES[$document_key]['tmp_name'] ) ) {
				$result = WDGKYCFile::add_file( $document_type, $WDGuser_current->wp_user->ID, WDGKYCFile::$owner_user, $_FILES[$document_key] );
				if ($result == 'ext') {
					array_push($user_kyc_errors, __("Le format de fichier n'est pas accept&eacute;.", 'yproject'));
				} else if ($result == 'size') {
					array_push($user_kyc_errors, __("Le fichier est trop lourd.", 'yproject'));
				}
			} else {
				array_push($user_kyc_errors, __("Le fichier n'a pas &eacute;t&eacute; renseign&eacute;.", 'yproject'));
			}
		}
		
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
	}
}