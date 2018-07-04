<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $lemonway_lib;
$lemonway_lib = new LemonwayLib();

/**
 * Classe de gestion de Lemonway
 */
class LemonwayLib {
	public static $limit_kyc1_moneyin_operation_amount = 250;
	public static $limit_kyc1_moneyin_day_nb = 20;
	public static $limit_kyc1_moneyin_year_amount = 2500;
	public static $limit_kyc1_moneyout_day_nb = 20;
	public static $limit_kyc1_moneyout_year_amount = 2500;
	public static $limit_kyc1_p2p_in_day_nb = 20;
	public static $limit_kyc1_p2p_out_year_nb = 20;
	public static $limit_kyc1_p2p_in_year_amount = 2500;
	public static $limit_kyc1_p2p_out_year_amount = 2500;
	
	public static $limit_kyc2_moneyin_day_nb = 1000;
	public static $limit_kyc2_moneyin_day_amount = 500000;
	public static $limit_kyc2_moneyin_month_amount = 1000000;
	public static $limit_kyc2_moneyout_day_nb = 20;
	public static $limit_kyc2_moneyout_day_amount = 1000000;
	public static $limit_kyc2_moneyout_month_amount = 1000000;
	public static $limit_kyc2_p2p_in_day_nb = 1000;
	public static $limit_kyc2_p2p_day_amount = 500000;
	public static $limit_kyc2_p2p_out_month_nb = 10000;
	public static $limit_kyc2_p2p_in_month_amount = 1000000;
	public static $limit_kyc2_p2p_out_month_amount = 1000000;
	
	public $soap_client, $params, $last_error;
    
	/**
	 * Initialise les données à envoyer à Lemonway
	 */
	public function __construct() {
		$this->params = array(
			'wlLogin' => YP_LW_LOGIN,
			'wlPass' => YP_LW_PASSWORD,
			'language' => 'fr',
			'version' => '1.9', //Version actuelle au moment du développement
			'walletIp' => $_SERVER['REMOTE_ADDR'],
			'walletUa' => $_SERVER['HTTP_USER_AGENT'],
		);
		$this->last_error = FALSE;
	}
	
	/**
	 * Requête au serveur
	 * @global LemonwayLib $lemonway_lib
	 * @param type $method_name
	 * @param type $params
	 * @param type $params_override
	 * @return boolean
	 */
	public static function call($method_name, $params, $params_override = array()) {
		global $lemonway_lib;
		ypcf_debug_log('LemonwayLib::call METHOD : ' .$method_name. ' ; FROM : ['.$_SERVER["REMOTE_ADDR"].','.$_SERVER['SERVER_ADDR'].'] ; $params : ' .print_r($params, true));
		
		//Récupération de tous les paramètres à envoyer
		$lw_params = $lemonway_lib->params;
		foreach ($lw_params as $key => $value) {
			$params[$key] = $value;
		}
		foreach ($params_override as $key => $value) {
			$params[$key] = $value;
		}
		//Appel de la fonction avec les paramètres complets
		try {
			LemonwayLib::set_error('', '');
			if (!isset($lemonway_lib->soap_client)) $lemonway_lib->soap_client = @new SoapClient(YP_LW_URL);
		} catch (SoapFault $E) {
			LemonwayLib::set_error('SOAPCLIENTINIT', $E->faultstring);
			ypcf_debug_log('LemonwayLib::call ERROR : ' . $E->faultstring);
			return FALSE;
		}
		
		$soap_client = $lemonway_lib->soap_client;
		if ( !isset( $params[ 'buffer' ] ) ) {
			$params = json_decode( json_encode( $params ), FALSE );
		}
		$call_result = $soap_client->$method_name($params);
		ypcf_debug_log('LemonwayLib::call RESULT : ' .print_r($call_result, true));
		$result_obj = $call_result->{$method_name . 'Result'};
		//Annalyse du résultat
		if (LemonwayLib::has_errors($result_obj)) {
			return FALSE;
		} else {
			return $result_obj;
		}
	}
	
	/**
	 * Parse le retour pour déterminer si il y a des erreurs et les enregistrer si c'est le cas
	 * @param type $result_obj
	 * @return boolean
	 */
	public static function has_errors($result_obj) {
		$buffer = false;
		if (isset($result_obj->E)) {
			global $lemonway_lib;
			$lemonway_lib->last_error['Code'] = $result_obj->E->Code;
			$lemonway_lib->last_error['Msg'] = $result_obj->E->Msg;
			$buffer = true;
		}
		return $buffer;
	}
	
