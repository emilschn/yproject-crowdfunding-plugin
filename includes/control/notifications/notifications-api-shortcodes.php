<?php
/**
 * Classe de gestion des shortcodes spécifiques aux templates de mails
 */
class NotificationsAPIShortcodes {
	private static $shortcode_list = array(
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

		'investment_error_reason',
		'investment_error_link',

		'wire_received_amount',
		'wire_transfer_amount',

		'declaration_url',
		'declaration_last_three_months',
		'declaration_revenues_amount',
		'declaration_tax_info',
		'declaration_quarter_count',
		'declaration_estimation_percent',
		'declaration_estimation_year_amount',
		'declaration_estimation_quarter_amount',
		'declaration_amount_royalties',
		'declaration_amount_fees',
		'declaration_amount_total',
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
	 * @var WDGUser
	 */
	private static $recipient;
	/**
	 * Définit les infos utilisateurs du destinataire
	 * @param WDGUser
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
	//*************************************

	//*************************************
	// Shortcodes
	//*************************************
	/**
	 * Destinataire
	 * Prénom
	 */
	public static function recipient_first_name($atts, $content = '') {
		return self::$recipient->get_firstname();
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
	public static function project_royalties_percent($atts, $content = '') {
		return self::$campaign->roi_percent();
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
	public static function investment_amount($atts, $content = '') {
		return self::$investment->get_session_amount();
	}

	/**
	 * Investissement
	 * Date
	 */
	public static function investment_date($atts, $content = '') {
		return self::$investment->get_saved_date_gmt();
	}

	/**
	 * Investissement
	 * Contenu 1
	 */
	public static function investment_description_text_before($atts, $content = '') {
		return self::$investment_success_data[ 'text_before' ];
	}

	/**
	 * Investissement
	 * Contenu 2
	 */
	public static function investment_description_text_after($atts, $content = '') {
		return self::$investment_success_data[ 'text_after' ];
	}

	/**
	 * Erreur d'investissement
	 * Raison
	 */
	public static function investment_error_reason($atts, $content = '') {
		return self::$investment_error_data[ 'reason' ];
	}

	/**
	 * Erreur d'investissement
	 * Lien de reprise
	 */
	public static function investment_error_link($atts, $content = '') {
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
	//*************************************
}
