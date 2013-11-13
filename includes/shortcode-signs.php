<?php

function ypcf_shortcode_signs() {
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


   if (isset($_POST['yp_signer'])) {
       $contract_id = ypcf_create_contract();
      // ypcf_add_signotories($contract_id);
      // ypcf_send_contract_pdf($contract_id);
    
    }

    ob_start();
    ?>
    
    <form method="post" action="">
        <input type="submit" name="yp_signer" value="Signer" />
    </form>

<?php
}
add_shortcode( 'yproject_crowdfunding_signs', 'ypcf_shortcode_signs' );


// Creating a contract
function ypcf_create_contract(){

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CAINFO,"cacert.pem");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 2);
    curl_setopt($curl, CURLOPT_URL, "https://www.google.fr/");
    //curl_setopt($curl, CURLOPT_URL, "https://app.signsquid.com/api/v1/contracts");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, 'MT9M49EHieWFAnaL7gcqBLKmTuNOz2HT:' );
    curl_setopt($curl, CURLOPT_VERBOSE, true);

    $curl_response = curl_exec($curl);  /* return [{"id":"5248a02c4bdcc849e4d38efe","name":"Test mention"}]*/
   // echo $curl_response;
    $curl_response =substr($curl_response,1, -1);/*Pour avoir un format JSON valide, il faut enlever les crochets [] qui entourent le tableau*/

    $obj = json_decode($curl_response); /*Parser Json pour recuperer la valeur id*/

    $contract_id = $obj->{'id'};
     echo $curl_response.'</br>';
    //echo $contract_id;
    
    curl_close($curl);

    return $contract_id;

}


// Add signatories 
function ypcf_add_signotories($contract_id){

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
function ypcf_send_contract_pdf($contract_id){

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
