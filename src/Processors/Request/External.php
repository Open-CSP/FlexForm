<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : WSForm
 * Filename    : external.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 13:21
 */


namespace WSForm\Processors\Request;

use WSForm\Core\Protect;
use WSForm\Processors\Utilities\General;
use WSForm\Modules\Handlers\Handlers;
use WSForm\WSFormException;

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