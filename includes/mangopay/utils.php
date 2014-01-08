<?php
/**
 * 
 * @param type $user_id
 * @param type $campaign_id
 * @param type $amount
 * @param type $page_return
 * @return type
 */
function ypcf_mangopay_contribution_user_to_project($current_user, $campaign_id, $amount, $page_return) {
    //Récupération du walletid de la campagne
    $post_camp = get_post($campaign_id);
    $campaign = atcf_get_campaign( $post_camp );
    $currentpost_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($campaign->ID);

    //Récupération du walletid de l'utilisateur
    $currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($current_user->ID);
    
    if ($currentpost_mangopayid == '' || $currentuser_mangopayid == '') return '';

    //Conversion de la somme saisie en cents
    $cent_amount = $amount * 100;

    //Récupération de l'url de retour
    $return_url = get_permalink($page_return->ID) . '?campaign_id=' . $campaign_id;
    
    //Récupération de l'url de template
    $page_payment = get_page_by_path('paiement');
    $template_url = get_permalink($page_payment->ID) . '?campaign_id='.$campaign_id;
    $template_url = 'https://www.wedogood.co/paiement?campaign_id='.$campaign_id;
    
    //Création de la contribution en elle-même
    $mangopay_newcontribution = request('contributions', 'POST', '{ 
					    "UserID" : '.$currentuser_mangopayid.', 
					    "WalletID" : '.$currentpost_mangopayid.',
					    "Amount" : '.$cent_amount.',
					    "ReturnURL" : "'. $return_url .'",
					    "TemplateURL" : "'.$template_url.'"
					}');
    
    return $mangopay_newcontribution;
}

function ypcf_mangopay_transfer_project_to_user($current_user, $campaign_id, $amount) {
    //Récupération du walletid de la campagne
    $post_camp = get_post($campaign_id);
    $campaign = atcf_get_campaign( $post_camp );
    $campaign_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($campaign->ID);
    
    //Récupération de l'id du porteur de projet
    $author_id = $campaign->data->post_author;
    $current_user = get_userdata($author_id);
    $campaign_author_mangopayid = ypcf_mangopay_get_mp_user_id($author_id);

    //Récupération du walletid de l'utilisateur
    $currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($current_user->ID);
    
    $cent_amount = $amount * 100;
    
    //Création du transfer
    $mangopay_newtransfer = request('transfers', 'POST', '{ 
					    "PayerID" : '.$campaign_author_mangopayid.', 
					    "PayerWalletID" : '.$campaign_mangopayid.',
					    "BeneficiaryID" : '.$currentuser_mangopayid.',
					    "BeneficiaryWalletID" : 0,
					    "Amount" : '.$cent_amount.'
					}');
    
    return $mangopay_newtransfer;
}

/**
 * 
 * @param type $contribution_id
 * @return type
 */
function ypcf_mangopay_get_contribution_by_id($contribution_id) {
    return request('contributions/'.$contribution_id, 'GET');
}

function ypcf_mangopay_get_user_by_id($mp_user_id) {
    return request('users/'.$mp_user_id, 'GET');
}

function ypcf_mangopay_get_user_strong_authentication($mp_user_id) {
    return request('users/'.$mp_user_id.'/strongAuthentication', 'GET');
}

function ypcf_mangopay_get_beneficiary_by_id($mp_beneficiary_id) {
    return request('beneficiaries/'.$mp_beneficiary_id, 'GET');
}

function ypcf_mangopay_get_withdrawal_by_id($mp_withdrawal_id) {
    return request('withdrawals/'.$mp_withdrawal_id, 'GET');
}

function ypcf_mangopay_set_user_strong_authentication_doc_transmitted($wp_user_id, $status = true) {
    $mp_user_id = ypcf_mangopay_get_mp_user_id($wp_user_id);
    $value = ($status) ? 'true' : 'false';
    request('users/'.$mp_user_id .'/strongAuthentication', 'PUT', '{"IsDocumentsTransmitted": '.$value.'}');
}

function ypcf_mangopay_is_user_strong_authenticated($wp_user_id) {
    $mp_user_id = ypcf_mangopay_get_mp_user_id($wp_user_id);
    $authentication_object = ypcf_mangopay_get_user_strong_authentication($mp_user_id);
    $buffer = false;
    if ($authentication_object) $buffer = ($authentication_object->IsDocumentsTransmitted && $authentication_object->IsCompleted && $authentication_object->IsSucceeded);
    return $buffer;
}

