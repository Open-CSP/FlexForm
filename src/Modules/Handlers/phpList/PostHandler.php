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

	private const HTML = true;
	private const PHPLISTURL = '';
	private const LISTS = [
		3,
		4,
		5,
		6,
		7,
		8
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
		$lists        = $this->getFieldFromFlexForm( 'list' );
		$emailHtml    = $this->getFieldFromFlexForm( 'emailhtml' );
		$name         = $this->getFieldFromFlexForm( 'attribute1' );
		$email        = $this->getFieldFromFlexForm( 'subscribe-email' );
		if ( $name === null || $email === null ) {
			throw new FlexFormException(
				'phpList : Essential fields missing',
				0,
				null
			);
		}
	}
}
