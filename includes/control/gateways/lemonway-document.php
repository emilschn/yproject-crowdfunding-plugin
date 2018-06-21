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
		3: Passeport de la communauté européenne
		4: Passeport en dehors de la communauté européenne 
		5: Carte de séjour
		7 : Kbis
		11 à 20 : documents divers
	 */
	public static $document_type_id = 0;
	public static $document_type_home = 1;
	public static $document_type_bank = 2;
	public static $document_type_passport_euro = 3;
	public static $document_type_passport_not_euro = 4;
	public static $document_type_residence_permit = 5;
	public static $document_type_kbis = 7;
	public static $document_type_status = 11;
	public static $document_type_capital_allocation = 20;
	public static $document_type_id2 = 16;
	public static $document_type_home2 = 17;
	public static $document_type_id3 = 18;
	public static $document_type_home3 = 19;
	
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
		if ( !empty( $wallet_id ) && !empty( $document_type ) ) {
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
		$this->error_str = FALSE;
		if ( !empty( $this->wallet_details->DOCS ) && !empty( $this->wallet_details->DOCS->DOC ) ) {
			foreach( $this->wallet_details->DOCS->DOC as $document_object ) {
				if ( isset( $document_object->TYPE ) && $document_object->TYPE == $this->document_type ) {
					$this->status = $document_object->S;
					$this->error_str = $this->init_error_str();
				} else if ( isset( $document_object->DOCS->DOC->TYPE ) && $document_object->DOCS->DOC->TYPE == $this->document_type ) {
					$this->status = $this->wallet_details->DOCS->DOC->S;
					$this->error_str = $this->init_error_str();
				}
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
	
}
