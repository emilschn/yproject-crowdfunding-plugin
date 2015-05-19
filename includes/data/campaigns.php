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

		add_filter( 'manage_edit-download_columns', array( $this, 'dashboard_columns' ), 11, 1 );
		add_filter( 'manage_download_posts_custom_column', array( $this, 'dashboard_column_item' ), 11, 2 );
		
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 11 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_filter( 'edd_metabox_fields_save', array( $this, 'meta_boxes_save' ) );
		add_filter( 'edd_metabox_save_campaign_end_date', 'atcf_campaign_save_end_date' );
		add_filter( 'edd_metabox_save_campaign_end_vote', 'atcf_campaign_save_end_vote' );

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
			'name' 			=> __( 'Projets', 'atcf' ),
			'singular_name' 	=> __( 'Projet', 'atcf' ),
			'add_new' 		=> __( 'Ajouter', 'atcf' ),
			'add_new_item' 		=> __( 'Nouveau projet', 'atcf' ),
			'edit_item' 		=> __( 'Editer projet', 'atcf' ),
			'new_item' 		=> __( 'Nouveau projet', 'atcf' ),
			'all_items' 		=> __( 'Tous les projets', 'atcf' ),
			'view_item' 		=> __( 'Voir le projet', 'atcf' ),
			'search_items' 		=> __( 'Rechercher projet', 'atcf' ),
			'not_found' 		=> __( 'Aucun projet trouvé', 'atcf' ),
			'not_found_in_trash'	=> __( 'Aucun projet dans la corbeille', 'atcf' ),
			'parent_item_colon'	=> '',
			'menu_name' 		=> __( 'Projets', 'atcf' )
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

		add_meta_box( 'atcf_campaign_status', 'Statut de la campagne', '_atcf_metabox_campaign_status', 'download', 'side', 'high' );
		add_meta_box( 'atcf_campaign_date_vote', 'Date de la fin des votes', '_atcf_metabox_campaign_date_vote', 'download', 'side', 'high' );
		
		add_meta_box( 'atcf_campaign_summary', 'Résumé', '_atcf_metabox_campaign_summary', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_societal_challenge', 'Utilité sociétale', '_atcf_metabox_campaign_societal_challenge', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_added_value', 'Opportunité économique', '_atcf_metabox_campaign_added_value', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_economic_model', 'Modèle économique', '_atcf_metabox_campaign_economic_model', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_implementation', 'Qui porte le projet ?', '_atcf_metabox_campaign_implementation', 'download', 'normal', 'high' );
		
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
		$fields[] = 'campaign_amount_check';
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

