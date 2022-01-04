<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Core\Core;
use WSForm\Core\Validate;
use WSForm\Render\Themes\FormRenderer;

class WSFormFormRenderer implements FormRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_form( string $actionUrl, string $mwReturn, ?string $messageOnSuccess, ?string $wikiComment, ?string $action, ?string $extension, ?string $autosaveType, ?string $additionalClass, bool $postAsUser, bool $showOnSelect, array $additionalArgs ): string {
		$javascript = "";

		$formAttributes = array_merge([
		    'action' => $actionUrl,
            'method' => 'post',
            'class' => 'wsform'
        ], $additionalArgs);

		$messageOnSuccess = $messageOnSuccess !== null ? Core::createHiddenField( 'mwonsuccess', htmlspecialchars( $messageOnSuccess ) ) : '';
		$wikiComment = $wikiComment !== null ? Core::createHiddenField( 'mwwikicomment', htmlspecialchars( $wikiComment ) ) : '';
		$action = $action !== null ? Core::createHiddenField( 'mwaction', htmlspecialchars( $action ) ) : '';
		$extension = $extension !== null ? Core::createHiddenField( 'extension', htmlspecialchars( $extension ) ) : '';

		$mwReturn = Core::createHiddenField( 'mwreturn', urlencode( $mwReturn ) );

		if ( $additionalClass !== null ) {
		    $formAttributes['class'] .= ' ' . htmlspecialchars( $additionalClass );
        }

		if ( $autosaveType !== null ) {
            switch( $autosaveType ) {
                case "onchange":
                    $formAttributes['data-autosave'] = 'onchange';
                    break;
                case "oninterval":
                    $formAttributes['data-autosave'] = 'oninterval';
                    break;
                default:
                    $formAttributes['data-autosave'] = 'auto';
                    break;
            }

            $formAttributes['class'] .= ' ws-autosave';

            if( isset( Core::$wsConfig['autosave-interval'] ) ) {
                $javascript .= 'var wsAutoSaveGlobalInterval = ' . Core::$wsConfig['autosave-interval'] . ';';
            } else {
                $javascript .= 'var wsAutoSaveGlobalInterval = 30000;';
            }

            if( isset( Core::$wsConfig['autosave-after-change'] ) ) {
                $javascript .= 'var wsAutoSaveOnChangeInterval = ' . Core::$wsConfig['autosave-after-change'] . ';';
            } else {
                $javascript .= 'var wsAutoSaveOnChangeInterval = 3000;';
            }

            if( isset( Core::$wsConfig['autosave-btn-on'] ) ) {
                $javascript .= 'var wsAutoSaveButtonOn = "' . Core::$wsConfig['autosave-btn-on'] . '";';
            } else {
                $javascript .= "var wsAutoSaveButtonOn = 'Autosave on';";
            }

            if( isset( Core::$wsConfig['autosave-btn-off'] ) ) {
                $javascript .= 'var wsAutoSaveButtonOff = "' . Core::$wsConfig['autosave-btn-off'] . '";';
            } else {
                $javascript .= "var wsAutoSaveButtonOff = \"Autosave off\";";
            }
        }

		if ( $postAsUser ) {
		    $formAttributes['data-wsform'] = 'wsform-general';
        }

		if ( $showOnSelect ) {
		    $formAttributes['class'] .= ' WSShowOnSelect wsform-hide';
		    Core::includeInlineCSS( '.wsform-hide { opacity:0; }' );
        }

		if ( $javascript !== '' ){
            Core::includeInlineScript( $javascript );
		}

		// Create a unique token for this form
        if( isset( $_SERVER['HTTP_HOST'] ) ) {
            $token = base64_encode("wsform_" . $_SERVER['HTTP_HOST'] . "_" . time());
        } else {
            $token = base64_encode("wsform_TERMINAL_" . time());
        }

        $tokenTag = \Xml::tags('input', [
            'type' => 'hidden',
            'name' => 'mwtoken',
            'value' => $token
        ], '');

        // TODO: Fix variable names, move some logic from hooks to here

		$ret .= ">\n" . $template . $wswrite . $wsreturn . $wsaction . $messageonsuccess . $mwwikicontent . $db . $wsextension . $wstoken;

		return $ret;
	}
}