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
			__( "E-mail *", 'yproject' ),
			WDG_Form_User_Details::$field_group_basics,
			$WDGUser->get_email(),
			FALSE,
			'email'
		);
		
		$this->addField(
			'text',
			'firstname',
			__( "Pr&eacute;nom *", 'yproject' ),
			WDG_Form_User_Details::$field_group_basics,
			$WDGUser->get_firstname()
		);
		
		$this->addField(
			'text',
			'lastname',
			__( "Nom *", 'yproject' ),
			WDG_Form_User_Details::$field_group_basics,
			$WDGUser->get_lastname()
		);
		
		if ( $this->user_details_type == WDG_Form_User_Details::$type_complete || $this->user_details_type == WDG_Form_User_Details::$type_extended ) {
			$this->addField(
				'text',
				'use_lastname',
				__( "Nom d'usage", 'yproject' ),
				WDG_Form_User_Details::$field_group_basics,
				$WDGUser->get_use_lastname()
			);
		}
		
		// $field_group_basics : Si on met le formulaire basique, on propose de valider l'inscription à la NL
		if ( $this->user_details_type == WDG_Form_User_Details::$type_basics ) {
			
			$is_subscribed_to_newsletter = FALSE;
			$user_email = $WDGUser->get_email();
			if ( !empty( $user_email ) ) {
				$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 5000 );
				$return = $mailin->get_user( array(
					"email"		=> $user_email
				) );
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
					'subscribe_newsletter' => __( "Je souhaite recevoir la newsletter WE DO GOOD mensuelle" )
				]
			);
			
		}
		
		// $field_group_complete : Si on met le formulaire complet, on rajoute nationalité, ville et date de naissance, adresse, genre
		if ( $this->user_details_type == WDG_Form_User_Details::$type_complete || $this->user_details_type == WDG_Form_User_Details::$type_extended ) {
		
			$this->addField(
				'select',
				'gender',
				__( "Vous &ecirc;tes *", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_gender(),
				FALSE,
				[
					'female'	=> __( "une femme", 'yproject' ),
					'male'		=> __( "un homme", 'yproject' )
				]
			);
			
			$this->addField(
				'date',
				'birthday',
				__( "Date de naissance *", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_lemonway_birthdate()
			);
			
			$this->addField(
				'text',
				'birthplace',
				__( "Ville de naissance *", 'yproject' ),
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
				__( "Arrondissement dans la ville de naissance", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_birthplace_district(),
				__( "Uniquement si la naissance a eu lieu &agrave; Paris, Marseille ou Lyon", 'yproject' ),
				$district_list
			);
			
			global $french_departments;
			$this->addField(
				'select',
				'birthplace_department',
				__( "D&eacute;partement de naissance", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_birthplace_department(),
				FALSE,
				$french_departments
			);
			
			global $country_list;
			$this->addField(
				'select',
				'birthplace_country',
				__( "Pays de naissance", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_birthplace_country(),
				FALSE,
				$country_list
			);
			
			$this->addField(
				'select',
				'nationality',
				__( "Nationalit&eacute; *", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_nationality(),
				FALSE,
				$country_list
			);
			
			$this->addField(
				'text',
				'address_number',
				__( "Num&eacute;ro", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_address_number()
			);
			
			global $address_number_complements;
			$this->addField(
				'select',
				'address_number_complement',
				__( "Compl&eacute;ment de num&eacute;ro", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_address_number_complement(),
				FALSE,
				$address_number_complements
			);
			
			$this->addField(
				'text',
				'address',
				__( "Adresse *", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_address()
			);
			
			$this->addField(
				'text',
				'postal_code',
				__( "Code postal *", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_postal_code()
			);
			
			$this->addField(
				'text',
				'city',
				__( "Ville *", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_city()
			);

			$this->addField(
				'select',
				'country',
				__( "Pays *", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_country( 'iso2' ),
				FALSE,
				$country_list
			);
			
			$this->addField(
				'select',
				'tax_country',
				__( "R&eacute;sidence fiscale *", 'yproject' ),
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
				__( "T&eacute;l&eacute;phone", 'yproject' ),
				WDG_Form_User_Details::$field_group_vote,
				$WDGUser->get_phone_number()
			);
		
		}
		
		// $field_group_extended : A la fin du formulaire étendu, on rajoute le téléphone et la description
		if ( $this->user_details_type == WDG_Form_User_Details::$type_extended ) {
			$this->addField(
				'text',
				'phone_number',
				__( "T&eacute;l&eacute;phone", 'yproject' ),
				WDG_Form_User_Details::$field_group_extended,
				$WDGUser->get_phone_number()
			);
		
			$this->addField(
				'textarea',
				'description',
				__( "Description", 'yproject' ),
				WDG_Form_User_Details::$field_group_extended,
				$WDGUser->get_description()
			);
		
			$this->addField(
				'textarea',
				'contact_if_deceased',
				__( "Personne &agrave; contacter en cas de d&eacute;c&egrave;s", 'yproject' ),
				WDG_Form_User_Details::$field_group_extended,
				$WDGUser->get_contact_if_deceased(),
				__( "Si nous sommes inform&eacute;s de votre d&eacute;c&egrave;s (justifi&eacute; par un avis de d&eacute;c&egrave;s), nous contacterons cette personne pour lui donner l'acc&egrave;s &agrave; votre compte WE DO GOOD. Laissez ce champs vide si vous souhaitez que personne ne soit contact&eacute;. Indiquez pr&eacute;nom, nom, adresse email et num&eacute;ro de t&eacute;l&eacute;phone.", 'yproject' )
			);
		
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
		} else if ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) {

		// Analyse du formulaire
		} else {
			// Informations de base
			$email = $this->getInputText( 'email' );
			if ( !is_email( $email ) ) {
				$error = array(
					'code'		=> 'email',
					'text'		=> __( "Cette adresse e-mail n'est pas valide.", 'yproject' ),
					'element'	=> 'email'
				);
				array_push( $feedback_errors, $error );
			}
			
			$firstname = $this->getInputText( 'firstname' );
			if ( empty( $firstname ) ) {
				$error = array(
					'code'		=> 'firstname',
					'text'		=> __( "Votre pr&eacute;nom n'a pas &eacute;t&eacute; renseign&eacute;.", 'yproject' ),
					'element'	=> 'firstname'
				);
				array_push( $feedback_errors, $error );
			}
			
			$lastname = $this->getInputText( 'lastname' );
			if ( empty( $lastname ) ) {
				$error = array(
					'code'		=> 'lastname',
					'text'		=> __( "Votre nom n'a pas &eacute;t&eacute; renseign&eacute;.", 'yproject' ),
					'element'	=> 'lastname'
				);
				array_push( $feedback_errors, $error );
			}
			
			$user_details_type = $this->getInputText( 'user_details_type' );
			if ( $user_details_type == WDG_Form_User_Details::$type_extended || $user_details_type == WDG_Form_User_Details::$type_complete ) {
				$use_lastname = $this->getInputText( 'use_lastname' );
				$gender = $this->getInputText( 'gender' );
				$birthday = $this->getInputText( 'birthday' );
				$birthdate = DateTime::createFromFormat( 'd/m/Y', $birthday );
				$birthplace = $this->getInputText( 'birthplace' );
				$birthplace_district = $this->getInputText( 'birthplace_district' );
				$birthplace_department = $this->getInputText( 'birthplace_department' );
				$birthplace_country = $this->getInputText( 'birthplace_country' );
				$nationality = $this->getInputText( 'nationality' );
				$address_number = $this->getInputText( 'address_number' );
				$address_number_complement = $this->getInputText( 'address_number_complement' );
				$address = $this->getInputText( 'address' );
				$postal_code = $this->getInputText( 'postal_code' );
				$city = $this->getInputText( 'city' );
				$country = $this->getInputText( 'country' );
				$tax_country = $this->getInputText( 'tax_country' );
			}
			
			if ( $user_details_type == WDG_Form_User_Details::$type_extended || $user_details_type == WDG_Form_User_Details::$type_vote ) {
				$phone_number = $this->getInputText( 'phone_number' );
			}
			
			$description = '';
			$contact_if_deceased = '';
			if ( $user_details_type == WDG_Form_User_Details::$type_extended ) {
				$description = $this->getInputText( 'description' );
				$contact_if_deceased = $this->getInputText( 'contact_if_deceased' );
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
						$description, $contact_if_deceased
					);
					
					$was_registered = $WDGUser->is_lemonway_registered();
					if ( !$was_registered && $WDGUser->can_register_lemonway() ) {
						$WDGUser->register_lemonway();
						// Si il n'était authentifié sur LW et qu'on vient de l'enregistrer, on envoie les documents si certains étaient déjà remplis
						if ( !$was_registered && $WDGUser->is_lemonway_registered() ) {
							$WDGUser->send_kyc();
						}
					}
					
					array_push( $feedback_success, __( "Vos informations ont &eacute;t&eacute; enregistr&eacute;es avec succ&egrave;s." ) );
					
				} else {
					$WDGUser->save_basics( $email, $firstname, $lastname );
					
					if ( $user_details_type == WDG_Form_User_Details::$type_vote ) {
						$WDGUser->save_meta( 'user_mobile_phone', $phone_number );
					}
					
					if ( $user_details_type == WDG_Form_User_Details::$type_basics ) {
						$subscribe_newsletter = $this->getInputChecked( 'subscribe_newsletter' );
						if ( empty( $subscribe_newsletter ) ) {
							try {
								$mailin = new Mailin( 'https://api.sendinblue.com/v2.0', WDG_SENDINBLUE_API_KEY, 5000 );
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
		
		return $buffer;
	}
	
	public function postFormAjax() {
		$buffer = $this->postForm();
		echo json_encode( $buffer );
		exit();
	}
	
}
