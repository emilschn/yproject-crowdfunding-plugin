<?php
class WDG_PDF_Generator {
	
/******************************************************************************/
/* Shortcodes spécifiques aux contrats */
/******************************************************************************/
	public static function add_shortcodes() {
		add_shortcode( 'wdg_campaign_contract_organization_name', 'WDG_PDF_Generator::shortcode_contract_organization_name' );
		add_shortcode( 'wdg_campaign_contract_organization_legalform', 'WDG_PDF_Generator::shortcode_contract_organization_legalform' );
		add_shortcode( 'wdg_campaign_contract_organization_capital', 'WDG_PDF_Generator::shortcode_contract_organization_capital' );
		add_shortcode( 'wdg_campaign_contract_organization_address', 'WDG_PDF_Generator::shortcode_contract_organization_address' );
		add_shortcode( 'wdg_campaign_contract_organization_postalcode', 'WDG_PDF_Generator::shortcode_contract_organization_postalcode' );
		add_shortcode( 'wdg_campaign_contract_organization_city', 'WDG_PDF_Generator::shortcode_contract_organization_city' );
		add_shortcode( 'wdg_campaign_contract_organization_rcs', 'WDG_PDF_Generator::shortcode_contract_organization_rcs' );
		add_shortcode( 'wdg_campaign_contract_organization_idnumber', 'WDG_PDF_Generator::shortcode_contract_organization_idnumber' );
		add_shortcode( 'wdg_campaign_contract_organization_reprensentative_civility', 'WDG_PDF_Generator::shortcode_contract_organization_reprensentative_civility' );
		add_shortcode( 'wdg_campaign_contract_organization_reprensentative_firstname', 'WDG_PDF_Generator::shortcode_contract_organization_reprensentative_firstname' );
		add_shortcode( 'wdg_campaign_contract_organization_reprensentative_lastname', 'WDG_PDF_Generator::shortcode_contract_organization_reprensentative_lastname' );
		add_shortcode( 'wdg_campaign_contract_organization_reprensentative_function', 'WDG_PDF_Generator::shortcode_contract_organization_reprensentative_function' );
		add_shortcode( 'wdg_campaign_contract_start_date', 'WDG_PDF_Generator::shortcode_contract_start_date' );
		add_shortcode( 'wdg_campaign_contract_organization_description', 'WDG_PDF_Generator::shortcode_contract_organization_description' );
		add_shortcode( 'wdg_campaign_contract_minimum_goal', 'WDG_PDF_Generator::shortcode_contract_minimum_goal' );
		add_shortcode( 'wdg_campaign_contract_maximum_goal', 'WDG_PDF_Generator::shortcode_contract_maximum_goal' );
		add_shortcode( 'wdg_campaign_contract_roi_percent_max', 'WDG_PDF_Generator::shortcode_contract_roi_percent_max' );
		add_shortcode( 'wdg_campaign_contract_duration', 'WDG_PDF_Generator::shortcode_contract_duration' );
		add_shortcode( 'wdg_campaign_contract_maximum_profit', 'WDG_PDF_Generator::shortcode_contract_maximum_profit' );
		add_shortcode( 'wdg_campaign_contract_budget_type', 'WDG_PDF_Generator::shortcode_contract_budget_type' );
		add_shortcode( 'wdg_campaign_contract_quarter_earnings_estimation_type', 'WDG_PDF_Generator::shortcode_contract_quarter_earnings_estimation_type' );
		add_shortcode( 'wdg_campaign_contract_earnings_description', 'WDG_PDF_Generator::shortcode_contract_earnings_description' );
		add_shortcode( 'wdg_campaign_contract_spendings_description', 'WDG_PDF_Generator::shortcode_contract_spendings_description' );
		add_shortcode( 'wdg_campaign_contract_simple_info', 'WDG_PDF_Generator::shortcode_contract_simple_info' );
		add_shortcode( 'wdg_campaign_contract_detailed_info', 'WDG_PDF_Generator::shortcode_contract_detailed_info' );
		add_shortcode( 'wdg_campaign_contract_estimated_turnover_per_year', 'WDG_PDF_Generator::shortcode_contract_estimated_turnover_per_year' );
		add_shortcode( 'wdg_campaign_custom_field', 'WDG_PDF_Generator::shortcode_custom_field' );
	}
	
