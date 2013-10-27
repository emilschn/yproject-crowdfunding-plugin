<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



//****************************************************************************//
//**********************Fonctions d'insertion pour le formualaire de votes*****************************************************
//****************************************************************//

// formulaire de vote
function ypcf_shortcode_printPageVoteForm($atts, $content = '') {
  
    global $wpdb, $post;
    $table_name = $wpdb->prefix . "ypVotes"; 
    $isvoted = false; 
    $sum_valid = false;

    $crowdfunding = crowdfunding();

    if (isset($_GET['campaign_id'])) $post = get_post($_GET['campaign_id']);
    $campaign = atcf_get_campaign( $post ); 
        
    if (isset($_POST['submit']))
        { 
            if ( is_user_logged_in() )
            {
                $impact                 = isset($_POST[ 'impact' ]) ? $_POST[ 'impact' ] : "";
                $local                  = isset($_POST[ 'local' ]) ? $_POST[ 'local' ] : false;
                $environmental          = isset($_POST[ 'environmental' ]) ? $_POST[ 'environmental' ] : false;
                $social                 = isset($_POST[ 'social' ]) ? $_POST[ 'social' ] : false;
                $autre                  = isset($_POST[ 'autre' ]) ? htmlentities($_POST[ 'autre' ]) : '';

                $sum                    = isset($_POST[ 'sum' ]) ? $_POST[ 'sum' ] : 0;    

                $liste_risque           = isset($_POST[ 'liste_risque' ]) ? $_POST[ 'liste_risque' ] : '';  
                   
                $maturite               = isset($_POST[ 'maturite' ]) ? $_POST[ 'maturite' ] : false; 
                $pas_responsable        = isset($_POST[ 'pas_responsable' ]) ? $_POST[ 'pas_responsable' ] : false; 
                $mal_explique           = isset($_POST[ 'mal_explique' ]) ? $_POST[ 'mal_explique' ] : false; 
                $qualite_produit        = isset($_POST[ 'qualite_produit' ]) ? $_POST[ 'qualite_produit' ] : false; 
                $qualite_equipe         = isset($_POST[ 'qualite_equipe' ]) ? $_POST[ 'qualite_equipe' ] : false; 
                $qualite_business_plan  = isset($_POST[ 'qualite_business_plan' ]) ? $_POST[ 'qualite_business_plan' ] : false; 
                $qualite_innovation     = isset($_POST[ 'qualite_innovation' ]) ? $_POST[ 'qualite_innovation' ] : false; 
                $qualite_marche         = isset($_POST[ 'qualite_marche' ]) ? $_POST[ 'qualite_marche' ] : false; 
                $conseil                = isset($_POST[ 'conseil' ]) ? htmlentities($_POST[ 'conseil' ]) : '';
     
                $user_last_name         = wp_get_current_user()->user_lastname;
                $user_first_name        = wp_get_current_user()->user_firstname;
                $user_email             = wp_get_current_user()->user_email;
                $user_login             = wp_get_current_user()->user_login;
                $user_id                = wp_get_current_user()->ID;

                $post                   = get_post(get_the_ID());
                $campaign               = atcf_get_campaign( $post );
                $campaign_id            =  $campaign->ID;

                if  (!is_numeric($_POST[ 'sum' ])) {
                    echo '<label style="color:red">*Somme invalide dans le champs</label></br> "Je serais pr&ecirct &agrave investir"</br>';
                } else {
                   $sum = $_POST[ 'sum' ];
                   $sum_valid = true;
		}
              /**  elseif( $impact || $local || $environmental || $social || $autre || $desaprouve ||
                        $pret_pour_collect || $sum || $liste_risque|| $maturite || $pas_responsable ||
                        $mal_explique  || $qualite_produit ||   $qualite_equipe || $qualite_business_plan ||
                        $qualite_innovation  ||  $qualite_marche  ||  $conseil  )
                {
                    echo '<label style="color:red">*Vous devez remplir les champs avant de valider</label></br> "Je serais pr&ecirct &agrave investir"</br>';
                }*/

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
         
    $atts = shortcode_atts( array(
    'remaining_days' => 0
    ), $atts );

?>
    </div>
    <div class="left post_bottom_infos">
	Il reste <?php echo $atts['remaining_days']; ?> jours pour voter sur ce projet.<br />
	
	<?php
            $users = $wpdb->get_results( 'SELECT user_id FROM '.$table_name.' WHERE campaign_id = '.$campaign->ID );
	    $has_voted = false;
            foreach ( $users as $user ){
                if ( $user->user_id == wp_get_current_user()->ID) $has_voted = true;
	    }
	    if ($has_voted):
	?>
	    Merci pour votre vote.
	<?php
	    else:
	?>
        <div class="post_bottom_buttons">
            <div class="dark" style="color:white;text-transform:none;padding-left:5px;">
                <legend>Votez sur ce projet</legend>
            </div>
            <div class="light" style="text-transform:none;text-align : left; padding-left:5px;" >
		<form name="ypvote" action="<?php get_permalink();?>" method="POST" class="ypvote-form" enctype="multipart/form-data">
                       
			<strong>Impacts et coh&eacute;rence du projet</strong><br />
                        <input id="impact-positif" type="radio" name="impact" value="positif">Je pense que ce projet va avoir un impact positif<br />
                        <p id="impact-positif-content" style="display: none;">
                            <input type="checkbox" id="local" name="local"  value="local">Local<br />
                            <input type="checkbox" id="environmental" name="environmental" value="environmental">Environnemental<br />
                            <input type="checkbox" id="social" name="social" value="social">Social<br />
                            <input type="checkbox" id="autre" name="autre" value="autre">Autre
                            <input id="precision"  id="precision" name="precision" type="text" placeholder="Pr&eacute;ciser..." />
                        </p>
                        <input id="desaprouve" type="radio" name="impact" value="negatif" >Je d&eacute;sapprouve ce projet car son impact pr&eacute;vu n'est pas significatif<br /><br />

                        
			<strong>Maturit&eacute; et collecte</strong><br />
                        <input id="pret" type="radio" name="maturite" value="pret">Je pense que ce projet est pr&ecirc;t pour la collecte<br />
                        <p id="pret-content" style="display: none;">
                            <label id="investir" name="investir" value="investir">Je serais pr&ecirc;t &agrave; investir</label><br />
                            <input id="sum" name="sum" type="text" placeholder="100" />&euro;<br />
                         
                            <label class="risque" name="risque" value="risque">Je pense que ce projet pr&eacute;sente un risque [<a href="javascript:void(0);" title="Evaluez les chances de r&eacute;ussite de ce projet en indiquant le risque que vous estimez. 1 repr&eacute;sente un risque faible (donc de grande chances de r&eacute;ussite), 5 un risque &eacute;lev&eacute; (de faibles chances de r&eacute;ussite). Le niveau de risque du projet a une influence sur sa valeur.">?</a>] :</label><br />
                            <select id="liste_risque" name="liste_risque"  placeholder="choisir le type de risque">
                                <option></option>
                                <option id="risque_tres_faible">tr&egrave;s faible</option>
                                <option id="risque_plutot_faible">plut&ocirc;t faible</option>
                                <option id="risque_modere">mod&eacute;r&eacute;</option>
                                <option id="risque_plutot_eleve">plut&ocirc;t &eacute;lev&eacute;</option>
                                <option id="risque_tres_eleve">tr&egrave;s &eacute;lev&eacute;</option>
                            </select>
                        </p>

                        <input id="retravaille" type="radio" name="maturite" value="retravaille">Je pense que ce projet doit &ecirc;tre retravaill&eacute; sur ces points :<br />
                        <p id="retravaille-content" style="display: none;">
                            <input type="checkbox" id="" name="pas_responsable" value="pas_responsable">Pas d&apos;impact responsable<br />
                            <input type="checkbox" id="" name="mal_explique" value="mal_explique">Projet mal expliqu&eacute;<br />
                            <input type="checkbox" id="" name="qualite_produit" value="qualite_produit">Qualit&eacute; du produit/service<br />
                            <input type="checkbox" id="" name="qualite_equipe" value="qualite_equipe">Qualit&eacute; de l&apos;&eacute;quipe<br />
                            <input type="checkbox" id="" name="qualite_business_plan" value="qualite_business_plan">Qualit&eacute; du business plan<br />
                            <input type="checkbox" id="" name="qualite_innovation" value="qualite_innovation">Qualit&eacute; d&apos;innovation<br />
                            <input type="checkbox" id="" name="qualite_marche" value="qualite_marche">Qualit&eacute; du march&eacute;, porteur<br />
                
                            <label for="other">Autre</label><input type="text" name="other" id="other">
                        </p><br />
			
			<strong>Remarques</strong><br />
                        <span>Quels conseils ou encouragements souhaiteriez-vous donner au(x) porteur(s) de ce projet ?</span><br />
                        <textarea type="text" name="conseil" id="conseil" value="conseil" style="width: 280px;"></textarea><br />
			
			<br />
                        <input type="submit" name="submit" value="Voter" />

		</form>
	    </div>
        </div>
	<?php endif; ?>
    </div>
    <div style="clear: both;"></div>
        
<?php
}

    add_shortcode( 'yproject_crowdfunding_printPageVoteForm', 'ypcf_shortcode_printPageVoteForm' );



function ypcf_shortcode_printPageVoteDeadLine($post, $campaign) {
    ?>
    <span id="tab-end-vote">
         Les votes sont clôturés pour ce projet, merci
    </span>
 
<?php
}
add_shortcode('yproject_crowdfunding_printPageVoteDeadLine','ypcf_shortcode_printPageVoteDeadLine');

function ypcf_shortcode_compte_rebours(){

    $debut = strtotime($campaign->start_vote);
    $fin   = strtotime($post->campaign_end_vote);

        $diff = $fin - $debut;

        echo $debut.'debut'.'</br>';
        echo $fin.'fin'.'</br>';

        echo $diff.'differ'.'</br>';

        $days = (round(abs($fin - $debut)/60/60/24/10));

        echo $days.'compte a rebours' ;
}

add_shortcode('yproject_crowdfunding_compte_rebours','ypcf_shortcode_compte_rebours');