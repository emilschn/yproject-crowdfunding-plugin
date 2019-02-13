<?php
class WDG_Form_User_Tax_Exemption extends WDG_Form {
	
	public static $name = 'user-tax-exemption';
	
	public static $field_group_hidden = 'user-tax-exemption-hidden';
	
	private $user_id;
	
	public function __construct( $user_id = FALSE ) {
		parent::__construct( self::$name );
		$this->user_id = $user_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		// $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			self::$field_group_hidden,
			self::$name
		);
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			self::$field_group_hidden,
			$this->user_id
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$WDGUser = new WDGUser( $user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {

		// Analyse du formulaire
		} else {
			
			// Création du fichier PDF correspondant
			$date_today = new DateTime();
			
			$filename = $WDGUser->get_wpref(). '-' .sanitize_title( $WDGUser->get_firstname() ). '-' .sanitize_title( $WDGUser->get_lastname() ). '.pdf';
			$filepath = __DIR__ . '/../../../files/tax-exemption/' .$date_today->format( 'Y' ). '/' . $filename;
			$dirname = dirname( $filepath );
			if ( !is_dir( $dirname ) ) {
				mkdir( $dirname, 0755, true );
			}
			update_user_meta( $WDGUser->get_wpref(), 'tax_exemption_' .$date_today->format( 'Y' ), $filename );
			
			$core = ATCF_CrowdFunding::instance();
			$core->include_control( 'templates/pdf/form-tax-exemption' );
			$user_name = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname();
			$user_address = $WDGUser->get_full_address_str(). ' ' .$WDGUser->get_postal_code( TRUE ). ' ' .$WDGUser->get_city();
			$form_ip_address = $_SERVER[ 'REMOTE_ADDR' ];
			$form_date = $date_today->format( 'd/m/Y' );
			$html_content = WDG_Template_PDF_Form_Tax_Exemption::get( $user_name, $user_address, $form_ip_address, $form_date );
		
			$html2pdf = new HTML2PDF( 'P', 'A4', 'fr', true, 'UTF-8', array(12, 5, 15, 8) );
			$html2pdf->WriteHTML( urldecode( $html_content ) );
			$html2pdf->Output( $filepath, 'F' );
			
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		return $buffer;
	}
	
}
