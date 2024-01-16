<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : wsSecurity.php
 * Description :
 * Date        : 28-12-2021
 * Time        : 12:41
 */

namespace FlexForm\Processors\Security;

use FlexForm\Core\Config;
use FlexForm\Core\Core;
use FlexForm\Core\Debug;
use FlexForm\Core\Protect;
use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Utilities\General;
use FlexForm\Processors;
use FlexForm\FlexFormException;

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
	public static function getRemoveList() : array {
		return self::$removeList;
	}

	public static function getFormId() {
		return self::$formId;
	}

	/**
	 * Return decrypted form value and parameters to their original state
	 *
	 * @return true
	 * @throws FlexFormException
	 */
	public static function resolvePosts() : bool {
		//$checkSumKey = Config::getConfigVariable( 'sec_key') === null ? false : Config::getConfigVariable( 'sec_key');
		$crypt = new Protect();
		try {
			$crypt::setCrypt();
		} catch ( FlexFormException $exception ) {
			throw new FlexFormException(
				$exception->getMessage(),
				0,
				$exception
			);
		}
		$checksum     = false;
		$showOnSelect = false;
		$formId       = General::getPostString( 'formid' );
		if ( $formId !== false ) {
			unset( $_POST['formid'] );
		}

		try {
			foreach ( $_POST as $k => $v ) {
				if ( $crypt::decrypt( $k ) === 'checksum' ) {
					$checksum = unserialize( $crypt::decrypt( $v ) );
					unset( $_POST[$k] );
				}
				if ( $crypt::decrypt( $k ) === 'showonselect' ) {
					$showOnSelect = true;
					unset( $_POST[$k] );
				}
			}
		} catch ( FlexFormException $exception ) {
			throw new FlexFormException(
				$exception->getMessage(),
				0,
				$exception
			);
		}

		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Decrypt checksum',
				$checksum
			);
		}

		if ( $checksum === false && $formId !== false ) {
			throw new FlexFormException( wfMessage( 'flexform-secure-not' ) );
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
						if ( $newK === false || $newV === false || $tmpName !== $secure['value'] ) {
							throw new FlexFormException( wfMessage( 'flexform-secure-fields-incomplete' ) );
						}
					} catch ( FlexFormException $exception ) {
						throw new FlexFormException(
							$exception->getMessage(),
							0,
							$exception
						);
					}
					$delMe = $secure['name'];
					unset( $_POST[$delMe] );
					self::$removeList[] = $newK;
					if ( substr(
							 $newK,
							 -2,
							 2
						 ) === '[]' ) {
						//echo "okokoko";
						$newK           = str_replace(
							'[]',
							'',
							$newK
						);
						$_POST[$newK][] = $newV;
					} else {
						$_POST[$newK] = $newV;
					}
				} elseif ( $showOnSelect ) {
					continue;
				} else {
					throw new FlexFormException( wfMessage( 'flexform-secure-fields-incomplete' ) );
				}
			}
		}
		self::$checksum = $checksum;
		self::$formId   = $formId;
		Core::setShowOnSelectActive();

		return true;
	}

	/**
	 * @param array $values
	 *
	 * @return void
	 */
	private static function cleanValues( array &$values ) {
		foreach ( $values as $key => $value ) {
			if ( !Processors\Definitions::isFlexFormSystemField( $key ) ) {
				if ( is_array( $value ) ) {
					self::cleanValues( $value );
				} else {
					$values[$key] = self::cleanHTML( $value, $key );
				}
			}
		}
	}

	/**
	 * return nothing
	 */
	public static function cleanPosts() {
		self::cleanValues( $_POST );
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
	 * @return string|array
	 */
	public static function getHTMLType( string $name ) {
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
	 * @param mixed $value
	 *
	 * @return mixed cleaned text
	 */
	public static function cleanBraces( $value ) {
		return $value;
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