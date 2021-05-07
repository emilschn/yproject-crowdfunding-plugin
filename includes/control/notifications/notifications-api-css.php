<?php
class NotificationsAPICSS {
	public static function get() {
		ob_start(); ?>

<style type="text/css">
	div.wdg-email { margin: auto; max-width: 590px; }
	.has-text-align-right { text-align: right; }
	.has-text-align-center { text-align: center; }
	.aligncenter, .align-center { text-align: center; }
	.alignleft, .align-left { text-align: left; }
	.align-justify { text-align: justify; }
	.align-right { text-align: right; }
	.alignwide { margin-left: -50px; margin-right: -50px; }
	.alignfull { margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw); }
	.wp-block-cover.alignfull,
	.wp-block-cover-image.alignfull {
		width: 100vw; padding: 0px;
	}
	.wp-block-cover.alignfull .wp-block-cover__inner-container,
	.wp-block-cover-image.alignfull .wp-block-cover__inner-container { 
		width: 1280px;
		margin: auto;
	}
	figcaption { font-size: 90%; }

	hr {
		color: #c2c2c2;
	}

	p.has-background a:only-child::parent {
		position: relative;
		display: inline-block;
		max-width: 320px;
		left: 50%;
		transform: translateX(-50%);
	}
	p.has-background a:only-child {
		display: inline-block;
		padding: 20px 38px;
		text-decoration: none;
		text-transform: uppercase;
	}
	p.has-background {
		padding: 20px 38px;
	}

	.has-rouge-background-color {
		background-color: #EA4F51 !important;
	}
	.has-rouge-color, .has-rouge-color a, a {
		color: #EA4F51 !important;
	}
	.has-bleu-background-color {
		background-color: #00879B !important;
	}
	.has-bleu-color, .has-bleu-color a {
		color: #00879B !important;
	}
	.has-bleu-clair-background-color {
		background-color: #B3DAE1 !important;
	}
	.has-bleu-clair-color, .has-bleu-clair-color a {
		color: #B3DAE1 !important;
	}
	.has-jaune-background-color {
		background-color: #EBCE67 !important;
	}
	.has-jaune-color, .has-jaune-color a {
		color: #EBCE67 !important;
	}
	.has-jaune-clair-background-color {
		background-color: #F9F0D1 !important;
	}
	.has-jaune-clair-color, .has-jaune-clair-color a {
		color: #F9F0D1 !important;
	}
	.has-vert-background-color {
		background-color: #5EB82C !important;
	}
	.has-vert-color, .has-vert-color a {
		color: #5EB82C !important;
	}
	.has-vert-clair-background-color {
		background-color: #CEE9C0 !important;
	}
	.has-vert-clair-color, .has-vert-clair-color a {
		color: #CEE9C0 !important;
	}
	.has-rose-background-color {
		background-color: #F8CACA !important;
	}
	.has-rose-color, .has-rose-color a {
		color: #F8CACA !important;
	}
	.has-noir-background-color {
		background-color: #333333 !important;
	}
	.has-noir-color, .has-noir-color a {
		color: #333333 !important;
	}
	.has-gris-background-color {
		background-color: #C2C2C2 !important;
	}
	.has-gris-color, .has-gris-color a {
		color: #C2C2C2 !important;
	}
	.has-gris-clair-background-color {
		background-color: #EBEBEB !important;
	}
	.has-gris-clair-color, .has-gris-clair-color a {
		color: #EBEBEB !important;
	}
	.has-blanc-background-color {
		background-color: #ffffff !important;
	}
	.has-blanc-color, .has-blanc-color a {
		color: #ffffff !important;
	}
</style>

		<?php
		$content = ob_get_contents();
		ob_clean();

		return $content;
	}
}
