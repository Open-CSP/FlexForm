<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering email fields.
 *
 * @package WSForm\Render
 */
interface EmailRenderer {
    /**
     * @brief Render mail
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_mail( string $input, array $args, Parser $parser, PPFrame $frame ): string;
}