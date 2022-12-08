<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des utilisateurs côté WDGWPREST
 */
class WDGWPREST_Entity_User {
	
	/**
	 * Retourne un utilisateur à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id, $shortcut_call = FALSE ) {
		if ( empty( $id ) ) {
			return FALSE;
		}
		return WDGWPRESTLib::call_get_wdg( 'user/' .$id. '?with_links=1', $shortcut_call );
	}
	
	/**
	 * Retourne la liste des utilisateurs de l'API avec des options
	 * @param int $offset
	 * @param int $limit
	 * @param boolean $full
	 * @param int $link_to_project
	 */
	public static function get_list( $offset = 0, $limit = FALSE, $full = FALSE, $link_to_project = FALSE ) {
		$url = 'users';
		$url .= '?offset=' . $offset;
		if ( !empty( $limit ) ) {
			$url .= '&limit=' . $limit;
		}
		if ( !empty( $full ) ) {
			$url .= '&full=' . $full;
		}
		if ( !empty( $link_to_project ) ) {
			$url .= '&link_to_project=' . $link_to_project;
		}
		return WDGWPRESTLib::call_get_wdg( $url );
	}
	
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @param WDGUser $user
	 * @return array
	 */
	public static function set_post_parameters( WDGUser $user ) {
		// Si la langue d'affichage de l'utilisateur n'est pas définie et que c'est bien l'utilisateur en cours qui se met à jour
		if ( $user->get_language() == '' ) {
			$WDGUser_current = WDGUser::current();
			if ( $user->get_wpref() == $WDGUser_current->get_wpref() ) {
				$user->set_language( WDG_Languages_Helpers::get_current_locale_id() );
			}
		}

		// Initialisation des paramètres à envoyer
		$parameters = array(
			'wpref'				=> $user->get_wpref(),
			'gender'			=> $user->get_gender(),
			'name'				=> $user->get_firstname(),
			'surname'			=> $user->get_lastname(),
			'surname_use'		=> $user->get_use_lastname(),
			'username'			=> $user->get_login(),
			'birthday_date'		=> $user->get_birthday_date(),
			'birthday_city'		=> $user->get_birthplace(),
			'birthday_district'	=> $user->get_birthplace_district(),
			'birthday_department'	=> $user->get_birthplace_department(),
			'birthday_country'	=> $user->get_birthplace_country(),
			'nationality'		=> $user->get_nationality(),
			'address_number'		=> $user->get_address_number(),
			'address_number_comp'	=> $user->get_address_number_complement(),
			'address'			=> $user->get_address(),
			'postalcode'		=> $user->get_postal_code(),
			'city'				=> $user->get_city(),
			'country'			=> $user->get_country(),
			'tax_country'		=> $user->get_tax_country(),
			'email'				=> $user->get_email(),
			'phone_number'		=> $user->get_phone_number(),
			'contact_if_deceased'	=> $user->get_contact_if_deceased(),
			'language'			=> $user->get_language(),
			'bank_iban'			=> $user->get_bank_iban(),
			'bank_bic'			=> $user->get_bank_bic(),
			'bank_holdername'	=> $user->get_bank_holdername(),
			'bank_address'		=> $user->get_bank_address(),
			'bank_address2'		=> $user->get_bank_address2(),
			'authentification_mode'	=> $user->get_authentification_mode(),
			/* 'picture_url', 'website_url', 'twitter_url', 'facebook_url', 'linkedin_url', 'viadeo_url', 'activation_key', 'password' */
			'signup_date'		=> $user->get_signup_date(),
			'royalties_notifications'		=> $user->get_royalties_notifications(),
			'gateway_list'		=> $user->get_encoded_gateway_list(),
			'email_is_validated'		=> $user->get_email_validation_code(),
			'risk_validation_time'		=> $user->get_risk_validation_time(),
			'source'					=> $user->get_source()
		);

		return $parameters;
	}
	
