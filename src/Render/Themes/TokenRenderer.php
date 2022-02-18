<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering tokens.
 *
 * @package FlexForm\Render
 */
interface TokenRenderer {
	/**
	 * @brief Render token
	 *
	 * @param string $input Input for the field (should be fully parsed)
	 * @param string $id
	 * @param int $inputLengthTrigger
	 * @param string|null $placeholder
	 * @param string|null $smwQuery
	 * @param string|null $json
	 * @param string|null $callback An optional callback for the select2 Javascript
	 * @param string|null $template An optional template for the select2 arguments
	 * @param bool $multiple Whether to add "multiple=multiple" to the tag attributes
	 * @param bool $allowTags Whether to add "allowTags: true" to the select2 arguments
	 * @param bool $allowClear Whether to add "allowClear: true" to the select2 arguments
	 * @param array $additionalArguments Any additional arguments given to the token field
	 *
	 * @return string Rendered HTML
	 */
	public function render_token(
		string $input,
		string $id,
		int $inputLengthTrigger,
		?string $placeholder,
		?string $smwQuery,
		?string $json,
		?string $callback,
		?string $template,
		bool $multiple,
		bool $allowTags,
		bool $allowClear,
		array $additionalArguments
	) : string;
}