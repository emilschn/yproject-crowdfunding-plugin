<?php
class WDG_Form_Organization_Details extends WDG_Form {
	public static $name = 'organization-details';

	public static $field_group_hidden = 'organization-details-hidden';
	public static $field_group_complete = 'organization-details-complete';
	public static $field_group_dashboard = 'organization-details-dashboard';
	public static $field_group_address = 'organization-details-address';
	public static $field_group_accountant = 'organization-details-accountant';
	public static $field_group_admin = 'organization-details-admin';

	private $organization_id;

	public function __construct($organization_id = FALSE) {
		parent::__construct( self::$name );
		$this->organization_id = $organization_id;
		$this->initFields();
	}

	protected function initFields() {
		parent::initFields();

		$WDGOrganization = new WDGOrganization( $this->organization_id );

		// $field_group_hidden
		/*$this->addField(
			'hidden',
			'action',
			'',
			self::$field_group_hidden,
			self::$name
		);*/

		$this->addField('hidden', 'organization_id', '', self::$field_group_hidden, $this->organization_id);

		// $field_group_complete
		$this->addField('text', 'name', __( 'form.organization-details.NAME', 'yproject' ) . ' *', self::$field_group_complete, $WDGOrganization->get_name());

		$this->addField('text', 'email', __( 'form.organization-details.EMAIL', 'yproject' ) . ' *', self::$field_group_complete, $WDGOrganization->get_email(), FALSE, 'email');

		$this->addField('text', 'idnumber', __( 'form.organization-details.ID_NUMBER', 'yproject' ) . ' *', self::$field_group_complete, $WDGOrganization->get_idnumber());

		$this->addField('text', 'description', __( 'form.organization-details.ACTIVITY', 'yproject' ) . ' *', self::$field_group_complete, $WDGOrganization->get_description());

		$this->addField('text', 'website', __( 'form.organization-details.WEBSITE', 'yproject' ) . ' *', self::$field_group_complete, $WDGOrganization->get_website(), __( 'form.organization-details.WEBSITE_DESCRIPTION', 'yproject' ));

		$this->addField('text', 'representative_function', __( 'form.organization-details.ORGANIZATION_MANAGER_TITLE', 'yproject' ), self::$field_group_complete, $WDGOrganization->get_representative_function());

		$this->addField('text', 'legalform', __( 'form.organization-details.LEGAL_FORM', 'yproject' ) . ' *', self::$field_group_complete, $WDGOrganization->get_legalform());

		$this->addField('text', 'rcs', __( 'form.organization-details.CITY', 'yproject' ) . ' *', self::$field_group_complete, $WDGOrganization->get_rcs(), __( 'form.organization-details.CITY_DESCRIPTION', 'yproject' ));

		$this->addField('text-money', 'capital', __( 'form.organization-details.SHARE_CAPITAL', 'yproject' ) . ' *', self::$field_group_complete, $WDGOrganization->get_capital());

		$this->addField('text', 'ape', __( 'form.organization-details.APE', 'yproject' ), self::$field_group_complete, $WDGOrganization->get_ape());

		$this->addField('text', 'vat', __( 'form.organization-details.VAT_NUMBER', 'yproject' ), self::$field_group_complete, $WDGOrganization->get_vat());

		$months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
		$months_list = array();
		$count_months = count( $months );
		for ( $i = 0; $i < $count_months; $i++ ) {
			$months_list[ $i + 1 ] = __( $months[ $i ] );
		}
		$this->addField('select', 'fiscal_year_end_month', __( 'form.organization-details.FISCAL_YEAR_END_MONTH', 'yproject' ), self::$field_group_dashboard, $WDGOrganization->get_fiscal_year_end_month(), FALSE, $months_list);

		$this->addField('number', 'employees_count', __( 'form.organization-details.EMPLOYEES_COUNT', 'yproject' ), self::$field_group_dashboard, $WDGOrganization->get_employees_count());

		//$field_group_address
		$this->addField('text', 'address_number', __( 'form.user-details.ADDRESS_NUMBER', 'yproject' ), self::$field_group_address, $WDGOrganization->get_address_number());

		global $address_number_complements;
		$this->addField('select', 'address_number_comp', __( 'form.user-details.ADDRESS_NUMBER_COMPLEMENT', 'yproject' ), self::$field_group_address, $WDGOrganization->get_address_number_comp(), FALSE, $address_number_complements);

		$this->addField('text', 'address', __( 'form.user-details.ADDRESS', 'yproject' ) . ' *', self::$field_group_address, $WDGOrganization->get_address());

		$this->addField('text', 'postal_code', __( 'form.user-details.ZIP_CODE', 'yproject' ) . ' *', self::$field_group_address, $WDGOrganization->get_postal_code());

		$this->addField('text', 'city', __( 'form.user-details.CITY', 'yproject' ) . ' *', self::$field_group_address, $WDGOrganization->get_city());

		global $country_list;
		$this->addField('select', 'nationality', __( 'form.user-details.COUNTRY', 'yproject' ) . ' *', self::$field_group_address, $WDGOrganization->get_nationality(), FALSE, $country_list);

		// Données Comptable
		$this->addField('text', 'org_accountant_name', __( 'form.organization-details.NAME', 'yproject' ) . ' *', self::$field_group_accountant, $WDGOrganization->get_accountant_name());

		$this->addField('text', 'org_accountant_email', __( 'form.organization-details.EMAIL', 'yproject' ) . ' *', self::$field_group_accountant, $WDGOrganization->get_accountant_email());

		$this->addField('text', 'org_accountant_address', __( 'account.parameters.orga.HEAD_OFFICE', 'yproject' ) . ' *', self::$field_group_accountant, $WDGOrganization->get_accountant_address());

		$this->addField('text', 'org_id_quickbooks', __( "ID Quickbooks", 'yproject' ), self::$field_group_admin, $WDGOrganization->get_id_quickbooks());
	}

