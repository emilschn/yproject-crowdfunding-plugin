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
		
		// $field_group_complete : Si on met le formulaire complet, on rajoute nationalité, ville et date de naissance, adresse, genre
		if ( $this->user_details_type == WDG_Form_User_Details::$type_complete || $this->user_details_type == WDG_Form_User_Details::$type_extended ) {
		
			$this->addField(
				'select',
				'gender',
				__( "Vous &ecirc;tes", 'yproject' ),
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
				__( "Date de naissance", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_lemonway_birthdate()
			);
			
			$this->addField(
				'text',
				'birthplace',
				__( "Ville de naissance", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_birthplace()
			);
			
			global $country_list;
			$this->addField(
				'select',
				'nationality',
				__( "Nationalit&eacute;", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_nationality(),
				FALSE,
				$country_list
			);
			
			$this->addField(
				'text',
				'address',
				__( "Adresse", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_address()
			);
			
			$this->addField(
				'text',
				'postal_code',
				__( "Code postal", 'yproject' ),
				WDG_Form_User_Details::$field_group_complete,
				$WDGUser->get_postal_code()
			);
			
			$this->addField(
				'text',
				'city',
				__( "Ville", 'yproject' ),
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
				$gender = $this->getInputText( 'gender' );
				$birthday = $this->getInputText( 'birthday' );
				$birthdate = DateTime::createFromFormat( 'd/m/Y', $birthday );
				$birthplace = $this->getInputText( 'birthplace' );
				$nationality = $this->getInputText( 'nationality' );
				$address = $this->getInputText( 'address' );
				$postal_code = $this->getInputText( 'postal_code' );
				$city = $this->getInputText( 'city' );
				$country = $this->getInputText( 'country' );
			}
			
			if ( $user_details_type == WDG_Form_User_Details::$type_extended || $user_details_type == WDG_Form_User_Details::$type_vote ) {
				$phone_number = $this->getInputText( 'phone_number' );
			}
			
			$description = '';
			if ( $user_details_type == WDG_Form_User_Details::$type_extended ) {
				$description = $this->getInputText( 'description' );
			}
			
			
			if ( empty( $feedback_errors ) ) {
				if ( $user_details_type == WDG_Form_User_Details::$type_complete || $user_details_type == WDG_Form_User_Details::$type_extended ) {
					if ( $user_details_type == WDG_Form_User_Details::$type_complete ) {
						// Quand on n'est pas au format étendu, le téléphone n'est pas transmis.
						// Il faut enregistrer l'existant, pour ne pas le supprimer
						$phone_number = $WDGUser->get_phone_number();
					}
					
					$WDGUser->save_data(
						$email, $gender, $firstname, $lastname,
						$birthdate->format('d'), $birthdate->format('m'), $birthdate->format('Y'),
						$birthplace, $nationality, $address, $postal_code, $city, $country, $phone_number, $description
					);
					
					$was_registered = $WDGUser->is_lemonway_registered();
					if ( $WDGUser->can_register_lemonway() ) {
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
