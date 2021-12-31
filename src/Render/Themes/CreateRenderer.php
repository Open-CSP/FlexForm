<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering create fields.
 *
 * @package WSForm\Render
 */
interface CreateRenderer {
    /**
     * @brief Render create
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_create( array $args ): string;
}