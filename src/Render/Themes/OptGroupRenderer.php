<?php
/**
 * Created by  : OpenCSP
 * Project     : FlexForm
 * Filename    : OptGroupRenderer.php
 * Description :
 * Date        : 16-2-2025
 * Time        : 19:25
 */

namespace  FlexForm\Render\Themes;
/**
 * Interface for rendering optgroups.
 *
 * @package FlexForm\Render
 */
interface OptGroupRenderer {
	/**
	 * @brief Render optgroup
	 *
	 * @param string $input The content of the field
	 * @param array $args The arguments to the field
	 *
	 * @return string Rendered HTML
	 */
	public function render_optgroup( string $input, array $args ): string;
}