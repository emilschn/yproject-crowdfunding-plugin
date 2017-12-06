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
		
		//**********************************************************************
		// Données de l'investisseur : $field_group_user_type
		$this->addField(
			'radio',
			'user-type',
			__( "Je souhaite investir", 'yproject' ),
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
			$WDGUser->wp_user->get( 'user_birthplace' )
		);

		global $country_list;
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
			'text',
			'country',
			__( "Pays *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_country()
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
		foreach ( $user_orga_list as $organization_item ) {
			if ( $campaign_organization->wpref != $organization_item->wpref ) {
				$organization_list[ $organization_item->wpref ] = $organization_item->name;
				$this->initFieldsHiddenOrga( $organization_item->wpref );
			}
		}
		$this->addField(
			'select',
			'orga-id',
			__( "Au nom de", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			FALSE,
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
			'org_legalform',
			__( "Forme juridique *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_idnumber',
			__( "Num&eacute;ro SIREN *", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_rcs',
			__( "RCS *", 'yproject' ),
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
		
		$this->addField(
			'hidden',
			'org_init_name_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_name()
		);
		
		$this->addField(
			'hidden',
			'org_init_email_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_email()
		);
		
		$this->addField(
			'hidden',
			'org_init_legalform_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_legalform()
		);
		
		$this->addField(
			'hidden',
			'org_init_idnumber_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_idnumber()
		);
		
		$this->addField(
			'hidden',
			'org_init_rcs_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_rcs()
		);
		
		$this->addField(
			'hidden',
			'org_init_capital_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_capital()
		);
		
		$this->addField(
			'hidden',
			'org_init_address_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_address()
		);
		
		$this->addField(
			'hidden',
			'org_init_postal_code_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_postal_code()
		);
		
		$this->addField(
			'hidden',
			'org_init_city_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_city()
		);
		
		$this->addField(
			'hidden',
			'org_init_nationality_' .$id_orga,
			'',
			WDG_Form_Invest_User_Details::$field_group_orga_select,
			$WDGOrganization->get_nationality()
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
			}
			
			$lastname = $this->getInputText( 'lastname' );
			if ( empty( $lastname ) ) {
				$this->addPostError(
					'lastname',
					__( "Votre nom n'a pas &eacute;t&eacute; renseign&eacute;.", 'yproject' ),
					'lastname'
				);
			}
			
			if ( !$this->hasErrors() ) {
			
				$gender = $this->getInputText( 'gender' );
				$birthday = $this->getInputText( 'birthday' );
				$birthdate = DateTime::createFromFormat( 'd/m/Y', $birthday );
				$birthplace = $this->getInputText( 'birthplace' );
				$nationality = $this->getInputText( 'nationality' );
				$address = $this->getInputText( 'address' );
				$postal_code = $this->getInputText( 'postal_code' );
				$city = $this->getInputText( 'city' );
				$country = $this->getInputText( 'country' );
				$phone_number = $this->getInputText( 'phone_number' );
			
				$WDGUser->save_data(
					$email, $gender, $firstname, $lastname,
					$birthdate->format('d'), $birthdate->format('m'), $birthdate->format('Y'),
					$birthplace, $nationality, $address, $postal_code, $city, $country, $phone_number
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
		}
		
		return $orga_id;
	}
	
	private function postFormOrganizationCheck() {
		$buffer = TRUE;
		
		$WDGOrganization = FALSE;
		$orga_id = $this->getInputText( 'orga-id' );
		if ( $orga_id == 'new-orga' ) {
			$org_capable = $this->getInputChecked( 'org-capable' );
			if ( !$org_capable ) {
				$buffer = FALSE;
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
			$buffer = FALSE;
			$this->addPostError(
				'org-name-empty',
				__( "Le nom de l'organisation doit &ecirc;tre renseign&eacute;.", 'yproject' ),
				'org_name'
			);
		}
		$org_email = $this->getInputText( 'org_email' );
		if ( !is_email( $org_email ) ) {
			$buffer = FALSE;
			$this->addPostError(
				'email',
				__( "L'adresse e-mail de l'organisation n'est pas valide.", 'yproject' ),
				'email'
			);
		}
		if ( ( $orga_id == 'new-orga' || ( $WDGOrganization != FALSE && $WDGOrganization->get_email() != $org_email ) ) && email_exists( $org_email ) ) {
			$buffer = FALSE;
			$this->addPostError(
				'email',
				__( "L'adresse e-mail de l'organisation est d&eacute;j&agrave; utilis&eacute;e.", 'yproject' ),
				'email'
			);
		}
		
		$org_capital = $this->getInputText( 'org_capital' );
		$org_capital = filter_var( $org_capital, FILTER_VALIDATE_INT );
		if ( $org_capital === FALSE ) {
			$this->addPostError(
				'capital-not-integer',
				__( "Le capital de l'organisation doit &ecirc;tre un nombre entier.", 'yproject' ),
				'capital'
			);
		}
		
		$org_postal_code = $this->getInputText( 'org_postal_code' );
		$org_postal_code = filter_var( $org_postal_code, FILTER_VALIDATE_INT );
		if ( $org_postal_code === FALSE ) {
			$this->addPostError(
				'postalcode-not-integer',
				__( "Le code postal de l'organisation doit &ecirc;tre un nombre entier.", 'yproject' ),
				'postal_code'
			);
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
		$WDGOrganization->set_email( $org_email );
		$org_legalform = $this->getInputText( 'org_legalform' );
		$WDGOrganization->set_legalform( $org_legalform );
		$org_idnumber = $this->getInputText( 'org_idnumber' );
		$WDGOrganization->set_idnumber( $org_idnumber );
		$org_rcs = $this->getInputText( 'org_rcs' );
		$WDGOrganization->set_rcs( $org_rcs );
		$org_capital = $this->getInputText( 'org_capital' );
		$WDGOrganization->set_capital( $org_capital );
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
	
}
