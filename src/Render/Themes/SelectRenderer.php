<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering select fields.
 *
 * @package WSForm\Render
 */
interface SelectRenderer {
    /**
     * @brief Render select
     *
     * @param string $input Inner content of the select field
     * @param string|null $placeholder Placeholder text
     * @param array $selectedValues
     * @param array $options
     * @param array $additionalArgs
     * @return string Rendered HTML
     */
    public function render_select( string $input, ?string $placeholder, array $selectedValues, array $options, array $additionalArgs ): string;
}