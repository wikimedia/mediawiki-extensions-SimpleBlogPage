window.ext = window.ext || {};
ext.simpleBlogPage = {
	ui: {
		panel: {},
		widget: {},
		dialog: {}
	},
	openCreateDialog: function( blog ) {
		mw.loader.using( [ 'ext.simpleBlogPage.create' ] ).done( function() {
			const dialog = new ext.simpleBlogPage.ui.dialog.CreateDialog( { blog: blog } );
			OO.ui.getWindowManager().addWindows( [ dialog ] );
			return OO.ui.getWindowManager().openWindow( dialog );
		} );
	}
};