/**
 * Functionality specific to Campaignify.
 *
 * Provides helper functions to enhance the theme experience.
 */

var delay = (function(){
	var timer = 0;

	return function(callback, ms){
		clearTimeout (timer);
		timer = setTimeout(callback, ms);
	};
})();

var Campaignify = {}

Campaignify.App = ( function($) {
	var fancyBoxSettings = {
		maxWidth   : 650,
		width      : 650,
		autoSize   : false,
		height     : 'auto',
		helpers    : {
			overlay : {
				css : {
					'background' : 'rgba(32, 32, 32, .90)'
				},
				locked : false
			}
		},
		beforeShow : function() {
			$( '.fancybox-wrap' ).addClass( 'animated fadeInDown' );
		}
	}

	function commentForm() {
		$( '#commentform p, .modal-login form p, .modal-register form p' ).each( function(index, value ) {
			var label = $( this ).find( 'label' ),
			    input = $( this ).find( 'input, textarea' );

			label.addClass( 'screen-reader-text' );
			input.attr( 'placeholder', label.text() );
		});
	}

	return {
		init : function() {
			Campaignify.App.isMobile();
			Campaignify.App.arrowThings();
			Campaignify.App.logInButton();

			commentForm();

			if ( campaignifySettings.page.is_blog || campaignifySettings.campaignWidgets.widget_campaignify_campaign_blog_posts )
				Campaignify.App.gridify();

			$( '.nav-menu-primary .login a, .nav-menu-primary .register a' ).click(function(e) {
				e.preventDefault();
				
				Campaignify.App.fancyBox( $(this), {
					'href' : '#' + $(this).parent().attr( 'id' ) + '-wrap',
					'type' : 'inline'
				});
			});

			$( '.primary-menu-toggle' ).click(function(e) {
				$( '.site-primary-navigation' ).slideToggle( 'fast' );
			});
		},

		/**
		 * Check if we are on a mobile device (or any size smaller than 980).
		 * Called once initially, and each time the page is resized.
		 */
		isMobile : function( width ) {
			var isMobile = false;

			var width = 1180;
			
			if ( $(window).width() <= width )
				isMobile = true;

			return isMobile;
		},

		fancyBox : function( _this, args ) {
			$.fancybox.open(
				_this,
				$.extend( fancyBoxSettings, args )
			);
		},

		arrowThings : function() {
			$.each( $( '.arrowed' ), function() {
				var area  = $(this);
				
				$( '<div class="arrow"></div>' )
					.appendTo( area )
					.css( 'border-top-color', area.css( 'background-color' ) );
			});
		},

		logInButton : function() {
			$( '.nav-menu-primary .login a' )
				.prepend( '<i class="icon-user"></i>' );
		},

		gridify : function() {
			if ( ! $().masonry )
				return;

			var container = $( '.site-content.full' );

			if ( container.masonry() )
				container.masonry( 'reload' );
			
			container.imagesLoaded( function() {
				container.masonry({
					itemSelector : '.hentry',
					columnWidth  : 550,
					gutterWidth  : 40
				});
			});
		}
	}
} )(jQuery);

