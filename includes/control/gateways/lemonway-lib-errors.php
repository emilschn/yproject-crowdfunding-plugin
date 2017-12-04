<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe de gestion des erreurs Lemonway
 */
class LemonwayLibErrors {
	
	private $error_code;
	private $error_message;
	private $ask_restart;
	
	private static $column_errors = array(
		array (
			'03' => "Notre prestataire de paiement nous signale une erreur de configuration.",
			'05' => "Votre paiement a &eacute;t&eacute; refus&eacute; par notre prestataire de paiement.",
			'12' => "La transaction n'a pas pu &ecirc;tre valid&eacute;e par notre prestataire de paiement.",
			'14' => "Les coordonn&eacute;es bancaires ou le cryptogramme visuel sont invalides. Merci de renouveler l'op&eacute;ration.",
			'17' => "Votre investissement a &eacute;t&eacute; annul&eacute;.",
			'34' => "Notre prestataire de paiement nous fait part d'une suspicion de fraude.",
			'54' => "La date de validit&eacute; de la carte bancaire est d&eacute;pass&eacute;e. Veuillez v&eacute;rifier vos informations, utiliser une autre carte bancaire ou choisir un autre mode de paiement.",
			'63' => "La transaction n'a pas &eacute;t&eacute; autoris&eacute;e. Nous vous conseillons de vous rapprocher de votre banque pour en conna&icirc;tre les raisons ou de choisir un autre mode de paiement.",
			'75' => "Votre tentative d'investissement a &eacute;chou&eacute; car le code de s&eacute;curit&eacute; 3-D Secure transmis par votre banque n'a pas &eacute;t&eacute; renseign&eacute; dans les temps ou sa saisie est incorrecte. Veuillez renouveler l'op&eacute;ration.",
			'90' => "Le service de paiement est temporairement indisponible. ",
			'99' => "Le service de paiement est temporairement indisponible. "
		),
		array (
			'02' => "Nous sommes tr&egrave;s heureux de voir que vous &ecirc;tes un investisseur engag&eacute; pour l'impact positif ! N&eacute;anmoins, pour des raisons de s&eacute;curit&eacute;, le nombre d'investissements par jour est limit&eacute; &agrave; 3. Il vous faudra attendre 24h avant votre prochain investissement !",
			'03' => "Vous ne pouvez pas investir avec cette carte car elle est sur la liste grise de notre prestataire de paiement Lemon Way, merci de choisir un autre mode de paiement.",
			'05' => "Le pays d'&eacute;mission de votre carte bancaire n'est pas reconnu par notre prestataire de paiement Lemon Way. Merci de v&eacute;rifier vos informations, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'06' => "Le pays d'émission de votre carte bancaire n'est pas autoris&eacute; par notre prestataire de paiement Lemon Way. Merci d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'07' => "L'e-carte de paiement n'est pas autoris&eacute;e par notre prestataire de paiement Lemon Way. Merci d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'08' => "Le pays d'&eacute;mission de votre carte bancaire n'est pas autoris&eacute; par notre prestataire de paiement Lemon Way. Merci d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'17' => "Votre tentative d'investissement a &eacute;chou&eacute; car le code de s&eacute;curit&eacute; 3-D Secure transmis par votre banque n'a pas &eacute;t&eacute; renseign&eacute; dans les temps ou sa saisie est incorrecte. Veuillez renouveler l’op&eacute;ration.",
			'99' => "Notre prestataire de paiement Lemon Way nous fait part d'un probl&egrave;me technique."
		),
		array (
			'04' => "Notre prestataire de paiement Lemon Way nous informe qu'il suspecte une fraude vis-&agrave;-vis de l'utilisation de cette carte. Nous vous conseillons de vous rapprocher de votre banque pour en conna&icirc;tre les raisons ou de choisir un autre mode de paiement.",
			'05' => "La transaction n'a pas &eacute;t&eacute; autoris&eacute;e par notre prestataire de paiement. Nous vous conseillons de vous rapprocher de votre banque pour en connaître les raisons en leur communiquant la date et le montant ou de choisir un autre mode de paiement.",
			'07' => "Notre prestataire de paiement Lemon Way nous informe qu'il suspecte une fraude vis-&agrave;-vis de l'utilisation de cette carte. Nous vous conseillons de vous rapprocher de votre banque pour en connaître les raisons en leur communiquant la date et le montant ou de choisir un autre mode de paiement.",
			'12' => "La transaction est invalide. ",
			'13' => "La transaction est invalide. ",
			'14' => "Notre prestataire de paiement Lemon Way ne reconna&icirc;t pas le num&eacute;ro de porteur.",
			'15' => "Notre prestataire de paiement Lemon Way ne reconna&icirc;t pas l'&eacute;metteur de la carte.",
			'30' => "Notre prestataire de paiement nous signale une erreur de format. Veuillez r&eacute;essayer ou choisir un autre mode de paiement.",
			'33' => "La date de validit&eacute; de la carte bancaire est d&eacute;pass&eacute;e. Merci de v&eacute;rifier vos informations, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'34' => "Notre prestataire de paiement Lemon Way nous informe qu'il suspecte une fraude vis-&agrave;-vis de l'utilisation de cette carte. Nous vous conseillons de vous rapprocher de votre banque pour en conna&icirc;tre les raisons en leur communiquant la date et le montant ou de choisir un autre mode de paiement.",
			'41' => "Le paiement a &eacute;t&eacute; bloqu&eacute; car la carte utilis&eacute;e pour le paiement semble avoir &eacute;t&eacute; d&eacute;clar&eacute;e comme perdue par son propriétaire. Veuillez contacter votre banque. En parall&egrave;le, vous pouvez utiliser une autre carte bancaire ou choisir un autre mode de paiement.",
			'43' => "Le paiement a &eacute;t&eacute; bloqu&eacute; car la carte utilis&eacute;e pour le paiement semble avoir &eacute;t&eacute; d&eacute;clar&eacute;e comme vol&eacute;e par son propriétaire. Veuillez contacter votre banque. En parall&egrave;le, vous pouvez utiliser une autre carte bancaire ou choisir un autre mode de paiement.",
			'51' => "La provision de votre compte bancaire semble insuffisante pour valider l'investissement ou le plafond de paiement de votre carte a &eacute;t&eacute; d&eacute;pass&eacute;. Nous vous conseillons de vous rapprocher de votre banque ou de choisir un autre mode de paiement.",
			'54' => "La date de validit&eacute; de la carte bancaire est d&eacute;pass&eacute;e. Merci de v&eacute;rifier vos informations, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'56' => "Notre prestataire de paiement Lemon Way nous signale une erreur technique pour le paiement avec cette carte. Merci de v&eacute;rifier vos informations, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'57' => "Selon notre prestataire de paiement, la transaction n'a pas &eacute;t&eacute; permise par votre banque. Nous vous conseillons de vous rapprocher de votre banque pour en conna&icirc;tre les raisons en leur communiquant la date et le montant ou de choisir un autre mode de paiement.",
			'59' => "Notre prestataire de paiement Lemon Way nous informe qu'il suspecte une fraude vis-&agrave;-vis de l'utilisation de cette carte. Nous vous conseillons de vous rapprocher de votre banque pour en conna&icirc;tre les raisons en leur communiquant la date et le montant ou de choisir un autre mode de paiement.",
			'61' => "La provision de votre compte bancaire semble insuffisante pour valider l'investissement ou le plafond de paiement de votre carte a &eacute;t&eacute; d&eacute;pass&eacute;. Merci de prendre contact avec votre banque ou de choisir un autre mode de paiement.",
			'63' => "Notre prestataire de paiement Lemon Way nous signale que les r&egrave;gles de s&eacute;curit&eacute; n'ont pas &eacute;t&eacute; respect&eacute;es. Merci de prendre contact avec votre banque ou de choisir un autre mode de paiement.",
			'68' => "Notre prestataire de paiement Lemon Way nous signale une erreur technique. Merci de bien vouloir r&eacute;essayer, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'90' => "Le service de paiement est temporairement indisponible.",
			'91' => "Selon notre prestataire de paiement Lemon Way, l'&eacute;metteur de cette carte est inaccessible. Merci de bien vouloir r&eacute;essayer, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'96' => "Le service de paiement est temporairement indisponible.",
			'97' => "Notre prestataire de paiement Lemon Way nous signale une erreur technique. Merci de bien vouloir r&eacute;essayer, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
			'98' => "Le service de paiement est temporairement indisponible.",
			'99' => "Notre prestataire de paiement Lemon Way nous signale une erreur technique. Merci de bien vouloir r&eacute;essayer, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.",
		)
	);
	private static $column_restart = array(
		array( '14', '54', '63', '75' ),
		array( '03', '05', '06', '07', '08', '17' ),
		array( '04', '05', '07', '30', '33', '34', '41', '43', '51', '54', '56', '57', '59', '61', '63', '68', '91', '97', '99' )
	);
	private static $raw_message = "Il y a eu un probl&egrave;me lors de votre investissement. Merci de bien vouloir r&eacute;essayer, d'utiliser une autre carte bancaire ou de choisir un autre mode de paiement.";
	private static $generic_message = "Si besoin, nous sommes &agrave; votre disposition sur le chat en ligne ou &agrave; l'adresse suivante : investir@wedogood.co. Merci de nous pr&eacute;ciser le code d'erreur ci-dessous.";
	
