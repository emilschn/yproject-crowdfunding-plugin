<?php
class WDG_Cache_Plugin {
	// TODO : Déplacer dans une classe spécifique de gestion de configuration de cache
	public static $nb_query_campaign_funded = 150;
	public static $stats_key = 'home-stats';
	public static $stats_duration = 864000; // 10 jours de cache (10*24*60*60)
	public static $stats_version = 1;

	public static $slider_key = 'project-slider';
	public static $slider_duration = 172800; // 48 heures de cache (48*60*60)
	public static $slider_version = 1;

	private static $funded_campaign_top_list = array( 'cuir-marin-de-france', 'listo', 'mylabel' );
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
	public function set_cache($name, $content, $expiration_time = 0, $version = 0) {
		$this->delete_cache( array( $name ) );
		if ($expiration_time != 0) {
			$expiration_time += time();
		}
		$this->wpdb->insert( $this->table_name, array(
				'name'		=> $name,
				'content'	=> $content,
				'expiration_time' => $expiration_time,
				'version'	=> $version
			), array( '%s', '%s', '%d', '%d' ) );
	}

	/**
	 * Retourne un contenu qui a été mis en cache
	 * @param type $name Cle du contenu que l'on veut obtenir
	 * @param type $version Version du cache que l'on veut obtenir
	 * @return boolean / string
	 */
	public function get_cache($name, $version = -1) {
		if ( defined('WDG_DISABLE_CACHE') && WDG_DISABLE_CACHE === true ) {
			return false;
		}

		$cache_row = $this->wpdb->get_row( "SELECT * FROM $this->table_name WHERE `name` = '$name' ", ARRAY_A );

		// L'utilisateur a demandé une version spécifique & la version demandée n'est pas celle de la base de données
		if ( $version != -1 && $cache_row[ 'version' ] != $version ) {
			return false;
		}

		// Vérification si la valeur a expiré
		if ( $cache_row[ 'expiration_time' ] != 0 && $cache_row[ 'expiration_time' ] < time() ) {
			return false;
		} else {
			if ( empty( $cache_row ) ) {
				return false;
			} else {
				return $cache_row[ 'content' ];
			}
		}
	}

	/**
	 * Suppression dans le cache
	 * @param type $array_name Liste des clés de contenu que l'on veut obtenir
	 */
	public function delete_cache($array_name) {
		foreach ( $array_name as $name ) {
			$this->wpdb->delete( $this->table_name, array( 'name' => $name ) );
		}
	}
	/***********************************************************************************
	 * TODO : Déplacer dans une classe spécifique de gestion de configuration de cache
	 **********************************************************************************/
	// Calcul les stats et les met en cache
	public static function initialize_home_stats() {
		$db_cacher = WDG_Cache_Plugin::current();

		$stats_list = array(
			'count_amount'				=> 0,
			'count_people'				=> 0,
			'royaltying_projects'		=> 0
		);

		if ( !defined( 'WDG_DISABLE_CACHE') || WDG_DISABLE_CACHE == FALSE ) {
			$home_stats = WDGWPREST_Entity_Project::get_home_stats();
			$stats_list[ 'count_amount' ] = $home_stats->amount_collected;
			$stats_list[ 'count_people' ] = $home_stats->count_investors;
			$stats_list[ 'royaltying_projects' ] = $home_stats->royaltying_projects;
		}

		$stats_content = json_encode($stats_list);
		$db_cacher->set_cache( WDG_Cache_Plugin::$stats_key, $stats_content, WDG_Cache_Plugin::$stats_duration, WDG_Cache_Plugin::$stats_version );

		return $stats_list;
	}

	// Recherche les 3 projects les plus récent et les met en cache
	public static function initialize_most_recent_projects() {
		$db_cacher = WDG_Cache_Plugin::current();
		$list_projects = ATCF_Campaign::get_list_most_recent( 3 );
		$slider = array();

		foreach ( $list_projects as $project_id ) {
			$campaign = atcf_get_campaign( $project_id );
			$img = $campaign->get_home_picture_src( TRUE, 'large' );
			array_push( $slider, array(
					'img'	=> $img,
					'title'	=> $campaign->data->post_title,
					'link'	=> get_permalink( $project_id )
				));
		}
		$slider_content = json_encode($slider);

		$db_cacher->set_cache( WDG_Cache_Plugin::$slider_key, $slider_content, WDG_Cache_Plugin::$slider_duration, WDG_Cache_Plugin::$slider_version );

		return $slider;
	}

