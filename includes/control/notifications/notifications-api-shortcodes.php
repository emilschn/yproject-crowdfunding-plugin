<?php
/**
 * Classe de gestion des shortcodes spécifiques aux templates de mails
 */
class NotificationsAPIShortcodes {
	private static $shortcode_list = array(
		'email_config_text',

		'recipient_first_name',

		'password_reinit_link',

		'kyc_refused_info',

		'project_name',
		'project_organization_name',
		'project_url',
		'project_dashboard_url',
		'project_funding_duration',
		'project_amount_minimum_goal',
		'project_percent_reached',
		'project_max_profit_string',
		'project_date_first_payment',
		'project_royalties_percent',
		'project_royalties_transfered_amount',
		'project_royalties_minimum_amount',
		'project_royalties_remaining_amount_to_minimum',
		'project_investors_list_with_more_than_200_euros',
		'project_days_remaining_count',
		'project_days_string',
		'project_end_date_hour',

		'project_news_title',
		'project_news_content',

		'project_advice_greetings',
		'project_advice_content',
		'project_advice_priority_actions',

		'reminder_invest_intention_amount',
		'reminder_project_testimony',
		'reminder_project_image',
		'reminder_project_description',

		'investment_pending_amount',
		'investment_pending_percent_to_reach',
		'investment_pending_viban_iban',
		'investment_pending_viban_bic',
		'investment_pending_viban_holder',

		'investment_amount',
		'investment_date',
		'investment_description_text_before',
		'investment_description_text_after',
		'investment_royalties_received',
		'investment_royalties_remaining',

		'investment_error_reason',
		'investment_error_link',

		'wire_received_amount',
		'wire_transfer_amount',

		'declaration_url',
		'declaration_last_three_months',
		'declaration_due_date',
		'declaration_due_date_previous_day',
		'declaration_revenues_amount',
		'declaration_tax_info',
		'declaration_quarter_count',
		'declaration_estimation_percent',
		'declaration_estimation_year_amount',
		'declaration_estimation_quarter_amount',
		'declaration_estimation_amount_royalties',
		'declaration_estimation_amount_fees',
		'declaration_estimation_amount_total',
		'declaration_mandate_date',

		'royalties_description',
		'royalties_project_message',

		'prospect_setup_recipient_email',
		'prospect_setup_recipient_first_name',
		'prospect_setup_draft_list',
		'prospect_setup_draft_url',
		'prospect_setup_draft_url_full',
		'prospect_setup_draft_organization_name',
		'prospect_setup_draft_amount_needed',
		'prospect_setup_draft_royalties_percent',
		'prospect_setup_draft_formula',
		'prospect_setup_draft_option',
		'prospect_setup_draft_payment_amount',
		'prospect_setup_draft_payment_iban',
		'prospect_setup_draft_payment_reference',
		'prospect_setup_draft_payment_date'
	);

	private static $instance;
	public static function instance() {
		if ( !isset( self::$instance ) ) {
			self::init_shortcodes();
		}

		return self::$instance;
	}

	private static function init_shortcodes() {
		foreach (self::$shortcode_list as $shortcode) {
			add_shortcode( $shortcode, array( 'NotificationsAPIShortcodes', $shortcode ) );
		}
	}

	//*************************************
	// Données utilisées par les shortcodes
	//*************************************
	/**
	 * @var WDGUser|WDGOrganization
	 */
	private static $recipient;
	/**
	 * Définit les infos utilisateurs du destinataire
	 * @param WDGUser|WDGOrganization
	 */
	public static function set_recipient($obj_user) {
		self::$recipient = $obj_user;
	}

	/**
	 * @var String
	 */
	private static $password_reinit_link;
	/**
	 * Définit l'URL de réinitialisation de mot de passe
	 * @param String
	 */
	public static function set_password_reinit_link($link_password_reinit) {
		self::$password_reinit_link = $link_password_reinit;
	}

	/**
	 * @var String
	 */
	private static $kyc_refused_info;
	/**
	 * Définit l'URL de réinitialisation de mot de passe
	 * @param String
	 */
	public static function set_kyc_refused_info($kyc_refused_info) {
		self::$kyc_refused_info = $kyc_refused_info;
	}

