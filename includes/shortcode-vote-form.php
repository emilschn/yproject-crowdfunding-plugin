<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



//****************************************************************************//
//**********************Fonctions d'insertion pour le formualaire de votes*****************************************************
//****************************************************************//

// formulaire de vote
function ypcf_shortcode_printPageVoteForm($post, $campaign) {
  
  global $wpdb;
    $table_name = $wpdb->prefix . "ypVotes"; 
    $isvoted = false; 
    $sum_valid = false;

    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post ); 
        
    if (isset($_POST['submit']))
        { 
            if ( is_user_logged_in() )
            {
                $impact                 = $_POST[ 'impact' ];
                $local                  = $_POST[ 'local' ];
                $environmental          = $_POST[ 'environmental' ];
                $social                 = $_POST[ 'social' ];
                $autre                  = $_POST[ 'autre' ];

                $desaprouve             = $_POST[ 'desaprouve' ]; 

                $pret_pour_collect      = $_POST[ 'pret_pour_collect' ]; 
                $sum                    = $_POST[ 'sum' ];          

                $liste_risque           = $_POST[ 'liste_risque' ];
                   
                $maturite               = $_POST[ 'maturite' ];
                $pas_responsable        = $_POST[ 'pas_responsable' ];
                $mal_explique           = $_POST[ 'mal_explique' ];
                $qualite_produit        = $_POST[ 'qualite_produit' ];
                $qualite_equipe         = $_POST[ 'qualite_equipe' ];
                $qualite_business_plan  = $_POST[ 'qualite_business_plan' ];
                $qualite_innovation     = $_POST[ 'qualite_innovation' ];
                $qualite_marche         = $_POST[ 'qualite_marche' ];
                $conseil                = $_POST[ 'conseil' ];
     
                $user_last_name         = wp_get_current_user()->user_lastname;
                $user_first_name        = wp_get_current_user()->user_firstname;
                $user_email             = wp_get_current_user()->user_email;
                $user_login             = wp_get_current_user()->user_login;
                $user_id                = wp_get_current_user()->ID;

                $post                   = get_post(get_the_ID());
                $campaign               = atcf_get_campaign( $post );
                $campaign_id            =  $campaign->ID;

                if  (is_numeric($_POST[ 'sum' ]) OR $_POST[ 'sum' ] == NULL ) {
                   $sum = $_POST[ 'sum' ];
                   $sum_valid = true;
                } 
                else
                {
                    echo '<label style="color:red">*Somme invalide dans le champs</label></br> "Je serais pr&ecirct &agrave investir"</br>';
                }

            // Vérifie si l'utilisateur a deja voté
            $users = $wpdb->get_results( "SELECT user_id FROM $table_name WHERE campaign_id = $campaign_id " );
            

            foreach ( $users as $user ){
                if ( $user->user_id == $user_id){
                    echo '<label style="color:red">* D&eacutesol&eacute vous avez d&egraveja vot&eacute, merci !</label></br>';
                    $isvoted = true;
                    break;
                     
                } 
            }

            if ($isvoted == false && $sum_valid)
            {
                        $wpdb->insert( $table_name, 
                                            array( 
                                                'impact'                  => $impact, 
                                                'local'                   => $local,
                                                'environmental'           => $environmental,
                                                'social'                  => $social,
                                                'autre'                   => $autre,
                                                'desaprouve'              => $desaprouve,
                                                'pret_pour_collect'       => $pret_pour_collect,
                                                'sum'                     => $sum,
                                                'liste_risque'            => $liste_risque,
                                                'retravaille'             => $maturite,
                                                'pas_responsable'         => $pas_responsable,
                                                'mal_explique'            => $mal_explique,
                                                'qualite_produit'         => $qualite_produit,
                                                'qualite_equipe'          => $qualite_equipe,
                                                'qualite_business_plan'   => $qualite_business_plan,
                                                'qualite_innovation'      => $qualite_innovation,
                                                'qualite_marche'          => $qualite_marche,
                                                'conseil'                 => $conseil,
                                                'isvoted'                 => $isvoted,
                                                'user_id'                 => $user_id,
                                                'user_first_name'         => $user_first_name,
                                                'user_last_name'          => $user_last_name,
                                                'user_login'              => $user_login,
                                                'user_email'              => $user_email,
                                                'campaign_id'             => $campaign_id
                                              )); 

                    echo '<label style="color:green">Le vote est valid&eacute, merci !</label>';
                       
                }
            }
            else
            {
                 echo '<label style="color:red"> * Vous devez vous connecter pour voter </label></br>';
            }

        }
         

