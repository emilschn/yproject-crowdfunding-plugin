<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

function ypcf_session_start() {
	if (session_id() == '' && !headers_sent()) {
		session_start();
	}
}

/**
 * Détermine si les informations utilisateurs sont complètes (nécessaires pour les porteurs de projet)
 * @param int $user_id
 * @return bool
 */
function ypcf_check_user_is_complete($user_id) {
	$is_complete = true;
	$current_user = get_user_by('id', $user_id);
	$wdg_current_user = new WDGUser($user_id);
	$is_complete = ( $wdg_current_user->get_firstname() != "" ) && ( $wdg_current_user->get_lastname() != "" );
	$is_complete = $is_complete && ( $wdg_current_user->get_birthday_day() != "" ) && ( $wdg_current_user->get_birthday_month() != "" ) && ( $wdg_current_user->get_birthday_year() != "" );
	$is_complete = $is_complete && $wdg_current_user->is_major();
	$is_complete = $is_complete && ( $wdg_current_user->get_nationality() != "" ) && ( $wdg_current_user->get_email() != "" );
	$is_complete = $is_complete && ( $wdg_current_user->get_address() != "" ) && ( $wdg_current_user->get_postal_code() != "" ) && ( $wdg_current_user->get_city() != "" );
	$is_complete = $is_complete && ( $wdg_current_user->get_country() != "" ) && ( $wdg_current_user->get_phone_number() != "" );
	$is_complete = $is_complete && ( $wdg_current_user->get_gender() != "" ) && ( $wdg_current_user->get_birthplace() != "" );

	return $is_complete;
}

/**
 * Après le login, si on venait de l'investissement, il faut y retourner
 * @param type $redirect_to
 * @param type $request
 * @param type $user
 * @return type
 */
function ypcf_login_redirect_invest($redirect_to, $request, $user) {
	$goback_url = ypcf_login_gobackinvest_url();
	if ($goback_url == '') {
		$goback_url = site_url();
	}

	return $goback_url;
}
add_filter( 'login_redirect', 'ypcf_login_redirect_invest', 10, 3 );

/**
 * retourne la page d'investissement si définie en variable de session
 * @return string
 */
function ypcf_login_gobackinvest_url() {
	$redirect_to = '';
	ypcf_session_start();
	if (isset($_SESSION['redirect_current_campaign_id']) && $_SESSION['redirect_current_campaign_id'] != "") {
		$page_invest = get_page_by_path('investir');
		$page_invest_link = get_permalink($page_invest->ID);
		$campaign_id_param = '?campaign_id=';
		$redirect_to = $page_invest_link . $campaign_id_param . $_SESSION['redirect_current_campaign_id'];
		unset($_SESSION['redirect_current_campaign_id']);
	}
	ypcf_debug_log('ypcf_login_gobackinvest_url > redirect to : ' . $redirect_to);

	return $redirect_to;
}

/**
 * Met à jour le statut edd en fonction du statut du paiement sur LW
 * @param int $payment_id
 * @param type $mangopay_contribution
 * @param type $lw_transaction_result
 * @param WDGInvestment $wdginvestment
 * @return string
 */
