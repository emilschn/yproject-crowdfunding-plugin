<?php
class WDG_Form_Declaration_Input extends WDG_Form {
	public static $name = 'project-declaration-input';

	public static $field_group_hidden = 'declaration-hidden';
	public static $field_group_declaration = 'declaration-data';

	private $campaign_id;
	private $declaration_id;

	public function __construct($campaign_id = FALSE, $declaration_id = FALSE) {
		parent::__construct( self::$name );
		$this->campaign_id = $campaign_id;
		$this->declaration_id = $declaration_id;
		$this->initFields();
	}

	protected function initFields() {
		$months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

		parent::initFields();
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$campaign_organization = $campaign->get_organization();
		$organization = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
		$roideclaration = new WDGROIDeclaration( $this->declaration_id );

		// Champs masqués : $field_group_hidden
		$this->addField('hidden', 'action', '', self::$field_group_hidden, self::$name);

		// Champs affichés : $field_group_declaration
		$nb_fields = $campaign->get_turnover_per_declaration();
		$date_due = new DateTime( $roideclaration->date_due );
		$date_due->sub( new DateInterval( 'P' .$nb_fields. 'M' ) );
		$declaration_turnover = $roideclaration->get_turnover();

		for ( $index_field = 0; $index_field < $nb_fields; $index_field++ ) {
			$input_declaration_turnover = $this->getInputText( 'turnover_' .$index_field );
			if ( empty( $input_declaration_turnover ) ) {
				if ( isset( $declaration_turnover[ $index_field ] ) ) {
					$input_declaration_turnover = $declaration_turnover[ $index_field ];
				} else {
					$input_declaration_turnover = 0;
				}
			}
			$label = ucfirst( __( $months[ $date_due->format( 'm' ) - 1 ] ) ) . ' *';

			$this->addField('text-money', 'turnover_' .$index_field, $label, self::$field_group_declaration, $input_declaration_turnover, __( "Indiquez le chiffre d'affaires HT", 'yproject' ));

			$date_due->add( new DateInterval( 'P1M' ) );
		}

		$input_declaration_message = $this->getInputText( 'message' );
		$declaration_message = $roideclaration->get_message();
		if ( empty( $input_declaration_message ) && !empty( $declaration_message ) ) {
			if ( $roideclaration->get_is_message_rich() ) {
				$input_declaration_message = $roideclaration->get_message_rich_decoded();
			} else {
				$input_declaration_message = $declaration_message;
			}
		}
		$description = __( "Informez vos investisseurs de l'&eacute;tat d'avancement de votre projet et incitez-les &agrave; &ecirc;tre vos ambassadeurs.", 'yproject' );
		$description .= ' ' . __( "Nous leur transmettrons le message en m&ecirc;me temps que le versement de leurs royalties.", 'yproject' );
		$description .= ' ' . __( "Merci de saisir au moins 140 caractères.", 'yproject' );
		$description .= '<br>';
		$description .= ' ' . __( "<strong>Attention :</strong> ce champ de texte enrichi vous permet de mettre en forme le texte et d'ajouter quelques images d'illustrations. Veillez &agrave; limiter la taille de ces images (500px de largeur maximum) et &agrave; &eacute;viter les autres types de media (vid&eacute;o notamment).", 'yproject' );
		$this->addField('wpeditor', 'message', __( "Informations aux investisseurs", 'yproject' ) . ' *', self::$field_group_declaration, $input_declaration_message, $description);

		$input_declaration_nb_employees = $this->getInputText( 'nb_employees' );
		if ( empty( $input_declaration_nb_employees ) ) {
			$declaration_employees_number = $roideclaration->employees_number;
			if ( !empty( $declaration_employees_number ) ) {
				$input_declaration_nb_employees = $declaration_employees_number;
			} else {
				$input_declaration_nb_employees = $organization->get_employees_count();
			}
		}
		$this->addField('text', 'nb_employees', __( "Nombre de salari&eacute;s &agrave; la fin de ce trimestre", 'yproject' ), self::$field_group_declaration, $input_declaration_nb_employees, __( "Information statistique non-communiqu&eacute;e", 'yproject' ));

		$input_declaration_other_fundings = $this->getInputText( 'other_fundings' );
		$declaration_other_fundings = $roideclaration->get_other_fundings();
		if ( empty( $input_declaration_other_fundings ) && !empty( $declaration_other_fundings ) ) {
			$input_declaration_other_fundings = $declaration_other_fundings;
		}
		$this->addField('text', 'other_fundings', __( "Autres financements re&ccedil;us au cours de ce trimestre", 'yproject' ), self::$field_group_declaration, $input_declaration_other_fundings, __( "Information statistique non-communiqu&eacute;e", 'yproject' ));

		$WDGUser_current = WDGUser::current();
		if ( $WDGUser_current->is_admin() ) {
			$input_declaration_transfer_delay = $this->getInputText( 'transfer_delay' );
			if ( empty( $input_declaration_transfer_delay ) ) {
				$input_declaration_transfer_delay = WDGROIDeclaration::$default_transfer_delay;
			}
			$this->addField('text', 'transfer_delay', __( "Nombre de jours de d&eacute;lais", 'yproject' ), self::$field_group_declaration, $input_declaration_transfer_delay, __( "D&eacute;lais avant versement, une fois l'argent re&ccedil;u", 'yproject' ), array( 'admin_theme' => 1 ));
		}
	}

