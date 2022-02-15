<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering labels.
 *
 * @package FlexForm\Render
 */
interface LabelRenderer {
    /**
     * @brief Render label
     *
     * @param string $input Input for the label (should be safe to output)
     * @param string $for
     * @param array $args The arguments given to the field
     *
     * @return string Rendered HTML
     */
    public function render_label( string $input, string $for, array $args ): string;
}