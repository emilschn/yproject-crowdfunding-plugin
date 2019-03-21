<?php
require_once(dirname(__FILE__) . "/../../../../../../signsquid-config.inc");

/*if (!function_exists('ypcf_debug_log')) {
    function ypcf_debug_log($log) {
	echo $log . '<br />';
    }
}*/

/**
 * General function to make request to signsquid
 * @global type $signsquidBaseURL : defined somewhere else to call signsquid API
 * @global type $signsquiduserpwd : defined somewhere else : user/pwd to access signsquid API
 * @param type $request_type : 3 different request types : GET, POST, POST_FILES
 * @param type $request_object : what we're trying to request from signsquid
 * @param type $post_data : data we're trying to send to signsquid
 * @return type
 */
function signsquid_request($request_type, $request_object, $post_data = '') {
    global $signsquidBaseURL, $signsquiduserpwd;
    
    $url = $signsquidBaseURL . $request_object;
    $data_string = ($post_data != '') ? json_encode($post_data) : '';
    $header_auth = "Authorization: Basic " . base64_encode($signsquiduserpwd) . "\r\n";
    
    //DEBUG LOG
    ypcf_debug_log("signsquid_request --- REQUEST :: ".$url." (" . $request_type . ") => " . $data_string);
    $error = '';
    $errorno = '';
    
    switch ($request_type) {
	case "POST" :
	    $ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $signsquiduserpwd );
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($ch);
	    $error = curl_error($ch);
	    $errorno = curl_errno($ch);
	    curl_close($ch);
	    break;
	case "GET" :
	    $response = @file_get_contents($url, null, stream_context_create(array(
		'http' => array(
		    'method' => 'GET',
		    'header' => $header_auth
		)
	    )));
	    //Transforme en objet json directement exploitable
	    $obj = json_decode($response);
	    break;
	case "POST_FILE" :
	    //In this context, $post_data is the complete file path
	    $filename = basename($post_data);
	    $fh = fopen($post_data, 'r');
	    $ch = curl_init($url . $filename);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream', 'Content-Length: ' . filesize($post_data)));
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $signsquiduserpwd );
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_INFILE, $fh );
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($post_data) );
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($ch);
	    $error = curl_error($ch);
	    $errorno = curl_errno($ch);
	    curl_close($ch);
	    fclose($fh);
	    break;
    }
    
    //DEBUG LOG
    ypcf_debug_log("signsquid_request --- RESPONSE :: ".$response." (".$error." ; ".$errorno.")");
    
    if (isset($obj)) return $obj;
    else return $response;
}

/**
 * Parse les headers reçus
 * @param type $response
 * @return type
 */
function get_headers_from_curl_response($response) {
    $headers = array();
    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

    foreach (explode("\r\n", $header_text) as $i => $line) {
        if ($i === 0) {
            $headers['http_code'] = $line;
	} else {
            list ($key, $value) = explode(': ', $line);
            $headers[$key] = $value;
        }
    }
    return $headers;
}

/**
 * get all the contracts
 */
function signsquid_get_contract_list() {
    ypcf_debug_log('signsquid_get_contract_list');
    return signsquid_request("GET", "contracts");
}

/**
 * retourne la première version d'un contrat
 * @param type $contract_id
 */
function signsquid_get_contract_infos($contract_id) {
    ypcf_debug_log('signsquid_get_contract_infos');
    $buffer = '';
    if ($contract_id != '') {
	$buffer = signsquid_request("GET", "contracts/".$contract_id."/versions/1");
	if (!isset($buffer->{'published'}) || $buffer->{'published'} != true) {
	    $buffer = '';
	    ypcf_debug_log('signsquid_get_contract_infos --- ERROR :: Wrong $contract_id called');
	}
    } else {
	ypcf_debug_log('signsquid_get_contract_infos --- ERROR :: $contract_id empty');
    }
    return $buffer;
}

/**
 * retourne toutes les informations d'un contrat
 * @param type $contract_id
 * @return string
 */
