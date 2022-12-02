<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering instance fields.
 *
 * @package FlexForm\Render
 */
interface InstanceRenderer {
    /**
     * @brief Render instance
     *
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     * @param string $content The innerHTML of the instance field
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_instance( Parser $parser, PPFrame $frame, string $content, array $args ): string;
}