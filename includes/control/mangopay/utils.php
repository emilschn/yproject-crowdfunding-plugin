<?php
/**
 * Gestion des contributions
 */
class MangopayContribution {
	/**
	 * Création d'une contribution par carte : utilisateur vers projet
	 * @param WP_User $wp_user
	 * @param int $wp_campaign_id
	 * @param int $amount
	 * @param WP_Post $wp_page_return
	 */
	public static function card_user_to_project($wp_user, $wp_campaign_id, $amount, $wp_page_return) {
		//Récupération du walletid de la campagne
		$currentpost_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($wp_campaign_id);

		//Récupération du walletid de l'utilisateur
		$currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($wp_user->ID);
		if ($currentpost_mangopayid == '' || $currentuser_mangopayid == '') return '';

		//Conversion de la somme saisie en cents
		$cent_amount = $amount * 100;

		//Récupération de l'url de retour
		$return_url = get_permalink($wp_page_return->ID) . '?campaign_id=' . $wp_campaign_id;

		//Récupération de l'url de template
		$template_url = '';
		if (!defined('WP_IS_DEV_SITE') || WP_IS_DEV_SITE === FALSE) {
			$locale = get_locale();
			$locale_url = '';
			if ( !empty($locale) ) {
				$locale_url = '&lang='.$locale;
			}
		    $template_url = ', "TemplateURL" : "https://www.wedogood.co/paiement?campaign_id='.$wp_campaign_id. $locale_url.'"';
		}

		//Création de la contribution en elle-même
		$mangopay_newcontribution = request('contributions', 'POST', '{ 
							"UserID" : '.$currentuser_mangopayid.', 
							"WalletID" : '.$currentpost_mangopayid.',
							"Amount" : '.$cent_amount.',
							'.(($locale == 'en_US') ? '"Culture" : "en",' : '').'
							"ReturnURL" : "'. $return_url .'"' . $template_url . '
						    }');

		return $mangopay_newcontribution;
	}
	
	/**
	 * Création d'une contribution par carte : utilisateur vers propre compte
	 * @param int $wp_user_id
	 * @param int $amount
	 * @param string $url_return
	 * @param int $wp_campaign_id
	 */
	public static function card_user_to_account($wp_user_id, $amount, $url_return, $wp_campaign_id = '') {
		$user_mangopayid = ypcf_mangopay_get_mp_user_id($wp_user_id);
		if ($user_mangopayid == '') return '';
		
		//Conversion de la somme saisie en cents
		$cent_amount = $amount * 100;

		//Récupération de l'url de template
		$template_url = '';
		if (!empty($wp_campaign_id) && (!defined('WP_IS_DEV_SITE') || WP_IS_DEV_SITE === FALSE)) {
			$locale = get_locale();
			$locale_url = '';
			if ( !empty($locale) ) {
				$locale_url = '&lang='.$locale;
			}
		    $template_url = ', "TemplateURL" : "https://www.wedogood.co/paiement?campaign_id='.$wp_campaign_id. $locale_url.'"';
		}

		//Création de la contribution en elle-même
		$mangopay_newcontribution = request('contributions', 'POST', '{ 
							"UserID" : '.$user_mangopayid.', 
							"WalletID" : 0,
							"Amount" : '.$cent_amount.',
							'.(($locale == 'en_US') ? '"Culture" : "en",' : '').'
							"ReturnURL" : "'. $url_return .'"' . $template_url . '
						    }');
		return $mangopay_newcontribution;
	}
	
	/**
	 * Création d'une contribution par virement : utilisateur vers projet
	 * @param WP_User $wp_user
	 * @param int $wp_campaign_id
	 * @param int $amount
	 */
	public static function withdrawal_user_to_project($wp_user, $wp_campaign_id, $amount) {
		//Récupération du walletid de la campagne
		$currentpost_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($wp_campaign_id);

		//Récupération du walletid de l'utilisateur
		$currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($wp_user->ID);
		if ($currentpost_mangopayid == '' || $currentuser_mangopayid == '') return '';

		//Conversion de la somme saisie en cents
		$cent_amount = $amount * 100;

		$mangopay_newcontribution = request('contributions-by-withdrawal', 'POST', '{ 
							"UserID" : '.$currentuser_mangopayid.', 
							"WalletID" : '.$currentpost_mangopayid.',
							"AmountDeclared" : '.$cent_amount.'
						    }');

		return $mangopay_newcontribution;
	}
	
