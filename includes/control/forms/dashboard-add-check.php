<?php
class WDG_Form_Dashboard_Add_Check extends WDG_Form {
	
	public static $name = 'dashboard-add-check';
	
	public static $field_group_hidden = 'dashboard-add-check-hidden';
	public static $field_group_user_email = 'dashboard-add-check-user-email';
	public static $field_group_user_info = 'dashboard-add-check-user-info';
	
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
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGUser = new WDGUser( $this->user_id );
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !$campaign->current_user_can_edit() ) {
			
		// Analyse du formulaire
		} else {
			// Informations de base
			$email = $this->getInputText( 'user-email' );
			if ( !is_email( $email ) ) {
				$this->addPostError(
					'email',
					__( "Cette adresse e-mail n'est pas valide.", 'yproject' ),
					'email'
				);
			}
		}
		
		return !$this->hasErrors();
	}
}
