<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestion des investissements côté WDGWPREST
 */
class WDGWPREST_Entity_Investment {
	/**
	 * Retourne un investissement à partir d'un id
	 * @param string $id
	 * @return object
	 */
	public static function get($id) {
		return WDGWPRESTLib::call_get_external( 'investment/' . $id );
	}

	/**
	 * Crée une ligne de donnée sur l'API
	 * @param WDGInvestment $investment
	 */
	private static function set_post_parameters( $investment, $user_wpref, $payment_status, $id_subscription = FALSE ) {
		$campaign = $investment->get_saved_campaign();
		$payment_date = $investment->get_saved_date();
		if ( empty( $payment_date ) ) {
			$date_now = new DateTime();
			$payment_date = $date_now->format( 'Y-m-d H:i:s' );
		}

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
		if ( !empty( $user_wpref ) ) {
			if ( WDGOrganization::is_user_organization( $user_wpref ) ) {
				$WDGOrganization = new WDGOrganization( $user_wpref );
				$info_user_api_id = $WDGOrganization->get_api_id();
				$info_email = $WDGOrganization->get_email();
				$linked_users = $WDGOrganization->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
				$WDGUser = $linked_users[ 0 ];
			} else {
				$WDGUser = new WDGUser( $user_wpref );
				$info_user_api_id = $WDGUser->get_api_id();
				$info_email = $WDGUser->get_email();
			}
			if ( isset( $WDGUser)) {
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
			}
		} else {
			return FALSE;
		}

		$amount = $investment->get_saved_amount();
		$amount_with_royalties_in_cents = 0;
		$status = $investment->get_saved_status();
		if ( empty( $payment_status ) ) {
			$payment_status = $investment->get_payment_status();
		}
		$contract_status = $investment->get_contract_status();

		$payment_key = $investment->get_saved_payment_key();
		$mean_of_payment = 'card';
		if ( strpos( $payment_key, 'wire_' ) !== FALSE) {
			$mean_of_payment = 'wire';
		} else {
			if ( strpos( $payment_key, '_wallet_' ) !== FALSE) {
				$payment_key_exploded = explode( '_wallet_', $payment_key );
				$lw_transaction_result = LemonwayLib::get_transaction_by_id( $payment_key_exploded[ 1 ], 'payment' );
				$amount_with_royalties_in_cents = $lw_transaction_result->DEB * 100;
				$mean_of_payment = 'card_wallet';
			} else {
				if ( strpos( $payment_key, 'wallet_' ) !== FALSE) {
					$amount_with_royalties_in_cents = $amount * 100;
					$mean_of_payment = 'wallet';
				} else {
					if ( $payment_key == 'check' ) {
						$mean_of_payment = 'check';
					}
				}
			}
		}
		$payment_provider = $campaign->get_payment_provider();

		$WDGInvestmentSignature = new WDGInvestmentSignature( $investment->get_id() );
		$signature_status = $WDGInvestmentSignature->get_status();
		$signature_id = $WDGInvestmentSignature->get_external_id();

		$parameters = array(
			'wpref'				=> $investment->get_id(),
			'redirect_url_ok'	=> 'https://www.wedogood.co',
			'redirect_url_nok'	=> 'https://www.wedogood.co',
			'notification_url'	=> 'https://www.wedogood.co',
			'user_id'			=> $info_user_api_id,
			'user_wpref'		=> $user_wpref,
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
			'status'					=> $status,
			'payment_key'				=> $payment_key,
			'payment_status'			=> $payment_status,
			'signature_key'				=> $signature_id,
			'signature_status'			=> $signature_status
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
		if ( !empty( $id_subscription ) ) {
			$parameters[ 'id_subscription' ] = $id_subscription;
		}

		return $parameters;
	}

	/**
	 * Crée une ligne de donnée sur l'API
	 * @param WDGInvestment $investment
	 */
	public static function create_or_update( $investment, $user_wpref, $payment_status, $id_subscription = FALSE ) {
		$buffer = FALSE;

		$parameters = self::set_post_parameters( $investment, $user_wpref, $payment_status, $id_subscription );
		if ( !empty( $parameters ) ) {
			$buffer = WDGWPRESTLib::call_post_wdg( 'investment', $parameters );
			if ( $buffer === FALSE ) {
				NotificationsAsana::investment_to_api_error_admin( $investment );
			}
		}

		return $buffer;
	}
}
