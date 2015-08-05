<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


function ypcf_shortcode_invest_payment_wire($atts, $content = '') {
	ob_start();

	if (isset($_GET['meanofpayment']) && $_GET['meanofpayment'] == 'wire' && isset($_REQUEST["ContributionID"])) {

		$mangopay_contribution = ypcf_mangopay_get_withdrawalcontribution_by_id($_REQUEST["ContributionID"]);
		
		$page_payment_done = get_page_by_path('paiement-effectue');

		$post = get_post($_GET['campaign_id']);
                $campaign = atcf_get_campaign( $post );
		echo ypcf_print_invest_breadcrumb(3, $campaign->funding_type());
?>

	Afin de proc&eacute;der au virement, voici les informations bancaires dont vous aurez besoin :<br />
	<ul>
	    <li><strong>Titulaire du compte :</strong> <?php echo $mangopay_contribution->BankAccountOwner; ?></li>
	    <li><strong>IBAN :</strong> <?php echo $mangopay_contribution->BankAccountIBAN; ?></li>
	    <li><strong>BIC :</strong> <?php echo $mangopay_contribution->BankAccountBIC; ?></li>
	    <li>
		    <strong>Code unique (pour identifier votre paiement) :</strong> <?php echo $mangopay_contribution->GeneratedReference; ?><br />
		    <ul>
			    <li>Indiquez imp&eacute;rativement ce code comme "libell&eacute; b&eacute;n&eacute;ficiaire" ou "code destinataire" au moment du virement !</li>
		    </ul>
	    </li>
	</ul>
	<br /><br />
	
	Une fois le virement effectu&eacute;, cliquez sur<br />
	<a href="<?php echo get_permalink($page_payment_done->ID) . '?ContributionID=' . $_REQUEST["ContributionID"] . '&campaign_id=' . $_GET['campaign_id'] . '&meanofpayment=wire'; ?>" class="button">SUIVANT</a><br /><br />

	<div class="align-center mangopay-image"><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/powered_by_mangopay.png" alt="Bandeau Mangopay" /></div>

<?php
	} else {
?>
		Error YPSIPW001 : Probl&egrave;me de page.
<?php
	}

	return ob_get_clean();
}
add_shortcode( 'yproject_crowdfunding_invest_payment_wire', 'ypcf_shortcode_invest_payment_wire' );

?>