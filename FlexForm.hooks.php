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
			$wsSection     = $adminLinksTree->getSection( 'WikiBase Solutions' );
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
				$realUrl . '/index.php/Special:FlexForm',
				'FlexForm'
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
	public static function onBeforePageOutput( OutputPage $out, Skin $skin ) {

		$out->addModules( [
			'ext.FlexForm.showOnSelect.script',
			'ext.wsForm.ajax.scripts'
		] );
		$out->addModuleStyles( [
			'ext.FlexForm.Instance.styles',
			'ext.wsForm.general.styles'
		] );
	}

	/**
	 * @param $out
	 *
	 * @return true
	 */
	public static function onAfterFinalPageOutput( $out ) {
		global $wgFlexFormConfig;
		$scripts    = array_unique( Core::getJavaScriptToBeIncluded() );
		$scriptTags = array_unique( Core::getJavaScriptTagsToBeIncluded() );
		$csss       = array_unique( Core::getCSSToBeIncluded() );
		$cssTags    = array_unique( Core::getCSSTagsToBeIncluded() );
		$jsconfigs  = Core::getJavaScriptConfigToBeAdded();
		$jsTags     = '';
		$cssTagsOut = '';
		$jsOut      = '';
		$csOut      = '';
		if ( ! empty( $scriptTags ) ) {
			foreach ( $scriptTags as $scriptTag ) {
				$jsTags .= \Xml::tags(
						'script',
						[
							'type'    => 'text/javascript',
							'charset' => 'UTF-8',
							'src'     => $scriptTag
						],
						''
					) . "\n";
			}

			Core::cleanJavaScriptTagsList();
		}

		if ( ! empty( $cssTags ) ) {
			foreach ( $cssTags as $cssTag ) {
				$cssTagsOut .= \Xml::tags(
						'link',
						[
							'rel'  => 'stylesheet',
							'href' => $cssTag
						],
						''
					) . "\n";
			}

			Core::cleanJavaScriptTagsList();
		}

		if ( ! empty( $jsconfigs ) ) {
			foreach ( $jsconfigs as $name => $jsConfig ) {
				$jsOut .= 'var ' . $name . ' = ' . json_encode( $jsConfig ) . "\n";
			}

			Core::cleanJavaScriptConfigVars();
		}

		if ( ! empty( $scripts ) ) {
			foreach ( $scripts as $js ) {
				$jsOut .= $js . "\n";
			}

			Core::cleanJavaScriptList();
		}

		if ( ! empty( $csss ) ) {
			foreach ( $csss as $css ) {
				$csOut .= $css . "\n";
			}

			Core::cleanCSSList();
		}

		$out = ob_get_clean();
		$out .= $cssTagsOut . $jsTags;
		if ( ! empty( $csOut ) ) {
			$out .= "<style>\n" . $csOut . "\n</style>\n";
		}
		if ( ! empty( $jsOut ) ) {
			$out .= "<script>\n" . $jsOut . "\n</script>\n";
		}
		ob_start();
		echo $out;

		return true;
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
		$wgFlexFormConfig['loaders']                  = [];
		$wgFlexFormConfig['loaders']['css']           = [];
		$wgFlexFormConfig['loaders']['javascript']    = [];
		$wgFlexFormConfig['loaders']['jsconfigvars']  = [];
		$wgFlexFormConfig['loaders']['javascripttag'] = [];
		$wgFlexFormConfig['loaders']['csstag']        = [];

		$formTags = [ 'wsform', '_form', 'form' ];

		$tagHooks = new TagHooks( MediaWikiServices::getInstance()->getService( 'FlexForm.ThemeStore' ) );

		$parser->setHook( 'wsform',
				[$tagHooks, 'renderForm']
			 );

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
		$parser->setHook( '_createuser',
			[
				$tagHooks,
				'renderCreateUser'
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
		$parser->setHook( 'form',
			[
				$tagHooks,
				'renderForm'
			] );
		$parser->setHook( 'input',
			[
				$tagHooks,
				'renderField'
			] );
		$parser->setHook( 'fieldset',
			[
				$tagHooks,
				'renderFieldset'
			] );
		$parser->setHook( 'legend',
			[
				$tagHooks,
				'renderLegend'
			] );
		$parser->setHook( 'label',
			[
				$tagHooks,
				'renderLabel'
			] );
		$parser->setHook( 'select',
			[
				$tagHooks,
				'renderSelect'
			] );
	}
}
