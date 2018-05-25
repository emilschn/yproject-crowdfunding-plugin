<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gestion des réponses aux sondages côté WDGWPREST
 */
class WDGWPREST_Entity_PollAnswer {
	
	/**
	 * Récupère des réponses aux sondages avec identifiant
	 * @param int $poll_anwser_id
	 * @return object
	 */
	public static function get( $poll_anwser_id ) {
		return WDGWPRESTLib::call_get_wdg( 'poll-answer/' .$poll_anwser_id );
	}
	
	/**
	 * Retourne la liste des réponses aux sondages sur l'API avec des options
	 * @param int $user_api_id
	 * @param int $campaign_api_id
	 * @param string $poll_slug
	 */
	public static function get_list( $user_api_id = FALSE, $campaign_api_id = FALSE, $poll_slug = FALSE ) {
		$url = 'poll-answers';
		$params = array();
		if ( !empty( $user_api_id ) ) {
			array_push( $params, 'user_id=' . $user_api_id );
		}
		if ( !empty( $campaign_api_id ) ) {
			array_push( $params, 'project_id=' . $campaign_api_id );
		}
		if ( !empty( $poll_slug ) ) {
			array_push( $params, 'poll_slug=' . $poll_slug );
		}
		if ( !empty( $params ) ) {
			$url .= '?' . implode( '&', $params );
		}
		return WDGWPRESTLib::call_get_wdg( $url );
	}
	
	/**
	 * Crée un groupe de réponses aux sondages sur l'API
	 * @param string $poll_slug
	 * @param int $poll_version
	 * @param string $answers
	 * @param string $context
	 * @param int $context_amount
	 * @param int $campaign_api_id
	 * @param int $user_api_id
	 * @param int $user_age
	 * @param string $user_postal_code
	 * @param string $user_gender
	 * @param string $user_email
	 * @return type
	 */
	public static function create( $poll_slug, $poll_version, $answers, $context, $context_amount, $campaign_api_id, $user_api_id, $user_age, $user_postal_code, $user_gender, $user_email ) {
		$parameters = array(
			'poll_slug'			=> $poll_slug,
			'poll_version'		=> $poll_version,
			'answers'			=> $answers,
			'context'			=> $context,
			'context_amount'	=> $context_amount,
			'project_id'		=> $campaign_api_id,
			'user_id'			=> $user_api_id,
			'user_age'			=> $user_age,
			'user_postal_code'	=> $user_postal_code,
			'user_gender'		=> $user_gender,
			'user_email'		=> $user_email
		);
		return WDGWPRESTLib::call_post_wdg( 'poll-answer', $parameters );
	}
	
}
