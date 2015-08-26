<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function atcf_get_campaign( $post_campaign ) {
	$campaign = new ATCF_Campaign( $post_campaign );
	return $campaign;
}

/**
 * Récupère la campagne en cours
 * @return objet campagne
 */
function atcf_get_current_campaign() {
	global $campaign_id, $is_campaign, $is_campaign_page, $post_campaign, $post;
	//Si l'id de campagne n'a pas encore été trouvé, on va le récupérer
	if (!isset($campaign_id)) {
		$campaign_id = '';
		if (is_category()) {
			global $cat;
			$campaign_id = atcf_get_campaign_id_from_category($cat);
		} else {
			$campaign_id = (isset($_GET['campaign_id'])) ? $_GET['campaign_id'] : $post->ID;
		}
	}
	
	//On a un id, alors on fait les vérifications pour savoir si c'est bien une campagne
	if (!empty($campaign_id)) {
		$is_campaign = (get_post_meta($campaign_id, 'campaign_goal', TRUE) != '');
		$is_campaign_page = $is_campaign && ($campaign_id == $post->ID);
		
		//Si c'est bien une campagne, on définit les objets utiles
		if ($is_campaign) {
			$post_campaign = get_post($campaign_id);
			$campaign = atcf_get_campaign($post_campaign);
		}
	}
	
	return $campaign;
}

function atcf_get_campaign_id_from_category($category) {
	$this_category = get_category($category);
	$this_category_name = $this_category->name;
	$name_exploded = explode('cat', $this_category_name);
	if (count($name_exploded) > 1) {
		$campaign_id = $name_exploded[1];
	}
	return $campaign_id;
}

function atcf_get_campaign_post_by_payment_id($payment_id) {
	$downloads = edd_get_payment_meta_downloads($payment_id); 
	$download_id = (is_array($downloads[0])) ? $downloads[0]["id"] : $downloads[0];
	return get_post($download_id);
}

/** Single Campaign *******************************************************/

class ATCF_Campaign {
	public $ID;
	public $data;
        
        /**
         * Number of days of vote
         * @var int
         */
        public static $vote_duration = 30;
        
        /**
         * Number of voters required to go to next step
         * @var int
         */
        public static $voters_min_required = 50;
        
        /**
         * The percent score of "yes" votes required to go to next step
         * @var int
         */
	public static $vote_score_min_required = 50;
        