function signsquid_get_contract_infos_complete($contract_id) {
    ypcf_debug_log('signsquid_get_contract_infos_complete');
    $buffer = '';
    if ($contract_id != '') {
	$buffer = signsquid_request("GET", "contracts/".$contract_id);
	if (!isset($buffer->{'id'}) || $buffer->{'id'} == '') {
	    $buffer = '';
	    ypcf_debug_log('signsquid_get_contract_infos_complete --- ERROR :: Wrong $contract_id called');
	}
    } else {
	ypcf_debug_log('signsquid_get_contract_infos_complete --- ERROR :: $contract_id empty');
    }
    return $buffer;
}

/**
 * retourne le signataire de la dernière version du contrat
 * @param type $contract_id
 * @return type
 */
function signsquid_get_contract_signatory($contract_id) {
    ypcf_debug_log('signsquid_get_contract_signatory');
    $buffer = '';
    $contract_infos = signsquid_get_contract_infos_complete($contract_id);
    if ($contract_infos != '') {
	if (isset($contract_infos->{'versions'}) && count($contract_infos->{'versions'}) > 0) {
	    $last_contract = $contract_infos->{'versions'}[count($contract_infos->{'versions'}) - 1];
	    $buffer = $last_contract->{'signatories'}[0];
	    ypcf_debug_log('signsquid_get_contract_signatory --- $buffer name : ' . $buffer->{'name'});
	}
    }
    return $buffer;
}

/**
 * creates a contract
 * @param type $contract_name
 */
function signsquid_create_contract($contract_name) {
    ypcf_debug_log('signsquid_create_contract');
    $buffer = '';
    $data_to_send = array( 'name' => $contract_name);
    $response = signsquid_request("POST", "contracts", $data_to_send);
    $headers = get_headers_from_curl_response($response);
    if (isset($headers['Location'])) {
	$buffer = basename($headers['Location']);
    } else {
	ypcf_debug_log('signsquid_create_contract --- ERROR');
    }
    return $buffer;
}

/**
 * add a signatory to a contract
 * @param type $contract_id
 * @param type $user_name
 * @param type $user_email
 * @param type $user_phone
 */
function signsquid_add_signatory($contract_id, $user_name, $user_email, $user_phone = '') {
    ypcf_debug_log('signsquid_add_signatory');
    $data_to_send = array( 'name' => $user_name, 'email' => $user_email, 'mobilePhone' => $user_phone);
    $response = signsquid_request("POST", "contracts/".$contract_id."/versions/1/signatories", $data_to_send);
    $headers = get_headers_from_curl_response($response);
    if ($headers['http_code'] == 'HTTP/1.1 201 Created') {
	$buffer = TRUE;
    } else {
	ypcf_debug_log('signsquid_add_signatory --- ERROR');
	$buffer = FALSE;
    }
    return $buffer;
}

/**
 * add a file to a contract
 * @param type $contract_id
 * @param type $filename
 */
function signsquid_add_file($contract_id, $filename) {
    ypcf_debug_log('signsquid_add_file');
    $response = signsquid_request("POST_FILE", "contracts/".$contract_id."/versions/1/files?filename=", $filename);
    $headers = get_headers_from_curl_response($response);
    if ($headers['http_code'] == 'HTTP/1.1 100 Continue') {
	$buffer = TRUE;
    } else {
	ypcf_debug_log('signsquid_add_file --- ERROR');
	$buffer = FALSE;
    }
    return $buffer;
}

/**
 * send invites to a contract signatories
 * @param type $contract_id
 */
function signsquid_send_invite($contract_id) {
    ypcf_debug_log('signsquid_send_invite');
    $response = signsquid_request("POST", "contracts/".$contract_id."/versions/1");
    $headers = get_headers_from_curl_response($response);
    if ($headers['http_code'] == 'HTTP/1.1 200 OK') {
	$buffer = TRUE;
    } else {
	ypcf_debug_log('signsquid_send_invite --- ERROR');
	$buffer = FALSE;
    }
    return $buffer;
}



class SignsquidContract {
	private $payment_id;
	private $payment_amount;
	private $contract_id;
	private $status_code;
	private $status_str;
	private $signing_code;
	
