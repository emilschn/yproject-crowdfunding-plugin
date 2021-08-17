<?php
class AjaxCommonHelper {
	public static function get_input_post( $label ) {
		$input_result = filter_input( INPUT_POST, $label );
		return stripslashes( htmlentities( $input_result, ENT_QUOTES | ENT_HTML401 ) );
	}
}
