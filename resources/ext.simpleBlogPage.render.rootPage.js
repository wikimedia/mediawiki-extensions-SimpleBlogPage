$( function() {
	const $cnt = $( '#blog-root' );
	if ( $cnt.length > 0 ) {
		const blogPage = $cnt.attr( 'data-blog' );
		const panel = new ext.simpleBlogPage.ui.panel.BlogList( {
			blog: blogPage,
			allowCreation: $cnt.attr( 'data-creatable' ) === '1',
			native: true
		} );
		$cnt.html( panel.$element );
	}
} );