	/**
	 * @var ATCF_Campaign
	 */
	private static $campaign;
	/**
	 * Définit la campagne dont on veut les données
	 * @param ATCF_Campaign
	 */
	public static function set_campaign($campaign) {
		self::$campaign = $campaign;
	}

	/**
	 * @var String
	 */
	private static $investors_list_with_more_than_200_euros_str;
	/**
	 * Définit la liste des investisseurs qui ont plus de 200 euros sur leur wallet
	 * @param String
	 */
	public static function set_investors_list_with_more_than_200_euros_str($investors_list_with_more_than_200_euros_str) {
		self::$investors_list_with_more_than_200_euros_str = $investors_list_with_more_than_200_euros_str;
	}

	/**
	 * @var String
	 */
	private static $campaign_news_title;
	/**
	 * Définit le titre d'une actualité à envoyer
	 * @param String
	 */
	public static function set_campaign_news_title($campaign_news_title) {
		self::$campaign_news_title = $campaign_news_title;
	}

	/**
	 * @var String
	 */
	private static $campaign_news_content;
	/**
	 * Définit le contenu d'une actualité à envoyer
	 * @param String
	 */
	public static function set_campaign_news_content($campaign_news_content) {
		self::$campaign_news_content = $campaign_news_content;
	}

	/**
	 * @var Array
	 */
	private static $campaign_advice;
	/**
	 * Définit les informations pour les conseils aux entrepreneurs
	 * @param Array
	 */
	public static function set_campaign_advice($campaign_advice) {
		self::$campaign_advice = $campaign_advice;
	}

	/**
	 * @var Array
	 */
	private static $reminder_data;
	/**
	 * Définit les informations envoyées dans les mails de rappels aux investisseurs
	 * @param Array
	 */
	public static function set_reminder_data($reminder_data) {
		self::$reminder_data = $reminder_data;
	}
	/**
	 * Définit le montant des informations envoyées dans les mails de rappels aux investisseurs
	 * @param Array
	 */
	public static function set_reminder_data_amount($reminder_data_amount) {
		self::$reminder_data[ 'amount' ] = $reminder_data_amount;
	}

	/**
	 * @var WDGInvestment
	 */
	private static $investment_pending;
	/**
	 * Définit l'objet de données d'investissement en attente
	 * @param WDGInvestment
	 */
	public static function set_investment_pending($investment_pending) {
		self::$investment_pending = $investment_pending;
	}

	/**
	 * @var Array
	 */
	private static $investment_pending_data;
	/**
	 * Définit les données complémentaires d'investissement en attente
	 * @param Array
	 */
	public static function set_investment_pending_data($investment_pending_data) {
		self::$investment_pending_data = $investment_pending_data;
	}

	/**
	 * @var WDGInvestment
	 */
	private static $investment;
	/**
	 * Définit l'objet de données d'investissement validé
	 * @param WDGInvestment
	 */
	public static function set_investment($investment) {
		self::$investment = $investment;
	}

	/**
	 * @var Array
	 */
	private static $investment_success_data;
	/**
	 * Définit les données complémentaires d'investissement validé
	 * @param Array
	 */
	public static function set_investment_success_data($investment_success_data) {
		self::$investment_success_data = $investment_success_data;
	}

	/**
	 * @var Array
	 */
	private static $investment_error_data;
	/**
	 * Définit les données d'erreur d'investissement
	 * @param Array
	 */
	public static function set_investment_error_data($investment_error_data) {
		self::$investment_error_data = $investment_error_data;
	}

	/**
	 * @var Object
	 */
	private static $investment_contract;
	/**
	 * Définit les données de contrat d'investissement
	 * @param Object
	 */
	public static function set_investment_contract($investment_contract) {
		self::$investment_contract = $investment_contract;
	}

	/**
	 * @var Number
	 */
	private static $investment_amount_received;
	/**
	 * Définit le montant des royalties déjà perçues sur un investissement
	 * @param Number
	 */
	public static function set_investment_amount_received($investment_amount_received) {
		self::$investment_amount_received = $investment_amount_received;
	}

	/**
	 * @var Number
	 */
	private static $amount_wire_received;
	/**
	 * Définit le montant d'un virement qui a été reçu sur la plateforme
	 * @param Number
	 */
	public static function set_amount_wire_received($amount_wire_received) {
		self::$amount_wire_received = $amount_wire_received;
	}

