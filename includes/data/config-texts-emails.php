<?php
/**
 * Custom post-type pour les textes de configuration
 */
class WDGConfigTextsEmails {
	private static $post_type_name = 'configtextemail';
	private static $post_type_categories = 'configtextemailtypes';

	/**
	 * Register sur WordPress
	 */
	public static function init_custom_post_type() {
		add_action( 'init', 'WDGConfigTextsEmails::register_post_type', 10 );
	}

	public static function register_post_type() {
		// Enregistrement des catégories de Custom Post Type
		$labels_taxonomy = array(
			'name'              => 'Catégories',
			'singular_name'     => 'Catégorie',
			'search_items'      => 'Rechercher',
			'all_items'         => 'Tous',
			'parent_item'       => 'Parent',
			'parent_item_colon' => 'Parent :',
			'edit_item'         => 'Editer',
			'update_item'       => 'Mettre à jour',
			'add_new_item'      => 'Ajouter nouvelle',
			'new_item_name'     => 'Nouvelle',
			'menu_name'         => 'Catégories',
		);
		$args_taxonomy = array(
			'labels'		=> $labels_taxonomy,
			'hierarchical'	=> true
		);
		register_taxonomy( WDGConfigTextsEmails::$post_type_categories, WDGConfigTextsEmails::$post_type_name, $args_taxonomy );

		// Enregistrement du Custom Post Type
		$labels = array(
			'name'                => 'Templates de mails',
			'singular_name'       => 'Template de mail',
			'menu_name'           => 'Templates de mails',
			'name_admin_bar'      => 'Templates de mails',
			'parent_item_colon'   => 'Parent',
			'all_items'           => 'Tous les templates',
			'add_new_item'        => 'Ajouter un template',
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
			'description'         => 'Templates',
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
			'show_in_rest'        => true,
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
	 * Récupération d'un template de mail par son url
	 */
	public static function get_config_text_email_by_name($name, $language_to_translate_to = '') {
		// Si il y a bien un post_type qui correspond
		$configpost = get_page_by_path( $name, OBJECT, self::$post_type_name );
		if ( !empty( $configpost ) ) {
			if ( !empty( $language_to_translate_to ) ) {
				$post_translated_id = self::get_translated_post_id( $configpost->ID, $language_to_translate_to );
				if ( !empty( $post_translated_id ) ) {
					$configpost = get_post( $post_translated_id );
				}
			}

			return $configpost;
		}

		return FALSE;
	}

	/**
	 * Retourne le template de mail correspondant à la langue en cours, à partir
	 */
	public static function get_translated_post_id($french_post_id, $language_to_translate_to = '') {
		$buffer = FALSE;

		// Récupérer la page traduite si nécessaire
		if ( !WDG_Languages_Helpers::is_french_displayed() ) {
			$locale_substr = substr( $language_to_translate_to, 0, 2 );
			$post_translated_id = apply_filters( 'wpml_object_id', $french_post_id, self::$post_type_name, FALSE, $locale_substr );
			if ( !empty( $post_translated_id ) ) {
				$buffer = $post_translated_id;
			}
		}

		return $buffer;
	}
}

WDGConfigTextsEmails::init_custom_post_type();