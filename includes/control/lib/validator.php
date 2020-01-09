<?php
/**
 * Classe de validation de données
 */
class WDGRESTAPI_Lib_Validator {
	
	/**
	 * Liste iso des pays
	 * Format iso3166-1 alpha-2
	 */
	public static $country_list = array(
		"FR" => "FRANCE",
		"AF" => "AFGHANISTAN",
		"AX" => "ÅLAND ISLANDS",
		"AL" => "ALBANIA",
		"DZ" => "ALGERIA",
		"AS" => "AMERICAN SAMOA",
		"AD" => "ANDORRA",
		"AO" => "ANGOLA",
		"AI" => "ANGUILLA",
		"AQ" => "ANTARCTICA",
		"AG" => "ANTIGUA AND BARBUDA",
		"AR" => "ARGENTINA",
		"AM" => "ARMENIA",
		"AW" => "ARUBA",
		"AU" => "AUSTRALIA",
		"AT" => "AUSTRIA",
		"AZ" => "AZERBAIJAN",
		"BS" => "BAHAMAS",
		"BH" => "BAHRAIN",
		"BD" => "BANGLADESH",
		"BB" => "BARBADOS",
		"BY" => "BELARUS",
		"BE" => "BELGIUM",
		"BZ" => "BELIZE",
		"BJ" => "BENIN",
		"BM" => "BERMUDA",
		"BT" => "BHUTAN",
		"BO" => "BOLIVIA, PLURINATIONAL STATE OF",
		"BQ" => "BONAIRE, SINT EUSTATIUS AND SABA",
		"BA" => "BOSNIA AND HERZEGOVINA",
		"BW" => "BOTSWANA",
		"BV" => "BOUVET ISLAND",
		"BR" => "BRAZIL",
		"IO" => "BRITISH INDIAN OCEAN TERRITORY",
		"BN" => "BRUNEI DARUSSALAM",
		"BG" => "BULGARIA",
		"BF" => "BURKINA FASO",
		"BI" => "BURUNDI",
		"KH" => "CAMBODIA",
		"CM" => "CAMEROON",
		"CA" => "CANADA",
		"CV" => "CAPE VERDE",
		"KY" => "CAYMAN ISLANDS",
		"CF" => "CENTRAL AFRICAN REPUBLIC",
		"TD" => "CHAD",
		"CL" => "CHILE",
		"CN" => "CHINA",
		"CX" => "CHRISTMAS ISLAND",
		"CC" => "COCOS (KEELING) ISLANDS",
		"CO" => "COLOMBIA",
		"KM" => "COMOROS",
		"CG" => "CONGO",
		"CD" => "CONGO, THE DEMOCRATIC REPUBLIC OF THE",
		"CK" => "COOK ISLANDS",
		"CR" => "COSTA RICA",
		"CI" => "CÔTE D'IVOIRE",
		"HR" => "CROATIA",
		"CU" => "CUBA",
		"CW" => "CURAÇAO",
		"CY" => "CYPRUS",
		"CZ" => "CZECH REPUBLIC",
		"DK" => "DENMARK",
		"DJ" => "DJIBOUTI",
		"DM" => "DOMINICA",
		"DO" => "DOMINICAN REPUBLIC",
		"EC" => "ECUADOR",
		"EG" => "EGYPT",
		"SV" => "EL SALVADOR",
		"GQ" => "EQUATORIAL GUINEA",
		"ER" => "ERITREA",
		"EE" => "ESTONIA",
		"ET" => "ETHIOPIA",
		"FK" => "FALKLAND ISLANDS (MALVINAS)",
		"FO" => "FAROE ISLANDS",
		"FJ" => "FIJI",
		"FI" => "FINLAND",
		"GF" => "FRENCH GUIANA",
		"PF" => "FRENCH POLYNESIA",
		"TF" => "FRENCH SOUTHERN TERRITORIES",
		"GA" => "GABON",
		"GM" => "GAMBIA",
		"GE" => "GEORGIA",
		"DE" => "GERMANY",
		"GH" => "GHANA",
		"GI" => "GIBRALTAR",
		"GR" => "GREECE",
		"GL" => "GREENLAND",
		"GD" => "GRENADA",
		"GP" => "GUADELOUPE",
		"GU" => "GUAM",
		"GT" => "GUATEMALA",
		"GG" => "GUERNSEY",
		"GN" => "GUINEA",
		"GW" => "GUINEA-BISSAU",
		"GY" => "GUYANA",
		"HT" => "HAITI",
		"HM" => "HEARD ISLAND AND MCDONALD ISLANDS",
		"VA" => "HOLY SEE (VATICAN CITY STATE)",
		"HN" => "HONDURAS",
		"HK" => "HONG KONG",
		"HU" => "HUNGARY",
		"IS" => "ICELAND",
		"IN" => "INDIA",
		"ID" => "INDONESIA",
		"IR" => "IRAN, ISLAMIC REPUBLIC OF",
		"IQ" => "IRAQ",
		"IE" => "IRELAND",
		"IM" => "ISLE OF MAN",
		"IL" => "ISRAEL",
		"IT" => "ITALY",
		"JM" => "JAMAICA",
		"JP" => "JAPAN",
		"JE" => "JERSEY",
		"JO" => "JORDAN",
		"KZ" => "KAZAKHSTAN",
		"KE" => "KENYA",
		"KI" => "KIRIBATI",
		"KP" => "KOREA, DEMOCRATIC PEOPLE'S REPUBLIC OF",
		"KR" => "KOREA, REPUBLIC OF",
		"KW" => "KUWAIT",
		"KG" => "KYRGYZSTAN",
		"LA" => "LAO PEOPLE'S DEMOCRATIC REPUBLIC",
		"LV" => "LATVIA",
		"LB" => "LEBANON",
		"LS" => "LESOTHO",
		"LR" => "LIBERIA",
		"LY" => "LIBYA",
		"LI" => "LIECHTENSTEIN",
		"LT" => "LITHUANIA",
		"LU" => "LUXEMBOURG",
		"MO" => "MACAO",
		"MK" => "MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF",
		"MG" => "MADAGASCAR",
		"MW" => "MALAWI",
		"MY" => "MALAYSIA",
		"MV" => "MALDIVES",
		"ML" => "MALI",
		"MT" => "MALTA",
		"MH" => "MARSHALL ISLANDS",
		"MQ" => "MARTINIQUE",
		"MR" => "MAURITANIA",
		"MU" => "MAURITIUS",
		"YT" => "MAYOTTE",
		"MX" => "MEXICO",
		"FM" => "MICRONESIA, FEDERATED STATES OF",
		"MD" => "MOLDOVA, REPUBLIC OF",
		"MC" => "MONACO",
		"MN" => "MONGOLIA",
		"ME" => "MONTENEGRO",
		"MS" => "MONTSERRAT",
		"MA" => "MOROCCO",
		"MZ" => "MOZAMBIQUE",
		"MM" => "MYANMAR",
		"NA" => "NAMIBIA",
		"NR" => "NAURU",
		"NP" => "NEPAL",
		"NL" => "NETHERLANDS",
		"NC" => "NEW CALEDONIA",
		"NZ" => "NEW ZEALAND",
		"NI" => "NICARAGUA",
		"NE" => "NIGER",
		"NG" => "NIGERIA",
		"NU" => "NIUE",
		"NF" => "NORFOLK ISLAND",
		"MP" => "NORTHERN MARIANA ISLANDS",
		"NO" => "NORWAY",
		"OM" => "OMAN",
		"PK" => "PAKISTAN",
		"PW" => "PALAU",
		"PS" => "PALESTINE, STATE OF",
		"PA" => "PANAMA",
		"PG" => "PAPUA NEW GUINEA",
		"PY" => "PARAGUAY",
		"PE" => "PERU",
		"PH" => "PHILIPPINES",
		"PN" => "PITCAIRN",
		"PL" => "POLAND",
		"PT" => "PORTUGAL",
		"PR" => "PUERTO RICO",
		"QA" => "QATAR",
		"RE" => "RÉUNION",
		"RO" => "ROMANIA",
		"RU" => "RUSSIAN FEDERATION",
		"RW" => "RWANDA",
		"BL" => "SAINT BARTHÉLEMY",
		"SH" => "SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA",
		"KN" => "SAINT KITTS AND NEVIS",
		"LC" => "SAINT LUCIA",
		"MF" => "SAINT MARTIN (FRENCH PART)",
		"PM" => "SAINT PIERRE AND MIQUELON",
		"VC" => "SAINT VINCENT AND THE GRENADINES",
		"WS" => "SAMOA",
		"SM" => "SAN MARINO",
		"ST" => "SAO TOME AND PRINCIPE",
		"SA" => "SAUDI ARABIA",
		"SN" => "SENEGAL",
		"RS" => "SERBIA",
		"SC" => "SEYCHELLES",
		"SL" => "SIERRA LEONE",
		"SG" => "SINGAPORE",
		"SX" => "SINT MAARTEN (DUTCH PART)",
		"SK" => "SLOVAKIA",
		"SI" => "SLOVENIA",
		"SB" => "SOLOMON ISLANDS",
		"SO" => "SOMALIA",
		"ZA" => "SOUTH AFRICA",
		"GS" => "SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS",
		"SS" => "SOUTH SUDAN",
		"ES" => "SPAIN",
		"LK" => "SRI LANKA",
		"SD" => "SUDAN",
		"SR" => "SURINAME",
		"SJ" => "SVALBARD AND JAN MAYEN",
		"SZ" => "SWAZILAND",
		"SE" => "SWEDEN",
		"CH" => "SWITZERLAND",
		"SY" => "SYRIAN ARAB REPUBLIC",
		"TW" => "TAIWAN, PROVINCE OF CHINA",
		"TJ" => "TAJIKISTAN",
		"TZ" => "TANZANIA, UNITED REPUBLIC OF",
		"TH" => "THAILAND",
		"TL" => "TIMOR-LESTE",
		"TG" => "TOGO",
		"TK" => "TOKELAU",
		"TO" => "TONGA",
		"TT" => "TRINIDAD AND TOBAGO",
		"TN" => "TUNISIA",
		"TR" => "TURKEY",
		"TM" => "TURKMENISTAN",
		"TC" => "TURKS AND CAICOS ISLANDS",
		"TV" => "TUVALU",
		"UG" => "UGANDA",
		"UA" => "UKRAINE",
		"AE" => "UNITED ARAB EMIRATES",
		"GB" => "UNITED KINGDOM",
		"US" => "UNITED STATES",
		"UM" => "UNITED STATES MINOR OUTLYING ISLANDS",
		"UY" => "URUGUAY",
		"UZ" => "UZBEKISTAN",
		"VU" => "VANUATU",
		"VE" => "VENEZUELA, BOLIVARIAN REPUBLIC OF",
		"VN" => "VIET NAM",
		"VG" => "VIRGIN ISLANDS, BRITISH",
		"VI" => "VIRGIN ISLANDS, U.S.",
		"WF" => "WALLIS AND FUTUNA",
		"EH" => "WESTERN SAHARA",
		"YE" => "YEMEN",
		"ZM" => "ZAMBIA",
		"ZW" => "ZIMBABWE"
	);
	
