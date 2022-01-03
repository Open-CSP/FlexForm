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
     * @param string $template
     * @param string $createId
     * @param string $write
     * @param string $slot
     * @param string $option
     * @param string $follow
     * @param string $fields
     * @param bool $leadingZero
     *
     * @return string Rendered HTML
     */
    public function render_create( string $template, string $createId, string $write, string $slot, string $option, string $follow, string $fields, bool $leadingZero ): string;
}