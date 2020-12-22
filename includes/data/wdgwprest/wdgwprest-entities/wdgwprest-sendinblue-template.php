<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des templates de mails côté WDGWPREST
 */
class WDGWPREST_Entity_SendinblueTemplate {
	/**
	 * Définit les paramètres en fonction de ce qu'on sait sur le site
	 * @return array
	 */
	public static function set_post_parameters( $template_slug, $template_data ) {
		$parameters = array(
			'slug'				=> $template_slug,
			'description'		=> $template_data[ 'description' ],
			'id_sib_fr'			=> $template_data[ 'fr-sib-id' ],
			'variables_names'	=> $template_data[ 'variables' ],
			'wdg_email_cc'		=> $template_data[ 'wdg-mail' ]
		);
		return $parameters;
	}

	/**
	 * Met à jour un template mail utilisé par la plateforme pour envoyer les données sur l'API
	 */
	public static function update_template( $template_slug ) {
		$template_data = NotificationsAPI::$description_str_by_template_id[ $template_slug ];
		$parameters = self::set_post_parameters( $template_slug, $template_data );
		$buffer = WDGWPRESTLib::call_post_wdg( 'sendinblue-template', $parameters );
	}
}