	public static function set_error($code, $msg) {
		global $lemonway_lib;
		$lemonway_lib->last_error['Code'] = $code;
		$lemonway_lib->last_error['Msg'] = $msg;
	}

	public static function get_last_error_code() {
		$buffer = '';
		global $lemonway_lib;
		if (isset($lemonway_lib->last_error['Code'])) {
			$buffer = $lemonway_lib->last_error['Code'];
		}
		return $buffer;
	}

	public static function get_last_error_message() {
		$buffer = '';
		global $lemonway_lib;
		if (isset($lemonway_lib->last_error['Msg'])) {
			$buffer = $lemonway_lib->last_error['Msg'];
		}
		return $buffer;
	}


	
/*********************** HELPERS ***********************/
	public static function check_amount($amount) {
		if (strpos($amount, '.') === FALSE) {
			$amount .= '.00';
		} else {
			$amount *= 100;
			$amount /= 100;
			//Modification pour les montants en .5 (doivent devenir .50)
			if (strpos($amount, '.') !== FALSE && strpos($amount, '.') == strlen($amount) - 2) {
				$amount .= '0';
			}
			if (strpos($amount, '.') === FALSE) {
				$amount .= '.00';
			}
		}
		return $amount;
	}
	
	public static function check_phone_number( $phone_number ) {
		$buffer = str_replace( array(' ', '.', '-', '+'), '', $phone_number);
		if ( !empty( $buffer ) ) {
			$buffer = substr( $buffer, -9 );
			$buffer = '33' . $buffer;
		}
		return $buffer;
	}
	
	public static function get_country_code( $country ) {
		
	}
	
	public static function make_token($invest_id = '', $roi_id = '') {
		$buffer = FALSE;
		$random = rand(10000, 99999);
		if ( !empty( $invest_id ) ) {
			$buffer = 'INV' . $invest_id . 'TS' . $random;
			
		} else if ( !empty( $roi_id ) ) {
			$buffer = 'ROI' . $roi_id . 'TS' . $random;
			
		}
		return $buffer;
	}



/*********************** WALLETS ***********************/
	public static $wallet_type_payer = '1';
	public static $wallet_type_beneficiary = '2';
	
	/**
	 * Création d'un porte-monnaie
	 * @param type $new_wallet_id : Identifiant du porte-monnaie sur la plateforme
	 * @param type $client_mail
	 * @param type $client_title : Civilité (1 char) 
	 * @param type $client_first_name
	 * @param type $client_last_name
	 * @param type $country : Pays au format ISO-3
	 * @param type $phone_number : Facultatif ; format MSISDN (code pays sans + ni 00)
	 * @param type $birthdate : Format JJ/MM/AAAA
	 * @param type $nationality : Pays au format ISO-3
	 * @param type $payer_or_beneficiary : Statut payer/beneficiary
	 * @return type
	 */
	public static function wallet_register( $new_wallet_id, $client_mail, $client_title, $client_first_name, $client_last_name, $country = '', $phone_number = '', $birthdate = '', $nationality = '', $payer_or_beneficiary = '' ) {
		$param_list = array(
			'wallet'			=> $new_wallet_id,
			'clientMail'		=> $client_mail,
			'clientTitle'		=> $client_title,
			'clientFirstName'	=> $client_first_name,
			'clientLastName'	=> $client_last_name,
			'ctry'				=> $country,
			'birthdate'			=> $birthdate,
			'nationality'		=> $nationality,
			'payerOrBeneficiary' => $payer_or_beneficiary
		);
		if ( !empty( $phone_number ) ) {
			$param_list['phoneNumber'] = $phone_number;
		}
		$result = LemonwayLib::call('RegisterWallet', $param_list);
		if ($result !== FALSE) {
			$result = $result->WALLET->LWID;
		}
		return $result;
	}
	
