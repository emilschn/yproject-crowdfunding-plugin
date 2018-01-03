<?php
/**
 * Template PDF : attestation de versement de royalties
 */
class WDG_Template_PDF_Campaign_Funded {
	public static function get(
		$organization_user_name,
		$organization_user_email,
		$organization_name,
		$organization_address,
		$organization_postalcode,
		$organization_city,
		$date_campaign_end,
		$number_investors,
		$amount_funded,
		$percent_commission,
		$amount_commission,
		$amount_project,
		$date_contract_start,
		$contract_duration,
		$percent_turnover,
		$fiscal_info
			
	) {
		ob_start();
?>


<?php require_once( 'common/footer.php' ); ?>

<div style="font-family: Arial; font-size: 10pt;" width="600">
	
	<div style="margin-top: 50px;">
		<img src="<?php echo __DIR__; ?>/../img/wdg-logo-red.png">
	</div>

	<div style="margin-top: 20px;">
		<table>
			<tr>
				<td width="500">
					WEDOGOOD.co<br>
					Changeons le monde par la finance !<br>
					<br>
					www.wedogood.co<br>
					bonjour@wedogood.co
				</td>
				<td>
					<?php echo $organization_user_name; ?><br>
					<?php echo $organization_user_email; ?><br>
					<?php echo $organization_name; ?><br>
					<?php echo $organization_address; ?><br>
					<?php echo $organization_postalcode. ' ' .$organization_city; ?>
				</td>
			</tr>
		</table>
	</div>

	<div style="margin-top: 40px;">
		<p>
			<span style="font-weight: bold; font-size: 18pt;">ATTESTATION DE LEV&Eacute;E DE FONDS</span><br>
			<span>Date : <?php echo $date_campaign_end; ?></span>
		</p>
	</div>

	<div style="margin-top: 30px;">
		
		<table>
			<tr>
				<td style="border-bottom: 1px solid gray; padding: 10px;" width="500">D&eacute;signation</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;">Valeur</td>
			</tr>
			
			<tr>
				<td style="border-bottom: 1px solid gray; padding: 10px;" width="500">LEV&Eacute;E DE FONDS EN ROYALTIES AUPR&Egrave;S DE <?php echo $number_investors; ?> INVESTISSEURS</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;"><?php echo $amount_funded; ?> &euro;</td>
			</tr>
			
			<tr>
				<td style="border-bottom: 1px solid gray; padding: 10px;" width="500">
					COMMISSION WE DO GOOD<br>
					* <?php echo $percent_commission; ?> % de la lev&eacute;e de fonds TTC
				</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;"><?php echo $amount_commission; ?> &euro;</td>
			</tr>
			
			<tr>
				<td style="border-bottom: 1px solid gray; padding: 10px; text-align: right;" width="400">TOTAL VERS&Eacute;</td>
				<td style="border-bottom: 1px solid gray; padding: 10px;"><?php echo $amount_project; ?> &euro;</td>
			</tr>
		</table>
		
	</div>
	
	<div style="margin-top: 30px;">
		<p>
			<span style="font-weight: bold; font-size: 15pt;">INFORMATIONS L&Eacute;GALES</span><br>
			Date de d&eacute;marrage du contrat de cession de revenus : <?php echo $date_contract_start; ?><br>
			Dur&eacute;e du contrat : <?php echo $contract_duration; ?> ans<br>
			Pourcentage du chiffre d'affaires &agrave; verser trimestriellement aux investisseurs : <?php echo $percent_turnover; ?> %<br>
			Co&ucirc;t total du financement : selon chiffre d'affaires r&eacute;alis&eacute; sur la dur&eacute;e du contrat
		</p>
	</div>

	<div style="margin-top: 30px;">
		<?php echo $fiscal_info; ?>
	</div>
	
</div>


<?php
		$buffer = ob_get_clean();
		ob_end_flush();
		return $buffer;
	}
}