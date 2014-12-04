<?php
/**
 * Campaigns
 *
 * All data related to campaigns. This includes wrangling various EDD
 * things, adding extra stuff, etc. There are two main classes:
 *
 * ATCF_Campaigns - Mostly admin things, and changing some settings of EDD
 * ATCF_Campaign  - A singular campaign. Includes getter methods for accessing a single campaign's info
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Global Campaigns *******************************************************/

/** Start me up! */
$atcf_campaigns = new ATCF_Campaigns;

class ATCF_Campaigns {

	/**
	 * Start things up.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup' ), 1 );
	}

	/**
	 * Some basic tweaking.
	 *
	 * Set the archive slug, and remove formatting from prices.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function setup() {
		define( 'EDD_SLUG', apply_filters( 'atcf_edd_slug', 'campaigns' ) );
		
		add_filter( 'edd_download_labels', array( $this, 'download_labels' ) );
		add_filter( 'edd_default_downloads_name', array( $this, 'download_names' ) );
		add_filter( 'edd_download_supports', array( $this, 'download_supports' ) );

		do_action( 'atcf_campaigns_actions' );
		
		if ( ! is_admin() )
			return;
		
		add_filter( 'edd_price_options_heading', 'atcf_edd_price_options_heading' );
		add_filter( 'edd_variable_pricing_toggle_text', 'atcf_edd_variable_pricing_toggle_text' );

		add_filter( 'manage_edit-download_columns', array( $this, 'dashboard_columns' ), 11, 1 );
		add_filter( 'manage_download_posts_custom_column', array( $this, 'dashboard_column_item' ), 11, 2 );
		
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 11 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_filter( 'edd_metabox_fields_save', array( $this, 'meta_boxes_save' ) );
		add_filter( 'edd_metabox_save_campaign_end_date', 'atcf_campaign_save_end_date' );

		add_action( 'edd_download_price_table_head', 'atcf_pledge_limit_head' );
		add_action( 'edd_download_price_table_row', 'atcf_pledge_limit_column', 10, 3 );

		add_action( 'admin_action_atcf-collect-funds', array( $this, 'collect_funds' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );

		do_action( 'atcf_campaigns_actions_admin' );
	}

	/**
	 * Download labels. Change it to "Campaigns".
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $labels The preset labels
	 * @return array $labels The modified labels
	 */
	function download_labels( $labels ) {
		$labels =  apply_filters( 'atcf_campaign_labels', array(
			'name' 				=> __( 'Campaigns', 'atcf' ),
			'singular_name' 	=> __( 'Campaign', 'atcf' ),
			'add_new' 			=> __( 'Add New', 'atcf' ),
			'add_new_item' 		=> __( 'Add New Campaign', 'atcf' ),
			'edit_item' 		=> __( 'Edit Campaign', 'atcf' ),
			'new_item' 			=> __( 'New Campaign', 'atcf' ),
			'all_items' 		=> __( 'All Campaigns', 'atcf' ),
			'view_item' 		=> __( 'View Campaign', 'atcf' ),
			'search_items' 		=> __( 'Search Campaigns', 'atcf' ),
			'not_found' 		=> __( 'No Campaigns found', 'atcf' ),
			'not_found_in_trash'=> __( 'No Campaigns found in Trash', 'atcf' ),
			'parent_item_colon' => '',
			'menu_name' 		=> __( 'Campaigns', 'atcf' )
		) );

		return $labels;
	}

	/**
	 * Further change "Download" & "Downloads" to "Campaign" and "Campaigns"
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $labels The preset labels
	 * @return array $labels The modified labels
	 */
	function download_names( $labels ) {
		$cpt_labels = $this->download_labels( array() );

		$labels = array(
			'singular' => $cpt_labels[ 'singular_name' ],
			'plural'   => $cpt_labels[ 'name' ]
		);

		return $labels;
	}

	/**
	 * Add excerpt support for downloads.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $supports The post type supports
	 * @return array $supports The modified post type supports
	 */
	function download_supports( $supports ) {
		$supports[] = 'excerpt';
		$supports[] = 'comments';

		return $supports;
	}

	/**
	 * Download Columns
	 *
	 * Add "Amount Funded" and "Expires" to the main campaign table listing. 
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $supports The post type supports
	 * @return array $supports The modified post type supports
	 */
	function dashboard_columns( $columns ) {
		$columns = apply_filters( 'atcf_dashboard_columns', array(
			'cb'                => '<input type="checkbox"/>',
			'title'             => __( 'Name', 'atcf' ),
			'type'              => __( 'Type', 'atcf' ),
			'backers'           => __( 'Backers', 'atcf' ),
			'funded'            => __( 'Amount Funded', 'atcf' ),
			'expires'           => __( 'Days Remaining', 'atcf' )
		) );

		return $columns;
	}

	/**
	 * Download Column Items
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $supports The post type supports
	 * @return array $supports The modified post type supports
	 */
	function dashboard_column_item( $column, $post_id ) {
		$campaign = atcf_get_campaign( $post_id );

		switch ( $column ) {
			case 'funded' :
				printf( _x( '%s of %s', 'funded of goal', 'atcf' ), $campaign->current_amount(true), $campaign->goal(true) );

				break;
			case 'expires' : 
				echo $campaign->days_remaining();

				break;
			case 'type' :
				echo ucfirst( $campaign->type() );

				break;
			case 'backers' :
				echo $campaign->backers_count();

				break;
			default : 
				break;
		}
	}

