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
			__( "Je souhaite investir *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_type,
			FALSE,
			FALSE,
			[
				'user'	=> __( "En mon nom (personne physique)", 'yproject' ),
				'orga'	=> __( "En tant qu'organisation (personne morale)", 'yproject' )
			]
		);
		
		//**********************************************************************
		// Données de l'investisseur : $field_group_user_info
		$this->addField(
			'select',
			'gender',
			__( "Vous &ecirc;tes *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_gender(),
			FALSE,
			[
				'female'	=> __( "une femme", 'yproject' ),
				'male'		=> __( "un homme", 'yproject' )
			]
		);

		$this->addField(
			'text',
			'email',
			__( "E-mail *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_email(),
			FALSE,
			'email'
		);
		
		$this->addField(
			'text',
			'firstname',
			__( "Pr&eacute;nom *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_firstname()
		);
		
		$this->addField(
			'text',
			'lastname',
			__( "Nom *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_lastname()
		);
		
		$this->addField(
			'text',
			'use_lastname',
			__( "Nom d'usage", 'yproject' ),
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
			__( "Date de naissance *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$lemonway_birthdate
		);

		$this->addField(
			'text',
			'birthplace',
			__( "Ville de naissance *", 'yproject' ),
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
			__( "Arrondissement dans la ville de naissance", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_birthplace_district(),
			__( "Uniquement si la naissance a eu lieu &agrave; Paris, Marseille ou Lyon", 'yproject' ),
			$district_list
		);
		
		global $french_departments;
		$this->addField(
			'select',
			'birthplace_department',
			__( "D&eacute;partement de naissance *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_birthplace_department(),
			FALSE,
			$french_departments
		);

		global $country_list;
		$this->addField(
			'select',
			'birthplace_country',
			__( "Pays de naissance *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_birthplace_country(),
			FALSE,
			$country_list
		);
		
		$this->addField(
			'select',
			'nationality',
			__( "Nationalit&eacute; *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_nationality(),
			FALSE,
			$country_list
		);
			
		$this->addField(
			'text',
			'address_number',
			__( "Num&eacute;ro", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_address_number()
		);

		global $address_number_complements;
		$this->addField(
			'select',
			'address_number_complement',
			__( "Compl&eacute;ment de num&eacute;ro", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_address_number_complement(),
			FALSE,
			$address_number_complements
		);

		$this->addField(
			'text',
			'address',
			__( "Adresse *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_address()
		);

		$this->addField(
			'text',
			'postal_code',
			__( "Code postal *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_postal_code()
		);

		$this->addField(
			'text',
			'city',
			__( "Ville *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_city()
		);

		$this->addField(
			'select',
			'country',
			__( "Pays *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_country( 'iso2' ),
			FALSE,
			$country_list
		);
			
		$this->addField(
			'select',
			'tax_country',
			__( "R&eacute;sidence fiscale *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_tax_country( 'iso2' ),
			FALSE,
			$country_list
		);

		$this->addField(
			'text',
			'phone_number',
			__( "Num&eacute;ro de t&eacute;l&eacute;phone", 'yproject' ),
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
				'confirm-info' => __( "Je d&eacute;clare que ces informations sont exactes" )
			]
		);
		
		//**********************************************************************
		// Sélection de l'orga : $field_group_orga_select
		$organization_list = array();
		$organization_list[ '' ] = '';
		$organization_list[ 'new-orga' ] = __( "Une nouvelle organisation", 'yproject' );
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
			__( "Au nom de", 'yproject' ),
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
			__( "D&eacute;nomination sociale *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_email',
			__( "E-mail de contact *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info,
			FALSE,
			__( "Cette adresse doit &ecirc;tre diff&eacute;rente de celle de votre compte.", 'yproject' )
		);
		
		$this->addField(
			'text',
			'org_website',
			__( "Site internet *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info,
			FALSE
		);
		
		$this->addField(
			'text',
			'org_legalform',
			__( "Forme juridique *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_idnumber',
			__( "Num&eacute;ro SIRET *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_rcs',
			__( "RCS (Ville) *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_capital',
			__( "Capital social (en euros) *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_address_number',
			__( "Num&eacute;ro *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);

		global $address_number_complements;
		$this->addField(
			'select',
			'org_address_number_comp',
			__( "Compl&eacute;ment de num&eacute;ro", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info,
			'',
			FALSE,
			$address_number_complements
		);
			
		
		$this->addField(
			'text',
			'org_address',
			__( "Adresse *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_postal_code',
			__( "Code postal *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_city',
			__( "Ville *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);

		global $country_list;
		$this->addField(
			'select',
			'org_nationality',
			__( "Pays *", 'yproject' ),
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
				'org-capable' => __( "Je d&eacute;clare &ecirc;tre en capacit&eacute; de repr&eacute;senter cette organisation" )
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
			$email = $this->getInputText( 'email' );
			if ( !is_email( $email ) ) {
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
			
			$firstname = $this->getInputText( 'firstname' );
			if ( empty( $firstname ) ) {
				$this->addPostError(
					'firstname',
					__( "Votre pr&eacute;nom n'a pas &eacute;t&eacute; renseign&eacute;.", 'yproject' ),
					'firstname'
				);
			} elseif ( strlen( $firstname ) < 3 ) {
				$this->addPostError(
					'firstname',
					__( "Votre pr&eacute;nom doit faire plus de 2 caract&egrave;res.", 'yproject' ),
					'firstname'
				);
			}
			
			$lastname = $this->getInputText( 'lastname' );
			if ( empty( $lastname ) ) {
				$this->addPostError(
					'lastname',
					__( "Votre nom n'a pas &eacute;t&eacute; renseign&eacute;.", 'yproject' ),
					'lastname'
				);
				
			} elseif ( strlen( $lastname ) < 3 ) {
				$this->addPostError(
					'lastname',
					__( "Votre nom doit faire plus de 2 caract&egrave;res.", 'yproject' ),
					'lastname'
				);
			}
			
			if ( !$this->hasErrors() ) {
			
				$use_lastname = $this->getInputText( 'use_lastname' );
				$gender = $this->getInputText( 'gender' );
				$birthday = $this->getInputText( 'birthday' );
				$birthdate = DateTime::createFromFormat( 'd/m/Y', $birthday );
				$birthplace = $this->getInputText( 'birthplace' );
				$birthplace_district = $this->getInputText( 'birthplace_district' );
				$birthplace_department = $this->getInputText( 'birthplace_department' );
				$birthplace_country = $this->getInputText( 'birthplace_country' );
				$nationality = $this->getInputText( 'nationality' );
				$address_number = $this->getInputText( 'address_number' );
				$address_number_comp = $this->getInputText( 'address_number_comp' );
				$address = $this->getInputText( 'address' );
				$postal_code = $this->getInputText( 'postal_code' );
				$city = $this->getInputText( 'city' );
				$country = $this->getInputText( 'country' );
				$tax_country = $this->getInputText( 'tax_country' );
				$phone_number = $this->getInputText( 'phone_number' );
			
				$WDGUser->save_data(
					$email, $gender, $firstname, $lastname, $use_lastname,
					$birthdate->format('d'), $birthdate->format('m'), $birthdate->format('Y'),
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
					__( "Vous n'avez pas d&eacute;clar&eacute; vos informations comme exactes.", 'yproject' ),
					'confirm-info'
				);
			}
			
			// Choix du type d'investisseur
			$user_type = $this->getInputText( 'user-type' );
			$_SESSION[ 'user_type' ] = $user_type;
			if ( empty( $user_type ) ) {
				$this->addPostError(
					'user-type-select',
					__( "Vous devez choisir en quel nom vous souhaitez investir.", 'yproject' ),
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
				__( "Vous n'avez pas choisi l'organisation au nom de laquelle vous souhaitez investir.", 'yproject' ),
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
					__( "Vous n'avez pas d&eacute;clar&eacute; &ecirc;tre en mesure de repr&eacute;senter l'organisation.", 'yproject' ),
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
				__( "Vous ne pouvez pas investir avec l'organisation qui porte le projet.", 'yproject' ),
				'orga-id'
			);
		}
		
		$org_name = $this->getInputText( 'org_name' );
		if ( empty( $org_name ) ) {
			$needs_update_organization = TRUE;
			$buffer = FALSE;
			$this->addPostError(
				'org-name-empty',
				__( "Le nom de l'organisation doit &ecirc;tre renseign&eacute;.", 'yproject' ),
				'org_name'
			);
		}
		
		$org_email = $this->getInputText( 'org_email' );
		if ( !is_email( $org_email ) ) {
			$needs_update_organization = TRUE;
			$buffer = FALSE;
			$this->addPostError(
				'email',
				__( "L'adresse e-mail de l'organisation n'est pas valide.", 'yproject' ),
				'email'
			);
		}
		
		$org_website = $this->getInputText( 'org_website' );
		if ( empty( $org_website ) ) {
			$needs_update_organization = TRUE;
			$buffer = FALSE;
			$this->addPostError(
				'website-empty',
				__( "Le site web de l'organisation n'est pas d&eacute;fini.", 'yproject' ),
				'org_website'
			);
		}
		
		if ( ( $orga_id == 'new-orga' || ( $WDGOrganization != FALSE && $WDGOrganization->get_email() != $org_email ) ) && email_exists( $org_email ) ) {
			$needs_update_organization = TRUE;
			$buffer = FALSE;
			$this->addPostError(
				'email',
				__( "L'adresse e-mail de l'organisation est d&eacute;j&agrave; utilis&eacute;e.", 'yproject' ),
				'email'
			);
		}

		$org_capital = $this->getInputTextMoney( 'org_capital' );
		$org_capital = filter_var( $org_capital, FILTER_VALIDATE_INT );
		if ( $org_capital === FALSE ) {
			$needs_update_organization = TRUE;
			$this->addPostError(
				'capital-not-integer',
				__( "Le capital de l'organisation doit &ecirc;tre un nombre entier.", 'yproject' ),
				'capital'
			);
		}
		
		$org_postal_code = $this->getInputText( 'org_postal_code' );
		$org_postal_code = filter_var( $org_postal_code, FILTER_VALIDATE_INT );
		if ( $org_postal_code === FALSE ) {
			$needs_update_organization = TRUE;
			$this->addPostError(
				'postalcode-not-integer',
				__( "Le code postal de l'organisation doit &ecirc;tre un nombre entier.", 'yproject' ),
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
					__( "Cette adresse e-mail d'organisation n'est pas valide.", 'yproject' ),
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
