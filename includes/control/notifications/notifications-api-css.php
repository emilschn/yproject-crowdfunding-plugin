<?php
class NotificationsAPICSS {
	public static function get() {
		ob_start(); ?>

<style type="text/css">
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

	.has-rouge-background-color {
		background-color: #EA4F51 !important;
	}
	.has-rouge-color {
		color: #EA4F51 !important;
	}
	.has-bleu-background-color {
		background-color: #00879B !important;
	}
	.has-bleu-color {
		color: #00879B !important;
	}
	.has-bleu-clair-background-color {
		background-color: #B3DAE1 !important;
	}
	.has-bleu-clair-color {
		color: #B3DAE1 !important;
	}
	.has-jaune-background-color {
		background-color: #EBCE67 !important;
	}
	.has-jaune-color {
		color: #EBCE67 !important;
	}
	.has-jaune-clair-background-color {
		background-color: #F9F0D1 !important;
	}
	.has-jaune-clair-color {
		color: #F9F0D1 !important;
	}
	.has-vert-background-color {
		background-color: #5EB82C !important;
	}
	.has-vert-color {
		color: #5EB82C !important;
	}
	.has-vert-clair-background-color {
		background-color: #CEE9C0 !important;
	}
	.has-vert-clair-color {
		color: #CEE9C0 !important;
	}
	.has-rose-background-color {
		background-color: #F8CACA !important;
	}
	.has-rose-color {
		color: #F8CACA !important;
	}
	.has-noir-background-color {
		background-color: #333333 !important;
	}
	.has-noir-color {
		color: #333333 !important;
	}
	.has-gris-background-color {
		background-color: #C2C2C2 !important;
	}
	.has-gris-color {
		color: #C2C2C2 !important;
	}
	.has-gris-clair-background-color {
		background-color: #EBEBEB !important;
	}
	.has-gris-clair-color {
		color: #EBEBEB !important;
	}
	.has-blanc-background-color {
		background-color: #ffffff !important;
	}
	.has-blanc-color {
		color: #ffffff !important;
	}
</style>

		<?php
		$content = ob_get_contents();
		ob_clean();

		return $content;
	}
}
