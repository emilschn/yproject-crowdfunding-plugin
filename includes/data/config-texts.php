<?php
/**
 * Custom post-type pour les textes de configuration
 */
class WDGConfigTexts {
	private static $post_type_name = 'configtext';
	private static $post_type_categories = 'configtexttypes';

	// URLs utilisées pour accéder aux textes spécifiques
	public static $type_term_cookies_retracted = 'cgu-cookies-retracte';
	public static $type_term_cookies_extended = 'cgu-cookies-etendu';
	public static $type_term_extracts = 'cgu-extraits-pour-lightbox';
	public static $type_term_particular = 'cgu-conditions-particulieres';
	public static $type_term_mandate = 'cgu-conditions-contractuelles-prelevement';

	public static $type_project_default_pitch = 'projet-defaut-pitch';
	public static $type_project_default_impacts = 'projet-defaut-impacts';
	public static $type_project_default_strategy = 'projet-defaut-strategie';
	public static $type_project_default_finance = 'projet-defaut-finance';
	public static $type_project_default_team = 'projet-defaut-equipe';

	public static $type_investment_generalities = 'avertissements-sur-linvestissement';
	public static $type_investment_generalities_preinvestment = 'avertissements-sur-le-preinvestissement';
	public static $type_investment_terms = 'modalites-dinvestissement';
	public static $type_subscription_terms = 'modalites-dabonnement';

	public static $type_contract_frame = 'accord-cadre';
	public static $type_contract_full = 'contrat-dinvestissement';

	public static $type_info_fiscal = 'informations-comptables-fiscales';
	public static $type_info_lemonway = 'informations-lemonway';
	public static $type_info_fiscal_royalties = 'informations-royalties-fiscales';

	// Slugs utilisés pour les catégories spécifiques
	public static $category_earnings_description = 'earnings_description';
	public static $category_simple_info = 'simple_info';
	public static $category_detailed_info = 'detailed_info';
	public static $category_premium = 'contract_premium';
	public static $category_warranty = 'contract_warranty';

	/**
	 * Register sur WordPress
	 */
	public static function init_custom_post_type() {
		add_action( 'init', 'WDGConfigTexts::register_post_type', 10 );
	}

	public static function register_post_type() {
		// Enregistrement des catégories de Custom Post Type
		$labels_taxonomy = array(
			'name'              => 'Types',
			'singular_name'     => 'Type',
			'search_items'      => 'Rechercher',
			'all_items'         => 'Tous',
			'parent_item'       => 'Parent',
			'parent_item_colon' => 'Parent :',
			'edit_item'         => 'Editer',
			'update_item'       => 'Mettre à jour',
			'add_new_item'      => 'Ajouter nouveau',
			'new_item_name'     => 'Nouveau',
			'menu_name'         => 'Types',
		);
		$args_taxonomy = array(
			'labels'		=> $labels_taxonomy,
			'hierarchical'	=> true
		);
		register_taxonomy( WDGConfigTexts::$post_type_categories, WDGConfigTexts::$post_type_name, $args_taxonomy );

		// Enregistrement du Custom Post Type
		$labels = array(
			'name'                => 'Textes de configuration',
			'singular_name'       => 'Texte de configuration',
			'menu_name'           => 'Textes de configuration',
			'name_admin_bar'      => 'Textes de configuration',
			'parent_item_colon'   => 'Parent',
			'all_items'           => 'Tous les textes',
			'add_new_item'        => 'Ajouter un texte',
			'add_new'             => 'Ajouter nouveau',
			'new_item'            => 'Nouveau',
			'edit_item'           => 'Editer',
			'update_item'         => 'Mettre à jour',
			'view_item'           => 'Voir',
			'search_items'        => 'Rechercher',
			'not_found'           => 'Non trouvé',
			'not_found_in_trash'  => 'Non trouvé dans la corbeille',
		);
		$rewrite = array(
			'slug'                => self::$post_type_name,
			'with_front'          => true,
			'pages'               => true,
			'feeds'               => false,
		);
		$args = array(
			'label'               => self::$post_type_name,
			'description'         => 'Configurations',
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'comments', 'revisions', 'custom-fields' ),
			'taxonomies'          => array( self::$post_type_categories ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-admin-home',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'query_var'           => self::$post_type_name,
			'rewrite'             => $rewrite,
			'capability_type'     => 'page',
		);
		register_post_type( self::$post_type_name, $args );
	}

	/**
	 * Récupération d'un texte de configuration par son url
	 */
	public static function get_config_text_by_name($name, $setting_name = FALSE) {
		// Si il y a bien un post_type qui correspond
		$configpost = get_page_by_path( $name, OBJECT, self::$post_type_name );
		if ( !empty( $configpost ) ) {
			$post_translated_id = self::get_translated_post_id( $configpost->ID );
			if ( !empty( $post_translated_id ) ) {
				$configpost = get_post( $post_translated_id );
			}

			return $configpost->post_content;
		}

		// Sinon, on reprend l'ancienne propriété
		global $locale;
		$old_property_edd_settings = ATCF_CrowdFunding::get_translated_setting( $setting_name, $locale );
		if ( !empty( $old_property_edd_settings ) ) {
			return wpautop( $old_property_edd_settings );
		}

		return get_option( $setting_name );
	}

	/**
	 * Retourne le texte de configuration correspondant à la langue en cours, à partir
	 */
	public static function get_translated_post_id($french_post_id) {
		$buffer = FALSE;

		global $locale, $force_language_to_translate_to;
		$locale_substr = FALSE;
		if ( !WDG_Languages_Helpers::is_french_displayed() && $locale != 'fr' && $locale != ' fr_FR') {
			$locale_substr = substr( $locale, 0, 2 );
		} else if ( !empty( $force_language_to_translate_to ) && $force_language_to_translate_to != 'fr' ) {
			$locale_substr = $force_language_to_translate_to;
		}

		// Récupérer la page traduite si nécessaire
		if ( !empty( $locale_substr ) ) {
			$post_translated_id = apply_filters( 'wpml_object_id', $french_post_id, self::$post_type_name, FALSE, $locale_substr );
			if ( !empty( $post_translated_id ) ) {
				$buffer = $post_translated_id;
			}
		}

		return $buffer;
	}

	/**
	 * Récupération d'une liste de textes de configuration en fonction d'un slug de catégorie
	 */
	public static function get_config_text_list_by_category_slug($category_slug) {
		$term_obj = get_term_by( 'slug', $category_slug, self::$post_type_categories );
		$term_id = $term_obj->term_id;
		if ( !empty( $term_id ) ) {
			return get_posts( array(
				'post_type'		=> self::$post_type_name,
				'numberposts'	=> -1,
				'tax_query' => array(
					array(
						'taxonomy'	=> self::$post_type_categories,
						'field' 	=> 'term_id',
						'terms' 	=> $term_id
					)
				)
			) );
		}

		return FALSE;
	}
}

WDGConfigTexts::init_custom_post_type();