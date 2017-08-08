<?php
/**
 * Se charge de visiter les pages à intervale régulier pour en enregistrer le contenu
 */
class WDG_File_Cacher {
	private $website;
	private $page_list = array(
		"home" => "",
		"les-projets" => "les-projets"
	);
	
	/**
	 * Constructeur
	 */
	public function __construct() {
		$this->website = home_url( '/' );

		add_action( 'wdg_delete_cache', array( $this, 'delete_db_cache' ), 10, 1 );
	}
	
	/**
	 * Parcourt la liste des pages à mettre en cache, supprime l'existant et réenregistre
	 */
	public function rebuild_cache() {
		if ( defined('WDG_DISABLE_CACHE') && WDG_DISABLE_CACHE === true ) {
			return false;
		}
		
		foreach ( $this->page_list as $page_name => $page_path ) {
			$this->delete( $page_name );
			$file_path = $this->get_filepath( $page_name );
			$page_content = $this->get_content( $page_path );
			$this->save( $file_path, $page_content );
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
		return file_get_contents( $this->website . $page_path );
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

global $WDG_File_Cacher;
$WDG_File_Cacher = new WDG_File_Cacher();