<?php

namespace FlexForm\Modules\Handlers\Logging;

use FlexForm\Core\HandleResponse;
use FlexForm\Modules\Handlers\HandlerInterface;
use FlexForm\FlexFormException;

class PostHandler implements HandlerInterface {
	/**
	 * @inerhitDoc
	 */
	public function execute( array $flexFormFields, ?array $config, HandleResponse $responseHandler ) : HandleResponse {
		// TODO: Implement execute() method.
		//echo "<h2>Logging</h2>";
		//print_r( $flexFormFields );
		//die();
		$responseHandler->setReturnType( HandleResponse::TYPE_ERROR );
		$responseHandler->setReturnData( "Not implemented yet" );

		return $responseHandler;
	}

}