	/**
	 * Crée un utilisateur sur l'API
	 * @param WDGUser $user
	 * @return object
	 */
	public static function create( WDGUser $user ) {
		if ( $user->get_wpref() == '' ) {
			return FALSE;
		}
		$parameters = WDGWPREST_Entity_User::set_post_parameters( $user );
		$date = new DateTime("NOW");
		$parameters['signup_date'] = $date->format('Y') .'-'. $date->format('m') .'-'. $date->format('d');
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'user', $parameters );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Mise à jour de l'utilisateur à partir d'un id
	 * @param WDGUser $user
	 * @return object
	 */
	public static function update( WDGUser $user ) {
		if ( $user->get_wpref() == '' ) {
			return FALSE;
		}
		
		$parameters = WDGWPREST_Entity_User::set_post_parameters( $user );
		
		$result_obj = WDGWPRESTLib::call_post_wdg( 'user/' . $user->get_api_id(), $parameters );
		WDGWPRESTLib::unset_cache( 'wdg/v1/user/' .$user->get_api_id(). '?with_links=1' );
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}

	/**
	 * Mise à jour de l'utilisateur sur l'API à partir de données transmises dans un tableau
	 */
	public static function update_from_array( $user_api_id, $user_data ) {
		return WDGWPRESTLib::call_post_wdg( 'user/' . $user_api_id, $user_data, TRUE );
	}
	
	/**
	 * Retourne la liste des organisations d'un utilisateur
	 * @param int $user_id
	 * @return array
	 */
	public static function get_organizations( $user_id ) {
		$result_get = WDGWPREST_Entity_User::get( $user_id );
		return $result_get->organizations;
	}
	
	/**
	 * Retourne la liste des organisations d'un utilisateur, filtrées par un role
	 * @param int $user_id
	 * @param string $role_slug
	 * @return array
	 */
	public static function get_organizations_by_role( $user_id, $role_slug ) {
		$buffer = array();
		$organization_list = WDGWPREST_Entity_User::get_organizations( $user_id );
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
	 * Retourne la liste des projets d'un utilisateur
	 * @param int $user_id
	 * @return array
	 */
	public static function get_projects( $user_id ) {
		$result_get = WDGWPREST_Entity_User::get( $user_id );
		return $result_get->projects;
	}
	
	/**
	 * Retourne la liste des projets d'un utilisateur, filtrées par un role
	 * @param int $user_id
	 * @param string $role_slug
	 * @return array
	 */
	public static function get_projects_by_role( $user_id, $role_slug ) {
		$buffer = array();
		$project_list = WDGWPREST_Entity_User::get_projects( $user_id );
		if ( $project_list ) {
			foreach ( $project_list as $project ) {
				if ( $project->type == $role_slug ) {
					array_push( $buffer, $project );
				}
			}
		}
		return $buffer;
	}

	/**
	 * Retourne les investissements liés à un utilisateur
	 * @return array
	 */
	public static function get_investments( $user_api_id, $sort = FALSE ) {
		$sort_str = '';
		if ( !empty( $sort ) ) {
			$sort_str = '?sort=' .$sort;
		}
		$result_obj = WDGWPRESTLib::call_get_wdg( 'user/' .$user_api_id. '/investments' .$sort_str );
		return $result_obj;
	}
	
	/**
	 * Retourne les contrats d'investissements liés à un utilisateur
	 * @return array
	 */
	public static function get_investment_contracts( $user_api_id ) {
		$result_obj = WDGWPRESTLib::call_get_wdg( 'user/' .$user_api_id. '/investment-contracts' );
		return $result_obj;
	}
	
	/**
	 * Retourne les ROIs liés à un utilisateur
	 * @return array
	 */
	public static function get_rois( $user_id ) {
		$buffer = array();
		if ( !empty( $user_id ) ) {
			$buffer = WDGWPRESTLib::call_get_wdg( 'user/' .$user_id. '/rois' );
		}
		return $buffer;
	}
	
	/**
	 * Retourne les transactions liés à un utilisateur
	 * @return array
	 */
	public static function get_transactions( $user_id ) {
		$buffer = array();
		if ( !empty( $user_id ) ) {
			$buffer = WDGWPRESTLib::call_get_wdg( 'user/' .$user_id. '/transactions' );
		}
		return $buffer;
	}
	
	/**
	 * Retourne le vIBAN lié à un utilisateur
	 * @return array
	 */
	public static function get_viban( $user_id ) {
		$buffer = array();
		if ( !empty( $user_id ) ) {
			$buffer = WDGWPRESTLib::call_get_wdg( 'user/' .$user_id. '/virtual-iban' );
		}
		return $buffer;
	}
}
