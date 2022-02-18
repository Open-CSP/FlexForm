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
	 * @return void
	 * @throws FlexFormException
	 */
	public static function handle( HandleResponse $responseHandler ) {
		$handler = new Handlers();
		$external = General::getGetString( 'script' );
		if ( $external !== false && $handler->handlerExist( $external ) ) {
			$handler->handlerExecute( $external, $responseHandler );
		} else {
			throw new FlexFormException(
				wfMessage( 'flexform-external-request-not-found' )->text(),
				0
			);
		}
	}
}