function ypcf_mangopay_is_user_strong_authentication_sent($wp_user_id) {
    $mp_user_id = ypcf_mangopay_get_mp_user_id($wp_user_id);
    $authentication_object = ypcf_mangopay_get_user_strong_authentication($mp_user_id);
    $buffer = false;
    if ($authentication_object) $buffer = $authentication_object->IsDocumentsTransmitted;
    return $buffer;
}

function ypcf_mangopay_send_strong_authentication($url_request) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
    curl_setopt($ch, CURLOPT_URL, $url_request);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $authorized_mime_type = array('image/jpeg', 'image/pjpeg', 'image/gif', 'image/png', 'application/pdf'); 
    if (!in_array($_FILES['StrongValidationDtoPicture']['type'], $authorized_mime_type)) { return false; } 
    $mime_type_text = ';type='.$_FILES['StrongValidationDtoPicture']['type'];
    $post = array('StrongValidationDto.Picture' => '@' . $_FILES['StrongValidationDtoPicture']['tmp_name'] . $mime_type_text);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200 || curl_getinfo($ch, CURLINFO_HTTP_CODE) == 0) $result = TRUE;
    else $result = FALSE;
    curl_close($ch);
    return $result;
}

function ypcf_mangopay_get_wallet_by_id($wallet_id) {
    return request('wallets/'.$wallet_id, 'GET');
}

function ypcf_mangopay_get_user_personalamount_by_wpid($wp_user_id) {
    $mp_user_id = ypcf_mangopay_get_mp_user_id($wp_user_id);
    $mp_user = (isset($mp_user_id) && $mp_user_id != "") ? ypcf_mangopay_get_user_by_id($mp_user_id) : false;
    if ($mp_user) return $mp_user->PersonalWalletAmount;
    else return 0;
}

function ypcf_mangopay_get_userwallet_personalamount_by_wpid($wp_user_id) {
    $mp_wallet_id = ypcf_mangopay_get_mp_user_wallet_id($wp_user_id);
    $mp_wallet = (isset($mp_wallet_id) && $mp_wallet_id != "") ? ypcf_mangopay_get_wallet_by_id($mp_wallet_id) : false;
    if ($mp_wallet) return $mp_wallet->CollectedAmount;
    else return 0;
}

function ypcf_mangopay_get_mp_user_id($wp_user_id) {
    return get_user_meta($wp_user_id, 'mangopay_user_id', true);
}

function ypcf_mangopay_set_mp_user_id($wp_user_id, $mp_user_id) {
    update_user_meta($wp_user_id, 'mangopay_user_id', $mp_user_id);
}

function ypcf_mangopay_get_mp_user_wallet_id($wp_user_id) {
    return get_user_meta($wp_user_id, 'mangopay_wallet_id', true);
}

function ypcf_mangopay_set_mp_user_wallet_id($wp_user_id, $mp_user_wallet_id) {
    update_user_meta($wp_user_id, 'mangopay_wallet_id', $mp_user_wallet_id);
}

function ypcf_mangopay_get_mp_user_beneficiary_id($wp_user_id) {
    return get_user_meta($wp_user_id, 'mangopay_beneficiary_id', true);
}

function ypcf_mangopay_set_mp_user_beneficiary_id($wp_user_id, $mp_user_beneficiary_id) {
    update_user_meta($wp_user_id, 'mangopay_beneficiary_id', $mp_user_beneficiary_id);
}

function ypcf_mangopay_get_mp_campaign_wallet_id($wp_campaign_id) {
    return get_post_meta($wp_campaign_id, 'mangopay_wallet_id', true);
}

function ypcf_mangopay_set_mp_campaign_wallet_id($wp_campaign_id, $mp_campaign_wallet_id) {
    update_post_meta($wp_campaign_id, 'mangopay_wallet_id', $mp_campaign_wallet_id);
}

/**
 * Vérifie que l'utilisateur connecté a une correspondance dans mangopay pour un id utilisateur et un id porte-monnaie
 * @param type $current_user
 * @return type
 */
