<?php

use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Mail;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Created by  : Designburo.nl
 * Project     : flexformWikiBaseNL
 * Filename    : ApiFlexForm.php
 * Description :
 * Date        : 09/10/2020
 * Time        : 20:14
 */
class ApiBotFlexForm extends ApiBase {

	/**
	 * @param mixed $failure
	 *
	 * @return void
	 */
	private function returnFailure( $failure ) {
		$ret            = [];
		$ret['message'] = $failure;
		$this->getResult()->addValue( null,
									  $this->getModuleName(),
									  [ 'error' => $ret ] );
	}

	/**
	 * @param mixed $content
	 *
	 * @return void
	 */
	private function returnResult( $content ): array {
		$ret            = [];
		$ret['message'] = $content;
		$this->getResult()->addValue( null,
			$this->getModuleName(),
			[ 'result' => $ret ] );
	}

	/**
	 * @return bool
	 * @throws ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$action = $params['trigger'];
		if ( !$action || $action === null ) {
			$this->returnFailure( wfMessage( 'flexform-bot-api-error-unknown-trigger-parameter' )->text() );
		}
		switch ( $action ) {
			case "email":
				$template = $params['data'];
				if ( $template === null || !$template ) {
					$this->returnFailure( wfMessage( 'flexform-bot-api-error-unknown-data-parameter' )->text() );
					break;
				}
				$mail = new Mail( $template );

				if ( $mail->getTemplate() !== false ) {
					try {
						$mail->handleTemplate();
						$output = "success";
					} catch ( FlexFormException | MWException $e ) {
						$this->returnFailure( $e->getMessage() );
					}
				}
				break;
			default :
				$this->returnFailure( wfMessage( 'flexform-bot-api-error-unknown-trigger-parameter' )->text() );
				break;
		}

		if ( $output !== null ) {
			$this->getResult()->addValue( null,
				$this->getModuleName(),
				array( 'result' => $output ) );
		}

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
			'trigger'            => [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'data' => [
				ParamValidator::PARAM_TYPE     => 'string'
			]
		];
	}

	/**
	 * @return array
	 */
	protected function getExamplesMessages() : array {
		return array(
			'action=flexform&what=getRange&titleStartsWith=Invoice/&range=0000-9999' => 'apihelp-flexform-example-1',
			'action=flexform&what=nextAvailable&&titleStartsWith=Invoice/'           => 'apihelp-flexform-example-2'
		);
	}

}