<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $bopp_lib;
$bopp_lib = new BoppLib();

/**
 * Classe de gestion de Lemonway
 */
class BoppLib {
	public $params, $last_error;
    
	/**
	 * Initialise les données à envoyer à Lemonway
	 */
	public function __construct() {
		$this->params = array(
			'api_url' => YP_SYM_API_URL,
			'api_key' => YP_SYM_API_KEY
		);
		$this->last_error = FALSE;
	}
	
	/**
	 * Va récupérer une donnée sur le serveur
	 * @param string $request
	 * @return object
	 */
	public static function call_get($request) {
		$url = BoppLib::build_url($request);
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_VERBOSE, true);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		$obj = json_decode($response);
		return $obj;
	}
	
	/**
	 * Crée une donnée sur le serveur
	 * @param string $request
	 * @param array $request_params
	 * @return object
	 */
	public static function call_post($request, $request_params = array()) {
		$url = BoppLib::build_url($request);
		$data_string = ($request_params != '') ? json_encode($request_params) : '';
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_POST, TRUE);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_HEADER, TRUE);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
//		$error = curl_error($ch);
//		$errorno = curl_errno($ch);
		curl_close($ch);
		$obj = json_decode($response);
		return $obj;
	}
	
	/**
	 * Met à jour une donnée sur le serveur
	 * @param string $request
	 * @param array $request_params
	 * @return object
	 */
	public static function call_put($request, $request_params = array()) {
		$url = BoppLib::build_url($request);
		$data_string = ($request_params != '') ? json_encode($request_params) : '';
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_HEADER, TRUE);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
//		$error = curl_error($ch);
//		$errorno = curl_errno($ch);
		curl_close($ch);
		$obj = json_decode($response);
		return $obj;
	}
	
	/**
	 * Retourne l'url à appeler
	 * @global BoppLib $bopp_lib
	 * @param string $request
	 * @return string
	 */
	public static function build_url($request) {
		global $bopp_lib;
		return $bopp_lib->params['api_url'] . $bopp_lib->params['api_key'] . '/' . $request;
	}
	
	/**
	 * TODO : Parse le retour pour déterminer si il y a des erreurs et les enregistrer si c'est le cas
	 * @param type $result_obj
	 * @return boolean
	 */
	public static function has_errors($result_obj) {
		$buffer = false;
		return $buffer;
	}



//******************************************************************************************//
//GESTION UTILISATEURS
	/**
	 * Crée un utilisateur sur l'API
	 * @param string $first_name
	 * @param string $last_name
	 * @return object
	 */
	public static function create_user($wp_user_id, $first_name, $last_name) {
		$request_params = array(
			'users' => array(
				'wpUserId' => $wp_user_id,
				'userName' => $first_name,
				'userSurname' => $last_name
			)
		);
		$result_obj = BoppLib::call_post('users', $request_params);
		return $result_obj;
	}
	
	/**
	 * Retourne un utilisateur à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get_user($id) {
		return BoppLib::call_get('users/' . $id);
	}
	
	/**
	 * Mise à jour de l'utilisateur à partir d'un id
	 * @param int $id
	 * @param string $first_name
	 * @param string $last_name
	 * @return object
	 */
	public static function update_user($id, $first_name, $last_name) {
		$request_params = array(
			'users' => array(
				'userName' => $first_name,
				'userSurname' => $last_name
			)
		);
		$result_obj = BoppLib::call_put('users/' . $id, $request_params);
		return $result_obj;
	}

//FIN GESTION UTILISATEURS
//******************************************************************************************//

}
