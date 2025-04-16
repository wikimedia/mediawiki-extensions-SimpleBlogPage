ext.simpleBlogPage.ui.panel.BlogList = function ( cfg ) {
	cfg = $.extend( cfg || {}, { // eslint-disable-line no-jquery/no-extend
		expanded: false
	} );
	ext.simpleBlogPage.ui.panel.BlogList.parent.call( this, cfg );
	this.$element.addClass( 'blog-list' );

	this.blog = cfg.blog || false;
	this.type = cfg.type || 'global';
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
		filter: this.blog ?
			{ root: { value: this.blog, operator: 'eq' }, type: { value: this.type, operator: 'eq' } } : {},
		sorter: { timestamp: { direction: 'DESC' } }
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

	this.store.load().done( () => {
		this.paginator.init();
		if ( !this.filtersInitialized ) {
			this.buckets = this.store.getBuckets() || {};
			this.filtersInitialized = true;
			this.renderFilters();
		}
	} ).fail( ( xhr, status, error ) => {
		this.$element.html( new OO.ui.MessageWidget( {
			type: 'error',
			label: xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : error
		} ).$element );
	} );

	this.$element.append( this.itemPanel.$element, this.paginator.$element );
};

OO.inheritClass( ext.simpleBlogPage.ui.panel.BlogList, OO.ui.PanelLayout );

ext.simpleBlogPage.ui.panel.BlogList.prototype.renderHeader = function () {
	this.header = new OO.ui.PanelLayout( {
		expanded: false,
		classes: [ 'blog-list-header' ]
	} );
	this.$element.append( this.header.$element );

	this.$actions = $( '<div>' ).addClass( 'blog-list-actions' );
	this.$filters = $( '<div>' ).addClass( 'blog-list-filters' );

	if ( this.allowCreation ) {
		this.createButton = new OO.ui.ButtonWidget( {
			label: mw.msg( 'simpleblogpage-create-entry' ),
			icon: 'add',
			flags: [ 'progressive' ],
			framed: false
		} );
		this.createButton.connect( this, { click: 'onCreateClick' } );
		this.$actions.append( this.createButton.$element );
		this.header.$element.append( this.$actions );
	}
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.clearItems = function () {
	this.itemPanel.$element.empty();
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.setItems = function ( data ) {
	this.itemPanel.$element.empty();
	if ( $.isEmptyObject( data ) ) {
		this.showNoPosts();
		return;
	}
	for ( const index in data ) {
		if ( !data.hasOwnProperty( index ) ) {
			continue;
		}
		const item = data[ index ];
		const entry = new ext.simpleBlogPage.ui.panel.Entry( {
			wikiTitle: mw.Title.makeTitle( item.namespace, item.wikipage ),
			forcedBlog: this.forcedBlog ? true : this.isNative || this.blog,
			userCanWatch: item.canWatch, userIsWatching: item.isWatching
		} );
		this.itemPanel.$element.append( entry.$element );
	}
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.onCreateClick = function () {
	ext.simpleBlogPage.openCreateDialog( this.blogPage ? this.blogPage : null );
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.renderFilters = async function () {
	if ( this.isNative || this.blog ) {
		return;
	}
	this.header.$element.append( this.$filters );
	// Native means that we are on a blog root page, which forces root filter, so we dont offer it
	if ( this.buckets.hasOwnProperty( 'root' ) ) {
		const blogNames = await this.loadBlogNames();
		const globalBlogs = [];
		const userBlogs = [];
		this.buckets.root.forEach( ( i ) => {
			let display = i;
			let type = 'global';
			for ( const key in blogNames ) {
				if ( !blogNames.hasOwnProperty( key ) ) {
					continue;
				}
				if ( blogNames[ key ].dbKey === i ) {
					type = blogNames[ key ].type;
					display = blogNames[ key ].display;
					break;
				}

			}
			if ( type === 'global' ) {
				globalBlogs.push( { data: i, label: display } );
			} else if ( type === 'user' ) {
				userBlogs.push( { data: i, label: display } );
			}
		} );
		const options = [
			new OO.ui.MenuOptionWidget( {
				data: '',
				label: mw.msg( 'simpleblogpage-filter-all' )
			} ),
			new OO.ui.MenuSectionOptionWidget( {
				label: mw.msg( 'simpleblogpage-filter-section-global' )
			} ),
			...globalBlogs.map( ( i ) => new OO.ui.MenuOptionWidget( i ) )
		];
		if ( userBlogs.length > 0 ) {
			options.push( new OO.ui.MenuSectionOptionWidget( {
				label: mw.msg( 'simpleblogpage-filter-section-user' )
			} ) );
			options.push( ...userBlogs.map( ( i ) => new OO.ui.MenuOptionWidget( i ) ) );
		}
		this.rootFilter = new OO.ui.DropdownWidget( {
			menu: { items: options },
			title: mw.msg( 'simpleblogpage-filter-root' ),
			$overlay: true
		} );
		this.rootFilter.getMenu().selectItemByData( '' );
		this.rootFilter.menu.connect( this, {
			select: function ( item ) {
				this.onFilter( 'root', item.getData() );
			}
		} );
		this.$filters.append( this.rootFilter.$element );
	}
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.onFilter = function ( field, value ) {
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

ext.simpleBlogPage.ui.panel.BlogList.prototype.loadBlogNames = async function () {
	const deferred = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/helper/root_pages',
		method: 'GET'
	} ).done( ( data ) => {
		deferred.resolve( data );
	} ).fail( ( xhr, s, e ) => {
		console.error( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : e ); // eslint-disable-line no-console
		deferred.resolve( {} );
	} );
	return deferred;
};

ext.simpleBlogPage.ui.panel.BlogList.prototype.showNoPosts = function () {
	let label = mw.msg( 'simpleblogpage-no-posts' );
	if ( !this.allowCreation ) {
		label = mw.msg( 'simpleblogpage-no-blog-no-create' );
	}
	this.itemPanel.$element.append( new OOJSPlus.ui.widget.NoContentPlaceholderWidget( {
		icon: 'blog-no-posts',
		label: label
	} ).$element );
};
