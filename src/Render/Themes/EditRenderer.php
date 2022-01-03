<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering edit fields.
 *
 * @package WSForm\Render
 */
interface EditRenderer {
    /**
     * @brief Render edit
     *
     * @param string $target
     * @param string $template
     * @param string $formfield
     * @param string $usefield
     * @param string $slot
     * @param string $value
     *
     * @return string Rendered HTML
     */
    public function render_edit( string $target, string $template, string $formfield, string $usefield, string $slot, string $value ): string;
}