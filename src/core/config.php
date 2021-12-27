<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : config.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 12:48
 */

namespace wsform\core;

use \MediaWiki\MediaWikiServices;
use wsform\WSFormException;

class config {

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
		if ( isset( self::$WSFormConfig[$var] ) ) {
			return self::$WSFormConfig[$var];
		} else {
			return null;
		}
	}

}