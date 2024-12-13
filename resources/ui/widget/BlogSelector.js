ext.simpleBlogPage.ui.widget.BlogSelector = function( cfg ) {
	cfg = cfg || {};
	ext.simpleBlogPage.ui.widget.BlogSelector.super.call( this, cfg );
	this.initialized = false;
	this.userBlog = null;
	this.optionMapping = {};
	this.optionMappingReverse = {};

	this.loadOptions().done( function( options ) {
		this.menu.clearItems();
		for ( var dbkey in options ) {
			if ( !options.hasOwnProperty( dbkey ) ) {
				continue;
			}
			this.optionMapping[dbkey] = options[dbkey].display;
			this.optionMappingReverse[options[dbkey].display] = dbkey;
			if ( options[dbkey].type === 'user' ) {
				this.userBlog = dbkey;
			}
			const data = options[dbkey];
			const item = new OO.ui.MenuOptionWidget( {
				data: dbkey,
				label: data.display
			} );
			this.menu.addItems( [ item ] );
		}
		this.initialized = true;
		this.emit( 'initialized' );
	}.bind( this ) ).fail( function() {
		this.initialized = true;
	}.bind( this ) );
};

OO.inheritClass( ext.simpleBlogPage.ui.widget.BlogSelector, OO.ui.ComboBoxInputWidget );

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.loadOptions = function() {
	var deferred = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/simpleblogpage/v1/helper/root_pages',
		method: 'GET'
	} ).done( function( data ) {
		deferred.resolve( data );
	} ).fail( function( xhr, s, e ) {
		console.error( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : e );
		deferred.reject();
	} );
	return deferred;
};

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.setValue = function( value ) {
	if ( value && this.optionMapping && this.optionMapping.hasOwnProperty( value ) ) {
		value = this.optionMapping[value];
	}
	ext.simpleBlogPage.ui.widget.BlogSelector.super.prototype.setValue.call( this, value );
};

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.getValue = function() {
	const value = ext.simpleBlogPage.ui.widget.BlogSelector.super.prototype.getValue.call( this );
	if ( this.optionMappingReverse && this.optionMappingReverse.hasOwnProperty( value ) ) {
		return { fromOption: true, value: this.optionMappingReverse[value] };
	}
	return { fromOption: false, value: value };
};

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.selectUserBlog = function() {
	if ( this.userBlog ) {
		this.setValue( this.userBlog );
	}
};
