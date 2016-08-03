<?php
/**
 * Lib de gestion des pages statiques
 */
class WDGStaticPage {
	
	public static $key_static_content_api_post_id = 'static_content_api_post_id';
	
	private $ID;
	private $post;
	private $content_api_post_id;
	
	public function __construct( $post_id ) {
		$this->ID = $post_id;
		$this->post = get_post( $post_id );
	}
	
	/**
	 * Retourne l'identifiant de post sur l'API
	 * @return string
	 */
	public function get_content_post_id() {
		if ( !isset( $this->content_api_post_id ) ) {
			$this->content_api_post_id = get_post_meta( $this->ID, WDGStaticPage::$key_static_content_api_post_id, TRUE );
		}
		return $this->content_api_post_id;
	}
	
	/**
	 * Retourne TRUE si la page est définie comme statique
	 * @return bool
	 */
	public function is_api_content() {
		$content_post_id = $this->get_content_post_id();
		return ( !empty( $content_post_id ) );
	}
	
	/**
	 * Retourne le contenu d'une page statique, selon qu'il vient de l'API ou de WP
	 * @return string
	 */
	public function get_content() {
		$api_content = "";
		
		//Si on doit récupérer du contenu de l'API
		if ( $this->is_api_content() ) {
			
			//Parcours des pages statiques de l'API pour voir celle-ci a été mise à jour
			$content_post_id = $this->get_content_post_id();
			$last_update = FALSE;
			$staticpages_list = WDGStaticPage::get_list();
			foreach ( $staticpages_list as $staticpage ) {
				if ( $staticpage->id == $content_post_id ) {
					$last_update = $staticpage->update;
				}
			}
			
			//Configuration du cache
			global $WDG_cache_plugin;
			$cache_wdgwpapi_id = 'wdgwpapi_get_gost_' . $content_post_id;
			$cache_wdgwpapi_id_date = 'wdgwpapi_get_gost_' . $content_post_id . '_cachedate';
			$cache_wdgwpapi_version = 1;
			$cache_wdgwpapi_duration = 60*60*24*30; // Mis à jour tous les 30 jours
			
			//Si la mise à jour de la page de l'API est plus récente que la dernière mise en cache
			$api_content_cachedate = $WDG_cache_plugin->get_cache( $cache_wdgwpapi_id_date, $cache_wdgwpapi_version );
			$date_cache = new DateTime( $api_content_cachedate );
			$date_update = new DateTime( $last_update );
			if ( empty( $api_content_cachedate ) || empty( $last_update ) || $date_cache < $date_update ) {
				$api_content = WDGWPRESTLib::get_post( $content_post_id );
				$WDG_cache_plugin->set_cache( $cache_wdgwpapi_id, $api_content, $cache_wdgwpapi_duration, $cache_wdgwpapi_version );
				$date_today = new DateTime();
				$WDG_cache_plugin->set_cache( $cache_wdgwpapi_id_date, $date_today->format( 'Y-m-d H:i' ), $cache_wdgwpapi_duration, $cache_wdgwpapi_version );
			
			//Récupération du cache et mise à jour si nécessaire
			} else {
				$api_content = $WDG_cache_plugin->get_cache( $cache_wdgwpapi_id, $cache_wdgwpapi_version );
				
				if ( empty( $api_content ) ) {
					$api_content = WDGWPRESTLib::get_post( $content_post_id );
					$WDG_cache_plugin->set_cache( $cache_wdgwpapi_id, $api_content, $cache_wdgwpapi_duration, $cache_wdgwpapi_version );
					$date_today = new DateTime();
					$WDG_cache_plugin->set_cache( $cache_wdgwpapi_id_date, $date_today->format( 'Y-m-d H:i' ), $cache_wdgwpapi_duration, $cache_wdgwpapi_version );
				}
			}
		
			if ( empty( $api_content ) ) {
				return apply_filters( "the_content", $this->post->post_content );
			} else {
				return apply_filters( "the_content", $api_content );
			}
		}
	}
	
	
/******************************************************************************/
	/**
	 * Retourne la liste des pages statiques recensées par l'API
	 * @return array
	 */
	public static function get_list() {
		return WDGWPRESTLib::get_staticpages_list();
	}
}