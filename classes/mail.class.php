<?php


namespace wsform\mail;

use wsform\validate\validate;

class render {

    /**
     * @brief Render Mail HTML input field
     *
     * @param  array $args Arguments for the input field
     *
     * @return string Rendered HTML
     */
    public static function render_mail( $args ) {
        $t1 = '<input type="hidden" name="%s" value="%s">' . "\n";
        $template = "";
        foreach ( $args as $k => $v ) {
            if ( validate::validEmailParameters( $k ) ) {
                if ( $k == "to" ) {
                    $template .= sprintf( $t1, 'mwmailto', $v );
                }
                if ( $k == "from" ) {
                    $template .= sprintf( $t1, 'mwmailfrom', $v );
                }
                if ( $k == "cc" ) {
                    $template .= sprintf( $t1, 'mwmailcc', $v );
                }
                if ( $k == "bcc" ) {
                    $template .= sprintf( $t1, 'mwmailbcc', $v );
                }
                if ( $k == "subject" ) {
                    $template .= sprintf( $t1, 'mwmailsubject', $v );
                }
                if ( $k == "type" ) {
                    $template .= sprintf( $t1, 'mwmailtype', $v );
                }
                if ( $k == "content" ) {
                    $template .= sprintf( $t1, 'mwmailcontent', $v );
                }
                if ( $k == "job" ) {
                    $template .= sprintf( $t1, 'mwmailjob', $v );
                }
                if ( $k == "header" ) {
                    $template .= sprintf( $t1, 'mwmailheader', $v );
                }
                if ( $k == "footer" ) {
                    $template .= sprintf( $t1, 'mwmailfooter', $v );
                }
                if ( $k == "html" ) {
                    $template .= sprintf( $t1, 'mwmailhtml', $v );
                }
                if ( $k == "template" ) {
                    $template .= sprintf( $t1, 'mwmailtemplate', $v );
                }
            }

        }

        return $template;


    }

}