<?php

namespace MediaWiki\Extension\SimpleBlogPage\Integration\ConfigDefinition;

use BlueSpice\ConfigDefinition\BooleanSetting;
use BlueSpice\ConfigDefinition\IOverwriteGlobal;

class MainLinksBlog extends BooleanSetting implements IOverwriteGlobal {

	/**
	 * @return array
	 */
	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_SKINNING . '/SimpleBlogPage',
			static::MAIN_PATH_EXTENSION . '/SimpleBlogPage/' . static::FEATURE_SKINNING,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_FREE . '/SimpleBlogPage',
		];
	}

	/**
	 * @return string
	 */
	public function getLabelMessageKey() {
		return 'simpleblogpage-config-mainlinks-blog-label';
	}

	/**
	 * @return string
	 */
	public function getHelpMessageKey() {
		return 'simpleblogpage-config-mainlinks-blog-help';
	}

	/**
	 * @return string
	 */
	public function getGlobalName() {
		return 'wgSimpleBlogPageShowInMainLinks';
	}
}
