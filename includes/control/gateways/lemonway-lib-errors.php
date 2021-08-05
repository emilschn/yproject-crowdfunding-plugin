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
			'03' => "lemonway.error_01_03",
			'05' => "lemonway.error_01_05",
			'12' => "lemonway.error_01_12",
			'14' => "lemonway.error_01_14",
			'17' => "lemonway.error_01_17",
			'34' => "lemonway.error_01_34",
			'54' => "lemonway.error_01_54",
			'63' => "lemonway.error_01_63",
			'75' => "lemonway.error_01_75",
			'90' => "lemonway.error_01_90",
			'99' => "lemonway.error_01_99"
		),
		array (
			'02' => "lemonway.error_02_02",
			'03' => "lemonway.error_02_03",
			'05' => "lemonway.error_02_05",
			'06' => "lemonway.error_02_06",
			'07' => "lemonway.error_02_07",
			'08' => "lemonway.error_02_08",
			'17' => "lemonway.error_02_17",
			'99' => "lemonway.error_02_99"
		),
		array (
			'04' => "lemonway.error_03_04",
			'05' => "lemonway.error_03_05",
			'07' => "lemonway.error_03_07",
			'12' => "lemonway.error_03_12",
			'13' => "lemonway.error_03_13",
			'14' => "lemonway.error_03_14",
			'15' => "lemonway.error_03_15",
			'30' => "lemonway.error_03_30",
			'33' => "lemonway.error_03_33",
			'34' => "lemonway.error_03_34",
			'41' => "lemonway.error_03_41",
			'43' => "lemonway.error_03_43",
			'51' => "lemonway.error_03_51",
			'54' => "lemonway.error_03_54",
			'56' => "lemonway.error_03_56",
			'57' => "lemonway.error_03_57",
			'59' => "lemonway.error_03_59",
			'61' => "lemonway.error_03_61",
			'63' => "lemonway.error_03_63",
			'68' => "lemonway.error_03_68",
			'90' => "lemonway.error_03_90",
			'91' => "lemonway.error_03_91",
			'96' => "lemonway.error_03_96",
			'97' => "lemonway.error_03_97",
			'98' => "lemonway.error_03_98",
			'99' => "lemonway.error_03_99"
		)
	);
	private static $column_restart = array(
		array( '14', '54', '63', '75' ),
		array( '03', '05', '06', '07', '08', '17' ),
		array( '04', '05', '07', '30', '33', '34', '41', '43', '51', '54', '56', '57', '59', '61', '63', '68', '91', '97', '99' )
	);
	
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
	public function get_error_message( $add_generic_message = TRUE, $use_cache = TRUE ) {
		if ( !isset( $this->error_message ) || !$use_cache ) {
			
			$buffer = '';
			WDG_Languages_Helpers::load_languages();
			
			// Si le code complet est listé dans les erreurs génériques
			if ( in_array( $this->error_code, LemonwayLibErrors::$generic_errors ) ) {
				
				$buffer = __( 'lemonway.error.RAW', 'yproject' ) . '<br>';
				if ( $add_generic_message ) {
					$buffer .= __( 'lemonway.error.GENERIC', 'yproject' ) . '<br>';
				}
				
				
			// Sinon, on coupe en morceaux pour savoir quoi afficher par partie
			} else {
			
				$code_exploded = explode( '-', $this->error_code );
				if ( count( $code_exploded ) >= 3 ) {

					for ( $i = 0; $i < 3; $i++ ) {

						if ( !empty( $code_exploded[ $i ] ) ) {

							$code_column = $code_exploded[ $i ];

							if ( isset( LemonwayLibErrors::$column_errors[ $i ][ $code_column ] ) ) {
								$buffer .= __( LemonwayLibErrors::$column_errors[ $i ][ $code_column ], 'yproject' ) . '<br />';
							}

							$this->ask_restart = $this->ask_restart || in_array( $code_column, LemonwayLibErrors::$column_restart[ $i ] );

						}

					}

				}

				if ( empty ( $buffer ) ) {
					$buffer = __( 'lemonway.error.RAW', 'yproject' ) . '<br />';
				}

				if ( $add_generic_message ) {
					$buffer .= __( 'lemonway.error.GENERIC', 'yproject' ) . '<br />';
				}
				
			}
			
			if ( $use_cache ) {
				$this->error_message = $buffer;
			}
			
		} else {
			$buffer = $this->error_message;
			
		}
		
		return $buffer;
	}
	
	/**
	 * Renvoie true si ce type d'erreur doit proposer de red&eacute;marrer
	 * @return boolean
	 */
	public function ask_restart() {
		return $this->ask_restart;
	}
	
}