function atcf_campaign_save_end_vote() {
	if ( ! isset( $_POST[ 'end-vote-aa' ] ) )
		return;
	
	date_default_timezone_set("Europe/Paris");
	$aa = $_POST['end-vote-aa'];
	$mm = $_POST['end-vote-mm'];
	$jj = $_POST['end-vote-jj'];
	$hh = $_POST['end-vote-hh'];
	$mn = $_POST['end-vote-mn'];

	$aa = ($aa <= 0 ) ? date('Y') : $aa;
	$mm = ($mm <= 0 ) ? date('n') : $mm;
	$jj = ($jj > 31 ) ? 31 : $jj;
	$jj = ($jj <= 0 ) ? date('j') : $jj;

	$hh = ($hh > 23 ) ? $hh -24 : $hh + 1; //Pourquoi y'a-t-il besoin d'un +1 ? Bonne question...
	$mn = ($mn > 59 ) ? $mn -60 : $mn;
	$ss = 0;

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

function _atcf_metabox_campaign_status() {
	global $post;
	$campaign = atcf_get_campaign( $post );
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
}

// CHOIX DES DATES DES VOTES
function _atcf_metabox_campaign_date_vote() {
	global $post, $wp_locale;
	$campaign = atcf_get_campaign( $post );
	$end_date = $campaign->end_vote_date();
	$jj = mysql2date( 'd', $end_date, false );
	$mm = mysql2date( 'm', $end_date, false );
	$aa = mysql2date( 'Y', $end_date, false );
	$hh = mysql2date( 'H', $end_date, false );
	$mn = mysql2date( 'i', $end_date, false );
?>  
	<p>
	    <input type="text" name="end-vote-jj" value="<?php echo esc_attr( $jj ); ?>" size="2" maxlength="2" autocomplete="off" />
	    <select name="end-vote-mm">
		    <?php for ( $i = 1; $i < 13; $i = $i + 1 ) : $monthnum = zeroise($i, 2); ?>
			    <option value="<?php echo $monthnum; ?>" <?php selected( $monthnum, $mm ); ?>>
			    <?php printf( '%1$s-%2$s', $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ); ?>
			    </option>
		    <?php endfor; ?>
	    </select>
	    <input type="text" name="end-vote-aa" value="<?php echo esc_attr( $aa ); ?>" size="4" maxlength="4" autocomplete="off" /> @

	    <input type="text" name="end-vote-hh" value="<?php echo esc_attr( $hh ); ?>" size="2" maxlength="2" autocomplete="off" /> :
	    <input type="text" name="end-vote-mn" value="<?php echo esc_attr( $mn ); ?>" size="2" maxlength="2" autocomplete="off" />
	    <input type="hidden" name="campaign_end_vote" value="1" />
	</p>
<?php
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
			wp_editor( $campaign ? html_entity_decode($campaign->societal_challenge()) : '', 'campaign_societal_challenge', array( 
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
			) ); 
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
			wp_editor( $editing ? html_entity_decode($campaign->added_value()) : '', 'campaign_added_value', array( 
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
			) ); 
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
			wp_editor( $editing ? html_entity_decode($campaign->development_strategy()) : '', 'campaign_development_strategy', array( 
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
			) ); 
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
			wp_editor( $editing ? html_entity_decode($campaign->economic_model()) : '', 'campaign_economic_model', array( 
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
			) ); 
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
			wp_editor( $editing ? html_entity_decode($campaign->measuring_impact()) : '', 'campaign_measuring_impact', array( 
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
			) ); 
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
			wp_editor( $editing ? html_entity_decode($campaign->implementation()) : '', 'campaign_implementation', array( 
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
			) ); 
		?>
	</div>
<?php

	do_action( 'atcf_metabox_campaign_implementation_after', $campaign );
}

function _atcf_metabox_campaign_investment_terms( $editing, $campaign ) {
	global $post;

	$campaign = atcf_get_campaign( $post );
?>
	<div class="atcf-metabox-campaign-investment_terms">
		<?php 
			wp_editor( $editing ? html_entity_decode($campaign->investment_terms()) : '', 'campaign_investment_terms', array( 
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
			) ); 
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
			wp_editor( $editing ? html_entity_decode($campaign->subscription_params()) : '', 'campaign_subscription_params', array( 
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
			) ); 
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
			wp_editor( $editing ? html_entity_decode($campaign->powers_params()) : '', 'campaign_powers_params', array( 
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
			) ); 
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
			wp_editor( $editing ? html_entity_decode($campaign->constitution_terms()) : '', 'campaign_constitution_terms', array( 
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
			) ); 
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
			Mise en avant
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
		<label for="campaign_email"><strong><?php _e( 'Contact Email:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_contact_email" id="campaign_contact_email" value="<?php echo esc_attr( $campaign->contact_email() ); ?>" class="regular-text" />
	</p>

	<style>#end-aa { width: 3.4em } #end-jj, #end-hh, #end-mn { width: 2em; }</style>

	<p>
		<strong><?php _e( 'End Date:', 'atcf' ); ?></strong><br />

		<input type="text" id="end-jj" name="end-jj" value="<?php echo esc_attr( $jj ); ?>" size="2" maxlength="2" autocomplete="off" />
		<select id="end-mm" name="end-mm">
			<?php for ( $i = 1; $i < 13; $i = $i + 1 ) : $monthnum = zeroise($i, 2); ?>
				<option value="<?php echo $monthnum; ?>" <?php selected( $monthnum, $mm ); ?>>
				<?php printf( '%1$s-%2$s', $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ); ?>
				</option>
			<?php endfor; ?>
		</select>
		<input type="text" id="end-aa" name="end-aa" value="<?php echo esc_attr( $aa ); ?>" size="4" maxlength="4" autocomplete="off" /> @
		
		<input type="text" id="end-hh" name="end-hh" value="<?php echo esc_attr( $hh ); ?>" size="2" maxlength="2" autocomplete="off" /> :
		<input type="text" id="end-mn" name="end-mn" value="<?php echo esc_attr( $mn ); ?>" size="2" maxlength="2" autocomplete="off" />
		<input type="hidden" id="end-ss" name="end-ss" value="<?php echo esc_attr( $ss ); ?>" />
		<input type="hidden" name="campaign_end_date" value="1" />
	</p>
	
	<p>
		Lien video (supporté par oembed) :
		<input type="text" name="campaign_video" value="<?php echo esc_url( $campaign->video() ); ?>" />
	</p>
	<p>
		Lien Google Doc :
		<input type="text" name="campaign_google_doc" value="<?php echo $campaign->google_doc(); ?>" />
	</p>
	
	<p>
		Nom de la société :
		<input type="text" name="campaign_company_name" value="<?php echo $campaign->company_name(); ?>" />
	</p>
	<p>
		Titre du contrat :
		<input type="text" name="campaign_contract_title" value="<?php echo $campaign->contract_title(); ?>" />
	</p>
	<p>
		Total des investissements par chèque :
		<input type="text" name="campaign_amount_check" value="<?php echo $campaign->current_amount_check(FALSE); ?>" />
	</p>
	
<?php
	do_action( 'atcf_metabox_campaign_info_after', $campaign );
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
