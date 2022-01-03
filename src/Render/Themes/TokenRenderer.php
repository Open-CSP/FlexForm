<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering tokens.
 *
 * @package WSForm\Render
 */
interface TokenRenderer {
    /**
     * @brief Render token
     *
     * @param string $input Input for the field (should be parsed)
     * @param string $mwDB
     * @param string $id
     * @param int $inputLengthTrigger
     * @param string|null $placeholder
     * @param string|null $multiple
     * @param string|null $json
     * @param string|null $callback
     * @param string|null $template
     * @param array $additionalArguments
     *
     * @return string Rendered HTML
     */
    public function render_token( string $input, string $mwDB, string $id, int $inputLengthTrigger, ?string $placeholder, ?string $multiple, ?string $json, ?string $callback, ?string $template, array $additionalArguments ): string;
}