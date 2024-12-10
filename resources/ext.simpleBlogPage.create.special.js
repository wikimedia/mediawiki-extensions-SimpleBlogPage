$( function() {
	const $cnt = $( '#createblogpost' );
	if ( $cnt.length ) {
		function showError( error ) {
			$cnt.find( '#error' ).html(
				new OO.ui.MessageWidget( {
					type: 'error',
					label: error
				} ).$element
			);
		}
		const toolbar = new OOJSPlus.ui.toolbar.ManagerToolbar( {
			saveable: true,
			cancelable: false
		} );
		$cnt.find( '#form' ).append( toolbar.$element );
		toolbar.setup();
		toolbar.initialize();

		const editor = new ext.simpleBlogPage.ui.panel.Editor( {
			blog: $cnt.data( 'blog' ) || false,
		} );
		editor.connect( toolbar, {
			validity: function( valid ) {
				toolbar.setAbilities( { save: valid } );
			},
			error: function( error ) {
				showError( error );
			}
		} );
		$cnt.find( '#form' ).append( editor.$element );

		toolbar.connect( this, {
			save: async function() {
				try {
					await editor.submit();
					const overview = mw.Title.makeTitle( -1, 'ArticlesHome' );
					window.location.href = overview.getUrl();
				} catch ( e ) {
					console.log( e );
					showError( e );
				}
			},
			initialize: function() {
				toolbar.setAbilities( { save: false } );
			}
		} );
	}
} );