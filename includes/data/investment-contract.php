<?php
class WDGInvestmentContract {
	
	/**
	 * Gestion contrats
	 */
	private $payment_id;
	private $payment_amount;
	private $signsquid_contract_id;
	private $yousign_contract_id;
	
	public function __construct( $payment_id ) {
		$this->payment_id = $payment_id;
		$this->payment_amount = edd_get_payment_amount( $this->payment_id );
		$this->signsquid_contract_id = get_post_meta( $payment_id, 'signsquid_contract_id', true );
		$this->yousign_contract_id = get_post_meta( $payment_id, 'yousign_contract_id', true );
	}
	
	/**
	 * Retourne l'identifiant du contrat sur Signsquid
	 */
	public function get_signsquid_contract_id() {
		return $this->signsquid_contract_id;
	}
	
	public function is_signsquid_contract() {
		return ( !empty( $this->signsquid_contract_id ) );
	}
	
	/**
	 * Retourne l'identifiant du contrat sur Yousign
	 */
	public function get_yousign_contract_id() {
		return $this->yousign_contract_id;
	}
	
	public function is_yousign_contract() {
		return ( !empty( $this->yousign_contract_id ) );
	}
	
	
/******************************************************************************/
/* FONCTIONS STATIQUES */
/******************************************************************************/
	/**
	 * Création de contrat
	 * @param int $payment_id
	 * @param string $file_path
	 * @param WDGUser $user_investor
	 */
	public static function create_contract( $payment_id, $file_path, $user_investor ) {
		// Liste des fichiers à signer
		$list_files = array (
			array (
				'name'		=> basename( $file_path ),
				'content'	=> base64_encode( file_get_contents( $file_path ) ),
				'idFile'	=> $file_path
			)
		);
		
		// Création de la liste des signataires
		$list_person = array (
			array (
				'firstName'				=> $user_investor->get_firstname(),
				'lastName'				=> $user_investor->get_lastname(),
				'mail'					=> $user_investor->get_email(),
				'phone'					=> $user_investor->get_phone_number( TRUE ),
				'proofLevel'			=> 'LOW',
				'authenticationMode'	=> 'sms'
			)
		);
		
		
		// Placement des signatures sur le document
		$signature_options = array (
			// Placement des signatures pour le document
			$list_files[0]['idFile'] => array (
				array (
					'visibleSignaturePage'		=> '1',
					'isVisibleSignature'		=> true,
					'visibleRectangleSignature' => '48,32,248,132',
					'mail'						=> $list_person[0]['mail']
				)
			)
		);
		
		// Message vide car on est en mode Iframe
		$message = '';
		
		// Autres options
		$options = array (
			'mode'		=> 'IFRAME',
			'archive'	=> false
		);
		
		// Appel du client et récupération du résultat
		$buffer = FALSE;
		
		try {
			$client = WDGInvestmentContract::yousign_instance();
			ypcf_debug_log( 'WDGInvestmentContract::create_contract > initCoSign > $list_files : ' . print_r( $list_files, TRUE ) );
			ypcf_debug_log( 'WDGInvestmentContract::create_contract > initCoSign > $list_person : ' . print_r( $list_person, TRUE ) );
			ypcf_debug_log( 'WDGInvestmentContract::create_contract > initCoSign > $signature_options : ' . print_r( $signature_options, TRUE ) );
			$result = $client->initCoSign( $list_files, $list_person, $signature_options, $message, $options );

			if ( empty( $result ) ) {
				$yousign_errors = $client->getErrors();
				ypcf_debug_log( 'WDGInvestmentContract::create_contract > ERROR > ' . print_r( $yousign_errors, TRUE ) );
				
			} else {
				ypcf_debug_log( 'WDGInvestmentContract::create_contract > SUCCESS > ' . print_r( $result, TRUE ) );
				update_post_meta( $payment_id, 'yousign_contract_id', $result[ 'idDemand' ] );
				
				// Récupération du lien d'accès à la signature, en fonction du token de retour
				$buffer = $client->getIframeUrl( $result[ 'tokens' ][ 'token' ] );
			}
			
		} catch ( Exception $e ) {
			ypcf_debug_log( 'WDGInvestmentContract::create_contract > ERROR[TRY] > ' . print_r( $e, TRUE ) );
		}
		
		return $buffer;
	}
	
	
/******************************************************************************/
/* GESTION API */
/******************************************************************************/
	/* YOUSIGN */
	private static $yousign_instance;
	
	public static function yousign_instance() {
		if ( ! isset ( self::$yousign_instance ) ) {
			ypcf_debug_log( 'WDGInvestmentContract::yousign_instance' );
			$config_file_path = __DIR__. '/../../../../../ysApiParameters.ini';
			self::$yousign_instance = new \YousignAPI\YsApi( $config_file_path );
		}

		return self::$yousign_instance;
	}
	
}