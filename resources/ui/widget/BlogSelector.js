ext.simpleBlogPage.ui.widget.BlogSelector = function( cfg ) {
	cfg = cfg || {};
	cfg.namespaces = [ 1502 ];
	cfg.mustExist = false;
	cfg.contentModels = [ 'blog_root' ];
	cfg.contentPagesOnly = false;
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

ext.simpleBlogPage.ui.widget.BlogSelector.prototype.setValue = function ( item ) {
	if ( !( item instanceof OO.ui.MenuOptionWidget ) ) {
		if ( !item ) {
			return ext.simpleBlogPage.ui.widget.BlogSelector.parent.prototype.setValue.call( this, '' );
		}
		// Strip namespace
		item = item.replace( /^[^:]+:/, '' );
		ext.simpleBlogPage.ui.widget.BlogSelector.parent.prototype.setValue.call( this, item );
	} else {
		OOJSPlus.ui.widget.TitleInputWidget.parent.prototype.setValue.call( this, item.getData().title );
		this.selectedTitle = item.getData();
	}
};