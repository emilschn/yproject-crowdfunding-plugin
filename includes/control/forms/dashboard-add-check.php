<?php
class WDG_Form_Dashboard_Add_Check extends WDG_Form {
	
	public static $name = 'dashboard-add-check';
	
	public static $field_group_hidden = 'dashboard-add-check-hidden';
	public static $field_group_user_email = 'dashboard-add-check-user-email';
	public static $field_group_user_info = 'dashboard-add-check-user-info';
	public static $field_group_orga_select = 'dashboard-add-check-orga-select';
	public static $field_group_orga_info = 'dashboard-add-check-orga-info';
	public static $field_group_invest_files = 'dashboard-add-check-invest-files';
	
	private $campaign_id;
	private $user_id;
	
	public function __construct( $campaign_id = FALSE, $user_id = FALSE ) {
		parent::__construct( self::$name );
		$this->campaign_id = $campaign_id;
		$this->user_id = $user_id;
		$this->initFields();
	}
	
	protected function initFields() {
		ypcf_session_start();
		parent::initFields();
		
		//**********************************************************************
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			self::$field_group_hidden,
			self::$name
		);
		
		//**********************************************************************
		// Données de l'investisseur : $field_group_user_type
		$this->addField(
			'text',
			'invest-amount',
			__( "Montant de l'investissement *", 'yproject' ),
			self::$field_group_user_email
		);
		
		$this->addField(
			'text',
			'user-email',
			__( "E-mail de l'investisseur *", 'yproject' ),
			self::$field_group_user_email
		);
		
		//**********************************************************************
		// Données de l'investisseur : $field_group_user_info
		$this->addField(
			'select',
			'gender',
			__( "Sexe *", 'yproject' ),
			self::$field_group_user_info,
			'',
			FALSE,
			[
				'female'	=> __( "une femme", 'yproject' ),
				'male'		=> __( "un homme", 'yproject' )
			]
		);
		
		$this->addField(
			'text',
			'firstname',
			__( "Pr&eacute;nom *", 'yproject' ),
			self::$field_group_user_info
		);
		
		$this->addField(
			'text',
			'lastname',
			__( "Nom *", 'yproject' ),
			self::$field_group_user_info
		);

		$this->addField(
			'date',
			'birthday',
			__( "Date de naissance *", 'yproject' ),
			self::$field_group_user_info
		);

		$this->addField(
			'text',
			'birthplace',
			__( "Ville de naissance *", 'yproject' ),
			self::$field_group_user_info
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
			self::$field_group_user_info,
			'',
			__( "Uniquement si la naissance a eu lieu &agrave; Paris, Marseille ou Lyon", 'yproject' ),
			$district_list
		);
		
		global $french_departments;
		$this->addField(
			'select',
			'birthplace_department',
			__( "D&eacute;partement de naissance *", 'yproject' ),
			self::$field_group_user_info,
			'',
			__( "Uniquement si la naissance a eu lieu en France", 'yproject' ),
			$french_departments
		);

		global $country_list;
		$this->addField(
			'select',
			'birthplace_country',
			__( "Pays de naissance *", 'yproject' ),
			self::$field_group_user_info,
			'',
			FALSE,
			$country_list
		);

		global $country_list;
		$this->addField(
			'select',
			'nationality',
			__( "Nationalit&eacute; *", 'yproject' ),
			self::$field_group_user_info,
			'',
			FALSE,
			$country_list
		);
			
		$this->addField(
			'text',
			'address_number',
			__( "Num&eacute;ro", 'yproject' ),
			self::$field_group_user_info
		);

		global $address_number_complements;
		$this->addField(
			'select',
			'address_number_complement',
			__( "Compl&eacute;ment de num&eacute;ro", 'yproject' ),
			self::$field_group_user_info,
			'',
			FALSE,
			$address_number_complements
		);

		$this->addField(
			'text',
			'address',
			__( "Adresse *", 'yproject' ),
			self::$field_group_user_info
		);

		$this->addField(
			'text',
			'postal_code',
			__( "Code postal *", 'yproject' ),
			self::$field_group_user_info
		);

		$this->addField(
			'text',
			'city',
			__( "Ville *", 'yproject' ),
			self::$field_group_user_info
		);

		$this->addField(
			'select',
			'country',
			__( "Pays *", 'yproject' ),
			self::$field_group_user_info,
			'',
			FALSE,
			$country_list
		);
		
		$this->addField(
			'select',
			'user_type',
			__( "Souhaite investir... *", 'yproject' ),
			self::$field_group_user_info,
			'',
			FALSE,
			[
				''		=> "",
				'user'	=> __( "En son nom (personne physique)", 'yproject' ),
				'orga'	=> __( "En tant qu'organisation (personne morale)", 'yproject' )
			]
		);
		
