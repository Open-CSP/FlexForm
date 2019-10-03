<?php


namespace wsform\create;

use wsform\validate\validate;

class render {

    /**
     * Render WSCreate
     *
     * @param $args
     *
     * @return string Formatted HTML
     */
    public static function render_create( $args ) {
        $template = "";
        $wswrite  = "";
        $wsoption = "";
        $wsfields = "";
        $wsfollow = "";
        $wsleadbyzero = "";

        if(isset($args['mwfields']) && $args['mwfields'] != '') {
            $def = '<input type="hidden" name="mwcreatemultiple[]" value="';
            $div = '-^^-';
            foreach ( $args as $k => $v ) {
                if ( $k == "mwtemplate" ) {
                    $template = $v;
                }

                if ( $k == "mwwrite" ) {
                    $wswrite = $v;
                }

                if ( $k == "mwoption" ) {
                    $wsoption = $v;
                }

                if ( $k == "mwfields" ) {
                    $wsfields = $v;
                }

            }
            if($template === '') {
                return 'No valid template for creating a page.';
            }
            if($wswrite === '') {
                return 'No valid title for creating a page.';
            }
            $def .= $template . $div . $wswrite . $div . $wsoption . $div . $wsfields . '">';
            return $def;
        } else {

            foreach ( $args as $k => $v ) {
                if ( $k == "mwtemplate" ) {
                    $template = '<input type="hidden" name="mwtemplate" value="' . $v . '">' . "\n";
                }

                if ( $k == "mwwrite" ) {
                    $wswrite = '<input type="hidden" name="mwwrite" value="' . $v . '">' . "\n";
                }

                if ( $k == "mwoption" ) {
                    $wsoption = '<input type="hidden" name="mwoption" value="' . $v . '">' . "\n";
                }

                if ( $k == "mwfollow" ) {
                    $wsfollow = '<input type="hidden" name="mwfollow" value="true">' . "\n";
                }

                if ( $k == "mwleadingzero" ) {
                    $wsfollow = '<input type="hidden" name="mwleadingzero" value="true">' . "\n";
                }

                /*
                if ( $k == "mwfields" ) {
                    $wsfields = '<input type="hidden" name="mwfields" value="' . $v . '">' . "\n";
                }
                */
            }
        }


        return $template . $wswrite . $wsoption . $wsfollow;


    }

}