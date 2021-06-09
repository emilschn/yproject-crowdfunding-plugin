<?php
/**
 * Classe qui gère les méthodes communes à user et organization
 */
interface WDGUserInterface {
	public function get_email();
	public function get_firstname();
	public function get_language();
}