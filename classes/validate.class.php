<?php
/**
 * Created by  : Designburo.nl
 * Project     : i
 * Filename    : validate.class.php
 * Description :
 * Date        : 11/04/2019
 * Time        : 21:43
 */

namespace wsform\validate;


class validate {


	/**
	 * Check for valid parameters when using email
	 *
	 * @param $check string Holds parameter to check
	 * @param bool $ret If set to true it will not check the "check" parameter but rather returns an array of valid parameters
	 *
	 * @return array|bool List of valid parameters, bool true when "$check" is valid, false if not
	 */
	public static function validEmailParameters ( $check, $ret = false ) {

		$validEmailElements = array(
			"to",
			"from",
			"cc",
			"bcc",
			"replyto",
			"subject",
			"type",
			"content",
			"job",
			"header",
			"footer",
			"html",
			"template",
            "parselast",
            "attachment"
		);
		if ( $ret ) {
			return $validEmailElements;
		}
		if ( in_array( $check, $validEmailElements ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check for valid parameters when using signature
	 *
	 * @param $check string Holds parameter to check
	 * @param bool $ret If set to true it will not check the "check" parameter but rather returns an array of valid parameters
	 *
	 * @return array|bool List of valid paramters bool true when "$check" is valid, false if not
	 */
	public static function validSignatureParameters ( $check, $ret = false ) {

		$validEmailElements = array(
			"fname",
			"ftype",
			"pagecontent",
			"background",
			"drawcolor",
			"thickness",
			"guideline",
			"guidelineoffset",
			"guidelineindent",
			"guidelinecolor",
			"notavailablemessage",
			"class"
		);
		if ( $ret ) {
			return $validEmailElements;
		}
		if ( in_array( $check, $validEmailElements ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check for valid parameters when using form
	 *
	 * @param $check string Holds parameter to check
	 * @param bool $ret If set to true it will not check the "check" parameter but rather returns an array of valid parameters
	 *
	 * @return array|bool List of valid paramters, bool true when "$check" is valid, false if not
	 */
	public static function validFormParameters( $check, $ret = false ) {
		$validFormElements = array(
			"alt",
			"class",
			"id",
			"title",
			"name",
			"disabled",
			"novalidate",
			"formtarget",
			"action",
			"style",
			"enctype",
			"getvalues",
			"permissions",
			"messageonsuccess",
			"setwikicomment",
			"mwreturn",
			"extension",
			"post-as-user",
			"lock",
			"recaptcha-v3-action"
		);
		if ( $ret ) {
			return $validFormElements;
		}
		if ( in_array( $check, $validFormElements ) ) {
			return true;
		} else {
			return false;
		}
	}


	public static function validHTML( $args ) {
		if( isset( $args['html'] ) ) {
			$tmp = explode('=', $args['html'] );
			$html = $tmp[0];
			$validParameters = array(
				"default",
				"nohtml",
				"all",
				"custom"
			);
			if ( in_array( $html, $validParameters ) ) {
				if( $html === 'custom' && isset( $tmp[1] ) ) {
					return array( $html, $tmp[1] );
				}
				return $html;
			}
		}
		return "default";
	}

	/**
	 * Check for valid parameters for general use
	 *
	 * @param $check string Holds parameter to check
	 * @param bool $ret If set to true it will not check the "check" parameter but rather returns an array of valid parameters
	 *
	 * @return array|bool List of valid parameters,  bool true when "$check" is valid, false if not
	 */
	public static function validParameters( $check, $ret = false ) {
		$validParameters = array(
			"alt",
			"class",
			"id",
			"title",
			"name",
			"list",
			"min",
			"max",
			"disabled",
			"checked",
			"selected",
			"value",
			"step",
			"required",
			"for",
			"novalidate",
			"pattern",
			"readonly",
			"size",
			"style",
			"maxlength",
			"minlength",
			"autocomplete",
			"placeholder",
			"autofocus",
			"formtarget",
			"src",
			"multiple",
			"rows",
			"cols",
			"enctype",
			"multiple",
			"onfocus",
			"onclick",
			"buttontype"
		);
		if ( $ret ) {
			return $validParameters;
		}
		if ( in_array( $check, $validParameters ) ) {
			return true;
		} elseif ( substr( $check,0,4 ) == 'data' ) {
			return true;
		} else return false;
	}

	/**
	 * Check for valid parameters when using file
	 *
	 * @param $check string Holds parameter to check
	 * @param bool $ret If set to true it will not check the "check" parameter but rather returns an array of valid parameters
	 *
	 * @return array|bool List of valid parameters, bool true when "$check" is valid, false if not
	 */
	public static function validFileParameters( $check, $ret = false ) {
		$validParameters = array(
			"target",
			"accept",
			"verbose_id",
			"error_id",
			"presentor",
			"pagecontent",
			"use_label",
			"force",
			"slim_class",
			"slim_thumb_width",
			"slim_thumb_height",
            "parsecontent"
		);
		if ( $ret ) {
			return $validParameters;
		}
		if ( in_array( $check, $validParameters ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check for valid input type when using wsfield
	 *
	 * @param $check string Holds parameter to check
	 * @param bool $ret If set to true it will not check the "check" parameter but rather returns an array of valid parameters
	 *
	 * @return array|bool List of valid parameters, bool true when "$check" is valid, false if not
	 */
	public static function validInputTypes( $check, $ret = false ) {
		$validInputFields = array(
			"search",
			"email",
			"url",
			"tel",
			"number",
			"range",
			"file",
			"date",
			"month",
			"week",
			"time",
			"datetime",
			"datetimelocal",
			"color",
			"text",
			"password",
			"submit",
			"reset",
			"radio",
			"checkbox",
			"button",
			"textarea",
			"datalist",
			"image",
			"list",
			"hidden",
			"secure",
			"option",
			"token",
			"signature",
			"mobilescreenshot"
		);
		if ( $ret ) {
			return $validInputFields;
		}
		if ( in_array( $check, $validInputFields ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * General function for parameters validation
	 *
	 * @param array $args List of parameters
	 *
	 * @return string of formatted HTML
	 */
	public static function doSimpleParameters( $args, $type = false ) {

		$name  = false;
		$value = false;
		$val   = '';
		$ret   = "";
		$html = self::validHTML( $args );
		$secure = \wsform\wsform::$secure;

		foreach ( $args as $k => $v ) {
			if ( self::validParameters( $k ) ) {
				if ( $k == "name" ) {
					if( $type === "secure" ) {
						\wsform\protect\protect::setCrypt( \wsform\wsform::$checksumKey );
						$name = \wsform\protect\protect::encrypt( $v );
						$v = $name; // set value to be encypted
					} else {
						$name = $v;
					}
				}
				if ( $k == "value" ) {
					$value = true;
					if( $type === "secure" ) {
						\wsform\protect\protect::setCrypt( \wsform\wsform::$checksumKey );
						$val = \wsform\protect\protect::encrypt( $v );
						$v = $val; // set value to be encypted
						$html = "all";
					} else {
						$val = \wsform\protect\protect::purify( $v, $html, $secure );
						$v = $val; // set value to be purified
					}
				}
				if ( self::check_disable_readonly_required_selected( $k, $v ) ) {
					continue;
				}
				$ret .= $k . '="' . $v . '" ';
			}
		}
		if ( $name && ! $value ) {
			if( $html === "nohtml" ) {
				$clean = true;
			} else $clean = false;
			//$tmp = \wsform\protect\protect::purify( \wsform\wsform::getValue( $name, $clean ), $html, $secure );
			$tmp = \wsform\wsform::getValue( $name, $clean );

			if ( $tmp !== "" ) {
				$ret .= 'value = "' . $tmp . '" ';
				\wsform\wsform::addCheckSum( $type, $name, $tmp, $html );
			} else \wsform\wsform::addCheckSum( $type, $name, '', $html );
		} else \wsform\wsform::addCheckSum( $type, $name, $val, $html );
		return  $ret;
	}

	/**
	 * General function for Radio button parameters validation
	 * @param array $args List of parameters
	 *
	 * @return string of formatted HTML
	 */
	public static function doRadioParameters( $args ) {
		$name    = false;
		$value   = false;
		$checked = false;
		$ret     = "";

		foreach ( $args as $k => $v ) {
			if ( self::validParameters( $k ) ) {
				if ( $k == "name" ) {
					$name = $v;
				}
				if ( $k == "value" ) {
					$value = $v;
				}
				if ( $k == "checked" ) {
					$checked = true;
				}
				if ( self::check_disable_readonly_required_selected( $k, $v ) ) {
					continue;
				}
				$ret .= $k . '="' . $v . '" ';

			}
		}

		if ( $name && $value && ! $checked ) {
			$tmp = \wsform\protect\protect::purify( \wsform\wsform::getValue( ( $name ) ),'', \wsform\wsform::$secure );
			//echo "<HR>name=$name, value=$value, get=$tmp<HR>";
			if ( $tmp !== "" ) {
				if ( $tmp == $value ) {
					$ret .= 'checked="" ';
				}
			}
		}
		\wsform\wsform::addCheckSum( 'radio', $name, $value );
		return $ret;
	}

	/**
	 * If Checked, Disabled, Readonly or Required is used check if they have values(FALSE). otherwise skip (TRUE).
	 *
	 * @param  string $k [key]
	 * @param  string $v [value]
	 *
	 * @return bool    [yes or no]
	 */
	public static function check_disable_readonly_required_selected( $k, $v ) {
		if ( $k == "checked" && $v != "checked" ) {
			return true;
		}
		if ( $k == "disabled" && $v != "disabled" ) {
			return true;
		}
		if ( $k == "readonly" && $v != "readonly" ) {
			return true;
		}
		if ( $k == "required" && $v != "required" ) {
			return true;
		}
		if ( $k == "selected" && $v != "selected" ) {
			return true;
		}

		return false;
	}

	/**
	 * General function for Checkbox parameters validation
	 * @param array $args List of parameters
	 *
	 * @return string of formatted HTML
	 */
	public static function doCheckboxParameters( $args ) {
		$name    = false;
		$value   = false;
		$checked = false;
		$ret     = "";
		foreach ( $args as $k => $v ) {
			if ( self::validParameters( $k ) ) {
				if ( $k == "name" ) {
					$name = $v;
					if ( ! strpos( $v, "[]" ) ) {
						$v .= '[]';
					}
				}
				if ( $k == "value" ) {
					$value = $v;
				}
				if ( $k === "checked" && $v !== "checked" ) {
					continue;
				} elseif ( $k === "checked" && $v === "checked" ) {
					$checked = true;
				}
				if ( self::check_disable_readonly_required_selected( $k, $v ) ) {
					continue;
				}
				$ret .= $k . '="' . $v . '" ';

			}
		}
		if ( $name && $value && ! $checked ) {
			if ( strpos( $name, "[]" ) ) {
				$name = rtrim( $name, '[]' );
			}
			$tmp = \wsform\protect\protect::purify( \wsform\wsform::getValue( ( $name ) ), '',  \wsform\wsform::$secure );
			if ( $tmp !== "" ) {
				if ( strpos( $tmp, "," ) ) {
					$options = explode( ",", $tmp );
					if ( in_array( $value, $options ) ) {
						$ret .= 'checked="" ';
					}
				} elseif ( $tmp == $value ) {
					$ret .= 'checked="" ';
				}
			}
		}
		\wsform\wsform::addCheckSum( "checkbox", $name, $value );
		return $ret;
	}

	/**
	 * General function for option parameters validation
	 * @param array $args List of parameters
	 *
	 * @return string of formatted HTML
	 */
	public static function doOptionParameters( $args ) {
		$name    = false;
		$value   = false;
		$checked = false;
		$ret     = "";
		foreach ( $args as $k => $v ) {
			if ( self::validParameters( $k ) ) {
				if ( $k == "name" ) {
					$name = $v;
				}
				if ( $k == "value" ) {
					$value = $v;
				}
				if ( $k == "checked" ) {
					$checked = true;
				}
				$ret .= $k . '="' . $v . '" ';

			}
		}

		if ( $name && $value && ! $checked ) {
			if ( strpos( $name, "[]" ) ) {
				$name = rtrim( $name, '[]' );
			}
			$tmp = \wsform\protect\protect::purify( \wsform\wsform::getValue( ( $name ) ), '', \wsform\wsform::$secure );

			if ( $tmp !== "" ) {
				if ( strpos( $tmp, "," ) ) {
					$options = explode( ",", $tmp );
					if ( in_array( $value, $options ) ) {
						$ret .= 'checked="" ';
					}
				} elseif ( $tmp == $value ) {
					$ret .= 'checked="" ';
				}
			}
		}
		\wsform\wsform::addCheckSum( "select", $name, $value );
		return $ret;
	}
}