	/**
	 * Création d'un wallet pour entité morale
	 * @param type $new_wallet_id
	 * @param type $client_mail
	 * @param type $client_first_name
	 * @param type $client_last_name
	 * @param type $company_name
	 * @param type $company_description
	 * @param type $company_website
	 * @param type $country : Pays au format ISO-3
	 * @param type $birthdate : Format JJ/MM/AAAA
	 * @param type $phone_number : Facultatif ; format MSISDN (code pays sans + ni 00)
	 * @param type $company_idnumber
	 * @param type $payer_or_beneficiary
	 * @return type
	 */
	public static function wallet_company_register( $new_wallet_id, $client_mail, $client_first_name, $client_last_name, $company_name, $company_description, $company_website = '', $country = '', $birthdate = '', $phone_number = '', $company_idnumber = '', $payer_or_beneficiary = '' ) {
		$param_list = array(
			'wallet'						=> $new_wallet_id,
			'clientMail'					=> $client_mail,
			'clientFirstName'				=> $client_first_name,
			'clientLastName'				=> $client_last_name,
			'companyName'					=> $company_name,
			'companyDescription'			=> $company_description,
			'companyWebsite'				=> $company_website,
			'ctry'							=> $country,
			'birthdate'						=> $birthdate,
			'phoneNumber'					=> $phone_number,
			'companyIdentificationNumber'	=> $company_idnumber,
			'isCompany'						=> '1',
			'isDebtor'						=> '1',
			'payerOrBeneficiary'			=> $payer_or_beneficiary
		);
		$result = LemonwayLib::call('RegisterWallet', $param_list);
		if ($result !== FALSE) {
			$result = $result->WALLET->LWID;
		}
		return $result;
	}
	
	/**
	 * Mise à jour d'un porte-monnaie
	 * @param type $wallet_id : Identifiant du porte-monnaie sur la plateforme
	 * @param type $client_mail
	 * @param type $client_title : Civilité (1 char) 
	 * @param type $client_first_name
	 * @param type $client_last_name
	 * @param type $country : Pays au format ISO-3
	 * @param type $phone_number : Facultatif ; format MSISDN (code pays sans + ni 00)
	 * @param type $birthdate : Format JJ/MM/AAAA
	 * @param type $nationality : Pays au format ISO-3
	 * @param type $company_website
	 * @return type
	 */
	public static function wallet_update( $wallet_id, $client_mail = '', $client_title = '', $client_first_name = '', $client_last_name = '', $country = '', $phone_number = '', $birthdate = '', $nationality = '', $company_website = '' ) {
		if ( empty( $wallet_id ) ) {
			return FALSE;
		}
		
		$param_list = array( 'wallet' => $wallet_id );
		if ( !empty( $client_mail ) ) {
			$param_list['newEmail'] = $client_mail;
		}
		if ( !empty( $client_title ) ) {
			$param_list['newTitle'] = $client_title;
		}
		if ( !empty( $client_first_name ) ) {
			$param_list['newFirstName'] = $client_first_name;
		}
		if ( !empty( $client_last_name ) ) {
			$param_list['newLastName'] = $client_last_name;
		}
		if ( !empty( $country ) ) {
			$param_list['newCtry'] = $country;
		}
		if ( !empty( $phone_number ) ) {
			$param_list['newPhoneNumber'] = $phone_number;
		}
		if ( !empty( $birthdate ) ) {
			$param_list['newBirthDate'] = $birthdate;
		}
		if ( !empty( $nationality ) ) {
			$param_list['newNationality'] = $nationality;
		}
		if ( !empty( $company_website ) ) {
			$param_list['newCompanyWebsite'] = $company_website;
		}
		
		
		$result = LemonwayLib::call('UpdateWalletDetails', $param_list);
		return $result;
	}
	