function ypcf_init_mangopay_user($current_user) {
    ypcf_debug_log('-------------');
    ypcf_debug_log('ypcf_init_mangopay_user : $current_user->ID = ' . $current_user->ID);
    //On s'apprête à confirmer, donc on vérifie si le currentuser a un compte sur mangopay. Si il n'en a pas, on le crée directement.
    $currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($current_user->ID);
    ypcf_debug_log('ypcf_init_mangopay_user >> $currentuser_mangopayid = ' . $currentuser_mangopayid);
    if ($currentuser_mangopayid == "") {
	$mangopay_new_user = request('users', 'POST', '{ 
				    "FirstName" : "'.$current_user->user_firstname.'", 
				    "LastName" : "'.$current_user->user_lastname.'", 
				    "Email" : "'.$current_user->user_email.'", 
				    "Nationality" : "'.$current_user->get('user_nationality').'", 
				    "Birthday" : '.strtotime($current_user->get('user_birthday_year') . "-" . $current_user->get('user_birthday_month') . "-" . $current_user->get('user_birthday_day')).', 
				    "IP" : "'.$_SERVER['REMOTE_ADDR'].'",
				    "PersonType" : "'.$current_user->get('user_person_type').'",
				    "Tag" : "'.$current_user->user_login.'"
				}');
	if (isset($mangopay_new_user->ID)) {
	    ypcf_debug_log('ypcf_init_mangopay_user --->> $mangopay_new_user->ID = ' . $mangopay_new_user->ID);
	    $currentuser_mangopayid = $mangopay_new_user->ID;
	    ypcf_mangopay_set_mp_user_id($current_user->ID, $currentuser_mangopayid);
	} else {
	    ypcf_debug_log('ypcf_init_mangopay_user --->> $mangopay_new_user creation failed');
	}
    }
    //De même, on vérifie si le currentuser a un wallet sur mangopay. Si il n'en a pas, on le crée.
    $currentuser_wallet_mangopayid = ypcf_mangopay_get_mp_user_wallet_id($current_user->ID);
    ypcf_debug_log('ypcf_init_mangopay_user >> $currentuser_wallet_mangopayid = ' . $currentuser_wallet_mangopayid);
    if ($currentuser_wallet_mangopayid == "") {
	$mangopay_new_wallet = request('wallets', 'POST', '{ 
					"Owners" : ['.$currentuser_mangopayid.'], 
					"Name" : "Wallet of '.$current_user->display_name.'",
					"Tag" : "Wallet of '.$current_user->display_name.'",
					"Description" : "Wallet of '.$current_user->display_name.'"
				    }');
	if (isset($mangopay_new_wallet->ID)) {
	    ypcf_debug_log('ypcf_init_mangopay_user --->> $mangopay_new_wallet->ID = ' . $mangopay_new_wallet->ID);
	    ypcf_mangopay_set_mp_user_wallet_id($current_user->ID, $mangopay_new_wallet->ID);
	} else {
	    ypcf_debug_log('ypcf_init_mangopay_user --->> $mangopay_new_wallet creation failed');
	}
    }
    /*//On crée un deuxième porte-monnaie d'investissement pour l'utilisateur
    $currentuser_invest_wallet_mangopayid = get_user_meta($current_user->ID, 'mangopay_invest_wallet_id', true);
    if ($currentuser_invest_wallet_mangopayid == "") {
	$mangopay_new_invest_wallet = request('wallets', 'POST', '{ 
					"Owners" : ['.$currentuser_mangopayid.'.], 
					"Name" : "Wallet of '.$current_user->display_name.'",
					"Tag" : "Wallet of '.$current_user->display_name.'",
					"Description" : "Wallet of '.$current_user->display_name.'"
				    }');
	if (isset($mangopay_new_invest_wallet->ID)) update_user_meta($current_user->ID, 'mangopay_invest_wallet_id', $mangopay_new_invest_wallet->ID);
    }*/
    return $currentuser_mangopayid;
}

/**
 * Initialise le besoin d'identification utilisateur
 * @param type $current_user
 */
function ypcf_init_mangopay_user_strongauthentification($current_user) {
    $currentuser_mangopayid = ypcf_init_mangopay_user($current_user);
    $authentication_object = ypcf_mangopay_get_user_strong_authentication($currentuser_mangopayid);
    if (!$authentication_object) {
	$authentication_object = request('users/'.$currentuser_mangopayid.'/strongAuthentication', 'POST', '{}');
    }
    return $authentication_object->UrlRequest;
}

/**
 * Initialise le créateur du projet sur mangopay si nécessaire puis le porte-monnaie du projet.
 */
