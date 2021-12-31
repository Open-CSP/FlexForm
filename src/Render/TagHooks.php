<?php

namespace WSForm\Render;

use Elasticsearch\Endpoints\Exists;
use Parser;
use PPFrame;
use WSForm\Core\Core;
use WSForm\Core\Protect;
use WSForm\Core\Validate;
use WSForm\Render\Themes\WSForm\Recaptcha;
use WSForm\WSFormException;

/**
 * Class TagHooks
 *
 * This class is responsible for rendering tags.
 */
class TagHooks {
    /**
     * @var ThemeStore
     */
    private $themeStore;

    /**
     * TagHooks constructor.
     *
     * @param ThemeStore $themeStore The theme store to use
     */
    public function __construct( ThemeStore $themeStore ) {
        $this->themeStore = $themeStore;
    }

    /**
     * @brief Function to render the Form itself.
     *
     * This function will call its subfunction render_form()
     * It will also add the JavaScript on the loadscript variable
     * \n Additional parameters
     * \li loadscript
     * \li showmessages
     * \li restrictions
     * \li no_submit_on_return
     * \li action
     * \li changetrigger
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array|string send to the MediaWiki Parser or send to the MediaWiki Parser with the message not a valid function
     * @throws WSFormException
     */
    public function renderForm( $input, array $args, Parser $parser, PPFrame $frame ) {
        global $wgUser, $wgEmailConfirmToEdit, $IP, $wgScript;

        Core::$chkSums = array();
        Core::$formId = uniqid();

        $ret = '';

        $parser->getOutput()->addModuleStyles( 'ext.wsForm.general.styles' );

        // Do we have some messages to show?
        if ( isset( $args['showmessages'] ) ) {
            if ( !isset ( $_COOKIE['wsform'] ) ) {
                return "";
            }

            $ret = '<div class="wsform alert-' . $_COOKIE['wsform']['type'] . '">' . $_COOKIE['wsform']['txt'] . '</div>';
            setcookie( "wsform[type]", "", time() - 3600, '/' );
            setcookie( "wsform[txt]", "", time() - 3600, '/' );

            return array( $ret, 'noparse' => true, "markerType" => 'nowiki' );
        }

        // Are there explicit 'restrictions' lifts set?
        if ( isset( $args['restrictions'] ) ) {
            // Parse the given restriction
            $restrictions = $parser->recursiveTagParse( $args['restrictions'], $frame );

            // Only allow anonymous users if the restrictions are lifted
            $allowAnonymous = strtolower( $restrictions ) === 'lifted';
        } else {
            // By default, deny anonymous users
            $allowAnonymous = false;
        }

        // Block the request if the user is not logged in and anonymous users are not allowed
        if ( $allowAnonymous === false && !$wgUser->isRegistered() ) {
            return wfMessage( "wsform-anonymous-user" )->parse();
        }

        if ( isset( $args['action'] ) && $args['action'] == 'addToWiki' ) {
            if ( $wgEmailConfirmToEdit === true && $wgUser->isRegistered() && !$wgUser->isEmailConfirmed() ) {
                return wfMessage( "wsform-unverified-email1" )->parse() . wfMessage( "wsform-unverified-email2" )->parse();
            }
        }

        $formId = isset( $args['id'] ) && $args['id'] !== '' ? $args['id'] : false;

        // Do we have scripts to load?
        if ( isset( $args['loadscript'] ) && $args['loadscript'] !== '' ) {
            $scriptToLoad = $args['loadscript'];

            // Validate the file name
            if ( preg_match( '/^[a-zA-Z0-9_-]+$/', $scriptToLoad ) === 1 ) {
                // Is this script already loaded?
                if ( !Core::isLoaded( $scriptToLoad ) ) {
                    if ( file_exists( $IP . '/extensions/WSForm/modules/customJS/loadScripts/' . $scriptToLoad . '.js' ) ) {
                        $scriptContent = file_get_contents( $IP . '/extensions/WSForm/modules/customJS/loadScripts/' . $scriptToLoad . '.js' );
                        if ( $scriptContent !== false ) {
                            if ( $formId !== false ) {
                                Core::includeJavaScriptConfig( 'wsForm_' . $scriptToLoad, $formId );
                            }

                            Core::includeInlineScript( $scriptContent );
                            Core::addAsLoaded( $scriptToLoad );
                        }
                    }
                }
            }
        }

        if ( isset( $args['no_submit_on_return'] ) ) {
            if ( !Core::isLoaded( 'keypress' ) ) {
                $noEnter = <<<SCRIPT
                $(document).on('keyup keypress', 'form input[type=\"text\"]', function(e) {
                    if(e.keyCode == 13) {
                      e.preventDefault();
                      return false;
                    }
                });
                
                $(document).on('keyup keypress', 'form input[type=\"search\"]', function(e) {
                    if(e.keyCode == 13) {
                      e.preventDefault();
                      return false;
                    }
                });
                
                $(document).on('keyup keypress', 'form input[type=\"password\"]', function(e) {
                    if(e.keyCode == 13) {
                      e.preventDefault();
                      return false;
                    }
                });
                SCRIPT;

                Core::includeInlineScript( $noEnter );
                Core::addAsLoaded( 'keypress' );
            }
        }

        if ( isset( $args['changetrigger'] ) && $args['changetrigger'] !== '' && isset( $args['id'] ) ) {
            $changeId = $args['id'];
            $changeCall = $args['changetrigger'];

            if ( preg_match( '/^[a-zA-Z0-9_]+$/', $changeId ) && preg_match( '/^[a-zA-Z0-9_]+$/', $changeCall) ) {
                // FIXME: Even though the changeId and changeCall are validated, they still allow for (quite weak) XSS.
                Core::includeInlineScript( "$('#" . $changeId . "').change(" . $changeCall . "(this));" );
            }
        }

        if ( isset( $args['messageonsuccess']) && $args['messageonsuccess'] !== '' ) {
            Core::includeInlineScript( 'var mwonsuccess = "' . htmlspecialchars( $args['messageonsuccess'] ) . '";' );
        }

        if ( isset( $args['show-on-select'] ) ) {
            Core::setShowOnSelectActive();
            $input = Core::checkForShowOnSelectValue( $input );
        }

        if ( Core::getRun() === false ) {
            // FIXME: Move to ResourceLoader
            $realUrl = str_replace( '/index.php', '', $wgScript );
            $ret = '<script type="text/javascript" charset="UTF-8" src="' . $realUrl . '/extensions/WSForm/WSForm.general.js"></script>' . "\n";

            Core::setRun( true );
        }

        $previousTheme = $this->themeStore->getFormThemeName();

        try {
            if ( isset( $args['theme'] ) && $args['theme'] !== '' ) {
                try {
                    $this->themeStore->setFormThemeName( $args['theme'] );
                } catch ( WSFormException $exception ) {
                    // Silently ignore and use the default theme
                }
            }

            // TODO: Fix the parsing and move some logic from render_form to here

            // Parse the input
            $input = $this->parseValue( $input, $parser, $frame );

            // Parse the arguments
            $args = $this->parseArguments( $args, $parser, $frame );

            // Render the actual contents of the form
            $ret .= $this->themeStore
                ->getFormTheme()
                ->getFormRenderer()
                ->render_form( $input, $args, $parser, $frame );
        } finally {
            $this->themeStore->setFormThemeName( $previousTheme );
        }

        if ( Core::isShowOnSelectActive() ) {
            $ret .= Core::createHiddenField( 'showonselect', '1' );
        }

        if ( Core::$secure ) {
            Protect::setCrypt( Core::$checksumKey );

            if ( Core::$runAsUser ) {
                $checksumUidName = Protect::encrypt( 'wsuid' );
                $uid = Protect::encrypt( $wgUser->getId() );

                Core::addCheckSum( 'secure', $checksumUidName, $uid, "all" );

                $ret .= '<input type="hidden" name="' . $checksumUidName . '" value="' . $uid . '">';
            }

            $chcksumName = Protect::encrypt( 'checksum' );

            if ( !empty( Core::$chkSums ) ) {
                $chcksumValue = Protect::encrypt( serialize( Core::$chkSums ) );

                $ret .= '<input type="hidden" name="' . $chcksumName . '" value="' . $chcksumValue . '">';
                $ret .= '<input type="hidden" name="formid" value="' . Core::$formId . '">';
            }

        }

        $ret .= $input . '</form>';

        if ( isset( $args['recaptcha-v3-action'] ) && ! Core::isLoaded( 'google-captcha' ) ) {
            $tmpCap = Recaptcha::render();

            if ( $tmpCap !== false ) {
                Core::addAsLoaded( 'google-captcha' );
                $ret = $tmpCap . $ret;
            }
        }

        if ( Core::$reCaptcha !== false  ) {
            if ( !isset( $args['id']) || $args['id'] === '' ) {
                return wfMessage( "wsform-recaptcha-no-form-id" )->parse();
            }

            if ( file_exists( $IP . '/extensions/WSForm/modules/recaptcha.js' ) ) {
                $rcaptcha = file_get_contents( $IP . '/extensions/WSForm/modules/recaptcha.js' );
                $replace = array(
                    '%%id%%',
                    '%%action%%',
                    '%%sitekey%%',
                );

                $with = array(
                    $args['id'],
                    Core::$reCaptcha,
                    Recaptcha::$rc_site_key
                );

                $rcaptcha = str_replace( $replace, $with, $rcaptcha );

                Core::includeInlineScript( $rcaptcha );
                Core::$reCaptcha = false;
            } else {
                return wfMessage( "wsform-recaptcha-no-js" )->parse();
            }
        }

        self::addInlineJavaScriptAndCSS();

        return array( $ret, "markerType" => 'nowiki' );
    }