	/**
	 * Mise à jour d'un porte-monnaie d'entité morale
	 * @param type $wallet_id : Identifiant du porte-monnaie sur la plateforme
	 * @param type $client_mail
	 * @param type $client_first_name
	 * @param type $client_last_name
	 * @param type $country : Pays au format ISO-3
	 * @param type $phone_number : Facultatif ; format MSISDN (code pays sans + ni 00)
	 * @param type $birthdate : Format JJ/MM/AAAA
	 * @param type $company_name
	 * @param type $company_description
	 * @param type $company_website
	 * @param type $company_idnumber
	 * @return type
	 */
	public static function wallet_company_update( $wallet_id, $client_mail = '', $client_first_name = '', $client_last_name = '', $country = '', $phone_number = '', $birthdate = '', $company_name = '', $company_description = '', $company_website = '', $company_idnumber = '' ) {
		if ( empty( $wallet_id ) ) {
			return FALSE;
		}
		
		$param_list = array( 'wallet' => $wallet_id );
		if ( !empty( $client_mail ) ) {
			$param_list['newEmail'] = $client_mail;
		}
		if ( !empty( $client_first_name ) ) {
			$param_list['newFirstName'] = $client_first_name;
		}
		if ( !empty( $client_last_name ) ) {
			$param_list['newLastName'] = $client_last_name;
		}
		if ( !empty( $country ) ) {
			$param_list['newCtry'] = $country;
		}
		if ( !empty( $phone_number ) ) {
			$param_list['newPhoneNumber'] = $phone_number;
		}
		if ( !empty( $birthdate ) ) {
			$param_list['newBirthDate'] = $birthdate;
		}
		if ( !empty( $company_name ) ) {
			$param_list['newCompanyName'] = $company_name;
		}
		if ( !empty( $company_description ) ) {
			$param_list['newCompanyDescription'] = $company_description;
		}
		if ( !empty( $company_website ) ) {
			$param_list['newCompanyWebsite'] = $company_website;
		}
		if ( !empty( $company_idnumber ) ) {
			$param_list['newCompanyIdentificationNumber'] = $company_idnumber;
		}
		
		
		$result = LemonwayLib::call('UpdateWalletDetails', $param_list);
		return $result;
	}
	
	/**
	 * Données d'un porte-monnaie
	 * @param type $wallet_id
	 * @return boolean or object
	 */
	public static function wallet_get_details( $wallet_id = FALSE, $wallet_email = FALSE ) {
		if ( empty( $wallet_id ) && empty( $wallet_email ) ) return FALSE;
		
		if ( !empty( $wallet_id ) ) {
			$param_list = array( 'wallet' => $wallet_id );
			
		} elseif ( !empty( $wallet_email ) ) {
			$param_list = array( 'email' => $wallet_email );
		
		}
		
		$result = LemonwayLib::call('GetWalletDetails', $param_list);
		
		/**
		 * Retourne les éléments suivants :
		 * ID (identifiant) ; BAL (solde) ; NAME ; EMAIL ; DOCS (liste de documents dont le statut a changé) ; IBANS (liste des IBANs) ; S (statut)
		 */
		return $result->WALLET;
	}
	
	public static $status_blocked = 'blocked';
	public static $status_ready = 'ready';
	public static $status_waiting = 'waiting';
	public static $status_incomplete = 'incomplete';
	public static $status_rejected = 'rejected';
	public static $status_registered = 'registered';
	/**
	 * Récupère les statuts qui ont changé depuis une certaine date
	 * @param type $update_date
	 * @return boolean or object
	 */
	public static function wallet_get_kyc_status_since($update_date) {
		if (!isset($update_date)) return FALSE;
		
		$param_list = array( 'updateDate' => $update_date );
		
		$result = LemonwayLib::call('GetKycStatus', $param_list);
		
		/**
		 * Retourne une liste de wallets
		 * ID (identifiant) ; S (statut) ; DATE ; DOCS ; IBANS
		 */
		return $result;
		
	}
	
	/**
	 * Envoi d'un justificatif de porte-monnaie
	 * @param type $wallet_id
	 * @param type $filename
	 * @param type $doctype : 0 (carte id communauté euro) ; 1 (justificatif de domicile) ; 2 (rib) ; 7 (kbis) ; 11,12,13 (docs divers)
	 * @param type $bytearray
	 * @return boolean or string
	 */
	public static function wallet_upload_file($wallet_id, $filename, $doctype, $bytearray) {
		if (!isset($wallet_id)) return FALSE;
		
		$param_list = array( 
			'wallet' => $wallet_id, 
			'fileName' => $filename, 
			'type' => $doctype, 
			'buffer' => $bytearray 
		);
		
		$result = LemonwayLib::call('UploadFile', $param_list);
		if ($result !== FALSE) {
			if (isset($result->E)) {
				$result = FALSE;
			} else {
				$result = $result->UPLOAD->ID;
			}
		}
		return $result;
	}
	
