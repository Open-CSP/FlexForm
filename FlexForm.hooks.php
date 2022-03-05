<?php
# @Author: Sen-Sai
# @Date:   15-05-2018
# @Last modified by:   Charlot
# @Last modified time: 04-07-2018 -- 10:03:36
# @License: Mine
# @Copyright: 2018

use MediaWiki\MediaWikiServices;
use FlexForm\Core\Core;
use FlexForm\Render\TagHooks;
use FlexForm\Render\Validate;
use FlexForm\FlexFormException;
use Wikimedia\Services\NoSuchServiceException;

/**
 * Class FlexFormHooks
 *
 * Hooks for FlexForm extension
 *
 * @author Sen-Sai
 */
class FlexFormHooks {
	/**
	 * List that returns an array of all FlexForm hooks
	 *
	 * @return array with all FlexForm hooks
	 */
	public static function availableHooks() {
		$data = array(
			'wsform',
			'wsfield',
			'wsfieldset',
			'wslegend',
			'wslabel',
			'wsselect',
			'wstoken',
			'wstoken2',
			'wsedit',
			'wscreate',
			'wsemail',
			'extension',
			'wsinstance'
		);

		return $data;
	}


	/**
	 * Implements AdminLinks hook from Extension:Admin_Links.
	 *
	 * @param ALTree &$adminLinksTree
	 *
	 * @return bool
	 */
	public static function addToAdminLinks( ALTree &$adminLinksTree ) {
		global $wgScript;
		$wsSection = $adminLinksTree->getSection( 'WikiBase Solutions' );
		if ( is_null( $wsSection ) ) {
			$section = new ALSection( 'WikiBase Solutions' );
			$adminLinksTree->addSection(
				$section,
				wfMessage( 'adminlinks_general' )->text()
			);
			$wsSection = $adminLinksTree->getSection( 'WikiBase Solutions' );
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}

		$extensionsRow = $wsSection->getRow( 'extensions' );

		if ( is_null( $extensionsRow ) ) {
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}

		$realUrl = str_replace(
			'/index.php',
			'',
			$wgScript
		);
		$extensionsRow->addItem(
			ALItem::newFromExternalLink(
				$realUrl . '/index.php/Special:FlexForm/Docs',
				'FlexForm documentation'
			)
		);

		return true;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 *
	 * @return void
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModules( [ 'ext.FlexForm.showOnSelect.script', 'ext.wsForm.ajax.scripts' ] );
		$out->addModuleStyles( [ 'ext.FlexForm.Instance.styles', 'ext.wsForm.general.styles' ] );
	}

	/**
	 * MediaWiki hook when FlexForm extension is initiated
	 *
	 * @param Parser $parser Sets a list of all FlexForm hooks
	 *
	 * @throws MWException
	 * @throws FlexFormException
	 * @throws NoSuchServiceException
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		if ( ! \FlexForm\Core\Config::getConfigStatus() ) {
			\FlexForm\Core\Config::setConfigFromMW();
		}
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders'] = [];
		$wgFlexFormConfig['loaders']['css'] = [];
		$wgFlexFormConfig['loaders']['javascript'] = [];
		$wgFlexFormConfig['loaders']['jsconfigvars'] = [];

		$tagHooks = new TagHooks( MediaWikiServices::getInstance()->getService( 'FlexForm.ThemeStore' ) );

		$parser->setHook( 'wsform',
						  [
							  $tagHooks,
							  'renderForm'
						  ] );
		$parser->setHook( 'wsfield',
						  [
							  $tagHooks,
							  'renderField'
						  ] );
		$parser->setHook( 'wsfieldset',
						  [
							  $tagHooks,
							  'renderFieldset'
						  ] );
		$parser->setHook( 'wslegend',
						  [
							  $tagHooks,
							  'renderLegend'
						  ] );
		$parser->setHook( 'wslabel',
						  [
							  $tagHooks,
							  'renderLabel'
						  ] );
		$parser->setHook( 'wsselect',
						  [
							  $tagHooks,
							  'renderSelect'
						  ] );
		$parser->setHook( 'wstoken',
						  [
							  $tagHooks,
							  'renderToken'
						  ] );
		$parser->setHook( 'wsedit',
						  [
							  $tagHooks,
							  'renderEdit'
						  ] );
		$parser->setHook( 'wscreate',
						  [
							  $tagHooks,
							  'renderCreate'
						  ] );
		$parser->setHook( 'wsemail',
						  [
							  $tagHooks,
							  'renderEmail'
						  ] );
		$parser->setHook( 'wsinstance',
						  [
							  $tagHooks,
							  'renderInstance'
						  ] );
		$parser->setHook( '_form',
						  [
							  $tagHooks,
							  'renderForm'
						  ] );
		$parser->setHook( '_input',
						  [
							  $tagHooks,
							  'renderField'
						  ] );
		$parser->setHook( '_fieldset',
						  [
							  $tagHooks,
							  'renderFieldset'
						  ] );
		$parser->setHook( '_legend',
						  [
							  $tagHooks,
							  'renderLegend'
						  ] );
		$parser->setHook( '_label',
						  [
							  $tagHooks,
							  'renderLabel'
						  ] );
		$parser->setHook( '_select',
						  [
							  $tagHooks,
							  'renderSelect'
						  ] );
		$parser->setHook( '_token',
						  [
							  $tagHooks,
							  'renderToken'
						  ] );
		$parser->setHook( '_edit',
						  [
							  $tagHooks,
							  'renderEdit'
						  ] );
		$parser->setHook( '_create',
						  [
							  $tagHooks,
							  'renderCreate'
						  ] );
		$parser->setHook( '_email',
						  [
							  $tagHooks,
							  'renderEmail'
						  ] );
		$parser->setHook( '_instance',
						  [
							  $tagHooks,
							  'renderInstance'
						  ] );
	}
}
