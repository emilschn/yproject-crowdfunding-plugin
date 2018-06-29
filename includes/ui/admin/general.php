<?php
// Blocks direct access
if ( ! function_exists( 'is_admin' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Modifie les posts admin
 */
class WDG_Admin_General {
	
	private static $tabs = array(
		'general' => "G&eacute;n&eacute;ral",
		'email' => "E-mails",
		'terms' => "CGU",
		'project' => "Page projet",
		'investment' => "Investissement",
		'royalties' => "Royalties",
		'translations' => "Traductions"
	);
	private static $langs = array(
		'en_US' => 'Anglais'
	);
	private static $translation_properties = array(
		'investment_generalities' => 'Explications g&eacute;n&eacute;rales sur l&apos;investissement',
		'standard_contract' => 'Contrat d&apos;investissement standard'
	);

	public static function add_actions() {
		add_action( 'admin_init', 'WDG_Admin_General::save' );
		add_action( 'admin_menu', 'WDG_Admin_General::set_menu' );
	}
	
	public static function set_menu() {
		add_options_page( 'WE DO GOOD', 'WE DO GOOD', 'manage_options', 'wdg-settings', 'WDG_Admin_General::display' );
	}
	
	public static function save() {
		//Sauvegarde toutes les données
		$need_save = filter_input(INPUT_POST, 'save-wdg');
		if (!empty($need_save)) {
			$active_tab = filter_input( INPUT_GET, 'tab' );
			if ( empty( $active_tab ) ) {
				$active_tab = 'general';
			}
			$display_function = 'save_' . $active_tab;
			WDG_Admin_General::{ $display_function }();
		}
	}
	
	public static function save_general() {
		// Données de la plateforme
		$option_platform = array();
		$option_platform[ "context" ] = filter_input( INPUT_POST, 'platform_context' );
		update_option( 'wdg_platform', $option_platform );
	}
	
	public static function save_email() {
		$edd_settings = get_option( 'edd_settings' );
		$edd_settings[ 'header_global_mail' ] = filter_input( INPUT_POST, 'email_header' );
		$edd_settings[ 'footer_global_mail' ] = filter_input( INPUT_POST, 'email_footer' );
		update_option( 'edd_settings', $edd_settings );
	}
	
	public static function save_terms() {
		$edd_settings = get_option( 'edd_settings' );
		$edd_settings[ 'terms_general_version' ] = filter_input( INPUT_POST, 'terms_general_version' );
		$edd_settings[ 'terms_general_excerpt' ] = filter_input( INPUT_POST, 'terms_general_excerpt' );
		$edd_settings[ 'agree_text' ] = filter_input( INPUT_POST, 'agree_text' );
		update_option( 'edd_settings', $edd_settings );
	}
	
	public static function save_project() {
		$edd_settings = get_option( 'edd_settings' );
		$edd_settings[ 'default_pitch' ] = filter_input( INPUT_POST, 'default_pitch' );
		$edd_settings[ 'default_positive_impacts' ] = filter_input( INPUT_POST, 'default_positive_impacts' );
		$edd_settings[ 'default_strategy' ] = filter_input( INPUT_POST, 'default_strategy' );
		$edd_settings[ 'default_financiary' ] = filter_input( INPUT_POST, 'default_financiary' );
		$edd_settings[ 'default_team' ] = filter_input( INPUT_POST, 'default_team' );
		update_option( 'edd_settings', $edd_settings );
	}
	
	public static function save_investment() {
		$edd_settings = get_option( 'edd_settings' );
		$edd_settings[ 'investment_generalities' ] = filter_input( INPUT_POST, 'investment_generalities' );
		$edd_settings[ 'investment_terms' ] = filter_input( INPUT_POST, 'investment_terms' );
		$edd_settings[ 'preinvest_warning' ] = filter_input( INPUT_POST, 'preinvest_warning' );
		$edd_settings[ 'standard_contract' ] = filter_input( INPUT_POST, 'standard_contract' );
		$edd_settings[ 'accounting_fiscal_info' ] = filter_input( INPUT_POST, 'accounting_fiscal_info' );
		$edd_settings[ 'lemonway_generalities' ] = filter_input( INPUT_POST, 'lemonway_generalities' );
		update_option( 'edd_settings', $edd_settings );
	}
	
	public static function save_royalties() {
		$option_roi = array();
		$option_roi[ "info_yearly_certificate" ] = filter_input( INPUT_POST, 'royalties_yearly_certificate' );
		update_option( WDGROI::$option_name, $option_roi );
	}
	
	public static function save_translations() {
		foreach (WDG_Admin_General::$langs as $lang_key => $lang_name) {
			$option = array();
			foreach (WDG_Admin_General::$translation_properties as $property_key => $property_label) {
				$value = filter_input(INPUT_POST, $property_key .'_'. $lang_key);
				$option[$property_key] = $value;
			}
			update_option(ATCF_CrowdFunding::$option_name .'_'. $lang_key, $option);
		}
	}
	
	public static function display() {
		if (!current_user_can('manage_options')) { wp_die( __('You do not have sufficient permissions to access this page.') ); }
		$active_tab = filter_input( INPUT_GET, 'tab' );
		if ( empty( $active_tab ) ) {
			$active_tab = 'general';
		}
		?>

		<form method="post">
			<h2>Paramétrage plateforme</h2>
			
			<h2 class="nav-tab-wrapper">
				<?php foreach( WDG_Admin_General::$tabs as $tab_id => $tab_name ): ?>
					<?php
					$tab_url = add_query_arg( array(
						'settings-updated' => false,
						'tab' => $tab_id
					) );

					$active = $active_tab == $tab_id ? ' nav-tab-active' : '';
					?>

					<a href="<?php echo esc_url( $tab_url ); ?>" title="<?php echo esc_attr( $tab_name ); ?>" class="nav-tab<?php echo $active; ?>"><?php echo esc_html( $tab_name ); ?></a>
				<?php endforeach; ?>
			</h2>

			<?php
			$display_function = 'display_' . $active_tab;
			WDG_Admin_General::{ $display_function }();
			?>

			<input type="hidden" name="save-wdg" value="1" />
			<input type="hidden" name="save-tab" value="<?php echo $active_tab; ?>" />
			<p class="submit"><input type="submit" name="Submit" class="button-primary" value="Enregistrer" /></p>
		</form>
		<?php
	}
	
	public static function display_general() {
		?>
		<h3>Contexte de la plateforme</h3>
		<label for="platform_context">
			<select id="platform_context" name="platform_context">
				<option value="wedogood" <?php selected( ATCF_CrowdFunding::get_platform_context(), "wedogood" ); ?>>WE DO GOOD</option>
				<option value="royaltycrowdfunding" <?php selected( ATCF_CrowdFunding::get_platform_context(), "royaltycrowdfunding" ); ?>>royaltycrowdfunding.fr</option>
			</select>
		</label>
		<?php
	}
	
	public static function display_email() {
		$edd_settings = get_option( 'edd_settings' );
		?>
		<h3>En-tête des e-mails envoyés par la plateforme</h3>
		<label for="email_header">
			Ce contenu sera intégré en haut des mails suivants :
            <ul>
				<li>Notifications d\'actualités de projet</li>
				<li>Mail direct des porteurs de projets aux investisseurs</li>
				<li>Confirmation d\'investissement/don</li>
				<li>Notifications pour Porteur de Projet : nouvel investissement/don, nouveau commentaire</li>
			</ul>
			<?php wp_editor( $edd_settings[ 'header_global_mail' ], 'email_header' ); ?>
		</label>
		<br /><br />
		
		<h3>Pied de page des mails envoyés par la plateforme</h3>
		<label for="email_footer">
			<?php wp_editor( $edd_settings[ 'footer_global_mail' ], 'email_footer' ); ?>
		</label>
		<?php
	}
	
	public static function display_terms() {
		$edd_settings = get_option( 'edd_settings' );
		?>
		<h3>Version des conditions générales</h3>
		<label for="terms_general_version">
			<input type="text" value="<?php echo $edd_settings[ 'terms_general_version' ]; ?>" name="terms_general_version" />
		</label>
		<br /><br />
		
		<h3>Extrait des conditions générales à afficher dans la lightbox</h3>
		<label for="terms_general_excerpt">
			<?php wp_editor( $edd_settings[ 'terms_general_excerpt' ], 'terms_general_excerpt' ); ?>
		</label>
		<br /><br />
		
		<h3>Conditions particulières</h3>
		<label for="agree_text">
			<?php wp_editor( $edd_settings[ 'agree_text' ], 'agree_text' ); ?>
		</label>
		<?php
	}
	
	public static function display_project() {
		$edd_settings = get_option( 'edd_settings' );
		?>
		<h3>Section "Pitch" par défaut</h3>
		<label for="default_pitch">
			<?php wp_editor( $edd_settings[ 'default_pitch' ], 'default_pitch' ); ?>
		</label>
		<br /><br />
		
		<h3>Section "Impacts positifs" par défaut</h3>
		<label for="default_positive_impacts">
			<?php wp_editor( $edd_settings[ 'default_positive_impacts' ], 'default_positive_impacts' ); ?>
		</label>
		<br /><br />
		
		<h3>Section "Stratégie" par défaut</h3>
		<label for="default_strategy">
			<?php wp_editor( $edd_settings[ 'default_strategy' ], 'default_strategy' ); ?>
		</label>
		<br /><br />
		
		<h3>Section "Données financières" par défaut</h3>
		<label for="default_financiary">
			<?php wp_editor( $edd_settings[ 'default_financiary' ], 'default_financiary' ); ?>
		</label>
		<br /><br />
		
		<h3>Section "Equipe" par défaut</h3>
		<label for="default_team">
			<?php wp_editor( $edd_settings[ 'default_team' ], 'default_team' ); ?>
		</label>
		<br /><br />
		
		<?php
	}
	
	public static function display_investment() {
		$edd_settings = get_option( 'edd_settings' );
		?>
		<h3>Avertissements sur l&apos;investissement</h3>
		<label for="investment_generalities">
			<?php wp_editor( $edd_settings[ 'investment_generalities' ], 'investment_generalities' ); ?>
		</label>
		<br><br>
		
		<h3>Avertissements sur le pré-investissement</h3>
		<label for="preinvest_warning">
			<?php wp_editor( $edd_settings[ 'preinvest_warning' ], 'preinvest_warning' ); ?>
		</label>
		<br><br>
		
		<h3>Modalités d'investissement</h3>
		<label for="investment_terms">
			<?php wp_editor( $edd_settings[ 'investment_terms' ], 'investment_terms' ); ?>
		</label>
		<br><br>
		
		<h3>Contrat d&apos;investissement standard</h3>
		<label for="standard_contract">
			<?php wp_editor( $edd_settings[ 'standard_contract' ], 'standard_contract' ); ?>
		</label>
		<br><br>
		
		<h3>Informations comptables et fiscales pour attestation</h3>
		<label for="accounting_fiscal_info">
			<?php wp_editor( $edd_settings[ 'accounting_fiscal_info' ], 'accounting_fiscal_info' ); ?>
		</label>
		<br><br>
		
		<h3>Informations sur Lemon Way (compte utilisateur, ...)</h3>
		<label for="lemonway_generalities">
			<?php wp_editor( $edd_settings[ 'lemonway_generalities' ], 'lemonway_generalities' ); ?>
		</label>
		<br><br>
		
		<?php
	}
	
	public static function display_royalties() {
		?>
		<h2>Royalties</h2>
		<label for="royalties_yearly_certificate">Informations relatives au site de l'administration :</label><br />
		<?php wp_editor( WDGROI::get_parameter( 'info_yearly_certificate' ), 'royalties_yearly_certificate' ); ?>
		<?php
	}
	
	public static function display_translations() {
		?>
		<h2>Traduction WE DO GOOD</h2>
		<?php foreach (WDG_Admin_General::$langs as $lang_key => $lang_name): ?>
			<h3><?php echo $lang_name; ?></h3>

			<?php foreach (WDG_Admin_General::$translation_properties as $property_key => $property_label): ?>
			<label for="<?php echo $property_key; ?>_<?php echo $lang_key; ?>"><?php echo $property_label; ?></label>
			<?php wp_editor(ATCF_CrowdFunding::get_translated_setting($property_key, $lang_key), $property_key .'_'. $lang_key); ?>
			<br /><br />
			<?php endforeach; ?>
		<?php endforeach; ?>
		<?php
	}
}
WDG_Admin_General::add_actions();