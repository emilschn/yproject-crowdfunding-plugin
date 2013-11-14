<?php
if( !class_exists( 'WP_Http' ) )
include_once( ABSPATH . WPINC. '/class-http.php' );

function ypcf_shortcode_contract_signs() {
    global $wpdb,  $post;
     
    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post );
    $campaign_id =  $campaign->ID;
    $user_id                = wp_get_current_user()->ID;
    $user_login             = wp_get_current_user()->user_login;
    $user_first_name        = wp_get_current_user()->user_firstname;
    $user_last_name         = wp_get_current_user()->user_lastname;
    $user_email             = wp_get_current_user()->user_email;
    $user_login             = wp_get_current_user()->user_phone;


   if (isset($_POST['yp_contract_signer'])) {
       $contract_id = ypcf_creating_contract();
       //ypcf_additing_signotories($contract_id);
      // ypcf_send_contract_pdf($contract_id);
    }

    ob_start();
    ?>
    
    <form method="post" action="">
        <input type="submit" name="yp_contract_signer" value="Signer le contrat" />
    </form>

<?php
}
add_shortcode( 'yproject_crowdfunding_contract_signs', 'ypcf_shortcode_contract_signs' );


// Creating a contract
function ypcf_creating_contract(){

    $username = 'MT9M49EHieWFAnaL7gcqBLKmTuNOz2HT'; // SignsQuid api key
    $password = ''; // api signsquid ne demande pas de password
    $message = "Test signs";

    
    // La requète:
   // $api_url = 'https://app.signsquid.com/api/v1/contracts';
    $api_url = 'https://www.google.fr/';
    //$body = array( 'status' => $message );
   // $headers = array( 'Authorization' => 'Basic '.base64_encode("$username:") );
    $request = new WP_Http;
    $result = $request->request( $api_url );
   // $result = $request->request( $api_url , array( 'method' => 'POST', 'body' => $body, 'headers' => $headers ) );
    //$contract_id = $result['body'];

    $json = $result['body'];

       echo $json;
   // echo $contract_id;


    return $contract_id;

}


// Add signatories 
function ypcf_additing_signotories($contract_id){

    $curl = curl_init();
    $data=array('name' => 'toto','email'=> 'boubacar@wedogood.co');
    //$data=array('name' => $user_firstname,'email'=> $user_email);
       
    $data_string = json_encode($data);
    $url = "https://app.signsquid.com/api/v1/contracts/".$contract_id."/versions/1/signatories";

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, 1); 
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json','Content-Length: '.strlen($data_string)));                                                
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, 'MT9M49EHieWFAnaL7gcqBLKmTuNOz2HT:' );

    $curl_response = curl_exec($curl); 

    curl_close($curl);

    echo 'merci pour la signature, vous allez recevoir un code de confirmation</br>';
    echo $url;
    
}


// Send contract pdf 
function ypcf_sending_contract_pdf($contract_id){

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   
    curl_setopt($curl, CURLOPT_URL, "https://app.signsquid.com/api/v1/contracts/$contract_id/versions/1/signatories?filename=contract");
    // send a file
    curl_setopt($request, CURLOPT_POST, true);
    curl_setopt(
        $request,
        CURLOPT_POSTFIELDS,
        array(
          'file' => '/contract.pdf'
        ));

    // output the response
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    echo curl_exec($request);

    // close the session
    curl_close($request);
}

?>
