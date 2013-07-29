<?php


/**
 * Vote Shortcode.
 *
 * [appthemer_crowdfunding_update] creates a upadate form.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Base page/form. All fields are loaded through an action,
 * so the form can be extended for ever, fields can be removed, added, etc.
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return $form
 */

 
 function atcf_shortcode_update( $editing = false ) {
	global $edd_options;

	$crowdfunding = crowdfunding();
	$campaign     = null;

	ob_start();

	if ( $editing ) {
		global $post;

		$campaign = atcf_get_campaign( $post );
	} else {
		wp_enqueue_script( 'jquery-validation', EDD_PLUGIN_URL . 'assets/js/jquery.validate.min.js');
		wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery', 'jquery-validation' ) );

		wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
			'oneReward' => __( 'At least one reward is required.', 'atcf' )
		) );
	}
?>
	<?php do_action( 'atcf_shortcode_update_before', $editing, $campaign ); ?>
	<form action="" method="post" class="atcf-update-campaign" enctype="multipart/form-data">
		<?php do_action( 'atcf_shortcode_update_fields', $editing, $campaign ); ?>

		<p class="atcf-update-campaign-update">
			<input type="submit" value="<?php echo $editing ? sprintf( _x( 'Update %s', 'edit "campaign"', 'atcf' ), edd_get_label_singular() ) : sprintf( _x( 'Update %s', 'submit "campaign"', 'atcf' ), edd_get_label_singular() ); ?>">
			<input type="hidden" name="action" value="atcf-campaign-<?php echo $editing ? 'edit' : 'submit'; ?>" />
			<?php wp_nonce_field( 'atcf-campaign-' . ( $editing ? 'edit' : 'submit' ) ); ?>
		</p>
		
	</form>
	<?php do_action( 'atcf_shortcode_update_after', $editing, $campaign ); ?>
	
<?php
	$form = ob_get_clean();

	return $form;
}

