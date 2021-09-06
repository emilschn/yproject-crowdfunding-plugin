<?php

use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
/**
 * Classe helper interne pour accès à la v5 de HTML2PDF
 * documentation : https://github.com/spipu/html2pdf/blob/master/doc/README.md
 */
class HTML2PDFv5Helper {
	/**
	 * Singleton
	 */
	private static $instance;
	private static $last_error;
	/**
	 * Retourne la seule instance chargée du helper pour éviter de charger plusieurs fois les fichiers
	 * @return HTML2PDFv5Helper
	 */
	public static function instance() {
		if ( !isset( self::$instance ) ) {
			// Initialisation de la classe du singleton
			self::$instance = new HTML2PDFv5Helper();

			// Chargement des fichiers nécessaires
			$crowdfunding = ATCF_CrowdFunding::instance();
			$crowdfunding->include_control( 'html2pdf/v5/vendor/autoload' );

		}

		return self::$instance;
	}

	/**
	 * Retourne le dernier message d'erreur
	 * @return string
	 */
	public static function getLastErrorMessage() {
		return self::$last_error;
	}

	/**
	 * Singletons d'accès à Html2Pdf
	 */
	private static $api_Html2Pdf;

	/**
	 * Récupération de l'API Html2Pdf en Singleton
	 * @return Html2Pdf
	 */
	public static function getHtml2PdfApi() {
		if ( !isset( self::$api_Html2Pdf ) ) {
			// TODO : pouvoir modifier les propriétés d'un appel à un autre
			// TODO : pouvoir choisir le langage
			self::$api_Html2Pdf= new Html2Pdf('P', 'A4', 'fr', true, 'UTF-8', array(12, 5, 15, 8));
		}

		return self::$api_Html2Pdf;
	}

	/**
	 * Helpers d'accès à Html2Pdf
	 */

	/**
	 * Ecrit un Pdf contenant $html_content sur le serveur  à l'adresse $filepath
	 * https://github.com/spipu/html2pdf/blob/master/doc/output.md
	 * 
     * @param string $html_content The content
     * @param string $filepath The path and name of the file when saved.
	 * @return Boolean
	 */
	public function writePDF($html_content, $filepath) {

		try {
			$html2pdf = self::getHtml2PdfApi();
			$html2pdf->WriteHTML( urldecode( $html_content ) );
			$html2pdf->Output( $filepath, 'F' );
			return TRUE;
		} catch (Html2PdfException $e) {
			$html2pdf->clean();		
			$formatter = new ExceptionFormatter($e);
			self::$last_error = $formatter->getHtmlMessage();
			return FALSE;
		}
	}
}