<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Render\LabelRenderer;
use Parser;
use PPFrame;

class WSFormLabelRenderer implements LabelRenderer {
    /**
     * @inheritDoc
     */
    public function render_label( string $input, array $args, Parser $parser, PPFrame $frame ): string {

        $ret = '<label ';
        if ( isset( $args['text'] ) ) {
            $txt = $args['text'];
        } else {
            $txt = "";
        }
        foreach ( $args as $k => $v ) {
            if ( validate::validParameters( $k ) ) {
                $ret .= $k . '="' . $v . '" ';
            }
        }
        $ret .= ">" . $txt . "</label>\n";

        return $ret;
    }

}