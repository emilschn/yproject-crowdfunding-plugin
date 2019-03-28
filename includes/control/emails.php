<?php
/**
 * Emails
 *
 * Handle a bit of extra email info.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trigger Purchase Receipt
 *
 * Causes the purchase receipt to be emailed when initially pledged.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @param int $payment_id The ID of the payment
 * @param string $new_status The status we are changing to
 * @param string $old_status The old status we are changing from
 * @return void
 */
function atcf_trigger_pending_purchase_receipt( $payment_id, $new_status, $old_status ) {
	// Make sure we don't send a purchase receipt while editing a payment
	if ( isset( $_POST[ 'edd-action' ] ) && $_POST[ 'edd-action' ] == 'edit_payment' )
		return;

	// Check if the payment was already set to complete
	if ( $old_status == 'publish' || $old_status == 'complete' )
		return; // Make sure that payments are only completed once

	// Make sure the receipt is only sent when new status is preapproval
	if ( $new_status != 'preapproval' )
		return;

	// Send email with secure download link
	atcf_email_pending_purchase_receipt( $payment_id );
}
add_action( 'edd_update_payment_status', 'edd_trigger_purchase_receipt', 10, 3 );

/**
 * Build the purchase email.
 *
 * Figure out who to send to, who it's from, etc.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @param int $payment_id The ID of the payment
 * @param boolean $admin_notice Alert admins, or not
 * @return void
 */
function atcf_email_pending_purchase_receipt( $payment_id, $admin_notice = true ) {
	global $edd_options;

	$payment_data = edd_get_payment_meta( $payment_id );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );

	if ( isset( $user_info['id'] ) && $user_info['id'] > 0 ) {
		$user_data = get_userdata($user_info['id']);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $user_info['email'];
	}

	$message  = edd_get_email_body_header();
	$message .= atcf_get_email_body_content( $payment_id, $payment_data );
	$message .= edd_get_email_body_footer();

	$from_name  = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo('name');
	$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option('admin_email');

	$subject = apply_filters( 'atcf_pending_purchase_subject', __( 'Your pledge has been received', 'atcf' ), $payment_id );
	$subject = edd_email_template_tags( $subject, $payment_data, $payment_id );

	$headers  = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";

	// Allow add-ons to add file attachments
	$attachments = apply_filters( 'atcf_pending_receipt_attachments', array(), $payment_id, $payment_data );

	wp_mail( $payment_data['email'], $subject, $message, $headers, $attachments );

	if ( $admin_notice ) {
		do_action( 'edd_admin_pending_purchase_notice', $payment_id, $payment_data );
	}
}

/**
 * Get the actual pending email body content. Default text, can be filtered, and will
 * use all template tags that EDD supports.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @param int $payment_id The ID of the payment
 * @param array $payment_data The relevant payment data
 * @return string $email_body The actual email body
 */
function atcf_get_email_body_content( $payment_id = 0, $payment_data = array() ) {
	global $edd_options;

	$downloads = edd_get_payment_meta_downloads( $payment_id );
	$campaign  = '';

	if ( $downloads ) {
		foreach ( $downloads as $download ) {
			$id       = isset( $payment_data[ 'cart_details' ] ) ? $download[ 'id' ] : $download;
			$campaign = get_the_title( $id );
			
			continue;
		}
	}

	$default_email_body = __( 'Dear {name}', 'atcf' ) . "\n\n";
	$default_email_body .= sprintf( __( 'Thank you for your pledging to support %1$s. This email is just to let you know your pledge was processed without a hitch! You will only be charged your pledge amount if the %2$s receives 100% funding.', 'atcf' ), $campaign, strtolower( edd_get_label_singular() ) ) . "\n\n";
	$default_email_body .= "{sitename}";

	$email = $default_email_body;

	$email_body = edd_email_template_tags( $email, $payment_data, $payment_id );

	return apply_filters( 'atcf_pending_purchase_receipt', $email_body, $payment_id, $payment_data );
}


class WDGEmails {
	
