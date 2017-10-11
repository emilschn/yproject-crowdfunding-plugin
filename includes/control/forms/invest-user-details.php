<?php
class WDG_Form_Invest_User_Details extends WDG_Form {
	
	public static $name = 'project-invest-user-details';
	
	public static $field_group_hidden = 'invest-hidden';
	public static $field_group_user_type = 'invest-user-type';
	public static $field_group_user_info = 'invest-user-info';
	public static $field_group_orga_select = 'invest-orga-select';
	
	private $campaign_id;
	private $user_id;
	private $errors;
	
	public function __construct( $campaign_id = FALSE, $user_id = FALSE ) {
		parent::__construct( WDG_Form_Invest_User_Details::$name );
		$this->campaign_id = $campaign_id;
		$this->user_id = $user_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		$campaign = new ATCF_Campaign( $this->campaign_id );
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
		// Type d'investisseur : $field_group_user_type
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
			__( "Vous &ecirc;tes", 'yproject' ),
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
			'date',
			'birthday',
			__( "Date de naissance", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_lemonway_birthdate()
		);

		$this->addField(
			'text',
			'birthplace',
			__( "Ville de naissance", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->wp_user->get( 'user_birthplace' )
		);

		global $country_list;
		$this->addField(
			'select',
			'nationality',
			__( "Nationalit&eacute;", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_nationality(),
			FALSE,
			$country_list
		);

		$this->addField(
			'text',
			'address',
			__( "Adresse", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_address()
		);

		$this->addField(
			'text',
			'postal_code',
			__( "Code postal", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_postal_code()
		);

		$this->addField(
			'text',
			'city',
			__( "Ville", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_city()
		);

		$this->addField(
			'text',
			'country',
			__( "Pays", 'yproject' ),
			WDG_Form_Invest_User_Details::$field_group_user_info,
			$WDGUser->get_country()
		);
		
		//**********************************************************************
		// Sélection de l'orga : $field_group_orga_select
		$organization_list = array();
		$organization_list[ '' ] = '';
		$organization_list[ 'new-orga' ] = __( "Une nouvelle organisation", 'yproject' );
		$user_orga_list = $WDGUser->get_organizations_list();
		foreach ( $user_orga_list as $organization_item ) {
			$organization_list[ $organization_item->wpref ] = $organization_item->name;
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
		
	}
	
	public function getPostErrors() {
		return $this->errors;
	}
	
	public function postForm() {
		parent::postForm();
		
		$feedback_errors = array();
		
		if ( !is_user_logged_in() ) {
			$error = array(
				'code'		=> 'user-not-logged-in',
				'text'		=> __( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
		}
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		if ( !$campaign->is_investable() ) {
			$error = array(
				'code'		=> 'invest-not-possible',
				'text'		=> __( "Il n'est pas possible d'investir.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
		}
		
		if ( empty( $feedback_errors ) ) {
		}
		
		$this->errors = $feedback_errors;
		return empty( $this->errors );
	}
	
}
