<?php
$list_optimized_actions = array(
	'account_signin_get_email_info'
);
$action_posted = filter_input( INPUT_POST, 'action' );

if ( empty( $action_posted ) || !in_array( $action_posted, $list_optimized_actions ) ) {
	require_once dirname(__FILE__) . '/../../../../../../wp-admin/admin-ajax.php';
	exit('');
}

// Imitation du vrai admin-ajax.php
define('DOING_AJAX', true);
ini_set('html_errors', 0);
define('SHORTINIT', true);

// Chargement des fichiers WordPress nécessaires
require_once dirname(__FILE__) . '/../../../../../../wp-load.php';

// Si site de dev en local, on autorise les appels cross-origin
if ( defined( 'WP_IS_DEV_SITE' ) && WP_IS_DEV_SITE ) {
	header('Access-Control-Allow-Origin: *');
}

// Headers classiques
header( 'Content-Type: text/html;' );
header( 'X-Robots-Tag: noindex' );
header( 'X-Content-Type-Options: nosniff' );
$headers = array(
	'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
	'Cache-Control' => 'no-cache, must-revalidate, max-age=0',
	'Last-Modified' => false
);
foreach ( $headers as $name => $field_value ) {
	header( "{$name}: {$field_value}" );
}

// Chargement du fichier correspondant à l'action
$domain_folder = '';
if ( strpos( $action_posted, 'account_signin' ) === 0 ) {
	$domain_folder = 'account-signin';
}
$action_posted = str_replace( '_', '-', $action_posted );
require_once dirname(__FILE__) . '/ajax/' .$domain_folder. '/' .$domain_folder. '-autoload.php';
require_once dirname(__FILE__) . '/ajax/' .$domain_folder. '/' .$action_posted. '.php';