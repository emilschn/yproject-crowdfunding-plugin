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
		ypcf_debug_log('BoppLib::call_get -- $url : ' . $url);
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_VERBOSE, true);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		ypcf_debug_log('BoppLib::call_get ----> $response : ' . $response);
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
		ypcf_debug_log('BoppLib::call_post -- $url : ' . $url . '  -----  ' . print_r($request_params, TRUE));
		$data_string = ($request_params != '') ? json_encode($request_params) : '';
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_POST, TRUE);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		ypcf_debug_log('BoppLib::call_post ----> $response : ' . $response);
		$error = curl_error($ch);
		ypcf_debug_log('BoppLib::call_post ----> $error : ' . $error);
//		$errorno = curl_errno($ch);
		curl_close($ch);
		$obj = json_decode($response);
		return $obj;
	}
	
	/**
	 * Met à jour toutes les données sur le serveur
	 * @param string $request
	 * @param array $request_params
	 * @return object
	 */
	public static function call_put($request, $request_params = array()) {
		$url = BoppLib::build_url($request);
		$data_string = ($request_params != '') ? json_encode($request_params) : '';
		ypcf_debug_log('BoppLib::call_put -- $url : ' . $url . ' --- ' . $data_string);
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_HEADER, TRUE);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		ypcf_debug_log('BoppLib::call_put ----> $response : ' . $response);
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
	public static function call_patch($request, $request_params = array()) {
		$url = BoppLib::build_url($request);
		ypcf_debug_log('BoppLib::call_patch -- $url : ' . $url);
		$data_string = ($request_params != '') ? json_encode($request_params) : '';
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_HEADER, TRUE);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		ypcf_debug_log('BoppLib::call_patch ----> $response : ' . $response);
//		$error = curl_error($ch);
//		$errorno = curl_errno($ch);
		curl_close($ch);
		$obj = json_decode($response);
		return $obj;
	}
	
	/**
	 * Supprime une donnée sur le serveur
	 * @param string $request
	 * @param array $request_params
	 * @return object
	 */
	public static function call_delete($request, $request_params = array()) {
		$url = BoppLib::build_url($request);
		ypcf_debug_log('BoppLib::call_delete -- $url : ' . $url);
		$data_string = ($request_params != '') ? json_encode($request_params) : '';
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_HEADER, TRUE);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		ypcf_debug_log('BoppLib::call_delete ----> $response : ' . $response);
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
//GESTION PROJETS
	/**
	 * Crée un projet sur l'API
	 * @param type $wp_project_id
	 * @param type $wp_project_name
	 * @return type
	 */
	public static function create_project($wp_project_id, $wp_project_name) {
		$request_params = array(
			'project' => array(
				'project_wp_id' => $wp_project_id, 
				'project_name' => $wp_project_name,
				'project_slogan' => '---',
				'project_description' => '---',
				'project_video_url' => '---',
				'project_image_url' => '---',
				'project_category' => '---',
				'project_business_sector' => '---',
				'project_funding_type' => '---',
				'project_funding_duration' => '---',
				'project_return_on_investment' => '---',
				'project_investor_benefit' => '---',
				'project_summary' => '---',
				'project_economy_excerpt' => '---',
				'project_social_excerpt' => '---',
				'project_environment_excerpt' => '---',
				'project_mission' => '---',
				'project_economy' => '---',
				'project_social' => '---',
				'project_environment' => '---',
				'project_measure_performance' => '---',
				'project_good_point' => '---',
				'project_context_excerpt' => '---',
				'project_market_excerpt' => '---',
				'project_context' => '---',
				'project_market' => '---',
				'project_worth_offer' => '---',
				'project_client_collaborator' => '---',
				'project_business_core' => '---',
				'project_income' => '---',
				'project_cost' => '---',
				'project_collaborators_canvas' => '---',
				'project_activities_canvas' => '---',
				'project_ressources_canvas' => '---',
				'project_worth_offer_canvas' => '---',
				'project_customers_relations_canvas' => '---',
				'project_chain_distribution_canvas' => '---',
				'project_clients_canvas' => '---',
				'project_cost_structure_canvas' => '---',
				'project_source_of_income_canvas' => '---',
				'project_financial_board' => '---',
				'project_perspectives' => '---',
				'project_other_information' => '---'
			)
		);
		$result_obj = BoppLib::call_post('projects', $request_params);
		return $result_obj;
	}
	
	/**
	 * Retourne un projet à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get_project($id) {
		return BoppLib::call_get('projects/' . $id);
	}
	
	/**
	 * Mise à jour du projet à partir d'un id
	 * @param int $id
	 * @param string $first_name
	 * @param string $last_name
	 * @return object
	 */
	public static function update_project($id, $params) {
		$bopp= BoppLib::get_project($id);

		$request_params = array(
			'project' => array()
		);
		$asso_tab = array(
				'project_wp_id' => 'wp_project_id',
				'project_name' => 'wp_project_name',
	            'project_slogan' => 'wp_project_slogan',
	            'project_description' => 'wp_project_description',
	            'project_video_url' => 'wp_project_video',
	            'project_image_url' => 'wp_project_image_cover',
	            'project_category' => 'wp_project_category',
	            'project_business_sector' => 'wp_project_business_sector',
	            'project_funding_type' => 'wp_project_funding_type',
	            'project_funding_duration' => 'wp_project_funding_duration',
	            'project_return_on_investment' => 'wp_project_return_on_investment',
	            'project_investor_benefit' => 'wp_project_investor_benefit',
	            'project_summary' => 'wp_project_summary',
	            'project_economy_excerpt' => 'wp_project_economy_excerpt',
	            'project_social_excerpt' => 'wp_project_social_excerpt',
	            'project_environment_excerpt' => 'wp_project_environment_excerpt',
	            'project_mission' => 'wp_project_mission',
	            'project_economy' => 'wp_project_economy',
	            'project_social' => 'wp_project_social',
	            'project_environment' => 'wp_project_environment',
	            'project_measure_performance' => 'wp_project_measure_performance',
	            'project_good_point' => 'wp_project_good_point',
	            'project_context_excerpt' => 'wp_project_context_excerpt',
	            'project_market_excerpt' => 'wp_project_market_excerpt',
	            'project_context' => 'wp_project_context',
	            'project_market' => 'wp_project_market',
	            'project_worth_offer' => 'wp_project_worth_offer',
	            'project_client_collaborator' => 'wp_project_client_collaborator',
	            'project_business_core' => 'wp_project_business_core',
	            'project_income' => 'wp_project_income',
	            'project_cost' => 'wp_project_cost',
	            'project_collaborators_canvas' => 'wp_project_collaborators_canvas',
	            'project_activities_canvas' => 'wp_project_activities_canvas',
	            'project_ressources_canvas' => 'wp_project_ressources_canvas',
	            'project_worth_offer_canvas' => 'wp_project_worth_offer_canvas',
	            'project_customers_relations_canvas' => 'wp_project_customers_relations_canvas',
	            'project_chain_distribution_canvas' => 'wp_project_chain_distribution_canvas',
	            'project_clients_canvas' => 'wp_project_clients_canvas',
	            'project_cost_structure_canvas' => 'wp_project_structure_canvas',
	            'project_source_of_income_canvas' => 'wp_project_source_income_canvas',
	            'project_financial_board' => 'wp_project_financial_board',
	            'project_perspectives' => 'wp_project_perspectives',
	            'project_other_information' => 'wp_project_other_information'
		);
		foreach ($asso_tab as $key => $value) {
			if ($params[$value] != null) $request_params['project'][$key] = $params[$value];
		}
		$result_obj = BoppLib::call_patch('projects/' . $id, $request_params);
		return $result_obj;
	}


