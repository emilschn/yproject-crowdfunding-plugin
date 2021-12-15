<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestion des projets côté WDGWPREST
 */
class WDGWPREST_Entity_Project {
	public static $link_user_type_team = 'team';
	public static $link_organization_type_manager = 'manager';

	/**
	 * Retourne un projet à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get($id) {
		if ( empty( $id ) ) {
			return FALSE;
		}

		return WDGWPRESTLib::call_get_wdg( 'project/' .$id. '?with_investments=1&with_organization=1&with_poll_answers=1' );
	}

	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param ATCF_Campaign $campaign
	 * @return array
	 */
	public static function set_post_parameters(ATCF_Campaign $campaign) {
		$vote_results = WDGCampaignVotes::get_results( $campaign->ID );
		$list_date = $vote_results['list_date'];
		$beginvotedate = date_create( $list_date[0] );
		$file_name_contract_orga = $campaign->backoffice_contract_orga();
		if ( !empty( $file_name_contract_orga ) ) {
			$file_name_exploded = explode( '.', $file_name_contract_orga );
			$file_name_contract_orga = site_url() . '/wp-content/plugins/appthemer-crowdfunding/includes/contracts/' . $file_name_contract_orga;
		}
		$file_name_bp = $campaign->backoffice_businessplan();
		if ( !empty( $file_name_bp ) ) {
			$file_name_exploded = explode( '.', $file_name_bp );
			$file_name = site_url() . '/wp-content/plugins/appthemer-crowdfunding/includes/kyc/' . $file_name_bp;
		}
		$estimated_turnover = $campaign->estimated_turnover();
		$estimated_turnover_param = json_encode( $estimated_turnover );
		$estimated_sales = $campaign->estimated_sales();
		$estimated_sales_param = json_encode( $estimated_sales );
		$can_go_next_str = $campaign->can_go_next_status() ? 1 : 0;
		$dt_first_payment_date = new DateTime( $campaign->first_payment_date() );
		$first_payment_date = $dt_first_payment_date->format( 'Y-m-d' );

		$parameters = array(
			'wpref'				=> $campaign->ID,
			'name'				=> $campaign->data->post_title,
			'url'				=> $campaign->get_url(),
			'status'			=> $campaign->campaign_status(),
			'description'		=> $campaign->backoffice_summary(),
			'can_go_next'		=> $can_go_next_str,
			'type'				=> $campaign->get_categories_by_type( 'types', TRUE ),
			'category'			=> $campaign->get_categories_by_type( 'activities', TRUE ),
			'impacts'			=> $campaign->get_categories_by_type( 'categories', TRUE ),
			'partners'			=> $campaign->get_categories_by_type( 'partners', TRUE ),
			'tousnosprojets'	=> $campaign->get_categories_by_type( 'tousnosprojets', TRUE ),
			'amount_collected'	=> $campaign->current_amount( FALSE ),
			'roi_percent_estimated'	=> $campaign->roi_percent_estimated(),
			'roi_percent'			=> $campaign->roi_percent(),
			'estimated_budget_file'	=> $file_name_bp,
			'funding_duration'		=> $campaign->funding_duration(),
			'funding_duration_infinite_estimation'		=> $campaign->funding_duration_infinite_estimation(),
			'declaration_periodicity'	=> $campaign->get_declaration_periodicity(),
			'goal_minimum'			=> $campaign->minimum_goal(),
			'goal_maximum'			=> $campaign->goal( FALSE ),
			'yield_for_investors'	=> '1', //TODO
			'maximum_profit'		=> $campaign->maximum_profit(),
			'maximum_profit_precision'	=> $campaign->maximum_profit_precision(),
			'minimum_profit'		=> $campaign->minimum_profit(),
			'contract_start_date'	=> $campaign->contract_start_date(),
			'contract_start_date_is_undefined'	=> $campaign->contract_start_date_is_undefined(),
			'declarations_start_date'	=> $first_payment_date,
			'spendings_description'	=> $campaign->contract_spendings_description(),
			'earnings_description'	=> $campaign->contract_earnings_description(),
			'simple_info'			=> $campaign->contract_simple_info(),
			'detailed_info'			=> $campaign->contract_detailed_info(),
			'total_previous_funding_description'	=> $campaign->total_previous_funding_description(),
			'total_previous_funding'		=> $campaign->total_previous_funding(),
			'turnover_previous_year'		=> $campaign->turnover_previous_year(),
			'working_capital_sufficient'	=> $campaign->has_sufficient_working_capital() ? '1' : '0',
			'working_capital_subsequent'	=> $campaign->working_capital_subsequent(),
			'financial_risks_others'		=> $campaign->financial_risks_others(),
			'estimated_turnover'	=> $estimated_turnover_param,
			'estimated_sales'		=> $estimated_sales_param,
			'blank_contract_file'	=> $file_name_contract_orga,
			'vote_start_datetime'	=> date_format( $beginvotedate, 'Y-m-d H:i:s'),
			'vote_end_datetime'		=> $campaign->end_vote(),
			'vote_count'			=> $campaign->nb_voters(),
			'vote_invest_amount'	=> $vote_results[ 'sum_invest_ready' ],
			'funding_start_datetime'	=> $campaign->begin_collecte_date(),
			'funding_end_datetime'		=> $campaign->end_date(),
			'investments_count'			=> $campaign->backers_count(),
			'minimum_costs_to_organization'	=> $campaign->get_minimum_costs_to_organization(),
			'costs_to_organization'		=> $campaign->get_costs_to_organization(),
			'costs_to_investors'		=> $campaign->get_costs_to_investors(),
			'turnover_per_declaration'	=> $campaign->get_turnover_per_declaration(),
			'team_contacts'			=> $campaign->team_contacts(),
			'employees_number'		=> $campaign->get_api_data( 'employees_number' ),
			'minimum_goal_display'	=> $campaign->get_minimum_goal_display(),
			'common_goods_turnover_percent'	=> $campaign->get_api_data( 'common_goods_turnover_percent' ),
			'product_type'					=> $campaign->get_api_data( 'product_type' ),
			'acquisition'					=> $campaign->get_api_data( 'acquisition' ),
			'legal_procedure'				=> $campaign->get_api_data( 'legal_procedure' ),
			'funding_type'					=> $campaign->funding_type(),
			'organization_type'				=> $campaign->get_api_data( 'organization_type' )
		);

		return $parameters;
	}

