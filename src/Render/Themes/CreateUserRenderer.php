<?php

namespace FlexForm\Render\Themes;

/**
 * Interface for rendering create fields.
 *
 * @package FlexForm\Render
 */
interface CreateUserRenderer {
	/**
	 * @param string $userName
	 * @param string $email
	 * @param string|null $realName
	 *
	 * @return string
	 */
	public function render_createUser( string $userName, string $email, ?string $realName ): string;
}