?>
            <!--Formulaire de soumission de vote, visible depuis la page project des projets en vote-->
    <div class="left post_bottom_infos">
        <form name="ypvote" action="<?php get_permalink();?>" method="POST" class="ypvote-form" enctype="multipart/form-data">
            
                <fieldset>
                    <legend>Votez sur ce projet</legend>
                   
                    <input id="impact-positif" type="radio" name="impact"  value="positif" checked="checked">Je pense que ce projet va avoir un impact positif</input></br>

                    <span id="impact-positif-content" style="display: ">
                        <input type="checkbox" name="local"  value="local">Local</input></br>
                        <input type="checkbox" name="environmental" value="environmental">Environnemental</input></br>
                        <input type="checkbox" name="social" value="social">Social</label></br>
                        <input type="checkbox" name="autre" value="autre">Autre</input>
                        <input id="precision" name="precision" type="text" placeholder="tapez ici" />
                    </span></br>
                    
                    <input id="desaprouve" type="radio" name="impact" value="negatif" >Je d&egravesapprouve ce projet car son impact pr&eacutevu n'est pas significatif</input></br></br>

                    
                    <input id="pret" type="radio" name="maturite" value="pret" checked="checked">Je pense que ce projet est pr&ecirct pour la collecte</input></br>
                    <span id="content-pret">
                        <label  id="investir" name="investir" value="investir">Je serais pr&ecirct &agrave investir</label></br>
                        <input id="sum" name="sum" type="text" placeholder="200 euro" v/></br>
                     
                        <label class="risque" name="risque" value="risque">Risque li&eacute &aring ce projet</label></br>
                        <select id="liste_risque" name="liste_risque"  placeholder="choisir le type de risque">
                            <option ></option>
                            <option id="risque_tres_faible">Le risque tr&egraves faible</option>
                            <option id="risque_plutot_faible">Le risque plut&ocirct faible</option>
                            <option id="risque_modere">Le risque mod&eacuter&eacute</option>
                            <option id="risque_plutot_eleve">Le risque plut&ocirct &eacutelev&eacute</option>
                            <option id="risque_tres_eleve">Le risque tr&egraves &eacutelev&eacute</option>
                        </select>
                    </span></br></br>

                    
                    <input id="retravaille" type="radio" name="maturite" value="retravaille">Je pense que ce projet doit &ecirctre retravaill&eacute avant de pouvoir &ecirctre financ&eacute. Sur quels points </input></br></br>
                    <span id="content-retravaille">
                        <input type="checkbox" name="pas_responsable" value="pas_responsable">Pas d&acuteimpact responsable</input></br>
                        <input type="checkbox" name="mal_explique" value="mal_explique"> Projet mal expliqu&eacute  </input></br>
                        <input type="checkbox" name="qualite_produit" value="qualite_produit">Qualit&eacute du produit/service</input></br>
                        <input type="checkbox" name="qualite_equipe" value="qualite_equipe">Qualit&eacute de l&acute&eacutequipe</input></br>
                        <input type="checkbox" name="qualite_business_plan" value="qualite_business_plan">Qualit&eacute du business plan</input></br>
                        <input type="checkbox" name="qualite_innovation" value="qualite_innovation">Qualit&eacute d&acuteinnovation</input></br>
                        <input type="checkbox" name="qualite_marche" value="qualite_marche" >Qualit&eacute du march&eacute, porteur</input></br>
            
                        <label> Expliquer pourquoi</label>
                        <textarea type="text" name="conseil" id="conseil" value="conseil"></textarea></br>
                    </span>
                    <input type="submit" name="submit" value= "valider" />
                 </fieldset>

         </form> 
    </div>
        
<?php
}

    add_shortcode( 'yproject_crowdfunding_printPageVoteForm', 'ypcf_shortcode_printPageVoteForm' );






