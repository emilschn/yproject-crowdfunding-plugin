<?php
class WDG_Form_Vote extends WDG_Form {
	
	public static $field_group_impacts = 'vote-impact';
	public static $field_group_validate = 'vote-validate';
	public static $field_group_risk = 'vote-risk';
	public static $field_group_info = 'vote-info';
	
	public function __construct() {
		parent::__construct();
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		// Impacts : $field_group_impacts
		$this->addField(
			'rate',
			'rate-economy',
			__( "Economie", 'yproject' ),
			WDG_Form_Vote::$field_group_impacts,
			FALSE,
			[
				__( "Tr&egrave;s faible", 'yproject' ),
				__( "Faible", 'yproject' ),
				__( "Moyen", 'yproject' ),
				__( "Fort", 'yproject' ),
				__( "Tr&egrave;s fort", 'yproject' )
			]
		);
		
		$this->addField(
			'rate',
			'rate-ecology',
			__( "Ecologie", 'yproject' ),
			WDG_Form_Vote::$field_group_impacts,
			FALSE,
			[
				__( "Tr&egrave;s faible", 'yproject' ),
				__( "Faible", 'yproject' ),
				__( "Moyen", 'yproject' ),
				__( "Fort", 'yproject' ),
				__( "Tr&egrave;s fort", 'yproject' )
			]
		);
		
		$this->addField(
			'rate',
			'rate-social',
			__( "Economie", 'yproject' ),
			WDG_Form_Vote::$field_group_impacts,
			FALSE,
			[
				__( "Tr&egrave;s faible", 'yproject' ),
				__( "Faible", 'yproject' ),
				__( "Moyen", 'yproject' ),
				__( "Fort", 'yproject' ),
				__( "Tr&egrave;s fort", 'yproject' )
			]
		);
		
		$this->addField(
			'text',
			'rate-other',
			__( "Autre", 'yproject' ),
			WDG_Form_Vote::$field_group_impacts
		);
		
		
		// Validate : $field_group_validate
		$this->addField(
			'radio',
			'validate-project',
			__( "Souhaitez-vous soutenir cette campagne de financement sur WE DO GOOD ?", 'yproject' ),
			WDG_Form_Vote::$field_group_validate,
			FALSE,
			[
				'1'	=> __( "Oui", 'yproject' ),
				'0' => __( "Non", 'yproject' )
			]
		);
		
		
		// Risk : $field_group_risk
		$this->addField(
			'rate',
			'risk',
			__( "Je pense que le risque est :", 'yproject' ),
			WDG_Form_Vote::$field_group_risk,
			FALSE,
			[
				__( "Tr&egrave;s faible", 'yproject' ),
				__( "Faible", 'yproject' ),
				__( "Mod&eacute;r&eacute;", 'yproject' ),
				__( "Elev&eacute;", 'yproject' ),
				__( "Tr&egrave;s &eacute;lev&eacute;", 'yproject' )
			]
		);
		
		
		// Risk : $field_group_risk
		$this->addField(
			'checkboxes',
			'info',
			__( "Avez-vous besoin de plus d'informations concernant l'un des aspects suivants ?", 'yproject' ),
			WDG_Form_Vote::$field_group_info,
			FALSE,
			[
				'more_info_service'	=> __( "Le produit / service", 'yproject' ),
				'more_info_impact'	=> __( "L'impact soci&eacute;tal", 'yproject' ),
				'more_info_team'	=> __( "La structuration de l'&eacute;quipe", 'yproject' ),
				'more_info_finance'	=> __( "Le pr&eacute;visionnel financier", 'yproject' ),
			]
		);
		
		$this->addField(
			'text',
			'more-info-other',
			__( "Autre", 'yproject' ),
			WDG_Form_Vote::$field_group_info
		);
		
	}
	
}
