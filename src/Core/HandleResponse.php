<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : handleResponse.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 13:08
 */

namespace FlexForm\Core;

use Database;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\ContentCore;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\IDatabase;

/**
 * Class to gather information down the path of form handling and create responses
 */
class HandleResponse {

	private $returnStatus = "ok"; // Default return status for success is ok
	private $returnType = "success"; // default return type is success
	private $returnData = array(); // accumulated information on form processing
	private $mwReturn = false; // In the end... where to we go.
	private $pauseBeforeRefresh = false; // Sometimes needed if multiple actions are done

	const TYPE_SUCCESS = 'success';
	const TYPE_WARNING = 'warning';
	const TYPE_ERROR = 'error';
	const TYPE_INFO = 'info';

	/**
	 * False will redirect to mwreturn, true will output json response
	 *
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
	public function getReturnStatus() : string {
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
	public function getReturnType() : string {
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
	public function getReturnData() : array {
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
	public function createMsg( $type = false ) : array {
		die();
		$tmp             = array();
		$tmp['status']   = $this->getReturnStatus();
		$tmp['type']     = $type;
		$tmp['mwreturn'] = $this->getMwReturn();
		if ( is_array( $this->getReturnData() ) ) {
			$combined   = implode(
				'<BR>',
				$this->getReturnData()
			);
			$tmp['msg'] = $combined;
		} else {
			$tmp['msg'] = $this->getReturnData();
		}

		return $tmp;
	}

	/**
	 * Default final response handler
	 *
	 * @throws FlexFormException
	 */
	public function exitResponse() {
		$status      = $this->getReturnStatus();
		$type        = $this->getReturnType();
		$mwReturn    = $this->getMwReturn();
		$messageData = $this->getReturnData();
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				"exitResponse return Type",
				$type
			);
			Debug::addToDebug(
				"exitResponse status",
				$status
			);
			Debug::addToDebug(
				"exitResponse mwreturn",
				$mwReturn
			);
			Debug::addToDebug(
				"exitResponse messagedata",
				$messageData
			);
		}
		if ( is_array( $messageData ) ) {
			$message = implode(
				'<BR>',
				$messageData
			);
		} else {
			$message = $messageData;
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				"exitResponse messagedata after implode " . time(),
				$message
			);
			echo Debug::createDebugOutput();
			die( '!testing.. No cookies set!' );
		}
		if ( $status === 'ok' && $this->apiAjax === false ) {
			$this->setCookieMessage(
				$message,
				$type
			); // set cookies
		}


		$database = wfGetDB( DB_PRIMARY );

		if ( $database->writesPending() ) {

			// If there is still a database update pending, commit it here
			try {
				$database->commit(
					__METHOD__,
					IDatabase::FLUSHING_INTERNAL
				);
			} catch ( DBError | DBUnexpectedError $e ) {
				throw new FlexFormException(
					$e->getMessage(),
					0,
					$e
				);
			}
		}

		try {
			if ( $status === 'ok' && $mwReturn !== false ) {
				$this->redirect();
			}
		} catch ( FlexFormException $e ) {
			throw new FlexFormException(
				$e->getMessage(),
				0,
				$e
			);
		}
		$logger = Logging::getMeLogger();
		// Status not ok, but we have redirect ?

		if ( $status !== 'ok' && $mwReturn !== false ) {
			// set cookies
			if ( !$this->apiAjax ) {
				$logger->error( $message );
				$this->setCookieMessage( $message );
			}
			try {
				// do a redirect or json output
				$this->redirect( $type, $message );
			} catch ( FlexFormException $e ) {
				throw new FlexFormException(
					$e->getMessage(),
					0,
					$e
				);
			}
		} else {
			// Status not ok.. and no redirect
			$logger->error( $message );
			$this->outputMsg( $message ); // show error on screen or do json output
		}
	}

	/**
	 * @param string $type
	 * @param string $message
	 *
	 * @return void
	 * @throws FlexFormException
	 */
	public function redirect( string $type = "ok", string $message = "ok" ) {
		// Check if url is from same domain
		$parsed = parse_url( $this->getMwReturn() );
		if ( isset( $parsed['host'] ) ) {
			$serverToCheck = $parsed['host'];
			if ( isset( $parsed['port'] ) && !empty( $parsed['port'] ) ) {
				$serverToCheck = $serverToCheck . ':' . $parsed['port'];
			}
			if ( $_SERVER['HTTP_HOST'] !== $serverToCheck ) {
				throw new FlexFormException( wfMessage( 'flexform-return-outside-domain' )->text() );
			}
		}
		// redirect
		if ( $this->getPauseBeforeRefresh() !== false ) {
			sleep( $this->getPauseBeforeRefresh() );
		}
		if ( !$this->apiAjax ) {
			header( 'Location: ' . $this->getMwReturn() );
			die();
		} else {
			$fields = ContentCore::getFields();
			if ( $fields['mwfollow'] === "true" ) {
				$follow = $this->getMwReturn();
			} else {
				$follow = false;
			}
			$this->outputJson(
				$type,
				$message,
				$follow
			);
			die();
		}
	}

	/**
	 * @param $status string : status keyword
	 * @param $data mixed : holds the date
	 */
	public function outputJson( string $status, $data, $follow = false ) {
		$ret            = [];
		$ret['status']  = $status;
		$ret['message'] = $data;
		if ( $follow !== false ) {
			$ret['redirect'] = $follow;
		}
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
		$wR = new \WebResponse();
		if ( $msg !== '' ) {
			$wR->setCookie(
				"wsform[type]",
				$type,
				0,
				[ 'path' => '/' ,
				  'prefix' => '' ]
			);
			if ( $type !== "danger" ) {
				$wR->setCookie(
					"wsform[txt]",
					$msg,
					0,
					[ 'path' => '/',
					  'prefix' => '' ]
				);
			} else {
				$wR->setCookie(
					"wsform[txt]",
					$msg,
					0,
					[ 'path' => '/' ,
					  'prefix' => '' ]
				);
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