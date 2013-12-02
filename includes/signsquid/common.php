<?php
require_once(dirname(__FILE__) . "/../../../../../signsquid-config.inc");

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
    
    switch ($request_type) {
	case "POST" :
	    $response = file_get_contents($url, null, stream_context_create(array(
		'http' => array(
		    'method' => 'POST',
		    'header' => $header_auth
				. "Content-Type: application/json\r\n"
				. "Content-Length: " . strlen($data_string) . "\r\n",
		    'content' => $data_string
		)
	    )));
	    break;
	case "GET" :
	    $response = file_get_contents($url, null, stream_context_create(array(
		'http' => array(
		    'method' => 'GET',
		    'header' => $header_auth
		)
	    )));
	    break;
	case "POST_FILE" :
	    //In this context, $post_data is the complete file path
	    $filename = basename($post_data);
	    $fh = fopen($post_data, 'r');
	    $ch = curl_init($url . $filename);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream', 'Content-Length: ' . filesize($post_data)));
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $signsquiduserpwd );
		curl_setopt($ch, CURLOPT_INFILE, $fh );
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($post_data) );
		curl_setopt($ch, CURLOPT_VERBOSE, true);
	    $response = curl_exec($ch);
	    $error = curl_error($ch);
	    $errorno = curl_errno($ch);
	    curl_close($ch);
	    fclose($fh);
	    break;
    }
    
    //Transforme en objet json directement exploitable
    $obj = json_decode($response); 
    
    return $obj;
}

/**
 * get all the contracts
 */
function signsquid_get_contract_list() {
    return signsquid_request("GET", "contracts");
}

/**
 * get specific contract
 * @param type $contract_id
 */
function signsquid_get_contract_infos($contract_id) {
    return signsquid_request("GET", "contracts/".$contract_id."/versions/1");
}

/**
 * creates a contract
 * @param type $contract_name
 */
function signsquid_create_contract($contract_name) {
    $data_to_send = array( 'name' => $contract_name);
    $response = signsquid_request("POST", "contracts", $data_to_send);
    $buffer = (isset($response->{'id'})) ? $response->{'id'} : '';
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
    //TODO : vérification sur le numéro de téléphone
    $data_to_send = array( 'name' => $user_name, 'email' => $user_email, 'mobilePhone' => $user_phone);
    $response = signsquid_request("POST", "contracts/".$contract_id."/versions/1/signatories", $data_to_send);
    $buffer = (isset($response->{'message'})) ? '' : TRUE;
    return $buffer;
}

/**
 * add a file to a contract
 * @param type $contract_id
 * @param type $filename
 */
function signsquid_add_file($contract_id, $filename) {
    $response = signsquid_request("POST_FILE", "contracts/".$contract_id."/versions/1/files?filename=", $filename);
    echo '<br />signsquid_add_file<br />';
    print_r($response);
}

/**
 * send invites to a contract signatories
 * @param type $contract_id
 */
function signsquid_send_invite($contract_id) {
    $response = signsquid_request("POST", "contracts/".$contract_id."/versions/1");
    echo '<br />signsquid_send_invite<br />';
    print_r($response);
}
?>