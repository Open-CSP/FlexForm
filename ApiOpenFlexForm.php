<?php

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\Core\Protect;
use FlexForm\FlexFormException;
use Wikimedia\ParamValidator\ParamValidator;

class ApiOpenFlexForm extends ApiBase {

	/**
	 * @param mixed $failure
	 *
	 * @return void
	 */
	private function returnFailure( $failure ) {
		$ret            = [];
		$ret['message'] = $failure;
		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			[ 'error' => $ret ]
		);
	}

	/**
	 * @param mixed $code
	 * @param mixed $result
	 *
	 * @return array
	 */
	private function createResult( $code, $result ): array {
		$ret           = [];
		$ret['status'] = $code;
		$ret['data']   = $result;

		return $ret;
	}

	/**
	 * @param string $txt
	 *
	 * @return array
	 * @throws FlexFormException
	 */
	private function decrypt( string $txt ): array {
		Config::setConfigFromMW();
		$crypt = new Protect();
		try {
			$crypt::setCrypt();
		} catch ( FlexFormException $exception ) {
			return $this->createResult(
				'error',
				$exception->getMessage()
			);
		}

		$json = json_decode(
			$txt,
			true
		);
		if ( $json === null ) {
			$json = $crypt::decrypt( $txt );
		} elseif ( is_array( $json ) ) {
			foreach ( $json as $k => $v ) {
				$json[$k] = $crypt::decrypt( $v );
			}
		} else {
			$json = $crypt::decrypt( $json );
		}

		return $this->createResult(
			'ok',
			$json
		);
	}

	/**
	 * @throws MWException
	 * @throws FlexFormException
	 * @throws \MediaWiki\Api\ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$action = $params['ffaction'];
		if ( !$action || $action === null ) {
			$this->dieWithError( 'missingparam' );
		}

		switch ( $action ) {
			case "user-exists":
				$mId = $params['mId'];
				if ( !$mId || $mId === null ) {
					$this->returnFailure( $this->msg( 'flexform-api-error-parameter-mid-missing' )->text() );
					break;
				}
				$messaging = new \FlexForm\Core\Messaging();
				$mId = intval( $mId );
				if ( $mId === 0 ) {
					$this->returnFailure( $this->msg( 'flexform-api-error-parameter-mid-missing' )->text() );
					break;
				}
				$result = $messaging->removeUserMessageById( $mId, true );
				$output = $this->createResult( "ok", "ok" );
				break;
			case "ask":
				$smwAsk = new SemanticAsk();
				$output = $smwAsk->execute( new HandleResponse() );
				$this->getResult()->addValue(
					null,
					'results',
					$output['results']
				);

				return true;
				break;
			case "decrypt":
				$output = $this->decrypt( $params['titleStartsWith'] );
				if ( $output['status'] === "error" ) {
					$this->returnFailure( $output['data'] );
					break;
				}

				break;
			case "nextAvailable":
				$title  = $params['titleStartsWith'];
				$result = $this->getNextAvailable( $title );
				if ( $result['status'] === "error" ) {
					$output = '';
					$this->returnFailure( $result['data'] );
					break;
				}
				$output = $result['data'];
				break;
			case "getRange" :
				$title = $params['titleStartsWith'];
				$range = $params['range'];
				if ( !$range ) {
					$output = '';
					$this->returnFailure( wfMessage( 'flexform-api-error-parameter-range-missing' )->text() );
					break;
				}
				$range = explode(
					'-',
					$range
				);

				if ( !ctype_digit( $range[0] ) || !ctype_digit( $range[1] ) ) {
					$this->returnFailure( wfMessage( 'flexform-api-error-bad-range' )->text() );
					break;
				}
				$startRange = (int)$range[0];
				$endRange   = (int)$range[1];
				$params['setrange']['start'] = $startRange;
				$params['setrange']['end'] = $endRange;

				$result = $this->getFromRange(
					$title,
					[
						'start' => $startRange,
						'end'   => $endRange
					]
				);
				if ( isset( $result['status'] ) && $result['status'] === "error" ) {
					$this->returnFailure( $result['data'] );
					$output = '';
					break;
				}
				if ( isset( $result['data'] ) ) {
					$output = $result['data'];
				} else {
					$output = '';
				}
				break;
			default :
				$this->returnFailure( wfMessage( 'flexform-api-error-unknown-what-parameter' )->text() );
				break;
		}

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			[ 'result' => $output,
				'request' => $params ]
		);

		return true;
	}

	public function needsToken() {
		return false;
	}

	public function isWriteMode() {
		return false;
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'ffAction'            => [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'additionData' => [
				ParamValidator::PARAM_TYPE     => 'string'
			],
		];
	}

	/**
	 * If there are more results from the API, get the next results
	 *
	 * @param array $result of previous API results
	 *
	 * @return bool|string when no further results or where to start next API call
	 */
	public function getApiContinue( array $result ) {
		return $result['continue']['apcontinue'] ?? false;
	}

	/**
	 * @return array
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=flexform&ffAction=userExists&additionalData=Harry%20Potter' => 'apihelp-flexform-ffaction-example-1'
		];
	}

}