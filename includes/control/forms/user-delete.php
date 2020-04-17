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
            /*Faire des vérifications :

    a voté ?
    a investi ?
    si oui => projet en cours ? versements terminés ?
    a des documents ?


    est-ce qu'il y a des investissements liés ?
        si oui, et que des versements sont en cours pour cet investissement, il faut discuter avec l'investisseur et réussir à éviter la suppression du compte
    est-ce qu'il y a une évaluation liée ?
        y'a-t-il une intention d'investissement ? l'investisseur a-t-il conscience que son évaluation sera supprimée ? (vérifier en base avec l'id utilisateur dans la table wpwdg_ypcf_project_votes )
    si il suit un projet, il faut supprimer cela
    y'a-t-il des documents KYC qui ont été envoyés ? Il faut les supprimer
*/


            $campaigns_voted = $WDGUser->get_campaigns_current_voted();
            $campaigns_followed = $WDGUser->get_campaigns_followed();
            $has_pending_preinvestments = $WDGUser->has_pending_preinvestments();
            $has_pending_not_validated_investments = $WDGUser->has_pending_not_validated_investments();
            $validated_investments = $WDGUser->get_validated_investments();

			if ( !empty( $campaigns_voted ) ) {
				$error = array(
					'code'		=> 'campaigns_voted',
					'text'		=> __( "L'utilisateur a vot&eacute; sur des projets", 'yproject' ),
					'element'	=> 'campaigns_voted'
				);
				array_push( $feedback_errors, $error );
			}
			if ( !empty( $campaigns_followed ) ) {
				$error = array(
					'code'		=> 'campaigns_followed',
					'text'		=> __( "L'utilisateur suit des projets", 'yproject' ),
					'element'	=> 'campaigns_followed'
				);
				array_push( $feedback_errors, $error );
			}
			if ( !empty( $validated_investments ) ) {
                foreach ( $validated_investments as $campaign_id => $campaign_investments ) {
                    $campaign = atcf_get_campaign( $campaign_id );
                    ypcf_debug_log( 'user-delete.php :: $campaign->data->post_title = '.$campaign->data->post_title);
                    foreach ($campaign_investments as $investment_id) {
                        $payment_amount = edd_get_payment_amount( $investment_id );
                        ypcf_debug_log( 'user-delete.php :: $payment_amount = '.$payment_amount);
                    }
                }
				$error = array(
					'code'		=> 'validated_investments',
					'text'		=> __( "L'utilisateur a des investissements valid&eacute;s", 'yproject' ),
					'element'	=> 'validated_investments'
				);
				array_push( $feedback_errors, $error );
			}
			if ( $has_pending_preinvestments ) {
				$error = array(
					'code'		=> 'has_pending_preinvestments',
					'text'		=> __( "L'utilisateur a des pr&eacute;investissements en attente", 'yproject' ),
					'element'	=> 'has_pending_preinvestments'
				);
				array_push( $feedback_errors, $error );
			}
			if ( $has_pending_not_validated_investments ) {
				$error = array(
					'code'		=> 'has_pending_not_validated_investments',
					'text'		=> __( "L'utilisateur a des investissements en attente", 'yproject' ),
					'element'	=> 'has_pending_not_validated_investments'
				);
				array_push( $feedback_errors, $error );
			}
            

			if ( empty( $feedback_errors ) ) {
                //Si possible de supprimer, transformer en __deleted202001011212 les données importantes dans l'API et dans le site
                //Préparer une chaîne, qu'on appelle “deleted”, sous cette forme, pour conserver la date exacte de suppression : __deletedAAAAMMJJHHMM
                $deleted_string = '__deleted'.date("YmdHi");
				$id_user = $WDGUser->get_wpref();
				$email_user = $WDGUser->get_email(); 

                /* Aller dans la table wpwdg_users
                    Dans le champ user_activation_key, stocker l'user_email et le display name, juste au cas où, sous cette forme user_email;display_name
                    Remplacer user_login, user_pass, user_nicename, user_email, display_name par la chaine “deleted” créée ci-dessus*/
                wp_update_user( array (
					'ID'		=> $WDGUser->get_wpref(),
                    'user_login' => $deleted_string,
                    'user_pass' => $deleted_string,
                    'user_nicename' => $deleted_string,
                    'user_email' => $deleted_string,
                    'display_name' => $deleted_string,
                    'user_activation_key' => $WDGUser->get_email().';'.$WDGUser->get_display_name()
                ) );
               
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
				// on met à jour les données de l'API
				WDGWPREST_Entity_User::update( $WDGUserReload );
                
				// on supprime les fichiers Kyc s'il y en a
				$WDGUser->delete_all_documents();

				// on envoie un mail à support@lemonway.com pour demander la suppression de l'utilisateur
				if ( $lemonway_id ){
					NotificationsEmails::send_lemonway_delete_order( $email_user, $lemonway_id );
					// QUESTION  : en envoyer un à l'utilisateur concerné ? à wedogood ?
				}

				array_push( $feedback_success, __( "Blablablabla" ) );
			}
			
		}
		
		$buffer = array(
			'success'	=> $feedback_success,
			'errors'	=> $feedback_errors
		);
		
		return $buffer;
	}
	
}
