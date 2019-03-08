<?php
class WDG_Form_Vote extends WDG_Form {
	
	public static $name = 'project-vote';
	
	public static $field_group_hidden = 'vote-hidden';
	public static $field_group_impacts = 'vote-impact';
	public static $field_group_validate = 'vote-validate';
	public static $field_group_risk = 'vote-risk';
	public static $field_group_info = 'vote-info';
	public static $field_group_invest = 'vote-invest';
	public static $field_group_advice = 'vote-advice';
	
	private $campaign_id;
	
	public function __construct( $campaign_id = FALSE ) {
		parent::__construct( WDG_Form_Vote::$name );
		$this->campaign_id = $campaign_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			WDG_Form_Vote::$field_group_hidden,
			WDG_Form_Vote::$name
		);
		
		$this->addField(
			'hidden',
			'campaign_id',
			'',
			WDG_Form_Vote::$field_group_hidden,
			$this->campaign_id
		);
		
		// Impacts : $field_group_impacts
		$this->addField(
			'rate',
			'rate-economy',
			__( "Sur l'&eacute;conomie (emploi, &eacute;conomie locale, innovation) :", 'yproject' ),
			WDG_Form_Vote::$field_group_impacts,
			FALSE,
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
			__( "Sur l'environnement (ressources, biodiversit&eacute;, pollution) :", 'yproject' ),
			WDG_Form_Vote::$field_group_impacts,
			FALSE,
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
			__( "Au niveau social (conditions de vie et de travail, lien social) :", 'yproject' ),
			WDG_Form_Vote::$field_group_impacts,
			FALSE,
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
			__( "Autre(s) :", 'yproject' ),
			WDG_Form_Vote::$field_group_impacts
		);
		
		
		// Validate : $field_group_validate
		$this->addField(
			'rate',
			'rate-project',
			__( "Globalement, ce projet...", 'yproject' ),
			WDG_Form_Vote::$field_group_validate,
			4,
			FALSE,
			[
				__( "Je n'y crois pas", 'yproject' ),
				__( "Je ne suis pas convaincu", 'yproject' ),
				__( "Je ne sais pas", 'yproject' ),
				__( "Je pense qu'il a du potentiel", 'yproject' ),
				__( "J'y crois !", 'yproject' )
			]
		);
		
		
		// Risk : $field_group_risk
		$this->addField(
			'rate',
			'risk',
			__( "Je pense qu'investir sur ce projet repr&eacute;sente un risque :", 'yproject' ),
			WDG_Form_Vote::$field_group_risk,
			FALSE,
			FALSE,
			[
				__( "Tr&egrave;s faible", 'yproject' ),
				__( "Faible", 'yproject' ),
				__( "Mod&eacute;r&eacute;", 'yproject' ),
				__( "Elev&eacute;", 'yproject' ),
				__( "Tr&egrave;s &eacute;lev&eacute;", 'yproject' )
			]
		);
		
		
		// Info : $field_group_info
		$this->addField(
			'checkboxes',
			'info',
			__( "J'ai besoin de plus d'information concernant ces aspects :", 'yproject' ),
			WDG_Form_Vote::$field_group_info,
			FALSE,
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
			__( "Autre(s) :", 'yproject' ),
			WDG_Form_Vote::$field_group_info
		);
		
		
		// Invest : $field_group_invest
		
		$this->addField(
			'text-money',
			'invest-sum',
			__( "J'ai l'intention d'investir :", 'yproject' ),
			WDG_Form_Vote::$field_group_invest
		);
		
		
		// Advice : $field_group_advice
		
		$this->addField(
			'textarea',
			'advice',
			__( "Mes conseils ou encouragements pour le(s) porteur(s) de projet :", 'yproject' ),
			WDG_Form_Vote::$field_group_advice
		);
		
		$this->addField(
			'checkboxes',
			'',
			'',
			WDG_Form_Vote::$field_group_advice,
			FALSE,
			FALSE,
			[
				'publish-advice'	=> __( "Je souhaite que mes conseils soient publi&eacute;s sur la page de pr&eacute;sentation du projet", 'yproject' )
			]
		);
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		$feedback_slide = 4;
		
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$campaign = new ATCF_Campaign( $campaign_id );
		$WDGUser_current = WDGUser::current();
		