	/**
	 * Création d'une contribution par virement : utilisateur vers projet
	 * @param int $wp_user_id
	 * @param int $amount
	 */
	public static function withdrawal_user_to_account($wp_user_id, $amount) {
		//Récupération du walletid de l'utilisateur
		$currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($wp_user_id);
		if ($currentuser_mangopayid == '') return '';

		//Conversion de la somme saisie en cents
		$cent_amount = $amount * 100;

		$mangopay_newcontribution = request('contributions-by-withdrawal', 'POST', '{ 
							"UserID" : '.$currentuser_mangopayid.',
							"AmountDeclared" : '.$cent_amount.'
						    }');

		return $mangopay_newcontribution;
	}
}


/*******************************************************************************
** DEPRECATED
*******************************************************************************/
function ypcf_mangopay_contribution_user_to_project($current_user, $campaign_id, $amount, $page_return) {
	return MangopayContribution::card_user_to_project($current_user, $campaign_id, $amount, $page_return);
}
function ypcf_mangopay_contribution_user_to_account($campaign_id, $user_id, $amount, $return_url) {
	return MangopayContribution::card_user_to_account($user_id, $amount, $return_url, $campaign_id);
}
function ypcf_mangopay_contribution_withdrawal_user_to_project($current_user, $campaign_id, $amount) {
	return MangopayContribution::withdrawal_user_to_project($current_user, $campaign_id, $amount);
}
/*******************************************************************************
** END DEPRECATED
*******************************************************************************/


/*******************************************************************************
** A TRIER
*******************************************************************************/
function ypcf_mangopay_transfer_project_to_user($current_user, $campaign_id, $amount, $amount_fees = 0) {
    //RÃ©cupÃ©ration du walletid de la campagne
    $post_camp = get_post($campaign_id);
    $campaign = atcf_get_campaign( $post_camp );
    $campaign_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($campaign->ID);
    
    //RÃ©cupÃ©ration de l'id du porteur de projet
    $author_id = $campaign->data->post_author;
    ypcf_debug_log("ypcf_mangopay_transfer_project_to_user --- current user id : ".$current_user->ID. ' ; campaign id : ' .$campaign_id . ' ; author id : ' . $author_id);
    $campaign_author_mangopayid = ypcf_mangopay_get_mp_user_id($author_id);

    //RÃ©cupÃ©ration du walletid de l'utilisateur
    $currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($current_user->ID);
    
    $cent_amount = $amount * 100;
    $cent_amount_fees = $amount_fees * 100;
    
    //Création du transfert
    $mangopay_newtransfer = request('transfers', 'POST', '{ 
					    "PayerID" : '.$campaign_author_mangopayid.', 
					    "PayerWalletID" : '.$campaign_mangopayid.',
					    "BeneficiaryID" : '.$currentuser_mangopayid.',
					    "BeneficiaryWalletID" : 0,
					    "Amount" : '.$cent_amount.',
					    "ClientFeeAmount" : '.$cent_amount_fees.'
					}');
    
    return $mangopay_newtransfer;
}

function ypcf_mangopay_transfer_project_organisation_to_user($current_user, $campaign_id, $amount, $amount_fees = 0) {
    //RÃ©cupÃ©ration du walletid de la campagne
    $post_camp = get_post($campaign_id);
    $campaign = atcf_get_campaign( $post_camp );
    $campaign_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($campaign->ID);
    
    //RÃ©cupÃ©ration de l'id du porteur de projet
//    $author_id = $campaign->data->post_author;
//    ypcf_debug_log("ypcf_mangopay_transfer_project_to_user --- current user id : ".$current_user->ID. ' ; campaign id : ' .$campaign_id . ' ; author id : ' . $author_id);
//    $campaign_author_mangopayid = ypcf_mangopay_get_mp_user_id($author_id);

    //RÃ©cupÃ©ration du walletid de l'utilisateur
    $currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($current_user->ID);
    
    $cent_amount = $amount * 100;
    $cent_amount_fees = $amount_fees * 100;
    
    //Création du transfert
    $mangopay_newtransfer = request('transfers', 'POST', '{ 
					    "PayerID" : '.$currentuser_mangopayid.', 
					    "PayerWalletID" : '.$campaign_mangopayid.',
					    "BeneficiaryID" : '.$currentuser_mangopayid.',
					    "BeneficiaryWalletID" : 0,
					    "Amount" : '.$cent_amount.',
					    "ClientFeeAmount" : '.$cent_amount_fees.'
					}');
    
    return $mangopay_newtransfer;
}

