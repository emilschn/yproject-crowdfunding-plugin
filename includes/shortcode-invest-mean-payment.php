<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


function ypcf_shortcode_invest_mean_payment($atts, $content = '') {
    ob_start();
    //Lien
    $page_mean_payment = get_page_by_path('moyen-de-paiement');
    $page_mean_payment_link = get_permalink($page_mean_payment->ID) . '?campaign_id=' . $_GET['campaign_id'] . '&meanofpayment=';
    //Possible de régler par virement ?
    $min_wire = 200;
    $can_use_wire = (ypcf_get_part_value() * $_SESSION['redirect_current_amount_part'] >= $min_wire);
    //Possible de régler par chèque ?
    $min_check = 500;
    $can_use_check = (ypcf_get_part_value() * $_SESSION['redirect_current_amount_part'] >= $min_check);
    
    echo ypcf_print_invest_breadcrumb(3);
    ?>
    Merci de choisir votre moyen de paiement :<br />
    <ul class="invest-mean-payment">
	    <li>
		    <a href="<?php echo $page_mean_payment_link; ?>card">
			    <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/paiement-carte.jpg" alt="Paiement par carte" />
			    Carte bancaire
		    </a>
	    </li>
	    <?php if ($can_use_wire) { ?>
	    <li>
		    <a href="<?php echo $page_mean_payment_link; ?>wire">
			    <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/paiement-virement.jpg" alt="Paiement par virement" />
			    Virement bancaire
		    </a>
	    </li>
	    <?php } ?>
	    <?php /*<li><?php if ($can_use_check) { ?><a href="<?php echo get_permalink($page_mean_payment->ID); ?>?meanofpayment=check"><?php } ?><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/paiement-cheque.jpg" alt="Paiement par cheque" /><br /> Ch&egrave;que<?php if ($can_use_check) { ?></a><?php } ?> (&agrave; partir de <?php echo $min_check; ?>&euro;)</li>*/ ?>
	    <div class="clear"></div>
    </ul>
    <center><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/powered_by_mangopay.png" alt="Bandeau Mangopay" /></center>
    <?php
    return ob_get_clean();
}
add_shortcode( 'yproject_crowdfunding_invest_mean_payment', 'ypcf_shortcode_invest_mean_payment' );

?>