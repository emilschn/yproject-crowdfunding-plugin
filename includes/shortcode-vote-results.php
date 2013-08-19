<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_vote_results() {
    global $wpdb, $campaign, $post, $edd_options;
    $table_name = $wpdb->prefix . "fVote";
  

    // La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    $author_id = get_the_author_meta('ID');
    if (($current_user_id == $author_id || current_user_can('manage_options')) && isset($_GET['campaign_id'])) {

    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post );
    
    $category_slug = $post->ID . '-vote-' . $post->post_title;
    $category_obj = get_category_by_slug($category_slug);



// Ces variables permettent de compter  combien de fois on cliqué l'un des checbox   du champs impact_ositif
    $count_local = 0;
    $count_environemental = 0;
    $count_economique = 0;
    $count_social = 0 ;
    $count_autre = 0;


    // Ces variables permettent de compter  combien de fois on cliqué l'un des checbox   du champs iprojet doit etre retravaille
    $count_pret_collect = 0;
    $count_responsable = 0;
    $count_mal_explique = 0;
    $count_service = 0 ;
    $count_equipe = 0;
    $count_plan = 0;
    $count_innovation = 0;
    $count_porteur = 0;

    // Ces variables permettent de compter  combien de fois on cliqué l'un des checbox   du champs projet doit etre retravaille
    $count_risque_tres_faible = 0;
    $count_risque_plutot_faible = 0;
    $count_risque_modere = 0;
    $count_risque_tres_eleve = 0 ;
    $count_risque_plutot_eleve= 0;

    // Ces variables permettent de compter  combien de fois on cliqué le choix: je désaprouve ce projet
    $count_desaprouve_projet = 0;

    ob_start();
    echo "</br></br>";
    // this will get the data from fvote table
    $retrieve_data = $wpdb->get_results( "SELECT * FROM $table_name" );
    print_r($retrieve_data);  /*test*/

?>  <table id="impact_positif">
    <tr>Nombre de votants : <?php $count_users ?></tr>

    <h4> Ce porojet a un impcat positif:</h4>
    <tr>
    <td>Local</td>
    <td><?php echo $count_local; ?></td>
    </tr>
    <tr>
    <td>Environnemental</td>
    <td><?php echo $count_environemental; ?></td>
    </tr>
    <tr>
    <td>Economique</td>
    <td><?php echo $count_economique; ?></td>
    </tr>
    <tr>
    <td>Social</td>
    <td><?php echo $count_social; ?></td>
    </tr>
    <tr>
    <td>Autre</td>
    <td><?php echo $count_autre; ?></td>
    </tr>
  </table>
  <table>
    <h4> Les risques liés à ce projet </h4>
    <tr>
    <td>Le risque est très faible</td>
    <td><?php echo $count_risque_tres_faible; ?></td>
    </tr>
    <tr>
    <td>Le risque est plutôt faible</td>
    <td><?php echo $count_risque_plutot_faibles; ?></td>
    </tr>
    <tr>
    <td>Le risque est moderé</td>
    <td><?php echo $count_risque_modere; ?></td>
    </tr>
    <tr>
    <td>Le risque est très élevé</td>
    <td><?php echo $count_risque_tres_eleve; ?></td>
    </tr>
    <tr>
    <td>Le risque plutôt élevé</td>
    <td><?php echo $count_risque_plutot_eleve;  ?></td>
    </tr>
 </table>
 <table>
    <td> Je désaprouve de projet </td>
    <td><?php echo  $count_desaprouve_projet ; ?></td>
    </tr>
    <tr> 
    <td>Je pense que ce projet est prèt pour la colecte</td>
    <td><?php echo $count_pret_collect; ?></td>
    </tr>
  </table>
  <tabla style={float:right;}>
    <h4> Je pense que ce projet doit être retravaillé avant de pouvoir être financé. Sur quels points:</h4>
    <tr>
    <td>Pas d’impact responsable</td>
    <td><?php echo $count_responsable; ?></td>
    </tr>
    <tr>
    <td>Projet mal expliqué</td>
    <td><?php echo $count_mal_explique; ?></td>
    </tr>
    <tr>
    <td>Qualité du produit/service</td>
    <td><?php echo $count_service; ?></td>
    </tr>
    <tr>
    <td>Qualité de l’équipe</td>
    <td><?php echo $count_equipe; ?></td>
    </tr>
    <tr>
    <td>Qualité du business plan</td>
    <td><?php echo $count_plan ?></td>
    </tr>
    <td>Qualité d’innovation</td>
    <td><?php echo $count_innovation ; ?></td>
    </tr>
    <tr>
    <td>Qualité du marché, porteur</td>
    <td><?php echo $count_porteur; ?></td>
    </tr>
  </table>
<?php

  

  echo "test";
  
 

    return ob_get_clean();
}


}
add_shortcode( 'yproject_crowdfunding_vote_results', 'ypcf_shortcode_vote_results' );