		// Utilisateur déconnecté
		if ( !is_user_logged_in() ) {
			$error = array(
				'code'		=> 'user-logged-out',
				'text'		=> __( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
		
		// Evaluation terminée
		} else if ( $campaign->time_remaining_str() == '-' ) {
			$error = array(
				'code'		=> 'vote-finished',
				'text'		=> __( "L'&eacute;valuation est termin&eacute;e.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );
				
		// A déjà voté (ne devrait pas arriver...)
		} else if ( $WDGUser_current->has_voted_on_campaign( $campaign_id ) ) {
			$error = array(
				'code'		=> 'already-voted',
				'text'		=> __( "Vous avez d&eacute;j&agrave; vot&eacute;.", 'yproject' ),
				'element'	=> 'general'
			);
			array_push( $feedback_errors, $error );

		// Analyse du formulaire
		} else {
			
			// C'est la seule réponse forcée mais pas initialisée
			$rate_project = $this->getInputRate( 'rate-project', 5 );
			if ( $rate_project === 0 ) {
				$feedback_slide = 1;
				$error = array(
					'code'		=> 'rate-project',
					'text'		=> __( "Vous n'avez pas exprim&eacute; votre vote.", 'yproject' ),
					'element'	=> 'rate-project'
				);
				array_push( $feedback_errors, $error );
			}
			
			
			// Vérifications sur la valeur d'investissement saisie
			$invest_sum = $this->getInputText( 'invest-sum' );
			if ( empty( $invest_sum ) ) {
				$invest_sum = 0;
			}
			if ( !is_numeric( $invest_sum ) || $invest_sum < 0 ) {
				$feedback_slide = 4;
				$error = array(
					'code'		=> 'invest-sum',
					'text'		=> __( "Votre intention d'investissement doit &ecirc;tre un nombre.", 'yproject' ),
					'element'	=> 'invest-sum'
				);
				array_push( $feedback_errors, $error );
			}

			if ( empty( $feedback_errors ) ) {
				$rate_economy = $this->getInputRate( 'rate-economy', 5 );
				$rate_ecology = $this->getInputRate( 'rate-ecology', 5 );
				$rate_social = $this->getInputRate( 'rate-social', 5 );
				$rate_other = $this->getInputText( 'rate-other' );
				$rate_risk = $this->getInputRate( 'risk', 5 );
				$more_info_service = $this->getInputChecked( 'more_info_service' );
				$more_info_impact = $this->getInputChecked( 'more_info_impact' );
				$more_info_team = $this->getInputChecked( 'more_info_team' );
				$more_info_finance = $this->getInputChecked( 'more_info_finance' );
				$more_info_other = $this->getInputText( 'more-info-other' );
				$advice = $this->getInputText( 'advice' );
				$publish_advice = $this->getInputChecked( 'publish-advice' );

				// Ajout à la base de données
				global $wpdb;
				date_default_timezone_set("Europe/Paris");
				$table_name = $wpdb->prefix . "ypcf_project_votes";
				$vote_result = $wpdb->insert( $table_name, array ( 
					'user_id'			=> $WDGUser_current->get_wpref(),
					'post_id'			=> $campaign_id,
					'impact_economy'	=> $rate_economy,
					'impact_environment'=> $rate_ecology,
					'impact_social'		=> $rate_social,
					'impact_other'		=> $rate_other,
					'validate_project'	=> ( $rate_project > 2 ) ? 1 : 0,
					'rate_project'		=> $rate_project,
					'invest_sum'		=> $invest_sum,
					'invest_risk'		=> $rate_risk,
					'more_info_impact'	=> $more_info_impact,
					'more_info_service'	=> $more_info_service,
					'more_info_team'	=> $more_info_team,
					'more_info_finance'	=> $more_info_finance,
					'more_info_other'	=> $more_info_other,
					'advice'			=> $advice,
					'date'				=> date_format( new DateTime(), 'Y-m-d H:i:s' )
				));
				
				if ( !$vote_result ) {
					$error = array(
						'code'		=> 'vote-save',
						'text'		=> __( "Il y a eu une erreur lors de l'enregistrement.", 'yproject' ),
						'element'	=> 'general'
					);
					array_push( $feedback_errors, $error );
				
				// Si pas d'erreur, on poste le formulaire de sondage
				} else {
					$core = ATCF_CrowdFunding::instance();
					$core->include_form( 'invest-poll' );
					$WDGPollForm = new WDG_Form_Invest_Poll( $campaign_id, $WDGUser_current->get_wpref(), 'vote' );
					$WDGPollForm->postForm( $invest_sum );
					
					if ( $invest_sum > 0 ) {
						if ( !$WDGUser_current->is_lemonway_registered() ) {
							WDGQueue::add_vote_authentication_needed_reminder( $WDGUser_current->get_wpref(), $WDGUser_current->get_email(), $campaign->get_name(), $campaign->get_api_id() );
						} else {
							WDGQueue::add_vote_authenticated_reminder( $WDGUser_current->get_wpref(), $WDGUser_current->get_email(), $campaign->get_name(), $campaign->ID, $campaign->get_api_id() );
						}
					}
				}
				
				// Ajout du suivi du projet
				if ( $rate_project > 1 ) {
					$table_jcrois = $wpdb->prefix . "jycrois";
					$users = $wpdb->get_results( "SELECT * FROM $table_jcrois WHERE campaign_id = ".$campaign_id." AND user_id=".$WDGUser_current->get_wpref() );
					if ( empty($users[0]->ID) ) {
						$wpdb->insert( 
							$table_jcrois,
							array(
								'user_id'		=> $WDGUser_current->get_wpref(),
								'campaign_id'   => $campaign_id
							)
						);
					}
				}

				// Sauvegarde du commentaire sur la campagne
				if ( $publish_advice && !empty( $advice ) ) {
					$current_user = wp_get_current_user();
					$user_name = $current_user->display_name;
					$user_url = $current_user->user_url;
					$data = array(
						'comment_post_ID'		=> $campaign_id,
						'comment_author'		=> $user_name,
						'comment_author_email'	=> $WDGUser_current->get_email(),
						'comment_author_url'	=> $user_url,
						'comment_content'		=> $advice,
						'comment_type'			=> '',
						'comment_parent'		=> 0,
						'user_id'				=> $WDGUser_current->get_wpref(),
						'comment_agent'			=> 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
						'comment_date'			=> current_time('mysql'),
						'comment_approved'		=> 1
					);

					wp_insert_comment($data);
				}
			}
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors,
			'gotoslide'	=> $feedback_slide
		);
		
		return $buffer;
	}
	
	public function postFormAjax() {
		$buffer = $this->postForm();
		echo json_encode( $buffer );
		exit();
	}
	
}
