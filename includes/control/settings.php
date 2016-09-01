<?php
/**
 * Supplement some settings stuff.
 *
 * @since Appthemer CrowdFunding 0.7
 */

/**
 * Add pages to settings. Splice and resplice. Ghetto.
 *
 * @since AppThemer Crowdfunding 0.7
 * 
 * @param $settings
 * @return $settings
 */
function atcf_settings_general_pages( $settings ) {
        $pages = get_pages();
        $pages_options = array( 0 => '' ); // Blank option
        if ( $pages ) {
                foreach ( $pages as $page ) {
                        $pages_options[ $page->ID ] = $page->post_title;
                }
        }

        $keys = array_keys( $settings );
        $vals = array_values( $settings );

        $spot = array_search( 'failure_page', $keys ) + 1;

        $keys2 = array_splice( $keys, $spot );
        $vals2 = array_splice( $vals, $spot );

        $keys[] = 'faq_page';
        $keys[] = 'submit_page';
        $keys[] = 'profile_page';
        $keys[] = 'login_page';
        $keys[] = 'register_page';
		$keys[] = 'update_page';

        $vals[] =  array(
                'id'      => 'faq_page',
                'name'    => __( 'FAQ Page', 'atcf' ),
                'desc'    => __( 'A page with general information about your site. Fees, etc.', 'atcf' ),
                'type'    => 'select',
                'options' => $pages_options
        );

        $vals[] =  array(
                'id'      => 'submit_page',
                'name'    => __( 'Submit Page', 'atcf' ),
                'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_submit]</code> shortcode.', 'atcf' ),
                'type'    => 'select',
                'options' => $pages_options
        );

        $vals[] =  array(
                'id'      => 'upade_page',
                'name'    => __( 'Project update', 'atcf' ),
                'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_update]</code> shortcode.', 'atcf' ),
                'type'    => 'select',
                'options' => $pages_options
        );
		
        $vals[] =  array(
                'id'      => 'profile_page',
                'name'    => __( 'Profile Page', 'atcf' ),
                'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_profile]</code> shortcode.', 'atcf' ),
                'type'    => 'select',
                'options' => $pages_options
        );

        $vals[] =  array(
                'id'      => 'login_page',
                'name'    => __( 'Login Page', 'atcf' ),
                'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_login]</code> shortcode.', 'atcf' ),
                'type'    => 'select',
                'options' => $pages_options
        );

        $vals[] =  array(
                'id'      => 'register_page',
                'name'    => __( 'Register Page', 'atcf' ),
                'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_register]</code> shortcode.', 'atcf' ),
                'type'    => 'select',
                'options' => $pages_options
        );

        return array_merge( array_combine( $keys, $vals ), array_combine( $keys2, $vals2 ) );
}
add_filter( 'edd_settings_general', 'atcf_settings_general_pages' );

/**
 * Add settings to set a flexible fee
 *
 * @since AppThemer Crowdfunding 0.7
 * 
 * @param $settings
 * @return $settings
 */
function atcf_settings_gateway( $settings ) {
        if ( ! class_exists( 'PayPalAdaptivePaymentsGateway' ) )
                return $settings;

        $settings[ 'epap_flexible_fee' ] = array(
                'id'   => 'epap_flexible_fee',
                'name' => __( 'Additional Flexible Fee', 'epap' ),
                'desc' => __( '%. <span class="description">If a campaign is flexible, increase commission by this percent.</span>', 'atcf' ),
                'type' => 'text',
                'size' => 'small'
        );

        return $settings;
}
add_filter( 'edd_settings_gateways', 'atcf_settings_gateway', 100 );

/**
 * General settings for Crowdfunding
 *
 * @since AppThemer Crowdfunding 0.9
 * 
 * @param $settings
 * @return $settings
 */