	public static $minimum_amount = 10;
	
	/**
	 * Vérifie si le champ est bien 1 ou 0
	 * @param string $input
	 * @return boolean
	 */
	public static function is_boolean( $input ) {
		return ( $input === 1 || $input === 0 ||  $input === '1' || $input === '0' );
	}
	
	/**
	 * Vérifie si le champ est bien un nombre
	 * @param string $input
	 * @return boolean
	 */
	public static function is_number( $input ) {
		if ( is_bool( $input ) ) {
			return FALSE;
		}
		$input = str_replace( ' ', '', $input );
		if ( $input == (string) (float) $input ) {
			return is_numeric( $input );
		}
		if ( $input >= 0 && is_string( $input ) && !is_float( $input ) ) {
			return ctype_digit( $input );
		}
		return is_numeric( $input );
	}
	
	/**
	 * Vérifie si le champ est bien un nombre positif
	 * @param string $input
	 * @return boolean
	 */
	public static function is_number_positive( $input ) {
		return ( self::is_number( $input ) && $input > 0 );
	}
	
	/**
	 * Vérifie si le champ est bien un nombre entier
	 * @param string $input
	 * @return boolean
	 */
	public static function is_number_integer( $input ) {
		return ( self::is_number( $input ) && $input == round( $input ) );
	}
	
