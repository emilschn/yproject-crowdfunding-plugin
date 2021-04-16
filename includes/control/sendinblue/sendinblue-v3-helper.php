<?php
use GuzzleHttp\Client;
/**
 * Classe helper interne pour accès l'API v3 de SendInBlue
 */
class SIBv3Helper {
	/**
	 * Singleton
	 */
	private static $instance;
	private static $last_error;
	/**
	 * Retourne la seule instance chargée du helper pour éviter de charger plusieurs fois les fichiers
	 * @return SIBv3Helper
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			// Initialisation de la classe du singleton
			self::$instance = new SIBv3Helper();

			// Chargement des fichiers nécessaires
			$crowdfunding = ATCF_CrowdFunding::instance();
			$crowdfunding->include_control( 'sendinblue/v3/vendor/autoload' );

			// Initialisation de la configuration de la connexion à SendInBlue avec la clé API
			self::$sib_config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', WDG_SENDINBLUE_API_KEY_V3 );
		}

		return self::$instance;
	}

	/**
	 * Retourne le dernier message d'erreur
	 */
	public static function getLastErrorMessage() {
		return self::$last_error;
	}

	/**
	 * Singletons d'accès à SendInBlue
	 */
	private static $sib_config;
	private static $api_instance_contacts;
	private static $api_instance_transactional_emails;

	/**
	 * Récupération de l'API de contacts en Singleton
	 * @return SendinBlue\Client\Api\ContactsApi
	 */
	private static function getContactsApi() {
		if ( !isset( self::$api_instance_contacts ) ) {
			self::$api_instance_contacts = new SendinBlue\Client\Api\ContactsApi(
				new GuzzleHttp\Client(),
				self::$sib_config
			);
		}
		return self::$api_instance_contacts;
	}

	/**
	 * Récupération de l'API d'e-mails transactionnels en Singleton
	 * @return SendinBlue\Client\Api\TransactionalEmailsApi
	 */
	private static function getTransactionalEmailsApi() {
		if ( !isset( self::$api_instance_transactional_emails ) ) {
			self::$api_instance_transactional_emails = new SendinBlue\Client\Api\TransactionalEmailsApi(
				new GuzzleHttp\Client(),
				self::$sib_config
			);
		}
		return self::$api_instance_transactional_emails;
	}


/**
 * Helpers d'accès à SendInBlue
 */
	/**
	 * Récupère les infos liées à un contact sur SendInBlue
	 * @return SendinBlue\Client\Model\GetExtendedContactDetails
	 */
	public function getContactInfo( $email ) {
		$api_contacts = self::getContactsApi();

		try {
			$result = $api_contacts->getContactInfo( $email );
			return $result;

		} catch (Exception $e) {
			self::$last_error = $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * Ajoute un contact dans une mailing list sur SendInBlue, via son e-mail
	 */
	public function addContactToList( $email, $list_id ) {
		$api_contacts = self::getContactsApi();
		$contactEmails = new \SendinBlue\Client\Model\AddContactToList();
		$contactEmails->setEmails( array( $email ) );

		try {
			$result = $api_contacts->addContactToList( $list_id, $contactEmails );
			return $result;

		} catch (Exception $e) {
			self::$last_error = $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * Supprime un contact dans une mailing list sur SendInBlue, via son e-mail
	 */
	public function removeContactFromList( $email, $list_id ) {
		$api_contacts = self::getContactsApi();
		$contactEmails = new \SendinBlue\Client\Model\RemoveContactFromList();
		$contactEmails->setEmails( array( $email ) );

		try {
			$result = $api_contacts->removeContactFromList( $list_id, $contactEmails );
			return $result;

		} catch (Exception $e) {
			self::$last_error = $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * Met à jour le numéro de téléphone d'un contact
	 */
	public function updateContactPhoneNumber( $email, $phone_number ) {
		$api_contacts = self::getContactsApi();
		$updateContact = new \SendinBlue\Client\Model\UpdateContact();
		$attributes = array( 'SMS' => $phone_number );
		$updateContact->setAttributes( json_decode( json_encode( $attributes ) ) );

		try {
			$result = $api_contacts->updateContact( $email, $updateContact );
			return $result;

		} catch (Exception $e) {
			self::$last_error = $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * Récupère le rapport liés à l'envoi d'un e-mail transactionnel
	 * @return SendinBlue\Client\Model\GetEmailEventReport
	 */
	public function getTransactionalEmailReport( $template_id, $message_id ) {
		$api_transactional_emails = self::getTransactionalEmailsApi();
		$limit = 50;
		$offset = 0;
		$startDate = null;
		$endDate = null;
		$days = null;
		$email = null;
		$event = null;
		$tags = null;
		$messageId = $message_id;
		$templateId = $template_id;
		$sort = 'desc';

		try {
			$result = $api_transactional_emails->getEmailEventReport( $limit, $offset, $startDate, $endDate, $days, $email, $event, $tags, $messageId, $templateId, $sort );
			return $result;

		} catch (Exception $e) {
			self::$last_error = $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * Récupère le rapport d'évènements liés à l'envoi d'un e-mail transactionnel
	 * @return array
	 */
	public function getTransactionalEmailReportEvents( $template_id, $message_id ) {
		$report = $this->getTransactionalEmailReport( $template_id, $message_id );
		if ( !empty( $report ) ) {
			$events = $report->getEvents();
			return $events;
		}
		return FALSE;
	}
}