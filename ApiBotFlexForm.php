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
	 * @var
	 */
	private $trigger;

	/**
	 * @var
	 */
	private $data;

	/**
	 * @var
	 */
	private $title;


	/**
	 * @return array
	 */
	private function getRequestData(): array {
		$ret = [];
		$ret['request']['trigger'] = $this->trigger;
		$ret['request']['title']   = $this->title;
		$ret['request']['data']    = $this->data;
		return $ret;
	}

	/**
	 * @param mixed $failure
	 *
	 * @return void
	 */
	private function returnFailure( $failure ) {
		$ret            = $this->getRequestData();
		$ret['message'] = $failure;
		$this->getResult()->addValue( null,
									  $this->getModuleName(),
									  [ 'error' => $ret ] );
	}

	/**
	 * @param mixed $status
	 *
	 * @return void
	 */
	private function returnResult( $status ) {
		$ret            = $this->getRequestData();
		$ret['message'] = $status;
		$this->getResult()->addValue( null,
									  $this->getModuleName(),
									  [ 'result' => $ret ] );
	}

	/**
	 * @return bool
	 * @throws ApiUsageException
	 */
	public function execute() {
		$this->checkUserRightsAny( [ 'read' ] );
		$params = $this->extractRequestParams();
		$action = $params['trigger'];
		if ( !$action || $action === null ) {
			$this->returnFailure( $this->msg( 'flexform-bot-api-error-unknown-trigger-parameter' )->text() );
		}
		$this->trigger = $action;
		switch ( $action ) {
			case "email":
				$template = $params['title'];
				if ( $template === null || !$template ) {
					$this->returnFailure( $this->msg( 'flexform-bot-api-error-unknown-title-parameter' )->text() );
					break;
				}
				$this->title = $template;
				$mail = new Mail( $template );
				$this->data = $params['data'];

				$bcc = [];
				if ( $this->data !== null && $this->data !== false ) {
					$json = json_decode( $this->data, true );

					if ( $json !== null ) {
						if ( isset( $json['bcc'] ) ) {
							$bcc['bcc'] = $json['bcc'];
						}
					}
				}

				if ( $mail->getTemplate() !== false ) {
					try {
						$mail->handleTemplate( $bcc );
						$this->returnResult( 'success' );
					} catch ( FlexFormException | MWException $e ) {
						$this->returnFailure( $e->getMessage() );
					}
				} else {
					$this->returnFailure( $this->msg( 'flexform-bot-api-error-unknown-title-parameter' )->text() );
				}
				break;
			default :
				$this->returnFailure( $this->msg( 'flexform-bot-api-error-unknown-trigger-parameter' )->text() );
				break;
		}
		return true;
	}

	public function needsToken() {
		return "csrf";
		//return false;
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
			,
			'title' => [
				ParamValidator::PARAM_TYPE     => 'string'
			]
		];
	}

	/**
	 * @return array
	 */
	protected function getExamplesMessages() : array {
		return array(
			'action=FlexFormBot&trigger=email&title=Email template page' => 'apihelp-flexform-bot-example-1'
		);
	}

}