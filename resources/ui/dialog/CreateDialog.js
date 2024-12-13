ext.simpleBlogPage.ui.dialog.CreateDialog = function( cfg ) {
	ext.simpleBlogPage.ui.dialog.CreateDialog.parent.call( this, cfg );
	this.forcedBlog = cfg.blog || false;
};

OO.inheritClass( ext.simpleBlogPage.ui.dialog.CreateDialog, OO.ui.ProcessDialog );

ext.simpleBlogPage.ui.dialog.CreateDialog.static.name = 'simpleBlogPageCreateDialog';
ext.simpleBlogPage.ui.dialog.CreateDialog.static.title = mw.message( 'simpleblogpage-create-label' ).text();
ext.simpleBlogPage.ui.dialog.CreateDialog.static.actions = [
	{
		action: 'submit',
		label: mw.message( 'simpleblogpage-create-dialog-button-submit' ).text(),
		flags: [ 'primary', 'progressive' ]
	},
	{
		action: 'cancel',
		label: mw.message( 'simpleblogpage-create-dialog-button-cancel' ).text(),
		flags: [ 'safe' ]
	}
];

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.initialize = function() {
	ext.simpleBlogPage.ui.dialog.CreateDialog.parent.prototype.initialize.call( this );

	this.panel = new OO.ui.PanelLayout( { padded: true, expanded: false } );
	this.addTitleField();
	this.addSelector();

	this.actions.setAbilities( { submit: false } );
	this.$body.append( this.panel.$element );
};

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.addSelector = function() {
	this.blogSelector = new ext.simpleBlogPage.ui.widget.BlogSelector( {
		$overlay: this.$overlay,
		required: true
	} );
	this.blogSelector.connect( this, {
		change: 'onBlogChange',
		initialized: function() {
			if ( !this.forcedBlog ) {
				this.blogSelector.selectUserBlog();
				return;
			}
			this.blogSelector.setValue( this.forcedBlog );
			this.blogSelector.setDisabled( true );
		}
	} );
	this.blogSelector.menu.connect( this, {
		highlight: function() {
			if ( this.blogTypingTimer ) {
				clearTimeout( this.blogTypingTimer );
			}
		}
	} );
	this.blogSelectorLayout = new OO.ui.FieldLayout( this.blogSelector, {
		label: mw.message( 'simpleblogpage-editor-blog-select' ).text(),
		align: 'top',
		classes: [ 'blog-selector-layout' ]
	} );
	this.panel.$element.append( this.blogSelectorLayout.$element );
};

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.addTitleField = function() {
	this.titleField = new OO.ui.TextInputWidget( {
		required: true,
		limit: 255
	} );
	this.titleField.connect( this, { change: 'onTitleChange' } );
	this.titleLayout = new OO.ui.FieldLayout( this.titleField, {
		label: mw.message( 'simpleblogpage-editor-title' ).text(),
		align: 'top'
	} );
	this.panel.$element.append( this.titleLayout.$element );
};

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.getActionProcess = function( action ) {
	return ext.simpleBlogPage.ui.dialog.CreateDialog.parent.prototype.getActionProcess.call( this, action ).next(
		async function() {
			if ( action === 'submit' ) {
				var dfd = $.Deferred();
				this.pushPending();
				try {
					await this.checkValidity();
					this.popPending();
					const title = mw.Title.newFromText( this.blogEntryPage );
					if ( window.ve ) {
						window.location.href = title.getUrl( { veaction: 'edit' } );
					} else {
						window.location.href = title.getUrl( { action: 'edit' } );
					}

				} catch ( e ) {
					this.popPending();
					dfd.reject();
				}
				return dfd.promise();
			}
			if ( action === 'cancel' ) {
				this.close();
			}
		}.bind( this )
	);
};

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.onBlogChange = async function( value ) {
	if ( !this.blogSelector.initialized ) {
		return;
	}
	value = this.blogSelector.getValue();

	if ( value.value )  {
		if ( !value.fromOption ) {
			const title = mw.Title.makeTitle( 1502, value.value );
			value.value = title.getPrefixedDb();
		}
		this.blog = value.value;
		if ( this.blogTypingTimer ) {
			clearTimeout( this.blogTypingTimer );
		}
		this.blogTypingTimer = setTimeout( async () => {
			this.blogSelectorLayout.setWarnings( [] );
			const exists = await this.doCheckExists( this.blog );
			if ( !exists ) {
				this.blogSelectorLayout.setWarnings( [ mw.message( 'simpleblogpage-editor-blog-select-new' ).text() ] );
			}
			this.blogSelector.menu.toggle( false );
			this.updateSize();
		}, 1000 );
	} else {
		this.blog = false;
	}
	try {
		await this.checkValidity();
	} catch ( e ) {
		// NOOP
	}
};

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.onTitleChange = function() {
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

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.doCheckExists = async function( title ) {
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

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.composeEntryTitle = async function() {
	var dfd = $.Deferred();
	const promises = [
		this.titleField.getValidity(),
		this.blogSelector.getValidity()
	];
	$.when( ...promises ).done( () => {
		let title = mw.Title.newFromText( this.blog + '/' + this.titleField.getValue().ucFirst() );
		dfd.resolve( title.getPrefixedDb() );
	} ).fail( () => { dfd.reject(); } );
	return dfd.promise();
};

ext.simpleBlogPage.ui.dialog.CreateDialog.prototype.checkValidity = async function() {
	var dfd = $.Deferred();

	try {
		this.titleLayout.setErrors( [] );
		this.updateSize();
		if ( !this.blog ) {
			this.actions.setAbilities( { submit: false } );
			return dfd.reject().promise();
		}
		this.blogEntryPage = await this.composeEntryTitle();
		var exists = await this.doCheckExists( this.blogEntryPage );
		if ( exists ) {
			this.titleLayout.setErrors( [ mw.msg( 'simpleblogpage-editor-title-exists' ) ] );
			this.blogEntryPage = false;
			this.updateSize();
			this.actions.setAbilities( { submit: false } );
			dfd.reject();
		} else {
			this.actions.setAbilities( { submit: true } );
			dfd.resolve();
		}
	} catch ( e ) {
		this.updateSize();
		this.blogEntryPage = false;
		this.actions.setAbilities( { submit: false } );
		dfd.reject();
	}

	return dfd.promise();
};