    /**
     * @brief Function to render an input field.
     *
     * This function will look for the type of input field and will call its subfunction render_<inputfield>
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array send to the MediaWiki Parser
     * @throws WSFormException
     */
    public function renderField( $input, array $args, Parser $parser, PPFrame $frame ) {
        if ( !isset( $args['type'] ) ) {
            return [wfMessage("wsform-field-invalid")->parse(), "markerType" => 'nowiki'];
        }

        $type = $args['type'];

        if ( !Validate::validInputTypes( $type ) ) {
            return [wfMessage("wsform-field-invalid")->parse() . ": " . $type, "markerType" => 'nowiki'];
        }

        if ( isset( $args['parsepost'] ) && isset( $args['name'] )) {
            $parsePost = true;
            $parseName = $args['name'];
            unset( $args['parsepost'] );
        } else {
            $parsePost = false;
        }

        // Parse the arguments
        foreach ( $args as $name => $value ) {
            if ( ( strpos( $value, '{' ) !== false ) && ( strpos( $value, "}" ) !== false ) ) {
                $args[$name] = $parser->recursiveTagParse( $value, $frame );
            }
        }

        $input = isset( $args['noparse'] ) ?
            $input :
            $parser->recursiveTagParse( $input, $frame );

        $renderer = $this->themeStore
            ->getFormTheme()
            ->getFieldRenderer();

        switch ( $type ) {
            case 'text':
                if ( isset( $args['mwidentifier'] ) && $args['mwidentifier'] === 'datepicker' ) {
                    $parser->getOutput()->addModules( 'ext.wsForm.datePicker.scripts' );
                    $parser->getOutput()->addModuleStyles( 'ext.wsForm.datePicker.styles' );
                }

                $ret = $renderer->render_text( $args );
                break;
            case 'hidden':
                $ret = $renderer->render_hidden( $args );
                break;
            case 'secure':
                if ( !Core::$secure ) {
                    $ret = wfMessage( 'wsform-field-secure-not-available')->parse();
                    break;
                }

                $ret = $renderer->render_secure( $args );
                break;
            case 'search':
                $ret = $renderer->render_search( $args );
                break;
            case 'number':
                $ret = $renderer->render_number( $args );
                break;
            case 'radio':
                $ret = $renderer->render_radio( $args, $args['show-on-checked'] ?? '' );
                break;
            case 'checkbox':
                // TODO: Move most of the render_checkbox logic to here
                $ret = $renderer->render_checkbox( $args );
                break;
            case 'file':
                // TODO: Move most of the render_file logic to here
                $ret = $renderer->render_file( $args );
                break;
            case 'date':
                $ret = $renderer->render_date( $args );
                break;
            case 'month':
                $ret = $renderer->render_month( $args );
                break;
            case 'week':
                $ret = $renderer->render_week( $args );
                break;
            case 'time':
                $ret = $renderer->render_time( $args );
                break;
            case 'datetime':
                $ret = $renderer->render_datetime( $args );
                break;
            case 'datetimelocal':
                $ret = $renderer->render_datetimelocal( $args );
                break;
            case 'password':
                $ret = $renderer->render_password( $args );
                break;
            case 'email':
                $ret = $renderer->render_email( $args );
                break;
            case 'color':
                $ret = $renderer->render_color( $args );
                break;
            case 'range':
                $ret = $renderer->render_range( $args );
                break;
            case 'image':
                $imageArguments = [];
                foreach ( $args as $name => $value ) {
                    if ( Validate::validParameters( $name ) ) {
                        continue;
                    }

                    $imageArguments[$name] = $value;
                    Core::addCheckSum( "image", $name, $value );
                }

                $ret = $renderer->render_image( $args );
                break;
            case 'url':
                $ret = $renderer->render_url( $args );
                break;
            case 'tel':
                // TODO
                break;
            case 'option':
                // TODO
                break;
            case 'submit':
                // TODO
                break;
            case 'button':
                // TODO
                break;
            case 'reset':
                // TODO
                break;
            case 'textarea':
                // TODO
                break;
            case 'signature':
                // TODO
                break;
            case 'mobilescreenshot':
                // TODO
                break;
            default:
                // This should not happen, since the Validate class should have caught it
                throw new WSFormException( "Invalid field type", 0 );
        }

        if ( $parsePost === true && isset( $parseName ) ) {
            $ret .= '<input type="hidden" name="wsparsepost[]" value="' . $parseName . "\">\n";
        }

        return array( $ret, "markerType" => 'nowiki');
    }

