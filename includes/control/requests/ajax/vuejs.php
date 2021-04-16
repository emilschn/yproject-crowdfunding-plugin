<?php
/**
 * Gestion des appels Ajax en provenance des logiciels Vue
 */
class WDGAjaxActionsVue {
	public static function vuejs_error_catcher() {
		$message = filter_input( INPUT_POST, 'message' );
		$app = filter_input( INPUT_POST, 'app' );

		ypcf_debug_log( 'ajax::vuejs_error_catcher >> [app::' .$app. '] >> ' . $message, FALSE );

		exit( '1' );
	}

	public static function create_project_form() {
		$result = WDGPostActions::create_project_form();
		exit( json_encode( $result ) );
	}
}