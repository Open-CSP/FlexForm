<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\OptGroupRenderer;

class PlainOptGroupRenderer implements OptGroupRenderer {
    /**
     * @inheritDoc
     */
    public function render_optgroup( string $input, array $args ): string {
        $ret = '<optgroup ';

        foreach ( $args as $name => $value ) {
            $ret .= sprintf( '%s="%s" ', htmlspecialchars( $name ), htmlspecialchars( $value ) );
        }

        return $ret . '>' . $input . '</optgroup>';
    }
}