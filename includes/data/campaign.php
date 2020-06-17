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
	$campaign = false;
	global $campaign_id, $is_campaign, $is_campaign_page, $post_campaign, $post;
	//Si l'id de campagne n'a pas encore été trouvé, on va le récupérer
	if (empty($campaign_id)) {
		$campaign_id = '';
		if ( is_single() && $post->post_type == "post" ) {
			$singlepost_category = get_the_category();
			$campaign_id = atcf_get_campaign_id_from_category($singlepost_category[0]);
			if ( !empty( $campaign_id ) ) {
				$is_campaign_page = TRUE;
			}
			
		} else if (is_category()) {
			global $cat;
			$campaign_id = atcf_get_campaign_id_from_category($cat);
			
		} else {
			$wdginvestment = WDGInvestment::current();
			if ( !empty( $wdginvestment) && isset( $wdginvestment->get_campaign()->ID ) ) {
				$campaign_id = $wdginvestment->get_campaign()->ID;
			} else {
				$campaign_id = filter_input( INPUT_GET, 'campaign_id' );
				if ( empty( $campaign_id ) ) {
					$campaign_id = $post->ID;
				}
			}
		}
	}
	
	//On a un id, alors on fait les vérifications pour savoir si c'est bien une campagne
	if (!empty($campaign_id)) {
		$is_campaign = (get_post_meta($campaign_id, 'campaign_funding_type', TRUE) != '');
		if (!isset($is_campaign_page) || $is_campaign_page != TRUE) {
			$is_campaign_page = $is_campaign && ($campaign_id == $post->ID);
		}
		
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
	$campaign_id = FALSE;
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

function atcf_create_campaign($author_ID, $title){
    global $edd_options;

    $args = array(
        'post_type'   		 	=> 'download',
        'post_status'  		 	=> 'publish',
        'post_content' 		 	=> $edd_options['default_pitch'] ,
        'post_title'   		 	=> $title,
        'post_author'  			=> $author_ID,

    );

    $newcampaign_id = wp_insert_post( $args, true );

    $default_date = date_format(date_add(new DateTime(),new DateInterval('P10Y')), 'Y-m-d H:i:s');

    // Create category for blog
    $id_category = wp_insert_category( array('cat_name' => 'cat'.$newcampaign_id, 'category_nicename' => sanitize_title($newcampaign_id . '-blog-' . $title)) );
    add_post_meta( $newcampaign_id, 'campaign_blog_category_id', $id_category );

    // Extra Campaign Information
    add_post_meta( $newcampaign_id, ATCF_Campaign::$key_campaign_status, ATCF_Campaign::$campaign_status_validated );
    add_post_meta( $newcampaign_id, ATCF_Campaign::$key_validation_next_status, 0);

    add_post_meta( $newcampaign_id, 'campaign_part_value', 1 );
    add_post_meta( $newcampaign_id, 'campaign_funding_type', 'fundingproject' );

    add_post_meta( $newcampaign_id, ATCF_Campaign::$key_end_vote_date, $default_date);
    add_post_meta( $newcampaign_id, ATCF_Campaign::$key_end_collecte_date,  $default_date);
    add_post_meta( $newcampaign_id, ATCF_Campaign::$key_begin_collecte_date, $default_date);

    add_post_meta( $newcampaign_id, 'campaign_societal_challenge', $edd_options['default_positive_impacts']);
    add_post_meta( $newcampaign_id, 'campaign_added_value', $edd_options['default_strategy']);
    add_post_meta( $newcampaign_id, 'campaign_economic_model', $edd_options['default_financiary']);
    add_post_meta( $newcampaign_id, 'campaign_implementation', $edd_options['default_team']);

    // EDD Stuff
    add_post_meta( $newcampaign_id, '_variable_pricing', 0 );
    add_post_meta( $newcampaign_id, '_edd_price_options_mode', 1 );
    add_post_meta( $newcampaign_id, '_edd_hide_purchase_link', 'on' );
    add_post_meta( $newcampaign_id, ATCF_Campaign::$key_payment_provider, ATCF_Campaign::$payment_provider_lemonway );
    add_post_meta( $newcampaign_id, 'edd_variable_prices', array(1) );

    return $newcampaign_id;

}

/** Single Campaign *******************************************************/

class ATCF_Campaign {
	public $ID;
	public $data;
	public $api_data;
        
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
	public static $vote_percent_invest_ready_min_required = 100;

	public static $campaign_status_preparing = 'preparing';
	public static $campaign_status_validated = 'validated';
	public static $campaign_status_preview = 'preview';
	public static $campaign_status_vote = 'vote';
	public static $campaign_status_collecte = 'collecte';
	public static $campaign_status_funded = 'funded';
	public static $campaign_status_closed = 'closed';
	public static $campaign_status_archive = 'archive';

	static public function get_campaign_status_list(){
		return array(
			ATCF_Campaign::$campaign_status_preparing	=> 'D&eacute;pot de dossier',
            ATCF_Campaign::$campaign_status_validated	=> 'Pr&eacute;paration',
			ATCF_Campaign::$campaign_status_preview		=> 'Avant-premi&egrave;re',
			ATCF_Campaign::$campaign_status_vote		=> '&Eacute;valuation',
			ATCF_Campaign::$campaign_status_collecte	=> 'Investissement',
			ATCF_Campaign::$campaign_status_funded		=> 'Versement des royalties',
			ATCF_Campaign::$campaign_status_closed		=> 'Projet termin&eacute;',
			ATCF_Campaign::$campaign_status_archive		=> 'Projet &eacute;chou&eacute;'
		);
	}

	function __construct( $post, $api_id = FALSE ) {
		if ( !empty( $api_id ) ) {
			$this->api_data = WDGWPREST_Entity_Project::get( $api_id );
			$post = $this->api_data->wpref;
		}
		$this->data = get_post( $post );
		if ( !empty( $this->data ) ) {
			$this->ID   = $this->data->ID;
		}
		$this->load_api_data();
	}
	
	

	public function duplicate(){
		global $edd_options;
		// on sauvegarde dans la campagne parente l'id de toutes les campagnes dupliquées
		$duplicated_campaigns = json_decode($this->__get('duplicated_campaigns'));

		$duplicata = count($duplicated_campaigns);
		if ( empty( $duplicated_campaigns ) ) {
			$duplicata = 1;
		} else {
			$duplicata++;
		}
		$title = $this->get_name().' '.$duplicata;
		$author_ID = $this->post_author();


		$args = array(
			'post_type'   		 	=> 'download', //TODO ?
			'post_status'  		 	=> 'publish',//TODO ?
			'post_content' 		 	=> $this->description() ,
			'post_title'   		 	=> $title,
			'post_author'  			=> $author_ID,

		);

		$newcampaign_id = wp_insert_post( $args, true );	
		$duplicated_campaigns[] = $newcampaign_id;
		$this->__set('duplicated_campaigns', json_encode($duplicated_campaigns));

		// on copie les metas en bloc
		$metas = get_post_meta( $this->ID );		
		foreach ( $metas as $key => $value ) {
			add_post_meta( $newcampaign_id, $key, $this->__get($key) );
		}

		// Create category for blog
		$id_category = wp_insert_category( array('cat_name' => 'cat'.$newcampaign_id, 'category_nicename' => sanitize_title($newcampaign_id . '-blog-' . $title)) );
		if ( ! add_post_meta( $newcampaign_id, 'campaign_blog_category_id', $id_category, true) ) { 
			update_post_meta( $newcampaign_id, 'campaign_blog_category_id', $id_category );
		}		
		// on change le status de la campagne dupliquée
		if ( ! add_post_meta( $newcampaign_id, ATCF_Campaign::$key_campaign_status, ATCF_Campaign::$campaign_status_funded, true) ) { 
			update_post_meta( $newcampaign_id, ATCF_Campaign::$key_campaign_status, ATCF_Campaign::$campaign_status_funded );
		}
		if ( ! add_post_meta( $newcampaign_id, ATCF_Campaign::$key_validation_next_status, 0, true) ) { 
			update_post_meta( $newcampaign_id, ATCF_Campaign::$key_validation_next_status, 0 );
		}
		// on ajoute ces tags au cas où ils n'ont pas été créés
		if ( ! add_post_meta( $newcampaign_id, '_vc_post_settings',  $this->__get('_vc_post_settings'), true) ) { 
			update_post_meta( $newcampaign_id, '_vc_post_settings',  $this->__get('_vc_post_settings') );
		}
		if ( ! add_post_meta( $newcampaign_id, '_variable_pricing', $this->__get('_variable_pricing'), true) ) { 
			update_post_meta( $newcampaign_id, '_variable_pricing', $this->__get('_variable_pricing') );
		}
		// on change l'objectif max d ela campagne dupliquée
		if ( ! add_post_meta( $newcampaign_id, 'campaign_goal', $this->__get('campaign_minimum_goal'), true) ) { 
			update_post_meta( $newcampaign_id, 'campaign_goal', $this->__get('campaign_minimum_goal') );
		}
		// on vide la liste des campagnes dupliquées
		delete_post_meta($newcampaign_id, 'duplicated_campaigns');
		delete_post_meta($newcampaign_id, 'campaign_duplicata');
		delete_post_meta($newcampaign_id, 'campaign_backoffice_contract_orga ');
		delete_post_meta($newcampaign_id, 'campaign_backoffice_contract_agreement');
		delete_post_meta($newcampaign_id, 'id_api');


		return $newcampaign_id;
	}

	/**
	 * Chargement des données dans l'API
	 */
	private function load_api_data() {
		$api_id = $this->get_api_id();
		if ( !isset( $this->api_data ) && !empty( $api_id ) ) {
			$this->api_data = WDGWPREST_Entity_Project::get( $api_id );
//			$this->update_from_api();
		}
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
	
	public function get_api_data( $data_name ) {
		$buffer = FALSE;
		if ( !empty( $data_name ) && isset( $this->api_data->{$data_name} ) ) {
			$buffer = $this->api_data->{$data_name};
		}
		return $buffer;
	}
	
	public function set_api_data( $data_name, $data_value, $update = FALSE ) {
		$this->api_data->{$data_name} = $data_value;
		if ( $update ) {
			WDGWPREST_Entity_Project::update_data( $this->get_api_id(), $data_name, $data_value );
		}
	}
	
	public function update_api() {
		WDGWPREST_Entity_Project::update( $this );
	}
	
	/**
	 * Mise à jour des données WP en fonction des données présentes sur l'API
	 */
	private function update_from_api() {
		/*// Mise à jour du titre du post
		$api_data_name = $this->get_api_data( 'name' );
		if ( !empty( $api_data_name ) && $api_data_name != $this->data->post_title ) {
			wp_update_post(array(
				'ID'			=> $this->ID,
				'post_title'	=> $api_data_name
			));
		}
		
		// Mise à jour de l'url du post
		$api_data_url = $this->get_api_data( 'url' );
		if ( !empty( $api_data_url ) && $api_data_url != $this->data->post_name ) {
			$posts = get_posts( array(
				'name'		=> $api_data_url,
				'post_type' => array( 'post', 'page', 'download' )
			) );
			if ( $posts ) {
				wp_update_post(array(
					'ID'		=> $this->ID,
					'post_name'	=> $api_data_url
				));
			}
		}*/
		
		// Liaison aux catégories
		/*$api_data_type = json_decode( $this->get_api_data( 'type' ) );
		$api_data_category = json_decode( $this->get_api_data( 'category' ) );
		$api_data_impacts = json_decode( $this->get_api_data( 'impacts' ) );
		$api_data_partners = json_decode( $this->get_api_data( 'partners' ) );
		$api_data_tousnosprojets = json_decode( $this->get_api_data( 'tousnosprojets' ) );
		$cat_ids = array_merge( $api_data_type, $api_data_category, $api_data_impacts, $api_data_partners, $api_data_tousnosprojets );
		$cat_ids = array_map( 'intval', $cat_ids );
		if ( !empty( $cat_ids ) ) { 
			wp_set_object_terms( $this->ID, $cat_ids, 'download_category' );
		}
		 */
	}
	
	/**
	 * Déplace les données des campagnes sur l'API
	 */
	public static function move_campaigns_to_api() {
		$query_options = array(
			'posts_per_page' => -1,
			'post_type' => 'download'
		);
		$wpcampaigns = get_posts( $query_options );
		foreach ( $wpcampaigns as $wpcampaign ) {
			$WDGCampaign = new ATCF_Campaign( $wpcampaign->ID );
			$WDGCampaign->update_api();
		}
	}
	
	public static function is_campaign( $post_id ) {
		return ( get_post_meta( $post_id, 'campaign_funding_type', TRUE ) != '' );
	}
	

/*******************************************************************************
 * GESTION DES CAMPAGNES DUPLIQUEES
 ******************************************************************************/
	public function get_duplicate_campaigns_id() {		
		$duplicated_campaigns = json_decode($this->__get('duplicated_campaigns') );
		return $duplicated_campaigns;
	}
	public function get_duplicate_campaigns_titles() {
		$duplicated_campaigns = json_decode($this->__get('duplicated_campaigns') );
		$duplicated_campaigns_title = array();
		foreach ( $duplicated_campaigns as $wpcampaign ) {
			$WDGCampaign = new ATCF_Campaign( $wpcampaign );
			array_push($duplicated_campaigns_title, $WDGCampaign->get_name());
		}
		return $duplicated_campaigns_title;
	}
/*******************************************************************************
 * METAS
 ******************************************************************************/
	/**
	 * Liaison API
	 */
	public static $key_api_id = 'id_api';
	private $api_id;
	public function get_api_id() {
		if ( !isset( $this->api_id ) && !empty( $this->data ) ) {
			$this->api_id = FALSE;
			$is_campaign = ( get_post_meta( $this->data->ID, 'campaign_funding_type', TRUE ) != '' );
			if ( $is_campaign ) {
				$this->api_id = get_post_meta( $this->data->ID, ATCF_Campaign::$key_api_id, TRUE );
				if ( empty( $this->api_id ) ) {
					$api_project_return = WDGWPREST_Entity_Project::create( $this );
					$this->api_id = $api_project_return->id;
					ypcf_debug_log('ATCF_Campaign::get_api_id > ' . $this->api_id);
					update_post_meta( $this->data->ID, ATCF_Campaign::$key_api_id, $this->api_id );
				}
			}
		}
		return $this->api_id;
	}
	
	/**
	 * Version du type de projet
	 * @return int 
	 */
	public function edit_version() {
		return 3;
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
			$provider = ATCF_Campaign::$payment_provider_lemonway;
		}
		return $provider;
	}
	
	
/*******************************************************************************
 * TABLEAU DE BORD
 ******************************************************************************/
    public static $key_google_doc = 'campaign_google_doc';
    public function google_doc() {
        return $this->__get_translated_property(ATCF_Campaign::$key_google_doc);
    }

    public static $key_logbook_google_doc = 'campaign_logbook_google_doc';
    public function logbook_google_doc() {
        return $this->__get_translated_property(ATCF_Campaign::$key_logbook_google_doc);
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
	public function get_name() {
		$buffer = $this->get_api_data( 'name' );
		if ( empty( $buffer ) && !empty( $this->data ) ) {
			$buffer = $this->data->post_title;
		}
		return $buffer;
	}
	
	public function get_url() {
		$buffer = $this->get_api_data( 'url' );
		if ( empty( $buffer ) ) {
			$buffer = $this->data->post_name;
		}
		return $buffer;
	}
	
	public function get_public_url() {
		$buffer = $this->get_fake_url();
		if ( empty( $buffer ) ) {
			$buffer = get_permalink( $this->ID );
		}
		return $buffer;
	}
	
	public static $key_fake_url = 'fake_url';
	public function get_fake_url() {
		return $this->__get( ATCF_Campaign::$key_fake_url );
	}
	
	public static $key_asset_name_singular = 'asset_name_singular';
	public function get_asset_name_singular() {
		$buffer = $this->__get( ATCF_Campaign::$key_asset_name_singular );
		if ( empty( $buffer ) ) {
			$buffer = "Fairphone";
		}
		return $buffer;
	}
	
	public static $key_asset_name_plural = 'asset_name_plural';
	public function get_asset_name_plural() {
		$buffer = $this->__get( ATCF_Campaign::$key_asset_name_plural );
		if ( empty( $buffer ) ) {
			$buffer = "Fairphones";
		}
		return $buffer;
	}
	
	public static $key_partner_company_name = 'partner_company_name';
	public function get_partner_company_name() {
		$buffer = $this->__get( ATCF_Campaign::$key_partner_company_name );
		if ( empty( $buffer ) ) {
			$buffer = "Commown";
		}
		return $buffer;
	}
	
	//Rédaction projet
	public function subtitle() {
		return $this->__get_translated_property( 'campaign_subtitle' );
	}
    public function summary() {
        return $this->__get_translated_property( 'campaign_summary' );
    }

    /**
     * @return string This summary is used in the back-office to introduce the project
     */
    public static $key_backoffice_summary = 'campaign_backoffice_summary';
    public function backoffice_summary() {
		$buffer = $this->get_api_data( 'description' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_backoffice_summary );
		}
		return $buffer;
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
	
    public function team_contacts() {
        return $this->get_api_data( 'team_contacts' );
    }
	
	
/*******************************************************************************
 * FICHIERS
 ******************************************************************************/
    /**
     * @return string Business plan filename
     */
    public static $key_backoffice_businessplan = 'campaign_backoffice_businessplan';
    public function backoffice_businessplan() {
        return $this->__get(ATCF_Campaign::$key_backoffice_businessplan);
    }
	
	
/*******************************************************************************
 * CONTRATS
 ******************************************************************************/
	public function copy_default_contract_if_empty() {
		$project_override_contract = $this->override_contract();
		if ( empty( $project_override_contract ) ) {
			$edd_settings = get_option( 'edd_settings' );
			update_post_meta( $this->ID, self::$key_override_contract, $edd_settings[ 'standard_contract' ] );
		}
	}

    // Contrat vierge pour les personnes morales
    public static $key_backoffice_contract_orga = 'campaign_backoffice_contract_orga';
    public static $key_backoffice_contract_agreement = 'campaign_backoffice_contract_agreement';
    public function backoffice_contract_orga() {
        return $this->__get(ATCF_Campaign::$key_backoffice_contract_orga);
    }
    public function backoffice_contract_agreement() {
        return $this->__get( ATCF_Campaign::$key_backoffice_contract_agreement );
    }
	public function generate_contract_pdf_blank_organization() {
		$filename = 'blank-contract-organization-'.$this->ID.'.pdf';
		$filepath = __DIR__ . '/../contracts/' . $filename;
		if ( file_exists( $filepath ) ) {
			unlink( $filepath );
		}
		if ( getNewPdfToSign( $this->ID, FALSE, 'orga', $filepath ) != FALSE ) {
			$this->__set( ATCF_Campaign::$key_backoffice_contract_orga, $filename );
		}
		
		$filename_agreement = 'blank-contract-agreement-'.$this->ID.'.pdf';
		$filepath_agreement = __DIR__ . '/../contracts/' . $filename_agreement;
		if ( file_exists( $filepath_agreement ) ) {
			unlink( $filepath_agreement );
		}
		if ( getNewPdfToSign( $this->ID, FALSE, 'orga', $filepath_agreement, TRUE ) != FALSE ) {
			$this->__set( ATCF_Campaign::$key_backoffice_contract_agreement, $filename_agreement );
		}
	}
	
    public static $key_backoffice_contract_modifications = 'campaign_contract_modifications';
	public function contract_modifications() {
        return $this->__get( ATCF_Campaign::$key_backoffice_contract_modifications );
	}
	
    public static $key_agreement_bundle = 'campaign_agreement_bundle';
	public function agreement_bundle() {
        return $this->__get( ATCF_Campaign::$key_agreement_bundle );
	}
	
	// Contrat : descriptions des revenus, des dépenses
    public static $key_contract_earnings_description = 'campaign_contract_earnings_description';
	public function contract_earnings_description() {
		$buffer = $this->get_api_data( 'earnings_description' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_contract_earnings_description );
		}
		return $buffer;
	}
    public static $key_contract_spendings_description = 'campaign_contract_spendings_description';
	public function contract_spendings_description() {
		$buffer = $this->get_api_data( 'spendings_description' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_contract_spendings_description );
		}
		return $buffer;
	}
	// Contrat : informations simples et détaillées
    public static $key_contract_simple_info = 'campaign_contract_simple_info';
	public function contract_simple_info() {
		$buffer = $this->get_api_data( 'simple_info' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_contract_simple_info );
		}
		return $buffer;
	}
    public static $key_contract_detailed_info = 'campaign_contract_detailed_info';
	public function contract_detailed_info() {
		$buffer = $this->get_api_data( 'detailed_info' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_contract_detailed_info );
		}
		return $buffer;
	}
	// Contrat : prime et garantie
    public static $key_contract_premium = 'campaign_contract_premium';
	public function contract_premium() {
        return $this->__get( ATCF_Campaign::$key_contract_premium );
	}
    public static $key_contract_warranty = 'campaign_contract_warranty';
	public function contract_warranty() {
        return $this->__get( ATCF_Campaign::$key_contract_warranty );
	} 
	// Contrat : Type de budget
	public static $key_contract_budget_type = 'contract_budget_type';
	public static $contract_budget_types = array(
		'maximum'			=> "Plafond",
		'collected_funds'	=> "Montant collect&eacute;"
	);
	public function contract_budget_type() {
		$buffer = $this->__get( ATCF_Campaign::$key_contract_budget_type );
		if ( empty( $buffer ) ) {
			$buffer = 'maximum';
		}
		if ( $this->contract_maximum_type() == 'infinite' ) {
			$buffer = 'collected_funds';
		}
        return $buffer;
	}
	// Contrat : Type de plafond
	public static $key_contract_maximum_type = 'contract_maximum_type';
	public static $contract_maximum_types = array(
		'fixed'				=> "D&eacute;t&eacute;rmin&eacute;",
		'infinite'			=> "Infini"
	);
	public function contract_maximum_type() {
		$buffer = $this->__get( ATCF_Campaign::$key_contract_maximum_type );
		if ( empty( $buffer ) ) {
			$buffer = ( $this->goal( false ) > 0 ) ? 'fixed' : 'infinite';
		}
        return $buffer;
	}
	// Contrat : Type d'estimation de revenus trimestriels
	public static $key_quarter_earnings_estimation_type = 'contract_quarter_earnings_estimation_type';
	public static $quarter_earnings_estimation_types = array(
		'progressive'		=> "Progressif (10%, 20%, 30%, 40%)",
		'linear'			=> "Lin&eacute;aire (25%, 25%, 25%, 25%)"
	);
	public function quarter_earnings_estimation_type() {
		$buffer = $this->__get( ATCF_Campaign::$key_quarter_earnings_estimation_type );
		if ( empty( $buffer ) ) {
			$buffer = 'progressive';
		}
        return $buffer;
	}
    // Contrat : Rédaction surchargeant le contrat standard
	public static $key_override_contract = 'campaign_override_contract';
    public function override_contract() {
        return $this->__get( ATCF_Campaign::$key_override_contract );
    }
	
	//Ajouts contrat
	public function contract_title() {
		$buffer = $this->__get_translated_property('campaign_contract_title');
		if ( empty( $buffer ) ) {
			$buffer = __( "Contrat de cession de revenus futurs", 'yproject' );
		}
		return $buffer;
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
    public static $key_contract_doc_url = 'campaign_contract_doc_url';
    public function contract_doc_url() {
        return $this->__get(ATCF_Campaign::$key_contract_doc_url);
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
	
	
	public static $maximum_profit_list = array(
		'infinite'		=> "Infini",
		'1'				=> "1",
		'2'				=> "2",
		'3'				=> "3",
		'4'				=> "4",
		'5'				=> "5",
		'6'				=> "6",
		'7'				=> "7",
		'8'				=> "8",
		'9'				=> "9",
		'10'			=> "10",
	);
	public static $key_maximum_profit = 'maximum_profit';
	public function maximum_profit() {
		$buffer = $this->get_api_data( ATCF_Campaign::$key_maximum_profit );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_maximum_profit );
		}
		
		if ( empty( $buffer ) ) {
			$buffer = 2;
		}
		return $buffer;
	}
	
	public static $key_maximum_profit_precision = 'maximum_profit_precision';
	public function maximum_profit_precision() {
		$buffer = $this->get_api_data( ATCF_Campaign::$key_maximum_profit_precision );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_maximum_profit_precision );
		}
		if ( empty( $buffer ) ) {
			$buffer = 0;
		}
		return $buffer;
	}
	
	public function maximum_profit_complete() {
		$maximum_profit = $this->maximum_profit();
		if ( $maximum_profit == 'infinite' ) {
			$buffer = 1000000000;
		} else {
			$maximum_profit_precision = $this->maximum_profit_precision();
			if ( $maximum_profit_precision > 0 ) {
				$buffer = $maximum_profit .'.'. $maximum_profit_precision;
			} else {
				$buffer = $maximum_profit;
			}
		}
		
		return $buffer;
	}
	
	public function maximum_profit_amount() {
		$maximum_profit = $this->maximum_profit();
		if ( $maximum_profit == 'infinite' ) {
			return 1000000000;
		} else {
			return $this->current_amount( FALSE ) * $this->maximum_profit_complete();
		}
	}
	
	public function maximum_profit_str() {
		$buffer = $this->maximum_profit();
		if ( $buffer == 'infinite' ) {
			$buffer = __( "Infini", 'yproject' );
		} else {
			$buffer = 'x' . $buffer;
			$maximum_profit_precision = $this->maximum_profit_precision();
			if ( $maximum_profit_precision > 0 ) {
				$buffer .= ',' . $maximum_profit_precision;
			}
		}
		return $buffer;
	}
	
	public function minimum_profit() {
		$buffer = $this->get_api_data( 'minimum_profit' );
		if ( empty( $buffer ) ) {
			$buffer = 1;
		}
		return $buffer;
	}
	
	public function minimum_profit_amount() {
		return $this->current_amount( FALSE ) * $this->minimum_profit();
	}
	
	public static $key_minimum_goal_display = 'minimum_goal_display';
	public static $key_minimum_goal_display_option_minimum_as_max = 'minimum_as_max';
	public static $key_minimum_goal_display_option_minimum_as_step = 'minimum_as_step';
	public function get_minimum_goal_display() {
		$buffer = $this->get_api_data( ATCF_Campaign::$key_minimum_goal_display );
		if ( empty( $buffer ) ) {
			$buffer = ATCF_Campaign::$key_minimum_goal_display_option_minimum_as_max;
		}
		return $buffer;
	}
	
	public static $key_hide_investors = 'hide_investors';
	public function get_hide_investors() {
		$metadata_value = $this->__get( ATCF_Campaign::$key_hide_investors );
		$buffer = ( $metadata_value == '1' );
		return $buffer;
	}
	
	public static $key_show_comments_for_everyone = 'show_comments_for_everyone';
	public function get_show_comments_for_everyone() {
		$metadata_value = $this->__get( ATCF_Campaign::$key_show_comments_for_everyone );
		$buffer = ( $metadata_value == '1' );
		return $buffer;
	}
	
	public function has_planned_advice_notification() {
		return WDGQueue::has_planned_campaign_advice_notification( $this->ID );
	}
	
	public static $key_can_invest_until_contract_start_date = 'can_invest_until_contract_start_date';
	public function can_invest_until_contract_start_date() {
		$metadata_value = $this->__get( ATCF_Campaign::$key_can_invest_until_contract_start_date );
		$buffer = ( $metadata_value == '1' );
		return $buffer;
	}
	
	public function get_end_date_when_can_invest_until_contract_start_date() {
		// 14 jours avant la date de début de contrat
		$datetime_first_payment = new DateTime( $this->contract_start_date() );
		$datetime_first_payment->sub( new DateInterval( 'P14D' ) );
		return $datetime_first_payment;
	}
	
	public static $key_archive_message = 'archive_message';
	public function archive_message() {
		return $this->__get( ATCF_Campaign::$key_archive_message );
	}

	public static $key_end_vote_pending_message = 'end_vote_pending_message';
	public function end_vote_pending_message() {
		return $this->__get( ATCF_Campaign::$key_end_vote_pending_message );
	}
	
	public static $key_maximum_complete_message = 'maximum_complete_message';
	public function maximum_complete_message() {
		return $this->__get( ATCF_Campaign::$key_maximum_complete_message );
	}
	
	public static $key_google_tag_manager_id = 'google_tag_manager_id';
	public function google_tag_manager_id() {
		return $this->__get( ATCF_Campaign::$key_google_tag_manager_id );
	}
	
	public static $key_custom_footer_code = 'custom_footer_code';
	public function custom_footer_code() {
		return $this->__get( ATCF_Campaign::$key_custom_footer_code );
	}
	
	public function get_funded_certificate_url() {
		$this->make_funded_certificate();
		$buffer = home_url() . '/wp-content/plugins/appthemer-crowdfunding/files/campaign-funded/';
		$buffer .= $this->get_funded_certificate_filename();
		return $buffer;
	}
	private function get_funded_certificate_filename() {
		$buffer = 'funded-certificate-' .$this->ID. '-' .$this->get_api_id(). '.pdf';
		return $buffer;
	}
	public function make_funded_certificate( $force = FALSE, $str_date_end = FALSE, $free_field = '' ) {
		$filename = $this->get_funded_certificate_filename();
		$filepath = __DIR__ . '/../../files/campaign-funded/' . $filename;
		if ( !$force && file_exists( $filepath ) ) {
			return;
		}
		
		if ( $this->platform_commission() == '' ) {
			return;
		}
		$data_contract_start_date = $this->contract_start_date();
		if ( !empty( $data_contract_start_date ) ) {
			$start_datetime = new DateTime( $data_contract_start_date );
		} else {
			return;
		}
		$edd_settings = get_option( 'edd_settings' );
		$fiscal_info = $edd_settings[ 'accounting_fiscal_info' ];
		if ( empty( $fiscal_info ) ) {
			return;
		}
		
		$WDGUser = new WDGUser( $this->data->post_author );
		$campaign_organization = $this->get_organization();
		$WDGOrganization = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
		
		$project_investors_list = array();
		$investments_list = $this->payments_data( TRUE );
		$date_end = FALSE;
		if ( !empty( $str_date_end ) ) {
			$date_end = new DateTime( $str_date_end );
			$date_end->setTime( 23, 59, 59 );
		}
		
		$amount = 0;
		
		foreach ( $investments_list as $investment_item ) {
			$date_investment = new DateTime( $investment_item[ 'date' ] );
			if ( $investment_item[ 'status' ] == 'publish' && ( empty( $date_end ) || $date_end >= $date_investment ) ) {
				if ( WDGOrganization::is_user_organization( $investment_item[ 'user' ] ) ) {
					$orga = new WDGOrganization( $investment_item[ 'user' ] );
					$firstname = $orga->get_name();
					$lastname = '';
				} else {
					if ( !empty( $investment_item['item'] ) ) {
						$firstname = $investment_item['item']->firstname;
						$lastname = $investment_item['item']->lastname;

					} else {
						$WDGUserPayment = new WDGUser( $investment_item[ 'user' ] );
						$firstname = $WDGUserPayment->get_firstname();
						$lastname = $WDGUserPayment->get_lastname();
					}
				}

				$amount += $investment_item['amount'];
				array_push( $project_investors_list, array( "firstname" => $firstname, "lastname" => $lastname, "amount" => $investment_item['amount'] ) );
			}
		}
		
		$today_date = new DateTime();
		$platform_commission = $this->platform_commission();
		$platform_commission_amount = $this->platform_commission_amount();
		$platform_commission_below_100000 = $this->platform_commission();
		$platform_commission_below_100000_amount = $this->platform_commission_below_100000_amount();
		$platform_commission_above_100000 = $this->platform_commission_above_100000();
		$platform_commission_above_100000_amount = $this->platform_commission_above_100000_amount();
		
		require __DIR__. '/../control/templates/pdf/certificate-campaign-funded.php';
		$html_content = WDG_Template_PDF_Campaign_Funded::get(
			$WDGUser->get_firstname() . ' ' . $WDGUser->get_lastname(),
			$WDGUser->get_email(),
			$WDGOrganization->get_name(),
			$WDGOrganization->get_full_address_str(),
			$WDGOrganization->get_postal_code(),
			$WDGOrganization->get_city(),
			$free_field,
			$today_date->format( 'd/m/Y' ),
			$this->backers_count(),
			UIHelpers::format_number( $amount ),
			UIHelpers::format_number( $platform_commission ),
			UIHelpers::format_number( $platform_commission_amount ),
			UIHelpers::format_number( $platform_commission_below_100000 ),
			UIHelpers::format_number( $platform_commission_below_100000_amount ),
			UIHelpers::format_number( $platform_commission_above_100000 ),
			UIHelpers::format_number( $platform_commission_above_100000_amount ),
			UIHelpers::format_number( $amount - $platform_commission_amount ),
			$start_datetime->format( 'd/m/Y' ),
			$this->funding_duration(),
			UIHelpers::format_number( $this->roi_percent(), 10 ),
			$fiscal_info,
			$project_investors_list
		);
		
		$html2pdf = new HTML2PDF( 'P', 'A4', 'fr', true, 'UTF-8', array(12, 5, 15, 8) );
		$html2pdf->WriteHTML( urldecode( $html_content ) );
		$html2pdf->Output( $filepath, 'F' );
	}
	
