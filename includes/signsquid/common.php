<?php
require_once(dirname(__FILE__) . "/../../../../../signsquid-config.inc");

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
    return signsquid_request("GET", "contracts");
}

/**
 * retourne la première version d'un contrat
 * @param type $contract_id
 */
function signsquid_get_contract_infos($contract_id) {
    $buffer = '';
    if ($contract_id != '') $buffer = signsquid_request("GET", "contracts/".$contract_id."/versions/1");
    else ypcf_debug_log('signsquid_get_contract_infos --- ERROR :: $contract_id empty');
    if (!isset($buffer->{'published'}) || $buffer->{'published'} != true) {
	$buffer = '';
	ypcf_debug_log('signsquid_get_contract_infos --- ERROR :: Wrong $contract_id called');
    }
    return $buffer;
}

/**
 * retourne toutes les informations d'un contrat
 * @param type $contract_id
 * @return string
 */
function signsquid_get_contract_infos_complete($contract_id) {
    $buffer = '';
    if ($contract_id != '') $buffer = signsquid_request("GET", "contracts/".$contract_id);
    else ypcf_debug_log('signsquid_get_contract_infos_complete --- ERROR :: $contract_id empty');
    if (!isset($buffer->{'id'}) || $buffer->{'id'} == '') {
	$buffer = '';
	ypcf_debug_log('signsquid_get_contract_infos_complete --- ERROR :: Wrong $contract_id called');
    }
    return $buffer;
}

/**
 * retourne le signataire de la dernière version du contrat
 * @param type $contract_id
 * @return type
 */
function signsquid_get_contract_signatory($contract_id) {
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
?>