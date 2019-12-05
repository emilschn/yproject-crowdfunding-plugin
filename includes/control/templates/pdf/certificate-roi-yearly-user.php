<?php
/**
 * Template PDF : attestation de versement de royalties
 */
class WDG_Template_PDF_Certificate_ROI_Yearly_User {
	public static function get(
		$user_organization_name,
		$user_organization_id,
		$user_organisation_vat,
		$user_name,
		$user_email,
		$user_address,
		$user_postal_code,
		$user_city,
		$certificate_date,
		$certificate_year,
		$investment_list,
		$roi_total,
		$tax_total,
		$info_yearly_certificate
			
	) {
		ob_start();
?>

<div style="font-family: Arial; font-size: 10pt;" width="600">
	
	<div style="margin-top: 50px;">
		<img src="<?php echo __DIR__; ?>/../img/wdg-logo-red.png" />
	</div>

	<div style="margin-top: 20px;">
		<table>
			<tr>
				<td width="400">
					WEDOGOOD.co<br>
					Changeons le monde par la finance !<br>
					<br>
					www.wedogood.co<br>
					bonjour@wedogood.co
				</td>
				<td>
					<?php if (!empty($user_organization_name)): ?>
						<?php echo $user_organization_name; ?><br>
						Numéro SIREN : <?php echo $user_organization_id; ?><br>
						<?php if (!empty($user_organisation_vat)): ?>
						Numéro TVA : <?php echo $user_organisation_vat; ?><br>
						<?php endif; ?>
					<?php else: ?>
						<?php echo $user_name; ?><br>
					<?php endif; ?>
					<?php echo $user_email; ?><br>
					<?php echo $user_address; ?><br>
					<?php echo $user_postal_code; ?> <?php echo $user_city; ?>
				</td>
			</tr>
		</table>
	</div>

	<div style="margin-top: 40px;">
		<p>
			<span style="font-weight: bold; font-size: 18pt;">DOCUMENT R&Eacute;CAPITULATIF DES TRANSACTIONS PER&Ccedil;UES EN <?php echo $certificate_year; ?></span><br>
			<?php echo $certificate_date; ?><br><br>
			<?php _e( "Montant total per&ccedil;u en ", 'yproject' ); ?><?php echo $certificate_year; ?> : <?php echo $roi_total; ?><br>
			<?php _e( "dont montant total imposable pour ", 'yproject' ); ?><?php echo $certificate_year; ?> : <?php echo $tax_total; ?><br>
			<span style="font-size: 8pt;">Les informations fiscales de cette attestation ne concernent que les foyers fiscaux français. Sinon, veuillez vous référer aux lois en vigueur dans votre résidence fiscale.</span>
		</p>
	</div>

	
	<?php // Liste d'investissements avec les royalties correspondantes perçues ?>
	<div style="margin-top: 40px;">
		<p>
			<span style="font-weight: bold; font-size: 16pt;">REDEVANCES PER&Ccedil;UES</span>
		</p>
	</div>
	
	<?php if ( !empty( $investment_list ) ): ?>
		<?php foreach ( $investment_list as $investment ): ?>
			<div style="margin-top: 25px;">

				<b>Projet <?php echo $investment[ 'project_name' ]; ?> de la société <?php echo $investment[ 'organization_name' ]; ?></b><br>
				<?php echo $investment['organization_address']; ?><br>
				Numéro SIRET : <?php echo $investment['organization_id']; ?><br>
				<?php if ( !empty( $investment['organization_vat'] ) ): ?>
					Numéro TVA : <?php echo $investment['organization_vat']; ?><br>
				<?php endif; ?>
				Investissement réalisé le : <b><?php echo $investment['date']; ?></b><br>
				Montant investi : <b><?php echo $investment['amount']; ?></b><br>
				Total per&ccedil;u : <b><?php echo $investment['roi_total']; ?></b><br>
				dont total per&ccedil;u en <?php echo $certificate_year; ?> : <b><?php echo $investment['roi_for_year']; ?></b><br>
				dont total imposable en <?php echo $certificate_year; ?> : <b><?php echo $investment['tax_for_year']; ?></b><br>
				<br>
				<b>Détail des redevances perçues</b><br>
				<ul>
					<?php foreach ( $investment['roi_list'] as $roi ): ?>
						<li><?php echo $roi['date'] ?> (<?php echo $roi['trimester_months'] ?>) : <b><?php echo $roi['amount'] ?></b></li>
					<?php endforeach; ?>
				</ul>
				
			</div>
		<?php endforeach; ?>
	
	<?php else: ?>
	
		<div style="margin-top: 30px;">
			Aucune transaction en <?php echo $certificate_year; ?>.
		</div>
	
	<?php endif; ?>
	
</div>
	


<div style="margin-top: 50px;">
	<?php echo $info_yearly_certificate; ?>
</div>


<?php require_once( 'common/footer.php' ); ?>


<?php
		$buffer = ob_get_clean();
		ob_end_flush();
		return $buffer;
	}
}