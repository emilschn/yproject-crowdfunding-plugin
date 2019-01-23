<?php
class WDG_FiscalDocuments {
	
	private static $wedogood_name = 'WE DO GOOD';
	private static $wedogood_street_number = '8';
	private static $wedogood_street = 'rue Kervegan';
	private static $wedogood_town_insee_number = '44109';
	private static $wedogood_town_label = 'Nantes';
	private static $wedogood_post_code = '44000';
	private static $wedogood_town_office = 'Nantes';
	private static $wedogood_siret = '79751910500051';
	private static $wedogood_previous_siret = '79751910500051';
	private static $wedogood_legal_category = '5710';
	
	private static $wedogood_person_incharge_name = 'Schneider Emilien';
	private static $wedogood_person_incharge_phone = '0972651589';
	private static $wedogood_person_incharge_email = 'admin@wedogood.co';
	
	private static $tax_coef = 0.3;
	
	
	/**************************************************************************/
	/* ERREURS */
	private static $errors;
	
	/**
	 * Ajoute une erreur dans la liste à écrire
	 * @param string $error
	 */
	private static function add_error( $error ) {
		// Init des erreurs
		if ( !isset( self::$errors ) ) {
			self::$errors = array();
		}
		array_push( self::$errors, $error );
	}
	
	private static function save_errors_file( $campaign_id, $fiscal_year ) {
		$file_path = dirname( __FILE__ ) . '/../../../files/fiscal-documents/errors_' .$campaign_id. '_' .$fiscal_year. '.txt';
		$errors_file_content = '';
		if ( !empty( self::$errors ) ) {
			foreach ( self::$errors as $error ) {
				$errors_file_content .= "- " .$error. "\n";
			}
			
			self::save_file( $file_path, $errors_file_content );
		}
	}
	/**************************************************************************/
	
	/**************************************************************************/
	/* ENREGISTREMENT DES FICHIERS */
	private static function save_file( $file_path, $content ) {
		$dirname = dirname( $file_path );
		if ( !is_dir( $dirname ) ) {
			mkdir( $dirname, 0755, TRUE );
		}
		$file_handle = fopen( $file_path, 'w' );
		fwrite( $file_handle, $content );
		fclose( $file_handle );
	}
	
	private static function save_resume_file( $campaign_id, $fiscal_year, $resume_file_content ) {
		$file_path = dirname( __FILE__ ) . '/../../../files/fiscal-documents/resume_' .$campaign_id. '_' .$fiscal_year. '.txt';
		self::save_file( $file_path, $resume_file_content );
	}
	
	private static function save_ifu_file( $campaign_id, $fiscal_year, $ifu_file_content ) {
		$file_path = dirname( __FILE__ ) . '/../../../files/fiscal-documents/ifu_' .$campaign_id. '_' .$fiscal_year. '.txt';
		self::save_file( $file_path, $ifu_file_content );
	}
	/**************************************************************************/
	
	
	private static $geolocation_data_by_town;

