<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



//****************************************************************************//
//**********************Fonctions d'insertion pour le formualaire de votes*****************************************************
//****************************************************************//

// formulaire de vote
function ypcf_shortcode_printPageVoteForm($atts, $content = '') {
    global $wpdb, $post;
    if (isset($_GET['campaign_id'])) $post = get_post($_GET['campaign_id']);
    
    $table_name = $wpdb->prefix . "ypVotes"; 

    $campaign = atcf_get_campaign( $post ); 
    
    $impact = false;
    $local = false;
    $environmental = false;
    $social = false;
    $autre = false;
    $maturite = false;
    $sum = false;
    $liste_risque = false;
    $pas_responsable = false;
    $qualite_produit = false;
    $qualite_equipe = false; 
    $qualite_marche = false;
    $retravaille_autre = false;
    $conseil = false;

        
    if (isset($_POST['submit'])) { 
	if ( is_user_logged_in() ) {
	    $vote_valid = true;
	    
	    $impact             = isset($_POST[ 'impact' ]) ? $_POST[ 'impact' ] : "";
	    $local              = ($impact == "positif" && isset($_POST[ 'local' ])) ? $_POST[ 'local' ] : false;
	    $environmental      = ($impact == "positif" && isset($_POST[ 'environmental' ])) ? $_POST[ 'environmental' ] : false;
	    $social             = ($impact == "positif" && isset($_POST[ 'social' ])) ? $_POST[ 'social' ] : false;
	    $autre              = ($impact == "positif" && isset($_POST[ 'autre' ]) && $_POST[ 'autre' ] && isset($_POST['precision'])) ? htmlentities($_POST[ 'precision' ], ENT_QUOTES | ENT_HTML401) : '';
	    if ($impact == "") {
		echo '<label style="color:red">Vous n&apos;avez pas r&eacute;pondu &agrave; la premi&egrave;re question.</label><br />';
		$vote_valid = false;
	    }
	    if ($impact == "positif" && !$local && !$environmental && !$social && (!isset($_POST[ 'autre' ]))) {
		echo '<label style="color:red">Vous n&apos;avez pas pr&eacute;cis&eacute; l&apos;impact.</label><br />';
		$vote_valid = false;
	    }
	    
	    $maturite		= isset($_POST[ 'maturite' ]) ? $_POST[ 'maturite' ] : false;
	    if ($maturite == "") {
		echo '<label style="color:red">Vous n&apos;avez pas r&eacute;pondu &agrave; la deuxi&egrave;me question.</label><br />';
		$vote_valid = false;
	    }
	    if  ($maturite == "pret" && !is_numeric($_POST[ 'sum' ])) {
		echo '<label style="color:red">Somme invalide</label><br />';
		$vote_valid = false;
	    } else {
		$sum = ($maturite == "pret" && isset($_POST[ 'sum' ])) ? $_POST[ 'sum' ] : 0;
	    }
	    
	    $liste_risque           = ($maturite == "pret" && isset($_POST[ 'liste_risque' ])) ? $_POST[ 'liste_risque' ] : '';
	    $pas_responsable        = ($maturite == "retravaille" && isset($_POST[ 'pas_responsable' ])) ? $_POST[ 'pas_responsable' ] : false; 
	    $qualite_produit        = ($maturite == "retravaille" && isset($_POST[ 'qualite_produit' ])) ? $_POST[ 'qualite_produit' ] : false; 
	    $qualite_equipe         = ($maturite == "retravaille" && isset($_POST[ 'qualite_equipe' ])) ? $_POST[ 'qualite_equipe' ] : false; 
	    $qualite_marche         = ($maturite == "retravaille" && isset($_POST[ 'qualite_marche' ])) ? $_POST[ 'qualite_marche' ] : false;
	    $retravaille_autre      = ($maturite == "retravaille" && isset($_POST[ 'retravaille_autre' ]) && $_POST[ 'retravaille_autre' ] && isset($_POST[ 'retravaille_autre_precision' ])) ? htmlentities($_POST[ 'retravaille_autre_precision' ], ENT_QUOTES | ENT_HTML401) : '';

	    if ($maturite == "pret" && $liste_risque == "") {
		echo '<label style="color:red">Vous n&apos;avez pas pr&eacute;cis&eacute; le risque que vous estimez.</label><br />';
		$vote_valid = false;
	    }
	    
	    $conseil                = (isset($_POST[ 'conseil' ])) ? htmlentities($_POST[ 'conseil' ]) : '';

	    $user_last_name         = wp_get_current_user()->user_lastname;
	    $user_first_name        = wp_get_current_user()->user_firstname;
	    $user_email             = wp_get_current_user()->user_email;
	    $user_login             = wp_get_current_user()->user_login;
	    $user_id                = wp_get_current_user()->ID;
	    $user_display_name      = wp_get_current_user()->display_name;
	    
	    $campaign_id            = $campaign->ID;



	    // Vérifie si l'utilisateur a deja voté
	    $users = $wpdb->get_results( "SELECT user_id FROM $table_name WHERE campaign_id = $campaign_id " );

	    $isvoted = false; 

	    foreach ( $users as $user ){
		if ( $user->user_id == $user_id){
		    echo '<label style="color:red">* D&eacutesol&eacute vous avez d&egraveja vot&eacute, merci !</label><br />';
		    $isvoted = true;
		    break;
		} 
	    }

	    if ($isvoted == false && $vote_valid) {
		$wpdb->insert( $table_name, 
		    array ( 
			'impact'                  => $impact, 
			'local'                   => $local,
			'environmental'           => $environmental,
			'social'                  => $social,
			'autre'                   => $autre,
			'sum'                     => $sum,
			'liste_risque'            => $liste_risque,
			'retravaille'             => $maturite,
			'pas_responsable'         => $pas_responsable,
			'mal_explique'            => $retravaille_autre,
			'qualite_produit'         => $qualite_produit,
			'qualite_equipe'          => $qualite_equipe,
			'qualite_business_plan'   => '',
			'qualite_innovation'      => '',
			'qualite_marche'          => $qualite_marche,
			'conseil'                 => $conseil,
			'isvoted'                 => $isvoted,
			'user_id'                 => $user_id,
			'user_first_name'         => $user_first_name,
			'user_last_name'          => $user_last_name,
			'user_login'              => $user_login,
			'user_email'              => $user_email,
			'campaign_id'             => $campaign_id
		    )
		); 


		/* Construction des urls utilisés dans les liens du fil d'actualité*/
		// url d'une campagne précisée par son nom 
		$campaign_url  = get_permalink($post->ID);
		$post_title = $post->post_title;
		$url_campaign = '<a href="'.$campaign_url.'">'.$post_title.'</a>';
		//url d'un utilisateur précis
		$url_profile = '<a href="' . bp_core_get_userlink($user_id, false, true) . '">' . $user_display_name . '</a>';
		
		bp_activity_add(array (
		    'component' => 'profile',
		    'type'      => 'voted',
		    'action'    => $url_profile.' a voté sur le projet '.$url_campaign
		));


		echo '<label style="color:green">Le vote est valid&eacute, merci !</label><br />';
	    }
	} else {
	     echo '<label style="color:red"> * Vous devez vous connecter pour voter </label><br />';
	}
    }
         
    $atts = shortcode_atts( array(
    'remaining_days' => 0
    ), $atts );

?>
    Il reste <?php echo $atts['remaining_days']; ?> jours pour voter sur ce projet.<br />
    

    <?php
    if (is_user_logged_in()) :
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
            
            <div class="dark" style="color:white;text-transform:none;padding-left:5px;">
                <legend>Votez sur ce projet</legend>
                
            </div>
            <div class="light" style="text-transform:none;text-align : left; padding-left:5px;" >
                <form name="ypvote" action="<?php get_permalink();?>" method="POST" class="ypvote-form" enctype="multipart/form-data">
                       
                        <strong>Impacts et coh&eacute;rence du projet</strong><br />
                        <em>Cette question d&eacute;termine la publication du projet sur le site.</em><br />
                        <input type="radio" id="impact-positif" name="impact" value="positif" <?php if ($impact == "positif") echo 'checked="checked"'; ?>>Impact positif, j&apos;approuve ce projet !<br />
                        <p id="impact-positif-content" <?php if ($impact != "positif") echo 'style="display: none;"'; ?>>
                            <em>Selon moi, l&apos;impact du projet sera significatif sur les points suivants :</em><br />
                            <input type="checkbox" id="local" name="local" value="local" <?php if ($local) echo 'checked="checked"'; ?>>Local<br />
                            <input type="checkbox" id="environmental" name="environmental" value="environmental" <?php if ($environmental) echo 'checked="checked"'; ?>>Environnemental<br />
                            <input type="checkbox" id="social" name="social" value="social" <?php if ($social) echo 'checked="checked"'; ?>>Social<br />
                            <input type="checkbox" id="autre" name="autre" value="autre" <?php if ($autre != "" && $autre != false) echo 'checked="checked"'; ?>>Autre
                            <input type="text" id="precision" name="precision" placeholder="Pr&eacute;ciser..." <?php if ($autre != "" && $autre != false) echo 'value="'.$_POST['precision'].'"'; ?>/>
                        </p>
                        <input type="radio" id="desaprouve" name="impact" value="negatif" <?php if ($impact == "negatif") echo 'checked="checked"'; ?>>Pas d&apos;impact, je d&eacute;sapprouve.<br />
                        <p id="impact-negatif-content" <?php if ($impact != "negatif") echo 'style="display: none;"'; ?>>
                            <em>En d&eacute;sapprouvant ce projet, je vote contre sa publication sur le site.</em>
                        </p><br /><br />

                        
                        <strong>Maturit&eacute; et collecte</strong><br />
                        <em>Cette question permet au porteur de projet de voir les am&eacute;liorations qu'il peut apporter avant de se lancer.</em><br />
                        <input id="pret" type="radio" name="maturite" value="pret" <?php if ($maturite == "pret") echo 'checked="checked"'; ?>>Je pense que ce projet est pr&ecirc;t pour la collecte !<br />
                        <p id="pret-content" <?php if ($maturite != "pret") echo 'style="display: none;"'; ?>>
                            <label id="investir" name="investir" value="investir">Je serais pr&ecirc;t &agrave; investir</label>
                            <input type="text" id="sum" name="sum" placeholder="10" size="10" <?php if ($sum !== false) echo 'value="'.$sum.'"'; ?> />&euro;<br />
                         
                            <label class="risque" name="risque" value="risque">Je pense que ce projet pr&eacute;sente un risque [<a href="javascript:void(0);" title="Evaluez les chances de r&eacute;ussite de ce projet en indiquant le risque que vous estimez. 1 repr&eacute;sente un risque faible (donc de grande chances de r&eacute;ussite), 5 un risque &eacute;lev&eacute; (de faibles chances de r&eacute;ussite). Le niveau de risque du projet a une influence sur sa valeur.">?</a>] :</label><br />
                            <select id="liste_risque" name="liste_risque">
                                <option value=""></option>
                                <option value="tres_faible" <?php if ($liste_risque == "tres_faible") echo 'selected'; ?>>(1) tr&egrave;s faible</option>
                                <option value="plutot_faible" <?php if ($liste_risque == "plutot_faible") echo 'selected'; ?>>(2) plut&ocirc;t faible</option>
                                <option value="modere" <?php if ($liste_risque == "modere") echo 'selected'; ?>>(3) mod&eacute;r&eacute;</option>
                                <option value="plutot_eleve" <?php if ($liste_risque == "plutot_eleve") echo 'selected'; ?>>(4) &eacute;lev&eacute;</option>
                                <option value="tres_eleve" <?php if ($liste_risque == "tres_eleve") echo 'selected'; ?>>(5) tr&egrave;s &eacute;lev&eacute;</option>
                            </select>
                        </p>

                        <input id="retravaille" type="radio" name="maturite" value="retravaille" <?php if ($maturite == "retravaille") echo 'checked="checked"'; ?>>Je pense que ce projet n&apos;est pas pr&ecirc;t.<br />
                        <p id="retravaille-content" <?php if ($maturite != "retravaille") echo 'style="display: none;"'; ?>>
                            <em>Selon moi, il doit &ecirc;tre retravaill&eacute; sur les points suivants :</em><br />
                            <input type="checkbox" name="pas_responsable" value="pas_responsable" <?php if ($pas_responsable) echo 'checked="checked"'; ?>>Impact soci&eacute;tal<br />
                            <input type="checkbox" name="qualite_produit" value="qualite_produit" <?php if ($qualite_produit) echo 'checked="checked"'; ?>>Produit/service<br />
                            <input type="checkbox" name="qualite_equipe" value="qualite_equipe" <?php if ($qualite_equipe) echo 'checked="checked"'; ?>>Structuration de l&apos;&eacute;quipe<br />
                            <input type="checkbox" name="qualite_marche" value="qualite_marche" <?php if ($qualite_marche) echo 'checked="checked"'; ?>>Pr&eacute;visionnel financier<br />
                
                            <input type="checkbox" id="retravaille_autre" name="retravaille_autre" value="autre" <?php if ($retravaille_autre != "" && $retravaille_autre != false) echo 'checked="checked"'; ?>>Autre
                            <input  type="text" id="retravaille_autre_precision" name="retravaille_autre_precision" placeholder="Pr&eacute;ciser..." <?php if ($retravaille_autre != "" && $retravaille_autre != false) echo 'value="'.$retravaille_autre.'"'; ?>/>
                        </p><br />
                        
                        <strong>Remarques</strong><br />
                        <span>Quels conseils ou encouragements souhaiteriez-vous donner au(x) porteur(s) de ce projet ?</span><br />
                        <textarea type="text" name="conseil" id="conseil" value="conseil" style="width: 280px;"><?php if ($conseil != "" && $conseil != false) echo $conseil; ?></textarea><br />
                        
                        <br />
                        <input type="submit" name="submit" value="Voter" />

                </form>
            </div>
    <?php endif;
    else :
        ?>
        <label class="errors">* Vous devez vous connecter pour voter.</label>
        <?php
    endif;
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