function atcf_settings_general( $settings ) {
        $settings[ 'atcf_settings' ] = array(
                'id'   => 'atcf_settings',
                'name' => '<strong>' . __( 'AppThemer Crowdfunding Settings', 'atcf' ) . '</strong>',
                'desc' => __( 'Configuration related to crowdfunding.', 'atcf' ),
                'type' => 'header'
        );

        $settings[ 'atcf_settings_campaign_minimum' ] = array(
                'id'   => 'atcf_campaign_length_min',
                'name' => __( 'Minimum Campaign Length', 'atcf' ),
                'desc' => __( 'The minimum days a campaign can run for.', 'atcf' ),
                'type' => 'text',
                'size' => 'small',
                'std'  => 14
        );

        $settings[ 'atcf_settings_campaign_maximum' ] = array(
                'id'   => 'atcf_campaign_length_max',
                'name' => __( 'Maximum Campaign Length', 'atcf' ),
                'desc' => __( 'The maximum days a campaign can run for.', 'atcf' ),
                'type' => 'text',
                'size' => 'small',
                'std'  => 42
        );

        $types = atcf_campaign_types();
        $_types = array();

        foreach ( $types as $key => $type ) {
                $_types[ $key ] = $type[ 'title' ] . ' &mdash; <small>' . $type[ 'description' ] . '</small>';
        }

        $settings[ 'atcf_settings_require_account' ] = array(
                'id'      => 'atcf_settings_require_account',
                'name'    => __( 'Require Account', 'atcf' ),
                'desc'    => __( 'Require users to be logged in to submit a campaign.', 'atcf' ),
                'type'    => 'checkbox'
        );

        return $settings;
}
add_filter( 'edd_settings_general', 'atcf_settings_general', 100 );

/*
 * Ajout aux réglages d'edd
 */
