<?php

namespace WSForm\Render;

use Elasticsearch\Endpoints\Exists;
use MediaWiki\Revision\RevisionRecord;
use Parser;
use PPFrame;
use RequestContext;
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

            if ( isset( $args['messageonsuccess'] ) ) {
                $messageOnSuccess = $parser->recursiveTagParse( $args['messageonsuccess'], $frame);
                unset( $args['messageonsuccess'] );
            } else {
                $messageOnSuccess = null;
            }

            if ( isset( $args['setwikicomment'] ) ) {
                $wikiComment = $parser->recursiveTagParse( $args['setwikicomment'], $frame);
                unset( $args['setwikicomment'] );
            } else {
                $wikiComment = null;
            }

            if ( isset( $args['mwreturn'] ) ) {
                $mwReturn = Core::getMWReturn( $parser->recursiveTagParse( $args['mwreturn'], $frame ) );
                unset( $args['mwreturn'] );
            } else {
                $mwReturn = $parser->getTitle()->getLinkURL();
            }

            if ( isset( $args['formtarget'] ) ) {
                $formTarget = $parser->recursiveTagParse( $args['formtarget'], $frame );
                unset( $args['formtarget'] );
            } else {
                $formTarget = null;
            }

            if ( isset( $args['action'] ) ) {
                $action = $parser->recursiveTagParse( $args['action'], $frame );
                unset( $args['action'] );
            } else {
                $action = null;
            }

            if ( isset( $args['extension'] ) ) {
                $extension = $parser->recursiveTagParse( $args['extension'], $frame );
                unset( $args['extension'] );
            } else {
                $extension = null;
            }

            if ( isset( $args['autosave'] ) ) {
                $autosaveType = $parser->recursiveTagParse( $args['autosave'], $frame );
                unset( $args['autosave'] );
            } else {
                $autosaveType = null;
            }

            if ( isset( $args['class'] ) ) {
                $additionalClass = $parser->recursiveTagParse( $args['class'], $frame );
                unset( $args['class'] );
            } else {
                $additionalClass = null;
            }

            if ( isset( $args['post-as-user'] ) ) {
                $postAsUser = true;
                unset( $args['post-as-user'] );
            } else {
                $postAsUser = false;
            }

            if ( isset( $args['show-on-select'] ) ) {
                $showOnSelect = true;
                unset( $args['show-on-select'] );
            } else {
                $showOnSelect = false;
            }

            if ( isset( $args['recaptcha-v3-action'] ) ) {
                Core::$reCaptcha = $args['recaptcha-v3-action'];
                unset( $args['recaptcha-v3-action'] );
            }

            $additionalArgs = [];
            foreach ( $args as $name => $argument ) {
                if ( Validate::validParameters( $name ) ) {
                    $additionalArgs[$name] = $parser->recursiveTagParse( $argument, $frame );
                }
            }

            $actionUrl = $formTarget ?? Core::getAPIurl();

            // Render the actual contents of the form
            $ret .= $this->themeStore
                ->getFormTheme()
                ->getFormRenderer()
                ->render_form(
                    $actionUrl,
                    $mwReturn,
                    $messageOnSuccess,
                    $wikiComment,
                    $formTarget,
                    $action,
                    $extension,
                    $autosaveType,
                    $additionalClass,
                    $postAsUser,
                    $showOnSelect,
                    $additionalArgs
                );
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
                $default = $args['default'] ?? '';
                $defaultName = isset( $args['name'] ) && $args['name'] !== '' ?
                    sprintf( 'wsdefault_%s', $args['name'] ) : '';

                $ret = $renderer->render_checkbox(
                    $args,
                    $args['show-on-checked'] ?? '',
                    $args['show-on-unchecked'] ?? '',
                    $default,
                    $defaultName
                );
                break;
            case 'file':
                // TODO: Move most of the render_file logic to here
                // TODO: Can you do this @Charlot?
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
                $ret = $renderer->render_tel( $args );
                break;
            case 'option':

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
        $placeholder = $args['placeholder'] ?? null;

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
     * @throws WSFormException
     */
    public function renderToken( $input, array $args, Parser $parser, PPFrame $frame ) {
        $parsedInput = $parser->recursiveTagParseFully( $input, $frame );

        if ( isset( $args['placeholder'] ) ) {
            $placeholder = $parser->recursiveTagParse( $args['placeholder'], $frame );
            unset( $args['placeholder'] );
        } else {
            $placeholder = null;
        }

        if ( isset( $args['multiple'] ) ) {
            $multiple = $parser->recursiveTagParse( $args['multiple'], $frame ) === "multiple";
            unset( $args['multiple'] );
        } else {
            $multiple = false;
        }

        if ( isset( $args['id'] ) ) {
            $id = $parser->recursiveTagParse( $args['id'], $frame );

            // Make sure ID is valid
            if ( !preg_match( '/^[a-zA-Z0-9_-]+$/', $id ) ) {
                return ['Invalid ID as it does not match pattern [a-zA-Z0-9_-]+', 'noparse' => true];
            }

            unset( $args['id'] );
        } else {
            try {
                // Generate a random fallback ID
                $id = bin2hex( random_bytes( 16 ) );
            } catch ( \Exception $exception ) {
                return ['Could not get enough entropy to generate random ID', 'noparse' => true];
            }
        }

        if ( isset( $args['input-length-trigger'] ) ) {
            $inputLengthTrigger = $parser->recursiveTagParse( $args['input-length-trigger'], $frame );
            $inputLengthTrigger = intval( trim( $inputLengthTrigger ) );
            unset( $args['input-length-trigger'] );
        } else {
            $inputLengthTrigger = 3;
        }

        if ( isset( $args['json'] ) ) {
            $json = strpos( $args['json'], 'semantic_ask' ) ?
                $args['json'] : $parser->recursiveTagParse( $args['json'], $frame );
            unset( $args['json'] );
        } else {
            $json = null;
        }

        if ( isset( $args['callback'] ) ) {
            $callback = $parser->recursiveTagParse( $args['callback'], $frame );

            // Make sure callback is valid
            if ( !preg_match( '/^[a-zA-Z0-9_]+$/', $callback ) ) {
                return ['Invalid callback as it does not match pattern [a-zA-Z0-9_]+', 'noparse' => true];
            }

            unset( $args['callback'] );
        } else {
            $callback = null;
        }

        if ( isset( $args['template'] ) ) {
            $template = $parser->recursiveTagParse( $args['template'], $frame );

            // Make sure callback is valid
            if ( !preg_match( '/^[a-zA-Z0-9_ ]+$/', $template ) ) {
                return ['Invalid template as it does not match pattern [a-zA-Z0-9_ ]+', 'noparse' => true];
            }

            unset( $args['template'] );
        } else {
            $template = null;
        }

        if ( isset( $args['allowtags'] ) ) {
            $allowTags = true;
            unset( $args['allowtags'] );
        } else {
            $allowTags = false;
        }

        if ( isset( $args['allowclear'] ) ) {
            $allowClear = true;
            unset( $args['allowclear'] );
        } else {
            $allowClear = false;
        }

        $additionalArguments = [];

        foreach ( $args as $name => $value ) {
            if ( Validate::validParameters( $name ) ) {
                $additionalArguments[$name] = $parser->recursiveTagParse( $value );
            }
        }

        $output = $this->themeStore->getFormTheme()->getTokenRenderer()->render_token(
            $parsedInput,
            $id,
            $inputLengthTrigger,
            $placeholder,
            $json,
            $callback,
            $template,
            $multiple,
            $allowTags,
            $allowClear,
            $additionalArguments
        );

        self::addInlineJavaScriptAndCSS();

        return [$output, "markerType" => 'nowiki'];
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
        if ( !isset( $args['target'] ) || $args['target'] === '' ) {
            return ['No valid target for edit', 'noparse' => true];
        }

        $target = $parser->recursiveTagParse( $args['target'], $frame );

        if ( !isset( $args['template'] ) && !isset( $args['mwtemplate'] ) ) {
            return ['No valid template for edit', 'noparse' => true];
        }

        $template = isset( $args['mwtemplate'] ) ? $args['mwtemplate'] : $args['template'];
        $template = str_replace( ' ', '_', $parser->recursiveTagParse( $template, $frame ) );

        if ( !isset( $args['formfield'] ) || $args['formfield'] === '' ) {
            return ['No valid formfield for edit', 'noparse' => true];
        }

        $formfield = $parser->recursiveTagParse( $args['formfield'], $frame );

        $usefield = isset( $args['usefield'] ) ? $parser->recursiveTagParse( $args['usefield'], $frame ) : '';
        $slot = isset( $args['mwslot'] ) ? $parser->recursiveTagParse( $args['mwslot'], $frame ) : '';
        $value = isset( $args['value'] ) ? $parser->recursiveTagParse( $args['value'], $frame ) : '';

        $output = $this->themeStore
            ->getFormTheme()
            ->getEditRenderer()
            ->render_edit( $target, $template, $formfield, $usefield, $slot, $value );

        return [
            $output,
            'noparse' => true,
            'markerType' => 'nowiki'
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
        $template = isset( $args['mwtemplate'] ) ? $parser->recursiveTagParse( $args['mwtemplate'], $frame ) : null;
        $createId = isset( $args['id'] ) ? $parser->recursiveTagParse( $args['id'], $frame ) : null;
        $write = isset( $args['mwwrite'] ) ? $parser->recursiveTagParse( $args['mwwrite'], $frame ) : null;
        $slot = isset( $args['mwslot'] ) ? $parser->recursiveTagParse( $args['mwslot'], $frame ) : null;
        $option = isset( $args['mwoption'] ) ? $parser->recursiveTagParse( $args['mwoption'], $frame ) : null;
        $fields = isset( $args['mwfields'] ) ? $parser->recursiveTagParse( $args['mwfields'], $frame ) : null;
        $follow = isset( $args['mwfollow'] ) ? $parser->recursiveTagParse( $args['mwfollow'], $frame ) : null;

        $leadingZero = isset( $args['mwleadingzero'] );

        if ( $fields !== null && $template === null ) {
            return ['No valid template for creating a page.', 'noparse' => true];
        }

        if ( $fields !== null && $write === null ) {
            return ['No valid title for creating a page.', 'noparse' => true];
        }

        $output = $this->themeStore
            ->getFormTheme()
            ->getCreateRenderer()
            ->render_create( $follow, $template, $createId, $write, $slot, $option, $fields, $leadingZero );

        return [
            $output,
            'noparse' => true,
            'markerType' => 'nowiki'
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

        if ( isset( $args['to'] ) ) {
            $mailArguments["mwmailto"] = $parser->recursiveTagParse( $args['to'], $frame );
        }

        if ( isset( $args['from'] ) ) {
            $mailArguments["mwmailfrom"] = $parser->recursiveTagParse( $args['from'], $frame );
        }

        if ( isset( $args['cc'] ) ) {
            $mailArguments["mwmailcc"] = $parser->recursiveTagParse( $args['cc'], $frame );
        }

        if ( isset( $args['bcc'] ) ) {
            $mailArguments["mwmailbcc"] = $parser->recursiveTagParse( $args['bcc'], $frame );
        }

        if ( isset( $args['replyto'] ) ) {
            $mailArguments["mwmailreplyto"] = $parser->recursiveTagParse( $args['replyto'], $frame );
        }

        if ( isset( $args['subject'] ) ) {
            $mailArguments["mwmailsubject"] = $parser->recursiveTagParse( $args['subject'], $frame );
        }

        if ( isset( $args['type'] ) ) {
            $mailArguments["mwmailtype"] = $parser->recursiveTagParse( $args['type'], $frame );
        }

        if ( isset( $args['content'] ) ) {
            $mailArguments["mwmailcontent"] = $parser->recursiveTagParse( $args['content'], $frame );
        }

        if ( isset( $args['job'] ) ) {
            $mailArguments["mwmailjob"] = $parser->recursiveTagParse( $args['job'], $frame );
        }

        if ( isset( $args['header'] ) ) {
            $mailArguments["mwmailheader"] = $parser->recursiveTagParse( $args['header'], $frame );
        }

        if ( isset( $args['footer'] ) ) {
            $mailArguments["mwmailfooter"] = $parser->recursiveTagParse( $args['footer'], $frame );
        }

        if ( isset( $args['html'] ) ) {
            $mailArguments["mwmailhtml"] = $parser->recursiveTagParse( $args['html'], $frame );
        }

        if ( isset( $args['attachment'] ) ) {
            $mailArguments["mwmailattachment"] = $parser->recursiveTagParse( $args['attachment'], $frame );
        }

        if ( isset( $args['template'] ) ) {
            $mailArguments["mwmailtemplate"] = $parser->recursiveTagParse( $args['template'], $frame );
        }

        if ( isset( $args['parselast'] ) ) {
            $mailArguments["mwparselast"] = "true";
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
     * @throws WSFormException
     */
    public function renderInstance( $input, array $args, Parser $parser, PPFrame $frame ) {
        global $IP, $wgScript;

        $realUrl = str_replace( '/index.php', '', $wgScript );

        // Add move, delete and add button with classes
        $parser->getOutput()->addModuleStyles( 'ext.wsForm.Instance.styles' );

        if ( !Core::isLoaded( 'wsinstance-initiated' ) ) {
            Core::addAsLoaded( 'wsinstance-initiated' );
        }

        if ( !Core::isLoaded( 'wsinstance-initiated' ) ) {
            Core::addAsLoaded( 'wsinstance-initiated' );
        }

        $content = $parser->recursiveTagParse( $input, $frame );

        // TODO: Can you take a look at this @Charlot?
        $ret = $this->themeStore->getFormTheme()->getInstanceRenderer()->render_instance( $content, $args );

        Core::removeAsLoaded( 'wsinstance-initiated' );

        if ( !Core::isLoaded( 'multipleinstance' ) && file_exists( $IP . '/extensions/WSForm/modules/instances/wsInstance.js' ) ) {
            $scriptPath = $realUrl . '/extensions/WSForm/modules/instances/wsInstance.js';
            $scriptTag = \Xml::tags('script', [
                'type' => 'text/javascript',
                'charset' => 'UTF-8',
                'src' => $scriptPath
            ], '');

            $ret = $scriptTag . $ret ;

            Core::addAsLoaded( 'multipleinstance' );
        }

        return [
            $ret,
            'noparse' => true,
            'markerType' => 'nowiki'
        ];
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
        $out = RequestContext::getMain()->getOutput();

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