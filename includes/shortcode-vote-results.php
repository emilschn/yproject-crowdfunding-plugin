<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function ypcf_printable_value($val) {
    return round($val, 1);
}

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

    // Cette variable permet de compter combien de fois les participants n'ont pas cliqué sur Prêt/Pas prêt
    $count_pasrepondu_collect  = $wpdb->get_var("SELECT count(retravaille) FROM $table_name WHERE campaign_id = $campaign_id  AND retravaille=''");

    // Ces variables permettent de stocker la somme totale que les participants sont prêts à mettre sur le projet
    $count_sum  = $wpdb->get_var( "SELECT sum(sum) FROM $table_name WHERE campaign_id = $campaign_id AND sum > 0" );
    $count_givers  = $wpdb->get_var( "SELECT count(user_id) FROM $table_name WHERE campaign_id = $campaign_id AND sum > 0" );



    // Ces variables permettent de compter  combien de fois les participants ont selectionne l'un des checbox du champs impact_positif
    $count_local          = $wpdb->get_var( "SELECT count(local)         FROM $table_name WHERE campaign_id = $campaign_id AND local         = 'local'" );
    $count_environemental = $wpdb->get_var( "SELECT count(environmental) FROM $table_name WHERE campaign_id = $campaign_id AND environmental = 'environmental'" );
    $count_social         = $wpdb->get_var( "SELECT count(social)        FROM $table_name WHERE campaign_id = $campaign_id AND social        = 'social'" );
    $count_autre          = $wpdb->get_var( "SELECT count(autre)         FROM $table_name WHERE campaign_id = $campaign_id AND autre         <> ''" );
    $autre_list		  = $wpdb->get_results( "SELECT autre		 FROM $table_name WHERE campaign_id = $campaign_id AND autre         <> ''" );

    // Ces variables permettent de compter  en pourcentage combien de fois on cliqué l'un des checkbox   du champs impact_positif
    if ($count_impact_postif != 0) {
        $percent_local           = ($count_local / $count_impact_postif )* 100;
        $percent_environemental  = ($count_environemental / $count_impact_postif )* 100;
        $percent_social          = ($count_social / $count_impact_postif )* 100; 
        $percent_autre           = ($count_autre / $count_impact_postif )* 100;
	
     } else {
        $percent_local           = 0;
        $percent_environemental  = 0;
        $percent_social          = 0; 
        $percent_autre           = 0; 
     }


   
    // Ces variables permettent de compter  combien de fois on cliqué l'un des checkbox   du champs iprojet doit etre retravaille
    $count_responsable    = $wpdb->get_var( "SELECT count(pas_responsable)       FROM $table_name WHERE campaign_id = $campaign_id AND pas_responsable       = 'pas_responsable'" );
    $count_mal_explique   = $wpdb->get_var( "SELECT count(mal_explique)          FROM $table_name WHERE campaign_id = $campaign_id AND mal_explique          <> ''" );
    $mal_explique_list	  = $wpdb->get_results( "SELECT mal_explique		 FROM $table_name WHERE campaign_id = $campaign_id AND mal_explique          <> ''" );
    $count_service        = $wpdb->get_var( "SELECT count(qualite_produit)       FROM $table_name WHERE campaign_id = $campaign_id AND qualite_produit       = 'qualite_produit'" );
    $count_equipe         = $wpdb->get_var( "SELECT count(qualite_equipe)        FROM $table_name WHERE campaign_id = $campaign_id AND qualite_equipe        = 'qualite_equipe'" );
    $count_plan           = $wpdb->get_var( "SELECT count(qualite_business_plan) FROM $table_name WHERE campaign_id = $campaign_id AND qualite_business_plan = 'qualite_business_plan'" );
    $count_innovation     = $wpdb->get_var( "SELECT count(qualite_innovation)    FROM $table_name WHERE campaign_id = $campaign_id AND qualite_innovation    = 'qualite_innovation'" );
    $count_porteur        = $wpdb->get_var( "SELECT count(qualite_marche)        FROM $table_name WHERE campaign_id = $campaign_id AND qualite_marche        = 'qualite_marche'" );

    // Ces variables permettent de compter le pourcentage  des on cliqué l'un des checbox du champs projet doit etre retravaille
    if ($count_retravaille != 0) {
        $percent_responsable   = ($count_responsable / $count_retravaille)* 100;
        $percent_mal_explique  = ($count_mal_explique / $count_retravaille)* 100;
        $percent_service       = ($count_service / $count_retravaille )* 100;
        $percent_equipe        = ($count_equipe / $count_retravaille )* 100;
        $percent_plan          = ($count_plan / $count_retravaille )* 100;
        $percent_innovation    = ($count_innovation / $count_retravaille )* 100;
        $percent_porteur       = ($count_porteur / $count_retravaille )* 100;
    } else {
        $percent_responsable   = 0;
        $percent_mal_explique  = 0;
        $percent_service       = 0;
        $percent_equipe        = 0;
        $percent_plan          = 0;
        $percent_innovation    = 0;
        $percent_porteur       = 0;
    }


    // Ces variables permettent de compter  combien de fois on element est choisi dans la liste "risque lié au projet"
    $count_risque_tres_faible   = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='tres_faible' " );
    $count_risque_plutot_faible = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='plutot_faible' " );
    $count_risque_modere        = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='modere' " );
    $count_risque_tres_eleve    = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='plutot_eleve' " );
    $count_risque_plutot_eleve  = $wpdb->get_var( "SELECT count(liste_risque) FROM $table_name WHERE campaign_id = $campaign_id AND liste_risque='tres_eleve' " );


    // Ces variables permettent de compter le pourcentage  du choix de l'element dans la liste "risque lié au projet"
    if($count_pret_collect != 0) {  
        $percent_risque_tres_faible   = ($count_risque_tres_faible / $count_pret_collect )* 100;
        $percent_risque_plutot_faible = ($count_risque_plutot_faible / $count_pret_collect )* 100;
        $percent_risque_modere        = ($count_risque_modere / $count_pret_collect )* 100;
        $percent_risque_tres_eleve    = ($count_risque_tres_eleve / $count_pret_collect )* 100;
        $percent_risque_plutot_eleve  = ($count_risque_plutot_eleve / $count_pret_collect )* 100;
    } else {
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
        $percent_pasrepondu_collect    = ($count_pasrepondu_collect / $count_users)*100;
    } else {
        $percent_impact_positif = 0;
        $percent_impact_negatif = 0;
        $percent_pret_collect   = 0;
        $percent_retravaille    = 0;
    }
    // calcul de la moyenne   
    if ($count_pret_collect == 0) {
        $moyenne = 0;
    } else {
        $moyenne = $count_sum / $count_pret_collect;
	$moyenne_givers = $count_sum / $count_givers;
    }
    
    // calcule de la mediane
    if ($count_givers == 0) {
	$mediane = 0;
	$mediane_value = 0;
    } else {
	$mediane = round(($count_givers + 1) / 2);
	$mediane_value  = $wpdb->get_var( "SELECT `sum` FROM $table_name WHERE campaign_id = $campaign_id AND sum > 0 ORDER BY `sum` LIMIT ".$mediane.", 1" );
    }
