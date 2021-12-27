<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : config.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 12:48
 */

namespace WSForm\Core;

use \MediaWiki\MediaWikiServices;
use WSForm\WSFormException;

class Config {

	private static $WSFormConfig;
	private static $WSConfigStatus = false;


	/**
	 * setConfig
	 *
	 * @throws WSFormException
	 */
	public static function setConfigFromMW() {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( $config->has( 'WSFormConfig' ) ) {
			self::$WSFormConfig = $config->get( 'WSFormConfig' );
			self::checkConfig();
			self::$WSConfigStatus = true;
		} else throw new WSFormException( 'No config set', 1 );
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
		return self::$WSFormConfig[$var] ?? null;
	}

	/**
	 * Add additional checks and default to loaded config
	 */
	private static function checkConfig(){
		global $IP, $wgCanonicalServer;
		$default_dir = $IP . "/extensions/WSForm/uploads/";
		$filePathFromConfig = self::getConfigVariable( 'file_temp_path' );
		$canonical = self::getConfigVariable( 'wgCanonicalServer' );
		if( is_null( $filePathFromConfig ) ) {
			if( !file_exists( $default_dir ) ) {
				mkdir( $default_dir, 0777 );
			}
			self::$WSFormConfig['file_temp_path'] = rtrim( $default_dir, '/' ) . '/';
		}
		if( is_null( $canonical ) ) {
			self::$WSFormConfig['wgCanonicalServer'] = rtrim( $wgCanonicalServer , '/' ) . '/';
		}
	}

}