<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



//****************************************************************************//
//**********************Fonctions d'insertion pour le formualaire de votes*****************************************************
//****************************************************************//

// formulaire de vote
function ypcf_shortcode_printPageVoteForm($post, $campaign) {
  
  global $wpdb;
    $table_name = $wpdb->prefix . "fvote";  

    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post ); 

 ?>
            <!--Formulaire de soumission de vote, visible depuis la page project des projets en vote-->
        <form name="fVote" action="<?php get_permalink();?>" method="POST" class="fVote-form" enctype="multipart/form-data">
            <div class="left post_bottom_infos">
            
                <fieldset>
                    <legend>Votez sur ce projet</legend>
                    
                    <input id="impact_positif" type="radio" name="radios1"  value="impact_positif">
                       Je pense que ce projet va avoir un impact positif
                    </input>

                    <div id="liste_impact_positif_choix" style="display: ">
                        <input type="checkbox" name="choice[]"  value="local">
                          Local
                        </input></br>
                        <input type="checkbox" name="choice[]" value="environnemental">
                          Environnemental
                        </input></br>
                        
                        <input type="checkbox" name="choice[]" value="social">
                          Social
                        </label></br>
                        <input type="checkbox" name="choice[]" value="autre">
                          Autre
                        </input>
                        <input id="precision" name="precision" type="text" placeholder="tapez ici" />
                    </div>
                    
                    <input id="impact_negatif" type="radio" name="radios1" value="impact_negatif" checked="checked">
                      Je d&egravesapprouve ce projet car son impact pr&eacutevu n'est pas significatif
                    </input></br></br>
                    
                    <input type="radio" name="radios2" value="pret_collect" checked="checked">
                     Je pense que ce projet est pr&ecirct pour la collecte
                    </input></br>

                    <div>
                        <label  id="investir" name="investir" value="investir">
                          Je serais pr&ecirct &aring investir
                        </label></br>

                        <input id="sum" name="sum" type="text" placeholder="200 euro"   style="display: "/></br>
                    </div>   

                    <label id="risque" name="risque" value="risque">
                       Risque li&eacute &aring ce projet
                    </label></br>
                    <select id="liste_risque" name="liste_risque" >
                        <option id="tres_faible">Le risque tr&egraves faible</option>
                        <option id="plutot_faible">Le risque plut&ocirct faible</option>
                        <option id="modere">Le risque mod&eacuter&eacute</option>
                        <option id="plutot_eleve">Le risque plut&ocirct &eacutelev&eacute</option>
                        <option id="tres_eleve">Le risque tr&egraves &eacutelev&eacute</option>
                    </select></br>
                    
                    <input type="radio" name="radios2" value="pret_collect">
                        Je pense que ce projet doit &ecirctre retravaill&eacute avant de pouvoir &ecirctre financ&eacute. Sur quels points 
                    </input></br>
                    <div id="liste_retravaille" style="display: ">
                        <input type="checkbox" iname="choice1[]" value="responsable">
                          Pas d&acuteimpact responsable
                        </input></br>

                        <input type="checkbox" name="choice1[]" value="mal_explique">
                          Projet mal expliqu&eacute  
                        </input></br>

                        <input type="checkbox" name="choice1[]" value="service">
                          Qualit&eacute du produit/service
                        </input></br>

                        <input type="checkbox" name="choice1[]" value="equipe">
                          Qualit&eacute de l&acute&eacutequipe
                        </input></br>

                        <input type="checkbox" id="plan" name="plan" value="plan">
                          Qualit&eacute du business plan
                        </input></br>

                        <input type="checkbox" id="innovation" name="innovation" value="innovation">
                          Qualit&eacute d&acuteinnovation
                        </input></br>

                        <input type="checkbox" name="porteur" value="porteur" id="porteur">
                          Qualit&eacute du march&eacute, porteur
                        </input></br>
            
                        <label> Expliquer pourquoi</label>
                        <textarea type="text" name="expliquers" id="expliquer" value="expliquer">
                        
                        </textarea></br>
                    </div>
                    <input type="submit" name="valider" value= "valider" />
                 </fieldset>
            
            </div>
         </form>   
        </div>
<?php
    



if (isset($_POST['submit']) && $_POST['submit'] == "valider")
    { 

        $precision        = $_POST[ 'precision' ];
        $investir         = $_POST[ 'investir' ];
        $sum              = $_POST[ 'sum' ];
        $liste_risque     = $_POST[ 'liste_risque' ];
        $isvoted          = $_POST[ 'isvoted' ];
        $user_id          =  wp_get_current_user()->ID;
        $post = get_post(get_the_ID());
        $campaign = atcf_get_campaign( $post );
        $campaign_id      =  $campaign->ID;


      // recuperer les valeurs des chekboxes sur l'impact postif depuis le formulaire vote
      $options = $_Post['choice'];  


      // recuperer les valeurs des chekboxes sur le projet doit etre retravaille depuis le formulaire vote
      $options1 = $_Post['choice1'];           
    
      // stocke des valeurs des cases à cocher dans un tabeau 
      $impact = implode(', ',$options); 
      $retravaille = implode(', ',$options1);
    
         
      $wpdb->query( "INSERT INTO $table_name (`id`, `impact`, `retravaille`, `liste_risque`, `investir`, `sum`, `risque`, `isvoted`, `user_id`, `campaign_id`)
                  VALUES ('', '$impact', '&retravaille', $liste_risque, '$investir', '$sum' ,NULL, '$isvoted', `$user_id`, '$campaign_id' )");
      // test la BDD  $wpdb->query("SELECT sum, local  FROM `wdg`.`wp_fvote`");
       //$wpdb->query("INSERT INTO $table_name (impact) VALUES($ser_impact) ") ;


        echo 'Votre vote a été bien validé, merci à bientôt !';



    }
      
 }// endfunction ypcf_shortcode_printPageVoteForm
add_shortcode( 'yproject_crowdfunding_printPageVoteForm', 'ypcf_shortcode_printPageVoteForm' );