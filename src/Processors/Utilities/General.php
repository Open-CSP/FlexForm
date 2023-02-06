<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : General.php
 * Description :
 * Date        : 28-12-2021
 * Time        : 12:38
 */

namespace FlexForm\Processors\Utilities;

use FlexForm\Processors\Security\wsSecurity;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Class wsUtilities
 * <p>General manipulation functions</p>
 *
 * @package FlexForm\Processors\Utilities
 */
class General {

	/**
	 * @param string $txt
	 *
	 * @return string
	 */
	public static function makeSpaceFromUnderscore( string $txt ) : string {
		return str_replace(
			"_",
			" ",
			$txt
		);
	}

	/**
	 * @param string $txt
	 *
	 * @return string
	 */
	public static function makeUnderscoreFromSpace( string $txt ) : string {
		return str_replace(
			" ",
			"_",
			$txt
		);
	}

	/**
	 * @param string $var
	 * @param array $json
	 *
	 * @return false|mixed
	 */
	public static function getJsonValue( string $var, array $json ) {
		if ( isset( $json[$var] ) && $json[$var] !== '' ) {
			return $json[$var];
		} else {
			return false;
		}
	}

	/**
	 * Check and get a $_POST value
	 *
	 * @param string $var $_POST value to check
	 * @param bool $clean to clean input
	 *
	 * @return bool|string  Returns false if not set or the value
	 */
	public static function getPostString( string $var, bool $clean = true ) {
		if ( isset( $_POST[$var] ) && $_POST[$var] !== "" ) {
			$template = $_POST[$var];
		} else {
			$template = false;
		}
		if ( $clean === true && $template !== false ) {
			$clean_html = wsSecurity::cleanHTML(
				$template,
				$var
			);

			return wsSecurity::cleanBraces( $clean_html );
		} else {
			return $template;
		}
	}

	/**
	 * @param string $var
	 * @param bool $check
	 * @param bool $clean
	 *
	 * @return bool|string bool when false else value of key
	 */
	public static function getGetString( string $var, bool $check = true, bool $clean = true ) {
		if ( $check ) {
			if ( isset( $_GET[$var] ) && $_GET[$var] !== "" ) {
				$value = $_GET[$var];
			} else {
				$value = false;
			}
		} else {
			if ( isset( $_GET[$var] ) ) {
				$value = $_GET[$var];
			} else {
				$value = false;
			}
		}
		global $IP;
		require_once $IP . '/extensions/FlexForm/Modules/htmlpurifier/library/HTMLPurifier.auto.php';
		if ( $clean === true && $value !== false ) {
			$config     = HTMLPurifier_Config::createDefault();
			$purifier   = new HTMLPurifier( $config );
			$clean_html = $purifier->purify( $value );
			$value   = wsSecurity::cleanBraces( $clean_html );
		}

		return $value;
	}

	/**
	 * Check is we are run from terminal
	 *
	 * @return bool
	 */
	public static function is_cli() : bool {
		if ( defined( 'STDIN' ) ) {
			return true;
		}

		if ( empty( $_SERVER['REMOTE_ADDR'] ) and ! isset( $_SERVER['HTTP_USER_AGENT'] ) and count(
																								 $_SERVER['argv']
																							 ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * @return int
	 */
	public static function MakeTitle() : int {
		return time();
	}

	/**
	 * Used for default value with e.g. checkboxes
	 */
	public static function handleDefaultValues() {
		foreach ( $_POST as $k => $v ) {
			if ( strpos(
					 $k,
					 'wsdefault_'
				 ) !== false ) {
				$tempVar = str_replace(
					'wsdefault_',
					'',
					$k
				);
				if ( ! isset( $_POST[$tempVar] ) ) {
					$_POST[$tempVar] = $v;
				}
				unset( $_POST[$k] );
			}
		}
	}

	/**
	 * @param mixed $var
	 *
	 * @return array|false
	 */
	public static function getPostArray( $var ) {
		if ( isset( $_POST[$var] ) && is_array( $_POST[$var] ) ) {
			$template = $_POST[$var];
		} else {
			$template = false;
		}

		return $template;
	}

	public static function get_string_between_until_last( $string, $start, $end ) {
		$string = " " . $string;
		$ini    = strpos(
			$string,
			$start
		);
		if ( $ini == 0 ) {
			return "";
		}
		$ini += strlen( $start );
		$len = strrpos(
				   $string,
				   $end,
				   $ini
			   ) - $ini;

		return substr(
			$string,
			$ini,
			$len
		);
	}

	public static function get_all_string_between( $string, $start, $end ) {
		$result = array();
		$string = " " . $string;
		$offset = 0;
		while ( true ) {
			$ini = strpos(
				$string,
				$start,
				$offset
			);
			if ( $ini == 0 ) {
				break;
			}
			$ini      += strlen( $start );
			$len      = strpos(
							$string,
							$end,
							$ini
						) - $ini;
			$result[] = substr(
				$string,
				$ini,
				$len
			);
			$offset   = $ini + $len;
		}

		return $result;
	}

	/**
	 * Experimental function to get a username from session
	 *
	 * @param bool $onlyName
	 *
	 * @return string
	 */
	public static function setSummary( $onlyName = false ) {
		//TODO: Still needs work as mwdb is not always a default
		$dbn = self::getPostString( 'mwdb' );
		if ( $dbn !== false && isset( $_COOKIE[$dbn . 'UserName'] ) ) {
			if ( $onlyName === true ) {
				return ( $_COOKIE[$dbn . 'UserName'] );
			} else {
				return ( '[[User:' . $_COOKIE[$dbn . 'UserName'] . ']]' );
			}
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];

			return ( 'Anon user: ' . $ip );
		}
	}
}