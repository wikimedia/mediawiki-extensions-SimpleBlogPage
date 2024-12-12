$( function() {
	const $cnt = $( '#blog-home' );
	if ( $cnt.length > 0 ) {
		const blogPage = $cnt.attr( 'data-blog' );
		const panel = new ext.simpleBlogPage.ui.panel.BlogList( {
			blog: blogPage === '' ? false : blogPage,
			allowCreation: $cnt.attr( 'data-creatable' ) === '1',
			native: false
		} );
		$cnt.html( panel.$element );
	}
} );