<?php
class WDGFormUsers {
	/**
	 * 
	 */
	public static function wallet_to_bankaccount() {
		$user_id = filter_input( INPUT_POST, 'user_id' );
		if (!isset( $user_id ) || !isset($_POST['action']) || $_POST['action'] != 'user_wallet_to_bankaccount') {
			return FALSE;
		}
		$WDGUser_current = WDGUser::current();
		if (bp_displayed_user_id() != $user_id && !$WDGUser_current->is_admin()) {
			return FALSE;
		}
		
		$WDGUser = new WDGUser( $user_id );
		
		$save_iban = filter_input( INPUT_POST, 'iban' );
		if (isset($save_iban) && !empty($save_iban)) {
			$save_holdername = filter_input( INPUT_POST, 'holdername' );
			$save_bic = filter_input( INPUT_POST, 'bic' );
			$save_address = filter_input( INPUT_POST, 'address' );
			$WDGUser->save_iban($save_holdername, $save_iban, $save_bic, $save_address);
		}
		
		//Save RIB
		return $WDGUser->transfer_wallet_to_bankaccount();
	}
}
