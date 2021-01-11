<?php
class WDG_Form_User_Tax_Exemption extends WDG_Form {
	
	public static $name = 'user-tax-exemption';
	
	public static $field_group_hidden = 'user-tax-exemption-hidden';
	public static $field_group_upload = 'user-tax-exemption-upload';
	public static $field_group_create = 'user-tax-exemption-create';
	
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

		// $field_group_create
		$this->addField(
			'hidden',
			'real-action',
			'',
			self::$field_group_create,
			'create'
		);

		// $field_group_upload
		$this->addField(
			'hidden',
			'real-action',
			'',
			self::$field_group_upload,
			'upload'
		);
		$this->addField(
			'file',
			'tax-exemption-file',
			'',
			self::$field_group_upload
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$action_posted = filter_input( INPUT_POST, 'real-action' );
		$year = filter_input( INPUT_POST, 'year' );
		$WDGUser = new WDGUser( $user_id );
		$WDGUser_current = WDGUser::current();
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} elseif ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {

		// Analyse du formulaire
		} else {
			
			// Création et enregistrement en base du nom du fichier
			$date_today = new DateTime();			
			$filename = $WDGUser->get_wpref(). '-' .sanitize_title( $WDGUser->get_firstname() ). '-' .sanitize_title( $WDGUser->get_lastname() );
			$filepath = __DIR__ . '/../../../files/tax-exemption/' .$year. '/' . $filename;
			$dirname = dirname( $filepath );
			if ( !is_dir( $dirname ) ) {
				mkdir( $dirname, 0755, true );
			}

			if ( $action_posted == 'create' ){				
				$ext = 'pdf';				// Création du fichier PDF correspondant
				$core = ATCF_CrowdFunding::instance();
				$core->include_control( 'templates/pdf/form-tax-exemption' );
				$user_name = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname();
				$user_address = $WDGUser->get_full_address_str(). ' ' .$WDGUser->get_postal_code( TRUE ). ' ' .$WDGUser->get_city();
				$form_ip_address = $_SERVER[ 'REMOTE_ADDR' ];
				$form_date = $date_today->format( 'd/m/Y' );// TODO à changer suivant l'année ?
				$html_content = WDG_Template_PDF_Form_Tax_Exemption::get( $user_name, $user_address, $form_ip_address, $form_date, $year);
			
				$html2pdf = new HTML2PDF( 'P', 'A4', 'fr', true, 'UTF-8', array(12, 5, 15, 8) );
				$html2pdf->WriteHTML( urldecode( $html_content ) );
				$html2pdf->Output( $filepath.'.'.$ext , 'F' );				
			}
			
			if ( $action_posted == 'upload' ){
				// renommage du fichier uploadé et déplacement dans le bon dossier
				if ( isset( $_FILES[ 'tax-exemption-file' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'tax-exemption-file' ][ 'tmp_name' ] ) ) {					

					$file_name = $_FILES[ 'tax-exemption-file' ]['name'];
					$file_name_exploded = explode('.', $file_name);
					$ext = $file_name_exploded[count($file_name_exploded) - 1];
					
					// TODO : prendre le bon tableau des formats ?
					$good_file = TRUE;
					if ( !in_array( strtolower( $ext ), WDGKYCFile::$authorized_format_list ) ) {
						$good_file = FALSE;
						$error = array(
							'code'		=> 'format',
							'text'		=> __( "Ce fichier n'est pas au bon format.", 'yproject' ),
							'element'	=> 'format'
						);
						array_push( $feedback_errors, $error );
					}
					if ( ($_FILES[ 'tax-exemption-file' ]['size'] / 1024) / 1024 > 6 ) {
						$good_file = FALSE;
						$error = array(
							'code'		=> 'size',
							'text'		=> __( "Ce fichier est trop lourd.", 'yproject' ),
							'element'	=> 'size'
						);
						array_push( $feedback_errors, $error );
					}
		
					if ( $good_file ) {
						// si pas d'erreur, on renomme et déplace le fichier
						move_uploaded_file( $_FILES[ 'tax-exemption-file' ][ 'tmp_name' ], $filepath.'.'.$ext  );
					}
				}
		
			}
			// enregistrement en base du nom du fichier
			update_user_meta( $WDGUser->get_wpref(), 'tax_exemption_' .$year, $filename.'.'.$ext );
			
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		return $buffer;
	}
	
}
