<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\EmailRenderer;

class PlainEmailRenderer implements EmailRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_mail( array $mailArguments, string $base64content ) : string {
		$template = "";

		foreach ( $mailArguments as $name => $value ) {
			$template .= sprintf(
				'<input type="hidden" name="%s" value="%s">' . PHP_EOL,
				htmlspecialchars( $name ),
				htmlspecialchars( $value )
			);
		}

		return $template;
	}
}