ext.simpleBlogPage.ui.widget.BlogSelector = function( cfg ) {
	cfg = cfg || {};
	cfg.namespaces = [ 1502 ];
	cfg.mustExist = false;
	cfg.contentModels = [ 'blog-root' ];
	ext.simpleBlogPage.ui.widget.BlogSelector.super.call( this, cfg );
};

OO.inheritClass( ext.simpleBlogPage.ui.widget.BlogSelector, OOJSPlus.ui.widget.TitleInputWidget );

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.getValue = function() {
	const title = this.getMWTitle();
	if ( title ) {
		return title.getMainText();
	}
	return this.getRawValue();
};