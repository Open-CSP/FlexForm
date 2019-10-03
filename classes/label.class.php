<?php


namespace wsform\label;

use wsform\validate\validate;

class render {

    /**
     * @brief Render Label Input field as HTML
     *
     * @param  array $args Arguments for the input field
     * @param  boolean $input not used
     *
     * @return string Rendered HTML
     */
    public static function render_label( $args, $input = false ) {

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