	//Recherche projets en cours de financement à impact positif et les met en cache
	public static function initialize_home_projects() {
		$db_cacher = WDG_Cache_Plugin::current();
		$projects_list = array();

		// On prend d'abord les projets en cours de financement
		$campaignlist_funding = ATCF_Campaign::get_list_funding( WDG_Cache_Plugin::$projects_nb_to_show, '', TRUE );
		$campaignlist_funding_sorted = WDG_Cache_Plugin::sort_project_list( $campaignlist_funding );
		$count_campaignlist = count( $campaignlist_funding_sorted );
		foreach ( $campaignlist_funding_sorted as $campaign ) {
			array_push( $projects_list, $campaign->ID );
		}

		// Si il n'y a pas assez de projet en cours de financement
		// On prend les projets en cours d'évaluation
		if ( $count_campaignlist < WDG_Cache_Plugin::$projects_nb_to_show ) {
			$campaignlist_vote = ATCF_Campaign::get_list_vote( WDG_Cache_Plugin::$projects_nb_to_show, '', TRUE );
			$campaignlist_vote_sorted = WDG_Cache_Plugin::sort_project_list( $campaignlist_vote );
			$count_campaignlist += count( $campaignlist_vote_sorted );
			foreach ( $campaignlist_vote_sorted as $campaign_post ) {
				$campaign = new ATCF_Campaign( $campaign_post->ID );
				// On ne prend que ceux dont la date de fin d'évaluation n'est pas dépassée
				if ( $campaign->end_vote_remaining() > 0 ) {
					array_push( $projects_list, $campaign_post->ID );
				}
			}
		}

		// Si il n'y a pas assez de projet en cours de financement + en évaluation
		// On prend les projets en post-cloture
		if ( $count_campaignlist < WDG_Cache_Plugin::$projects_nb_to_show ) {
			$campaignlist_post_funding = ATCF_Campaign::get_list_funding( WDG_Cache_Plugin::$projects_nb_to_show, '', TRUE, FALSE );
			$campaignlist_post_funding_sorted = WDG_Cache_Plugin::sort_project_list( $campaignlist_post_funding );
			$count_campaignlist += count( $campaignlist_post_funding_sorted );
			foreach ( $campaignlist_post_funding_sorted as $campaign_post ) {
				array_push( $projects_list, $campaign_post->ID );
			}
		}

		// On n'en garde que 3 parmi ceux ci-dessus
		$i = $count_campaignlist - 1;
		while ( $i > WDG_Cache_Plugin::$projects_nb_to_show - 1 ) {
			array_splice( $projects_list, $i, 1 );
			$i--;
		}
		// Si il y en a moins que 3, on rajoute les "meilleurs projets financés"
		if ( $count_campaignlist < WDG_Cache_Plugin::$projects_nb_to_show ) {
			for ( $i = 0; $i < WDG_Cache_Plugin::$projects_nb_to_show - $count_campaignlist; $i++ ) {
				global $wpdb;
				$result = $wpdb->get_results( "SELECT ID FROM ".$wpdb->posts." WHERE ".$wpdb->posts.".post_type = 'download' AND ".$wpdb->posts.".post_name = '".WDG_Cache_Plugin::$funded_campaign_top_list[ $i ]."'", OBJECT);
				if ( count( $result ) > 0 ) {
					array_push( $projects_list, $result[0]->ID );
				} else {
					array_push( $projects_list, 1 );
				}
			}
		}
		// On enregistre le résultat des différentes requêtes en cache de base de données pour ne pas le re-calculer à chaque fois
		$projects_content = json_encode($projects_list);
		$db_cacher->set_cache( WDG_Cache_Plugin::$projects_key, $projects_content, WDG_Cache_Plugin::$projects_duration, WDG_Cache_Plugin::$projects_version );

		return $projects_list;
	}

	public static function sort_project_list($campaign_list) {
		// On commence par mélanger toute la liste pour être sûr d'avoir de l'aléatoire
		shuffle( $campaign_list );

		// On parcourt tous les projets
		$count_campaigns = count( $campaign_list );
		for ( $i = $count_campaigns - 1; $i >= 0; $i-- ) {
			$campaign_post = $campaign_list[ $i ];
			$campaign = new ATCF_Campaign( $campaign_post->ID );
			$campaign_categories_str = $campaign->get_categories_str();

			// On supprime ceux dont la date de fin est passée
			if ( !$campaign->time_remaining_str() == '-' ) {
				array_splice( $campaign_list, $i, 1 );
			}
			// On supprime ceux qui ne sont pas des projets d'entreprise
			if ( strpos( $campaign_categories_str, 'entreprises' ) === FALSE ) {
				array_splice( $campaign_list, $i, 1 );
			}
			// On met au début ceux qui sont "positifs"
			if ( strpos( $campaign_categories_str, 'environnemental' ) !== FALSE || strpos( $campaign_categories_str, 'social' ) !== FALSE ) {
				$campaign_element = $campaign_list[ $i ];
				array_splice( $campaign_list, $i, 1 );
				array_unshift( $campaign_list, $campaign_element );
			}
		}

		return $campaign_list;
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_query );
	}
}

global $WDG_Cache_Plugin;
$WDG_cache_plugin = new WDG_Cache_Plugin();