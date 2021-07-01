<?php
/**
 * Donne les informations à Account Signin en fonction de l'adresse e-mail
 */
$input_email = filter_input( INPUT_POST, 'email-address' );
$result = AccountSigninHelper::get_user_type_by_email_address( $input_email );
exit( json_encode( $result ) );