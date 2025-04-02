ext.simpleBlogPage.ui.widget.BlogSelector = function ( cfg ) {
	cfg = cfg || {};
	ext.simpleBlogPage.ui.widget.BlogSelector.super.call( this, cfg );
	this.initialized = false;
	this.userBlog = null;
	this.optionMapping = {};
	this.optionMappingReverse = {};

	this.loadOptions().done( ( options ) => {
		this.menu.clearItems();
		for ( const dbkey in options ) {
			if ( !options.hasOwnProperty( dbkey ) ) {
				continue;
			}
			if ( options[ dbkey ].type === 'user' ) {
				this.userBlog = dbkey;
			}
			const display = dbkey === this.userBlog ? mw.msg( 'simpleblogpage-create-blog-own' ) : options[ dbkey ].display;
			this.optionMapping[ dbkey ] = display;
			this.optionMappingReverse[ display ] = dbkey;
			const item = new OO.ui.MenuOptionWidget( {
				data: dbkey,
				label: display,
				icon: this.userBlog === dbkey ? 'userAvatarOutline' : ''
			} );
			this.menu.addItems( [ item ] );
		}
		this.initialized = true;
		this.emit( 'initialized' );
	} ).fail( () => {
		this.initialized = true;
	} );
};

OO.inheritClass( ext.simpleBlogPage.ui.widget.BlogSelector, OO.ui.ComboBoxInputWidget );

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.loadOptions = function () {
	const deferred = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/helper/root_pages',
		data: { forCreation: true },
		method: 'GET'
	} ).done( ( data ) => {
		deferred.resolve( data );
	} ).fail( ( xhr, s, e ) => {
		console.error( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : e ); // eslint-disable-line no-console
		deferred.reject();
	} );
	return deferred;
};

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.setValue = function ( value ) {
	if ( value ) {
		value = value.replace( /\s/g, '_' );
	}
	if ( value && this.optionMapping && this.optionMapping.hasOwnProperty( value ) ) {
		value = this.optionMapping[ value ];
	}
	ext.simpleBlogPage.ui.widget.BlogSelector.super.prototype.setValue.call( this, value );
};

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.getValue = function () {
	const value = ext.simpleBlogPage.ui.widget.BlogSelector.super.prototype.getValue.call( this );
	if ( this.optionMappingReverse && this.optionMappingReverse.hasOwnProperty( value ) ) {
		return { fromOption: true, value: this.optionMappingReverse[ value ] };
	}
	return { fromOption: false, value: value };
};

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.selectUserBlog = function () {
	if ( this.userBlog ) {
		this.setValue( this.userBlog );
	}
};
