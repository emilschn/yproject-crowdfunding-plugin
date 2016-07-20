<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function atcf_get_campaign( $post_campaign ) {
	$campaign = new ATCF_Campaign( $post_campaign );
	return $campaign;
}

/**
 * Récupère la campagne en cours
 * @return objet campagne
 */
function atcf_get_current_campaign() {
	global $campaign_id, $is_campaign, $is_campaign_page, $post_campaign, $post;
	//Si l'id de campagne n'a pas encore été trouvé, on va le récupérer
	if (empty($campaign_id)) {
		$campaign_id = '';
		if (is_category()) {
			global $cat;
			$campaign_id = atcf_get_campaign_id_from_category($cat);
		} else {
			$campaign_id = (isset($_GET['campaign_id'])) ? $_GET['campaign_id'] : $post->ID;
		}
	}
	
	//On a un id, alors on fait les vérifications pour savoir si c'est bien une campagne
	if (!empty($campaign_id)) {
		$is_campaign = (get_post_meta($campaign_id, 'campaign_goal', TRUE) != '');
		$is_campaign_page = $is_campaign && ($campaign_id == $post->ID);
		
		//Si c'est bien une campagne, on définit les objets utiles
		if ($is_campaign) {
			$post_campaign = get_post($campaign_id);
			$campaign = atcf_get_campaign($post_campaign);
			$campaign->set_current_lang(get_locale());
		}
	}
	
	return $campaign;
}

function atcf_get_campaign_id_from_category($category) {
	$this_category = get_category($category);
	$this_category_name = $this_category->name;
	$name_exploded = explode('cat', $this_category_name);
	if (count($name_exploded) > 1) {
		$campaign_id = $name_exploded[1];
	}
	return $campaign_id;
}

function atcf_get_campaign_post_by_payment_id($payment_id) {
	$downloads = edd_get_payment_meta_downloads($payment_id); 
	$download_id = (is_array($downloads[0])) ? $downloads[0]["id"] : $downloads[0];
	return get_post($download_id);
}

/** Single Campaign *******************************************************/

class ATCF_Campaign {
	public $ID;
	public $data;
        
	/**
	 * Default number of days of vote
	 * @var int
	 */
	public static $vote_duration = 30;

	/**
	 * Number of voters required to go to next step
	 * @var int
	 */
	public static $voters_min_required = 50;

	/**
	 * The percent score of "yes" votes required to go to next step
	 * @var int
	 */
	public static $vote_score_min_required = 50;
        
	/**
	 * The percent of min goal required in invest promises during vote
	 * @var int
	 */
	public static $vote_percent_invest_ready_min_required = 50;

	public static $campaign_status_preparing = 'preparing';
	public static $campaign_status_validated = 'validated';
	public static $campaign_status_preview = 'preview';
	public static $campaign_status_vote = 'vote';
	public static $campaign_status_collecte = 'collecte';
	public static $campaign_status_funded = 'funded';
	public static $campaign_status_archive = 'archive';

	static public function get_campaign_status_list(){
		return array(
			ATCF_Campaign::$campaign_status_preparing => 'Pr&eacute;paration',
			ATCF_Campaign::$campaign_status_preview => 'Avant-premi&egrave;re',
			ATCF_Campaign::$campaign_status_vote => 'Vote',
			ATCF_Campaign::$campaign_status_collecte=> 'Collecte',
			ATCF_Campaign::$campaign_status_funded => 'Termin&eacute',
			ATCF_Campaign::$campaign_status_archive => 'Archiv&eacute'
		);
	}

	function __construct( $post ) {
		$this->data = get_post( $post );
		$this->ID   = $this->data->ID;
	}

	/**
	 * @param string $key The meta key to fetch
	 * @return string $meta The fetched value
	 */
	public function __get( $key ) {
		if (is_object($this->data)) {
		    $meta = apply_filters( 'atcf_campaign_meta_' . $key, $this->data->__get( $key ) );
		}

		return $meta;
	}

    public function __set( $key, $value) {
        if (is_object($this->data)) {
            update_post_meta($this->ID, $key, $value);
        }
    }
	
/*******************************************************************************
 * METAS
 ******************************************************************************/
	/**
	 * Version du type de projet
	 * @return int 
	 */
	public static $key_edit_version = 'campaign_edit_version';
	public function edit_version() {
		$version = $this->__get(ATCF_Campaign::$key_edit_version);
		if (!isset($version) || !is_numeric($version) || $version < 1) { $version = 1; }
		$display_version = filter_input(INPUT_GET, 'display-version');
		if (!empty($display_version)) { $version = $display_version; }
		return $version;
	}
	
	/**
	 * Mots-clés
	 */
	public static $keywords_taxonomy = 'download_tag';
	public function get_keywords() {
		return wp_get_post_terms($this->ID, ATCF_Campaign::$keywords_taxonomy);
	}
	
/*******************************************************************************
 * GESTION LANGUES
 ******************************************************************************/
	public static $key_meta_lang = 'campaign_lang_list';
	private $current_lang = '';
	/**
	 * Ajoute une langue au projet
	 * @param string $new_lang
	 */
	public function add_lang( $new_lang ) {
		$lang_list = $this->get_lang_list();
		array_push( $lang_list, $new_lang );
	    update_post_meta( $this->ID, ATCF_Campaign::$key_meta_lang, json_encode( $lang_list ) );
	}
	/**
	 * Retourne la liste des langues du projet
	 * @return array
	 */
	public function get_lang_list() {
		$lang_list = json_decode( $this->__get( ATCF_Campaign::$key_meta_lang ) );
		if (empty($lang_list)) {
			$lang_list = array();
		}
		return $lang_list;
	}
	/**
	 * Définit la langue en cours du projet
	 * @param string $current_lang
	 */
	public function set_current_lang( $current_lang ) {
		$this->current_lang = $current_lang;
	}
	/**
	 * Retourne la traduction d'une propriété particulière
	 * @param string $property
	 * @return string
	 */
	private function __get_translated_property( $property ) {
		// Tentative de récupération dans la langue en cours
		$value = $this->__get( $property . '_' . $this->current_lang );
		// Si la valeur est vide et que la langue en cours est définie, on récupère le texte par défaut
		if ((empty( $value ) && !empty( $this->current_lang )) || empty( $this->current_lang )) {
			$value = $this->__get( $property );
		}
		return $value;
	}
	
	
/*******************************************************************************
 * PARAMS
 ******************************************************************************/
	public static $key_payment_provider = 'payment_provider';
	public static $payment_provider_mangopay = 'mangopay';
	public static $payment_provider_lemonway = 'lemonway';
	public function get_payment_provider() {
		$provider = $this->__get( ATCF_Campaign::$key_payment_provider );
		if ( $provider != ATCF_Campaign::$payment_provider_mangopay && $provider != ATCF_Campaign::$payment_provider_lemonway ) {
			$provider = ATCF_Campaign::$payment_provider_mangopay;
		}
		return $provider;
	}
	
	
/*******************************************************************************
 * TABLEAU DE BORD
 ******************************************************************************/
	public function google_doc() {
		return $this->__get('campaign_google_doc');
	}
	
	
/*******************************************************************************
 * AFFICHAGE
 ******************************************************************************/
	/**
	 * Retourne l'éventuel client auquel le projet appartient
	 * @return string
	 */
	public function get_client_context() {
		$client_context = '';
		$tag_list = $this->get_keywords();
		foreach ($tag_list as $tag) {
			$client_context = $tag->slug;
		}
		return $client_context;
	}
	
