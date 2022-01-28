<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWWSForm
 * Filename    : wsSecurity.php
 * Description :
 * Date        : 28-12-2021
 * Time        : 12:41
 */

namespace WSForm\Processors\Security;

use WSForm\Core\Config;
use WSForm\Core\Core;
use WSForm\Core\Protect;
use WSForm\Core\HandleResponse;
use WSForm\Processors\Utilities\General;
use WSForm\Processors;
use WSForm\WSFormException;

class wsSecurity {

	private static $checksum = array();
	private static $formId;
	private static $removeList = array();

	public static function getChecksum() {
		return self::$checksum;
	}

	/**
	 * @return array
	 */
	public static function getRemoveList(): array {
		return self::$removeList;
	}

	public static function getFormId() {
		return self::$formId;
	}

	/**
	 * Return decrypted form value and parameters to their original state
	 *
	 * @return true
	 * @throws WSFormException
	 */
	public static function resolvePosts() : bool {

		//$checkSumKey = Config::getConfigVariable( 'sec_key') === null ? false : Config::getConfigVariable( 'sec_key');
		$crypt = new Protect();
		try {
			$crypt::setCrypt();
		} catch( WSFormException $exception ) {
			throw new WSFormException( $exception->getMessage(), 0, $exception );
		}
		$checksum = false;
		$showOnSelect = false;
		$formId   = General::getPostString( 'formid' );
		if ( $formId !== false ) {
			unset( $_POST['formid'] );
		}

		try {
			foreach ( $_POST as $k => $v ) {
				if ( $crypt::decrypt( $k ) === 'checksum' ) {
					$checksum = unserialize( $crypt::decrypt( $v ) );
					unset( $_POST[ $k ] );
				}
				if ( $crypt::decrypt( $k ) === 'showonselect' ) {
					$showOnSelect = true;
					unset( $_POST[$k] );
				}
			}
		} catch( WSFormException $exception ) {
			throw new WSFormException( $exception->getMessage(), 0, $exception );
		}

		if ( $checksum === false && $formId !== false ) {
			throw new WSFormException( wfMessage( 'wsform-secure-not' ) );

		}
		if ( isset( $checksum[$formId]['secure'] ) ) {
			foreach ( $checksum[$formId]['secure'] as $secure ) {
				$tmpName = General::getPostString(
					$secure['name'],
					false
				);
				if ( $tmpName !== false ) {
					try {
						$newK = $crypt::decrypt( $secure['name'] );
						$newV = $crypt::decrypt( $tmpName );
					} catch( WSFormException $exception ) {
						throw new WSFormException( $exception->getMessage(), 0, $exception );
					}
					$delMe = $secure['name'];
					unset( $_POST[$delMe] );
					self::$removeList[] = $newK;
					if ( substr( $newK, - 2, 2 ) === '[]' ) {
						//echo "okokoko";
						$newK             = str_replace( '[]', '', $newK );
						$_POST[ $newK ][] = $newV;
					} else {
						$_POST[ $newK ] = $newV;
					}
				} elseif( $showOnSelect ) {
					continue;
				}else {
					throw new WSFormException( wfMessage( 'wsform-secure-fields-incomplete' ) );
				}
			}
		}
		self::$checksum = $checksum;
		self::$formId   = $formId;
		Core::setShowOnSelectActive();
		return true;
	}



	/**
	 * return nothing
	 */
	public static function cleanPosts() {
		foreach ( $_POST as $k => $v ) {
			if ( ! Processors\Definitions::isWSFormSystemField( $k ) ) {
				if ( is_array( $v ) ) {
					$newArray = array();
					foreach ( $v as $multiple ) {
						$newArray[] = self::cleanHTML(
							$multiple,
							$k
						);
					}
					$_POST[$k] = $newArray;
				} else {
					$_POST[$k] = self::cleanHTML(
						$v,
						$k
					);
				}
			}
		}
	}

	/**
	 * @param string $var
	 * @param false|string $name
	 *
	 * @return string Purified text or $var;
	 */
	public static function cleanHTML( string $var, $name = false ) : string {
		global $securedVersion;
		if ( $securedVersion === false ) {
			return $var;
		}
		$html = '';
		if ( $name !== false ) {
			$html = self::getHTMLType( $name );
		}
		$pure = new protect();

		return $pure::purify(
			$var,
			$html,
			$securedVersion
		);
	}

	/**
	 * See if form variable has specific html instructions
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public static function getHTMLType( string $name ) : string {
		$formId = self::$formId;
		if ( is_null( self::$checksum ) ) {
			return "default";
		}
		if ( isset( self::$checksum[$formId][$name] ) ) {
			return self::$checksum[$formId][$name]['html'];
		} else {
			return "default";
		}
	}

	/**
	 * Remove all curly braces
	 *
	 * @param string $value
	 *
	 * @return string cleaned text
	 */
	public static function cleanBraces( string $value ) : string {
		global $wsuid;
		if ( $wsuid !== false && ! is_null( $wsuid ) ) {
			return $value;
		}

		//return $value;
		return preg_replace(
			"/\{{[^)]+\}}/",
			"",
			$value
		);
	}

	/**
	 * @param $var
	 *
	 * @return string
	 */
	public static function cleanUrl( $var ) : string {
		return urlencode( $var );
	}
}