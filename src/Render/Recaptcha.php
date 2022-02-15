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
class Recaptcha {
	// TODO: Add to mwapi !!
	public static $rc_site_key = '';
	public static $rc_secret_key = '';


	public static function loadSettings() {
		global $IP;
		$serverName='';
		include( $IP . '/extensions/FlexForm/config/config.php' );
		if( isset( $config['rc_site_key'] ) && isset( $config['rc_secret_key'] ) ) {
			self::$rc_site_key   = $config['rc_site_key'];
			self::$rc_secret_key = $config['rc_secret_key'];
		}
	}

	/**
	 * @brief Load reCaptcha JavaScript
	 *
	 * @return string Rendered HTML
	 */
	public static function render() {
		self::loadSettings();
		if( self::$rc_site_key === '' || self::$rc_secret_key === false ) return false;
		$ret = '<script src="https://www.google.com/recaptcha/api.js?render=' . self::$rc_site_key . '"></script> ';
		return $ret;
	}

}