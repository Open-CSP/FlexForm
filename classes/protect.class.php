<?php
/**
 * Created by  : Designburo.nl
 * Filename    : protect.class.php
 * Description :
 * Date        : 08/10/2020
 * Time        : 08:15
 */

namespace wsform\protect;


class protect {

	protected static $method = 'aes-128-ctr'; // default cipher method if none supplied
	private static $key;

	protected static function iv_bytes() {
		return openssl_cipher_iv_length( self::$method );
	}

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
				die( __METHOD__ . ": unrecognised cipher method: {$method}");
			}
		}
	}

	public static function encrypt( $data )	{
		$iv = openssl_random_pseudo_bytes( self::iv_bytes() );
		return bin2hex( $iv ) . openssl_encrypt( $data, self::$method, self::$key, 0, $iv );
	}

	// decrypt encrypted string
	public static function decrypt( $data )	{
		$iv_strlen = 2  * self::iv_bytes();
		if(preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $data, $regs)) {
			list(, $iv, $crypted_string) = $regs;
			if(ctype_xdigit($iv) && strlen($iv) % 2 == 0) {
				return openssl_decrypt( $crypted_string, self::$method, self::$key, 0, hex2bin( $iv ) );
			}
		}
		return false; // failed to decrypt
	}

	/**
	 * @param $value String to purify html
	 *
	 * @return string purified html
	 */
	public static function purify( $value ) {
		if( \wsform\wsform::$secure ) {
			global $IP;
			require_once( $IP . '/extensions/WSForm/modules/htmlpurifier/library/HTMLPurifier.auto.php' );
			$config = \HTMLPurifier_Config::createDefault();
			$purifier = new \HTMLPurifier($config);
			return $purifier->purify( $value );
		} else return $value;
	}


}