	/**
	 * Remove some metaboxes that we don't need to worry about. Sales
	 * and download stats, aren't really important. 
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function remove_meta_boxes() {
		$boxes = array( 
			'edd_file_download_log' => 'normal',
			'edd_purchase_log'      => 'normal',
			'edd_download_stats'    => 'side'
		);

		foreach ( $boxes as $box => $context ) {
			remove_meta_box( $box, 'download', $context );
		}
	}

	/**
	 * Add our custom metaboxes.
	 *
	 * - Collect Funds
	 * - Campaign Stats
	 * - Campaign Video
	 *
	 * As well as some other information plugged into EDD in the Download Configuration
	 * metabox that already exists.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function add_meta_boxes() {
		global $post;

		if ( ! is_object( $post ) )
			return;

		$campaign = atcf_get_campaign( $post );

		if ( ! $campaign->is_collected() && ( 'flexible' == $campaign->type() || $campaign->is_funded() ) && atcf_has_preapproval_gateway() )
			add_meta_box( 'atcf_campaign_funds', __( 'Campaign Funds', 'atcf' ), '_atcf_metabox_campaign_funds', 'download', 'side', 'high' );

		add_meta_box( 'atcf_campaign_stats', __( 'Campaign Stats', 'atcf' ), '_atcf_metabox_campaign_stats', 'download', 'side', 'high' );
		add_meta_box( 'atcf_campaign_vote', 'Statut de la campagne', '_atcf_metabox_campaign_vote', 'download', 'side', 'high' );
		add_meta_box( 'atcf_campaign_date_vote', 'Date de la fin des votes', '_atcf_metabox_campaign_date_vote', 'download', 'side', 'high' );
		
		
		add_meta_box( 'atcf_campaign_video', __( 'Campaign Video', 'atcf' ), '_atcf_metabox_campaign_video', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_summary', 'Résumé', '_atcf_metabox_campaign_summary', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_added_value', 'Opportunité économique', '_atcf_metabox_campaign_added_value', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_societal_challenge', 'Utilité sociétale', '_atcf_metabox_campaign_societal_challenge', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_economic_model', 'Modèle économique', '_atcf_metabox_campaign_economic_model', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_implementation', 'Qui porte le projet ?', '_atcf_metabox_campaign_implementation', 'download', 'normal', 'high' );
		
		add_meta_box( 'atcf_campaign_google_doc', 'ID du doc google', '_atcf_metabox_campaign_google_doc', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_contract_title', 'Titre du contrat', '_atcf_metabox_campaign_contract_title', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_company_name', 'Nom de la société', '_atcf_metabox_campaign_company_name', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_investment_terms', 'Modalités d&apos;investissement', '_atcf_metabox_campaign_investment_terms', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_subscription_params', 'Paramètres de souscriptions (apports, domicile, ...)', '_atcf_metabox_campaign_subscription_params', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_powers_params', 'Paramètres de pouvoirs (déposer, signer, ...)', '_atcf_metabox_campaign_powers_params', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_constitution_terms', 'Modalités de constitutions', '_atcf_metabox_campaign_constitution_terms', 'download', 'normal', 'high' );
		
		add_action( 'edd_meta_box_fields', '_atcf_metabox_campaign_info', 5 );
	}

	/**
	 * Campaign Information
	 *
	 * Hook in to EDD and add a few more things that will be saved. Use
	 * this so we are already cleared/validated. 
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $fields An array of fields to save
	 * @return array $fields An updated array of fields to save
	 */
	function meta_boxes_save( $fields ) {
		$fields[] = '_campaign_featured';
		$fields[] = '_campaign_physical';
		$fields[] = 'campaign_goal';
		$fields[] = 'campaign_minimum_goal';
		$fields[] = 'campaign_part_value';
		$fields[] = 'campaign_contact_email';
		$fields[] = 'campaign_end_date';
		$fields[] = 'campaign_vote';
		$fields[] = 'campaign_start_vote';
		$fields[] = 'campaign_end_vote';
		$fields[] = 'campaign_video';
		$fields[] = 'campaign_images';
		$fields[] = 'campaign_location';
		$fields[] = 'campaign_author';
		$fields[] = 'campaign_type';
		$fields[] = 'campaign_owner';
		$fields[] = 'campaign_summary';
		$fields[] = 'campaign_societal_challenge';
		$fields[] = 'campaign_google_doc';
		$fields[] = 'campaign_contract_title';
		$fields[] = 'campaign_company_name';
		$fields[] = 'campaign_company_status';
		$fields[] = 'campaign_company_status_other';
		$fields[] = 'campaign_init_capital';
		$fields[] = 'campaign_funding_type';
		$fields[] = 'campaign_funding_duration';
		$fields[] = 'campaign_added_value';
		$fields[] = 'campaign_economic_model';
		$fields[] = 'campaign_implementation';
		$fields[] = 'campaign_investment_terms';
		$fields[] = 'campaign_subscription_params';
		$fields[] = 'campaign_powers_params';
		$fields[] = 'campaign_constitution_terms';

//		$fields[] = 'campaign_updates';
//		$fields[] = 'campaign_impact_area';
//		$fields[] = 'campaign_development_strategy';
//		$fields[] = 'campaign_measuring_impact';
		

		return $fields;
	}

	/**
	 * Collect Funds
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function collect_funds() {
		global $edd_options, $errors;

		$campaign = absint( $_GET[ 'campaign' ] );
		$campaign = atcf_get_campaign( $campaign );

		/** check nonce */
		if ( ! check_admin_referer( 'atcf-collect-funds' ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
			exit();
		}

		/** check roles */
		if ( ! current_user_can( 'update_core' ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 12 ), admin_url( 'post.php' ) ) );
			exit();
		}

		$backers  = $campaign->backers();
		$gateways = edd_get_enabled_payment_gateways(); 
		$errors   = new WP_Error();

		if ( empty( $backers ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 14 ), admin_url( 'post.php' ) ) );
			exit();
		}

		foreach ( $backers as $backer ) {
			$payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			$gateway    = get_post_meta( $payment_id, '_edd_payment_gateway', true );

			$gateways[ $gateway ][ 'payments' ][] = $payment_id;
		}

		foreach ( $gateways as $gateway => $gateway_args ) {
			do_action( 'atcf_collect_funds_' . $gateway, $gateway, $gateway_args, $campaign, $errors );
		}

		if ( ! empty ( $errors->errors ) )
			wp_die( $errors );
		else {
			update_post_meta( $campaign->ID, '_campaign_expired', current_time( 'mysql' ) );
			update_post_meta( $campaign->ID, '_campaign_bulk_collected', 1 );

			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 13, 'collected' => $campaign->backers_count() ), admin_url( 'post.php' ) ) );
			exit();
		}
	}

	/**
	 * Custom messages for various actions when managing campaigns.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $messages An array of messages to display
	 * @return array $messages An updated array of messages to display
	 */
	function messages( $messages ) {
		$messages[ 'download' ][11] = sprintf( __( 'This %s has not reached its funding goal.', 'atcf' ), strtolower( edd_get_label_singular() ) );
		$messages[ 'download' ][12] = sprintf( __( 'You do not have permission to collect funds for %s.', 'atcf' ), strtolower( edd_get_label_plural() ) );
		$messages[ 'download' ][13] = sprintf( __( '%d payments have been collected for this %s.', 'atcf' ), isset ( $_GET[ 'collected' ] ) ? $_GET[ 'collected' ] : 0, strtolower( edd_get_label_singular() ) );
		$messages[ 'download' ][14] = sprintf( __( 'There are no payments for this %s.', 'atcf' ), strtolower( edd_get_label_singular() ) );

		return $messages;
	}
}

/**
 * Filter the expiration date for a campaign.
 *
 * A hidden/fake input field so the filter is triggered, then
 * add all the other date fields together to create the MySQL date.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @param string $date
 * @return string $end_date Formatted date
 */
function atcf_campaign_save_end_date( $new ) {
	if ( ! isset( $_POST[ 'end-aa' ] ) )
		return;
	
	date_default_timezone_set("Europe/Paris");
	$aa = $_POST['end-aa'];
	$mm = $_POST['end-mm'];
	$jj = $_POST['end-jj'];
	$hh = $_POST['end-hh'];
	$mn = $_POST['end-mn'];
	$ss = $_POST['end-ss'];

	$aa = ($aa <= 0 ) ? date('Y') : $aa;
	$mm = ($mm <= 0 ) ? date('n') : $mm;
	$jj = ($jj > 31 ) ? 31 : $jj;
	$jj = ($jj <= 0 ) ? date('j') : $jj;

	$hh = ($hh > 23 ) ? $hh -24 : $hh + 1; //Pourquoi y'a-t-il besoin d'un +1 ? Bonne question...
	$mn = ($mn > 59 ) ? $mn -60 : $mn;
	$ss = ($ss > 59 ) ? $ss -60 : $ss;

	$end_date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
	
	$valid_date = wp_checkdate( $mm, $jj, $aa, $end_date );
	
	if ( ! $valid_date ) {
		return new WP_Error( 'invalid_date', __( 'Whoops, the provided date is invalid.', 'atcf' ) );
	}

	$end_date = get_gmt_from_date( $end_date );

	return $end_date;
}



