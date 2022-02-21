<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Core\Core;
use FlexForm\Render\Themes\EditRenderer;

class PlainEditRenderer implements EditRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_edit(
		string $target,
		string $template,
		string $formfield,
		string $usefield,
		string $slot,
		string $value
	) : string {
		$tagValue = htmlspecialchars( $target ) . '-^^-' . htmlspecialchars( $template ) . '-^^-' . htmlspecialchars(
				$formfield
			) . '-^^-' . htmlspecialchars( $usefield ) . '-^^-' . htmlspecialchars(
						$value
					) . '-^^-' . htmlspecialchars( $slot );

		return Core::createHiddenField( 'mwedit[]', $tagValue );
		/*
		return sprintf(
			'<input type="hidden" name="mwedit[]" value="%s">' . PHP_EOL,
			$tagValue
		);
		*/
	}
}