	/**
	 * Enregistre un RIB associé à un porte-monnaie
	 * @param int $wallet_id
	 * @param string $holder_name
	 * @param string $iban
	 * @param string $bic
	 * @param string $dom1
	 * @param string $dom2
	 * @return boolean or string
	 */
	public static function wallet_register_iban($wallet_id, $holder_name, $iban, $bic, $dom1, $dom2 = '') {
		if (!isset($wallet_id)) return FALSE;
		if (!isset($holder_name)) return FALSE;
		if (!isset($bic)) return FALSE;
		if (!isset($iban)) return FALSE;
		if (!isset($dom1)) return FALSE;
		
		//wallet ; holder; bic ; iban ; dom1 ; dom2
		$param_list = array(
			'wallet' => $wallet_id,
			'holder' => $holder_name,
			'iban' => $iban,
			'bic' => $bic,
			'dom1' => $dom1,
			'dom2' => $dom2
		);
		
		$result = LemonwayLib::call('RegisterIBAN', $param_list);
		if ($result !== FALSE) {
			//Retourne : ID ; S (status)
		}
		return $result;
	}
	
	/**
	 * Enregistre un mandat de prélévement automatique lié à un wallet
	 * @param int $wallet_id
	 * @param string $holder_name
	 * @param string $iban
	 * @param string $bic
	 * @param int $is_recurring
	 * @param int $is_b2b
	 * @param string $street
	 * @param string $post_code
	 * @param string $city
	 * @param string $country
	 * @param string $language
	 * @return boolean or string
	 */
	public static function wallet_register_mandate( $wallet_id, $holder_name, $iban, $bic, $is_recurring, $is_b2b, $street, $post_code, $city, $country, $language = 'fr' ) {
		if (!isset($wallet_id)) return FALSE;
		if (!isset($holder_name)) return FALSE;
		if (!isset($bic)) return FALSE;
		if (!isset($iban)) return FALSE;
		
		//wallet ; holder ; iban ; bic ; isRecurring (1/0) ; isB2B (1/0) ; street ; postCode ; city ; country (FRANCE) ; mandateLanguage(fr/en/es/de)
		$param_list = array(
			'wallet'		=> $wallet_id,
			'holder'		=> $holder_name,
			'iban'			=> $iban,
			'bic'			=> $bic,
			'isRecurring'	=> $is_recurring,
			'isB2B'			=> $is_b2b,
			'street'		=> $street,
			'postCode'		=> $post_code,
			'city'			=> $city,
			'country'		=> $country,
			'mandateLanguage'	=> $language,
		);
		
		$result = LemonwayLib::call('RegisterSddMandate', $param_list);
		if ($result !== FALSE) {
			//Retourne : ID ; S (status)
		}
		return $result;
	}
	
	public static function wallet_unregister_mandate( $wallet_id, $mandate_id ) {
		if ( empty ( $wallet_id ) || empty ( $mandate_id ) ) {
			return FALSE;
		}
		
		$param_list = array(
			'wallet'		=> $wallet_id,
			'sddMandateId'	=> $mandate_id
		);
		
		$result = LemonwayLib::call( 'UnregisterSddMandate', $param_list );
		if ($result !== FALSE) {
			//Retourne : ID ; S (status)
		}
		return $result;
	}
	
	/**
	 * Démarre la signature d'un mandat
	 * @param int $wallet_id
	 * @param int $mobile_number
	 * @param int $document_id
	 * @param string $url_return
	 * @param string $url_error
	 * @param int $document_type (21)
	 * @return boolean or int
	 */
	public static function wallet_sign_mandate_init( $wallet_id, $mobile_number, $document_id, $url_return, $url_error, $document_type = 21 ) {
		if (!isset($wallet_id)) return FALSE;
		if (!isset($mobile_number)) return FALSE;
		if (!isset($document_id)) return FALSE;
		if (!isset($url_return)) return FALSE;
		if (!isset($url_error)) return FALSE;
		
		$phone_number = LemonwayLib::check_phone_number( $mobile_number );
		
		//wallet ; mobileNumber ; documentId ; documentType (21 pour SDD) ; returnUrl ; errorUrl
		$param_list = array(
			'wallet'		=> $wallet_id,
			'mobileNumber'	=> $phone_number,
			'documentId'	=> $document_id,
			'documentType'	=> $document_type,
			'returnUrl'		=> $url_return,
			'errorUrl'		=> $url_error
		);
		
		$result = LemonwayLib::call('SignDocumentInit', $param_list);
		if ($result !== FALSE) {
			//Retourne : TOKEN
		}
		return $result;
	}
			
