<?php
/**
 * Template PDF : attestation de versement de royalties
 */
class WDG_Template_PDF_Certificate_ROI_Payment {
	public static function get(
		$certificate_date,
		$project_roi_percent,
		$project_amount_collected,
		$project_roi_start_date,
		$project_investors_list,
		$project_roi_nb_years,
		$organization_name,
		$organization_address,
		$organization_postalcode,
		$organization_city,
		$declaration_date,
		$declaration_trimester,
		$declaration_year,
		$declaration_declared_turnover,
		$declaration_amount,
		$declaration_percent_commission,
		$declaration_amount_commission,
		$declaration_amount_and_commission,
		$declaration_adjustment_value,
		$declaration_remaining_amount_transfered
			
	) {
		ob_start();
?>
	
<page>
	<?php require_once( 'common/footer.php' ); ?>

	<div style="font-family: Arial; font-size: 10pt;" width="600">

		<div style="margin-top: 50px;">
			<img src="<?php echo __DIR__; ?>/../img/wdg-logo-red.png" />
		</div>

		<div style="margin-top: 20px;">
			<table>
				<tr>
					<td width="400">
						WEDOGOOD.co<br />
						Changeons le monde par la finance !<br>
						<br />
						www.wedogood.co<br />
						bonjour@wedogood.co
					</td>
					<td>
						<?php echo $organization_name; ?><br />
						<?php echo $organization_address; ?><br />
						<?php echo $organization_postalcode; ?> <?php echo $organization_city; ?>
					</td>
				</tr>
			</table>
		</div>

		<div style="margin-top: 70px;">
			<p>
				<span style="font-weight: bold; font-size: 18pt;">ATTESTATION DE VERSEMENT DE ROYALTIES T<?php echo $declaration_trimester; ?> <?php echo $declaration_year; ?></span><br />
				<span>Date : <?php echo $certificate_date; ?></span>
			</p>
			<p>
				Nous attestons avoir constaté via notre prestataire de service de paiement LEMON WAY que le paiement de <i>royalties (Redevance)</i>
				suivant a été réalisé en vertu des contrats de cession de revenus futurs mentionnés en annexe.
			</p>
		</div>

		<div style="margin-top: 40px;">

			<table>
				<tr style="font-weight: bold;">
					<td style="border-top: 1px solid; border-bottom: 1px solid; padding: 10px;" width="600">Désignation</td>
					<td style="border-top: 1px solid; border-bottom: 1px solid; padding: 10px; text-align: right;">Valeur</td>
				</tr>

				<tr>
					<td style="border-bottom: 1px solid gray; padding: 10px;">
						CHIFFRE D'AFFAIRES DÉCLARÉ PAR <?php echo $organization_name; ?><br />
						POUR LE TRIMESTRE <?php echo $declaration_trimester; ?> DE <?php echo $declaration_year; ?>
					</td>
					<td style="border-bottom: 1px solid gray; padding: 10px; text-align: right;"><?php echo number_format( $declaration_declared_turnover, 2, ',', ' ' ); ?> €</td>
				</tr>

				<tr>
					<td style="border-bottom: 1px solid gray; padding: 10px;">
						REDEVANCE DUE<br />
						<ul> <li> <?php echo number_format( $project_roi_percent, 1, ',', ' ' ); ?> % du chiffre d'affaires HT </li> </ul>
					</td>
					<td style="border-bottom: 1px solid gray; padding: 10px; text-align: right;"><?php echo number_format( $declaration_amount, 2, ',', ' ' ); ?> €</td>
				</tr>

				<?php if ( !empty( $declaration_adjustment_value ) ): ?>
				<tr>
					<td style="border-bottom: 1px solid gray; padding: 10px;">
						AJUSTEMENT EFFECTU&Eacute; PAR WE DO GOOD<br />
					</td>
					<td style="border-bottom: 1px solid gray; padding: 10px; text-align: right;"><?php echo number_format( $declaration_adjustment_value, 2, ',', ' ' ); ?> €</td>
				</tr>
				<?php endif; ?>

				<?php if ( !empty( $declaration_remaining_amount_transfered ) ): ?>
				<tr>
					<td style="border-bottom: 1px solid gray; padding: 10px;">
						RELIQUATS DES D&Eacute;CLARATIONS PR&Eacute;C&Eacute;DENTES REVERS&Eacute;S<br />
					</td>
					<td style="border-bottom: 1px solid gray; padding: 10px; text-align: right;"><?php echo number_format( $declaration_remaining_amount_transfered, 2, ',', ' ' ); ?> €</td>
				</tr>
				<?php endif; ?>

				<tr>
					<td style="border-bottom: 1px solid; padding: 10px;">
						FRAIS DE GESTION WE DO GOOD<br />
						<ul> <li> <?php echo number_format( $declaration_percent_commission, 1, ',', ' ' ); ?> % TTC de la Redevance </li> </ul>
					</td>
					<td style="border-bottom: 1px solid; padding: 10px; text-align: right;"><?php echo number_format( $declaration_amount_commission, 2, ',', ' ' ); ?> €</td>
				</tr>

				<tr>
					<td style="padding: 10px; text-align: right;">TOTAL PAYÉ POUR L'ÉCHÉANCE DU <?php echo $declaration_date; ?></td>
					<td style="padding: 10px; text-align: right;"><?php echo number_format( $declaration_amount_and_commission, 2, ',', ' ' ); ?> €</td>
				</tr>
			</table>

		</div>


		<div style="margin-top: 70px;">
			<b>Fait pour valoir ce que de droit</b><br />
			<br />
			WE DO GOOD SAS<br />
			Jean-David BAR, Président<br />
		</div>

	</div>


	<?php // PAGE 2 ?>

	<div>
		<img src="<?php echo __DIR__; ?>/../img/wdg-logo-red.png" />
	</div>

	<div style="margin-top: 20px;">
		<b>CONTRATS DE CESSION DE REVENUS FUTURS CONCERNÉS</b><br />
		<br />
		Les contrats passés avec les personnes ci-dessous, qui ont investi un montant total de <?php echo number_format( $project_amount_collected, 2, ',', ' ' ); ?> euros,
		ont engendré un engagement global de <?php echo $organization_name; ?> de <?php echo number_format( $project_roi_percent, 2, ',', ' ' ); ?> %
		de son chiffre d'affaires pendant <?php echo $project_roi_nb_years; ?> ans &agrave; compter du <?php echo $project_roi_start_date; ?>.
		Cet engagement lie <?php echo $organization_name; ?> &agrave; chacune de ces personnes &agrave; proportion de leur investissement.
	</div>
</page>

<page>
	<div style="margin-top: 30px;">
		<table style="border: 1px solid gray;">
			<tr>
				<td width="150" style="background: #222; color: #FFF; text-align: center;">Nom</td>
				<td width="150" style="background: #222; color: #FFF; text-align: center;">Prénom</td>
				<td width="100" style="background: #222; color: #FFF; text-align: center;">Montant investi</td>
				<td width="100" style="background: #222; color: #FFF; text-align: center;">Royalties per&ccedil;ues</td>
			</tr>
			<?php foreach ( $project_investors_list as $investor_obj ): ?>
			<tr>
				<td><?php echo $investor_obj[ 'lastname' ]; ?></td>
				<td><?php echo $investor_obj[ 'firstname' ]; ?></td>
				<td><?php echo number_format( $investor_obj[ 'amount' ], 0, ',', ' ' ); ?> €</td>
				<td><?php echo number_format( $investor_obj[ 'roi_amount' ], 2, ',', ' ' ); ?> €</td>
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