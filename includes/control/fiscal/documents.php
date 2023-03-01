<?php
class WDG_FiscalDocuments {
	
	private static $wedogood_name = 'WE DO GOOD';
	private static $wedogood_street_number = '40';
	private static $wedogood_street = 'rue de la tour d\'Auvergne';
	private static $wedogood_town_insee_number = '44109';
	private static $wedogood_town_label = 'Nantes';
	private static $wedogood_post_code = '44200';
	private static $wedogood_town_office = 'Nantes';
	
	private static $wedogood_siren = '797519105';
	private static $wedogood_siret = '79751910500051';
	private static $wedogood_previous_siret = '79751910500051';
	private static $wedogood_legal_category = '5710';
	
	private static $wedogood_person_incharge_name = 'Schneider Emilien';
	private static $wedogood_person_incharge_phone = '0972651589';
	private static $wedogood_person_incharge_email = 'admin@wedogood.co';
	
	private static $tax_coef = 0.3;
	private static $declaration_type_init = 1;
	private static $declaration_type_rectif = 2;

	private static $geolocation_data_by_town;

	private static $declaration_type = 1;
	
	
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
		$file_path = dirname( __FILE__ ) . '/../../../files/fiscal-documents/errors__' .$campaign_id. '_' .$fiscal_year. '.txt';
		$errors_file_content = '';
		if ( !empty( self::$errors ) ) {
			foreach ( self::$errors as $error ) {
				$errors_file_content .= "- " .$error. "\n";
			}
			
			self::save_file( $file_path, $errors_file_content );
		}

