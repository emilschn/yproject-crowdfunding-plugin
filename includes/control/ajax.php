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

        //Dashboard
		WDGAjaxActions::add_action('save_project_infos');
		WDGAjaxActions::add_action('save_project_funding');
		WDGAjaxActions::add_action('save_project_communication');
		WDGAjaxActions::add_action('save_project_organisation');
		WDGAjaxActions::add_action('save_project_campaigntab');
		WDGAjaxActions::add_action('save_project_contract');
		WDGAjaxActions::add_action('save_project_status');

        WDGAjaxActions::add_action('create_contacts_table');
		WDGAjaxActions::add_action('preview_mail_message');

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

		$is_project_holder = false;
		if (filter_input(INPUT_POST, 'is_project_holder')=="1"){$is_project_holder = true;}

		if ($current_user->has_filled_invest_infos($campaign->funding_type(),$is_project_holder) &&
			filter_input(INPUT_POST, 'invest_type')!='') {
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

	/**
	 * Enregistre les informations générales du projet
	 */
	public static function save_project_infos(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$errors = array();

		//Titre du projet
		$title = sanitize_text_field(filter_input(INPUT_POST,'project_name'));
		if (!empty($title)) {
			$return = wp_update_post(array(
				'ID' => $campaign_id,
				'post_title' => $title
			));
			if ($return != $campaign_id){
				//$errors["project_name"].="Le nouveau nom du projet n'est pas valide";
				array_push($errors,"Le nouveau nom du projet n'est pas valide");
			}
		} else {
			//$errors["project_name"].="Le nom du projet ne peut pas &ecirc;tre vide";
			array_push($errors,"Le nom du projet ne peut pas &ecirc;tre vide");
		}

		//Résumé backoffice du projet
		$backoffice_summary = (filter_input(INPUT_POST,'backoffice_summary'));
		if (!empty($backoffice_summary)) {
			$campaign->__set(ATCF_Campaign::$key_backoffice_summary, $backoffice_summary);
		} else {
			array_push($errors,"Décrivez votre projet");
		}

		//Catégories du projet
		$cat_cat_id = -1; $cat_act_id = -1;
		if (isset($_POST['project_category'])) { $cat_cat_id = $_POST['project_category']; } else { $buffer = FALSE; }
		if (isset($_POST['project_activity'])) { $cat_act_id = $_POST['project_activity']; } else { $buffer = FALSE; }
		if ($cat_cat_id != -1 && $cat_act_id != -1) {
			$cat_ids = array_map( 'intval', array($cat_cat_id, $cat_act_id) );
			wp_set_object_terms($campaign_id, $cat_ids, 'download_category');
		}

		//Localisation du projet
		$location = sanitize_text_field(filter_input(INPUT_POST,'project_location'));
		if (!empty($location)) {
			update_post_meta($campaign_id, 'campaign_location', $location);
		}

		$return_values = array(
			"response" => "edit_project",
			"errors" => $errors
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

		//Update required amount
		$new_minimum_goal = intval(sanitize_text_field(filter_input(INPUT_POST, 'minimum_goal')));
		$new_maximum_goal = intval(sanitize_text_field(filter_input(INPUT_POST, 'maximum_goal')));
		if($new_minimum_goal > $new_maximum_goal){
			array_push($errors, "Le montant maximum ne peut &ecirc;tre inf&eacute;rieur au montant minimum");
		} else if($new_minimum_goal<0 || $new_maximum_goal<0) {
			array_push($errors, "Les montants doivent &ecirc;tre positifs");
		} else {
			update_post_meta($campaign_id, 'campaign_minimum_goal', $new_minimum_goal);
			update_post_meta($campaign_id, 'campaign_goal', $new_maximum_goal);
		}

		//Update funding duration
		$new_duration = intval(sanitize_text_field(filter_input(INPUT_POST, 'funding_duration')));
		if($new_duration>=1){
			update_post_meta($campaign_id, ATCF_Campaign::$key_funding_duration, $new_duration);
		} else {
			array_push($errors, "Le financement doit au moins durer une ann&eacute;e");
		}

		//Update roi_percent_estimated duration
		$new_roi_percent = round(floatval(sanitize_text_field(filter_input(INPUT_POST, 'roi_percent_estimated'))),2);
		if($new_roi_percent>=0){
			update_post_meta($campaign_id, ATCF_Campaign::$key_roi_percent_estimated, $new_roi_percent);
		} else {
			array_push($errors, "Le pourcentage de CA reversé doit être positif");
		}

		//Update first_payment_date
		try {
			$new_first_payment_date = new DateTime(sanitize_text_field(filter_input(INPUT_POST, 'first_payment_date')));
			update_post_meta($campaign_id, ATCF_Campaign::$key_first_payment_date, date_format($new_first_payment_date, 'Y-m-d H:i:s'));
		} catch (Exception $e) {
			array_push($errors, "La date est invalide");
		}

		//Update list of estimated turnover
		$new_turnover_list = filter_input(INPUT_POST, 'list_turnover');
		$turnover_list = json_decode($new_turnover_list);
		$sanitized_list = array();
		foreach ($turnover_list as $key => $value){
			$sanitized_list[strval(intval($key))] = strval(intval($value));
		}

 		if(json_last_error() == JSON_ERROR_NONE){
			$campaign->__set(ATCF_Campaign::$key_estimated_turnover,json_encode($sanitized_list));
		}


		$return_values = array(
			"response" => "edit_funding",
			"errors" => $errors
		);
		echo json_encode($return_values);
		exit();
	}

	/**
	 * Enregistre les informations de l'organisation liée à un projet
	 */
	public static function save_project_organisation(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');

		//Récupération de l'ancienne organisation
		$api_project_id = BoppLibHelpers::get_api_project_id(intval($campaign_id));
		$current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
		$current_organisation = FALSE;
		if (count($current_organisations) > 0) {
			$current_organisation = $current_organisations[0];
		}

		$delete = FALSE;
		$update = FALSE;

		//On met à jour : si une nouvelle organisation est renseignée et différente de celle d'avant
		//On supprime : si la nouvelle organisation renseignée est différente de celle d'avant
		if (!empty(filter_input(INPUT_POST, 'project-organisation'))) {
			$organisation_selected = new YPOrganisation(filter_input(INPUT_POST, 'project-organisation'));
			if ($current_organisation === FALSE || $current_organisation->organisation_wpref != $organisation_selected->get_wpref()) {
				$update = TRUE;
				if ($current_organisation !== FALSE) {
					$delete = TRUE;
				}
			}

			//On supprime : si rien n'est sélectionné + il y avait quelque chose avant
		} else {
			if ($current_organisation !== FALSE) {
				$delete = TRUE;
			}
		}

		if ($delete) {
			BoppLib::unlink_organisation_from_project($api_project_id, $current_organisation->id);
		}

		if ($update) {
			$api_organisation_id = $organisation_selected->get_bopp_id();
			BoppLib::link_organisation_to_project($api_project_id, $api_organisation_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
		}

		$return_values = array(
			"response" => "edit_organisation",
			"errors" => array()
		);
		echo json_encode($return_values);
		exit();
	}

	/**
	 * Enregistre les informations de communication du projet
	 */
	public static function save_project_communication(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);

		$campaign->__set(ATCF_Campaign::$key_external_website, (sanitize_text_field(filter_input(INPUT_POST, 'website'))));
		$campaign->__set(ATCF_Campaign::$key_facebook_name, (sanitize_text_field(filter_input(INPUT_POST, 'facebook'))));
		$campaign->__set(ATCF_Campaign::$key_twitter_name, (sanitize_text_field(filter_input(INPUT_POST, 'twitter'))));

		$return_values = array(
			"response" => "edit_communication",
			"errors" => array()
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

		$new_gdoc_url = sanitize_text_field(filter_input(INPUT_POST, 'google_doc'));
        if(strpos($new_gdoc_url,"https://docs.google.com/document/d/")===0){
            $campaign->__set(ATCF_Campaign::$key_google_doc, $new_gdoc_url);
        } else if (!empty($new_logbook_gdoc_url)) {
            array_push($errors, "L'URL du planning est invalide ");
        }

        $new_logbook_gdoc_url = sanitize_text_field(filter_input(INPUT_POST, 'logbook_google_doc'));
        if(strpos($new_logbook_gdoc_url,"https://docs.google.com/document/d/")===0){
            $campaign->__set(ATCF_Campaign::$key_logbook_google_doc, $new_logbook_gdoc_url);
        } else if (!empty($new_logbook_gdoc_url)) {
            array_push($errors, "L'URL du journal de bord est invalide ");
        }

		if(!empty(filter_input(INPUT_POST, 'end_vote_date'))){
			try {
				$new_end_vote_date = new DateTime(sanitize_text_field(filter_input(INPUT_POST, 'end_vote_date')));
				$campaign->set_end_vote_date($new_end_vote_date);
			} catch (Exception $e) {
				array_push($errors, "La date est invalide");
			}
		} else {
			array_push($errors, "Il faut une date de fin de vote !");
		}

		if(!empty(filter_input(INPUT_POST, 'end_collecte_date'))){
			try {
				$new_end_collecte_date = new DateTime(sanitize_text_field(filter_input(INPUT_POST, 'end_collecte_date')));
				$campaign->set_end_date($new_end_collecte_date);
			} catch (Exception $e) {
				array_push($errors, "La date est invalide");
			}
		} else {
			array_push($errors, "Il faut une date de fin de collecte !");
		}

		$return_values = array(
			"response" => "save_project_campaigntab",
			"errors" => $errors
		);
		echo json_encode($return_values);
		exit();
	}

	/**
	 * Enregistre les informations de l'onglet "contractualisation" du projet
	 */
	public static function save_project_contract(){
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);

		$campaign->__set(ATCF_Campaign::$key_contract_doc_url, (sanitize_text_field(filter_input(INPUT_POST, 'contract_url'))));

		$return_values = array(
			"response" => "edit_contract",
			"errors" => array()
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

		$new_status = (sanitize_text_field(filter_input(INPUT_POST, 'campaign_status')));
		$campaign->set_status($new_status);

		$new_validation_status = (sanitize_text_field(filter_input(INPUT_POST, 'can_go_next')));
		$campaign->set_validation_next_status($new_validation_status);

		$return_values = array(
			"response" => "edit_status",
			"errors" => $errors
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
        $current_wdg_user = WDGUser::current();
        require_once("country_list.php");
        global $country_list;
		global $wpdb;
		$table_vote = $wpdb->prefix . "ypcf_project_votes";
		$table_jcrois = $wpdb->prefix . "jycrois";

		//Données suiveurs
		$list_user_follow = $wpdb->get_col( "SELECT user_id FROM ".$table_jcrois." WHERE subscribe_news = 1 AND campaign_id = ".$campaign_id);

		//Données d'investissement
		$investments_list = (json_decode(filter_input(INPUT_POST, 'data'),true));

		//Données de vote
		$list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum, date, validate_project, advice FROM ".$table_vote." WHERE post_id = ".$campaign_id );


        /******************Lignes du tableau*********************/
        $array_contacts = array();

		//Extraction infos suiveurs
		foreach ( $list_user_follow as $item_follow ) {
			$array_contacts[$item_follow]["follow"]=1;
		}

        //Extraction infos de vote
        foreach ( $list_user_voters as $item_vote ) {
            $u_id = $item_vote->user_id;
            $array_contacts[$u_id]["vote"]=1;
            $array_contacts[$u_id]["vote_date"]=$item_vote->date;


            $array_contacts[$u_id]["vote_advice"]='<i class="infobutton fa fa-comment" aria-hidden="true"></i><div class="tooltiptext">'.$item_vote->advice.'</div>';

            switch ($item_vote->validate_project) {
                case '1':
                    $array_contacts[$u_id]["vote_validate"]="Oui";
                    $array_contacts[$u_id]["vote_invest_sum"]=$item_vote->invest_sum;
                    break;
                case '0' :
                default :
                    $array_contacts[$u_id]["vote_validate"]="Non";
                    $array_contacts[$u_id]["vote_invest_sum"]="";
                    break;
            }
        }

        //Extraction infos d'investissements
        foreach ( $investments_list['payments_data'] as $item_invest ) {
            $post_invest = get_post($item_invest['ID']);
            $mangopay_id = edd_get_payment_key($item_invest['ID']);

            $u_id = $item_invest['user'];

            $payment_type = 'Carte';
            if (strpos($mangopay_id, 'wire_') !== FALSE) {
                $payment_type = 'Virement';
            } else if ($mangopay_id == 'check') {
                $payment_type = 'Ch&egrave;que';
            }

            $investment_state = 'Validé';
            if ($campaign->campaign_status() == ATCF_Campaign::$campaign_status_archive || $campaign->campaign_status() == ATCF_Campaign::$campaign_status_preparing) {
                $investment_state = 'Annulé';

                $refund_id = get_post_meta($item_invest['ID'], 'refund_id', TRUE);
                if (isset($refund_id) && !empty($refund_id)) {
                    $refund_obj = ypcf_mangopay_get_refund_by_id($refund_id);
                    $investment_state = 'Remboursement en cours';
                    if ($refund_obj->IsCompleted) {
                        if ($refund_obj->IsSucceeded) {
                            $investment_state = 'Remboursé';
                        } else {
                            $investment_state = 'Remboursement échoué';
                        }
                    }

                } else {
                    $refund_id = get_post_meta($item_invest['ID'], 'refund_wire_id', TRUE);
                    if (isset($refund_id) && !empty($refund_id)) {
                        $investment_state = 'Remboursé';
                    }
                }
            }

            $page_dashboard = get_page_by_path('tableau-de-bord');
            $campaign_id_param = '?campaign_id=' . $campaign->ID;
            $payment_state = edd_get_payment_status( $post_invest, true );
            if ($payment_state == "En attente" && $current_wdg_user->is_admin()) {
                $payment_state .= '<br /><a href="' .get_permalink($page_dashboard->ID) . $campaign_id_param. '&approve_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Confirmer]</a>';
                $payment_state .= '<br /><br /><a href="' .get_permalink($page_dashboard->ID) . $campaign_id_param. '&cancel_payment='.$item_invest['ID'].'" style="font-size: 10pt;">[Annuler]</a>';
            }
            $array_contacts[$u_id]["invest"] = 1;

            $array_contacts[$u_id]["invest_payment_type"] = $payment_type;
			$array_contacts[$u_id]["invest_payment_state"] = $investment_state;
			$array_contacts[$u_id]["invest_state"] = $payment_state;
            $array_contacts[$u_id]["invest_amount"] = $item_invest['amount'];
            $array_contacts[$u_id]["invest_date"] = date_i18n( 'Y-m-d', strtotime( get_post_field( 'post_date', $item_invest['ID'] ) ) );
            $array_contacts[$u_id]["invest_sign"] = $item_invest['signsquid_status_text'];
        }

        //Extraction infos utilisateur
        foreach ( $array_contacts as $user_id=>$user_item ){
            //Données si l'investisseur est une organisation
			$array_contacts[$user_id]["user_id"]= $user_id;

            if(YPOrganisation::is_user_organisation($user_id)){
                $orga = new YPOrganisation($user_id);
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

					//Infos supplémentaires pour les investisseurs
					if($array_contacts[$user_id]["invest"] == 1){
						$array_contacts[$user_id]["user_address"] = $orga->get_address();
						$array_contacts[$user_id]["user_country"] = ucfirst(strtolower($orga->get_nationality()));
						$array_contacts[$user_id]["user_mobile_phone"] = $orga_creator->get('user_mobile_phone');
						$array_contacts[$user_id]["user_orga_id"] = $orga->get_rcs() .' ('.$orga->get_idnumber().')';
					}
				}

            }
            //Données si l'investisseur est un utilisateur normal
            else {
                $user_data = get_userdata($user_id);

                $array_contacts[$user_id]["user_link"] = bp_core_get_userlink($user_id);
                $array_contacts[$user_id]["user_email"] = $user_data->get('user_email');

				//Infos supplémentaires pour les votants
				if($array_contacts[$user_id]["vote"] == 1 || $array_contacts[$user_id]["invest"] == 1){
					$array_contacts[$user_id]["user_last_name"] = $user_data->last_name;
					$array_contacts[$user_id]["user_first_name"] = $user_data->first_name;
					$array_contacts[$user_id]["user_city"] = $user_data->get('user_city');
					$array_contacts[$user_id]["user_postal_code"] = $user_data->get('user_postal_code');
					$array_contacts[$user_id]["user_nationality"] = ucfirst(strtolower($country_list[$user_data->get('user_nationality')]));

					//Infos supplémentaires pour les investisseurs
					if($array_contacts[$user_id]["invest"] == 1){
						$array_contacts[$user_id]["user_birthday"] = $user_data->user_birthday_year.'-'.$user_data->user_birthday_month.'-'.$user_data->user_birthday_day;
						$array_contacts[$user_id]["user_birthplace"] = $user_data->get('user_birthplace');
						$array_contacts[$user_id]["user_address"] = $user_data->user_address;
						$array_contacts[$user_id]["user_country"] = $user_data->user_country;
						$array_contacts[$user_id]["user_mobile_phone"] = $user_data->get('user_mobile_phone');
					}
				}
            }
        }

        /*********Intitulés et paramètres des colonnes***********/
        $status = $campaign->campaign_status();
        $display_invest_infos = false;
        if($status == ATCF_Campaign::$campaign_status_collecte ||
            $status == ATCF_Campaign::$campaign_status_funded ||
            $status == ATCF_Campaign::$campaign_status_archive
        ){
            $display_invest_infos = true;
        }

        $display_vote_infos = true;
        if($status == ATCF_Campaign::$campaign_status_collecte ||
            $status == ATCF_Campaign::$campaign_status_funded ||
            $status == ATCF_Campaign::$campaign_status_archive
        ){
            $display_vote_infos = false;
        }

        $array_columns = array(
        	new ContactColumn('checkbox','',true,"none"),
            new ContactColumn('user_link', 'Utilisateur', true),
			new ContactColumn('follow','',true,"check"),
			new ContactColumn('vote','',true,"check"),
            new ContactColumn('invest','',true,"check"),
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

            new ContactColumn('vote_date','Date de vote',$display_vote_infos, "date"),
            new ContactColumn('vote_validate','A valid&eacute;',true),
            new ContactColumn('vote_invest_sum','Intention d\'inv.',true, "range"),
			new ContactColumn('vote_advice','Conseil',$display_vote_infos),

			new ContactColumn('invest_amount', 'Montant investi', $display_invest_infos, "range"),
            new ContactColumn('invest_date', 'Date d\'inv.', $display_invest_infos, "date"),
            new ContactColumn('invest_payment_type', 'Type de paiement', $display_invest_infos),
            new ContactColumn('invest_payment_state', 'Etat du paiement', $display_invest_infos),
            new ContactColumn('invest_sign', 'Signature', false),
            new ContactColumn('invest_state', 'Investissement', $display_invest_infos),
        );

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
            <?php foreach($array_contacts as $id_contact=>$data_contact) { ?>
                <tr data-DT_RowId="<?php echo $id_contact; ?>">
                <?php foreach($array_columns as $column) {
                	echo "<td>";
					if($column->columnData == "follow" && $data_contact[$column->columnData]==1){
						?><div class="dirty-hide">1</div><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/good.png" alt="suit" title="Suit le projet" /><?php

					} else if($column->columnData == "vote" && $data_contact[$column->columnData]==1){
						?><div class="dirty-hide">1</div><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/goodvote.png" alt="vote" title="A voté" /><?php

					} else if ($column->columnData == "invest" && $data_contact[$column->columnData]==1){
						?><div class="dirty-hide">1</div><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/goodmains.png" alt="investi" title="A investi" /><?php
					} else {
						echo $data_contact[$column->columnData];
					}
					echo "</td>";
                }?>
                </tr>
            <?php }?>
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
								echo '<input type="text" placeholder="Filtrer " data-index="'.$i.'"/><br/>'.$column->columnName;
								break;
							case "check":
								echo '<input type="checkbox" data-index="'.$i.'"/>';
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

        //Colonnes à afficher par défaut
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

	public static function preview_mail_message(){
		$campaign_id = filter_input(INPUT_POST, 'id_campaign');
		$errors = array();

		$title = sanitize_text_field(filter_input(INPUT_POST, 'mail_title'));
		if (empty($title)){
			$errors[]= "L'objet du mail ne peut être vide";
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
}

/**
 * Class ContactColumn utilisé pour les colonnes du tableau de contacts
 */
class ContactColumn {
    public $columnData;
    public $columnName;
    public $defaultDisplay = false;
	public $filterClass = "text";

    function ContactColumn ($newColumnData, $newColumnName, $newDefaultDisplay=false, $newFilterClass = "text") {
        $this->columnData = $newColumnData;
        $this->columnName = $newColumnName;
        $this->defaultDisplay = $newDefaultDisplay;
		$this->filterClass = $newFilterClass;
    }
}