/**
 * Price row head
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function atcf_pledge_limit_head() {
?>
	<th style="width: 30px"><?php _e( 'Limit', 'edd' ); ?></th>
	<th style="width: 30px"><?php _e( 'Purchased', 'edd' ); ?></th>
<?php
}

/**
 * Price row columns
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function atcf_pledge_limit_column( $post_id, $key, $args ) {
?>
	<td>
		<input type="text" class="edd_repeatable_name_field" name="edd_variable_prices[<?php echo $key; ?>][limit]" id="edd_variable_prices[<?php echo $key; ?>][limit]" value="<?php echo isset ( $args[ 'limit' ] ) ? $args[ 'limit' ] : null; ?>" style="width:100%" />
	</td>
	<td>
		<input type="text" class="edd_repeatable_name_field" name="edd_variable_prices[<?php echo $key; ?>][bought]" id="edd_variable_prices[<?php echo $key; ?>][bought]" value="<?php echo isset ( $args[ 'bought' ] ) ? $args[ 'bought' ] : null; ?>" readonly style="width:100%" />
	</td>
<?php
}

/**
 * Price row fields
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function atcf_price_row_args( $args, $value ) {
	$args[ 'limit' ] = isset( $value[ 'limit' ] ) ? $value[ 'limit' ] : '';
	$args[ 'bought' ] = isset( $value[ 'bought' ] ) ? $value[ 'bought' ] : 0;

	return $args;
}
add_filter( 'edd_price_row_args', 'atcf_price_row_args', 10, 2 );

/**
 * Campaign Stats Box
 *
 * These are read-only stats/info for the current campaign.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_stats() {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_stats_before', $campaign );
?>
	<p>
		<strong><?php _e( 'Current Amount:', 'atcf' ); ?></strong>
		<?php echo $campaign->current_amount(); ?> &mdash; <?php echo $campaign->percent_completed(); ?>
	</p>

	<p>
		<strong><?php _e( 'Backers:' ,'atcf' ); ?></strong>
		<?php echo $campaign->backers_count(); ?>
	</p>

	<p>
		<strong><?php _e( 'Days Remaining:', 'atcf' ); ?></strong>
		<?php echo $campaign->days_remaining(); ?>
	</p>
<?php
	do_action( 'atcf_metabox_campaign_stats_after', $campaign );
}

/**
 * Campaign Collect Funds Box
 *
 * If a campaign is fully funded (or expired and fully funded) show this box.
 * Includes a button to collect funds.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_funds() {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_funds_before', $campaign );
?>
	<?php if ( 'fixed' == $campaign->type() ) : ?>
	<p><?php printf( __( 'This %1$s has reached its funding goal. You may now send the funds to the owner. This will end the %1$s.', 'atcf' ), strtolower( edd_get_label_singular() ) ); ?></p>
	<?php else : ?>
	<p><?php printf( __( 'This %1$s is flexible. You may collect the funds at any time. This will end the %1$s.', 'atcf' ), strtolower( edd_get_label_singular() ) ); ?></p>
	<?php endif; ?>

	<p><a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'atcf-collect-funds', 'campaign' => $campaign->ID ), admin_url() ), 'atcf-collect-funds' ); ?>" class="button button-primary"><?php _e( 'Collect Funds', 'atcf' ); ?></a></p>
<?php
	do_action( 'atcf_metabox_campaign_funds_after', $campaign );
}

/**
 * Campaign Video Box
 *
 * oEmbed campaign video.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_video() {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_video_before', $campaign );
?>
	<input type="text" name="campaign_video" id="campaign_video" class="widefat" value="<?php echo esc_url( $campaign->video() ); ?>" />
	<p class="description"><?php _e( 'oEmbed supported video links.', 'atcf' ); ?></p>
<?php
	do_action( 'atcf_metabox_campaign_video_after', $campaign );
}


function _atcf_metabox_campaign_vote() {
	global $post;

	$campaign = atcf_get_campaign( $post );
	do_action( 'atcf_metabox_campaign_vote_before', $campaign );
?>  
	<p>Choisir le statut de la campagne</p>
	<select id="campaign_vote" name="campaign_vote" class="regular-text" style="width:200px;">
	    <option></option>
	    <option <?php if ($campaign->vote() == "preparing") { ?>selected="selected"<?php } ?> value="preparing">Préparation</option>
	    <option <?php if ($campaign->vote() == "preview") { ?>selected="selected"<?php } ?> value="preview">Avant-première</option>
	    <option <?php if ($campaign->vote() == "vote") { ?>selected="selected"<?php } ?>value="vote">En cours de vote</option>
	    <option <?php if ($campaign->vote() == "collecte") { ?>selected="selected"<?php } ?> value="collecte">En cours de collecte</option>
	    <option <?php if ($campaign->vote() == "funded") { ?>selected="selected"<?php } ?>value="funded">Terminé</option>
	    <option <?php if ($campaign->vote() == "archive") { ?>selected="selected"<?php } ?>value="archive">Archivé</option>
	</select>
	
<?php
	do_action( 'atcf_metabox_campaign_vote_after', $campaign );
}

// CHOIX DES DATES DES VOTES
function _atcf_metabox_campaign_date_vote() {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_start_vote_before', $campaign );
?>  
    
	<p>
	    Date de la fin :<br />
	    <input type="date" name="campaign_end_vote" id="campaign_end_vote" value="<?php echo $campaign->end_vote_date(); ?>">
	</p>
<?php
	do_action( 'atcf_metabox_campaign_start_vote_after', $campaign );
}




function _atcf_metabox_campaign_summary() {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_summary_before', $campaign );
?>
		<p class="summary">	<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->summary()) : '', 'campaign_summary', apply_filters( 'atcf_submit_field_summary_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => true,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?></p>
<?php
	do_action( 'atcf_metabox_campaign_summary_after', $campaign );
}

function _atcf_metabox_campaign_impact_area () {
	global $post;

	$campaign = atcf_get_campaign( $post );
	do_action( 'atcf_metabox_campaign_impact_area_before', $campaign );
?>
	<p class="impact_area">
		<textarea name="impact_area" id="impact_area" class="widefat"><?php echo $campaign->impact_area(); ?></textarea>
	</p>
<?php
	do_action( 'atcf_metabox_campaign_impact_area_after', $campaign );
}

function _atcf_metabox_campaign_company_status() {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<p class="company_status">
		<textarea name="company_status" id="company_status" class="widefat"><?php echo $campaign->company_status(); ?></textarea>
	</p>
<?php
    
}

function _atcf_metabox_campaign_company_status_other() {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<p class="company_status_other">
		<textarea name="company_status_other" id="company_status_other" class="widefat"><?php echo $campaign->company_status_other(); ?></textarea>
	</p>
<?php
    
}

function _atcf_metabox_campaign_init_capital() {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<p class="init_capital">
		<textarea name="init_capital" id="init_capital" class="widefat"><?php echo $campaign->init_capital(); ?></textarea>
	</p>
<?php
    
}

function _atcf_metabox_campaign_funding_type() {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<p class="funding_type">
		<textarea name="funding_type" id="funding_type" class="widefat"><?php echo $campaign->funding_type(); ?></textarea>
	</p>
<?php
    
}

function _atcf_metabox_campaign_funding_duration() {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<p class="funding_duration">
		<textarea name="funding_duration" id="funding_duration" class="widefat"><?php echo $campaign->funding_duration(); ?></textarea>
	</p>
<?php
    
}

function _atcf_metabox_campaign_societal_challenge() {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_societal_challenge_before', $campaign );
?>
		<p class="atcf_metabox_campaign-societal_challenge">	
		<?php 
			wp_editor( $campaign ? html_entity_decode($campaign->societal_challenge()) : '', 'campaign_societal_challenge', apply_filters( 'atcf_metabox_field_societal_challenge_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => true,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?></p>
<?php
	do_action( 'atcf_metabox_campaign_societal_challenge_after', $campaign );
}

/**
 * Campaign Valeur ajout�e
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
 function _atcf_metabox_campaign_added_value( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_added_value_before', $campaign );
?>
	<div class="atcf-metabox-campaign_added_value">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->added_value()) : '', 'campaign_added_value', apply_filters( 'atcf_metabox_field_added_value_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
	do_action( 'atcf_metabox_campaign_added_value_after', $campaign );
}



/**
 * Campaign Strat�gie de d�veloppement
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */

 
 function _atcf_metabox_campaign_development_strategy( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_development_strategy_before', $campaign );
?>
	<div class="atcf-metabox-campaign-development_strategy">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->development_strategy()) : '', 'campaign_development_strategy', apply_filters( 'atcf_metabox_field_development_strategy_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php

	do_action( 'atcf_metabox_campaign_development_strategy_after', $campaign );
}