	/**
	 * Shortcode affichant le nom de l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_name( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_name();
	}
	/**
	 * Shortcode affichant la forme juridique de l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_legalform( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_legalform();
	}
	/**
	 * Shortcode affichant le capital de l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_capital( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_capital();
	}
	/**
	 * Shortcode affichant l'adresse de l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_address( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_address();
	}
	/**
	 * Shortcode affichant le code postal de l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_postalcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_postal_code();
	}
	/**
	 * Shortcode affichant la ville de l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_city( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_city();
	}
	/**
	 * Shortcode affichant le RCS de l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_rcs( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_rcs();
	}
	/**
	 * Shortcode affichant le numéro SIREN de l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_idnumber( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_idnumber();
	}
	/**
	 * Shortcode affichant la civilité de la personne représentant l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_reprensentative_civility( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_creator;
		return ( $shortcode_organization_creator->get_gender() == 'male' ) ? 'M' : 'Mme';
	}
	/**
	 * Shortcode affichant le prénom de la personne représentant l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_reprensentative_firstname( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_creator;
		return $shortcode_organization_creator->get_firstname();
	}
	/**
	 * Shortcode affichant le nom de famille de la personne représentant l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_reprensentative_lastname( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_creator;
		return $shortcode_organization_creator->get_lastname();
	}
	/**
	 * Shortcode affichant la fonction de la personne représentant l'organisation qui porte le projet
	 */
	public static function shortcode_contract_organization_reprensentative_function( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_organization_obj;
		return $shortcode_organization_obj->get_representative_function();
	}
	
	/**
	 * Shortcode affichant la date de démarrage du contrat
	 */
	public static function shortcode_contract_start_date( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		$buffer = "";
		global $shortcode_campaign_obj;
		$data_contract_start_date = $shortcode_campaign_obj->contract_start_date();
		if ( !empty( $data_contract_start_date ) ) {
			$start_datetime = new DateTime( $data_contract_start_date );
			$buffer = $start_datetime->format( 'd/m/Y' );
		}
		return $buffer;
	}
	
	/**
	 * Shortcode affichant le descriptif de l'activité
	 */
	public static function shortcode_contract_organization_description( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		$buffer = "";
		global $shortcode_campaign_obj;
		$campaign_organization = $shortcode_campaign_obj->get_organization();
		if ( !empty( $campaign_organization->wpref ) ) {
			$wdg_organization = new WDGOrganization( $campaign_organization->wpref );
			$buffer = $wdg_organization->get_description();
		}
		return $buffer;
	}
	
	/**
	 * Shortcode affichant l'objectif minimum
	 */
	public static function shortcode_contract_minimum_goal( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = $shortcode_campaign_obj->minimum_goal();
		return $buffer;
	}
	
	/**
	 * Shortcode affichant l'objectif maximum
	 */
	public static function shortcode_contract_maximum_goal( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = $shortcode_campaign_obj->goal( false );
		if ( $shortcode_campaign_obj->contract_maximum_type() == 'infinite' ) {
			$buffer = ATCF_Campaign::$contract_maximum_types[ $shortcode_campaign_obj->contract_maximum_type() ];
		}
		return $buffer;
	}
	
	/**
	 * Shortcode affichant le pourcentage de versement maximal
	 */
	public static function shortcode_contract_roi_percent_max( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$roi_percent_estimated = $shortcode_campaign_obj->roi_percent_estimated();
		
		require_once('number-words/Numbers/Words.php');
		$nbwd_class = new Numbers_Words();
		$buffer_in_words = $nbwd_class->toWords( $roi_percent_estimated, 'fr' );
		if ( !is_int( $roi_percent_estimated ) ) {
			$number_exploded = explode( '.', $roi_percent_estimated );
			$buffer_in_words .= " VIRGULE " . $nbwd_class->toWords( $number_exploded[ 1 ], 'fr' );
		}
	
		$buffer = $roi_percent_estimated. '% (' . strtoupper( $buffer_in_words ) . ' POURCENTS)';
		return $buffer;
	}
	
	/**
	 * Shortcode affichant la durée du contrat
	 */
	public static function shortcode_contract_duration( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = $shortcode_campaign_obj->funding_duration();
		return $buffer;
	}
	
