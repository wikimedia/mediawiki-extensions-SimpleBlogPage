ext.simpleBlogPage.ui.panel.EntryHeader = function EntryHeader( config, wikiTitle, forcedBlog, watchInfo ) {
	config = config || {};
	config.expanded = false;
	ext.simpleBlogPage.ui.panel.EntryHeader.parent.call( this, config );

	this.isNative = config.native || false;
	this.wikiTitle = wikiTitle;
	this.isForcedBlog = forcedBlog || false;
	this.watchInfo = watchInfo || {};
	this.$element.addClass( 'blog-entry-header' );

	this.render( config );
};

OO.inheritClass( ext.simpleBlogPage.ui.panel.EntryHeader, OO.ui.PanelLayout );

ext.simpleBlogPage.ui.panel.EntryHeader.prototype.render = function( config ) {
	this.$title = $( '<div>' ).addClass( 'blog-entry-title' );
	this.$contribution = $( '<div>' ).addClass( 'blog-entry-contribution' );
	this.$actions = $( '<div>' ).addClass( 'blog-entry-actions' );
	this.$element.append( this.$title, this.$contribution, this.$actions );
	this.$headerAnchor = $( '<h2>' );

	this.contribution = new OOJSPlus.ui.widget.ContributionWidget( config.userTimestamp, { user_name: config.author } );
	this.$contribution.append( this.contribution.$element );
	if ( !this.isNative ) {
		this.$element.addClass( 'non-native' );
		const $targetAnchor = $( '<a>' )
			.attr( 'href', this.wikiTitle.getUrl() )
			.text( config.name )
			.addClass( 'blog-entry-name' );
		this.$headerAnchor.append( $targetAnchor );
		this.$title.append( this.$headerAnchor );
		if ( !this.isForcedBlog ) {
			// Blogs entries viewed come from mixed blogs
			const $userBlogPill = $( '<span>' )
				.addClass( 'blog-pill' ).text( mw.msg( 'simpleblogpage-blog-type-user' ) );
			this.$title.append( $( '<div>' )
				.addClass( 'blog-entry-blog' )
				.append(
					$( '<span>' ).html(
						mw.message( 'simpleblogpage-entry-blog-root-link', config.rootPage, config.root ).parse()
					),
					config.type === 'user' ? $userBlogPill : ''
				)
			);
		}
	}

	if ( this.watchInfo.canWatch ) {
		this.watchButton = new OO.ui.ButtonWidget( {
			icon: this.watchInfo.isWatching ? 'unStar' : 'star',
			title: mw.msg( this.watchInfo.isWatching ? 'simpleblogpage-unwatch' : 'simpleblogpage-watch' ),
			framed: false,
			classes: [ 'simpleblog-watch' ],
			flags: [ 'progressive' ]
		} );
		this.watchButton.connect( this, { click: 'onWatchClick' } );
		this.watchButton.$element.insertAfter( this.$headerAnchor );
	}
};

ext.simpleBlogPage.ui.panel.EntryHeader.prototype.onWatchClick = function() {
	const unwatch = this.watchInfo.isWatching;
	const api = new mw.Api();
	this.watchButton.setDisabled( true );
	api.postWithToken( 'watch', {
		action: 'watch',
		titles: this.wikiTitle.getPrefixedText(),
		unwatch: unwatch ? '' : '1'
	} ).done( function() {
		this.watchButton.setIcon( unwatch ? 'star' : 'unStar' );
		this.watchButton.setTitle( mw.msg( unwatch ? 'simpleblogpage-watch' : 'simpleblogpage-unwatch' ) );
		this.watchInfo.isWatching = !unwatch;
	}.bind( this ) ).always( function() {
		this.watchButton.setDisabled( false );
	}.bind( this ) );
};
