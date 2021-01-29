<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ypcf_shortcode_agree_text() {
    return wpautop( stripslashes( WDGConfigTexts::get_config_text_by_name( WDGConfigTexts::$type_term_particular, 'agree_text' ) ) );
}
add_shortcode('yproject_agree_text', 'ypcf_shortcode_agree_text');