	/**
	 * Retourne un statut correspondant à un KYC
	 * @param object $document_object
	 * @return string
	 */
	public static function document_get_status_string($document_object) {
		$buffer = '';
		if ($document_object !== FALSE) {
			switch ($document_object->S) {
				case 1: $buffer = __('Document en cours d&apos;&eacute;tude.', 'yproject'); break;
				case 2: $buffer = __('Document accept&eacute;.', 'yproject'); break;
				case 3: $buffer = __('Document refus&eacute;.', 'yproject'); break;
				case 4: $buffer = __('Document remplac&eacute;.', 'yproject'); break;
				case 5: $buffer = __('Document expir&eacute;.', 'yproject'); break;
				default: $buffer = __('Il y a eu un probl&egrave;me lors de l&apos;envoi. Merci de le renvoyer.', 'yproject'); break;
			}
		} else {
			$buffer = __('Ce document n&apos;a pas encore &eacute;t&eacute; envoy&eacute;.', 'yproject');
		}
		return $buffer;
	}
	
/*********************** FIN WALLETS ***********************/
	
/*********************** PAIEMENTS ***********************/
	/**
	 * Différents cas :
	 * - Page web saisie de carte sur site partenaire (Payline ou Atos) MoneyInWebInit
	 * - Page web saisie de carte sur site WDG avec 3DSecore MoneyIn3DInit
	 * - Page web saisie de carte sur site WDG sans 3DSecore MoneyIn
	 * - Virement GetMoneyIBANDetails
	 * - Entre wallets SendPayment
	 * - Enregistrement de carte pour utilisation ultérieure RegisterCard ; MoneyInWithCardId
	 */
	
	/**
	 * 
	 * @param type $transaction_id
	 * @return boolean or string
	 */
	public static function get_transaction_by_id($transaction_id, $type = 'moneyin') {
		if (!isset($transaction_id)) return FALSE;
		
		
		global $WDG_cache_plugin;
		if ($WDG_cache_plugin == null) {
			$WDG_cache_plugin = new WDG_Cache_Plugin();
		}
		$url_called = 'transaction::'.$type.'::'.$transaction_id;
		$result_cached = $WDG_cache_plugin->get_cache( $url_called, 1 );
		$result = unserialize( $result_cached );
		
		if ($result_cached === FALSE || empty($result)) {
			switch ($type) {
				case 'payment':
					$param_list = array( 
						'transactionId' => $transaction_id
					);
					$result = LemonwayLib::call('GetPaymentDetails', $param_list);
					break;

				case 'moneyin':
				default:
					$param_list = array( 
						'transactionMerchantToken' => $transaction_id
					);
					$result = LemonwayLib::call('GetMoneyInTransDetails', $param_list);
					break;
			}
			
			$result_save = serialize($result);
			if (!empty($result_save)) {
				$WDG_cache_plugin->set_cache($url_called, $result_save, 60*60*5, 1);
			}
		}
		
		if ($result !== FALSE) {
			//Retourne : ID ; DATE ; CRED (montant) ; COM (commission) ; STATUS : 3 (terminé) ou 4 (erreur)
			$result = $result->TRANS->HPAY;
		}
		return $result;
	}
	
	/**
	 * 
	 * @param type $date
	 * @return boolean
	 */
	public static function get_transactions_wire_since($date) {
		if (!isset($date)) return FALSE;
		
		$param_list = array(
			'updateDate' => $date
		);
		
		$result = LemonwayLib::call('GetMoneyInIBANDetails', $param_list);
		
		if ($result !== FALSE) {
			//Retourne : HPAY -> ID ; DATE ; REC (wallet) ; CRED (montant) ; COM (commission) ; STATUS : 3 (terminé) ou 4 (erreur)
			$result = $result;
		}
		return $result;
	}
	
	/**
	 * 
	 * @param type $wallet_id
	 * @param type $card_type
	 * @param type $card_number
	 * @param type $card_crypto
	 * @param type $card_date
	 * @return boolean
	 */
	public static function save_card($wallet_id, $card_type, $card_number, $card_crypto, $card_date) {
		if (!isset($wallet_id)) return FALSE;
		if (!isset($card_type)) return FALSE;
		if (!isset($card_number)) return FALSE;
		if (!isset($card_crypto)) return FALSE;
		if (!isset($card_date)) return FALSE;
		
		$param_list = array( 
			'wallet' => $wallet_id, 
			'cardType' => $card_type, 
			'cardNumber' => $card_number, 
			'cardCode' => $card_crypto,
			'cardDate' => $card_date,
		);
		
		$result = LemonwayLib::call('RegisterCard', $param_list);
		if ($result !== FALSE) {
			//Retourne : ID
			$result = $result->ID->__toString();
		}
		return $result;
	}
	