function ypcf_init_mangopay_project() {
    $currentpost_mangopayid = '';
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	
	$currentpost_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($campaign->ID);
	//Si le projet n'existe pas encore
	if ($currentpost_mangopayid == "") {
	    //On va chercher l'identifiant mangopay du porteur de projet
	    $author_id = $campaign->data->post_author;
	    $current_user = get_userdata($author_id);
	    $mangopay_new_user_id = ypcf_init_mangopay_user($current_user);
	    
	    //On crée le poret-monnaie du projet
	    if ($mangopay_new_user_id != "") {
		$mangopay_new_wallet = request('wallets', 'POST', '{ 
					    "Owners" : ['.$mangopay_new_user_id.'], 
					    "Name" : "Wallet for '.$campaign->data->post_title.'",
					    "Tag" : "Wallet for '.$campaign->data->post_title.'",
					    "Description" : "Wallet for '.$campaign->data->post_title.'",
					    "RaisingGoalAmount" : '.$campaign->goal(false).'
					}');
		if (isset($mangopay_new_wallet->ID)) {
		    ypcf_mangopay_set_mp_campaign_wallet_id($campaign->ID, $mangopay_new_wallet->ID);
		    $currentpost_mangopayid = $mangopay_new_wallet->ID;
		}
	    }
	}
    }
    return $currentpost_mangopayid;
}

function ypcf_init_mangopay_beneficiary($wp_user_id, $bank_owner_name, $bank_owner_address, $bank_iban, $bank_bic) {
    $currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($wp_user_id);
    $beneficiary_id = "";
    if ($currentuser_mangopayid != "") {
	$beneficiary_id = ypcf_mangopay_get_mp_user_beneficiary_id($wp_user_id);
	if ($beneficiary_id == "") {
	    $mangopay_new_beneficiary = request('beneficiaries', 'POST', '{ 
					"BankAccountOwnerName" : "'.$bank_owner_name.'", 
					"BankAccountOwnerAddress" : "'.$bank_owner_address.'",
					"BankAccountIBAN" : "'.$bank_iban.'",
					"BankAccountBIC" : "'.$bank_bic.'",
					"UserID" : '.$currentuser_mangopayid.'
				    }');
	    //Si on reçoit une chaine : il y a eu une erreur
	    if (isset($mangopay_new_beneficiary->ErrorCode) && $mangopay_new_beneficiary->ErrorCode != 0) {
		global $mp_errors;
		$mp_errors = $mangopay_new_beneficiary->UserMessage;
		
	    } else {
		ypcf_mangopay_set_mp_user_beneficiary_id($wp_user_id, $mangopay_new_beneficiary->ID);
		$beneficiary_id = $mangopay_new_beneficiary->ID;
	    }
	}
    }
    return $beneficiary_id;
}

function ypcf_refund_wallet_project_to_wallet_contributors($campaign_id) {
    //Utiliser les données stockées par edd
    // - plus optimisé (plutôt que multiplier les appels vers mangopay)
    // - récupération de l'id de la campagne, du wallet correspondant, de la liste des backers et de leur wallet
    // - transfert wallet-campagne > wallet-user
    if (isset($campaign_id)) {
	$campaign_wallet_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($campaign_id);
	
	$post = get_post($campaign_id);
	$campaign = atcf_get_campaign( $post );
	$backers_list = $campaign->backers();
	
	foreach ($backers_list as $backer) {
	    $contributor_id = $backer->post_author;
	    $contributor_user_mangopayid = ypcf_mangopay_get_mp_user_id($contributor_id);
	    
	    $payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
	    $payment    = get_post( $payment_id );
	    if ( !empty( $payment ) ) {
		$cart_items = edd_get_payment_meta_cart_details( $payment_id );
		foreach ( $cart_items as $item ) $amount = $item[ 'quantity' ];
		ypcf_mangopay_make_transfer($campaign_wallet_mangopayid, $contributor_user_mangopayid, $amount);
	    }
	}
    }
}

function ypcf_mangopay_make_transfer($mp_payer_id, $mp_beneficiary_id, $amount) {
    return request('transfers', 'POST', '{ 
					"Amount" : '.$amount.', 
					"PayerID" : '.$mp_payer_id.', 
					"BeneficiaryID" : '.$mp_beneficiary_id.',
					"Tag" : "'.$mp_payer_id.' -> '.$mp_beneficiary_id.' = '.$amount.'"
				    }');
}

function ypcf_mangopay_make_withdrawal($wp_user_id, $mp_beneficiary_id, $mp_amount) {
    $mp_user_id = ypcf_mangopay_get_mp_user_id($wp_user_id);
    return request('withdrawals', 'POST', '{ 
					"Amount" : '.$mp_amount.', 
					"UserID" : '.$mp_user_id.', 
					"BeneficiaryID" : '.$mp_beneficiary_id.',
					"Tag" : "'.$mp_user_id.' -> '.$mp_beneficiary_id.' = '.$mp_amount.'"
				    }');
}
?>