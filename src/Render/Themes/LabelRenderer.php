<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering labels.
 *
 * @package WSForm\Render
 */
interface LabelRenderer {
    /**
     * @brief Render label
     *
     * @param string $input Input for the label (should be safe to output)
     *
     * @return string Rendered HTML
     */
    public function render_label( string $input ): string;
}