<?php

namespace FlexForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering forms.
 *
 * @package FlexForm\Render
 */
interface FormRenderer {
    /**
     * @brief Render form
     *
     * @param string $input Input for the field (should be fully parsed)
     * @param string $actionUrl The form "action" URL
     * @param string $mwReturn The return value to be included as a hidden field
     * @param string $formId The ID of the form
     * @param string|null $messageOnSuccess The messageOnSuccess to be included as a hidden field
     * @param string|null $wikiComment The comment to be included as a hidden field
     * @param string|null $action The action to be included as a hidden field
     * @param string|null $extension
     * @param string|null $autosaveType
     * @param string|null $additionalClass
     * @param bool $showOnSelect
     * @param array $additionalArgs
     * @return string Rendered HTML
     */
    public function render_form( string $input, string $actionUrl, string $mwReturn, string $formId, ?string $messageOnSuccess, ?string $wikiComment, ?string $action, ?string $extension, ?string $autosaveType, ?string $additionalClass, bool $showOnSelect, array $additionalArgs ): string;
}