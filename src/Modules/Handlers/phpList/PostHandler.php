<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : PHPList extension for FlexForm
 * Filename    : PostHandler.php
 * Description :
 * Date        : 27-3-2023
 * Time        : 21:49
 */

namespace FlexForm\Modules\Handlers\phpList;

use FlexForm\Core\HandleResponse;
use FlexForm\FlexFormException;
use FlexForm\Modules\Handlers\HandlerInterface;

class PostHandler implements HandlerInterface {

	private const HTML = 1;
	private const PHP_LIST_URL = '';
	private const FIELD_EMAIL = 'email';
	private const FIELD_NAME = 'name';
	private const FIELD_AS_EMAIL = 'useFieldAsEmail';
	private const FIELD_AS_NAME = 'useFieldAsName';
	private const DEFAULT_LISTS = [
		3 => 'signup',
		4 => 'signup',
		5 => 'signup',
		6 => 'signup',
		7 => 'signup',
		8 => 'signup'
	];

	/**
	 * @var array
	 */
	private array $fields;

	/**
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	private function getFieldFromFlexForm( string $name ) {
		if ( isset( $this->fields[$name] ) && !empty( $this->fields[$name] ) ) {
			return $this->fields[$name];
		} else {
			return null;
		}
	}

	/**
	 * @inerhitDoc
	 * @throws FlexFormException
	 */
	public function execute( array $flexFormFields, HandleResponse $responseHandler ) : HandleResponse {
		$this->fields = $flexFormFields;

		// Lists to subscribe to
		$lists = $this->getFieldFromFlexForm( 'list' );

		// emailhtml field
		$emailHtml = $this->getFieldFromFlexForm( 'emailhtml' );

		// attribute1 field
		$name = $this->getFieldFromFlexForm( self::FIELD_NAME );
		if ( $this->getFieldFromFlexForm( self::FIELD_AS_NAME ) !== null ) {
			if ( $this->getFieldFromFlexForm( $this->getFieldFromFlexForm( self::FIELD_AS_NAME ) ) !== null ) {
				$name = $this->getFieldFromFlexForm( $this->getFieldFromFlexForm( self::FIELD_AS_NAME ) );
			}
		}
		// subscribe-email field
		$email = $this->getFieldFromFlexForm( self::FIELD_EMAIL );
		if ( $this->getFieldFromFlexForm( self::FIELD_AS_EMAIL ) !== null ) {
			if ( $this->getFieldFromFlexForm( $this->getFieldFromFlexForm( self::FIELD_AS_EMAIL ) ) !== null ) {
				$email = $this->getFieldFromFlexForm( $this->getFieldFromFlexForm( self::FIELD_AS_EMAIL ) );
			}
		}

		if ( $name === null || $email === null ) {
			throw new FlexFormException(
				'phpList : Essential fields missing',
				0,
				null
			);
		}

		if ( $emailHtml === null ) {
			$emailHtml = self::HTML;
		}

		if ( $lists === null ) {
			$lists = self::DEFAULT_LISTS;
		}

		$postData['emailhtml'] = $emailHtml;
		$postData['attribute1'] = $name;
		$postData['email'] = $email;
		$postData['list'] = $lists;
		$ret = $this->apiPost( $postData );
		$responseHandler->setReturnType( HandleResponse::TYPE_SUCCESS );
		$responseHandler->setReturnData( $ret );
		return $responseHandler;
	}

	/**
	 * @param array $postData
	 *
	 * @return bool|string
	 * @throws FlexFormException
	 */
	private function apiPost( array $postData ) {
		$postData['VerificationCodeX'] = '';
		$postData['emailconfirm'] = $postData['email'];
		$url = rtrim( '/' . self::PHP_LIST_URL ) . '/' . '?p=asubscribe&id=1';
		$data = http_build_query( $postData );
		$curlOptions =
			[
				CURLOPT_CONNECTTIMEOUT => 30,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_POST => true
			];
		$ch = curl_init();
		curl_setopt_array( $ch, $curlOptions );

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		$result = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			throw new FlexFormException(
				'phpList : ' . curl_error( $ch ),
				0,
				null
			);
		}
		return $result;
	}
}
