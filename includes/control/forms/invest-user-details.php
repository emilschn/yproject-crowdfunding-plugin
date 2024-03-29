<?php
class WDG_Form_Invest_User_Details extends WDG_Form {
	
	public static $name = 'project-invest-user-details';
	
	public static $field_group_hidden = 'invest-hidden';
	public static $field_group_user_type = 'invest-user-type';
	public static $field_group_user_info = 'invest-user-info';
	public static $field_group_orga_select = 'invest-orga-select';
	public static $field_group_confirm = 'invest-confirm-info';
	public static $field_group_orga_info = 'invest-orga-info';
	public static $field_group_orga_info_new = 'invest-orga-info-new';
	
	private $campaign_id;
	private $user_id;
	
	public function __construct( $campaign_id = FALSE, $user_id = FALSE ) {
		parent::__construct( WDG_Form_Invest_User_Details::$name );
		$this->campaign_id = $campaign_id;
		$this->user_id = $user_id;
		$this->initFields();
	}
	
	protected function initFields() {
		ypcf_session_start();
		parent::initFields();
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$campaign_organization = $campaign->get_organization();
		$WDGUser = new WDGUser( $this->user_id );
		
		//**********************************************************************
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			WDG_Form_Invest_User_Details::$field_group_hidden,
			WDG_Form_Invest_User_Details::$name
		);

		$this->addField(
			'hidden',
			'user-type-select',
			'',
			WDG_Form_Invest_User_Details::$field_group_hidden,
			$_SESSION[ 'user_type' ]
		);
		
