<?php
/**
 * Template PDF : attestation de versement de royalties
 */
class WDG_Template_PDF_Certificate_ROI_Yearly_User {
	public static function get(
		$user_organization_name,
		$user_organization_id,
		$user_name,
		$user_email,
		$user_address,
		$user_postal_code,
		$user_city,
		$certificate_date,
		$certificate_year,
		$investment_list,
		$roi_number,
		$roi_list,
		$roi_total,
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
					WEDOGOOD.co<br />
					Changeons le monde par la finance<br />
					<br />
					www.wedogood.co<br />
					bonjour@wedogood.co
				</td>
				<td>
					<?php if (!empty($user_organization_name)): ?>
						<?php echo $user_organization_name; ?><br />
						<?php echo $user_organization_id; ?><br />
					<?php endif; ?>
					<?php echo $user_name; ?><br />
					<?php echo $user_email; ?><br />
					<?php echo $user_address; ?><br />
					<?php echo $user_postal_code; ?> <?php echo $user_city; ?>
				</td>
			</tr>
		</table>
	</div>

	<div style="margin-top: 40px;">
		<p>
			<span style="font-weight: bold; font-size: 18pt;">DOCUMENT R&Eacute;CAPITULATIF DES TRANSACTIONS PER&Ccedil;UES EN <?php echo $certificate_year; ?></span><br />
			<span><?php echo $certificate_date; ?></span>
		</p>
	</div>

	
	<?php // Tableau investissements ?>
	<div style="margin-top: 40px;">
		<p>
			<span style="font-weight: bold; font-size: 16pt;">RAPPEL DES INVESTISSEMENTS R&Eacute;ALIS&Eacute;S</span>
		</p>
	</div>
	
	<?php if ( $roi_number > 0 ): ?>
	<div style="margin-top: 30px;">
		
		<table>
			<tr style="font-weight: bold;">
				<td style="border-top: 1px solid; border-bottom: 1px solid; padding: 10px;" colspan="3">INVESTISSEMENTS</td>
			</tr>
			
			<tr>
				<td style="border-bottom: 1px solid gray; padding: 10px;" width="400">Description</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;" width="50">Date</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;">Montant</td>
			</tr>
			
			<?php foreach ( $investment_list as $investment ): ?>
			<tr>
				<td style="border-bottom: 1px solid gray; padding: 10px;">
					Investissement sur le projet de la société <?php echo $investment['organization_name']; ?><br />
					<?php echo $investment['organization_address']; ?><br />
					Numéro SIREN : <?php echo $investment['organization_id']; ?>
				</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;"><?php echo $investment['date']; ?></td>
				<td style="border-bottom: 1px solid gray; padding: 10px;"><?php echo $investment['amount']; ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
		
	</div>

	
	<?php // Tableau investissements ?>
	<div style="margin-top: 40px;">
		<p>
			<span style="font-weight: bold; font-size: 16pt;"><?php echo $roi_number; ?> TRANSACTION<?php if ( $roi_number > 1 ): ?>S<?php endif; ?> PER&Ccedil;UE<?php if ( $roi_number > 1 ): ?>S<?php endif; ?> EN <?php echo $certificate_year; ?></span>
		</p>
	</div>

	<div style="margin-top: 30px;">
		
		<table>
			<tr style="font-weight: bold;">
				<td style="border-top: 1px solid; border-bottom: 1px solid; padding: 10px;" colspan="3">RECETTES ENCAISS&Eacute;ES</td>
			</tr>
			
			<tr>
				<td style="border-bottom: 1px solid gray; padding: 10px;" width="400">Description des recettes</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;" width="50">Date de versement</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;">Montant</td>
			</tr>
			
			<?php foreach ( $roi_list as $roi ): ?>
			<tr>
				<td style="border-bottom: 1px solid gray; padding: 10px;">Redevance <?php echo $roi['organization_name']; ?> - <?php echo $roi['trimester_months']; ?></td>
				<td style="border-bottom: 1px solid gray; padding: 10px;"><?php echo $roi['date']; ?></td>
				<td style="border-bottom: 1px solid gray; padding: 10px;"><?php echo $roi['amount']; ?></td>
			</tr>
			<?php endforeach; ?>
			
			<tr style="font-weight: bold;">
				<td style="border-top: 1px solid; border-bottom: 1px solid; padding: 10px;" colspan="2">Total</td>
				<td style="border-top: 1px solid; border-bottom: 1px solid; padding: 10px;"><?php echo $roi_total; ?></td>
			</tr>
		</table>
		
	</div>
	
	<?php else: ?>
	
	<div style="margin-top: 30px;">
		Aucune transaction en <?php echo $certificate_year; ?>
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