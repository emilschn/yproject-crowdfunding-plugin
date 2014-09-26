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
	 * Supprime une donnée sur le serveur
	 * @param string $request
	 * @param array $request_params
	 * @return object
	 */
	public static function call_delete($request, $request_params = array()) {
		$url = BoppLib::build_url($request);
		$data_string = ($request_params != '') ? json_encode($request_params) : '';
		$ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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
	public static function update_project(
			$id,
			$wp_project_id, 
			$wp_project_name, 
			$wp_project_slogan, 
			$wp_project_description, 
			$wp_project_video, 
			$wp_project_image_video, 
			$wp_project_image_cover, 
			$wp_project_category, 
			$wp_project_business_sector, 
			$wp_project_funding_type, 
			$wp_project_funding_duration, 
			$wp_project_return_investment, 
			$wp_project_investor_benefit, 
			$wp_project_summary, 
			$wp_project_economy_excerpt, 
			$wp_project_social_excerpt, 
			$wp_project_environment_excerpt, 
			$wp_project_mission, 
			$wp_project_economy, 
			$wp_project_social, 
			$wp_project_environment, 
			$wp_project_measure_performance, 
			$wp_project_good_point, 
			$wp_project_content_excerpt, 
			$wp_project_market_excerpt, 
			$wp_project_context, 
			$wp_project_market, 
			$wp_project_worth_offer, 
			$wp_project_client_collaborator, 
			$wp_project_business_core, 
			$wp_project_income, 
			$wp_project_cost, 
			$wp_project_collaborators_canvas, 
			$wp_project_activities_canvas, 
			$wp_project_activities_canvas, 
			$wp_project_ressources_canvas, 
			$wp_project_worth_offer_canvas,
			$wp_project_customers_relations_canvas, 
			$wp_project_chain_distribution_canvas, 
			$wp_project_clients_canvas, 
			$wp_project_structure_canvas, 
			$wp_project_source_income_canvas, 
			$wp_project_financial_board, 
			$wp_project_perspectives, 
			$wp_project_other_information
		) {
		$request_params = array(
			'projects' => array(
				'wpProjectId' => $wp_project_id, 
	            'projectName' => $wp_project_name,
	            'projectSlogan' => $wp_project_slogan,
	            'projectDescription' => $wp_project_description,
	            'projectVideo' => $wp_project_video,
	            'projectImageVideo' => $wp_project_image_video,
	            'projectImageCover' => $wp_project_image_cover,
	            'projectCategory' => $wp_project_category,
	            'projectBusinessSector' => $wp_project_business_sector,
	            'projectFundingType' => $wp_project_funding_type,
	            'projectFundingDuration' => $wp_project_funding_duration,
	            'projectReturnOnInvestment' => $wp_project_return_investment,
	            'projectInvestorBenefit' => $wp_project_investor_benefit,
	            'projectSummary' => $wp_project_summary,
	            'projectEconomyExcerpt' => $wp_project_economy_excerpt,
	            'projectSocialExcerpt' => $wp_project_social_excerpt,
	            'projectEnvironmentExcerpt' => $wp_project_environment_excerpt,
	            'projectMission' => $wp_project_mission,
	            'projectEconomy' => $wp_project_economy,
	            'projectSocial' => $wp_project_social, 
	            'projectEnvironment' => $wp_project_environment,
	            'projectMeasurePerformance' => $wp_project_measure_performance,
	            'projectGoodPoint' => $wp_project_good_point,
	            'projectContextExcerpt' => $wp_project_context_excerpt,
	            'projectMarketExcerpt' => $wp_project_market_excerpt,
	            'projectContext' => $wp_project_context,
	            'projectMarket' => $wp_project_market,
	            'projectWorthOffer' => $wp_project_worth_offer,
	            'projectClientCollaborator' => $wp_project_client_collaborator,
	            'projectBusinessCore' => $wp_project_business_core,
	            'projectIncome' => $wp_project_income,
	            'projectCost' => $wp_project_cost,
	            'projectCollaboratorsCanvas' => $wp_project_collaborators_canvas,
	            'projectActivitiesCanvas' => $wp_project_activities_canvas,
	            'projectRessourcesCanvas' => $wp_project_ressources_canvas,
	            'projectWorthOfferCanvas' => $wp_project_worth_offer_canvas,
	            'projectCustomersRelationsCanvas' => $wp_project_customers_relations_canvas,
	            'projectChainDistributionsCanvas' => $wp_project_chain_distribution_canvas,
	            'projectClientsCanvas' => $wp_project_clients_canvas,
	            'projectCostStructureCanvas' => $wp_project_structure_canvas,
	            'projectSourceOfIncomeCanvas' => $wp_project_source_income_canvas,
	            'projectFinancialBoard' => $wp_project_financial_board,
	            'projectPerspectives' => $wp_project_perspectives,
	            'projectOtherInformation' => $wp_project_other_information
			)
		);
		$result_obj = BoppLib::call_put('projects/' . $id, $request_params);
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
	 * Retourne un projet à partir d'un id
	 * @param string $id
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
		if (isset($result->code) && $result->code == '404') return array();
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
