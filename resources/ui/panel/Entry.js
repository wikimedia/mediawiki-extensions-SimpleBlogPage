	ext.simpleBlogPage.ui.panel.Entry = function EntryHeader( config ) {
	config = config || {};
	config.expanded = false;
	ext.simpleBlogPage.ui.panel.Entry.parent.call( this, config );
	this.title = config.title;
	this.revision = config.revision || 0;

	this.isNative = config.native || false;
	this.$element.addClass( 'blog-entry' );

	this.loadData().done( function( data ) {
		this.data = data;
		this.render();
	}.bind( this ) ).fail( function( e ) {
		this.showError( e );
	}.bind( this ) );

	this.render( config );
};

OO.inheritClass( ext.simpleBlogPage.ui.panel.Entry, OO.ui.PanelLayout );

ext.simpleBlogPage.ui.panel.Entry.prototype.render = function() {
	this.header = new ext.simpleBlogPage.ui.panel.EntryHeader( this.data.meta );
	this.content = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false,
		classes: [ 'blog-entry-body' ]
	} );
	this.content.$element.append( new OO.ui.HtmlSnippet( this.data.text ) );

	this.$element.append( this.header.$element, this.content.$element );
};

ext.simpleBlogPage.ui.panel.Entry.prototype.loadData = function() {
	var deferred = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/entry',
		data: {
			title: this.title,
			revision: this.revision
		},
		method: 'GET'
	} ).done( function( data ) {
		deferred.resolve( data );
	} ).fail( function( xhr, s, e ) {
		deferred.reject( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : e );
	} );
	return deferred;
};

ext.simpleBlogPage.ui.panel.Entry.prototype.showError = function( e ) {
	this.$element.html( new OO.ui.MessageWidget( {
		type: 'error',
		label: e
	} ).$element );
};