/**
 * Campaign Modele economique
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
  function _atcf_metabox_campaign_economic_model( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_economic_model_before', $campaign );
?>
	<div class="atcf-metabox-campaign_economic_model">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->economic_model()) : '', 'campaign_economic_model', apply_filters( 'atcf_metabox_field_economic_model_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php

	do_action( 'atcf_metabox_campaign_economic_model_after', $campaign );
}


/**
 * Campaign Mesure d�impact
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
function _atcf_metabox_campaign_measuring_impact( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_measuring_impact_before', $campaign );
?>
	<div class="atcf-metabox-campaign-measuring_impact">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->measuring_impact()) : '', 'campaign_measuring_impact', apply_filters( 'atcf_metabox_field_measuring_impact_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php

	do_action( 'atcf_metabox_campaign_measuring_impact_after', $campaign );
}


/**
 * Campaign Mise en oeuvre
 *implementation
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
   function _atcf_metabox_campaign_implementation( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_implementation_before', $campaign );
?>
	<div class="atcf-metabox-campaign-implementation">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->implementation()) : '', 'campaign_implementation', apply_filters( 'atcf_metabox_field_implementation_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php

	do_action( 'atcf_metabox_campaign_implementation_after', $campaign );
}

function _atcf_metabox_campaign_google_doc() {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<p class="campaign_google_doc">
		<textarea name="campaign_google_doc" id="campaign_google_doc" class="widefat"><?php echo $campaign->google_doc(); ?></textarea>
	</p>
<?php
}

function _atcf_metabox_campaign_contract_title() {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<p class="campaign_contract_title">
		<textarea name="campaign_contract_title" id="campaign_contract_title" class="widefat"><?php echo $campaign->contract_title(); ?></textarea>
	</p>
<?php
}


function _atcf_metabox_campaign_company_name() {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<p class="campaign_company_name">
		<textarea name="campaign_company_name" id="campaign_company_name" class="widefat"><?php echo $campaign->company_name(); ?></textarea>
	</p>
<?php
}

function _atcf_metabox_campaign_investment_terms( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<div class="atcf-metabox-campaign-investment_terms">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->investment_terms()) : '', 'campaign_investment_terms', apply_filters( 'atcf_metabox_field_investment_terms_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}

function _atcf_metabox_campaign_subscription_params( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<div class="atcf-metabox-campaign-subscription_params">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->subscription_params()) : '', 'campaign_subscription_params', apply_filters( 'atcf_metabox_field_subscription_params_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}

function _atcf_metabox_campaign_powers_params( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<div class="atcf-metabox-campaign-powers_params">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->powers_params()) : '', 'campaign_powers_params', apply_filters( 'atcf_metabox_field_powers_params_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}

function _atcf_metabox_campaign_constitution_terms( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<div class="atcf-metabox-campaign-constitution_terms">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->constitution_terms()) : '', 'campaign_constitution_terms', apply_filters( 'atcf_metabox_field_constitution_terms_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}


/**
 * Campaign Updates Box
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function _atcf_metabox_campaign_updates() {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_updates_before', $campaign );
?>
	<textarea name="campaign_updates" rows="4" class="widefat"><?php echo esc_textarea( $campaign->updates() ); ?></textarea>
	<p class="description"><?php _e( 'Notes and updates about the campaign.', 'atcf' ); ?></p>
<?php
	do_action( 'atcf_metabox_campaign_updates_after', $campaign );
}


/**
 * Goal Save
 *
 * Sanitize goal before it is saved, to remove commas.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return string $price The formatted price
 */
add_filter( 'edd_metabox_save_campaign_goal', 'edd_sanitize_price_save' );
add_filter( 'edd_metabox_save_campaign_minimum_goal', 'edd_sanitize_price_save' );

/**
 * Updates Save
 *
 * EDD trys to escape this data, and we don't want that.
 *
 * @since Appthemer CrowdFunding 0.9
 */
function atcf_sanitize_campaign_updates( $updates ) {
	$updates = $_POST[ 'campaign_updates' ];
	$updates = wp_kses_post( $updates );

	return $updates;
}
add_filter( 'edd_metabox_save_campaign_updates', 'atcf_sanitize_campaign_updates' );

/** Single Campaign *******************************************************/

class ATCF_Campaign {
	public $ID;
	public $data;
	
	public static $status_list = array(
		'preparing' => 'Pr&eacute;paration',
		'preview'   => 'Avant-premi&egrave;re',
		'vote'	    => 'Vote',
		'collecte'  => 'Collecte',
		'funded'  => 'Termin&eacute',
		'archive'  => 'Archiv&eacute'
	);

	function __construct( $post ) {
		$this->data = get_post( $post );
		$this->ID   = $this->data->ID;
	}

	/**
	 * Getter
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param string $key The meta key to fetch
	 * @return string $meta The fetched value
	 */
	public function __get( $key ) {
		$meta = apply_filters( 'atcf_campaign_meta_' . $key, $this->data->__get( $key ) );

		return $meta;
	}
	
	public static $key_edit_version = 'campaign_edit_version';
	public function edit_version() {
		$version = $this->__get(ATCF_Campaign::$key_edit_version);
		if (!isset($version) || !is_numeric($version) || $version < 1) { $version = 1; }
		return $version;
	}

	/**
	 * Campaign Featured
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Featured
	 */
	public function featured() {
		return $this->__get( '_campaign_featured' );
	}
	
	public function subtitle() {
		return $this->__get( 'campaign_subtitle' );
	}

	
	//Description projet
	public function summary() {
		return $this->__get( 'campaign_summary' );
	}
	public function added_value() {
		return $this->__get( 'campaign_added_value' );
	}
	public function development_strategy() {
		return $this->__get( 'campaign_development_strategy' );
	}
	public function economic_model() {
		return $this->__get( 'campaign_economic_model' );
	}
	public function measuring_impact() {
		return $this->__get( 'campaign_measuring_impact' );
	}
	public function implementation() {
		return $this->__get( 'campaign_implementation' );
	}
	public function impact_area() {
		return $this->__get( 'campaign_impact_area' );
	}
	public function societal_challenge() {
		return $this->__get( 'campaign_societal_challenge' );
	}
	
	public function google_doc() {
		return $this->__get('campaign_google_doc');
	}
	//Ajouts contrat
	public function contract_title() {
		return $this->__get('campaign_contract_title');
	}
	public function investment_terms() {
		return $this->__get('campaign_investment_terms');
	}
	public function subscription_params() {
		return $this->__get('campaign_subscription_params');
	}
	public function powers_params() {
		return $this->__get('campaign_powers_params');
	}
	public function constitution_terms() {
		return $this->__get('campaign_constitution_terms');
	}
	
	public function company_name() {
	    return $this->__get('campaign_company_name');
	}
	public function company_status() {
	    return $this->__get('campaign_company_status');
	}
	public function company_status_other() {
	    return $this->__get('campaign_company_status_other');
	}
	public function init_capital() {
	    return $this->__get('campaign_init_capital');
	}
	public function funding_type() {
	    return $this->__get('campaign_funding_type');
	}
	public function funding_duration() {
	    return $this->__get('campaign_funding_duration');
	}

	/**
	 * Needs Shipping
	 *
	 * @since Appthemer CrowdFunding 0.9
	 *
	 * @return sting Requires Shipping
	 */
	public function needs_shipping() {
		$physical = $this->__get( '_campaign_physical' );

		return apply_filters( 'atcf_campaign_needs_shipping', $physical, $this );
	}

	public function is_flexible() {
	    return ($this->minimum_goal() != $this->goal());
	}
	
	/**
	 * Campaign Goal
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $goal A goal amount (formatted or not)
	 */
	public function goal( $formatted = true ) {
		$goal = $this->__get( 'campaign_goal' );

		if ( ! is_numeric( $goal ) )
			return 0;

		if ( $formatted ) {
		    $currency = edd_get_currency();
		    if ($currency == "EUR") {
			if (strpos($goal, '.00') !== false) $goal = substr ($goal, 0, -3);
			return $goal . ' &euro;';
		    } else {
			return edd_currency_filter( edd_format_amount( $goal ) );
		    }
		}

		return $goal;
	}
	
	public function minimum_goal($formatted = false) {
	    $goal = $this->__get( 'campaign_minimum_goal' );
	    if (strpos($goal, '.00') !== false) $goal = substr ($goal, 0, -3);
	    if ( ! is_numeric( $goal ) && ($this->type() != 'flexible') )
		    $goal = 0;
	    if ($goal == 0) $goal = $this->goal(false);
	    if ($formatted) $goal .= ' &euro;';
	    return $goal;
	}
	
