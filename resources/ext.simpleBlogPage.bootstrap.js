window.ext = window.ext || {};
ext.simpleBlogPage = {
	ui: {
		panel: {},
		widget: {},
		dialog: {}
	},
	openCreateDialog: function ( blog ) {
		mw.loader.using( [ 'ext.simpleBlogPage.create' ] ).done( () => {
			const dialog = new ext.simpleBlogPage.ui.dialog.CreateDialog( {
				blog: blog,
				actor: mw.config.get( 'wgUserName' )
			} );
			OO.ui.getWindowManager().addWindows( [ dialog ] );
			return OO.ui.getWindowManager().openWindow( dialog );
		} );
	}
};

$( document ).on( 'click', '#ca-simpleblogpage-create', ( e ) => {
	e.preventDefault();
	ext.simpleBlogPage.openCreateDialog();
} );
