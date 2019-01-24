<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des investissements côté WDGWPREST
 */
class WDGWPREST_Entity_Investment {
	
	/**
	 * Retourne un investissement à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get( $id ) {
		return WDGWPRESTLib::call_get_external( 'investment/' . $id );
	}
	
	/**
	 * Crée une ligne de donnée sur l'API
	 * @param ATCF_Campaign $campaign
	 * @param object $edd_payment_item
	 */
	public static function set_post_parameters( $campaign, $edd_payment_item ) {
		$user_info = edd_get_payment_meta_user_info( $edd_payment_item->ID );
		$payment_date = $edd_payment_item->post_date;
		
		$WDGUser = FALSE;
		$WDGOrganization = FALSE;
		$info_user_api_id = FALSE;
		$info_email = FALSE;
		$info_gender = '';
		$info_firstname = '';
		$info_lastname = '';
		$info_nationality = '';
		$info_birthday_day = '';
		$info_birthday_month = '';
		$info_birthday_year = '';
		$info_birthplace = '';
		$info_age = '';
		$info_address = '';
		$info_postalcode = '';
		$info_city = '';
		$info_country = '';
		$info_phone = '';
		if ( !empty( $user_info['id'] ) ) {
			if ( WDGOrganization::is_user_organization( $user_info['id'] ) ) {
				$WDGOrganization = new WDGOrganization( $user_info['id'] );
				$info_user_api_id = $WDGOrganization->get_api_id();
				$info_email = $WDGOrganization->get_email();
				$linked_users = $WDGOrganization->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
				$WDGUser = $linked_users[ 0 ];
				
			} else {
				$WDGUser = new WDGUser( $user_info['id'] );
				$info_user_api_id = $WDGUser->get_api_id();
				$info_email = $WDGUser->get_email();
			}
			
			$info_gender = $WDGUser->get_gender();
			$info_firstname = $WDGUser->get_firstname();
			$info_lastname = $WDGUser->get_lastname();
			$info_nationality = $WDGUser->get_nationality();
			$info_birthday_day = $WDGUser->get_birthday_day();
			$info_birthday_month = $WDGUser->get_birthday_month();
			$info_birthday_year = $WDGUser->get_birthday_year();
			$info_birthplace = $WDGUser->get_birthplace();
			$info_age = $WDGUser->get_age( $payment_date );
			$info_address = $WDGUser->get_full_address_str();
			$info_postalcode = $WDGUser->get_postal_code();
			$info_postalcode = str_replace( ' ', '', $info_postalcode );
			if ( strlen( $info_postalcode ) == 4 ) {
				$info_postalcode = '0' . $info_postalcode;
			}
			$info_city = $WDGUser->get_city();
			$info_country = $WDGUser->get_country( 'iso2' );
			$info_phone = $WDGUser->get_phone_number();
			
		} else {
			return FALSE;
		}
		
		$amount = edd_get_payment_amount( $edd_payment_item->ID );
		$amount_with_royalties_in_cents = 0;
		$payment_status = ypcf_get_updated_payment_status( $edd_payment_item->ID );
		$contract_status = get_post_meta( $edd_payment_item->ID, WDGInvestment::$contract_status_meta, TRUE );
		
		$payment_key = edd_get_payment_key( $edd_payment_item->ID );
		$mean_of_payment = 'card';
		if ( strpos( $payment_key, 'wire_' ) !== FALSE) {
			$mean_of_payment = 'wire';
		} else if ( strpos( $payment_key, '_wallet_' ) !== FALSE) {
			$payment_key_exploded = explode( '_wallet_', $payment_key );
			$lw_transaction_result = LemonwayLib::get_transaction_by_id( $payment_key_exploded[ 1 ], 'payment' );
			$amount_with_royalties_in_cents = $lw_transaction_result->DEB * 100;
			$mean_of_payment = 'card_wallet';
		} else if ( strpos( $payment_key, 'wallet_' ) !== FALSE) {
			$amount_with_royalties_in_cents = $amount * 100;
			$mean_of_payment = 'wallet';
		} else if ( $payment_key == 'check' ) {
			$mean_of_payment = 'check';
		}
		$payment_provider = $campaign->get_payment_provider();
		
		$signsquid_contract = new SignsquidContract( $edd_payment_item->ID );
		
		$parameters = array(
			'wpref'				=> $edd_payment_item->ID,
			'redirect_url_ok'	=> 'https://www.wedogood.co',
			'redirect_url_nok'	=> 'https://www.wedogood.co',
			'notification_url'	=> 'https://www.wedogood.co',
			'user_id'			=> $info_user_api_id,
			'user_wpref'		=> $user_info['id'],
			'email'				=> $info_email,
			'gender'			=> $info_gender,
			'firstname'			=> $info_firstname,
			'lastname'			=> $info_lastname,
			'nationality'		=> $info_nationality,
			'birthday_day'		=> $info_birthday_day,
			'birthday_month'	=> $info_birthday_month,
			'birthday_year'		=> $info_birthday_year,
			'birthday_city'		=> $info_birthplace,
			'age'				=> $info_age,
			'address'			=> $info_address,
			'postalcode'		=> $info_postalcode,
			'city'				=> $info_city,
			'country'			=> $info_country,
			'phone_number'		=> $info_phone,
			'is_legal_entity'			=> ( $WDGOrganization != FALSE ),
			'project'					=> $campaign->get_api_id(),
			'amount'					=> $amount,
			'cents_with_royalties'		=> $amount_with_royalties_in_cents,
			'contract_url'				=> '', // TODO
			'invest_datetime'			=> $payment_date,
			'is_preinvestment'			=> !empty( $contract_status ),
			'mean_payment'				=> $mean_of_payment,
			'payment_provider'			=> $payment_provider,
			'status'					=> $payment_status,
			'payment_key'				=> $payment_key,
			'payment_status'			=> $payment_status,
			'signature_key'				=> $signsquid_contract->get_contract_id(),
			'signature_status'			=> $signsquid_contract->get_status_code()
		);
		if ( $WDGOrganization != FALSE ) {
			$parameters[ 'legal_entity_form' ] = $WDGOrganization->get_legalform();
			$parameters[ 'legal_entity_id' ] = $WDGOrganization->get_idnumber();
			$parameters[ 'legal_entity_rcs' ] = $WDGOrganization->get_rcs();
			$parameters[ 'legal_entity_capital' ] = $WDGOrganization->get_capital();
			$parameters[ 'legal_entity_address' ] = $WDGOrganization->get_full_address_str();
			$orga_postalcode = $WDGOrganization->get_postal_code();
			$orga_postalcode = str_replace( ' ', '', $orga_postalcode );
			if ( strlen( $orga_postalcode ) == 4 ) {
				$orga_postalcode = '0' . $orga_postalcode;
			}
			$parameters[ 'legal_entity_postalcode' ] = $orga_postalcode;
			$parameters[ 'legal_entity_city' ] = $WDGOrganization->get_city();
			$parameters[ 'legal_entity_nationality' ] = $WDGOrganization->get_nationality();
		}
		return $parameters;
	}
	
	/**
	 * Crée une ligne de donnée sur l'API
	 * @param ATCF_Campaign $campaign
	 * @param object $edd_payment_item
	 */
	public static function create( $campaign, $edd_payment_item ) {
		$buffer = FALSE;
		
		$parameters = WDGWPREST_Entity_Investment::set_post_parameters( $campaign, $edd_payment_item );
		if ( !empty( $parameters ) ) {
			$buffer = WDGWPRESTLib::call_post_wdg( 'investment', $parameters );
			if ( isset( $buffer->code ) && $buffer->code == 400 ) { $buffer = FALSE; }
		}
		
		return $buffer;
	}
}
