<?php
class WDG_Form_Adjustement extends WDG_Form {
	
	public static $name = 'project-adjustment';
	
	public static $field_group_hidden = 'adjustment-hidden';
	public static $field_group_adjustment = 'adjustment-data';
	
	private $campaign_id;
	private $adjustment;
	
	public function __construct( $campaign_id = FALSE, $adjustment = FALSE ) {
		parent::__construct( self::$name );
		$this->campaign_id = $campaign_id;
		$this->adjustment = $adjustment;
		$this->initFields();
	}
	
	protected function initFields() {
		parent::initFields();
		
		$campaign = new ATCF_Campaign( $this->campaign_id );
		$adjustment = FALSE;
		if ( !empty( $this->adjustment ) ) {
			$adjustment = new WDGAdjustment( $this->adjustment->id, $this->adjustment );
		}

		
		// Champs masqués : $field_group_hidden
		$this->addField(
			'hidden',
			'campaign_id',
			'',
			self::$field_group_hidden,
			$this->campaign_id
		);
		
		$this->addField(
			'hidden',
			'adjustment_id',
			'',
			self::$field_group_hidden,
			$this->adjustment->id
		);
		
		$this->addField(
			'hidden',
			'roi_percent',
			'',
			self::$field_group_hidden,
			$campaign->roi_percent_remaining()
		);
		
		// Champs affichés : $field_group_adjustment
		$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $this->campaign_id );
		$declaration_list_by_id = array( ''	=> '' );
		foreach ( $declaration_list as $WDGROIDeclaration ) {
			if ( $WDGROIDeclaration->get_status() == WDGROIDeclaration::$status_declaration ) {
				$declaration_list_by_id[ 'declaration-' . $WDGROIDeclaration->id ] = $WDGROIDeclaration->date_due;
			}
		}
		$this->addField(
			'select',
			'declaration',
			__( "Versement au moment duquel l'ajustement s'applique *", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? 'declaration-' .$adjustment->id_declaration : '',
			FALSE,
			$declaration_list_by_id
		);
		
		$adjustment_type_str_by_id = array( '' => '' );
		foreach ( WDGAdjustment::$types_str_by_id as $key => $value ) {
			$adjustment_type_str_by_id[ $key ] = $value;
		}
		$this->addField(
			'select',
			'type',
			__( "Type d'ajustement *", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->type : '',
			FALSE,
			$adjustment_type_str_by_id
		);
		
		$date_today = new DateTime();
		$declaration_list_by_id_past = array();
		foreach ( $declaration_list as $WDGROIDeclaration ) {
			$date_due = new DateTime( $WDGROIDeclaration->date_due );
			if ( $date_today > $date_due && !$WDGROIDeclaration->is_checked_by_adjustments()) {
				$declaration_list_by_id_past[ 'declaration-' . $WDGROIDeclaration->id ] = $WDGROIDeclaration->date_due;
			}
		}
		$declaration_checked_values = array();
		if ( !empty( $adjustment ) ) {
			$temp_declarations_checked = $adjustment->get_declarations_checked();
			foreach ( $temp_declarations_checked as $declaration_item ) {
				array_push( $declaration_checked_values, 'declaration-' . $declaration_item->id );
			}
		}
		$this->addField(
			'select-multiple',
			'declarations_checked',
			__( "Versements &agrave; marquer comme v&eacute;rifi&eacute;s", 'yproject' ),
			self::$field_group_adjustment,
			$declaration_checked_values,
			FALSE,
			$declaration_list_by_id_past
		);
		
		$files = WDGWPREST_Entity_Project::get_files( $campaign->get_api_id(), 'project_document' );
		$documents_by_id = array();
		foreach ( $files as $file_item ) {
			$file_item_metadata = json_decode( $file_item->metadata );
			$documents_by_id[ 'file-' . $file_item->id ] = $file_item_metadata->name;
		}
		$document_values = array();
		if ( !empty( $adjustment ) ) {
			$temp_documents = $adjustment->get_documents();
			foreach ( $temp_documents as $document_item ) {
				array_push( $document_values, 'file-' . $document_item->id );
			}
		}
		$this->addField(
			'select-multiple',
			'documents',
			__( "Documents justificatifs li&eacute;s", 'yproject' ),
			self::$field_group_adjustment,
			$document_values,
			FALSE,
			$documents_by_id
		);
		
		$this->addField(
			'text-money',
			'turnover_checked',
			__( "Montant du CA vérifié", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->turnover_checked : ''
		);
		
		$this->addField(
			'text-money',
			'turnover_difference',
			__( "Diff&eacute;rentiel de CA", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->turnover_difference : ''
		);
		
		$this->addField(
			'text-money',
			'amount',
			__( "Montant de l'ajustement *", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->amount : ''
		);
		
		$message_organization_default = "Nous avons procédé à l'ajustement annuel des comptes en prenant en compte le différentiel de chiffre d'affaires entre les chiffres indiqués tout au long de l'année et ce que vous nous avez transmis, qui représente XXX €. Vous aurez donc un montant supplémentaire à verser à vos investisseurs de XXX €. / Vous aurez donc un solde qui se déduira de votre prochain paiement de XXX €";
		$this->addField(
			'textarea',
			'message_organization',
			__( "Message pour l'entrepreneur", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->message_organization : $message_organization_default
		);
		
		$message_investors_default = "Le versement de ce trimestre a été ajusté par rapport aux comptes annuels de l'entreprise pour l'année XXXX. Chaque année, nous demandons à l'entrepreneur ses comptes, comparons le chiffre annuel déclaré trimestriellement au chiffre d'affaires effectivement réalisé et procédons à un ajustement en cas de décalage. Cela peut être simplement dû à des délais entre les facturations et les encaissements, par exemple.";
		$this->addField(
			'textarea',
			'message_investors',
			__( "Message pour les investisseurs", 'yproject' ),
			self::$field_group_adjustment,
			( !empty( $adjustment ) ) ? $adjustment->message_investors : $message_investors_default
		);
	}
	
	public function postForm() {
		parent::postForm();
		
		if ( !is_user_logged_in() ) {
			$this->addPostError(
				'user-not-logged-in',
				__( "Vous n'&ecirc;tes pas identifi&eacute;.", 'yproject' ),
				'general'
			);
			return;
		}
		
		$WDGUser_current = WDGUser::current();
		if ( !$WDGUser_current->is_admin() ) {
			$this->addPostError(
				'user-cant-edit',
				__( "Vous ne pouvez pas &eacute;diter cet ajustement.", 'yproject' ),
				'general'
			);
			return;
		}
		
		$campaign_id = $this->getInputText( 'campaign_id' );
		if ( empty( $campaign_id ) ) {
			$this->addPostError(
				'campaign_id',
				__( "Erreur de validation du formulaire.", 'yproject' ),
				'general'
			);
		}
		
		
		
		$declaration = $this->getInputText( 'declaration' );
		if ( empty( $declaration ) ) {
			$this->addPostError(
				'declaration',
				__( "Une d&eacute;claration doit &ecirc;tre s&eacute;lectionn&eacute;e.", 'yproject' ),
				'general'
			);
		}
		
		$type = $this->getInputText( 'type' );
		if ( empty( $type ) ) {
			$this->addPostError(
				'type',
				__( "Un type de d&eacute;claration doit &ecirc;tre s&eacute;lectionn&eacute;e.", 'yproject' ),
				'general'
			);
		}
		
		$turnover_checked = $this->getInputTextMoney( 'turnover_checked', FALSE );
		if ( empty( $turnover_checked ) ) {
			$turnover_checked = 0;
		}
		if ( !is_numeric( $turnover_checked ) ) {
			$this->addPostError(
				'turnover_checked',
				__( "Erreur de saisie du CA vérifié.", 'yproject' ),
				'general'
			);
		}
		
		$turnover_difference = $this->getInputTextMoney( 'turnover_difference', FALSE );
		if ( empty( $turnover_difference ) ) {
			$turnover_difference = 0;
		}
		if ( !is_numeric( $turnover_difference ) ) {
			$this->addPostError(
				'turnover_difference',
				__( "Erreur de saisie de la diff&eacute;rence de CA.", 'yproject' ),
				'general'
			);
		}
		
		$amount = $this->getInputTextMoney( 'amount', FALSE );
		if ( !is_numeric( $amount ) ) {
			$this->addPostError(
				'amount',
				__( "Erreur de saisie du montant (ne peut pas &ecirc;tre &eacute;gal &agrave; zero).", 'yproject' ),
				'general'
			);
		}
		
		
		if ( !$this->hasErrors() ) {
			$campaign = new ATCF_Campaign( $campaign_id );
			$message_organization = $this->getInputText( 'message_organization' );
			$message_investors = $this->getInputText( 'message_investors' );
			
			$documents = array();
			$files = WDGWPREST_Entity_Project::get_files( $campaign->get_api_id(), 'project_document' );
			foreach ( $files as $file_item ) {
				if ( $this->getInputChecked( 'file-' . $file_item->id ) ) {
					array_push( $documents, $file_item->id );
				}
			}
			
			$declarations_checked = array();
			$declaration_list = WDGROIDeclaration::get_list_by_campaign_id( $this->campaign_id );
			foreach ( $declaration_list as $WDGROIDeclaration ) {
				if ( $this->getInputChecked( 'declaration-' . $WDGROIDeclaration->id ) ) {
					array_push( $declarations_checked, $WDGROIDeclaration->id );
				}
			}
		
			$adjustment = FALSE;
			if ( !empty( $this->adjustment->id ) ) {
				$adjustment = new WDGAdjustment( $this->adjustment->id );
			} else {
				$adjustment = new WDGAdjustment();
			}
			
			$adjustment->id_api_campaign = $campaign->get_api_id();
			$adjustment->id_declaration = substr( $declaration, strlen( 'declaration-' ) );
			$adjustment->type = $type;
			$adjustment->turnover_checked = $turnover_checked;
			$adjustment->turnover_difference = $turnover_difference;
			$adjustment->amount = $amount;
			$adjustment->documents = $documents;
			$adjustment->declarations_checked = $declarations_checked;
			$adjustment->message_organization = $message_organization;
			$adjustment->message_investors = $message_investors;
			
			if ( !empty( $this->adjustment->id ) ) {
				$adjustment->update();
			} else {
				$adjustment->create();
			}
		}
		
		return !$this->hasErrors();
	}
	
}