	/**
	 * @var Number
	 */
	private static $amount_wire_transfer;
	/**
	 * Définit le montant d'un virement qui a transféré vers un compte bancaire
	 * @param Number
	 */
	public static function set_amount_wire_transfer($amount_wire_transfer) {
		self::$amount_wire_transfer = $amount_wire_transfer;
	}

	/**
	 * @var WDGROIDeclaration
	 */
	private static $declaration;
	/**
	 * Définit la déclaration dont on va utiliser les données
	 * @param WDGROIDeclaration
	 */
	public static function set_declaration($declaration) {
		self::$declaration = $declaration;
	}

	/**
	 * @var Array
	 */
	private static $declaration_estimation_data;
	/**
	 * Définit les données de prévisionnel d'une déclaration
	 * @param Array
	 */
	public static function set_declaration_estimation_data($declaration_estimation_data) {
		self::$declaration_estimation_data = $declaration_estimation_data;
	}

	/**
	 * @var String
	 */
	private static $user_royalties_details;
	/**
	 * Définit le texte du résumé des royalties
	 * @param String
	 */
	public static function set_user_royalties_details($user_royalties_details) {
		self::$user_royalties_details = $user_royalties_details;
	}

	/**
	 * @var String
	 */
	private static $project_royalties_message;
	/**
	 * Définit le texte du message d'un projet pendant le versement de royalties
	 * @param String
	 */
	public static function set_project_royalties_message($project_royalties_message) {
		self::$project_royalties_message = $project_royalties_message;
	}

	/**
	 * @var Object
	 */
	private static $prospect_setup_draft;
	/**
	 * Définit les données d'un test d'interface prospect
	 * @param Object
	 */
	public static function set_prospect_setup_draft($prospect_setup_draft) {
		self::$prospect_setup_draft = $prospect_setup_draft;
	}

	/**
	 * @var String
	 */
	private static $prospect_setup_draft_list;
	/**
	 * Définit la liste des tests d'éligibilité d'un utilisateur
	 * @param String
	 */
	public static function set_prospect_setup_draft_list($prospect_setup_draft_list) {
		self::$prospect_setup_draft_list = $prospect_setup_draft_list;
	}

	/**
	 * @var String
	 */
	private static $prospect_setup_draft_payment_amount;
	/**
	 * Définit le montant du paiement d'un test d'éligibilité
	 * @param String
	 */
	public static function set_prospect_setup_draft_payment_amount($prospect_setup_draft_payment_amount) {
		self::$prospect_setup_draft_payment_amount = $prospect_setup_draft_payment_amount;
	}
	//*************************************

	//*************************************
	// Shortcodes
	//*************************************
	/**
	 * Texte de configuration
	 * Contenu
	 */
	public static function email_config_text($atts, $content = '') {
		$atts = shortcode_atts( array(
			'url'		=> ''
		), $atts );

		if ( empty( $atts[ 'url' ] ) ) {
			return '';
		}

		$config_text_content = WDGConfigTexts::get_config_text_by_name( $atts[ 'url' ] );

		return $config_text_content;
	}

	/**
	 * Destinataire
	 * Prénom
	 */
	public static function recipient_first_name($atts, $content = '') {
		return WDGOrganization::is_user_organization( self::$recipient->get_wpref() ) ? self::$recipient->get_name() : self::$recipient->get_firstname();
	}

	/**
	 * Mail de réinitialisation de mot de passe
	 * Lien pour réinitialiser
	 */
	public static function password_reinit_link($atts, $content = '') {
		return self::$password_reinit_link;
	}

	/**
	 * Mail de KYCs refusés
	 * Détails du refus
	 */
	public static function kyc_refused_info($atts, $content = '') {
		return self::$kyc_refused_info;
	}

	/**
	 * Levée de fonds
	 * Nom
	 */
	public static function project_name($atts, $content = '') {
		return self::$campaign->get_name();
	}

	/**
	 * Levée de fonds
	 * Nom de l'organisation
	 */
	public static function project_organization_name($atts, $content = '') {
		$organization = self::$campaign->get_organization();
		$WDGOrganization = new WDGOrganization( $organization->wpref );

		return $WDGOrganization->get_name();
	}

