<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class NotificationsZapier {
	private static $type_prospect_setup_payment = "prospect_setup_payment";

	private static function send( $type, $data ) {
		if ( empty( $type ) ) {
			return;
		}

		$url = '';
		switch ( $type ) {
			case self::$type_prospect_setup_payment:
				$url = YP_ZAPIER_WEBHOOK_PROSPECT_SETUP_PAYMENT;
				break;
		}

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec($ch);
		$error = curl_error($ch);
		$errorno = curl_errno($ch);
		curl_close($ch);
	}

	public static function send_prospect_setup_payment_received(
			$project_draft_api_data,
			$amount = FALSE, $payment_date = FALSE,
			$user_name = FALSE, $organization_name = FALSE, $organization_email = FALSE, $tax_number = FALSE, $address = FALSE, $postal_code = FALSE, $city = FALSE, $country = FALSE,
			$bundle1_title = FALSE, $bundle1_type = FALSE, $bundle1_price_without_discount = FALSE, $bundle1_discount = FALSE, $bundle1_discount_reason = FALSE,
			$bundle2_title = FALSE, $bundle2_type = FALSE, $bundle2_price_without_discount = FALSE, $bundle2_discount = FALSE, $bundle2_discount_reason = FALSE
			) {

		if ( !empty( $project_draft_api_data ) ) {
			self::send( self::$type_prospect_setup_payment, $project_draft_api_data );

		} else {
			$data = array(
				'payment_date' 			=> $payment_date,
				'amount' 				=> $amount,
				'user_name' 			=> $user_name,
				'organization_name' 	=> $organization_name,
				'organization_email' 	=> $organization_email,
				'tax_number' 			=> $tax_number,
				'address' 				=> $address,
				'postal_code' 			=> $postal_code,
				'city' 					=> $city,
				'country' 				=> $country,
				'bundle1'				=> array(
					'title'						=> $bundle1_title,
					'type'						=> $bundle1_type,
					'price_without_discount'	=> $bundle1_price_without_discount,
					'discount'					=> $bundle1_discount,
					'discount_reason'			=> $bundle1_discount_reason
				),
				'bundle2'				=> array(
					'title'						=> $bundle2_title,
					'type'						=> $bundle2_type,
					'price_without_discount'	=> $bundle2_price_without_discount,
					'discount'					=> $bundle2_discount,
					'discount_reason'			=> $bundle2_discount_reason
				)
			);
			self::send( self::$type_prospect_setup_payment, $data );
		}
	}
}
