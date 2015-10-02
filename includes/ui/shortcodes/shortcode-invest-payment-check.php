<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


function ypcf_shortcode_invest_payment_check($atts, $content = '') {
    $campaign = atcf_get_current_campaign();
    if (empty($campaign->ID)) { return; }
    
    ob_start();

    $filename = dirname ( __FILE__ ) . '/../../pdf_files/contract-'.$campaign->ID.'.docx';
    $url = home_url() . '/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/contract-'.$campaign->ID.'.docx';
    
    echo ypcf_print_invest_breadcrumb(3, $campaign->funding_type());
?>
	
    <?php if (file_exists($filename)) { ?>

	<?php _e('Pour investir par ch&egrave;que, merci de suivre les &eacute;tapes suivantes :', 'yproject'); ?><br /><br />
	
	1. <?php _e('Imprimez le contrat accessible en cliquant sur le lien suivant :', 'yproject'); ?>
	<a href="<?php echo $url; ?>"><?php _e('Contrat', 'yproject'); ?></a><br /><br />

	2. <?php _e('Pr&eacute;parez un courrier contenant :', 'yproject'); ?>
	<ul>
	    <li><?php _e('le contrat imprim&eacute; et rempli', 'yproject'); ?></li>
	    <li><?php _e('un ch&egrave;que &agrave; l&apos;ordre de ', 'yproject'); ?> <strong><?php echo $campaign->company_name(); ?></strong></li>
	</ul>

	3. <?php _e('Envoyez-le &agrave; l&apos;adresse suivante :', 'yproject'); ?><br />
	WE DO GOOD<br />
	8 route de la Joneli&egrave;re<br />
	44300 NANTES<br /><br />
	
	<?php _e('Le montant de votre ch&egrave;que sera pris en compte d&egrave;s r&eacute;ception.', 'yproject'); ?>
	<?php _e('Le montant total atteint sera alors de', 'yproject'); ?> <?php echo ($campaign->current_amount(false) + $_SESSION['redirect_current_amount_part'] * $campaign->part_value()); ?> &euro;.<br /><br />

	<?php _e('Votre ch&egrave;que ne sera transmis au Porteur de Projet qu&apos;en cas de r&eacute;ussite de la collecte.', 'yproject'); ?>
	<?php _e('Dans le cas contraire, il vous sera retourn&eacute;.', 'yproject'); ?><br /><br />

	<?php _e('Pour toute question, contactez-nous &agrave; l&apos;adresse', 'yproject'); ?> investir@wedogood.co<br /><br />


    <?php } else { ?>

	<?php _e('Afin d&apos;investir par ch&egrave;que, contactez-nous &agrave; l&apos;adresse', 'yproject'); ?> investir@wedogood.co<br /><br />

    <?php } ?>
    
    <?php
    if (isset($_SESSION['redirect_current_campaign_id'])) unset($_SESSION['redirect_current_campaign_id']);
    if (isset($_SESSION['redirect_current_amount_part'])) unset($_SESSION['redirect_current_amount_part']);
    ?>

<?php
    return ob_get_clean();
}
add_shortcode( 'yproject_crowdfunding_invest_payment_check', 'ypcf_shortcode_invest_payment_check' );

?>