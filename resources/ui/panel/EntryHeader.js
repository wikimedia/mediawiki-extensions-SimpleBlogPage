ext.simpleBlogPage.ui.panel.EntryHeader = function EntryHeader( config ) {
	config = config || {};
	config.expanded = false;
	ext.simpleBlogPage.ui.panel.EntryHeader.parent.call( this, config );

	this.isNative = config.native || false;
	this.$element.addClass( 'blog-entry-header' );

	this.render( config );
};

OO.inheritClass( ext.simpleBlogPage.ui.panel.EntryHeader, OO.ui.PanelLayout );

ext.simpleBlogPage.ui.panel.EntryHeader.prototype.render = function( config ) {
	this.contribution = new OOJSPlus.ui.widget.ContributionWidget( config.userTimestamp, { user_name: config.author } );
	if ( !this.isNative ) {
		const entryTitle = mw.Title.newFromText( config.entryPage );
		const $targetAnchor = $( '<a>' )
			.attr( 'href', entryTitle.getUrl( { returnto: mw.config.get( 'wgPageName' ) } ) )
			.text( config.name )
			.addClass( 'blog-entry-name' );
		this.$element.addClass( 'non-native' );
		this.$element.append( $targetAnchor );
	}

	this.$element.append( this.contribution.$element );
};
