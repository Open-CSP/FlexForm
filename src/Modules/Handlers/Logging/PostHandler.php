<?php

namespace FlexForm\Modules\Handlers\Logging;

use FlexForm\Modules\Handlers\HandlerInterface;

class PostHandler implements HandlerInterface {
	/**
	 * @inerhitDoc
	 */
	public function execute( array $flexFormFields ) {
		// TODO: Implement execute() method.
		print_r( $flexFormFields );
	}

}