	/**
	 * Génère les fichiers de l'année précédente
	 * @param int $campaign_id
	 */
	public static function generate( $campaign_id, $fiscal_year = 0 ) {
		// On stocke d'un côté un résumé textuel lisible. CSV ?
		$resume_txt = '';
		// On stocke d'un autre côté le fichier txt de déclaration des IFUs
		// Documentation de référence (2018) : https://www.impots.gouv.fr/portail/files/media/1_metier/3_partenaire/tiers_declarants/cdc_td_bilateral/td_rcm_2018.pdf
		$ifu_txt = '';
		
		// Campagne analysée
		$campaign = new ATCF_Campaign( $campaign_id );
		$resume_txt .= "Information fiscales\n";
		$resume_txt .= $campaign->get_name(). "\n";
		
		// Ouverture fichier de geoloc
		self::parse_geolocation_data();
		
		// Récupération de l'année en cours pour trouver l'année dernière
		if ( empty( $fiscal_year ) ) {
			$current_date = new DateTime();
			$fiscal_year = $current_date->format( 'Y' ) - 1;
		}
		$resume_txt .= "Année " .$fiscal_year. "\n\n";
		$ifu_txt .= self::add_ifu_declaring_info( $fiscal_year );
		
		// On récupère la liste des investissements de la campagne
		$entity_index = 1;
		$investments = $campaign->payments_data();
		foreach ( $investments as $investment_item ) {
			if ( $investment_item[ 'status' ] == 'publish' ) {
				$investment_amount = $investment_item[ 'amount' ];
				$investment_entity_id = $investment_item[ 'user' ];
				$investment_entity = WDGOrganization::is_user_organization( $investment_entity_id ) ? new WDGOrganization( $investment_entity_id ) : new WDGUser( $investment_entity_id );
				
				// On récupère la liste des royalties reçues par investissement jusqu'à l'année précédente
				$investment_user_rois = $investment_entity->get_royalties_by_investment_id( $investment_item[ 'ID' ] );
				$investment_user_rois_amount_total = 0;
				$investment_user_rois_amount_year = 0;
				foreach ( $investment_user_rois as $roi_item ) {
					$date_transfer = new DateTime( $roi_item->date_transfer );
					if ( $date_transfer->format( 'Y' ) <= $fiscal_year ) {
						$investment_user_rois_amount_total += $roi_item->amount;
						if ( $date_transfer->format( 'Y' ) == $fiscal_year ) {
							$investment_user_rois_amount_year += $roi_item->amount;
						}
					}
				}
				
				// Calcul de la somme à déclarer : on ne doit prendre que l'année en cours
				$amount_to_declare = min( $investment_user_rois_amount_year, $investment_user_rois_amount_total - $investment_amount );
				// Si la somme des royalties a dépassé l'investissement initial
				if ( $amount_to_declare > 0 ) {
					$ifu_txt .= self::add_ifu_entity( $investment_entity_id, $fiscal_year );
					$amount_to_declare_round = round( $amount_to_declare );
					$amount_tax_round = round( $amount_to_declare_round * self::$tax_coef );
					$ifu_txt .= self::add_ifu_amount_1( $fiscal_year, $amount_to_declare_round, $amount_tax_round );
					$resume_txt .= self::add_resume_entity( $investment_entity_id, $investment_amount, $amount_to_declare_round, $amount_tax_round );
					$entity_index++;
				}
			}
		}
		
		$ifu_txt .= self::add_ifu_amount_total( $fiscal_year, $entity_index );
		
		self::save_resume_file( $campaign_id, $fiscal_year, $resume_txt );
		self::save_ifu_file( $campaign_id, $fiscal_year, $ifu_txt );
		self::save_errors_file( $campaign_id, $fiscal_year );
	}
	
	/**
	 * Retourne une chaine qui sera ajoutée au fichier texte de résumé
	 * @param int $investment_entity_id
	 * @param int $investment_amount
	 * @param number $amount_to_declare
	 * @return string
	 */
	private static function add_resume_entity( $investment_entity_id, $investment_amount, $amount_to_declare, $amount_tax ) {
		$buffer = "";
		
		$investor_name = "";
		$investor_type = "";
		$investor_fiscal_residence = "";
		if ( WDGOrganization::is_user_organization( $investment_entity_id ) ) {
			$WDGOrganization = new WDGOrganization( $investment_entity_id );
			$investor_name = $WDGOrganization->get_name();
			$investor_type = 'Personne morale';
			$investor_fiscal_residence = $WDGOrganization->get_country();
			
		} else {
			$WDGUser = new WDGUser( $investment_entity_id );
			$investor_name = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname();
			$investor_type = 'Personne physique';
			$investor_fiscal_residence = $WDGUser->get_tax_country();
		}
		
		$buffer = "- " .$investor_name. " (" .$investor_type. "). " .$investor_fiscal_residence. "\n";
		$buffer .= ">> Investissement : " .$investment_amount. " €\n";
		$buffer .= ">> Somme à déclarer : " .$amount_to_declare. " €\n";
		$buffer .= ">> Montant des impots : " .$amount_tax. " €\n\n";
		
		return $buffer;
	}
	
