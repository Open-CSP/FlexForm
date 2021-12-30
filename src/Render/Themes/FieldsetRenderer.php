<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering fieldsets.
 *
 * @package WSForm\Render
 */
interface FieldsetRenderer {
    /**
     * @brief Render fieldset
     *
     * @param string $content The content of the field
     * @param array $args The arguments to the field
     *
     * @return string Rendered HTML
     */
    public function render_fieldset( string $content, array $args ): string;
}