/**
 * Transfert d'argent entre 2 comptes utilisateur
 */
function ypcf_mangopay_transfer_user_to_user($wp_user_payer_id, $wp_user_beneficiary_id, $amount, $amount_fees = 0) {
    //Init des données
    $user_payer_mangopayid = ypcf_mangopay_get_mp_user_id($wp_user_payer_id);
    $user_beneficiary_mangopayid = ypcf_mangopay_get_mp_user_id($wp_user_beneficiary_id);
    $cent_amount = $amount * 100;
    $cent_amount_fees = $amount_fees * 100;
    
    //Création du transfert
    $mangopay_newtransfer = request('transfers', 'POST', '{ 
					    "PayerID" : '.$user_payer_mangopayid.', 
					    "PayerWalletID" : 0,
					    "BeneficiaryID" : '.$user_beneficiary_mangopayid.',
					    "BeneficiaryWalletID" : 0,
					    "Amount" : '.$cent_amount.',
					    "ClientFeeAmount" : '.$cent_amount_fees.'
					}');
    return $mangopay_newtransfer;
}

function ypcf_mangopay_refund_project_to_user($wp_invest_id) {
    $post_invest = get_post($wp_invest_id);
    $payment_key = edd_get_payment_key($wp_invest_id);
    $contribution_mangopayid = $payment_key;
    if (strpos($payment_key, 'wire_') !== FALSE) {
	    $contribution_mangopayid = substr($payment_key, 5);
    }
    $user_mangopayid = ypcf_mangopay_get_mp_user_id($post_invest->post_author);

    $mangopay_newrefund = request('refunds', 'POST', '{
					    "ContributionID" : '.$contribution_mangopayid.',
					    "UserID" : '.$user_mangopayid.'
					}');
    
    update_post_meta($wp_invest_id, 'refund_id', $mangopay_newrefund->ID);
    
    return $mangopay_newrefund;
}

/**
 * 
 * @param type $contribution_id
 * @return type
 */
function ypcf_mangopay_get_contribution_by_id($contribution_id) {
	if (!empty($contribution_id)) {
		global $WDG_cache_plugin;
		$url_called = 'contributions/'.$contribution_id;

		$result_cached = $WDG_cache_plugin->get_cache($url_called, 1);
		$result = unserialize($result_cached);
		if ($result_cached === FALSE || empty($result)) {
			$result = request($url_called, 'GET');
			$result_save = serialize($result);
			if (!empty($result_save)) {
				$WDG_cache_plugin->set_cache($url_called, $result_save, 60*60*5, 1);
			}
		}
		if (isset($result->code) && ($result->code == '404' || $result->code == '500')) return array();
		else return $result;
	} else {
		return array();
	}
}

function ypcf_mangopay_get_withdrawalcontribution_by_id($contribution_id) {
	if (!empty($contribution_id)) {
		global $WDG_cache_plugin;
		$url_called = 'contributions-by-withdrawal/'.$contribution_id;

		$result_cached = $WDG_cache_plugin->get_cache($url_called, 1);
		$result = unserialize($result_cached);
		if ($result_cached === FALSE || empty($result)) {
			$result = request($url_called, 'GET');
			$result_save = serialize($result);
			if (!empty($result_save)) {
				$WDG_cache_plugin->set_cache($url_called, $result_save, 60*60*5, 1);
			}
		}
		if (isset($result->code) && ($result->code == '404' || $result->code == '500')) return array();
		else return $result;
	} else {
		return array();
	}
}

function ypcf_mangopay_get_transfer_by_id($transfer_id) {
    return request('transfers/'.$transfer_id, 'GET');
}