	public function featured() {
		return $this->__get( '_campaign_featured' );
	}
	
	
/*******************************************************************************
 * DONNEES
 ******************************************************************************/
	//Rédaction projet
	public function subtitle() {
		return $this->__get_translated_property( 'campaign_subtitle' );
	}
	public function summary() {
		return $this->__get_translated_property( 'campaign_summary' );
	}
	public function rewards() {
		return $this->__get_translated_property( 'campaign_rewards' );
	}
	public function description() {
		$description = $this->__get_translated_property( 'campaign_description' );
		if ( empty( $description ) ) {
			$description = $this->data->post_content;
		}
		return $description;
	}
	public function added_value() {
		return $this->__get_translated_property( 'campaign_added_value' );
	}
	public function development_strategy() {
		return $this->__get_translated_property( 'campaign_development_strategy' );
	}
	public function economic_model() {
		return $this->__get_translated_property( 'campaign_economic_model' );
	}
	public function measuring_impact() {
		return $this->__get_translated_property( 'campaign_measuring_impact' );
	}
	public function implementation() {
		return $this->__get_translated_property( 'campaign_implementation' );
	}
	public function impact_area() {
		return $this->__get_translated_property( 'campaign_impact_area' );
	}
	public function societal_challenge() {
		return $this->__get_translated_property( 'campaign_societal_challenge' );
	}
	
	//Ajouts contrat
	public function contract_title() {
		return $this->__get_translated_property('campaign_contract_title');
	}
	public function investment_terms() {
		return $this->__get_translated_property('campaign_investment_terms');
	}
	public function subscription_params() {
		return $this->__get_translated_property('campaign_subscription_params');
	}
	public function powers_params() {
		return $this->__get_translated_property('campaign_powers_params');
	}
	public function constitution_terms() {
		return $this->__get_translated_property('campaign_constitution_terms');
	}
	
	public function company_name() {
	    return $this->__get('campaign_company_name');
	}
	public function company_status() {
	    return $this->__get('campaign_company_status');
	}
	public function company_status_other() {
	    return $this->__get('campaign_company_status_other');
	}
	public function init_capital() {
	    return $this->__get('campaign_init_capital');
	}
	public function funding_type() {
	    return $this->__get('campaign_funding_type');
	}
	
	
/*******************************************************************************
 * GESTION ROI
 ******************************************************************************/
    public static $key_funding_duration = 'campaign_funding_duration';
    public function funding_duration() {
	    return $this->__get(ATCF_Campaign::$key_funding_duration);
	}

    public static $key_roi_percent_estimated = 'campaign_roi_percent_estimated';
	public function roi_percent_estimated() {
	    $buffer = $this->__get(ATCF_Campaign::$key_roi_percent_estimated);
		if (empty($buffer)) {
			$buffer = $this->roi_percent();
		}
		return $buffer;
	}
	public function roi_percent() {
	    return $this->__get('campaign_roi_percent');
	}

    public static $key_first_payment_date = 'campaign_first_payment_date';
	public function first_payment_date() {
	    return $this->__get(ATCF_Campaign::$key_first_payment_date);
	}
	
	// Frais appliqués au porteur de projet
	public static $key_costs_to_organization = 'costs_to_organization';
	public function get_costs_to_organization() {
		$buffer = $this->__get( ATCF_Campaign::$key_costs_to_organization );
		if (empty($buffer)) {
			$buffer = 0;
		}
		return $buffer;
	}
	// Frais appliqués aux investisseurs
	public static $key_costs_to_investors = 'costs_to_investors';
	public function get_costs_to_investors() {
		$buffer = $this->__get( ATCF_Campaign::$key_costs_to_investors );
		if (empty($buffer)) {
			$buffer = 0;
		}
		return $buffer;
	}
	
	
	public static $key_estimated_turnover = 'campaign_estimated_turnover';
	public function estimated_turnover() {
	    $buffer = $this->__get( ATCF_Campaign::$key_estimated_turnover );
	    return json_decode($buffer, TRUE);
	}
	public static $key_turnover_per_declaration = 'turnover_per_declaration';
	public function get_turnover_per_declaration() {
		$buffer = $this->__get( ATCF_Campaign::$key_turnover_per_declaration );
		if (empty($buffer)) { $buffer = 3; }
		return $buffer;
	}
	
	
	
	public function payment_list() {
	    $buffer = $this->__get('campaign_payment_list');
	    return json_decode($buffer, TRUE);
	}
	public function yearly_accounts_file($year) {
	    $attachments = get_posts( array(
		    'post_type' => 'attachment',
		    'post_parent' => $this->ID
	    ));
	    $buffer = array();
	    foreach ($attachments as $attachment) {
		    if ($attachment->post_title == 'Yearly Accounts ' . $year) {
			    $buffer[$attachment->ID]["url"] = get_the_guid($attachment->ID);
			    $buffer[$attachment->ID]["filename"] = get_post_meta($attachment->ID, "_wp_attached_file");
		    }
	    }
	    return $buffer;
	}
	public function payment_amount_for_year($year) {
	    $payment_list = $this->payment_list();
	    return $payment_list[$year];
	}
	public function payment_status_for_year($year) {
	    $payment_list = $this->payment_list_status();
	    return $payment_list[$year];
	}
	
