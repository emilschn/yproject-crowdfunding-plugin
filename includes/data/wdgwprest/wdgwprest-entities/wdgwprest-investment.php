<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des investissement côté WDGWPREST
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
}