	public function part_value() {
	    $part_value = $this->__get( 'campaign_part_value' );
	    if ( ! is_numeric( $part_value ) )
		    return 0;
	    return $part_value;
	}
	
	public function total_minimum_parts() {
	    return round($this->minimum_goal() / $this->part_value());
	}
	
	public function total_parts() {
	    return round($this->goal(false) / $this->part_value());
	}

	/**
	 * Campaign Type
	 *
	 * @since Appthemer CrowdFunding 0.7
	 *
	 * @return string $type The type of campaign
	 */
	public function type() {
		$type = $this->__get( 'campaign_type' );

		if ( ! $type )
			$type = atcf_campaign_type_default();

		return $type;
	}

	/**
	 * Campaign Location
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Location
	 */
	public function location() {
		return $this->__get( 'campaign_location' );
	}

	/**
	 * Campaign Author
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Author
	 */
	public function author() {
		return $this->__get( 'campaign_author' );
	}

	/**
	 * Campaign Contact Email
	 *
	 * @since Appthemer CrowdFunding 0.5
	 *
	 * @return sting Campaign Contact Email
	 */
	public function contact_email() {
		return $this->__get( 'campaign_contact_email' );
	}

	/**
	 * Campaign End Date
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign End Date
	 */
	public function end_date() {
		return mysql2date( 'Y-m-d H:i:s', $this->__get( 'campaign_end_date' ), false );
	}

	public function start_vote() {
		return mysql2date( 'Y-m-d H:i:s', $this->__get( 'campaign_start_vote' ), false);
	}

	public function end_vote() {
		return mysql2date( 'Y-m-d H:i:s', $this->__get( 'campaign_end_vote' ), false);
	}

	public function end_vote_date() {
		return mysql2date( 'Y-m-d', $this->__get( 'campaign_end_vote' ), false);
	}
	public function end_vote_date_home() {
		setlocale(LC_TIME, array('fr_FR.UTF-8', 'fr_FR.UTF-8', 'fra'));
		return  utf8_encode(strftime("%d %B", strtotime(mysql2date( 'm/d', $this->__get( 'campaign_end_vote' ), false))));
	}
	public function end_vote_remaining() {
	    $dateJour = strtotime(date("d-m-Y"));
	    $fin = strtotime($this->__get( 'campaign_end_vote' ));
	    $buffer = round(($fin - $dateJour) / 60 / 60 / 24);
	    $buffer = max(0, $buffer);
	    return $buffer;
	}
	
	public function nb_voters() {
	    global $wpdb;
	    $table_name = $wpdb->prefix . "ypcf_project_votes";
	    $count_users = $wpdb->get_var( "SELECT count(id) FROM $table_name WHERE post_id = " . $this->ID );
	    return $count_users;
	}

	/**
	 * Campaign Video
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Video
	 */
	public function video() {
		return $this->__get( 'campaign_video' );
	}

	/**
	 * Deprecated : use campaign_status instead
	 */
	public function vote() {
		return $this->__get( 'campaign_vote' );
	}
	/**
	 * Récupérer le statut du projet
	 * @return Statuts possibles : preparing ; preview ; vote ; collecte ; funded ; archive
	 */
	public function campaign_status() {
		return $this->__get( 'campaign_vote' );
	}
	
	/**
	 * Campaign Updates
	 *
	 * @since Appthemer CrowdFunding 0.9
	 *
	 * @return sting Campaign Updates
	 */
	public function updates() {
		return $this->__get( 'campaign_updates' );
	}

	/**
	 * Campaign Backers
	 *
	 * Use EDD logs to get all sales. This includes both preapproved
	 * payments (if they have Plugin installed) or standard payments.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Backers
	 */
	public function backers() {
		global $edd_logs;

		$backers = $edd_logs->get_connected_logs( array(
			'post_parent'    => $this->ID, 
			'log_type'       => atcf_has_preapproval_gateway() ? 'preapproval' : 'sale',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => -1
		) );

		return $backers;
	}

	/**
	 * Campaign Backers Count
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return int Campaign Backers Count
	 */
	public function backers_count() {
		$backers = $this->backers();
		
		if ( ! $backers )
			return 0;

		return absint( count( $backers ) );
	}

	/**
	 * Campaign Backers Per Price
	 *
	 * Get all of the backers, then figure out what they purchased. Increment
	 * a counter for each price point, so they can be displayed elsewhere. 
	 * Not 100% because keys can change in EDD, but it's the best way I think.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return array $totals The number of backers for each price point
	 */
	public function backers_per_price() {
		$backers = $this->backers();
		$prices  = edd_get_variable_prices( $this->ID );
		$totals  = array();

		if ( ! is_array( $backers ) )
			$backers = array();

		foreach ( $prices as $price ) {
			$totals[$price[ 'amount' ]] = 0;
		}

		foreach ( $backers as $log ) {
			$payment_id = get_post_meta( $log->ID, '_edd_log_payment_id', true );

			$payment    = get_post( $payment_id );
			
			if ( empty( $payment ) )
				continue;

			$cart_items = edd_get_payment_meta_cart_details( $payment_id );
			
			foreach ( $cart_items as $item ) {
				if ( isset ( $item[ 'item_number' ][ 'options' ][ 'atcf_extra_price' ] ) ) {
					$price_id = $item[ 'price' ] - $item[ 'item_number' ][ 'options' ][ 'atcf_extra_price' ];
				} else
					$price_id = $item[ 'price' ];

				$totals[$price_id] = isset ( $totals[$price_id] ) ? $totals[$price_id] + 1 : 1;
			}
		}

		return $totals;
	}

	/**
	 * Campaign Days Remaining
	 *
	 * Calculate the end date, minus today's date, and output a number.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return int The number of days remaining
	 */
	public function days_remaining() {
		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );

		if ( $now > $expires )
			return 0;

		$diff = $expires - $now;

		if ( $diff < 0 )
			return 0;

		$days = $diff / 86400;