	public function payment_list_status() {
	    $buffer = $this->__get('campaign_payment_list_status');
	    return json_decode($buffer, TRUE);
	}
	public function update_payment_status($date, $year, $post_id) {
	    $payment_list_status = $this->payment_list_status();
	    $payment_list_status[$year] = $post_id;
	    update_post_meta($this->ID, 'campaign_payment_list_status', json_encode($payment_list_status));
	}
        
	/**
	 * Indique si le porteur de projet est autorisé à passer à l'étape
	 * suivante par la modération
	 * @return boolean
	 */
	public function can_go_next_step(){
		$res = $this->__get('campaign_validated_next_step');
		if($res==1){
			return true;
		} else {
			return false; //Y compris le cas où il n'y a pas de valeur
		}
	}
        
        
	/**
	 * Indique si le porteur de projet a déjà eu le message de bienvenue
	 * en arrivant sur le tableau de bord
	 * @return boolean
	 */
	public function get_has_been_welcomed(){
		$res = $this->__get('campaign_has_been_welcomed');
		if($res==1){
			return true;
		} else {
			return false; //Y compris le cas où il n'y a pas de valeur
		}
	}

	/**
	 * Needs Shipping
	 *
	 * @since Appthemer CrowdFunding 0.9
	 *
	 * @return sting Requires Shipping
	 */
	public function needs_shipping() {
		$physical = $this->__get( '_campaign_physical' );

		return apply_filters( 'atcf_campaign_needs_shipping', $physical, $this );
	}

	public function is_flexible() {
	    return ($this->minimum_goal() != $this->goal());
	}
	
	/**
	 * Campaign Goal
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $goal A goal amount (formatted or not)
	 */
	public function goal( $formatted = true ) {
		$goal = $this->__get( 'campaign_goal' );

		if ( ! is_numeric( $goal ) )
			return 0;

		if ( $formatted ) {
		    $currency = edd_get_currency();
		    if ($currency == "EUR") {
			if (strpos($goal, '.00') !== false) $goal = substr ($goal, 0, -3);
			return $goal . ' &euro;';
		    } else {
			return edd_currency_filter( edd_format_amount( $goal ) );
		    }
		}

		return $goal;
	}
	
	public function minimum_goal($formatted = false) {
	    $goal = $this->__get( 'campaign_minimum_goal' );
	    if (strpos($goal, '.00') !== false) $goal = substr ($goal, 0, -3);
	    if ( ! is_numeric( $goal ) && ($this->type() != 'flexible') )
		    $goal = 0;
	    if ($goal == 0) $goal = $this->goal(false);
	    if ($formatted) $goal .= ' &euro;';
	    return $goal;
	}
	
	public function part_value() {
	    $part_value = $this->__get( 'campaign_part_value' );
	    if ( ! is_numeric( $part_value ) )
		    return 1;
	    return $part_value;
	}
	
	public function total_minimum_parts() {
	    return round($this->minimum_goal() / $this->part_value());
	}
	
	public function total_parts() {
	    return round($this->goal(false) / $this->part_value());
	}

	/**
	 * Campaign Type
	 *
	 * @since Appthemer CrowdFunding 0.7
	 *
	 * @return string $type The type of campaign
	 */
	public function type() {
		$type = $this->__get( 'campaign_type' );

		if ( ! $type )
			$type = atcf_campaign_type_default();

		return $type;
	}

	/**
	 * Campaign Location
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Location
	 */
	public function location() {
		return $this->__get( 'campaign_location' );
	}

	/**
	 * Campaign Author
	 * Deprecated : the meta is not used. Use post_author instead.
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Author
	 */
	public function author() {
		return $this->__get( 'campaign_author' );
	}
        
	public function post_author(){
			$post_campaign = get_post($this->ID);
			return $post_campaign->post_author;
	}
	
	private $organisation;
	public function get_organisation() {
		if (!isset($this->organisation)) {
			$api_project_id = BoppLibHelpers::get_api_project_id($this->ID);
			$current_organisations = BoppLib::get_project_organisations_by_role($api_project_id, BoppLibHelpers::$project_organisation_manager_role['slug']);
			if (isset($current_organisations) && count($current_organisations) > 0) {
				$this->organisation = $current_organisations[0];
			}
		}
		return $this->organisation;
	}

	public function contact_email() {
		return $this->__get( 'campaign_contact_email' );
	}
	public function contact_phone() {
		return $this->__get( 'campaign_contact_phone' );
	}

	/**
	 * Campaign End Date
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign End Date
	 */
	public function end_date($format = 'Y-m-d H:i:s') {
		return mysql2date( $format, $this->__get( 'campaign_end_date' ), false );
	}
        
        /**
	 * Campaign Begin Collecte Date
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Begin Collecte Date
	 */
	public function begin_collecte_date($format = 'Y-m-d H:i:s') {
		return mysql2date( $format, $this->__get( 'campaign_begin_collecte_date' ), false );
	}
        
	/**
	 * Set the date when vote finishes
	 * @param type DateTime $newDate
	 */
	public function set_end_vote_date($newDate){
		$res = update_post_meta($this->ID, 'campaign_end_vote', date_format($newDate, 'Y-m-d H:i:s'));
	}

	/**
	 * Set the date when collecte is started
	 * @param type DateTime $newDate
	 */
	public function set_begin_collecte_date($newDate){
		$res = update_post_meta($this->ID, 'campaign_begin_collecte_date', date_format($newDate, 'Y-m-d H:i:s'));
	}

	/**
	 * Set the date when collecte finishes
	 * @param type DateTime $newDate
	 */
	public function set_end_date($newDate){
		$res = update_post_meta($this->ID, 'campaign_end_date', date_format($newDate, 'Y-m-d H:i:s'));
	}

	public function end_vote() {
		return mysql2date( 'Y-m-d H:i:s', $this->__get( 'campaign_end_vote' ), false);
	}

	public function end_vote_date() {
		return mysql2date( 'Y-m-d H:i', $this->__get( 'campaign_end_vote' ), false);
	}
	public function end_vote_date_home() {
		setlocale(LC_TIME, array('fr_FR.UTF-8', 'fr_FR.UTF-8', 'fra'));
		return strftime("%d %B", strtotime(mysql2date( 'm/d', $this->__get( 'campaign_end_vote' ), false)));
	}
	public function end_vote_remaining() {
	    date_default_timezone_set('Europe/Paris');
	    $dateJour = strtotime(date("d-m-Y H:i"));
	    $fin = strtotime($this->__get( 'campaign_end_vote' ));
	    $buffer = floor(($fin - $dateJour) / 60 / 60 / 24);
	    $buffer = max(0, $buffer + 1);
	    return $buffer;
	}
	
