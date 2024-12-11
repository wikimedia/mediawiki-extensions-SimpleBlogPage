ext.simpleBlogPage.ui.panel.BlogList = function( cfg ) {
	cfg = $.extend( cfg || {}, {
		expanded: false
	} );
	ext.simpleBlogPage.ui.panel.BlogList.parent.call( this, cfg );
	this.$element.addClass( 'blog-list' );

	this.blog = cfg.blog || false;
	this.isNative = cfg.native || false;
	this.allowCreation = cfg.allowCreation || false;
	this.total = 0;
	this.offset = 0;
	this.limit = cfg.limit || 10;

	this.store = new OOJSPlus.ui.data.store.RemoteRestStore( {
		path: 'simpleblogpage/v1/list',
		remoteFilter: true,
		remoteSort: true,
		filter: this.blog ? { root: { value: this.blog, operator: 'eq' } } : {},
		sorter: { timestamp: { direction: 'DESC' } },
	} );

	this.renderHeader();
	this.store.loadRaw().done( function( response ) {
		console.log( response );
	}.bind( this ) ).fail( function ( xhr, status, error ) {
		this.$element.html( new OO.ui.MessageWidget(  {
			type: 'error',
			label: xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : error
		} ).$element );
	}.bind( this ) );
};

OO.inheritClass( ext.simpleBlogPage.ui.panel.BlogList, OO.ui.PanelLayout );

ext.simpleBlogPage.ui.panel.BlogList.prototype.renderHeader = function() {
	this.header = new OO.ui.PanelLayout( {
		expanded: false,
		classes: [ 'blog-list-header' ]
	} );
	this.$element.append( this.header.$element );

	this.$actions = $( '<div>' ).addClass( 'blog-list-actions' );
	this.$filters = $( '<div>' ).addClass( 'blog-list-filters' );
	this.header.$element.append( this.$actions, this.$filters );

	if ( this.allowCreation ) {
		this.createButton = new OO.ui.ButtonWidget( {
			label: mw.msg( 'simpleblogpage-create-entry' ),
			icon: 'add',
			flags: [ 'progressive' ],
			framed: false
		} );
		this.createButton.connect( this, { click: 'onCreateClick' } );
		this.$actions.append( this.createButton.$element );
	}
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.renderItems = function( data ) {
	for ( var i = 0; i < data.length; i++ ) {
		console.log( data[i] );
	}
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.loadData = async function() {
	const deferred = $.Deferred();
	try {
		const data = {
			limit: this.limit,
			offset: this.offset
		};
		if ( this.blog ) {
			data.blog = this.blog;
		}
		const res = await $.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/list',
			data: data,
			method: 'GET'
		} );
		deferred.resolve( res.results, res.total );
	} catch ( e ) {
		deferred.reject( e );
	}
	return deferred;
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.onCreateClick = function() {
	const targetPage = 'CreateBlogPost';
	if ( this.blog ) {
		targetPage += '/' + this.blog;
	}
	const title = mw.Title.makeTitle( -1, targetPage );

	window.location.href = title.getUrl( { returnto: mw.config.get( 'wgPageName' ) } );
	// Optionally: Open dialog
};