/*******************************************************************************
 * GESTION ROI
 ******************************************************************************/
	public static $key_forced_mandate = 'campaign_forced_mandate';
	public function is_forced_mandate() {
		$buffer = $this->__get( ATCF_Campaign::$key_forced_mandate );
		return ($buffer == 1);
	}
	public function set_forced_mandate( $new_value ) {
		update_post_meta( $this->ID, ATCF_Campaign::$key_forced_mandate, $new_value );
	}
	
	public static $key_mandate_conditions = 'campaign_mandate_conditions';
	public function mandate_conditions() {
		return $this->__get( ATCF_Campaign::$key_mandate_conditions );
	}
	
	public static $key_declaration_info = 'campaign_declaration_info';
	public function declaration_info() {
		return $this->__get( ATCF_Campaign::$key_declaration_info );
	}

	public static $funding_duration_list = array(
		'1'				=> "1 an",
		'2'				=> "2 ans",
		'3'				=> "3 ans",
		'4'				=> "4 ans",
		'5'				=> "5 ans",
		'6'				=> "6 ans",
		'7'				=> "7 ans",
		'8'				=> "8 ans",
		'9'				=> "9 ans",
		'10'			=> "10 ans",
		'15'			=> "15 ans",
		'20'			=> "20 ans",
		'25'			=> "25 ans",
		'30'			=> "30 ans",
		'0'				=> "Dur&eacute;e ind&eacute;termin&eacute;e"
	);
    public static $key_funding_duration = 'campaign_funding_duration';
    public function funding_duration() {
		$buffer = $this->get_api_data( 'funding_duration' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_funding_duration );
		}
		if ( empty( $buffer ) && $buffer != 0 ) {
			$buffer = 5;
		}
	    return $buffer;
	}
    public function funding_duration_str() {
		$buffer = $this->funding_duration() . __( " ans", 'yproject' );
		if ( $this->funding_duration() == 0 ) {
			$buffer = __( "une dur&eacute;e ind&eacute;termin&eacute;e", 'yproject' );
		}
		return $buffer;
	}
	
	public function is_beyond_funding_duration() {
		$today_date = new DateTime();
		$str_date_contract_start = $this->contract_start_date();
		$date_contract_start = new DateTime( $str_date_contract_start );
		$date_contract_start->add( new DateInterval( 'P' .$this->funding_duration(). 'Y' ) );
		
		return ( $today_date > $date_contract_start );
	}

	/**
	 * Pourcentage de royalties si la campagne atteint le maximum indiqué
	 */
    public static $key_roi_percent_estimated = 'campaign_roi_percent_estimated';
	public function roi_percent_estimated() {
		$buffer = $this->get_api_data( 'roi_percent_estimated' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_roi_percent_estimated );
		}
		if ( empty( $buffer ) ) {
			$buffer = $this->roi_percent();
		}
		return $buffer;
	}
	/**
	 * Pourcentage de royalties engagé, en fonction du montant atteint
	 */
	public static $key_roi_percent = 'campaign_roi_percent';
	public function roi_percent() {
		$buffer = $this->__get( ATCF_Campaign::$key_roi_percent );
		if ( empty( $buffer ) ) {
			$buffer = 0;
		}
	    return $buffer;
	}
	/**
	 * Pourcentage de royalties restant (sur la liste des contrats en cours)
	 */
	public function roi_percent_remaining() {
		$buffer = 0;
		$investment_contracts = WDGInvestmentContract::get_list( $this->ID );
		if ( !empty( $investment_contracts ) ) {
			foreach ( $investment_contracts as $investment_contract ) {
				if ( $investment_contract->status == WDGInvestmentContract::$status_active ) {
					$buffer += $investment_contract->turnover_percent;
				}
			}
			
		} else {
			$buffer = $this->roi_percent();

		}
		return $buffer;
	}

    public static $key_contract_start_date = 'campaign_contract_start_date';
	public function contract_start_date() {
		$buffer = $this->get_api_data( 'contract_start_date' );
		if ( empty( $buffer ) || $buffer == '0000-00-00' ) {
			$buffer = $this->__get( ATCF_Campaign::$key_contract_start_date );
		}
	    return $buffer;
	}

	public function contract_start_date_is_undefined() {
		$buffer = $this->get_api_data( 'contract_start_date_is_undefined' );
	    return $buffer;
	}

    public static $key_first_payment_date = 'campaign_first_payment_date';
	public function first_payment_date() {
	    return $this->__get(ATCF_Campaign::$key_first_payment_date);
	}
	
	// Frais minimums appliqués au porteur de projet
	public function get_minimum_costs_to_organization() {
		$buffer = $this->get_api_data( 'minimum_costs_to_organization' );
		if ( empty( $buffer ) ) {
			$buffer = 0;
		}
		return $buffer;
	}
	// Frais (%) appliqués au porteur de projet
	public static $key_costs_to_organization = 'costs_to_organization';
	public function get_costs_to_organization() {
		$buffer = $this->get_api_data( 'costs_to_organization' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_costs_to_organization );
		}
		if ( empty( $buffer ) ) {
			$buffer = 0;
		}
		return $buffer;
	}
	// Frais appliqués aux investisseurs
	public static $key_costs_to_investors = 'costs_to_investors';
	public function get_costs_to_investors() {
		$buffer = $this->get_api_data( 'costs_to_investors' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_costs_to_investors );
		}
		if ( empty( $buffer ) ) {
			$buffer = 0;
		}
		return $buffer;
	}
	
	
	public static $key_estimated_turnover_unit = 'campaign_estimated_turnover_unit';
	public function estimated_turnover_unit() {
		$buffer = $this->get_api_data( 'estimated_turnover_unit' );
		if ( empty( $buffer ) ) {
			// Values : euro, percent
			$buffer = $this->__get( ATCF_Campaign::$key_estimated_turnover_unit );
		}
		if ( empty( $buffer ) ) {
			$buffer = 'euro';
		}
	    return $buffer;
	}
	public static $key_estimated_turnover = 'campaign_estimated_turnover';
	public function estimated_turnover() {
		$buffer = $this->get_api_data( 'estimated_turnover' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_estimated_turnover );
		}
	    return json_decode($buffer, TRUE);
	}
	
	public function yield_for_investors() {
		$estimated_turnover_list = $this->estimated_turnover();
		$estimated_turnover_total = 0;
		if ( !empty( $estimated_turnover_list ) ){
			foreach ( $estimated_turnover_list as $key => $turnover ) {
				$estimated_turnover_total += $turnover;
			}
		}
		
		$roi_percent_estimated = $this->roi_percent_estimated();
		$roi_amount_estimated = $estimated_turnover_total * $roi_percent_estimated / 100;
				
		$goal = $this->goal( false );
		$buffer = round( ( ( $roi_amount_estimated / $goal ) - 1 ) * 100 * 100 ) / 100;
		
		return $buffer;
	}
	
	public static $key_turnover_per_declaration = 'turnover_per_declaration';
	public function get_turnover_per_declaration() {
		$buffer = $this->get_api_data( 'turnover_per_declaration' );
		if ( empty( $buffer ) ) {
			$buffer = $this->__get( ATCF_Campaign::$key_turnover_per_declaration );
		}
		if ( empty( $buffer ) ) { $buffer = 3; }
		return $buffer;
	}
	
	public static $key_declaration_periodicity = 'declaration_periodicity';
	public static $declaration_periodicity_list = array(
		'month'		=> 'mensuelle',
		'quarter'	=> 'trimestrielle',
		'semester'	=> 'semestrielle',
		'year'		=> 'annuelle'
	);
	public static $declaration_period_list = array(
		'month'		=> 'mois',
		'quarter'	=> 'trimestre',
		'semester'	=> 'semestre',
		'year'		=> 'an'
	);
	public static $declaration_period_list_plural = array(
		'month'		=> 'mois',
		'quarter'	=> 'trimestres',
		'semester'	=> 'semestres',
		'year'		=> 'ans'
	);
	public function get_declaration_periodicity() {
		$buffer = $this->get_api_data( self::$key_declaration_periodicity );
		if ( empty( $buffer ) ) { $buffer = 'quarter'; }
		return $buffer;
	}
	public function get_declararations_count_per_year() {
		$buffer = 4;
		$declaration_periodicity = $this->get_declaration_periodicity();
		switch ( $declaration_periodicity ) {
			case 'month':
				$buffer = 12;
				break;
			case 'semester':
				$buffer = 2;
				break;
			case 'year':
				$buffer = 1;
				break;
		}
		
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
	
	public function generate_missing_declarations( $month_count = 3, $declarations_limit = FALSE ) {
		// Calcul du nombre de déclarations que devra faire le projet
		$nb_in_a_year = 12 / $month_count;
		$funding_duration = $this->funding_duration();
		if ( $funding_duration == 0 ) {
			$funding_duration = 1;
		}
		$nb_declarations = $funding_duration * $nb_in_a_year;
		
		// Permet de rajouter des déclarations si nécessaires
		$count_added_declaration = 0;
		
		if ( ( isset( $nb_declarations ) && $nb_declarations > 0 ) || !empty( $declarations_limit ) ) {
			// Récupération des déclarations existantes
			$existing_roi_declarations = $this->get_roi_declarations();
			// On part de la date de début de versement
			$current_date = new DateTime( $this->first_payment_date() );
			for ( $i = 0; $i < $nb_declarations; $i++ ) {
				// On ne l'ajoute que si elle n'existe pas déjà
				$add_date = TRUE;
				foreach ( $existing_roi_declarations as $declaration_object ) {
					if ( $current_date->format( 'Y-m-d' ) == $declaration_object[ 'date_due' ] ) {
						$add_date = FALSE;
					}
				}
				if ( $add_date ) {
					WDGROIDeclaration::insert( $this->get_api_id(), $current_date->format( 'Y-m-d' ) );
					$count_added_declaration++;
				}
				if ( !empty( $declarations_limit ) && $count_added_declaration >= $declarations_limit ) {
					break;
				}
				$current_date->add( new DateInterval( 'P'.$month_count.'M' ) );
			}
			
			// Si il faut ajouter des déclarations
			if ( !empty( $declarations_limit ) ) {
				while ( $count_added_declaration < $declarations_limit ) {
					WDGROIDeclaration::insert( $this->get_api_id(), $current_date->format( 'Y-m-d' ) );
					$current_date->add( new DateInterval( 'P'.$month_count.'M' ) );
					$count_added_declaration++;
				}
			}
		}
	}
	
	private $adjustments;
	public function get_adjustments() {
		if ( !isset( $this->adjustments ) ) {
			$this->adjustments = array();
			
			$adjustment_list = WDGWPREST_Entity_Adjustment::get_list_by_project_id( $this->get_api_id() );
			if ( !empty( $adjustment_list ) ) {
				foreach ( $adjustment_list as $adjustment_item ) {
					$WDGAdjustment = new WDGAdjustment( $adjustment_item->id, $adjustment_item );
					array_push( $this->adjustments, $WDGAdjustment );
				}
			}
		}
		return $this->adjustments;
	}
	
	private $roi_declarations;
	public function get_roi_declarations() {
		if ( !isset( $this->roi_declarations ) ) {
			$this->roi_declarations = array();

			$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $this->ID );
			foreach ( $declaration_list as $declaration_item ) {
				$buffer_declaration_object = array();
				$buffer_declaration_object['id'] = $declaration_item->id;
				$buffer_declaration_object['item'] = $declaration_item;
				$buffer_declaration_object['project'] = $this->ID;
				$buffer_declaration_object['date_due'] = $declaration_item->date_due;
				$buffer_declaration_object['date_transfer'] = $declaration_item->date_transfer;
				$buffer_declaration_object['total_turnover'] = $declaration_item->get_turnover_total();
				$buffer_declaration_object['total_roi'] = $declaration_item->amount;
				$buffer_declaration_object['total_roi_with_adjustment'] = $declaration_item->get_amount_with_adjustment();
				$buffer_declaration_object['status'] = $declaration_item->status;
				$buffer_declaration_object['roi_list'] = array();
				$buffer_declaration_object['roi_list_by_investment_id'] = array();
				if ( $declaration_item->status == WDGROIDeclaration::$status_finished ) {
					$roi_list = $declaration_item->get_rois();
					foreach ( $roi_list as $roi_item ) {
						$roi_object = array();
						$roi_object['id'] = $roi_item->id;
						$roi_object['amount'] = $roi_item->amount;
						$roi_object['recipient_type'] = $roi_item->recipient_type;
						$roi_object['recipient_api_id'] = $roi_item->id_user;
						$roi_object['id_investment'] = $roi_item->id_investment;
						array_push( $buffer_declaration_object["roi_list"], $roi_object );
						$buffer_declaration_object['roi_list_by_investment_id'][ $roi_item->id_investment ] = $roi_object;
					}
				}
				array_push( $this->roi_declarations, $buffer_declaration_object );
			}
		}
		
		return $this->roi_declarations;
	}
	
	/**
	 * Retourne le nombre de déclarations de ROI (total)
	 * @return int
	 */
	public function get_roi_declarations_number() {
		$roi_declarations = $this->get_roi_declarations();
		return count( $roi_declarations );
	}
	
	/**
	 * Retourne le montant des CA déclarés
	 * @return int
	 */
	public function get_roi_declarations_total_turnover_amount() {
		$buffer = 0;
		$declaration_list = $this->get_roi_declarations();
		foreach ( $declaration_list as $declaration_item ) {
			$buffer += $declaration_item["total_turnover"];
		}
		return $buffer;
	}
	
	/**
	 * Retourne le montant des ROI versées
	 * @return int
	 */
	public function get_roi_declarations_total_roi_amount() {
		$buffer = 0;
		$declaration_list = $this->get_roi_declarations();
		foreach ( $declaration_list as $declaration_item ) {
			if ( $declaration_item[ 'status' ] != WDGROIDeclaration::$status_declaration ) {
				$buffer += $declaration_item["total_roi"];
			}
		}
		return $buffer;
	}
	
	/**
	 * Renvoie la liste des déclarations selon un statut particulier
	 * @param string $status
	 */
	public function get_roi_declarations_by_status( $status ) {
		$buffer = array();
		
		if ( !empty( $status ) ) {
			$declaration_list = $this->get_roi_declarations();
			foreach ( $declaration_list as $declaration_item ) {
				if ( $declaration_item[ 'status' ] == $status ) {
					array_push( $buffer, $declaration_item[ 'item' ] );
				}
			}
		}
		
		return $buffer;
	}
	
	private $current_roi_declarations;
	public function has_current_roi_declaration() {
		$current_roi_declarations = $this->get_current_roi_declarations();
		return ( !empty( $current_roi_declarations ) );
	}
	/**
	 * Retourne la liste des déclarations qui sont en cours
	 * Ce sont les déclarations dont 
	 * - le statut n'est pas "finished"
	 * - la date est dépassée ou la différence par rapport à aujourd'hui est de moins de 10 jours
	 * @return array
	 */
	public function get_current_roi_declarations() {
		if ( !isset( $this->current_roi_declarations ) ) {
			$declaration_list = $this->get_roi_declarations();
			$this->current_roi_declarations = array();
			$date_now = new DateTime();
			foreach ( $declaration_list as $declaration_item ) {
				$date_due = new DateTime( $declaration_item[ 'date_due' ] );
				$date_interval = $date_now->diff( $date_due );
				if ( $declaration_item[ 'status' ] != WDGROIDeclaration::$status_finished && $declaration_item[ 'status' ] != WDGROIDeclaration::$status_failed && ( $date_due < $date_now || $date_interval->format( '%a' ) < $date_due->format( 'd' ) + 1 ) ) {
					array_push( $this->current_roi_declarations, $declaration_item[ 'item' ] );
				}
			}
		}
		return $this->current_roi_declarations;
	}
	
	private $next_roi_declaration;
	public function has_next_roi_declaration() {
		$next_roi_declaration = $this->get_next_roi_declaration();
		return ( !empty( $next_roi_declaration ) );
	}
	public function get_next_roi_declaration() {
		if ( !isset( $this->next_roi_declaration ) ) {
			$declaration_list = $this->get_roi_declarations_by_status( WDGROIDeclaration::$status_declaration );
			$this->next_roi_declaration = FALSE;
			if ( !empty( $declaration_list ) ) {
				$this->next_roi_declaration = $declaration_list[ 0 ];
			}
		}
		return $this->next_roi_declaration;
	}
	
	
