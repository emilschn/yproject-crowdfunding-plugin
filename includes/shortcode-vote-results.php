<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_vote_results() {
    global $wpdb, $campaign, $post, $edd_options;
    $table_name = $wpdb->prefix . "ypVotes";
    


    // La barre d'admin n'apparait que pour l'admin du site et pour l'admin de la page
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    $save_post = $post;
    if (isset($_GET['campaign_id'])) $post = get_post($_GET['campaign_id']);
    $author_id = $post->post_author;
    ob_start();
    if (($current_user_id == $author_id || current_user_can('manage_options')) && isset($_GET['campaign_id'])) {

    $crowdfunding = crowdfunding();

    $campaign = atcf_get_campaign( $post );
    $campaign_id =  $campaign->ID;

    // Cette variable permet de compter  le nombre de  partcipants
    $count_users = $wpdb->get_var( "SELECT count( user_id) FROM $table_name WHERE campaign_id = $campaign_id " );

    // Cette variable permet de compter  combien de fois les participants ont selectionner le bouton impact_positif
    $count_impact_postif  = $wpdb->get_var("SELECT count(impact) FROM $table_name WHERE campaign_id = $campaign_id  AND impact='positif'");

    // Cette variable permet de compter  combien de fois les participants ont selectionner le bouton impact_negatif
    $count_impact_negatif  = $wpdb->get_var("SELECT count(impact) FROM $table_name WHERE campaign_id = $campaign_id  AND impact='negatif'");

     // Cette variable permet de compter  combien de fois les participants ont selectionner le bouton le porjet doit etre retravaillé
    $count_retravaille  = $wpdb->get_var("SELECT count(retravaille) FROM $table_name WHERE campaign_id = $campaign_id  AND retravaille='retravaille'");

    // Cette variable permet de compter  combien de fois les participants ont selectionner le bouton le projet est pret pour la collecte
    $count_pret_collect  = $wpdb->get_var("SELECT count(retravaille) FROM $table_name WHERE campaign_id = $campaign_id  AND retravaille='pret'");

    // Ces variables permettent de compter  combien de fois on choisis l'un des element de la liste des risque est choisi
    $count_liste_risque  = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id " );

    // Ces variables permettent de stocker la somme totale que les participants sont préts à mettre sur le projet
    $count_sum  = $wpdb->get_var( "SELECT sum(sum) FROM $table_name WHERE campaign_id = $campaign_id " );



    // Ces variables permettent de compter  combien de fois les participants ont selectionne l'un des checbox du champs impact_positif
    $count_local          = $wpdb->get_var( "SELECT count(local)         FROM $table_name WHERE campaign_id = $campaign_id AND local         = 'local'" );
    $count_environemental = $wpdb->get_var( "SELECT count(environmental) FROM $table_name WHERE campaign_id = $campaign_id AND environmental = 'environmental'" );
    $count_social         = $wpdb->get_var( "SELECT count(social)        FROM $table_name WHERE campaign_id = $campaign_id AND social        = 'social'" );
    $count_autre          = $wpdb->get_var( "SELECT count(autre)         FROM $table_name WHERE campaign_id = $campaign_id AND autre         = 'autre'" );

    $count_total_impact_postif = $count_local +$count_environemental +$count_social +$count_autre;
    // Ces variables permettent de compter  en pourcentage combien de fois on cliqué l'un des checkbox   du champs impact_positif
    if ($count_impact_postif != 0) {
            
        $percent_local           = ($count_local / $count_impact_postif )* 100;
        $percent_environemental  = ($count_environemental / $count_impact_postif )* 100;
        $percent_economique      = ($count_economique / $count_impact_postif )* 100;
        $percent_social          = ($count_social / $count_impact_postif )* 100; 
        $percent_autre           = ($count_autre / $count_impact_postif )* 100;
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
    $count_responsable    = $wpdb->get_var( "SELECT count(pas_responsable)       FROM $table_name WHERE campaign_id = $campaign_id AND pas_responsable       = 'pas_responsable'" );
    $count_mal_explique   = $wpdb->get_var( "SELECT count(mal_explique)          FROM $table_name WHERE campaign_id = $campaign_id AND mal_explique          = 'mal_explique'" );
    $count_service        = $wpdb->get_var( "SELECT count(qualite_produit)       FROM $table_name WHERE campaign_id = $campaign_id AND qualite_produit       = 'qualite_produit'" );
    $count_equipe         = $wpdb->get_var( "SELECT count(qualite_equipe)        FROM $table_name WHERE campaign_id = $campaign_id AND qualite_equipe        = 'qualite_equipe'" );
    $count_plan           = $wpdb->get_var( "SELECT count(qualite_business_plan) FROM $table_name WHERE campaign_id = $campaign_id AND qualite_business_plan = 'qualite_business_plan'" );
    $count_innovation     = $wpdb->get_var( "SELECT count(qualite_innovation)    FROM $table_name WHERE campaign_id = $campaign_id AND qualite_innovation    = 'qualite_innovation'" );
    $count_porteur        = $wpdb->get_var( "SELECT count(qualite_marche)        FROM $table_name WHERE campaign_id = $campaign_id AND qualite_marche        = 'qualite_marche'" );

    $count_total_retravaille = $count_responsable + $count_mal_explique + $count_service + $count_equipe + $count_plan + $count_innovation + $count_porteur ;
    // Ces variables permettent de compter le pourcentage  des on cliqué l'un des checbox du champs projet doit etre retravaille
    if ($count_retravaille != 0) 
    {
        $percent_responsable   = ($count_responsable / $count_retravaille)* 100;
        $percent_mal_explique  = ($count_mal_explique / $count_retravaille)* 100;
        $percent_service       = ($count_service / $count_retravaille )* 100;
        $percent_equipe        = ($count_equipe / $count_retravaille )* 100;
        $percent_plan          = ($count_plan / $count_retravaille )* 100;
        $percent_innovation    = ($count_innovation / $count_retravaille )* 100;
        $percent_porteur       = ($count_porteur / $count_retravaille )* 100;
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
    $count_risque_tres_faible   = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='Le risque très faible' " );
    $count_risque_plutot_faible = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='Le risque est plutôt faible' " );
    $count_risque_modere        = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='Le risque est moderé' " );
    $count_risque_tres_eleve    = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='Le risque est très élevé' " );
    $count_risque_plutot_eleve  = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='Le risque plutôt élevé' " );

    
  
    $count_total_risque = $count_risque_tres_faible + $count_risque_plutot_faible + $count_risque_modere + $count_risque_tres_eleve + $count_risque_plutot_eleve;

    // Ces variables permettent de compter le pourcentage  du choix de l'element dans la liste "risque lié au projet"
    if($count_pret_collect != 0) 
    {  
        $percent_risque_tres_faible   = ($count_risque_tres_faible / $count_pret_collect )* 100;
        $percent_risque_plutot_faible = ($count_risque_plutot_faible / $count_pret_collect )* 100;
        $percent_risque_modere        = ($count_risque_modere / $count_pret_collect )* 100;
        $percent_risque_tres_eleve    = ($count_risque_tres_eleve / $count_pret_collect )* 100;
        $percent_risque_plutot_eleve  = ($count_risque_plutot_eleve / $count_pret_collect )* 100;
    }
    else
    {
        $percent_risque_tres_faible   = 0;
        $percent_risque_plutot_faible = 0;
        $percent_risque_modere        = 0;
        $percent_risque_tres_eleve    = 0;
        $percent_risque_plutot_eleve  = 0;   
    }

    if ($count_users != 0) {
        $percent_impact_positif = ($count_impact_postif / $count_users)*100 ;
        $percent_impact_negatif = ($count_impact_negatif / $count_users)*100 ;
        $percent_pret_collect   = ($count_pret_collect / $count_users)*100;
        $percent_retravaille    = ($count_retravaille / $count_users)*100;
    } else
    {
        $percent_impact_positif = 0;
        $percent_impact_negatif = 0;
        $percent_pret_collect   = 0;
        $percent_retravaille    = 0;
    }
    // calcul de la moyenne   
    if ($count_pret_collect == 0) {
        $moyenne = 0;
    }else
    {
        $moyenne = $count_sum / $count_pret_collect;
    }
    // calcule de la mediane
    if ($count_sum % 2 == 0) { // La somme totale est paire
        $mediane = $count_sum /2;
    } else
    {
       $mediane = ($count_sum + 1)/2; 
    }
    
    


    echo "</br>";

?>

 
<h2> Nombre total de participants : <?php  echo($count_users) ;?></h2> 
<div id="tab-title"><h3> Impact du projet</h3></div>
<table id="tab-results">
    <h4>  <?php  echo $percent_impact_positif; ?> %  des participants pensent que ce projet va avoir un impact positif</h4>
    <h4>  <?php  echo $percent_impact_negatif ; ?> % des participants pensent que ce projet n'a pas d'impact significatif</h4>
    <tr>
    <tr>Les personnes qui croient en l'impact positif de ce projet pensent qu'il va porter sur la(les) dimensions suivantes:</tr>
    <td>Local</td>
    <td><?php echo($count_local); ?></td><td>    <?php echo $percent_local; ?>%  <label><strong> de ceux qui pensent que le projet a un impact positif</strong></label></td>
    </tr>
    <tr>
    <td>Environnemental</td>
    <td><?php echo($count_environemental); ?></td><td> <?php echo $percent_environemental; ?>% <label><strong> de ceux qui pensent que le projet a un impact positif</strong></label></td>
    </tr>
    <tr>
    <tr>
    <td>Social</td>
    <td><?php echo($count_social); ?></td><td>   <?php echo $percent_social; ?>%<label><strong> de ceux qui pensent que le projet a un impact positif</strong></label></td>
    </tr>
    <tr>
    <td>Autre</td>
    <td><?php echo($count_autre); ?></td><td>   <?php echo $percent_autre; ?>%<label><strong> de ceux qui pensent que le projet a un impact positif</strong></label></td>
    </tr>
  </table>
  <div id="tab-title"><h3>Maturité du projet</h3></div>
  <table id="tab-results">
    <h4> <?php echo $percent_pret_collect; ?> %  pensent que ce projet est prêt pour la collecte</h4>
    <h4> <?php echo $percent_retravaille; ?> % pensent que ce projet doit être retravaillé </h4>
    <tr>
    <td>Les personnes qui pensent que ce projet est prêt seraient prêt à investir <?php echo $moyenne; ?>€  [ la moyenne du risque] </td>
    </tr>
    <tr>
    <td>La moitié de ces personnes investiraient plus de <?php echo $mediane;?>€  [médiane]</td>
    </tr>
</table>

<table id="tab-results">
</br>
    <h4>Les personnes qui pensent que ce projet est prêt ont évalué le risque à [moyenne] en moyenne:</h4>
    <tr>
    <td>Le risque est très faible</td>
    <td><?php echo($count_risque_tres_faible); ?></td> <td>  <?php echo $percent_risque_tres_faible; ?>% <label><strong>de ceux qui pensent que le projet est prêt</strong></label></td>
    </tr>
    <tr>
    <td>Le risque est plutôt faible</td>
    <td><?php echo($count_risque_plutot_faible); ?></td><td>  <?php echo $percent_risque_plutot_faible; ?>%<label><strong> de ceux qui pensent que le projet est prêt</strong></label></td>
    </tr>
    <tr>
    <td>Le risque est moderé</td>
    <td><?php echo($count_risque_modere); ?></td><td>   <?php echo $percent_risque_modere; ?>% <label><strong> de ceux qui pensent que le projet est prêt</strong></label></td>
    </tr>
    <tr>
    <td>Le risque est très élevé</td>
    <td><?php echo($count_risque_tres_eleve); ?></td><td>   soit    <?php echo $percent_risque_tres_eleve; ?>% <label><strong> de ceux qui pensent que le projet est prêt</strong></label></td>
    </tr>
    <tr>
    <td>Le risque plutôt élevé</td>
    <td><?php echo($count_risque_plutot_eleve);  ?></td><td>   soit    <?php echo $percent_risque_plutot_eleve; ?>%<label><strong> de ceux qui pensent que le projet est prêt</strong></label></td>
    </tr>
 </table>

 <table id="tab-results">
 </br>
    <h4>Les personnes qui pensent que ce projet doit être retravaillé ont souligné les points suivants:</h3>
    <tr>
    <td>Pas d’impact responsable</td>
    <td><?php echo($count_responsable);  ?></td><td> <?php  echo  $percent_responsable; ?>% <label><strong>de ceux qui pensent que le projet doit être retravaillé</strong></label></td>
    </tr>
    <tr>
    <td>Projet mal expliqué</td>
    <td><?php echo($count_mal_explique); ?></td><td>   <?php echo $percent_mal_explique; ?>% <label><strong>de ceux qui pensent que le projet doit être retravaillé</strong></label></td>
    </tr>
    <tr>
    <td>Qualité du produit/service</td>
    <td><?php echo($count_service); ?></td><td>   <?php echo $percent_service; ?>% <label><strong>de ceux qui pensent que le projet doit être retravaillé</strong></label></td>
    </tr>
    <tr>
    <td>Qualité de l’équipe</td>
    <td><?php echo($count_equipe); ?></td><td>    <?php echo $percent_equipe; ?>% <label><strong>de ceux qui pensent que le projet doit être retravaillé</strong></label></td>
    </tr>
    <tr>
    <td>Qualité du business plan</td>
    <td><?php echo($count_plan); ?></td><td>   <?php echo $percent_plan; ?>% <label><strong>de ceux qui pensent que le projet doit être retravaillé</strong></label></td>
    </tr>
    <td>Qualité d’innovation</td>
    <td><?php echo($count_innovation) ; ?></td><td>   <?php echo $percent_innovation; ?>% <label><strong>de ceux qui pensent que le projet doit être retravaillé</strong></label></td>
    </tr>
    <tr>
    <td>Qualité du marché, porteur</td>
    <td><?php echo($count_porteur); ?></td><td>   <?php echo $percent_porteur; ?>% <label><strong>de ceux qui pensent que le projet doit être retravaillé</strong></label></td>
    </tr>
  </table>
  
 <table>
 </br>
    <div id="tab-title"><h3>Conseils</h3></div>
    <h4>Les personnes qui ont voté ont souhaité vous apporter ces quelques conseils:</h4>

<?php

    $conseils = $wpdb->get_results( "SELECT user_login,user_email,user_id,conseil FROM $table_name WHERE campaign_id = $campaign_id " );

    echo '<table id="tab-results">';
    echo '<tr>'.'<td>';
        echo 'Conseils';
        echo '</td>';
        echo '<td>';
        echo 'Participants';
        echo '</td>'.'</tr>';
    foreach ( $conseils as $cons ) 
    {
        if(empty($cons->conseil)){
            echo '</table>';
        } else
        {
        echo '<tr>'.'<td>';
        echo $cons->conseil;
        echo '</td>';
        echo '<td>';
        echo $cons->user_login;
        echo '</td>'.'</tr>';
       }
    }
    echo '</table>';
    $post = $save_post;
    return ob_get_clean();
}


}
add_shortcode( 'yproject_crowdfunding_vote_results', 'ypcf_shortcode_vote_results' );