	public function postForm($skip_wallet = FALSE) {
		parent::postForm();

		$feedback_success = array();
		$feedback_errors = array();

		$organization_id = filter_input( INPUT_POST, 'organization_id' );
		$WDGOrganization = new WDGOrganization( $organization_id );
		$WDGUser_current = WDGUser::current();

		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		// Sécurité, ne devrait pas arriver non plus
		} else {
			if ( !$WDGUser_current->can_edit_organization( $organization_id ) ) {
		// Analyse du formulaire
			} else {
				// Informations de base
				$email = $this->getInputText( 'email' );
				if ( $email != $WDGOrganization->get_email() ) {
					if ( !is_email( $email ) || email_exists( $email ) ) {
						$error = array(
						'code'		=> 'email',
						'text'		=> __( 'form.user-details.EMAIL_ADDRESS_NOT_OK', 'yproject' ),
						'element'	=> 'email'
					);
						array_push( $feedback_errors, $error );
					} else {
						$WDGOrganization->set_email( $email );
					}
				}

				$name = $this->getInputText( 'name' );
				if ( !empty( $name ) ) {
					$WDGOrganization->set_name( $name );
				} else {
					$error = array(
					'code'		=> 'name',
					'text'		=> __( 'form.organization-details.error.NAME', 'yproject' ),
					'element'	=> 'name'
				);
					array_push( $feedback_errors, $error );
				}

				$idnumber = $this->getInputText( 'idnumber' );
				if ( !empty( $idnumber ) ) {
					$WDGOrganization->set_idnumber( $idnumber );
				}

				$description = $this->getInputText( 'description' );
				if ( !empty( $description ) ) {
					$WDGOrganization->set_description( $description );
				} else {
					$error = array(
					'code'		=> 'description',
					'text'		=> __( "La description de l'activit&eacute; doit &ecirc;tre renseign&eacute;e.", 'yproject' ),
					'element'	=> 'description'
				);
					array_push( $feedback_errors, $error );
				}

				$website = $this->getInputText( 'website' );
				if ( !empty( $website ) ) {
					$WDGOrganization->set_website( $website );
				} else {
					$error = array(
					'code'		=> 'website',
					'text'		=> __( "Le site web doit &ecirc;tre renseign&eacute; (si impossible, mettre l'adresse sur societe.com).", 'yproject' ),
					'element'	=> 'website'
				);
					array_push( $feedback_errors, $error );
				}

				$representative_function = $this->getInputText( 'representative_function' );
				if ( !empty( $representative_function ) ) {
					$WDGOrganization->set_representative_function( $representative_function );
				}

				$legalform = $this->getInputText( 'legalform' );
				if ( !empty( $legalform ) ) {
					$WDGOrganization->set_legalform( $legalform );
				}

				$rcs = $this->getInputText( 'rcs' );
				if ( !empty( $rcs ) || WDGRESTAPI_Lib_Validator::is_rcs( $rcs ) ) {
					$WDGOrganization->set_rcs( $rcs );
				} else {
					$error = array(
					'code'		=> 'rcs',
					'text'		=> __( 'form.organization-details.error.RCS', 'yproject' ),
					'element'	=> 'rcs'
				);
					array_push( $feedback_errors, $error );
				}

				$capital = $this->getInputTextMoney( 'capital' );
				if ( !empty( $capital ) && ( $capital === '0' || is_numeric( $capital ) ) ) {
					$WDGOrganization->set_capital( $capital );
				} else {
					$error = array(
					'code'		=> 'capital',
					'text'		=> __( 'form.organization-details.error.CAPITAL', 'yproject' ),
					'element'	=> 'capital'
				);
					array_push( $feedback_errors, $error );
				}

				$ape = $this->getInputText( 'ape' );
				if ( !empty( $ape ) ) {
					$WDGOrganization->set_ape( $ape );
				}

				$vat = $this->getInputText( 'vat' );
				if ( !empty( $vat ) ) {
					$WDGOrganization->set_vat( $vat );
				}

				$fiscal_year_end_month = $this->getInputText( 'fiscal_year_end_month' );
				if ( !empty( $fiscal_year_end_month ) ) {
					$WDGOrganization->set_fiscal_year_end_month( $fiscal_year_end_month );
				}

				$employees_count = $this->getInputText( 'employees_count' );
				if ( !empty( $employees_count ) ) {
					$WDGOrganization->set_employees_count( $employees_count );
				}

				$address_number = $this->getInputText( 'address_number' );
				if ( !empty( $address_number ) && is_numeric( $address_number ) ) {
					$WDGOrganization->set_address_number( $address_number );
				} else {
					$error = array(
					'code'		=> 'address_number',
					'text'		=> __( 'form.organization-details.error.ADDRESS_NUMBER', 'yproject' ),
					'element'	=> 'address_number'
				);
					array_push( $feedback_errors, $error );
				}

				$address_number_comp = $this->getInputText( 'address_number_comp' );
				if ( !empty( $address_number_comp ) ) {
					$WDGOrganization->set_address_number_comp( $address_number_comp );
				}

				$address = $this->getInputText( 'address' );
				if ( !empty( $address ) ) {
					$WDGOrganization->set_address( $address );
				}

				$postal_code = $this->getInputText( 'postal_code' );
				if ( !empty( $postal_code ) && is_numeric( $postal_code ) ) {
					$WDGOrganization->set_postal_code( $postal_code );
				} else {
					$error = array(
					'code'		=> 'postal_code',
					'text'		=> __( 'form.organization-details.error.ZIP_CODE', 'yproject' ),
					'element'	=> 'postal_code'
				);
					array_push( $feedback_errors, $error );
				}

				$city = $this->getInputText( 'city' );
				if ( !empty( $city ) ) {
					$WDGOrganization->set_city( $city );
				}

				$nationality = $this->getInputText( 'nationality' );
				if ( !empty( $nationality ) ) {
					$WDGOrganization->set_nationality( $nationality );
				}

				// Données du comptable
				$org_accountant_name = $this->getInputText( 'org_accountant_name' );
				if ( !empty( $org_accountant_name ) ) {
					$WDGOrganization->set_accountant_name( $org_accountant_name );
				}
				$org_accountant_email = $this->getInputText( 'org_accountant_email' );
				if ( !empty( $org_accountant_email ) ) {
					$WDGOrganization->set_accountant_email( $org_accountant_email );
				}
				$org_accountant_address = $this->getInputText( 'org_accountant_address' );
				if ( !empty( $org_accountant_address ) ) {
					$WDGOrganization->set_accountant_address( $org_accountant_address );
				}

				// Référence Quickbooks
				if ( $WDGUser_current->is_admin() ) {
					$id_quickbooks = $this->getInputText( 'org_id_quickbooks' );
					if ( !empty( $id_quickbooks ) ) {
						$WDGOrganization->set_id_quickbooks( $id_quickbooks );
					}
				}

				$WDGOrganization->save();

				if ( !$skip_wallet ) {
					$was_registered = $WDGOrganization->has_lemonway_wallet();
					if ( $WDGOrganization->can_register_lemonway() ) {
						ypcf_debug_log( 'WDG_Form_Organization_Details::postForm > $WDGOrganization->register_lemonway();' );
						$WDGOrganization->register_lemonway();
						// Si il n'était enregistré sur LW et qu'on vient de l'enregistrer, on envoie les documents si certains étaient déjà remplis
						if ( !$was_registered && $WDGOrganization->has_lemonway_wallet() ) {
							ypcf_debug_log( 'WDG_Form_Organization_Details::postForm > $WDGOrganization->send_kyc();' );
							$WDGOrganization->send_kyc();

							// Si des infos bancaires avaient déjà été enregistrées, on les envoie à LW
							if ( $WDGOrganization->has_saved_iban() ) {
								$bank_holdername = $WDGOrganization->get_bank_owner();
								$bank_iban = $WDGOrganization->get_bank_iban();
								$bank_bic = $WDGOrganization->get_bank_bic();
								$bank_address = $WDGOrganization->get_bank_address();
								$bank_address2 = $WDGOrganization->get_bank_address2();
								LemonwayLib::wallet_register_iban( $WDGOrganization->get_lemonway_id(), $bank_holdername, $bank_iban, $bank_bic, $bank_address, $bank_address2 );
							}
						}
					}
				}
			}
		}

		if ( empty( $feedback_errors ) ) {
			$feedback_success = array();
			array_push( $feedback_success, __( "Informations de l'organisation enregistr&eacute;es avec succ&egrave;s", 'yproject' ) );
		}
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);

		$this->initFields(); // Reinit pour avoir les bonnes valeurs

		return $buffer;
	}

	public function postFormAjax() {
		$buffer = $this->postForm();
		echo json_encode( $buffer );
		exit();
	}
}
