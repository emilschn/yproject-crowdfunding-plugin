<?php
class WDG_FiscalDocuments {
	
	private static function save_resume_file( $campaign_id, $fiscal_year, $resume_file_content ) {
		$file_path = dirname( __FILE__ ) . '/../../../files/fiscal-documents/resume_' .$campaign_id. '_' .$fiscal_year. '.txt';
		$dirname = dirname( $file_path );
		if ( !is_dir( $dirname ) ) {
			mkdir( $dirname, 0755, TRUE );
		}
		$file_handle = fopen( $file_path, 'w' );
		fwrite( $file_handle, $resume_file_content );
		fclose( $file_handle );
	}
	
	/**
	 * Génère les fichiers de l'année précédente
	 * @param int $campaign_id
	 */
	public static function generate( $campaign_id, $fiscal_year = 0 ) {
		// On stocke d'un côté un résumé textuel lisible. CSV ?
		$resume_txt = '';
		// On stocke d'un autre côté le fichier txt de déclaration des IFUs
		$ifu_txt = '';
		
		// Campagne analysée
		$campaign = new ATCF_Campaign( $campaign_id );
		$resume_txt .= "Information fiscales\n";
		$resume_txt .= $campaign->get_name(). "\n";
		
		// Récupération de l'année en cours pour trouver l'année dernière
		if ( empty( $fiscal_year ) ) {
			$current_date = new DateTime();
			$fiscal_year = $current_date->format( 'Y' ) - 1;
		}
		$resume_txt .= "Année " .$fiscal_year. "\n\n";
		
		// On récupère la liste des investissements de la campagne
		$investments = $campaign->payments_data();
		foreach ( $investments as $investment_item ) {
			if ( $investment_item[ 'status' ] == 'publish' ) {
				$investment_amount = $investment_item[ 'amount' ];
				$investment_entity_id = $investment_item[ 'user' ];
				$investment_entity = WDGOrganization::is_user_organization( $investment_entity_id ) ? new WDGOrganization( $investment_entity_id ) : new WDGUser( $investment_entity_id );
				
				// On récupère la liste des royalties reçues par investissement jusqu'à l'année précédente
				$investment_user_rois = $investment_entity->get_royalties_by_investment_id( $investment_item[ 'ID' ] );
				$investment_user_rois_amount_total = 0;
				$investment_user_rois_amount_year = 0;
				foreach ( $investment_user_rois as $roi_item ) {
					$date_transfer = new DateTime( $roi_item->date_transfer );
					if ( $date_transfer->format( 'Y' ) <= $fiscal_year ) {
						$investment_user_rois_amount_total += $roi_item->amount;
						if ( $date_transfer->format( 'Y' ) == $fiscal_year ) {
							$investment_user_rois_amount_year += $roi_item->amount;
						}
					}
				}
				
				// Calcul de la somme à déclarer : on ne doit prendre que l'année en cours
				$amount_to_declare = min( $investment_user_rois_amount_year, $investment_user_rois_amount_total - $investment_amount );
				// Si la somme des royalties a dépassé l'investissement initial
				if ( $amount_to_declare > 0 ) {
					$resume_txt .= self::add_resume_entity( $investment_entity_id, $investment_amount, $amount_to_declare );
				}
			}
		}
		
		self::save_resume_file( $campaign_id, $fiscal_year, $resume_txt );
	}
	
	/**
	 * Retourne une chaine qui sera ajoutée au fichier texte de résumé
	 * @param int $investment_entity_id
	 * @param int $investment_amount
	 * @param number $amount_to_declare
	 * @return string
	 */
	private static function add_resume_entity( $investment_entity_id, $investment_amount, $amount_to_declare ) {
		$buffer = "";
		
		$investor_name = "";
		$investor_type = "";
		$investor_fiscal_residence = "";
		if ( WDGOrganization::is_user_organization( $investment_entity_id ) ) {
			$WDGOrganization = new WDGOrganization( $investment_entity_id );
			$investor_name = $WDGOrganization->get_name();
			$investor_type = 'Personne morale';
			$investor_fiscal_residence = $WDGOrganization->get_country();
			
		} else {
			$WDGUser = new WDGUser( $investment_entity_id );
			$investor_name = $WDGUser->get_firstname(). ' ' .$WDGUser->get_lastname();
			$investor_type = 'Personne physique';
			$investor_fiscal_residence = $WDGUser->get_tax_country();
		}
		
		$buffer = "- " .$investor_name. " (" .$investor_type. "). " .$investor_fiscal_residence. "\n";
		$buffer .= ">> Investissement : " .$investment_amount. " €\n";
		$buffer .= ">> Somme à déclarer : " .$amount_to_declare. " €\n\n";
		
		return $buffer;
	}
}