	public function postForm() {
		parent::postForm();

		if ( !is_user_logged_in() ) {
			$this->addPostError('user-not-logged-in', __( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ), 'general');

			return;
		}

		$campaign = new ATCF_Campaign( $this->campaign_id );
		if ( !$campaign->current_user_can_edit() ) {
			$this->addPostError('user-cant-edit', __( "Vous ne pouvez pas faire cette d&eacute;claration.", 'yproject' ), 'general');

			return;
		}

		$roideclaration = new WDGROIDeclaration( $this->declaration_id );
		$saved_declaration = array();
		$total_turnover = 0;
		$nb_fields = $campaign->get_turnover_per_declaration();
		for ( $index_field = 0; $index_field < $nb_fields; $index_field++ ) {
			$input_declaration_turnover = $this->getInputTextMoney( 'turnover_' .$index_field );
			if ( empty( $input_declaration_turnover ) ) {
				$input_declaration_turnover = 0;
			}

			if ( is_numeric( $input_declaration_turnover ) ) {
				$total_turnover += $input_declaration_turnover;
				array_push( $saved_declaration, $input_declaration_turnover );
			} else {
				$this->addPostError('bad-turnover-amount', __( "Erreur de saisie de montant de chiffre d'affaires.", 'yproject' ), 'general');
			}
		}

		$declaration_message = $this->getInputText( 'message' );
		if ( empty( $declaration_message ) ) {
			$this->addPostError('empty-message', __( "Merci de saisir un petit message pour vos investisseurs.", 'yproject' ), 'general');
		}
		if ( strlen( $declaration_message ) < 140 ) {
			$this->addPostError('empty-message', __( "Le message pour vos investisseurs devrait être un peu plus long.", 'yproject' ), 'general');
		}

		$employees_number = $this->getInputText( 'nb_employees' );
		$employees_number = intval( $employees_number );
		if ( !is_numeric( $employees_number ) || !is_int( $employees_number ) || $employees_number < 0 ) {
			$this->addPostError('bad-employees-number', __( "Erreur de saisie du nombre d'employ&eacute;s.", 'yproject' ), 'general');
		}

		if ( !$this->hasErrors() ) {
			$roideclaration->set_turnover( $saved_declaration );
			$roideclaration->percent_commission_without_tax = $campaign->get_costs_to_organization() / 1.2;
			$roideclaration->amount = round( ( $total_turnover * $campaign->roi_percent_remaining() / 100 ) * 100 ) / 100;
			if ( $roideclaration->get_amount_with_adjustment() == 0 ) {
				NotificationsSlack::turnover_declaration_null( $this->declaration_id, $declaration_message );
				if ( $roideclaration->get_amount_with_commission() == 0 ) {
					$roideclaration->status = WDGROIDeclaration::$status_transfer;
				} else {
					$roideclaration->status = WDGROIDeclaration::$status_payment;
				}

				// Si il reste un montant négatif, on crée un nouvel ajustement à appliquer sur une prochaine déclaration
				if ( $roideclaration->get_amount_royalties() + $roideclaration->get_adjustment_value() < 0 ) {
					$WDGAdjustment = new WDGAdjustment();
					$WDGAdjustment->id_api_campaign = $campaign->get_api_id();

					// Recherche de la prochaine déclaration
					$declaration_list = $campaign->get_roi_declarations();
					if ( !empty( $declaration_list ) ) {
						$has_found_current_in_list = false;
						foreach ( $declaration_list as $declaration_obj ) {
							if ( $has_found_current_in_list ) {
								if ( $declaration_obj[ 'status' ] == WDGROIDeclaration::$status_declaration ) {
									$WDGAdjustment->id_declaration = $declaration_obj[ 'id' ];
									break;
								}
							} elseif ( $declaration_obj[ 'date_due' ] == $roideclaration->date_due ) {
								$has_found_current_in_list = true;
							}
						}
					}

					$WDGAdjustment->amount = $roideclaration->get_amount_royalties() + $roideclaration->get_adjustment_value();
					$WDGAdjustment->type = WDGAdjustment::$type_turnover_difference_remainders;
					$WDGAdjustment->create();
				}
			} else {
				NotificationsSlack::turnover_declaration_not_null( $this->declaration_id, $declaration_message );
				$roideclaration->status = WDGROIDeclaration::$status_payment;

				// Si le montant des royalties fait que ça dépassera le max, on ajoute un ajustement qui fait baisser le montant
				if ( $roideclaration->get_amount_with_adjustment() > $campaign->maximum_profit_amount() - $campaign->get_roi_declarations_total_roi_amount() ) {
					$WDGAdjustment = new WDGAdjustment();
					$WDGAdjustment->id_api_campaign = $campaign->get_api_id();
					$WDGAdjustment->id_declaration = $roideclaration->id;
					$WDGAdjustment->amount = $campaign->maximum_profit_amount() - $campaign->get_roi_declarations_total_roi_amount() - $roideclaration->get_amount_with_adjustment();
					$WDGAdjustment->message_organization = "Afin de ne pas dépasser le maximum à reverser";
					$WDGAdjustment->type = WDGAdjustment::$type_fixed_amount;
					$WDGAdjustment->create();
				}
				/*
				// Si la campagne est dans sa prolongation et que la déclaration dépasse le rendement minimal, on ajoute un ajustement qui fait baisser le montant
				if ( $campaign->is_beyond_funding_duration() ) {
					if ( $roideclaration->get_amount_with_adjustment() > $campaign->minimum_profit_amount() - $campaign->get_roi_declarations_total_roi_amount() ) {
						$WDGAdjustment = new WDGAdjustment();
						$WDGAdjustment->id_api_campaign = $campaign->get_api_id();
						$WDGAdjustment->id_declaration = $roideclaration->id;
						$WDGAdjustment->amount = $campaign->minimum_profit_amount() - $campaign->get_roi_declarations_total_roi_amount() - $roideclaration->get_amount_with_adjustment();
						$WDGAdjustment->message_organization = "Afin de ne pas dépasser le rendement minimal après prolongation";
						$WDGAdjustment->type = WDGAdjustment::$type_fixed_amount;
						$WDGAdjustment->create();
					}
				}
				*/
			}
			// on met à jour date de déclaration
			$date_now = new DateTime();
			$roideclaration->date_declaration = $date_now->format( 'Y-m-d' );

			$roideclaration->employees_number = $employees_number;
			
			$other_fundings = $this->getInputText( 'other_fundings' );
			$roideclaration->set_other_fundings( $other_fundings );

			$WDGUser_current = WDGUser::current();

			$roideclaration->set_transfer_delay( WDGROIDeclaration::$default_transfer_delay );
			if ( $WDGUser_current->is_admin() ) {
				$transfer_delay = $this->getInputText( 'transfer_delay' );
				$roideclaration->set_transfer_delay( $transfer_delay );
			}

			$roideclaration->set_declared_by( $WDGUser_current->get_api_id(), $WDGUser_current->get_firstname(). ' ' .$WDGUser_current->get_lastname(), $WDGUser_current->get_email(), ( $WDGUser_current->is_admin() ? 'admin' : 'team' ) );

			$roideclaration->set_message( $declaration_message );
			$roideclaration->set_is_message_rich( true );

			$roideclaration->save();

			NotificationsSlack::send_declaration_filled( $campaign->get_name(), $roideclaration->get_turnover_total(), $roideclaration->turnover, $roideclaration->get_amount_with_adjustment(), $roideclaration->get_commission_to_pay() );
			NotificationsAsana::send_declaration_filled( $campaign->get_name(), $roideclaration->get_turnover_total(), $roideclaration->get_amount_with_adjustment(), $roideclaration->get_commission_to_pay() );

			// Mise à jour du nombre d'employés de l'organisation en fonction de ce qui a été rempli dans cette déclaration
			$campaign_organization = $campaign->get_organization();
			$organization = new WDGOrganization( $campaign_organization->wpref, $campaign_organization );
			$organization->set_employees_count( $employees_number );
			$organization->save();
		}

		return !$this->hasErrors();
	}
}