add_shortcode( 'appthemer_crowdfunding_update', 'atcf_shortcode_update' );

 

 function atcf_shortcode_update_field_title( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<h3 class="atcf-update-section campaign-information"><?php _e( 'Campaign Information', 'atcf' ); ?></h3>

	<p class="atcf-update-title">
		<label for="title"><?php _e( 'Name', 'atcf' ); ?></label>
		<output type="text" name="title" id="title" placeholder="<?php esc_attr_e( 'Title', 'atcf' ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_title', 10, 2 );




/**
 * Campaign Category
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_category( $editing, $campaign ) {
	if ( $editing ) {
		$categories = get_the_terms( $campaign->ID, 'download_category' );

		$selected = 0;

		if ( ! $categories )
			$categories = array();

		foreach( $categories as $category ) {
			$selected = $category->term_id;
			break;
		}
	}
?>
	<p class="atcf-update-campaign-category">
		<label for="category"><?php _e( 'Category', 'atcf' ); ?></label>			
		<?php 
			wp_dropdown_categories( array( 
				'orderby'    => 'name', 
				'hide_empty' => 0,
				'taxonomy'   => 'download_category',
				'selected'   => $editing ? $selected : 0
			) );
		?>
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_category', 10, 2 );


/**
 * Campaign Images
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_images( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<p class="atcf-update-campaign-images">
		<label for="excerpt"><?php _e( 'Preview Image', 'atcf' ); ?></label>
		<input type="file" name="image" id="image" />
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_images', 10, 2 );

/**
 * Campaign Video
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_video( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<p class="atcf-update-campaign-video">
		<label for="length"><?php _e( 'Video URL', 'atcf' ); ?></label>
		<input type="text" name="video" id="video">
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_video', 10, 2 );



/**
 * Campaign summary
 * Ce champs représente le Resumé
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_summary( $editing, $campaign ) {
?>
	<p class="atcf-submit-campaign-summary">
		<label for="summary"><?php _e( 'Summary', 'atcf' ); ?></label>
		<textarea name="summary" id="summary" value="<?php echo $editing ? apply_filters( 'get_summary', $campaign->data->post_summary ) : null; ?>"></textarea>
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_summary', 10, 2 );

/**
 * Campaign gestionnaire du project
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_owner( $editing, $campaign ) {
?>
				
  <p>
	<label for="owner"><?php _e( 'Owner', 'atcf' ); ?></label>
    <select id="owner" name="owner" class="input-xlarge">
      <option>owner' organisations</option>
    </select>
  </p>
<?php
}

add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_owner', 10, 2 );
/**
 * Campaign Author
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_author( $editing, $campaign ) {
?>
	<p class="atcf-update-campaign-author">
		<label for="name"><?php _e( 'Name d organization', 'atcf' ); ?></label>
		<input type="text" name="name" id="name" value="<?php echo $editing ? $campaign->author() : null; ?>" />
	</p>
<?php
}/* 
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_author', 10, 2 ); */


/**
 * Campaign gestionnaire du project
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_location( $editing, $campaign ) {
?>
  
  <p>
  <label for="location"><?php _e( 'Location', 'atcf' ); ?></label>
    <select id="location" name="location" >
      <option>01 Ain</option>
      <option>02 Aisne</option>
      <option>03 Allier</option>
      <option>04 Alpes-de-Haute-Provence</option>
      <option>05 Hautes-Alpes</option>
      <option>06 Alpes-Maritimes</option>
      <option>07 Ardèche</option>
      <option>08 Ardennes</option>
      <option>09 Ariège</option>
      <option>10 Aube</option>
      <option>11 Aude</option>
      <option>12 Aveyron</option>
      <option>13 Bouches-du-Rhône</option>
      <option>14 Calvados</option>
      <option>15 Cantal</option>
      <option>16 Charente</option>
      <option>17 Charente-Maritime</option>
      <option>18 Cher</option>
      <option>19 Corrèze</option>
      <option>2A Corse-du-Sud</option>
      <option>2B Haute-Corse</option>
      <option>21 Côte-d'Or</option>
      <option>22 Côtes-d'Armor</option>
      <option>23 Creuse</option>
      <option>24 Dordogne</option>
      <option>25 Doubs</option>
      <option>26 Drôme</option>
      <option>27 Eure</option>
      <option>28 Eure-et-Loir</option>
      <option>29 Finistère</option>
      <option>30 Gard</option>
      <option>31 Haute-Garonne</option>
      <option>32 Gers</option>
      <option>33 Gironde</option>
      <option>34 Hérault</option>
      <option>35 Ille-et-Vilaine</option>
      <option>36 Indre</option>
      <option>37 Indre-et-Loire</option>
      <option>38 Isère</option>
      <option>39 Jura</option>
      <option>40 Landes</option>
      <option>41 Loir-et-Cher</option>
      <option>42 Loire</option>
      <option>43 Haute-Loire</option>
      <option>44 Loire-Atlantique</option>
      <option>45 Loiret</option>
      <option>46 Lot</option>
      <option>47 Lot-et-Garonne</option>
      <option>48 Lozère</option>
      <option>49 Maine-et-Loire</option>
      <option>50 Manche</option>
      <option>51 Marne</option>
      <option>52 Haute-Marne</option>
      <option>53 Mayenne</option>
      <option>54 Meurthe-et-Moselle</option>
      <option>55 Meuse</option>
      <option>56 Morbihan</option>
      <option>57 Moselle</option>
      <option>58 Nièvre</option>
      <option>59 Nord</option>
      <option>60 Oise</option>
      <option>61 Orne</option>
      <option>62 Pas-de-Calais</option>
      <option>63 Puy-de-Dôme</option>
      <option>64 Pyrénées-Atlantiques</option>
      <option>65 Hautes-Pyrénées</option>
      <option>66 Pyrénées-Orientales</option>
      <option>67 Bas-Rhin</option>
      <option>68 Haut-Rhin</option>
      <option>69 Rhône</option>
      <option>70 Haute-Saône</option>
      <option>71 Saône-et-Loire</option>
      <option>72 Sarthe</option>
      <option>73 Savoie</option>
      <option>74 Haute-Savoie</option>
      <option>75 Paris</option>
      <option>76 Seine-Maritime</option>
      <option>77 Seine-et-Marne</option>
      <option>78 Yvelines</option>
      <option>79 Deux-Sèvres</option>
      <option>80 Somme</option>
      <option>81 Tarn</option>
      <option>82 Tarn-et-Garonne</option>
      <option>83 Var</option>
      <option>84 Vaucluse</option>
      <option>85 Vendée</option>
      <option>86 Vienne</option>
      <option>87 Haute-Vienne</option>
      <option>88 Vosges</option>
      <option>89 Yonne</option>
      <option>90 Territoire de Belfort</option>
      <option>91 Essonne</option>
      <option>92 Hauts-de-Seine</option>
      <option>93 Seine-Saint-Denis</option>
      <option>94 Val-de-Marne</option>
      <option>95 Val-d'Oi</option>
    </select>
</p>
<?php
}

add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_location', 10, 2 );


/**
 * Campaign Zone d'impact
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_impact_area ($editing, $campaign ) {
?>
	<p class="atcf-update-campaign-impact_area">
		<label for="impact_area"><?php _e( 'Impact area', 'atcf' ); ?></label>
		<textarea name="impact_area" id="impact_area" value="<?php echo $editing ? apply_filters( 'get_impact_area', $campaign->data->post_impact_area ) : null; ?>"></textarea>
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_impact_area', 10, 2 );



function atcf_shortcode_update_field_type( $editing, $campaign ) {
	if ( $editing ) {
		$categories = get_the_terms( $campaign->ID, 'download_category' );

		$selected = 0;

		if ( ! $categories )
			$categories = array();

		foreach( $categories as $category ) {
			$selected = $category->term_id;
			break;
		}
	}
?>
	<!-- Multiple Checkboxes -->

    <label><?php _e( 'Funding Type', 'atcf' ); ?></label>
    <input type="radio" name="fixe" value="fixe" checked="checked">
		<?php _e( 'Fixe', 'atcf' ); ?>
	</input>
	
    <input type="radio" name="flexible" value="flexible">
		<?php _e( 'Flexible', 'atcf' ); ?>
	</input>
    
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_type', 10, 2 );


function atcf_shortcode_update_field_goal_interval( $editing, $campaign ) {
	global $edd_options;

	if ( $editing )
		return;

	$currencies = edd_get_currencies();
?>
	<p class="atcf-submit-campaign-goal-intervall">
		<label for="goal"><?php printf( __( 'Minimum Goal (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
		<input type="text" name="minimum_goal" id="minimum_goal" placeholder="<?php echo edd_format_amount( 100 ); ?>">
		<label for="goal"><?php printf( __( 'Maximum Goal (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
		<input type="text" name="maximum_goal" id="minimum_goal" placeholder="<?php echo edd_format_amount( 800000 ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_goal_interval', 10, 2 );



/**
 * Campaign Goal
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_goal( $editing, $campaign ) {
	global $edd_options;

	if ( $editing )
		return;

	$currencies = edd_get_currencies();
?>
	<p class="atcf-update-campaign-goal">
		<label for="goal"><?php printf( __( 'Goal (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
		<input type="text" name="goal" id="goal" placeholder="<?php echo edd_format_amount( 8000 ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_goal', 10, 2 );

/**
 * Campaign Length 
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_length( $editing, $campaign ) {
	global $edd_options;

	if ( $editing  )
		return;

	$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
	$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 100;

	$start = apply_filters( 'atcf_shortcode_submit_field_length_start', round( ( $min + $max ) / 2 ) );
?>
	<p class="atcf-submit-campaign-length">
		<label for="length"><?php _e( 'Length (Days)', 'atcf' ); ?></label>
		<input type="number" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="1" name="length" id="length" value="<?php echo esc_attr( $start ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_length', 10, 2 );


/**
 * Campaign Description
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_description( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign-description">
		<label for="description"><?php _e( 'Description', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->data->post_content ) : '', 'description', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_description', 11, 2 );

/**
 * Campaign Defi sociatal
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_societal_challenge( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign-societal_challenge">
		<label for="societal_challenge"><?php _e( 'Societal challenge', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->data->post_content ) : '', 'societal_challenge', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: red; width: 200 px; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_societal_challenge', 11, 2 );


/**
 * Campaign Valeur ajoutée
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
 function atcf_shortcode_update_field_value_added( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign_value_added">
		<label for="value_added"><?php _e( 'Added value', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->data->post_content ) : '', 'value_added', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_value_added', 11, 2 );


/**
 * Campaign Stratégie de développement
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */

 
 function atcf_shortcode_update_field_developement_strategy( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign-developement_strategy">
		<label for="developement_strategy"><?php _e( 'Strategy of  development', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->data->post_content ) : '', 'developement_strategy', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_developement_strategy', 11, 2 );



/**
 * Campaign Modele economique
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
  function atcf_shortcode_update_field_economic_model( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign_economic_model">
		<label for="economic_model"><?php _e( 'Economic model', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->data->post_content ) : '', 'economic_model', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_economic_model', 11, 2 );


/**
 * Campaign Mesure d’impact
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
   function atcf_shortcode_update_field_measuring_impact( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign_measuring_impact">
		<label for="measuring_impact"><?php _e( 'Measuring Impact', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->data->post_content ) : '', 'measuring_impact', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_measuring_impact', 11, 2 );

/**
 * Campaign Mise en oeuvre
 *implementation
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
   function atcf_shortcode_update_field_implementation( $editing, $campaign ) {
?>
	<div class="atcf-update-campaign-implementation">
		<label for="implementation"><?php _e( 'Implementation', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->data->post_content ) : '', 'implementation', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_implementation', 11, 2 );

 

/**
 * Campaign Updates
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_update_field_updates( $editing, $campaign ) {
	if ( ! $editing )
		return;
?>
	<div class="atcf-update-campaign-updates">
		<label for="description"><?php _e( 'Updates', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign->updates(), 'updates', apply_filters( 'atcf_submit_field_updates_editor_args', array( 
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_updates', 55, 2 );





function atcf_shortcode_update_field_contact_email( $editing, $campaign ) {
?>
	<h3 class="atcf-update-section payment-information"><?php _e( 'Your Information', 'atcf' ); ?></h3>

	<?php if ( ! $editing ) : ?>
	<p class="atcf-update-campaign-contact-email">
	<?php if ( ! is_user_logged_in() ) : ?>
		<label for="email"><?php _e( 'Contact Email', 'atcf' ); ?></label>
		<input type="text" name="contact-email" id="contact-email" value="<?php echo $editing ? $campaign->contact_email() : null; ?>" />
		<?php if ( ! $editing ) : ?><span class="description"><?php _e( 'An account will be created for you with this email address. It must be active.', 'atcf' ); ?></span><?php endif; ?>
	<?php else : ?>
		<?php $current_user = wp_get_current_user(); ?>
		<?php printf( __( '<strong>Note</strong>: You are currently logged in as %1$s. This %2$s will be associated with that account. Please <a href="%3$s">log out</a> if you would like to make a %2$s under a new account.', 'atcf' ), $current_user->user_email, strtolower( edd_get_label_singular() ), wp_logout_url( get_permalink() ) ); ?>
	<?php endif; ?>
	</p>
	<?php endif; ?>
<?php
}
add_action( 'atcf_shortcode_update_fields', 'atcf_shortcode_update_field_contact_email', 100, 2 );



