ext.simpleBlogPage.ui.panel.BlogList = function( cfg ) {
	cfg = $.extend( cfg || {}, {
		expanded: false
	} );
	ext.simpleBlogPage.ui.panel.BlogList.parent.call( this, cfg );
	this.$element.addClass( 'blog-list' );

	this.blog = cfg.blog || false;
	this.blogPage = cfg.blogPage || false;
	this.isNative = cfg.native || false;
	this.allowCreation = cfg.allowCreation || false;
	this.limit = cfg.limit || 10;
	this.filtersInitialized = false;
	this.forcedBlog = null;

	this.store = new OOJSPlus.ui.data.store.RemoteRestStore( {
		path: 'simpleblogpage/v1/list',
		remoteFilter: true,
		remoteSort: true,
		pageSize: this.limit,
		filter: this.blog ? { root: { value: this.blog, operator: 'eq' } } : {},
		sorter: { timestamp: { direction: 'DESC' } },
	} );

	this.renderHeader();
	this.itemPanel = new OO.ui.PanelLayout( {
		expanded: false,
		classes: [ 'blog-list-items' ]
	} );
	this.paginator = new OOJSPlus.ui.data.grid.Paginator( {
		store: this.store,
		grid: this
	} );

	this.store.load().done( function() {
		this.paginator.init();
		if ( !this.filtersInitialized ) {
			this.buckets = this.store.getBuckets() || {};
			this.filtersInitialized = true;
			this.renderFilters();
		}
	}.bind( this ) ).fail( function( xhr, status, error ) {
		this.$element.html( new OO.ui.MessageWidget(  {
			type: 'error',
			label: xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : error
		} ).$element );
	}.bind( this ) );

	this.$element.append( this.itemPanel.$element, this.paginator.$element );
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

ext.simpleBlogPage.ui.panel.BlogList.prototype.clearItems = function() {
	this.itemPanel.$element.empty();
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.setItems = function( data ) {
	this.itemPanel.$element.empty();
	for ( let index in data ) {
		if ( !data.hasOwnProperty( index ) ) {
			continue;
		}
		const item = data[index];
		const entry = new ext.simpleBlogPage.ui.panel.Entry({
			wikiTitle: mw.Title.makeTitle( item.namespace, item.wikipage ),
			forcedBlog: this.forcedBlog ? true : this.isNative,
		} );
		this.itemPanel.$element.append( entry.$element );
	}
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.onCreateClick = function() {
	ext.simpleBlogPage.openCreateDialog( this.isNative && this.blogPage ? this.blogPage : null );
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.renderFilters = async function() {
	if ( !this.isNative && !this.blog ) {
		// Native means that we are on a blog root page, which forces root filter, so we dont offer it
		if ( this.buckets.hasOwnProperty( 'root' ) ) {
			var blogNames = await this.loadBlogNames();
			let options = [ { data: '', label: mw.msg( 'simpleblogpage-filter-all' ) } ];
			this.buckets.root.forEach( function( i ) {
				let display = i;
				for ( var key in blogNames ) {
					if ( !blogNames.hasOwnProperty( key ) ) {
						continue;
					}
					if ( blogNames[key].dbKey === i ) {
						display = blogNames[key].display;
						break;
					}
				}
				options.push( { data: i, label: display } );
			} );
			this.rootFilter = new OO.ui.DropdownInputWidget( {
				options: options,
				title: mw.msg( 'simpleblogpage-filter-root' )
			} );
			this.rootFilter.connect( this, {
				change: function( value ) {
					this.onFilter( 'root', value );
				}
			} );
			this.$filters.append( this.rootFilter.$element );
		}
	}
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.onFilter = function( field, value ) {
	let filter = null;
	if ( value ) {
		filter = new OOJSPlus.ui.data.filter.String( {
			value: value,
			operator: 'eq'
		} );
		this.forcedBlog = value;
	} else {
		this.forcedBlog = null;
	}
	this.store.filter( filter, field );
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.loadBlogNames = async function() {
	var deferred = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/helper/root_pages',
		method: 'GET'
	} ).done( function( data ) {
		deferred.resolve( data );
	} ).fail( function( xhr, s, e ) {
		console.error( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : e );
		deferred.resolve( {} );
	} );
	return deferred;
};