function ypcf_mangopay_get_refund_by_id($refund_id) {
    return request('refunds/'.$refund_id, 'GET');
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
    $buffer = false;
    $mp_user_id = ypcf_mangopay_get_mp_user_id($wp_user_id);
    $mp_user_object = ypcf_mangopay_get_user_by_id($mp_user_id);
    ypcf_debug_log('ypcf_mangopay_is_user_strong_authenticated >> [' . $mp_user_object->ID . '] --> IsStrongAuthenticated : ' . $mp_user_object->IsStrongAuthenticated);
    if ($mp_user_object && $mp_user_object->IsStrongAuthenticated) {
	    $buffer = true;
    } else {
	    $authentication_object = ypcf_mangopay_get_user_strong_authentication($mp_user_id);
	    if ($authentication_object) $buffer = ($authentication_object->IsDocumentsTransmitted && $authentication_object->IsCompleted && $authentication_object->IsSucceeded);
    }
    return $buffer;
}

function ypcf_mangopay_is_user_strong_authentication_sent($wp_user_id) {
    $mp_user_id = ypcf_mangopay_get_mp_user_id($wp_user_id);
    $authentication_object = ypcf_mangopay_get_user_strong_authentication($mp_user_id);
    $buffer = false;
    if ($authentication_object && isset($authentication_object->IsDocumentsTransmitted)) $buffer = $authentication_object->IsDocumentsTransmitted;
    return $buffer;
}

function ypcf_mangopay_get_user_strong_authentication_status($wp_user_id) {
    $mp_user_id = ypcf_mangopay_get_mp_user_id($wp_user_id);
    $authentication_object = ypcf_mangopay_get_user_strong_authentication($mp_user_id);
    $buffer = array('status' => '', 'message' => '');
    if ($authentication_object && isset($authentication_object->IsDocumentsTransmitted) && $authentication_object->IsDocumentsTransmitted && !$authentication_object->IsCompleted) {
	$buffer['status'] = 'waiting';
	$buffer['message'] = "Votre pi&egrave;ce d&apos;identit&eacute; est en cours de validation. Un d&eacute;lai maximum de 24h est n&eacute;cessaire &agrave; cette validation.";
    } elseif ($authentication_object && isset($authentication_object->IsDocumentsTransmitted) && $authentication_object->IsDocumentsTransmitted && $authentication_object->IsCompleted && !$authentication_object->IsSucceeded) {
	$buffer['status'] = 'error';
	$buffer['message'] = $authentication_object->Message;
    }
    switch ($buffer['message']) {
	case "The copy of the other side of the ID card is missing.":
	    $buffer['message'] = "Il manque la copie de l&apos;autre c&ocirc;t&eacute; de la carte d&apos;identit&eacute;.";
	    break;
    }
    return $buffer;
}

function ypcf_mangopay_send_strong_authentication($url_request, $field_name) {
    ypcf_debug_log("ypcf_mangopay_send_strong_authentication --- ".$url_request." (" . $field_name . ")");
    
    $authorized_mime_type = array('image/jpeg', 'image/pjpeg', 'image/gif', 'image/png', 'application/pdf'); 
    ypcf_debug_log('ypcf_mangopay_send_strong_authentication --- TEST MIME >> $_FILES -> '.$_FILES[$field_name]['type']);
    if (!in_array($_FILES[$field_name]['type'], $authorized_mime_type)) { return false; } 
    
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_request);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $mime_type_text = ';type='.$_FILES[$field_name]['type'];
    $post = array('StrongValidationDto.Picture' => '@' . $_FILES[$field_name]['tmp_name'] . $mime_type_text);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    
    $curl_info = curl_exec($ch);
    if ($curl_info == 200 || $curl_info == 0) $result = TRUE;
    else $result = FALSE;
    curl_close($ch);
    
    ypcf_debug_log("ypcf_mangopay_send_strong_authentication ---> ".($result ? "success" : "fail"). " with code " . $curl_info);
    return $result;
}

function ypcf_mangopay_get_wallet_by_id($wallet_id) {
    return request('wallets/'.$wallet_id, 'GET');
}

function ypcf_mangopay_get_operations_by_wallet_id($wallet_id) {
    return request('wallets/'.$wallet_id.'/operations', 'GET');
}

