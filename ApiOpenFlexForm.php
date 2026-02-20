<?php

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\Core\Protect;
use FlexForm\FlexFormException;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class ApiOpenFlexForm extends ApiBase {

	/**
	 * @param mixed $code
	 * @param mixed $result
	 *
	 * @return array
	 */
	private function createResult( $code, $result ): array {
		$ret = [];
		$ret['status'] = $code;
		$ret['data'] = $result;

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
	 */
	public function execute() {
		Config::setConfigFromMW();
		$configVar = Config::getConfigVariable( 'allowFlexFormOpenAPI' );
		if ( $configVar === null ) {
			$this->dieWithError( $this->msg( 'flexform-api-error-api-function-not-active' )->text() );
		}
		$params = $this->extractRequestParams();
		$action = $params['ffAction'];
		if ( !$action || $action === null ) {
			$this->dieWithError( 'missingparam ffAction' );
		}

		switch ( $action ) {
			case "canUserBeCreated":
				$result = true;
				$userName = $params['additionalData'];
				if ( empty( $userName ) ) {
					$this->dieWithError( 'missingparam' );
				}
				$userFactory = MediaWikiServices::getInstance()->getUserFactory();
				$userNew = $userFactory->newFromName( $userName );
				if ( $userNew->isRegistered() ) {
					$result = false;
				}
				$nameUtils = MediaWikiServices::getInstance()->getUserNameUtils();
				if ( !$nameUtils->isCreatable( $userName ) ) {
					$result = false;
				}
				$this->getResult()->addValue( null, 'canUserBeCreated', $result );
				break;
			case "decrypt":
				$output = $this->decrypt( $params['additionalData'] );
				if ( $output['status'] === "error" ) {
					$this->dieWithError( $output['data'] );
				}
				$this->getResult()->addValue( null, 'decrypt', $output );

				break;
			default :
				$this->dieWithError( $this->msg( 'flexform-api-error-unknown-what-parameter' )->text() );
		}

		return true;
	}

	public function isReadMode() {
		return false;
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
			'ffAction' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'additionalData' => [
				ParamValidator::PARAM_TYPE => 'string'
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
			'action=FlexFormOpen&ffAction=canUserBeCreated&additionalData=Harry%20Potter' => 'apihelp-flexform-ffaction-example-1'
		];
	}

}