		return floor( $days );
	}

	public function vote_duration() 
	{

	}

	/**
	 * Campaign Percent Completed
	 *
	 * MATH!
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $percent The percent completed (formatted with a % or not)
	 */
	public function percent_completed( $formatted = true ) {
		$goal    = $this->goal(false);
		$current = $this->current_amount(false);

		if ( 0 == $goal )
			return $formatted ? 0 . '%' : 0;

		$percent = ( $current / $goal ) * 100;
		$percent = round( $percent );

		if ( $formatted )
			return $percent . '%';

		return $percent;
	}
	public function percent_minimum_completed($formatted = true ) {
		$goal    = $this->minimum_goal(false);
		$current = $this->current_amount(false);

		if ( 0 == $goal )
			return $formatted ? 0 . '%' : 0;

		$percent = ( $current / $goal ) * 100;
		$percent = round( $percent );

		if ( $formatted )
			return $percent . '%';

		return $percent;
	}
	
	public function percent_minimum_to_total() {
	    $min = $this->minimum_goal(false);
	    $total = $this->goal(false);
	    return round($min / $total * 100);
	}

	/**
	 * Current amount funded.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $total The amount funded (currency formatted or not)
	 */
	public function current_amount( $formatted = true ) {
		$total   = 0;
		$backers = $this->backers();

		if ($backers > 0) {
		    foreach ( $backers as $backer ) {
			    $payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			    $payment    = get_post( $payment_id );

			    if ( empty( $payment ) )
				    continue;

			    $total      = $total + edd_get_payment_amount( $payment_id );
		    }
		}
		
		if ( $formatted ) {
		    $currency = edd_get_currency();
		    if ($currency == "EUR") {
			if (strpos($total, '.00') !== false) $total = substr ($total, 0, -3);
			return $total . ' &euro;';
		    } else {
			return edd_currency_filter( edd_format_amount( $total ) );
		    }
		}

		return $total;
	}

	/**
	 * Campaign Active
	 *
	 * Check if the campaign has expired based on time, or it has
	 * manually been expired (via meta)
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return boolean
	 */
	public function is_active() {
		$active  = true;

		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );

		if ( $now > $expires )
			$active = false;

		if ( $this->__get( '_campaign_expired' ) )
			$active = false;

		if ( $this->is_collected() )
			$active = false;

		return apply_filters( 'atcf_campaign_active', $active, $this );
	}

	/**
	 * Funds Collected
	 *
	 * When funds are collected in bulk, remember that, so we can end the
	 * campaign, and not repeat things.
	 *
	 * @since Appthemer CrowdFunding 0.3-alpha
	 *
	 * @return boolean
	 */
	public function is_collected() {
		return $this->__get( '_campaign_bulk_collected' );
	}

	/**
	 * Campaign Funded
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return boolean
	 */
	public function is_funded() {
		if ( $this->current_amount(false) >= $this->minimum_goal() )
			return true;

		return false;
	}
	
	
	public function payments_data() {
		$payments_data = array();

		$payments = edd_get_payments( array(
		    'number'	 => -1,
		    'download'   => $this->ID
		) );

		if ( $payments ) {
			foreach ( $payments as $payment ) {
				$user_info = edd_get_payment_meta_user_info( $payment->ID );
				$cart_details = edd_get_payment_meta_cart_details( $payment->ID );

				$user_id = (isset( $user_info['id'] ) && $user_info['id'] != -1) ? $user_info['id'] : $user_info['email'];

				$contractid = ypcf_get_signsquidcontractid_from_invest($payment->ID);
				$signsquid_infos = signsquid_get_contract_infos_complete($contractid);
				$signsquid_status = ($signsquid_infos != '' && is_object($signsquid_infos)) ? $signsquid_infos->{'status'} : '';
				$signsquid_status_text = ypcf_get_signsquidstatus_from_infos($signsquid_infos);
				$mangopay_id = edd_get_payment_key($payment->ID);
				$mangopay_contribution = ypcf_mangopay_get_contribution_by_id($mangopay_id);


				$payments_data[] = array(
					'ID'			=> $payment->ID,
					'email'			=> edd_get_payment_user_email( $payment->ID ),
					'products'		=> $cart_details,
					'amount'		=> edd_get_payment_amount( $payment->ID ),
					'date'			=> $payment->post_date,
					'user'			=> $user_id,
					'status'		=> ypcf_get_updated_payment_status( $payment->ID, $mangopay_contribution ),
					'mangopay_contribution' => $mangopay_contribution,
					'signsquid_status'	=> $signsquid_status,
					'signsquid_status_text' => $signsquid_status_text
				);
			}
		}
		return $payments_data;
	}
	
	public function manage_jycrois($user_id = FALSE) {
		global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";
		

		// Construction des urls utilisés dans les liens du fil d'actualité
		// url d'une campagne précisée par son nom 
		$campaign_url = get_permalink($_POST['id_campaign']);
		$post_campaign = get_post($_POST['id_campaign']);
		$post_title = $post_campaign->post_title;
		$url_campaign = '<a href="'.$campaign_url.'">'.$post_title.'</a>';

		//url d'un utilisateur précis
		$user_item = ($user_id === FALSE) ? wp_get_current_user() : get_userdata($user_id);
		$user_id = $user_item->ID;
		$user_display_name = $user_item->display_name;
		$url_profile = '<a href="' . bp_core_get_userlink($user_id, false, true) . '">' . $user_display_name . '</a>';
		$user_avatar = UIHelpers::get_user_avatar($user_id);

		//J'y crois
		if(isset($_POST['jy_crois']) && $_POST['jy_crois'] == 1){
			$wpdb->insert( 
				$table_jcrois,
				array(
					'user_id'	=> $user_id,
					'campaign_id'   => $this->ID
				)
			); 
			bp_activity_add(array (
				'component' => 'profile',
				'type'      => 'jycrois',
				'action'    => $user_avatar . $url_profile.' croit au projet '.$url_campaign
			));

		//J'y crois pas
		} else if (isset($_POST['jy_crois']) && $_POST['jy_crois'] == 0) { 
			$wpdb->delete( 
				$table_jcrois,
				array(
					'user_id'      => $user_id,
					'campaign_id'  => $this->ID
				)
			);
			// Inserer l'information dans la table du fil d'activité  de la BDD wp_bp_activity 
			bp_activity_delete(array (
				'user_id'   => $user_id,
				'component' => 'profile',
				'type'      => 'jycrois',
				'action'    => $user_avatar . $url_profile . ' croit au projet '.$url_campaign
			));
		}
		
		return $this->get_jycrois_nb();
	}
	
	public function get_jycrois_nb() {
		global $wpdb;
		$table_jcrois = $wpdb->prefix . "jycrois";
		return $wpdb->get_var( 'SELECT count(campaign_id) FROM '.$table_jcrois.' WHERE campaign_id = '.$this->ID );
	}
	
	public function get_header_picture_src($force = true) {
		$src = $this->get_picture_src('image_header', $force);
		if ($this->is_header_blur() === FALSE) {
			$src = str_replace('_blur', '', $src);
			
			//Test si le fichier existe
			if ($src !== '') {
				$src_exploded = explode('uploads', $src);
				$upload_dir = wp_upload_dir();
				if (!file_exists($upload_dir['basedir'] . $src_exploded[1])) {
					$ext_exploded = explode('.', $src);
					$ext_exploded[count($ext_exploded) - 1] = 'png';
					$src = implode('.', $ext_exploded);
				}
			}
		}
		return $src;
	}
	
	public function get_home_picture_src($force = true) {
		return $this->get_picture_src('image_home', $force);
	}
	
	public function get_picture_src($type, $force) {
		$image_obj = '';
		$img_src = '';
		$attachments = get_posts( array(
			'post_type' => 'attachment',
			'post_parent' => $this->ID,
			'post_mime_type' => 'image'
		));
		//Si on en trouve bien une avec le titre "image_home" on prend celle-là
		foreach ($attachments as $attachment) {
			if ($attachment->post_title == $type) $image_obj = wp_get_attachment_image_src($attachment->ID, "full");
		}
		//Sinon on prend la première image rattachée à l'article
		if ($force && $image_obj == '') $image_obj = wp_get_attachment_image_src($attachments[0]->ID, "full");
		
		if ($image_obj != '') $img_src = $image_obj[0];
		return $img_src;
	}
	
	public function is_header_blur() {
		$buffer = get_post_meta($this->ID, 'campaign_header_blur_active', TRUE);
		if ($buffer === FALSE || $buffer === 'FALSE') { 
		    $buffer = FALSE; 
		} else {
		    $buffer = TRUE;
		}
		return $buffer;
	}
	
	public function get_header_picture_position_style() {
		$buffer = '';
		$cover_position = get_post_meta($this->ID, 'campaign_cover_position', TRUE);
		if ($cover_position !== '') {
			$buffer = 'top: ' . $cover_position;
		}
		return $buffer;
	}
}

function atcf_get_campaign( $campaign ) {
	$campaign = new ATCF_Campaign( $campaign );

	return $campaign;
}

function atcf_get_campaign_post_by_payment_id($payment_id) {
	$downloads = edd_get_payment_meta_downloads($payment_id); 
	$download_id = (is_array($downloads[0])) ? $downloads[0]["id"] : $downloads[0];
	return get_post($download_id);
}


/**
 * Campaign Configuration
 *
 * Hook into EDD Download Information and add a bit more stuff.
 * These are all things that can be updated while the campaign runs/before
 * being published.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_info() {
	global $post, $edd_options, $wp_locale;

	/** Verification Field */
	wp_nonce_field( 'cf', 'cf-save' );
	
	$campaign = atcf_get_campaign( $post );

	$end_date = $campaign->end_date();


	$jj = mysql2date( 'd', $end_date, false );
	$mm = mysql2date( 'm', $end_date, false );
	$aa = mysql2date( 'Y', $end_date, false );
	$hh = mysql2date( 'H', $end_date, false );
	$mn = mysql2date( 'i', $end_date, false );
	$ss = mysql2date( 's', $end_date, false );

	do_action( 'atcf_metabox_campaign_info_before', $campaign );

	$types = atcf_campaign_types();
