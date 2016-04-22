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
			$mp_wallet_campaign_id = ypcf_mangopay_get_mp_campaign_wallet_id($campaign_id);
			$mp_wallet_campaign_infos = ypcf_mangopay_get_wallet_by_id($mp_wallet_campaign_id);
			$campaign_organisation = $campaign->get_organisation();
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
			exit();
		}
	}
	
	/**
	 * Vérifie le passage à l'étape suivante pour les utilisateurs lors de l'investissement
	 */
	public static function check_invest_input() {
		$campaign_id = filter_input(INPUT_POST, 'campaign_id');
		$campaign = new ATCF_Campaign($campaign_id);
		$invest_value = filter_input(INPUT_POST, 'invest_value');
		$invest_type = filter_input(INPUT_POST, 'invest_type');
		$current_user = WDGUser::current();
		
		//Dans tous les cas, vérifie que l'utilisateur a rempli ses infos pour investir
		if (!$current_user->has_filled_invest_infos( $campaign->funding_type() )) {
			global $user_can_invest_errors;
			$return_values = array(
				"response" => "edit_user",
				"errors" => $user_can_invest_errors,
				"firstname" => $current_user->wp_user->user_firstname,
				"lastname" => $current_user->wp_user->user_lastname,
				"email" => $current_user->wp_user->user_email,
				"nationality" => $current_user->wp_user->get('user_nationality'),
				"birthday_day" => $current_user->wp_user->get('user_birthday_day'),
				"birthday_month" => $current_user->wp_user->get('user_birthday_month'),
				"birthday_year" => $current_user->wp_user->get('user_birthday_year'),
				"address" => $current_user->wp_user->get('user_address'),
				"postal_code" => $current_user->wp_user->get('user_postal_code'),
				"city" => $current_user->wp_user->get('user_city'),
				"country" => $current_user->wp_user->get('user_country'),
				"birthplace" => $current_user->wp_user->get('user_birthplace'),
				"gender" => $current_user->wp_user->get('user_gender'),
			);
			echo json_encode($return_values);
			exit();
		}
		
		//Vérifie si on crée une organisation
		if ($invest_type == "new_orga") {
			$return_values = array(
				"response" => "create_orga",
			);
			echo json_encode($return_values);
			exit();
			
		//Vérifie si on veut investir en tant qu'organisation (différent de user)
		} else if ($invest_type != "user") {
			//Vérifie si les informations de l'organisation sont bien remplies
			//TODO
		}
		
		//Vérifie, selon le prestataire de paiement, que les kyc sont remplis 
		if ($campaign->get_payment_provider() == 'lemonway' && $invest_value > YP_LW_STRONGAUTH_MIN) {
			//Vérifie si les documents LW sont déjà envoyés
			
		} else if ($campaign->get_payment_provider() == 'mangopay' && $invest_value > YP_STRONGAUTH_AMOUNT_LIMIT) {
			//Vérifie si les documents MP sont déjà envoyés
			
		}
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
}