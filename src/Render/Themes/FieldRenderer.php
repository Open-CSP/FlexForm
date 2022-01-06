<?php

namespace WSForm\Render\Themes;

use Parser;
use PPFrame;

/**
 * Interface for rendering form fields.
 *
 * @package WSForm\Render
 */
interface FieldRenderer {
    /**
     * @brief Render text input field
     *
     * @param array $args Arguments for the field (should be parsed)
     *
     * @return string Rendered HTML
     */
    public function render_text( array $args ): string;

    /**
     * @brief Render hidden input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_hidden( array $args ): string;

    /**
     * @brief Render hidden input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_secure( array $args ): string;

    /**
     * @brief Render search input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_search( array $args ): string;

    /**
     * @brief Render number input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_number( array $args ): string;

    /**
     * @brief Render radio input field
     *
     * @param array $args Arguments for the field
     * @param string $showOnChecked
     *
     * @return string Rendered HTML
     */
    public function render_radio( array $args, string $showOnChecked = '' ): string;

    /**
     * @brief Render checkbox input field
     *
     * @param array $args Arguments for the field
     * @param string $showOnChecked
     * @param string $showOnUnchecked
     * @param string $default
     * @param string $defaultName
     *
     * @return string Rendered HTML
     */
    public function render_checkbox( array $args, string $showOnChecked = '', string $showOnUnchecked = '', string $default = '', string $defaultName = '' ): string;

    /**
     * @brief Render file input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_file( array $args ): string;

    /**
     * @brief Render date input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_date( array $args ): string;

    /**
     * @brief Render month input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_month(array $args ): string;

    /**
     * @brief Render week input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_week( array $args ): string;

    /**
     * @brief Render time input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_time( array $args ): string;

    /**
     * @brief Render DateTime input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_datetime( array $args ): string;

    /**
     * @brief Render local DateTime input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_datetimelocal( array $args ): string;

    /**
     * @brief Render password input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_password( array $args ): string;

    /**
     * @brief Render email input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_email( array $args ): string;

    /**
     * @brief Render color input field
     *
     * @param array $args Arguments for the field (should be parsed)
     *
     * @return string Rendered HTML
     */
    public function render_color( array $args ): string;

    /**
     * @brief Render range input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_range( array $args ): string;

    /**
     * @brief Render image input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_image( array $args ): string;

    /**
     * @brief Render URL input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_url( array $args ): string;

    /**
     * @brief Render telephone number input field
     *
     * @param array $args Arguments for the field
     *
     * @return string Rendered HTML
     */
    public function render_tel( array $args ): string;

    /**
     * @brief Render options for select input field
     *
     * @param string $input Input for the field (should be parsed)
     * @param string $value
     * @param string|null $showOnSelect
     * @param bool $isSelected
     * @param array $additionalArgs
     * @return string Rendered HTML
     */
    public function render_option( string $input, string $value, ?string $showOnSelect, bool $isSelected, array $additionalArgs ): string;

    /**
     * @brief Render submit
     *
     * @param string $input Input for the field (should be parsed)
     * @param array $args Arguments for the field (should be parsed)
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_submit( array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render button field
     *
     * @param string $input Input for the field (should be parsed)
     * @param array $args Arguments for the field (should be parsed)
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_button( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render reset input field
     *
     * @param string $input Input for the field (should be parsed)
     * @param array $args Arguments for the field (should be parsed)
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_reset( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render textarea input field
     *
     * @param string $input Input for the field (should be parsed)
     * @param array $args Arguments for the field (should be parsed)
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_textarea( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render signature input field
     *
     * @param string $input Input for the field (should be parsed)
     * @param array $args Arguments for the field (should be parsed)
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_signature( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render mobile screenshot file input field
     *
     * @param string $input Input for the field (should be parsed)
     * @param array $args Arguments for the field (should be parsed)
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_mobilescreenshot( string $input, array $args, Parser $parser, PPFrame $frame ): string;
}