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
     * @param string $input Input for the field (should be parsed)
     * @param array $args Arguments for the field (should be parsed)
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_create( string $input, array $args, Parser $parser, PPFrame $frame ): string;
}