<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : FlexForm
 * Filename    : recaptcha.class.php
 * Description :
 * Date        : 28/10/2019
 * Time        : 20:54
 */

namespace FlexForm\Render;

// TODO: Cleanup and move to theme
use FlexForm\Core\Config;

class Recaptcha {
	// TODO: Add to mwapi !!
	public static $rc_site_key = '';
	public static $rc_secret_key = '';

	/**
	 * @return void
	 */
	public static function loadSettings() {
		self::$rc_site_key = Config::getConfigVariable( 'rc_site_key' );
		self::$rc_secret_key = Config::getConfigVariable( 'rc_secret_key' );
		if ( empty( self::$rc_site_key ) ) {
			self::$rc_site_key = null;
		}
		if ( empty( self::$rc_secret_key ) ) {
			self::$rc_secret_key = null;
		}
	}

	/**
	 * @return false|string
	 */
	public static function render() {
		self::loadSettings();
		if ( self::$rc_site_key === null || self::$rc_secret_key === null ) {
			return false;
		}
		$ret = '<script src="https://www.google.com/recaptcha/api.js?render=' . self::$rc_site_key . '"></script> ';
		return $ret;
	}

}