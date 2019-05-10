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
	
	public function __construct( $campaign_id = FALSE ) {
		parent::__construct( self::$name );
		$this->campaign_id = $campaign_id;
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
		
		$this->addField(
			'hidden',
			'campaign-id',
			'',
			self::$field_group_hidden,
			$this->campaign_id
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
			'orga_id',
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
		
		$data_to_save = array();
		$feedback_errors = array();
		
		if ( empty( $this->campaign_id ) ) {
			$this->campaign_id = $this->getInputText( 'campaign-id' );
		}
		$campaign = new ATCF_Campaign( $this->campaign_id );
		
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
			$data_to_save[ 'email' ] = $email;
			if ( !is_email( $email ) ) {
				$error = array(
					'email',
					__( "Cette adresse e-mail n'est pas valide.", 'yproject' ),
					'email'
				);
				array_push( $feedback_errors, $error );
			}
			
			$invest_amount = $this->getInputTextMoney( 'invest-amount' );
			$data_to_save[ 'invest_amount' ] = $invest_amount;
			if ( $invest_amount < 10 ) {
				$error = array(
					'invest-amount',
					__( "Le montant n'est pas suffisant", 'yproject' ),
					'invest-amount'
				);
				array_push( $feedback_errors, $error );
			}
			if ( !is_numeric( $invest_amount ) ) {
				$error = array(
					'invest-amount',
					__( "Le montant doit &ecirc;tre au format num&eacute;rique", 'yproject' ),
					'invest-amount'
				);
				array_push( $feedback_errors, $error );
			}
			if ( intval( $invest_amount ) != $invest_amount ) {
				$error = array(
					'invest-amount',
					__( "Le montant doit &ecirc;tre un nombre entier", 'yproject' ),
					'invest-amount'
				);
				array_push( $feedback_errors, $error );
			}
			$max_part_value = $campaign->goal( false ) - $campaign->current_amount( false, true );
			if ( $invest_amount > $max_part_value ) {
				$error = array(
					'invest-amount',
					__( "Le montant est sup&eacute;rieur &agrave; ce qu'il reste jusqu'&agrave; l'objectif maximal", 'yproject' ) . ' - ' .$max_part_value . ' €',
					'invest-amount'
				);
				array_push( $feedback_errors, $error );
			}
			
			$gender = $this->getInputText( 'gender' );
			$data_to_save[ 'gender' ] = $gender;
			if ( empty( $gender ) ) {
				$error = array(
					'gender',
					__( "Le sexe de l'investisseur ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
					'gender'
				);
				array_push( $feedback_errors, $error );
			}
			$firstname = $this->getInputText( 'firstname' );
			$data_to_save[ 'firstname' ] = $firstname;
			if ( empty( $firstname ) ) {
				$error = array(
					'firstname',
					__( "Le prenom de l'investisseur ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
					'firstname'
				);
				array_push( $feedback_errors, $error );
			}
			$lastname = $this->getInputText( 'lastname' );
			$data_to_save[ 'lastname' ] = $lastname;
			if ( empty( $lastname ) ) {
				$error = array(
					'lastname',
					__( "Le nom de l'investisseur ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
					'lastname'
				);
				array_push( $feedback_errors, $error );
			}
			$birthday = $this->getInputText( 'birthday' );
			$data_to_save[ 'birthday' ] = $birthday;
			if ( empty( $birthday ) ) {
				$error = array(
					'birthday',
					__( "La date de naissance de l'investisseur ne peut pas &ecirc;tre ind&eacute;finie", 'yproject' ),
					'birthday'
				);
				array_push( $feedback_errors, $error );
			}
			$birthplace = $this->getInputText( 'birthplace' );
			$data_to_save[ 'birthplace' ] = $birthplace;
			if ( empty( $birthplace ) ) {
				$error = array(
					'birthplace',
					__( "La ville de naissance de l'investisseur ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
					'birthplace'
				);
				array_push( $feedback_errors, $error );
			}
			$birthplace_district = $this->getInputText( 'birthplace_district' );
			$data_to_save[ 'birthplace_district' ] = $birthplace_district;
			$birthplace_department = $this->getInputText( 'birthplace_department' );
			$data_to_save[ 'birthplace_department' ] = $birthplace_department;
			if ( empty( $birthplace_department ) ) {
				$error = array(
					'birthplace_department',
					__( "Le d&eacute;partement de naissance de l'investisseur ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
					'birthplace_department'
				);
				array_push( $feedback_errors, $error );
			}
			$birthplace_country = $this->getInputText( 'birthplace_country' );
			$data_to_save[ 'birthplace_country' ] = $birthplace_country;
			if ( empty( $birthplace_country ) ) {
				$error = array(
					'birthplace_country',
					__( "Le pays de naissance de l'investisseur ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
					'birthplace_country'
				);
				array_push( $feedback_errors, $error );
			}
			$nationality = $this->getInputText( 'nationality' );
			$data_to_save[ 'nationality' ] = $nationality;
			if ( empty( $nationality ) ) {
				$error = array(
					'nationality',
					__( "La nationalite de l'investisseur ne peut pas &ecirc;tre ind&eacute;finie", 'yproject' ),
					'nationality'
				);
				array_push( $feedback_errors, $error );
			}
			$address_number = $this->getInputText( 'address_number' );
			$data_to_save[ 'address_number' ] = $address_number;
			$address_number_complement = $this->getInputText( 'address_number_complement' );
			$data_to_save[ 'address_number_complement' ] = $address_number_complement;
			$address = $this->getInputText( 'address' );
			$data_to_save[ 'address' ] = $address;
			if ( empty( $address ) ) {
				$error = array(
					'address',
					__( "L'adresse de l'investisseur ne peut pas &ecirc;tre ind&eacute;finie", 'yproject' ),
					'address'
				);
				array_push( $feedback_errors, $error );
			}
			$postal_code = $this->getInputText( 'postal_code' );
			$data_to_save[ 'postal_code' ] = $postal_code;
			if ( empty( $postal_code ) ) {
				$error = array(
					'postal_code',
					__( "Le code postal de l'investisseur ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
					'postal_code'
				);
				array_push( $feedback_errors, $error );
			}
			$city = $this->getInputText( 'city' );
			$data_to_save[ 'city' ] = $city;
			if ( empty( $city ) ) {
				$error = array(
					'city',
					__( "La ville de l'investisseur ne peut pas &ecirc;tre ind&eacute;finie", 'yproject' ),
					'city'
				);
				array_push( $feedback_errors, $error );
			}
			$country = $this->getInputText( 'country' );
			$data_to_save[ 'country' ] = $country;
			if ( empty( $country ) ) {
				$error = array(
					'country',
					__( "Le pays de l'investisseur ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
					'country'
				);
				array_push( $feedback_errors, $error );
			}
			
			$user_type = $this->getInputText( 'user_type' );
			$data_to_save[ 'user_type' ] = $user_type;
			if ( $user_type == 'orga' ) {
				$orga_id = $this->getInputText( 'orga_id' );
				$data_to_save[ 'orga_id' ] = $orga_id;
				$orga_name = $this->getInputText( 'org_name' );
				$data_to_save[ 'orga_name' ] = $orga_name;
				if ( empty( $orga_name ) ) {
					$error = array(
						'orga_name',
						__( "Le nom de l'organisation ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
						'orga_name'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_email = $this->getInputText( 'org_email' );
				$data_to_save[ 'orga_email' ] = $orga_email;
				if ( empty( $orga_email ) ) {
					$error = array(
						'orga_email',
						__( "Le mail de l'organisation ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
						'orga_email'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_website = $this->getInputText( 'org_website' );
				$data_to_save[ 'orga_website' ] = $orga_website;
				if ( empty( $orga_website ) ) {
					$error = array(
						'orga_website',
						__( "Le site de l'organisation ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
						'orga_website'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_legalform = $this->getInputText( 'org_legalform' );
				$data_to_save[ 'orga_legalform' ] = $orga_legalform;
				if ( empty( $orga_legalform ) ) {
					$error = array(
						'orga_legalform',
						__( "La forme juridique de l'organisation ne peut pas &ecirc;tre ind&eacute;finie", 'yproject' ),
						'orga_legalform'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_idnumber = $this->getInputText( 'org_idnumber' );
				$data_to_save[ 'orga_idnumber' ] = $orga_idnumber;
				if ( empty( $orga_idnumber ) ) {
					$error = array(
						'orga_idnumber',
						__( "Le SIRET de l'organisation ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
						'orga_idnumber'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_rcs = $this->getInputText( 'org_rcs' );
				$data_to_save[ 'orga_rcs' ] = $orga_rcs;
				if ( empty( $orga_rcs ) ) {
					$error = array(
						'orga_rcs',
						__( "Le RCS de l'organisation ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
						'orga_rcs'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_capital = $this->getInputText( 'org_capital' );
				$data_to_save[ 'orga_capital' ] = $orga_capital;
				if ( empty( $orga_capital ) ) {
					$error = array(
						'orga_capital',
						__( "Le capital de l'organisation ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
						'orga_capital'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_address_number = $this->getInputText( 'org_address_number' );
				$data_to_save[ 'orga_address_number' ] = $orga_address_number;
				$orga_address_number_comp = $this->getInputText( 'org_address_number_comp' );
				$data_to_save[ 'orga_address_number_comp' ] = $orga_address_number_comp;
				$orga_address = $this->getInputText( 'org_address' );
				$data_to_save[ 'orga_address' ] = $orga_address;
				if ( empty( $orga_address ) ) {
					$error = array(
						'orga_address',
						__( "L'adresse de l'organisation ne peut pas &ecirc;tre ind&eacute;finie", 'yproject' ),
						'orga_address'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_postal_code = $this->getInputText( 'org_postal_code' );
				$data_to_save[ 'orga_postal_code' ] = $orga_postal_code;
				if ( empty( $orga_postal_code ) ) {
					$error = array(
						'orga_postal_code',
						__( "Le code postal de l'organisation ne peut pas &ecirc;tre ind&eacute;fini", 'yproject' ),
						'orga_postal_code'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_city = $this->getInputText( 'org_city' );
				$data_to_save[ 'orga_city' ] = $orga_city;
				if ( empty( $orga_city ) ) {
					$error = array(
						'orga_city',
						__( "La ville de l'organisation ne peut pas &ecirc;tre ind&eacute;finie", 'yproject' ),
						'orga_city'
					);
					array_push( $feedback_errors, $error );
				}
				$orga_nationality = $this->getInputText( 'org_nationality' );
				$data_to_save[ 'orga_nationality' ] = $orga_nationality;
				if ( empty( $orga_nationality ) ) {
					$error = array(
						'orga_nationality',
						__( "La nationalit&eacute; de l'organisation ne peut pas &ecirc;tre ind&eacute;finie", 'yproject' ),
						'orga_nationality'
					);
					array_push( $feedback_errors, $error );
				}
			}
			
			$file_picture_check = $this->getInputFile( 'picture-check' );
			if ( empty( $file_picture_check ) ) {
				$error = array(
					'picture-check',
					__( "La photo du cheque n'a pas &eacute;t&eacute; envoy&eacute;e", 'yproject' ),
					'picture-check'
				);
				array_push( $feedback_errors, $error );
			}
			$file_picture_contract = $this->getInputFile( 'picture-contract' );
			if ( empty( $file_picture_contract ) ) {
				$error = array(
					'picture-contract',
					__( "Les fichiers du contrat n'ont pas &eacute;t&eacute; envoy&eacute;s", 'yproject' ),
					'picture-contract'
				);
				array_push( $feedback_errors, $error );
			}
		}
		
		$feedback_success = '0';
		if ( empty( $feedback_errors ) ) {
			$feedback_success = '1';
			$investment_draft_item = WDGWPREST_Entity_InvestmentDraft::create( 'draft', $campaign->get_api_id(), $data_to_save );
			
			// photo du chèque
			$file_name = $file_picture_check[ 'name' ];
			$file_name_exploded = explode( '.', $file_name );
			$ext = $file_name_exploded[ count( $file_name_exploded ) - 1 ];
			$byte_array = file_get_contents( $file_picture_check[ 'tmp_name' ] );
			$file_picture_check = WDGWPREST_Entity_File::create( $investment_draft_item->id, 'investment-draft', 'picture-check', $ext, base64_encode( $byte_array ) );
			
			// photo du contrat
			$file_name = $file_picture_contract[ 'name' ];
			$file_name_exploded = explode( '.', $file_name );
			$ext = $file_name_exploded[ count( $file_name_exploded ) - 1 ];
			$byte_array = file_get_contents( $file_picture_contract[ 'tmp_name' ] );
			$file_picture_contract = WDGWPREST_Entity_File::create( $investment_draft_item->id, 'investment-draft', 'picture-contract', $ext, base64_encode( $byte_array ) );
			
			$dashboard_url = home_url( '/tableau-de-bord/?campaign_id=' . $campaign->ID );
			NotificationsEmails::investment_draft_created_admin( $campaign->get_name(), $dashboard_url );
		}
			
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		echo json_encode( $buffer );
		exit();
	}
}
