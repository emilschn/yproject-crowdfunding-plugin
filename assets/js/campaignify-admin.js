jQuery(document).ready(function($){
	wp.media.campaignifyEditGallery = {
	 
		frame: function() {
			if ( this._frame )
				return this._frame;
	 
			var selection = this.select();
			
			this._frame = wp.media({
				id:         'campaignifySliderFrame',                
				frame:      'post',
				state:      'gallery-edit',
				editing:    true,
				multiple:   true,
				selection : selection
			});

			return this._frame;
		},

		select: function() {
			var shortcode = wp.shortcode.next( 'gallery', $( '#campaignify_slider' ).val() ),
				defaultPostId = wp.media.gallery.defaults.id,
				attachments, selection;
		 
			// Bail if we didn't match the shortcode or all of the content.
			if ( ! shortcode )
				return;
		 
			// Ignore the rest of the match object.
			shortcode = shortcode.shortcode;
		 
			if ( _.isUndefined( shortcode.get('id') ) && ! _.isUndefined( defaultPostId ) )
				shortcode.set( 'id', defaultPostId );
		 
			attachments = wp.media.gallery.attachments( shortcode );
			selection = new wp.media.model.Selection( attachments.models, {
				props:    attachments.props.toJSON(),
				multiple: true
			});
			 
			selection.gallery = attachments.gallery;
		 
			// Fetch the query's attachments, and then break ties from the
			// query to allow for sorting.
			selection.more().done( function() {
				// Break ties with the query.
				selection.props.set({ query: false });
				selection.unmirror();
				selection.props.unset('orderby');
			});
		 
			return selection;
		},
	 
		init: function() {
			$( '.campaignify-manage-slider' ).click( function( event ) {
				event.preventDefault();
	 
				wp.media.campaignifyEditGallery.frame().open();
			});

			wp.media.campaignifyEditGallery.frame().on( 'update', function(selection) {
				$( '#campaignify_slider' ).val( wp.media.gallery.shortcode( selection ).string() );
			});
		}
	};

	$( wp.media.campaignifyEditGallery.init );
});