	/**
	 * Retourne une chaine d'introduction pour le fichier IFU : la partie décrivant le déclarant
	 * Doc 2018 : https://www.impots.gouv.fr/portail/files/media/1_metier/3_partenaire/tiers_declarants/cdc_td_bilateral/td_rcm_2018.pdf
	 * @param ATCF_Campaign $campaign
	 * @param int $fiscal_year
	 * @return string
	 */
	private static function add_ifu_declaring_info( $fiscal_year ) {
		$buffer = '';
		
		//**********************************************************************
		// ZONE INDICATIF
		// D001 - 4 caractères : année de référence
		$buffer .= $fiscal_year;
		// D002 - 14 caractères : SIRET au 31/12
		$buffer .= self::$wedogood_siret;
		// D003 - 1 caractère : 1 si initiale ; 2 si rectificative
		$buffer .= '1';
		// D004 - 30 caractères : uniquement des 0
		for ( $i = 0; $i < 30; $i++ ) {
			$buffer .= '0';
		}
		// D005 - 2 caractères : code article D0
		$buffer .= 'D0';
		// D006 - 50 caractères : raison sociale
		$company_name = self::$wedogood_name;
		$buffer .= $company_name;
		for ( $i = strlen( $company_name ); $i < 50; $i++ ) {
			$buffer .= ' ';
		}
		// D007 - 4 caractères : catégorie juridique du déclarant. Cf https://www.insee.fr/fr/information/2028129
		$buffer .= self::$wedogood_legal_category;
		//**********************************************************************
		
		
		//**********************************************************************
		// ADRESSE DU DECLARANT
		// D009 - 32 caractères : complément d'adresse
		for ( $i = 0; $i < 32; $i++ ) {
			$buffer .= ' ';
		}
		// D010 - 4 caractères : numéro dans la voie (préfixé par 0 si nécessaire)
		for ( $i = strlen( self::$wedogood_street_number ); $i < 4; $i++ ) {
			$buffer .= '0';
		}
		$buffer .= self::$wedogood_street_number;
		// D011 - 1 caractère : B T Q C
		$buffer .= ' ';
		// D012 - 1 caractère : séparateur
		$buffer .= ' ';
		// D013 - 26 caractères : nature et nom de la voie
		$buffer .= strtoupper( self::$wedogood_street );
		// D014 - 5 caractères : code INSEE des communes. 
		// Cf https://www.insee.fr/fr/recherche/recherche-geographique?debut=0
		// OU https://www.insee.fr/fr/information/2666684 pour le fichier complet
		$buffer .= self::$wedogood_town_insee_number;
		// D015 - 1 caractère : séparateur
		$buffer .= ' ';
		// D016 - 26 caractères : libellé commune
		$buffer .= strtoupper( self::$wedogood_town_label );
		for ( $i = strlen( self::$wedogood_town_label ); $i < 26; $i++ ) {
			$buffer .= ' ';
		}
		// D017 - 5 caractères : code postal
		$buffer .= self::$wedogood_post_code;
		// D018 - 1 caractère : séparateur
		$buffer .= ' ';
		// D019 - 26 caractères : bureau distributeur
		$buffer .= strtoupper( self::$wedogood_town_office );
		for ( $i = strlen( self::$wedogood_town_office ); $i < 26; $i++ ) {
			$buffer .= ' ';
		}
		// D020 - 8 caractères : date d'émission de la déclaration AAAAMMJJ
		$current_date = new DateTime();
		$buffer .= $current_date->format( 'Ymd' );
		// D021 - 14 caractères : SIRET au 31/12 précédent en cas de changement
		$buffer .= self::$wedogood_previous_siret;
		// D022 - 175 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 175; $i++ ) {
			$buffer .= ' ';
		}
		//**********************************************************************
		
