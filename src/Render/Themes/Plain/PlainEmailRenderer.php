<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Core\Core;
use FlexForm\FlexFormException;
use FlexForm\Render\Themes\EmailRenderer;

class PlainEmailRenderer implements EmailRenderer {
	/**
	 * @inheritDoc
	 * @throws FlexFormException
	 */
	public function render_mail( array $mailArguments, string $base64content ) : string {
		return Core::createHiddenField( 'mwmail[]', base64_encode( json_encode( $mailArguments ) ) );
	}
}