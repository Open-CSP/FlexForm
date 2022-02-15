<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\LegendRenderer;

class PlainLegendRenderer implements LegendRenderer {
    /**
     * @inheritDoc
     */
    public function render_legend( string $input, string $class, string $align ): string {
        $ret = '<legend ';

        if ( $class !== '' ) {
            $ret .= ' class="' . htmlspecialchars( $class ) . '" ';
        }

        if ( $align !== '' ) {
            $ret .= ' align="' . htmlspecialchars( $align ) . '"';
        }

        return $ret . '>' . htmlspecialchars( $input ) . '</legend>';
    }
}