/*******************************************************************************
 * GESTION CATEGORIES
 ******************************************************************************/
	public static $key_blog_category_id = 'campaign_blog_category_id';
	public function get_news_category_id() {
	    $cat_id = $this->__get( ATCF_Campaign::$key_blog_category_id );
		
		if ( empty ( $cat_id ) ) {
			$category_slug = $this->ID . '-blog-' . $this->data->post_name;
			$category_obj = get_category_by_slug($category_slug);
			$cat_id = $category_obj->cat_ID;
		}
		
		return $cat_id;
	}
	
	public function get_news_posts( $nb = -1 ) {
		$posts_in_category = array();
		
		$cat_id = $this->get_news_category_id();
		if ( !empty( $cat_id ) ) {
			$posts_in_category = get_posts( array(
				'category'	=> $cat_id,
				'showposts'	=> $nb
			) );
		}
		
		return $posts_in_category;
	}
	
	public function get_categories() {
		$buffer = wp_get_object_terms( $this->ID, 'download_category' );
		return $buffer;
	}
	public function get_categories_str() {
		$buffer = '';
		$categories = $this->get_categories();
		foreach ($categories as $category) {
			if (!empty($buffer)) {
				$buffer .= ', ';
			}
			$buffer .= $category->slug;
		}
			
		return $buffer;
	}
	
	/**
	 * 
	 * @param string $type Type de catégorie selon le parent : categories, activities, types, partners, tousnosprojets
	 * @param boolean $return_str Si true, retourne une chaine de caractère
	 * @return array or string
	 */
	public function get_categories_by_type( $type = 'categories', $return_str = FALSE ) {
		// Récupération de la liste des catégories sous le slug en paramètre
		$terms = get_terms( 'download_category', array( 'slug' => $type, 'hide_empty' => false ) );
		$term_category_type_id = $terms[0]->term_id;
		// Récupération de la liste des catégories de la campagne
		$campaign_categories = $this->get_categories();
		
		// Construction chaine
		if ( $return_str ) {
			$buffer = '';
			foreach ( $campaign_categories as $campaign_category ) {
				if ( $campaign_category->parent == $term_category_type_id ) {
					if ( !empty( $buffer ) ) {
						$buffer .= ', ';
					}
					$buffer .= $campaign_category->name;
				}
			}
			
		// Construction tableau
		} else {
			$buffer = array();
			foreach ( $campaign_categories as $campaign_category ) {
				if ( $campaign_category->parent == $term_category_type_id ) {
					array_push( $buffer, $campaign_category );
				}
			}
		}
		return $buffer;
	}
	
	public function has_category_slug( $type, $slug ) {
		$buffer = FALSE;
		$categories_by_type = $this->get_categories_by_type( $type );
		foreach ( $categories_by_type as $campaign_category ) {
			if ( $campaign_category->slug == $slug ) {
				$buffer = TRUE;
			}
		}
		return $buffer;
	}
	
	public function get_subcategories_hashtags() {
		$buffer = '';
		
		$categories_env_list = $this->get_categories_by_type( 'environnemental' );
		if ( $categories_env_list ) {
			foreach ( $categories_env_list as $category ) {
				if ( $buffer != '' ) {
					$buffer .= ', ';
				}
				$buffer .= '<span class="hashtag-environment">' . __( htmlentities( strtolower( $category->name ) ), 'yproject' ) . '</span>';
			}
		}
		
		$categories_soc_list = $this->get_categories_by_type( 'social' );
		if ( $categories_soc_list ) {
			foreach ( $categories_soc_list as $category ) {
				if ( $buffer != '' ) {
					$buffer .= ', ';
				}
				$buffer .= '<span class="hashtag-social">' . __( htmlentities( strtolower( $category->name ) ), 'yproject' ) . '</span>';
			}
		}
		
		$categories_eco_list = $this->get_categories_by_type( 'economique' );
		if ( $categories_eco_list ) {
			foreach ( $categories_eco_list as $category ) {
				if ( $buffer != '' ) {
					$buffer .= ', ';
				}
				$buffer .= '<span class="hashtag-economy">' . __( htmlentities( strtolower( $category->name ) ), 'yproject' ) . '</span>';
			}
		}
		
		return $buffer;
	}
	
	public function is_positive_savings() {
		return $this->has_category_slug( 'types', 'epargne-positive' );
	}
	
