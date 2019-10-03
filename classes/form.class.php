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
		$ret      .= 'action="' . wsform::getAPIurl() . '" method="post" ';
		foreach ( $args as $k => $v ) {
			if ( validate::validFormParameters( $k ) ) {
				switch ($k) {
					case "messageonsuccess" :
						$messageonsuccess = '<input type="hidden" name="mwonsuccess" value="' . $v . '">' . "\n";
						break;
					case "setwikicomment":
						$mwwikicontent = '<input type="hidden" name="mwwikicomment" value="' . $v . '">' . "\n";
						break;
					case "mwreturn":
						$wsreturn = '<input type="hidden" name="mwreturn" value="' . $v . '">' . "\n";
						break;
					case "formtarget":
						$ret = '<form action="' . $v . '" method="post" ';
						break;
					case "action":
						$wsaction = '<input type="hidden" name="mwaction" value="' . $v . '">' . "\n";
						break;
					case "extension":
						$wsextension = '<input type="hidden" name="mwextension" value="' . $v . '">' . "\n";
						break;
					case "post-as-user" :
						$ret .= 'data-wsform="wsform-general" ';
						break;
					default :
						$ret .= $k . '="' . $v . '" ';
						break;

				}
			}
		}

		// Create a unique token for this form
        $token = base64_encode( "wsform_" . $_SERVER['HTTP_HOST'] . "_" . time() );
		$wstoken = '<input type="hidden" name="mwtoken" value="' . $token . '">' . "\n";
		if ( $wsreturn == "" ) {
			$wsreturn = '<input type="hidden" name="mwreturn" value="' . $title . '">' . "\n";
		}


		$db = '<input type="hidden" name="mwdb" value="' . $wgDBname . $prefix . '">' . "\n";
		$ret .= ">\n" . $template . $wswrite . $wsreturn . $wsaction . $messageonsuccess . $mwwikicontent . $db . $wsextension . $wstoken;

		return $ret;
	}
}