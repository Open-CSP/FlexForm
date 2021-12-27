<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : protect.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 13:01
 */

namespace WSForm\Core;

use WSForm\WSFormException;

class protect {

	protected static $method = 'aes-128-ctr'; // default cipher method if none supplied
	private static $key;

	protected static function iv_bytes() {
		return openssl_cipher_iv_length( self::$method );
	}

	/**
	 * @throws WSFormException
	 */
	public static function setCrypt( $key = false, $method = false ) {
		if( !$key ) {
			$key = php_uname(); // default encryption key if none supplied
		}
		if( ctype_print ($key )) {
			// convert ASCII keys to binary format
			self::$key = openssl_digest( $key, 'SHA256', true );
		} else {
			self::$key = $key;
		}
		if( $method ) {
			if( in_array( strtolower( $method ), openssl_get_cipher_methods() ) ) {
				self::$method = $method;
			} else {
				throw new WSFormException( __METHOD__ . ": unrecognised cipher method: {$method}", 1 );
			}
		}
	}

	public static function encrypt( $data )	{
		$iv = openssl_random_pseudo_bytes( self::iv_bytes() );
		return bin2hex( $iv ) . openssl_encrypt( $data, self::$method, self::$key, 0, $iv );
	}

	// decrypt encrypted string

	/**
	 * @throws WSFormException
	 */
	public static function decrypt( $data )	{
		$iv_strlen = 2  * self::iv_bytes();
		if(preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $data, $regs)) {
			list(, $iv, $crypted_string) = $regs;
			if(ctype_xdigit($iv) && strlen($iv) % 2 == 0) {
				return openssl_decrypt( $crypted_string, self::$method, self::$key, 0, hex2bin( $iv ) );
			}
		}
		throw new WSFormException( "failed to decrypt", 1 );
		//return false; // failed to decrypt
	}

	/**
	 * @param $value String to purify html
	 * @param $clean Array|String type
	 * @param $custom String custom purify
	 *
	 * @return string purified html
	 */
	public static function purify( $value, $clean = "default", $secure = false ) {
		if( is_array( $clean ) ) {
			$custom = $clean[1];
			$clean = $clean[0];
		} else $custom = false;
		if( $secure === false ) {
			return $value;
		}
		if( $clean === '' ) $clean = 'default';
		if( $clean === "all" ) return $value;
		if( $secure ) {
			global $IP;
			require_once( $IP . '/extensions/WSForm/modules/htmlpurifier/library/HTMLPurifier.auto.php' );
			$config = \HTMLPurifier_Config::createDefault();
			//
			switch( $clean ) {
				case "nohtml":
					$config->set('HTML.Allowed', '');
					break;
				case "custom":
					if( $custom !== false ){
						$config->set('HTML.Allowed', $custom); // e.g. 'p,ul[style],ol,li'
					}
					break;
				case "default" :
					$config->set('HTML.Allowed', 'nowiki');
				default:
					break;
			}
			$def = $config->getHTMLDefinition(true);
			$def->addElement('nowiki',  'Inline', 'Inline', 'Common');

			$purifier = new \HTMLPurifier($config);
			return $purifier->purify( $value );
		} else return $value;
	}


}