        /**
         * The percent of min goal required in invest promises during vote
         * @var int
         */
        public static $vote_percent_invest_ready_min_required = 50;
        
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
		if (is_object($this->data)) {
		    $meta = apply_filters( 'atcf_campaign_meta_' . $key, $this->data->__get( $key ) );
		}

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
	public function rewards() {
		return $this->__get( 'campaign_rewards' );
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
	public function first_payment_date() {
	    return $this->__get('campaign_first_payment_date');
	}
	public function payment_list() {
	    $buffer = $this->__get('campaign_payment_list');
	    return json_decode($buffer, TRUE);
	}
	public function yearly_accounts_file($year) {
	    $attachments = get_posts( array(
		    'post_type' => 'attachment',
		    'post_parent' => $this->ID
	    ));
	    $buffer = array();
	    foreach ($attachments as $attachment) {
		    if ($attachment->post_title == 'Yearly Accounts ' . $year) {
			    $buffer[$attachment->ID]["url"] = get_the_guid($attachment->ID);
			    $buffer[$attachment->ID]["filename"] = get_post_meta($attachment->ID, "_wp_attached_file");
		    }
	    }
	    return $buffer;
	}
	public function payment_amount_for_year($year) {
	    $payment_list = $this->payment_list();
	    return $payment_list[$year];
	}
	public function payment_status_for_year($year) {
	    $payment_list = $this->payment_list_status();
	    return $payment_list[$year];
	}
	
	public function payment_list_status() {
	    $buffer = $this->__get('campaign_payment_list_status');
	    return json_decode($buffer, TRUE);
	}
	public function update_payment_status($date, $year, $post_id) {
	    $payment_list_status = $this->payment_list_status();
	    $payment_list_status[$year] = $post_id;
	    update_post_meta($this->ID, 'campaign_payment_list_status', json_encode($payment_list_status));
	}
        
        /**
         * Indique si le porteur de projet est autorisé à passer à l'étape
         * suivante par la modération
         * @return boolean
         */
        public function can_go_next_step(){
            $res = $this->__get('campaign_validated_next_step');
            if($res==1){
                return true;
            } else {
                return false; //Y compris le cas où il n'y a pas de valeur
            }
        }
        
        
        /**
         * Indique si le porteur de projet a déjà eu le message de bienvenue
         * en arrivant sur le tableau de bord
         * @return boolean
         */
        public function get_has_been_welcomed(){
            $res = $this->__get('campaign_has_been_welcomed');
            if($res==1){
                return true;
            } else {
                return false; //Y compris le cas où il n'y a pas de valeur
            }
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
	 * Deprecated : the meta is not used. Use post_author instead.
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Author
	 */
	public function author() {
		return $this->__get( 'campaign_author' );
	}
        
        public function post_author(){
                $post_campaign = get_post($this->ID);
                return $post_campaign->post_author;
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
	 * Campaign Contact Email
	 *
	 * @since Appthemer CrowdFunding 0.5
	 *
	 * @return sting Campaign Contact Phone
	 */
	public function contact_phone() {
		return $this->__get( 'campaign_contact_phone' );
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
        
        /**
	 * Campaign Begin Collecte Date
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Begin Collecte Date
	 */
	public function begin_collecte_date() {
		return mysql2date( 'Y-m-d H:i:s', $this->__get( 'campaign_begin_collecte_date' ), false );
	}
        
        /**
         * Set the date when vote finishes
         * @param type DateTime $newDate
         */
        public function set_end_vote_date($newDate){
            $res = update_post_meta($this->ID, 'campaign_end_vote', date_format($newDate, 'Y-m-d H:i:s'));
        }
        
        /**
         * Set the date when collecte is started
         * @param type DateTime $newDate
         */
        public function set_begin_collecte_date($newDate){
            $res = update_post_meta($this->ID, 'campaign_begin_collecte_date', date_format($newDate, 'Y-m-d H:i:s'));
        }
        
        /**
         * Set the date when collecte finishes
         * @param type DateTime $newDate
         */
        public function set_end_date($newDate){
            $res = update_post_meta($this->ID, 'campaign_end_date', date_format($newDate, 'Y-m-d H:i:s'));
        }

	public function end_vote() {
		return mysql2date( 'Y-m-d H:i:s', $this->__get( 'campaign_end_vote' ), false);
	}

	public function end_vote_date() {
		return mysql2date( 'Y-m-d H:i', $this->__get( 'campaign_end_vote' ), false);
	}
	public function end_vote_date_home() {
		setlocale(LC_TIME, array('fr_FR.UTF-8', 'fr_FR.UTF-8', 'fra'));
		return strftime("%d %B", strtotime(mysql2date( 'm/d', $this->__get( 'campaign_end_vote' ), false)));
	}
	public function end_vote_remaining() {
	    date_default_timezone_set('Europe/Paris');
	    $dateJour = strtotime(date("d-m-Y H:i"));
	    $fin = strtotime($this->__get( 'campaign_end_vote' ));
	    $buffer = floor(($fin - $dateJour) / 60 / 60 / 24);
	    $buffer = max(0, $buffer + 1);
	    return $buffer;
	}
	
	public function nb_voters() {
	    global $wpdb;
	    $table_name = $wpdb->prefix . "ypcf_project_votes";
	    $count_users = $wpdb->get_var( "SELECT count(id) FROM $table_name WHERE post_id = " . $this->ID );
	    return $count_users;
	}
        
        public function vote_invest_ready_min_required(){
            return $this->minimum_goal(false)*(ATCF_Campaign::$vote_percent_invest_ready_min_required/100);
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
			'log_type'       => /*atcf_has_preapproval_gateway()*/FALSE ? 'preapproval' : 'sale',
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
		$total = 0;

		if ($backers > 0) {
		    foreach ( $backers as $backer ) {
			    $payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			    $payment    = get_post( $payment_id );

			    if ( empty( $payment ) || $payment->post_status == 'pending' )
				    continue;

			    $total++;
		    }
		}
		
		return $total;
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
	
	public function is_remaining_time() {
		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );
		return ( $now < $expires );
	}
	
	/**
	 * Retourne une chaine avec le temps restant (J-6, H-2, M-23)
	 */
	public function time_remaining_str() {
		//Récupération de la date de fin et de la date actuelle
		$buffer = '';
		switch ($this->campaign_status()) {
			case 'vote':
			    $expires = strtotime( $this->end_vote() );
			    break;
			case 'collecte':
			    $expires = strtotime( $this->end_date() );
			    break;
			default:
			    $expires = 0;
			    break;
		}
		
		date_default_timezone_set("Europe/London");
		$now = current_time( 'timestamp' );
		
		//Si on a dépassé la date de fin, on retourne "-"
		if ( $now > $expires ) {
			$buffer = '-';
		} else {
			$diff = $expires - $now;
			$nb_days = floor($diff / (60 * 60 * 24));
			if ($nb_days > 0) {
				$buffer = 'J-' . $nb_days;
			} else {
				$nb_hours = floor($diff / (60 * 60));
				if ($nb_hours > 0) {
					$buffer = 'H-' . $nb_hours;
				} else {
					$nb_minutes = floor($diff / 60);
					$buffer = 'M-' . $nb_minutes;
				}
			}
		}
		    
		return $buffer;
	}
	/**
	 * Retourne une chaine complète avec le temps restant
	 */
	public function time_remaining_fullstr() {
		$buffer = '';
		
		date_default_timezone_set("Europe/London");
		$now = current_time( 'timestamp' );
		switch ($this->campaign_status()) {
			case 'vote':
			    $expires = strtotime( $this->end_vote() );
			    //Si on a dépassé la date de fin, on retourne "-"
			    if ( $now >= $expires ) {
				    $buffer = 'Vote termin&eacute;';
			    } else {
				    $diff = $expires - $now;
				    $nb_days = floor($diff / (60 * 60 * 24));
				    $plural = ($nb_days > 1) ? 's' : '';
				    $buffer = 'Plus que <b>' . $nb_days . '</b> jour'.$plural.' pour voter !';
				    if ($nb_days <= 0) {
					    $nb_hours = floor($diff / (60 * 60));
					    $plural = ($nb_hours > 1) ? 's' : '';
					    $buffer = 'Plus que <b>' . $nb_hours . '</b> heure'.$plural.' pour voter !';
					    if ($nb_hours <= 0) {
						    $nb_minutes = floor($diff / 60);
						    $plural = ($nb_minutes > 1) ? 's' : '';
						    $buffer = 'Plus que <b>' . $nb_minutes . '</b> minute'.$plural.' pour voter !';
					    }
				    }
			    }
			    break;
			case 'collecte':
			    $expires = strtotime( $this->end_date() );
			    //Si on a dépassé la date de fin, on retourne "-"
			    if ( $now >= $expires ) {
				    $buffer = 'Collecte termin&eacute;e';
			    } else {
				    $diff = $expires - $now;
				    $nb_days = floor($diff / (60 * 60 * 24));
				    $plural = ($nb_days > 1) ? 's' : '';
				    $buffer = 'Plus que <b>' . $nb_days . '</b> jour'.$plural.' !';
				    if ($nb_days <= 0) {
					    $nb_hours = floor($diff / (60 * 60));
					    $plural = ($nb_hours > 1) ? 's' : '';
					    $buffer = 'Plus que <b>' . $nb_hours . '</b> heure'.$plural.' !';
					    if ($nb_hours <= 0) {
						    $nb_minutes = floor($diff / 60);
						    $plural = ($nb_minutes > 1) ? 's' : '';
						    $buffer = 'Plus que <b>' . $nb_minutes . '</b> minute'.$plural.' !';
					    }
				    }
			    }
			    break;
			default:
			    $buffer = '-';
			    break;
		}
		    
		return $buffer;
	}

	public function can_user_wire($amount_part) {
		$min_wire = 200;
		return ($this->is_remaining_time() > 7 && $this->part_value() * $amount_part >= $min_wire);
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

			    if ( empty( $payment ) || $payment->post_status == 'pending' )
				    continue;

			    $total      = $total + edd_get_payment_amount( $payment_id );
		    }
		}
		
		$amount_check = $this->current_amount_check(FALSE);
		$total += $amount_check;
		
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
	
	public function current_amount_check($formatted = true){
		$amount_check = $this->__get( 'campaign_amount_check' );

		if ( ! is_numeric( $amount_check ) )
			$amount_check = 0;

		if ( $formatted ) {
		    $currency = edd_get_currency();
		    if ($currency == "EUR") {
			if (strpos($amount_check, '.00') !== false) $amount_check = substr ($amount_check, 0, -3);
			return $amount_check . ' &euro;';
		    } else {
			return edd_currency_filter( edd_format_amount( $amount_check ) );
		    }
		}

		return $amount_check;
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
	
        /**
         * Return payments data. 
         * This function is very slow, it is advisable to use it as few as possible
         * @return array
         */
	public function payments_data($skip_apis = FALSE) {
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
				$signsquid_infos = ($skip_apis == FALSE) ? signsquid_get_contract_infos_complete($contractid): '';
				$signsquid_status = ($signsquid_infos != '' && is_object($signsquid_infos)) ? $signsquid_infos->{'status'} : '';
				$signsquid_status_text = ypcf_get_signsquidstatus_from_infos($signsquid_infos, edd_get_payment_amount( $payment->ID ));
				$mangopay_id = edd_get_payment_key($payment->ID);
				if (strpos($mangopay_id, 'wire_') !== FALSE) {
					$mangopay_id = substr($mangopay_id, 5);
					$mangopay_contribution = ($skip_apis == FALSE) ? ypcf_mangopay_get_withdrawalcontribution_by_id($mangopay_id) : '';
					$mangopay_is_completed = ($mangopay_contribution != '' && $mangopay_contribution->Status == 'ACCEPTED') ? 'Oui' : 'Non';
					$mangopay_is_succeeded = $mangopay_is_completed;
				} else {
					$mangopay_contribution = ($skip_apis == FALSE) ? ypcf_mangopay_get_contribution_by_id($mangopay_id) : '';
					$mangopay_is_completed = ($mangopay_contribution != '' && isset($mangopay_contribution->IsCompleted) && $mangopay_contribution->IsCompleted) ? 'Oui' : 'Non';
					$mangopay_is_succeeded = ($mangopay_contribution != '' && isset($mangopay_contribution->IsSucceeded) && $mangopay_contribution->IsSucceeded) ? 'Oui' : 'Non';
				}


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
		
		if (count($attachments) > 0) {
			//Si on en trouve bien une avec le titre "image_home" on prend celle-là
			foreach ($attachments as $attachment) {
				if ($attachment->post_title == $type) $image_obj = wp_get_attachment_image_src($attachment->ID, "full");
			}
			//Sinon on prend la première image rattachée à l'article
			if ($force && $image_obj == '') $image_obj = wp_get_attachment_image_src($attachments[0]->ID, "full");
			if ($image_obj != '') $img_src = $image_obj[0];
		}
		
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
	
	public function current_user_can_edit() {
		//Il faut qu'il soit connecté
		if (!is_user_logged_in()) return FALSE;
		
		//On autorise les admin
		if (current_user_can('manage_options')) return TRUE;
	    
		//On autorise l'auteur
		$post_campaign = get_post($this->ID);
		$current_user = wp_get_current_user();
		$current_user_id = $current_user->ID;
		if ($current_user_id == $post_campaign->post_author) return TRUE;
		
		//On autorise les personnes de l'équipe projet
		$project_api_id = BoppLibHelpers::get_api_project_id($this->ID);
		$team_member_list = BoppLib::get_project_members_by_role($project_api_id, BoppLibHelpers::$project_team_member_role['slug']);
		foreach ($team_member_list as $team_member) {
			if ($current_user_id == $team_member->wp_user_id) return TRUE;
		}
		
		return FALSE;
	}
	
	public function get_documents_list() {
		$attachments = get_posts( array(
			'post_type' => 'projectdoc',
			'post_parent' => $this->ID,
			'post_status'	=> 'inherit'
		));
		return $attachments;
	}
	
	public function add_document($title, $url) {
		$args = array(
			'post_type'	=> 'projectdoc',
			'post_status'	=> 'inherit',
			'post_title'	=> $title,
			'post_content'	=> $url,
			'post_author'	=> $this->data->post_author,
			'post_parent'	=> $this->ID
		);
		wp_insert_post($args, true);
	}
	
	public function delete_document($id) {
		$post = get_post($id);
		if ($post->post_parent == $this->ID) wp_delete_post($id);
	}
        
        /**
         * Gère la validation de modération pour le passage à l'étape suivante
         * 
         * $value : Valeur du flag de validation (true si le PP peut passer à
         *      l'étape suivante, false sinon)
         */
        public function set_validation_next_step($value){
            if($value==0||$value==1) {
                $res = update_post_meta($this->ID, 'campaign_validated_next_step', $value);
            }            
        }
        
        /**
         * Setter si le PP a déjà vu la LB de bienvenue sur son TB
         * 
         * $value : Valeur du flag (true si le PP a déjà vu la LB, false sinon)
         */
        public function set_has_been_welcomed($value){
            if($value==0||$value==1) {
                $res = update_post_meta($this->ID, 'campaign_has_been_welcomed', $value);
            }
        }
        
        public function set_status($newstatus){
            if(array_key_exists($newstatus, ATCF_Campaign::$status_list)){
                $res = update_post_meta($this->ID, 'campaign_vote', $newstatus);
            }
        }
        
        /**
         * Provides various words to describe the campaign according to it funding type :
         * @return array
         */
        public function funding_type_vocabulary(){
            switch ($this->funding_type()) {
                case 'fundingdonation' :
                    return array(
                    'investor_name' => 'contributeur',
                    'investor_action' => 'contribution',
                    'action_feminin' => true,
                    'investor_verb' => 'contribu&eacute;'
                    );
                default :
                    return array(
                    'investor_name' => 'investisseur',
                    'investor_action' => 'investissement',
                    'action_feminin' => false,
                    'investor_verb' => 'investi'
                    );
            }
            return array();
        }
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