	public static function ask_payment_webkit($wallet_id, $amount, $amount_com, $wk_token, $return_url, $error_url, $cancel_url, $register_card = 0, $comment = '', $auto_commission = 0) {
		if (!isset($wallet_id)) return FALSE;
		if (!isset($amount)) return FALSE;
		if (!isset($amount_com)) return FALSE;
		if (!isset($wk_token)) return FALSE;
		if (!isset($return_url)) return FALSE;
		if (!isset($error_url)) return FALSE;
		if (!isset($cancel_url)) return FALSE;
		
		$amount = LemonwayLib::check_amount($amount);
		$amount_com = LemonwayLib::check_amount($amount_com);
		
		$param_list = array( 
			'wallet' => $wallet_id, 
			'amountTot' => $amount, 
			'amountCom' => $amount_com,
			'comment' => $comment,
			'registerCard' => $register_card,
		    
			'wkToken' => $wk_token,
		    
			'autoCommission' => $auto_commission,
		    
			'returnUrl' => $return_url, 
			'errorUrl' => $error_url, 
			'cancelUrl' => $cancel_url 
		);
		
		$result = LemonwayLib::call('MoneyInWebInit', $param_list);
		if ($result !== FALSE) {
			//Retourne : 
			//  - MONEYINWEB => TOKEN
		}
		return $result;
	}
	
	public static function ask_payment_3Dsecure($wallet_id, $card_type, $card_number, $card_code, $card_date, $amount, $amount_com, $wk_token, $return_url, $comment = '', $auto_commission = 0) {
		if (!isset($wallet_id)) return FALSE;
		if (!isset($card_type)) return FALSE;
		if (!isset($card_number)) return FALSE;
		if (!isset($card_code)) return FALSE;
		if (!isset($card_date)) return FALSE;
		if (!isset($amount)) return FALSE;
		if (!isset($amount_com)) return FALSE;
		if (!isset($wk_token)) return FALSE;
		if (!isset($return_url)) return FALSE;
		
		$amount = LemonwayLib::check_amount($amount);
		$amount_com = LemonwayLib::check_amount($amount_com);
		
		$param_list = array( 
			'wallet' => $wallet_id, 
			'amountTot' => $amount, 
			'amountCom' => $amount_com,
			'comment' => $comment,
		    
			'wkToken' => $wk_token,
		    
			'cardType' => $card_type, 
			'cardNumber' => $card_number, 
			'cardCode' => $card_code,
			'cardDate' => $card_date,
			'autoCommission' => $auto_commission,
		    
			'returnUrl' => $return_url 
		);
		
		$result = LemonwayLib::call('MoneyIn3DInit', $param_list);
		if ($result !== FALSE) {
			//Retourne : 
			//  - ACS => actionURL ; actionMethod ; pareqFieldName ; pareqFieldValue ; termurFieldName ; mdFieldName ; mdFieldValue ; mpiResult
			//  - TRANS->HPAY => ID ; MLABEL ; DATE ; SEN ; REC; DEB ; CRED ; COM ; MSG ; STATUS
		}
		return $result;
	}
	
//	public static function complete_payment_3Dsecure($transaction_id, $md, $pares, $card_type, $card_number, $card_code, $card_date) {
	public static function complete_payment_3Dsecure($transaction_id) {
		if (!isset($transaction_id)) return FALSE;
//		if (!isset($md)) return FALSE;
//		if (!isset($pares)) return FALSE;
//		if (!isset($card_type)) return FALSE;
//		if (!isset($card_number)) return FALSE;
//		if (!isset($card_code)) return FALSE;
//		if (!isset($card_date)) return FALSE;
		
		$param_list = array( 
			'transactionId' => $transaction_id
		);
		
		$result = LemonwayLib::call('MoneyIn3DConfirm', $param_list);
		if ($result !== FALSE) {
			//Retourne : 
			//  - TRANS->HPAY => ID ; MLABEL ; DATE ; SEN ; REC ; DEB ; CRED ; COM ; MSG ; STATUS
		}
		return $result;
	}
	