	/**
	 * Vérifie si le champ est bien positif et entier
	 * @param string $input
	 * @return boolean
	 */
	public static function is_number_positive_integer( $input ) {
		return ( self::is_number_positive( $input ) && self::is_number_integer( $input ) );
	}

	/**
	 * Vérifie si la variable est un e-mail
	 * @param string $input
	 * @return boolean
	 */
	public static function is_email( $input ) {
		return ( filter_var( $input, FILTER_VALIDATE_EMAIL ) !== FALSE );
	}
	
	/**
	 * Vérifie si la variable est un nom
	 * @param string $input
	 * @return boolean
	 */
	public static function is_name( $input ) {
		return !empty( $input ) && !is_bool( $input ) && !self::is_number( $input ) && !self::is_email( $input );
	}
	
	/**
	 * Vérifie si la variable est de type "genre"
	 * @param string $input
	 * @return boolean
	 */
	public static function is_gender( $input ) {
		return ( $input === "male" || $input === "female" );
	}
	
	/**
	 * Vérifie si la variable est bien un code de nationalité
	 * @param string $input
	 * @return boolean
	 */
	public static function is_country_iso_code( $input ) {
		return !empty( $input ) && isset( WDGRESTAPI_Lib_Validator::$country_list[ strtoupper( $input ) ] );
	}
	