	/**
	 * Levée de fonds
	 * URL
	 */
	public static function project_url($atts, $content = '') {
		return self::$campaign->get_public_url();
	}

	/**
	 * Levée de fonds
	 * URL du TBPP
	 */
	public static function project_dashboard_url($atts, $content = '') {
		$campaign_id = self::$campaign->ID;
		$dashboard_url = WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' .$campaign_id;

		return $dashboard_url;
	}

	/**
	 * Levée de fonds
	 * Durée du financement
	 */
	public static function project_funding_duration($atts, $content = '') {
		return self::$campaign->funding_duration();
	}

	/**
	 * Levée de fonds
	 * Objectif minimum
	 */
	public static function project_amount_minimum_goal($atts, $content = '') {
		return self::$campaign->minimum_goal( FALSE );
	}

	/**
	 * Levée de fonds
	 * Pourcentage atteint
	 */
	public static function project_percent_reached($atts, $content = '') {
		return self::$campaign->percent_minimum_completed( FALSE );
	}

	/**
	 * Levée de fonds
	 * Rendement maximum (chaine complète)
	 */
	public static function project_max_profit_string($atts, $content = '') {
		return self::$campaign->maximum_profit_str();
	}

	/**
	 * Levée de fonds
	 * Date de premier paiement
	 */
	public static function project_date_first_payment($atts, $content = '') {
		$project_date_first_payment = self::$campaign->first_payment_date();
		$date_first_payment = new DateTime( $project_date_first_payment );
		$months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
		$month_str = __( $months[ $date_first_payment->format('m') - 1 ] );
		$project_date_first_payment_month_str = $month_str . ' ' . $date_first_payment->format( 'Y' );

		return $project_date_first_payment_month_str;
	}

	/**
	 * Levée de fonds
	 * Pourcent de royalties
	 */
	public static function project_royalties_percent() {
		if ( !empty( self::$campaign ) ) {
			return self::$campaign->roi_percent();
		}

		return '0';
	}

	/**
	 * Levée de fonds
	 * Montant de royalties déjà versées
	 */
	public static function project_royalties_transfered_amount($atts, $content = '') {
		$amount_transferred = 0;
		$existing_roi_declarations = self::$campaign->get_roi_declarations();
		foreach ( $existing_roi_declarations as $declaration_object ) {
			if ( $declaration_object[ 'status' ] == WDGROIDeclaration::$status_finished ) {
				$amount_transferred += $declaration_object[ 'total_roi' ];
			}
		}
		$amount_transferred_str = UIHelpers::format_number( $amount_transferred );

		return $amount_transferred_str;
	}

	/**
	 * Levée de fonds
	 * Montant minimum à verser
	 */
	public static function project_royalties_minimum_amount($atts, $content = '') {
		$amount_minimum_royalties = self::$campaign->current_amount( FALSE ) * self::$campaign->minimum_profit();
		$amount_minimum_royalties_str = UIHelpers::format_number( $amount_minimum_royalties );

		return $amount_minimum_royalties_str;
	}

	/**
	 * Levée de fonds
	 * Montant minimum restant à verser
	 */
	public static function project_royalties_remaining_amount_to_minimum($atts, $content = '') {
		$amount_minimum_royalties = self::$campaign->current_amount( FALSE ) * self::$campaign->minimum_profit();
		$amount_transferred = 0;
		$existing_roi_declarations = self::$campaign->get_roi_declarations();
		foreach ( $existing_roi_declarations as $declaration_object ) {
			if ( $declaration_object[ 'status' ] == WDGROIDeclaration::$status_finished ) {
				$amount_transferred += $declaration_object[ 'total_roi' ];
			}
		}
		$amount_remaining = $amount_minimum_royalties - $amount_transferred;
		$amount_remaining_str = UIHelpers::format_number( $amount_remaining );

		return $amount_remaining_str;
	}

	/**
	 * Levée de fonds
	 * Liste des investisseurs qui ont +200 euros sur leur wallet
	 */
	public static function project_investors_list_with_more_than_200_euros($atts, $content = '') {
		return self::$investors_list_with_more_than_200_euros_str;
	}

