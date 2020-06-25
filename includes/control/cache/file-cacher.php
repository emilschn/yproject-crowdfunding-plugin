<?php
/**
 * Se charge de visiter les pages à intervale régulier pour en enregistrer le contenu
 */
class WDG_File_Cacher {
	public static $key_post_is_cached_as_html = 'is_cached_as_html';
	
	private $website;
	private static $page_list_to_recache = array(
		"home"				=> "",
		"les-projets"		=> "les-projets"
	);

	protected static $_current = null;
	
	/**
	 * Constructeur
	 */
	public function __construct() {
		$this->website = home_url( '/' );

		add_action( 'wdg_delete_cache', array( $this, 'delete_from_action' ), 10, 1 );
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
		foreach ( WDG_File_Cacher::$page_list_to_recache as $page_name => $page_path ) {
			$this->build_static_page_cache( $page_name );
		}
		
		// Mise en cache des pages des projets
		// Principe :
		// - on ne supprime les fichiers html que pour les fichiers qui datent de plus de 2h pour les campagnes en cours (le cache bdd n'est plus valable)
		// - on met en cache 5 campagnes dont le cache a expiré (pour pouvoir finir la procédure)
		$max_page_to_cache = 4;
		$nb_page_cached = 0;
		$list_campaign_funded = ATCF_Campaign::get_list_funded( 15 );
		foreach ( $list_campaign_funded as $project_post ) {
			$has_built_campaign = $this->build_campaign_page_cache( $project_post->ID, TRUE );
			if ( $has_built_campaign ) {
				$nb_page_cached++;
			}
			if ( $nb_page_cached >= $max_page_to_cache ) {
				break;
			}
		}
		
	}
	
	/**
	 * Recontruit le fichier html pour une page statique
	 * @param string $page_name
	 */
	public function build_static_page_cache( $page_name ) {
		$page_path = WDG_File_Cacher::$page_list_to_recache[ $page_name ];
		$this->delete( $page_name );
		$file_path = $this->get_filepath( $page_name );
		$page_content = $this->get_content( $page_path );
		$this->save( $file_path, $page_content );
	}
	
	/**
	 * Recontruit le fichier html pour une page statique
	 * @param int $id_post
	 */
	public function build_post( $id_post ) {
		switch ( $id_post ) {
			case 1:
				$this->build_static_page_cache( 'home' );
				break;
			case 2:
				$this->build_static_page_cache( 'les-projets' );
				break;
			default:
				$post_uri = get_page_uri( $id_post );
				if ( !empty( $post_uri ) ) {
					$this->delete( $post_uri );

					$file_path = $this->get_filepath( $post_uri );
					$page_content = $this->get_content( $post_uri );
					$this->save( $file_path, $page_content );
				}
				break;
		}
	}
	
	/**
	 * Recontruit le fichier html pour une page de campaign
	 * @param int $campaign_id
	 */
	public function build_campaign_page_cache( $campaign_id, $rebuild = TRUE ) {
		$buffer = FALSE;
		$funded_cache_duration = 60 * 60 * 48;
		
		$db_cacher = WDG_Cache_Plugin::current();
		$skip_cache_campaign = $db_cacher->get_cache( 'cache_campaign_' . $campaign_id, 1 );
		if ( !$skip_cache_campaign ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$lang_list = $campaign->get_lang_list();
			$this->delete( $campaign->data->post_name );
			if ( $rebuild && empty( $lang_list ) && $campaign->campaign_status() == ATCF_Campaign::$campaign_status_funded ) {
				$file_path = $this->get_filepath( $campaign->data->post_name );
				$page_content = $this->get_content( $campaign->data->post_name );
				$this->save( $file_path, $page_content );
				$duration = $funded_cache_duration;
				$db_cacher->set_cache( 'cache_campaign_' . $campaign_id, '1', $duration, 1 );
				$buffer = TRUE;
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Renvoie le chemin d'un fichier à partir de son identifiant
	 * @param string $name
	 * @return string
	 */
	private function get_filepath( $name ) {
		return dirname( __FILE__ ) . '/../../../files/cache/' .$name. '.html';
	}
	
	/**
	 * Renvoie le contenu distant d'une page
	 * @param type $page_path
	 * @return type
	 */
	private function get_content( $page_path ) {
		ypcf_debug_log( 'WDG_File_Cacher::get_content > ' . $this->website . $page_path );
		$context = stream_context_create( array(
				'http' => array(
					'timeout' => 120, // Timeout de 2 minutes
				)
		) );
		
		try {
			return file_get_contents( $this->website . $page_path . '/', FALSE, $context );
		} catch (Exception $e) { }

		return FALSE;
	}
	
	/**
	 * 
	 * @param type $file_path
	 * @param type $page_content
	 */
	private function save( $file_path, $page_content ) {
		if ( !empty( $file_path ) && !empty( $page_content ) ) {
			ypcf_debug_log( 'WDG_File_Cacher::save > ' . $file_path );
			$dir = dirname( $file_path );
			if ( !is_dir( $dir ) ) {
				mkdir( $dir, 0777, TRUE );
			}
			$file_handle = fopen( $file_path, 'a' );
			fwrite( $file_handle, $page_content );
			fclose( $file_handle );
		}
	}
	
	/**
	 * Met dans la liste des queues d'action la création d'une nouvelle page html
	 * @param int $id_post
	 */
	public function queue_cache_post( $id_post, $priority = 'date' ) {
		WDGQueue::add_cache_post_as_html( $id_post, $priority );
	}
	
	/**
	 * Supprime un fichier de cache par l'ID de post correspondant
	 * @param int $id_post
	 */
	public function delete_by_post_id( $id_post ) {
		$post_uri = get_page_uri( $id_post );
		if ( !empty( $post_uri ) ) {
			$this->delete( $post_uri );
		}
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
	public function delete_from_action( $array_name ) {
		foreach ( $array_name as $name ) {
			switch ( $name ) {
				case 'home-projects':
					$this->delete( 'home' );
					$this->queue_cache_post( 1, 'high' );
					break;
				case 'projectlist-projects-current':
				case 'projectlist-projects-funded':
					$this->delete( 'les-projets' );
					$this->queue_cache_post( 2, 'high' );
					break;
			}
		}
	}
}

WDG_File_Cacher::current();