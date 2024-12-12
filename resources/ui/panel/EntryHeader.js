ext.simpleBlogPage.ui.panel.EntryHeader = function EntryHeader( config, wikiTitle ) {
	config = config || {};
	config.expanded = false;
	ext.simpleBlogPage.ui.panel.EntryHeader.parent.call( this, config );

	this.isNative = config.native || false;
	this.wikiTitle = wikiTitle;
	this.$element.addClass( 'blog-entry-header' );

	this.render( config );
};

OO.inheritClass( ext.simpleBlogPage.ui.panel.EntryHeader, OO.ui.PanelLayout );

ext.simpleBlogPage.ui.panel.EntryHeader.prototype.render = function( config ) {
	this.$title = $( '<div>' ).addClass( 'blog-entry-title' );
	this.$contribution = $( '<div>' ).addClass( 'blog-entry-contribution' );
	this.$actions = $( '<div>' ).addClass( 'blog-entry-actions' );
	this.$element.append( this.$title, this.$contribution, this.$actions );

	this.contribution = new OOJSPlus.ui.widget.ContributionWidget( config.userTimestamp, { user_name: config.author } );
	this.$contribution.append( this.contribution.$element );
	if ( !this.isNative ) {
		this.$element.addClass( 'non-native' );
		const $targetAnchor = $( '<a>' )
			.attr( 'href', this.wikiTitle.getUrl( { returnto: mw.config.get( 'wgPageName' ) } ) )
			.text( config.name )
			.addClass( 'blog-entry-name' );
		this.$title.append( $targetAnchor );
		this.$title.append( $( '<div>' )
			.addClass( 'blog-entry-blog' )
			.append(
				$( '<span>' ).html(
					mw.message( 'simpleblogpage-entry-blog-root-link', config.rootPage, config.root ).parse()
				)
			)
		);
	}
};