	/**
	 * Shortcode affichant le gain maximal
	 */
	public static function shortcode_contract_maximum_profit( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = $shortcode_campaign_obj->maximum_profit();
		if ( $buffer == 'infinite' ) {
			$buffer = __( "Infini", 'yproject' );
		}
		return $buffer;
	}
	
	/**
	 * Shortcode affichant le type de budget : todo
	 */
	public static function shortcode_contract_budget_type( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = '';
		if ( isset( ATCF_Campaign::$contract_budget_types[ $shortcode_campaign_obj->contract_budget_type() ] ) ) {
			$buffer = ATCF_Campaign::$contract_budget_types[ $shortcode_campaign_obj->contract_budget_type() ];
		}
		return $buffer;
	}
	
	/**
	 * Shortcode affichant le type d'estimation de revenus trimestriels : todo
	 */
	public static function shortcode_contract_quarter_earnings_estimation_type( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = '';
		if ( $shortcode_campaign_obj->quarter_earnings_estimation_type() == 'linear' ) {
			$buffer = "- 25% pour le premier trimestre<br />";
			$buffer .= "- 25% pour le deuxième trimestre<br />";
			$buffer .= "- 25% pour le troisième trimestre<br />";
			$buffer .= "- 25% pour le quatrième trimestre<br />";
		} elseif ( $shortcode_campaign_obj->quarter_earnings_estimation_type() == 'progressive' ) {
			$buffer = "- 10% pour le premier trimestre<br />";
			$buffer .= "- 20% pour le deuxième trimestre<br />";
			$buffer .= "- 30% pour le troisième trimestre<br />";
			$buffer .= "- 40% pour le quatrième trimestre<br />";
		}
		return $buffer;
	}
	
	/**
	 * Shortcode affichant la description des revenus
	 */
	public static function shortcode_contract_earnings_description( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = $shortcode_campaign_obj->contract_earnings_description();
		return $buffer;
	}
	
	/**
	 * Shortcode affichant la description des dépenses
	 */
	public static function shortcode_contract_spendings_description( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = $shortcode_campaign_obj->contract_spendings_description();
		return $buffer;
	}
	
	/**
	 * Shortcode affichant les informations simples
	 */
	public static function shortcode_contract_simple_info( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = $shortcode_campaign_obj->contract_simple_info();
		return $buffer;
	}
	
	/**
	 * Shortcode affichant les informations détaillées
	 */
	public static function shortcode_contract_detailed_info( $atts, $content = '' ) {
		$atts = shortcode_atts( array( ), $atts );
		global $shortcode_campaign_obj;
		$buffer = $shortcode_campaign_obj->contract_detailed_info();
		return $buffer;
	}
	
	/**
	 * Shortcode affichant le CA prévisionnel pour une année spécifique
	 */
	public static function shortcode_contract_estimated_turnover_per_year( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'year'	=> '1'
		), $atts );
		global $shortcode_campaign_obj;
		$buffer = 0;
		$estimated_turnover = $shortcode_campaign_obj->estimated_turnover();
		if ( !empty( $estimated_turnover ) ){
			$i = 1;
			foreach ( $estimated_turnover as $key => $turnover ) {
				if ( $i == $atts[ 'year' ] || $key == $atts[ 'year' ] ) {
					$buffer = $turnover;
				}
				$i++;
			}
		}
		return $buffer;
	}
	
	/**
	 * Shortcode affichant le contenu d'un champ personnalisé
	 */
	public static function shortcode_custom_field( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'id'	=> '1'
		), $atts );
		global $shortcode_campaign_obj;
		$buffer = get_post_meta( $shortcode_campaign_obj->ID, 'custom_field_' . $atts['id'], TRUE);
		return $buffer;
	}
	
	
	
}

/**
 * Creates a pdf file with the content
 * @param type $html_content
 * @param type $filename
 * @return boolean
 */
function generatePDF($html_content, $filename) {
    ypcf_debug_log('generatePDF > ' . $filename);
    $buffer = false;
    if (isset($html_content) && isset($filename) && ($filename != "") && !file_exists($filename)) {
		$html2pdf = new HTML2PDF('P','A4','fr');
		$html2pdf->WriteHTML(urldecode($html_content));
		$html2pdf->Output($filename, 'F');
		$buffer = true;
    }
    return $buffer;
}

