ext.simpleBlogPage.ui.panel.Entry = function EntryHeader( config ) {
	config = config || {};
	config.expanded = false;
	ext.simpleBlogPage.ui.panel.Entry.parent.call( this, config );
	this.wikiTitle = config.wikiTitle;
	this.revision = config.revision || 0;
	this.forcedBlog = config.forcedBlog || false;
	this.userCanWatch = config.userCanWatch || false;
	this.userIsWatching = config.userIsWatching || false;

	this.isNative = config.native || false;
	this.$element.addClass( 'blog-entry' );
	this.setLoading( true );

	this.loadData().done( ( data ) => {
		this.data = data;
		this.render();
		this.setLoading( false );
	} ).fail( ( e ) => {
		this.showError( e );
		this.setLoading( false );
	} );
};

OO.inheritClass( ext.simpleBlogPage.ui.panel.Entry, OO.ui.PanelLayout );

ext.simpleBlogPage.ui.panel.Entry.prototype.render = function () {
	this.header = new ext.simpleBlogPage.ui.panel.EntryHeader(
		this.data.meta, this.wikiTitle, this.forcedBlog, { canWatch: this.userCanWatch, isWatching: this.userIsWatching }
	);
	this.content = new OO.ui.PanelLayout( {
		padded: false,
		expanded: false,
		classes: [ 'blog-entry-body' ]
	} );
	this.content.$element.append( this.data.text );
	if ( this.data.meta.hasMoreText ) {
		const readMoreBtn = new OO.ui.ButtonWidget( {
			label: mw.message( 'simpleblogpage-readmore' ).text(),
			flags: [ 'progressive' ],
			framed: false,
			classes: [ 'read-more-button' ],
			href: this.wikiTitle.getUrl()
		} );
		this.content.$element.append( readMoreBtn.$element );
	}
	const $unFloater = $( '<div>' ).css( 'clear', 'both' );

	this.$element.append( this.header.$element, this.content.$element, $unFloater );
};

ext.simpleBlogPage.ui.panel.Entry.prototype.loadData = function () {
	const deferred = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/entry',
		data: {
			title: this.wikiTitle.getPrefixedDb(),
			revision: this.revision || 0
		},
		method: 'GET'
	} ).done( ( data ) => {
		deferred.resolve( data );
	} ).fail( ( xhr, s, e ) => {
		deferred.reject( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : e );
	} );
	return deferred;
};

ext.simpleBlogPage.ui.panel.Entry.prototype.showError = function ( e ) {
	this.$element.html( new OO.ui.MessageWidget( {
		type: 'error',
		label: e
	} ).$element );
};

ext.simpleBlogPage.ui.panel.Entry.prototype.setLoading = function ( loading ) {
	if ( loading ) {
		this.$element.empty();
		this.$element.addClass( 'loading' );
	} else {
		this.$element.removeClass( 'loading' );
	}
};
