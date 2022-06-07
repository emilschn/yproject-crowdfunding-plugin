<?php
// Classes WP nécessaires pour les appels HTTP
require_once ABSPATH . WPINC . '/class-wp-http-proxy.php';
require_once ABSPATH . WPINC . '/Requests/Hooker.php';
require_once ABSPATH . WPINC . '/Requests/Hooks.php';
require_once ABSPATH . WPINC . '/class-wp-http-requests-hooks.php';
require_once ABSPATH . WPINC . '/link-template.php';
require_once ABSPATH . WPINC . '/general-template.php';
require_once ABSPATH . WPINC . '/http.php';
require_once ABSPATH . WPINC . '/class-http.php';
// Classes WP nécessaires pour les retours d'appels HTTP
require_once ABSPATH . WPINC . '/class-wp-http-response.php';
require_once ABSPATH . WPINC . '/class-wp-http-requests-response.php';
// Fichiers nécessaires pour vérifier que l'utilisateur est connecté
require_once ABSPATH . WPINC . '/rest-api.php';
require_once ABSPATH . WPINC . '/kses.php';
require_once ABSPATH . WPINC . '/class-wp-session-tokens.php';
require_once ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php';
require_once ABSPATH . WPINC . '/class-wp-role.php';
require_once ABSPATH . WPINC . '/class-wp-roles.php';
require_once ABSPATH . WPINC . '/capabilities.php';
require_once ABSPATH . WPINC . '/class-wp-user.php';
require_once ABSPATH . WPINC . '/user.php';
require_once ABSPATH . WPINC . '/pluggable.php';
require_once ABSPATH . WPINC . '/default-constants.php';
wp_initial_constants();
wp_cookie_constants();
require_once ABSPATH . WPINC . '/default-filters.php';
// Fichiers nécessaires pour la création d'organisation
require_once ABSPATH . WPINC . '/class-wp-block-parser.php';
require_once ABSPATH . WPINC . '/blocks.php';
require_once ABSPATH . WPINC . '/formatting.php';
require_once ABSPATH . WPINC . '/l10n.php';
require_once ABSPATH . WPINC . '/user.php';
// Classes WDG nécessaires (divers)
require_once dirname(__FILE__) . '/../../../lib/validator.php';
// Classes WDG nécessaires aux appels à l'API
require_once dirname(__FILE__) . '/../../../cache/db-cacher.php';
require_once dirname(__FILE__) . '/../../../../data/kyc-file.php';
require_once dirname(__FILE__) . '/../../../../data/wdgwprest/wdgwprest-lib.php';
require_once dirname(__FILE__) . '/../../../../data/wdgwprest/wdgwprest-entities/wdgwprest-organization.php';
require_once dirname(__FILE__) . '/../../../../data/wdgwprest/wdgwprest-entities/wdgwprest-user.php';
require_once dirname(__FILE__) . '/../../../../data/wdgwprest/wdgwprest-entities/wdgwprest-file-kyc.php';

// Log de l'appel vers Amplitude
$action = filter_input( INPUT_POST, 'action' );
if ( !empty( $action ) ) {
	require_once dirname(__FILE__) . '/../../../amplitude/api-calls.php';
	$sessionUID = filter_input( INPUT_POST, 'sessionUID' );
	WDGAmplitude::logEvent( $action, $sessionUID );
}
