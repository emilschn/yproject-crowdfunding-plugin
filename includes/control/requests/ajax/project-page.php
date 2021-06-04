<?php
/**
 * Gestion des appels Ajax en provenance de la page projet
 */
class WDGAjaxActionsProjectPage {
	/**
	 * Enregistre la petite image et/ou url de la vidéo
	 */
	public static function save_image_url_video() {
		$campaign_id = filter_input( INPUT_POST, 'campaign_id' );
		$url_video = filter_input( INPUT_POST, 'url_video' );
		$image = $_FILES[ 'image_video_zone' ];

		echo WDGFormProjects::edit_image_url_video( $image, $url_video, $campaign_id );
		exit();
	}

	public static function send_project_notification() {
		$id_campaign = filter_input( INPUT_POST, 'id_campaign' );
		$is_for_project = filter_input( INPUT_POST, 'is_for_project' );

		$buffer = FALSE;
		if ( $is_for_project ) {
			$buffer = WDGCampaignNotifications::send_has_finished_proofreading( $id_campaign );
		} else {
			$buffer = WDGCampaignNotifications::ask_proofreading( $id_campaign );
		}

		if ( $buffer ) {
			exit( '1' );
		} else {
			exit( '0' );
		}
	}

	public static function remove_project_cache() {
		$id_campaign = filter_input( INPUT_POST, 'id_campaign' );
		$campaign = new ATCF_Campaign( $id_campaign );

		$file_cacher = WDG_File_Cacher::current();
		$file_cacher->delete( $campaign->data->post_name );

		$db_cacher = WDG_Cache_Plugin::current();
		$db_cacher->set_cache( 'cache_campaign_' . $id_campaign, '0', 1, 1 );

		WDGQueue::add_cache_post_as_html( $id_campaign, 'date', 'PT50M' );

		exit( '1' );
	}

	public static function remove_project_lang() {
		// Vérification que l'utilisateur peut supprimer la langue
		$id_campaign = filter_input( INPUT_POST, 'id_campaign' );
		$campaign = new ATCF_Campaign( $id_campaign );
		if ( $campaign->current_user_can_edit() ) {
			// Suppression de la langue dans la liste
			$lang = filter_input( INPUT_POST, 'lang' );
			$lang_list = $campaign->get_lang_list();
			foreach ( $lang_list as $key => $lang_item_id ) {
				if ( $lang == $lang_item_id ) {
					array_splice( $lang_list, $key, 1 );
					break;
				}
			}
			update_post_meta( $id_campaign, ATCF_Campaign::$key_meta_lang, json_encode( $lang_list ) );

			// Suppression des meta associées à la langue
			delete_post_meta( $id_campaign, ATCF_Campaign::$key_google_doc . '_' . $lang );
			delete_post_meta( $id_campaign, ATCF_Campaign::$key_logbook_google_doc . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_subtitle' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_summary' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_rewards' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_description' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_added_value' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_development_strategy' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_economic_model' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_measuring_impact' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_implementation' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_impact_area' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_societal_challenge' . '_' . $lang );
			delete_post_meta( $id_campaign, 'campaign_video' . '_' . $lang );
		}
	}
	
	public static function get_current_project_infos() {
		$buffer = '0';

		if ( is_user_logged_in() ) {
			$response = array();

			$response[ 'scripts' ] = array();
			$response[ 'context' ] = array();
			$input_pageinfo = filter_input( INPUT_POST, 'pageinfo' );
			if ( !empty( $input_pageinfo ) ) {
				$current_campaign = new ATCF_Campaign( $input_pageinfo );
				if ( $current_campaign->current_user_can_edit() ) {
					$project_editor_script_url = dirname( get_bloginfo('stylesheet_url') ). '/_inc/js/wdg-project-editor.js?d=' .time();
					array_push( $response[ 'scripts' ], $project_editor_script_url );
					$response[ 'context' ][ 'dashboard_url' ] = WDG_Redirect_Engine::override_get_page_url( 'tableau-de-bord' ) . '?campaign_id=' . $current_campaign->ID;
				}
			}

			$buffer = json_encode( $response );
		}

		echo $buffer;
		exit();
	}
}