?>

 
<h2>Nombre total de participants : <?php  echo($count_users) ;?></h2> 

<div class="tab-title"><h3>Impact du projet</h3></div>
<p>
    <?php echo ypcf_printable_value($percent_impact_positif); ?>% des participants (<?php echo $count_impact_postif; ?>) pensent que ce projet va avoir un impact positif<br />
    <?php echo ypcf_printable_value($percent_impact_negatif) ; ?>% des participants (<?php echo $count_impact_negatif; ?>) pensent que ce projet n'a pas d&apos;impact significatif<br /><br />
    Les personnes qui croient en l&apos;impact positif de ce projet pensent qu&apos;il va porter sur les dimensions suivantes :<br />
</p>
<table class="tab-results">
    <tr>
	<td>Local</td>
	<td><?php echo $count_local; ?></td>
	<td><?php echo ypcf_printable_value($percent_local); ?>%</td>
    </tr>
    <tr>
	<td>Environnemental</td>
	<td><?php echo $count_environemental; ?></td>
	<td><?php echo ypcf_printable_value($percent_environemental); ?>%</td>
    </tr>
    <tr>
	<td>Social</td>
	<td><?php echo $count_social; ?></td>
	<td><?php echo ypcf_printable_value($percent_social); ?>%</td>
    </tr>
    <tr>
	<td>Autre</td>
	<td><?php echo $count_autre; ?></td>
	<td>
	    <?php echo ypcf_printable_value($percent_autre); ?>%<br />
	    <?php
		if ($count_autre > 0) {
		    echo 'Liste des raisons :<br /><ul>';
		    foreach ($autre_list as $autre_item) {
			echo '<li>' . html_entity_decode($autre_item->autre) . '</li>';
		    }
		    echo '</ul>';
		}
	    ?>
	</td>
    </tr>
</table>

<div class="tab-title"><h3>Maturité du projet</h3></div>
<p>
    <?php echo ypcf_printable_value($percent_pret_collect); ?>% (<?php echo $count_pret_collect; ?>) pensent que ce projet est pr&ecirc;t pour la collecte.<br />
    <?php echo ypcf_printable_value($percent_retravaille); ?>% (<?php echo $count_retravaille; ?>) pensent que ce projet doit &ecirc;tre retravaill&eacute;.<br />
    (<?php echo ypcf_printable_value($percent_pasrepondu_collect); ?>% (<?php echo $count_pasrepondu_collect; ?>) n&apos;ont pas r&eacute;pondu &agrave; cette question.)<br />
