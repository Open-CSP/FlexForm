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
	 * @throws FlexFormException
	 * @throws \MediaWiki\Api\ApiUsageException
	 */
	public function execute() {
		Config::setConfigFromMW();
		$configVar = Config::getConfigVariable( 'allowFlexFormOpenAPI' );
		if ( $configVar === null || $configVar === false ) {
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
	 * @return array
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=FlexFormOpen&ffAction=canUserBeCreated&additionalData=Harry%20Potter' => 'apihelp-flexform-ffaction-example-1'
		];
	}

}