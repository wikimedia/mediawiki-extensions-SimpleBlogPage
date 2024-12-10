$( function() {
	const $cnt = $( '.blog-entry.native' );
	$cnt.each( function( i, entry ) {
		const $entry = $( entry );
		const meta = JSON.parse( $entry.attr( 'data-blog-meta' ) || '{}' );
		const $headerCtn = $entry.find( '.blog-entry-header' );

		var header = new ext.simpleBlogPage.ui.panel.EntryHeader( $.extend( meta, {
			padded: false,
			native: true
		} ) );
		$headerCtn.replaceWith( header.$element );
	} );
} );