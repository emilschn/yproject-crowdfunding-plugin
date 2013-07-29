<?php

/**
 * Submit Shortcode.
 *
 * [appthemer_crowdfunding_submit] creates a submission form.
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
function atcf_shortcode_submit( $editing = false ) {
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
	<?php do_action( 'atcf_shortcode_submit_before', $editing, $campaign ); ?>
	<form action="" method="post" class="atcf-submit-campaign" enctype="multipart/form-data">
		<?php do_action( 'atcf_shortcode_submit_fields', $editing, $campaign ); ?>

		<p class="atcf-submit-campaign-submit">
			<input type="submit" value="<?php echo $editing ? sprintf( _x( 'Update %s', 'edit "campaign"', 'atcf' ), edd_get_label_singular() ) : sprintf( _x( 'Submit %s', 'submit "campaign"', 'atcf' ), edd_get_label_singular() ); ?>">
			<input type="hidden" name="action" value="atcf-campaign-<?php echo $editing ? 'edit' : 'submit'; ?>" />
			<?php wp_nonce_field( 'atcf-campaign-' . ( $editing ? 'edit' : 'submit' ) ); ?>
		</p>
		
	</form>
	<?php do_action( 'atcf_shortcode_submit_after', $editing, $campaign ); ?>
	
<?php
	$form = ob_get_clean();

	return $form;
}

add_shortcode( 'appthemer_crowdfunding_submit', 'atcf_shortcode_submit' );

/**
 * Campaign Title
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_title( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<h3 class="atcf-submit-section campaign-information"><?php _e( 'Campaign Information', 'atcf' ); ?></h3>

	<p class="atcf-submit-title">
		<label for="title"><?php _e( 'Name', 'atcf' ); ?></label>
		<input type="text" name="title" id="title" placeholder="<?php esc_attr_e( 'Title', 'atcf' ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_title', 10, 2 );




/**
 * Campaign Category
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_category( $editing, $campaign ) {
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
	<p class="atcf-submit-campaign-category">
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_category', 10, 2 );


/**
 * Campaign Images
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_images( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<p class="atcf-submit-campaign-images">
		<label for="excerpt"><?php _e( 'Preview Image', 'atcf' ); ?></label>
		<input type="file" name="image" id="image" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_images', 10, 2 );

/**
 * Campaign Video
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_video( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<p class="atcf-submit-campaign-video">
		<label for="length"><?php _e( 'Video URL', 'atcf' ); ?></label>
		<input type="text" name="video" id="video">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_video', 10, 2 );



/**
 * Campaign summary
 * Ce champs repr�sente le Resum�
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_summary( $editing, $campaign ) {
?>
	<p class="atcf-submit-campaign-summary">
		<label for="summary"><?php _e( 'Summary', 'atcf' ); ?></label>
		<textarea name="summary" id="summary" value="<?php echo $editing ? apply_filters( 'get_summary', $campaign->data->post_summary ) : null; ?>"></textarea>
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_summary', 10, 2 );

/**
 * Campaign gestionnaire du project
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_owner( $editing, $campaign ) {
?>
				
  <p>
	<label for="owner"><?php _e( 'Owner', 'atcf' ); ?></label>
    <select id="owner" name="owner" class="input-xlarge">
      <option>owner' organisations</option>
    </select>
  </p>
<?php
}

add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_owner', 10, 2 );
/**
 * Campaign Author
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_author( $editing, $campaign ) {
?>
	<p class="atcf-submit-campaign-author">
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
function atcf_shortcode_submit_field_location( $editing, $campaign ) {
?>
  
  <p>
  <label for="lieu"><?php _e( 'Location', 'atcf' ); ?></label>
    <select id="location" name="location" >
      <option>01 Ain</option>
      <option>02 Aisne</option>
      <option>03 Allier</option>
      <option>04 Alpes-de-Haute-Provence</option>
      <option>05 Hautes-Alpes</option>
      <option>06 Alpes-Maritimes</option>
      <option>07 Ard�che</option>
      <option>08 Ardennes</option>
      <option>09 Ari�ge</option>
      <option>10 Aube</option>
      <option>11 Aude</option>
      <option>12 Aveyron</option>
      <option>13 Bouches-du-Rh�ne</option>
      <option>14 Calvados</option>
      <option>15 Cantal</option>
      <option>16 Charente</option>
      <option>17 Charente-Maritime</option>
      <option>18 Cher</option>
      <option>19 Corr�ze</option>
      <option>2A Corse-du-Sud</option>
      <option>2B Haute-Corse</option>
      <option>21 C�te-d'Or</option>
      <option>22 C�tes-d'Armor</option>
      <option>23 Creuse</option>
      <option>24 Dordogne</option>
      <option>25 Doubs</option>
      <option>26 Dr�me</option>
      <option>27 Eure</option>
      <option>28 Eure-et-Loir</option>
      <option>29 Finist�re</option>
      <option>30 Gard</option>
      <option>31 Haute-Garonne</option>
      <option>32 Gers</option>
      <option>33 Gironde</option>
      <option>34 H�rault</option>
      <option>35 Ille-et-Vilaine</option>
      <option>36 Indre</option>
      <option>37 Indre-et-Loire</option>
      <option>38 Is�re</option>
      <option>39 Jura</option>
      <option>40 Landes</option>
      <option>41 Loir-et-Cher</option>
      <option>42 Loire</option>
      <option>43 Haute-Loire</option>
      <option>44 Loire-Atlantique</option>
      <option>45 Loiret</option>
      <option>46 Lot</option>
      <option>47 Lot-et-Garonne</option>
      <option>48 Loz�re</option>
      <option>49 Maine-et-Loire</option>
      <option>50 Manche</option>
      <option>51 Marne</option>
      <option>52 Haute-Marne</option>
      <option>53 Mayenne</option>
      <option>54 Meurthe-et-Moselle</option>
      <option>55 Meuse</option>
      <option>56 Morbihan</option>
      <option>57 Moselle</option>
      <option>58 Ni�vre</option>
      <option>59 Nord</option>
      <option>60 Oise</option>
      <option>61 Orne</option>
      <option>62 Pas-de-Calais</option>
      <option>63 Puy-de-D�me</option>
      <option>64 Pyr�n�es-Atlantiques</option>
      <option>65 Hautes-Pyr�n�es</option>
      <option>66 Pyr�n�es-Orientales</option>
      <option>67 Bas-Rhin</option>
      <option>68 Haut-Rhin</option>
      <option>69 Rh�ne</option>
      <option>70 Haute-Sa�ne</option>
      <option>71 Sa�ne-et-Loire</option>
      <option>72 Sarthe</option>
      <option>73 Savoie</option>
      <option>74 Haute-Savoie</option>
      <option>75 Paris</option>
      <option>76 Seine-Maritime</option>
      <option>77 Seine-et-Marne</option>
      <option>78 Yvelines</option>
      <option>79 Deux-S�vres</option>
      <option>80 Somme</option>
      <option>81 Tarn</option>
      <option>82 Tarn-et-Garonne</option>
      <option>83 Var</option>
      <option>84 Vaucluse</option>
      <option>85 Vend�e</option>
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

add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_location', 10, 2 );


/**
 * Campaign Zone d'impact
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_impact_area ($editing, $campaign ) {
?>
	<p class="atcf-submit-campaign-impact_area">
		<label for="impact_area"><?php _e( 'Impact area', 'atcf' ); ?></label>
		<textarea name="impact_area" id="impact_area" value="<?php echo $editing ? apply_filters( 'get_impact_area', $campaign->data->post_impact_area ) : null; ?>"></textarea>
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_impact_area', 10, 2 );



function atcf_shortcode_submit_field_type( $editing, $campaign ) {
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_type', 10, 2 );


function atcf_shortcode_submit_field_goal_interval( $editing, $campaign ) {
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_goal_interval', 10, 2 );



/**
 * Campaign Goal
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_goal( $editing, $campaign ) {
	global $edd_options;

	if ( $editing )
		return;

	$currencies = edd_get_currencies();
?>
	<p class="atcf-submit-campaign-goal">
		<label for="goal"><?php printf( __( 'Goal (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
		<input type="text" name="goal" id="goal" placeholder="<?php echo edd_format_amount( 8000 ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_goal', 10, 2 );

/**
 * Campaign Length 
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_length( $editing, $campaign ) {
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_length', 10, 2 );


/**
 * Campaign Description
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_description( $editing, $campaign ) {
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_description', 11, 2 );

/**
 * Campaign Defi sociatal
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_societal_challenge( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign-societal_challenge">
		<label for="societal_challenge"><?php _e( 'Societal challenge', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->societal_challenge() ) : '', 'societal_challenge', apply_filters( 'atcf_submit_field_societal_challenge_editor_args', array( 
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_societal_challenge', 11, 2 );


/**
 * Campaign Valeur ajout�e
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
 function atcf_shortcode_submit_field_added_value( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign_added_value">
		<label for="added_value"><?php _e( 'Added value', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->added_value() ) : '', 'added_value', apply_filters( 'atcf_submit_field_added_value"_editor_args', array( 
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_added_value', 11, 2 );


/**
 * Campaign Strat�gie de d�veloppement
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */

 
 function atcf_shortcode_submit_field_development_strategy( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign-development_strategy">
		<label for="development_strategy"><?php _e( 'Development strategy', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->development_strategy() ) : '', 'development_strategy', apply_filters( 'atcf_submit_field_development_strategy_editor_args', array( 
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_development_strategy', 11, 2 );



/**
 * Campaign Modele economique
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
  function atcf_shortcode_submit_field_economic_model( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign_economic_model">
		<label for="economic_model"><?php _e( 'Economic model', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->economic_model() ) : '', 'economic_model', apply_filters( 'atcf_submit_field_economic_model_editor_args', array( 
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_economic_model', 11, 2 );


/**
 * Campaign Mesure d�impact
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
   function atcf_shortcode_submit_field_measuring_impact( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign_measuring_impact">
		<label for="measuring_impact"><?php _e( 'Measuring Impact', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->measuring_impact() ) : '', 'measuring_impact', apply_filters( 'atcf_submit_field_measuring_impact_editor_args', array( 
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_measuring_impact', 11, 2 );

/**
 * Campaign Mise en oeuvre
 *implementation
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
 
   function atcf_shortcode_submit_field_implementation( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign-implementation">
		<label for="implementation"><?php _e( 'Implementation', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->implementation() ) : '', 'implementation', apply_filters( 'atcf_submit_field_implementation_editor_args', array( 
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_implementation', 11, 2 );

 

/**
 * Campaign Updates
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_updates( $editing, $campaign ) {
	if ( ! $editing )
		return;
?>
	<div class="atcf-submit-campaign-updates">
		<label for="description"><?php _e( 'Updates', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign->updates(), 'updates', apply_filters( 'atcf_submit_field_updates_editor_args', array( 
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
	</div><br />
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_updates', 55, 2 );


function atcf_shortcode_submit_field_contact_email( $editing, $campaign ) {
?>
	<h3 class="atcf-submit-section payment-information"><?php _e( 'Your Information', 'atcf' ); ?></h3>

	<?php if ( ! $editing ) : ?>
	<p class="atcf-submit-campaign-contact-email">
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_contact_email', 100, 2 );




/**
 * Terms
 *
 * @since CrowdFunding 1.0
 *
 * @return void
 */
function atcf_shortcode_submit_field_terms( $editing, $campaign ) {
	if ( $editing )
		return;
	
	edd_agree_to_terms_js();
	edd_terms_agreement();
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_terms', 200, 2 );

/**
 * Success Message
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_before_success() {
	if ( ! isset ( $_GET[ 'success' ] ) )
		return;

	$message = apply_filters( 'atcf_shortcode_submit_success', __( 'Success! Your campaign has been received. It will be reviewed shortly.', 'atcf' ) );
?>
	<p class="edd_success"><?php echo esc_attr( $message ); ?></p>	
<?php
}
add_action( 'atcf_shortcode_submit_before', 'atcf_shortcode_submit_before_success', 1 );

/**
 * Process shortcode submission.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_process() {
	global $edd_options, $post;
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'atcf-campaign-submit' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'atcf-campaign-submit' ) )
		return;

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	}

	$errors           = new WP_Error();
	$prices           = array();
	$edd_files        = array();
	$upload_overrides = array( 'test_form' => false );

	$terms     		= isset ( $_POST[ 'edd_agree_to_terms' ] ) ? $_POST[ 'edd_agree_to_terms' ] : 0;
	$title     		= $_POST[ 'title' ];
	$summary     	= $_POST[ 'summary' ];
	$owner          = $_POST[ 'owner' ];
	$actu           = $_POST[ 'updates' ];
	$location       = $_POST[ 'location' ];
	$impact_area    = $_POST[ 'impact_area' ];
	$goal      		= $_POST[ 'goal' ];
	$length    		= $_POST[ 'length' ];
	$type      		= $_POST[ 'campaign_type' ];
	$category  		= isset ( $_POST[ 'cat' ] ) ? $_POST[ 'cat' ] : 0;
	$content   		= $_POST[ 'description' ];
	$excerpt   		= $_POST[ 'excerpt' ];
	$societal_challenge  = $_POST[ 'societal_challenge' ];
	$added_value = $_POST[ 'added_value' ];

	$development_strategy  = $_POST[ 'development_strategy' ];
	$economic_model         = $_POST[ 'economic_model' ];
	$measuring_impact       = $_POST[ 'measuring_impact' ];
	$implementation  		= $_POST[ 'implementation' ];
	
	$author    		= $_POST[ 'name' ];
	$shipping  		= $_POST[ 'shipping' ];

	$image     = $_FILES[ 'image' ];
	$video     = $_POST[ 'video' ];

	/* $rewards   = $_POST[ 'rewards' ]; */
	$files     = $_FILES[ 'files' ];
	
	
	if ( isset ( $_POST[ 'contact-email' ] ) )
		$c_email = $_POST[ 'contact-email' ];
	else {
		$current_user = wp_get_current_user();
		$c_email = $current_user->user_email;
	}

	if ( isset( $edd_options[ 'show_agree_to_terms' ] ) && ! $terms )
		$errors->add( 'terms', __( 'Please agree to the Terms and Conditions', 'atcf' ) );

	/** Check Title */
	if ( empty( $title ) )
		$errors->add( 'invalid-title', __( 'Please add a title to this campaign.', 'atcf' ) );

	/** Check Goal */
	$goal = edd_sanitize_amount( $goal );

	if ( ! is_numeric( $goal ) )
		$errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );

	/** Check Length */
	$length = absint( $length );

	$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
	$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 42;

	if ( $length < $min )
		$length = $min;
	else if ( $length > $max )
		$length = $max;

	$end_date = strtotime( sprintf( '+%d day', $length ) );
	$end_date = get_gmt_from_date( date( 'Y-m-d H:i:s', $end_date ) );

	/** Check Category */
	$category = absint( $category );

	/** Check Content */
	if ( empty( $content ) )
		$errors->add( 'invalid-content', __( 'Please add content to this campaign.', 'atcf' ) );

	/** Check Excerpt */
	if ( empty( $excerpt ) )
		$excerpt = null;

	/** Check Image */
	if ( empty( $image ) )
		$errors->add( 'invalid-previews', __( 'Please add a campaign image.', 'atcf' ) );

	/** Check Rewards */
	/* if ( empty( $rewards ) )
		$errors->add( 'invalid-rewards', __( 'Please add at least one reward to the campaign.', 'atcf' ) ); */

	if ( email_exists( $c_email ) && ! isset ( $current_user ) )
		$errors->add( 'invalid-c-email', __( 'That contact email address already exists.', 'atcf' ) );		

	do_action( 'atcf_campaign_submit_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) // Not sure how to avoid empty instantiated WP_Error
		wp_die( $errors );

	if ( ! $type )
		$type = atcf_campaign_type_default();

	if ( ! isset ( $current_user ) ) {
		$user_id = atcf_register_user( array(
			'user_login'           => $c_email, 
			'user_pass'            => $password, 
			'user_email'           => $c_email,
			'display_name'         => $author,
		) );
	} else {
		$user_id = $current_user->ID;
	}

	$args = apply_filters( 'atcf_campaign_submit_data', array(
		'post_type'   		 	=> 'download',
		'post_status'  		 	=> 'pending',
		'post_title'   		 	=> $title,
		'post_content' 		 	=> $content,
		'post_excerpt' 			=> $excerpt,
		'post_author'  			=> $user_id,
		
	), $_POST );

	$campaign = wp_insert_post( $args, true );

	wp_set_object_terms( $campaign, array( $category ), 'download_category' );
	
	
	// Create category for blog
	$id_category = wp_insert_category( array('cat_name' => 'cat'.$campaign, 'category_parent' => $parent, 'category_nicename' => sanitize_title($campaign . '-blog-' . $title)) );
	
	
	// Insert posts in blog from updates field

	$blog  = array(
		  'post_title'    => $title,
		  'post_content'  => $actu.$updates.'blog Patati patatata my project blog',
		  'post_status'   => 'publish',
		  'post_author'   => $user_id,
		  'post_category' => array($id_category )
		);

		// Insert the post into the database
		wp_insert_post( $blog,true );

	/** Extra Campaign Information */
	add_post_meta( $campaign, 'campaign_goal', apply_filters( 'edd_metabox_save_edd_price', $goal ) );
	add_post_meta( $campaign, 'campaign_type', sanitize_text_field( $type ) );
	add_post_meta( $campaign, 'campaign_owner', sanitize_text_field( $owner ) );
	add_post_meta( $campaign, 'campaign_contact_email', sanitize_text_field( $c_email ) );
	add_post_meta( $campaign, 'campaign_end_date', sanitize_text_field( $end_date ) );
	add_post_meta( $campaign, 'campaign_location', sanitize_text_field( $location ) );
	add_post_meta( $campaign, 'campaign_author', sanitize_text_field( $author ) );
	add_post_meta( $campaign, 'campaign_video', esc_url( $video ) );
	add_post_meta( $campaign, '_campaign_physical', sanitize_text_field( $shipping ) );
	add_post_meta( $campaign, 'campaign_summary', sanitize_text_field( $summary ) );
	add_post_meta( $campaign, 'campaign_impact_area', sanitize_text_field( $impact_area ) );
	add_post_meta( $campaign, 'campaign_added_value', sanitize_text_field( $added_value ) );
	add_post_meta( $campaign, 'campaign_development_strategy', sanitize_text_field( $development_strategy ) );
	add_post_meta( $campaign, 'campaign_economic_model', sanitize_text_field( $economic_model ) );
	add_post_meta( $campaign, 'campaign_measuring_impact', sanitize_text_field( $measuring_impact ) );
	add_post_meta( $campaign, 'campaign_implementation', sanitize_text_field( $implementation ) );
	add_post_meta( $campaign, 'campaign_societal_challenge', sanitize_text_field( $societal_challenge ) );

	

	if ( ! empty( $files ) ) {
		foreach ( $files[ 'name' ] as $key => $value ) {
			if ( $files[ 'name' ][$key] ) {
				$file = array(
					'name'     => $files[ 'name' ][$key],
					'type'     => $files[ 'type' ][$key],
					'tmp_name' => $files[ 'tmp_name' ][$key],
					'error'    => $files[ 'error' ][$key],
					'size'     => $files[ 'size' ][$key]
				);

				$upload = wp_handle_upload( $file, $upload_overrides );

				if ( isset( $upload[ 'url' ] ) )
					$edd_files[$key]['file'] = $upload[ 'url' ];
				else
					unset($files[$key]);
			}
		}
	}

	if ( '' != $image[ 'name' ] ) {
		$upload = wp_handle_upload( $image, $upload_overrides );
		$attachment = array(
			'guid'           => $upload[ 'url' ], 
			'post_mime_type' => $upload[ 'type' ],
			'post_title'     => $upload[ 'file' ],
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign );		
		
		wp_update_attachment_metadata( 
			$attach_id, 
			wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
		);

		add_post_meta( $campaign, '_thumbnail_id', absint( $attach_id ) );
	}

	/** EDD Stuff */
	add_post_meta( $campaign, '_variable_pricing', 1 );
	add_post_meta( $campaign, '_edd_price_options_mode', 1 );
	add_post_meta( $campaign, '_edd_hide_purchase_link', 'on' );
	
	add_post_meta( $campaign, 'edd_variable_prices', $prices );

	if ( ! empty( $files ) ) {
		add_post_meta( $campaign, 'edd_download_files', $edd_files );
	}

	do_action( 'atcf_submit_process_after', $campaign, $_POST );

	$url = isset ( $edd_options[ 'submit_page' ] ) ? get_permalink( $edd_options[ 'submit_page' ] ) : get_permalink();

	$redirect = apply_filters( 'atcf_submit_campaign_success_redirect', add_query_arg( array( 'success' => 'true' ), $url ) );
	wp_safe_redirect( $redirect );
	exit();
}
add_action( 'template_redirect', 'atcf_shortcode_submit_process' );

/**
 * Redirect submit page if needed.
 *
 * @since Appthemer CrowdFunding 1.1
 *
 * @return void
 */
function atcf_shortcode_submit_redirect() {
	global $edd_options, $post;

	if ( ! is_a( $post, 'WP_Post' ) )
		return;

	if ( ! is_user_logged_in() && ( !empty($edd_options[ 'submit_page' ]) && $post->ID == $edd_options[ 'submit_page' ] ) && isset ( $edd_options[ 'atcf_settings_require_account' ] ) ) {
		$redirect = apply_filters( 'atcf_require_account_redirect', isset ( $edd_options[ 'login_page' ] ) ? get_permalink( $edd_options[ 'login_page' ] ) : home_url() );
			
		wp_safe_redirect( $redirect );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_shortcode_submit_redirect', 1 );