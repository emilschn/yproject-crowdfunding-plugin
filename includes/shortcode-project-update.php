<?php
/**
 * Vote Shortcode.
 *
 * [appthemer_crowdfunding_project-update] creates a upadate form.
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

function atcf_shortcode_project_update() {
	global $post;

	$user = wp_get_current_user();

	ob_start();

	echo '<div class="atcf-vote">';
	echo '<form name="voteform" id="voteform" action="" method="post">';
	do_action( 'atcf_shortcode_vote', $user, $post );
	echo '</form>';
	echo '</div>';

	$form = ob_get_clean();

	return $form;
	
	wp_enqueue_script( 'jquery-validation', EDD_PLUGIN_URL . 'assets/js/jquery.validate.min.js');
	wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery', 'jquery-validation' ) );

	wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
		'oneReward' => __( 'At least one reward is required.', 'atcf' )
	) );
}
add_shortcode( 'appthemer_crowdfunding_vote', 'atcf_shortcode_vote' );

/**
 * Register form
 *
 * @since CrowdFunding 1.0
 *
 * @return $form
 */
function atcf_shortcode_project_update_form() {
	global $edd_options;
?>
  <form class="form-horizontal action="vote_reception">
<fieldset>

<a href="#" id="clickMe">Un lien</a>
<div id="slideMe">Blablabla... Vachement inspiré comme gars</div>
<!-- Form Name -->
<legend>Formulaire de vote</legend>

<!-- Multiple Checkboxes -->
<div class="control-group">
  <label class="control-label">Je pense que ce projet va avoir un impact positif</label>
  <div class="controls">
    <label class="checkbox">
      <input type="checkbox" name="checkboxes" value="Local">
      Local
    </label>
    <label class="checkbox">
      <input type="checkbox" name="checkboxes" value="Environnemental">
      Environnemental
    </label>
    <label class="checkbox">
      <input type="checkbox" name="checkboxes" value="Economique">
      Economique
    </label>
    <label class="checkbox">
      <input type="checkbox" name="checkboxes" value="Social">
      Social
    </label>
    <label class="checkbox">
      <input type="checkbox" name="checkboxes" value="Autre">
      Autre
    </label>
  </div>
</div>

<!-- Text input-->
<div class="control-group">
  <label class="control-label">Présisez</label>
  <div class="controls">
    <input id="autre" name="autre" type="text" placeholder="" class="input-xlarge">
    
  </div>
</div>

<!-- Multiple Checkboxes (inline) -->
<!-- Multiple Radios -->
<div class="control-group">
  <label class="control-label"></label>
  <div class="controls">
    <label class="radio">
      <input type="radio" name="radios" value="Je pense que ce projet va avoir un impact positif" checked="checked">
      Je pense que ce projet va avoir un impact positif
    </label>
    <label class="radio">
      <input type="radio" name="radios" value="Je désapprouve ce projet car son impact prévu n'est pas significatif">
      Je désapprouve ce projet car son impact prévu n'est pas significatif
    </label>
  </div>
</div>

<!-- Multiple Checkboxes -->
<div class="control-group">
  <label class="control-label"></label>
  <div class="controls">
    <label class="checkbox">
      <input type="checkbox" name="radio_apourve" value="Je pense que ce projet présente un risque très faible">
      Je pense que ce projet présente un risque très faible
    </label>
    <label class="checkbox">
      <input type="checkbox" name="radio_apourve" value="Je pense que ce projet présente un risque plutôt faible">
      Je pense que ce projet présente un risque plutôt faible
    </label>
    <label class="checkbox">
      <input type="checkbox" name="radio_apourve" value="Je pense que ce projet présente un risque modéré">
      Je pense que ce projet présente un risque modéré
    </label>
    <label class="checkbox">
      <input type="checkbox" name="radio_apourve" value="Je pense que ce projet présente un risque plutôt élevé">
      Je pense que ce projet présente un risque plutôt élevé
    </label>
    <label class="checkbox">
      <input type="checkbox" name="radio_apourve" value="Je pense que ce projet présente un risque très élevé">
      Je pense que ce projet présente un risque très élevé
    </label>
  </div>
</div>

<!-- Multiple Checkboxes (inline) -->
<div class="control-group">
  <label class="control-label"></label>
  <div class="controls">
    <label class="checkbox inline">
      <input type="checkbox" name="checkboxes" value="Je pense que ce projet doit être retravaillé avant de pouvoir être financé">
      Je pense que ce projet doit être retravaillé avant de pouvoir être financé
    </label>
  </div>
</div>

<!-- Select Basic -->
<div class="control-group">
  <label class="control-label"></label>
  <div class="controls">
    <select id="select_impact" name="select_impact" class="input-xlarge">
      <option>Pas d’impact responsable</option>
      <option>Projet mal expliqué</option>
      <option>Qualité du produit/service</option>
      <option>Qualité de l’équipe</option>
      <option>Qualité du business plan</option>
      <option>Qualité d’innovation</option>
      <option>Qualité du marché</option>
      <option>Porteur</option>
    </select>
  </div>
</div>

<!-- Button -->
<div class="control-group">
  <label class="control-label"></label>
  <div class="controls">
    <button id="vote" name="vote" class="btn btn-primary">Voter</button>
  </div>
</div>

</fieldset>
</form>
</body>
</html>
<?php
}
add_action( 'atcf_shortcode_vote', 'atcf_shortcode_vote_form' );



/*********Le java script**************/