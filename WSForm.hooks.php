<?php
# @Author: Sen-Sai
# @Date:   15-05-2018
# @Last modified by:   Charlot
# @Last modified time: 04-07-2018 -- 10:03:36
# @License: Mine
# @Copyright: 2018

//error_reporting( -1 );
//ini_set( 'display_errors', 1 );
use MediaWiki\MediaWikiServices;
use WSForm\Core\Core;
use WSForm\Render\Themes\WSForm\WSFormFieldRenderer;
use WSForm\Render\Validate;
use WSForm\WSFormException;


/**
 * Class WSFormHooks
 *
 * Hooks for WSForm extension
 *
 * @author Sen-Sai
 */
class WSFormHooks {
	/**
	 * List that returns an array of all WSForm hooks
	 *
	 * @return array with all WSForm hooks
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
	 * @return bool
	 */
	public static function addToAdminLinks( ALTree &$adminLinksTree ) {
        global $wgScript;
		$wsSection = $adminLinksTree->getSection( 'WikiBase Solutions' );
		if ( is_null( $wsSection ) ) {
			$section = new ALSection( 'WikiBase Solutions' );
			$adminLinksTree->addSection( $section, wfMessage( 'adminlinks_general' )->text() );
			$wsSection = $adminLinksTree->getSection( 'WikiBase Solutions' );
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}

		$extensionsRow = $wsSection->getRow( 'extensions' );

		if ( is_null( $extensionsRow) ) {
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}

		$realUrl = str_replace( '/index.php', '', $wgScript );
		$extensionsRow->addItem( ALItem::newFromExternalLink( $realUrl.'/index.php/Special:WSForm/Docs', 'WSForm documentation' ) );
		return true;
	}

    /**
     * MediaWiki hook when WSForm extension is initiated
     *
     * @param Parser $parser Sets a list of all WSForm hooks
     * @throws MWException
     */
	public static function onParserFirstCallInit( Parser &$parser ) {
		global $wgAbsoluteWikiPath, $IP;
		if( php_sapi_name() !== 'cli' ) {
			$serverName = strtolower( $_SERVER['SERVER_NAME'] );
		}

		/*
		 @Charlot: Do we still need this?

		include_once( 'classes/loader.php' );
		\wsform\WSClassLoader::register();
		*/

		// Load config settings
		if ( file_exists( __DIR__ . '/config/config.php' ) ) {
			include( __DIR__ . '/config/config.php' );

			if( isset( $config['sec'] ) && $config['sec'] === true ) {
				Core::$secure = true;
			}

			if( isset( $config['use-api-user-only'] ) && $config['use-api-user-only'] !== "yes" ) {
                Core::$runAsUser = true;
			}

			if( isset( $config['sec-key'] ) && !empty( $config['sec-key'] ) ) {
                Core::$checksumKey = $config['sec-key'];
			}

            Core::$wsConfig = $config;
		}

		$tagHooks = new TagHooks( MediaWikiServices::getInstance()->getService( 'WSForm.ThemeStore' ) );

		$parser->setHook( 'wsform', [$tagHooks, 'renderForm'] );
		$parser->setHook( 'wsfield', [$tagHooks, 'renderField'] );
		$parser->setHook( 'wsfieldset', [$tagHooks, 'renderFieldset'] );
		$parser->setHook( 'wslegend', [$tagHooks, 'renderLegend'] );
		$parser->setHook( 'wslabel', [$tagHooks, 'renderLabel'] );
		$parser->setHook( 'wsselect', [$tagHooks, 'renderSelect'] );
		$parser->setHook( 'wstoken', [$tagHooks, 'renderToken'] );
		$parser->setHook( 'wsedit', [$tagHooks, 'renderEdit'] );
		$parser->setHook( 'wscreate', [$tagHooks, 'renderCreate'] );
		$parser->setHook( 'wsemail', [$tagHooks, 'renderEmail'] );
		$parser->setHook( 'wsinstance', [$tagHooks, 'renderInstance'] );
	}
}
