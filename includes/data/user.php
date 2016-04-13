<?php
/**
 * Lib de gestion des utilisateurs
 */
class WDGUser {
	public static $key_validated_general_terms_version = 'validated_general_terms_version';
	public static $edd_general_terms_version = 'terms_general_version';
	public static $edd_general_terms_excerpt = 'terms_general_excerpt';
	
	/**
	 * @var WP_User 
	 */
	public $wp_user;
	private $wallet_details;
	
	protected static $_current = null;
	/**
	 * @return WDGUser
	 */
	public static function current() {
		if ( is_null( self::$_current ) ) {
			self::$_current = new self();
		}
		return self::$_current;
	}
	
	public function __construct($user_id = '') {
		if ($user_id === '') {
			$this->wp_user = wp_get_current_user();
		} else {
			$this->wp_user = new WP_User($user_id);
		}
	}
	
	/**
	 * Détermine si l'utilisateur est admin
	 * @return boolean
	 */
	public function is_admin() {
		return ($this->wp_user->has_cap('manage_options'));
	}
	
	public static $key_bank_holdername = "bank_holdername";
	public static $key_bank_iban = "bank_iban";
	public static $key_bank_bic = "bank_bic";
	public static $key_bank_address1 = "bank_address1";
	
	/**
	 * Retourne une info correspondante au IBAN
	 * @param string $info
	 * @return string
	 */
	public function get_iban_info( $info ) {
		return get_user_meta( $this->wp_user->ID, "bank_" . $info, TRUE);
	}
	
	/**
	 * Est-ce que le RIB est enregistré ?
	 */
	public function has_saved_iban() {
		$saved_holdername = $this->get_iban_info("holdername");
		$saved_iban = $this->get_iban_info("iban");
		return (!empty($saved_holdername) && !empty($saved_iban));
	}
	
	/**
	 * Enregistre le RIB
	 */
	public function save_iban( $holder_name, $iban, $bic, $address1, $address2 = '' ) {
		update_user_meta( $this->wp_user->ID, WDGUser::$key_bank_holdername, $holder_name );
		update_user_meta( $this->wp_user->ID, WDGUser::$key_bank_iban, $iban );
		update_user_meta( $this->wp_user->ID, WDGUser::$key_bank_bic, $bic );
		update_user_meta( $this->wp_user->ID, WDGUser::$key_bank_address1, $address1 );
		if ( !empty( $address2 ) ) {
			update_user_meta( $this->wp_user->ID, "bank_address2", $address2 );
		}
	}
	
/*******************************************************************************
 * Gestion Lemonway
*******************************************************************************/
	private function get_wallet_details( $reload = false ) {
		if ( !isset($this->wallet_details) || empty($this->wallet_details) || $reload == true ) {
			$this->wallet_details = LemonwayLib::wallet_get_details($this->get_lemonway_id());
		}
		return $this->wallet_details;
	}
	
	/**
	 * Enregistrement sur Lemonway
	 */
	public function register_lemonway() {
		//Vérifie que le wallet n'est pas déjà enregistré
		$wallet_details = $this->get_wallet_details();
		if ( !isset($wallet_details->NAME) || empty($wallet_details->NAME) ) {
			return LemonwayLib::wallet_register( $this->get_lemonway_id(), $this->wp_user->user_email, $this->get_lemonway_title(), $this->wp_user->user_firstname, $this->wp_user->user_lastname );
		}
		return TRUE;
	}
	
	/**
	 * Détermine si les informations nécessaires sont remplies : mail, prénom, nom
	 */
	public function can_register_lemonway() {
		$buffer = ($this->wp_user->user_email != "")
				&& ($this->wp_user->user_firstname == "")
				&& ($this->wp_user->user_lastname == "");
		return $buffer;
	}
	
	/**
	 * Définit l'identifiant de l'orga sur lemonway
	 */
	public function get_lemonway_id() {
		return 'USERW'.$this->wp_user->ID;
	}
	