Campaignify.Widgets = ( function($) {
	function campaignHeroSlider() {
		$( '.campaign-hero-slider-title' ).fitText(1.2);

		var heroSlider = $( '.campaign-hero-slider' ).flexslider({
			controlNav     : false,
			slideshowSpeed : 5000,
			animation      :  'slide',
			prevText       : '<i class="icon-left-open-big"></i>',
			nextText       : '<i class="icon-right-open-big"></i>',
			start          : function(slider) {
				delay( function() {
					$( '.campaign-hero' ).removeClass( 'loading' );
				}, 500);
			}
		});
	}

	function campaignBackers() {
		var backerSlider = $( '.campaign-backers-slider' ).flexslider({
			controlNav : false,
			animation  :  'slide',
			prevText   : '<i class="icon-left-open-big"></i>',
			nextText   : '<i class="icon-right-open-big"></i>',
			maxItems   : 6,
			minItems   : 2,
			itemWidth  : 153,
			itemMargin : 44,
			slideshow  : false,
			move       : 2
		});
	}

	function campaignGallery( _this ) {
		var container = _this.parents( '.widget_campaignify_campaign_gallery' ).find( '.campaign-gallery' ),
		    showing   = container.data( 'showing' ),
		    post      = container.data( 'post' );

		var data = {
			'action'  : 'widget_campaignify_campaign_gallery_load',
			'offset'  : showing,
			'post'    : post,
			'_nonce'  : campaignifySettings.security.gallery
		}

		_this.fadeOut();

		$.post( campaignifySettings.ajaxurl, data, function( response ) {
			container.append( response );

			$( '.campaign-gallery-item' ).on( 'click', function(e) {
				e.preventDefault();

				$.fancybox(this, {
					href       : $(this).attr( 'href' ),
					type       : 'image',
					padding    : 0,
					maxWidth   : 9999,
					width      : 800,
					autoSize   : true,
					helpers: {
						overlay: {
							locked : false
						}
					}
				});
			});
		});
	}

	function campaignPledgeLevels() {
		$( '.campaignify-pledge-box' ).click( function(e) {
			e.preventDefault();

			if ( $( this ).hasClass( 'inactive' ) )
				return false;

			var price = $( this ).data( 'price' );

			Campaignify.App.fancyBox( $(this), {
				href : '#contribute-modal-wrap',
				type : 'inline',
				beforeShow : function() {
					$( '.edd_price_options' )
						.find( 'li[data-price="' + price + '"]' )
						.trigger( 'click' );
				}
			});
		} );
	}

	return {
		init : function() {
			if ( campaignifySettings.campaignWidgets.widget_campaignify_hero_contribute )
				campaignHeroSlider();

			if ( campaignifySettings.campaignWidgets.widget_campaignify_campaign_backers )
				campaignBackers();

			if ( campaignifySettings.campaignWidgets.widget_campaignify_campaign_gallery ) {
				$( '.campaign-gallery-more' ).click(function(e) {
					e.preventDefault();

					campaignGallery( $(this) );
				});

				/** Fancybox Stuff */
				$( '.campaign-gallery-item' ).fancybox({
					type       : 'image',
					padding    : 0,
					maxWidth   : 9999,
					width      : 800,
					autoSize   : true,
					helpers: {
						overlay: {
							locked : false
						}
					}
				});
			}

			if ( campaignifySettings.campaignWidgets.widget_campaignify_campaign_pledge_levels )
				campaignPledgeLevels();
		},

		resize : function () {
			if ( campaignifySettings.campaignWidgets.widget_campaignify_campaign_pledge_levels )
				campaignPledgeLevels();
		}
	}
} )(jQuery);

Campaignify.Checkout = ( function($) {
	var customPriceField  = $( '#campaignify_custom_price' ),
	    priceOptions      = $( '.edd_price_options li' ),
	    submitButton      = $( '.edd-add-to-cart' ),
	    currentPrice,
	    startPledgeLevel;

	var formatCurrentSettings = {
		'decimalSymbol'    : campaignifySettings.currency.decimal,
		'digitGroupSymbol' : campaignifySettings.currency.thousands,
		'symbol'           : ''
	}

	function priceOptionsHandler() {
		customPriceField.keyup(function() {
			submitButton.attr( 'disabled', true );

			var price = $( this ).val();

			delay( function() {
				Campaignify.Checkout.setPrice( price );

				if ( currentPrice < startPledgeLevel )
					Campaignify.Checkout.setPrice( startPledgeLevel );
			}, 750);
		});

		priceOptions.click(function(e) {
			var pledgeLevel = $(this),
			    price = pledgeLevel.data( 'price' );

			if ( pledgeLevel.hasClass( 'inactive' ) )
				return;

			Campaignify.Checkout.setPrice( price );
		});
	}

	return {
		init : function() {
			if ( customPriceField.length == 0 )
				return;

			Campaignify.Checkout.setBasePrice();
			priceOptionsHandler();

			$( '.contribute, .contribute a' ).click(function(e) {
				e.preventDefault();

				Campaignify.App.fancyBox( $(this), {
					'href' : '#contribute-modal-wrap',
					'type' : 'inline'
				});
			});
		},

		setPrice : function( price ) {
			customPriceField
				.val( price )
				.formatCurrency( formatCurrentSettings );

			/** get formatted amount as number */
			currentPrice = customPriceField.asNumber({
				parseType : 'float'
			});

			priceOptions.each( function( index ) {
				var pledgeLevel = parseFloat( $(this).data( 'price' ) );

				if ( ( currentPrice >= pledgeLevel ) && ! $( this ).hasClass( 'inactive' ) )
					$( this ).find( 'input[type="radio"]' ).attr( 'checked', true );
			});

			submitButton.attr( 'disabled', false );
		},

		setBasePrice : function() {
			priceOptions.each( function( index ) {
				if ( ! $( this ).hasClass( 'inactive' ) && null == startPledgeLevel ) {
					startPledgeLevel = parseFloat( $(this).data( 'price' ) );

					Campaignify.Checkout.setPrice( startPledgeLevel );
				}
			});
		}
	}
} )(jQuery);

jQuery( document ).ready(function($) {
	Campaignify.App.init();
	Campaignify.Widgets.init();

	if ( campaignifySettings.page.is_campaign )
		Campaignify.Checkout.init();

	$( window ).on( 'resize', function() {
		Campaignify.Widgets.resize();

		Campaignify.App.gridify();
	});
});