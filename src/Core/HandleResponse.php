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

use Database;
use WSForm\WSFormException;

class HandleResponse {

	private $returnStatus = "ok";
	private $returnType = "success";
	private $returnData = array();
	private $mwReturn = false;
	private $pauseBeforeRefresh = false;

	const TYPE_SUCCESS = 'success';
	const TYPE_WARNING = 'warning';
	const TYPE_ERROR = 'error';
	const TYPE_INFO = 'info';

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
	 * @param string $status
	 */
	public function setReturnType( string $type ) {
		$this->returnType = $type;
	}

	/**
	 * @return string
	 */
	public function getReturnType(): string {
		return $this->returnType;
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
	 * @param string|bool $refresh
	 */
	public function setPauseBeforeRefresh( $refresh ) {
		$this->pauseBeforeRefresh = $refresh;
	}

	/**
	 * @return string|bool
	 */
	public function getPauseBeforeRefresh() {
		return $this->pauseBeforeRefresh;
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

	/**
	 * Default response handler
	 *
	 * @throws WSFormException
	 */
	public function exitResponse() {
		$status = $this->getReturnStatus();
		$type = $this->getReturnType();
		$mwReturn = $this->getMwReturn();
		$messageData = $this->getReturnData();
		if( Config::isDebug() ){
			Debug::addToDebug( "exitResponse return Type", $type );
			Debug::addToDebug( "exitResponse mwreturn", $mwReturn );
			Debug::addToDebug( "exitResponse messagedata", $messageData );
			echo Debug::createDebugOutput();
			die('!testing..');
		}
		if ( is_array( $messageData ) ) {
			$message   = implode( '<BR>', $messageData );
		} else {
			$message = $messageData;
		}
		if ( $status === 'ok' && $this->apiAjax === false ) {
            $this->setCookieMessage(
                $message,
                $type
            ); // set cookies
        }

        $database = wfGetDB(DB_PRIMARY);

        if ( $database->writesPending() ) {
            // If there is still a database update pending, commit it here
            $database->commit( __METHOD__, Database::FLUSHING_ALL_PEERS );
        }

		try {
			if ( $status === 'ok' && $mwReturn !== false ) {
				$this->redirect( $mwReturn );
			}
		} catch ( WSFormException $e ){
			throw new WSFormException( $e->getMessage(), 0, $e );
		}

		if ( $status !== 'ok' && $mwReturn !== false ) { // Status not ok.. but we have redirect ?
			$this->setCookieMessage( $message ); // set cookies
			try {
				$this->redirect( $mwReturn ); // do a redirect or json output
			} catch ( WSFormException $e ){
				throw new WSFormException( $e->getMessage(), 0, $e );
			}
		} else { // Status not ok.. and no redirect
			$this->outputMsg( $message ); // show error on screen or do json output
		}

		exit();
	}

	/**
	 * Do a final redirect
	 *
	 * @param string $redirect
	 *
	 * @throws WSFormException
	 */
	public function redirect() {
		// Check if url is from same domain
		$parsed = parse_url( $this->getMwReturn() );
		if ( isset( $parsed['host'] ) ) {
			if ( $parsed['host'] !== $_SERVER['HTTP_HOST'] ) {
				throw new WSFormException( wfMessage( 'wsform-return-outside-domain' )->text() );
			}
		}
		// redirect
		if ( $this->getPauseBeforeRefresh() !== false ) {
			sleep( $this->getPauseBeforeRefresh() );
		}
		if ( ! $this->apiAjax ) {
			header( 'Location: ' . $this->getMwReturn() );
		} else {
			$this->outputJson(
				'ok',
				'ok'
			);
		}
	}

	/**
	 * @param $status string : status keyword
	 * @param $data mixed : holds the date
	 */
	public function outputJson( string $status, $data ) {
		$ret            = array();
		$ret['status']  = $status;
		$ret['message'] = $data;
		header( 'Content-Type: application/json' );
		echo json_encode(
			$ret,
			JSON_PRETTY_PRINT
		);
		die();
	}

	/**
	 * Set message for after page reload using cookies
	 *
	 * @param string $msg
	 * @param string $type
	 */
	public function setCookieMessage( string $msg, string $type = "danger" ) {

		if ( $msg !== '' ) {
			setcookie( "wsform[type]", $type, 0, '/' );
			if ( $type !== "danger" ) {
				setcookie( "wsform[txt]", $msg, 0, '/' );
			} else {
				setcookie( "wsform[txt]", 'WSForm :: ' . $msg, 0, '/' );
			}
		}

	}

	/**
	 * Function to output messages if multiple
	 * Used when there is no mwreturn
	 */
	public function outputMsg() {
		$numargs = func_num_args();
		$args    = func_get_args();
		if ( ! $this->apiAjax ) {
			for ( $i = 0; $i < $numargs; $i++ ) {
				if ( is_array( $args[$i] ) ) {
					echo "<pre>";
					print_r( $args[$i] );
					echo "</pre>";
				} else {
					echo "<p>" . $args[$i] . "</p>";
				}
			}
		} else {
			$tmp = '';
			for ( $i = 0; $i < $numargs; $i++ ) {
				if ( is_array( $args[$i] ) ) {
					$tmp .= implode(
						'<BR>',
						$args[$i]
					);
				} else {
					$tmp .= "<p>" . $args[$i] . "</p>";
				}
			}
			$this->outputJson(
				'error',
				$tmp
			);
		}
	}

}