	public function nb_voters() {
	    global $wpdb;
	    $table_name = $wpdb->prefix . "ypcf_project_votes";
	    $count_users = $wpdb->get_var( "SELECT count(id) FROM $table_name WHERE post_id = " . $this->ID );
	    return $count_users;
	}
        
	public function vote_invest_ready_min_required(){
		return $this->minimum_goal(false)*(ATCF_Campaign::$vote_percent_invest_ready_min_required/100);
	}
	
	public function is_vote_validated() {
	    $buffer = FALSE;
	    if ($this->nb_voters() >= ATCF_Campaign::$voters_min_required) {
		    $vote_results = WDGCampaignVotes::get_results($this->ID);
		    $buffer = ($vote_results['percent_project_validated'] >= ATCF_Campaign::$vote_score_min_required)
				&& ($vote_results['sum_invest_ready'] >= $this->vote_invest_ready_min_required());
	    }
	    return $buffer;
	}

	/**
	 * Campaign Video
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Video
	 */
	public function video() {
		return $this->__get_translated_property( 'campaign_video' );
	}

	/**
	 * Récupérer le statut du projet
	 * @return string Statuts possibles : preparing ; preview ; vote ; collecte ; funded ; archive
	 */
	public function campaign_status() {
		return $this->vote();
	}
	/**
	 * Deprecated : use campaign_status instead
	 */
	public function vote() {
		return $this->__get( 'campaign_vote' );
	}
	
	/**
	 * Campaign Updates
	 *
	 * @since Appthemer CrowdFunding 0.9
	 *
	 * @return sting Campaign Updates
	 */
	public function updates() {
		return $this->__get( 'campaign_updates' );
	}

	/**
	 * Campaign Backers
	 *
	 * Use EDD logs to get all sales. This includes both preapproved
	 * payments (if they have Plugin installed) or standard payments.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Backers
	 */
	public function backers() {
		global $edd_logs;

		$backers = $edd_logs->get_connected_logs( array(
			'post_parent'    => $this->ID, 
			'log_type'       => /*atcf_has_preapproval_gateway()*/FALSE ? 'preapproval' : 'sale',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => -1
		) );

		return $backers;
	}

	/**
	 * Campaign Backers Count
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return int Campaign Backers Count
	 */
	public function backers_count() {
		$backers = $this->backers();
		$total = 0;

		if ($backers > 0) {
		    foreach ( $backers as $backer ) {
			    $payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			    $payment    = get_post( $payment_id );

			    if ( empty( $payment ) || $payment->post_status == 'pending' )
				    continue;

			    $total++;
		    }
		}
		
		return $total;
	}

	/**
	 * Campaign Backers Per Price
	 *
	 * Get all of the backers, then figure out what they purchased. Increment
	 * a counter for each price point, so they can be displayed elsewhere. 
	 * Not 100% because keys can change in EDD, but it's the best way I think.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return array $totals The number of backers for each price point
	 */
	public function backers_per_price() {
		$backers = $this->backers();
		$prices  = edd_get_variable_prices( $this->ID );
		$totals  = array();

		if ( ! is_array( $backers ) )
			$backers = array();

		foreach ( $prices as $price ) {
			$totals[$price[ 'amount' ]] = 0;
		}

		foreach ( $backers as $log ) {
			$payment_id = get_post_meta( $log->ID, '_edd_log_payment_id', true );

			$payment    = get_post( $payment_id );
			
			if ( empty( $payment ) )
				continue;

			$cart_items = edd_get_payment_meta_cart_details( $payment_id );
			
			foreach ( $cart_items as $item ) {
				if ( isset ( $item[ 'item_number' ][ 'options' ][ 'atcf_extra_price' ] ) ) {
					$price_id = $item[ 'price' ] - $item[ 'item_number' ][ 'options' ][ 'atcf_extra_price' ];
				} else
					$price_id = $item[ 'price' ];

				$totals[$price_id] = isset ( $totals[$price_id] ) ? $totals[$price_id] + 1 : 1;
			}
		}

		return $totals;
	}

	/**
	 * Campaign Days Remaining
	 *
	 * Calculate the end date, minus today's date, and output a number.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return int The number of days remaining
	 */
	public function days_remaining() {
		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );

		if ( $now > $expires )
			return 0;

		$diff = $expires - $now;

		if ( $diff < 0 )
			return 0;

		$days = $diff / 86400;

