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
    $campaign_id =  $campaign->ID;
    
    $category_slug = $post->ID . '-vote-' . $post->post_title;
    $category_obj = get_category_by_slug($category_slug);

    // Ces variables permettent de compter  combien de fois on cliqué l'un des checbox   du champs impact_ositif
    $count_users  = $wpdb->query("SELECT count('user_id') FROM `$table_name` WHERE 'campaign_id'= $campaign_id ");


    // Ces variables permettent de compter  combien de fois on cliqué l'un des checbox   du champs impact_ositif
    $count_impact_postif  = $wpdb->query("SELECT count('impact_postif') FROM `$table_name` WHERE 'campaign_id'= $campaign_id ");


// Ces variables permettent de compter  combien de fois on cliqué l'un des checbox   du champs impact_ositif
    $count_local          = $wpdb->query("SELECT count('local')         FROM `$table_name`");
    $count_environemental = $wpdb->query("SELECT count('environmental') FROM `$table_name`");
    $count_social         = $wpdb->query("SELECT count('social')        FROM `$table_name`");
    $count_autre          = $wpdb->query("SELECT count('autre')         FROM `$table_name`");

    // Ces variables permettent de compter  en pourcentage combien de fois on cliqué l'un des checbox   du champs impact_ositif
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

    // Ces variables permettent de compter  combien de fois on cliqué l'un radio bouton "le projet est pret pour la collecte"
    $count_pret_collect   = $wpdb->query("SELECT count('pret_collect') FROM `$table_name` WHERE 'campaign_id'= $campaign_id");;
    
    // Ces variables permettent de compter  en porcentage combien de fois on cliqué l'un des checbox   du champs iprojet doit etre retravaille
    if ($count_users != 0) {
        $percent_pret_collect  = ($count_pret_collect % $count_users)* 100 ;
    }
    else
    {
        $count_users = 0;
    }
    

    // Ces variables permettent de compter  combien de fois on cliqué l'un des checbox   du champs impact_ositif
    $count_retravaille_projet  = $wpdb->query("SELECT count('retravaille_projet') FROM `$table_name` WHERE 'campaign_id'= $campaign_id");
   
    // Ces variables permettent de compter  combien de fois on cliqué l'un des checbox   du champs iprojet doit etre retravaille
    $count_responsable    = 0;
    $count_mal_explique   = 0;
    $count_service        = 0;
    $count_equipe         = 0;
    $count_plan           = 0;
    $count_innovation     = 0;
    $count_porteur        = 0;

    // Ces variables permettent de compter le pourcentage  des on cliqué l'un des checbox du champs projet doit etre retravaille
    if ($count_retravaille_projet != 0) 
    {
        $percent_responsable   = ($count_responsable % $count_retravaille_projet )* 100;
        $percent_mal_explique  = ($count_mal_explique % $count_retravaille_projet )* 100;
        $percent_service       = ($count_service % $count_retravaille_projet )* 100;
        $percent_equipe        = ($count_equipe % $count_retravaille_projet )* 100;
        $percent_plan          = ($count_plan % $count_retravaille_projet )* 100;
        $percent_innovation    = ($count_innovation % $count_retravaille_projet )* 100;
        $percent_porteur       = ($count_porteur % $count_retravaille_projet )* 100;
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
    $count_risque_projet   = $wpdb->query("SELECT count('risque') FROM `$table_name`WHERE 'campaign_id'= $campaign_id");

    // Ces variables permettent de compter  combien de fois on element est choisi dans la liste "risque lié au projet"
    $count_risque_tres_faible   = 0;
    $count_risque_plutot_faible = 0;
    $count_risque_modere        = 0;
    $count_risque_tres_eleve    = 0;
    $count_risque_plutot_eleve  = 0;

    // Ces variables permettent de compter le pourcentage  du choix de l'element dans la liste "risque lié au projet"
    if($count_risque_projet != 0) 
    {  
        $percent_risque_tres_faible   = ($count_risque_tres_faible % $count_risque_projet )* 100;
        $percent_risque_plutot_faible = ($count_risque_plutot_faible % $count_risque_projet )* 100;
        $percent_risque_modere        = ($count_risque_modere % $count_risque_projet )* 100;
        $percent_risque_tres_eleve    = ($count_risque_tres_eleve % $count_risque_projet )* 100;
        $percent_risque_plutot_eleve  = ($count_risque_plutot_eleve % $count_risque_projet )* 100;
    }
    else
    {
        $percent_risque_tres_faible   = 0;
        $percent_risque_plutot_faible = 0;
        $percent_risque_modere        = 0;
        $percent_risque_tres_eleve    = 0;
        $percent_risque_plutot_eleve  = 0;   
    }

    // Ces variables permettent de compter  combien de fois on cliqué le choix: je désaprouve ce projet
    $count_desaprouve_projet = $wpdb->query("SELECT count('desarprouve') FROM `$table_name`WHERE 'campaign_id'= $campaign_id");

    if ($count_users != 0) 
    {
        $percent_desaprouve_projet = ($count_pret_collect % $count_users)* 100 ; 
    }
    else
    {
        $count_users = 0;
    }
    

    ob_start();
    echo "</br>";
    // this will get the data from fvote table
   // $retrieve_data = $wpdb->get_results( "SELECT * FROM $table_name" );
   // print_r($retrieve_data);  /*test*/

?>

 
    <h2> Nombre total de participants : <?php  echo $count_users; ?></h2> 
   
 <table id="impact_positif">
    <h3> Impact du projet</h3>
    <h4>  <?php  echo $count_impact_postif; ?> %  pensent que ce projet va avoir un impact positif</h4>
    <h4>  <?php  echo $count_desaprouve_projet ; ?> % pensent que ce projet n'a pas d'impact significatif</h4>
    <tr>
    <tr>Les personnes qui croient en l'impact positif de ce projet pensent qu'il va porter sur la(les) dimensions suivantes:</tr>
    <td>Local</td>
    <td><?php echo $count_local; ?></td><td>   soit    <?php echo $percent_local; ?>%</td>
    </tr>
    <tr>
    <td>Environnemental</td>
    <td><?php echo $count_environemental; ?></td><td>   soit    <?php echo $percent_environemental; ?>%</td>
    </tr>
    <tr>
    <tr>
    <td>Social</td>
    <td><?php echo $count_social; ?></td><td>   soit    <?php echo $percent_social; ?>%</td>
    </tr>
    <tr>
    <td>Autre</td>
    <td><?php echo $count_autre; ?></td><td>   soit    <?php echo $percent_autre; ?>%</td>
    </tr>
  </table>
  <table>
    </br>
    <h3>Maturité du projet</h3>
    <h4> <?php echo $count_pret_collect; ?> % pensent que ce projet est prêt pour la collecte</h4>
    <h4> <?php echo $count_impact_postif; ?> % pensent que ce projet doit être retravaillé </h4>
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
    <td><?php echo $count_risque_tres_faible; ?></td> <td>   soit    <?php echo $percent_risque_tres_faible; ?>%</td>
    </tr>
    <tr>
    <td>Le risque est plutôt faible</td>
    <td><?php echo $count_risque_plutot_faible; ?></td><td>   soit    <?php echo $percent_risque_plutot_faible; ?>%</td>
    </tr>
    <tr>
    <td>Le risque est moderé</td>
    <td><?php echo $count_risque_modere; ?></td><td>   soit    <?php echo $percent_risque_modere; ?>%</td>
    </tr>
    <tr>
    <td>Le risque est très élevé</td>
    <td><?php echo $count_risque_tres_eleve; ?></td><td>   soit    <?php echo $percent_risque_tres_eleve; ?>%</td>
    </tr>
    <tr>
    <td>Le risque plutôt élevé</td>
    <td><?php echo $count_risque_plutot_eleve;  ?></td><td>   soit    <?php echo $percent_risque_plutot_faible; ?>%</td>
    </tr>
 </table>

 <tabla style={float:right;}>
 </br>
    <h4>Les personnes qui pensent que ce projet doit être retravaillé ont souligné les points suivants:</h3>
    <tr>
    <td>Pas d’impact responsable</td>
    <td><?php echo  $count_responsable;  ?></td><td>   soit    <?php  echo  $percent_responsable; ?>%</td>
    </tr></br>
    <tr>
    <td>Projet mal expliqué</td>
    <td><?php echo $count_mal_explique; ?></td><td>   soit    <?php echo $percent_mal_explique; ?>%</td>
    </tr></br>
    <tr>
    <td>Qualité du produit/service</td>
    <td><?php echo $count_service; ?></td><td>   soit    <?php echo $percent_service; ?>%</td>
    </tr></br>
    <tr>
    <td>Qualité de l’équipe</td>
    <td><?php echo $count_equipe; ?></td><td>   soit    <?php echo $percent_equipe; ?>%</td>
    </tr></br>
    <tr>
    <td>Qualité du business plan</td>
    <td><?php echo $count_plan ?></td><td>   soit    <?php echo $percent_plan; ?>%</td>
    </tr></br>
    <td>Qualité d’innovation</td>
    <td><?php echo $count_innovation ; ?></td><td>   soit    <?php echo $percent_innovation; ?>%</td>
    </tr></br>
    <tr>
    <td>Qualité du marché, porteur</td>
    <td><?php echo $count_porteur; ?></td><td>   soit    <?php echo $percent_porteur; ?>%</td>
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