function ypcf_get_updated_payment_status($payment_id, $mangopay_contribution = FALSE, $lw_transaction_result = FALSE, $wdginvestment = FALSE) {
	$payment_investment = new WDGInvestment( $payment_id );
	$payment_post = get_post($payment_id);
	$downloads = edd_get_payment_meta_downloads($payment_id);
	$download_id = '';
	if (is_array($downloads[0])) {
		$download_id = $downloads[0]["id"];
	} else {
		$download_id = $downloads[0];
	}
	$post_campaign = get_post($download_id);
	$campaign = atcf_get_campaign($post_campaign);

	$init_payment_status = $payment_post->post_status;
	$buffer = $init_payment_status;

	$contract_status = $payment_investment->get_contract_status();
	if ( $contract_status == WDGInvestment::$contract_status_preinvestment_validated || $contract_status == WDGInvestment::$contract_status_investment_refused ) {
		return $buffer;
	}

	if (isset($payment_id) && $payment_id != '' && $init_payment_status != 'failed' && $init_payment_status != 'publish') {
		//On teste d'abord si ça a été refunded
		$refund_transfer_id = get_post_meta($payment_id, 'refund_transfer_id', true);
		if (($init_payment_status == 'refunded') || (isset($refund_transfer_id) && $refund_transfer_id != '')) {
			$buffer = 'refunded';
			$postdata = array(
				'ID'		=> $payment_id,
				'post_status'	=> $buffer,
				'edit_date'	=> current_time( 'mysql' )
			);
			wp_update_post($postdata);
		} else {
			$contribution_id = edd_get_payment_key($payment_id);
			if (strpos($contribution_id, '_wallet_') !== FALSE) {
				$split_contribution_id = explode('_wallet_', $contribution_id);
				$contribution_id = $split_contribution_id[0];
			}

			if ( isset( $contribution_id ) && $contribution_id != '' && $contribution_id != 'check' && strpos( $contribution_id, 'unset_' ) === FALSE ) {
				$update_post = FALSE;
				$is_card_contribution = TRUE;
				$is_only_wallet = FALSE;
				//Si la clé de contribution contient "wire", il s'agissait d'un paiement par virement, il faut découper la clé
				if (strpos($contribution_id, 'wire_') !== FALSE) {
					$is_card_contribution = FALSE;
					$contribution_id = substr($contribution_id, 5);

				//Paiement par wallet uniquement
				} elseif (strpos($contribution_id, 'wallet_') !== FALSE && strpos($contribution_id, '_wallet_') === FALSE) {
					$buffer = 'publish';
					$update_post = TRUE;
					$is_only_wallet = TRUE;

				//On teste une contribution classique
				} else {
					if ($lw_transaction_result === FALSE) {
						$lw_transaction_result = LemonwayLib::get_transaction_by_id( $contribution_id );
					}
					if ($lw_transaction_result) {
						switch ($lw_transaction_result->STATUS) {
							case 3:
								$buffer = 'publish';
								break;
							case 4:
								$buffer = 'failed';
								if ( !empty( $wdginvestment ) ) {
									$wdginvestment->set_status( WDGInvestment::$status_error );
								} else {
									if ( !empty( $payment_investment ) ) {
										$payment_investment->set_status( WDGInvestment::$status_error );
									}
								}
								break;
							case 0:
							default:
								$buffer = 'pending';
								break;
						}
						$update_post = TRUE;
					}
				}

				//Le paiement vient d'être validé
				if ($buffer == 'publish' && $buffer !== $init_payment_status) {
					//Mise à jour du statut du paiement pour être comptabilisé correctement dans le décompte
					$postdata = array(
						'ID'			=> $payment_id,
						'post_status'	=> $buffer,
						'edit_date'		=> current_time( 'mysql' )
					);
					wp_update_post($postdata);

					$amount = edd_get_payment_amount($payment_id);
					$current_user = get_user_by('id', $payment_post->post_author);
					if ( $amount >= WDGInvestmentSignature::$investment_amount_signature_needed_minimum ) {
						//Création du contrat à signer
						$WDGInvestmentSignature = new WDGInvestmentSignature( $payment_id );
						$contract_id = $WDGInvestmentSignature->create_eversign();
						if ( !empty( $contract_id ) ) {
							NotificationsEmails::new_purchase_user_success( $payment_id, $is_card_contribution, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ), $is_only_wallet );
							if ( !empty( $wdginvestment ) && $wdginvestment->has_token() ) {
								global $contract_filename;
								$new_contract_pdf_filename = basename( $contract_filename );
								$new_contract_pdf_url = site_url('/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/') . $new_contract_pdf_filename;
								$wdginvestment->update_contract_url( $new_contract_pdf_url );
							}
						} else {
							global $contract_errors;
							$contract_errors = 'contract_failed';
							NotificationsEmails::new_purchase_user_error_contract( $payment_id, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ), $is_only_wallet );
							NotificationsAsana::new_purchase_admin_error_contract( $payment_id );
						}
					} else {
						$new_contract_pdf_file = getNewPdfToSign($download_id, $payment_id, $current_user->ID);
						NotificationsEmails::new_purchase_user_success_nocontract( $payment_id, $new_contract_pdf_file, $is_card_contribution, ( $campaign->campaign_status() == ATCF_Campaign::$campaign_status_vote ), $is_only_wallet );
						if ( !empty( $wdginvestment ) && $wdginvestment->has_token() ) {
							$new_contract_pdf_filename = basename( $new_contract_pdf_file );
							$new_contract_pdf_url = site_url('/wp-content/plugins/appthemer-crowdfunding/includes/pdf_files/') . $new_contract_pdf_filename;
							$wdginvestment->update_contract_url( $new_contract_pdf_url );
						}
					}

					NotificationsSlack::send_new_investment( $campaign->get_name(), $amount, $current_user->user_email );
					NotificationsEmails::new_purchase_team_members( $payment_id );
					$WDGInvestment = new WDGInvestment( $payment_id );
					$WDGInvestment->save_to_api();

				//Le paiement vient d'échouer
				} else {
					if ($buffer == 'failed' && $buffer !== $init_payment_status) {
						$post_items = get_posts(array(
						'post_type' => 'edd_log',
						'meta_key' => '_edd_log_payment_id',
						'meta_value' => $payment_id
					));
						foreach ($post_items as $post_item) {
							$postdata = array(
							'ID' => $post_item->ID,
							'post_status' => $buffer
						);
							wp_update_post($postdata);
							$WDGInvestment = new WDGInvestment( $post_item->ID );
							$WDGInvestment->save_to_api();
						}

						//Le paiement est validé, mais aucun contrat n'existe
					} else {
						if ($buffer == 'publish') {
							$WDGInvestmentSignature = new WDGInvestmentSignature( $payment_id );
							$contract_id = $WDGInvestmentSignature->check_signature_creation();
						}
					}
				}

				if ($update_post) {
					$postdata = array(
						'ID'		=> $payment_id,
						'post_status'	=> $buffer,
						'edit_date'	=> current_time( 'mysql' )
					);
					wp_update_post($postdata);
					$WDGInvestment = new WDGInvestment( $payment_id );
					$WDGInvestment->save_to_api();

					if (isset($download_id) && !empty($download_id)) {
						do_action('wdg_delete_cache', array(
							'home-projects',
							'projectlist-projects-current'
						));
						$file_cacher = WDG_File_Cacher::current();
						$file_cacher->build_campaign_page_cache( $download_id );
					}
				}
			}
		}
	}

	return $buffer;
}