/*******************************************************************************
 * GESTION STATUTS
 ******************************************************************************/
	/**
	 * Returns the campaign current status
	 * @return string Possible answers : see get_campaign_status_list()
	 */
	public static $key_campaign_status = 'campaign_vote';
	private $status;
	public function campaign_status() {
		if ( empty( $this->status ) ) {
			$this->status = $this->get_api_data( 'status' );
			if ( empty( $this->status ) ) {
				$this->status = $this->__get( ATCF_Campaign::$key_campaign_status );
			}
		}
		return $this->status;
	}
	/**
	 * Deprecated : use campaign_status instead
	 */
	public function vote() {
		return $this->campaign_status();
	}
	
	/**
	 * Returns true the author is preparing the project
	 */
	public function is_preparing() {
		$campaign_status = $this->campaign_status();
		return ( $campaign_status == ATCF_Campaign::$campaign_status_preparing || $campaign_status == ATCF_Campaign::$campaign_status_validated );
	}
	
	/**
	 * Returns true if it is possible to invest on the project
	 */
	public function is_investable() {
		// Possible d'investir si le porteur de projet a bien rempli ses informations et que le montant max n'a pas été atteint
		$buffer = ypcf_check_user_is_complete( $this->data->post_author ) && $this->percent_completed( false ) < 100;
		// Si en évaluation, il faut que l'utilisateur ait évalué
		$WDGUser_current = WDGUser::current();
		$is_vote_investable = ( $this->campaign_status() == ATCF_Campaign::$campaign_status_vote ) && ( $WDGUser_current->has_voted_on_campaign( $this->ID ) );
		// Si en investissement et qu'il reste du temps
		$is_collecte_investable = ( $this->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) && $this->is_remaining_time();
		return $buffer && ( $is_vote_investable || $is_collecte_investable );
	}
	
	/**
	 * Indique si le porteur de projet est autorisé à passer à l'étape
	 * suivante par la modération
	 * @return boolean
	 */
    public static $key_validation_next_status = 'campaign_validated_next_step';
	private $can_go_next;
    public function can_go_next_status() {
		if ( !isset( $this->can_go_next ) ) {
			$this->can_go_next = $this->get_api_data( 'can_go_next' );
			if ( empty( $this->can_go_next ) ) {
				$this->can_go_next = $this->__get( ATCF_Campaign::$key_validation_next_status );
			}
		}
		return ( $this->can_go_next == 1 );
	}

    /**
     * Modifie la validation de modération pour le passage à l'étape suivante
     * @param $value Valeur du flag de validation (true si le PP peut passer à l'étape suivante, false sinon)
     * @return bool|int
     */
    public function set_validation_next_status($value){
        if($value === true || $value === "true" || $value===1){
			$this->can_go_next = 1;
            return update_post_meta($this->ID, ATCF_Campaign::$key_validation_next_status, 1);
        }

        if($value === false || $value === "false" || $value===0 || $value===''){
			$this->can_go_next = 0;
            return update_post_meta($this->ID, ATCF_Campaign::$key_validation_next_status, 0);
        }

        return false;
    }
	
	
	/**
	 * Gestion centralisée des différentes coches dans le TB
	 * Valeurs possibles pour les step : has_filled_desc, has_filled_finance, has_filled_parameters, has_signed_order
	 * @var string 
	 */
	private $validation_steps;
	public static $key_validation_steps = 'campaign_validation_steps';
	public function get_validation_step_status( $step ) {
		if ( !isset( $this->validation_steps ) ) {
			$this->validation_steps = json_decode( $this->__get( ATCF_Campaign::$key_validation_steps ) );
		}
		if ( isset( $this->validation_steps->$step ) ) {
			$buffer = $this->validation_steps->$step;
		}
		if ( empty( $buffer ) ) {
			$buffer = FALSE;
		}
		return $buffer;
	}
	
	public function set_validation_step_status( $step, $status ) {
		if ( !isset( $this->validation_steps ) ) {
			$this->validation_steps = array();
		}
		$this->validation_steps[ $step ] = $status;
		update_post_meta( $this->ID, ATCF_Campaign::$key_validation_steps, json_encode( $this->validation_steps ) );
	}
	

	