    /**
     * @brief This is the initial call from the MediaWiki parser for the WSFieldset
     *
     * @param string $input Received from parser from begin till end
     * @param array $args List of argmuments for the Fieldset
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame MediaWiki pframe
     *
     * @return array with full rendered html for the parser to add
     * @throws WSFormException
     */
    public function renderFieldset( $input, array $args, Parser $parser, PPFrame $frame ) {
        $input = $parser->recursiveTagParseFully( $input, $frame );

        foreach ( $args as $name => $value ) {
            if ( ( strpos( $value, '{' ) !== false ) && ( strpos( $value, "}" ) !== false ) ) {
                $args[$name] = $parser->recursiveTagParse( $value, $frame );
            }
        }

        $output = $this->themeStore
            ->getFormTheme()
            ->getFieldsetRenderer()
            ->render_fieldset( $input, $args );

        return [
            $output,
            'markerType' => 'nowiki'
        ];
    }

    /**
     * @brief renderes the html legend (for use with fieldset)
     *
     * @param string $input Received from parser from begin till end
     * @param array $args List of argmuments for the Legend
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame MediaWiki pframe
     *
     * @return array with full rendered html for the parser to add
     * @throws WSFormException
     */
    public function renderLegend( $input, array $args, Parser $parser, PPFrame $frame ) {
        $class = $parser->recursiveTagParse( $args['class'], $frame ) ?? '';
        $align = $parser->recursiveTagParse( $args['align'], $frame ) ?? '';
        $input = $parser->recursiveTagParse( $input, $frame );

        $output = $this->themeStore
            ->getFormTheme()
            ->getLegendRenderer()
            ->render_legend( $input, $class, $align );

        return [
            $output,
            'markerType' => 'nowiki'
        ];
    }

