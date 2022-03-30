<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : FlexForm
 * Filename    : external.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 13:21
 */

namespace FlexForm\Processors\Request;

use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors\Utilities\General;
use FlexForm\FlexFormException;

/**
 * Class external
 * <p>Handles external request</p>
 *
 * @package flexform\processors\request
 */
class External {

	/**
	 * @param HandleResponse $responseHandler
	 *
	 * @return void
	 * @throws FlexFormException
	 */
	public static function handle( HandleResponse $responseHandler ) {
		$external = General::getGetString( 'script' );
		self::doHandle( $external, $responseHandler );
	}

	/**
	 * Handle extensions
	 * @param HandleResponse $responseHandler
	 *
	 * @return void
	 * @throws FlexFormException
	 */
	public static function handlePost( HandleResponse $responseHandler ) {
		$external = General::getPostString( 'mwextension' );
		self::doHandle( $external, $responseHandler, true );
	}

	/**
	 * @param string|bool $external
	 * @param HandleResponse $responseHandler
	 * @param bool $postHandler
	 *
	 * @return void
	 * @throws FlexFormException
	 */
	private static function doHandle( $external, HandleResponse $responseHandler, bool $postHandler = false ) {
		$handler = new Handlers();
		$handler->setPostHandler( $postHandler );
		if ( $external !== false ) {
			$external = wsSecurity::cleanHTML( $external );
			if ( $handler->handlerExist( $external ) ) {
				$handler->handlerExecute(
					$external,
					$responseHandler
				);
			} else {
				throw new FlexFormException(
					wfMessage( 'flexform-external-request-not-found' )->text(),
					0
				);
			}
		} else {
			if ( $postHandler ) {
				$throwMessage = wfMessage( 'flexform-extension-not-found' )->text();
			} else {
				$throwMessage = wfMessage( 'flexform-external-request-not-found' )->text();
			}
			throw new FlexFormException(
				$throwMessage,
				0
			);
		}
	}
}
