<?php
class WDG_Cache_Plugin {
	
	// TODO : Déplacer dans une classe spécifique de gestion de configuration de cache	
	public static $nb_query_campaign_funded = 40;
	public static $stats_key = 'home-stats';
	public static $stats_duration = 172800; // 48 heures de cache (48*60*60)
	public static $stats_version = 1;

	public static $slider_key = 'project-slider';
	public static $slider_duration = 172800; // 48 heures de cache (48*60*60)
	public static $slider_version = 1;

	public static $projects_nb_to_show = 3;
	public static $projects_key = 'home-projects-list';
	public static $projects_duration = 172800; // 48 heures de cache (48*60*60)
	public static $projects_version = 1;

	private $table_name;
	private $wpdb;

	protected static $_current = null;
	
	/**
	 * Constructeur
	 */
	function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = "wp_wdg_cache";

		add_action( 'wdg_delete_cache', array( $this, 'delete_cache' ), 10, 1 );
	}
	
	/**
	 * Récupération d'une instance statique
	 * @return WDG_Cache_Plugin
	 */
	public static function current() {
		if ( is_null( self::$_current ) ) {
			self::$_current = new self();
		}
		return self::$_current;
	}
	
	/**
	 * Permet de mettre en cache du contenu
	 * @param type $name Cle du contenu mis en cache, permet de retrouver le contenu.
	 * @param type $content Contenu mis en cache
	 * @param type $expiration_time Temps d'expiration en secondes
	 * @param type $version Version du cache
	 */
	public function set_cache( $name, $content, $expiration_time = 0, $version = 0) {
		$this->delete_cache( array( $name ) );
		if ($expiration_time != 0) {
			$expiration_time += time();
		}
		$this->wpdb->insert( 
			$this->table_name, 
			array(
				'name'		=> $name, 
				'content'	=> $content,
				'expiration_time' => $expiration_time,
				'version'	=> $version
			), 
			array( '%s', '%s', '%d', '%d' ) 
		);
	}
	
	/**
	 * Retourne un contenu qui a été mis en cache
	 * @param type $name Cle du contenu que l'on veut obtenir
	 * @param type $version Version du cache que l'on veut obtenir
	 * @return boolean / string
	 */
	public function get_cache( $name, $version = -1 ) {
		if ( defined('WDG_DISABLE_CACHE') && WDG_DISABLE_CACHE === true ) {
			return false;
		}
		
		//L'utilisateur a demandé une version spécifique & la version demandée n'est pas celle de la base de données
		if ( $version != -1 && $this->get_version($name) != $version ) {
			return false;
		}
		
		$cache_row = $this->wpdb->get_row( "SELECT * FROM $this->table_name WHERE `name` = '$name' ", ARRAY_A );
		if ( $cache_row['expiration_time'] != 0 && $cache_row['expiration_time'] < time() ) {
			return false;
		} else if ( empty( $cache_row ) ) {
			return false;
		} else {
			return $cache_row['content'];
		}
	}
	
	/**
	 * Retourne la version associé à la clé $name
	 * @param type $name Cle du contenu que l'on veut obtenir
	 */
	public function get_version( $name ) {
		$cache_version = $this->wpdb->get_var("SELECT version FROM $this->table_name WHERE `name` = '$name' ");
		return $cache_version;
	}
	
	/**
	 * Suppression dans le cache
	 * @param type $array_name Liste des clés de contenu que l'on veut obtenir
	 */
	public function delete_cache( $array_name ) {
		foreach ( $array_name as $name ) {
			$this->wpdb->delete( $this->table_name, array( 'name' => $name ) );
		}
	}
	
	
	
/*******************************************************************************
 * CREATION BASE DE DONNES
 ******************************************************************************/
	/**
	 * Met à jour la base de données si nécessaire
	 */
	public static function upgrade_db() {
		if ( !WDG_Cache_Plugin::table_exists() ) {
			WDG_Cache_Plugin::create_cache_table();
		}
	}
	
	/**
	 * Permet de vérifier si la table existe dans la base de données.
	 */
	private static function table_exists() {
		global $wpdb;
		if ( $wpdb->get_var("SHOW TABLES LIKE 'wp_wdg_cache'") != "wp_wdg_cache" ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Permet de créer la table dans la base de données
	 */
	private static function create_cache_table() {
		$sql_query = "CREATE TABLE IF NOT EXISTS `wp_wdg_cache` (
 						`id` int(11) NOT NULL AUTO_INCREMENT,
  						`name` text COLLATE latin1_general_ci NOT NULL,
  						`content` text COLLATE latin1_general_ci NOT NULL,
  						`expiration_time` int(11) NOT NULL,
 						`version` int(11) NOT NULL,
  						PRIMARY KEY (`id`)
					) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_query );
	}
}

global $WDG_Cache_Plugin;
$WDG_cache_plugin = new WDG_Cache_Plugin();