function ypcf_register_settings() {
    add_settings_field(
	'edd_settings_misc[terms_general]',
	'CGU',
	function_exists( 'edd_header_callback' ) ? 'edd_header_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'terms_general',
	    'desc' => '',
	    'name' => 'terms_general',
	    'section' => 'misc',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
    add_settings_field(
	'edd_settings_misc[terms_general_version]',
	'Version des conditions générales',
	function_exists( 'edd_text_callback' ) ? 'edd_text_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'terms_general_version',
	    'desc' => '',
	    'name' => 'terms_general_version',
	    'section' => 'misc',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
    add_settings_field(
	'edd_settings_misc[terms_general_excerpt]',
	'Extrait des conditions générales à afficher dans la lightbox',
	function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'terms_general_excerpt',
	    'desc' => '',
	    'name' => 'terms_general_excerpt',
	    'section' => 'misc',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
    
    add_settings_field(
	'edd_settings_misc[investment_generalities]',
	'Explications g&eacute;n&eacute;rales sur l&apos;investissement',
	function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'investment_generalities',
	    'desc' => '',
	    'name' => 'investment_generalities',
	    'section' => 'misc',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
    
    add_settings_field(
	'edd_settings_misc[donation_generalities]',
	'Explications g&eacute;n&eacute;rales sur le don',
	function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'donation_generalities',
	    'desc' => '',
	    'name' => 'donation_generalities',
	    'section' => 'misc',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
    
    
    add_settings_field(
	'edd_settings_misc[contract_label]',
	'Libelles du contrat d&apos;investissement',
	function_exists( 'edd_text_callback' ) ? 'edd_text_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'contract_label',
	    'desc' => '',
	    'name' => 'contract_label',
	    'section' => 'misc',
	    'size' => 'regular' ,
	    'options' => '',
	    'std' => ''
	)
    );
    
    add_settings_field(
	'edd_settings_misc[contract]',
	'Contrat d&apos;investissement',
	function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'contract',
	    'desc' => '',
	    'name' => 'contract',
	    'section' => 'misc',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
    
    add_settings_field(
	'edd_settings_misc[message_before_donation]',
	'Message affiché avant un don',
	function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
	'edd_settings_misc',
	'edd_settings_misc',
	array(
	    'id' => 'message_before_donation',
	    'desc' => '',
	    'name' => 'message_before_donation',
	    'section' => 'misc',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
    
    add_settings_field(
	'edd_settings_emails[header_global_mails]',
	'En-tête des mails envoyés par la plateforme '
            . '<br/>Ce contenu sera intégré en haut des mails suivants :'
            . '<ul style="list-style: inherit;">'
            . '<li>Notifications d\'actualités de projet</li>'
            . '<li>Mail direct des porteurs de projets aux investisseurs</li>'
            . '<li>Confirmation d\'investissement/don</li>'
            . '<li>Notifications pour Porteur de Projet : nouvel investissement/don, nouveau commentaire</li>'
            . '</ul>',
	function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
	'edd_settings_emails',
	'edd_settings_emails',
	array(
	    'id' => 'header_global_mail',
	    'desc' => '',
	    'name' => 'header_global_mail',
	    'section' => 'mail',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	)
    );
    
    add_settings_field(
	'edd_settings_emails[footer_global_mails]',
	'Pied de page des mails envoyés par la plateforme',
	function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
	'edd_settings_emails',
	'edd_settings_emails',
	array(
	    'id' => 'footer_global_mail',
	    'desc' => '',
	    'name' => 'footer_global_mail',
	    'section' => 'mail',
	    'size' => '' ,
	    'options' => '',
	    'std' => ''
	));

	add_settings_field(
		'edd_settings_misc[default_pitch]',
		'Section "Pitch" par défaut',
		function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
		'edd_settings_misc',
		'edd_settings_misc',
		array(
			'id' => 'default_pitch',
			'desc' => '',
			'name' => 'default_pitch',
			'section' => 'misc',
			'size' => '' ,
			'options' => '',
			'std' => ''
		)
	);

	add_settings_field(
		'edd_settings_misc[default_positive_impacts]',
		'Section "Impacts positifs" par défaut',
		function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
		'edd_settings_misc',
		'edd_settings_misc',
		array(
			'id' => 'default_positive_impacts',
			'desc' => '',
			'name' => 'default_positive_impacts',
			'section' => 'misc',
			'size' => '' ,
			'options' => '',
			'std' => ''
		)
	);

	add_settings_field(
		'edd_settings_misc[default_strategy]',
		'Section "Stratégie" par défaut',
		function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
		'edd_settings_misc',
		'edd_settings_misc',
		array(
			'id' => 'default_strategy',
			'desc' => '',
			'name' => 'default_strategy',
			'section' => 'misc',
			'size' => '' ,
			'options' => '',
			'std' => ''
		)
	);

	add_settings_field(
		'edd_settings_misc[default_financiary]',
		'Section "Données financières" par défaut',
		function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
		'edd_settings_misc',
		'edd_settings_misc',
		array(
			'id' => 'default_financiary',
			'desc' => '',
			'name' => 'default_financiary',
			'section' => 'misc',
			'size' => '' ,
			'options' => '',
			'std' => ''
		)
	);

	add_settings_field(
		'edd_settings_misc[default_team]',
		'Section "Equipe" par défaut',
		function_exists( 'edd_rich_editor_callback' ) ? 'edd_rich_editor_callback' : 'edd_missing_callback',
		'edd_settings_misc',
		'edd_settings_misc',
		array(
			'id' => 'default_team',
			'desc' => '',
			'name' => 'default_team',
			'section' => 'misc',
			'size' => '' ,
			'options' => '',
			'std' => ''
		)
	);
}
add_action('admin_init', 'ypcf_register_settings', 11);


add_action( 'admin_menu', 'ypcf_setup_wdg_menu' );
function ypcf_setup_wdg_menu() {
	add_options_page( 'WE DO GOOD', 'WE DO GOOD', 'manage_options', 'wdg-settings', 'ypcf_display_wdg_admin' );
}

function ypcf_display_wdg_admin() {
	if (!current_user_can('manage_options')) { wp_die( __('You do not have sufficient permissions to access this page.') ); }
	
	$lang_list = array(
		'en_US' => 'Anglais'
	);
	$properties_list = array(
		'investment_generalities' => 'Explications g&eacute;n&eacute;rales sur l&apos;investissement',
		'contract' => 'Contrat d&apos;investissement'
	);
	
	//Sauvegarde toutes les données
	$need_save = filter_input(INPUT_POST, 'save-wdg');
	if (!empty($need_save)) {
		foreach ($lang_list as $lang_key => $lang_name) {
			$option = array();
			foreach ($properties_list as $property_key => $property_label) {
				$value = filter_input(INPUT_POST, $property_key .'_'. $lang_key);
				$option[$property_key] = $value;
			}
			update_option(ATCF_CrowdFunding::$option_name .'_'. $lang_key, $option);
		}
	}
	?>

	<h2>Traduction WE DO GOOD</h2>
	
	<form method="post" action="">
		<?php foreach ($lang_list as $lang_key => $lang_name): ?>
			<h3><?php echo $lang_name; ?></h3>

			<?php foreach ($properties_list as $property_key => $property_label): ?>
			<label for="<?php echo $property_key; ?>_<?php echo $lang_key; ?>"><?php echo $property_label; ?></label>
			<?php wp_editor(ATCF_CrowdFunding::get_translated_setting($property_key, $lang_key), $property_key .'_'. $lang_key); ?>
			<br /><br />
			<?php endforeach; ?>
		<?php endforeach; ?>

		<input type="hidden" name="save-wdg" value="1" />
		<p class="submit"><input type="submit" name="Submit" class="button-primary" value="Enregistrer" /></p>
	</form>
	<?php
}