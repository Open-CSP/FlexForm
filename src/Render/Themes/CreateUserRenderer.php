<?php

namespace FlexForm\Render\Themes;

/**
 * Interface for rendering create fields.
 *
 * @package FlexForm\Render
 */
interface CreateUserRenderer {
	/**
	 * @brief render user create
	 *
	 * @param string $userName
	 * @param string $email
	 *
	 * @return string
	 */
	public function render_createUser( string $userName, string $email ): string;
}