	/**
	 * Vérifie si la variable est bien un jour de date
	 * @param string $input
	 * @return boolean
	 */
	public static function is_date_day( $input ) {
		if ( !self::is_number_positive_integer( $input ) ) {
			return FALSE;
		}
		$input = str_replace( ' ', '', $input );
		return ( $input <= 31 );
	}
	
	/**
	 * Vérifie si la variable est bien un mois de date
	 * @param string $input
	 * @return boolean
	 */
	public static function is_date_month( $input ) {
		if ( !self::is_number_positive_integer( $input ) ) {
			return FALSE;
		}
		$input = str_replace( ' ', '', $input );
		return ( $input <= 12 );
	}
	
	/**
	 * Vérifie si la variable est bien une année de date
	 * @param string $input
	 * @return boolean
	 */
	public static function is_date_year( $input ) {
		return self::is_number_integer( $input );
	}
	
	/**
	 * Vérifie si la date est correcte
	 * @param string $input_day
	 * @param string $input_month
	 * @param string $input_year
	 * @return boolean
	 */
	public static function is_date( $input_day, $input_month, $input_year ) {
		if ( !self::is_number_integer( $input_day ) || !self::is_number_integer( $input_month ) || !self::is_number_integer( $input_year ) ) {
			return FALSE;
		}
		return checkdate( $input_month, $input_day, $input_year );
	}
	
	/**
	 * Vérifie si la date correspond à quelqu'un de majeur
	 * @param string $input_day
	 * @param string $input_month
	 * @param string $input_year
	 * @return boolean
	 */
	public static function is_major( $input_day, $input_month, $input_year ) {
		if ( !self::is_date( $input_day, $input_month, $input_year ) ) {
			return FALSE;
		}
		$today_day = date('j');
		$today_month = date('n');
		$today_year = date('Y');
		$years_diff = $today_year - $input_year;
		if ( $today_month <= $input_month ) {
			if ( $input_month == $today_month ) {
				if ( $input_day > $today_day ) {
					$years_diff--;
				}
			} else {
				$years_diff--;
			}
		}
		return ( $years_diff >= 18 );
	}
	
	/**
	 * Vérifie si la variable est bien un code postal
	 * @param string $input
	 * @param string $input_country
	 * @return boolean
	 */
	public static function is_postalcode( $input, $input_country ) {
		$input = str_replace( ' ', '', $input );
		if ( $input_country == 'FR' && strlen( $input ) == 4 ) {
			$input = '0' . $input;
		}
		return ( $input_country != 'FR' || preg_match( '#^[0-9]{5}$#', $input ) );
	}
	
	/**
	 * Vérifie si le montant est supérieur à 10
	 * @param string $input
	 * @return boolean
	 */
	public static function is_minimum_amount( $input ) {
		return ( self::is_number_positive( $input ) && $input >= self::$minimum_amount );
	}
	
	/**
	 * Vérifie si la variable est une URL
	 * @param string $input
	 * @return boolean
	 */
	public static function is_url( $input ) {
		return ( filter_var( $input, FILTER_VALIDATE_URL ) !== FALSE );
	}
	
	/**
	 * Vérifie si la variable est un IBAN
	 * @param string $input
	 * @return boolean
	 */
	public static function is_iban( $input ) {
		$iban = strtolower( str_replace( ' ', '', $input ) );
		$countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
		$chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);

		if ( strlen( $iban ) < 2 ) {
			return FALSE;
		}
		$country_key = substr( $iban, 0, 2 );
		if ( !array_key_exists( $country_key, $countries ) ) {
			return FALSE;
		}

		if ( strlen($iban) == $countries[ $country_key ] ) {
			$moved_char = substr( $iban, 4 ).substr( $iban, 0, 4 );
			$moved_char_array = str_split( $moved_char );
			$new_string = "";

			foreach ( $moved_char_array AS $key => $value ) {
				if ( !is_numeric( $moved_char_array[ $key ] ) ) {
					$moved_char_array[ $key ] = $chars[ $moved_char_array[ $key ] ];
				}
				$new_string .= $moved_char_array[ $key ];
			}

			if ( bcmod( $new_string, '97' ) == 1 ) {
				return TRUE;
			} else{
				return FALSE;
			}
			
		} else {
			return FALSE;
		}   
	}
	
	/**
	 * Vérifie si la variable est un BIC
	 * @param string $input
	 * @return boolean
	 */
	public static function is_bic( $input ) {
		return preg_match( '/^[a-z]{6}[2-9a-z][0-9a-np-z]([a-z0-9]{3}|x{3})?$/i', $input );
	}
}