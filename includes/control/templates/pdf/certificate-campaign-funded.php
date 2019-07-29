<?php
/**
 * Template PDF : attestation de levée de fonds
 */
class WDG_Template_PDF_Campaign_Funded {
	public static function get(
		$organization_user_name,
		$organization_user_email,
		$organization_name,
		$organization_address,
		$organization_postalcode,
		$organization_city,
		$free_field,
		$date_campaign_end,
		$number_investors,
		$amount_funded,
		$percent_commission,
		$amount_commission,
		$platform_commission_below_100000,
		$platform_commission_below_100000_amount,
		$platform_commission_above_100000,
		$platform_commission_above_100000_amount,
		$amount_project,
		$date_contract_start,
		$contract_duration,
		$percent_turnover,
		$fiscal_info,
		$project_investors_list
			
	) {
		ob_start();
?>


<page>
	<?php require_once( 'common/footer.php' ); ?>
	
	<div style="font-family: Arial; font-size: 10pt;" width="600">

		<div style="margin-top: 50px;">
			<img src="<?php echo __DIR__; ?>/../img/wdg-logo-red.png">
		</div>

		<div style="margin-top: 20px;">
			<table>
				<tr>
					<td width="450">
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
				<?php if ( !empty( $free_field ) ): ?>
				<span><?php echo $free_field; ?></span><br><br>
				<?php endif; ?>
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
						
						<?php if ( $platform_commission_above_100000_amount > 0 && $platform_commission_below_100000 != $platform_commission_above_100000 ): ?>
							- <?php echo $platform_commission_below_100000; ?> % de 100 000 &euro; : <?php echo $platform_commission_below_100000_amount; ?> &euro;<br>
							- <?php echo $platform_commission_above_100000; ?> % au-delà de 100 000 &euro; : <?php echo $platform_commission_above_100000_amount; ?> &euro;
							
						<?php else: ?>
							* <?php echo $percent_commission; ?> % de la lev&eacute;e de fonds TTC
							
						<?php endif; ?>
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
</page>

<page>
	<div style="margin-top: 10px;">
		<p>
			<span style="font-weight: bold; font-size: 15pt;">CONTRATS DE CESSION DE REVENUS FUTURS CONCERN&Eacute;S</span><br>
			Les contrats pass&eacute;s avec les personnes ci-dessous, qui ont investi un montant total de <?php echo $amount_funded; ?> &euro;, 
			ont engendr&eacute; un engagement global de <?php echo $organization_name; ?> de verser <?php echo $percent_turnover; ?> % de son chiffre d'affaires
			pendant <?php echo $contract_duration; ?> ans &agrave; compter du <?php echo $date_contract_start; ?>.
			Cet engagement lie <?php echo $organization_name; ?> &agrave; chacune de ces personnes &agrave; proportion de leur investissement.
		</p>
		<br>
		
		<table style="border: 1px solid gray;">
			<tr>
				<td width="150" style="background: #222; color: #FFF; text-align: center;">Nom</td>
				<td width="150" style="background: #222; color: #FFF; text-align: center;">Prénom</td>
				<td width="100" style="background: #222; color: #FFF; text-align: center;">Montant investi</td>
			</tr>
			<?php foreach ( $project_investors_list as $investor_obj ): ?>
			<tr>
				<td><?php echo $investor_obj[ 'lastname' ]; ?></td>
				<td><?php echo $investor_obj[ 'firstname' ]; ?></td>
				<td><?php echo number_format( $investor_obj[ 'amount' ], 0, ',', ' ' ); ?> €</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>
</page>


<?php
		$buffer = ob_get_clean();
		ob_end_flush();
		return $buffer;
	}
}