	/**
	 * Récupère le genre de l'utilisateur, formatté pour lemonway
	 * @return string
	 */
	public function get_lemonway_title() {
		$buffer = "U";
		if ($this->wp_user->get('user_gender') == "male") {
			$buffer = "M";
		} elseif ($this->wp_user->get('user_gender') == "female") {
			$buffer = "F";
		}
		return $buffer;
	}
	
	/**
	 * Retourne le montant actuel sur le compte bancaire
	 * @return number
	 */
	public function get_lemonway_wallet_amount() {
		$wallet_details = $this->get_wallet_details();
		return $wallet_details->BAL;
	}
	
	/**
	 * Transfère l'argent du porte-monnaie utilisateur vers son compte bancaire
	 */
	public function transfer_wallet_to_bankaccount() {
		$buffer = FALSE;
		
		//Il faut qu'un iban ait déjà été enregistré
		if ($this->has_saved_iban()) {
			//Vérification que des IBANS existent
			$wallet_details = $this->get_wallet_details();
			$first_iban = $wallet_details->IBANS->IBAN;
			//Sinon on l'enregistre auprès de Lemonway
			if (empty($first_iban)) {
				$saved_holdername = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_holdername, TRUE );
				$saved_iban = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_iban, TRUE );
				$saved_bic = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_bic, TRUE );
				$saved_dom1 = get_user_meta( $this->wp_user->ID, WDGUser::$key_bank_address1, TRUE );
				$result_iban = LemonwayLib::wallet_register_iban( $this->get_lemonway_id(), $saved_holdername, $saved_iban, $saved_bic, $saved_dom1 );
				if ($result_iban == FALSE) {
					$buffer = LemonwayLib::get_last_error_message();
				}
			}
			
			if ($buffer == FALSE) {
				//Exécution du transfert vers le compte du montant du solde
				$amount = $wallet_details->BAL;
				$result_transfer = LemonwayLib::ask_transfer_to_iban($this->get_lemonway_id(), $wallet_details->BAL);
				$buffer = ($result_transfer->TRANS->HPAY->ID) ? "success" : $result_transfer->TRANS->HPAY->MSG;
				if ($buffer == "success") {
					NotificationsEmails::wallet_transfer_to_account( $this->wp_user->ID, $amount );
					$withdrawal_post = array(
						'post_author'   => $this->wp_user->ID,
						'post_title'    => $wallet_details->BAL,
						'post_content'  => print_r($result_transfer, TRUE),
						'post_status'   => 'publish',
						'post_type'	    => 'withdrawal_order_lw'
					);
					wp_insert_post( $withdrawal_post );
				}
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Retourne true si l'iban est enregistré sur lemonway
	 */
	public function has_registered_iban() {
		$buffer = true;
		$wallet_details = $this->get_wallet_details();
		$first_iban = $wallet_details->IBANS->IBAN;
		if (empty($first_iban)) {
			$buffer = false;
		}
		return $buffer;
	}
    
/*******************************************************************************
 * Fonctions statiques
*******************************************************************************/
	/**
	 * Vérifie si l'utilisateur a bien validé les cgu
	 * @global type $edd_options
	 * @global type $current_user
	 * @param type $user_id
	 * @return type
	 */
	public static function has_validated_general_terms($user_id = FALSE) {
		global $edd_options;
		if ($user_id === FALSE) {
			global $current_user;
			$user_id = $current_user->ID;
		}
		$current_signed_terms = get_user_meta($user_id, WDGUser::$key_validated_general_terms_version, TRUE);
		return ($current_signed_terms == $edd_options[WDGUser::$edd_general_terms_version]);
	}
	
	/**
	 * Vérifie si le formulaire est complet et valide les cgu
	 * @global type $edd_options
	 * @global type $current_user
	 * @param type $user_id
	 * @return boolean
	 */
	public static function check_validate_general_terms($user_id = FALSE) {
		//Vérification des champs de formulaire
		if (WDGUser::has_validated_general_terms($user_id)) return FALSE;
		if (!isset($_POST['action']) || $_POST['action'] != 'validate-terms') return FALSE;
		if (!isset($_POST['validate-terms-check']) || !$_POST['validate-terms-check']) return FALSE;
			    
		global $edd_options;
		if ($user_id === FALSE) {
			global $current_user;
			$user_id = $current_user->ID;
		}
		update_user_meta($user_id, WDGUser::$key_validated_general_terms_version, $edd_options[WDGUser::$edd_general_terms_version]);
	}
	
	/**
	 * Vérifie si il est nécessaie d'afficher la lightbox de cgu
	 * @global type $post
	 * @param type $user_id
	 * @return type
	 */
	public static function must_show_general_terms_block($user_id = FALSE) {
		global $post, $edd_options;
		if (isset($edd_options[WDGUser::$edd_general_terms_version]) && !empty($edd_options[WDGUser::$edd_general_terms_version])) $isset_general_terms = TRUE;
		//On affiche la lightbox de cgu si : l'utilisateur est connecté, il n'est pas sur la page cgu, il ne les a pas encore validées
		return (is_user_logged_in() && $post->post_name != 'cgu' && !WDGUser::has_validated_general_terms($user_id) && $isset_general_terms);
	}
	
	/**
	 * Récupération de la liste des id des projets auxquels un utilisateur est lié
	 * @param type $user_id
	 * @param type $complete
	 * @return array
	 */
	public static function get_projects_by_id($user_id, $complete = FALSE) {
		$buffer = array();
		
		//Récupération des projets dont l'utilisateur est porteur
		$campaign_status = array('publish');
		if ($complete === TRUE) {
			array_push($campaign_status, 'private');
		}
		$args = array(
			'post_type' => 'download',
			'author' => $user_id,
			'post_status' => $campaign_status
		);
		if ($complete === FALSE) {
			$args['meta_key'] = 'campaign_vote';
			$args['meta_compare'] = '!='; 
			$args['meta_value'] = 'preparing';
		}
		query_posts($args);
		if (have_posts()) {
			while (have_posts()) {
				the_post();
				array_push($buffer, get_the_ID());
			}
		}
		wp_reset_query();
		
		//Récupération des projets dont l'utilisateur appartient à l'équipe
		$api_user_id = BoppLibHelpers::get_api_user_id($user_id);
		$project_list = BoppUsers::get_projects_by_role($api_user_id, BoppLibHelpers::$project_team_member_role['slug']);
		if (!empty($project_list)) {
			foreach ($project_list as $project) {
				array_push($buffer, $project->project_wp_id);
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Définit la page vers laquelle il faudrait rediriger l'utilisateur lors de sa connexion
	 * @global type $post
	 * @return type
	 */
	public static function get_login_redirect_page() {
		global $post;
		$buffer = home_url();
		
		//Si on est sur la page de connexion ou d'identification,
		// il faut retrouver la page précédente et vérifier qu'elle est de WDG
		if ($post->post_name == 'connexion' || $post->post_name == 'register') {
			//Récupération de la page précédente
			$referer_url = wp_get_referer();
			//On vérifie que l'url appartient bien au site en cours (home_url dans referer)
			if (strpos($referer_url, $buffer) !== FALSE) {
				
				//Si la page précédente était déjà la page connexion ou enregistrement, 
				// on tente de voir si la redirection était passée en paramètre
				if (strpos($referer_url, '/connexion') !== FALSE || strpos($referer_url, '/register') !== FALSE) {
					$posted_redirect_page = filter_input(INPUT_POST, 'redirect-page');
					if (!empty($posted_redirect_page)) {
						$buffer = $posted_redirect_page;
					}
					
				//Sinon on peut effectivement rediriger vers la page précédente
				} else {
					$buffer = $referer_url;
				}
			}
			
		//Sur les autres pages
		} else {
			//On tente de voir si une redirection n'avait pas été demandée auparavant
			$posted_redirect_page = filter_input(INPUT_POST, 'redirect-page');
			if (!empty($posted_redirect_page)) {
				$buffer = $posted_redirect_page;
			
			//Sinon, on récupère simplement la page en cours
			} else {
				if (isset($post->ID)) {
					$buffer = get_permalink($post->ID);
				}
			}
		}
		
		return $buffer;
	}
    
	/**
	 * Tente de se connecter au site
	 * @return boolean
	 */
	public static function login() {
		//Pas la peine de tenter un login si l'utilisateur est déjà connecté
		if (is_user_logged_in()) { return FALSE; }
		//Pas la peine de tenter un login si on ne l'a pas demandé
		$posted_login_form = filter_input(INPUT_POST, 'login-form');
		if (empty($posted_login_form)) { return FALSE; }
		
		remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
		add_filter('authenticate', 'WDGUser::filter_login_email', 20, 3);
		add_action('wp_login', 'WDGUser::redirect_after_login');
		add_action('wp_login_failed', 'WDGUser::redirect_after_login_failed'); 
		global $signon_errors;
		$signon_result = wp_signon('', is_ssl());
		if (is_wp_error($signon_result) && !isset($signon_errors)) {
			$signon_errors = $signon_result;
		}
	}
	
	/**
	 * permet d'autoriser l'identification par email
	 * @param type $user
	 * @param type $username
	 * @param type $password
	 * @return type
	 */
	public static function filter_login_email( $user, $username, $password ) {
		if ( is_a( $user, 'WP_User' ) ) return $user;
		
		if (empty($username) || empty($password)) {
			global $signon_errors;
			$signon_errors = new WP_Error();
			$signon_errors->add('empty_authentication', __('Champs vides', 'yproject'));
			WDGUser::redirect_after_login_failed();
		}

		if ( !empty( $username ) ) {
			$username = str_replace( '&', '&amp;', stripslashes( $username ) );
			$user = get_user_by( 'email', $username );
			if ( isset( $user, $user->user_login, $user->user_status ) && 0 == (int) $user->user_status )
				$username = $user->user_login;
		}

		return wp_authenticate_username_password( null, $username, $password );
	}
	
	/**
	 * Détecte et gère l'affichage des erreurs de login
	 * @global type $signon_errors
	 * @return type
	 */
	public static function display_login_errors() {
		global $signon_errors;
		$buffer = '';
		if (is_wp_error($signon_errors)) {
			switch ($signon_errors->get_error_code()) {
				case 'empty_authentication':
				case 'empty_username':
				case 'empty_password':
					$buffer = __('Merci de saisir votre identifiant et votre mot de passe.', 'yproject');
					break;
				case 'invalid_username':
					$buffer = __('Cet utilisateur n&apos;existe pas.', 'yproject');
					break;
				case 'incorrect_password':
					$buffer = __('Le mot de passe saisi ne correspond pas.', 'yproject');
					break;
			}
		}
		return $buffer;
	}
	
	/**
	 * Retourne si il y a eu des erreurs pendant le login
	 * @global type $signon_errors
	 * @return type
	 */
	public static function has_login_errors() {
		global $signon_errors; return is_wp_error($signon_errors);
	}
	
	public static function register() {
		if (is_user_logged_in()) { return FALSE; }
	}
	
	/**
	 * Redirige après la connexion
	 */
	public static function redirect_after_login() {
		//Récupération de la page de redirection à appliquer
		$posted_redirect_page = filter_input(INPUT_POST, 'redirect-page');
		//Si ce n'est pas défini, on retourne à l'accueil
		if (empty($posted_redirect_page)) { wp_safe_redirect(home_url()); }
		
		//Vérification si l'url ne contient pas de liens vers l'admin
		if (strpos($posted_redirect_page, 'wp-admin') !== FALSE) {
			wp_safe_redirect(home_url());
		} else {
			wp_safe_redirect($posted_redirect_page);
		}
		
		exit();
	}
	
	/**
	 * Redirige après une connexion échouée
	 */
	public static function redirect_after_login_failed() {
		$posted_redirect_error_page = filter_input(INPUT_POST, 'redirect-error');
		if (!empty($posted_redirect_error_page)) {
			wp_safe_redirect($posted_redirect_error_page);
			exit();
		}
	}
}