		//**********************************************************************
		// Sélection de l'organisation : $field_group_orga_select
		$this->addField(
			'select',
			'orga-id',
			__( "Au nom de", 'yproject' ),
			self::$field_group_orga_select,
			$_SESSION[ 'orga_id' ],
			FALSE,
			[
				''			=> "",
				'new-orga'	=> __( "Nouvelle organisation", 'yproject' )
			]
		);
		
		//**********************************************************************
		// Informations de l'orga : $field_group_orga_info
		$this->addField(
			'text',
			'org_name',
			__( "D&eacute;nomination sociale *", 'yproject' ),
			self::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_email',
			__( "E-mail de contact *", 'yproject' ),
			self::$field_group_orga_info,
			FALSE,
			__( "Cette adresse doit &ecirc;tre diff&eacute;rente de celle de votre compte.", 'yproject' )
		);
		
		$this->addField(
			'text',
			'org_website',
			__( "Site internet *", 'yproject' ),
			self::$field_group_orga_info,
			FALSE
		);
		
		$this->addField(
			'text',
			'org_legalform',
			__( "Forme juridique *", 'yproject' ),
			self::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_idnumber',
			__( "Num&eacute;ro SIRET *", 'yproject' ),
			self::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_rcs',
			__( "RCS (Ville) *", 'yproject' ),
			self::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_capital',
			__( "Capital social (en euros) *", 'yproject' ),
			self::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_address_number',
			__( "Num&eacute;ro *", 'yproject' ),
			self::$field_group_orga_info
		);

		global $address_number_complements;
		$this->addField(
			'select',
			'org_address_number_comp',
			__( "Compl&eacute;ment de num&eacute;ro", 'yproject' ),
			self::$field_group_orga_info,
			'',
			FALSE,
			$address_number_complements
		);
			
		
		$this->addField(
			'text',
			'org_address',
			__( "Adresse *", 'yproject' ),
			self::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_postal_code',
			__( "Code postal *", 'yproject' ),
			self::$field_group_orga_info
		);
		
		$this->addField(
			'text',
			'org_city',
			__( "Ville *", 'yproject' ),
			self::$field_group_orga_info
		);

		global $country_list;
		$this->addField(
			'select',
			'org_nationality',
			__( "Pays *", 'yproject' ),
			self::$field_group_orga_info,
			FALSE,
			FALSE,
			$country_list
		);
		
		//**********************************************************************
		// Fichiers de l'investissement : $field_group_invest_files
		$this->addField(
			'file',
			'picture-check',
			__( "Photo du ch&egrave;que *", 'yproject' ),
			self::$field_group_invest_files
		);
		
		$this->addField(
			'file',
			'picture-contract',
			__( "Photos du contrat *", 'yproject' ),
			self::$field_group_invest_files
		);
		
	}
	
	public function postFormAjax() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGUser = new WDGUser( $this->user_id );
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !$campaign->current_user_can_edit() ) {
			$error = array(
				'cant-edit',
				__( "Impossible", 'yproject' ),
				'cant-edit'
			);
			array_push( $feedback_errors, $error );
			
		// Analyse du formulaire
		} else {
			// Informations de base
			$email = $this->getInputText( 'user-email' );
			if ( !is_email( $email ) ) {
				$error = array(
					'email',
					__( "Cette adresse e-mail n'est pas valide.", 'yproject' ),
					'email'
				);
				array_push( $feedback_errors, $error );
			}
			
			$invest_amount = $this->getInputTextMoney( 'invest-amount' );
			$max_part_value = ypcf_get_max_part_value();
			if ( $invest_amount < 10 || !is_numeric( $invest_amount ) || intval( $invest_amount ) != $invest_amount || $invest_amount > $max_part_value ) {
				$error = array(
					'invest-amount',
					__( "Le montant n'est pas valide", 'yproject' ),
					'invest-amount'
				);
				array_push( $feedback_errors, $error );
			}
			
			$user_type = $this->getInputText( 'user_type' );
			
			$gender = $this->getInputText( 'gender' );
			$firstname = $this->getInputText( 'firstname' );
			$lastname = $this->getInputText( 'lastname' );
			$birthday = $this->getInputText( 'birthday' );
			$birthplace = $this->getInputText( 'birthplace' );
			$nationality = $this->getInputText( 'nationality' );
			$address_number = $this->getInputText( 'address_number' );
			$address_number_complement = $this->getInputText( 'address_number_complement' );
			$address = $this->getInputText( 'address' );
			$address = $this->getInputText( 'address' );
			$postal_code = $this->getInputText( 'postal_code' );
			$city = $this->getInputText( 'city' );
			$country = $this->getInputText( 'country' );
			
			if ( $user_type == 'orga' ) {
				
			}
			
			
//			$file_check_picture = $this->getI( 'picture-check' );
//			$file_check_picture = $this->getI( 'picture-contract' );
		}
		
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		echo json_encode( $buffer );
		exit();
	}
}