		//**********************************************************************
		// Données de l'investisseur : $field_group_user_type
		$this->addField(
			'radio',
			'user-type',
			__( 'form.invest-input.I_WISH_TO_INVEST', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_type,
			FALSE,
			FALSE,
			[
				'user'	=> __( 'form.invest-user-details.IN_MY_NAME', 'yproject' ),
				'orga'	=> __( 'form.invest-user-details.AS_ORGANIZATION', 'yproject' )
			]
		);
		
		//**********************************************************************
		// Données de l'investisseur : $field_group_user_info
		$this->addField(
			'select',
			'gender',
			__( 'form.user-details.YOU_ARE', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_gender(),
			FALSE,
			[
				'female'	=> __( 'form.user-details.A_WOMAN', 'yproject' ),
				'male'		=> __( 'form.user-details.A_MAN', 'yproject' )
			]
		);

		// Le champ e-mail est masqué par défaut, sauf si l'e-mail de l'utilisateur est vide
		$email_field_type = 'not-editable';
		$email_field_init_value = $WDGUser->get_email();
		if ( empty( $email_field_init_value ) ) {
			$email_field_type = 'text';
		}
		$this->addField(
			$email_field_type,
			'email',
			__( 'form.user-details.EMAIL', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$email_field_init_value,
			FALSE,
			'email'
		);
		
		$this->addField(
			'text',
			'firstname',
			__( 'form.user-details.FIRSTNAME', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_firstname()
		);
		
		$this->addField(
			'text',
			'lastname',
			__( 'form.user-details.LASTNAME', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_lastname()
		);
		
		$this->addField(
			'text',
			'use_lastname',
			__( 'form.user-details.USENAME', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_use_lastname()
		);

		$lemonway_birthdate = $WDGUser->get_lemonway_birthdate();
		if ( $lemonway_birthdate == '0/0/0' ) {
			$lemonway_birthdate = '00/00/0000';
		}
		$this->addField(
			'date',
			'birthday',
			__( 'form.user-details.BIRTH_DATE', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$lemonway_birthdate
		);

		$this->addField(
			'text',
			'birthplace',
			__( 'form.user-details.BIRTH_PLACE', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_birthplace()
		);

		$district_list = array();
		$district_list[ 0 ] = '-';
		for ( $i = 1; $i <= 20; $i++ ) {
			$district_list[ $i ] = $i;
		}
		$this->addField(
			'select',
			'birthplace_district',
			__( 'form.user-details.BIRTH_PLACE_DISTRICT', 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_birthplace_district(),
			__( 'form.user-details.BIRTH_PLACE_DISTRICT_DESCRIPTION', 'yproject' ),
			$district_list
		);
		
		global $french_departments;
		$this->addField(
			'select',
			'birthplace_department',
			__( 'form.user-details.BIRTH_PLACE_COUNTY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_birthplace_department(),
			__( 'form.user-details.BIRTH_PLACE_COUNTY_DESCRIPTION', 'yproject' ),
			$french_departments
		);

		global $country_list;
		$this->addField(
			'select',
			'birthplace_country',
			__( 'form.user-details.BIRTH_COUNTRY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_birthplace_country(),
			FALSE,
			$country_list
		);
		
		$this->addField(
			'select',
			'nationality',
			__( 'form.user-details.NATIONALITY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_nationality(),
			FALSE,
			$country_list
		);
			
		$this->addField(
			'text',
			'address_number',
			__( 'form.user-details.ADDRESS_NUMBER', 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_address_number()
		);

		global $address_number_complements;
		$this->addField(
			'select',
			'address_number_complement',
			__( 'form.user-details.ADDRESS_NUMBER_COMPLEMENT', 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_address_number_complement(),
			FALSE,
			$address_number_complements
		);

		$this->addField(
			'text',
			'address',
			__( 'form.user-details.ADDRESS', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_address()
		);

		$this->addField(
			'text',
			'postal_code',
			__( 'form.user-details.ZIP_CODE', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_postal_code()
		);

		$this->addField(
			'text',
			'city',
			__( 'form.user-details.CITY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_city()
		);

		$this->addField(
			'select',
			'country',
			__( 'form.user-details.COUNTRY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_country( 'iso2' ),
			FALSE,
			$country_list
		);
			
		$this->addField(
			'select',
			'tax_country',
			__( 'form.user-details.TAX_COUNTRY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_tax_country( 'iso2' ),
			FALSE,
			$country_list
		);

		$this->addField(
			'text',
			'phone_number',
			__( 'form.user-details.PHONE', 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_phone_number()
		);
		
		//**********************************************************************
		// Validation des informations : $field_group_confirm
		$this->addField(
			'checkboxes',
			'',
			'',
			WDG_Form_Invest_User_Details::$field_group_confirm,
			FALSE,
			FALSE,
			[
				'confirm-info' => __( 'form.invest-user-details.CORRECT_INFORMATION', 'yproject' )
			]
		);
		
		//**********************************************************************
		// Sélection de l'orga : $field_group_orga_select
		$organization_list = array();
		$organization_list[ '' ] = '';
		$organization_list[ 'new-orga' ] = __( 'form.invest-user-details.A_NEW_ORGANIZATION', 'yproject' );
		$user_orga_list = $WDGUser->get_organizations_list();
		array_unshift($user_orga_list, 'new-orga');
		foreach ( $user_orga_list as $organization_item ) {
			if ( $organization_item == 'new-orga' ){
				$this->initFieldsHiddenOrga('new-orga');
			} elseif( $campaign_organization->wpref != $organization_item->wpref ) {
				$organization_list[ $organization_item->wpref ] = $organization_item->name;
				$this->initFieldsHiddenOrga( $organization_item->wpref ); 
			} 
		}

		$this->addField(
			'select',
			'orga-id',
			__( 'form.invest-user-details.ORGANIZATION_SELECT', 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$_SESSION[ 'orga_id' ],
			FALSE,
			$organization_list
		);
		
		//**********************************************************************
		// Informations de l'orga : $field_group_orga_info
		$this->addField(
			'text',
			'org_name',
			__( 'form.organization-details.NAME', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_email',
			__( 'form.organization-details.EMAIL', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info,
			FALSE,
			__( 'form.organization-details.EMAIL_DESCRIPTION', 'yproject' )
		);
		
		$this->addField(
			'text',
			'org_website',
			__( 'form.organization-details.WEBSITE', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info,
			FALSE,
			__( 'form.organization-details.WEBSITE_DESCRIPTION', 'yproject' )
		);
		
		$this->addField(
			'text',
			'org_legalform',
			__( 'form.organization-details.LEGAL_FORM', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);

		$this->addField(
			'text',
			'org_description',
			__( 'form.organization-details.ACTIVITY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_idnumber',
			__( 'form.organization-details.ID_NUMBER', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_rcs',
			__( 'form.organization-details.CITY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info,
			FALSE,
			__( 'form.organization-details.CITY_DESCRIPTION', 'yproject' )
		);
		
		$this->addField(
			'text',
			'org_capital',
			__( 'form.organization-details.SHARE_CAPITAL', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_address_number',
			__( 'form.user-details.ADDRESS_NUMBER', 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);

		global $address_number_complements;
		$this->addField(
			'select',
			'org_address_number_comp',
			__( 'form.user-details.ADDRESS_NUMBER_COMPLEMENT', 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info,
			'',
			FALSE,
			$address_number_complements
		);
			
		
		$this->addField(
			'text',
			'org_address',
			__( 'form.user-details.ADDRESS', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_postal_code',
			__( 'form.user-details.ZIP_CODE', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_city',
			__( 'form.user-details.CITY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);

		global $country_list;
		$this->addField(
			'select',
			'org_nationality',
			__( 'form.user-details.COUNTRY', 'yproject' ) . ' *',
			WDG_Form_Invest_User_Details::$field_group_orga_info,
			FALSE,
			FALSE,
			$country_list
		);
		
		$this->addField(
			'checkboxes',
			'',
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_info_new,
			FALSE,
			FALSE,
			[
				'org-capable' => __( 'form.invest-user-details.CAPACITY_REPRESENT_ORGANIZATION', 'yproject' )
			]
		);
		
	}
	
	private function initFieldsHiddenOrga( $id_orga ) {
		$WDGOrganization = new WDGOrganization( $id_orga );
		
		if ( $id_orga == 'new-orga' ) {
			$org_name = ( $_SESSION[ 'org_name' ] == '' ) ? FALSE : $_SESSION[ 'org_name' ];
		} else {
			$org_name = $WDGOrganization->get_name();
		}
		$this->addField(
			'hidden',
			'org_init_name_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_name
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_email = ( $_SESSION[ 'org_email' ] == '' ) ? FALSE : $_SESSION[ 'org_email' ];
		} else {
			$org_email = $WDGOrganization->get_email();
		}
		$this->addField(
			'hidden',
			'org_init_email_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_email
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_website = ( $_SESSION[ 'org_website' ] == '' ) ? FALSE : $_SESSION[ 'org_website' ];
		} else {
			$org_website = $WDGOrganization->get_website();
		}
		$this->addField(
			'hidden',
			'org_init_website_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_website
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_legalform = ( $_SESSION[ 'org_legalform' ] == '' ) ? FALSE : $_SESSION[ 'org_legalform' ];
		} else {
			$org_legalform = $WDGOrganization->get_legalform();
		}
		$this->addField(
			'hidden',
			'org_init_legalform_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_legalform
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_description = ( $_SESSION[ 'org_description' ] == '' ) ? FALSE : $_SESSION[ 'org_description' ];
		} else {
			$org_description = $WDGOrganization->get_description();
		}
		$this->addField(
			'hidden',
			'org_init_description_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_description
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_idnumber = ( $_SESSION[ 'org_idnumber' ] == '' ) ? FALSE : $_SESSION[ 'org_idnumber' ];
		} else {
			$org_idnumber = $WDGOrganization->get_idnumber();
		}
		$this->addField(
			'hidden',
			'org_init_idnumber_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_idnumber
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_rcs = ( $_SESSION[ 'org_rcs' ] == '' ) ? FALSE : $_SESSION[ 'org_rcs' ];
		} else {
			$org_rcs = $WDGOrganization->get_rcs();
		}
		$this->addField(
			'hidden',
			'org_init_rcs_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_rcs
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_capital = ( $_SESSION[ 'org_capital' ] == '' ) ? FALSE : $_SESSION[ 'org_capital' ];
		} else {
			$org_capital = $WDGOrganization->get_capital();
		}
		$this->addField(
			'hidden',
			'org_init_capital_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_capital
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_address_number = ( $_SESSION[ 'org_address_number' ] == '' ) ? FALSE : $_SESSION[ 'org_address_number' ];
		} else {
			$org_address_number = $WDGOrganization->get_address_number();
		}
		$this->addField(
			'hidden',
			'org_init_address_number_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_address_number
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_address_number_comp = ( $_SESSION[ 'org_address_number_comp' ] == '' ) ? FALSE : $_SESSION[ 'org_address_number_comp' ];
		} else {
			$org_address_number_comp = $WDGOrganization->get_address_number_comp();
		}
		$this->addField(
			'hidden',
			'org_init_address_number_comp_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_address_number_comp
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_address = ( $_SESSION[ 'org_address' ] == '' ) ? FALSE : $_SESSION[ 'org_address' ];
		} else {
			$org_address = $WDGOrganization->get_address();
		}
		$this->addField(
			'hidden',
			'org_init_address_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_address
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_postal_code = ( $_SESSION[ 'org_postal_code' ] == '' ) ? FALSE : $_SESSION[ 'org_postal_code' ];
		} else {
			$org_postal_code = $WDGOrganization->get_postal_code();
		}
		$this->addField(
			'hidden',
			'org_init_postal_code_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_postal_code
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_city = ( $_SESSION[ 'org_city' ] == '' ) ? FALSE : $_SESSION[ 'org_city' ];
		} else {
			$org_city = $WDGOrganization->get_city();
		}
		$this->addField(
			'hidden',
			'org_init_city_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_city
		);
		
		if ( $id_orga == 'new-orga' ) {
			$org_nationality = ( $_SESSION[ 'org_nationality' ] == '' ) ? FALSE : $_SESSION[ 'org_nationality' ];
		} else {
			$org_nationality = $WDGOrganization->get_nationality();
		}
		$this->addField(
			'hidden',
			'org_init_nationality_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$org_nationality
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGUser = new WDGUser( $this->user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {

		// Analyse du formulaire
		} else {
			// Informations de base
			$email = $WDGUser->get_email();
			// On ne prend l'enregistrement que si il était vide à la base
			if ( empty( $email ) ) {
				$email = $this->getInputText( 'email' );
				if ( !is_email( $email )  || !WDGRESTAPI_Lib_Validator::is_email( $email )) {
					$this->addPostError(
						'email',
						__( "Cette adresse e-mail n'est pas valide.", 'yproject' ),
						'email'
					);
				}
				// Si l'utilisateur change d'e-mail et que celui-ci est déjà utilisé, on bloque
				if ( $WDGUser->get_email() != $email && email_exists( $email ) ) {
					$this->addPostError(
						'email',
						__( "Cette adresse e-mail est d&eacute;j&agrave; utilis&eacute;e.", 'yproject' ),
						'email'
					);
				}
			}
			
			$firstname = $this->getInputText( 'firstname' );
			if ( empty( $firstname ) || !WDGRESTAPI_Lib_Validator::is_name( $firstname )  ) {
				$this->addPostError(
					'firstname',
					__( 'form.user-details.FIRST_NAME_EMPTY', 'yproject' ),
					'firstname'
				);
			} elseif ( strlen( $firstname ) < 2 ) {
				$this->addPostError(
					'firstname',
					__( 'form.invest-user-details.error.FIRST_NAME_SIZE', 'yproject' ),
					'firstname'
				);
			}
			
			$lastname = $this->getInputText( 'lastname' );
			if ( empty( $lastname ) || !WDGRESTAPI_Lib_Validator::is_name( $lastname ) ) {
				$this->addPostError(
					'lastname',
					__( 'form.user-details.LAST_NAME_EMPTY', 'yproject' ),
					'lastname'
				);
				
			} elseif ( strlen( $lastname ) < 2 ) {
				$this->addPostError(
					'lastname',
					__( 'form.invest-user-details.error.LAST_NAME_SIZE', 'yproject' ),
					'lastname'
				);
			}
			
			if ( !$this->hasErrors() ) {
			
				$use_lastname = $this->getInputText( 'use_lastname' );
				$gender = $this->getInputText( 'gender' );
				$birthday = $this->getInputText( 'birthday' );
				$birthdate = DateTime::createFromFormat( 'd/m/Y', $birthday );
				$birthplace = $this->getInputText( 'birthplace' );
				if ( empty( $birthplace ) || !WDGRESTAPI_Lib_Validator::is_name( $birthplace ) ) {
					$this->addPostError(
						'birthplace',
						__( 'form.user-details.BIRTH_PLACE_ERROR', 'yproject' ),
						'birthplace'
					);
				}
				$birthplace_district = $this->getInputText( 'birthplace_district' );
				$birthplace_department = $this->getInputText( 'birthplace_department' );
				$birthplace_country = $this->getInputText( 'birthplace_country' );
				$nationality = $this->getInputText( 'nationality' );
				$address_number = $this->getInputText( 'address_number' );
				$address_number_comp = $this->getInputText( 'address_number_complement' );
				$address = $this->getInputText( 'address' );
				if ( empty( $address ) || !WDGRESTAPI_Lib_Validator::is_name( $address ) ) {
					$this->addPostError(
						'address',
						__( 'form.user-details.ADDRESS_ERROR', 'yproject' ),
						'address'
					);
				}
				$country = $this->getInputText( 'country' );
				
				$postal_code = $this->getInputText( 'postal_code' );
				if ( empty( $postal_code ) || !WDGRESTAPI_Lib_Validator::is_postalcode( $postal_code, $country ) ) {
					$this->addPostError(
						'postal_code',
						__( 'form.user-details.ZIP_CODE_ERROR', 'yproject' ),
						'postal_code'
					);
				}
				$city = $this->getInputText( 'city' );
				if ( empty( $city ) || !WDGRESTAPI_Lib_Validator::is_name( $city ) ) {
					$this->addPostError(
						'city',
						__( 'form.user-details.CITY_ERROR', 'yproject' ),
						'city'
					);
				}
				
				$tax_country = $this->getInputText( 'tax_country' );
				$phone_number = $this->getInputText( 'phone_number' );
				$birthdate_day = FALSE;
				$birthdate_month = FALSE;
				$birthdate_year = FALSE;
				if ( !empty( $birthdate ) ) {
					$birthdate_day = $birthdate->format('d');
					$birthdate_month = $birthdate->format('m');
					$birthdate_year = $birthdate->format('Y');
				}
			
				$WDGUser->save_data(
					$email, $gender, $firstname, $lastname, $use_lastname,
					$birthdate_day, $birthdate_month, $birthdate_year,
					$birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality,
					$address_number, $address_number_comp, $address, $postal_code, $city, $country, $tax_country, $phone_number
				);
				
			}
			
			// Vérifications non bloquantes pour l'enregistrement, mais bloquantes pour l'investissement
			if ( !$WDGUser->has_filled_invest_infos( $campaign->funding_type() ) ) {
				global $user_can_invest_errors;
				foreach ( $user_can_invest_errors as $invest_error ) {
					$this->addPostError(
						'user-invest-error',
						$invest_error,
						'general'
					);
				}
			}
				
			$confirm_info = $this->getInputChecked( 'confirm-info' );
			if ( !$confirm_info ) {
				$this->addPostError(
					'info-not-confirmed',
					__( 'form.invest-user-details.error.NOT_DECLARED_INFORMATION', 'yproject' ),
					'confirm-info'
				);
			}
			
			// Choix du type d'investisseur
			$user_type = $this->getInputText( 'user-type' );
			$_SESSION[ 'user_type' ] = $user_type;
			if ( empty( $user_type ) ) {
				$this->addPostError(
					'user-type-select',
					__( 'form.invest-user-details.error.CHOOSE_USER_TYPE', 'yproject' ),
					'user-type'
				);
			}
			
			// Si l'investissement est au nom d'une organisation, il faut vérifier tous les paramètres de l'organisation
			if ( $user_type == 'orga' ) {
				$user_type = $this->postFormOrganization();
			} else {
				$_SESSION[ 'orga_id' ] = FALSE;
			}
		}
		
		$this->initFields();
		
		$current_investment = WDGInvestment::current();
		$current_investment->update_session( FALSE, $user_type );
		
		return !$this->hasErrors();
	}
	
	private function postFormOrganization() {
		$orga_id = $this->getInputText( 'orga-id' );
		if ( empty( $orga_id ) ) {
			$this->addPostError(
				'orga-select',
				__( 'form.invest-user-details.error.ORGANIZATION_SELECTION', 'yproject' ),
				'orga-id'
			);
			
		} else {
			
			if ( $this->postFormOrganizationCheck() ) {
				if ( $orga_id == 'new-orga' ) {
					$orga_id = $this->postFormOrganizationNew();
				}
				if ( !empty( $orga_id ) && !$this->hasErrors() ) {
					$this->postFormOrganizationCommon( $orga_id );
				}
			}
			$_SESSION[ 'orga_id' ] = $orga_id;
		}
		
		return $orga_id;
	}
	
	private function postFormOrganizationCheck() {
		$buffer = TRUE;
		$needs_update_organization = FALSE;
		
		$WDGOrganization = FALSE;
		$orga_id = $this->getInputText( 'orga-id' );
		if ( $orga_id == 'new-orga' ) {
			$org_capable = $this->getInputChecked( 'org-capable' );
			if ( !$org_capable ) {
				$buffer = FALSE;
				$needs_update_organization = TRUE;
				$this->addPostError(
					'orga-new-capable',
					__( 'form.invest-user-details.error.ORGANIZATION_REPRESENT', 'yproject' ),
					'org-capable'
				);
			}
			
		} else {
			$WDGOrganization = new WDGOrganization( $orga_id );
		}
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$campaign_organization = $campaign->get_organization();
		if ( $orga_id == $campaign_organization->wpref ) {
			$buffer = FALSE;
			$this->addPostError(
				'orga-same-project',
				__( 'form.invest-user-details.error.SAME_ORGANIZATION_PROJECT', 'yproject' ),
				'orga-id'
			);
		}
		
		$org_name = $this->getInputText( 'org_name' );
		if ( empty( $org_name ) ) {
			$needs_update_organization = TRUE;
			$buffer = FALSE;
			$this->addPostError(
				'org-name-empty',
				__( 'form.organization-details.error.NAME', 'yproject' ),
				'org_name'
			);
		}
		
		$org_email = $this->getInputText( 'org_email' );
		if ( !is_email( $org_email ) ) {
			$needs_update_organization = TRUE;
			$buffer = FALSE;
			$this->addPostError(
				'email',
				__( 'form.invest-user-details.error.ORGANIZATION_EMAIL_NOT_OK', 'yproject' ),
				'email'
			);
		}
		
		$org_website = $this->getInputText( 'org_website' );
		if ( empty( $org_website ) ) {
			$needs_update_organization = TRUE;
			$buffer = FALSE;
			$this->addPostError(
				'website-empty',
				__( 'form.invest-user-details.error.ORGANIZATION_WEBSITE_EMPTY', 'yproject' ),
				'org_website'
			);
		}
		
		if ( ( $orga_id == 'new-orga' || ( $WDGOrganization != FALSE && $WDGOrganization->get_email() != $org_email ) ) && email_exists( $org_email ) ) {
			$needs_update_organization = TRUE;
			$buffer = FALSE;
			$this->addPostError(
				'email',
				__( 'form.invest-user-details.error.ORGANIZATION_EMAIL_ALREADY_USED', 'yproject' ),
				'email'
			);
		}

		$org_rcs = $this->getInputText( 'org_rcs' );
		if ( empty( $org_rcs ) || !WDGRESTAPI_Lib_Validator::is_rcs( $org_rcs ) ) {
					$this->addPostError(
						'org_rcs',
						__( 'form.organization-details.error.RCS', 'yproject' ),
						'org_rcs'
					);
				}

		$org_capital = $this->getInputTextMoney( 'org_capital' );
		$org_capital = filter_var( $org_capital, FILTER_VALIDATE_INT );
		if ( $org_capital === FALSE ) {
			$needs_update_organization = TRUE;
			$this->addPostError(
				'capital-not-integer',
				__( 'form.organization-details.error.CAPITAL', 'yproject' ),
				'capital'
			);
		}
		
		$country = $this->getInputText( 'country' );
		$org_postal_code = $this->getInputText( 'org_postal_code' );
		if ( empty( $org_postal_code ) || !WDGRESTAPI_Lib_Validator::is_postalcode( $org_postal_code, $country ) )  {
			$needs_update_organization = TRUE;
			$this->addPostError(
				'postalcode-not-integer',
				__( 'form.organization-details.error.ZIP_CODE', 'yproject' ),
				'postal_code'
			);
		}

		if ( $needs_update_organization ) {
			$this->update_session_organization();
		}

		return $buffer;
	}
	
	private function postFormOrganizationNew() {
		$WDGUser_Current = WDGUser::current();
		$org_name = $this->getInputText( 'org_name' );
		$org_email = $this->getInputText( 'org_email' );
		$WDGOrganization_created = WDGOrganization::createSimpleOrganization( $WDGUser_Current->get_wpref(), $org_name, $org_email );
		if ( $WDGOrganization_created !== FALSE ) {
			return $WDGOrganization_created->get_wpref();
		}
		return FALSE;
	}
	
	private function postFormOrganizationCommon( $id_organization ) {
		$WDGOrganization = new WDGOrganization( $id_organization );
		$org_name = $this->getInputText( 'org_name' );
		$WDGOrganization->set_name( $org_name );
		$org_email = $this->getInputText( 'org_email' );
		if ( $org_email != $WDGOrganization->get_email() ) {
			if ( !is_email( $org_email ) || email_exists( $org_email ) ) {
				$this->addPostError(
					'org-invest-error',
					__( 'form.invest-user-details.error.ORGANIZATION_EMAIL_NOT_OK', 'yproject' ),
					'general'
				);
			} else {
				$WDGOrganization->set_email( $org_email );
			}
		}
		$org_website = $this->getInputText( 'org_website' );
		$WDGOrganization->set_website( $org_website );
		$org_legalform = $this->getInputText( 'org_legalform' );
		$WDGOrganization->set_legalform( $org_legalform );
		$org_description = $this->getInputText( 'org_description' );
		$WDGOrganization->set_description( $org_description );
		$org_idnumber = $this->getInputText( 'org_idnumber' );
		$WDGOrganization->set_idnumber( $org_idnumber );
		$org_rcs = $this->getInputText( 'org_rcs' );
		$WDGOrganization->set_rcs( $org_rcs );
		$org_capital = $this->getInputTextMoney( 'org_capital' );
		$WDGOrganization->set_capital( $org_capital );
		$org_address_number = $this->getInputText( 'org_address_number' );
		$WDGOrganization->set_address_number( $org_address_number );
		$org_address_number_comp = $this->getInputText( 'org_address_number_comp' );
		$WDGOrganization->set_address_number_comp( $org_address_number_comp );
		$org_address = $this->getInputText( 'org_address' );
		$WDGOrganization->set_address( $org_address );
		$org_postal_code = $this->getInputText( 'org_postal_code' );
		$WDGOrganization->set_postal_code( $org_postal_code );
		$org_city = $this->getInputText( 'org_city' );
		$WDGOrganization->set_city( $org_city );
		$org_nationality = $this->getInputText( 'org_nationality' );
		$WDGOrganization->set_nationality( $org_nationality );
		$WDGOrganization->save();

		if ( !$WDGOrganization->has_filled_invest_infos() ) {
			global $organization_can_invest_errors;
			foreach ( $organization_can_invest_errors as $invest_error ) {
				$this->addPostError(
					'org-invest-error',
					$invest_error,
					'general'
				);
			}
		}
	}	

	private function update_session_organization() {
		ypcf_session_start();
		$_SESSION[ 'org_name' ] = $this->getInputText( 'org_name' );
		$_SESSION[ 'org_email' ] = $this->getInputText( 'org_email' );
		$_SESSION[ 'org_website' ] = $this->getInputText( 'org_website' );
		$_SESSION[ 'org_legalform' ] = $this->getInputText( 'org_legalform' );
		$_SESSION[ 'org_description' ] = $this->getInputText( 'org_description' );
		$_SESSION[ 'org_idnumber' ] = $this->getInputText( 'org_idnumber' );
		$_SESSION[ 'org_rcs' ] = $this->getInputText( 'org_rcs' );
		$_SESSION[ 'org_capital' ] = $this->getInputTextMoney( 'org_capital' );
		$_SESSION[ 'org_address_number' ] = $this->getInputText( 'org_address_number' );
		$_SESSION[ 'org_address_number_comp' ] = $this->getInputText( 'org_address_number_comp' );
		$_SESSION[ 'org_address' ] = $this->getInputText( 'org_address' );
		$_SESSION[ 'org_postal_code' ] = $this->getInputText( 'org_postal_code' );
		$_SESSION[ 'org_city' ] = $this->getInputText( 'org_city' );
		$_SESSION[ 'org_nationality' ] = $this->getInputText( 'org_nationality' );
	}
}