	private static $status_str_array = array(
		'Small' => 'Investissement valid&eacute;',
		'NotPublished' => 'Contrat non-cr&eacute;&eacute;',
		'WaitingForSignatoryAction' => 'En attente de signature',
		'Refused' => 'Contrat refus&eacute;',
		'Agreed' => 'Contrat sign&eacute;',
		'NewVersionAvailable' => 'Contrat mis &agrave; jour'
	);
    
	public function __construct( $payment_id, $contract_id = FALSE ) {
		if ( !empty( $payment_id ) ) {
			$this->payment_id = $payment_id;
			$this->payment_amount = edd_get_payment_amount($this->payment_id);
			$this->contract_id = get_post_meta($payment_id, 'signsquid_contract_id', true);
			
		} else {
			$this->contract_id = $contract_id;
		}
		
		$this->update_status_code();
	}
	
	/**
	 * Retourne l'identifiant du contrat sur Signsquid
	 */
	public function get_contract_id() {
		return $this->contract_id;
	}
	
	/**
	 * Retourne le statut du contrat
	 * @return type
	 */
	public function get_status_code() {
		return $this->status_code;
	}
	
	/**
	 * Retourne le statut du contrat sous forme de chaine lisible
	 * @return type
	 */
	public function get_status_str() {
		return $this->status_str;
	}
	
	/**
	 * Retourne le code de signature lié au contrat
	 */
	public function get_signing_code() {
		return $this->signing_code;
	}
    
	/**
	 * Initialisation du statut du contrat
	 * @return type
	 */
	public function update_status_code() {
		$this->status_code = FALSE;
		
		//Si c'est une petite somme, on ne fait pas de vérification, c'est ok !
		if ( isset( $this->payment_amount ) && $this->payment_amount <= 1500 ) {
			$this->status_code = "Small";
			
		} else {
			//On teste si le contrat a déjà été signé
			$status_payment_agreed = get_post_meta($this->payment_id, 'signsquid_contract_agreed', true);
			if ($status_payment_agreed == "Agreed") {
				$this->status_code = $status_payment_agreed;

			//Sinon, on va chercher sur Signsquid
			} else {
				//Récupération de l'id du contrat préalablement créé
				$contract_infos = $this->get_complete_infos();
				if ($contract_infos != FALSE) {
					$this->status_code = $contract_infos->{'status'};
					$this->signing_code = $contract_infos->{'signatories'}[0]->{'code'};
					if ( empty( $this->signing_code ) ) {
						$this->signing_code = $contract_infos->{'versions'}[0]->{'signatories'}[0]->{'code'};
					}
					update_post_meta($this->payment_id, 'signsquid_contract_agreed', $this->status_code);
				}
			}
		}
		
		$this->update_status_str();
	}
	
	/**
	 * Retourne un statut lisible en fonction d'un code transmis
	 * @param type $code
	 * @return type
	 */
	public static function get_status_str_by_code( $code ) {
		$buffer = '- Pas de contrat -';
		if ( isset( SignsquidContract::$status_str_array[ $code ] ) ) {
			$buffer = SignsquidContract::$status_str_array[ $code ];
		}
		return $buffer;
	}
	
	/**
	 * Initialisation de la chaine lisible pour le statut du contrat
	 */
	public function update_status_str() {
		$this->status_str = SignsquidContract::get_status_str_by_code( $this->status_code );
	}
	
	/**
	 * Retourne les infos du contrat en ligne
	 * @return string
	 */
	public function get_complete_infos() {
		ypcf_debug_log('SignsquidContract:get_complete_infos : ' .$this->contract_id);
		$buffer = FALSE;
		if (!empty($this->contract_id)) {
			$buffer = signsquid_request("GET", "contracts/" . $this->contract_id);
			if (!isset($buffer->{'id'}) || $buffer->{'id'} == '') {
				$buffer = FALSE;
				ypcf_debug_log('SignsquidContract:get_complete_infos --- ERROR :: Wrong $contract_id called');
			}
			
		} else {
			ypcf_debug_log('SignsquidContract:get_complete_infos --- ERROR :: $contract_id empty');
		}
		return $buffer;
	}
}