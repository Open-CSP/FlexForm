<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Core\Core;
use FlexForm\Render\Themes\CreateUserRenderer;

class PlainCreateUserRenderer implements CreateUserRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_createUser( string $userName, string $email, ?string $realName ): string {
		if ( $realName === null ) {
			$realName = '';
		}
		return Core::createHiddenField(
			'mwcreateuser',
			$userName . Core::DIVIDER . $email . Core::DIVIDER . $realName
		);
	}
}