		// Reinit du tableau d'erreurs
		self::$errors = array();
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
		$file_path = dirname( __FILE__ ) . '/../../../files/fiscal-documents/resume__' .$campaign_id. '_' .$fiscal_year. '.txt';
		self::save_file( $file_path, $resume_file_content );
	}
	
	private static function save_ifu_file( $campaign_id, $fiscal_year, $ifu_file_content ) {
		$file_path = dirname( __FILE__ ) . '/../../../files/fiscal-documents/ifu__' .$campaign_id. '_' .$fiscal_year. '.txt';
		self::save_file( $file_path, $ifu_file_content );
	}
	/**************************************************************************/
	

	/**
	 * Génère les fichiers de l'année précédente
	 * @param array $campaign_year
	 * @param int $init
	 */
	public static function generate( $campaign_year, $init = 1 ) {
		// Récupération du type de déclarations (initiale ou rectificative)
		if ( $init == self::$declaration_type_rectif ) {
			self::$declaration_type = self::$declaration_type_rectif;
		}

		// Initialisation des parcours
		$amount_by_user = array();
		$file_prefix = '';
		$current_date = new DateTime();

		// Ouverture fichier de geoloc
		self::parse_geolocation_data();

		// On stocke d'un côté un résumé textuel lisible. TODO CSV ?
		$resume_txt = "Information fiscales\n\n";
		// On stocke d'un autre côté le fichier txt de déclaration des IFUs
		// Documentation de référence (2020) : https://www.impots.gouv.fr/portail/files/media/1_metier/3_partenaire/tiers_declarants/cdc_td_bilateral/td_rcm_r20_v1.0.pdf
		$ifu_txt = self::add_ifu_declaring_info( $current_date->format( 'Y' ) - 1 );

		// Année en cours (telle que sortie dans les noms de fichiers)
		$file_year = $current_date->format( 'Y' );

		// Parcours de chaque ID de campagne et de chaque année fiscale associée
		// Cela nous permet de déterminer la liste des investisseurs qui ont bien perçu des plus-values dans l'année fiscale de référence
		foreach ( $campaign_year as $campaign_id => $fiscal_year ) {
			$file_prefix .= $campaign_id . '_';

			// Campagne analysée
			$campaign = new ATCF_Campaign( $campaign_id );
			
			// Récupération de l'année en cours pour trouver l'année dernière
			if ( empty( $fiscal_year ) ) {
				$fiscal_year = $file_year - 1;
			}
			
			// On récupère la liste des investissements de la campagne
			// Premier parcours pour regrouper par utilisateur
			$investments_items_by_user = array();
			$investments = $campaign->payments_data();
			foreach ( $investments as $investment_item ) {
				if ( $investment_item[ 'status' ] == 'publish' ) {
					$investment_entity_id = $investment_item[ 'user' ];
					if ( !isset( $investments_items_by_user[ $investment_entity_id ] ) ) {
						$investments_items_by_user[ $investment_entity_id ] = array();
					}
					array_push( $investments_items_by_user[ $investment_entity_id ], $investment_item );
				}
			}
		
			// Ensuite on parcourt par utilisateur pour regrouper les montants
			$entity_index = 0;
			foreach ( $investments_items_by_user as $investment_user_id => $investments_for_user ) {
				$investment_amount = 0;
				$investment_entity_id = $investment_user_id;
				$investment_entity = FALSE;
				$investment_entity_is_registered = FALSE;
				if ( WDGOrganization::is_user_organization( $investment_entity_id ) ) {
					$investment_entity = new WDGOrganization( $investment_entity_id );
					$investment_entity_is_registered = $investment_entity->is_registered_lemonway_wallet();
				} else {
					$investment_entity = new WDGUser( $investment_entity_id );
					$investment_entity_is_registered = $investment_entity->is_lemonway_registered();
				}
	
				// Si la personne n'est pas authentifiée, elle n'a pas reçu ses royalties pour l'instant
				if ( !$investment_entity_is_registered ) {
					continue;
				}
	
				$investment_user_rois_amount_total = 0;
				$investment_user_rois_amount_year = 0;
				$amount_tax_sampled_year = 0;
				
				foreach ( $investments_for_user as $investment_item ) {
					$investment_amount += $investment_item[ 'amount' ];
	
					// On récupère la liste des royalties reçues par investissement jusqu'à l'année précédente
					$investment_user_rois = $investment_entity->get_royalties_by_investment_id( $investment_item[ 'ID' ] );
					foreach ( $investment_user_rois as $roi_item ) {
						$date_transfer = new DateTime( $roi_item->date_transfer );
						if ( $date_transfer->format( 'Y' ) <= $fiscal_year ) {
							$investment_user_rois_amount_total += $roi_item->amount;
							if ( $date_transfer->format( 'Y' ) == $fiscal_year ) {
								$investment_user_rois_amount_year += $roi_item->amount;
								// Calcul de la taxe effectivement prélevée avec la donnée spécifique de taxe
								$tax_items = WDGWPREST_Entity_ROITax::get_by_id_roi( $roi_item->id );
								foreach ( $tax_items as $tax_item ) {
									$amount_tax_sampled_year += $tax_item->amount_tax_in_cents / 100;
								}
							}
						}
					}
				}
	
				// Calcul de la somme à déclarer : on ne doit prendre que la plus-value sur l'année en cours
				$amount_to_declare = min( $investment_user_rois_amount_year, $investment_user_rois_amount_total - $investment_amount );

				if ( !isset( $amount_by_user[ $investment_entity_id ] ) ) {
					$amount_by_user[ $investment_entity_id ] = array(
						'investment_amount'			=> 0,
						'amount_to_declare'			=> 0,
						'amount_tax_sampled_year'	=> 0,
						'project_year_str'			=> ''
					);
				}
				$amount_by_user[ $investment_entity_id ][ 'investment_amount' ] += $investment_amount;
				$amount_by_user[ $investment_entity_id ][ 'amount_to_declare' ] += $amount_to_declare;
				$amount_by_user[ $investment_entity_id ][ 'amount_tax_sampled_year' ] += $amount_tax_sampled_year;
				$amount_by_user[ $investment_entity_id ][ 'project_year_str' ] .= '- ' .$campaign->get_name(). ' (Année ' .$fiscal_year. ') ';
			}
		}


		// Parcours de la liste des investisseurs concernés pour éditer les fichiers
		foreach ( $amount_by_user as $investment_entity_id => $declaration_item ) {
			$investment_amount = $declaration_item[ 'investment_amount' ];
			$amount_to_declare = $declaration_item[ 'amount_to_declare' ];
			$amount_tax_sampled_year = $declaration_item[ 'amount_tax_sampled_year' ];
			$project_year_str = $declaration_item[ 'project_year_str' ];

			// Si la somme des royalties a dépassé l'investissement initial
			if ( $amount_to_declare > 0 ) {
				$ifu_entity_txt = self::add_ifu_entity( $investment_entity_id, $fiscal_year );
				$amount_to_declare_round = round( $amount_to_declare );
				$resume_txt .= $project_year_str . "\n";
				$resume_txt .= self::add_resume_entity( $investment_entity_id, $investment_amount, $amount_to_declare_round, $amount_tax_sampled_year );
				if ( !empty( $ifu_entity_txt ) ) {
					$ifu_txt .= $ifu_entity_txt;
					$ifu_txt .= self::add_ifu_amount_1( $investment_entity_id, $fiscal_year, $amount_to_declare_round, $amount_tax_sampled_year );
					$entity_index++;
				}
			}
		}

		// Fin du fichier IFU (nombre de déclarations)
		$ifu_txt .= self::add_ifu_amount_total( $file_year - 1, $entity_index );
		$resume_txt .= "\nTotal : " .$entity_index;

		// Enregistrement des fichiers finaux
		self::save_errors_file( $file_prefix, $file_year );
		self::save_resume_file( $file_prefix, $file_year, $resume_txt );
		self::save_ifu_file( $file_prefix, $file_year, $ifu_txt );
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
		$buffer .= ">> Montant du prélèvement : " .$amount_tax. " €\n\n";
		
		return $buffer;
	}
	
	/**
	 * Retourne une chaine d'introduction pour le fichier IFU : la partie décrivant le déclarant
	 * Documentation de référence (2020) : https://www.impots.gouv.fr/portail/files/media/1_metier/3_partenaire/tiers_declarants/cdc_td_bilateral/td_rcm_r20_v1.0.pdf
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
		$buffer .= self::$declaration_type;
		// D004 - 30 caractères : uniquement des 0
		for ( $i = 0; $i < 30; $i++ ) {
			$buffer .= '0';
		}
		// D005 - 2 caractères : code article D0
		$buffer .= 'D0';
		// D006 - 50 caractères : raison sociale
		$company_name = self::$wedogood_name;
		$buffer .= self::clean_size( $company_name, 50, 0, 'raison sociale wdg' );
		// D007 - 4 caractères : catégorie juridique du déclarant. Cf https://www.insee.fr/fr/information/2028129
		$buffer .= self::$wedogood_legal_category;
		//**********************************************************************
		
		// TODO : changer toutes les boucles qui ajoutent des espaces par des str_pad
		
		//**********************************************************************
		// ADRESSE DU DECLARANT
		// D009 - 32 caractères : complément d'adresse
		for ( $i = 0; $i < 32; $i++ ) {
			$buffer .= ' ';
		}
		// D010 - 4 caractères : numéro dans la voie (préfixé par 0 si nécessaire)
		$buffer .= str_pad( self::$wedogood_street_number, 4, '0', STR_PAD_LEFT );
		// D011 - 1 caractère : B T Q C
		$buffer .= ' ';
		// D012 - 1 caractère : séparateur
		$buffer .= ' ';
		// D013 - 26 caractères : nature et nom de la voie
		$buffer .= self::clean_size( self::$wedogood_street, 26, 0, 'adresse wdg' );
		// D014 - 5 caractères : code INSEE des communes. 
		// Cf https://www.insee.fr/fr/recherche/recherche-geographique?debut=0
		// OU https://www.insee.fr/fr/information/2666684 pour le fichier complet
		$buffer .= self::$wedogood_town_insee_number;
		// D015 - 1 caractère : séparateur
		$buffer .= ' ';
		// D016 - 26 caractères : libellé commune
		$buffer .= self::clean_size( self::$wedogood_town_label, 26, 0, 'commune wdg' );
		// D017 - 5 caractères : code postal
		$buffer .= self::$wedogood_post_code;
		// D018 - 1 caractère : séparateur
		$buffer .= ' ';
		// D019 - 26 caractères : bureau distributeur
		$buffer .= self::clean_size( self::$wedogood_town_office, 26, 0, 'bureau distrib wdg' );
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
	
	private static function add_ifu_entity_intro( $investment_entity_id, $fiscal_year ) {
		$buffer = "";
		// R101/R201 - 4 caractères : année de référence
		$buffer .= $fiscal_year;
		// R102/R202 - 14 caractères : SIRET déclarant au 31/12
		$buffer .= self::$wedogood_siret;
		// R103/R203 - 1 caractère : 1 si initiale ; 2 si rectificative
		$buffer .= self::$declaration_type;
		
		$wallet_id = '';
		if ( WDGOrganization::is_user_organization( $investment_entity_id ) ) {
			$WDGOrganization = new WDGOrganization( $investment_entity_id );
			$wallet_id = $WDGOrganization->get_lemonway_id();
			
		} else {
			$WDGUser = new WDGUser( $investment_entity_id );
			$wallet_id = $WDGUser->get_lemonway_id();
		}


		// Ces champs sont utilisés pour les versements sur comptes bancaires habituels
		// Mais nous pouvons les utiliser pour transmettre les identifiants de wallet sur LW
		// Cela se transforme en une zone de 30 caractères

		// R104/R204 - 9 caractères : code établissement
		// R105/R205 - 5 caractères : code guichet
		for ( $i = 0; $i < 14; $i++ ) {
			$buffer .= '0';
		}
		// R106/R206 - 14 caractères : numéro de compte ou numéro de contrat
		$buffer .= self::clean_size( $wallet_id, 14, $investment_entity_id, 'ID WALLET', STR_PAD_LEFT );
		// R107/R207 - 2 caractères : clé
		for ( $i = 0; $i < 2; $i++ ) {
			$buffer .= '0';
		}


		return $buffer;
	}
	
	private static function add_ifu_entity( $investment_entity_id, $fiscal_year ) {
		$buffer = "";
		
		
		//**********************************************************************
		// ZONE INDICATIF
		$buffer .= self::add_ifu_entity_intro( $investment_entity_id, $fiscal_year );
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
			$investment_entity_address = self::clean_name( $WDGOrganization->get_address() );
			$investment_entity_address_complement = '';
			$investment_entity_address_number = $WDGOrganization->get_address_number();
			global $address_number_complements_tax_format;
			$investment_entity_address_number_complement = $address_number_complements_tax_format[ $WDGOrganization->get_address_number_comp() ];
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
			$investment_entity_period = '1231';
			
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
				$user_birthday_department_code = substr( $user_birthday_department_code, 0, 2 );
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
			$investment_entity_address = self::clean_name( $WDGUser->get_address() );
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
				// Si résident à l'étranger, on change ces données
				if ( $WDGUser->get_country() != 'FR' ) {
					global $country_list_insee;
					$investment_entity_address_town_code = $country_list_insee[ $WDGUser->get_country() ];
					$investment_entity_address_town_office = $country_list_insee[ $WDGUser->get_country() ];
					// Et on précoupe le code postal, au cas où ça dépasse
					$investment_entity_address_post_code = substr( $investment_entity_address_post_code, 0, 5 );
				}
				if ( empty( $investment_entity_address_town_code ) || empty( $investment_entity_address_town_office ) ) {
					self::add_error( 'Problème récupération de données pour localisation adresse - ID USER ' . $investment_entity_id . ' - ' . $user_firstname . ' ' . $user_lastname . ' --- infos recherchees : ' . $investment_entity_address_post_code . ' ' . $investment_entity_address_town );
				}
			}
			$investment_entity_period = '1231';
		}
		
		// R112 - 14 caractères : SIRET bénéficiaire
		$buffer .= $orga_idnumber;
		// R113 - 50 caractères : raison sociale
		$buffer .= self::clean_size( $orga_name, 50, $investment_entity_id, 'raison sociale' );
		// R114 - 30 caractères : nom de famille
		$buffer .= self::clean_size( $user_lastname, 30, $investment_entity_id, 'nom de famille' );
		// R115 - 20 caractères : prénoms
		$buffer .= self::clean_size( $user_firstname, 20, $investment_entity_id, 'prénoms' );
		// R116 - 30 caractères : nom d'usage
		$buffer .= self::clean_size( $user_use_lastname, 30, $investment_entity_id, 'nom usage' );
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
		$buffer .= self::clean_size( $user_birthday_town_label, 26, $investment_entity_id, 'ville naissance' );
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
		$investment_entity_address_complement = substr( $investment_entity_address, 26 );
		$buffer .= self::clean_size( $investment_entity_address_complement, 32, $investment_entity_id, 'comp adresse' );
		// R128 - 4 caractères : numéro dans la voie
		$buffer .= str_pad( $investment_entity_address_number, 4, '0', STR_PAD_LEFT );
		// R129 - 1 caractère : B T Q C
		$buffer .= self::clean_size( $investment_entity_address_number_complement, 1, $investment_entity_id, 'bis' );
		// R130 - 1 caractère : espace
		$buffer .= ' ';
		// R131 - 26 caractères : nature et nom de la voie
		$investment_entity_address = substr( $investment_entity_address, 0, 26 );
		$buffer .= self::clean_size( $investment_entity_address, 26, $investment_entity_id, 'adresse' );
		// R132 - 5 caractères : code insee commune
		$buffer .= $investment_entity_address_town_code;
		// R133 - 1 caractère : espace
		$buffer .= ' ';
		// R134 - 26 caractères : libellé commune
		$buffer .= self::clean_size( $investment_entity_address_town, 26, $investment_entity_id, 'libellé commune' );
		// R135 - 5 caractères : code postal
		$buffer .= self::clean_size( $investment_entity_address_post_code, 5, $investment_entity_id, 'code postal' );
		// R136 - 1 caractère : espace
		$buffer .= ' ';
		// R137 - 26 caractères : bureau distributeur
		$buffer .= self::clean_size( $investment_entity_address_town_office, 26, $investment_entity_id, 'bureau distributeur' );
		// R138 - 1 caractère : espace
		$buffer .= ' ';
		// R139 - 4 caractères : code catégorie juridique - laisser vide
		for ( $i = 0; $i < 4; $i++ ) {
			$buffer .= '0';
		}
		// R140 - 4 caractères : période de référence MMJJ
		$buffer .= $investment_entity_period;
		// R141 - 4 caractères : espaces
		for ( $i = 0; $i < 4; $i++ ) {
			$buffer .= ' ';
		}
		//**********************************************************************
		
		return $buffer;
	}
	
	private static function add_ifu_amount_1( $investment_entity_id, $fiscal_year, $amount_to_declare_received, $amount_to_declare_tax ) {
		$buffer = "";
		
		
		//**********************************************************************
		// ZONE INDICATIF
		$buffer .= self::add_ifu_entity_intro( $investment_entity_id, $fiscal_year );
		// R108/R208 - 2 caractères : code article
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
		// R224 - 10 caractères : Produits attachés aux retraits en capital des PER
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// REVENUS SOUMIS A PRELEVEMENT LIBERATOIRE OU A RETENUE A LA SOURCE
		// R226 - 10 caractères : base du prélèvement ou de la retenue à la source
		$buffer .= str_pad( $amount_to_declare_received, 10, '0', STR_PAD_LEFT );
		// R227 - 10 caractères : montant du prélèvement ou de la retenue à la source
		$buffer .= str_pad( $amount_to_declare_tax, 10, '0', STR_PAD_LEFT );
		// R228 - 10 caractères : Etablissement financier européen : base de la retenue à la source
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		//**********************************************************************
		
		
		//**********************************************************************
		// CESSION DE VALEURS MOBILIERES
		// R230 - 10 caractères : Soultes reçues lors d'opérations d'échange ou d'apport de titres
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R231 - 10 caractères : montant total des cessions de valeurs mobilières
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
		// R264 - 10 caractères : Produits   de   l'article   R2   soumis   au   seulprélèvement de solidarité
		for ( $i = 0; $i < 10; $i++ ) {
			$buffer .= '0';
		}
		// R271 - 9 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 9; $i++ ) {
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
		$buffer .= self::$declaration_type;
		// T004 - 30 caractère : que des 9
		for ( $i = 0; $i < 30; $i++ ) {
			$buffer .= '9';
		}
		// T005 - 2 caractère : code article
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
		for ( $i = strlen( $entity_index ); $i < 8; $i++ ) {
			$buffer .= '0';
		}
		$buffer .= $entity_index;
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
		// T010 - 50 caractères : Identité personne en charge
		$buffer .= self::clean_size( self::$wedogood_person_incharge_name, 50, 0, 'personne en charge' );
		// T011 - 10 caractères : Numéro de téléphone
		$buffer .= self::$wedogood_person_incharge_phone;
		// T012 - 60 caractères : Adresse courriel
		$buffer .= self::clean_size( self::$wedogood_person_incharge_email, 60, 0, 'courriel personne en charge' );
		// T013 - 9 caractères : SIREN du remettant
		$buffer .= self::$wedogood_siren;
		// T014 - 218 caractères : espaces (zone réservée)
		for ( $i = 0; $i < 218; $i++ ) {
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
			'-' => ' ',
			'SAINT-' => 'ST ',
			'SAINTE-' => 'STE ',
			'SAINT ' => 'ST ',
			'SAINTE ' => 'STE '
		);
		$buffer = str_replace( array_keys( $search_replace ), array_values( $search_replace ), $buffer );
		
		return $buffer;
	}
	
	public static function clean_name( $name ) {
		$buffer = strtoupper( trim( $name ) );
		
		// Caractères spéciaux
		$search_replace = array(
			'&#039;' => ' ',
			'&DEG;' => ' ',
			'&ATILDE;' => 'A',
			'à' => 'A',
			'À' => 'A',
			'&AGRAVE;' => 'A',
			'é' => 'E',
			'É' => 'E',
			'&EACUTE;' => 'E',
			'è' => 'E',
			'È' => 'E',
			'&EGRAVE;' => 'E',
			'ê' => 'E',
			'Ê' => 'E',
			'&ECIRC;' => 'E',
			'ë' => 'E',
			'Ë' => 'E',
			'&EUML;' => 'E',
			'î' => 'I',
			'Î' => 'I',
			'&ICIRC;' => 'I',
			'ô' => 'O',
			'Ô' => 'O',
			'&OCIRC;' => 'O',
			'ç' => 'C',
			'Ç' => 'C',
			'&CCEDIL;' => 'C'
		);
		$buffer = str_replace( array_keys( $search_replace ), array_values( $search_replace ), $buffer );
		
		return $buffer;
	}
	
	/**
	 * Arrange les champs à la bonne taille, et inscrit une erreur si dépassement
	 * @param string $input
	 * @param int $size
	 * @param int $error_entity_id
	 * @param string $error_field
	 * @return string
	 */
	public static function clean_size( $input, $size, $error_entity_id, $error_field, $pad_type = STR_PAD_RIGHT ) {
		if ( strlen( $input ) > $size ) {
			// Suppression des caractères qui dépassent
			$buffer = substr( $input, 0, $size );
			self::add_error( 'Problème taille pour le champs '. $error_field .' - ID USER ' . $error_entity_id . ' >> ' . $input );
			
		} else {
			$buffer = str_pad( $input, $size, ' ', $pad_type );
		}
		
		return $buffer;
	}
	/**************************************************************************/
}
