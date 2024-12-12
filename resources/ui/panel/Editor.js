ext.simpleBlogPage.ui.panel.Editor = function( cfg ) {
	cfg = cfg || {};
	cfg = $.extend( {
		expanded: false,
		padded: true,
	}, cfg );
	ext.simpleBlogPage.ui.panel.Editor.super.call( this, cfg );

	this.blog = cfg.blog || false;
	this.blogEntryPage = cfg.blogEntryPage || false;
	this.render();

	if ( this.blogEntryPage ) {
		this.loadBlog();
	}
};

OO.inheritClass( ext.simpleBlogPage.ui.panel.Editor, OO.ui.PanelLayout );

ext.simpleBlogPage.ui.panel.Editor.prototype.render = function() {
	let formItems = [];
	if ( !this.blogEntryPage ) {
		// Creation, offer selection of blog
		this.blogSelector = new ext.simpleBlogPage.ui.widget.BlogSelector( { required: true } );
		this.blogSelector.connect( this, {
			change: 'onBlogChange',
			initialized: function() {
				if ( this.blog ) {
					this.blogSelector.setValue( this.blog );
				}
			}
		} );
		this.blogSelectorLayout = new OO.ui.FieldLayout( this.blogSelector, {
			label: mw.message( 'simpleblogpage-editor-blog-select' ).text(),
			align: 'top'
		} );
		formItems.push( this.blogSelectorLayout );
	}
	this.titleField = new OO.ui.TextInputWidget( {
		required: true,
		limit: 255
	} );
	this.titleField.connect( this, { change: 'onTitleChange' } );
	this.titleLayout = new OO.ui.FieldLayout( this.titleField, {
		label: mw.message( 'simpleblogpage-editor-title' ).text(),
		align: 'top'
	} );
	formItems.push( this.titleLayout );
	this.contentField = new OO.ui.MultilineTextInputWidget( {
		rows: 10,
		required: true
	} );
	this.contentField.connect( this, { change: 'onContentChange' } );
	formItems.push( new OO.ui.FieldLayout( this.contentField, {
		label: mw.message( 'simpleblogpage-editor-content' ).text(),
		align: 'top'
	} ) );

	this.$element.append( new OO.ui.FormLayout( {
		items: formItems
	} ).$element );
};

ext.simpleBlogPage.ui.panel.Editor.prototype.loadBlog = async function() {
	try {
		var data = await this.doLoad( this.blogEntryPage );
		this.setValue( data );
		this.emit( 'dataLoaded', data );
	} catch ( e ) {
		this.emit( 'error', e );
	}
};

ext.simpleBlogPage.ui.panel.Editor.prototype.doLoad = async function( blogPage ) {
	const dfd = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/blog/' + blogPage,
		dataType: 'json',
		success: function( data ) {
			dfd.resolve( data );
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			dfd.reject( jqXHR.hasOwnProperty( 'responseJSON' ) ? jqXHR.responseJSON.message : errorThrown );
		}
	} );
	return dfd.promise();
};

ext.simpleBlogPage.ui.panel.Editor.prototype.onTitleChange = function() {
	if ( this.titleTypeTimer ) {
		clearTimeout( this.titleTypeTimer );
	}
	this.titleTypeTimer = setTimeout( async () => {
		try {
			await this.checkValidity();
		} catch ( e ) {
			// NOOP
		}
	}, 500 );
};

ext.simpleBlogPage.ui.panel.Editor.prototype.onBlogChange = async function( value ) {
	if ( !this.blogSelector.initialized ) {
		return;
	}
	value = this.blogSelector.getValue();

	this.blogSelectorLayout.setWarnings( [] );
	if ( value )  {
		this.blog = value;
		if ( this.blogTypingTimer ) {
			clearTimeout( this.blogTypingTimer );
		}
		this.blogTypingTimer = setTimeout( async () => {
			const title = mw.Title.makeTitle( 1502, value );
			const exists = await this.doCheckExists( title.getPrefixedDb() );
			if ( !exists ) {
				this.blogSelectorLayout.setWarnings( [ mw.message( 'simpleblogpage-editor-blog-select-new' ).text() ] );
			}
		}, 500 );
		this.blog = value;
	}
	try {
		await this.checkValidity();
	} catch ( e ) {
		// NOOP
	}
};

ext.simpleBlogPage.ui.panel.Editor.prototype.onContentChange = async function() {
	try {
		await this.checkValidity();
	} catch ( e ) {
		// NOOP
	}
};

ext.simpleBlogPage.ui.panel.Editor.prototype.setValue = function( data ) {
	this.titleField.setValue( data.title );
	this.contentField.setValue( data.content );
};

ext.simpleBlogPage.ui.panel.Editor.prototype.doCheckExists = async function( title ) {
	const dfd = $.Deferred();
	new mw.Api().get( {
		action: 'query',
		titles: title
	} ).done( function( data ) {
		if ( data.query.pages[ -1 ] ) {
			dfd.resolve( false );
		} else {
			dfd.resolve( true );
		}
	} ).fail( function( error ) {
		dfd.reject( error );
	} );
	return dfd.promise();
};

ext.simpleBlogPage.ui.panel.Editor.prototype.composeEntryTitle = async function() {
	var dfd = $.Deferred();
	const promises = [
		this.titleField.getValidity(),
		this.blogSelector.getValidity()
	];
	$.when( ...promises ).done( () => {
		dfd.resolve( 'Blog:' + this.blog + '/' + this.titleField.getValue().ucFirst() );
	} ).fail( () => { dfd.reject(); } );
	return dfd.promise();
};

ext.simpleBlogPage.ui.panel.Editor.prototype.checkValidity = async function() {
	var dfd = $.Deferred();

	try {
		this.titleLayout.setErrors( [] );
		if ( !this.blog ) {
			this.emit( 'validity', false );
			return dfd.reject().promise();
		}
		this.blogEntryPage = await this.composeEntryTitle();
		var exists = await this.doCheckExists( this.blogEntryPage );
		if ( exists ) {
			this.titleLayout.setErrors( [ mw.msg( 'simpleblogpage-editor-title-exists' ) ] );
			this.blogEntryPage = false;
		}
		await this.contentField.getValidity();
		this.emit( 'validity', true );
		dfd.resolve();
	} catch ( e ) {
		this.blogEntryPage = false;
		this.emit( 'validity', false );
		dfd.reject();
	}

	return dfd.promise();
};

ext.simpleBlogPage.ui.panel.Editor.prototype.submit = async function() {
	const dfd = $.Deferred();
	try {
		await this.checkValidity();
		$.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/blog',
			dataType: 'json',
			contentType: 'application/json',
			method: 'PUT',
			data: JSON.stringify( {
				target_page: this.blogEntryPage,
				content: this.contentField.getValue()
			} ),
			success: function( data ) {
				dfd.resolve( data );
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				dfd.reject( jqXHR.hasOwnProperty( 'responseJSON' ) ? jqXHR.responseJSON.message : errorThrown );
			}
		} );
	} catch ( e ) {
		dfd.reject( e );
	}
	return dfd.promise();
};
