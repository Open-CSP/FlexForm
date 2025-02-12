<?php

namespace  FlexForm\Render\Themes;

use Parser;
use PPFrame;

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