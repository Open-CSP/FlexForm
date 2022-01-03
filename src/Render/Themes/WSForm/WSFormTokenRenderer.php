<?php


namespace WSForm\Render\Themes\WSForm;

use WSForm\Core\Core;
use WSForm\Render\Themes\TokenRenderer;

class WSFormTokenRenderer implements TokenRenderer {
    /**
     * @inheritDoc
     */
    public function render_token( string $input, string $mwDB, string $id, int $inputLengthTrigger, ?string $placeholder, ?string $multiple, ?string $json, ?string $callback, ?string $template, bool $allowTags, bool $allowClear, array $additionalArguments ): string {
        // Escape the ID
        $id = htmlspecialchars( $id );

        // Start by building the opening tag
        $selectTag = '<select data-inputtype="ws-select2"';

        if ( $multiple !== null ) {
            $selectTag .= ' multiple="multiple"';
        }

        if ( $id !== null && Core::isLoaded( 'wsinstance-initiated' ) ) {
            $selectTag .= ' data-wsselect2id="' . htmlspecialchars( $id ) . '"';
        }

        foreach ( $additionalArguments as $name => $value ) {
            $selectTag .= ' ' . htmlspecialchars( $name ) . '="' . htmlspecialchars( $value ) . '"';
        }

        $selectTag .= '>';

        // If we have a placeholder, we put an empty option inside of the select tag
        if( $placeholder !== null ){
            $selectTag .= '<option></option>';
        }

        // Place the fully parsed contents of the "wstoken" tag inside of the select
        $selectTag .= $input . '</select>' . "\n";

        $selectJavascript = '';

        if ( !Core::isLoaded( 'wsinstance-initiated' ) ) {
            $selectJavascript .= '<input type="hidden" id="select2options-' . $id . '" value="';
        } else {
            $selectJavascript .= '<input type="hidden" data-wsselect2options="select2options-' . $id . '" value="';
        }

        if ( $json !== null ) {
            $selectJavascript .= "var jsonDecoded = decodeURIComponent( '" . urlencode( $json ) . "' );\n";
        }

        $selectJavascript .= "$('#" . $id . "').select2({";

        if ( $placeholder !== null ) {
            $selectJavascript .= "placeholder: '" . htmlspecialchars( $placeholder ) . "',";
        }

        $callbackJavascript = '';

        if ( $json !== null ) {
            $selectJavascript .= "\ntemplateResult: testSelect2Callback,\n";
            $selectJavascript .= "\nescapeMarkup: function (markup) { return markup; },\n";
            $selectJavascript .= "\nminimumInputLength: $inputLengthTrigger,\n";
            $selectJavascript .= "\najax: { url: jsonDecoded, delay:500, dataType: 'json',"."\n";
            $selectJavascript .= "\ndata: function (params) { var queryParameters = { q: params.term, mwdb: '" . htmlspecialchars( $mwDB ) . "' }\n";
            $selectJavascript .= "\nreturn queryParameters; }}";

            if ( $callback !== null ) {
                $template = $template ?? '';

                $callbackJavascript = "$('#" . $id . "').on('select2:select', function(e) { " . $callback . "('" . $id . "'" . $template . ")});\n";
                $callbackJavascript .= "$('#" . $id . "').on('select2:unselect', function(e) { " . $callback . "('" . $id . "'" . $template . ")});\n";
            }
        }

        // TODO: Rewrite beneath this line
        // TODO: Rename callb
        // TODO: Testing!
        // TODO: Rewrite this so it is not as ugly as it is now

        if ( $allowTags ) {
            if ( $json !== null ) {
                $selectJavascript .= ",\ntags: true";
            } else {
                $selectJavascript .= "\ntags: true";
            }
        }

        if ( $allowClear && $placeholder !== null ) {
            if ( $json !== null || $allowTags !== null ) {
                $selectJavascript .= ",\nallowClear: true";
            } else {
                $selectJavascript .= "\nallowClear: true";
            }
        }

        $selectJavascript .= '});';
        $callbackJavascript .= "$('select').trigger('change');\"\n";
        $selectJavascript .= $callbackJavascript . ' />';

        if(isset($args['loadcallback'])) {
            if(! Core::isLoaded($args['loadcallback'] ) ) {
                if ( file_exists( $IP . '/extensions/WSForm/modules/customJS/wstoken/' . $args['callback'] . '.js' ) ) {
                    $lf  = file_get_contents( $IP . '/extensions/WSForm/modules/customJS/wstoken/' . $args['callback'] . '.js' );

                    Core::includeInlineScript( $lf );
                    Core::addAsLoaded( $args['loadcallback'] );
                }
            }
        }

        // FIXME: Rename 'wachtff' to something that makes more sense
        return $selectTag . $selectJavascript . "<script>wachtff(attachTokens, true );</script>";
    }
}