/**
 *
 */
function ypcf_get_updated_transfer_status($transfer_post) {
	$transfer_post_obj = get_post($transfer_post);

	return $transfer_post_obj->post_status;
}

/**
 * retourne la valeur d'une part
 * @return type
 */
function ypcf_get_part_value() {
	$buffer = 0;
	$current_campaign = atcf_get_current_campaign();
	if ( $current_campaign ) {
		$buffer = $current_campaign->part_value();
	}

	return $buffer;
}

function ypcf_get_max_part_value() {
	$max_value = ypcf_get_max_value_to_invest();
	$part_value = ypcf_get_part_value();
	if ($part_value > 0) {
		$remaining_parts = floor($max_value / $part_value);
	} else {
		$remaining_parts = 0;
	}

	return $remaining_parts;
}

/**
 * retourne la valeur maximale que l'on peut investir
 * @return int
 */
function ypcf_get_max_value_to_invest() {
	$buffer = 0;
	$current_campaign = atcf_get_current_campaign();
	if ( $current_campaign ) {
		//Récupérer la valeur maximale possible : la valeur totale du projet moins le montant déjà atteint
		$buffer = $current_campaign->goal(false) - $current_campaign->current_amount(false, true);
	}

	return $buffer;
}

/**
 * Test pour vérifier que le numéro de téléphone est conforme
 * @param type $mobile_phone
 */
function ypcf_check_user_phone_format($mobile_phone) {
	$buffer = false;

	//Numéro de téléphone type que doit renvoyer ypcf_format_french_phonenumber
	$classic_phone_number = '+33612345678';
	if ($mobile_phone != '' && strlen(ypcf_format_french_phonenumber($mobile_phone)) == strlen($classic_phone_number)) {
		$buffer = true;
	}

	return $buffer;
}

