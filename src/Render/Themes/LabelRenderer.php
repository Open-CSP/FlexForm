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
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_label( string $input, array $args, Parser $parser, PPFrame $frame ): string;
}