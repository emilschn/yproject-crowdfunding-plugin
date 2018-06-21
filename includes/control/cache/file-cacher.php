<?php
/**
 * Se charge de visiter les pages à intervale régulier pour en enregistrer le contenu
 */
class WDG_File_Cacher {
	private $website;
	private $page_list = array(
		"home" => "",
		"les-projets" => "les-projets",
		"financement" => "financement",
		"investissement" => "investissement"
	);

	protected static $_current = null;
	
	/**
	 * Constructeur
	 */
	public function __construct() {
		$this->website = home_url( '/' );

		add_action( 'wdg_delete_cache', array( $this, 'delete_db_cache' ), 10, 1 );
	}
	
	/**
	 * Récupération d'une instance statique
	 * @return WDG_File_Cacher
	 */
	public static function current() {
		if ( is_null( self::$_current ) ) {
			self::$_current = new self();
		}
		return self::$_current;
	}
	
	/**
	 * Parcourt la liste des pages à mettre en cache, supprime l'existant et réenregistre
	 */
	public function rebuild_cache() {
		if ( defined('WDG_DISABLE_CACHE') && WDG_DISABLE_CACHE === true ) {
//			return false;
		}
		
		// Mise en cache des pages statiques de base
		foreach ( $this->page_list as $page_name => $page_path ) {
			$this->build_static_page_cache( $page_name );
		}
		
		// Mise en cache des pages des projets
		// Principe :
		// - on ne supprime les fichiers html que pour les fichiers qui datent de plus de 2h pour les campagnes en cours (le cache bdd n'est plus valable)
		// - on met en cache 5 campagnes dont le cache a expiré (pour pouvoir finir la procédure)
		$max_page_to_cache = 5;
		$nb_page_cached = 0;
		$list_campaign_recent = ATCF_Campaign::get_list_most_recent( 15 );
		foreach ( $list_campaign_recent as $campaign_id ) {
			$this->build_static_page_cache( $campaign_id, ( $nb_page_cached < $max_page_to_cache ) );
			$nb_page_cached++;
		}
		
	}
	
	/**
	 * Recontruit le fichier html pour une page statique
	 * @param string $page_name
	 */
	public function build_static_page_cache( $page_name ) {
		$page_path = $this->page_list[ $page_name ];
		$this->delete( $page_name );
		$file_path = $this->get_filepath( $page_name );
		$page_content = $this->get_content( $page_path );
		$this->save( $file_path, $page_content );
	}
	
	/**
	 * Recontruit le fichier html pour une page de campaign
	 * @param int $campaign_id
	 */
	public function build_campaign_page_cache( $campaign_id, $rebuild = TRUE ) {
		$vote_cache_duration = 60 * 60 * 2;
		$funding_cache_duration = 60 * 60 * 2;
		$funded_cache_duration = 60 * 60 * 48;
		
		$db_cacher = WDG_Cache_Plugin::current();
		$skip_cache_campaign = $db_cacher->get_cache( 'cache_campaign_' . $campaign_id, 1 );
		if ( !$skip_cache_campaign ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$this->delete( $campaign->data->post_name );
			if ( $rebuild ) {
				$file_path = $this->get_filepath( $campaign->data->post_name );
				$page_content = $this->get_content( $campaign->data->post_name );
				$this->save( $file_path, $page_content );
				$duration = $vote_cache_duration;
				if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_collecte ) {
					$duration = $funding_cache_duration;
				} else if ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded ) {
					$duration = $funded_cache_duration;
				}
				$db_cacher->set_cache( 'cache_campaign_' . $campaign_id, '1', $duration, 1 );
			}
		}
	}
	
	/**
	 * Renvoie le chemin d'un fichier à partir de son identifiant
	 * @param string $name
	 * @return string
	 */
	private function get_filepath( $name ) {
		return dirname( __FILE__ ) . '/files/' .$name. '.html';
	}
	
	/**
	 * Renvoie le contenu distant d'une page
	 * @param type $page_path
	 * @return type
	 */
	private function get_content( $page_path ) {
		ypcf_debug_log( 'WDG_File_Cacher::get_content > ' . $this->website . $page_path );
		return file_get_contents( $this->website . $page_path . '/' );
	}
	
	/**
	 * 
	 * @param type $file_path
	 * @param type $page_content
	 */
	private function save( $file_path, $page_content ) {
		ypcf_debug_log( 'WDG_File_Cacher::save > ' . $file_path );
		$file_handle = fopen( $file_path, 'a' );
		fwrite( $file_handle, $page_content );
		fclose( $file_handle );
	}
	
	/**
	 * Supprime un fichier de cache
	 * @param string $name
	 */
	public function delete( $name ) {
		ypcf_debug_log( 'WDG_File_Cacher::delete > ' . $name );
		if ( !empty( $name ) ) {
			@unlink( $this->get_filepath( $name ) );
		}
	}
	
	/**
	 * Récupération action de suppression de cache de la base de données
	 */
	public function delete_db_cache( $array_name ) {
		foreach ( $array_name as $name ) {
			switch ( $name ) {
				case 'home-projects':
					$this->delete( 'home' );
					break;
				case 'projectlist-projects-current':
				case 'projectlist-projects-funded':
					$this->delete( 'les-projets' );
					break;
			}
		}
	}
}

WDG_File_Cacher::current();