<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering fieldsets.
 *
 * @package FlexForm\Render
 */
interface FieldsetRenderer {
    /**
     * @brief Render fieldset
     *
     * @param string $input The content of the field
     * @param array $args The arguments to the field
     *
     * @return string Rendered HTML
     */
    public function render_fieldset( string $input, array $args ): string;
}