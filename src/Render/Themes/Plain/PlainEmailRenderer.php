<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\EmailRenderer;

class PlainEmailRenderer implements EmailRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_mail( array $mailArguments, string $base64content ) : string {
		$template = '';
		if ( isset( $mailArguments['mwparselast'] ) ) {
			$template = sprintf(
				'<input type="hidden" name="mwparselast" value="%s">' . PHP_EOL,
				htmlspecialchars( $mailArguments['mwparselast'] )
			);
			unset( $mailArguments['mwparselast'] );
		}
		$template .= sprintf(
			'<input type="hidden" name="mwmail[]" value="%s">' . PHP_EOL,
			htmlspecialchars( json_encode( $mailArguments ) )
		);

		return $template;
	}
}
