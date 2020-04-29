<?php
class WDG_Form_User_Delete extends WDG_Form {
	
	public static $name = 'user-delete';
	
	public static $field_group_hidden = 'user-delete-hidden';
	
	private $user_id;
	
	public function __construct( $user_id = FALSE ) {
		parent::__construct( self::$name );
		$this->user_id = $user_id;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		$WDGUser = new WDGUser( $this->user_id );
		
		// $field_group_hidden
		$this->addField(
			'hidden',
			'action',
			'',
			self::$field_group_hidden,
			self::$name
		);
		
		$this->addField(
			'hidden',
			'user_id',
			'',
			self::$field_group_hidden,
			$this->user_id
		);
		
		
	}
	
	public function postForm() {
		parent::postForm();
		
		$feedback_success = array();
		$feedback_errors = array();
		
		$user_id = filter_input( INPUT_POST, 'user_id' );
		$WDGUser = new WDGUser( $user_id );
		$WDGUser_current = WDGUser::current();

		// On s'en fout du feedback, ça ne devrait pas arriver
		if ( !is_user_logged_in() ) {
		
		// Un admin peut faire la manip à la place de l'utilisateur, mais c'est tout
		} else if ( ( $WDGUser->get_wpref() != $WDGUser_current->get_wpref() ) && !$WDGUser_current->is_admin() ) {
			
		// Analyse du formulaire
		} else {
            // si l'utilisateur a voté sur une campagne, il faut d'abord supprimer ce vote (avec son accord)
            $campaigns_voted = $WDGUser->get_campaigns_current_voted();
			if ( !empty( $campaigns_voted ) ) {
				$error = array(
					'code'		=> 'campaigns_voted',
					'text'		=> __( "L'utilisateur a vot&eacute; sur des projets", 'yproject' ),
					'element'	=> 'campaigns_voted'
				);
				array_push( $feedback_errors, $error );
			}

			// si l'utilisateur suit une campagne, il faut d'abord aller supprimer son suivi de campagne (avec son accord)
            $campaigns_followed = $WDGUser->get_campaigns_followed();
			if ( !empty( $campaigns_followed ) ) {
				$error = array(
					'code'		=> 'campaigns_followed',
					'text'		=> __( "L'utilisateur suit des projets", 'yproject' ),
					'element'	=> 'campaigns_followed'
				);
				array_push( $feedback_errors, $error );
			}

			// si l'utilisateur a des investissements validés, il ne peut pas être supprimé
            $validated_investments = $WDGUser->get_validated_investments();
			if ( !empty( $validated_investments ) ) {
				$investments_text = '';	
                foreach ( $validated_investments as $campaign_id => $campaign_investments ) {
                    $campaign = atcf_get_campaign( $campaign_id );
					ypcf_debug_log( 'user-delete.php :: $campaign->data->post_title = '.$campaign->data->post_title);
					if( $campaign->data->post_title != '') {
						$amount = 0;
						foreach ($campaign_investments as $investment_id) {
							$payment_amount = edd_get_payment_amount( $investment_id );
							ypcf_debug_log( 'user-delete.php :: $payment_amount = '.$payment_amount);
							if( $payment_amount > 0) {
								$amount += $payment_amount;
							}
						}
						if ($amount > 0) {
							$investments_text .= $amount.' € pour '.$campaign->data->post_title.', ';
						}

					}
				}
				if($investments_text != '') {
					$error = array(
						'code'		=> 'validated_investments',
						'text'		=> __( "L'utilisateur a des investissements valid&eacute;s : ", 'yproject' ).$investments_text,
						'element'	=> 'validated_investments'
					);
					array_push( $feedback_errors, $error );
				}
			}

			// si l'utilisateur a des préinvestissements en attente, il ne peut pas être supprimé
            $has_pending_preinvestments = $WDGUser->has_pending_preinvestments();
			if ( $has_pending_preinvestments ) {
				$error = array(
					'code'		=> 'has_pending_preinvestments',
					'text'		=> __( "L'utilisateur a des pr&eacute;investissements en attente", 'yproject' ),
					'element'	=> 'has_pending_preinvestments'
				);
				array_push( $feedback_errors, $error );
			}

			// si l'utilisateur a des investissements en attente, il ne peut pas être supprimé
            $has_pending_not_validated_investments = $WDGUser->has_pending_not_validated_investments();
			if ( $has_pending_not_validated_investments ) {
				$error = array(
					'code'		=> 'has_pending_not_validated_investments',
					'text'		=> __( "L'utilisateur a des investissements en attente", 'yproject' ),
					'element'	=> 'has_pending_not_validated_investments'
				);
				array_push( $feedback_errors, $error );
			}

			// si l'utilisateur a créé un projet, il ne peut pas être supprimé
			$project_list = WDGUser::get_projects_by_id( $WDGUser->get_wpref(), TRUE );
			if ( !empty( $users_projects ) ) {
				$projects_text = '';	
                foreach ($project_list as $project_id) {
                    if (!empty($project_id)) {
                        $project_campaign = new ATCF_Campaign($project_id);
                        if (isset($project_campaign) && $project_campaign->get_name() != '') {
							$projects_text .= $project_campaign->get_name().', ';
                        }
                    }
                }
				$error = array(
					'code'		=> 'project_list',
					'text'		=> __( "L'utilisateur appartient à des projets : ", 'yproject' ).$projects_text,
					'element'	=> 'project_list'
				);
				array_push( $feedback_errors, $error );
			}
			
			// si l'utilisateur gère une organisation , il ne peut pas être supprimé
			$organizations_list = $WDGUser->get_organizations_list();
			if ( !empty( $organizations_list ) ) {
				$organizations_text = '';
				foreach ( $organizations_list as $organization_item ) {
					$organization_obj = new WDGOrganization( $organization_item->wpref );
					$organizations_text .= $organization_item->name.', ';
				}
				$error = array(
					'code'		=> 'organizations_list',
					'text'		=> __( "L'utilisateur g&egrave;re des organisations : ", 'yproject' ).$organizations_text,
					'element'	=> 'organizations_list'
				);
				array_push( $feedback_errors, $error );
			}

			// si l'utilisateur n'est dans aucun des cas ci-dessus, il peut être supprimé
			if ( empty( $feedback_errors ) ) {
                //Si possible de supprimer, transformer en __deleted202001011212 les données importantes dans l'API et dans le site
                //Préparer une chaîne, qu'on appelle “deleted”, sous cette forme, pour conserver la date exacte de suppression : __deletedAAAAMMJJHHMM
                $deleted_string = '__deleted'.date("YmdHi");
				$id_user = $WDGUser->get_wpref();
				$email_user = $WDGUser->get_email(); 

                /* Aller dans la table wpwdg_users
                    Dans le champ user_activation_key, stocker l'user_email et le display name, juste au cas où, sous cette forme user_email;display_name
					Remplacer user_login, user_pass, user_nicename, user_email, display_name par la chaine “deleted” créée ci-dessus*/
				
				global $wpdb;
				$table_name = $wpdb->prefix . "users";

				$wpdb->update( 
					$table_name, 
					array( 
						'user_login' => $deleted_string,
						'user_pass' => $deleted_string,
						'user_nicename' => $deleted_string,
						'user_email' => $deleted_string,
						'display_name' => $deleted_string,
						'user_activation_key' => $WDGUser->get_email().';'.$WDGUser->get_display_name()
					),
					array(
						'ID' => $WDGUser->get_wpref()
					)
				);
               
                /* Aller dans la table wpwdg_usermeta
                    Faire une recherche par user_id, avec l'ID noté ci-dessus
                    Supprimer toutes les meta sauf les 3 suivantes id_api, lemonway_id, lemonway_status*/
                $metas = get_user_meta( $WDGUser->get_wpref() );		
                foreach ( $metas as $key => $value ) {
                    if ($key != 'id_api' && $key != 'lemonway_id' && $key != 'lemonway_status' ) {
                        delete_user_meta( $WDGUser->get_wpref(), $key );
                    } elseif ($key == 'lemonway_id') {
						// on mémorise l'id lemonway de l'utilisateur pour envoyer un mail au support de lemonway
						$lemonway_id = $value;
					}			
                }

                /*Aller dans la table wdgrestapi1524_entity_user
                    Chercher l'utilisateur en mettant par wpref avec l'ID noté ci-dessus
                    Remplacer les champs email, username par la chaine “deleted”créée ci-dessus
                    Vider les informations de tous les autres champs SAUF id, wpref, signup_date, client_user_id, authentification_mode
				*/
				// on recharge l'utilisateur avec les données wordpress qu'on vient de modifier
				$WDGUserReload = new WDGUser( $WDGUser->get_wpref(), FALSE );
				$WDGUserReload->set_login($deleted_string);
				$WDGUserReload->set_email($deleted_string);
				// on met à jour les données de l'API
				WDGWPREST_Entity_User::update( $WDGUserReload );
                
				// on supprime les fichiers Kyc s'il y en a
				$WDGUser->delete_all_documents();

				if ( $lemonway_id ){
					// on envoie un mail à admin@wedogood.co pour informer de la suppression de l'utilisateur
					NotificationsEmails::send_wedogood_delete_order( $email_user );
				}

				array_push( $feedback_success, __( "Le compte utilisateur a bien &eacute;t&eacute; supprim&eacute;. Pensez &agrave; envoyer une demande de suppression &agrave; LemonWay. ", 'yproject' ) );
			}
			
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		return $buffer;
	}
	
}
