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
		ypcf_debug_log('BoppLib::call_post -- $url : ' . $url);
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
		ypcf_debug_log('BoppLib::call_put -- $url : ' . $url);
		$data_string = ($request_params != '') ? json_encode($request_params) : '';
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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
	
	/**
	 * Récupère la liste des projets auxquels est lié un utilisateur par un certain rôle
	 * @param int $id
	 * @param string $role_slug
	 * @return object
	 */
	public static function get_user_projects_by_role($id, $role_slug) {
		$project_list = BoppLib::call_get('users/' . $id . '/roles/' . $role_slug);
		if (!isset($project_list->code)) return $project_list;
		else return array();
	}

//FIN GESTION UTILISATEURS
//******************************************************************************************//

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
			'projects' => array(
				'wpProjectId' => $wp_project_id, 
				'projectName' => $wp_project_name
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

/*		if ($params['wp_project_name'] == null) {
			$params['wp_project_name'] = $bopp->project_name;
		}

		if ($params['wp_project_slogan'] == null) {
			$params['wp_project_slogan'] = $bopp->project_slogan;
		}

		if ($params['wp_project_description'] == null) {
			$params['wp_project_description'] = $bopp->project_description;
		}

		if ($params['wp_project_video'] == null) {
			$params['wp_project_video'] = $bopp->project_video;
		}

		if ($params['wp_project_category'] == null) {
			$params['wp_project_category'] = $bopp->project_category;
		}

		if ($params['wp_project_business_sector'] == null) {
			$params['wp_project_business_sector'] = $bopp->project_business_sector;
		}

		if ($params['wp_project_funding_type'] == null) {
			$params['wp_project_funding_type'] = $bopp->project_funding_type;
		}

		if ($params['wp_project_funding_duration'] == null) {
			$params['wp_project_funding_duration'] = $bopp->project_funding_duration;
		}

		if ($params['wp_project_return_on_investment'] == null) {
			$params['wp_project_return_on_investment'] = $bopp->project_return_on_investment;
		}

		if ($params['wp_project_investor_benefit'] == null) {
			$params['wp_project_investor_benefit'] = $bopp->project_investor_benefit;
		}

		if ($params['wp_project_summary'] == null) {
			$params['wp_project_summary'] = $bopp->project_summary;
		}

		if ($params['wp_project_economy_excerpt'] == null) {
			$params['wp_project_economy_excerpt'] = $bopp->project_economy_excerpt;
		}

		if ($params['wp_project_social_excerpt'] == null) {
			$params['wp_project_social_excerpt'] = $bopp->project_social_excerpt;
		}

		if ($params['wp_project_environment_excerpt'] == null) {
			$params['wp_project_environment_excerpt'] = $bopp->project_environment_excerpt;
		}

		if ($params['wp_project_mission'] == null) {
			$params['wp_project_mission'] = $bopp->project_mission;
		}

		if ($params['wp_project_economy'] == null) {
			$params['wp_project_economy'] = $bopp->project_economy;
		}

		if ($params['wp_project_social'] == null) {
			$params['wp_project_social'] = $bopp->project_social;
		}

		if ($params['wp_project_environment'] == null) {
			$params['wp_project_environment'] = $bopp->project_environment;
		}

		if ($params['wp_project_measure_performance'] == null) {
			$params['wp_project_measure_performance'] = $bopp->project_measure_performance;
		}

		if ($params['wp_project_good_point'] == null) {
			$params['wp_project_good_point'] = $bopp->project_good_point;
		}

		if ($bopp->project_context_excerpt != null) {
			$params['wp_project_context_excerpt'] = $bopp->project_context_excerpt;
		}

		if ($bopp->project_market_excerpt != null) {
			$params['wp_project_market_excerpt'] = $bopp->project_market_excerpt;
		}

		if ($bopp->project_context != null) {
			$params['wp_project_context'] = $bopp->project_context;
		}

		if ($bopp->project_market != null) {
			$params['wp_project_market'] = $bopp->project_market;
		}

		if ($params['wp_project_worth_offer'] == null) {
			$params['wp_project_worth_offer'] = $bopp->project_worth_offer;
		}

		if ($params['wp_project_client_collaborator'] == null) {
			$params['wp_project_client_collaborator'] = $bopp->project_client_collaborator;
		}

		if ($params['wp_project_business_core'] == null) {
			$params['wp_project_business_core'] = $bopp->project_business_core;
		}

		if ($params['wp_project_income'] == null) {
			$params['wp_project_income'] = $bopp->project_income;
		}

		if ($params['wp_project_cost'] == null) {
			$params['wp_project_cost'] = $bopp->project_cost;
		}

		if ($params['wp_project_collaborators_canvas'] == null) {
			$params['wp_project_collaborators_canvas'] = $bopp->project_collaborators_canvas;
		}

		if ($params['wp_project_activities_canvas'] == null) {
			$params['wp_project_activities_canvas'] = $bopp->project_activities_canvas;
		}

		if ($params['wp_project_ressources_canvas'] == null) {
			$params['wp_project_ressources_canvas'] = $bopp->project_ressources_canvas;
		}

		if ($params['wp_project_worth_offer_canvas'] == null) {
			$params['wp_project_worth_offer_canvas'] = $bopp->project_worth_offer_canvas;
		}

		if ($params['wp_project_customers_relations_canvas'] == null) {
			$params['wp_project_customers_relations_canvas'] = $bopp->project_customers_relations_canvas;
		}

		if ($params['wp_project_chain_distribution_canvas'] == null) {
			$params['wp_project_chain_distribution_canvas'] = $bopp->project_chain_distribution_canvas;
		}

		if ($params['wp_project_clients_canvas'] == null) {
			$params['wp_project_clients_canvas'] = $bopp->project_clients_canvas;
		}

		if ($params['wp_project_structure_canvas'] == null) {
			$params['wp_project_structure_canvas'] = $bopp->project_structure_canvas;
		}

		if ($params['wp_project_source_income_canvas'] == null) {
			$params['wp_project_source_income_canvas'] = $bopp->project_source_income_canvas;
		}

		if ($params['wp_project_financial_board'] == null) {
			$params['wp_project_financial_board'] = $bopp->project_financial_board;
		}

		if ($params['wp_project_perspectives'] == null) {
			$params['wp_project_perspectives'] = $bopp->project_perspectives;
		}

		if ($params['wp_project_other_information'] == null) {
			$params['wp_project_other_information'] = $bopp->project_other_information;
		}*/

		$request_params = array(
			'projects' => array(
				'wpProjectId' => $params['wp_project_id'], 
	            'projectName' => $params['wp_project_name'],
	            'projectSlogan' => $params['wp_project_slogan'],
	            'projectDescription' => $params['wp_project_description'],
	            'projectVideo' => $params['wp_project_video'],
	            'projectImageVideo' => $params['wp_project_image_video'],
	            'projectImageCover' => $params['wp_project_image_cover'],
	            'projectCategory' => $params['wp_project_category'],
	            'projectBusinessSector' => $params['wp_project_business_sector'],
	            'projectFundingType' => $params['wp_project_funding_type'],
	            'projectFundingDuration' => $params['wp_project_funding_duration'],
	            'projectReturnOnInvestment' => $params['wp_project_return_on_investment'],
	            'projectInvestorBenefit' => $params['wp_project_investor_benefit'],
	            'projectSummary' => $params['wp_project_summary'],
	            'projectEconomyExcerpt' => $params['wp_project_economy_excerpt'],
	            'projectSocialExcerpt' => $params['wp_project_social_excerpt'],
	            'projectEnvironmentExcerpt' => $params['wp_project_environment_excerpt'],
	            'projectMission' => $params['wp_project_mission'],
	            'projectEconomy' => $params['wp_project_economy'],
	            'projectSocial' => $params['wp_project_social'],
	            'projectEnvironment' => $params['wp_project_environment'],
	            'projectMeasurePerformance' => $params['wp_project_measure_performance'],
	            'projectGoodPoint' => $params['wp_project_good_point'],
	            'projectContextExcerpt' => $params['wp_project_context_excerpt'],
	            'projectMarketExcerpt' => $params['wp_project_market_excerpt'],
	            'projectContext' => $params['wp_project_context'],
	            'projectMarket' => $params['wp_project_market'],
	            'projectWorthOffer' => $params['wp_project_worth_offer'],
	            'projectClientCollaborator' => $params['wp_project_client_collaborator'],
	            'projectBusinessCore' => $params['wp_project_business_core'],
	            'projectIncome' => $params['wp_project_income'],
	            'projectCost' => $params['wp_project_cost'],
	            'projectCollaboratorsCanvas' => $params['wp_project_collaborators_canvas'],
	            'projectActivitiesCanvas' => $params['wp_project_activities_canvas'],
	            'projectRessourcesCanvas' => $params['wp_project_ressources_canvas'],
	            'projectWorthOfferCanvas' => $params['wp_project_worth_offer_canvas'],
	            'projectCustomersRelationsCanvas' => $params['wp_project_customers_relations_canvas'],
	            'projectChainDistributionsCanvas' => $params['wp_project_chain_distribution_canvas'],
	            'projectClientsCanvas' => $params['wp_project_clients_canvas'],
	            'projectCostStructureCanvas' => $params['wp_project_structure_canvas'],
	            'projectSourceOfIncomeCanvas' => $params['wp_project_source_income_canvas'],
	            'projectFinancialBoard' => $params['wp_project_financial_board'],
	            'projectPerspectives' => $params['wp_project_perspectives'],
	            'projectOtherInformation' => $params['wp_project_other_information']
			)
		);
		//var_dump($request_params);
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
			'roles' => array(
				'roleName' => $title, 
				'roleSlug' => $slug
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
		$result = BoppLib::call_get('projects/' . $api_project_id . '/roles/' . $api_role_slug . '/members');
		if (isset($result->code) && ($result->code == '404' || $result->code == '500')) return array();
		else return $result;
	}
	
	/**
	 * Lie un utilisateur à un projet en définissant un rôle
	 */
	public static function link_user_to_project($api_project_id, $api_user_id, $api_role_slug) {
		$request_params = array(
			'projectsUsers' => array(
				'users' => $api_user_id, 
				'roles' => $api_role_slug
			)
		);
		$result_obj = BoppLib::call_post('projects/'.$api_project_id.'/members', $request_params);
		return $result_obj;
	}
	
	/**
	 * Délie un utilisateur d'un projet
	 * @param type $api_project_id
	 * @param type $api_user_id
	 */
	public static function unlink_user_from_project($api_project_id, $api_user_id) {
		$request_params = array();
		$result_obj = BoppLib::call_delete('projects/' . $api_project_id . '/members/' . $api_user_id, $request_params);
		return $result_obj;
	}
//FIN GESTION ROLES
//******************************************************************************************//



}
