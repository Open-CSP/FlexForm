<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering edit fields.
 *
 * @package WSForm\Render
 */
interface EditRenderer {
    /**
     * @brief Render edit
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_edit( array $args ): string;
}