	public static function auto_notifications( $campaign_id, $mail_type, $input_testimony_in, $input_image_url, $input_image_description, $input_send_option ) {
		$campaign = new ATCF_Campaign( $campaign_id );
		$project_name = $campaign->get_name();
		$project_url = get_permalink( $campaign->ID );
		$project_api_id = $campaign->get_api_id();
		// Gestion des sauts de ligne
		$input_testimony = nl2br( $input_testimony_in );

		// Si on teste, on biaise les données et on arrête de suite
		if ( strpos( strtolower( $input_send_option ), 'test' ) !== FALSE ) {
			$recipient_email = 'communication@wedogood.co';
			$recipient_name = 'Anna';
			$intention_amount = 100;
			switch ( $mail_type ) {
				case 'preinvestment':
					NotificationsAPI::confirm_vote_invest_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_vote_invest_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					break;
				case 'prelaunch':
					NotificationsAPI::confirm_prelaunch_invest_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_prelaunch_invest_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_prelaunch_invest_follow( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					break;
				case 'investment-30':
					NotificationsAPI::confirm_investment_invest30_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_investment_invest30_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					break;
				case 'investment-100':
					NotificationsAPI::confirm_investment_invest100_invested( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_investment_invest100_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					NotificationsAPI::confirm_investment_invest100_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					break;
			}
			$url_return = wp_get_referer() . "#contacts";
			wp_redirect( $url_return );
			die();
		}

		$user_list_by_id = array();

		// Récupération des followers
		$followers_list_by_id = array();
		$list_user_followers = $campaign->get_followers();
		foreach ( $list_user_followers as $db_item_follower_user_id ) {
			$followers_list_by_id[ $db_item_follower_user_id ] = 1;
		}

		// Récupération des investisseurs
		$investors_list_by_id = array();
		$list_user_investors = $campaign->payments_data();
		foreach ( $list_user_investors as $item_investment ) {
			$investors_list_by_id[ $item_investment[ 'user' ] ] = 1;
		}

		// On parcourt la liste des évaluateurs
		$list_user_voters = $campaign->get_voters();
		foreach ( $list_user_voters as $db_item_vote ) {
					// On ne prend que des notes d'au moins 3
			if (	$db_item_vote->rate_project >= 3
					// On ne prend que ceux qui suivent toujours le projet
					&& isset( $followers_list_by_id[ $db_item_vote->user_id ] )
					// On ne prend que ceux qui n'ont pas investi
					&& !isset( $investors_list_by_id[ $db_item_vote->user_id ] ) ) {

				if ( !isset( $user_list_by_id[ $db_item_vote->user_id ] ) ) {
					$user_list_by_id[ $db_item_vote->user_id ] = array();
				}
				$user_list_by_id[ $db_item_vote->user_id ][ 'vote_amount' ] = $db_item_vote->invest_sum;
			}
		}

		// Si le mail est celui de pré-lancement
		if ( $mail_type == 'prelaunch' ) {
			// On reprend les followers qui n'ont pas évalué et qui n'ont pas fait d'action d'investissement
			foreach ( $list_user_followers as $db_item_follower_user_id ) {
				if (	!isset( $user_list_by_id[ $db_item_follower_user_id ] )
						&& !isset( $investors_list_by_id[ $db_item_follower_user_id ] ) ) {

					$user_list_by_id[ $db_item_follower_user_id ] = array();
					$user_list_by_id[ $db_item_follower_user_id ][ 'vote_amount' ] = 'follow';
				}
			}
		}

		foreach ( $user_list_by_id as $user_id => $vote_data ) {
			if ( WDGOrganization::is_user_organization( $user_id ) ) {
				$WDGOrganization = new WDGOrganization( $user_id );
				$recipient_email = $WDGOrganization->get_email();
				$recipient_name = $WDGOrganization->get_name();
			} else {
				$WDGUser = new WDGUser( $user_id );
				$recipient_email = $WDGUser->get_email();
				$recipient_name = $WDGUser->get_firstname();
			}

			$intention_amount = $vote_data[ 'vote_amount' ];

			// Pour les restants, on envoie un template différent selon si ils ont mis une intention ou non.
			switch ( $mail_type ) {
				case 'preinvestment':
					if ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_vote_invest_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );

					} else {
						NotificationsAPI::confirm_vote_invest_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					}
					break;
					
				case 'prelaunch':
					if ( $intention_amount == 'follow' ) {
						NotificationsAPI::confirm_prelaunch_invest_follow( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );

					} elseif ( $intention_amount > 0 ) {
						NotificationsAPI::confirm_prelaunch_invest_intention( $recipient_email, $recipient_name, $intention_amount, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );

					} else {
						NotificationsAPI::confirm_prelaunch_invest_no_intention( $recipient_email, $recipient_name, $project_name, $project_url, $input_testimony, $input_image_url, $input_image_description, $project_api_id );
					}
					break;
			}
		}
	}
	
}