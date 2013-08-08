<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 */
 function ypcf_shortcode_vote_results() {
    global $campaign, $post, $edd_options;
  

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

  

  ob_start();
  
    }

    return ob_get_clean();
}
add_shortcode( 'yproject_crowdfunding_vote_results', 'ypcf_shortcode_vote_results' );
// Séléction toutes les inforùmations liées à une vote
function ypcf_shortcode_users_votes() {
    global $wpdb;
    $table_name = $wpdb->prefix . "fVote";

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
    
    
    ?>


    <table> 
      <?php  
      
      // this will get the data from fvote table
      $retrieve_data = $wpdb->get_results( "SELECT * FROM $table_name WHERE $table_name->campaign_id = $this->campaign_id" );

      foreach ($retrieve_data as $retrieved_data){ 
      ?>
      <tr>
         <td><?php echo $retrieved_data->column_name; ?></td>
          <td><?php echo $retrieved_data->i; ?></td>
      </tr>
     
    </table>
    <?php    

    //on recupere les tableaux contenant les  valeurs à choix multiple : impact_ositif
     $question=$atts[question];
     $votes=$atts[total_votes];
     $answered_question = $wpdb->get_col("SELECT vote FROM ".$table_name." WHERE question='".$question."'");
     $subjects = $wpdb->get_results("SELECT subjects FROM ".$table_name." WHERE question='".$question."' limit 1");
     $remarks = $wpdb->get_results("SELECT remarks FROM ".$table_name." WHERE question='".$question."' limit 1");

       //Je désapprouve ce projet car son impact prévu n'est pas significatif
      $count_users = $wpdb->get_var("SELECT COUNT(user_id) FROM $table_name WHERE $table_name->campaign_id = $this->campaign_id ");
 
     // Parcours du tableau pour compter le nombre de présence de chaque valeur de checkboks

     for( $i=0; $i< sizeof($vote); $i++){

      switch ( $vote->i ) 
      {
      case 'local' :
         $count_local = $count_local + 1;

        break;
      case 'environemental' : 
        $count_environemental = $count_environemental + 1;

        break;
      case 'economique' :
        $count_economique = $count_economique + 1;

         break;
      case 'social' :
        $count_social = $count_social + 1;

        break;
      case 'autre' :
        $count_autre = $count_autre + 1;

        break;
      default : 
        break;
    }

  }

    //Je désapprouve ce projet car son impact prévu n'est pas significatif
  $count_desaprouve = $wpdb->get_var("SELECT COUNT(desaprouve) FROM $table_name WHERE $table_name->campaign_id = $this->campaign_id ");
 

  // Risque lié au projet

   $risque = $wpdb->get_var("SELECT COUNT(liste_risque) FROM $table_name WHERE $table_name->campaign_id = $this->campaign_id ");
   for( $i=0; $i< sizeof($risque); $i++)
   {
       echo $risque->column_name; 
       echo $risque->i; 
   }

    // Parcours du tableau pour compter le nombre de présence de chaque valeur de checkboks dans la liste "Je pense que ce projet doit être retravaillé"

     for( $i=0; $i< sizeof($vote); $i++){

      switch ( $question->i ) 
      {
      case 'pret_collect' :
         $count_pret_collect = $count_pret_collect + 1;

        break;
      case 'responsable' : 
        $count_responsable = $count_responsable + 1;

        break;
      case 'mal_explique' :
        $count_mal_explique = $count_mal_explique + 1;

         break;
      case 'service' :
        $count_service = $count_service + 1;

        break;
      case 'equipe' :
        $count_equipe = $count_equipe + 1;
        break;
      case 'plan' :
      $count_plan = $count_plan + 1;

         break;
      case 'innovation' :
        $count_innovation = $count_innovation + 1;

        break;
      case 'porteur' :
        $count_porteur = $count_porteur + 1;
        break;
      default : 
        break;
    }

  }

?>
  
  <table id="impact_positif">
    <tr>Nombre de votants : <?php $count_users ?></tr>

    <h2> Ce porojet a un impcat positif:</h2>
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
    <h2> Risque lié à ce projet:</h2>
    <tr>
    <td>Le risque très faible</td>
    <td><?php  ?></td>
    </tr>
    <tr>
    <td>Le risque plutôt faible</td>
    <td><?php  ?></td>
    </tr>
    <tr>
    <td>Le risque plutôt faible</td>
    <td><?php  ?></td>
    </tr>
    <tr>
    <td>Le risque plutôt élevé</td>
    <td><?php  ?></td>
    </tr>
    <tr>
    <td>Le risque très élevé</td>
    <td><?php  ?></td>
    </tr>
     <td> Je désaprouve de projet </td>
    <td><?php echo  $count_desaprouve ; ?></td>
    </tr>
    <tr> 
    <td>Je pense que ce projet est prèt pour la colecte</td>
    <td><?php  $count_pret_collect; ?></td>
    </tr>
    <h2> Je pense que ce projet doit être retravaillé:</h2>
    <tr>
    <td>Pas d’impact responsable</td>
    <td><?php $count_responsable; ?></td>
    </tr>
    <tr>
    <td>Projet mal expliqué</td>
    <td><?php $count_mal_explique; ?></td>
    </tr>
    <tr>
    <td>Qualité du produit/service</td>
    <td><?php $count_service; ?></td>
    </tr>
    <tr>
    <td>Qualité de l’équipe</td>
    <td><?php $count_equipe; ?></td>
    </tr>
    <tr>
    <td>Qualité du business plan</td>
    <td><?php  ?>$count_plan</td>
    </tr>
    <td>Qualité d’innovation</td>
    <td><?php $count_innovation ; ?></td>
    </tr>
    <tr>
    <td>Qualité du marché, porteur</td>
    <td><?php $count_porteur; ?></td>
    </tr>
  </table>
<?php


 //Je pense que ce projet est prêt pour la collecte

}
}
add_action( 'yproject_crowdfunding_users_vote','ypcf_shortcode_users_votes', 10, 0);




