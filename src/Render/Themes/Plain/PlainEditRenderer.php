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
		string $value,
		string $format
	) : string {
		$tagValue = htmlspecialchars( $target ) . Core::DIVIDER . htmlspecialchars(
				$template
			) . Core::DIVIDER . htmlspecialchars(
						$formfield
					) . Core::DIVIDER . htmlspecialchars( $usefield ) . Core::DIVIDER . htmlspecialchars(
						$value
					) . Core::DIVIDER . htmlspecialchars( $slot ) . Core::DIVIDER . htmlspecialchars( $format );

		return Core::createHiddenField(
			'mwedit[]',
			$tagValue
		);
		/*
		return sprintf(
			'<input type="hidden" name="mwedit[]" value="%s">' . PHP_EOL,
			$tagValue
		);
		*/
	}
}