<?php
class WDGInvestmentContract {
	
	/**
	 * Gestion contrats
	 */
	private $payment_id;
	private $payment_amount;
	private $signsquid_contract_id;
	private $yousign_contract_id;
	
	public static $signature_minimum_amount = 1500;
	
	public function __construct( $payment_id ) {
		$this->payment_id = $payment_id;
		$this->payment_amount = edd_get_payment_amount( $this->payment_id );
		$this->signsquid_contract_id = get_post_meta( $payment_id, 'signsquid_contract_id', true );
		$this->yousign_contract_id = get_post_meta( $payment_id, 'yousign_contract_id', true );
	}
	
	/**
	 * Retourne vrai si un contrat a déjà été créé chez un prestataire
	 * @return boolean
	 */
	public function exists() {
		return ( $this->is_signsquid_contract() || $this->is_yousign_contract() );
	}
	
	public static $status_code_agreed = 'AGREED';
	/**
	 * Retourne une chaine qui indique le code de statut du contrat
	 * @return string or boolean
	 */
	public function get_status_code() {
		$buffer = FALSE;
		if ( $this->is_signsquid_contract() ) {
			$buffer = WDGInvestmentContract::$status_code_agreed;
			
		} elseif ( $this->is_yousign_contract() ) {
			if ( $this->get_yousign_contract_status() ) {
				$buffer = WDGInvestmentContract::$status_code_agreed;
				
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne le statut du contrat (signé ou non) en texte lisible
	 */
	public function get_status_str() {
		$buffer = __( "Investissement valid&eacute;", 'yproject' );
		if ( $this->is_signsquid_contract() ) {
			$buffer = __( "Contrat sign&eacute;", 'yproject' );
			
		} elseif ( $this->is_yousign_contract() ) {
			if ( $this->get_yousign_contract_status() ) {
				$buffer = __( "Contrat sign&eacute;", 'yproject' );
				
			} else {
				$buffer = __( "En attente de signature", 'yproject' );
				
			}
		}
		
		return $buffer;
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
	
	public function get_yousign_contract_status() {
		$buffer = FALSE;
		
		$client = WDGInvestmentContract::yousign_instance();
		$result = $client->getCosignInfoFromIdDemand( $this->get_yousign_contract_id() );
		$yousign_contract_status = $result[ 'status' ];
		switch ( $yousign_contract_status ) {
			// when the process is still waiting for one signature
			case 'COSIGNATURE_EVENT_REQUEST_PENDING':
				break;
			// when all signers have signed
			case 'COSIGNATURE_EVENT_OK':
				$buffer = TRUE;
				break;
			// when someone is signing a document
			case 'COSIGNATURE_EVENT_PROCESSING':
				break;
			// if the process has been cancelled
			case 'COSIGNATURE_EVENT_CANCELLED':
				break;
			// if the signature process is finished and contains at least one error
			case 'COSIGNATURE_EVENT_PARTIAL_ERROR': 
				break;
		}
		
		return $buffer;
	}
	
	public function get_yousign_url() {
		$buffer = FALSE;
		
		$client = WDGInvestmentContract::yousign_instance();
		$result = $client->getCosignInfoFromIdDemand( $this->get_yousign_contract_id() );
		ypcf_debug_log( 'WDGInvestmentContract::get_yousign_url > getCosignInfoFromIdDemand : ' . print_r( $result, TRUE ) );
		if ( !empty( $result ) ) {
			$yousign_contract_token = $result[ 'cosignerInfos' ][ 'token' ];
		}
		ypcf_debug_log( 'WDGInvestmentContract::get_yousign_url > $yousign_contract_token : ' . $yousign_contract_token );
		if ( !empty( $yousign_contract_token ) ) {
			$buffer = $client->getIframeUrl( $yousign_contract_token );
		}
		ypcf_debug_log( 'WDGInvestmentContract::get_yousign_url > $buffer : ' . $buffer );
		
		return $buffer;
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
	public static function create( $payment_id, $file_path, $user_investor ) {
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
					'isVisibleSignature'		=> false,
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