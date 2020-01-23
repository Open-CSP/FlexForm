<?php
/**
 * Created by  : Designburo.nl
 * Project     : wsformWikiBaseNL
 * Filename    : recaptcha.class.php
 * Description :
 * Date        : 28/10/2019
 * Time        : 20:54
 */

namespace wsform\recaptcha;


class render {


	// TODO: Add to mwapi !!
	public static $rc_site_key = '';
	public static $rc_secret_key = '';


	public static function loadSettings() {
		global $IP;
		$serverName='';
		include( $IP . '/extensions/WSForm/config/config.php' );
		self::$rc_site_key = $config['rc_site_key'];
		self::$rc_secret_key = $config['rc_secret_key'];
	}

	/**
	 * @brief Render Label Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_reCaptcha() {
		self::loadSettings();
		if( self::$rc_site_key === '' || self::$rc_secret_key === false ) return false;
		$ret = '<script src="https://www.google.com/recaptcha/api.js?render=' . self::$rc_site_key . '"></script> ';
		return $ret;
	}

}