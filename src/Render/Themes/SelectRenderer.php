<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering select fields.
 *
 * @package WSForm\Render
 */
interface SelectRenderer {
    /**
     * @brief Render select
     *
     * @param string $input Inner content of the select field
     * @param array $args Arguments to pass to the select field
     * @param string $placeholder Placeholder text
     *
     * @return string Rendered HTML
     */
    public function render_select( string $input, array $args, string $placeholder ): string;
}