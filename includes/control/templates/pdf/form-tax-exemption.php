<?php
/**
 * Template PDF : attestation de versement de royalties
 */
class WDG_Template_PDF_Form_Tax_Exemption {
	public static function get(
		$user_name,
		$user_address,
		$form_ip_address,
		$form_date
			
	) {
		ob_start();
?>

<div style="font-family: Arial; font-size: 10pt;" width="600">

	<div style="margin-top: 20px;">
		<p style="text-align: center;">
			<span style="font-weight: bold; font-size: 18pt;">ATTESTATION SUR L'HONNEUR</span>
			<br><br>
			Demande de dispense de prélèvement prévu au I de l'article 125 A du Code Général des Impôts<br>
			relative à mes investissements sur la plateforme WE DO GOOD
		</p>
		
		<p style="text-align: justify">
			Je soussigné, <?php echo $user_name; ?>,<br>
			demeurant <?php echo $user_address; ?><br><br>
			demande à être dispensé du prélèvement prévu au I de l'article 125 A du CGI
			et atteste sur l'honneur que le revenu fiscal de référence de mon foyer fiscal figurant sur mon avis d'imposition 
			établi au titre des revenus de l'avant-dernière année précédant le paiement des produits de placements à revenu fixe 
			et gains assimilés mentionnés au I de l'article précité est inférieur à :<br>
			- 25 000 € (pour les contribuables célibataires, divorcés ou veufs) ;<br>
			- 50 000 € (pour les contribuables soumis à imposition commune).
		</p>
		
		<p>
			A l'adresse IP <?php echo $form_ip_address; ?>, le <?php echo $form_date; ?>,<br>
			<?php echo $user_name; ?>
		</p>
	</div>

</div>


<?php
		$buffer = ob_get_clean();
		ob_end_flush();
		return $buffer;
	}
}