	/**
	 * Levée de fonds
	 * Nombre de jours restants
	 */
	public static function project_days_remaining_count($atts, $content = '') {
		return self::$campaign->days_remaining();
	}

	/**
	 * Levée de fonds
	 * Nombre de jours restants (texte complet)
	 */
	public static function project_days_string($atts, $content = '') {
		$nb_days_remaining = self::$campaign->days_remaining();
		$str_days = ($nb_days_remaining > 1) ? __( 'jours', 'yproject' ) : __( 'jour', 'yproject' );

		return $nb_days_remaining . ' ' . $str_days;
	}

	/**
	 * Levée de fonds
	 * Date et jour de fin de la levée de fonds
	 */
	public static function project_end_date_hour($atts, $content = '') {
		return self::$campaign->end_date( 'd/m/Y h:i' );
	}

	/**
	 * Levée de fonds - Actualité
	 * Titre
	 */
	public static function project_news_title($atts, $content = '') {
		return self::$campaign_news_title;
	}

	/**
	 * Levée de fonds - Actualité
	 * Contenu
	 */
	public static function project_news_content($atts, $content = '') {
		return self::$campaign_news_content;
	}

	/**
	 * Levée de fonds - Conseils
	 * Introduction aléatoire de bienvenue
	 */
	public static function project_advice_greetings($atts, $content = '') {
		return self::$campaign_advice[ 'greetings' ];
	}

	/**
	 * Levée de fonds - Conseils
	 * Contenu
	 */
	public static function project_advice_content($atts, $content = '') {
		return self::$campaign_advice[ 'content' ];
	}

	/**
	 * Levée de fonds - Conseils
	 * Actions prioritaires
	 */
	public static function project_advice_priority_actions($atts, $content = '') {
		return self::$campaign_advice[ 'priority_actions' ];
	}

	/**
	 * Rappel
	 * Montant intention d'investissement
	 */
	public static function reminder_invest_intention_amount($atts, $content = '') {
		$reminder_data_amount = self::$reminder_data[ 'amount' ];
		$reminder_data_amount_str = UIHelpers::format_number( $reminder_data_amount );

		return $reminder_data_amount_str;
	}

	/**
	 * Rappel
	 * Témoignage projet
	 */
	public static function reminder_project_testimony($atts, $content = '') {
		return self::$reminder_data[ 'testimony' ];
	}

	/**
	 * Rappel
	 * Image d'illustration
	 */
	public static function reminder_project_image($atts, $content = '') {
		return self::$reminder_data[ 'image' ];
	}

	/**
	 * Rappel
	 * Description du projet
	 */
	public static function reminder_project_description($atts, $content = '') {
		return self::$reminder_data[ 'description' ];
	}

	/**
	 * Investissement en attente
	 * Montant
	 */
	public static function investment_pending_amount($atts, $content = '') {
		$amount_total = self::$investment_pending->get_session_amount();
		$amount_total_str = UIHelpers::format_number( $amount_total );

		return $amount_total_str;
	}

	/**
	 * Investissement en attente
	 * Pourcentage que ça permettra d'atteindre
	 */
	public static function investment_pending_percent_to_reach($atts, $content = '') {
		$percent_to_reach = round( ( self::$campaign->current_amount( FALSE ) +  self::$investment_pending->get_session_amount() ) / self::$campaign->minimum_goal( FALSE ) * 100 );

		return $percent_to_reach;
	}

	/**
	 * Investissement en attente
	 * Compte bancaire de destination - IBAN
	 */
	public static function investment_pending_viban_iban($atts, $content = '') {
		return self::$investment_pending_data[ 'viban_iban' ];
	}

	/**
	 * Investissement en attente
	 * Compte bancaire de destination - BIC
	 */
	public static function investment_pending_viban_bic($atts, $content = '') {
		return self::$investment_pending_data[ 'viban_bic' ];
	}

	/**
	 * Investissement en attente
	 * Compte bancaire de destination - Propriétaire du compte
	 */
	public static function investment_pending_viban_holder($atts, $content = '') {
		return self::$investment_pending_data[ 'viban_holder' ];
	}

