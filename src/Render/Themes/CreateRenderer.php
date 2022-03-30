<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering create fields.
 *
 * @package FlexForm\Render
 */
interface CreateRenderer {
	/**
	 * @brief Render create
	 *
	 * @param string|null $follow
	 * @param string|null $template
	 * @param string|null $createId
	 * @param string|null $write
	 * @param string|null $slot
	 * @param string|null $option
	 * @param string|null $fields
	 * @param bool $leadingZero
	 * @param bool $noOverWrite
	 *
	 * @return string Rendered HTML
	 */
	public function render_create(
		?string $follow,
		?string $template,
		?string $createId,
		?string $write,
		?string $slot,
		?string $option,
		?string $fields,
		bool $leadingZero,
		bool $noOverWrite
	): string;
}