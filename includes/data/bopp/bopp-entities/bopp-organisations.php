<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des organisations côté API
 */
class BoppOrganisations {
	/**
	 * retourne la liste des paramètres
	 * @return string
	 */
	public static function empty_params() {
		$params = array(
			'organisation_wpref' => '---',
			'organisation_name' => '---',
			'organisation_creation_date' => '---',
			'organisation_strong_authentication' => '---',
			'organisation_type' => '---',
			'organisation_legalform' => '---',
			'organisation_idnumber' => '---',
			'organisation_rcs' => '---',
			'organisation_capital' => '---',
			'organisation_address' => '---',
			'organisation_postalcode' => '---', 
			'organisation_city' => '---', 
			'organisation_country' => '---',
			'organisation_ape' => '---',
			'organisation_bank_owner' => '---',
			'organisation_bank_address' => '---',
			'organisation_bank_iban' => '---',
			'organisation_bank_bic' => '---',
			'organisation_website_url' => '---',
			'organisation_twitter_url' => '---',
			'organisation_facebook_url' => '---',
			'organisation_linkedin_url' => '---',
			'organisation_viadeo_url' => '---'
		);
		return $params;
	}
    
	/**
	 * Crée une organisation sur l'API
	 * @return object
	 */
	public static function create($wpref, $name, $strong_authentication, $type, $legalform, $idnumber, $rcs, $capital, $address, $postalcode, $city, $country, $ape, $bank_owner, $bank_address, $bank_iban, $bank_bic) {
		$default_params = BoppOrganisations::empty_params();
		$default_params['organisation_wpref'] = $wpref;
		$default_params['organisation_name'] = $name;
		$strong_authentication = ($strong_authentication === TRUE) ? 1 : 0;
		$default_params['organisation_strong_authentication'] = $strong_authentication;
		$default_params['organisation_type'] = $type;
		$default_params['organisation_legalform'] = $legalform;
		$default_params['organisation_idnumber'] = $idnumber;
		$default_params['organisation_rcs'] = $rcs;
		$default_params['organisation_capital'] = $capital;
		$default_params['organisation_address'] = $address;
		$default_params['organisation_postalcode'] = $postalcode;
		$default_params['organisation_city'] = $city;
		$default_params['organisation_country'] = $country;
		$default_params['organisation_ape'] = $ape;
		$default_params['organisation_bank_owner'] = $bank_owner;
		$default_params['organisation_bank_address'] = $bank_address;
		$default_params['organisation_bank_iban'] = $bank_iban;
		$default_params['organisation_bank_bic'] = $bank_bic;
		
		$date = new DateTime("NOW");
		$default_params['organisation_creation_date'] = array(
			"year" => $date->format('Y'),
			"month" => $date->format('n'),
			"day" => $date->format('j')
		);
		
		$request_params = array(
			'organisation' => $default_params
		);
		
		$result_obj = BoppLib::call_post('organisations', $request_params);
		if (isset($result_obj->code) && $result_obj->code == 400) { $result_obj = ''; }
		return $result_obj;
	}
	
	/**
	 * Retourne une organisation à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get($id) {
		return BoppLib::call_get('organisations/' . $id);
	}
	
	/**
	 * Mise à jour de l'organisation à partir d'un id
	 * @param int $id
	 * @param string $first_name
	 * @param string $last_name
	 * @return object
	 */
	public static function update($id, $strong_authentication, $type, $legalform, $idnumber, $rcs, $capital, $address, $postalcode, $city, $country, $ape, $bank_owner, $bank_address, $bank_iban, $bank_bic) {
		$default_params = BoppOrganisations::empty_params();
		unset($default_params['organisation_wpref']);
		unset($default_params['organisation_name']);
		unset($default_params['organisation_creation_date']);
		$default_params['organisation_strong_authentication'] = $strong_authentication;
		$default_params['organisation_type'] = $type;
		$default_params['organisation_legalform'] = $legalform;
		$default_params['organisation_idnumber'] = $idnumber;
		$default_params['organisation_rcs'] = $rcs;
		$default_params['organisation_capital'] = $capital;
		$default_params['organisation_address'] = $address;
		$default_params['organisation_postalcode'] = $postalcode;
		$default_params['organisation_city'] = $city;
		$default_params['organisation_country'] = $country;
		$default_params['organisation_ape'] = $ape;
		$default_params['organisation_bank_owner'] = $bank_owner;
		$default_params['organisation_bank_address'] = $bank_address;
		$default_params['organisation_bank_iban'] = $bank_iban;
		$default_params['organisation_bank_bic'] = $bank_bic;
		$request_params = array(
			'organisation' => $default_params
		);
		
		$result_obj = BoppLib::call_put('organisations/' . $id, $request_params);
		return $result_obj;
	}

	/**
	 * Lie un utilisateur à une organisation en définissant son rôle
	 * @param type $bopp_organisation_id
	 * @param type $bopp_user_id
	 * @param type $bopp_role_slug
	 * @return type
	 */
	public static function link_user_to_organisation($bopp_organisation_id, $bopp_user_id, $bopp_role_slug) {
		$request_params = array(
			'organisation_management' => array(
				'boppUser' => $bopp_user_id, 
				'boppRole' => $bopp_role_slug
			)
		);
		$result_obj = BoppLib::call_post('organisations/'.$bopp_organisation_id.'/members', $request_params);
		return $result_obj;
	}
}