	/**
	 * Investissement
	 * Montant
	 */
	public static function investment_amount() {
		if ( !empty( self::$investment_contract ) ) {
			return self::$investment_contract->subscription_amount;
		}

		if ( !empty( self::$investment ) ) {
			$amount = self::$investment->get_session_amount();
			if ( empty( $amount ) ) {
				$amount = self::$investment->get_saved_amount();
			}

			if ( !empty( $amount ) ) {
				return $amount;
			}
		}

		return 0;
	}

	/**
	 * Investissement
	 * Date
	 */
	public static function investment_date() {
		if ( !empty( self::$investment_contract ) ) {
			return self::$investment_contract->subscription_date;
		} else {
			return self::$investment->get_saved_date_gmt();
		}
	}

	/**
	 * Investissement
	 * Contenu 1
	 */
	public static function investment_description_text_before() {
		return self::$investment_success_data[ 'text_before' ];
	}

	/**
	 * Investissement
	 * Contenu 2
	 */
	public static function investment_description_text_after() {
		return self::$investment_success_data[ 'text_after' ];
	}

	/**
	 * Investissement
	 * Royalties perçues
	 */
	public static function investment_royalties_received() {
		if ( !empty( self::$investment_amount_received ) ) {
			return self::$investment_amount_received;
		} else {
			if ( !empty( self::$investment_contract ) ) {
				return self::$investment_contract->amount_received;
			} else {
				return 0;
			}
		}
	}

	/**
	 * Investissement
	 * Royalties restantes
	 */
	public static function investment_royalties_remaining() {
		if ( !empty( self::$investment_contract ) ) {
			return self::$investment_contract->subscription_amount - self::$investment_contract->amount_received;
		} else {
			return 0;
		}
	}

	/**
	 * Erreur d'investissement
	 * Raison
	 */
	public static function investment_error_reason() {
		return self::$investment_error_data[ 'reason' ];
	}

	/**
	 * Erreur d'investissement
	 * Lien de reprise
	 */
	public static function investment_error_link() {
		return self::$investment_error_data[ 'link' ];
	}

	/**
	 * Virement reçu
	 * Montant
	 */
	public static function wire_received_amount($atts, $content = '') {
		return self::$amount_wire_received;
	}

	/**
	 * Virement vers compte bancaire
	 * Montant
	 */
	public static function wire_transfer_amount($atts, $content = '') {
		return self::$amount_wire_transfer;
	}

	/**
	 * Déclaration de CA
	 * URL directe
	 */
	public static function declaration_url($atts, $content = '') {
		$declaration_direct_url = WDG_Redirect_Engine::override_get_page_url( 'declarer-chiffre-daffaires' ) . '?campaign_id='.self::$campaign->ID.'&declaration_id='.self::$declaration->id;

		return $declaration_direct_url;
	}

	/**
	 * Déclaration de CA
	 * Trois derniers mois en texte
	 */
	public static function declaration_last_three_months($atts, $content = '') {
		$date_due_previous_day = new DateTime( self::$declaration->date_due );
		$date_due_previous_day->sub( new DateInterval( 'P1D' ) );
		$months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
		$nb_fields = self::$campaign->get_turnover_per_declaration();
		$date_last_months = new DateTime( self::$declaration->date_due );
		$date_last_months->sub( new DateInterval( 'P'.$nb_fields.'M' ) );
		$last_months_str = '';
		for ( $i = 0; $i < $nb_fields; $i++ ) {
			$last_months_str .= __( $months[ $date_last_months->format('m') - 1 ] );
			if ( $i < $nb_fields - 2 ) {
				$last_months_str .= ', ';
			}
			if ( $i == $nb_fields - 2 ) {
				$last_months_str .= ' et ';
			}
			$date_last_months->add( new DateInterval( 'P1M' ) );
		}
		$date_due = new DateTime( self::$declaration->date_due );
		$year = $date_due->format( 'Y' );
		if ( $date_due->format( 'n' ) < 4 ) {
			$year--;
		}
		$last_months_str .= ' ' . $year;

		return $last_months_str;
	}

	/**
	 * Déclaration de CA
	 * Jour prévu pour la déclaration
	 */
	public static function declaration_due_date($atts, $content = '') {
		$date_due = new DateTime( self::$declaration->date_due );

		return $date_due->format( 'd/m/Y' );
	}

