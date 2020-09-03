<?php
class WDG_Form_Invest_Poll extends WDG_Form {
	
	public static $name = 'project-invest-poll';
	
	public static $field_group_hidden = 'invest-hidden';
	public static $field_group_poll_source = 'invest-poll-source';
	
	private static $poll_source_slug = 'source';
	private static $poll_source_version = 1;
	
	private $campaign_id;
	private $user_id;
	private $context;
	private $context_amount;
	
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
		// Champs garantie : $field_group_poll_source
		$label = __( 'form.invest-poll.source.TITLE', 'yproject' );
		if ( $this->context == 'vote' ) {
			$label = __( 'form.invest-poll.source.TITLE_VOTE', 'yproject' );
		}
		$this->addField(
			'checkboxes',
			'',
			$label,
			self::$field_group_poll_source,
			FALSE,
			FALSE,
			[
				'know-project-manager'			=> __( 'form.invest-poll.source.OPTION_PROJECT_MANAGER', 'yproject' ),
				'interrested-by-domain'			=> __( 'form.invest-poll.source.OPTION_INTEREST', 'yproject' ),
				'diversify-savings'				=> __( 'form.invest-poll.source.OPTION_SAVINGS', 'yproject' ),
				'looking-for-positive-impact'	=> __( 'form.invest-poll.source.OPTION_IMPACTS', 'yproject' ),
				'other-motivations'				=> __( 'form.invest-poll.source.OPTION_OTHER', 'yproject' ),
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
			__( 'form.invest-poll.known.TITLE', 'yproject' ),
			self::$field_group_poll_source,
			FALSE,
			FALSE,
			[
				'known-by-project-manager'	=> __( 'form.invest-poll.known.OPTION_PROJECT_MANAGER', 'yproject' ),
				'known-by-wedogood'			=> __( 'form.invest-poll.known.OPTION_WEDOGOOD', 'yproject' ),
				'known-by-other-investor'	=> __( 'form.invest-poll.known.OPTION_OTHER_INVESTOR', 'yproject' ),
				'known-by-other-source'		=> __( 'form.invest-poll.known.OPTION_OTHER', 'yproject' )
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
			__( 'form.invest-poll.come-from.TITLE', 'yproject' ),
			self::$field_group_poll_source,
			FALSE,
			FALSE,
			[
				'mail-from-project-manager'			=> __( 'form.invest-poll.come-from.OPTION_MAIL', 'yproject' ),
				'social-network-private-message'	=> __( 'form.invest-poll.come-from.OPTION_SOCIAL_PRIVATE', 'yproject' ),
				'social-network-publication'		=> __( 'form.invest-poll.come-from.OPTION_SOCIAL_PUBLIC', 'yproject' ),
				'wedogood-site-or-newsletter'		=> __( 'form.invest-poll.come-from.OPTION_WEDOGOOD', 'yproject' ),
				'press-article'						=> __( 'form.invest-poll.come-from.OPTION_PRESS', 'yproject' ),
				'other-source'						=> __( 'form.invest-poll.come-from.OPTION_OTHER', 'yproject' )
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

	public function setContextAmount( $context_investment_amount ) {
		$this->context_amount = $context_investment_amount;
	}
	
	public function postForm() {
		parent::postForm();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$WDGUser = new WDGUser( $this->user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( 'form.invest-input.error.NOT_LOGGED_IN', 'yproject' ),
				'general'
			);
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( 'form.invest-input.error.NOT_LOGGED_IN', 'yproject' ),
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
			WDGWPREST_Entity_PollAnswer::create( self::$poll_source_slug, self::$poll_source_version, $poll_source_answers_str, $this->context, $this->context_amount, $project_id, $user_id, $user_age, $user_postal_code, $user_gender, $user_email );
		}
		
		return !$this->hasErrors();
	}
	
}
