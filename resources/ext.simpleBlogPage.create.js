$( function() {
	const $btn = $( '#ca-simpleblogpage-create' );
	if ( !$btn.length ) {
		return;
	}
	$btn.on( 'click', function( e ) {
		e.preventDefault();
		ext.simpleBlogPage.openCreateDialog();
	} );
} );