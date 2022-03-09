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