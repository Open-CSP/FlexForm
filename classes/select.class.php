<?php


namespace wsform\select;

use wsform\validate\validate;

class render {


    /**
     * @brief Render Select Input field as HTML
     *
     * @param  array $args Arguments for the input field
     * @param  boolean $input not used
     *
     * @return string Rendered HTML
     */
    public static function render_select( $args, $input = false ) {
        $ret = '<select ';
        foreach ( $args as $k => $v ) {
            if ( validate::validParameters( $k ) ) {
                if ( $k == "name" && strpos( $v, '[]' ) === false ) {
                    $v .= '[]';
                }
                $ret .= $k . '="' . $v . '" ';
            }
        }
        $ret .= ">\n";
        if ( isset( $args['placeholder'] ) ) {
            if ( ! isset( $args['selected'] ) ) {
                $ret .= '<option value="" disabled selected>' . $args['placeholder'] . '</option>';
            } else {
                $ret .= '<option value="" disabled>' . $args['placeholder'] . '</option>';
            }
        }
        if ( isset( $args['selected'] ) ) {
            $selected = explode( ',', $args['selected'] );
        } else {
            $selected = false;
        }
        if ( isset( $args['options'] ) ) {
            $options = explode( ",", $args['options'] );
            foreach ( $options as $option ) {
                $line = explode( '::', $option );
                if ( $selected && in_array( $line[0], $selected ) ) {
                    $ret .= '<option selected value="' . $line[1] . '">' . $line[0] . '</option>' . "\n";
                } else {
                    $tmp = \wsform\wsform::getValue( $line[1] );
                    $ret .= '<option value="' . $line[1] . '">' . $line[0] . '</option>' . "\n";
                }
            }
        }
        $ret .= "</select>\n";
        return $ret;
    }

}