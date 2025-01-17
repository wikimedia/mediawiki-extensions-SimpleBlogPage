window.ext = window.ext || {};
ext.simpleBlogPage = {
	ui: {
		panel: {},
		widget: {},
		dialog: {}
	},
	openCreateDialog: function( blog ) {
		mw.loader.using( [ 'ext.simpleBlogPage.create' ] ).done( function() {
			const dialog = new ext.simpleBlogPage.ui.dialog.CreateDialog( {
				blog: blog,
				actor: mw.config.get( 'wgUserName' )
			} );
			OO.ui.getWindowManager().addWindows( [ dialog ] );
			return OO.ui.getWindowManager().openWindow( dialog );
		} );
	}
};

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