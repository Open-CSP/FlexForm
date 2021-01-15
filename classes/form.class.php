<?php


namespace wsform\form;

use wsform\validate\validate;
use wsform\wsform;

class render {

	/**
	 * @brief Render begin of HTML Form
	 *
	 * This function is called by WSForm()
	 *
	 * \n Additional parameters
	 * \li messageonsuccess
	 * \li setwikicomment
	 * \li mwreturn
	 * \li formtarget
	 * \li action
	 * \li extension
	 * \li post-as-user - Not functional
	 *
	 * @param array $args Arguments for the form
	 * @param bool $title Url to return to when form is submitted
	 *
	 * @return string Rendered HTML
	 */
	public static function render_form( $args, $title=false ) {
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
		$lock = false;
		$secure = true;
		$wslock = "";
		$class = array();
		$ret      .= 'action="' . wsform::getAPIurl() . '" method="post" ';
		foreach ( $args as $k => $v ) {
			if ( validate::validFormParameters( $k ) ) {
				switch ($k) {
					case "'messageonsuccess'" :
						$messageonsuccess = \wsform\wsform::createHiddenField( 'mwonsuccess', $v );
						break;
					case "setwikicomment":
						$mwwikicontent = \wsform\wsform::createHiddenField( 'mwwikicomment', $v );
						break;
					case "mwreturn":
						$wsreturn = \wsform\wsform::createHiddenField( 'mwreturn', $v );
						break;
					case "formtarget":
						$ret = '<form action="' . $v . '" method="post" ';
						break;
					case "action":
						$wsaction = \wsform\wsform::createHiddenField( 'mwaction', $v );
						break;
					case "recaptcha-v3-action":
						wsform::$reCaptcha = $v;
						break;
					case "extension":
						$wsextension = \wsform\wsform::createHiddenField( 'mwextension', $v );
						break;
					case "post-as-user" :
						$ret .= 'data-wsform="wsform-general" ';
						break;
                    case "autosave" :
                        $class[] = 'ws-autosave';
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

		$ret .= 'class = "' . implode( " ", $class ) . '" ';

		// Create a unique token for this form
        if( isset( $_SERVER['HTTP_HOST'] ) ) {
            $token = base64_encode("wsform_" . $_SERVER['HTTP_HOST'] . "_" . time());
        } else {
            $token = base64_encode("wsform_TERMINAL_" . time());
        }
		$wstoken = '<input type="hidden" name="mwtoken" value="' . $token . '">' . "\n";
		if ( $wsreturn == "" ) {
			$wsreturn = \wsform\wsform::createHiddenField( 'mwreturn', $title );
		}

		$db = \wsform\wsform::createHiddenField( 'mwdb', $wgDBname . $prefix );

		$ret .= ">\n" . $template . $wswrite . $wsreturn . $wsaction . $messageonsuccess . $mwwikicontent . $db . $wsextension . $wstoken;

		return $ret;
	}
}