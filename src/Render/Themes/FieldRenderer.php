<?php


namespace WSForm\Render;

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
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_text( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render hidden input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_hidden( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render hidden input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_secure( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render search input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_search( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render number input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_number( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render radio input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_radio( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render checkbox input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_checkbox( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render file input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_file( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render date input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_date( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render month input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_month( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render week input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_week( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render time input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_time( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render DateTime input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_datetime( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render local DateTime input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_datetimelocal( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render password input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_password( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render email input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_email( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render color input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_color( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render range input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_range( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render image input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_image( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render URL input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_url( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render telephone number input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_tel( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render options for select input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_option( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render submit
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_submit( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render button field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_button( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render reset input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_reset( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render textarea input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_textarea( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render signature input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_signature( string $input, array $args, Parser $parser, PPFrame $frame ): string;

    /**
     * @brief Render mobile screenshot file input field
     *
     * @param string $input Input for the field
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame Current PPFrame
     *
     * @return string Rendered HTML
     */
    public function render_mobilescreenshot( string $input, array $args, Parser $parser, PPFrame $frame ): string;
}