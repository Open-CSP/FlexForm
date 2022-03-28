<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Core\Core;
use FlexForm\Render\Themes\CreateUserRenderer;

class PlainCreateUserRenderer implements CreateUserRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_createUser( string $userName, string $email ): string {
		return Core::createHiddenField(
			'mwcreateuser',
			$userName . '-^^-' . $email
		);
	}
}