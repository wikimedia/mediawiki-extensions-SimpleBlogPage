ext.simpleBlogPage.ui.widget.BlogSelector = function( cfg ) {
	cfg = cfg || {};
	ext.simpleBlogPage.ui.widget.BlogSelector.super.call( this, cfg );
	this.optionValues = {};
	this.optionValuesReverse = {};
	this.initialized = false;

	this.loadOptions().done( function( options ) {
		this.menu.clearItems();
		this.optionValues = options;
		for ( var dbkey in options ) {
			if ( !options.hasOwnProperty( dbkey ) ) {
				continue;
			}
			this.optionValuesReverse[options[dbkey]] = dbkey;
			const title = options[dbkey];
			const item = new OO.ui.MenuOptionWidget( {
				data: title,
				label: title
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
	if ( value && this.optionValues && this.optionValues.hasOwnProperty( value ) ) {
		value = this.optionValues[value];
	}
	ext.simpleBlogPage.ui.widget.BlogSelector.parent.prototype.setValue.call( this, value );
};

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.getValue = function() {
	if ( !this.initialized ) {
		return '';
	}
	console.log( "IIII", this.optionValuesReverse );
	const value = ext.simpleBlogPage.ui.widget.BlogSelector.parent.prototype.getValue.call( this );
	if ( this.optionValuesReverse.hasOwnProperty( value ) ) {
		return this.optionValuesReverse[value];
	}
	return value;
};
