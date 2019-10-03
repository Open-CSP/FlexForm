<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WSForm' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WSForm'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WSFormAlias'] = __DIR__ . '/WSForm.i18n.alias.php';
	$wgExtensionMessagesFiles['WSFormMagic'] = __DIR__ . '/WSForm.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for WSForm extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WSForm extension requires MediaWiki 1.25+' );
}