//FIN GESTION PROJETS
//******************************************************************************************//

//******************************************************************************************//
//GESTION ROLES
	/**
	 * Crée un rôle d'utilisateur dans l'api
	 * @param type $title
	 * @param type $slug
	 */
	public static function add_role($title, $slug) {
		$request_params = array(
			'role' => array(
				'role_name' => $title, 
				'role_slug' => $slug
			)
		);
		$result_obj = BoppLib::call_post('roles', $request_params);
		return $result_obj;
	}
	
	/**
	 * Retourne un rôle à partir d'un slug
	 * @param string $api_role_slug
	 * @return object
	 */
	public static function get_role($api_role_slug) {
		return BoppLib::call_get('roles/' . $api_role_slug);
	}
	
	/**
	 * Retourne les utilisateurs liés à un projet par rapport à un rôle
	 * @param int $api_project_id
	 * @param string $api_role_slug
	 * @return array
	 */
	public static function get_project_members_by_role($api_project_id, $api_role_slug) {
		if (!empty($api_project_id) && !empty($api_role_slug)) {
			global $WDG_cache_plugin;
			$url_called = 'projects/' . $api_project_id . '/roles/' . $api_role_slug . '/members';

			$result_cached = $WDG_cache_plugin->get_cache($url_called, 1);
			$result = unserialize($result_cached);
			if ($result_cached === FALSE || empty($result)) {
				$result = BoppLib::call_get($url_called);
				$result_save = serialize($result);
				if (!empty($result_save)) {
					$WDG_cache_plugin->set_cache($url_called, $result_save, 60*60*12, 1);
				}
			}
			if (isset($result->code) && ($result->code == '404' || $result->code == '500')) return array();
			else return $result;
		} else {
			return array();
		}
	}
	
	/**
	 * Lie un utilisateur à un projet en définissant un rôle
	 */
	public static function link_user_to_project($api_project_id, $api_user_id, $api_role_slug) {
		$request_params = array(
			'project_management' => array(
				'boppUser' => $api_user_id, 
				'boppRole' => $api_role_slug
			)
		);
		$result_obj = BoppLib::call_post('projects/'.$api_project_id.'/members', $request_params);
		return $result_obj;
	}
	
	/**
	 * Délie un utilisateur d'un projet
	 * @param int $api_project_id
	 * @param int $api_user_id
	 * @return object
	 */
	public static function unlink_user_from_project($api_project_id, $api_user_id) {
		$request_params = array();
		$result_obj = BoppLib::call_delete('projects/' . $api_project_id . '/members/' . $api_user_id, $request_params);
		return $result_obj;
	}
	
	/**
	 * Retourne les organisations liés à un projet par rapport à un rôle
	 * @param int $api_project_id
	 * @param string $api_role_slug
	 * @return array
	 */
	public static function get_project_organisations_by_role($api_project_id, $api_role_slug) {
		$result = BoppLib::call_get('projects/' . $api_project_id . '/roles/' . $api_role_slug . '/organisations');
		if (isset($result->code) && ($result->code == '404' || $result->code == '500')) return array();
		else return $result;
	}
	
	/**
	 * Lie une organisation à un projet en définissant un rôle
	 * @param int $api_project_id
	 * @param int $api_organisation_id
	 * @param string $api_role_slug
	 * @return object
	 */
	public static function link_organisation_to_project($api_project_id, $api_organisation_id, $api_role_slug) {
		$request_params = array(
			'project_organisation_management' => array(
				'boppOrganisation' => $api_organisation_id, 
				'boppRole' => $api_role_slug
			)
		);
		$result_obj = BoppLib::call_post('projects/'.$api_project_id.'/organisations', $request_params);
		return $result_obj;
	}
	
	/**
	 * Délie une organisation d'un projet
	 * @param int $api_project_id
	 * @param int $api_organisation_id
	 * @return object
	 */
	public static function unlink_organisation_from_project($api_project_id, $api_organisation_id) {
		$request_params = array();
		$result_obj = BoppLib::call_delete('projects/' . $api_project_id . '/organisations/' . $api_organisation_id, $request_params);
		return $result_obj;
	}
//FIN GESTION ROLES
//******************************************************************************************//



}
