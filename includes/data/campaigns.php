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
		define( 'EDD_SLUG', 'campaigns' );
		add_action( 'init', array( $this, 'campaigns_rewrite_news_links' ) );
		add_filter( 'pre_post_link', array( $this, 'campaigns_news_links' ), 1, 3 );
		
		add_filter( 'post_type_link', array( $this, 'campaigns_type_links' ), 1, 3 );
		add_action( 'pre_get_posts', array( $this, 'campaigns_type_get_posts' ) );
		
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
		add_filter( 'edd_metabox_save_campaign_postname', 'atcf_campaign_save_postname' );
		add_filter( 'edd_metabox_save_campaign_end_date', 'atcf_campaign_save_end_date' );
		add_filter( 'edd_metabox_save_campaign_begin_collecte_date', 'atcf_campaign_save_begin_collecte_date' );
		add_filter( 'edd_metabox_save_campaign_end_vote', 'atcf_campaign_save_end_vote' );
		add_filter( 'edd_metabox_save_campaign_first_payment_date', 'atcf_campaign_save_first_payment_date' );
		add_filter( 'edd_metabox_save_campaign_payment_list', 'atcf_campaign_save_payment_list' );
		add_filter( 'edd_metabox_save_save_declarations', 'atcf_campaign_save_declarations' );
		add_filter( 'edd_metabox_save_add_first_declaration', 'atcf_campaign_add_first_declaration' );
		add_filter( 'edd_metabox_save_add_new_declaration', 'atcf_campaign_add_new_declaration' );

		add_action( 'edd_download_price_table_head', 'atcf_pledge_limit_head' );
		add_action( 'edd_download_price_table_row', 'atcf_pledge_limit_column', 10, 3 );

		add_action( 'admin_action_atcf-collect-funds', array( $this, 'collect_funds' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );

		do_action( 'atcf_campaigns_actions_admin' );
	}
	
	function campaigns_rewrite_news_links() {
		add_rewrite_rule( '^les-projets/([^/]+)/?', 'index.php?name=$matches[1]', 'top' );
	}
	
	function campaigns_news_links( $permalink, $post, $leavename ) {
		if ( $post->post_type === 'post' ) {
			return '/les-projets/' .$post->post_name. '/';
		}
		return $permalink;
	}

	/**
	* Réécritures des liens des campagnes
	*/
	function campaigns_type_links( $post_link, $post = 0 ) {
		if ( $post->post_type === 'download' ) {
			return home_url( $post->post_name . '/' );
		}
		return $post_link;
	}

	/**
	* Fait en sorte que les liens directs fonctionnent
	*/
	function campaigns_type_get_posts( $query ) {
		//Si c'est l'ancienne url (/campaigns/) qui est appelée, on redirige vers l'url directe
		if ( isset( $query->query )
			&& isset( $query->query['post_type'] ) && $query->query['post_type'] == 'download'
			&& isset( $query->query['download'] ) ) {
			wp_redirect( home_url( $query->query['download'] . '/' ) );
			exit();
		}

		//Si on n'est pas sur la page directe, on ne tient pas compte du reste
		if ( ! $query->is_main_query() || 2 != count( $query->query ) || ! isset( $query->query['page'] ) ) {
			return;
		}

		//On accepte le type "download" pour être appelé directement
		if ( ! empty( $query->query['name'] ) ) {
			$query->set( 'post_type', array( 'post', 'page', 'download' ) );
		}
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
			case ATCF_Campaign::$campaign_status_funded :
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

		add_meta_box( 'atcf_campaign_status', 'Statut de la levée de fonds', '_atcf_metabox_campaign_status', 'download', 'side', 'high' );
		add_meta_box( 'atcf_campaign_date_vote', 'Dates de la levée de fonds', '_atcf_metabox_campaign_dates', 'download', 'side', 'high' );
		
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
		$fields[] = 'campaign_postname';
		$fields[] = '_campaign_featured';
		$fields[] = ATCF_Campaign::$key_payment_provider;
		$fields[] = '_campaign_physical';
		$fields[] = 'campaign_part_value';
		$fields[] = 'campaign_end_vote';
		$fields[] = 'campaign_begin_collecte_date';
		$fields[] = 'campaign_end_date';
		$fields[] = 'campaign_vote';
		$fields[] = 'campaign_validated_next_step';
		$fields[] = 'campaign_first_payment_date';
		$fields[] = 'campaign_payment_list';
		$fields[] = 'campaign_video';
		$fields[] = 'campaign_images';
		$fields[] = 'campaign_location';
		$fields[] = 'campaign_author';
		$fields[] = 'campaign_type';
		$fields[] = 'campaign_google_doc';
		$fields[] = 'campaign_contract_title';
		$fields[] = 'campaign_contract_title_en_US';
		$fields[] = 'campaign_amount_check';
		$fields[] = 'campaign_company_status';
		$fields[] = 'campaign_company_status_other';
		$fields[] = 'campaign_init_capital';
		$fields[] = 'campaign_funding_type';
		$fields[] = 'save_declarations';
		$fields[] = 'add_first_declaration';
		$fields[] = 'add_new_declaration';
		
		$fields[] = 'campaign_investment_terms';
		$fields[] = 'campaign_investment_terms_en_US';
		$fields[] = 'campaign_subscription_params';
		$fields[] = 'campaign_subscription_params_en_US';
		$fields[] = 'campaign_powers_params';
		$fields[] = 'campaign_powers_params_en_US';
		$fields[] = 'campaign_constitution_terms';
		$fields[] = 'campaign_constitution_terms_en_US';
		

		return $fields;
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

function atcf_campaign_save_postname( $new_name ) {
	$campaign_id = filter_input( INPUT_POST, 'post_ID' );
	$current_post = get_post( $campaign_id );
	
	if ( !empty( $new_name ) && $current_post->post_name != $new_name ) {
		if ( $posts = get_posts( array(
			'name' => $new_name,
			'post_type' => array( 'post', 'page', 'download' )
		) ) ) {
			return new WP_Error( 'invalid_name', "L'URL est déjà utilisée." );
			
		} else {
			wp_update_post( array(
				'ID'		=> $campaign_id,
				'post_name' => $new_name
			) );
			return $new_name;
		}
	}
	
	return $current_post->post_name;
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

function atcf_campaign_save_begin_collecte_date( $new ) {
	if ( ! isset( $_POST[ 'begin-aa' ] ) )
		return;
	
	date_default_timezone_set("Europe/Paris");
	$aa = $_POST['begin-aa'];
	$mm = $_POST['begin-mm'];
	$jj = $_POST['begin-jj'];
	$hh = $_POST['begin-hh'];
	$mn = $_POST['begin-mn'];
	$ss = $_POST['begin-ss'];

	$aa = ($aa <= 0 ) ? date('Y') : $aa;
	$mm = ($mm <= 0 ) ? date('n') : $mm;
	$jj = ($jj > 31 ) ? 31 : $jj;
	$jj = ($jj <= 0 ) ? date('j') : $jj;

	$hh = ($hh > 23 ) ? $hh -24 : $hh + 1; //Pourquoi y'a-t-il besoin d'un +1 ? Bonne question...
	$mn = ($mn > 59 ) ? $mn -60 : $mn;
	$ss = ($ss > 59 ) ? $ss -60 : $ss;

	$begin_date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
	
	$valid_date = wp_checkdate( $mm, $jj, $aa, $begin_date );
	
	if ( ! $valid_date ) {
		return new WP_Error( 'invalid_date', __( 'Whoops, the provided date is invalid.', 'atcf' ) );
	}

	$begin_date = get_gmt_from_date( $begin_date );

	return $begin_date;
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

function atcf_campaign_save_first_payment_date() {
	if ( ! isset( $_POST[ 'first-payment-dd' ] ) )
		return;

	date_default_timezone_set("Europe/Paris");
	$yy = $_POST['first-payment-yy'];
	$mm = $_POST['first-payment-mm'];
	$dd = $_POST['first-payment-dd'];
	$yy = ($yy <= 0 ) ? date('Y') : $yy;
	$mm = ($mm <= 0 ) ? date('n') : $mm;
	$dd = ($dd > 31 ) ? 31 : $dd;
	$dd = ($dd <= 0 ) ? date('j') : $dd;

	$fp_date = sprintf("%04d-%02d-%02d 12:00:00", $yy, $mm, $dd);
	$valid_date = wp_checkdate( $mm, $dd, $yy, $fp_date );
	if ( ! $valid_date ) {
		return new WP_Error( 'invalid_date', __( 'La date de premier paiement n&apos;est pas valide.', 'atcf' ) );
	}

	$fp_date = get_gmt_from_date( $fp_date );
	return $fp_date;
}

function atcf_campaign_save_payment_list() {
	$payment_list = array();
	$fp_yy = $_POST['first-payment-yy'];
	for ($i = $fp_yy; $i < $_POST['campaign_funding_duration'] + $fp_yy; $i++) {
		$payment_list[$i] = $_POST["payment-" . $i];
	}
	$payment_list = json_encode($payment_list);
	return $payment_list;
}

function atcf_campaign_save_declarations() {
	$campaign_id = filter_input( INPUT_POST, 'post_ID' );
	$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $campaign_id );
	foreach ( $declaration_list as $ROIdeclaration ) {
		$declaration_date = filter_input( INPUT_POST, 'declaration-date_due-' . $ROIdeclaration->id );
		if ( !empty($declaration_date) ) {
			$exploded_date = explode('/', $declaration_date);
			$formatted_date = $exploded_date[2] .'-'. $exploded_date[1] .'-'. $exploded_date[0];
			$ROIdeclaration->date_due = $formatted_date;
		}
		$declaration_files = filter_input( INPUT_POST, 'declaration-files-' . $ROIdeclaration->id );
		if ($declaration_files && $ROIdeclaration->file_list == "") {
			$ROIdeclaration->file_list = '1';
		} elseif (!$declaration_files && $ROIdeclaration->file_list == '1') {
			$ROIdeclaration->file_list = '';
		}
		$ROIdeclaration->update();
		$ROIdeclaration->save();
	}
}

function atcf_campaign_add_first_declaration() {
	$save_first = filter_input(INPUT_POST, 'declaration-files-first');
	$campaign_id = filter_input(INPUT_POST, 'post_ID');
	
	$post_campaign = get_post( $campaign_id );
	$campaign = atcf_get_campaign( $post_campaign );
	$fp_date = $campaign->first_payment_date();
	$fp_dd = mysql2date( 'd', $fp_date, false );
	$fp_mm = mysql2date( 'm', $fp_date, false );
	$fp_yy = mysql2date( 'Y', $fp_date, false );
	$date_formatted = $fp_yy. '-' .$fp_mm. '-' .$fp_dd;
	$declaration_id = WDGROIDeclaration::insert($campaign->get_api_id(), $date_formatted);
	
	if ( !empty( $save_first ) ) {
		$new_declaration = new WDGROIDeclaration($declaration_id);
		$new_declaration->file_list = '1';
		$new_declaration->update();
		$new_declaration->save();
	}
}

function atcf_campaign_add_new_declaration() {
	$due_date = filter_input(INPUT_POST, 'declaration-date_due-new');
	$campaign_id = filter_input(INPUT_POST, 'post_ID');
	$campaign = new ATCF_Campaign( $campaign_id );
	if ( !empty( $due_date ) ) {
		$date_exploded = explode('/', $due_date);
		$date_formatted = $date_exploded[2] .'-'. $date_exploded[1] .'-'. $date_exploded[0];
		WDGROIDeclaration::insert( $campaign->get_api_id(), $date_formatted );
	}
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
	<th style="width: 60px"><?php _e( 'Limite', 'edd' ); ?></th>
        <th style="width: 60px"><?php _e( 'Achet&eacute;s', 'edd' ); ?></th>
        <th style="width: 50px"><?php _e( 'Id', 'edd' ); ?></th>
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
    //Il est possible de modifier les "bought" et "id" en modifiant le CSS, et ainsi enregistrer n'importe quoi dans la BDD.
?>
	<td>
		<input type="number" min="0" step="1" class="edd_repeatable_name_field" name="edd_variable_prices[<?php echo $key; ?>][limit]" id="edd_variable_prices[<?php echo $key; ?>][limit]" value="<?php echo isset ( $args[ 'limit' ] ) ? $args[ 'limit' ] : null; ?>" style="width:100%" />
	</td>
	<td>
		<input type="number" class="edd_repeatable_name_field" name="edd_variable_prices[<?php echo $key; ?>][bought]" id="edd_variable_prices[<?php echo $key; ?>][bought]" value="<?php echo isset ( $args[ 'bought' ] ) ? $args[ 'bought' ] : null; ?>" readonly style="width:100%" />
	</td>
        <td>
		<input type="number" class="edd_repeatable_name_field" name="edd_variable_prices[<?php echo $key; ?>][id]" id="edd_variable_prices[<?php echo $key; ?>][id]" value="<?php echo isset ( $args[ 'id' ] ) ? $args[ 'id' ] : null; ?>" readonly style="width:100%" />
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
        $args[ 'id' ] = isset( $value[ 'id' ] ) ? $value[ 'id' ] : '';

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
	<p>Choisir le statut de la levée de fonds</p>
	<?php $status_list = ATCF_Campaign::get_campaign_status_list(); ?>
	<select id="campaign_vote" name="campaign_vote" class="regular-text" style="width:200px;">
	    <option></option>
		<?php foreach ($status_list as $status => $status_label): ?>
		<option <?php if ($campaign->campaign_status() == $status) { ?>selected="selected"<?php } ?> value=<?php echo $status; ?>><?php echo $status_label; ?></option>
		<?php endforeach; ?>
	</select>
        
	<p>Autoriser le porteur de projet &agrave;  passer &agrave;  l'&eacute;tape suivante</p>
	<select id="campaign_validated_next_step" name="campaign_validated_next_step" class="regular-text" style="width:200px;">
		<option <?php if (!$campaign->can_go_next_status()) { ?>selected="selected"<?php } ?> value="0">Non</option>
		<option <?php if ($campaign->can_go_next_status()) { ?>selected="selected"<?php } ?> value="1">Oui</option>
	</select>
<?php
}

// CHOIX DES DATES DE LA CAMPAGNE
function _atcf_metabox_campaign_dates() {
	global $post, $wp_locale;
	$campaign = atcf_get_campaign( $post );
	$end_vote_date = $campaign->end_vote_date();
	$jj = mysql2date( 'd', $end_vote_date, false );
	$mm = mysql2date( 'm', $end_vote_date, false );
	$aa = mysql2date( 'Y', $end_vote_date, false );
	$hh = mysql2date( 'H', $end_vote_date, false );
	$mn = mysql2date( 'i', $end_vote_date, false );
?>  
	<p>
            <strong><?php _e( 'Date de fin de vote:', 'atcf' ); ?></strong><br />

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
            $begin_date = $campaign->begin_collecte_date();
            $jj = mysql2date( 'd', $begin_date, false );
            $mm = mysql2date( 'm', $begin_date, false );
            $aa = mysql2date( 'Y', $begin_date, false );
            $hh = mysql2date( 'H', $begin_date, false );
            $mn = mysql2date( 'i', $begin_date, false );
            $ss = mysql2date( 's', $begin_date, false );
        ?>
        <p>
		<strong><?php _e( "Date de début d'investissement :", 'atcf' ); ?></strong><br />

		<input type="text" id="begin-jj" name="begin-jj" value="<?php echo esc_attr( $jj ); ?>" size="2" maxlength="2" autocomplete="off" />
		<select id="begin-mm" name="begin-mm">
			<?php for ( $i = 1; $i < 13; $i = $i + 1 ) : $monthnum = zeroise($i, 2); ?>
				<option value="<?php echo $monthnum; ?>" <?php selected( $monthnum, $mm ); ?>>
				<?php printf( '%1$s-%2$s', $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ); ?>
				</option>
			<?php endfor; ?>
		</select>
		<input type="text" id="begin-aa" name="begin-aa" value="<?php echo esc_attr( $aa ); ?>" size="4" maxlength="4" autocomplete="off" /> @
		
		<input type="text" id="begin-hh" name="begin-hh" value="<?php echo esc_attr( $hh ); ?>" size="2" maxlength="2" autocomplete="off" /> :
		<input type="text" id="begin-mn" name="begin-mn" value="<?php echo esc_attr( $mn ); ?>" size="2" maxlength="2" autocomplete="off" />
		<input type="hidden" id="begin-ss" name="begin-ss" value="<?php echo esc_attr( $ss ); ?>" />
		<input type="hidden" name="campaign_begin_collecte_date" value="1" />
	</p>
        
        <?php
            $end_date = $campaign->end_date();
            $jj = mysql2date( 'd', $end_date, false );
            $mm = mysql2date( 'm', $end_date, false );
            $aa = mysql2date( 'Y', $end_date, false );
            $hh = mysql2date( 'H', $end_date, false );
            $mn = mysql2date( 'i', $end_date, false );
            $ss = mysql2date( 's', $end_date, false );
        ?>
        <p>
		<strong><?php _e( "Date de fin d'investissement :", 'atcf' ); ?></strong><br />

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
<?php
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
	
	<h3>ENGLISH VERSION</h3>
	<div class="atcf-metabox-campaign-investment_terms_en_US">
		<?php 
			$campaign->set_current_lang('en_US');
			wp_editor( $editing ? html_entity_decode($campaign->investment_terms()) : '', 'campaign_investment_terms_en_US', array( 
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
			$campaign->set_current_lang('');
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
	
	<h3>ENGLISH VERSION</h3>
	<div class="atcf-metabox-campaign-subscription_params_en_US">
		<?php 
			$campaign->set_current_lang('en_US');
			wp_editor( $editing ? html_entity_decode($campaign->subscription_params()) : '', 'campaign_subscription_params_en_US', array( 
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
			$campaign->set_current_lang('');
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
	
	<h3>ENGLISH VERSION</h3>
	<div class="atcf-metabox-campaign-powers_params_en_US">
		<?php 
			$campaign->set_current_lang('en_US');
			wp_editor( $editing ? html_entity_decode($campaign->powers_params()) : '', 'campaign_powers_params_en_US', array( 
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
			$campaign->set_current_lang('');
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
	
	<h3>ENGLISH VERSION</h3>
	<div class="atcf-metabox-campaign-constitution_terms_en_US">
		<?php 
			$campaign->set_current_lang('en_US');
			wp_editor( $editing ? html_entity_decode($campaign->constitution_terms()) : '', 'campaign_constitution_terms_en_US', array( 
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
			$campaign->set_current_lang('');
		?>
	</div>
<?php
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
	$campaign = atcf_get_campaign( $post );

	/** Verification Field */
	wp_nonce_field( 'cf', 'cf-save' );
	do_action( 'atcf_metabox_campaign_info_before', $campaign );
?>
	<p>
		<label for="campaign_postname">
			URL (unique - celle-ci ne sera pas enregistrée si elle existe déjà) :
			<input type="text" name="campaign_postname" id="campaign_postname" value="<?php echo $post->post_name; ?>" />
		</label>
	</p>

	<p>
		<label for="_campaign_featured">
			<input type="checkbox" name="_campaign_featured" id="_campaign_featured" value="1" <?php checked( 1, $campaign->featured() ); ?> />
			Mise en avant
		</label>
	</p>
	
	<p>
		Prestataire de paiement : 
		<select name="<?php echo ATCF_Campaign::$key_payment_provider; ?>">
			<option value="<?php echo ATCF_Campaign::$payment_provider_mangopay; ?>" <?php selected($campaign->get_payment_provider(), ATCF_Campaign::$payment_provider_mangopay); ?>>Mangopay</option>
			<option value="<?php echo ATCF_Campaign::$payment_provider_lemonway; ?>" <?php selected($campaign->get_payment_provider(), ATCF_Campaign::$payment_provider_lemonway); ?>>Lemonway</option>
		</select>
	</p>
	
	
	<p>
		<strong><?php _e( 'Funding Type:', 'atcf' ); ?></strong>
	</p>

	<?php $types = atcf_campaign_types(); ?>
	<p>
		<?php foreach ( atcf_campaign_types_active() as $key => $desc ) : ?>
		<label for="campaign_type[<?php echo esc_attr( $key ); ?>]"><input type="radio" name="campaign_type" id="campaign_type[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $campaign->type() ); ?> /> <strong><?php echo $types[ $key ][ 'title' ]; ?></strong> &mdash; <?php echo $types[ $key ][ 'description' ]; ?></label><br />
		<?php endforeach; ?>
	</p>

	<p>
		<?php
		$fundingproject = 'checked="checked"';
		$fundingdevelopment = '';
		$fundingdonation = '';
		if ($campaign->funding_type() == 'fundingdevelopment') {
			$fundingproject = '';
			$fundingdevelopment = 'checked="checked"';
			$fundingdonation = '';
		}
		if ($campaign->funding_type() == 'fundingdonation') {
			$fundingproject = '';
			$fundingdevelopment = '';
			$fundingdonation = 'checked="checked"';
		}
		?>
		<label for="campaign_funding_type"><strong>Type de financement</strong></label><br />
		<input type="radio" name="campaign_funding_type" value="fundingproject" <?php echo $fundingproject; ?>>Royalties<br />
		<input type="radio" name="campaign_funding_type" value="fundingdevelopment" <?php echo $fundingdevelopment; ?>>Capital<br />
		<input type="radio" name="campaign_funding_type" value="fundingdonation" <?php echo $fundingdonation; ?>>Don<br />
	</p>
	
	<p>
		<label for="campaign_part_value"><strong><?php _e( 'Valeur de la part', 'yproject' ); ?></strong></label><br />
		<input type="text" name="campaign_part_value" id="campaign_part_value" value="<?php echo edd_format_amount($campaign->part_value() ); ?>" style="width:80px" /><?php echo edd_currency_filter( '' ); ?>
	</p>

	<p>
		<label for="campaign_location"><strong><?php _e( 'Location:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_location" id="campaign_location" value="<?php echo esc_attr( $campaign->location() ); ?>" class="regular-text" />
	</p>

	<style>#end-aa { width: 3.4em } #end-jj, #end-hh, #end-mn { width: 2em; }</style>

	
	<p>
		Lien video (supporté par oembed) :
		<input type="text" name="campaign_video" value="<?php echo esc_url( $campaign->video() ); ?>" />
	</p>
	
	<p>
		Lien Google Doc :
		<input type="text" name="campaign_google_doc" value="<?php echo $campaign->google_doc(); ?>" />
	</p>
	
	<p>
		Total des investissements par chèque :
		<input type="text" name="campaign_amount_check" value="<?php echo $campaign->current_amount_check_meta(FALSE); ?>" />
	</p>
	
	
	<p>
	    <h4 style="font-size: 1.2em">Paramètres de reversement :</h4>
	    <ul style="margin-left: 10px; list-style: disc;">
			
			<li>
				Première date de versement :
				<?php
				$fp_date = $campaign->first_payment_date();
				$fp_dd = mysql2date( 'd', $fp_date, false );
				$fp_mm = mysql2date( 'm', $fp_date, false );
				$fp_yy = mysql2date( 'Y', $fp_date, false );
				?>
				<input type="text" name="first-payment-dd" value="<?php echo esc_attr( $fp_dd ); ?>" size="2" maxlength="2" autocomplete="off" />
				<select name="first-payment-mm">
					<?php for ( $i = 1; $i < 13; $i = $i + 1 ) : $monthnum = zeroise($i, 2); ?>
						<option value="<?php echo $monthnum; ?>" <?php selected( $monthnum, $fp_mm ); ?>>
						<?php printf( '%1$s-%2$s', $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ); ?>
						</option>
					<?php endfor; ?>
				</select>
				<input type="text" name="first-payment-yy" value="<?php echo esc_attr( $fp_yy ); ?>" size="4" maxlength="4" autocomplete="off" />
				<input type="hidden" name="campaign_first_payment_date" value="1" />
			</li>
			
			<?php if ($campaign->funding_duration() > 0 && !empty($fp_date)): ?>
			<li>
				Dates et montants des versements :
				<?php $declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $campaign->ID ); ?>
				<ul style="margin-left: 10px; list-style: disc;">
					<?php if ($declaration_list): ?>
					<?php foreach ( $declaration_list as $declaration ): ?>
					<li>
						<input type="text" name="declaration-date_due-<?php echo $declaration->id; ?>" value="<?php echo $declaration->get_formatted_date(); ?>" />
						<?php if ($declaration->file_list != "" && $declaration->file_list != "1"): ?>
						Fichiers liés
						<?php else: ?>
						<input type="checkbox" name="declaration-files-<?php echo $declaration->id; ?>" <?php checked(($declaration->file_list != "")); ?> /> Activer l'upload de fichiers
						<?php endif; ?>
					</li>
					<?php endforeach; ?>
					<input type="hidden" name="save_declarations" value="1" />
					
					<?php else: ?>
					<li>
						<?php echo $fp_dd. '/' .$fp_mm. '/' .$fp_yy; ?> : 
						<input type="checkbox" name="declaration-files-first" <?php checked(($declaration->file_list != "")); ?> /> Activer l'upload de fichiers
					</li>
					<input type="hidden" name="add_first_declaration" value="1" />
					<?php endif; ?>
					
					<li>
						<input type="text" name="declaration-date_due-new" placeholder="jj/mm/aaaa" />
						<input type="submit" name="add_new_declaration" value="Ajouter une nouvelle date" />
					</li>
				</ul>
			</li>
			<?php else: ?>
				<li><span style="color: red;">Définissez les paramètres ci-dessus pour pouvoir paramétrer les sommes à reverser par date.</span></li>
			<?php endif; ?>
	    </ul>
	</p>
	<p>
		Titre du contrat :
		<input type="text" name="campaign_contract_title" value="<?php echo $campaign->contract_title(); ?>" />
	</p>
	<p>
		Titre du contrat ANGLAIS :
		<input type="text" name="campaign_contract_title_en_US" value="<?php $campaign->set_current_lang('en_US'); echo $campaign->contract_title(); $campaign->set_current_lang(''); ?>" />
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