/**
 * Retourne le bon numéro de téléphone
 * @param type $phoneNumber
 * @return type
 */
function ypcf_format_french_phonenumber($phoneNumber) {
	//Supprimer tous les caractères qui ne sont pas des chiffres
	$phoneNumber = preg_replace('/[^0-9]+/', '', $phoneNumber);
	//Garder les 9 derniers chiffres
	$phoneNumber = substr($phoneNumber, -9);
	//On ajoute +33
	$motif = '+33\1\2\3\4\5';
	$phoneNumber = preg_replace('/(\d{1})(\d{2})(\d{2})(\d{2})(\d{2})/', $motif, $phoneNumber);

	return $phoneNumber;
}

function ypcf_fake_sharing_display($text = '', $echo = false) {
	global $post, $wp_current_filter;

	if ( empty( $post ) ) {
//		return $text;

		if ( is_preview() ) {
//		return $text;
		}
	}

	// Don't output flair on excerpts
	if ( in_array( 'get_the_excerpt', (array) $wp_current_filter ) ) {
//		return $text;
	}

	// Don't allow flair to be added to the_content more than once (prevent infinite loops)
	$done = false;
	foreach ( $wp_current_filter as $filter ) {
		if ( 'the_content' == $filter ) {
			if ( $done ) {
				return $text;
			} else {
				$done = true;
			}
		}
	}

	// check whether we are viewing the front page and whether the front page option is checked
	$options = get_option( 'sharing-options' );
	$display_options = $options['global']['show'];

	if ( is_front_page() && ( is_array( $display_options ) && !in_array( 'index', $display_options ) ) ) {
//		return $text;

		if ( is_attachment() && in_array( 'the_excerpt', (array) $wp_current_filter ) ) {
			// Many themes run the_excerpt() conditionally on an attachment page, then run the_content().
		// We only want to output the sharing buttons once.  Let's stick with the_content().
//		return $text;
		}
	}

	$sharer = new Sharing_Service();
	$global = $sharer->get_global_options();

	/*$show = false;
	if ( !is_feed() ) {
		if ( is_singular() && in_array( get_post_type(), $global['show'] ) ) {
			$show = true;
		} elseif ( in_array( 'index', $global['show'] ) && ( is_home() || is_archive() || is_search() ) ) {
			$show = true;
		}
	}

	// Pass through a filter for final say so
	$show = apply_filters( 'sharing_show', $show, $post );*/
	$show = true;

	// Disabled for this post?
	$switched_status = get_post_meta( $post->ID, 'sharing_disabled', false );

	if ( !empty( $switched_status ) ) {
		$show = false;
	}

	// Allow to be used on P2 ajax requests for latest posts.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && 'get_latest_posts' == $_REQUEST['action'] ) {
		$show = true;
	}

	$sharing_content = '';

	if ( $show ) {
		$enabled = apply_filters( 'sharing_enabled', $sharer->get_blog_services() );

		if ( count( $enabled['all'] ) > 0 ) {
			global $post;

			$dir = get_option( 'text_direction' );

			// Wrapper
			$sharing_content .= '<div class="sharedaddy sd-sharing-enabled"><div class="robots-nocontent sd-block sd-social sd-social-' . $global['button_style'] . ' sd-sharing">';
			if ( $global['sharing_label'] != '' ) {
				$sharing_content .= '<h3 class="sd-title">' . $global['sharing_label'] . '</h3>';
			}
			$sharing_content .= '<div class="sd-content"><ul>';

			// Visible items
			$visible = '';
			foreach ( $enabled['visible'] as $id => $service ) {
				// Individual HTML for sharing service
				$visible .= '<li class="share-' . $service->get_class() . '">' . $service->get_display( $post ) . '</li>';
			}

			$parts = array();
			$parts[] = $visible;
			if ( count( $enabled['hidden'] ) > 0 ) {
				if ( count( $enabled['visible'] ) > 0 ) {
					$expand = __( 'More', 'jetpack' );
				} else {
					$expand = __( 'Share', 'jetpack' );
				}
				$parts[] = '<li><a href="#" class="sharing-anchor sd-button share-more"><span>'.$expand.'</span></a></li>';
			}

			if ( $dir == 'rtl' ) {
				$parts = array_reverse( $parts );
			}

			$sharing_content .= implode( '', $parts );
			$sharing_content .= '<li class="share-end"></li></ul>';

			if ( count( $enabled['hidden'] ) > 0 ) {
				$sharing_content .= '<div class="sharing-hidden"><div class="inner" style="display: none;';

				if ( count( $enabled['hidden'] ) == 1 ) {
					$sharing_content .= 'width:150px;';
				}

				$sharing_content .= '">';

				if ( count( $enabled['hidden'] ) == 1 ) {
					$sharing_content .= '<ul style="background-image:none;">';
				} else {
					$sharing_content .= '<ul>';
				}

				$count = 1;
				foreach ( $enabled['hidden'] as $id => $service ) {
					// Individual HTML for sharing service
					$sharing_content .= '<li class="share-'.$service->get_class().'">';
					$sharing_content .= $service->get_display( $post );
					$sharing_content .= '</li>';

					if ( ( $count % 2 ) == 0 ) {
						$sharing_content .= '<li class="share-end"></li>';
					}

					$count++;
				}

				// End of wrapper
				$sharing_content .= '<li class="share-end"></li></ul></div></div>';
			}

			$sharing_content .= '</div></div></div>';

			// Register our JS
//			wp_register_script( 'sharing-js', plugin_dir_url( __FILE__ ).'sharing.js', array( 'jquery' ), '20121205' );
			add_action( 'wp_footer', 'sharing_add_footer' );
		}
	}

	if ( $echo ) {
		echo $text.$sharing_content;
	} else {
		return $text.$sharing_content;
	}
}

