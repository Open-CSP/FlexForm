<?php
/**
 * Created by  : OpenCSP
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

	/**
	 * @var array
	 */
	private array $fields;

	/**
	 * @var array
	 */
	private array $config;

	/**
	 * @param string|null $name
	 * @param bool $checkIfEmpty
	 *
	 * @return mixed|null
	 */
	private function getFieldFromFlexForm( ?string $name, bool $checkIfEmpty = true ) {
		if ( $name === null ) {
			return null;
		}
		if ( $checkIfEmpty ) {
			if ( isset( $this->fields[ $name ] ) && !empty( $this->fields[ $name ] ) ) {
				return $this->fields[ $name ];
			} else {
				return null;
			}
		} else {
			if ( isset( $this->fields[ $name ] ) ) {
				return true;
			} else {
				return null;
			}
		}
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	private function getConfigValue( string $name ) {
		if ( isset( $this->config[ $name ] ) && ! empty( $this->config[ $name ] ) ) {
			return $this->config[ $name ];
		} else {
			return null;
		}
	}

	/**
	 * @inerhitDoc
	 * @throws FlexFormException
	 */
	public function execute( array $flexFormFields, ?array $config, HandleResponse $responseHandler ) : HandleResponse {
		$this->fields = $flexFormFields;
		if ( $config === null ) {
			throw new FlexFormException( 'phpList : Configuration is missing. Please read the documentation',
				0,
				null );
		}
		$this->config = $config;

		// Do we have a field needs to exist to perform our actions?
		$fieldExists = $this->getConfigValue( 'NEEDS_THIS_FIELD' );
		if ( $fieldExists !== null ) {
			$fieldInForm = $this->getFieldFromFlexForm( $fieldExists, false );
			if ( $fieldInForm === null ) {
				return $responseHandler;
			}
		}

		// Lists to subscribe to
		$lists = $this->getFieldFromFlexForm( 'list' );

		// emailhtml field
		$emailHtml = $this->getFieldFromFlexForm( 'emailhtml' );

		// attribute1 field
		$name = $this->getFieldFromFlexForm( $this->getConfigValue( 'FIELD_NAME' ) );
		if ( $this->getFieldFromFlexForm( $this->getConfigValue( 'FIELD_AS_NAME' ) ) !== null ) {
			$name = $this->getFieldFromFlexForm(
				$this->getFieldFromFlexForm( $this->getConfigValue( 'FIELD_AS_NAME' ) )
			);
		}
		// subscribe-email field
		$email = $this->getFieldFromFlexForm( $this->getConfigValue( 'FIELD_EMAIL' ) );
		if ( $this->getFieldFromFlexForm( $this->getConfigValue( 'FIELD_AS_EMAIL' ) ) !== null ) {
			$email = $this->getFieldFromFlexForm(
				$this->getFieldFromFlexForm( $this->getConfigValue( 'FIELD_AS_EMAIL' ) )
			);
		}

		if ( $name === null || $email === null ) {
			if ( $name === null ) {
				$field = "name";
			} else {
				$field = "email";
			}
			throw new FlexFormException( 'phpList : Essential fields missing: ' . $field,
				0,
				null );
		}

		if ( $emailHtml === null && $this->getConfigValue( 'HTML' ) !== null ) {
			$emailHtml = $this->getConfigValue( 'HTML' );
		} else {
			$emailHtml = 1;
		}

		if ( $lists === null && $this->getConfigValue( 'DEFAULT_LISTS' ) !== null ) {
			$lists = $this->getConfigValue( 'DEFAULT_LISTS' );
		} else {
			throw new FlexFormException( 'phpList : Missing subscriber lists',
				0,
				null );
		}
		$postData['emailhtml']  = $emailHtml;
		$postData['attribute1'] = $name;
		$postData['email']      = $email;
		$postData['list']       = $this->readyListsForPosting( $lists );
		$ret                    = $this->apiPost( $postData );
		$responseHandler->setReturnType( HandleResponse::TYPE_SUCCESS );
		$responseHandler->setReturnData( $ret );

		return $responseHandler;
	}

	/**
	 * @param array $lists
	 *
	 * @return array
	 */
	private function readyListsForPosting( array $lists ): array {
		$ret = [];
		foreach ( $lists as $list ) {
			$ret[$list] = 'signup';
		}
		return $ret;
	}

	/**
	 * @param array $postData
	 *
	 * @return bool|string
	 * @throws FlexFormException
	 */
	private function apiPost( array $postData ) {
		$postData['VerificationCodeX'] = '';
		$postData['emailconfirm']      = $postData['email'];
		$phpUrl                        = $this->getConfigValue( 'PHP_LIST_URL' );
		if ( $phpUrl === null ) {
			throw new FlexFormException( 'phpList : Missing PHPList Url',
				0,
				null );
		}
		$url         = rtrim( $phpUrl,
				'/' ) . '/?p=asubscribe&id=1';
		$data        = http_build_query( $postData );
		$curlOptions = [
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_USERAGENT      => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_POST           => true
		];
		$ch          = curl_init();
		curl_setopt_array( $ch,
			$curlOptions );

		curl_setopt( $ch,
			CURLOPT_URL,
			$url );
		curl_setopt( $ch,
			CURLOPT_POSTFIELDS,
			$data );
		$result = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			throw new FlexFormException( 'phpList : ' . curl_error( $ch ),
				0,
				null );
		}

		return $result;
	}
}
