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
	//*************************************
}