	private static $generic_errors = array(
		'75---ERR_PSP_REFUSED'
	);
	
	/**
	 * 
	 * @param string $lemonway_error_code
	 */
	public function __construct( $lemonway_error_code ) {
		$this->ask_restart = FALSE;
		$this->error_code = $lemonway_error_code;
	}
	
	/**
	 * Retourne le code d'erreur LW
	 * @return string
	 */
	public function get_error_code() {
		return $this->error_code;
	}
	
	/**
	 * Retourne un message humain en découpant le code erreur LW
	 * @param string $code (Ex : 05-00-51-ERR_PSP_REFUSED)
	 * @return string
	 */
	public function get_error_message() {
		if ( !isset( $this->error_message ) ) {
			
			$this->error_message = '';
			
			// Si le code complet est listé dans les erreurs génériques
			if ( in_array( $this->error_code, LemonwayLibErrors::$generic_errors ) ) {
				
				$this->error_message = __( LemonwayLibErrors::$raw_message, 'yproject' ) . '<br />';
				$this->error_message .= __( LemonwayLibErrors::$generic_message, 'yproject' ) . '<br />';
				
				
			// Sinon, on coupe en morceaux pour savoir quoi afficher par partie
			} else {
			
				$code_exploded = explode( '-', $this->error_code );
				if ( count( $code_exploded ) >= 3 ) {

					for ( $i = 0; $i < 3; $i++ ) {

						if ( !empty( $code_exploded[ $i ] ) ) {

							$code_column = $code_exploded[ $i ];

							if ( isset( LemonwayLibErrors::$column_errors[ $i ][ $code_column ] ) ) {
								$this->error_message .= __( LemonwayLibErrors::$column_errors[ $i ][ $code_column ], 'yproject' ) . '<br />';
							}

							$this->ask_restart = $this->ask_restart || in_array( $code_column, LemonwayLibErrors::$column_restart[ $i ] );

						}

					}

				}

				if ( empty ( $this->error_message ) ) {
					$this->error_message = __( LemonwayLibErrors::$raw_message, 'yproject' ) . '<br />';
				}

				$this->error_message .= __( LemonwayLibErrors::$generic_message, 'yproject' ) . '<br />';
				
			}
			
		}
		
		return $this->error_message;
	}
	
	/**
	 * Renvoie true si ce type d'erreur doit proposer de red&eacute;marrer
	 * @return boolean
	 */
	public function ask_restart() {
		return $this->ask_restart;
	}
	
}