/*******************************************************************************
 * AUTRES DONNEES
 ******************************************************************************/
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
    public static $key_goal = 'campaign_goal';
    public function goal( $formatted = true ) {
		$goal = $this->get_api_data( 'goal_maximum' );
		if ( empty( $goal ) ) {
			$goal = $this->__get( ATCF_Campaign::$key_goal );
		}

		if ( ! is_numeric( $goal ) )
			return 0;

		if ( $formatted ) {
		    $currency = edd_get_currency();
		    if ($currency == "EUR") {
			if (strpos($goal, '.00') !== false) $goal = substr ($goal, 0, -3);
				return $goal . '&nbsp;&euro;';
		    } else {
				return edd_currency_filter( edd_format_amount( $goal ) );
		    }
		}

		return $goal;
	}
	
	public static $key_minimum_goal = 'campaign_minimum_goal';
	public function minimum_goal($formatted = false) {
		$goal = $this->get_api_data( 'goal_minimum' );
		if ( empty( $goal ) ) {
			$goal = $this->__get( ATCF_Campaign::$key_minimum_goal );
		}
	    if (strpos($goal, '.00') !== false) $goal = substr ($goal, 0, -3);
	    if ( ! is_numeric( $goal ) && ($this->type() != 'flexible') )
		    $goal = 0;
	    if ($goal == 0) $goal = $this->goal(false);
	    if ($formatted) $goal .= '&nbsp;&euro;';
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
	
    public static $key_platform_commission = 'campaign_platform_commission';
	public function platform_commission( $with_tax = TRUE ) {
		$commission_with_tax = $this->__get( ATCF_Campaign::$key_platform_commission );
		if ( !empty( $commission_with_tax ) && !$with_tax ) {
			$buffer = $commission_with_tax / 1.2;
		} else {
			$buffer = $commission_with_tax;
		}
	    return $buffer;
	}
	public function platform_commission_below_100000_amount( $with_tax = TRUE ) {
		$buffer = round( min( $this->current_amount( FALSE ), 100000 ) * $this->platform_commission( $with_tax ) / 100, 2 );
		return $buffer;
	}
	
	public static $key_platform_commission_above_100000 = 'campaign_platform_commission_above_100000';
	public function platform_commission_above_100000( $with_tax = TRUE ) {
		$commission_with_tax = $this->__get( ATCF_Campaign::$key_platform_commission_above_100000 );
		// Par défaut (si pas rempli), on reprend la commission normale
		if ( empty( $commission_with_tax ) ) {
			$buffer = $this->platform_commission( $with_tax );
			
		} else {
			if ( !empty( $commission_with_tax ) && !$with_tax ) {
				$buffer = $commission_with_tax / 1.2;
			} else {
				$buffer = $commission_with_tax;
			}
		}
	    return $buffer;
	}
	public function platform_commission_above_100000_amount( $with_tax = TRUE ) {
		$buffer = round( max( $this->current_amount( FALSE ) - 100000, 0 ) * $this->platform_commission_above_100000( $with_tax ) / 100, 2 );
		return $buffer;
	}
	
	public function platform_commission_amount( $with_tax = TRUE ) {
		$buffer = $this->platform_commission_below_100000_amount( $with_tax ) + $this->platform_commission_above_100000_amount( $with_tax );
		return $buffer;
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
	 * Le département complet du projet
	 */
	public function location() {
		return $this->__get( 'campaign_location' );
	}
	/**
	 * Le département en prenant les deux premiers caractères, et en supprimant le premier 0
	 */
	public function get_location_number() {
		$locations = atcf_get_locations();
		$this_location = $this->location();
		$location_complete = '';
		if ( !empty( $this_location ) ) {
			$location_complete = $locations[ $this_location ];
		}
		
		$buffer = substr( $location_complete, 0, 3 );
		
		$first_car = substr( $buffer, 0, 1 );
		if ( $first_car == '0' ) {
			$buffer = substr( $buffer, 1, 3 );
		}
		$buffer = str_replace( ' ', '', $buffer );
		
		return $buffer;
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
	
	private $organization;
	public function get_organization() {
		if ( !empty( $this->api_data ) ) {
			return $this->api_data->organization;
		}
		return FALSE;
	}
	
	public function link_organization( $id_api_organization, $link_type = '' ) {
		if ( empty( $link_type ) ) {
			$link_type = WDGWPREST_Entity_Project::$link_organization_type_manager;
		}
		WDGWPREST_Entity_Project::link_organization( $this->get_api_id(), $id_api_organization, $link_type );
		$cache_id = 'ATCF_Campaign::' .$this->ID. '::get_organization';
		do_action( 'wdg_delete_cache', array( $cache_id ) );
	}
	
	public function unlink_organization( $id_api_organization, $link_type = '' ) {
		if ( empty( $link_type ) ) {
			$link_type = WDGWPREST_Entity_Project::$link_organization_type_manager;
		}
		WDGWPREST_Entity_Project::unlink_organization( $this->get_api_id(), $id_api_organization, $link_type );
		$cache_id = 'ATCF_Campaign::' .$this->ID. '::get_organization';
		do_action( 'wdg_delete_cache', array( $cache_id ) );
	}

    /**
     * @deprecated Utiliser plutôt mail de l'auteur
     * @return string
     */
	public function contact_email() {
		return $this->__get( 'campaign_contact_email' );
	}

    /**
     * @deprecated Utiliser plutot Téléphone de l'auteur
     * @return string
     */
	public function contact_phone() {
		return $this->__get( 'campaign_contact_phone' );
	}

    public static $key_external_website = 'campaign_website';
    public function campaign_external_website(){
        return $this->__get(ATCF_Campaign::$key_external_website);
    }

    public static $key_facebook_name = 'campaign_facebook';
    public function facebook_name(){
        return $this->__get(ATCF_Campaign::$key_facebook_name);
    }

    public static $key_twitter_name = 'campaign_twitter';
    public function twitter_name(){
        return $this->__get( ATCF_Campaign::$key_twitter_name );
    }

	/**
	 * Campaign End Date
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign End Date
	 */
    public static $key_end_collecte_date = 'campaign_end_date';
	public function end_date( $format = 'Y-m-d H:i:s' ) {
		$end_datetime_str = $this->get_api_data( 'funding_end_datetime' );
		if ( empty( $end_datetime_str ) || $end_datetime_str == '0000-00-00 00:00:00' ) {
			$end_datetime_str = $this->__get( ATCF_Campaign::$key_end_collecte_date );
		}
		return mysql2date( $format, $end_datetime_str, false );
	}
	
	private static $retraction_days_number = 14;
	private $has_retraction_passed;
	public function has_retraction_passed() {
		if ( !isset( $this->has_retraction_passed ) ) {
			$this->has_retraction_passed = FALSE;
			$current_date = new DateTime();
			$end_date = new DateTime( $this->end_date() );
			if ( $current_date > $end_date ) {
				$interval = $current_date->diff( $end_date );
				$this->has_retraction_passed = ( $interval > self::$retraction_days_number );
			}
		}
		return $this->has_retraction_passed;
	}
        
        /**
	 * Campaign Begin Collecte Date
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Begin Collecte Date
	 */
    public static $key_begin_collecte_date = 'campaign_begin_collecte_date';
	public function begin_collecte_date($format = 'Y-m-d H:i:s') {
		return mysql2date( $format, $this->__get( ATCF_Campaign::$key_begin_collecte_date ), false );
	}

    /**
     * Set the date when vote finishes
     * @param type DateTime $newDate
     * @return bool|int
     */
    public static $key_end_vote_date = 'campaign_end_vote';
    public function set_end_vote_date($newDate){
		$this->set_api_data( 'vote_end_datetime', date_format($newDate, 'Y-m-d H:i:s') );
		$res = update_post_meta($this->ID, ATCF_Campaign::$key_end_vote_date, date_format($newDate, 'Y-m-d H:i:s'));
        return $res;
	}

	/**
	 * Set the date when collecte is started
	 * @param type DateTime $newDate
     * @return bool|int
     */
	public function set_begin_collecte_date($newDate){
		$this->set_api_data( 'funding_start_datetime', date_format($newDate, 'Y-m-d H:i:s') );
		$res = update_post_meta($this->ID, ATCF_Campaign::$key_begin_collecte_date, date_format($newDate, 'Y-m-d H:i:s'));
        return $res;
	}

	/**
	 * Set the date when collecte finishes
	 * @param type DateTime $newDate
     * @return bool|int
     */
	public function set_end_date($newDate){
		$this->set_api_data( 'funding_end_datetime', date_format($newDate, 'Y-m-d H:i:s') );
		$res = update_post_meta($this->ID, ATCF_Campaign::$key_end_collecte_date, date_format($newDate, 'Y-m-d H:i:s'));
        return $res;
    }
	
	public function get_begin_vote_str() {
		$vote_results = WDGCampaignVotes::get_results( $this->ID );
		$list_date = $vote_results[ 'list_date' ];
		// Si il y a eu un vote, on prend la date du premier vote
		if ( !empty( $list_date[0] ) ) {
			$beginvotedate = date_create( $list_date[0] );
			return date_format( $beginvotedate, 'Y-m-d H:i:s' );
			
		// Sinon on chope la date du jour
		} else {
			$beginvotedate = new DateTime( $this->get_end_vote_str() );
			$beginvotedate->modify('-1 day');
			return date_format( $beginvotedate, 'Y-m-d H:i:s' );
		}
	}
	
	public function get_end_vote_str() {
		$buffer = $this->get_api_data( 'vote_end_datetime' );
		if ( empty( $buffer ) || $buffer == '0000-00-00 00:00:00' ) {
			$buffer = $this->__get( ATCF_Campaign::$key_end_vote_date );
		}
		return $buffer;
	}

	public function end_vote() {
		return mysql2date( 'Y-m-d H:i:s', $this->get_end_vote_str(), false);
	}

	public function end_vote_date() {
		return mysql2date( 'Y-m-d H:i', $this->get_end_vote_str(), false);
	}
	public function end_vote_date_home() {
		setlocale(LC_TIME, array('fr_FR.UTF-8', 'fr_FR.UTF-8', 'fra'));
		return strftime("%d %B", strtotime(mysql2date( 'm/d', $this->get_end_vote_str(), false)));
	}
	public function end_vote_remaining() {
	    date_default_timezone_set('Europe/Paris');
	    $dateJour = strtotime(date("d-m-Y H:i"));
	    $fin = strtotime( $this->get_end_vote_str() );
	    $buffer = floor(($fin - $dateJour) / 60 / 60 / 24);
	    $buffer = max(0, $buffer + 1);
	    return $buffer;
	}
	
	public function get_followers() {
		global $wpdb;
		$table_jycrois = $wpdb->prefix . "jycrois";
		$list_user_follow = $wpdb->get_col( "SELECT DISTINCT user_id FROM ".$table_jycrois." WHERE subscribe_news = 1 AND campaign_id = ".$this->ID. " GROUP BY user_id" );
		return $list_user_follow;
	}
	
	public function get_voters() {
		global $wpdb;
		$table_vote = $wpdb->prefix . "ypcf_project_votes";
		$list_user_voters = $wpdb->get_results( "SELECT user_id, invest_sum, date, rate_project, advice FROM ".$table_vote." WHERE post_id = ".$this->ID );
		return $list_user_voters;
	}
	
	public function nb_voters() {
	    global $wpdb;
	    $table_name = $wpdb->prefix . "ypcf_project_votes";
	    $count_users = $wpdb->get_var( "SELECT count(id) FROM $table_name WHERE post_id = " . $this->ID );
	    return $count_users;
	}
        
	public function vote_invest_ready_min_required(){
		return round($this->minimum_goal(false)*(ATCF_Campaign::$vote_percent_invest_ready_min_required/100));
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
	private $backers;
	public function backers() {
		if ( empty( $this->backers ) ) {
			global $edd_logs;
	
			$this->backers = $edd_logs->get_connected_logs( array(
				'post_parent'    => $this->ID, 
				'log_type'       => /*atcf_has_preapproval_gateway()*/FALSE ? 'preapproval' : 'sale',
				'post_status'    => array( 'publish' ),
				'posts_per_page' => -1
			) );
		}

		return $this->backers;
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
	
	public function backers_id_list() {
		$backers = $this->backers();
		$buffer = array();

		if ($backers > 0) {
		    foreach ( $backers as $backer ) {
			    $payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			    $payment    = get_post( $payment_id );

			    if ( empty( $payment ) || $payment->post_status == 'pending' )
				    continue;

				array_push( $buffer, $payment->post_author );
		    }
		}
		
		return $buffer;
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
		$buffer = TRUE;
		
		if ( $this->time_remaining_str() == '-' ) {
			$update = false;
					
			$can_invest_after = $this->can_invest_until_contract_start_date();
			if ( $can_invest_after ) {
				$datetime_end = $this->get_end_date_when_can_invest_until_contract_start_date();
				$datetime_today = new DateTime();
				$buffer = ( $datetime_today < $datetime_end );
				
			} else {
				$buffer = FALSE;
			}
				
			if ( !$buffer && $this->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
				if ( $this->is_funded() ) {
					$this->set_status( ATCF_Campaign::$campaign_status_funded );
					$update = true;
				} else {
					$this->__set( ATCF_Campaign::$key_archive_message, "Ce projet est en cours de cl&ocirc;ture." );
					$this->set_status( ATCF_Campaign::$campaign_status_archive );
					$update = true;
				}
			
				if ( $update ) {
					$this->update_api();
					do_action('wdg_delete_cache', array(
						'home-projects',
						'projectlist-projects-current',
						'projectlist-projects-funded'
					));
					$file_cacher = WDG_File_Cacher::current();
					$file_cacher->build_campaign_page_cache( $this->ID );
				}
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne une chaine avec le temps restant (J-6, H-2, M-23)
	 */
	private $time_remaining_str;
	public function time_remaining_str() {
		if ( !isset( $this->time_remaining_str ) ) {
			date_default_timezone_set("Europe/London");

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

			$now = current_time( 'timestamp' );

			//Si on a dépassé la date de fin, on retourne "-"
			if ( $now > $expires ) {
				$buffer = '-';
			} else {
				$diff = $expires - $now;
				$nb_days = floor($diff / (60 * 60 * 24));
				if ($nb_days > 1) {
					$buffer = 'J-' . $nb_days;
				} else {
					$nb_hours = floor($diff / (60 * 60));
					if ($nb_hours > 1) {
						$buffer = 'H-' . $nb_hours;
					} else {
						$nb_minutes = floor($diff / 60);
						$buffer = 'M-' . $nb_minutes;
					}
				}
			}
			
			$this->time_remaining_str = $buffer;
			
		} else {
			$buffer = $this->time_remaining_str;
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
				    $buffer = __('&Eacute;valuation termin&eacute;e', 'yproject');
			    } else {
				    $diff = $expires - $now;
				    $nb_days = floor($diff / (60 * 60 * 24));
				    $plural = ($nb_days > 1) ? 's' : '';
				    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_days+1) . '</b> '. __('jour', 'yproject').$plural.__(' pour &eacute;valuer !', 'yproject');
				    if ($nb_days <= 0) {
					    $nb_hours = floor($diff / (60 * 60));
					    $plural = ($nb_hours > 1) ? 's' : '';
					    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_hours+1) . '</b> '. __('heure', 'yproject').$plural.__(' pour &eacute;valuer !', 'yproject');
					    if ($nb_hours <= 0) {
						    $nb_minutes = floor($diff / 60);
						    $plural = ($nb_minutes > 1) ? 's' : '';
						    $buffer = __('Plus que', 'yproject').' <b>' . ($nb_minutes+1) . '</b> '. __('minute', 'yproject').$plural.__(' pour &eacute;valuer !', 'yproject');
					    }
				    }
			    }
			    break;
			case ATCF_Campaign::$campaign_status_collecte:
			    $expires = strtotime( $this->end_date() );
			    //Si on a dépassé la date de fin, on retourne "-"
			    if ( $now >= $expires ) {
				    $buffer = __("Investissement termin&eacute;", 'yproject');
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
	public function time_remaining_str_until_contract_start_date() {
		$datetime_end = $this->get_end_date_when_can_invest_until_contract_start_date();
		$expires = $datetime_end->getTimestamp();
		$now = current_time( 'timestamp' );

		//Si on a dépassé la date de fin, on retourne "-"
		if ( $now > $expires ) {
			$buffer = '-';
		} else {
			$diff = $expires - $now;
			$nb_days = floor( $diff / ( 60 * 60 * 24 ) );
			if ( $nb_days > 1 ) {
				$buffer = 'J-' . $nb_days;
			} else {
				$nb_hours = floor( $diff / ( 60 * 60 ) );
				if ( $nb_hours > 1 ) {
					$buffer = 'H-' . $nb_hours;
				} else {
					$nb_minutes = floor( $diff / 60 );
					$buffer = 'M-' . $nb_minutes;
				}
			}
		}
		    
		return $buffer;
	}

	public static $invest_amount_min_wire = 500;
	public static $invest_time_min_wire = 7;
	public static $campaign_max_remaining_amount = 3000;
	public function can_use_wire_remaining_time() {
		// Si on a annulé les contraintes des virements ou si il reste assez de jours ou si la campagne a déjà atteint 80%
		return ( $this->has_overridden_wire_constraints() || $this->days_remaining() > ATCF_Campaign::$invest_time_min_wire || $this->percent_minimum_completed( FALSE ) > 80 );
	}
	public function can_use_wire_amount($amount_part) {
		return ($this->part_value() * $amount_part >= ATCF_Campaign::$invest_amount_min_wire);
	}
	public function can_use_wire_remaining_amount() {
		$goal    = $this->goal(false);
		$current = $this->current_amount(false);
		$remaining = $goal - $current;
		return ($remaining > ATCF_Campaign::$campaign_max_remaining_amount);
	}
	public function can_use_wire($amount_part) {
		return ($this->can_use_wire_remaining_time() && $this->can_use_wire_amount($amount_part) && $this->can_use_wire_remaining_amount());
	}
	public static $key_has_overridden_wire_constraints = 'has_overridden_wire_constraints';
	public function has_overridden_wire_constraints() {
		$buffer = $this->__get( self::$key_has_overridden_wire_constraints );
		return ( $buffer == '1' );
	}
	
	
	public function can_use_check( $amount_part ) {
		return ( $this->can_use_check_option() && $this->can_use_check_amount( $amount_part ) && !$this->is_positive_savings() );
	}
	
	public static $key_can_use_check = 'can_use_check';
	public function can_use_check_option() {
		$buffer = $this->__get( self::$key_can_use_check );
		return ( $buffer !== '0' );
	}
	
	public static $invest_amount_min_check = 500;
	public function can_use_check_amount( $amount_part ) {
		return ( $this->part_value() * $amount_part >= ATCF_Campaign::$invest_amount_min_check );
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
		if ( $percent < 90 ) {
			$percent = round( $percent );
		} else {
			$percent = floor( $percent );
		}

		if ( $formatted )
			return $percent . '%';

		return $percent;
	}

	private $percent_minimum_completed;
	public function percent_minimum_completed( $formatted = true ) {
		if ( !isset( $this->percent_minimum_completed ) || empty( $this->percent_minimum_completed ) ) {
			$goal    = $this->minimum_goal(false);
			$current = $this->current_amount(false);
	
			if ( 0 == $goal )
				return $formatted ? 0 . '%' : 0;
	
			$this->percent_minimum_completed = ( $current / $goal ) * 100;
			if ( $this->percent_minimum_completed < 90 ) {
				$this->percent_minimum_completed = round( $this->percent_minimum_completed );
			} else {
				$this->percent_minimum_completed = floor( $this->percent_minimum_completed );
			}
	
		}
	
		if ( $formatted ) {
			return $this->percent_minimum_completed . '%';
		} else {
			return $this->percent_minimum_completed;
		}
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
	private $current_amount;
	public function current_amount( $formatted = true ) {
		if ( !isset( $this->current_amount ) ) {
			$total   = 0;
			$backers = $this->backers();
	
			if ($backers > 0) {
				foreach ( $backers as $backer ) {
					$payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
					$payment = get_post( $payment_id );
	
					if ( empty( $payment ) || $payment->post_status == 'pending' )
						continue;
	
					$total = $total + edd_get_payment_amount( $payment_id );
				}
			}
		
			$amount_check = $this->current_amount_check_meta(FALSE);
			$total += $amount_check;
			$this->current_amount = $total;
		}
		
		if ( $formatted ) {
			$current_amount = $this->current_amount;
			$currency = edd_get_currency();
			if ($currency == "EUR") {
				if ( strpos( $current_amount, '.00' ) !== false ) {
					$current_amount = substr ( $current_amount, 0, -3 );
				}
				$current_amount = number_format( $current_amount, 0, ".", " " );
				return $current_amount . ' &euro;';
			} else {
				return edd_currency_filter( edd_format_amount( $current_amount ) );
			}
		}

		return $this->current_amount;
	}
	
	public function current_amount_with_check() {
		$total   = 0;
		$backers = $this->backers();

		if ($backers > 0) {
		    foreach ( $backers as $backer ) {
			    $payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			    $payment    = get_post( $payment_id );
				$payment_key = edd_get_payment_key( $payment_id );

			    if ( !empty( $payment ) && $payment_key == 'check' && $payment->post_status != 'pending' ) {
					$total += edd_get_payment_amount( $payment_id );
				}

		    }
		}
		
		return $total;
	}
	
	public function current_amount_check_meta($formatted = true){
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
	
	public static $key_campaign_is_hidden = '_campaign_is_hidden';
	public function is_hidden() {
		$buffer = false;
		$meta_hidden = $this->__get( ATCF_Campaign::$key_campaign_is_hidden );
		if ( !empty( $meta_hidden ) ) {
			$buffer = ( $meta_hidden == '1' );
		}
		return $buffer;
	}
	
	public static $key_skip_vote = '_campaign_skip_vote';
	public function skip_vote() {
		$buffer = false;
		$meta_skip_vote = $this->__get( ATCF_Campaign::$key_skip_vote );
		if ( !empty( $meta_skip_vote ) ) {
			$buffer = ( $meta_skip_vote == '1' );
		}
		return $buffer;
	}
	
	public static $key_skip_in_stats = '_campaign_skip_in_stats';
	public function skip_in_stats() {
		$buffer = false;
		$meta_skip_in_stats = $this->__get( ATCF_Campaign::$key_skip_in_stats );
		if ( !empty( $meta_skip_in_stats ) ) {
			$buffer = ( $meta_skip_in_stats == '1' );
		}
		return $buffer;
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
	
	public function has_investment_contracts_in_api() {
		return ( !empty( $this->api_data->investment_contracts ) );
	}


	/**
	 * Détermine si les investissements ont déjà été transférés sur l'API
	 * @return boolean
	 */
	public function has_investments_in_api() {
		return ( !empty( $this->api_data->investments ) );
	}
	
	/**
	 * Return payments data. 
	 * This function is very slow, it is advisable to use it as few as possible
	 * @return array
	 */
	private $payments_data;
	public function payments_data($skip_apis = FALSE, $order_by_older = FALSE) {
		if ( !isset( $this->payments_data ) ) {
		
			$this->payments_data = array();
		
			if ( $this->has_investments_in_api() ) {
				foreach ( $this->api_data->investments as $investment_item ) {
					
					if ( $investment_item->status != 'failed' ) {
						// Récupération simple des paiements dans l'API
						// On dégage 'products' et 'signsquid_status_text' pas très utile
						// On simplifie 'mangopay_contribution' et 'lemonway_contribution' pour avoir les infos au cas où, mais pas les chercher de suite, car normalement pas besoin
						$this->payments_data[] = array(
							'item'			=> $investment_item,
							'ID'			=> $investment_item->wpref,
							'user'			=> $investment_item->user_wpref,
							'email'			=> $investment_item->email,
							'amount'		=> $investment_item->amount,
							'date'			=> $investment_item->invest_datetime,
							'user_api_id'	=> $investment_item->user_id,
							'status'		=> $investment_item->status,
							'mangopay_contribution'	=> ( $investment_item->payment_provider == ATCF_Campaign::$payment_provider_mangopay ) ? $investment_item->payment_key : FALSE,
							'lemonway_contribution' => ( $investment_item->payment_provider == ATCF_Campaign::$payment_provider_lemonway ) ? $investment_item->payment_key : FALSE,
							'signsquid_status'		=> $investment_item->signature_status
						);
					}
					
				}
				
			} else {
				$payments = edd_get_payments( array(
					'number'	 => -1,
					'download'   => $this->ID
				) );

				if ( $payments ) {
					foreach ( $payments as $payment ) {
						$user_info = edd_get_payment_meta_user_info( $payment->ID );
						$cart_details = edd_get_payment_meta_cart_details( $payment->ID );

						$user_id = (isset( $user_info['id'] ) && $user_info['id'] != -1) ? $user_info['id'] : $user_info['email'];

						$WDGInvestmentSignature = new WDGInvestmentSignature( $payment->ID );
						$signature_status = $WDGInvestmentSignature->get_status();

						$lemonway_contribution = FALSE;
						if ($this->get_payment_provider() == ATCF_Campaign::$payment_provider_lemonway) {
							$lemonway_id = edd_get_payment_key($payment->ID);

							if ( $lemonway_id == 'check' ) {

							} else if ( strpos( $lemonway_id, 'wire_' ) !== FALSE ) {


							} else if ( strpos( $lemonway_id, '_wallet_' ) !== FALSE ) {
								$lemonway_id_exploded = explode( '_wallet_', $lemonway_id );
								$lemonway_contribution = ($skip_apis == FALSE) ? LemonwayLib::get_transaction_by_id( $lemonway_id_exploded[ 0 ] ) : '';

							} else if ( strpos( $lemonway_id, 'wallet_' ) !== FALSE ) {

							} else {
								$lemonway_contribution = ($skip_apis == FALSE) ? LemonwayLib::get_transaction_by_id($lemonway_id) : '';
							}
						}

						$payment_status = ypcf_get_updated_payment_status( $payment->ID, FALSE, $lemonway_contribution );

						if ($payment_status != 'failed') {
							$this->payments_data[] = array(
								'ID'			=> $payment->ID,
								'email'			=> edd_get_payment_user_email( $payment->ID ),
								'products'		=> $cart_details,
								'amount'		=> edd_get_payment_amount( $payment->ID ),
								'date'			=> $payment->post_date,
								'user'			=> $user_id,
								'status'		=> $payment_status,
								'mangopay_contribution' => FALSE,
								'lemonway_contribution' => $lemonway_contribution,
								'payment_key' => $lemonway_id,
								'signsquid_status'	=> $signature_status
							);
						}
					}
				}
				
			}
			
			if( $order_by_older ){
				// on trie les investissements par date, le plus vieux en premier
				array_multisort (array_column($this->payments_data, 'date'), SORT_ASC, $this->payments_data);
			}
		}
		
		return $this->payments_data;
	}
	
	public function pending_preinvestments() {
		$buffer = array();

		$payments = edd_get_payments( array(
		    'number'	=> -1,
		    'download'	=> $this->ID,
			'status'	=> 'pending'
		) );
		
		foreach ( $payments as $payment ) {
			$payment_investment = new WDGInvestment( $payment->ID );
			if ( $payment_investment->get_contract_status() == WDGInvestment::$contract_status_preinvestment_validated ) {
				array_push( $buffer, $payment_investment );
			}
		}
		
		return $buffer;
	}
	
	public function investment_drafts() {
		return $this->api_data->investment_drafts;
	}
	
	/**
	 * Ajoute un investissement dans la liste des investissements
	 * @param string $type
	 * @param string $email
	 * @param string $value
	 * @param string $status
	 */
	public function add_investment(
			$type, $email, $value, $status = 'publish',
			$new_username = '', $new_password = '', 
			$new_gender = '', $new_firstname = '', $new_lastname = '', 
			$birthday_day = '', $birthday_month = '', $birthday_year = '', $birthplace = '', $nationality = '', 
			$address = '', $postal_code = '', $city = '', $country = '', $iban = '', 
			$orga_email = '', $orga_name = '') {
		$user_id = FALSE;
		
		if ( empty( $email ) || empty( $value ) ) {
			return;
		}
		
		$use_lastname = '';
		$birthplace_district = '';
		$birthplace_department = '';
		$birthplace_country = '';
		$address_number = '';
		$address_number_complement = '';
		$tax_country = '';
	    
		//Vérification si un utilisateur existe avec l'email en paramètre
		$user_payment = get_user_by('email', $email);
		if ($user_payment) {
			if (!WDGOrganization::is_user_organization($user_payment->ID)) {
				$user_id = $user_payment->ID;
				$wdg_user = new WDGUser( $user_id );
				$new_gender = $wdg_user->get_gender();
				$new_firstname = $wdg_user->get_firstname();
				$new_lastname = $wdg_user->get_lastname();
				$wdg_user->save_data(
					$email, $new_gender, $new_firstname, $new_lastname, $use_lastname,
					$birthday_day, $birthday_month, $birthday_year,
					$birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality,
					$address_number, $address_number_complement, $address, $postal_code, $city, $country, $tax_country, ''
				);
			}
				
		//Sinon, on vérifie si il y a un login et pwd transmis, pour créer le nouvel utilisateur
		} else {
			if (!empty($new_username) && !empty($new_password)) {
				$user_id = wp_create_user($new_username, $new_password, $email);
				$wdg_user = new WDGUser( $user_id );
				$use_lastname = '';
				$birthplace_department = '';
				$wdg_user->save_data(
					$email, $new_gender, $new_firstname, $new_lastname,  $use_lastname, 
					$birthday_day, $birthday_month, $birthday_year, 
					$birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality,
					$address_number, $address_number_complement, $address, $postal_code, $city, $country, $tax_country, ''
				);
			}
		}
		$saved_user_id = $user_id;
		
		if (!is_wp_error($saved_user_id) && !empty($saved_user_id) && $saved_user_id != FALSE) {
			//Gestion organisation
			if ( !empty($orga_email) ) {
				//Vérification si organisation existante
				$orga_payment = get_user_by('email', $orga_email);
				if ($orga_payment) {
					if ( WDGOrganization::is_user_organization( $orga_payment->ID ) ) {
						$saved_user_id = $orga_payment->ID;
						
					} else {
						$saved_user_id = FALSE;
					}

				//Sinon, on la crée juste avec un e-mail et un nom
				} else {
                    $wp_orga = WDGOrganization::createSimpleOrganization($user_id, $orga_name, $orga_email);
					$saved_user_id = $wp_orga->get_wpref();
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
					'name'			=> get_the_title( $this->ID ),
					'id'			=> $this->ID,
					'item_number'	=> array(
						'id'			=> $this->ID,
						'quantity'		=> $value,
						'options'		=> array()
					),
					'item_price'	=> 1,
					'subtotal'		=> $value,
					'price'			=> $value,
					'quantity'		=> $value
				)
			);

			$payment_data = array( 
				'subtotal'		=> $value, 
				'price'			=> $value, 
				'date'			=> date('Y-m-d H:i:s'), 
				'user_email'	=> $email,
				'purchase_key'	=> $type,
				'currency'		=> edd_get_currency(),
				'downloads'		=> array($this->ID),
				'user_info'		=> $user_info,
				'cart_details'	=> $cart_details,
				'status'		=> 'pending' // On initialise à pending, sinon la sauvegarde se fait 2 fois dans les logs (edd_record_sale_in_log)
			);
			$payment_id = edd_insert_payment( $payment_data );
			$_SESSION[ 'investment_id' ] = $payment_id;
			update_post_meta( $payment_id, '_edd_payment_total', $value );
			edd_record_sale_in_log($this->ID, $payment_id);
			delete_post_meta( $payment_id, '_edd_payment_customer_id' );
			update_post_meta( $payment_id, '_edd_payment_user_id', $saved_user_id );

			$WDGInvestment = new WDGInvestment( $payment_id );
			
			// Mise à jour du statut de paiement si nécessaire
			if ( $this->campaign_status() == ATCF_Campaign::$campaign_status_vote ) {
				$WDGInvestment->set_contract_status( WDGInvestment::$contract_status_preinvestment_validated );
				$postdata = array(
					'ID'			=> $payment_id,
					'post_status'	=> 'pending'
				);
				wp_update_post( $postdata );

			} elseif ( $status != 'pending' ) {
				$postdata = array(
					'ID'			=> $payment_id,
					'post_status'	=> $status
				);
				wp_update_post( $postdata );
			}

			$WDGInvestment->save_to_api();

		} else {
			$payment_id = FALSE;
		}
		
		return $payment_id;
	}
	
	/**
	 * Rembourse les investisseurs
	 */
	public function refund() {
		$payments_data = $this->payments_data();
		foreach ( $payments_data as $payment_data ) {
			if ( $payment_data[ 'status' ] == 'publish' ) {
				$WDGInvestment = new WDGInvestment( $payment_data['ID'] );
				$WDGInvestment->refund();
			}
		}
	}
	
	/**
	 * Retourne la liste des paiement, augmentée par les informations utiles pour un ROI particulier
	 * @param WDGROIDeclaration $declaration
	 */
	public function roi_payments_data( $declaration, $transfer_remaining_amount = false, $is_refund = false ) {
		$buffer = array();
		
		// Récupération de la liste des investissements concernés
		$investment_contracts = WDGInvestmentContract::get_list( $this->ID );
		if ( !empty( $investment_contracts ) ) {
			
			// Si c'est un remboursement, on fait juste une soustraction
			if ( $is_refund ) {
				foreach ( $investment_contracts as $investment_contract ) {
					if ( $investment_contract->status == WDGInvestmentContract::$status_active ) {
						$investor_id = 0;
						if ( $investment_contract->investor_type == 'user' ) {
							$WDGUser = WDGUser::get_by_api_id( $investment_contract->investor_id );
							$investor_id = $WDGUser->get_wpref();
						} else {
							$WDGOrganization = WDGOrganization::get_by_api_id( $investment_contract->investor_id );
							$investor_id = $WDGOrganization->get_wpref();
						}
						$investment_item = array(
							'contract_id'	=> $investment_contract->id,
							'ID'			=> $investment_contract->subscription_id,
							'amount'		=> $investment_contract->subscription_amount,
							'user'			=> $investor_id
						);

						// Calcul du montant à récupérer
						$investor_amount = $investment_contract->subscription_amount - $investment_contract->amount_received;
						// Calcul de la commission sur le roi de l'utilisateur
						$fees_total = $investor_amount * $this->get_costs_to_investors() / 100; //10.50 * 1.8 / 100 = 0.189
						// Et arrondi
						$fees = round($fees_total * 100) / 100; //0.189 * 100 = 18.9 = 19 = 0.19
						$investment_item['roi_fees'] = $fees;
						// Reste à verser pour l'investisseur
						$investor_proportion_amount_remaining = $investor_amount - $fees;
						$investment_item['roi_amount'] = $investor_proportion_amount_remaining;

						array_push( $buffer, $investment_item );
					}
				}
				
				
			} else {
				// Calcul préalable spécifique à l'ajustement pour pouvoir le prendre en compte en tant que CA
				$adjustement_turned_into_turnover = 0;
				if ( $declaration->get_adjustment_value() != 0 ) {
					// Pour transformer l'ajustement en CA, il faut avoir le nombre de contrats actifs pris en compte et en additionner le pourcentage de CA
					$total_turnover_percent = 0;
					foreach ( $investment_contracts as $investment_contract ) {
						if ( $investment_contract->status == WDGInvestmentContract::$status_active ) {
							$total_turnover_percent += $investment_contract->turnover_percent;
						}
					}
					$adjustement_turned_into_turnover = $declaration->get_adjustment_value() * 100 / $total_turnover_percent;
				}

				$turnover_to_apply = max( 0, $adjustement_turned_into_turnover + $declaration->get_turnover_total() );

				// Détermination des montants par contrat
				foreach ( $investment_contracts as $investment_contract ) {
					if ( $investment_contract->status == WDGInvestmentContract::$status_active ) {
						$investor_id = 0;
						if ( $investment_contract->investor_type == 'user' ) {
							$WDGUser = WDGUser::get_by_api_id( $investment_contract->investor_id );
							$investor_id = $WDGUser->get_wpref();
						} else {
							$WDGOrganization = WDGOrganization::get_by_api_id( $investment_contract->investor_id );
							$investor_id = $WDGOrganization->get_wpref();
						}
						$investment_item = array(
							'contract_id'	=> $investment_contract->id,
							'ID'			=> $investment_contract->subscription_id,
							'amount'		=> $investment_contract->subscription_amount,
							'user'			=> $investor_id
						);

						// Calcul du montant à récupérer en roi à partir du pourcentage du CA
						$investor_proportion_amount = floor( $turnover_to_apply * $investment_contract->turnover_percent ) / 100; //10.50
						// Calcul de la commission sur le roi de l'utilisateur
						$fees_total = $investor_proportion_amount * $this->get_costs_to_investors() / 100; //10.50 * 1.8 / 100 = 0.189
						// Et arrondi
						$fees = round($fees_total * 100) / 100; //0.189 * 100 = 18.9 = 19 = 0.19
						$investment_item['roi_fees'] = $fees;
						// Reste à verser pour l'investisseur
						$investor_proportion_amount_remaining = $investor_proportion_amount - $fees;
						$investment_item['roi_amount'] = $investor_proportion_amount_remaining;

						array_push( $buffer, $investment_item );
					}
				}
				
			}
			
		} else {
			//Calculs des montants à reverser
			$total_amount = $this->current_amount(FALSE);
			$roi_amount = $declaration->get_amount_with_adjustment();
			if ( $transfer_remaining_amount ) {
				$roi_amount += $declaration->get_previous_remaining_amount();
			}
		
			$investments_list = $this->payments_data(TRUE);
		
			// Parcours
			foreach ( $investments_list as $investment_item ) {
				if ( $investment_item[ 'status' ] != 'publish' ) {
					continue;
				}

				// Calcul de la part de l'investisseur dans le total
				$investor_proportion = $investment_item['amount'] / $total_amount; //0.105
				// Calcul du montant à récupérer en roi
				$investor_proportion_amount = floor( $roi_amount * $investor_proportion * 100 ) / 100; //10.50
				// Calcul de la commission sur le roi de l'utilisateur
				$fees_total = $investor_proportion_amount * $this->get_costs_to_investors() / 100; //10.50 * 1.8 / 100 = 0.189
				// Et arrondi
				$fees = round($fees_total * 100) / 100; //0.189 * 100 = 18.9 = 19 = 0.19
				$investment_item['roi_fees'] = $fees;
				// Reste à verser pour l'investisseur
				$investor_proportion_amount_remaining = $investor_proportion_amount - $fees;
				$investment_item['roi_amount'] = $investor_proportion_amount_remaining;
				array_push($buffer, $investment_item);
			}
		}
	    
		return $buffer;
	}
	
	public function manage_jycrois($user_id = FALSE) {
		global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";

		if ( empty( $user_id ) ) {
			$user_item = wp_get_current_user();
			$user_id = $user_item->ID;
		}

		//J'y crois
		if(isset($_POST['jy_crois']) && $_POST['jy_crois'] == 1){
			$wpdb->insert( 
				$table_jcrois,
				array(
					'user_id'	=> $user_id,
					'campaign_id'   => $this->ID
				)
			);

		//J'y crois pas
		} else if (isset($_POST['jy_crois']) && $_POST['jy_crois'] == 0) { 
			$wpdb->delete( 
				$table_jcrois,
				array(
					'user_id'      => $user_id,
					'campaign_id'  => $this->ID
				)
			);
		}
		
		return $this->get_jycrois_nb();
	}
	
	private $nb_followers;
	public function get_jycrois_nb() {
		if ( !isset( $this->nb_followers ) ) {
			global $wpdb;
			$table_jcrois = $wpdb->prefix . "jycrois";
			$this->nb_followers = $wpdb->get_var( 'SELECT count(campaign_id) FROM '.$table_jcrois.' WHERE campaign_id = '.$this->ID );
		}
		return $this->nb_followers;
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
	
	private $home_picture;
	public function get_home_picture_src( $force = true, $size = 'full' ) {
		if ( empty( $this->home_picture ) ) {
			$this->home_picture = $this->get_picture_src( 'image_home', $force, $size );
		}
		return $this->home_picture;
	}
	
	public function get_picture_src( $type, $force, $size = 'full' ) {
		$buffer = '';
		$attachments = get_posts( array(
			'post_type' => 'attachment',
			'post_parent' => $this->ID,
			'post_mime_type' => 'image'
		));
		
		if ( count( $attachments ) > 0 ) {
			$image_obj = '';
			
			//Si on en trouve bien une avec le titre "image_home" on prend celle-là
			foreach ( $attachments as $attachment ) {
				if ( $attachment->post_title == $type ) {
					$image_obj = wp_get_attachment_image_src( $attachment->ID, $size );
				}
			}
			//Sinon on prend la première image rattachée à l'article
			if ($force && $image_obj == '') {
				$image_obj = wp_get_attachment_image_src( $attachments[0]->ID, $size );
			}
			if ($image_obj != '') {
				$buffer = $image_obj[0];
			}
		}
		
		return $buffer;
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
		$project_api_id = $this->get_api_id();
		$team_member_list = WDGWPREST_Entity_Project::get_users_by_role( $project_api_id, WDGWPREST_Entity_Project::$link_user_type_team );
		foreach ($team_member_list as $team_member) {
			if ($current_user_id == $team_member->wpref) return TRUE;
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

	public function set_status($newstatus){
		if ( array_key_exists( $newstatus, ATCF_Campaign::get_campaign_status_list() ) ) {
			$this->status = $newstatus;
			return update_post_meta( $this->ID, ATCF_Campaign::$key_campaign_status, $newstatus );
		} else {
		    return false;
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
	
	
	
	
/*******************************************************************************
 * RECUPERATION DE LISTE DE PROJETS
 ******************************************************************************/
	/**
	 * Retourne une liste de tous les projets
	 */
	public static function get_list_all () {
		$query_options = array(
			'numberposts' => -1,
			'post_type' => 'download',
			'post_status' => 'publish',
			'meta_key' => 'campaign_vote',
			'meta_key' => 'campaign_funding_type',
			'meta_key' => 'campaign_end_date'
		);
		return get_posts( $query_options );

	}

	public static function get_list_most_recent( $nb = 1, $client = '' ) {
		$buffer = array();
		
		$projectlist_funding = ATCF_Campaign::get_list_funding( $nb, $client );
		$count_projectlist = count( $projectlist_funding );
		foreach ( $projectlist_funding as $project ) { array_push( $buffer, $project->ID ); }
		
		if ( $count_projectlist < $nb ) {
			$projectlist_vote = ATCF_Campaign::get_list_vote( $nb - $count_projectlist, $client );
			$count_projectlist += count( $projectlist_vote );
			foreach ( $projectlist_vote as $project ) { array_push( $buffer, $project->ID ); }
		}
		
		if ( $count_projectlist < $nb ) {
			$projectlist_funded = ATCF_Campaign::get_list_funded( $nb - $count_projectlist, $client );
			$count_projectlist += count( $projectlist_funded );
			foreach ( $projectlist_funded as $project ) { array_push( $buffer, $project->ID ); }
		}
		
		return $buffer;
	}
	
	public static function get_list_preview( $nb = 0, $client = '' ) { return ATCF_Campaign::get_list_current( $nb, ATCF_Campaign::$campaign_status_preview, 'asc', $client ); }
	public static function get_list_vote( $nb = 0, $client = '', $random = false ) { return ATCF_Campaign::get_list_current( $nb, ATCF_Campaign::$campaign_status_vote, ( $random ? 'rand' : 'desc'), $client ); }
	public static function get_list_funding( $nb = 0, $client = '', $random = FALSE, $is_time_remaining = TRUE ) { return ATCF_Campaign::get_list_current( $nb, ATCF_Campaign::$campaign_status_collecte, ( $random ? 'rand' : 'asc'), $client, $is_time_remaining ); }
	
	public static function get_list_positive_savings( $nb = 0, $random = TRUE ) {
		$term_positive_savings_by_slug = get_term_by( 'slug', 'epargne-positive', 'download_category' );
		$id_cat_positive_savings = $term_positive_savings_by_slug->term_id;
		$query_options = array(
			'numberposts'	=> $nb,
			'post_type'		=> 'download',
			'post_status'	=> 'publish',
			'meta_query'	=> array (
				array ( 'key' => 'campaign_vote', 'value' => 'collecte' ),
				array ( 'key' => 'campaign_end_date', 'compare' => '>', 'value' => date('Y-m-d H:i:s') )
			),
			'tax_query'		=> array(
				array(
					'taxonomy'	=> 'download_category',
					'terms'		=> $id_cat_positive_savings
				)
				
			)
		);
		
		if ( $random ) {
			$query_options[ 'orderby' ] = 'rand';
			
		} else {
			$query_options[ 'orderby' ] = 'post_date';
			$query_options[ 'order' ] = 'asc';
			
		}
		
		return get_posts( $query_options );
	}
	
	public static function get_list_funded( $nb = 0, $client = '', $include_current = false, $skip_hidden = true ) {
		$buffer = ATCF_Campaign::get_list_finished( $nb, array( ATCF_Campaign::$campaign_status_funded, ATCF_Campaign::$campaign_status_closed ), $client, $skip_hidden );
		if ( $include_current ) {
			$list_current = ATCF_Campaign::get_list_current( $nb, ATCF_Campaign::$campaign_status_collecte, 'asc', $client );
			foreach ( $list_current as $campaign_post ) {
				$campaign = atcf_get_campaign( $campaign_post->ID );
				if ( $campaign->is_funded() ) {
					array_push( $buffer, $campaign_post );
				}
			}
			$list_current_notime = ATCF_Campaign::get_list_current( $nb, ATCF_Campaign::$campaign_status_collecte, 'asc', $client, FALSE );
			foreach ( $list_current_notime as $campaign_post ) {
				$campaign = atcf_get_campaign( $campaign_post->ID );
				if ( $campaign->is_funded() ) {
					array_push( $buffer, $campaign_post );
				}
			}
		}
		return $buffer;
	}
	public static function get_list_archive($nb = 0, $client = '') { return ATCF_Campaign::get_list_finished( $nb, ATCF_Campaign::$campaign_status_archive, $client ); }
	
	
	public static function get_list_current( $nb, $type, $order, $client, $is_time_remaining = true ) {
		$compare_end_date = ( $is_time_remaining ) ? '>' : '<=';
		$query_options = array(
			'numberposts' => $nb,
			'post_type' => 'download',
			'post_status' => 'publish',
			'meta_query' => array (
				array ( 'key' => 'campaign_vote', 'value' => $type ),
				array ( 'key' => 'campaign_end_date', 'compare' => $compare_end_date, 'value' => date('Y-m-d H:i:s') ),
				array ( 'key' => ATCF_Campaign::$key_campaign_is_hidden, 'compare' => 'NOT EXISTS' )
			)
		);
		
		if ( $order == 'rand' ) {
			$query_options[ 'orderby' ] = 'rand';
			
		} else {
			$query_options[ 'orderby' ] = 'post_date';
			$query_options[ 'order' ] = $order;
			
		}
		
		if (!empty($client)) {
			$query_options['tax_query'] = array( array( 
				'taxonomy' => 'download_tag',
				'field' => 'slug', 
				'terms' => array($client) 
			) );
		}
		return get_posts( $query_options );
	}
	public static function get_list_finished( $nb, $type, $client, $skip_hidden = TRUE ) {
		$query_options = array(
			'numberposts' => $nb,
			'post_type' => 'download',
			'post_status' => 'publish',
			'meta_query' => array (
				'relation' => 'AND',
				array ( 'key' => 'campaign_vote', 'value' => $type ),
				array ( 'key' => 'campaign_funding_type', 'value' => 'fundingproject' )
			),
			'meta_key' => 'campaign_end_date',
			'orderby' => 'meta_value',
			'order' => 'desc'
		);
		
		// Si on ne veut pas les campagnes masquées, on ajoute une requete sur les META
		if ( $skip_hidden ) {
			array_push(
				$query_options[ 'meta_query' ],
				array ( 'key' => ATCF_Campaign::$key_campaign_is_hidden, 'compare' => 'NOT EXISTS' )
			);	
		} else {
			array_push(
				$query_options[ 'meta_query' ],
				array ( 'key' => ATCF_Campaign::$key_skip_in_stats, 'compare' => 'NOT EXISTS' )
			);	
		}
		
		if (!empty($client)) {
			$query_options['tax_query'] = array( array( 
				'taxonomy' => 'download_tag',
				'field' => 'slug', 
				'terms' => array($client) 
			) );
		}
		return get_posts( $query_options );
	}
	
	public static function get_list_current_hidden( $type, $is_time_remaining = true ) {
		$compare_end_date = ( $is_time_remaining ) ? '>' : '<=';
		$query_options = array(
			'numberposts' => $nb,
			'post_type' => 'download',
			'post_status' => 'publish',
			'meta_query' => array (
				array ( 'key' => 'campaign_vote', 'value' => $type ),
				array ( 'key' => 'campaign_end_date', 'compare' => $compare_end_date, 'value' => date('Y-m-d H:i:s') ),
				array ( 'key' => ATCF_Campaign::$key_campaign_is_hidden, 'compare' => 'EXISTS' )
			)
		);
		return get_posts( $query_options );
	}
	
	
	
	public static function list_projects_preview($nb = 0, $client = '') { return ATCF_Campaign::list_projects_current($nb, ATCF_Campaign::$campaign_status_preview, 'asc', $client); }
	public static function list_projects_vote($nb = 0, $client = '') { return ATCF_Campaign::list_projects_current($nb, ATCF_Campaign::$campaign_status_vote, 'desc', $client); }
	public static function list_projects_funding($nb = 0, $client = '') { return ATCF_Campaign::list_projects_current($nb, ATCF_Campaign::$campaign_status_collecte, 'asc', $client); }
	public static function list_projects_funded($nb = 0, $client = '') { return ATCF_Campaign::list_projects_finished($nb, array( ATCF_Campaign::$campaign_status_funded, ATCF_Campaign::$campaign_status_closed ), $client); }
	public static function list_projects_archive($nb = 0, $client = '') { return ATCF_Campaign::list_projects_finished($nb, ATCF_Campaign::$campaign_status_archive, $client); }
	
	public static function list_projects_current($nb, $type, $order, $client) {
		$query_options = array(
			'posts_per_page' => $nb,
			'post_type' => 'download',
			'post_status' => 'publish',
			'meta_query' => array (

				array (
					'key' => 'campaign_vote',
					'value' => $type
					),
				array (
					'key' => 'campaign_end_date',
					'compare' => '>',
					'value' => date('Y-m-d H:i:s')
				)
			),
			'orderby' => 'post_date',
			'order' => $order
		);
		if (!empty($client)) {
			$query_options['tax_query'] = array( array( 
				'taxonomy' => 'download_tag',
				'field' => 'slug', 
				'terms' => array($client) 
			) );
		}
		return query_posts( $query_options );
	}
	
	public static function list_projects_finished($nb, $type, $client) {
		$query_options = array(
			'posts_per_page' => $nb,
			'post_type' => 'download',
			'post_status' => 'publish',
			'meta_query' => array (
				'relation' => 'AND',
				array ( 'key' => 'campaign_vote', 'value' => $type ),
				array ( 'key' => 'campaign_funding_type', 'value' => 'fundingproject' )
			),
			'meta_key' => 'campaign_end_date',
			'orderby' => 'meta_value',
			'order' => 'desc'
		);
		if (!empty($client)) {
			$query_options['tax_query'] = array( array( 
				'taxonomy' => 'download_tag',
				'field' => 'slug', 
				'terms' => array($client) 
			) );
		}
		return query_posts( $query_options );
	}
	
	public static function list_projects_started() {
		$query_options = array(
			'posts_per_page' => -1,
			'post_type' => 'download',
			'post_status' => 'publish',
			'meta_query' => array (
				'relation' => 'OR',
				array ( 'key' => 'campaign_vote', 'value' => ATCF_Campaign::$campaign_status_collecte ),
				array ( 'key' => 'campaign_vote', 'value' => ATCF_Campaign::$campaign_status_funded ),
				array ( 'key' => 'campaign_vote', 'value' => ATCF_Campaign::$campaign_status_closed ),
				array ( 'key' => 'campaign_vote', 'value' => ATCF_Campaign::$campaign_status_archive )
			)
		);
		return query_posts( $query_options );
	}
	
	public static function list_projects_searchable() {
		global $wpdb;
		$results = $wpdb->get_results( "
			SELECT ID, post_title, post_name FROM ".$wpdb->posts."
			INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id
			WHERE ".$wpdb->posts.".post_type = 'download' AND ".$wpdb->posts.".post_status = 'publish' AND ".$wpdb->postmeta.".meta_key = 'campaign_vote' 
				AND (".$wpdb->postmeta.".meta_value = '".ATCF_Campaign::$campaign_status_vote."' OR ".$wpdb->postmeta.".meta_value = '".ATCF_Campaign::$campaign_status_collecte."' OR ".$wpdb->postmeta.".meta_value = '".ATCF_Campaign::$campaign_status_funded."' OR ".$wpdb->postmeta.".meta_value = '".ATCF_Campaign::$campaign_status_closed."' OR ".$wpdb->postmeta.".meta_value = '".ATCF_Campaign::$campaign_status_archive."')
			ORDER BY ".$wpdb->posts.".post_date DESC
		", OBJECT );
		
		$buffer = array();
		foreach ( $results as $project_post ) {
			$meta_is_hidden = get_post_meta( $project_post->ID, ATCF_Campaign::$key_campaign_is_hidden, TRUE );
			$meta_project_type = get_post_meta( $project_post->ID, 'campaign_funding_type', TRUE );
			if ( empty( $meta_is_hidden ) && $meta_project_type == 'fundingproject' ) {
				array_push( $buffer, $project_post );
			}
		}
		return $buffer;
	}

	public function is_user_editing_meta( $user_id, $meta_key ) {
		$buffer = FALSE;
		$activity_max = 15;

	    $meta_value = get_post_meta( $this->ID, $meta_key, TRUE );

	    if ( !empty($meta_value) ) {
	    	if ( $meta_value[ 'user' ] != $user_id ) {
	    		$meta_datetime = new DateTime( $meta_value[ 'date' ] );
				$current_datetime = new DateTime();

				$interval = $current_datetime->diff( $meta_datetime );
				$interval_formatted = $interval->format('%I');

				if ( $interval_formatted <= $activity_max ) {
					$buffer = TRUE;
				}
	    	}
	    }

		return $buffer;
	}

	public function is_different_content( $current_content, $property, $lang ) {
		$buffer = FALSE; 
		$this->set_current_lang( $lang );

		switch ( $property ) {
			case "description" :
				$content = md5( $this->description() );
				break;
			case "societal_challenge":
				$content = md5( $this->societal_challenge() );
				break;
			case "added_value":
				$content = md5( $this->added_value() );
				break;
			case "economic_model":
				$content = md5( $this->economic_model() );
				break;
			case "implementation":
				$content = md5( $this->implementation() );
				break;
		} 

		if ( $content != $current_content ) {
		 	$buffer = TRUE;
		}
		return $buffer;
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
		'976 Mayotte',
		'Italie',
		'Belgique',
		'Espagne'
	);
	return $buffer;
}
function atcf_get_regions() {
	$buffer = array(
		"Auvergne-Rhône-Alpes"			=> array( 1, 3, 7, 15, 26, 38, 42, 43, 63, 69, 73, 74 ),
		"Bourgogne-Franche-Comté"		=> array( 21, 25, 39, 58, 70, 71, 89, 90 ),
		"Bretagne"						=> array( 22, 29 ,35, 56 ),
		"Centre-Val de Loire"			=> array( 18, 28, 36, 37, 41, 45 ),
		"Corse"							=> array( '2A', '2B' ),
		"Grand Est"						=> array( 8, 10, 51, 52, 54, 55, 57, 67, 68, 88 ),
		"Guadeloupe"					=> array( 971 ),
		"Guyane"						=> array( 973 ),
		"Hauts-de-France"				=> array( 2, 59, 60, 62, 80 ),
		"Île-de-France"					=> array( 75, 77, 78, 91, 92, 93, 94, 95 ),
		"La Réunion"					=> array( 974 ),
		"Martinique"					=> array( 972 ),
		"Mayotte"						=> array( 976 ),
		"Normandie"						=> array( 14, 27, 50, 61, 76 ),
		"Nouvelle-Aquitaine"			=> array( 16, 17, 19, 23, 24, 33, 40, 47, 64, 79, 86, 87 ),
		"Occitanie"						=> array( 9, 11, 12, 30, 31, 32, 34, 46, 48, 65, 66, 81, 82 ),
		"Pays de la Loire"				=> array( 44, 49, 53, 72, 85 ),
		"Provence-Alpes-Côte d'Azur"	=> array( 4, 5, 6, 13, 83, 84 ),
		"Etranger"						=> array( 'Ita', 'Bel', 'Esp' )
	);
	return $buffer;
}
