$( function() {
	const $cnt = $( '#blog-home' );
	if ( $cnt.length > 0 ) {
		const blogToFilter = $cnt.attr( 'data-blog' );
		const blogPage = $cnt.attr( 'data-blog-page' );
		const panel = new ext.simpleBlogPage.ui.panel.BlogList( {
			blog: blogToFilter === '' ? false : blogToFilter,
			blogPage: blogPage === '' ? false : blogPage,
			type: $cnt.attr( 'data-type' ),
			allowCreation: $cnt.attr( 'data-creatable' ) === '1',
			native: false
		} );
		$cnt.html( panel.$element );
	}
} );