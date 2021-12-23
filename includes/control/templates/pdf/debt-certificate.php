<?php
/**
 * Template PDF : attestation de créance
 */
class WDG_Template_PDF_Debt_Certificate {
	public static function get(
		$organization_name,
		$organization_address,
		$organization_postalcode,
		$organization_city,
		$organization_type,
		$organization_capital,
		$organization_siren,
		$organization_rcs,
		$organization_representative_function,
		$orga_creator_gender,
		$orga_creator_firstname,
		$orga_creator_lastname,
		$date_campaign_end,
		$number_investors,
		$amount_funded,
		$amount_paid,
		$amount_left,
		$date_contract_start,
		$date_contract_end,
		$percent_turnover,
		$date_today
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
				</tr>
			</table>
		</div>

		<div style="margin-top: 40px;">
			<p>
				<span style="font-weight: bold; font-size: 18pt;">ATTESTATION DE CR&Eacute;ANCE</span><br>
				<span>De <?php echo $number_investors; ?> personnes vis-à-vis de la société <?php echo $organization_name; ?></span>
			</p>
		</div>

		<div style="margin-top: 30px;">
			<p>
				<span>WE DO GOOD, SAS à capital variable au capital minimum de 10 000 €,</span><br>
				<span>immatriculée sous le numéro SIREN 797 519 105 au RCS de NANTES,</span><br>
				<span>Intermédiaire en Financement Participatif immatriculé à l’ORIAS sous le numéro 17002712,</span><br>
				<span>représentée par son Président, M. Jean-David BAR,</span><br>
			</p>
			<p>atteste que :</p>
		</div>
		<div style="margin-top: 30px;">
		<?php echo $organization_name; ?>, <?php echo $organization_type; ?> au capital minimum de <?php echo $organization_capital; ?>€ dont le siège social est à <?php echo $organization_address; ?> <?php echo $organization_postalcode; ?> <?php echo $organization_city; ?>, <br>
immatriculée sous le numéro de SIREN <?php echo $organization_siren; ?> au RCS de <?php echo $organization_rcs; ?>, <br>
représentée par son <?php echo $organization_representative_function; ?>, <?php echo $orga_creator_gender; ?> <?php echo $orga_creator_firstname; ?> <?php echo $orga_creator_lastname; ?>,

		</div>
		<div style="margin-top: 30px;">a effectué une levée de fonds en échange de royalties (redevance trimestrielle indexée sur son chiffre d’affaires) le <?php echo $date_campaign_end; ?> pour un montant total de <?php echo $amount_funded; ?> auprès de <?php echo $number_investors; ?> souscripteurs et s’est engagé à verser à ses souscripteurs <?php echo $percent_turnover; ?> % de son chiffre d’affaires réalisé entre le <?php echo $date_contract_start; ?> et le <?php echo $date_contract_end; ?>.
		</div>
		<div style="margin-top: 30px;">
Cet engagement était assorti d’une obligation de remboursement des investisseurs aux mêmes conditions après le <?php echo $date_contract_end; ?> si ces derniers n’avaient pas été remboursés à ce stade.
		</div>
		<div style="margin-top: 30px;">
Au total, <?php echo $organization_name; ?> a versé à ses souscripteurs <?php echo $amount_paid; ?> € jusqu’à ce jour, soit un reste à payer de <?php echo $amount_left; ?>  €.
		</div>
		<div style="margin-top: 30px;">
Les <?php echo $number_investors; ?> investisseurs ont mandaté WE DO GOOD pour mener toute action nécessaire à l'exécution du contrat signé avec <?php echo $organization_name; ?>.
		</div>
		
		<div style="margin-top: 70px;">
			<b>Fait pour valoir ce que de droit</b><br />
			<br />
			Nantes, le <?php echo $date_today; ?>  <br />
			Jean-David BAR, Président<br />
		</div>
	</div>
</page>


<?php require_once( 'common/footer.php' ); ?>

<?php
		$buffer = ob_get_clean();
		ob_end_flush();
		return $buffer;
	}
}