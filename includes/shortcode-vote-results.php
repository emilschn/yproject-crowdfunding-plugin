<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_vote_results() {
    global $wpdb, $campaign, $post, $edd_options;
    $table_name = $wpdb->prefix . "ypVote";
    


    // La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    $author_id = get_the_author_meta('ID');
    if (($current_user_id == $author_id || current_user_can('manage_options')) && isset($_GET['campaign_id'])) {

    $crowdfunding = crowdfunding();

    $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post );
    $campaign_id =  $campaign->ID;
    
    $category_slug = $post->ID . '-vote-' . $post->post_title;
    $category_obj = get_category_by_slug($category_slug);

    // Cette variable permet de compter  le nombre de  partcipants
    $count_users = $wpdb->get_results( "SELECT count(distinct user_id) FROM $table_name WHERE campaign_id = $campaign_id " );

    // Cette variable permet de compter  combien de fois les participants ont selectionner le bouton impact_positif
    $count_impact_postif  = $wpdb->get_results("SELECT count(impact) FROM $table_name WHERE campaign_id = $campaign_id  AND impact='positif'");

    // Cette variable permet de compter  combien de fois les participants ont selectionner le bouton impact_negatif
    $count_impact_negatif  = $wpdb->get_results("SELECT count(impact) FROM $table_name WHERE campaign_id = $campaign_id  AND impact='negatif'");

     // Cette variable permet de compter  combien de fois les participants ont selectionner le bouton le porjet doit etre retravaillé
    $count_retravaille  = $wpdb->get_results("SELECT count(retravaille) FROM $table_name WHERE campaign_id = $campaign_id  AND impact='retravaille'");

    // Cette variable permet de compter  combien de fois les participants ont selectionner le bouton le projet est pret pour la collecte
    $count_pret_collect  = $wpdb->get_results("SELECT count(retravaille) FROM $table_name WHERE campaign_id = $campaign_id  AND impact='pret'");

    // Ces variables permettent de compter  combien de fois on choisis l'un des element de la liste des risque est choisi
    $count_liste_risque  = $wpdb->get_results( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id " );




    // Ces variables permettent de compter  combien de fois les participants ont selectionne l'un des checbox du champs impact_positif
    $count_local          = $wpdb->get_results( "SELECT count(local)         FROM $table_name WHERE campaign_id = $campaign_id AND local         = 'local'" );
    $count_environemental = $wpdb->get_results( "SELECT count(environmental) FROM $table_name WHERE campaign_id = $campaign_id AND environmental = 'environmental'" );
    $count_social         = $wpdb->get_results( "SELECT count(social)        FROM $table_name WHERE campaign_id = $campaign_id AND social        = 'social'" );
    $count_autre          = $wpdb->get_results( "SELECT count(autre)         FROM $table_name WHERE campaign_id = $campaign_id AND autre         = 'autre'" );

    // Ces variables permettent de compter  en pourcentage combien de fois on cliqué l'un des checkbox   du champs impact_positif
    if ($count_impact_postif != 0) {
            
        $percent_local           = ($count_local % $count_impact_postif )* 100;
        $percent_environemental  = ($count_environemental % $count_impact_postif )* 100;
        $percent_economique      = ($count_economique % $count_impact_postif )* 100;
        $percent_social          = ($count_social % $count_impact_postif )* 100; 
        $percent_autre           = ($count_autre % $count_impact_postif )* 100;
     }
     else
     {
        $percent_local           = 0;
        $percent_environemental  = 0;
        $percent_economique      = 0;
        $percent_social          = 0; 
        $percent_autre           = 0; 
     }


   
    // Ces variables permettent de compter  combien de fois on cliqué l'un des checkbox   du champs iprojet doit etre retravaille
    $count_responsable    = $wpdb->get_results( "SELECT count(pas_responsable)       FROM $table_name WHERE campaign_id = $campaign_id AND pas_responsable       = 'pas_responsable'" );
    $count_mal_explique   = $wpdb->get_results( "SELECT count(mal_explique)          FROM $table_name WHERE campaign_id = $campaign_id AND mal_explique          = 'mal_explique'" );
    $count_service        = $wpdb->get_results( "SELECT count(qualite_produit)       FROM $table_name WHERE campaign_id = $campaign_id AND qualite_produit       = 'qualite_produit'" );
    $count_equipe         = $wpdb->get_results( "SELECT count(qualite_equipe)        FROM $table_name WHERE campaign_id = $campaign_id AND qualite_equipe        = 'qualite_equipe'" );
    $count_plan           = $wpdb->get_results( "SELECT count(qualite_business_plan) FROM $table_name WHERE campaign_id = $campaign_id AND qualite_business_plan = 'qualite_business_plan'" );
    $count_innovation     = $wpdb->get_results( "SELECT count(qualite_innovation)    FROM $table_name WHERE campaign_id = $campaign_id AND qualite_innovation    = 'qualite_innovation'" );
    $count_porteur        = $wpdb->get_results( "SELECT count(qualite_marche)        FROM $table_name WHERE campaign_id = $campaign_id AND qualite_marche        = 'qualite_marche'" );

    // Ces variables permettent de compter le pourcentage  des on cliqué l'un des checbox du champs projet doit etre retravaille
    if ($count_retravaille != 0) 
    {
        $percent_responsable   = ($count_responsable % $count_retravaille)* 100;
        $percent_mal_explique  = ($count_mal_explique % $count_retravaille)* 100;
        $percent_service       = ($count_service % $count_retravaille )* 100;
        $percent_equipe        = ($count_equipe % $count_retravaille )* 100;
        $percent_plan          = ($count_plan % $count_retravaille )* 100;
        $percent_innovation    = ($count_innovation % $count_retravaille )* 100;
        $percent_porteur       = ($count_porteur % $count_retravaille )* 100;
    }
    else
    {
        $percent_responsable   = 0;
        $percent_mal_explique  = 0;
        $percent_service       = 0;
        $percent_equipe        = 0;
        $percent_plan          = 0;
        $percent_innovation    = 0;
        $percent_porteur       = 0;
    }


    // Ces variables permettent de compter  combien de fois on element est choisi dans la liste "risque lié au projet"
    $count_risque_tres_faible   = $wpdb->get_results( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='risque_tres_faible' " );
    $count_risque_plutot_faible = $wpdb->get_results( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='risque_plutot_faible' " );
    $count_risque_modere        = $wpdb->get_results( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='risque_modere' " );
    $count_risque_tres_eleve    = $wpdb->get_results( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='risque_tres_eleve' " );
    $count_risque_plutot_eleve  = $wpdb->get_results( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='risque_plutot_eleve' " );

    
  


    // Ces variables permettent de compter le pourcentage  du choix de l'element dans la liste "risque lié au projet"
    if($count_liste_risque != 0) 
    {  
        $percent_risque_tres_faible   = ($count_risque_tres_faible % $count_liste_risque )* 100;
        $percent_risque_plutot_faible = ($count_risque_plutot_faible % $count_liste_risque )* 100;
        $percent_risque_modere        = ($count_risque_modere % $count_liste_risque )* 100;
        $percent_risque_tres_eleve    = ($count_risque_tres_eleve % $count_liste_risque )* 100;
        $percent_risque_plutot_eleve  = ($count_risque_plutot_eleve % $count_liste_risque )* 100;
    }
    else
    {
        $percent_risque_tres_faible   = 0;
        $percent_risque_plutot_faible = 0;
        $percent_risque_modere        = 0;
        $percent_risque_tres_eleve    = 0;
        $percent_risque_plutot_eleve  = 0;   
    }
    

    ob_start();
    echo "</br>";

?>

 
    <h2> Nombre total de participants : <?php  print_r($count_users) ;?></h2> 
   
 <table id="impact_positif">
    <h3> Impact du projet</h3>
    <h4>  <?php  print_r($count_impact_postif); ?> %  pensent que ce projet va avoir un impact positif</h4>
    <h4>  <?php  print_r($count_impact_negatif) ; ?> % pensent que ce projet n'a pas d'impact significatif</h4>
    <tr>
    <tr>Les personnes qui croient en l'impact positif de ce projet pensent qu'il va porter sur la(les) dimensions suivantes:</tr>
    <td>Local</td>
    <td><?php print_r($count_local); ?></td><td>   soit    <?php echo $percent_local; ?>%</td>
    </tr>
    <tr>
    <td>Environnemental</td>
    <td><?php print_r($count_environemental); ?></td><td>   soit    <?php echo $percent_environemental; ?>%</td>
    </tr>
    <tr>
    <tr>
    <td>Social</td>
    <td><?php print_r($count_social); ?></td><td>   soit    <?php echo $percent_social; ?>%</td>
    </tr>
    <tr>
    <td>Autre</td>
    <td><?php print_r($count_autre); ?></td><td>   soit    <?php echo $percent_autre; ?>%</td>
    </tr>
  </table>
  <table>
    </br>
    <h3>Maturité du projet</h3>
    <h4> <?php print_r($count_pret_collect); ?> % pensent que ce projet est prêt pour la collecte</h4>
    <h4> <?php print_r($count_retravaille); ?> % pensent que ce projet doit être retravaillé </h4>
    <tr>
    <td>Les personnes qui pensent que ce projet est prêt seraient prêt à investir  0€ en moyenne. </td>
    </tr>
    <tr>
    <td>La moitié de cette personne investiraient plus de X[médiane]€</td>
    </tr>
</table>

<table>
</br>
    <h4>Les personnes qui pensent que ce projet est prêt ont évalué le risque à [moyenne] en moyenne:</h4>
    <tr>
    <td>Le risque est très faible</td>
    <td><?php print_r($count_risque_tres_faible); ?></td> <td>   soit    <?php echo $percent_risque_tres_faible; ?>%</td>
    </tr>
    <tr>
    <td>Le risque est plutôt faible</td>
    <td><?php print_r($count_risque_plutot_faible); ?></td><td>   soit    <?php echo $percent_risque_plutot_faible; ?>%</td>
    </tr>
    <tr>
    <td>Le risque est moderé</td>
    <td><?php print_r($count_risque_modere); ?></td><td>   soit    <?php echo $percent_risque_modere; ?>%</td>
    </tr>
    <tr>
    <td>Le risque est très élevé</td>
    <td><?php print_r($count_risque_tres_eleve); ?></td><td>   soit    <?php echo $percent_risque_tres_eleve; ?>%</td>
    </tr>
    <tr>
    <td>Le risque plutôt élevé</td>
    <td><?php print_r($count_risque_plutot_eleve);  ?></td><td>   soit    <?php echo $percent_risque_plutot_faible; ?>%</td>
    </tr>
 </table>

 <tabla style={float:right;}>
 </br>
    <h4>Les personnes qui pensent que ce projet doit être retravaillé ont souligné les points suivants:</h3>
    <tr>
    <td>Pas d’impact responsable</td>
    <td><?php print_r($count_responsable);  ?></td><td>   soit    <?php  echo  $percent_responsable; ?>%</td>
    </tr></br>
    <tr>
    <td>Projet mal expliqué</td>
    <td><?php print_r($count_mal_explique); ?></td><td>   soit    <?php echo $percent_mal_explique; ?>%</td>
    </tr></br>
    <tr>
    <td>Qualité du produit/service</td>
    <td><?php print_r($count_service); ?></td><td>   soit    <?php echo $percent_service; ?>%</td>
    </tr></br>
    <tr>
    <td>Qualité de l’équipe</td>
    <td><?php print_r($count_equipe); ?></td><td>   soit    <?php echo $percent_equipe; ?>%</td>
    </tr></br>
    <tr>
    <td>Qualité du business plan</td>
    <td><?php print_r($count_plan); ?></td><td>   soit    <?php echo $percent_plan; ?>%</td>
    </tr></br>
    <td>Qualité d’innovation</td>
    <td><?php print_r($count_innovation) ; ?></td><td>   soit    <?php echo $percent_innovation; ?>%</td>
    </tr></br>
    <tr>
    <td>Qualité du marché, porteur</td>
    <td><?php print_r($count_porteur); ?></td><td>   soit    <?php echo $percent_porteur; ?>%</td>
    </tr></br>
  </table>
  
 <table>
 </br>
    <h3>Conseils</h3>
    <h4>Les personnes qui ont voté ont souhaité vous apporter ces quelques conseils:</h4>
    <tr style="min-width:300px"> 
         <td style="min-width:300px;"> Conseil</td>
         <td style="min-width:300px;"> Utilisateur</td> 
          
    </tr> 
    <tr style="min-width:300px"> 
         <td style="min-width:300px;"> patati patata  </td> 
         <td style="min-width:300px;"> Mr XX </td>
    </tr>
   
 </table>



<?php

 

    return ob_get_clean();
}


}
add_shortcode( 'yproject_crowdfunding_vote_results', 'ypcf_shortcode_vote_results' );