	public static function ask_payment_registered_card($wallet_id, $card_id, $amount, $amount_com = 0, $message = '', $auto_commission = 0) {
		if (!isset($wallet_id)) return FALSE;
		if (!isset($card_id)) return FALSE;
		if (!isset($amount)) return FALSE;
		
		$amount = LemonwayLib::check_amount($amount);
		$amount_com = LemonwayLib::check_amount($amount_com);
		
		$param_list = array(
			'wallet' => $wallet_id,
			'cardId' => $card_id,
			'amountTot' => $amount,
			'amountCom' => $amount_com,
			'message' => $message,
			'autoCommission' => $auto_commission
		);
		
		$result = LemonwayLib::call('MoneyInWithCardId', $param_list);
		if ($result !== FALSE) {
			//Retourne : 
			//  - TRANS->HPAY => ID ; MLABEL ; DATE ; SEN ; REC ; DEB ; CRED ; COM ; MSG ; STATUS
		}
		return $result;
	}
	
	public static function ask_transfer_funds($debit_wallet_id, $credit_wallet_id, $amount, $message = '') {
		if (!isset($debit_wallet_id)) return FALSE;
		if (!isset($credit_wallet_id)) return FALSE;
		if (!isset($amount)) return FALSE;
		
		$amount = LemonwayLib::check_amount($amount);
		
		$param_list = array(
			'debitWallet' => $debit_wallet_id,
			'creditWallet' => $credit_wallet_id,
			'amount' => $amount,
			'message' => $message
		);
		
		$result = LemonwayLib::call('SendPayment', $param_list);
		if ($result !== FALSE) {
			//Retourne : 
			//  - TRANS->HPAY => ID ; DATE ; SEN ; REC ; DEB ; CRED ; COM ; MSG ; STATUS
			$result = $result->TRANS->HPAY;
		}
		return $result;
	}
	
	public static function ask_transfer_to_iban($wallet_id, $amount, $iban_id = 0, $amount_com = 0, $message = '', $auto_commission = 0) {
		if (!isset($wallet_id)) return FALSE;
		if (!isset($amount)) return FALSE;
		
		$amount = LemonwayLib::check_amount($amount);
		$amount_com = LemonwayLib::check_amount($amount_com);
		
		$param_list = array(
			'wallet' => $wallet_id,
			'amountTot' => $amount,
			'amountCom' => $amount_com,
			'message' => $message,
			'autoCommission' => $auto_commission
		);
		if ($iban_id > 0) {
			$param_list['ibanId'] = $iban_id;
		}
		
		$result = LemonwayLib::call('MoneyOut', $param_list);
		if ($result !== FALSE) {
			//Retourne : 
			//  - TRANS->HPAY => ID ; MLABEL ; MID ; DATE ; SEN ; REC ; DEB ; CRED ; COM ; MSG ; STATUS
		}
		return $result;
	}
	
	public static function ask_payment_with_mandate( $wallet_id, $amount, $mandate_id, $amount_com = 0, $comment = '', $auto_commission = 0, $date = '' ) {
		if ( !isset( $wallet_id ) || !isset( $amount ) || !isset( $mandate_id ) ) {
			return FALSE;
		}
		
		$amount = LemonwayLib::check_amount($amount);
		$amount_com = LemonwayLib::check_amount($amount_com);
		
		$param_list = array(
			'wallet' => $wallet_id,
			'amountTot' => $amount,
			'amountCom' => $amount_com,
			'comment' => $comment,
			'autoCommission' => $auto_commission,
			'sddMandateId' => $mandate_id
		);
		
		if ( !empty( $date ) ) {
			$param_list['collectionDate'] = $date;
		}
		
		$result = LemonwayLib::call('MoneyInSddInit', $param_list);
		if ($result !== FALSE) {
			//Retourne : 
			//  - TRANS->HPAY => ID ; MLABEL ; DATE ; SEN ; REC ; DEB ; CRED ; COM ; MSG ; STATUS ; REFUND
		}
		return $result;
		
	}
	
	public static function ask_refund($transaction_id, $amount = 0) {
		if (!isset($transaction_id)) return FALSE;
		
		$param_list = array(
			'transactionId' => $transaction_id
		);
		
		$amount = LemonwayLib::check_amount($amount);
		if ($amount > 0) {
			$param_list['amountToRefund'] = $amount;
		}
		
		$result = LemonwayLib::call('RefundMoneyIn', $param_list);
		if ($result !== FALSE) {
			//Retourne : 
			//  - TRANS->HPAY => ID ; DATE ; SEN ; REC ; DEB ; CRED ; COM ; STATUS
		}
		return $result;
	}
	
/*********************** FIN PAIEMENTS ***********************/
}
