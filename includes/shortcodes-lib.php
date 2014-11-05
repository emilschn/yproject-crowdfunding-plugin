<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ypcf_shortcode_agree_text() {
    global $edd_options;
    return wpautop( stripslashes( $edd_options['agree_text'] ) );
}
add_shortcode('yproject_agree_text', 'ypcf_shortcode_agree_text');