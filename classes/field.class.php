<?php

namespace wsform\field;

use \wsform\validate\validate;

class render {


	/**
	 * @brief Render Text Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 * @param  object $parser MediaWiki parser
	 * @param  object $frame MediaWiki frame used for parser
	 *
	 * @return string Rendered HTML
	 */
	public static function render_text( $args, $input = false, $parser, $frame ) {
		$ret = '<input type="text" ';
		$ret .= validate::doSimpleParameters( $args );
		foreach ( $args as $k => $v ) {
			if ( $k == 'mwidentifier' && $v == 'datepicker' ) {
				$parser->disableCache();
				$parser->getOutput()->addModules( 'ext.wsForm.datePicker.scripts' );
				$parser->getOutput()->addModuleStyles( 'ext.wsForm.datePicker.styles' );
			}
		}
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render hidden Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_hidden( $args, $input = false ) {
		$ret = '<input type="hidden" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Search Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_search( $args, $input = false ) {
		$ret = '<input type="search" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render number Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_number( $args, $input = false ) {
		$ret = '<input type="number" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Radio Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_radio( $args, $input = false ) {
		$ret = '<input type="radio" ';
		$ret .= validate::doRadioParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render checkbox Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_checkbox( $args, $input = false ) {
		$ret = '<input type="checkbox" ';
		$ret .= validate::doCheckboxParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render File Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 * @param  object $parser MediaWiki parser
	 * @param  object $frame MediaWiki frame used for parser
	 *
	 * @return string Rendered HTML
	 */
	public static function render_file( $args, $input = false, $parser, $frame ) {
		$slim           = '<div class="';
		$ret            = '<input type="file" ';
		$br             = "\n";
		$id             = false;
		$target         = false;
		$verbose_id     = false;
		$error_id       = false;
		$presentor      = false; // Holds name of external presentor, e.g. Slim
		$pagecontent    = "";
		$use_label      = false;
		$verbose_custom = "none";
		$error_custom   = "none";
		$slim_class     = "slim ";
		$slim_image     = false;
		$slim_data      = "";
		$force          = false;
		$thumbWidth     = false;
		$thumbHeight    = false;
		$parseContent   = false;
		foreach ( $args as $k => $v ) {
			if ( validate::validParameters( $k ) || validate::validFileParameters( $k ) ) {
				// going through specific extra's.
				$v = $parser->recursiveTagParse( $v, $frame );
				switch ( $k ) {
					case "presentor":
						$presentor = $v;
						break;
					case "slim_class":
						$slim_class .= $v;
						break;
					case "pagecontent" :
						$pagecontent = $v;
						break;
                    case "parsecontent" :
                        $parseContent = true;
                        break;
					case "value" :
						$slim_image = $v;
						break;
					case "target":
						$target = $v;
						break;
					case "use_label":
						$use_label = true;
						break;
					case "force":
						$force = $v;
						break;
					case "id":
						$id  = $v;
						$ret .= $k . '="' . $v . '" ';
						break;
					case "verbose_id":
						$verbose_id = $v;
						break;
					case "error_id":
						$error_id = $v;
						break;
					case "name":
						$ret .= $k . '="wsformfile" ';
						break;
					case "slim_thumb_width":
						$thumbWidth=$v;
						break;
					case "slim_thumb_height":
						$thumbHeight=$v;
						break;
					default:
						$ret .= $k . '="' . $v . '" ';
				}

			}
			if ( substr( $k, 0, 5 ) == "data-" ) {
				$slim_data .= $k . '="' . $v . '" ';
			}

		}
		$slim .= $slim_class . '" ' . $slim_data . '>' . $br;
		$ret  .= ">$br";
		global $IP;
		if ( ! $id ) {
			$ret = 'You cannot upload files without adding an unique id.';

			return $ret;
		}
		if ( $presentor == "slim" ) {
			$ret .= '<input type="hidden" name="wsformfile_slim">' . "\n";
		}
		if ( ! $target ) {
			$ret = 'You cannot upload files without a target.';

			return $ret;
		} else {
			$ret .= '<input type="hidden" name="wsform_file_target" value="' . $target . '">';
		}
		if ( $pagecontent ) {
			$ret .= '<input type="hidden" name="wsform_page_content" value="' . $pagecontent . '">';
		}
        if ( $parseContent ) {
            $ret .= '<input type="hidden" name="wsform_parse_content" value="true">';
        }
		if ( $force ) {
			$ret .= '<input type="hidden" name="wsform_image_force" value="' . $force . '">';
		}
		if ( $thumbWidth ) {
			$ret .= '<input type="hidden" name="wsform_file_thumb_width" value="' . $thumbWidth . '">';
		}
		if ( $thumbHeight ) {
			$ret .= '<input type="hidden" name="wsform_file_thumb_height" value="' . $thumbHeight . '">';
		}


		if ( ! $presentor ) {

			if ( $verbose_id === false) {
				$verbose_id = 'verbose_' . $id;

				$ret .= '<div id="' . $verbose_id . '" class="wsform-verbose"></div>';
			} else {
				$verbose_custom = "yes";
			}
			if ( ! $error_id ) {
				$error_id = 'error_' . $id;
				$ret      .= '<div id="' . $error_id . '" class="wsform-error"></div>';
			} else {
				$error_custom = "yes";
			}
			$random = round(microtime(true) * 1000);
			$onChangeScript = 'function WSFile'.$random.'(){'. "\n".'$("#' . $id . '").on("change", function(){'. "\n".'wsfiles( "';
			$onChangeScript .= $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label;
			$onChangeScript .= '", "' . $verbose_custom . '", "' . $error_custom . '");'. "\n".'});'. "\n".'};';
			$ret .= "<script>\n" . $onChangeScript . "\n";
			$ret .= "\n" . "wachtff(WSFile".$random.");\n</script>";
			//$ret     .= '<script>$( document ).ready(function() { $("#' . $random . '").on("change", function(){ wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '", "' . $verbose_custom . '", "' . $error_custom . '");});});</script>';
			$css     = file_get_contents( "$IP/extensions/WSForm/WSForm_upload.css" );
			$replace = array(
				'{{verboseid}}',
				'{{errorid}}'
			);
			$with    = array(
				$verbose_id,
				$error_id
			); //wsfiles( "file-upload2", "hiddendiv2", "error_file-upload2", "", "yes", "none");
			$css     = str_replace( $replace, $with, $css );
			$ret     .= $css;
			if(! \wsform\wsform::isLoaded( 'WSFORM_upload.js' ) ) {
				\wsform\wsform::addAsLoaded( 'WSFORM_upload.js' );
				$js = file_get_contents( "$IP/extensions/WSForm/WSForm_upload.js" );
			} else $js = '';
			$ret     .= "\n" . '<script>' . $js . '</script>';
			$wsFileScript = "\nfunction wsfilesFunc" . $random . "(){\n";
			$wsFileScript .= "\n" . 'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");' . "\n";
			$wsFileScript .= "}\n";
			//$ret .= '<script>'. "\n".'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");</script>';

			$ret .= '<script>'. "\n" . $wsFileScript . "\n" . 'wachtff(wsfilesFunc'. $random .');</script>';
		} elseif ( $presentor == "slim" ) {
			if ( $slim_image !== false ) {
				$slim_image = '<img src="' . $slim_image . '">';
			} else {
				$slim_image = "";
			}
			$ret = $slim . $ret . $slim_image . "</div>$br";
			$parser->disableCache();
			$parser->getOutput()->addModuleStyles( 'ext.wsForm.slim.styles' );
			$parser->getOutput()->addModules( 'ext.wsForm.slim.scripts' );
		}


		return $ret;
	}

	/**
	 * @brief Render date Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_date( $args, $input = false ) {
		$ret = '<input type="date" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render month Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_month( $args, $input = false ) {
		$ret = '<input type="month" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render week Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_week( $args, $input = false ) {
		$ret = '<input type="week" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Time Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_time( $args, $input = false ) {
		$ret = '<input type="time" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render DateTime Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_datetime( $args, $input = false ) {
		$ret = '<input type="datetime" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render local DateTime Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_datetimelocal( $args, $input = false ) {
		$ret = '<input type="datetime-local" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Password Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_password( $args, $input = false ) {
		$ret = '<input type="password" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Email Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_email( $args, $input = false ) {
		$ret = '<input type="email" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Color Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_color( $args, $input = false ) {
		$ret = '<input type="color" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Range Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_range( $args, $input = false ) {
		$ret = '<input type="range" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Image Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_image( $args, $input = false ) {
		$ret = '<input type="image" ';
		foreach ( $args as $k => $v ) {
			if ( validate::validParameters( $k ) ) {
				$ret .= $k . '="' . $v . '" ';
			}
		}
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render URL Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_url( $args, $input = false ) {
		$ret = '<input type="url" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Telephone numner Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_tel( $args, $input = false ) {
		$ret = '<input type="tel" ';
		$ret .= validate::doSimpleParameters( $args );
		$ret .= ">\n";

		return $ret;
	}

	/*
    public static function render_token($args,$input=false) {
        $ret = '<input type="text" ';
        foreach ($args as $k=>$v) {
            if(validate::validParameters($k)) {
                $ret .= $k.'="'.$v.'" ';
            }
        }
        $ret .= ">\n";
        if(isset($args['id'])) {
            $id = $args['id'];
        } else return "";
        $ret .= '<script>$(document).ready(function () {'."\n";
        $ret .= "$('#".$id."').select2({"."\n";
        $ret .= 'autocomplete: {'."\n";
        $ret .= 'source: '.$input.','."\n";
        $ret .= 'delay: 100'."\n";
        $ret .= '},'."\n";
        $ret .= 'showAutocompleteOnFocus: true'."\n";
        $ret .= "});\n});\n</script>\n";
        return $ret;
    }
*/
	/**
	 * @brief Render Options for Select Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input Everything between the start and end tags
	 * @param  object $parser MediaWiki parser
	 * @param  object $frame MediaWiki frame used for parser
	 *
	 * @return string Rendered HTML
	 */
	public static function render_option( $args, $input = false, $parser, $frame ) {
		$ret = '<option ';
		foreach ( $args as $k => $v ) {
			if ( validate::check_disable_readonly_required_selected( $k, $v ) ) {
				continue;
			}
			$k = $parser->recursiveTagParse( $k, $frame );
			$v = $parser->recursiveTagParse( $v, $frame );
			if ( validate::validParameters( $k ) ) {
				if ( $k == "for" ) {
					$name = $v;
				} else {
					if ( $k == "value" ) {
						$value = $v;
					}
					$ret .= $k . '="' . $v . '" ';
				}
			}
		}
		if(isset($name)) {
			$val = \wsform\wsform::getValue( $name );
		} else $val = "";
		if ( $val != "" ) {
			$values = explode( ",", $val );
			foreach ( $values as $v ) {
				if ( trim( $v ) == $value ) {
					$ret .= 'selected="selected"';
				}
			}
		}
		$ret .= ">" . $input . "</option>\n";

		return $ret;
	}


	/**
	 * @brief Render actual submit HTML

	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 * @param  object $parser MediaWiki parser
	 * @param  object $frame MediaWiki frame used for parser
	 *
	 * @return string Rendered HTML
	 */
	public static function render_submit( $args, $input = false, $parser, $frame ) {
		global $IP, $wgOut, $wgResourceBasePath;
		$ret = '<input type="submit" ';
		$res = '';
		$identifier = false;
		$callback = 0;
		$beforecallback = 0;
		foreach ( $args as $k => $v ) {
			if ( validate::validParameters( $k ) ) {
				$res .= $k . '="' . $v . '" ';
			}
			if ( $k == 'mwidentifier' && $v == 'ajax' ) {

				$ret = '<input type="hidden" name="mwidentifier" value="' . $v . '">' . "\n";
				if (! \wsform\wsform::isLoaded( 'wsform-ajax' ) ) {
					if(file_exists($IP.'/extensions/WSForm/wsform-ajax.js')) {
						//$lf = htmlspecialchars_decode(file_get_contents($IP.'/extensions/WSForm/wsform-ajax.js'));
						$ret .= '<script src="'.$wgResourceBasePath.'/extensions/WSForm/wsform-ajax.js"></script>'."\n";
						\wsform\wsform::addAsLoaded('wsform-ajax');
					}
				}
				$identifier = true;

				$ret .= '<input type="button" ';
			}
            if ( $k == 'mwpausebeforerefresh' ) {
                $ins = '<input type="hidden" name="mwpause" value="' . $v . '">' . "\n";
                $ret = $ins . $ret;
            }
			if ( $k == 'callback' && $v != '' ) {
				$callback = trim($v);
			}
			if ( $k == 'beforecallback' && $v != '' ) {
				$beforecallback = trim($v);
			}
		}

		if ($identifier) {
			$res .= 'onClick="wsform(this,'.$callback.','.$beforecallback.');" ';
		}
		$res .= ">\n";
		if( $callback !== false && $identifier === true ) {
			if(! \wsform\wsform::isLoaded( $callback ) ) {
				if ( file_exists( $IP . '/extensions/WSForm/modules/customJS/' . $callback . '.js' ) ) {
					$lf  = file_get_contents( $IP . '/extensions/WSForm/modules/customJS/' . $callback . '.js' );
					$res .= "<script>$lf</script>\n";
					\wsform\wsform::addAsLoaded($callback);
				} //else die($IP.'/extensions/WSForm/modules/customJS/'.$callback.'.js');
			}
		}

		return $ret.$res;
	}

	/**
	 * @brief Render Buttonfield as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_button( $args, $input = false ) {
		$ret = '<button ';
		$setButtonType = "button";
		foreach ( $args as $k => $v ) {
			if ( validate::validParameters( $k ) ) {
				if( $k === "buttontype" ) {
					$setButtonType = $v;
				} else {
					$ret .= $k . '="' . $v . '" ';
				}
			}
		}
		$ret .= 'type="' . $setButtonType . '"';
		$ret .= ">" . $input . "</button>\n";

		return $ret;
	}

	/**
	 * @brief Render Reset Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_reset( $args, $input = false ) {
		$ret = '<input type="reset" ';
		foreach ( $args as $k => $v ) {
			if ( validate::validParameters( $k ) ) {
				$ret .= $k . '="' . $v . '" ';
			}
		}
		$ret .= ">\n";

		return $ret;
	}

	/**
	 * @brief Render Text Area Input field as HTML
	 *
	 * @param  array $args Arguments for the input field
	 * @param  boolean $input not used
	 *
	 * @return string Rendered HTML
	 */
	public static function render_textarea( $args, $input = false ) {
		$ret = '<textarea ';
		foreach ( $args as $k => $v ) {
			if ( validate::check_disable_readonly_required_selected( $k, $v ) ) {
				continue;
			}
			if ( validate::validParameters( $k ) ) {
				$ret .= $k . '="' . $v . '" ';
			}
		}
		$ret .= ">";
		if ( $input !== false ) {
			$ret .=  $input;
		}
		$ret .= "</textarea>\n";
		return $ret;
	}

	/**
	 * @brief Render Signature HTML input field
	 *
	 * @param  array $args Arguments for the input field
	 *
	 * @return string Rendered HTML
	 */
	public static function render_signature( $args) {
		global $IP, $wgOut;

		$jsOptions = ", ";

		if(!isset($args['fname'])) {
			return 'Need target filename';
		}
		if(!isset($args['ftype'])) {
			$ftype = "svg";
		} else $ftype = $args['ftype'];

		if(!isset($args['pagecontent'])) {
			return 'Page content is missing';
		} else $pcontent = $args['pagecontent'];

		if(isset($args['class'])) {
			$class = $args['class'];
		} else $class = "";

		if(isset($args['required']) && $args['required'] === 'required') {
			$required = "required ";
		} else $required = "";

		if(isset($args['clearbuttonclass'])) {
			$bclass = $args['clearbuttonclass'];
		} else $bclass = "";

		if(isset($args['clearbuttontext'])) {
			$btxt = $args['clearbuttontext'];
		} else $btxt = "Clear";

		if(isset($args['background'])) {
			$jsOptions .= 'background: "'.$args['background'].'", ';
		}

		if(isset($args['drawcolor'])) {
			$jsOptions .= 'color: "'.$args['drawcolor'].'", ';
		}

		if(isset($args['thickness'])) {
			$jsOptions .= 'thickness: "'.$args['thickness'].'", ';
		}

		if(isset($args['guideline']) && $args['guideline'] === 'true' ) {
			$gl = true;
			$jsOptions .= 'guideline: true, ';
		} else $gl=false;

		if( isset( $args['guidelineoffset'] ) && $gl === true) {
			$jsOptions .= 'guidelineOffset: "'.$args['guidelineoffset'].'", ';
		}

		if( isset( $args['guidelineindent'] ) && $gl === true) {
			$jsOptions .= 'guidelineIndent: "'.$args['guidelineindent'].'", ';
		}

		if( isset( $args['guidelinecolor'] ) && $gl === true) {
			$jsOptions .= 'guidelineColor: "'.$args['guidelinecolor'].'", ';
		}

		if( isset( $args['notavailablemessage'] ) && $gl === true) {
			$jsOptions .= 'notAvailable: "'.$args['notavailablemessage'].'", ';
		}

		$jsOptions = rtrim($jsOptions,', \n');

		$css = "<style>".file_get_contents($IP.'/extensions/WSForm/modules/signature/css/jquery.signature.css')."</style>";
		$css .= '<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/south-street/jquery-ui.css" rel="stylesheet">';
		$js = '<script type="text/javascript" charset="UTF-8" src="/extensions/WSForm/modules/signature/js/do-signature.js"></script>'."\n";
		$js .= '<script>';

		$js .= 'function doWSformActions(){'."\n";
		$js .= '$("#wsform-signature").signature({'."\n";
		$js .= 'syncField: "#wsform_signature_data", syncFormat: "'.strtoupper($ftype).'"';
		$js .= $jsOptions;
		$js .= '} );'."\n";
		$js .= '$("#wsform_signature_clear").click(function(){'."\n".'$("#wsform-signature").signature("clear");'."\n".'});'."\n";

		$js .='};';
		$js .= '</script>';
		$ret = '<input type="hidden" name="wsform_signature_filename" value="'.$args['fname'].'" >'."\n";
		$ret .= '<input type="hidden" name="wsform_signature_type" value="'.$ftype.'" >'."\n";
		$ret .= '<input type="hidden" name="wsform_signature_page_content" value="'.$pcontent.'" >'."\n";
		$ret .= '<input type="hidden" id="wsform_signature_data" name="wsform_signature" '.$required.'value="" >'."\n";
		$ret .= '<div id="wsform-signature" class="wsform-signature '.$class.'"></div>'."\n";
		$ret .= '<button type="button" id="wsform_signature_clear" class="wsform-signature-clear '.$bclass.'">'.$btxt.'</button>'."\n";
		$ret .= "\n";

		return $css.$ret.$js;
	}

	/**
	 * @brief Render Mobile screenshot File input field
	 *
	 * @param  array $args Arguments for the input field
	 *
	 * @return string Rendered HTML
	 */
	public static function render_mobilescreenshot( $args) {
		global $IP, $wgOut;

		$end = "\n";

		if(!isset($args['fname'])) {
			return 'Need target filename';
		}
		if(!isset($args['ftype'])) {
			$ftype = "svg";
		} else $ftype = $args['ftype'];

		if(!isset($args['pagecontent'])) {
			return 'Page content is missing';
		} else $pcontent = $args['pagecontent'];

		if(isset($args['liveclass'])) {
			$class = $args['liveclass'];
		} else $class = "";

		if(isset($args['previewclass'])) {
			$pclass = $args['previewclass'];
		} else $pclass = "";

		if(isset($args['previewwidth'])) {
			$pw = $args['previewwidth'];
		} else $pw = "320";

		if(isset($args['previewheight'])) {
			$ph = $args['previewheight'];
		} else $ph = "250";

		if(isset($args['capturebuttontext'])) {
			$btnTxt= $args['capturebuttontext'];
		} else $btnTxt = "Capture";

		if(isset($args['capturebuttonclass'])) {
			$btnClass= $args['capturebuttonclass'];
		} else $btnClass = "";


		$html = '<video id="wsform-player" controls autoplay class="'.$class.'"></video>'.$end;
		$html .= '<button id="wsform-capture-screenshot" type="button" class="'.$btnClass.'">'.$btnTxt.'</button>'.$end;
		$html .= '<canvas id="wsform-screenshot-canvas" width='.$pw.' height='.$ph.'></canvas>'.$end;
		$html .= "<script>".$end;
		$html .= "const player = document.getElementById('wsform-player');".$end;
		$html .= "const canvas = document.getElementById('wsform-screenshot-canvas');".$end;
		$html .= "const context = canvas.getContext('2d');".$end;
		$html .= "const captureButton = document.getElementById('wsform-capture-screenshot');".$end;
		$html .= 'const constraints = {'.$end.'video: true,'.$end.'};'.$end;
		$html .= "captureButton.addEventListener('click', () => {
    context.drawImage(player, 0, 0, canvas.width, canvas.height);

    // Stop all video streams.
    player.srcObject.getVideoTracks().forEach(track => track.stop());
  });

  navigator.mediaDevices.getUserMedia(constraints)
    .then((stream) => {
      // Attach the video stream to the video element and autoplay.
      player.srcObject = stream;
    });
</script>";

		$html .= '<input type="hidden" name="wsform_screenshot_filename" value="'.$args['fname'].'" >'."\n";
		$html .= '<input type="hidden" name="wsform_screenshot_type" value="'.$ftype.'" >'."\n";
		$html .= '<input type="hidden" name="wsform_screenshot_page_content" value="'.$pcontent.'" >'."\n";


		return $html;
	}

}