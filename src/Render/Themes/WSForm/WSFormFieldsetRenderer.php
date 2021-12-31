<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Render\Themes\FieldsetRenderer;

class WSFormFieldsetRenderer implements FieldsetRenderer {
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