		return floor( $days );
	}
	
	public function is_remaining_time() {
		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );
		return ( $now < $expires );
	}
	
	/**
	 * Retourne une chaine avec le temps restant (J-6, H-2, M-23)
	 */
	public function time_remaining_str() {
		//Récupération de la date de fin et de la date actuelle
		$buffer = '';
		switch ($this->campaign_status()) {
			case ATCF_Campaign::$campaign_status_vote:
			    $expires = strtotime( $this->end_vote() );
			    break;
			case ATCF_Campaign::$campaign_status_collecte:
			    $expires = strtotime( $this->end_date() );
			    break;
			default:
			    $expires = 0;
			    break;
		}
		
		date_default_timezone_set("Europe/London");
		$now = current_time( 'timestamp' );
		
		//Si on a dépassé la date de fin, on retourne "-"
		if ( $now > $expires ) {
			$buffer = '-';
		} else {
			$diff = $expires - $now;
			$nb_days = floor($diff / (60 * 60 * 24));
			if ($nb_days > 0) {
				$buffer = 'J-' . $nb_days;
			} else {
				$nb_hours = floor($diff / (60 * 60));
				if ($nb_hours > 0) {
					$buffer = 'H-' . $nb_hours;
				} else {
					$nb_minutes = floor($diff / 60);
					$buffer = 'M-' . $nb_minutes;
				}
			}
		}
		    
		return $buffer;
	}
	/**
	 * Retourne une chaine complète avec le temps restant
	 */
	public function time_remaining_fullstr() {
		$buffer = '';
		
		date_default_timezone_set("Europe/London");
		$now = current_time( 'timestamp' );
		switch ($this->campaign_status()) {
			case ATCF_Campaign::$campaign_status_vote:
			    $expires = strtotime( $this->end_vote() );
			    //Si on a dépassé la date de fin, on retourne "-"
			    if ( $now >= $expires ) {
				    $buffer = __('Vote termin&eacute;', 'yproject');
			    } else {
				    $diff = $expires - $now;
				    $nb_days = floor($diff / (60 * 60 * 24));
				    $plural = ($nb_days > 1) ? 's' : '';
				    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_days+1) . '</b> '. __('jour', 'yproject').$plural.__(' pour voter !', 'yproject');
				    if ($nb_days <= 0) {
					    $nb_hours = floor($diff / (60 * 60));
					    $plural = ($nb_hours > 1) ? 's' : '';
					    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_hours+1) . '</b> '. __('heure', 'yproject').$plural.__(' pour voter !', 'yproject');
					    if ($nb_hours <= 0) {
						    $nb_minutes = floor($diff / 60);
						    $plural = ($nb_minutes > 1) ? 's' : '';
						    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_minutes+1) . '</b> '. __('minute', 'yproject').$plural.__(' pour voter !', 'yproject');
					    }
				    }
			    }
			    break;
			case ATCF_Campaign::$campaign_status_collecte:
			    $expires = strtotime( $this->end_date() );
			    //Si on a dépassé la date de fin, on retourne "-"
			    if ( $now >= $expires ) {
				    $buffer = __('Collecte termin&eacute;e', 'yproject');
			    } else {
				    $diff = $expires - $now;
				    $nb_days = floor($diff / (60 * 60 * 24));
				    $plural = ($nb_days > 1) ? 's' : '';
				    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_days+1) . '</b> '. __('jour', 'yproject').$plural.__(' !', 'yproject');
				    if ($nb_days <= 0) {
					    $nb_hours = floor($diff / (60 * 60));
					    $plural = ($nb_hours > 1) ? 's' : '';
					    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_hours+1) . '</b> '. __('heure', 'yproject').$plural.__(' !', 'yproject');
					    if ($nb_hours <= 0) {
						    $nb_minutes = floor($diff / 60);
						    $plural = ($nb_minutes > 1) ? 's' : '';
						    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_minutes+1) . '</b> '. __('minute', 'yproject').$plural.__(' !', 'yproject');
					    }
				    }
			    }
			    break;
			default:
			    $buffer = '-';
			    break;
		}
		    
		return $buffer;
	}

	public function can_use_wire($amount_part) {
		$min_wire = 150;
		return ($this->days_remaining() > 7 && $this->part_value() * $amount_part >= $min_wire);
	}
	
	public function can_use_check($amount_part) {
		$min_check = 150;
		return ($this->part_value() * $amount_part >= $min_check);
	}

	/**
	 * Campaign Percent Completed
	 *
	 * MATH!
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $percent The percent completed (formatted with a % or not)
	 */
	public function percent_completed( $formatted = true ) {
		$goal    = $this->goal(false);
		$current = $this->current_amount(false);

		if ( 0 == $goal )
			return $formatted ? 0 . '%' : 0;

		$percent = ( $current / $goal ) * 100;
		$percent = round( $percent );

		if ( $formatted )
			return $percent . '%';

		return $percent;
	}
	public function percent_minimum_completed($formatted = true ) {
		$goal    = $this->minimum_goal(false);
		$current = $this->current_amount(false);

		if ( 0 == $goal )
			return $formatted ? 0 . '%' : 0;

		$percent = ( $current / $goal ) * 100;
		$percent = round( $percent );

		if ( $formatted )
			return $percent . '%';

		return $percent;
	}
	
	public function percent_minimum_to_total() {
	    $min = $this->minimum_goal(false);
	    $total = $this->goal(false);
	    return round($min / $total * 100);
	}

	/**
	 * Current amount funded.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $total The amount funded (currency formatted or not)
	 */
	public function current_amount( $formatted = true ) {
		$total   = 0;
		$backers = $this->backers();

		if ($backers > 0) {
		    foreach ( $backers as $backer ) {
			    $payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			    $payment    = get_post( $payment_id );

			    if ( empty( $payment ) || $payment->post_status == 'pending' )
				    continue;

			    $total      = $total + edd_get_payment_amount( $payment_id );
		    }
		}
		
		$amount_check = $this->current_amount_check(FALSE);
		$total += $amount_check;
		
		if ( $formatted ) {
		    $currency = edd_get_currency();
		    if ($currency == "EUR") {
			if (strpos($total, '.00') !== false) $total = substr ($total, 0, -3);
			return $total . ' &euro;';
		    } else {
			return edd_currency_filter( edd_format_amount( $total ) );
		    }
		}

		return $total;
	}
	
	public function current_amount_check($formatted = true){
		$amount_check = $this->__get( 'campaign_amount_check' );

		if ( ! is_numeric( $amount_check ) )
			$amount_check = 0;

		if ( $formatted ) {
		    $currency = edd_get_currency();
		    if ($currency == "EUR") {
			if (strpos($amount_check, '.00') !== false) $amount_check = substr ($amount_check, 0, -3);
			return $amount_check . ' &euro;';
		    } else {
			return edd_currency_filter( edd_format_amount( $amount_check ) );
		    }
		}

		return $amount_check;
	}

	/**
	 * Campaign Active
	 *
	 * Check if the campaign has expired based on time, or it has
	 * manually been expired (via meta)
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return boolean
	 */
	public function is_active() {
		$active  = true;

		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );

		if ( $now > $expires )
			$active = false;

		if ( $this->__get( '_campaign_expired' ) )
			$active = false;

		if ( $this->is_collected() )
			$active = false;

		return apply_filters( 'atcf_campaign_active', $active, $this );
	}

	/**
	 * Funds Collected
	 *
	 * When funds are collected in bulk, remember that, so we can end the
	 * campaign, and not repeat things.
	 *
	 * @since Appthemer CrowdFunding 0.3-alpha
	 *
	 * @return boolean
	 */
	public function is_collected() {
		return $this->__get( '_campaign_bulk_collected' );
	}

	/**
	 * Campaign Funded
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return boolean
	 */
	public function is_funded() {
		if ( $this->current_amount(false) >= $this->minimum_goal() )
			return true;

		return false;
	}
	
        /**
         * Return payments data. 
         * This function is very slow, it is advisable to use it as few as possible
         * @return array
         */
	public function payments_data($skip_apis = FALSE) {
		global $WDG_cache_plugin;
		$payments_data = array();

		$payments = edd_get_payments( array(
		    'number'	 => -1,
		    'download'   => $this->ID
		) );

		
//		$cache_stats = $WDG_cache_plugin->get_cache('project-investments-data-' . $this->ID, 2);
//		if ($cache_stats === false) {
			if ( $payments ) {
				foreach ( $payments as $payment ) {
					$user_info = edd_get_payment_meta_user_info( $payment->ID );
					$cart_details = edd_get_payment_meta_cart_details( $payment->ID );

					$user_id = (isset( $user_info['id'] ) && $user_info['id'] != -1) ? $user_info['id'] : $user_info['email'];

					$signsquid_contract = new SignsquidContract($payment->ID);
					$signsquid_status = $signsquid_contract->get_status_code();
					$signsquid_status_text = $signsquid_contract->get_status_str();
					
					$mangopay_contribution = FALSE;
					$lemonway_contribution = FALSE;
					if ($this->get_payment_provider() == ATCF_Campaign::$payment_provider_mangopay) {
						$mangopay_id = edd_get_payment_key($payment->ID);
						if ($mangopay_id == 'check') {
							//Rien

						} else if (strpos($mangopay_id, 'wire_') !== FALSE) {
							$mangopay_id = substr($mangopay_id, 5);
							$mangopay_contribution = ($skip_apis == FALSE) ? ypcf_mangopay_get_withdrawalcontribution_by_id($mangopay_id) : '';
						} else {
							$mangopay_contribution = ($skip_apis == FALSE) ? ypcf_mangopay_get_contribution_by_id($mangopay_id) : '';
						}
						
					} else if ($this->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway) {
						$lemonway_id = edd_get_payment_key($payment->ID);
						
						if ($lemonway_id == 'check') {

						} else if (strpos($lemonway_id, 'wire_') !== FALSE) {
							
						} else {
							$lemonway_contribution = ($skip_apis == FALSE) ? LemonwayLib::get_transaction_by_id($lemonway_id) : '';
						}
					}

					$payment_status = ypcf_get_updated_payment_status( $payment->ID, $mangopay_contribution, $lemonway_contribution );

					if ($payment_status != 'failed') {
						$payments_data[] = array(
							'ID'			=> $payment->ID,
							'email'			=> edd_get_payment_user_email( $payment->ID ),
							'products'		=> $cart_details,
							'amount'		=> edd_get_payment_amount( $payment->ID ),
							'date'			=> $payment->post_date,
							'user'			=> $user_id,
							'status'		=> $payment_status,
							'mangopay_contribution' => $mangopay_contribution,
							'lemonway_contribution' => $lemonway_contribution,
							'signsquid_status'	=> $signsquid_status,
							'signsquid_status_text' => $signsquid_status_text
						);
					}
				}
			}
			
//			$cache_stats = json_encode($payments_data);
//			$WDG_cache_plugin->set_cache('project-investments-data-' . $this->ID, $cache_stats, 60*60*3, 2);
//		}
		
		return $payments_data;
	}
	
	/**
	 * Ajoute un investissement dans la liste des investissements
	 * @param string $type
	 * @param string $email
	 * @param string $date
	 * @param string $value
	 * @param string $new_username
	 * @param string $new_pwd
	 */
	public function add_investment($type, $email, $value, $new_username = '', $new_password = '', $new_gender = '', $new_firstname = '', $new_lastname = '', $orga_email = '', $orga_name = '') {
		$user_id = FALSE;
	    
		//Vérification si un utilisateur existe avec l'email en paramètre
		$user_payment = get_user_by('email', $email);
		if ($user_payment) {
			$user_id = $user_payment->ID;
			$new_gender = $user_payment->get('user_gender');
			$new_firstname = $user_payment->user_firstname;
			$new_lastname = $user_payment->user_lastname;
		
		//Sinon, on vérifie si il y a un login et pwd transmis, pour créer le nouvel utilisateur
		} else {
			if (!empty($new_username) && !empty($new_password)) {
				$user_id = wp_create_user($new_username, $new_password, $email);
				if (!empty($new_gender)) update_user_meta($user_id, 'user_gender', $new_gender);
				if (!empty($new_firstname)) wp_update_user( array ( 'ID' => $user_id, 'first_name' => $new_firstname ) );
				if (!empty($new_lastname)) wp_update_user( array ( 'ID' => $user_id, 'last_name' => $new_lastname ) );
			}
		}
		$saved_user_id = $user_id;
		
		if (!is_wp_error($saved_user_id) && !empty($saved_user_id) && $saved_user_id != FALSE) {
			//Gestion organisation
			if ( !empty($orga_email) ) {
				//Vérification si organisation existante
				$orga_payment = get_user_by('email', $orga_email);
				if ($orga_payment) {
					$saved_user_id = $orga_payment->ID;

				//Sinon, on la crée juste avec un e-mail et un nom
				} else {
					$org_object = new YPOrganisation();
					$org_object->set_strong_authentication(FALSE);
					$org_object->set_name($orga_name);
					$org_object->set_email($orga_email);
					
					$org_object->set_address('---');
					$org_object->set_postal_code('00000');
					$org_object->set_city('---');
					$org_object->set_nationality('---');
					$org_object->set_type('society');
					$org_object->set_legalform('---');
					$org_object->set_capital(0);
					$org_object->set_idnumber('---');
					$org_object->set_rcs('---');
					$org_object->set_ape('---');
					$org_object->set_bank_owner('---');
					$org_object->set_bank_address('---');
					$org_object->set_bank_iban('---');
					$org_object->set_bank_bic('---');
		
					$wp_orga_user_id = $org_object->create();
					$org_object->set_creator( $user_id );
					$saved_user_id = $wp_orga_user_id;
				}
			}
		}
		
		if (!is_wp_error($saved_user_id) && !empty($saved_user_id) && $saved_user_id != FALSE) {
			$user_info = array(
				'id'		=> $saved_user_id,
				'gender'	=> $new_gender,
				'email'		=> $email,
				'first_name'	=> $new_firstname,
				'last_name'	=> $new_lastname,
				'discount'	=> '',
				'address'	=> array()
			);
			
			$cart_details = array(
				array(
					'name'        => get_the_title( $this->ID ),
					'id'          => $this->ID,
					'item_number' => array(
						'id'	    => $this->ID,
						'options'   => array()
					),
					'price'       => 1,
					'quantity'    => $value
				)
			);

			$payment_data = array( 
				'price'		=> $value, 
				'date'		=> date('Y-m-d H:i:s'), 
				'user_email'	=> $email,
				'purchase_key'	=> $type,
				'currency'	=> edd_get_currency(),
				'downloads'	=> array($this->ID),
				'user_info'	=> $user_info,
				'cart_details'	=> $cart_details,
				'status'	=> 'publish'
			);
			$payment_id = edd_insert_payment( $payment_data );
			edd_record_sale_in_log($this->ID, $payment_id);

		} else {
			$saved_user_id = FALSE;
		}
		
		return $saved_user_id;
	}
	
	/**
	 * Retourne la liste des paiement, augmentée par les informations utiles pour un ROI particulier
	 * @param WDGROIDeclaration $declaration
	 */
	public function roi_payments_data($declaration) {
		$buffer = array();
		$investments_list = $this->payments_data(TRUE);
		//Calculs des montants à reverser
		$total_amount = $this->current_amount(FALSE);
		$roi_amount = $declaration->amount;
		foreach ($investments_list as $investment_item) {
			//Calcul de la part de l'investisseur dans le total
			$investor_proportion = $investment_item['amount'] / $total_amount; //0.105
			//Calcul du montant à récupérer en roi
			$investor_proportion_amount = floor($roi_amount * $investor_proportion * 100) / 100; //10.50
			//Calcul de la commission sur le roi de l'utilisateur
			$fees_total = $investor_proportion_amount * $this->get_costs_to_investors() / 100; //10.50 * 1.8 / 100 = 0.189
			//Et arrondi
			$fees = round($fees_total * 100) / 100; //0.189 * 100 = 18.9 = 19 = 0.19
			$investment_item['roi_fees'] = $fees;
			//Reste à verser pour l'investisseur
			$investor_proportion_amount_remaining = $investor_proportion_amount - $fees;
			$investment_item['roi_amount'] = $investor_proportion_amount_remaining;
			array_push($buffer, $investment_item);
		}
	    
		return $buffer;
	}
	
	public function manage_jycrois($user_id = FALSE) {
		global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";
		

		// Construction des urls utilisés dans les liens du fil d'actualité
		// url d'une campagne précisée par son nom 
		$campaign_url = get_permalink($_POST['id_campaign']);
		$post_campaign = get_post($_POST['id_campaign']);
		$post_title = $post_campaign->post_title;
		$url_campaign = '<a href="'.$campaign_url.'">'.$post_title.'</a>';

		//url d'un utilisateur précis
		$user_item = ($user_id === FALSE) ? wp_get_current_user() : get_userdata($user_id);
		$user_id = $user_item->ID;
		$user_display_name = $user_item->display_name;
		$url_profile = '<a href="' . bp_core_get_userlink($user_id, false, true) . '">' . $user_display_name . '</a>';
		$user_avatar = UIHelpers::get_user_avatar($user_id);

		//J'y crois
		if(isset($_POST['jy_crois']) && $_POST['jy_crois'] == 1){
			$wpdb->insert( 
				$table_jcrois,
				array(
					'user_id'	=> $user_id,
					'campaign_id'   => $this->ID
				)
			); 
			bp_activity_add(array (
				'component' => 'profile',
				'type'      => 'jycrois',
				'action'    => $user_avatar . $url_profile.' croit au projet '.$url_campaign
			));

		//J'y crois pas
		} else if (isset($_POST['jy_crois']) && $_POST['jy_crois'] == 0) { 
			$wpdb->delete( 
				$table_jcrois,
				array(
					'user_id'      => $user_id,
					'campaign_id'  => $this->ID
				)
			);
			// Inserer l'information dans la table du fil d'activité  de la BDD wp_bp_activity 
			bp_activity_delete(array (
				'user_id'   => $user_id,
				'component' => 'profile',
				'type'      => 'jycrois',
				'action'    => $user_avatar . $url_profile . ' croit au projet '.$url_campaign
			));
		}
		
		return $this->get_jycrois_nb();
	}
	
	public function get_jycrois_nb() {
		global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";
		return $wpdb->get_var( 'SELECT count(campaign_id) FROM '.$table_jcrois.' WHERE campaign_id = '.$this->ID );
	}
	
	public function get_header_picture_src($force = true) {
		$src = $this->get_picture_src('image_header', $force);
		if ($this->is_header_blur() === FALSE) {
			$src = str_replace('_blur', '', $src);
			
			//Test si le fichier existe
			if ($src !== '') {
				$src_exploded = explode('uploads', $src);
				$upload_dir = wp_upload_dir();
				if (!file_exists($upload_dir['basedir'] . $src_exploded[1])) {
					$ext_exploded = explode('.', $src);
					$ext_exploded[count($ext_exploded) - 1] = 'png';
					$src = implode('.', $ext_exploded);
				}
			}
		}
		return $src;
	}
	
	public function get_home_picture_src($force = true) {
		return $this->get_picture_src('image_home', $force);
	}
	
	public function get_picture_src($type, $force) {
		$image_obj = '';
		$img_src = '';
		$attachments = get_posts( array(
			'post_type' => 'attachment',
			'post_parent' => $this->ID,
			'post_mime_type' => 'image'
		));
		
		if (count($attachments) > 0) {
			//Si on en trouve bien une avec le titre "image_home" on prend celle-là
			foreach ($attachments as $attachment) {
				if ($attachment->post_title == $type) $image_obj = wp_get_attachment_image_src($attachment->ID, "full");
			}
			//Sinon on prend la première image rattachée à l'article
			if ($force && $image_obj == '') $image_obj = wp_get_attachment_image_src($attachments[0]->ID, "full");
			if ($image_obj != '') $img_src = $image_obj[0];
		}
		
		return $img_src;
	}
	
	public function is_header_blur() {
		$buffer = get_post_meta($this->ID, 'campaign_header_blur_active', TRUE);
		if ($buffer === FALSE || $buffer === 'FALSE') { 
		    $buffer = FALSE; 
		} else {
		    $buffer = TRUE;
		}
		return $buffer;
	}
	
	public function get_header_picture_position_style() {
		$buffer = '';
		$cover_position = get_post_meta($this->ID, 'campaign_cover_position', TRUE);
		if ($cover_position !== '') {
			$buffer = 'top: ' . $cover_position;
		}
		return $buffer;
	}
	
	public function current_user_can_edit() {
		//Il faut qu'il soit connecté
		if (!is_user_logged_in()) return FALSE;
		
		//On autorise les admin
		if (current_user_can('manage_options')) return TRUE;
	    
		//On autorise l'auteur
		$post_campaign = get_post($this->ID);
		$current_user = wp_get_current_user();
		$current_user_id = $current_user->ID;
		if ($current_user_id == $post_campaign->post_author) return TRUE;
		
		//On autorise les personnes de l'équipe projet
		$project_api_id = BoppLibHelpers::get_api_project_id($this->ID);
		$team_member_list = BoppLib::get_project_members_by_role($project_api_id, BoppLibHelpers::$project_team_member_role['slug']);
		foreach ($team_member_list as $team_member) {
			if ($current_user_id == $team_member->wp_user_id) return TRUE;
		}
		
		return FALSE;
	}
	
	public function get_documents_list() {
		$attachments = get_posts( array(
			'post_type' => 'projectdoc',
			'post_parent' => $this->ID,
			'post_status'	=> 'inherit'
		));
		return $attachments;
	}
	
	public function add_document($title, $url) {
		$args = array(
			'post_type'	=> 'projectdoc',
			'post_status'	=> 'inherit',
			'post_title'	=> $title,
			'post_content'	=> $url,
			'post_author'	=> $this->data->post_author,
			'post_parent'	=> $this->ID
		);
		wp_insert_post($args, true);
	}
	
	public function delete_document($id) {
		$post = get_post($id);
		if ($post->post_parent == $this->ID) wp_delete_post($id);
	}
        
	/**
	 * Gère la validation de modération pour le passage à l'étape suivante
	 * 
	 * $value : Valeur du flag de validation (true si le PP peut passer à
	 *      l'étape suivante, false sinon)
	 */
	public function set_validation_next_step($value){
		if($value==0||$value==1) {
			$res = update_post_meta($this->ID, 'campaign_validated_next_step', $value);
		}            
	}

	/**
	 * Setter si le PP a déjà vu la LB de bienvenue sur son TB
	 * 
	 * $value : Valeur du flag (true si le PP a déjà vu la LB, false sinon)
	 */
	public function set_has_been_welcomed($value){
		if($value==0||$value==1) {
			$res = update_post_meta($this->ID, 'campaign_has_been_welcomed', $value);
		}
	}

	public function set_status($newstatus){
		if(array_key_exists($newstatus, ATCF_Campaign::get_campaign_status_list())){
			$res = update_post_meta($this->ID, 'campaign_vote', $newstatus);
		}
	}

	/**
	 * Provides various words to describe the campaign according to it funding type :
	 * @return array
	 */
	public function funding_type_vocabulary(){
		switch ($this->funding_type()) {
			case 'fundingdonation' :
				return array(
				'investor_name' => __('contributeur', 'yproject'),
				'investor_action' => __('contribution', 'yproject'),
				'action_feminin' => true,
				'investor_verb' => __('contribu&eacute;', 'yproject')
				);
			default :
				return array(
				'investor_name' => __('investisseur', 'yproject'),
				'investor_action' => __('investissement', 'yproject'),
				'action_feminin' => false,
				'investor_verb' => __('investi', 'yproject')
				);
		}
		return array();
	}
}