    /**
     * @brief renders the html label
     *
     * @param string $input Received from parser from begin till end
     * @param array $args List of arguments for a Label
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame MediaWiki pframe
     *
     * @return array with full rendered html for the parser to add
     * @throws WSFormException
     */
    public function renderLabel( $input, array $args, Parser $parser, PPFrame $frame ) {
        $input = $parser->recursiveTagParse( $input, $frame );

        $output = $this->themeStore
            ->getFormTheme()
            ->getLabelRenderer()
            ->render_label( $input );

        return [$output, 'markerType' => 'nowiki'];
    }

    /**
     * @brief This is the initial call from the MediaWiki parser for the WSSelect
     *
     * @param $input string Received from parser from begin till end
     * @param array $args List of argmuments for the selectset
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame MediaWiki pframe
     *
     * @return array with full rendered html for the parser to add
     * @throws WSFormException
     */
    public function renderSelect( $input, array $args, Parser $parser, PPFrame $frame ) {
        $selectArguments = [];

        foreach ( $args as $name => $value ) {
            if ( !Validate::validParameters( $value ) ) {
                continue;
            }

            if ( $name === "name" && strpos( $value, '[]' ) === false ) {
                $value .= '[]';
            }

            $selectArguments[$name] = $parser->recursiveTagParse( $value, $frame );
        }

        $input = $parser->recursiveTagParseFully( $input, $frame );
        $placeholder = $args['placeholder'] ?? '';

        $select = $this->themeStore
            ->getFormTheme()
            ->getSelectRenderer()
            ->render_select( $input, $selectArguments, $placeholder );

        return [$select, 'markerType' => 'nowiki'];
    }

