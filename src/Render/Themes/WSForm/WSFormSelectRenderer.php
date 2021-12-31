<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Render\Themes\SelectRenderer;

class WSFormSelectRenderer implements SelectRenderer {
    /**
     * @inheritDoc
     */
    public function render_select( string $input, array $args, string $placeholder ): string {
        $ret = '<select ';

        foreach ( $args as $name => $value ) {
            $ret .= sprintf( '%s="%s"', htmlspecialchars( $name ), htmlspecialchars( $value ) );
        }

        $ret .= '>';

        if ( $placeholder !== '' ) {
            $ret .= '<option value="" disabled selected>' . htmlspecialchars( $placeholder ) . '</option>';
        }

        $ret .=  $input . '</select>';

        return $ret;
    }

}