function atcf_get_locations() {
	$buffer = array(
		'01 Ain',
		'02 Aisne',
		'03 Allier',
		'04 Alpes-de-Haute-Provence',
		'05 Hautes-Alpes',
		'06 Alpes-Maritimes',
		'07 Ardèche',
		'08 Ardennes',
		'09 Ariège',
		'10 Aube',
		'11 Aude',
		'12 Aveyron',
		'13 Bouches-du-Rhône',
		'14 Calvados',
		'15 Cantal',
		'16 Charente',
		'17 Charente-Maritime',
		'18 Cher',
		'19 Corrèze',
		'2A Corse-du-Sud',
		'2B Haute-Corse',
		'21 Côte-d\'Or',
		'22 Côtes d\'Armor',
		'23 Creuse',
		'24 Dordogne',
		'25 Doubs',
		'26 Drôme',
		'27 Eure',
		'28 Eure-et-Loir',
		'29 Finistère',
		'30 Gard',
		'31 Haute-Garonne',
		'32 Gers',
		'33 Gironde',
		'34 Hérault',
		'35 Ille-et-Vilaine',
		'36 Indre',
		'37 Indre-et-Loire',
		'38 Isère',
		'39 Jura',
		'40 Landes',
		'41 Loir-et-Cher',
		'42 Loire',
		'43 Haute-Loire',
		'44 Loire-Atlantique',
		'45 Loiret',
		'46 Lot',
		'47 Lot-et-Garonne',
		'48 Lozère',
		'49 Maine-et-Loire',
		'50 Manche',
		'51 Marne',
		'52 Haute-Marne',
		'53 Mayenne',
		'54 Meurthe-et-Moselle',
		'55 Meuse',
		'56 Morbihan',
		'57 Moselle',
		'58 Nièvre',
		'59 Nord',
		'60 Oise',
		'61 Orne',
		'62 Pas-de-Calais',
		'63 Puy-de-Dôme',
		'64 Pyrénées-Atlantiques',
		'65 Hautes-Pyrénées',
		'66 Pyrénées-Orientales',
		'67 Bas-Rhin',
		'68 Haut-Rhin',
		'69 Rhône',
		'70 Haute-Saône',
		'71 Saône-et-Loire',
		'72 Sarthe',
		'73 Savoie',
		'74 Haute-Savoie',
		'75 Paris',
		'76 Seine-Maritime',
		'77 Seine-et-Marne',
		'78 Yvelines',
		'79 Deux-Sèvres',
		'80 Somme',
		'81 Tarn',
		'82 Tarn-et-Garonne',
		'83 Var',
		'84 Vaucluse',
		'85 Vendée',
		'86 Vienne',
		'87 Haute-Vienne',
		'88 Vosges',
		'89 Yonne',
		'90 Territoire de Belfort',
		'91 Essonne',
		'92 Hauts-de-Seine',
		'93 Seine-Saint-Denis',
		'94 Val-de-Marne',
		'95 Val-d\'Oise',
		'971 Guadeloupe',
		'972 Martinique',
		'973 Guyane',
		'974 La Réunion',
		'976 Mayotte'
	);
	return $buffer;
}