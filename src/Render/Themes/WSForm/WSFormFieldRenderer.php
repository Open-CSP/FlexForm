<?php

namespace WSForm\Render\Themes\WSForm;

use Parser;
use PPFrame;
use ExtensionRegistry;
use WSForm\Core\Core;
use WSForm\Core\Validate;
use WSForm\Render\Themes\FieldRenderer;

class WSFormFieldRenderer implements FieldRenderer {
    /**
     * @inheritDoc
     */
	public function render_text( array $args ): string {
		$ret = '<input type="text" ';
		$ret .= Validate::doSimpleParameters( $args, "text" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_hidden( array $args ): string {
		$ret = '<input type="hidden" ';
		$ret .= Validate::doSimpleParameters( $args, "hidden" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_secure( array $args ): string {
        $ret = '<input type="hidden" ';
        $ret .= validate::doSimpleParameters( $args, "secure" );
        $ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_search( array $args ): string {
		$ret = '<input type="search" ';
		$ret .= validate::doSimpleParameters( $args, "search" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_number( array $args ): string {
		$ret = '<input type="number" ';
		$ret .= validate::doSimpleParameters( $args, "number" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_radio( array $args, string $showOnChecked = '' ): string {
		$ret = '<input type="radio" ';
		$ret .= validate::doRadioParameters( $args );

		if ( $showOnChecked !== '' ) {
			$ret .= 'data-wssos-show="' . htmlspecialchars( $showOnChecked ) . '" ';
		}

		$ret .= ">\n";

		if ( $showOnChecked !== '' ) {
			$ret .= Core::addShowOnSelectJS();
		}

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_checkbox( array $args, string $showOnChecked = '', string $showOnUnchecked = '', string $default = '', string $defaultName = '' ): string {
	    $ret = '';

		if ( $default !== '' && $defaultName !== '' ) {
            // Added in v0.8.0.9.6.2. Allowing for a default value for a checkbox
            // for when the checkbox is not checked.
		    $ret .= Core::createHiddenField( $defaultName, $default );
		}

		$ret .= '<input type="checkbox" ';
		$ret .= validate::doCheckboxParameters( $args );

		if ( $showOnChecked !== '' ) {
			$ret .= 'data-wssos-show="' . $showOnChecked . '" ';
		}

		if ( $showOnUnchecked !== '' ) {
			$ret .= 'data-wssos-show-unchecked="' . $showOnUnchecked . '" ';
		}

		$ret .= ">\n";

		if ( $showOnChecked !== '' || $showOnUnchecked !== '' ) {
			$ret .= Core::addShowOnSelectJS();
		}

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_file( array $args ): string {
	    // FIXME: This is terrible

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
			$jsChange = $onChangeScript . "\n";
			//$ret .= "<script>\n" . $onChangeScript . "\n";
			$jsChange .= "\n" . "wachtff(WSFile".$random.");\n";
			Core::includeInlineScript( $jsChange );
			//$ret     .= '<script>$( document ).ready(function() { $("#' . $random . '").on("change", function(){ wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '", "' . $verbose_custom . '", "' . $error_custom . '");});});</script>';
			$css     = file_get_contents( "$IP/extensions/WSForm/WSForm_upload.css" );
			$replace = array(
				'{{verboseid}}',
				'{{errorid}}',
				'<style>',
				'</style>'
			);
			$with    = array(
				$verbose_id,
				$error_id,
				'',
				''
			); //wsfiles( "file-upload2", "hiddendiv2", "error_file-upload2", "", "yes", "none");
			$css = str_replace( $replace, $with, $css );
			Core::includeInlineCSS( $css );
			//$ret     .= $css;
			if ( !Core::isLoaded( 'WSFORM_upload.js' ) ) {
				Core::addAsLoaded( 'WSFORM_upload.js' );
				$js = file_get_contents( "$IP/extensions/WSForm/WSForm_upload.js" );
				Core::includeInlineScript( $js );
			} else $js = '';
			// As of MW 1.35+ we get errors here. It's replacing spaces with &#160; So now we put the js in the header
			//echo "\n<script>" . $js . "</script>";

			$js="";
			$wsFileScript = "\nfunction wsfilesFunc" . $random . "(){\n";
			$wsFileScript .= "\n" . 'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");' . "\n";
			$wsFileScript .= "}\n";
			//$ret .= '<script>'. "\n".'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");</script>';

			Core::includeInlineScript( "\n" . $wsFileScript . "\n" . 'wachtff(wsfilesFunc'. $random .');' );
		} elseif ( $presentor == "slim" ) {
		    /*
			if ( $slim_image !== false ) {
				$slim_image = '<img src="' . $slim_image . '">';
			} else {
				$slim_image = "";
			}
			$ret = $slim . $ret . $slim_image . "</div>$br";

			// TODO: Move this logic to the caller
			$parser->getOutput()->addModuleStyles( 'ext.wsForm.slim.styles' );
			$parser->getOutput()->addModules( 'ext.wsForm.slim.scripts' );
		    */
		}


		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_date( array $args ): string {
		$ret = '<input type="date" ';
		$ret .= validate::doSimpleParameters( $args, "date" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_month( array $args ): string {
		$ret = '<input type="month" ';
		$ret .= validate::doSimpleParameters( $args, "month" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_week( array $args ): string {
		$ret = '<input type="week" ';
		$ret .= validate::doSimpleParameters( $args, "week" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_time( array $args ): string {
		$ret = '<input type="time" ';
		$ret .= validate::doSimpleParameters( $args, "time" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_datetime( array $args ): string {
		$ret = '<input type="datetime" ';
		$ret .= validate::doSimpleParameters( $args, "datetime" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_datetimelocal( array $args ): string {
		$ret = '<input type="datetime-local" ';
		$ret .= validate::doSimpleParameters( $args, "datetimelocal" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_password( array $args ): string {
		$ret = '<input type="password" ';
		$ret .= validate::doSimpleParameters( $args, "password" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_email( array $args ): string {
		$ret = '<input type="email" ';
		$ret .= validate::doSimpleParameters( $args, "email" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_color( array $args ): string {
		$ret = '<input type="color" ';
		$ret .= validate::doSimpleParameters( $args, "color" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_range( array $args ): string {
		$ret = '<input type="range" ';
		$ret .= validate::doSimpleParameters( $args, "range" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_image( array $args ): string {
		$ret = '<input type="image" ';

		foreach ( $args as $name => $value ) {
            $ret .= htmlspecialchars( $name ) . '="' . htmlspecialchars( $value ) . '" ';
		}

		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_url( array $args ): string {
		$ret = '<input type="url" ';
		$ret .= validate::doSimpleParameters( $args, "url" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_tel( array $args ): string {
		$ret = '<input type="tel" ';
		$ret .= validate::doSimpleParameters( $args, "tel" );
		$ret .= ">\n";

		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_option( string $input, string $value, ?string $showOnSelect, bool $isSelected, array $additionalArgs ): string {
		$optionAttributes = array_merge( [
		    'value' => htmlspecialchars( $value )
        ], $additionalArgs );

		if ( $showOnSelect !== null ) {
		    $optionAttributes['data-wssos-show'] = htmlspecialchars( $showOnSelect );
        }

		if ( $isSelected ) {
		    $optionAttributes['selected'] = 'selected';
        }

		return \Xml::tags('option', $optionAttributes, htmlspecialchars( $input ) );
	}


    /**
     * @inheritDoc
     */
	public function render_submit( array $args, Parser $parser, PPFrame $frame ): string {
		$ret = '<input type="submit" ';
		foreach( $args as $k => $v ){
			$ret .= $k . '="' . $v . '" ';
		}
		$ret .= '/>' . PHP_EOL;
		return $ret;
	}

    /**
     * @inheritDoc
     */
	public function render_button( string $input, array $args, Parser $parser, PPFrame $frame ): string {
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
     * @inheritDoc
     */
	public function render_reset( string $input, array $args, Parser $parser, PPFrame $frame ): string {
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
     * @inheritDoc
     */
	public function render_textarea( string $input, array $args, Parser $parser, PPFrame $frame ): string {
		global $wgOutput;
		$out = $parser->getOutput();
		if( $input === '' ) $input = false;
		$name = '';
		$html = validate::validHTML( $args );
		$ret = '<textarea ';
		$editor = false;
		$class = '';
		$js = '';
		foreach ( $args as $k => $v ) {
			if( $k == 'name' ) {
				$name = $v;
			}
			if( $k == 'value' ) {
				$input = $v;
			}
			if ( validate::check_disable_readonly_required_selected( $k, $v ) ) {
				continue;
			}
			if( $k === "class" ) {
				$class .= $v;
			}
			if ( validate::validParameters( $k ) && $k !== 'value' && $k !== 'class' ) {
				$ret .= $k . '="' . $v . '" ';
			}
			// Editor option
			if( strtolower( $k )  === 'editor' && strtolower( $v ) === 've' ){
				if ( ExtensionRegistry::getInstance()->isLoaded( 'VEForAll' ) ) {
					$out->addModules( 'ext.veforall.main' );
					$editor = true;
					wsform::includeInlineScript( 'var WSFormEditor = "VE";' );
					$cssVE = '.load-editor{ 
								background: url("https://www.wikibase.nl/load-editor.gif") no-repeat bottom right #fff;
								background-size: 50px; 
							}';
					wsform::includeInlineCSS( $cssVE );
					$ret = '<span class="ve-area-wrapper">' . $ret;
					$class .= ' load-editor ';
				}

			}
		}
		$ret .= 'class="' . $class . '">';
		if ( $input !== false ) {

			$input = \wsform\protect\protect::purify( $input, $html, \wsform\wsform::$secure );

			//die();
			$ret .= $input;
			\wsform\wsform::addCheckSum( 'textarea', $name, $input, $html );
		} else {
			$tmp = \wsform\protect\protect::purify( \wsform\wsform::getValue( ( $name ) ), $html, \wsform\wsform::$secure );
			if ( $tmp !== "" ) {
				$ret .= $tmp;
				\wsform\wsform::addCheckSum( 'textarea', $name, $tmp, $html );
			} else \wsform\wsform::addCheckSum( 'textarea', $name, '', $html );
		}
		$ret .= "</textarea>\n";
		if( $editor ) {
			$ret .= '</span>' . PHP_EOL;
		}

		return $ret . $js;
	}

    /**
     * @inheritDoc
     */
	public function render_signature( string $input, array $args, Parser $parser, PPFrame $frame ): string {
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

		$cssFile = file_get_contents($IP.'/extensions/WSForm/modules/signature/css/jquery.signature.css');
		wsform::includeInlineCSS( $cssFile );
		$css = '<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/south-street/jquery-ui.css" rel="stylesheet">';
		$css .= '<script type="text/javascript" charset="UTF-8" src="/extensions/WSForm/modules/signature/js/do-signature.js"></script>' ."\n";


		$js = 'function doWSformActions(){'."\n";
		$js .= '$("#wsform-signature").signature({'."\n";
		$js .= 'syncField: "#wsform_signature_data", syncFormat: "'.strtoupper($ftype).'"';
		$js .= $jsOptions;
		$js .= '} );'."\n";
		$js .= '$("#wsform_signature_clear").click(function(){'."\n".'$("#wsform-signature").signature("clear");'."\n".'});'."\n";

		$js .='};';
		wsform::includeInlineScript( $js );
		$ret = '<input type="hidden" name="wsform_signature_filename" value="'.$args['fname'].'" >'."\n";
		$ret .= '<input type="hidden" name="wsform_signature_type" value="'.$ftype.'" >'."\n";
		$ret .= '<input type="hidden" name="wsform_signature_page_content" value="'.$pcontent.'" >'."\n";
		$ret .= '<input type="hidden" id="wsform_signature_data" name="wsform_signature" '.$required.'value="" >'."\n";
		$ret .= '<div id="wsform-signature" class="wsform-signature '.$class.'"></div>'."\n";
		$ret .= '<button type="button" id="wsform_signature_clear" class="wsform-signature-clear '.$bclass.'">'.$btxt.'</button>'."\n";
		$ret .= "\n";

		return $css.$ret;
	}

    /**
     * @inheritDoc
     */
	public function render_mobilescreenshot( string $input, array $args, Parser $parser, PPFrame $frame ): string {
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

		$js = "const player = document.getElementById('wsform-player');".$end;
		$js .= "const canvas = document.getElementById('wsform-screenshot-canvas');".$end;
		$js .= "const context = canvas.getContext('2d');".$end;
		$js .= "const captureButton = document.getElementById('wsform-capture-screenshot');".$end;
		$js .= 'const constraints = {'.$end.'video: true,'.$end.'};'.$end;
		$js .= "captureButton.addEventListener('click', () => {
    context.drawImage(player, 0, 0, canvas.width, canvas.height);

    // Stop all video streams.
    player.srcObject.getVideoTracks().forEach(track => track.stop());
  });

  navigator.mediaDevices.getUserMedia(constraints)
    .then((stream) => {
      // Attach the video stream to the video element and autoplay.
      player.srcObject = stream;
    });";
		wsform::includeInlineScript( $js );

		$html .= '<input type="hidden" name="wsform_screenshot_filename" value="'.$args['fname'].'" >'."\n";
		$html .= '<input type="hidden" name="wsform_screenshot_type" value="'.$ftype.'" >'."\n";
		$html .= '<input type="hidden" name="wsform_screenshot_page_content" value="'.$pcontent.'" >'."\n";


		return $html;
	}

}