/**
 * Fill the pdf default content with infos
 * @return string
 */
function fillPDFHTMLDefaultContent($user_obj, $campaign_obj, $payment_data, $organization = false) {
	if ( !empty( $payment_data ) ) {
		ypcf_debug_log('fillPDFHTMLDefaultContent > ' . $payment_data["amount"]);
	}
    $buffer = '';
	
	//Si on doit faire une version anglaise
	if (get_locale() == 'en_US') {
		$buffer .= doFillPDFHTMLDefaultContentByLang($user_obj, $campaign_obj, $payment_data, $organization, 'en_US');
	}
	$buffer .= doFillPDFHTMLDefaultContentByLang($user_obj, $campaign_obj, $payment_data, $organization);
	
	return $buffer;
}

function doFillPDFHTMLDefaultContentByLang($user_obj, $campaign_obj, $payment_data, $organization, $lang = '') {
	if (empty($lang)) {
		setlocale( LC_CTYPE, 'fr_FR' );
	}
	$campaign_obj->set_current_lang($lang);
	$campaign_orga = $campaign_obj->get_organization();
	$organization_obj = new WDGOrganization( $campaign_orga->wpref );
	
	WDG_PDF_Generator::add_shortcodes();
	
	$blank_space_small = '________________';
	$blank_space = '________________________________________________';
	$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	
	if ( $user_obj != 'user' ) {
		global $country_list;
		$nationality = $country_list[$user_obj->get('user_nationality')];

		if ($lang == 'en_US') {
			$user_title = ($user_obj->get('user_gender') == "male") ? "Mr" : "Mrs";
		} else {
			$user_title = ($user_obj->get('user_gender') == "male") ? "Monsieur" : "Madame";
		}
		$user_name = mb_strtoupper($user_title . ' ' . $user_obj->first_name . ' ' . $user_obj->last_name);
	}
		
    
    $buffer .= '<div style="border: 1px solid black; width:100%; padding:5px 0px 5px 0px; text-align:center;"><h1>'.$campaign_obj->contract_title().' '.$organization_obj->get_name().'</h1></div>';
    
    $buffer .= '<p style="line-height: 1.8;">';
    switch ($campaign_obj->funding_type()) {
	    case 'fundingproject':
			if ($lang == 'en_US') {
				$buffer .= '<h2>BETWEEN THE UNDERSIGNED</h2>';
			} else {
				$buffer .= '<h2>ENTRE LES SOUSSIGNÉS</h2>';
			}
		break;
		
	    case 'fundingdevelopment':
	    default:
			if ($lang == 'en_US') {
				$buffer .= '<h2>THE UNDERSIGNED</h2>';
			} else {
				$buffer .= '<h2>LE SOUSSIGNÉ</h2>';
			}
		break;
    }
	
	if ( $user_obj == 'user' ) {
		$buffer .= "<i>(Civilité Prénom Nom)</i> " . $blank_space."<br />";
		$buffer .= "né(e) le ".$blank_space_small." &agrave; ".$blank_space."<br />";
		$buffer .= "de nationalité ".$blank_space."<br />";
		$buffer .= "demeurant <i>(adresse)</i>".$blank_space."<br />";
		$buffer .= "<i>(ville et CP)</i>" . $blank_space."<br />";
		$buffer .= "Adresse e-mail : ".$blank_space;
		
	} else {
		$buffer .= '<strong>'.$user_name.'</strong><br />';
		if ($lang == 'en_US') {
			$birthday_month = mb_strtoupper($months[$user_obj->get('user_birthday_month') - 1]);
			$buffer .= 'born on '.$birthday_month.' '.$user_obj->get('user_birthday_day').' '.$user_obj->get('user_birthday_year').' in '.$user_obj->get('user_birthplace').'<br />';
			$buffer .= 'from '.$nationality.'<br />';
			$buffer .= 'living in '.$user_obj->get('user_city').' ('.$user_obj->get('user_postal_code').') - ' . $user_obj->get('user_address').'<br />';;
			$buffer .= 'E-mail address: '.$user_obj->user_email;

		} else {
			$birthday_month = htmlentities( mb_strtoupper(__($months[$user_obj->get('user_birthday_month') - 1])) );
			$suffix_born = ($user_obj->get('user_gender') == "female") ? 'e' : '';
			$buffer .= 'né'.$suffix_born.' le '.$user_obj->get('user_birthday_day').' '.$birthday_month.' '.$user_obj->get('user_birthday_year').' &agrave; '.$user_obj->get('user_birthplace').'<br />';
			$buffer .= 'de nationalité '.$nationality.'<br />';
			$buffer .= 'demeurant ' . $user_obj->get('user_address').' '.$user_obj->get('user_postal_code').' '.$user_obj->get('user_city').'<br />';;
			$buffer .= 'Adresse e-mail : '.$user_obj->user_email;
		}
	}
	
    if ($organization !== false) {
	    $buffer .= '<br /><br />';
		if ( is_object($organization) ) {
			if ($lang == 'en_US') {
				$buffer .= '<strong>'.$organization->get_name().', '.$organization->get_legalform().' with the capital of '.$organization->get_capital().'&euro;</strong><br />';
				$buffer .= 'which address is '.$organization->get_city().' ('.$organization->get_postal_code().') - '.$organization->get_address().'<br />';
				$buffer .= 'registered with the number '.$organization->get_idnumber().' in '.$organization->get_rcs().'<br />';
			} else {
				$buffer .= "agissant, ayant tous pouvoirs à l'effet des présentes, pour le compte de :<br />";
				$buffer .= '<strong>'.$organization->get_name().', '.$organization->get_legalform().' au capital de '.$organization->get_capital().'&euro;</strong><br />';
				$buffer .= 'dont le siège social est '.$organization->get_address().' '.$organization->get_postal_code().' '.$organization->get_city().'<br />';
				$buffer .= 'immatriculée sous le numéro SIREN '.$organization->get_idnumber().' au RCS de '.$organization->get_rcs().'<br />';
			}
		} elseif ( $organization == 'orga' ) {
			$buffer .= "<i>&nbsp;&nbsp;&nbsp;A remplir si personne morale</i><br />";
			$buffer .= "agissant, ayant tous pouvoirs à l'effet des présentes, pour le compte de :<br />";
			$buffer .= "<i>(Nom de l'organisation)</i> " . $blank_space . ",<br />";
			$buffer .= "<i>(Forme légale)</i> " . $blank_space . " ";
			$buffer .= "<i>au capital de</i> " . $blank_space_small . " &euro;<br />";
			$buffer .= "dont le siège social est <i>(adresse)</i>" . $blank_space . " <i>(ville et CP)</i>" . $blank_space . "<br />";
			$buffer .= "immatriculée sous le numéro " . $blank_space_small . " au RCS de " . $blank_space . "<br />";
		}
    }
    
    if ($campaign_obj->funding_type() == 'fundingproject') {
	    $buffer .= '<br /><br />';

		if ( empty( $payment_data ) ) {
			$buffer .= "<i>&nbsp;&nbsp;&nbsp;A remplir dans tous les cas</i><br />";
			$buffer .= 'qui paie la somme de '.$blank_space_small.' € ci-après désignée la « <strong>Souscription</strong> »,<br /><br />';
			$buffer .= 'ci-après désigné le « Souscripteur »,<br />';
			$buffer .= 'D\'UNE PART<br />';
			
		} else {
			require_once('number-words/Numbers/Words.php');
			$nbwd_class = new Numbers_Words();

			if ($lang == 'en_US') {
				$nbwd_text = $nbwd_class->toWords($payment_data["amount"]);
				$buffer .= 'paying the amount of '.$payment_data["amount"].' € ('.strtoupper(str_replace(' ', '-', $nbwd_text)).' EUROS) further designed as the « Subscription »,<br /><br />';
				$buffer .= 'further designed as the « Subscriber »,<br />';
				$buffer .= 'ON ONE SIDE<br />';
			} else {
				$nbwd_text = $nbwd_class->toWords($payment_data["amount"], 'fr');
				$buffer .= 'qui paie la somme de '.$payment_data["amount"].' € ('.strtoupper(str_replace(' ', '-', $nbwd_text)).' EUROS) ci-après désignée la « Souscription »,<br /><br />';
				$buffer .= 'ci-après désigné'.$suffix_born.' le « Souscripteur »,<br />';
				$buffer .= 'D\'UNE PART<br />';
			}
		}
		
    }
    $buffer .= '</p>';
	
	global $shortcode_campaign_obj, $shortcode_organization_obj, $shortcode_organization_creator;
	$shortcode_campaign_obj = $campaign_obj;
	$shortcode_organization_obj = $organization_obj;
	$campaign_orga_linked_users = $shortcode_organization_obj->get_linked_users( WDGWPREST_Entity_Organization::$link_user_type_creator );
	$shortcode_organization_creator = $campaign_orga_linked_users[0];
	$edd_settings = get_option( 'edd_settings' );
	// Si le projet surcharge le contrat standard
	$project_override_contract = $campaign_obj->override_contract();
	if ( !empty( $project_override_contract ) ) {
		$buffer .= apply_filters( 'the_content', $project_override_contract );
		
	// Si il y a un contrat standard défini, on le prend directement
	} else if ( !empty( $edd_settings[ 'standard_contract' ] ) ) {
		$buffer .= apply_filters( 'the_content', $edd_settings[ 'standard_contract' ] );
		
	
	} else {
    
		switch ($campaign_obj->funding_type()) {
			case 'fundingproject': 
			break;
			case 'fundingdevelopment':
			default:
			$buffer .= '<p>';
			if ($lang == 'en_US') {
				$buffer .= '<h2>DECLARES</h2>';
			} else {
				$buffer .= '<h2>DECLARE</h2>';
			}
			$buffer .= '</p>';
			break;
		}

		$buffer .= '<p>';
		switch ($campaign_obj->funding_type()) {
			case 'fundingproject': 
			break;
			case 'fundingdevelopment':
			default:
			if ( !empty( $payment_data ) ) {
				$plurial = '';
				if ($lang == 'en_US') {
					if ($payment_data["amount_part"] > 1) $plurial = 's';
					$buffer .= '- Subscribe ' . $payment_data["amount_part"] . ' part'.$plurial.' of the company which main characteristics are the following:<br />';
				} else {
					if ($payment_data["amount_part"] > 1) $plurial = 's';
					$buffer .= '- Souscrire ' . $payment_data["amount_part"] . ' part'.$plurial.' de la société dont les principales caractéristiques sont les suivantes :<br />';

				}
			}
			break;
		}

		$buffer .= html_entity_decode($campaign_obj->subscription_params());
		$buffer .= '</p>';

		$buffer .= '<p>';
		$user_author = new WDGUser( $campaign_obj->post_author() );
		$override_contract = $user_author->wp_user->get('wdg-contract-override');
		if ( !empty( $override_contract ) ) {
			global $shortcode_campaign_obj;
			$shortcode_campaign_obj = $campaign_obj;
			$override_contract_filtered = apply_filters( 'the_content', $override_contract );
			$buffer .= html_entity_decode( $override_contract_filtered );
		} else {
			$buffer .= html_entity_decode( $campaign_obj->powers_params() );
		}
		$buffer .= '</p>';
		
	}
	
	
    
    $buffer .= '<table style="border:0px;"><tr><td>';
	if ( !empty( $payment_data ) ) {
		if ($lang == 'en_US') {
			$buffer .= 'Done with the IP address '.$payment_data["ip"].'<br />';
		} else {
			$buffer .= 'Fait avec l\'adresse IP '.$payment_data["ip"].'<br />';
		}
		$day = date("d");
		$month = mb_strtoupper(__($months[date("m") - 1]));
		$year = date("Y");
		$hour = date("H");
		$minute = date("i");
		if ($lang == 'en_US') {
			$buffer .= 'On '.$month.' '.$day.' '.$year.'<br />';
			if (is_object($organization) && $organization !== false) {
				$buffer .= 'THE '.$organization->get_legalform().' '.$organization->get_name().'<br />';
				$buffer .= 'represented by ';
			}

		} else {
			$buffer .= 'Le '.$day.' '.$month.' '.$year.'<br />';
			if (is_object($organization) && $organization !== false) {
				$buffer .= 'LA '.$organization->get_legalform().' '.$organization->get_name().'<br />';
				$buffer .= 'représentée par ';
			}
		}
		$buffer .= $user_name.'<br />';
		
	} else {
		$buffer .= '<span style="line-height: 1.8">';
		$buffer .= 'Le (<i>date</i>)'.$blank_space_small.'<br />';
		if ( $organization == 'orga' ) {
			$buffer .= "LA (<i>forme légale et nom de l'organisation</i>)".$blank_space.'<br />';
			$buffer .= 'représentée par ' .$blank_space;
		}
		$buffer .= '</span>';
	}
    $buffer .= '(1)<br />';
    $buffer .= 'Bon pour souscription';
    $buffer .= '</td>';
    
    $buffer .= '<td></td></tr></table>';
    
	
	if ( !empty( $payment_data ) ) {
		if ($payment_data["amount"] <= 1500) {
			$buffer .= '<div style="margin-top: 20px; border: 1px solid green; color: green;">';
			if ($lang == 'en_US') {
				$buffer .= 'Investment done on '.$month.' '.$day.' '.$year.', at '.$hour.':'.$minute.'<br />';
				$buffer .= 'E-mail address: '.$user_obj->user_email.'<br />';
				$buffer .= 'IP address: '.$payment_data["ip"].'<br />';
			} else {
				$buffer .= 'Investissement réalisé le '.$day.' '.$month.' '.$year.', à '.$hour.'h'.$minute.'<br />';
				$buffer .= 'Adresse e-mail : '.$user_obj->user_email.'<br />';
				$buffer .= 'Adresse IP : '.$payment_data["ip"].'<br />';
			}
			$buffer .= '</div>';
		}
	}
    
    $buffer .= '<div style="padding-top: 60px;">';
	if ($lang == 'en_US') {
		$buffer .= '(1) signature with the mention "Bon pour souscription"<br /><br />';
	} else {
		$buffer .= '(1) signature accompagnée de la mention "Bon pour souscription"<br /><br />';
	}
    $buffer .= '</div>';
   
    
    return $buffer;
}

