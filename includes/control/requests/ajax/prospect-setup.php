<?php
/**
 * Gestion des appels Ajax en provenance de l'interface prospect
 */
class WDGAjaxActionsProspectSetup {
	/**
	 * Enregistrement d'un test d'interface prospect
	 */
	public static function prospect_setup_save() {
		$guid = filter_input( INPUT_POST, 'guid' );
		$id_user = filter_input( INPUT_POST, 'id_user' );
		$email = filter_input( INPUT_POST, 'email' );
		$status = filter_input( INPUT_POST, 'status' );
		$step = filter_input( INPUT_POST, 'step' );
		$authorization = filter_input( INPUT_POST, 'authorization' );
		$metadata = filter_input( INPUT_POST, 'metadata' );

		$return = array();
		$return[ 'guid' ] = $guid;
		if ( empty( $id_user ) ) {
			$id_user = 0;
		}
		$return[ 'id_user' ] = $id_user;
		$return[ 'save_status' ] = 'failed';

		if ( empty( $guid ) ) {
			$api_result = WDGWPREST_Entity_Project_Draft::create( $id_user, $email, $status, $step, $authorization, $metadata );
			$return[ 'trace_create' ] = print_r($api_result, true);
		} else {
			$api_result = WDGWPREST_Entity_Project_Draft::update( $guid, $id_user, $email, $status, $step, $authorization, $metadata );
			$return[ 'trace_update' ] = print_r($api_result, true);
		}

		if ( !empty( $api_result ) ) {
			$return[ 'guid' ] = $api_result->guid;
			$return[ 'id_user' ] = $api_result->id_user;
			$return[ 'save_status' ] = 'saved';
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Enregistrement des fichiers en provenance d'un test d'interface prospect
	 */
	public static function prospect_setup_save_files() {
		$guid = filter_input( INPUT_POST, 'guid' );
		$return = array();
		$return[ 'data' ] = FALSE;
		$return[ 'error_str' ] = '';
		$return[ 'has_error' ] = '0';

		if ( empty( $guid ) ) {
			$return[ 'error_str' ] = 'empty_guid';
		}

		$api_result = FALSE;
		if ( empty( $return[ 'error_str' ] ) ) {
			$api_result = WDGWPREST_Entity_Project_Draft::get( $guid );
			$return[ 'data' ] = print_r($api_result, true);
		}

		if ( !empty( $api_result ) ) {
			$i = 0;
			while ( isset( $_FILES[ 'file' . $i ] ) ) {
				$file_name = $_FILES[ 'file' . $i ][ 'name' ];
				$file_name_exploded = explode( '.', $file_name );
				$ext = $file_name_exploded[ count( $file_name_exploded ) - 1 ];
				$byte_array = file_get_contents( $_FILES[ 'file' . $i ][ 'tmp_name' ] );
				$file_create_item = WDGWPREST_Entity_File::create( $api_result->id, 'project-draft', 'business', $ext, base64_encode( $byte_array ) );
				$i++;
			}
		}

		if ( !empty( $api_result ) ) {
			$return[ 'guid' ] = $api_result->guid;
			$return[ 'save_status' ] = 'saved';
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Récupération des infos spécifiques d'un test d'interface prospect
	 */
	public static function prospect_setup_get_by_guid() {
		$guid = filter_input( INPUT_POST, 'guid' );
		$return = array();
		$return[ 'data' ] = FALSE;
		$return[ 'error_str' ] = '';
		$return[ 'has_error' ] = '0';

		if ( empty( $guid ) ) {
			$return[ 'error_str' ] = 'empty_guid';
		}

		$api_result = FALSE;
		if ( empty( $return[ 'error_str' ] ) ) {
			$api_result = WDGWPREST_Entity_Project_Draft::get( $guid );
		}

		if ( !empty( $api_result ) ) {
			$return[ 'data' ] = $api_result;
		}

		if ( !empty( $return[ 'error_str' ] ) ) {
			$return[ 'has_error' ] = '1';
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Récupération des droits de l'utilisateur en cours
	 */
	public static function prospect_setup_load_capacities() {
		$WDGUser_current = WDGUser::current();
		$return = array();

		if ( $WDGUser_current->is_admin() ) {
			$return[ 'edit_bundles' ] = '1';
			$return[ 'enable_payment' ] = '1';
			$return[ 'accept_wire_payment' ] = '1';
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Envoi d'un mail avec la liste des tests d'interface prospect liés à un e-mail
	 */
	public static function prospect_setup_send_mail_user_project_drafts() {
		$email = filter_input( INPUT_POST, 'email' );
		$return = array();
		$return[ 'error_str' ] = '';
		$return[ 'has_error' ] = '0';
		$return[ 'email_sent' ] = '0';

		if ( empty( $email ) ) {
			$return[ 'error_str' ] = 'empty_email';
		}
		if ( !is_email( $email ) ) {
			$return[ 'error_str' ] = 'incorrect_email';
		}

		if ( empty( $return[ 'error_str' ] ) ) {
			$api_result = WDGWPREST_Entity_Project_Draft::get_list_by_email( $email );

			if ( empty( $api_result ) ) {
				$return[ 'error_str' ] = 'no_project';
			}

			if ( empty( $return[ 'error_str' ] ) ) {
				$recipient_name = '';
				$project_list = '<ul>';
				foreach ( $api_result as $project_draft_item ) {
					$metadata_decoded = json_decode( $project_draft_item->metadata );
					if ( !empty( $metadata_decoded->user->name ) ) {
						$recipient_name = $metadata_decoded->user->name;
					}
					$project_name = 'Mon projet';
					if ( !empty( $metadata_decoded->organization->name ) ) {
						$project_name = $metadata_decoded->organization->name;
					}
					$project_list .= '<li><a href="' .WDG_Redirect_Engine::override_get_page_url( 'financement/eligibilite' ). '?guid=' .$project_draft_item->guid. '">' .$project_name. '</a></li>';
				}
				$project_list .= '</ul>';
				if ( NotificationsAPI::prospect_setup_draft_list( $email, $recipient_name, $project_list ) ) {
					$return[ 'email_sent' ] = '1';
				}
			}
		}

		if ( !empty( $return[ 'error_str' ] ) ) {
			$return[ 'has_error' ] = '1';
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Envoi d'un mail avec le lien du test d'interface prospect en cours
	 */
	public static function prospect_setup_send_mail_user_draft_started() {
		$guid = filter_input( INPUT_POST, 'guid' );
		$return = array();
		$return[ 'error_str' ] = '';
		$return[ 'has_error' ] = '0';

		if ( empty( $guid ) ) {
			$return[ 'error_str' ] = 'empty_guid';
		}
		if ( empty( $return[ 'error_str' ] ) ) {
			$api_result = WDGWPREST_Entity_Project_Draft::get( $guid );
			if ( empty( $api_result ) ) {
				$return[ 'error_str' ] = 'no_project';
			}

			if ( empty( $return[ 'error_str' ] ) ) {
				$metadata_decoded = json_decode( $api_result->metadata );
				$email = $api_result->email;
				$recipient_name = $metadata_decoded->user->name;
				$organization_name = $metadata_decoded->organization->name;
				$draft_url = WDG_Redirect_Engine::override_get_page_url( 'financement/eligibilite' ) . '?guid=' . $api_result->guid;
				if ( NotificationsAPI::prospect_setup_draft_started( $email, $recipient_name, $organization_name, $draft_url ) ) {
					$return[ 'email_sent' ] = '1';
				}
			}
		}

		if ( !empty( $return[ 'error_str' ] ) ) {
			$return[ 'has_error' ] = '1';
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Envoi d'un mail qui dit que le test d'interface prospect est valide
	 */
	public static function prospect_setup_send_mail_user_draft_finished() {
		$guid = filter_input( INPUT_POST, 'guid' );
		$return = array();
		$return[ 'error_str' ] = '';
		$return[ 'has_error' ] = '0';

		if ( empty( $guid ) ) {
			$return[ 'error_str' ] = 'empty_guid';
		}
		if ( empty( $return[ 'error_str' ] ) ) {
			$api_result = WDGWPREST_Entity_Project_Draft::get( $guid );
			if ( empty( $api_result ) ) {
				$return[ 'error_str' ] = 'no_project';
			}

			if ( empty( $return[ 'error_str' ] ) ) {
				$metadata_decoded = json_decode( $api_result->metadata );
				$email = $api_result->email;
				$recipient_name = $metadata_decoded->user->name;
				$draft_url = WDG_Redirect_Engine::override_get_page_url( 'financement/eligibilite' ) . '?guid=' . $api_result->guid;
				$organization_name = $metadata_decoded->organization->name;
				$amount_needed = $metadata_decoded->project->amountNeeded * 1000;
				$royalties_percent = $metadata_decoded->project->royaltiesAmount;
				$formula = '';
				switch ( $metadata_decoded->project->circlesToCommunicate ) {
					case 'lovemoney':
						$formula = 'Formule Love Money';
						break;
					case 'private':
						$formula = 'Formule Réseau privé';
						break;
					case 'public':
						$formula = 'Formule Crowdfunding';
						break;
				}
				$options = '';
				if ( $metadata_decoded->project->needCommunicationAdvice ) {
					$options = 'Accompagnement Intégral';
				} elseif ( $metadata_decoded->project->circlesToCommunicate != 'lovemoney' && !$metadata_decoded->project->alreadydonecrowdfunding ) {
					$options = 'Accompagnement Intégral';
				} else {
					$options = 'Accompagnement Essentiel';
				}

				if ( NotificationsAPI::prospect_setup_draft_finished( $email, $recipient_name, $draft_url, $organization_name, $amount_needed, $royalties_percent, $formula, $options ) ) {
					$return[ 'email_sent' ] = '1';
				}
			}
		}

		if ( !empty( $return[ 'error_str' ] ) ) {
			$return[ 'has_error' ] = '1';
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Démarrage d'un paiement par carte
	 */
	public static function prospect_setup_ask_card_payment() {
		$guid = filter_input( INPUT_POST, 'guid' );
		$amount = filter_input( INPUT_POST, 'amount' );

		$return = array();
		$return[ 'url_redirect' ] = '';
		$return[ 'error_str' ] = '';
		$return[ 'has_error' ] = '0';

		if ( empty( $guid ) ) {
			$return[ 'error_str' ] = 'empty_guid';
		}
		if ( empty( $amount ) ) {
			$return[ 'error_str' ] = 'empty_amount';
		}

		if ( empty( $return[ 'error_str' ] ) ) {
			$orga_email = 'bonjour@wedogood.co';
			if ( defined( 'PAYMENT_ORGA_EMAIL' ) ) {
				$orga_email = PAYMENT_ORGA_EMAIL;
			}
			$orga_user = get_user_by( 'email', $orga_email );
			$WDGOrganization = new WDGOrganization( $orga_user->ID );

			$token = LemonwayLib::make_token( $guid );

			$url_success = WDG_Redirect_Engine::override_get_page_url( 'financement/eligibilite' ) . '?guid=' .$guid. '&is_success=1';
			$url_error = WDG_Redirect_Engine::override_get_page_url( 'financement/eligibilite' ) . '?guid=' .$guid. '&is_error=1';
			$url_cancel = WDG_Redirect_Engine::override_get_page_url( 'financement/eligibilite' ) . '?guid=' .$guid. '&is_canceled=1';

			$url_redirect = LemonwayLib::ask_payment_webkit( $WDGOrganization->get_lemonway_id(), $amount, 0, $token, $url_success, $url_error, $url_cancel );
			if ( $url_redirect !== FALSE ) {
				$return[ 'url_redirect' ] = $url_redirect;
			} else {
				$return[ 'error_str' ] = 'payment_failed';
			}
		}

		if ( !empty( $return[ 'error_str' ] ) ) {
			$return[ 'has_error' ] = '1';
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Envoi d'un mail avec les infos pour un paiement par virement
	 */
	public static function prospect_setup_send_mail_payment_method_select_wire() {
		$guid = filter_input( INPUT_POST, 'guid' );
		$amount = filter_input( INPUT_POST, 'amount' );

		$return = array();
		$return[ 'error_str' ] = '';
		$return[ 'has_error' ] = '0';

		if ( empty( $guid ) ) {
			$return[ 'error_str' ] = 'empty_guid';
		}
		if ( empty( $return[ 'error_str' ] ) ) {
			$api_result = WDGWPREST_Entity_Project_Draft::get( $guid );
			if ( empty( $api_result ) ) {
				$return[ 'error_str' ] = 'no_project';
			}

			if ( empty( $return[ 'error_str' ] ) ) {
				$email = $api_result->email;
				$recipient_name = $metadata_decoded->user->name;
				$iban = WDG_IBAN;
				$subscription_reference = $metadata_decoded->organization->name;
				if ( NotificationsAPI::prospect_setup_payment_method_select_wire( $email, $recipient_name, $amount, $iban, $subscription_reference ) ) {
					$return[ 'email_sent' ] = '1';
				}
			}
		}

		echo json_encode( $return );
		exit();
	}

	/**
	 * Envoi d'un mail confirmant la réception d'un paiement par virement
	 */
	public static function prospect_setup_send_mail_payment_method_received_wire() {
		$guid = filter_input( INPUT_POST, 'guid' );
		$amount = filter_input( INPUT_POST, 'amount' );

		$return = array();
		$return[ 'error_str' ] = '';
		$return[ 'has_error' ] = '0';

		if ( empty( $guid ) ) {
			$return[ 'error_str' ] = 'empty_guid';
		}
		if ( empty( $return[ 'error_str' ] ) ) {
			$api_result = WDGWPREST_Entity_Project_Draft::get( $guid );
			if ( empty( $api_result ) ) {
				$return[ 'error_str' ] = 'no_project';
			}

			if ( empty( $return[ 'error_str' ] ) ) {
				$metadata_decoded = json_decode( $api_result->metadata );
				$email = $api_result->email;
				$recipient_name = $metadata_decoded->user->name;
				date_default_timezone_set("Europe/Paris");
				$today_datetime = new DateTime();
				if ( NotificationsAPI::prospect_setup_payment_method_received_wire( $email, $recipient_name, $amount, $today_datetime->format( 'd/m/Y H:i' ), $metadata_decoded->organization->name ) ) {
					$return[ 'email_sent' ] = '1';
				}

				$new_status = 'paid';
				$new_step = 'project-complete';
				$new_authorization = 'can-create-db';
				$metadata_decoded->package->paymentDate = $today_datetime->format( 'Y-m-d H:i:s' );
				$metadata_decoded->package->paymentTransferedOnAccount = TRUE;

				$api_result->metadata = json_encode( $metadata_decoded );
				WDGWPREST_Entity_Project_Draft::update( $guid, $api_result->id_user, $api_result->email, $new_status, $new_step, $new_authorization, $api_result->metadata );

				NotificationsZapier::send_prospect_setup_payment_received( $api_result );

				// Ajout test dans 3 jours si TBPP créé
				WDGQueue::add_notifications_dashboard_not_created( $api_result->id );
			}
		}

		echo json_encode( $return );
		exit();
	}
}