	/**
	 * Crée un projet sur l'API
	 * @param ATCF_Campaign $campaign
	 * @return object
	 */
	public static function create(ATCF_Campaign $campaign) {
		$date = new DateTime("NOW");

		$parameters = array(
			'wpref'				=> $campaign->ID,
			'name'				=> $campaign->data->post_title,
			'url'				=> $campaign->data->post_name,
			'creation_date'		=> $date->format('Y-m-d')
		);

		$result_obj = WDGWPRESTLib::call_post_wdg( 'project', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) {
			$result_obj = '';
		}

		return $result_obj;
	}

	/**
	 * Mise à jour du projet à partir d'un id
	 * @param ATCF_Campaign $campaign
	 * @return object
	 */
	public static function update(ATCF_Campaign $campaign) {
		$buffer = FALSE;

		$api_id = $campaign->get_api_id();
		if ( !empty( $api_id ) ) {
			$parameters = WDGWPREST_Entity_Project::set_post_parameters( $campaign );

			$buffer = WDGWPRESTLib::call_post_wdg( 'project/' . $campaign->get_api_id(), $parameters );
			WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$campaign->get_api_id(). '?with_investments=1&with_organization=1&with_poll_answers=1' );
			if ( isset( $buffer->code ) && $buffer->code == 400 ) {
				$buffer = FALSE;
			}
		}