function ypcf_mangopay_get_operations_by_user_id($user_id) {
    return request('users/'.$user_id.'/operations', 'GET');
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
 * VÃ©rifie que l'utilisateur connectÃ© a une correspondance dans mangopay pour un id utilisateur et un id porte-monnaie
 * @param type $current_user
 * @return type
 */
function ypcf_init_mangopay_user($current_user, $legal_type = false) {
    ypcf_debug_log('-------------');
    ypcf_debug_log('ypcf_init_mangopay_user : $current_user->ID = ' . $current_user->ID);
    //On s'apprÃªte Ã  confirmer, donc on vÃ©rifie si le currentuser a un compte sur mangopay. Si il n'en a pas, on le crÃ©e directement.
    $currentuser_mangopayid = ypcf_mangopay_get_mp_user_id($current_user->ID);
    ypcf_debug_log('ypcf_init_mangopay_user >> $currentuser_mangopayid = ' . $currentuser_mangopayid);
    if ($currentuser_mangopayid == "") {
	$first_name = ($legal_type) ? $current_user->display_name : $current_user->user_firstname;
	$last_name = ($legal_type) ? $current_user->display_name : $current_user->user_lastname;
	$birthday = ($legal_type) ? "" : strtotime($current_user->get('user_birthday_year') . "-" . $current_user->get('user_birthday_month') . "-" . $current_user->get('user_birthday_day'));
	$person_type = ($legal_type) ? "LEGAL_PERSONALITY" : "NATURAL_PERSON";
	$mangopay_new_user = request('users', 'POST', '{ 
				    "FirstName" : "'.$first_name.'", 
				    "LastName" : "'.$last_name.'", 
				    "Email" : "'.$current_user->user_email.'", 
				    "Nationality" : "'.$current_user->get('user_nationality').'", 
				    "Birthday" : '.$birthday.', 
				    "IP" : "'.$_SERVER['REMOTE_ADDR'].'",
				    "PersonType" : "'.$person_type.'",
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
    //De mÃªme, on vÃ©rifie si le currentuser a un wallet sur mangopay. Si il n'en a pas, on le crÃ©e.
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
 * Initialise le crÃ©ateur du projet sur mangopay si nÃ©cessaire puis le porte-monnaie du projet.
 */
function ypcf_init_mangopay_project() {
    $currentpost_mangopayid = FALSE;
    if (isset($_GET['campaign_id'])) {
	$post = get_post($_GET['campaign_id']);
	$campaign = atcf_get_campaign( $post );
	
	$currentpost_mangopayid = ypcf_mangopay_get_mp_campaign_wallet_id($campaign->ID);
	//Si le projet n'existe pas encore
	if ($currentpost_mangopayid == "") {
	    
	    $api_project_id = $campaign->get_api_id();
	    $current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
	    if (count($current_organisations) > 0) {
		    $current_organisation = $current_organisations[0];
		    $organisation_object = new YPOrganisation($current_organisation->organisation_wpref);
		    $mangopay_new_user_id = ypcf_init_mangopay_user($organisation_object->get_creator(), TRUE);
		    
	    } else {
		    //On va chercher l'identifiant mangopay du porteur de projet
		    $current_user = get_userdata($campaign->data->post_author);
		    $mangopay_new_user_id = ypcf_init_mangopay_user($current_user);
	    }
	    
	    //On crée le porte-monnaie du projet
	    if ($mangopay_new_user_id != "") {
		$mangopay_new_wallet = request('wallets', 'POST', '{ 
					    "Owners" : ['.$mangopay_new_user_id.'], 
					    "Name" : "Wallet for '.$campaign->data->post_title.'",
					    "Tag" : "Wallet for '.$campaign->data->post_title.'",
					    "Description" : "Wallet for '.$campaign->data->post_title.'",
					    "RaisingGoalAmount" : '.($campaign->goal(false) * 100).'
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
	    //Si on reÃ§oit une chaine : il y a eu une erreur
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
    //Utiliser les donnÃ©es stockÃ©es par edd
    // - plus optimisÃ© (plutÃ´t que multiplier les appels vers mangopay)
    // - rÃ©cupÃ©ration de l'id de la campagne, du wallet correspondant, de la liste des backers et de leur wallet
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