<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe d'appels à l'API WDGWPREST
 */
class WDGAirtableUsers {
	
	private static $key_wpid = 'ID WP';
	private static $key_apiid = 'ID API';
	private static $key_name = 'Nom';
	private static $key_email = 'E-mail';
	private static $key_gender = 'Sexe / Entité';
	private static $key_birthday_date = 'Date de naissance';
	private static $key_birthday_year = 'Année de naissance';
	private static $key_age = 'Age';
	private static $key_address = 'Adresse';
	private static $key_postal_code = 'Code Postal';
	private static $key_city = 'Ville';
	private static $key_department = 'Département';
	private static $key_country = 'Pays';
	private static $key_count_project_followed = 'Nb Projets suivis';
	private static $key_count_project_voted = 'Nb Projets évalués';
	private static $key_count_project_invested = 'Nb Investissements';
	private static $key_amount_project_invested = 'Montant investissements';
	
	public static function get_by_user_wpid( $user_wpid ) {
		return WDGAirtable::get_line_by_wdg_data( self::$key_wpid, $user_wpid );
	}
	
	public static function update_or_create( $user_wpid ) {
		$entity_apiid = '';
		$entity_name = '';
		$entity_email = '';
		$entity_gender = '';
		$entity_birthday_date = '';
		$entity_birthday_year = '';
		$entity_age = '';
		$entity_address = '';
		$entity_postal_code = '';
		$entity_city = '';
		$entity_department = '';
		$entity_country = '';
		
		if ( WDGOrganization::is_user_organization( $user_wpid ) ) {
			$WDGOrganization = new WDGOrganization( $user_wpid );
			$entity_apiid = $WDGOrganization->get_api_id();
			$entity_name = $WDGOrganization->get_name();
			$entity_email = $WDGOrganization->get_email();
			$entity_gender = 'O';
			$entity_address = $WDGOrganization->get_full_address_str();
			$entity_postal_code = (string)$WDGOrganization->get_postal_code();
			$entity_city = $WDGOrganization->get_city();
			$entity_country = $WDGOrganization->get_country();
			if ( strtoupper( $entity_country ) == 'FR' || strtoupper( $entity_country ) == 'FRANCE' ) {
				$entity_postal_code = (string)$WDGOrganization->get_postal_code( TRUE );
				$entity_department = substr( $entity_postal_code, 0, 2 );
			}
			
		} else {
			$WDGUser = new WDGUser( $user_wpid );
			$entity_apiid = $WDGUser->get_api_id();
			$entity_name = $WDGUser->get_firstname() .' '. $WDGUser->get_lastname();
			$entity_email = $WDGUser->get_email();
			$entity_gender = strtoupper( substr( $WDGUser->get_gender(), 0, 1 ) );
			$entity_birthday_date = $WDGUser->get_birthday_date();
			if ( !empty( $entity_birthday_date ) ) {
				$entity_birthday_year = (string)$WDGUser->get_birthday_year();
				$entity_age = (string)$WDGUser->get_age();
			}
			$entity_address = $WDGUser->get_full_address_str();
			$entity_postal_code = (string)$WDGUser->get_postal_code();
			$entity_city = $WDGUser->get_city();
			$entity_country = $WDGUser->get_country();
			if ( strtoupper( $entity_country ) == 'FR' || strtoupper( $entity_country ) == 'FRANCE' ) {
				$entity_postal_code = (string)$WDGUser->get_postal_code( TRUE );
				$entity_department = substr( $entity_postal_code, 0, 2 );
			}
		}
		if ( empty( $entity_birthday_date ) ) {
			$entity_birthday_date = '-';
		}
		
		global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";
		$table_vote = $wpdb->prefix . WDGCampaignVotes::$table_name_votes;
		$sql = "SELECT COUNT(post_meta.meta_value) AS nb_invest, SUM(post_meta.meta_value) AS sum_invest, ";
			$sql .= "(SELECT COUNT(jycrois.campaign_id) FROM ".$table_jcrois." jycrois WHERE jycrois.user_id = " .$user_wpid. ") AS nb_follow, ";
			$sql .= "(SELECT COUNT(vote.post_id) FROM ".$table_vote." vote WHERE vote.user_id = " .$user_wpid. ") AS nb_votes ";
		$sql .= "FROM ".$wpdb->postmeta." post_meta ";
		$sql .= "LEFT JOIN ".$wpdb->posts." post ON post.ID = post_meta.post_id ";
		$sql .= "LEFT JOIN ".$wpdb->postmeta." post_meta2 ON post_meta.post_id = post_meta2.post_id ";
		$sql .= "WHERE post.post_type='edd_payment' AND post.post_status='publish' AND post_meta.meta_key = '_edd_payment_total' ";
		$sql .= "AND post_meta2.meta_key = '_edd_payment_user_id' AND post_meta2.meta_value = " . $user_wpid;
		$user_results = $wpdb->get_results( $sql );
		$user_result = $user_results[0];
		
		$parameters = array(
			self::$key_wpid					=> $user_wpid,
			self::$key_apiid				=> $entity_apiid,
			self::$key_name					=> html_entity_decode( $entity_name ),
			self::$key_email				=> $entity_email,
			self::$key_gender				=> $entity_gender,
			self::$key_birthday_date		=> $entity_birthday_date,
			self::$key_birthday_year		=> $entity_birthday_year,
			self::$key_age					=> $entity_age,
			self::$key_address				=> html_entity_decode( $entity_address ),
			self::$key_postal_code			=> $entity_postal_code,
			self::$key_city					=> html_entity_decode( $entity_city ),
			self::$key_department			=> $entity_department,
			self::$key_country				=> $entity_country,
			self::$key_count_project_followed		=> $user_result->nb_follow,
			self::$key_count_project_voted			=> $user_result->nb_votes,
			self::$key_count_project_invested		=> $user_result->nb_invest,
			self::$key_amount_project_invested		=> $user_result->sum_invest
		);
		return WDGAirtable::create_or_update_line( self::$key_wpid, $user_wpid, $parameters );
	}
	
}