		return $buffer;
	}

	/**
	 * Mise à jour d'une donnée particulière d'un projet
	 * @param int $project_id
	 * @param string $data_name
	 * @param string $data_value
	 * @return string
	 */
	public static function update_data($project_id, $data_name, $data_value) {
		$parameters = array();
		$parameters[ $data_name ] = $data_value;

		$result_obj = WDGWPRESTLib::call_post_wdg( 'project/' . $project_id, $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) {
			$result_obj = '';
		}

		return $result_obj;
	}

	/**
	 * Retourne la liste des utilisateurs liés au projet
	 * @param int $project_id
	 * @return array
	 */
	public static function get_users($project_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/users' );

		return $result_obj;
	}

	/**
	 * Retourne la liste des utilisateurs liés au projet, filtrés selon leur rôle
	 * @param int $project_id
	 * @param string $role_slug
	 * @return array
	 */
	public static function get_users_by_role($project_id, $role_slug) {
		$buffer = array();
		$user_list = WDGWPREST_Entity_Project::get_users( $project_id );
		if ( !empty( $user_list ) ) {
			foreach ( $user_list as $user ) {
				if ( $user->type == $role_slug ) {
					array_push( $buffer, $user );
				}
			}
		}

		return $buffer;
	}

	/**
	 * Retourne une chaine avec la liste des e-mails des utilisateurs liés à un projet
	 * @param int $project_id
	 * @param string $role_slug
	 * @return string
	 */
	public static function get_users_mail_list_by_role($project_id, $role_slug) {
		$emails = '';
		$user_list = WDGWPREST_Entity_Project::get_users_by_role( $project_id, $role_slug );
		foreach ( $user_list as $user ) {
			$user_data = get_userdata( $user->wpref );
			$emails .= ',' . $user_data->user_email;
		}

		return $emails;
	}

	/**
	 * Lie un utilisateur à un projet en définissant son rôle
	 * @param int $project_id
	 * @param int $user_id
	 * @param string $role_slug
	 * @return object
	 */
	public static function link_user($project_id, $user_id, $role_slug) {
		$request_params = array(
			'id_user' => $user_id,
			'type' => $role_slug
		);
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project/' .$project_id. '/users', $request_params );

		return $result_obj;
	}

	/**
	 * Supprime la liaison d'un utilisateur à un projet en définissant son rôle
	 * @param int $project_id
	 * @param int $user_id
	 * @param string $role_slug
	 * @return object
	 */
	public static function unlink_user($project_id, $user_id, $role_slug) {
		$result_obj = WDGWPRESTLib::call_delete_wdg( 'project/' .$project_id. '/user/' .$user_id. '/type/' .$role_slug );

		return $result_obj;
	}

	/**
	 * Retourne la liste des organisations liées au projet
	 * @param int $project_id
	 * @return array
	 */
	public static function get_organizations($project_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/organizations' );

		return $result_obj;
	}

	/**
	 * Retourne la liste des organisations liées au projet, filtrées selon leur rôle
	 * @param int $project_id
	 * @param string $role_slug
	 * @return array
	 */
	public static function get_organizations_by_role($project_id, $role_slug) {
		$buffer = array();
		$organization_list = WDGWPREST_Entity_Project::get_organizations( $project_id );
		if ( $organization_list ) {
			foreach ( $organization_list as $organization ) {
				if ( $organization->type == $role_slug ) {
					array_push( $buffer, $organization );
				}
			}
		}

		return $buffer;
	}

	/**
	 * Lie une organisation à un projet en définissant son rôle
	 * @param int $project_id
	 * @param int $organization_id
	 * @param string $role_slug
	 * @return object
	 */
	public static function link_organization($project_id, $organization_id, $role_slug) {
		$request_params = array(
			'id_organization' => $organization_id,
			'type' => $role_slug
		);
		$result_obj = WDGWPRESTLib::call_post_wdg( 'project/' .$project_id. '/organizations', $request_params );
		WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$project_id. '?with_investments=1&with_organization=1&with_poll_answers=1' );

		return $result_obj;
	}

	/**
	 * Supprime la liaison d'une organisation à un projet en définissant son rôle
	 * @param int $project_id
	 * @param int $organization_id
	 * @param string $role_slug
	 * @return object
	 */
	public static function unlink_organization($project_id, $organization_id, $role_slug) {
		$result_obj = WDGWPRESTLib::call_delete_wdg( 'project/' .$project_id. '/organization/' .$organization_id. '/type/' .$role_slug );
		WDGWPRESTLib::unset_cache( 'wdg/v1/project/' .$project_id. '?with_investments=1&with_organization=1&with_poll_answers=1' );

		return $result_obj;
	}

	/**
	 * Retourne la liste des déclarations liées à un projet
	 * @param int $project_id
	 * @return array
	 */
	private static $declarations_by_project;
	public static function get_declarations($project_id, $with_links = FALSE) {
		$buffer = FALSE;
		if ( !empty( $project_id ) ) {
			if ( !isset( self::$declarations_by_project[ $project_id ] ) ) {
				$with_links_param = '';
				if ( $with_links ) {
					$with_links_param = '&with_links=1';
				}
				self::$declarations_by_project[ $project_id ] = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/declarations?data_restricted_to_entity=1' .$with_links_param );
			}
			$buffer = self::$declarations_by_project[ $project_id ];
		}

		return $buffer;
	}

	public static function get_contract_models($project_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/contract-models' );

		return $result_obj;
	}

	public static function get_contracts($project_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/contracts' );

		return $result_obj;
	}

	public static function get_investment_contracts($project_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/investment-contracts' );

		return $result_obj;
	}

	public static function get_emails($project_id) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/emails' );

		return $result_obj;
	}

	public static function get_files($project_id, $file_type) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/files?file_type=' .$file_type );

		return $result_obj;
	}

	public static function get_files_unused($project_id, $file_type) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'project/' .$project_id. '/files?file_type=' .$file_type. '&exclude_linked_to_adjustment=1' );

		return $result_obj;
	}

	public static function get_home_stats() {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'projects/home-stats' );

		return $result_obj;
	}

	public static function get_search_list() {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'projects/search-list' );

		return $result_obj;
	}
}
