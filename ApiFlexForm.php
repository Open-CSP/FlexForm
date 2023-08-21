<?php

use FlexForm\Core\Protect;
use FlexForm\FlexFormException;
use Wikimedia\ParamValidator\ParamValidator;

class ApiFlexForm extends ApiBase {

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
	private function decrypt( string $txt ) : array {
		\FlexForm\Core\Config::setConfigFromMW();
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

	public function execute() {
		$params = $this->extractRequestParams();
		$action = $params['what'];
		if ( !$action || $action === null ) {
			$this->dieUsageMsg( 'missingparam' );
		}

		switch ( $action ) {
			case "decrypt":
				$output = $this->decrypt( $params['titleStartsWith'] );
				if ( $output['status'] === "error" ) {
					$this->returnFailure( $output['data'] );
					break;
				}

				break;
			case "nextAvailable" :
				$title  = $params['titleStartsWith'];
				$result = $this->getNextAvailable( $title );
				if ( $result['status'] === "error" ) {
					$this->returnFailure( $result['data'] );
					break;
				}
				$output = $result['data'];
				break;
			case "getRange" :
				$title = $params['titleStartsWith'];
				$range = $params['range'];
				if ( !$range || $range === null ) {
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

				$result = $this->getFromRange(
					$title,
					[
						'start' => $startRange,
						'end'   => $endRange
					]
				);
				if ( isset( $result['status'] ) && $result['status'] === "error" ) {
					$this->returnFailure( $result['data'] );
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
			[ 'result' => $output ]
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
			'what'            => [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'titleStartsWith' => [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'range'           => [
				ParamValidator::PARAM_TYPE => 'string'
			],
			'for'             => [
				ParamValidator::PARAM_TYPE => 'string'
			]
		];
	}

	/**
	 * @param string $nameStartsWith
	 *
	 * @return array
	 * @throws MWException
	 */
	private function getNextAvailable( string $nameStartsWith ) : array {
		$number      = [];
		$continue    = true;
		$appContinue = false;
		$cnt         = 0;
		while ( $continue ) {
			$result      = $this->getDataForWikiList(
				$nameStartsWith,
				$appContinue,
				false
			);
			if ( $result === false ) {
				return $this->createResult(
					'error',
					wfMessage( 'flexform-api-error-unkown-namespace' )->text()
				);
			}
			$appContinue = $this->getApiContinue( $result );

			if ( !isset( $result['query'] ) ) {
				return $this->createResult(
					'error',
					wfMessage( 'flexform-api-error-noquery-response' )->text()
				);
			}

			$pages = $result['query']['allpages'];
			if ( is_null( $pages ) || $pages === false ) {
				return $this->createResult(
					'error',
					wfMessage( 'flexform-api-error-allpages' )->text()
				);
			}
			if ( isset( $pages['_element'] ) ) {
				unset( $pages['_element'] );
			}

			$thisCnt = count( $pages );

			$cnt = $cnt + $thisCnt;
			if ( $cnt < 1 && $appContinue === false ) {
				return $this->createResult(
					'ok',
					"1"
				);
			}
			if ( $thisCnt > 0 ) {
				foreach ( $pages as $page ) {
					$tempTitle = str_replace(
						ltrim(
							$nameStartsWith,
							':'
						),
						'',
						$page['title']
					);
					if ( is_numeric( $tempTitle ) ) {
						$number[] = $tempTitle;
					}
				}
			}
			if ( $appContinue === false ) {
				$continue = false;
			}
		}
		rsort( $number );
		if ( isset( $number[0] ) ) {
			$nr = intval( $number[0] );
		} else {
			$nr = 0;
		}

		return $this->createResult(
			'ok',
			$nr + 1
		);
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
	 * @param string $nameStartsWith
	 * @param $range
	 *
	 * @return array|false|int|mixed
	 */
	public function getFromRange( string $nameStartsWith, $range = false ) {
		$number      = [];
		$continue    = true;
		$appContinue = false;
		$cnt         = 0;
		while ( $continue ) {
			$result = $this->getDataForWikiList(
				$nameStartsWith,
				$appContinue,
				$range
			);
			if ( $result === false ) {
				return $this->createResult(
					'error',
					wfMessage( 'flexform-api-error-unkown-namespace' )->text()
				);
			}
			$appContinue = $this->getApiContinue( $result );
			if ( !isset( $result['query'] ) ) {
				return $this->createResult(
					'error',
					wfMessage( 'flexform-api-error-noquery-response' )->text()
				);
			}

			$pages = $result['query']['allpages'];

			if ( is_null( $pages ) || $pages === false ) {
				return $this->createResult(
					'error',
					wfMessage( 'flexform-api-error-allpages' )->text()
				);
			}
			if ( isset( $pages['_element'] ) ) {
				unset( $pages['_element'] );
			}

			$thisCnt = count( $pages );

			$cnt = $cnt + $thisCnt;

			if ( $cnt < 1 && $appContinue === false ) {
				return $range['start'] + 1;
			}

			if ( $thisCnt > 0 ) {
				foreach ( $pages as $page ) {
					$tempTitle = str_replace(
						ltrim(
							$nameStartsWith,
							':'
						),
						'',
						$page['title']
					);

					if ( is_numeric( $tempTitle ) ) {
						$number[] = $tempTitle;
					}
				}
			}
			if ( $appContinue === false ) {
				$continue = false;
			}
		}

		if ( count( $pages ) < 1 ) {
			return $range['start'] + 1;
		}
		$s = $range['start'];
		$e = $range['end'];

		for ( $t = $s; $t < $e; $t++ ) {
			if ( !in_array(
				$t,
				$number
			) ) {
				return $this->createResult(
					'ok',
					$t
				);
			}
		}

		// TODO:  Still need a procedure what to do if range is full
		return false;
	}

	/**
	 * @param string $nameStartsWith
	 * @param mixed $appContinue
	 * @param mixed $range
	 *
	 * @return false|mixed
	 * @throws MWException
	 */
	private function getDataForWikiList( string $nameStartsWith, $appContinue, $range = false ) {
		if ( strpos(
				 $nameStartsWith,
				 ':'
			 ) !== false ) {
			$split = explode(
				':',
				$nameStartsWith
			);

			$nameStartsWith = $split[1];
			$nameSpace      = $split[0];
			if ( empty( $nameSpace ) ) {
				$id = 0;
			} else {
				$id = $this->getLanguage()->getNsIndex( $nameSpace );
			}
		} else {
			$id = 0;
		}

		if ( $id === false ) {
			return false;
		}
		if ( !$range ) { // next available
			if ( $appContinue === false ) {
				$postdata = [
					"action"      => "query",
					"format"      => "json",
					"list"        => "allpages",
					"aplimit"     => 5,
					"apprefix"    => $nameStartsWith,
					"apnamespace" => $id
				];
			} else {
				$postdata = [
					"action"      => "query",
					"format"      => "json",
					"aplimit"     => 5,
					"apcontinue"  => $appContinue,
					"list"        => "allpages",
					"apprefix"    => $nameStartsWith,
					"apnamespace" => $id
				];
			}
		} else {
			$trimmedStart = rtrim(
				$range['start'],
				"0"
			);
			$trimmedStart = $range['start'];
			if ( $appContinue === false ) {
				$postdata = [
					"action"      => "query",
					"format"      => "json",
					"aplimit"     => "max",
					"list"        => "allpages",
					"apnamespace" => $id,
					"apprefix"    => $nameStartsWith
				];
			} else {
				$postdata = [
					"action"      => "query",
					"format"      => "json",
					"list"        => "allpages",
					"aplimit"     => "max",
					"apcontinue"  => $appContinue,
					"apnamespace" => $id,
					"apprefix"    => $nameStartsWith
				];
			}
		}
		$render = new \FlexForm\Processors\Content\Render();

		return $render->makeRequest(
			$postdata
		);
	}

	/**
	 * @return array
	 */
	protected function getExamplesMessages() : array {
		return [
			'action=flexform&what=getRange&titleStartsWith=Invoice/&range=0000-9999' => 'apihelp-flexform-example-1',
			'action=flexform&what=nextAvailable&&titleStartsWith=Invoice/'           => 'apihelp-flexform-example-2'
		];
	}

}