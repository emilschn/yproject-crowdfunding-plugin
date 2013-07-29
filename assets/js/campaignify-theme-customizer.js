/**
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes asynchronously.
 * Things like site title and description changes.
 */

( function( $ ) {
	// Site title and description.
	wp.customize( 'blogname', function( value ) {
		value.bind( function( to ) {
			$( '.site-title' ).text( to );
		} );
	} );

	wp.customize( 'blogdescription', function( value ) {
		value.bind( function( to ) {
			$( '.site-description' ).text( to );
		} );
	} );

	// Header text color.
	wp.customize( 'header_textcolor', function( value ) {
		value.bind( function( to ) {
			$( '.site-title' ).css( 'color', to );
		} );
	} );

	// Primary color.
	wp.customize( 'campaignify_primary', function( value ) {
		value.bind( function( to ) {
			$( '.site-branding:hover .site-title, .nav-menu-primary ul li a:hover, .nav-menu-primary li a:hover, .entry-meta a:hover, .blog-widget a:hover' ).css( 'color', to );

			$( '.nav-menu-primary li.login a:hover, .page-header' ).css( 'background-color', to );
			$( '.page-header .arrow' ).css( 'border-top-color', to );
		} );
	} );

	// Accent color.
	wp.customize( 'campaignify_accent', function( value ) {
		value.bind( function( to ) {
			$( '.button-primary, .edd-add-to-cart, .donation-progress' ).css( 'background-color', to );
		} );
	} );

	// Widget Colors
	$.each( CampaignifyCustomizerParams.colors, function( index, key ) {
		wp.customize( key, function( value ) {
			value.bind( function( to ) {
				var is_text = key.substring( key.length, key.length - 4 );
				
				if ( 'text' == is_text ) {
					$( '#' + key.substring(0, key.length - 5) ).css( 'color', to )
				} else {
					$( '#' + key ).css( 'background', to );
					$( '#' + key ).find( '.arrow' ).css( 'border-top-color', to );
				}
			} );
		} );
	});

	// Social links.
	$.each( CampaignifyCustomizerParams.social, function( index, key ) {
		wp.customize( key, function( value ) {
			value.bind( function( to ) {
				var link = $( '.' + key );

				link
					.attr( 'href', to )
					.toggleClass( 'hidden', '' == to );
			} );
		} );
	});
} )( jQuery );
