<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering select fields.
 *
 * @package WSForm\Render
 */
interface SelectRenderer
{
    /**
     * @brief Render select
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_select(string $input, array $args, Parser $parser, PPFrame $frame): string;
}