?>	
	<p>
		<label for="_campaign_featured">
			<input type="checkbox" name="_campaign_featured" id="_campaign_featured" value="1" <?php checked( 1, $campaign->featured() ); ?> />
			<?php _e( 'Featured campaign', 'atcf' ); ?>
		</label>
	</p>
	
	
	<p>
		<label for="_campaign_physical">
			<input type="checkbox" name="_campaign_physical" id="_campaign_physical" value="1" <?php checked( 1, $campaign->needs_shipping() ); ?> />
			<?php _e( 'Collect shipping test information on checkout', 'atcf' ); ?>
		</label>
	</p>
	
	
	<p>
		<strong><?php _e( 'Funding Type:', 'atcf' ); ?></strong>
	</p>

	<p>

		<?php foreach ( atcf_campaign_types_active() as $key => $desc ) : ?>
		<label for="campaign_type[<?php echo esc_attr( $key ); ?>]"><input type="radio" name="campaign_type" id="campaign_type[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $campaign->type() ); ?> /> <strong><?php echo $types[ $key ][ 'title' ]; ?></strong> &mdash; <?php echo $types[ $key ][ 'description' ]; ?></label><br />
		<?php endforeach; ?>
	</p>

	<p>
		<?php
		$fundingproject = 'checked="checked"';
		$fundingdevelopment = '';
		if ($campaign->funding_type() == 'fundingdevelopment') {
			$fundingproject = '';
			$fundingdevelopment = 'checked="checked"';
		}
		?>
		<label for="campaign_funding_type"><strong>Type de financement</strong></label><br />
		<input type="radio" name="campaign_funding_type" value="fundingproject" <?php echo $fundingproject; ?>>Projet<br />
		<input type="radio" name="campaign_funding_type" value="fundingdevelopment" <?php echo $fundingdevelopment; ?>>Capital<br />
	</p>

	<p>
	    <label for="campaign_goal"><strong><?php _e( 'Goal:', 'atcf' ); ?></strong></label><br />	
	    <input type="text" name="campaign_goal" id="campaign_goal" value="<?php echo edd_format_amount($campaign->goal(false) ); ?>" style="width:80px" /><?php echo edd_currency_filter( '' ); ?>
	</p>

	<p>
	    <label for="campaign_minimum_goal"><strong>Seuil minimum</strong></label><br />	
	    <input type="text" name="campaign_minimum_goal" id="campaign_minimum_goal" value="<?php echo edd_format_amount($campaign->minimum_goal() ); ?>" style="width:80px" /> &euro;
	</p>
	<p>
	    <label for="campaign_part_value"><strong><?php _e( 'Valeur de la part', 'yproject' ); ?></strong></label><br />
	    <input type="text" name="campaign_part_value" id="campaign_part_value" value="<?php echo edd_format_amount($campaign->part_value() ); ?>" style="width:80px" /><?php echo edd_currency_filter( '' ); ?>
	</p>

	<p>
		<label for="campaign_location"><strong><?php _e( 'Location:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_location" id="campaign_location" value="<?php echo esc_attr( $campaign->location() ); ?>" class="regular-text" />
	</p>

	<p>
		<label for="campaign_author"><strong><?php _e( 'Author:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_author" id="campaign_author" value="<?php echo esc_attr( $campaign->author() ); ?>" class="regular-text" />
	</p>
	
	<p>
		<label for="campaign_email"><strong><?php _e( 'Contact Email:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_contact_email" id="campaign_contact_email" value="<?php echo esc_attr( $campaign->contact_email() ); ?>" class="regular-text" />
	</p>

	<style>#end-aa { width: 3.4em } #end-jj, #end-hh, #end-mn { width: 2em; }</style>

	<p>
		<strong><?php _e( 'End Date:', 'atcf' ); ?></strong><br />

		<select id="end-mm" name="end-mm">
			<?php for ( $i = 1; $i < 13; $i = $i + 1 ) : $monthnum = zeroise($i, 2); ?>
				<option value="<?php echo $monthnum; ?>" <?php selected( $monthnum, $mm ); ?>>
				<?php printf( '%1$s-%2$s', $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ); ?>
				</option>
			<?php endfor; ?>
		</select>

		<input type="text" id="end-jj" name="end-jj" value="<?php echo esc_attr( $jj ); ?>" size="2" maxlength="2" autocomplete="off" />, 
		<input type="text" id="end-aa" name="end-aa" value="<?php echo esc_attr( $aa ); ?>" size="4" maxlength="4" autocomplete="off" /> @
		<input type="text" id="end-hh" name="end-hh" value="<?php echo esc_attr( $hh ); ?>" size="2" maxlength="2" autocomplete="off" /> :
		<input type="text" id="end-mn" name="end-mn" value="<?php echo esc_attr( $mn ); ?>" size="2" maxlength="2" autocomplete="off" />
		<input type="hidden" id="end-ss" name="end-ss" value="<?php echo esc_attr( $ss ); ?>" />
		<input type="hidden" id="campaign_end_date" name="campaign_end_date" value="1" />
		<input type="hidden" id="campaign_end_date_vote" name="campaign_end_date_vote" value="1" />
	</p>
	
<?php
	do_action( 'atcf_metabox_campaign_info_after', $campaign );
}



/** Frontend Submission *******************************************************/

/**
 * Process shortcode submission.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
function atcf_campaign_edit() {
	global $edd_options, $post;
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'atcf-campaign-edit' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'atcf-campaign-edit' ) )
		return;

	if ( ! ( $post->post_author == get_current_user_id() || current_user_can( 'manage_options' ) ) )
		return;

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	}

	$errors    = new WP_Error();
	
	$category  = $_POST[ 'cat' ];
	$content   = $_POST[ 'description' ];
	$actu      = $_POST[ 'blogs' ];
//	$updates   = $_POST[ 'updates' ];
	$excerpt   = $_POST[ 'excerpt' ];
	
	$summary                     = $_POST[ 'summary' ];
	$societal_challenge          = $_POST[ 'societal_challenge' ];
	$company_name		     = $_POST[ 'company_name' ];
	$company_status              = $_POST[ 'company_status' ];
	$company_status_other        = $_POST[ 'company_status_other' ];
	$init_capital                = $_POST[ 'init_capital' ];
	$funding_type                = $_POST[ 'funding_type' ];
	$funding_duration            = $_POST[ 'funding_duration' ];
//	$impact_area                 = $_POST[ 'impact_area' ];
	$added_value                 = $_POST[ 'added_value' ];
//	$development_strategy	     = $_POST[ 'development_strategy' ];
	$economic_model              = $_POST[ 'economic_model' ];
//	$measuring_impact            = $_POST[ 'measuring_impact' ];
	$implementation              = $_POST[ 'implementation' ];
	$investment_terms            = $_POST[ 'investment_terms' ];
	$vote                        = $_POST[ 'vote' ];
	$end_vote                    = $_POST[ 'end_vote' ];

	$email     = $_POST[ 'email' ];
	$author    = $_POST[ 'name' ];
	$location  = $_POST[ 'location' ];

	if ( isset ( $_POST[ 'contact-email' ] ) )
		$c_email = $_POST[ 'contact-email' ];
	else {
		$current_user = wp_get_current_user();
		$c_email = $current_user->user_email;
	}

	//** Check Category
	$category = absint( $category );

	//** Check Content
	if ( empty( $content ) )
		$errors->add( 'invalid-content', __( 'Please add content to this campaign.', 'atcf' ) );

	//** Check Excerpt
	if ( empty( $excerpt ) )
		$excerpt = null;

	do_action( 'atcf_edit_campaign_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) // Not sure how to avoid empty instantiated WP_Error
		wp_die( $errors );

	$args = apply_filters( 'atcf_edit_campaign_data', array(
		'ID'           => $post->ID,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'tax_input'    => array(
			'download_category' => array( $category )
		)
	), $_POST );

	$campaign = wp_update_post( $args, true );

	//** Extra Campaign Information

	update_post_meta( $post->ID, 'campaign_contact_email', sanitize_text_field( $c_email ) );
	update_post_meta( $post->ID, 'campaign_location', sanitize_text_field( $location ) );
	update_post_meta( $post->ID, 'campaign_author', sanitize_text_field( $author ) );
	update_post_meta( $post->ID, 'campaign_updates', wp_kses_post( $updates ) );
	update_post_meta( $post->ID, 'campaign_summary', sanitize_text_field( $summary ) );
	update_post_meta( $post->ID, 'campaign_impact_area', sanitize_text_field( $impact_area ) );
	update_post_meta( $post->ID, 'campaign_societal_challenge', sanitize_text_field( $societal_challenge ) );
	update_post_meta( $post->ID, 'campaign_company_name', sanitize_text_field( $company_name ) );
	update_post_meta( $post->ID, 'campaign_company_status', sanitize_text_field( $company_status ) );
	update_post_meta( $post->ID, 'campaign_company_status_other', sanitize_text_field( $company_status_other ) );
	update_post_meta( $post->ID, 'campaign_init_capital', sanitize_text_field( $init_capital ) );
	update_post_meta( $post->ID, 'campaign_funding_type', sanitize_text_field( $funding_type ) );
	update_post_meta( $post->ID, 'campaign_funding_duration', sanitize_text_field( $funding_duration ) );
	update_post_meta( $post->ID, 'campaign_added_value', sanitize_text_field( $added_value ) );
	update_post_meta( $post->ID, 'campaign_development_strategy', sanitize_text_field( $development_strategy ) );
	update_post_meta( $post->ID, 'campaign_economic_model', sanitize_text_field( $economic_model ) );
	update_post_meta( $post->ID, 'campaign_measuring_impact', sanitize_text_field( $measuring_impact ) );
	update_post_meta( $post->ID, 'campaign_implementation', sanitize_text_field( $implementation ) );
	update_post_meta( $post->ID, 'campaign_investment_terms', sanitize_text_field( $investment_terms ) );
	update_post_meta( $post->ID, 'campaign_vote', sanitize_text_field( $vote ) );
	update_post_meta( $post->ID, 'campaign_end_vote', sanitize_text_field( $end_vote ) );
	

	do_action( 'atcf_edit_campaign_after', $post->ID, $_POST );

	$redirect = apply_filters( 'atcf_submit_campaign_success_redirect', add_query_arg( array( 'success' => 'true' ), get_permalink( $post->ID ) ) );
	wp_safe_redirect( $redirect );
	exit();
}
add_action( 'template_redirect', 'atcf_campaign_edit' );
 */

/**
 * Price Options Heading
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @param string $heading Price options heading
 * @return string Modified price options heading
 */
function atcf_edd_price_options_heading( $heading ) {
	return __( 'Reward Options:', 'atcf' );
}

/**
 * Reward toggle text
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @param string $heading Reward toggle text
 * @return string Modified reward toggle text
 */
function atcf_edd_variable_pricing_toggle_text( $text ) {
	return __( 'Enable multiple reward options', 'atcf' );
}

/**
 * Campaign Types
 *
 * @since AppThemer Crowdfunding 0.9
 */
function atcf_campaign_types() {
	$types = apply_filters( 'atcf_campaign_types', array(
		'fixed'    => array(
			'title'       => __( 'Fixe', 'atcf' ),
			'description' => __( 'Only collect pledged funds when the campaign ends if the set goal is met.', 'atcf' )
		),
		'flexible' => array(
			'title'       => __( 'Flexible', 'atcf' ),
			'description' => __( 'Collect funds pledged at the end of the campaign no matter what.', 'atcf' )
		)
	) );

	return $types;
}

function atcf_campaign_types_active() {
	global $edd_options;

	$types  = atcf_campaign_types();
	$active = isset ( $edd_options[ 'atcf_campaign_types' ] ) ? $edd_options[ 'atcf_campaign_types' ] : null;

	if ( ! $active ) {
		$keys = array();

		foreach ( $types as $key => $type )
			$keys[ $key ] = $type[ 'title' ] . ' &mdash; <small>' . $type[ 'description' ] . '</small>';

		return $keys;
	}

	return $active;
}

function atcf_campaign_type_default() {
	$type = apply_filters( 'atcf_campaign_type_default', 'fixed' );

	return $type;
}

function atcf_get_locations() {
	$buffer = array(
		'01 Ain',
		'02 Aisne',
		'03 Allier',
		'04 Alpes-de-Haute-Provence',
		'05 Hautes-Alpes',
		'06 Alpes-Maritimes',
		'07 Ard&egraveche',
		'08 Ardennes',
		'09 Ari&egravege',
		'10 Aube',
		'11 Aude',
		'12 Aveyron',
		'13 Bouches-du-Rh&ocircne',
		'14 Calvados',
		'15 Cantal',
		'16 Charente',
		'17 Charente-Maritime',
		'18 Cher',
		'19 Corr&egraveze',
		'2A Corse-du-Sud',
		'2B Haute-Corse',
		'21 C&ocircte-d\'Or',
		'22 C&ocirctes d\'Armor',
		'23 Creuse',
		'24 Dordogne',
		'25 Doubs',
		'26 Dr&ocircme',
		'27 Eure',
		'28 Eure-et-Loir',
		'29 Finist&egravere',
		'30 Gard',
		'31 Haute-Garonne',
		'32 Gers',
		'33 Gironde',
		'34 H&eacuterault',
		'35 Ille-et-Vilaine',
		'36 Indre',
		'37 Indre-et-Loire',
		'38 Is&egravere',
		'39 Jura',
		'40 Landes',
		'41 Loir-et-Cher',
		'42 Loire',
		'43 Haute-Loire',
		'44 Loire-Atlantique',
		'45 Loiret',
		'46 Lot',
		'47 Lot-et-Garonne',
		'48 Loz&egravere',
		'49 Maine-et-Loire',
		'50 Manche',
		'51 Marne',
		'52 Haute-Marne',
		'53 Mayenne',
		'54 Meurthe-et-Moselle',
		'55 Meuse',
		'56 Morbihan',
		'57 Moselle',
		'58 Ni&egravevre',
		'59 Nord',
		'60 Oise',
		'61 Orne',
		'62 Pas-de-Calais',
		'63 Puy-de-D&ocircme',
		'64 Pyr&eacuten&eacutees-Atlantiques',
		'65 Hautes-Pyr&eacuten&eacutees',
		'66 Pyr&eacuten&eacutees-Orientales',
		'67 Bas-Rhin',
		'68 Haut-Rhin',
		'69 Rh&ocircne',
		'70 Haute-Sa&ocircne',
		'71 Sa&ocircne-et-Loire',
		'72 Sarthe',
		'73 Savoie',
		'74 Haute-Savoie',
		'75 Paris',
		'76 Seine-Maritime',
		'77 Seine-et-Marne',
		'78 Yvelines',
		'79 Deux-S&egravevres',
		'80 Somme',
		'81 Tarn',
		'82 Tarn-et-Garonne',
		'83 Var',
		'84 Vaucluse',
		'85 Vend&eacutee',
		'86 Vienne',
		'87 Haute-Vienne',
		'88 Vosges',
		'89 Yonne',
		'90 Territoire de Belfort',
		'91 Essonne',
		'92 Hauts-de-Seine',
		'93 Seine-Saint-Denis',
		'94 Val-de-Marne',
		'95 Val-d\'Oise',
		'971 Guadeloupe',
		'972 Martinique',
		'973 Guyane',
		'974 La Réunion',
		'976 Mayotte'
	);
	return $buffer;
}