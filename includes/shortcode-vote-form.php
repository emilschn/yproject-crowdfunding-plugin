<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



//****************************************************************************//
//**********************Fonctions d'insertion pour le formualaire de votes*****************************************************
//****************************************************************//

// formulaire de vote
function ypcf_shortcode_printPageVoteForm($post, $campaign) {
  
  global $wpdb;
    $table_name = $wpdb->prefix . "ypVote";  

    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post ); 

  

     if (isset($_POST['submit']))
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

        
        $isvoted                ; // cette variable verifiera si l'ulisateur a deja vote
        $user_id                =  wp_get_current_user()->ID;
        $post                   = get_post(get_the_ID());
        $campaign               = atcf_get_campaign( $post );
        $campaign_id            =  $campaign->ID;



    
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
                                'campaign_id'             => $campaign_id
                              )); 

        echo "Le vote est valid&eacute, merci";
        
     }
 ?>
            <!--Formulaire de soumission de vote, visible depuis la page project des projets en vote-->
        <form name="fVote" action="<?php get_permalink();?>" method="POST" class="fVote-form" enctype="multipart/form-data">
            <div class="left post_bottom_infos">
            
                <fieldset>
                    <legend>Votez sur ce projet</legend>
                    
                    <input id="impact_positif" type="radio" name="impact"  value="positif" checked="checked">
                       Je pense que ce projet va avoir un impact positif
                    </input>

                    <div id="liste_impact_positif_choix" style="display: ">
                        <input type="checkbox" name="local"  value="local">
                          Local
                        </input></br>
                        <input type="checkbox" name="environmental" value="environmental">
                          Environnemental
                        </input></br>
                        
                        <input type="checkbox" name="social" value="social">
                          Social
                        </label></br>
                        <input type="checkbox" name="autre" value="autre">
                          Autre
                        </input>
                        <input id="precision" name="precision" type="text" placeholder="tapez ici" />
                    </div>
                    
                    <input id="desaprouve" type="radio" name="impact" value="negatif" >
                      Je d&egravesapprouve ce projet car son impact pr&eacutevu n'est pas significatif
                    </input></br></br>
                    
                    <input type="radio" name="maturite" value="pret" checked="checked">
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
                        <option id="risque_tres_faible">Le risque tr&egraves faible</option>
                        <option id="risque_plutot_faible">Le risque plut&ocirct faible</option>
                        <option id="risque_modere">Le risque mod&eacuter&eacute</option>
                        <option id="risque_plutot_eleve">Le risque plut&ocirct &eacutelev&eacute</option>
                        <option id="risque_tres_eleve">Le risque tr&egraves &eacutelev&eacute</option>
                    </select></br>
                    
                    <input type="radio" name="maturite" value="retravaille">
                        Je pense que ce projet doit &ecirctre retravaill&eacute avant de pouvoir &ecirctre financ&eacute. Sur quels points 
                    </input></br>
                    <div id="liste_retravaille" style="display: ">
                        <input type="checkbox" name="pas_responsable" value="pas_responsable">
                          Pas d&acuteimpact responsable
                        </input></br>

                        <input type="checkbox" name="mal_explique" value="mal_explique">
                          Projet mal expliqu&eacute  
                        </input></br>

                        <input type="checkbox" name="qualite_produit" value="qualite_produit">
                          Qualit&eacute du produit/service
                        </input></br>

                        <input type="checkbox" name="qualite_equipe" value="qualite_equipe">
                          Qualit&eacute de l&acute&eacutequipe
                        </input></br>

                        <input type="checkbox"  name="qualite_business_plan" value="qualite_business_plan">
                          Qualit&eacute du business plan
                        </input></br>

                        <input type="checkbox"  name="qualite_innovation" value="qualite_innovation">
                          Qualit&eacute d&acuteinnovation
                        </input></br>

                        <input type="checkbox" name="qualite_marche" value="qualite_marche" >
                          Qualit&eacute du march&eacute, porteur
                        </input></br>
            
                        <label> Expliquer pourquoi</label>
                        <textarea type="text" name="conseil" id="conseil" value="conseil">
                        
                        </textarea></br>
                    </div>
                    <input type="submit" name="submit" value= "valider" />
                 </fieldset>
            </div>
         </form>   
        </div>
<?php
}

    add_shortcode( 'yproject_crowdfunding_printPageVoteForm', 'ypcf_shortcode_printPageVoteForm' );

