<?php
class WDG_Form_Invest_Poll_Continuous extends WDG_Form {
	
	public static $name = 'project-invest-poll-continuous';
	
	public static $field_group_hidden = 'invest-hidden';
	public static $field_group_poll_continuous = 'invest-poll-continuous';
	
	private static $poll_source_slug = 'continuous';
	private static $poll_source_version = 1;
	
	private $campaign_id;
	private $user_id;
	private $context;
	
	public function __construct( $campaign_id, $user_id, $context = 'investment' ) {
		parent::__construct( self::$name );
		$this->campaign_id = $campaign_id;
		$this->user_id = $user_id;
		$this->context = $context;
		$this->initFields();
	}
	
	protected function initFields() {
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
		// Champs garantie : $field_group_poll_continuous
		$this->addField(
			'checkboxes',
			'',
			__( "Je voudrais recevoir une notification&nbsp;:", 'yproject' ),
			self::$field_group_poll_continuous,
			FALSE,
			FALSE,
			[
				'new-campaign'			=> __( "Lorsqu'il sera possible d'investir sur les prochains Fairphones", 'yproject' ),
				'new-subject'			=> __( "Lorsqu'il sera possible d'investir sur une nouvelle th&eacute;matique (apiculture, &eacute;nergie, mobilit&eacute;, logement...)", 'yproject' )
			]
		);
		
		$this->addField(
			'checkboxes',
			'',
			__( "Je souhaiterais investir sur les projets d'&eacute;pargne positive, de mani&egrave;re&nbsp;:", 'yproject' ),
			self::$field_group_poll_continuous,
			FALSE,
			FALSE,
			[
				'invest-ponctual'		=> __( "Ponctuelle", 'yproject' ),
				'invest-monthly'		=> __( "R&eacute;guli&egrave;re : mensuelle", 'yproject' ),
				'invest-quarterly'		=> __( "R&eacute;guli&egrave;re : trimestrielle", 'yproject' ),
				'invest-campaign'		=> __( "Occasionnelle : &agrave; chaque fois qu'il est possible d'investir sur de nouveaux Fairphones", 'yproject' ),
				'invest-other'			=> __( "Autre", 'yproject' )
			]
		);
		
		$this->addField(
			'text',
			'other-invest-rythm',
			'',
			self::$field_group_poll_continuous,
			''
		);
		
		$this->addField(
			'checkboxes',
			'',
			__( "Comment avez-vous connu l'&eacute;pargne positive&nbsp;?", 'yproject' ),
			self::$field_group_poll_continuous,
			FALSE,
			FALSE,
			[
				'known-by-wedogood'	=> __( "Par WE DO GOOD", 'yproject' ),
				'known-by-project'	=> __( "Par Commown", 'yproject' ),
				'known-by-other'	=> __( "Autre", 'yproject' )
			]
		);
		
		$this->addField(
			'text',
			'other-known-by-source',
			'',
			self::$field_group_poll_continuous,
			''
		);
		
	}
	
	public function postForm( $context_investment_amount ) {
		parent::postForm();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGUser = new WDGUser( $this->user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ),
				'general'
			);
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ),
				'general'
			);
		}
		
		if ( !$this->hasErrors() ) {
			// Données valables pour les deux sondages :
			$project_id = $campaign->get_api_id();
			$user_id = $WDGUser->get_api_id();
			$user_age = $WDGUser->get_age();
			$user_postal_code = $WDGUser->get_postal_code();
			$user_gender = $WDGUser->get_gender();
			$user_email = $WDGUser->get_email();
			
			// Enregistrement de la réponse sur la source
			$poll_source_answers = array(
				'notifications'	=> array(
					'new-campaign'		=> ( $this->getInputChecked( 'new-campaign' ) ? '1' : '0' ),
					'new-subject'		=> ( $this->getInputChecked( 'new-subject' ) ? '1' : '0' )
				),
				'invest-rythm'	=> array(
					'invest-ponctual'	=> ( $this->getInputChecked( 'invest-ponctual' ) ? '1' : '0' ),
					'invest-monthly'	=> ( $this->getInputChecked( 'invest-monthly' ) ? '1' : '0' ),
					'invest-quarterly'	=> ( $this->getInputChecked( 'invest-quarterly' ) ? '1' : '0' ),
					'invest-campaign'	=> ( $this->getInputChecked( 'invest-campaign' ) ? '1' : '0' ),
					'invest-other'		=> ( $this->getInputChecked( 'invest-other' ) ? '1' : '0' )
				),
				'other-invest-rythm'	=> $this->getInputText( 'other-invest-rythm' ),
				'known-by'		=> array(
					'known-by-wedogood'	=> ( $this->getInputChecked( 'known-by-wedogood' ) ? '1' : '0' ),
					'known-by-project'	=> ( $this->getInputChecked( 'known-by-project' ) ? '1' : '0' ),
					'known-by-other'	=> ( $this->getInputChecked( 'known-by-other' ) ? '1' : '0' )
				),
				'other-known-by-source'	=> $this->getInputText( 'other-known-by-source' )
			);
			
			$poll_source_answers_str = json_encode( $poll_source_answers );
			WDGWPREST_Entity_PollAnswer::create( self::$poll_source_slug, self::$poll_source_version, $poll_source_answers_str, $this->context, $context_investment_amount, $project_id, $user_id, $user_age, $user_postal_code, $user_gender, $user_email );
		}
		
		return !$this->hasErrors();
	}
	
}
