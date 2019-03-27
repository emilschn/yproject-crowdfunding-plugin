<?php
/**
 * Gestion des contrats d'investissement
 */
class WDGInvestmentSignature {
	private $subscription_id;
	/**
	 * @var WDGInvestment 
	 */
	private $investment;
	private $external_provider;
	private $external_id;
	private $status;
	
	public static $investment_amount_signature_needed_minimum = 1501;
	
	private static $external_provider_none = 'none';
	private static $external_provider_signsquid = 'signsquid';
	private static $external_provider_eversign = 'eversign';
	
	private static $meta_signsquid = 'signsquid_contract_id';
	private static $meta_eversign = 'eversign_contract_id';
	
	private static $status_too_small = 'too_small';
	private static $status_waiting_for_creation = 'waiting_for_creation';
	private static $status_waiting = 'waiting';
	private static $status_signed = 'signed';
	private static $status_refused = 'refused';
	
	public function __construct( $subscription_id ) {
		$this->subscription_id = $subscription_id;
		$this->investment = new WDGInvestment( $this->subscription_id );
		$this->init_provider();
		$this->init_status();
	}
	
/*******************************************************************************
 * INITIALISATIONS
 ******************************************************************************/
	private function init_provider() {
		if ( empty( $this->external_provider ) ) {
			$signsquid_id = get_post_meta( $this->subscription_id, self::$meta_signsquid, TRUE );
			if ( !empty( $signsquid_id ) ) {
				$this->external_provider = self::$external_provider_signsquid;
				$this->external_id = $signsquid_id;
			}
		}
		
		if ( empty( $this->external_provider ) ) {
			$eversign_id = get_post_meta( $this->subscription_id, self::$meta_eversign, TRUE );
			if ( !empty( $eversign_id ) ) {
				$this->external_provider = self::$external_provider_eversign;
				$this->external_id = $eversign_id;
			}
		}
		
		if ( empty( $this->external_provider ) ) {
			$this->external_provider = self::$external_provider_none;
		}
	}
	
	private function init_status() {
		if ( $this->investment->get_saved_amount() < self::$investment_amount_signature_needed_minimum ) {
			$this->status = self::$status_too_small;
			
		} else if ( $this->external_provider == self::$external_provider_signsquid ) {
			$this->status = self::$status_signed;
			
		} else if ( $this->external_provider == self::$external_provider_eversign ) {
			$this->init_status_eversign();
		}
	}
	
	private function init_status_eversign() {
		$this->status = self::$status_waiting_for_creation;
		$this->include_eversign();
		$document_info = WDGEversign::get_document( $this->external_id );
		
		if ( !empty( $document_info ) ) {
			$this->status = self::$status_waiting;
		
			if ( $document_info->is_completed ) {
				$this->status = self::$status_signed;
				
			} else if ( $document_info->is_cancelled || $document_info->is_deleted || $document_info->is_expired ) {
				$this->status = self::$status_refused;
			}
		}
	}
	
	private function include_eversign() {
		$core = crowdfunding();
		$core->include_control( 'esignature/eversign/wdgeversign' );
	}
	
/*******************************************************************************
 * INFORMATIONS
 ******************************************************************************/
	public function get_external_id() {
		return $this->external_id;
	}
	
	public function get_status() {
		return $this->status;
	}
	
	public function is_waiting_signature() {
		return ( $this->status != self::$status_signed && $this->status != self::$status_too_small );
	}
	
	public function is_signed() {
		return ( $this->status == self::$status_signed );
	}
	
/*******************************************************************************
 * VERIFICATIONS
 ******************************************************************************/
	public function check_signature_creation() {
		if ( $this->status == self::$status_waiting_for_creation || ( $this->external_provider == self::$external_provider_none && $this->status != self::$status_too_small ) ) {
			$this->create_eversign();
		}
	}
	
/*******************************************************************************
 * CREATIONS
 ******************************************************************************/
	public function create_eversign() {
		$this->include_eversign();
		$WDGInvestment = new WDGInvestment( $this->subscription_id );
		$campaign = $WDGInvestment->get_saved_campaign();
		$investor_id = $WDGInvestment->get_saved_user_id();
		if ( WDGOrganization::is_user_organization( $investor_id ) ) {
			$WDGEntity = new WDGOrganization( $investor_id );
			$user_name = $WDGEntity->get_name();
			$user_email = $WDGEntity->get_email();
		} else {
			$WDGEntity = new WDGUser( $investor_id );
			$user_name = $WDGEntity->get_firstname(). ' ' .$WDGEntity->get_lastname();
			$user_email = $WDGEntity->get_email();
		}
		$date_payment = date_i18n( get_option('date_format'), strtotime( get_post_field( 'post_date', $this->subscription_id ) ) );
	
		$title = $WDGInvestment->get_saved_amount(). " € de " .$user_name. " sur " .$campaign->get_name();
		$message = "Investissement de " .$WDGInvestment->get_saved_amount(). " € de " .$user_name. " (" .$user_email. ") sur " .$campaign->get_name(). " - Le " .$date_payment; 
		$wdg_signature_id = 'INVESTMENT-' .$this->subscription_id;
		
		getNewPdfToSign( $campaign->ID, $this->subscription_id, $investor_id );
		global $new_pdf_file_name;
		$file_name = $new_pdf_file_name;
		$file_url = home_url( '/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/' .$new_pdf_file_name );
		
		$signer_id = '1';
		$signer_name = $user_name;
		$signer_email = $user_email;
		
		$result = WDGEversign::create_document( $title, $message, $wdg_signature_id, $file_name, $file_url, $signer_id, $signer_name, $signer_email, $campaign->ID );
		
		update_post_meta( $this->subscription_id, self::$meta_eversign, $result->document_hash );
		
		$buffer = FALSE;
		if ( !empty( $result ) ) {
			$buffer = $result->document_hash;
		}
		return $buffer;
	}
	
}