    /**
     * @brief This is the initial call from the MediaWiki parser for the WSToken
     *
     * @param $input string Received from parser from begin till end
     * @param array $args List of argmuments for the Fieldset
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame MediaWiki pframe
     *
     * @return array with full rendered html for the parser to add
     */
    public function renderToken( $input, array $args, Parser $parser, PPFrame $frame ) {
        // TODO

        global $wgOut, $IP, $wgDBname, $wgDBprefix;

        if( isset ( $wgDBprefix ) && !empty($wgDBprefix) ) {
            $prefix = '_' . $wgDBprefix;
        } else $prefix = '';

        $ret         = '<select data-inputtype="ws-select2"';
        $placeholder = false;


        foreach ( $args as $k => $v ) {
            if ( wsform\validate\validate::validParameters( $k ) ) {
                if ( $k == 'placeholder' ) {
                    $placeholder = $parser->recursiveTagParse( $v, $frame );
                } elseif( strtolower( $k ) === "multiple") {
                    $multiple = $parser->recursiveTagParse( $v, $frame );
                    if ( $multiple === "multiple" ) {
                        $ret .= 'multiple="multiple" ';
                    }
                } elseif( strtolower( $k ) === 'id' &&  \wsform\wsform::isLoaded( 'wsinstance-initiated' ) ) {
                    $ret .= 'data-wsselect2id="' . $v . '"';
                } else {
                    $ret .= $k . '="' . $parser->recursiveTagParse( $v, $frame ) . '" ';
                }
            }
        }

        $output = $parser->recursiveTagParse( $input );
        $id   = $parser->recursiveTagParse( $args['id'], $frame );

        $ret    .= '>';
        if( $placeholder !== false ){
            $ret .= '<option></option>';
        }
        $ret .= $output . '</select>' . "\n";
        $out    = "";
        if ( ! \wsform\wsform::isLoaded( 'wsinstance-initiated' ) ){
            $out    .= '<input type="hidden" id="select2options-' . $id . '" value="';
        } else {
            $out    .= '<input type="hidden" data-wsselect2options="select2options-' . $id . '" value="';
        }

        if( isset( $args['input-length-trigger'] ) && $args['input-length-trigger' !== '' ] ) {
            $iLength = trim( $args['input-length-trigger'] );
        } else $iLength = 3;

        if ( isset( $args['json'] ) && isset( $args['id'] ) ) {
            if ( strpos( $args['json'], 'semantic_ask' ) ) {
                $json = $args['json'];
            } else {
                $json = $parser->recursiveTagParse( $args['json'], $frame );
            }
            $out .= "var jsonDecoded = decodeURIComponent( '" . urlencode( $json ) . "' );\n";
        }


        $out .= "$('#" . $id . "').select2({";

        $callb = '';

        $mwdb = $wgDBname . $prefix;

        if ( $placeholder !== false ) {
            $out .= "placeholder: '" . $placeholder . "',";
        }

        if ( isset( $args['json'] ) && isset( $args['id'] ) ) {

            $out .= "\ntemplateResult: testSelect2Callback,\n";
            $out .= "\nescapeMarkup: function (markup) { return markup; },\n";
            $out .= "\nminimumInputLength: $iLength,\n";
            $out .= "\najax: { url: jsonDecoded, delay:500, dataType: 'json',"."\n";
            $out .= "\ndata: function (params) { var queryParameters = { q: params.term, mwdb: '".$mwdb."' }\n";
            $out .= "\nreturn queryParameters; }}";
            $callb= '';
            if ( isset( $args['callback'] ) ) {
                if ( isset( $args['template'] ) ) {
                    $templ = ", '" . $args['template'] . "'";
                } else $templ = '';
                $cb  = $parser->recursiveTagParse( $args['callback'], $frame );
                $callb = "$('#" . $id . "').on('select2:select', function(e) { " . $cb . "('" . $id . "'" . $templ . ")});\n";
                $callb .= "$('#" . $id . "').on('select2:unselect', function(e) { " . $cb . "('" . $id . "'" . $templ . ")});\n";
            }
        }
        if( isset( $args['allowtags'] ) ) {
            if ( isset( $args['json'] ) && isset( $args['id'] ) ) {
                $out .= ",\ntags: true";
            } else {
                $out .= "\ntags: true";
            }
        }
        if( isset( $args['allowclear'] ) && isset( $args['placeholder'] ) ) {
            if ( ( isset( $args['json'] ) ) || isset( $args['allowtags'] ) ) {
                $out .= ",\nallowClear: true";
            } else {
                $out .= "\nallowClear: true";
            }
        }

        /*
                if( $multiple !== false && strtolower( $multiple ) === "multiple" ) {

                    if ( ( isset( $args['json'] ) && isset( $args['id'] ) ) || isset( $args['allowtags'] ) || isset( $args['allowclear'] ) ) {
                        $out .= ",\nmultiple: true";
                    } else {
                        $out .= "\nmultiple: true";
                    }
                } else {
                    if ( ( isset( $args['json'] ) && isset( $args['id'] ) ) || isset( $args['allowtags'] ) || isset( $args['allowclear'] ) ) {
                        $out .= ",\nmultiple: false";
                    } else {
                        $out .= "\nmultiple: false";
                    }
                }
        */
        $out .= '});';
        $callb .= "$('select').trigger('change');\"\n";
        $out .= $callb . ' />';
        $lcallback = '';
        if(isset($args['loadcallback'])) {
            if(! wsform\wsform::isLoaded($args['loadcallback'] ) ) {
                if ( file_exists( $IP . '/extensions/WSForm/modules/customJS/wstoken/' . $args['callback'] . '.js' ) ) {
                    $lf  = file_get_contents( $IP . '/extensions/WSForm/modules/customJS/wstoken/' . $args['callback'] . '.js' );
                    $lcallback = "<script>$lf</script>\n";
                    wsform\wsform::includeInlineScript( $lf );
                    wsform\wsform::addAsLoaded( $args['loadcallback'] );
                }
            }
        }
        $attach = "<script>wachtff(attachTokens, true );</script>";
        //wsform\wsform::includeInlineScript( 'document.addEventListener("DOMContentLoaded", function() { wachtff(attachTokens, true); }, false);' );
        //$wgOut->addHTML( $out );

        $ret = $ret . $out . $attach;
        self::addInlineJavaScriptAndCSS();
        return array( $ret, "markerType" => 'nowiki' );
    }

