<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe de gestion des documents envoyés à Lemon Way
 */
class LemonwayDocument {
	
	/**
	 * ANciens Types de documents :
		0: Carte d'identité de la Communauté Euro 
		1: Justificatif de domicile (fournisseurs d'énergie, tel fixe, feuille d'imposition) 
		2: Scan ou copie d'un RIB 
		3: Deuxième pièce d'identité personne physique (Sur LW : Passeport de la communauté européenne)
		4: Verso carte d'identité (Sur LW : peut aussi être passeport en dehors de la communauté européenne)
		5: Carte de séjour, pas utilisé
		7 : Kbis
		11 : Anciennement statuts entreprise (Permis de conduire sur LW)
		12 : Inversion avec 11 = Verso de la deuxième pièce d'identité (Statuts entreprise sur LW)
		13 : Selfie ?
		14 à 20 : documents divers
	 */
	public static $document_type_id = 0;
	public static $document_type_home = 1;
	public static $document_type_bank = 2;
	public static $document_type_idbis = 3;
	public static $document_type_id_back = 4;
	public static $document_type_residence_permit = 5;
	public static $document_type_kbis = 7;
	public static $document_type_status = 11;
	public static $document_type_idbis_back = 12;
	public static $document_type_selfie = 13;
	public static $document_type_id2 = 16;
	public static $document_type_idbis2 = 17;
	public static $document_type_id3 = 18;
	public static $document_type_idbis3 = 19;
	public static $document_type_capital_allocation = 20;

	/**
	 * Nouveaux types de documents  sur API WDGRESTAPI_Lib_Lemonway
	0	Identity Card (both sides in one file)
	1	Proof of address
	2	Proof of Bank Information (IBAN or other)
	3	Passport (European Community)
	4	Passport (outside the European Community) 
	5	Residence permit (both sides in one file)
	7	Official company registration document (Kbis extract or equivalent)
	8-10 N'existent pas !
	11	Driving licence (both sides in one file)
	12	Status
	13	Selfie
	21	SDD mandate
	Espaces libres : 6, 14-20
	 */
	// public static $document_type_id = 0;
	public static $document_type_proof_address = 1;
	public static $document_type_iban = 2;
	public static $document_type_passport_europe_community = 3;
	public static $document_type_passport_out_europe = 4;
	// public static $document_type_residence_permit = 5;
	public static $document_type_second_id = 6;
	public static $document_type_company_official_document = 7;
	public static $document_type_driving_licence = 11;
	public static $document_type_company_status = 12;
	// public static $document_type_selfie = 13;
	public static $document_type_person2_doc1 = 14;
	public static $document_type_person2_doc2 = 15;
	public static $document_type_person3_doc1 = 16;
	public static $document_type_person3_doc2 = 17;
	public static $document_type_person4_doc1 = 18;
	public static $document_type_person4_doc2 = 19;
	
	/**
	 * Statuts de documents :
		0	Document mis en attente après vérification (en attente d'un autre document, ou en attente d'autre information)
		1	Document reçu (non traité, statut par défaut après upload)
		2	Document vérifié et accepté
		3	Document vérifié mais non accepté
		4	Illisible
		5	Validité du document expiré
		6	Mauvais type
		7	Mauvais titulaire
	 */
	public static $document_status_waiting_verification = 0;
	public static $document_status_waiting = 1;
	public static $document_status_accepted = 2;
	public static $document_status_refused = 3;
	public static $document_status_refused_unreadable = 4;
	public static $document_status_refused_expired = 5;
	public static $document_status_refused_wrong_type = 6;
	public static $document_status_refused_wrong_person = 7;
	

	private static $documents_list;

	private $wallet_id;
	private $document_type;
	private $wallet_details;
	
	private $status;
	private $lw_comment;
	private $error_str;
	
	public function __construct( $wallet_id, $document_type, $wallet_details = FALSE, $wallet_email = FALSE ) {
		$this->wallet_id = $wallet_id;
		$this->document_type = $document_type;
		if ( !empty( $wallet_details ) ) {
			$this->wallet_details = $wallet_details;
			
		} else {
			if ( !empty( $wallet_id ) ) {
				$this->wallet_details = LemonwayLib::wallet_get_details( $wallet_id );
				
			} elseif ( !empty( $wallet_email ) ) {
				$this->wallet_details = LemonwayLib::wallet_get_details( FALSE, $wallet_email );
				
			}
		}
		$this->init();
	}
	