function ypcf_get_current_step() {
	ypcf_session_start();
	$buffer = 1;
	$max_part_value = ypcf_get_max_part_value();
	$wdginvestment = WDGInvestment::current();

	$amount_part = FALSE;
	$invest_type = FALSE;

	if ( $wdginvestment->has_token() ) {
		$amount_part = $wdginvestment->get_amount();
		$_SESSION['redirect_current_amount_part'] = $amount_part;
		$invest_type = 'user';
		$_SESSION['redirect_current_invest_type'] = $invest_type;
	} else {
		if (isset($_POST['amount_part'])) {
			$_SESSION['redirect_current_amount_part'] = $_POST['amount_part'];
		}
		if (isset($_SESSION['new_orga_just_created']) && !empty($_SESSION['new_orga_just_created'])) {
			$_SESSION['redirect_current_invest_type'] = $_SESSION['new_orga_just_created'];
		} else {
			if (isset($_POST['invest_type'])) {
				$_SESSION['redirect_current_invest_type'] = $_POST['invest_type'];
			}
		}
	}
	if (isset($_SESSION['redirect_current_amount_part'])) {
		$amount_part = $_SESSION['redirect_current_amount_part'];
	}
	if (isset($_SESSION['redirect_current_invest_type'])) {
		$invest_type = $_SESSION['redirect_current_invest_type'];
	}
	$_SESSION['error_own_organization'] = FALSE;

	$current_campaign = atcf_get_current_campaign();
	$organization = $current_campaign->get_organization();
	$organization_id = $organization->wpref;
	if ( $invest_type == $organization_id ) {
		$_SESSION['error_own_organization'] = '1';
	}

	if ($invest_type != FALSE && $invest_type != $organization_id && $amount_part !== FALSE && is_numeric($amount_part) && ctype_digit($amount_part)
			&& intval($amount_part) == $amount_part && $amount_part >= 1 && $amount_part <= $max_part_value ) {
		$buffer = 2;
	}

	return $buffer;
}

/**
 * retourne une valeur minimale arbitraire à investir
 * @return int
 */
function ypcf_get_min_value_to_invest() {
	return YP_MIN_INVEST_VALUE;
}

/**
 * retourne la somme déjà atteinte
 * @return type
 */
function ypcf_get_current_amount() {
	$buffer = 0;
	$current_campaign = atcf_get_current_campaign();
	if ( $current_campaign ) {
		//Récupérer la valeur maximale possible : la valeur totale du projet moins le montant déjà atteint
		$buffer = $current_campaign->current_amount(false);
	}

	return $buffer;
}