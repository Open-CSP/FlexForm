<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : handleResponse.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 13:08
 */

namespace WSForm\Core;

class HandleResponse {

	private static $returnStatus = "ok";
	private static $returnData = array();
	private static $mwReturn = false;

	/**
	 * @var bool
	 */
	private static $apiAjax = false;

	/**
	 * @param string $status
	 */
	public static function setReturnStatus( string $status ) {
		self::$returnStatus = $status;
	}

	/**
	 * @return string
	 */
	public static function getReturnStatus(): string {
		return self::$returnStatus;
	}

	/**
	 * @param string $data
	 */
	public static function setReturnData( string $data ) {
		self::$returnData[] = $data;
	}

	/**
	 * @return array
	 */
	public static function getReturnData(): array {
		return self::$returnData;
	}

	/**
	 * @param string|bool $mwReturn
	 */
	public static function setMwReturn( $mwReturn ) {
		self::$mwReturn = $mwReturn;
	}

	/**
	 * @return string|bool
	 */
	public static function getMwReturn() {
		return self::$mwReturn;
	}

	/**
	 * Set global result handler to normal or Ajax
	 *
	 * @param mixed $identifier
	 */
	public static function setIdentifier( $identifier = false ) {
		if ( $identifier === 'ajax' ) {
			self::$apiAjax = true;
		}
	}

	/**
	 * @return bool
	 */
	public static function isAjax() : bool {
		return self::$apiAjax;
	}

	/**
	 * Function called to create the final return parameters in a consistent way.
	 *
	 * @param bool|string $type type of visual notice to show (error, warning, success, etc)
	 *
	 * @return array
	 */
	public static function createMsg( $type = false ):array {
		$tmp             = array();
		$tmp['status']   = self::getReturnStatus();
		$tmp['type']     = $type;
		$tmp['mwreturn'] = self::getMwReturn();
		if ( is_array( self::getReturnData() ) ) {
			$combined   = implode( '<BR>', self::getReturnData() );
			$tmp['msg'] = $combined;
		} else {
			$tmp['msg'] = self::getReturnData();
		}

		return $tmp;
	}
}