/**
 * Returns the pdf created with a project_id and a user_id
 * @param type $project_id
 */
function getNewPdfToSign($project_id, $payment_id, $user_id, $filepath = FALSE) {
    ypcf_debug_log('getNewPdfToSign > ' . $payment_id);
    $post_camp = get_post($project_id);
    $campaign = atcf_get_campaign( $post_camp );
    
	$current_user = FALSE;
	$invest_data = FALSE;
    $organization = FALSE;
	if ( !empty( $payment_id ) ) {
		$current_user = get_userdata($user_id);
		$saved_user_id = get_post_meta($payment_id, '_edd_payment_user_id', TRUE);
		if (isset($_SESSION['redirect_current_invest_type']) && $_SESSION['redirect_current_invest_type'] != "user") {
			$group_id = $_SESSION['redirect_current_invest_type'];
			$organization = new WDGOrganization($group_id);
		} else if (!empty($saved_user_id) && $saved_user_id != $user_id) {
			$organization = new WDGOrganization($saved_user_id);
		}
		$amount = edd_get_payment_amount($payment_id);
		$amount_part = $amount / $campaign->part_value();

		$ip_address = get_post_meta($payment_id, '_edd_payment_ip', TRUE);

		$invest_data = array(
			"amount_part" => $amount_part, 
			"amount" => $amount, 
			"total_parts_company" => $campaign->total_parts(), 
			"total_minimum_parts_company" => $campaign->total_minimum_parts(),
			"ip" => $ip_address
		);
		
	} else {
		if ( $user_id == 'user' ) {
			$current_user = 'user';
		} elseif ( $user_id == 'orga' ) {
			$current_user = 'user';
			$organization = 'orga';
		}
	}
	
    $html_content = fillPDFHTMLDefaultContent($current_user, $campaign, $invest_data, $organization);
    $filename = ( empty( $filepath ) ) ? dirname ( __FILE__ ) . '/../pdf_files/' . $campaign->ID . '_' . $current_user->ID . '_' . time() . '.pdf' : $filepath;
    
    ypcf_debug_log('getNewPdfToSign > write in ' . $filename);
    if (generatePDF($html_content, $filename)) return $filename;
    else return false;
}

?>