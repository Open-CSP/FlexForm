<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : WSForm
 * Filename    : external.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 13:21
 */


namespace FlexForm\Processors\Request;

use FlexForm\Core\Protect;
use FlexForm\Processors\Utilities\General;
use FlexForm\Modules\Handlers\Handlers;
use FlexForm\WSFormException;

/**
 * Class external
 * <p>Handles external request</p>
 *
 * @package wsform\processors\request
 */
class External {

	public static function handle() {
		$handler = new Handlers();
		$external = General::getGetString( 'script' );
		if ( $external !== false && $handler->handlerExist( $external ) ) {
			$handler->handlerExecute( $external );
		} else {
			throw new WSFormException(
				wfMessage( 'wsform-external-request-not-found' )->text(),
				0
			);
		}
	}


}