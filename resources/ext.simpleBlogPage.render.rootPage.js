$( () => {
	const $cnt = $( '#blog-root' );
	if ( $cnt.length > 0 ) {
		const blog = $cnt.attr( 'data-blog' );
		const blogPage = $cnt.attr( 'data-blog-page' );
		const exists = $cnt.attr( 'data-blog-exists' ) === 'true';
		const panel = new ext.simpleBlogPage.ui.panel.BlogList( {
			blog: blog,
			type: $cnt.attr( 'data-type' ),
			// If page exists, we want to reference it, if not, just use the name
			blogPage: exists ? blogPage : blog,
			allowCreation: $cnt.attr( 'data-creatable' ) === '1',
			native: true
		} );
		$cnt.html( panel.$element );
	}
} );
