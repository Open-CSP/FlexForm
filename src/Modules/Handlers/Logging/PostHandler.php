<?php

namespace FlexForm\Modules\Handlers\Logging;

use FlexForm\Modules\Handlers\HandlerInterface;

class PostHandler implements HandlerInterface {
	/**
	 * @inerhitDoc
	 */
	public function execute( array $flexFormFields ) {
		// TODO: Implement execute() method.
		echo "<h2>Logging</h2>";
		print_r( $flexFormFields );
		//die();
	}

}