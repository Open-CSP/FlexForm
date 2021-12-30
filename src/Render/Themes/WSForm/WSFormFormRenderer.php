<?php

namespace WSForm\Render\Themes\WSForm;

use Parser;
use PPFrame;
use WSForm\Core\Core;
use WSForm\Core\Validate;
use WSForm\Render\Themes\FormRenderer;

class WSFormFormRenderer implements FormRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_form( string $input, array $args, Parser $parser, PPFrame $frame ): string {
		global $wgDBname;
		global $wgDBprefix;

		if( isset ( $wgDBprefix ) && !empty($wgDBprefix) ) {
		    $prefix = '_' . $wgDBprefix;
        } else $prefix = '';

		$ret      = '<form ';
		$template = "";
		$wswrite  = "";
		$wsreturn = "";
		$wsaction = "";
		$messageonsuccess = "";
		$mwwikicontent = "";
		$wsextension = "";
		$class = array( 'wsform' );
		$ret .= 'action="' . Core::getAPIurl() . '" method="post" ';
		$js = "";

		foreach ( $args as $k => $v ) {
			if ( Validate::validFormParameters( $k ) ) {
				switch ( $k ) {
					case "messageonsuccess" :
						$messageonsuccess = Core::createHiddenField( 'mwonsuccess', $v );
						break;
					case "setwikicomment":
						$mwwikicontent = Core::createHiddenField( 'mwwikicomment', $v );
						break;
					case "mwreturn":
						$wsreturn = Core::createHiddenField( 'mwreturn', Core::getMWReturn( $v ) );
						break;
					case "formtarget":
						$ret = '<form action="' . $v . '" method="post" ';
						break;
					case "action":
						$wsaction = Core::createHiddenField( 'mwaction', $v );
						break;
					case "recaptcha-v3-action":
                        Core::$reCaptcha = $v;
						break;
					case "extension":
						$wsextension = Core::createHiddenField( 'mwextension', $v );
						break;
					case "post-as-user" :
						$ret .= 'data-wsform="wsform-general" ';
						break;
					case "show-on-select" :
						$class[] = 'WSShowOnSelect';
						$class[] = 'wsform-hide';
						$style = '.wsform-hide { opacity:0; }';

                        Core::includeInlineCSS( $style );
						break;
					case "autosave" :
						switch( $v ) {
							case "onchange":
								$ret .= ' data-autosave="onchange" ';
								break;
							case "oninterval":
								$ret .= ' data-autosave="oninterval" ';
								break;
							case "auto":
							default:
								$ret .= ' data-autosave="auto" ';
								break;
						}
						$class[] = 'ws-autosave';

						if( isset( Core::$wsConfig['autosave-interval'] ) ) {
							$js .= 'var wsAutoSaveGlobalInterval = ' . Core::$wsConfig['autosave-interval'] . ';';
						} else $js .= 'var wsAutoSaveGlobalInterval = 30000;';

						if( isset( Core::$wsConfig['autosave-after-change'] ) ) {
							$js .='var wsAutoSaveOnChangeInterval = ' . Core::$wsConfig['autosave-after-change'] . ';';
						} else $js .='var wsAutoSaveOnChangeInterval = 3000;';

						if( isset( Core::$wsConfig['autosave-btn-on'] ) ) {
							$js .= 'var wsAutoSaveButtonOn = "' . Core::$wsConfig['autosave-btn-on'] . '";';
						} else $js .="var wsAutoSaveButtonOn = 'Autosave on';";

						if( isset( Core::$wsConfig['autosave-btn-off'] ) ) {
							$js .='var wsAutoSaveButtonOff = "' . Core::$wsConfig['autosave-btn-off'] . '";';
						} else $js .="var wsAutoSaveButtonOff = \"Autosave off\";";

						break;
                    case "class" :
                        $class[] = $v;
                        break;
					default :
						$ret .= $k . '="' . $v . '" ';
						break;

				}
			}
		}

		if ( $js !== "" ){
            Core::includeInlineScript( $js );
		}

		$ret .= 'class = "' . implode( " ", $class ) . '" ';

		// Create a unique token for this form
        if( isset( $_SERVER['HTTP_HOST'] ) ) {
            $token = base64_encode("wsform_" . $_SERVER['HTTP_HOST'] . "_" . time());
        } else {
            $token = base64_encode("wsform_TERMINAL_" . time());
        }

		$wstoken = '<input type="hidden" name="mwtoken" value="' . $token . '">' . "\n";

		if ( $wsreturn == "" ) {
			$wsreturn = Core::createHiddenField( 'mwreturn', $parser->getTitle()->getLinkURL() );
		}

		$db = Core::createHiddenField( 'mwdb', $wgDBname . $prefix );

		$ret .= ">\n" . $template . $wswrite . $wsreturn . $wsaction . $messageonsuccess . $mwwikicontent . $db . $wsextension . $wstoken;

		return $ret;
	}
}