$( function() {
	const $cnt = $( '#blog-root' );
	if ( $cnt.length > 0 ) {
		const blogPage = $cnt.attr( 'data-blog' );
		const panel = new ext.simpleBlogPage.ui.panel.BlogList( {
			blog: blogPage
		} );
		$cnt.html( panel.$element );
	}
} );