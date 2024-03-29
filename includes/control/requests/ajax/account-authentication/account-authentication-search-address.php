<?php
/**
 * Cherche une adresse via API en fonction d'une chaine transmise par Account Authentication
 */
$input_address = filter_input( INPUT_POST, 'address' );
$query_address = rawurlencode( $input_address );
$url_geolocation_api = 'https://api-adresse.data.gouv.fr/search/?q=' . $query_address . '&limit=15';
$ch = curl_init( $url_geolocation_api );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
$result = curl_exec($ch);
$error = curl_error($ch);
$errorno = curl_errno($ch);
curl_close($ch);

exit( json_encode( $result ) );