    /**
     * @brief Function to render the Page Edit options.
     *
     * This function will call its subfunction render_edit()
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array send to the MediaWiki Parser
     * @throws WSFormException
     */
    public function renderEdit( $input, array $args, Parser $parser, PPFrame $frame ) {
        foreach ( $args as $name => $value ) {
            if ( ( strpos( $value, '{' ) !== false ) && ( strpos( $value, "}" ) !== false ) ) {
                $args[$name] = $parser->recursiveTagParse( $value, $frame );
            }
        }

        $output = $this->themeStore
            ->getFormTheme()
            ->getEditRenderer()
            ->render_edit( $args );

        return [
            $output,
            'noparse' => true,
            "markerType" => 'nowiki'
        ];
    }

    /**
     * @brief Function to render the Page Create options.
     *
     * This function will call its subfunction render_create()
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array send to the MediaWiki Parser
     * @throws WSFormException
     */
    public function renderCreate( $input, array $args, Parser $parser, PPFrame $frame ) {
        foreach ( $args as $name => $value ) {
            if ( ( strpos( $value, '{' ) !== false ) && ( strpos( $value, "}" ) !== false ) ) {
                $args[$name] = $parser->recursiveTagParse( $value, $frame );
            }
        }

        // TODO

        $output = $this->themeStore
            ->getFormTheme()
            ->getCreateRenderer()
            ->render_create( $args );

        return [
            $output,
            'noparse' => true,
            "markerType" => 'nowiki'
        ];

    }

