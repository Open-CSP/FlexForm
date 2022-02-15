<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering email fields.
 *
 * @package FlexForm\Render
 */
interface EmailRenderer {
    /**
     * @brief Render mail
     *
     * @param array $mailArguments Arguments for the field
     * @param string $base64content The content of the email as base64
     *
     * @return string Rendered HTML
     */
    public function render_mail( array $mailArguments, string $base64content ): string;
}