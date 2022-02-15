<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\FieldsetRenderer;

class PlainFieldsetRenderer implements FieldsetRenderer {
    /**
     * @inheritDoc
     */
    public function render_fieldset( string $input, array $args ): string {
        $ret = '<fieldset ';

        foreach ( $args as $name => $value ) {
            $ret .= sprintf( '%s="%s" ', htmlspecialchars( $name ), htmlspecialchars( $value ) );
        }

        return $ret . '>' . $input . '</fieldset>';
    }
}