	/**
	 * Récupère le type de doc LW à partir d'une chaine interne
	 * Liste des chaines internes : 'id', 'passport', 'tax', 'welfare', 'family', 'birth', 'driving', 'kbis', 'status', 'capital-allocation', 'person2-doc1', 'person2-doc2', 'person3-doc1', 'person3-doc2'
	 */
	// cf sur API WDGRESTAPI_Lib_Lemonway::get_lw_document_id_from_document_type
	public static function get_lw_document_id_from_document_type ( $document_type, $index ) {
		switch ( $document_type ) {
			case 'id':
				if ( $index == 1 ) {
					return self::$document_type_id;
				} else {
					return self::$document_type_proof_address;
				}
				break;
			case 'passport':
				if ( $index == 1 ) {
					return self::$document_type_passport_europe_community;
				} else {
					return self::$document_type_passport_out_europe;
				}
				break;
			case 'tax':
			case 'welfare':
			case 'family':
			case 'birth':
				if ( $index == 1 ) {
					return self::$document_type_second_id;
				} else {
					return self::$document_type_selfie;
				}
				break;
			case 'driving':
				if ( $index == 1 ) {
					return self::$document_type_driving_licence;
				} else {
					return self::$document_type_selfie;
				}
				break;
			case 'kbis':
				return self::$document_type_company_official_document;
				break;
			case 'status':
				return self::$document_type_company_status;
				break;
			case 'capital-allocation':
				return self::$document_type_capital_allocation;
				break;
			case 'person2-doc1':
				return self::$document_type_person2_doc1;
				break;
			case 'person2-doc2':
				return self::$document_type_person2_doc2;
				break;
			case 'person3-doc1':
				return self::$document_type_person3_doc1;
				break;
			case 'person3-doc2':
				return self::$document_type_person3_doc2;
				break;
			case 'person4-doc1':
				return self::$document_type_person4_doc1;
				break;
			case 'person4-doc2':
				return self::$document_type_person4_doc2;
				break;
		}
		return 20;
	}

	public static function get_by_id_and_type( $wallet_id, $document_type, $wallet_details = FALSE ) {
		$buffer = FALSE;
		if ( !isset( LemonwayDocument::$documents_list ) ) {
			LemonwayDocument::$documents_list = array();
		}
		if ( !empty( $wallet_id ) && isset( $document_type ) ) {
			if ( !empty( LemonwayDocument::$documents_list[ $wallet_id ][ $document_type ] ) ) {
				$buffer = LemonwayDocument::$documents_list[ $wallet_id ][ $document_type ];
			} else {
				$buffer = new LemonwayDocument( $wallet_id, $document_type, $wallet_details );
				LemonwayDocument::$documents_list[ $wallet_id ][ $document_type ] = $buffer;
			}
		}
		
		return $buffer;
	}
	
	public function get_error_str() {
		return $this->error_str;
	}
	
	public function get_status() {
		return $this->status;
	}

	
	private function init() {
		$this->status = FALSE;
		$this->error_str = FALSE;
		if ( !empty( $this->wallet_details->DOCS ) && !empty( $this->wallet_details->DOCS->DOC ) ) {
			foreach( $this->wallet_details->DOCS->DOC as $document_object ) {
				if ( isset( $document_object->TYPE ) && $document_object->TYPE == $this->document_type ) {
					$this->status = $document_object->S;
					$this->lw_comment = $document_object->C;
					$this->error_str = $this->init_error_str();
				} else if ( isset( $document_object->DOCS->DOC->TYPE ) && $document_object->DOCS->DOC->TYPE == $this->document_type ) {
					$this->status = $document_object->DOCS->DOC->S;
					$this->lw_comment = $document_object->DOCS->DOC->C;
					$this->error_str = $this->init_error_str();
				}
				// on arrête de parcourir le tableau quand on a un document du bon type au status validé
				if( !empty( $this->status ) && $this->status == LemonwayDocument::$document_status_accepted ){
					break;
				}
			}
			if ( empty( $this->status ) && $this->wallet_details->DOCS->DOC->TYPE == $this->document_type ) {
				$this->status = $this->wallet_details->DOCS->DOC->S;
				$this->lw_comment = $this->wallet_details->DOCS->DOC->C;
				$this->error_str = $this->init_error_str();
			}
		}
	}
	
