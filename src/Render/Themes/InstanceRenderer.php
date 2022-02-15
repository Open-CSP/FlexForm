<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering instance fields.
 *
 * @package WSForm\Render
 */
interface InstanceRenderer {
    /**
     * @brief Render instance
     *
     * @param string $content The innerHTML of the instance field
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_instance( string $content, array $args ): string;
}