	/**
	 * Déclaration de CA
	 * Veille du jour prévu pour la déclaration
	 */
	public static function declaration_due_date_previous_day($atts, $content = '') {
		$date_due_previous_day = new DateTime( self::$declaration->date_due );
		$date_due_previous_day->sub( new DateInterval( 'P1D' ) );

		return $date_due_previous_day->format( 'd/m/Y' );
	}

	/**
	 * Déclaration de CA
	 * Montant du CA
	 */
	public static function declaration_revenues_amount($atts, $content = '') {
		return self::$declaration->get_amount_with_adjustment();
	}

	/**
	 * Déclaration de CA
	 * Informations fiscales
	 */
	public static function declaration_tax_info($atts, $content = '') {
		$tax_infos = '';
		if (self::$declaration->has_paid_gain() ) {
			$tax_infos = "<br><br>Vos investisseurs ont réalisé une plus-value sur leur investissement.";
			$tax_infos .= "Ceux et celles dont le foyer fiscal est en France et qui sont soumis à l’impôt sur le revenu ";
			$tax_infos .= "verront donc 30 % de leur plus-value prélevés à la source (Prélèvement Forfaitaire Unique - flat tax), sauf en cas de demande de dispense de leur part. ";
			$tax_infos .= '<a href="https://support.wedogood.co/investir-et-suivre-mes-investissements/fiscalit%C3%A9-et-comptabilit%C3%A9/quelle-est-la-comptabilit%C3%A9-et-la-fiscalit%C3%A9-de-mon-investissement">En savoir plus sur la fiscalité des investissements</a>.';
		}

		return $tax_infos;
	}

	/**
	 * Déclaration de CA
	 * Numéro du trimestre en cours
	 */
	public static function declaration_quarter_count($atts, $content = '') {
		return self::$declaration_estimation_data[ 'quarter_count' ];
	}

	/**
	 * Déclaration de CA
	 * Montant prévisionnel de l'année
	 */
	public static function declaration_estimation_year_amount($atts, $content = '') {
		return self::$declaration_estimation_data[ 'year_amount' ];
	}

	/**
	 * Déclaration de CA
	 * Pourcent prévisionnel de cette partie de l'année
	 */
	public static function declaration_estimation_percent($atts, $content = '') {
		return self::$declaration_estimation_data[ 'percent' ];
	}

	/**
	 * Déclaration de CA
	 * Montant prévisionnel du trimestre
	 */
	public static function declaration_estimation_quarter_amount($atts, $content = '') {
		return self::$declaration_estimation_data[ 'quarter_amount' ];
	}

	/**
	 * Déclaration de CA
	 * Montant prévisionnel des royalties
	 */
	public static function declaration_estimation_amount_royalties($atts, $content = '') {
		return self::$declaration_estimation_data[ 'amount_royalties' ];
	}

	/**
	 * Déclaration de CA
	 * Montant prévisionnel des frais de gestion des royalties
	 */
	public static function declaration_estimation_amount_fees($atts, $content = '') {
		return self::$declaration_estimation_data[ 'amount_fees' ];
	}

	/**
	 * Déclaration de CA
	 * Montant prévisionnel total
	 */
	public static function declaration_estimation_amount_total($atts, $content = '') {
		return self::$declaration_estimation_data[ 'amount_total' ];
	}

	/**
	 * Déclaration de CA
	 * Date de prélèvement prévue
	 */
	public static function declaration_mandate_date($atts, $content = '') {
		$date_in_5_days = new DateTime();
		$date_in_5_days->add( new DateInterval('P5D') );
		$mandate_wire_date = $date_in_5_days->format( 'd/m/Y' );

		return $mandate_wire_date;
	}

	/**
	 * Relevé de royalties
	 * Description automatique
	 */
	public static function royalties_description($atts, $content = '') {
		return self::$user_royalties_details;
	}

	/**
	 * Message de versement individuel
	 * Message du projet
	 */
	public static function royalties_project_message($atts, $content = '') {
		return self::$project_royalties_message;
	}

	/**
	 * Interface prospect
	 * E-mail du destinataire
	 */
	public static function prospect_setup_recipient_email($atts, $content = '') {
		return self::$prospect_setup_draft->email;
	}

