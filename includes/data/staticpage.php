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
		
		if ( $this->is_api_content() ) {
			$api_content = WDGWPRESTLib::get_post( $this->get_content_post_id() );
		}
		
		if ( empty( $api_content ) ) {
			return apply_filters( "the_content", $this->post->post_content );
		} else {
			return apply_filters( "the_content", $api_content );
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