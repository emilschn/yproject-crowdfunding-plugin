<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe de gestion des documents envoyés à Lemon Way
 */
class LemonwayDocument {
	
	/**
	 * Types de documents :
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
	
	public static function get_document_type_str_by_type_id( $type_id ) {
		$document_type_str = array(
			0	=> 'lemonway.document.type.ID',
			1	=> 'lemonway.document.type.PROOF_ADDRESS',
			2	=> 'lemonway.document.type.RIB',
			3	=> 'lemonway.document.type.PASSPORT',
			4	=> 'lemonway.document.type.PASSPORT',
			5	=> 'lemonway.document.type.RESIDENCY_PERMIT',
			7	=> 'lemonway.document.type.KBIS'
		);
		
		$buffer = __( 'common.OTHER', 'yproject' );
		if ( isset( $document_type_str[ $type_id ] ) ) {
			$buffer = __( $document_type_str[ $type_id ], 'yproject' );
		}
		
		return $buffer. ' (' .$type_id. ')';
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

		if ( !empty( $wallet_details ) && !empty( $wallet_details->DOCS ) && !empty( $wallet_details->DOCS->DOC ) ) {
			foreach ( $wallet_details->DOCS->DOC as $document_object ) {
				// Type de document au format écrit pour l'utilisateur
				$document_type = '';
				if ( !empty( $document_object->TYPE ) ) {
					switch ( $document_object->TYPE ) {
						case LemonwayDocument::$document_type_id:
							$document_type = __( 'lemonway.document.details_MAIN_ID', 'yproject' );
							break;
						case LemonwayDocument::$document_type_home:
							$document_type = __( 'lemonway.document.type.PROOF_ADDRESS', 'yproject' );
							break;
						case LemonwayDocument::$document_type_bank:
							// Rien, le RIB ne bloque pas l'authentification
							break;
						case LemonwayDocument::$document_type_idbis:
							$document_type = __( 'lemonway.email.error.SECOND_ID', 'yproject' );
							break;
						case LemonwayDocument::$document_type_id_back:
							$document_type = __( 'lemonway.email.error.MAIN_ID_BACK', 'yproject' );
							break;
						case LemonwayDocument::$document_type_residence_permit:
							$document_type = __( 'lemonway.document.type.RESIDENCY_PERMIT', 'yproject' );
							break;
						case LemonwayDocument::$document_type_kbis:
							$document_type = __( 'lemonway.email.error.ORGA_KBIS', 'yproject' );
							break;
						case LemonwayDocument::$document_type_status:
							$document_type = __( 'lemonway.email.error.ORGA_STATUS', 'yproject' );
							break;
						case LemonwayDocument::$document_type_idbis_back:
							$document_type = __( 'lemonway.email.error.SECOND_ID_BACK', 'yproject' );
							break;
						case LemonwayDocument::$document_type_selfie:
							$document_type = __( 'lemonway.email.error.SELFIE', 'yproject' );
							break;
						case LemonwayDocument::$document_type_id2:
							$document_type = __( 'lemonway.email.error.ID_SECOND_PERSON', 'yproject' );
							break;
						case LemonwayDocument::$document_type_idbis2:
							$document_type = __( 'lemonway.email.error.ID_SECOND_PERSON_2', 'yproject' );
							break;
						case LemonwayDocument::$document_type_id3:
							$document_type = __( 'lemonway.email.error.ID_THIRD_PERSON', 'yproject' );
							break;
						case LemonwayDocument::$document_type_idbis3:
							$document_type = __( 'lemonway.email.error.ID_THIRD_PERSON_2', 'yproject' );
							break;
						case LemonwayDocument::$document_type_capital_allocation:
							$document_type = __( 'lemonway.email.error.CAPITAL_ALLOCATION', 'yproject' );
							break;
					}
				}

				// Statut de document au format écrit pour l'utilisateur
				$document_status = '';
				if ( !empty( $document_object->S ) && $document_object->S > 2 ) {
					switch ( $document_object->S ) {
						case LemonwayDocument::$document_status_refused:
							$document_status = __( 'lemonway.email.error.action.REFUSED', 'yproject' );
							break;
						case LemonwayDocument::$document_status_refused_unreadable:
							$document_status = __( 'lemonway.email.error.action.UNREADABLE', 'yproject' );
							break;
						case LemonwayDocument::$document_status_refused_expired:
							$document_status = __( 'lemonway.email.error.action.EXPIRED', 'yproject' );
							break;
						case LemonwayDocument::$document_status_refused_wrong_type:
							$document_status = __( 'lemonway.email.error.action.WRONG_TYPE', 'yproject' );
							break;
						case LemonwayDocument::$document_status_refused_wrong_person:
							$document_status = __( 'lemonway.email.error.action.WRONG_PERSON', 'yproject' );
							break;
					}
				}

				if ( !empty( $document_type ) && !empty( $document_status ) ) {
					$return_by_document_type[ $document_object->TYPE ] = $document_type. " " .__( 'lemonway.email.error.message.BLOCK_AUTHENTICATION', 'yproject' );
					$return_by_document_type[ $document_object->TYPE ] .= " " .__( 'lemonway.email.error.message.DOCUMENT_HAS_BEEN', 'yproject' ). " " .$document_status. ".";
					if ( !empty( $document_object->C ) ) {
						$return_by_document_type[ $document_object->TYPE ] .= " " .__( 'lemonway.email.error.message.LEMONWAY_COMMENT', 'yproject' ). " \"" .$document_object->C. "\"";
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

	public static function get_list_sorted_by_kyc_type() {
		return array( 
			WDGKYCFile::$type_bank		=> LemonwayDocument::$document_type_bank,
			WDGKYCFile::$type_kbis		=> LemonwayDocument::$document_type_kbis,
			WDGKYCFile::$type_status	=> LemonwayDocument::$document_type_status,
			WDGKYCFile::$type_id		=> LemonwayDocument::$document_type_id,
			WDGKYCFile::$type_idbis		=> LemonwayDocument::$document_type_idbis,
			WDGKYCFile::$type_capital_allocation		=> LemonwayDocument::$document_type_capital_allocation,
			WDGKYCFile::$type_id_2		=> LemonwayDocument::$document_type_id2,
			WDGKYCFile::$type_idbis_2	=> LemonwayDocument::$document_type_idbis2,
			WDGKYCFile::$type_id_3		=> LemonwayDocument::$document_type_id3,
			WDGKYCFile::$type_idbis_3	=> LemonwayDocument::$document_type_idbis3
		);
	}

	public static function get_type_by_kyc_type( $kyc_type ) {
		$documents_type_list = self::get_list_sorted_by_kyc_type();
		return $documents_type_list[ $kyc_type ];
	}

	public static function get_kyc_type_by_lw_type( $input_lw_type ) {
		$documents_type_list = self::get_list_sorted_by_kyc_type();
		foreach ( $documents_type_list as $item_kyc_type => $item_lw_type ) {
			if ( $input_lw_type == $item_lw_type ) {
				return $item_kyc_type;
				break;
			}
		}
		return FALSE;
	}

	public static function has_only_first_doc_validated( $wallet_details ) {
		$has_all_documents_validated = TRUE;
		// Flag permettant de savoir si les documents validés ne concernent que la première pièce d'identité ou le RIB
		// On ne fait cette vérification que si il s'agit de la validation du recto ou verso de la première pièce
		$only_first_document = ( $lemonway_posted_document_type == LemonwayDocument::$document_type_id || $lemonway_posted_document_type == LemonwayDocument::$document_type_id_back );

		// On vérifie si tous les documents sont validés
		if ( !empty( $wallet_details ) && !empty( $wallet_details->DOCS ) && !empty( $wallet_details->DOCS->DOC ) ) {
			foreach ( $wallet_details->DOCS->DOC as $document_object ) {
				if ( !empty( $document_object->S ) && $document_object->S != 2 ) {
					$has_all_documents_validated = FALSE;
				}
				// Si le document est validé et que ce n'est pas la première pièce ou le RIB, on n'envoie pas de notif à ce sujet
				if ( $document_object->S == 2 
							&& $document_object->TYPE != LemonwayDocument::$document_type_id
							&& $document_object->TYPE != LemonwayDocument::$document_type_id_back
							&& $document_object->TYPE != LemonwayDocument::$document_type_bank ) {
					$only_first_document = FALSE;
				}
			}
		}

		return $has_all_documents_validated && $only_first_document;
	}
}
