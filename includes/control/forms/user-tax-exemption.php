<?php
class WDG_Form_User_Tax_Exemption extends WDG_Form {
	public static $name = 'user-tax-exemption';

	public static $field_group_hidden_inprogress = 'user-tax-exemption-hidden-inprogress';
	public static $field_group_hidden_next = 'user-tax-exemption-hidden-next';
	public static $field_group_upload_inprogress = 'user-tax-exemption-upload-inprogress';
	public static $field_group_upload_next = 'user-tax-exemption-upload-next';
	public static $field_group_create = 'user-tax-exemption-create';

	private $user_id;

	public function __construct($user_id = FALSE) {
		parent::__construct( self::$name );
		$this->user_id = $user_id;
		$this->initFields();
	}

	protected function initFields() {
		parent::initFields();

		$date_today = new DateTime();
		$inprogress_year = $date_today->format( 'Y' );
		$next_year = $date_today->format( 'Y' )+1;

		// $field_group_hidden_inprogress
		$this->addField('hidden', 'action', '', self::$field_group_hidden_inprogress, self::$name);

		$this->addField('hidden', 'user_id', '', self::$field_group_hidden_inprogress, $this->user_id);

		$this->addField('hidden', 'year_tax_exemption', '', self::$field_group_hidden_inprogress, $inprogress_year);

		// $field_group_hidden_next
		$this->addField('hidden', 'action', '', self::$field_group_hidden_next, self::$name);

		$this->addField('hidden', 'user_id', '', self::$field_group_hidden_next, $this->user_id);

		$this->addField('hidden', 'year_tax_exemption', '', self::$field_group_hidden_next, $next_year);

		// $field_group_create
		$this->addField('hidden', 'real-action', '', self::$field_group_create, 'create');

		// $field_group_upload
		$this->addField('hidden', 'real-action', '', self::$field_group_upload_inprogress, 'upload');
		$this->addField('file', 'tax-exemption-file-inprogress', '', self::$field_group_upload_inprogress);

		// $field_group_upload
		$this->addField('hidden', 'real-action', '', self::$field_group_upload_next, 'upload');
		$this->addField('file', 'tax-exemption-file-next', '', self::$field_group_upload_next);
	}

	public function postForm() {
		parent::postForm();

		$feedback_success = array();
		$feedback_errors = array();

		$user_id = filter_input( INPUT_POST, 'user_id' );
		$action_posted = filter_input( INPUT_POST, 'real-action' );
		$year = filter_input( INPUT_POST, 'year_tax_exemption' );
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

			if ( $action_posted == 'create' ) {
				$ext = 'pdf';				// Création du fichier PDF correspondant
				$core = ATCF_CrowdFunding::instance();
				$core->include_control( 'templates/pdf/form-tax-exemption' );
				$user_name = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname();
				$user_address = $WDGUser->get_full_address_str(). ' ' .$WDGUser->get_postal_code( TRUE ). ' ' .$WDGUser->get_city();
				$form_ip_address = $_SERVER[ 'REMOTE_ADDR' ];
				$form_date = $date_today->format( 'd/m/Y' );// TODO à changer suivant l'année ?
				$html_content = WDG_Template_PDF_Form_Tax_Exemption::get( $user_name, $user_address, $form_ip_address, $form_date, $year);

				$crowdfunding = ATCF_CrowdFunding::instance();
				$crowdfunding->include_html2pdf();
				$html2pdf = new HTML2PDF( 'P', 'A4', 'fr', true, 'UTF-8', array(12, 5, 15, 8) );
				$html2pdf->WriteHTML( urldecode( $html_content ) );
				$html2pdf->Output( $filepath.'.'.$ext, 'F' );
			}

			if ( $action_posted == 'upload' ) {
				// renommage du fichier uploadé et déplacement dans le bon dossier
				$file_name = FALSE;
				if ( isset( $_FILES[ 'tax-exemption-file-next' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'tax-exemption-file-next' ][ 'tmp_name' ] )) {
					$file_name = $_FILES[ 'tax-exemption-file-next' ]['name'];
					$file_size = $_FILES[ 'tax-exemption-file-next' ]['size'];
					$file_to_move = $_FILES[ 'tax-exemption-file-next' ][ 'tmp_name' ];
				} elseif (isset( $_FILES[ 'tax-exemption-file-inprogress' ][ 'tmp_name' ] ) && !empty( $_FILES[ 'tax-exemption-file-inprogress' ][ 'tmp_name' ] )) {
					$file_name = $_FILES[ 'tax-exemption-file-inprogress' ]['name'];
					$file_size = $_FILES[ 'tax-exemption-file-inprogress' ]['size'];
					$file_to_move = $_FILES[ 'tax-exemption-file-inprogress' ][ 'tmp_name' ];
				}

				if ( $file_name != FALSE ) {
					$file_name_exploded = explode('.', $file_name);
					$ext = $file_name_exploded[count($file_name_exploded) - 1];

					// TODO : prendre le bon tableau des formats ?
					$good_file = TRUE;
					if ( !in_array( strtolower( $ext ), WDGKYCFile::$authorized_format_list ) ) {
						$good_file = FALSE;
						$error = array(
							'code'		=> 'format',
							'text'		=> __( 'common.forms.error.FILE_WRONG_FORMAT', 'yproject' ),
							'element'	=> 'format'
						);
						array_push( $feedback_errors, $error );
					}
					if ( ($file_size / 1024) / 1024 > 6 ) {
						$good_file = FALSE;
						$error = array(
							'code'		=> 'size',
							'text'		=> __( 'common.forms.error.FILE_TOO_BIG', 'yproject' ),
							'element'	=> 'size'
						);
						array_push( $feedback_errors, $error );
					}

					if ( $good_file ) {
						// si pas d'erreur, on renomme et déplace le fichier
						move_uploaded_file( $file_to_move, $filepath.'.'.$ext  );
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
