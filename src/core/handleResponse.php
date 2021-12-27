<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : handleResponse.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 13:08
 */

namespace wsform\core;

class handleResponse {

	private $returnStatus = "error";
	private $returnData = array();
	private $mwReturn = false;

	/**
	 * @var bool
	 */
	private $apiAjax = false;

	/**
	 * @param mixed $output
	 */
	public function __construct( $output ) {
		$this->setIdentifier( $output );
	}

	/**
	 * @param string $status
	 */
	public function setReturnStatus( string $status ) {
		$this->returnStatus = $status;
	}

	/**
	 * @return string
	 */
	public function getReturnStatus(): string {
		return $this->returnStatus;
	}

	/**
	 * @param string $data
	 */
	public function setReturnData( string $data ) {
		$this->returnData[] = $data;
	}

	/**
	 * @return array
	 */
	public function getReturnData(): array {
		return $this->returnData;
	}

	/**
	 * @param string|bool $mwReturn
	 */
	public function setMwReturn( $mwReturn ) {
		$this->mwReturn = $mwReturn;
	}

	/**
	 * @return string|bool
	 */
	public function getMwReturn() {
		return $this->mwReturn;
	}

	/**
	 * Set global result handler to normal or Ajax
	 *
	 * @param mixed $identifier
	 */
	public function setIdentifier( $identifier = false ) {
		if ( $identifier === 'ajax' ) {
			$this->apiAjax = true;
		}
	}

	/**
	 * @return bool
	 */
	public function isAjax() : bool {
		return $this->apiAjax;
	}

	/**
	 * Function called to create the final return parameters in a consistent way.
	 *
	 * @param string|array $msg Message to pass
	 * @param string $status Defaults to error. "ok" to pass
	 * @param bool|string $mwreturn url to return page
	 * @param bool|string $type type of visual notice to show (error, warning, success, etc)
	 *
	 * @return array
	 */
	public function createMsg( $msg, string $status = "error", $mwreturn = false, $type = false ):array {
		$tmp             = array();
		$tmp['status']   = $status;
		$tmp['type']     = $type;
		$tmp['mwreturn'] = $this->getMwReturn();
		if ( is_array( $msg ) ) {
			$combined   = implode( '<BR>', $msg );
			$tmp['msg'] = $combined;
		} else {
			$tmp['msg'] = $msg;
		}

		return $tmp;
	}
}