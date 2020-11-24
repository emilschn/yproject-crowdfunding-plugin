<?php
class WDG_Form_User_Details extends WDG_Form {
	
	public static $name = 'user-details';
	
	public static $type_basics = 'basics';
	public static $type_vote = 'vote';
	public static $type_complete = 'complete';
	public static $type_extended = 'extended';
	
	public static $field_group_hidden = 'user-details-hidden';
	public static $field_group_basics = 'user-details-basics';
	public static $field_group_complete = 'user-details-complete';
	public static $field_group_extended = 'user-details-extended';
	public static $field_group_vote = 'user-details-vote';
	
	private $user_id;
	private $user_details_type;
	
	public function __construct( $user_id = FALSE, $user_details_type = FALSE ) {
		parent::__construct( WDG_Form_User_Details::$name );
		$this->user_id = $user_id;
		$this->user_details_type = $user_details_type;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			$active_languages = apply_filters( 'wpml_active_languages', NULL );
		}
		
		$WDGUser = new WDGUser( $this->user_id );
		
		// $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			WDG_Form_User_Details::$field_group_hidden,
			WDG_Form_User_Details::$name
		);
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			WDG_Form_User_Details::$field_group_hidden,
			$this->user_id
		);
		
		$this->addField(
			'hidden',
			'user_details_type',
			'',
			WDG_Form_User_Details::$field_group_hidden,
			$this->user_details_type
		);
		
		// $field_group_basics : Dans tous les cas, on met e-mail, prénom et nom
		$this->addField(
			'text',
			'email',
			__( 'form.user-details.EMAIL', 'yproject' ) . ' *',
			WDG_Form_User_Details::$field_group_basics,
			$WDGUser->get_email(),
			FALSE,
			'email'
		);
		
		$this->addField(
			'text',
			'firstname',
			__( 'form.user-details.FIRSTNAME', 'yproject' ) . ' *',
			WDG_Form_User_Details::$field_group_basics,
			$WDGUser->get_firstname(),
			__( "Merci de renseigner votre vrai pr&eacute;nom, sans abr&eacute;viation" )
		);
		
		$this->addField(
			'text',
			'lastname',
			__( 'form.user-details.LASTNAME', 'yproject' ) . ' *',
			WDG_Form_User_Details::$field_group_basics,
			$WDGUser->get_lastname(),
			__( "Merci de renseigner votre vrai nom, sans abr&eacute;viation" )
		);
		
		if ( $this->user_details_type == WDG_Form_User_Details::$type_complete || $this->user_details_type == WDG_Form_User_Details::$type_extended ) {
			$this->addField(
				'text',
				'use_lastname',
				__( 'form.user-details.USENAME', 'yproject' ),
				WDG_Form_User_Details::$field_group_basics,
				$WDGUser->get_use_lastname()
			);
		}
		
		// $field_group_basics : Si on met le formulaire basique, on propose de valider l'inscription à la NL
		if ( $this->user_details_type == WDG_Form_User_Details::$type_basics ) {
			
			$is_subscribed_to_newsletter = FALSE;
			$user_email = $WDGUser->get_email();
			if ( !empty( $user_email ) ) {
				$return = FALSE;
				try {
					$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 15000 );
					$return = $mailin->get_user( array(
						"email"		=> $user_email
					) );
				} catch ( Exception $e ) {
					ypcf_debug_log( "WDGUser::set_subscribe_authentication_notification > erreur sendinblue" );
				}

				if ( isset( $return[ 'code' ] ) && $return[ 'code' ] != 'failure' ) {
					if ( isset( $return[ 'data' ] ) && isset( $return[ 'data' ][ 'listid' ] ) ) {
						$lists_is_in = array();
						foreach( $return[ 'data' ][ 'listid' ] as $list_id ) {
							$lists_is_in[ $list_id ] = TRUE;
						}
						if ( !empty( $lists_is_in[ 5 ] ) && !empty( $lists_is_in[ 6 ] ) ) {
							$is_subscribed_to_newsletter = TRUE;
						}
					}
				}
			}
			
			$this->addField(
				'checkboxes',
				'',
				'',
				WDG_Form_User_Details::$field_group_basics,
				[ $is_subscribed_to_newsletter ],
				FALSE,
				[
					'subscribe_newsletter' => __( 'form.user-details.WISH_RECEIVE_NEWSLETTER', 'yproject' )
				]
			);
			
		}
		
		// $field_group_complete : Si on met le formulaire complet, on rajoute nationalité, ville et date de naissance, adresse, genre
		if ( $this->user_details_type == WDG_Form_User_Details::$type_complete || $this->user_details_type == WDG_Form_User_Details::$type_extended ) {
		
			$this->addField(
				'select',
				'gender',
				__( 'form.user-details.YOU_ARE', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_gender(),
				FALSE,
				[
					'female'	=> __( 'form.user-details.A_WOMAN', 'yproject' ),
					'male'		=> __( 'form.user-details.A_MAN', 'yproject' )
				]
			);
			
			$this->addField(
				'date',
				'birthday',
				__( 'form.user-details.BIRTH_DATE', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_lemonway_birthdate()
			);
			
			$this->addField(
				'text',
				'birthplace',
				__( 'form.user-details.BIRTH_PLACE', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_birthplace()
			);

			$district_list = array();
			$district_list[ 0 ] = '-';
			for ( $i = 1; $i <= 20; $i++ ) {
				$district_list[ $i ] = $i;
			}
			$this->addField(
				'select',
				'birthplace_district',
				__( 'form.user-details.BIRTH_PLACE_DISTRICT', 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_birthplace_district(),
				__( 'form.user-details.BIRTH_PLACE_DISTRICT_DESCRIPTION', 'yproject' ),
				$district_list
			);
			
			global $french_departments;
			$this->addField(
				'select',
				'birthplace_department',
				__( 'form.user-details.BIRTH_PLACE_COUNTY', 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_birthplace_department(),
				__( 'form.user-details.BIRTH_PLACE_COUNTY_DESCRIPTION', 'yproject' ),
				$french_departments
			);
			
			global $country_list;
			$this->addField(
				'select',
				'birthplace_country',
				__( 'form.user-details.BIRTH_COUNTRY', 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_birthplace_country(),
				FALSE,
				$country_list
			);
			
			$this->addField(
				'select',
				'nationality',
				__( 'form.user-details.NATIONALITY', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_nationality(),
				FALSE,
				$country_list
			);
			
			$this->addField(
				'text',
				'address_number',
				__( 'form.user-details.ADDRESS_NUMBER', 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_address_number()
			);
			
			global $address_number_complements;
			$this->addField(
				'select',
				'address_number_complement',
				__( 'form.user-details.ADDRESS_NUMBER_COMPLEMENT', 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_address_number_complement(),
				FALSE,
				$address_number_complements
			);
			
			$this->addField(
				'text',
				'address',
				__( 'form.user-details.ADDRESS', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_address()
			);
			
			$this->addField(
				'text',
				'postal_code',
				__( 'form.user-details.ZIP_CODE', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_postal_code()
			);
			
			$this->addField(
				'text',
				'city',
				__( 'form.user-details.CITY', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_city()
			);

			$this->addField(
				'select',
				'country',
				__( 'form.user-details.COUNTRY', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_country( 'iso2' ),
				FALSE,
				$country_list
			);
			
			$this->addField(
				'select',
				'tax_country',
				__( 'form.user-details.TAX_COUNTRY', 'yproject' ) . ' *',
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_tax_country( 'iso2' ),
				FALSE,
				$country_list
			);
			
		}
		
		// $field_group_vote : A la fin du formulaire de vote, on rajoute le téléphone
		if ( $this->user_details_type == WDG_Form_User_Details::$type_vote ) {
			$this->addField(
				'text',
				'phone_number',
				__( 'form.user-details.PHONE', 'yproject' ),
				WDG_Form_User_Details::$field_group_vote,
				$WDGUser->get_phone_number()
			);
		
		}
		
		// $field_group_extended : A la fin du formulaire étendu, on rajoute le téléphone et la description
		if ( $this->user_details_type == WDG_Form_User_Details::$type_extended ) {
			$this->addField(
				'text',
				'phone_number',
				__( 'form.user-details.PHONE', 'yproject' ),
				WDG_Form_User_Details::$field_group_extended,
				$WDGUser->get_phone_number()
			);
		
			$this->addField(
				'textarea',
				'contact_if_deceased',
				__( 'form.user-details.CONTACT_IF_DECEASED', 'yproject' ),
				WDG_Form_User_Details::$field_group_extended,
				$WDGUser->get_contact_if_deceased(),
				__( 'form.user-details.CONTACT_IF_DECEASED_DESCRIPTION', 'yproject' )
			);
		
			
			if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
				$language_list = array();
				$language_list[ 'fr' ] = $active_languages[ 'fr' ][ 'native_name' ];
				foreach ( $active_languages as $language_key => $language_item ) {
					if ( $language_key != 'fr' ) {
						$language_list[ $language_key ] = $language_item[ 'native_name' ];
					}
				}

				$this->addField(
					'select',
					'language',
					__( 'form.user-details.LANGUAGE', 'yproject' ),
					WDG_Form_User_Details::$field_group_extended,
					$WDGUser->get_language(),
					FALSE,
					$language_list
				);
			}
		}
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$WDGUser = new WDGUser( $user_id );
		$WDGUser_current = WDGUser::current();
		
		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Sécurité, ne devrait pas arriver non plus
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() && !$WDGUser_current->is_admin() ) {

		// Analyse du formulaire
		} else {
			// Informations de base
			$email = $this->getInputText( 'email' );
			if ( !is_email( $email ) || !WDGRESTAPI_Lib_Validator::is_email( $email )  ) {
				$error = array(
					'code'		=> 'email',
					'text'		=> __( 'form.user-details.EMAIL_ADDRESS_NOT_OK', 'yproject' ),
					'element'	=> 'email'
				);
				array_push( $feedback_errors, $error );
			}
			
			$firstname = $this->getInputText( 'firstname' );
			if ( empty( $firstname ) || !WDGRESTAPI_Lib_Validator::is_name( $firstname )  ) {
				$error = array(
					'code'		=> 'firstname',
					'text'		=> __( 'form.user-details.FIRST_NAME_EMPTY', 'yproject' ),
					'element'	=> 'firstname'
				);
				array_push( $feedback_errors, $error );
			}
			
			$lastname = $this->getInputText( 'lastname' );
			if ( empty( $lastname ) || !WDGRESTAPI_Lib_Validator::is_name( $lastname ) ) {
				$error = array(
					'code'		=> 'lastname',
					'text'		=> __( 'form.user-details.LAST_NAME_EMPTY', 'yproject' ),
					'element'	=> 'lastname'
				);
				array_push( $feedback_errors, $error );
			}
			


			$user_details_type = $this->getInputText( 'user_details_type' );
			if ( $user_details_type == WDG_Form_User_Details::$type_extended || $user_details_type == WDG_Form_User_Details::$type_complete ) {
				$use_lastname = $this->getInputText( 'use_lastname' );
				$gender = $this->getInputText( 'gender' );
				$birthday = $this->getInputText( 'birthday' );
				$birthdate = new DateTime();
				if ( !empty( $birthday ) ) {
					$birthdate = DateTime::createFromFormat( 'd/m/Y', $birthday );
					if ( empty( $birthdate ) ) {
						$birthdate = new DateTime();
					}
				}
				$birthplace = $this->getInputText( 'birthplace' );
				if ( empty( $birthplace ) || !WDGRESTAPI_Lib_Validator::is_name( $birthplace ) ) {
					$error = array(
						'code'		=> 'birthplace',
						'text'		=> __( 'form.user-details.BIRTH_PLACE_ERROR', 'yproject' ),
						'element'	=> 'birthplace'
					);
					array_push( $feedback_errors, $error );
				}

				$birthplace_district = $this->getInputText( 'birthplace_district' );
				$birthplace_department = $this->getInputText( 'birthplace_department' );
				$birthplace_country = $this->getInputText( 'birthplace_country' );
				$nationality = $this->getInputText( 'nationality' );
				$address_number = $this->getInputText( 'address_number' );
				$address_number_complement = $this->getInputText( 'address_number_complement' );
				$country = $this->getInputText( 'country' );
				$tax_country = $this->getInputText( 'tax_country' );

				$address = $this->getInputText( 'address' );
				if ( empty( $address ) || !WDGRESTAPI_Lib_Validator::is_name( $address ) ) {
					$error = array(
						'code'		=> 'address',
						'text'		=> __( 'form.user-details.ADDRESS_ERROR', 'yproject' ),
						'element'	=> 'address'
					);
					array_push( $feedback_errors, $error );
				}

				$postal_code = $this->getInputText( 'postal_code' );				
				if ( empty( $postal_code ) || !WDGRESTAPI_Lib_Validator::is_postalcode( $postal_code, $country ) ) {
					$error = array(
						'code'		=> 'postal_code',
						'text'		=> __( 'form.user-details.ZIP_CODE_ERROR', 'yproject' ),
						'element'	=> 'postal_code'
					);
					array_push( $feedback_errors, $error );
				}

				$city = $this->getInputText( 'city' );
				if ( empty( $city ) || !WDGRESTAPI_Lib_Validator::is_name( $city ) ) {
					$error = array(
						'code'		=> 'city',
						'text'		=> __( 'form.user-details.CITY_ERROR', 'yproject' ),
						'element'	=> 'city'
					);
					array_push( $feedback_errors, $error );
				}
			}
			
			if ( $user_details_type == WDG_Form_User_Details::$type_extended || $user_details_type == WDG_Form_User_Details::$type_vote ) {
				$phone_number = $this->getInputText( 'phone_number' );
			}
			
			$description = '';
			$contact_if_deceased = '';
			$language = '';
			if ( $user_details_type == WDG_Form_User_Details::$type_extended ) {
				$description = $this->getInputText( 'description' );
				$contact_if_deceased = $this->getInputText( 'contact_if_deceased' );
				$language = $this->getInputText( 'language' );
			}
			
			
			if ( empty( $feedback_errors ) ) {
				if ( $user_details_type == WDG_Form_User_Details::$type_complete || $user_details_type == WDG_Form_User_Details::$type_extended ) {
					if ( $user_details_type == WDG_Form_User_Details::$type_complete ) {
						// Quand on n'est pas au format étendu, le téléphone n'est pas transmis.
						// Il faut enregistrer l'existant, pour ne pas le supprimer
						$phone_number = $WDGUser->get_phone_number();
					}
					
					$WDGUser->save_data(
						$email, $gender, $firstname, $lastname, $use_lastname,
						$birthdate->format('d'), $birthdate->format('m'), $birthdate->format('Y'),
						$birthplace, $birthplace_district, $birthplace_department, $birthplace_country, $nationality,
						$address_number, $address_number_complement, $address, $postal_code, $city, $country, $tax_country, $phone_number, 
						$contact_if_deceased, $language
					);
					
					$was_registered = $WDGUser->has_lemonway_wallet();
					if ( !$was_registered && $WDGUser->can_register_lemonway() ) {
						ypcf_debug_log( 'WDG_Form_User_Details::postForm > $WDGUser->register_lemonway();' );
						$WDGUser->register_lemonway();
						// Si il n'était authentifié sur LW et qu'on vient de l'enregistrer, on envoie les documents si certains étaient déjà remplis
						if ( !$was_registered && $WDGUser->has_lemonway_wallet( TRUE ) ) {
							ypcf_debug_log( 'WDG_Form_User_Details::postForm > $WDGUser->send_kyc();' );
							$WDGUser->send_kyc();
						}
					}
					
					array_push( $feedback_success, __( 'form.user-details.SAVE_SUCCESS', 'yproject' ) );
					
				} else {
					$WDGUser->save_basics( $email, $firstname, $lastname );
					
					if ( $user_details_type == WDG_Form_User_Details::$type_vote ) {
						$WDGUser->save_meta( 'user_mobile_phone', $phone_number );
					}
					
					if ( $user_details_type == WDG_Form_User_Details::$type_basics ) {
						$subscribe_newsletter = $this->getInputChecked( 'subscribe_newsletter' );
						if ( empty( $subscribe_newsletter ) ) {
							try {
								$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 15000 );
								$return = $mailin->create_update_user( array(
									"email"		=> $email,
									"listid_unlink"	=> array( 5, 6 )
								) );
							} catch ( Exception $e ) {
								ypcf_debug_log( "postForm > erreur de désinscription à la NL -- " . print_r( $e, TRUE ) );
							}
							
						} else {
							WDGPostActions::subscribe_newsletter_sendinblue( $email );
						}
					}
				}
			}
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		$this->initFields(); // Reinit pour avoir les bonnes valeurs
		WDGUser::reload_current();
		
		return $buffer;
	}
	
	public function postFormAjax() {
		$buffer = $this->postForm();
		echo json_encode( $buffer );
		exit();
	}
	
}