    /**
     * @brief Function to render the email options.
     *
     * This function will call its subfunction render_mail()
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array send to the MediaWiki Parser or
     * @throws WSFormException
     */
    public function renderEmail( $input, array $args, Parser $parser, PPFrame $frame ) {
        $mailArguments = [];

        foreach ( $args as $name => $value ) {
            switch ( $name ) {
                case "to":
                    $mailArguments["mwmailto"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "from":
                    $mailArguments["mwmailfrom"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "cc":
                    $mailArguments["mwmailcc"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "bcc":
                    $mailArguments["mwmailbcc"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "replyto":
                    $mailArguments["mwmailreplyto"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "subject":
                    $mailArguments["mwmailsubject"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "type":
                    $mailArguments["mwmailtype"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "content":
                    $mailArguments["mwmailcontent"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "job":
                    $mailArguments["mwmailjob"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "header":
                    $mailArguments["mwmailheader"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "footer":
                    $mailArguments["mwmailfooter"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "html":
                    $mailArguments["mwmailhtml"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "attachment":
                    $mailArguments["mwmailattachment"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "template":
                    $mailArguments["mwmailtemplate"] = $parser->recursiveTagParse( $value, $frame );
                    break;
                case "parselast":
                    $mailArguments["mwparselast"] = "true";
                    break;
            }
        }

        $base64content = base64_encode( $parser->recursiveTagParse( $input, $frame ) );
        $output = $this->themeStore
            ->getFormTheme()
            ->getEmailRenderer()
            ->render_mail( $mailArguments, $base64content );

        return [
            $output,
            'noparse' => true,
            'markerType' => 'nowiki'
        ];
    }

    /**
     * @brief Function to render a WSInstance.
     *
     * @param string $input Parser Between beginning and end
     * @param array $args Arguments for the field
     * @param Parser $parser MediaWiki Parser
     * @param PPFrame $frame MediaWiki PPFrame
     *
     * @return array send to the MediaWiki Parser or
     */
    public function renderInstance( $input, array $args, Parser $parser, PPFrame $frame ) {
        // TODO

        global $IP, $wgScript;
        $realUrl = str_replace( '/index.php', '', $wgScript );


        // Add move, delete and add button with classes
        $parser->getOutput()->addModuleStyles( 'ext.wsForm.Instance.styles' );

        if ( !Core::isLoaded( 'wsinstance-initiated' ) ) {
            Core::addAsLoaded( 'wsinstance-initiated' );
        }

        $output = $parser->recursiveTagParse( $input, $frame );

        if ( !Core::isLoaded( 'wsinstance-initiated' ) ) {
            Core::addAsLoaded( 'wsinstance-initiated' );
        }

        // TODO: This:
        $ret = wsform\instance\render::render_instance( $args, $output );

        Core::removeAsLoaded( 'wsinstance-initiated' );

        if ( !Core::isLoaded( 'multipleinstance' ) ) {
            if ( file_exists( $IP . '/extensions/WSForm/modules/instances/wsInstance.js' ) ) {
                $ls =  $realUrl . '/extensions/WSForm/modules/instances/wsInstance.js';
                $ret = '<script type="text/javascript" charset="UTF-8" src="' . $ls . '"></script>' . $ret ;

                Core::addAsLoaded( 'multipleinstance' );
            }
        }

        return array( $ret, 'noparse' => true, "markerType" => 'nowiki' );
    }

    /**
     * Helper function to add the currently configured inline JavaScript and CSS to the OutputPage.
     *
     * @param bool $parentConfig
     */
    private function addInlineJavaScriptAndCSS( $parentConfig = false ) {
        $scripts = array_unique( Core::getJavaScriptToBeIncluded() );
        $csss = array_unique( Core::getCSSToBeIncluded() );
        $jsconfigs = Core::getJavaScriptConfigToBeAdded();
        $out = \RequestContext::getMain()->getOutput();

        if ( !empty( $scripts ) ) {
            foreach ( $scripts as $js ) {
                $out->addInlineScript( $js );
            }

            Core::cleanJavaScriptList();
        }

        if ( !empty( $csss ) ) {
            foreach ( $csss as $css ) {
                $out->addInlineStyle( $css );
            }

            Core::cleanCSSList();
        }

        if ( !empty( $jsconfigs ) ) {
            if( $parentConfig ) {
                $out->addJsConfigVars( array( $jsconfigs ) );
            } else {
                $out->addJsConfigVars( array( 'wsformConfigVars' => $jsconfigs ) );
            }

            Core::cleanJavaScriptConfigVars();
        }
    }
}