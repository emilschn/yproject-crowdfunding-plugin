<?php
class WDG_Form_Invest_Poll extends WDG_Form {
	
	public static $name = 'project-invest-poll';
	
	public static $field_group_hidden = 'invest-hidden';
	public static $field_group_poll_warranty = 'invest-poll-warranty';
	public static $field_group_poll_source = 'invest-poll-source';
	
	private static $poll_warranty_slug = 'warranty';
	private static $poll_warranty_version = 1;
	private static $poll_source_slug = 'source';
	private static $poll_source_version = 1;
	
	private $campaign_id;
	private $user_id;
	private $context;
	
	public function __construct( $campaign_id, $user_id, $context = 'investment' ) {
		parent::__construct( WDG_Form_Invest_Poll::$name );
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
		// Champs garantie : $field_group_poll_warranty
		$this->addField(
			'radio',
			'would-invest-more-amount',
			__( "Si nous vous proposions une protection totale de votre investissement sur ce projet, seriez-vous int&eacute;ress&eacute;(e) pour augmenter votre ticket d'investissement&nbsp;?", 'yproject' ),
			self::$field_group_poll_warranty,
			FALSE,
			FALSE,
			[
				'yes'	=> __( "Oui", 'yproject' ),
				'no'	=> __( "Non", 'yproject' )
			]
		);
		
		$this->addField(
			'text-money',
			'would-invest-amount-with-warranty',
			__( "Combien auriez-vous investi au total&nbsp;?", 'yproject' ),
			self::$field_group_poll_warranty,
			0
		);
		
		$this->addField(
			'radio',
			'would-invest-more-number',
			__( "Investiriez-vous sur plus de projets si votre investissement &eacute;tait prot&eacute;g&eacute;&nbsp;?", 'yproject' ),
			self::$field_group_poll_warranty,
			FALSE,
			FALSE,
			[
				'yes'	=> __( "Oui", 'yproject' ),
				'no'	=> __( "Non", 'yproject' )
			]
		);
		
		$this->addField(
			'text',
			'would-invest-number-per-year-with-warranty',
			__( "Combien par an&nbsp;?", 'yproject' ),
			self::$field_group_poll_warranty,
			0
		);
		
		
		//**********************************************************************
		// Champs garantie : $field_group_poll_source
		$label = __( "Ce qui m'a motiv&eacute;(e) &agrave; investir aujourd'hui&nbsp;: ", 'yproject' );
		if ( $this->context == 'vote' ) {
			$label = __( "Ce qui me donnerait envie d'investir sur ce projet&nbsp;: ", 'yproject' );
		}
		$this->addField(
			'checkboxes',
			'',
			$label,
			self::$field_group_poll_source,
			FALSE,
			FALSE,
			[
				'know-project-manager'			=> __( "Je connais le(s) porteur(s) de projet" ),
				'interrested-by-domain'			=> __( "Le projet fait partie d'un secteur qui m'int&eacute;resse" ),
				'diversify-savings'				=> __( "Je cherche &agrave; diversifier les placements de mon &eacute;pargne" ),
				'looking-for-positive-impact'	=> __( "Je cherche &agrave; investir dans un projet &agrave; impact positif" ),
				'other-motivations'				=> __( "Autre(s) :" ),
			]
		);
		
		$this->addField(
			'text',
			'other-motivations-to-invest',
			'',
			self::$field_group_poll_source,
			''
		);
		
		$this->addField(
			'radio',
			'how-the-fundraising-was-known',
			__( "J'ai connu cette lev&eacute;e de fonds via&nbsp;:", 'yproject' ),
			self::$field_group_poll_source,
			FALSE,
			FALSE,
			[
				'known-by-project-manager'	=> __( "L'entrepreneur", 'yproject' ),
				'known-by-wedogood'			=> __( "WE DO GOOD", 'yproject' ),
				'known-by-other-investor'	=> __( "Un autre investisseur du projet", 'yproject' ),
				'known-by-other-source'		=> __( "Autre (presse,...)", 'yproject' )
			]
		);
		
		$this->addField(
			'text',
			'other-source-to-know-the-fundraising',
			'',
			self::$field_group_poll_source,
			''
		);
		
		$this->addField(
			'radio',
			'where-user-come-from',
			__( "Je suis arriv&eacute;(e) sur la page de cette lev&eacute;e de fonds via&nbsp;:", 'yproject' ),
			self::$field_group_poll_source,
			FALSE,
			FALSE,
			[
				'mail-from-project-manager'			=> __( "Un mail du porteur de projet", 'yproject' ),
				'social-network-private-message'	=> __( "Un message priv&eacute; sur Facebook, LinkedIn, Twitter...", 'yproject' ),
				'social-network-publication'		=> __( "Une publication sur les r&eacute;seaux sociaux", 'yproject' ),
				'wedogood-site-or-newsletter'		=> __( "La newsletter ou le site de WE DO GOOD", 'yproject' ),
				'press-article'						=> __( "Un article de presse", 'yproject' ),
				'other-source'						=> __( "Autre(s) :", 'yproject' )
			]
		);
		
		$this->addField(
			'text',
			'other-source-where-the-user-come-from',
			'',
			self::$field_group_poll_source,
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
			$context_amount = $context_investment_amount;
			$project_id = $campaign->get_api_id();
			$user_id = $WDGUser->get_api_id();
			$user_age = $WDGUser->get_age();
			$user_postal_code = $WDGUser->get_postal_code();
			$user_gender = $WDGUser->get_gender();
			$user_email = $WDGUser->get_email();
			
			// Enregistrement de la réponse sur la garantie
			$poll_warranty_answers = array(
				'would-invest-more-amount'			=> $this->getInputText( 'would-invest-more-amount' ),
				'would-invest-amount-with-warranty'				=> $this->getInputText( 'would-invest-amount-with-warranty' ),
				'would-invest-more-number'			=> $this->getInputText( 'would-invest-more-number' ),
				'would-invest-number-per-year-with-warranty'	=> $this->getInputText( 'would-invest-number-per-year-with-warranty' ),
			);
			$poll_warranty_answers_str = json_encode( $poll_warranty_answers );
			WDGWPREST_Entity_PollAnswer::create( self::$poll_warranty_slug, self::$poll_warranty_version, $poll_warranty_answers_str, $this->context, $context_amount, $project_id, $user_id, $user_age, $user_postal_code, $user_gender, $user_email );

			// Enregistrement de la réponse sur la source
			$poll_source_answers = array(
				'motivations'	=> array(
					'know-project-manager'			=> ( $this->getInputChecked( 'know-project-manager' ) ? '1' : '0' ),
					'interrested-by-domain'			=> ( $this->getInputChecked( 'interrested-by-domain' ) ? '1' : '0' ),
					'diversify-savings'				=> ( $this->getInputChecked( 'diversify-savings' ) ? '1' : '0' ),
					'looking-for-positive-impact'	=> ( $this->getInputChecked( 'looking-for-positive-impact' ) ? '1' : '0' ),
					'other-motivations'				=> ( $this->getInputChecked( 'other-motivations' ) ? '1' : '0' )
				),
				'other-motivations-to-invest'			=> $this->getInputText( 'other-motivations-to-invest' ),
				'how-the-fundraising-was-known'			=> $this->getInputText( 'how-the-fundraising-was-known' ),
				'other-source-to-know-the-fundraising'	=> $this->getInputText( 'other-source-to-know-the-fundraising' ),
				'where-user-come-from'					=> $this->getInputText( 'where-user-come-from' ),
				'other-source-where-the-user-come-from'	=> $this->getInputText( 'other-source-where-the-user-come-from' )
			);
			
			$poll_source_answers_str = json_encode( $poll_source_answers );
			WDGWPREST_Entity_PollAnswer::create( self::$poll_source_slug, self::$poll_source_version, $poll_source_answers_str, $this->context, $context_amount, $project_id, $user_id, $user_age, $user_postal_code, $user_gender, $user_email );
		}
		
		return !$this->hasErrors();
	}
	
}
