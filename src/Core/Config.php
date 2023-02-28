<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : config.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 12:48
 */

namespace FlexForm\Core;

use \MediaWiki\MediaWikiServices;
use FlexForm\FlexFormException;

class Config {

	private static $FlexFormConfig;
	private static $WSConfigStatus = false;
	private static $debug = false;
	private static $secure = true;
	private static $filterInputTags = true;

	/**
	 * setConfig
	 *
	 * @throws FlexFormException
	 */
	public static function setConfigFromMW() {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( $config->has( 'FlexFormConfig' ) ) {
			self::$FlexFormConfig = $config->get( 'FlexFormConfig' );
			self::checkConfig();
			self::$WSConfigStatus = true;
			Core::$chkSums    = [];
			Core::$securityId = uniqid();
		} else {
			throw new FlexFormException(
				'No config set',
				1
			);
		}
	}

	/**
	 * @return bool
	 */
	public static function getConfigStatus() : bool {
		return self::$WSConfigStatus;
	}

	/**
	 * @param string $var
	 *
	 * @return mixed|null
	 */
	public static function getConfigVariable( string $var ) {
		return self::$FlexFormConfig[$var] ?? null;
	}

	/**
	 * @return bool
	 */
	public static function isDebug() {
		return self::$debug;
	}

	/**
	 * @return bool
	 */
	public static function isSecure() {
		return self::$secure;
	}

	/**
	 * @return bool
	 */
	public static function isFilterTags(): bool {
		return self::$filterInputTags;
	}

	private static function setDefaultLoadScriptPath(){
		global $IP;
		self::$FlexFormConfig['loadScriptPath'] = $IP . '/extensions/FlexForm/Modules/customJS/loadScripts/';
	}

	/**
	 * Add additional checks and default to loaded config
	 */
	private static function checkConfig() {
		global $IP, $wgCanonicalServer;
		$default_dir = $IP . "/extensions/FlexForm/uploads/";
		$filePathFromConfig = self::getConfigVariable( 'file_temp_path' );
		if ( self::getConfigVariable( 'debug' ) !== null ) {
			self::$debug = self::getConfigVariable( 'debug' );
		}
		if ( self::getConfigVariable( 'secure' ) !== null ) {
			self::$secure = self::getConfigVariable( 'secure' );
		}
		if ( self::getConfigVariable( 'filter_input_tags' ) !== null ) {
			self::$filterInputTags = self::getConfigVariable( 'filter_input_tags' );
		}
		// get a possible localsetting for loadscriptpath
		$loadScriptPath = self::getConfigVariable( 'loadScriptPath' );
		// set the loadscriptpath setting to default
		self::setDefaultLoadScriptPath();
		// if a localsetting was set, check if the path exist
		if ( $loadScriptPath !== null ) {
			$loadScriptPath = rtrim( $loadScriptPath, '/' ) . '/';
			if ( file_exists( self::getConfigVariable( 'loadScriptPath' ) ) ) {
				// if it exists, set the loadscriptpath setting
				self::$FlexFormConfig['loadScriptPath'] = $loadScriptPath;
			}
		}
		$canonical = self::getConfigVariable( 'wgCanonicalServer' );
		if ( is_null( $filePathFromConfig ) || $filePathFromConfig === '' ) {
			if ( ! file_exists( $default_dir ) ) {
				mkdir(
					$default_dir,
					0777
				);
			}
			self::$FlexFormConfig['file_temp_path'] = rtrim(
														$default_dir,
														'/'
													) . '/';
		}
		if ( is_null( $canonical ) ) {
			self::$FlexFormConfig['wgCanonicalServer'] = rtrim(
														   $wgCanonicalServer,
														   '/'
													   ) . '/';
		}
	}

}