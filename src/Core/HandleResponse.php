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

	private $returnStatus = "ok";
	private $returnData = array();
	private $mwReturn = false;

	/**
	 * @var bool
	 */
	private $apiAjax = false;

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
	 * @param bool|string $type type of visual notice to show (error, warning, success, etc)
	 *
	 * @return array
	 */
	public function createMsg( $type = false ):array {
		$tmp             = array();
		$tmp['status']   = $this->getReturnStatus();
		$tmp['type']     = $type;
		$tmp['mwreturn'] = $this->getMwReturn();
		if ( is_array( $this->getReturnData() ) ) {
			$combined   = implode( '<BR>', $this->getReturnData() );
			$tmp['msg'] = $combined;
		} else {
			$tmp['msg'] = $this->getReturnData();
		}

		return $tmp;
	}
}