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
	private static $rc_secret_key = '';

	public static $rc_enterprise_project = "";

	public static $rc_enterprise_siteKey = "";

	public static $rc_enterprise_apiKey = "";

	/**
	 * @return void
	 */
	public static function loadSettings() {
		$type = Config::getConfigVariable( 'rc_use' );
		if ( $type === "v3" ) {
			self::$rc_site_key = Config::getConfigVariable( 'rc_site_key' );
			self::$rc_secret_key = Config::getConfigVariable( 'rc_secret_key' );
			if ( empty( self::$rc_site_key ) ) {
				self::$rc_site_key = null;
			}
			if ( empty( self::$rc_secret_key ) ) {
				self::$rc_secret_key = null;
			}
		}
		if ( $type === "enterprise" ) {
			self::$rc_enterprise_project = Config::getConfigVariable( 'rce_project' );
			self::$rc_enterprise_siteKey  = Config::getConfigVariable( 'rce_site_key' );
			self::$rc_enterprise_apiKey = Config::getConfigVariable( 'rce_api_key' );

			if ( empty( self::$rc_enterprise_project ) ) {
				self::$rc_enterprise_project = null;
			}
			if ( empty( self::$rc_enterprise_siteKey ) ) {
				self::$rc_enterprise_siteKey = null;
			}
			if ( empty( self::$rc_enterprise_siteKey ) ) {
				self::$rc_enterprise_siteKey = null;
			}
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