</p>
<p>
    En moyenne, les gens seraient pr&ecirc;ts &agrave; donner <?php echo ypcf_printable_value($moyenne); ?>&euro;.
    Si on ne compte que ceux qui investissent, la moyenne monte &agrave; <?php echo ypcf_printable_value($moyenne_givers); ?>&euro;.<br />
    La moiti&eacute; de ces personnes investiraient plus de <?php echo $mediane_value; ?>&euro; (médiane)
</p>

<p>En moyenne, les personnes qui pensent que ce projet est pr&ecirc;t ont &eacute;valu&eacute; le risque &agrave; :</p>
<table class="tab-results">
    <tr>
	<td>Tr&egrave;s faible</td>
	<td><?php echo $count_risque_tres_faible; ?></td>
	<td><?php echo ypcf_printable_value($percent_risque_tres_faible); ?>%</td>
    </tr>
    <tr>
	<td>Plut&ocirc;t faible</td>
	<td><?php echo $count_risque_plutot_faible; ?></td>
	<td><?php echo ypcf_printable_value($percent_risque_plutot_faible); ?>%</td>
    </tr>
    <tr>
	<td>Moder&eacute;</td>
	<td><?php echo $count_risque_modere; ?></td>
	<td><?php echo ypcf_printable_value($percent_risque_modere); ?>%</td>
    </tr>
    <tr>
	<td>&Eacute;lev&eacute;</td>
	<td><?php echo $count_risque_plutot_eleve; ?></td>
	<td><?php echo ypcf_printable_value($percent_risque_plutot_eleve); ?>%</td>
    </tr>
    <tr>
	<td>Tr&egrave;s élevé</td>
	<td><?php echo $count_risque_tres_eleve; ?></td>
	<td><?php echo ypcf_printable_value($percent_risque_tres_eleve); ?>%</td>
    </tr>
 </table>

<p>Les personnes qui pensent que ce projet doit &ecirc;tre retravaill&eacute; ont soulign&eacute; les points suivants :</p>
<table class="tab-results">
    <tr>
	<td>Impact soci&eacute;tal</td>
	<td><?php echo $count_responsable; ?></td>
	<td><?php echo ypcf_printable_value($percent_responsable); ?>%</td>
    </tr>
    <tr>
	<td>Produit/service</td>
	<td><?php echo $count_service; ?></td>
	<td><?php echo ypcf_printable_value($percent_service); ?>%</td>
    </tr>
    <tr>
	<td>Structuration de l&apos;équipe</td>
	<td><?php echo $count_equipe; ?></td>
	<td><?php echo ypcf_printable_value($percent_equipe); ?>%</td>
    </tr>
    <tr>
	<td>Pr&eacute;visionnel financier</td>
	<td><?php echo $count_porteur; ?></td>
	<td><?php echo ypcf_printable_value($percent_porteur); ?>%</td>
    </tr>
    <tr>
	<td>Autre</td>
	<td><?php echo $count_mal_explique; ?></td>
	<td>
	    <?php echo ypcf_printable_value($percent_mal_explique); ?>%<br />
	    <?php 
		if ($count_mal_explique > 0) {
		    echo 'Liste des raisons :<br /><ul>';
		    foreach ($mal_explique_list as $mal_explique_item) {
			echo '<li>' . $mal_explique_item->mal_explique . '</li>';
		    } 
		    echo '</ul>';
		}
	    ?>
	</td>
    </tr>
</table>
  
<div class="tab-title"><h3>Conseils et encouragements</h3></div>
<p>Les personnes qui ont vot&eacute; ont souhait&eacute; vous apporter ces quelques conseils :</p>

<?php $conseils = $wpdb->get_results( "SELECT user_login,user_email,user_id,conseil FROM $table_name WHERE campaign_id = $campaign_id " ); ?>

<table class="tab-results">
    <tr>
	<td>Participants</td>
	<td>Conseils</td>
    </tr>
    <?php
        if (empty($conseils)) {
    ?>
</table>
    <?php
        } else {
	    foreach ( $conseils as $cons ) {
    ?>
    <tr>
	<td><a href="<?php echo bp_core_get_userlink($cons->user_id, false, true); ?>"><?php echo $cons->user_login; ?></a></td>
	<td><?php echo $cons->conseil; ?></td>
    </tr>
    <?php
	    }
	    ?>
</table>
    <?php
	}
    $post = $save_post;
    return ob_get_clean();
}


}
add_shortcode( 'yproject_crowdfunding_vote_results', 'ypcf_shortcode_vote_results' );


