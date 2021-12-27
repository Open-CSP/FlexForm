<?php

namespace WSForm\Render;

use Parser;
use PPFrame;

/**
 * Class Theme
 *
 * This class is responsible for rendering a theme.
 */
interface Theme {
    /**
     * Render a WSField.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderField( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSEdit.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderEdit( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSCreate.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderCreate( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSEmail.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderEmail( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSInstance.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderInstance( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSForm.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderForm( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSFieldset.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderFieldset( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSSelect.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderSelect( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSToken.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderToken( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSLegend.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderLegend( string $input, array $args, Parser $parser, PPFrame $frame );

    /**
     * Render a WSLabel.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string
     */
    public function renderLabel( string $input, array $args, Parser $parser, PPFrame $frame );
}