		return $buffer;
	}
	
	private static function add_ifu_entity_intro( $fiscal_year ) {
		$buffer = "";
		// R101/R201 - 4 caractères : année de référence
		$buffer .= $fiscal_year;
		// R102/R202 - 14 caractères : SIRET déclarant au 31/12
		$buffer .= self::$wedogood_siret;
		// R103/R203 - 1 caractère : 1 si initiale ; 2 si rectificative
		$buffer .= '1';
		// R104/R204 - 9 caractères : code établissement
		// TODO
		// R105/R205 - 5 caractères : code guichet
		// TODO
		// R106/R206 - 14 caractères : numéro de compte ou numéro de contrat
		// TODO
		// R107/R207 - 2 caractères : clé
		// TODO
		return $buffer;
	}
	
	private static function add_ifu_entity( $investment_entity_id, $fiscal_year ) {
		$buffer = "";
		
		
		//**********************************************************************
		// ZONE INDICATIF
		$buffer .= self::add_ifu_entity_intro( $fiscal_year );
		// R108 - 2 caractères : code article
		$buffer .= 'R1';
		// R109 - 1 caractère : nature du compte ou du contrat (1 compte bancaire, 2 contrat d'assurance, 3 autre)
		$buffer .= '3';
		// R110 - 1 caractère : type de compte (1 simple, 2 joint époux, 3 collectif, 4 indivision, 5 succession, 6 autres)
		$buffer .= '1';
		// R111 - 1 caractère : code bénéficiaire (B bénéficiaire, T tiers)
		$buffer .= 'B';
		//**********************************************************************
		
		//**********************************************************************
		// IDENTIFICATION DU BENEFICIAIRE
		// Personne morale
		if ( WDGOrganization::is_user_organization( $investment_entity_id ) ) {
			$WDGOrganization = new WDGOrganization( $investment_entity_id );
			$orga_name = self::clean_name( $WDGOrganization->get_name() );
			$orga_idnumber = $WDGOrganization->get_idnumber();
			if ( strlen( $orga_idnumber ) != 14 ) {
				self::add_error( "Le SIRET de " .$orga_name. " (ID WP " .$investment_entity_id. ") ne fait pas la bonne taille." );
			}
			$user_lastname = '';
			$user_firstname = '';
			$user_use_lastname = '';
			$user_gender = ' ';
			$user_birthday_year = '0000';
			$user_birthday_month = '00';
			$user_birthday_day = '00';
			$user_birthday_department_code = '00';
			$user_birthday_town_code = '000';
			$user_birthday_town_label = '';
			$investment_entity_address = $WDGOrganization->get_address();
			$investment_entity_address_complement = '';
			$investment_entity_address_number = ''; // TODO
			$investment_entity_address_number_complement = ''; // TODO
			$investment_entity_address_post_code = $WDGOrganization->get_postal_code( TRUE );
			$investment_entity_address_town = self::clean_town_name( $WDGOrganization->get_city() );
			// Si Paris, Marseille ou Lyon, trouver l'arrondissement de la ville
			if ( $investment_entity_address_town == 'PARIS' || $investment_entity_address_town == 'MARSEILLE' || $investment_entity_address_town == 'LYON' ) {
				$investment_entity_address_town .= ' ' . substr( $investment_entity_address_post_code, 3, 2);
			}
			$entity_geo_info = self::get_official_info_by_postal_code_and_town( $investment_entity_address_post_code, $investment_entity_address_town );
			if ( !empty( $entity_geo_info ) ) {
				$investment_entity_address_town_code = $entity_geo_info[ 'town_insee_code' ];
				$investment_entity_address_town_office = $entity_geo_info[ 'town_office' ];
			} else {
				self::add_error( 'Problème récupération de données pour localisation adresse - ID ORGA ' . $investment_entity_id . ' - ' . $user_firstname . ' ' . $user_lastname . ' --- infos recherchees : ' . $user_birthday_department_code . ' ' . $user_birthday_town_label );
			}
			
			
		// Personne physique
		} else {
			$WDGUser = new WDGUser( $investment_entity_id );
			$orga_name = '';
			$orga_idnumber = '';
			for ( $i = 0; $i < 14; $i++ ) {
				$orga_idnumber .= '0';
			}
			$user_lastname = self::clean_name( $WDGUser->get_lastname() );
			$user_firstname = self::clean_name( $WDGUser->get_firstname() );
			$user_use_lastname = self::clean_name( $WDGUser->get_use_lastname() );
			$user_gender = ( $WDGUser->get_gender() == 'male' ) ? '1' : '2';
			$user_birthday_year = $WDGUser->get_birthday_year();
			$user_birthday_month = $WDGUser->get_birthday_month();
			$user_birthday_day = $WDGUser->get_birthday_day();
			$user_birthday_country = $WDGUser->get_birthplace_country();
			$user_birthday_town_label = self::clean_town_name( strtoupper( $WDGUser->get_birthplace() ) );
			if ( $user_birthday_country == 'FR' ) {
				$user_birthday_department_code = $WDGUser->get_birthplace_department();
				// Pour Paris, Marseille et Lyon, récupérer l'arrondissement de naissance
				if ( $user_birthday_town_label == 'PARIS' || $user_birthday_town_label == 'MARSEILLE' || $user_birthday_town_label == 'LYON' ) {
					$user_birthday_town_label .= ' ' . $WDGUser->get_birthplace_district( TRUE );
				}
				$birhplace_geo_info = self::get_official_info_by_postal_code_and_town( $user_birthday_department_code, $user_birthday_town_label );
				if ( !empty( $birhplace_geo_info ) ) {
					$user_birthday_town_code = substr( $birhplace_geo_info[ 'town_insee_code' ], 2, 3);
				} else {
					self::add_error( 'Problème récupération de données pour localisation naissance - ID USER ' . $investment_entity_id . ' - ' . $user_firstname . ' ' . $user_lastname . ' --- infos recherchees : ' . $user_birthday_department_code . ' ' . $user_birthday_town_label );
				}
			} else {
				global $country_list_insee;
				if ( isset( $country_list_insee[ $user_birthday_country ] ) ) {
					$insee_code = $country_list_insee[ $user_birthday_country ];
					$user_birthday_department_code = substr( $insee_code, 0, 2 );
					$user_birthday_town_code = substr( $insee_code, 2, 3 );
				} else {
					self::add_error( 'Problème récupération de données pour localisation naissance étranger - ID USER ' . $investment_entity_id . ' - ' . $user_firstname . ' ' . $user_lastname . ' --- infos recherchees : ' . $user_birthday_department_code . ' ' . $user_birthday_country );
				}
			}
			$investment_entity_address = $WDGUser->get_address();
			$investment_entity_address_complement = '';
			$investment_entity_address_number = $WDGUser->get_address_number();
			global $address_number_complements_tax_format;
			$investment_entity_address_number_complement = $address_number_complements_tax_format[ $WDGUser->get_address_number_complement() ];
			$investment_entity_address_post_code = $WDGUser->get_postal_code( TRUE );
			$investment_entity_address_town = self::clean_town_name( strtoupper( $WDGUser->get_city() ) );
			// Si Paris, Marseille ou Lyon, trouver l'arrondissement de la ville
			if ( $investment_entity_address_town == 'PARIS' || $investment_entity_address_town == 'MARSEILLE' || $investment_entity_address_town == 'LYON' ) {
				$investment_entity_address_town .= ' ' . substr( $investment_entity_address_post_code, 3, 2);
			}
			$entity_geo_info = self::get_official_info_by_postal_code_and_town( $investment_entity_address_post_code, $investment_entity_address_town );
			if ( !empty( $entity_geo_info ) ) {
				$investment_entity_address_town_code = $entity_geo_info[ 'town_insee_code' ];
				$investment_entity_address_town_office = $entity_geo_info[ 'town_office' ];
			} else {
				self::add_error( 'Problème récupération de données pour localisation adresse - ID USER ' . $investment_entity_id . ' - ' . $user_firstname . ' ' . $user_lastname . ' --- infos recherchees : ' . $user_birthday_department_code . ' ' . $user_birthday_town_label );
			}
		}
		
		// R112 - 14 caractères : SIRET bénéficiaire
		$buffer .= $orga_idnumber;
		// R113 - 50 caractères : raison sociale
		$buffer .= strtoupper( $orga_name );
		for ( $i = strlen( $orga_name ); $i < 50; $i++ ) {
			$buffer .= ' ';
		}
		// R114 - 30 caractères : nom de famille
		$buffer .= strtoupper( $user_lastname );
		for ( $i = strlen( $user_lastname ); $i < 30; $i++ ) {
			$buffer .= ' ';
		}
		// R115 - 20 caractères : prénoms
		$buffer .= strtoupper( $user_firstname );
		for ( $i = strlen( $user_firstname ); $i < 20; $i++ ) {
			$buffer .= ' ';
		}
		// R116 - 30 caractères : nom d'usage
		$buffer .= strtoupper( $user_use_lastname );
		for ( $i = strlen( $user_use_lastname ); $i < 30; $i++ ) {
			$buffer .= ' ';
		}
		// R117 - 20 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 20; $i++ ) {
			$buffer .= ' ';
		}
		// R118 - 1 caractère : sexe (1 homme, 2 femme)
		$buffer .= $user_gender;
		//**********************************************************************
		
		//**********************************************************************
		// DATE ET LIEU DE NAISSANCE (Personnes physiques)
		// R119 - 4 caractères : année de naissance
		$buffer .= $user_birthday_year;
		// R120 - 2 caractères : mois de naissance
		$buffer .= $user_birthday_month;
		// R121 - 2 caractères : jour de naissance
		$buffer .= $user_birthday_day;
		// R122 - 2 caractères : code département
		$buffer .= $user_birthday_department_code;
		// R123 - 3 caractères : code commune
		$buffer .= $user_birthday_town_code;
		// R124 - 26 caractères : libellé commune
		$buffer .= $user_birthday_town_label;
		for ( $i = strlen( $user_birthday_town_label ); $i < 26; $i++ ) {
			$buffer .= ' ';
		}
		// R125 - 1 caractère : espace (zone réservée)
		$buffer .= ' ';
		// R126 - 30 caractères : profession (laisser vide)
		for ( $i = 0; $i < 30; $i++ ) {
			$buffer .= ' ';
		}
		//**********************************************************************
		
		//**********************************************************************
		// ADRESSE DU BENEFICIAIRE
		// R127 - 32 caractères : complément d'adresse
		$buffer .= strtoupper( $investment_entity_address_complement );
		for ( $i = strlen( $investment_entity_address_complement ); $i < 32; $i++ ) {
			$buffer .= ' ';
		}
		// R128 - 4 caractères : numéro dans la voie
		for ( $i = strlen( $investment_entity_address_number ); $i < 4; $i++ ) {
			$buffer .= '0';
		}
		$buffer .= $investment_entity_address_number;
		// R129 - 1 caractère : B T Q C
		$buffer .= $investment_entity_address_number_complement;
		// R130 - 1 caractère : espace
		$buffer .= ' ';
		// R131 - 26 caractères : nature et nom de la voie
		$buffer .= strtoupper( $investment_entity_address );
		for ( $i = strlen( $investment_entity_address ); $i < 26; $i++ ) {
			$buffer .= ' ';
		}
		// R132 - 5 caractères : code insee commune
		$buffer .= $investment_entity_address_town_code;
		// R133 - 1 caractère : espace
		$buffer .= ' ';
		// R134 - 26 caractères : libellé commune
		$buffer .= $investment_entity_address_town;
		// R135 - 5 caractères : code postal
		$buffer .= $investment_entity_address_post_code;
		// R136 - 1 caractère : espace
		$buffer .= ' ';
		// R137 - 26 caractères : bureau distributeur
		$buffer .= $investment_entity_address_town_office;
		// R138 - 1 caractère : espace
		$buffer .= ' ';
		// R139 - 4 caractères : code catégorie juridique - laisser vide
		for ( $i = strlen( $investment_entity_address_number ); $i < 4; $i++ ) {
			$buffer .= '0';
		}
		// R140 - 4 caractères : période de référence MMJJ
		// TODO
		// R141 - 4 caractères : espaces
		$buffer .= '    ';
		//**********************************************************************
		
		return $buffer;
	}
	
	private static function add_ifu_amount_1( $fiscal_year, $amount_to_declare_received, $amount_to_declare_tax ) {
		$buffer = "";
		
		
		//**********************************************************************
		// ZONE INDICATIF
		$buffer .= self::add_ifu_entity_intro( $fiscal_year );
		// R108 - 2 caractères : code article
		$buffer .= 'R2';
		//**********************************************************************
		
		
		//**********************************************************************
		// CREDIT D'IMPOT
		// R209 - 10 caractères : crédit d’impôt non restituable
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R210 - 10 caractères : crédit d’impôt restituable
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R211 - 10 caractères : crédit d’impôt prélèvement restituable
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// PRODUITS DISTRIBUES ET REVENUS ASSIMILES
		// R213 - 10 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= ' ';
		}
		// R214 - 10 caractères : Avances, prêts ou acomptes
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R218 - 10 caractères : Distributions non éligibles à l'abattement de 40%
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R219 - 10 caractères : Dont valeurs étrangères (pour mémoire)
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R220 - 10 caractères : Jetons de présence
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R221 - 10 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= ' ';
		}
		// R222 - 10 caractères : revenus distribués éligibles à l'abattement de 40%
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R223 - 10 caractères : revenus exonérés
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R224 - 20 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 20; $i++ ) {
			$buffer .= ' ';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// REVENUS SOUMIS A PRELEVEMENT LIBERATOIRE OU A RETENUE A LA SOURCE
		// R226 - 10 caractères : base du prélèvement ou de la retenue à la source
		for ( $i = strlen( $amount_to_declare_received ); $i < 10; $i++ ) {
			$buffer .= '0';
		}
		$buffer .= $amount_to_declare_received;
		// R227 - 10 caractères : montant du prélèvement ou de la retenue à la source
		for ( $i = strlen( $amount_to_declare_tax ); $i < 10; $i++ ) {
			$buffer .= '0';
		}
		$buffer .= $amount_to_declare_tax;
		// R230 - 10 caractères : montant du prélèvement ou de la retenue à la source
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// CESSION DE VALEURS MOBILIERES
		// R231 - 10 caractères : montant total des cessions
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// REVENUS SOUMIS A L’IR ET POUR LESQUELS LES PRÉLÈVEMENTS SOCIAUX ONT DEJÀ ÉTÉ ACQUITTÉS
		// R232 - 10 caractères : Produits soumis à une imposition à taux forfaitaire (sans CSG déductible)
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R233 - 10 caractères : Répartitions de FCPR et distributions de SCR (sans CSG déductible)
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R234 - 10 caractères : Produits imposables au barème progressif (avec CSG déductible)
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// PRODUITS DE PLACEMENT A REVENU FIXE
		// R237 - 10 caractères : Produits ou gains
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R238 - 10 caractères : Pertes
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// PRODUITS DES MINIBONS ET DES PRÊTS DANS LE CADRE DU FINANCEMENT PARTICIPATIF
		// R239 - 10 caractères : Produits
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R240 - 10 caractères : Pertes
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// PRODUITS DES CONTRATS D'ASSURANCE-VIE ET PLACEMENTS ASSIMILÉS
		// Produits des contrats de moins de huit ans
		// R245 - 10 caractères : Produits des versements effectués avant le 27/09/17 soumis au barème progressif de l'impôt sur le revenu
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R246 - 10 caractères : Produits des versements effectués avant le 27/09/17 soumis à un prélèvement forfaitaire libératoire
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R247 - 10 caractères : Montant du prélèvement forfaitaire libératoire appliqué aux produits des versements effectués avant le 27/09/17
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R248 - 10 caractères : Produits des versements effectués à compter du 27/09/17
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// Produits des contrats de plus de huit ans
		// R252 - 10 caractères : Produits des versements effectués avant le 27/09/17 bénéficiant de l’abattement et soumis au barème progressif de l’impôt sur le revenu
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R253 - 10 caractères : Produits des versements effectués avant le 27/09/17 bénéficiant de l’abattement et soumis au prélèvement forfaitaire libératoire
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R254 - 10 caractères : Produits des versements effectués à compter du 27/09/17 bénéficiant de l'abattement
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// SOCIÉTÉS DE CAPITAL RISQUE
		// R249 - 10 caractères : Gains et distributions taxables
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R250 - 10 caractères : Gains et distributions exonérées
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// FRAIS
		// R251 - 10 caractères : Montant des frais
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// PARTS OU ACTIONS DE «CARRIED INTEREST» : OBLIGATION DÉCLARATIVE SPÉCIFIQUE PRÉVUE PAR L'ARTICLE 242 TER C DU CGI
		// R261 - 10 caractères : Gains et distributions imposables selon les règles des plus-values de cession de valeurs mobilières des particuliers
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R262 - 10 caractères : Gains et distributions imposables selon les règles des traitements et salaires
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R271 - 19 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 19; $i++ ) {
			$buffer .= ' ';
		}
		//**********************************************************************
		
		return $buffer;
	}
	
	private static function add_ifu_amount_total( $fiscal_year, $entity_index ) {
		$buffer = "";
		
		//**********************************************************************
		// ZONE INDICATIF
		// T001 - 4 caractères : année de référence
		$buffer .= $fiscal_year;
		// T002 - 14 caractères : SIRET déclarant au 31/12
		$buffer .= self::$wedogood_siret;
		// T003 - 1 caractère : 1 si initiale ; 2 si rectificative
		$buffer .= '1';
		// T004 - 30 caractère : que des 9
		for ( $i = 0; $i < 30; $i++ ) {
			$buffer .= '9';
		}
		// T005 - 30 caractère : que des 9
		$buffer .= 'T0';
		//**********************************************************************
		
		//**********************************************************************
		// NOMBRE D'ENREGISTREMENTS
		// T006 - 8 caractères : Nombre d'enregistrements R1
		for ( $i = strlen( $entity_index ); $i < 8; $i++ ) {
			$buffer .= '0';
		}
		$buffer .= $entity_index;
		// T007 - 8 caractères : Nombre d'enregistrements R2
		for ( $i = 0; $i < 8; $i++ ) {
			$buffer .= '0';
		}
		// T008 - 8 caractères : Nombre d'enregistrements R3
		for ( $i = 0; $i < 8; $i++ ) {
			$buffer .= '0';
		}
		// T009 - 8 caractères : Nombre d'enregistrements R4
		for ( $i = 0; $i < 8; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		//**********************************************************************
		// DESIGNATION DU RESPONSABLE
		// T010 - 50 caractères : Nombre d'enregistrements R1
		$buffer .= self::$wedogood_person_incharge_name;
		for ( $i = strlen( self::$wedogood_person_incharge_name ); $i < 50; $i++ ) {
			$buffer .= ' ';
		}
		// T011 - 10 caractères : Numéro de téléphone
		$buffer .= self::$wedogood_person_incharge_phone;
		// T012 - 60 caractères : Adresse courriel
		$buffer .= self::$wedogood_person_incharge_email;
		for ( $i = strlen( self::$wedogood_person_incharge_email ); $i < 60; $i++ ) {
			$buffer .= ' ';
		}
		// T013 - 227 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 227; $i++ ) {
			$buffer .= ' ';
		}
		//**********************************************************************
		
		return $buffer;
	}
	
	/**************************************************************************/
	/* RECUPERATION DES DONNES GEOLOCALISEES */
	public static function parse_geolocation_data() {
		// On les range par nom de ville, en mettant une liste pour les homonymes
		self::$geolocation_data_by_town = array();
		
		// Format du fichier : Code_commune_INSEE;Nom_commune;Code_postal;Libelle_acheminement;Ligne_5;coordonnees_gps
		$geoloca_file_path = dirname( __FILE__ ) . '/../../data/geolocation/laposte_hexasmal.csv';
		$csv_handle = fopen( $geoloca_file_path, 'r' );
		
		if ( $csv_handle !== FALSE ) {
			while ( ( $data = fgetcsv( $csv_handle, 1000, ";" ) ) !== FALSE ) {
				$item = array();
				$item[ 'town_insee_code' ] = $data[ 0 ];
				$item[ 'town_label' ] = $data[ 1 ];
				$item[ 'postal_code' ] = $data[ 2 ];
				$item[ 'town_office' ] = $data[ 3 ];
				$item[ 'town_coordinates' ] = $data[ 5 ];
				
				if ( !isset( self::$geolocation_data_by_town[ $item[ 'town_label' ] ] ) ) {
					self::$geolocation_data_by_town[ $item[ 'town_label' ] ] = array();
				}
				array_push( self::$geolocation_data_by_town[ $item[ 'town_label' ] ], $item );
			}
			fclose( $csv_handle );
		}
	}
	
	public static function get_official_info_by_postal_code_and_town( $postal_code, $town ) {
		$buffer = FALSE;
		
		if ( isset( self::$geolocation_data_by_town[ $town ] ) ) {
			foreach ( self::$geolocation_data_by_town[ $town ] as $town_item ) {
				if ( strpos( strval( $town_item[ 'postal_code' ] ), strval( $postal_code ) ) !== FALSE ) {
					$buffer = $town_item;
					break;
				}
			}
		}
		
		return $buffer;
	}
	
	public static function clean_town_name( $town_name ) {
		$buffer = self::clean_name( $town_name );
		
		// Formatages spécifiques
		$search_replace = array(
			'SAINT-' => 'ST ',
			'SAINTE-' => 'STE ',
			'SAINT ' => 'ST ',
			'SAINTE ' => 'STE '
		);
		$buffer = str_replace( array_keys( $search_replace ), array_values( $search_replace ), $buffer );
		
		return $buffer;
	}
	
	public static function clean_name( $name ) {
		$buffer = strtoupper( $name );
		
		// Caractères spéciaux
		$search_replace = array(
			'&#039;' => ' ',
			'-' => ' ',
			'&EACUTE;' => 'E',
			'&EGRAVE;' => 'E',
			'&ECIRC;' => 'E',
			'&ATILDE;' => 'A',
			'&AGRAVE;' => 'A',
			'&OCIRC;' => 'O',
			'&CCEDIL;' => 'C'
		);
		$buffer = str_replace( array_keys( $search_replace ), array_values( $search_replace ), $buffer );
		
		return $buffer;
	}
	/**************************************************************************/
}
