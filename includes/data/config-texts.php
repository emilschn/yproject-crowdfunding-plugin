<?php
/**
 * Custom post-type pour les textes de configuration
 */
class WDGConfigTexts {
	private static $post_type_name = 'configtext';


	// URLs utilisées pour accéder aux textes spécifiques
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

	public static $type_contract_frame = 'accord-cadre';
	public static $type_contract_full = 'contract-dinvestissement';

	public static $type_info_fiscal = 'informations-comptables-fiscales';
	public static $type_info_lemonway = 'informations-lemonway';
	public static $type_info_fiscal_royalties = 'informations-royalties-fiscales';


	/**
	 * Register sur WordPress
	 */
	public static function init_custom_post_type() {
		add_action( 'init', 'WDGConfigTexts::register_post_type', 10 );
	}

	public static function register_post_type() {
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
			'supports'            => array('title', 'editor', 'thumbnail', 'comments', 'revisions', 'custom-fields'),
			'taxonomies'          => array(self::$post_type_name),
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

	public static function get_config_text_by_name( $name, $setting_name = FALSE ) {
		global $locale;

		// Si il y a bien un post_type qui correspond
		$configpost = get_page_by_path( $name, OBJECT, self::$post_type_name );
		if ( !empty( $configpost ) ) {
			// Récupérer la page traduite si nécessaire
			if ( $locale != 'fr' && $locale != 'fr_FR' ) {
				$locale_substr = substr( $locale, 0, 2 );
				$post_translated_id = apply_filters( 'wpml_object_id', $configpost->ID, self::$post_type_name, FALSE, $locale_substr );
				if ( !empty( $post_translated_id ) ) {
					$configpost = get_post( $post_translated_id );
				}
			}

			return $configpost->post_content;
		}

		// Sinon, on reprend l'ancienne propriété
		$old_property_edd_settings_= ATCF_CrowdFunding::get_translated_setting( $setting_name, $locale );
		if ( !empty( $old_property_edd_settings ) ) {
			return wpautop( $old_property_edd_settings );
		}

		return get_option( $setting_name );
	}
}


WDGConfigTexts::init_custom_post_type();