//****************************************************************************//
//**********************Fonctions d'insertion pour le formualaire de votes*****************************************************
//****************************************************************//

// formulaire de vote
function ypcf_shortcode_printPageVoteForm($post, $campaign) {
  ?>
  <?php
  global $wpdb;
    $table_name = $wpdb->prefix . "fvote";
    
   


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
    
      // recupération des valeurs des cases à cocher dans un tabeau 
      $impact = implode(', ',$options); 
      $retravaille = implode(', ',$options1);
    
     //  print_r($retrieve_data); 
      
       $ser_impact = serialize($impact);
      $wpdb->query( "INSERT INTO `wdg`.`wp_fvote` (`id`, `impact`, `retravaille`, `liste_risque`, `investir`, `sum`, `risque`, `isvoted`, `user_id`, `campaign_id`)
                  VALUES (NULL, 'localeee', 'impact plutôt faible', NULL, '$investir', '$sum' ,NULL, '$isvoted',  NULL, '$user_id', '$campaign_id'      )");
      // test la BDD  $wpdb->query("SELECT sum, local  FROM `wdg`.`wp_fvote`");
       //$wpdb->query("INSERT INTO $table_name (impact) VALUES($ser_impact) ") ;


        echo 'Success, merci à bientôt !';



    }
         

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
                        <input id="precision" name="precision" type="text" placeholder="précisez ici" />
                    </div>
                    
                    <input id="impact_negatif" type="radio" name="radios1" value="impact_negatif" checked="checked">
                      Je désapprouve ce projet car son impact prévu n'est pas significatif
                    </input></br></br>
                    
                    <input type="radio" name="radios2" value="pret_collect" checked="checked">
                     Je pense que ce projet est prêt pour la collecte
                    </input></br>

                    <div>
                        <label  id="investir" name="investir" value="investir">
                          Je serais prêt à investir
                        </label></br>

                        <input id="sum" name="sum" type="text" placeholder="200€"   style="display: "/></br>
                    </div>   

                    <label id="risque" name="risque" value="risque">
                       Risque lié à ce projet
                    </label></br>
                    <select id="liste_risque" name="liste_risque" >
                        <option id="tres_faible">Le risque très faible</option>
                        <option id="plutot_faible">Le risque plutôt faible</option>
                        <option id="modere">Le risque modéré</option>
                        <option id="plutot_eleve">Le risque plutôt élevé</option>
                        <option id="tres_eleve">Le risque très élevé</option>
                    </select></br>
                    
                    <input type="radio" name="radios2" value="pret_collect">
                        Je pense que ce projet doit être retravaillé avant de pouvoir être financé. Sur quels points 
                    </input></br>
                    <div id="liste_retravaille" style="display: ">
                        <input type="checkbox" iname="choice1[]" value="responsable">
                          Pas d’impact responsable
                        </input></br>

                        <input type="checkbox" name="choice1[]" value="mal_explique">
                          Projet mal expliqué  
                        </input></br>

                        <input type="checkbox" name="choice1[]" value="service">
                          Qualité du produit/service
                        </input></br>

                        <input type="checkbox" name="choice1[]" value="equipe">
                          Qualité de l’équipe
                        </input></br>

                        <input type="checkbox" id="plan" name="plan" value="plan">
                          Qualité du business plan
                        </input></br>

                        <input type="checkbox" id="innovation" name="innovation" value="innovation">
                          Qualité d’innovation
                        </input></br>

                        <input type="checkbox" name="porteur" value="porteur" id="porteur">
                          Qualité du marché, porteur
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
 }

add_shortcode( 'yproject_crowdfunding_printPageVoteForm', 'ypcf_shortcode_printPageVoteForm' );
