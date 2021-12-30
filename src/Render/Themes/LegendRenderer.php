<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering legends.
 *
 * @package WSForm\Render
 */
interface LegendRenderer {
    /**
     * @brief Render legend
     *
     * @param string $input Input for the field
     * @param string $class Class to add to the legend
     * @param string $align Align to add to the legend
     *
     * @return string Rendered HTML
     */
    public function render_legend( string $input, string $class = '', string $align = ''  ): string;
}