	/**
	 * Interface prospect
	 * Prénom du destinataire
	 */
	public static function prospect_setup_recipient_first_name($atts, $content = '') {
		$metadata_decoded = json_decode( self::$prospect_setup_draft->metadata );
		$recipient_name = '';
		if ( !empty( $metadata_decoded->user->name ) ) {
			$recipient_name = $metadata_decoded->user->name;
		}

		return $recipient_name;
	}

	/**
	 * Interface prospect
	 * Liste des tests démarrés
	 */
	public static function prospect_setup_draft_list($atts, $content = '') {
		return self::$prospect_setup_draft_list;
	}

	/**
	 * Interface prospect
	 * 	URL du test
	 */
	public static function prospect_setup_draft_url($atts, $content = '') {
		$draft_url = WDG_Redirect_Engine::override_get_page_url( 'financement/eligibilite' ) . '?guid=' . self::$prospect_setup_draft->guid;

		return $draft_url;
	}

	/**
	 * Interface prospect
	 * URL complète du test
	 */
	public static function prospect_setup_draft_url_full($atts, $content = '') {
		$draft_url = WDG_Redirect_Engine::override_get_page_url( 'financement/eligibilite' ) . '?guid=' . self::$prospect_setup_draft->guid;

		return $draft_url;
	}

	/**
	 * Interface prospect
	 * Nom de l'organisation
	 */
	public static function prospect_setup_draft_organization_name($atts, $content = '') {
		$metadata_decoded = json_decode( self::$prospect_setup_draft->metadata );

		return $metadata_decoded->organization->name;
	}

	/**
	 * Interface prospect
	 * Montant recherché
	 */
	public static function prospect_setup_draft_amount_needed($atts, $content = '') {
		$metadata_decoded = json_decode( self::$prospect_setup_draft->metadata );

		return $metadata_decoded->project->amountNeeded * 1000;
	}

	/**
	 * Interface prospect
	 * Pourcent de royalties proposé
	 */
	public static function prospect_setup_draft_royalties_percent($atts, $content = '') {
		$metadata_decoded = json_decode( self::$prospect_setup_draft->metadata );

		return $metadata_decoded->project->royaltiesAmount;
	}

	/**
	 * Interface prospect
	 * 	Formule sélectionnée
	 */
	public static function prospect_setup_draft_formula($atts, $content = '') {
		$metadata_decoded = json_decode( self::$prospect_setup_draft->metadata );
		$formula = '';
		switch ( $metadata_decoded->project->circlesToCommunicate ) {
			case 'lovemoney':
				$formula = 'Formule Love Money';
				break;
			case 'private':
				$formula = 'Formule Réseau privé';
				break;
			case 'public':
				$formula = 'Formule Crowdfunding';
				break;
		}

		return $formula;
	}

	/**
	 * Interface prospect
	 * Option sélectionnée
	 */
	public static function prospect_setup_draft_option($atts, $content = '') {
		$metadata_decoded = json_decode( self::$prospect_setup_draft->metadata );
		$options = '';
		if ( $metadata_decoded->project->needCommunicationAdvice ) {
			$options = 'Accompagnement Intégral';
		} elseif ( $metadata_decoded->project->circlesToCommunicate != 'lovemoney' && !$metadata_decoded->project->alreadydonecrowdfunding ) {
			$options = 'Accompagnement Intégral';
		} else {
			$options = 'Accompagnement Essentiel';
		}

		return $options;
	}

	/**
	 * Interface prospect
	 * Montant du paiement
	 */
	public static function prospect_setup_draft_payment_amount($atts, $content = '') {
		return self::$prospect_setup_draft_payment_amount;
	}

	/**
	 * Interface prospect
	 * IBAN pour le paiement par virement
	 */
	public static function prospect_setup_draft_payment_iban($atts, $content = '') {
		return WDG_IBAN;
	}

	/**
	 * Interface prospect
	 * Référence du paiement
	 */
	public static function prospect_setup_draft_payment_reference($atts, $content = '') {
		$metadata_decoded = json_decode( self::$prospect_setup_draft->metadata );

		return $metadata_decoded->organization->name;
	}

	/**
	 * Interface prospect
	 * Date du paiement
	 */
	public static function prospect_setup_draft_payment_date($atts, $content = '') {
		$today_datetime = new DateTime();

		return $today_datetime->format( 'd/m/Y H:i' );
	}
	//*************************************
}
