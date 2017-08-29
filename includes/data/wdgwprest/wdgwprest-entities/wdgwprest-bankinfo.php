<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des données bancaires côté WDGWPREST
 */
class WDGWPREST_Entity_BankInfo {
	
	/**
	 * Retourne des informations bancaires à partir d'un e-mail
	 * @param string $email
	 * @return object
	 */
	public static function get( $email ) {
		return WDGWPRESTLib::call_get_external( 'bankinfo/' . $email );
	}
}