	private function init_error_str() {
		$buffer = FALSE;
		$contact_us_error = __( 'lemonway.document.CONTACT', 'yproject' );
		switch ( $this->status ) {
			case LemonwayDocument::$document_status_waiting_verification:
				$buffer = __( 'lemonway.document.MISSING_INFO', 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_waiting:
				$buffer = __( 'lemonway.document.DOCUMENT_RECEIVED_NOT_ANALYZED', 'yproject' );
				break;
			
			case LemonwayDocument::$document_status_accepted:
				// Pas d'erreur
				break;
			
			case LemonwayDocument::$document_status_refused:
				$buffer = __( 'lemonway.document.DOCUMENT_REJECTED', 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_refused_unreadable:
				$buffer = __( 'lemonway.document.DOCUMENT_REJECTED_ILLEGIBLE', 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_refused_expired:
				$buffer = __( 'lemonway.document.DOCUMENT_REJECTED_EXPIRED', 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_refused_wrong_type:
				$buffer = __( 'lemonway.document.DOCUMENT_REJECTED_WRONG', 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_refused_wrong_person:
				$buffer = __( 'lemonway.document.DOCUMENT_REJECTED_WRONG_OWNER', 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
		}
		
		if ( !empty( $this->lw_comment ) ) {
			$buffer .= ' ' . __( "Commentaire compl&eacute;mentaire de Lemon Way :", 'yproject' ) . '"' .$this->lw_comment. '"';
		}
		
		return $buffer;
	}
	
	private static function convert_type_id_to_str( $type_id, $document_id ) {
		// si le type_id correspond à 2 ou 7 ou 20, on est sûrs de ce que c'est (même chose côté site et API)
		switch ($type_id) {
			case LemonwayDocument::$document_type_bank:
				$document_type_str = __('lemonway.document.type.RIB', 'yproject');
				break;
			case LemonwayDocument::$document_type_company_official_document:
				$document_type_str = __('lemonway.document.type.KBIS', 'yproject');
				break;
			case LemonwayDocument::$document_type_capital_allocation:
				$document_type_str = __( 'lemonway.document.type.CAPITAL_ALLOCATION', 'yproject' );
				break;
		}
		//sinon on doit savoir si le fichier est sur le site ou l'api
		if( !isset( $document_type_str ) ){
			$WDGFile = WDGKYCFile::get_by_gateway_id( $document_id );
			if (!$WDGFile->is_api_file) {
				switch ($type_id) {
					// on retourne une chaine du type suivant les anciens types
					case LemonwayDocument::$document_type_id:
						$document_type_str = __( 'lemonway.document.type.CARD_ID', 'yproject' );
						break;
					case LemonwayDocument::$document_type_home:
						$document_type_str = __( 'lemonway.document.type.PROOF_ADDRESS', 'yproject' );
						break;
					case LemonwayDocument::$document_type_idbis:
					case LemonwayDocument::$document_type_passport_europe_community:
						$document_type_str = __( 'lemonway.document.type.SECOND_ID', 'yproject' );
						break;
					case LemonwayDocument::$document_type_id_back:
						$document_type_str = __( 'lemonway.document.type.MAIN_ID_BACK', 'yproject' );
						break;
					case LemonwayDocument::$document_type_residence_permit:
						$document_type_str = __( 'lemonway.document.type.RESIDENCY_PERMIT', 'yproject' );
						break;
					case LemonwayDocument::$document_type_status:
						$document_type_str = __( 'lemonway.document.type.ORGA_STATUS', 'yproject' );
						break;
					case LemonwayDocument::$document_type_idbis_back:
						$document_type_str = __( 'lemonway.document.type.SECOND_ID_BACK', 'yproject' );
						break;
					case LemonwayDocument::$document_type_selfie:
						$document_type_str = __( 'lemonway.document.type.SELFIE', 'yproject' );
						break;
					case LemonwayDocument::$document_type_id2:
						$document_type_str = __( 'lemonway.document.type.ID_SECOND_PERSON', 'yproject' );
						break;
					case LemonwayDocument::$document_type_idbis2:
						$document_type_str = __( 'lemonway.document.type.ID_SECOND_PERSON_2', 'yproject' );
						break;
					case LemonwayDocument::$document_type_id3:
						$document_type_str = __( 'lemonway.document.type.ID_THIRD_PERSON', 'yproject' );
						break;
					case LemonwayDocument::$document_type_idbis3:
						$document_type_str = __( 'lemonway.document.type.ID_THIRD_PERSON_2', 'yproject' );
						break;
				}
			} else {
				switch ( $type_id ) {
					case LemonwayDocument::$document_type_id:
						$document_type_str = __( 'lemonway.document.type.CARD_ID', 'yproject' );
						break;
					case LemonwayDocument::$document_type_proof_address:
						// verso de la carte d'identité
						$document_type_str = __( 'lemonway.document.type.CARD_ID_BACK', 'yproject' );
						break;
					case LemonwayDocument::$document_type_passport_europe_community:
						$document_type_str = __( 'lemonway.document.type.PASSPORT', 'yproject' );
						break;
					case LemonwayDocument::$document_type_passport_out_europe:
						$document_type_str = __( 'lemonway.document.type.PASSPORT_BACK', 'yproject' );
						break;
					// la deuxième pièce d'identité (tax, welfare, family, birth ou driving)
					case LemonwayDocument::$document_type_second_id:
						$document_type_str = __( 'lemonway.document.type.SECOND_ID', 'yproject' );
						break;
					case LemonwayDocument::$document_type_driving_licence:
						$document_type_str = __( 'lemonway.document.type.DRIVING_LICENSE', 'yproject' );
						break;
					case LemonwayDocument::$document_type_selfie:
						$document_type_str = __( 'lemonway.document.type.SECOND_ID_BACK', 'yproject' );
						break;
					case LemonwayDocument::$document_type_status:
						$document_type_str = __( 'lemonway.document.type.ORGA_STATUS', 'yproject' );
						break;
					case LemonwayDocument::$document_type_person2_doc1:
						$document_type_str = __( 'lemonway.document.type.ID_SECOND_PERSON', 'yproject' );
						break;
					case LemonwayDocument::$document_type_person2_doc2:
						$document_type_str = __( 'lemonway.document.type.ID_SECOND_PERSON_2', 'yproject' );
						break;
					case LemonwayDocument::$document_type_person3_doc1:
						$document_type_str = __( 'lemonway.document.type.ID_THIRD_PERSON', 'yproject' );
						break;
					case LemonwayDocument::$document_type_person3_doc2:
						$document_type_str = __( 'lemonway.document.type.ID_THIRD_PERSON_2', 'yproject' );
						break;
					case LemonwayDocument::$document_type_person4_doc1:
						$document_type_str = __( 'lemonway.document.type.ID_FOURTH_PERSON', 'yproject' );
						break;
					case LemonwayDocument::$document_type_person4_doc2:
						$document_type_str = __( 'lemonway.document.type.ID_FOURTH_PERSON_2', 'yproject' );
						break;
				}

			}

		}
		// si on n'a pas trouvé, on reste vague (n'arrive pas normalement)
		if (!isset($document_type_str)) {
			$document_type_str = __( 'common.OTHER', 'yproject' );
		}
		
		return $document_type_str;
	}
	

	public static function get_document_type_str_by_type_id( $type_id, $document_id ) {
		$document_type_str = self::convert_type_id_to_str( $type_id, $document_id );
		return $document_type_str. ' (' .$type_id. ')';
	}
	
	public static function get_document_status_str_by_status_id( $status_id ) {
		$document_status_str = array(
			0	=> 'lemonway.document.status.WAITING',
			1	=> 'lemonway.document.status.PENDING',
			2	=> 'lemonway.document.status.ACCEPTED',
			3	=> 'lemonway.document.status.REJECTED',
			4	=> 'lemonway.document.status.ILLEGIBLE',
			5	=> 'lemonway.document.status.EXPIRED',
			6	=> 'lemonway.document.status.WRONG_TYPE',
			7	=> 'lemonway.document.status.WRONG_OWNER'
		);
		
		$buffer = __( 'common.OTHER', 'yproject' );
		if ( isset( $document_status_str[ $status_id ] ) ) {
			$buffer = __( $document_status_str[ $status_id ], 'yproject' );
		}
		
		return $buffer. ' (' .$status_id. ')';
	}

	/**
	 * Construit une chaine avec les infos d'erreurs sur les documents
	 */
	public static function build_error_str_from_wallet_details( $wallet_details ) {
		// Lemon Way renvoie l'historique de chaque document
		// Pour éviter les doublons, construction d'un tableau indexé par type de document
		$return_by_document_type = array();

		// TODO : A vérifier, est-ce que cela fonctionne bien pour les fichiers sur l'API (ayant le nouveau classement de type) ??? pour avoir le bon message
		if ( !empty( $wallet_details ) && !empty( $wallet_details->DOCS ) && !empty( $wallet_details->DOCS->DOC ) ) {
			foreach ( $wallet_details->DOCS->DOC as $document_object ) {
				// Type de document au format écrit pour l'utilisateur

				$document_type = '';
				if ( !empty( $document_object->TYPE ) ) {					
					$document_type = self::convert_type_id_to_str( $document_object->TYPE, $document_object->ID );
				}
				if( $document_object->TYPE == LemonwayDocument::$document_type_bank ) {
					// Rien, le RIB ne bloque pas l'authentification, on 
					$document_type = '';
				}

				// Statut de document au format écrit pour l'utilisateur
				$document_status = '';
				if ( !empty( $document_object->S ) && $document_object->S > 2 ) {
					switch ( $document_object->S ) {
						case LemonwayDocument::$document_status_refused:
							$document_status = __( 'lemonway.document.type.action.REFUSED', 'yproject' );
							break;
						case LemonwayDocument::$document_status_refused_unreadable:
							$document_status = __( 'lemonway.document.type.action.UNREADABLE', 'yproject' );
							break;
						case LemonwayDocument::$document_status_refused_expired:
							$document_status = __( 'lemonway.document.type.action.EXPIRED', 'yproject' );
							break;
						case LemonwayDocument::$document_status_refused_wrong_type:
							$document_status = __( 'lemonway.document.type.action.WRONG_TYPE', 'yproject' );
							break;
						case LemonwayDocument::$document_status_refused_wrong_person:
							$document_status = __( 'lemonway.document.type.action.WRONG_PERSON', 'yproject' );
							break;
					}
				}

				if ( !empty( $document_type ) && !empty( $document_status ) ) {
					$return_by_document_type[ $document_object->TYPE ] = $document_type. " " .__( 'lemonway.document.type.message.BLOCK_AUTHENTICATION', 'yproject' );
					$return_by_document_type[ $document_object->TYPE ] .= " " .__( 'lemonway.document.type.message.DOCUMENT_HAS_BEEN', 'yproject' ). " " .$document_status. ".";
					if ( !empty( $document_object->C ) ) {
						$return_by_document_type[ $document_object->TYPE ] .= " " .__( 'lemonway.document.type.message.LEMONWAY_COMMENT', 'yproject' ). " \"" .$document_object->C. "\"";
					}
				}
			}
		}

		$buffer = '';
		foreach ( $return_by_document_type as $document_type => $return_str ) {
			$buffer .= $return_str . '<br>';
		}

		return $buffer;
	}

	public static function all_doc_validated_but_wallet_not_authentified( $wallet_details ) {
		$has_all_documents_validated = TRUE;

		// On vérifie si tous les documents sont validés
        if (!empty($wallet_details) && !empty($wallet_details->DOCS) && !empty($wallet_details->DOCS->DOC)) {
            foreach ($wallet_details->DOCS->DOC as $document_object) {
				if ( !empty( $document_object->S ) && $document_object->S != 2 ) {
					$has_all_documents_validated = FALSE;
				}
            }
        }
		// on regarde si le wallet est authentifié
		$wallet_authentified = TRUE;
		if ( $wallet_details->STATUS != '6' ) {
			$wallet_authentified = FALSE;
		}

		return $has_all_documents_validated && !$wallet_authentified;
	}
}
