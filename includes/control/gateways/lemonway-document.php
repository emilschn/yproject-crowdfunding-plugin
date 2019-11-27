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
	public static $document_type_home2 = 17;
	public static $document_type_id3 = 18;
	public static $document_type_home3 = 19;
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
					$this->status = $this->wallet_details->DOCS->DOC->S;
					$this->lw_comment = $this->wallet_details->DOCS->DOC->C;
					$this->error_str = $this->init_error_str();
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
		$contact_us_error = __( "Merci de nous contacter par chat ou par mail sur investir@wedogood.co." );
		switch ( $this->status ) {
			case LemonwayDocument::$document_status_waiting_verification:
				$buffer = __( "Il manque des informations pour permettre la validation.", 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_waiting:
				$buffer = __( "Le document est re&ccedil;u mais pas encore analys&eacute;.", 'yproject' );
				break;
			
			case LemonwayDocument::$document_status_accepted:
				// Pas d'erreur
				break;
			
			case LemonwayDocument::$document_status_refused:
				$buffer = __( "Le document a &eacute;t&eacute; refus&eacute; par notre prestataire.", 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_refused_unreadable:
				$buffer = __( "Le document a &eacute;t&eacute; refus&eacute; par notre prestataire qui l'a jug&eacute; illisible.", 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_refused_expired:
				$buffer = __( "Le document a &eacute;t&eacute; refus&eacute; par notre prestataire car le document a expir&eacute;.", 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_refused_wrong_type:
				$buffer = __( "Le document a &eacute;t&eacute; refus&eacute; par notre prestataire car ce n'est pas le bon document.", 'yproject' );
				$buffer .= ' ' . $contact_us_error;
				break;
			
			case LemonwayDocument::$document_status_refused_wrong_person:
				$buffer = __( "Le document a &eacute;t&eacute; refus&eacute; par notre prestataire car il ne correspond pas au propri&eacute;taire du porte-monnaie.", 'yproject' );
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
			0	=> "Pi&egrave;ce d'identit&eacute;",
			1	=> "Justificatif de domicile",
			2	=> "RIB",
			3	=> "Passeport",
			4	=> "Passeport",
			5	=> "Permis de r&eacute;sidence",
			7	=> "KBIS"
		);
		
		$buffer = __( "Autre", 'yproject' );
		if ( isset( $document_type_str[ $type_id ] ) ) {
			$buffer = __( $document_type_str[ $type_id ], 'yproject' );
		}
		
		return $buffer. ' (' .$type_id. ')';
	}
	
	public static function get_document_status_str_by_status_id( $status_id ) {
		$document_status_str = array(
			0	=> "En attente de v&eacute;rification",
			1	=> "En attente",
			2	=> "Accept&eacute;",
			3	=> "Refus&eacute;",
			4	=> "Illisible",
			5	=> "Expir&eacute;",
			6	=> "Mauvais type",
			7	=> "Mauvaise personne"
		);
		
		$buffer = __( "Autre", 'yproject' );
		if ( isset( $document_status_str[ $status_id ] ) ) {
			$buffer = __( $document_status_str[ $status_id ], 'yproject' );
		}
		
		return $buffer. ' (' .$status_id. ')';
	}

	/**
	 * Construit une chaine avec les infos d'erreurs sur les documents
	 */
	public static function build_error_str_from_wallet_details( $wallet_details ) {
		$buffer = '';

		if ( !empty( $wallet_details ) && !empty( $wallet_details->DOCS ) && !empty( $wallet_details->DOCS->DOC ) ) {
			foreach ( $wallet_details->DOCS->DOC as $document_object ) {
				// Type de document au format écrit pour l'utilisateur
				$document_type = '';
				if ( !empty( $document_object->TYPE ) ) {
					switch ( $document_object->TYPE ) {
						case LemonwayDocument::$document_type_id:
							$document_type = "La pièce d'identité principale";
							break;
						case LemonwayDocument::$document_type_home:
							$document_type = "Le justificatif de domicile";
							break;
						case LemonwayDocument::$document_type_bank:
							// Rien, le RIB ne bloque pas l'authentification
							break;
						case LemonwayDocument::$document_type_idbis:
							$document_type = "La deuxième pièce d'identité";
							break;
						case LemonwayDocument::$document_type_id_back:
							$document_type = "Le verso de la pièce d'identité principale";
							break;
						case LemonwayDocument::$document_type_residence_permit:
							$document_type = "Le permis de résidence";
							break;
						case LemonwayDocument::$document_type_kbis:
							$document_type = "Le KBIS de l'organisation";
							break;
						case LemonwayDocument::$document_type_status:
							$document_type = "Les statuts de l'organisation";
							break;
						case LemonwayDocument::$document_type_idbis_back:
							$document_type = "Le verso de la deuxième pièce d'identité";
							break;
						case LemonwayDocument::$document_type_selfie:
							$document_type = "Le selfie (Type 13)";
							break;
						case LemonwayDocument::$document_type_id2:
							$document_type = "La pièce d'identité de la deuxième personne (Type 16)";
							break;
						case LemonwayDocument::$document_type_home2:
							$document_type = "Le justificatif de domicile de la deuxième personne (Type 17)";
							break;
						case LemonwayDocument::$document_type_id3:
							$document_type = "La pièce d'identité de la troisième personne (Type 18)";
							break;
						case LemonwayDocument::$document_type_home3:
							$document_type = "Le justificatif de domicile de la troisième personne (Type 19)";
							break;
						case LemonwayDocument::$document_type_capital_allocation:
							$document_type = "Le document de répartition du capital (Type 20)";
							break;
					}
				}

				// Statut de document au format écrit pour l'utilisateur
				$document_status = '';
				if ( !empty( $document_object->S ) && $document_object->S > 2 ) {
					switch ( $document_object->S ) {
						case LemonwayDocument::$document_status_refused:
							$document_status = "refusé";
							break;
						case LemonwayDocument::$document_status_refused_unreadable:
							$document_status = "considéré illisible";
							break;
						case LemonwayDocument::$document_status_refused_expired:
							$document_status = "considéré expiré";
							break;
						case LemonwayDocument::$document_status_refused_wrong_type:
							$document_status = "considéré du mauvais type";
							break;
						case LemonwayDocument::$document_status_refused_wrong_person:
							$document_status = "considéré comme lié à une personne qui ne correspond pas";
							break;
					}
				}

				if ( !empty( $document_type ) && !empty( $document_status ) ) {
					$buffer .= $document_type. " bloque l'authentification. Le document a été " .$document_status. ".";
					if ( !empty( $document_object->C ) ) {
						$buffer .= " Commentaire complémentaire de Lemon Way : \"" .$document_object->C. "\"";
					}
					$buffer .= '<br>';
				}
			}
		}

		return $buffer;
	}

	public static function get_list_sorted_by_kyc_type() {
		return array( 
			WDGKYCFile::$type_bank		=> LemonwayDocument::$document_type_bank,
			WDGKYCFile::$type_kbis		=> LemonwayDocument::$document_type_kbis,
			WDGKYCFile::$type_status	=> LemonwayDocument::$document_type_status,
			WDGKYCFile::$type_id		=> LemonwayDocument::$document_type_id,
			WDGKYCFile::$type_home		=> LemonwayDocument::$document_type_home,
			WDGKYCFile::$type_capital_allocation		=> LemonwayDocument::$document_type_capital_allocation,
			WDGKYCFile::$type_id_2		=> LemonwayDocument::$document_type_id2,
			WDGKYCFile::$type_home_2	=> LemonwayDocument::$document_type_home2,
			WDGKYCFile::$type_id_3		=> LemonwayDocument::$document_type_id3,
			WDGKYCFile::$type_home_3	=> LemonwayDocument::$document_type_home3
		);
	}

	public static function get_type_by_kyc_type( $kyc_type ) {
		$documents_type_list = self::get_list_sorted_by_kyc_type();
		return $documents_type_list[ $kyc_type ];
	}
}
