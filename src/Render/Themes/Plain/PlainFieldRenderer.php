<?php

namespace FlexForm\Render\Themes\Plain;

use Parser;
use PPFrame;
use FlexForm\Core\Core;
use FlexForm\Core\Validate;
use FlexForm\Render\Themes\FieldRenderer;
use Xml;

class PlainFieldRenderer implements FieldRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_text( array $args ) : string {
		$args['type'] = 'text';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_hidden( array $args ) : string {
		$args['type'] = 'hidden';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_secure( array $args ) : string {
		$args['type'] = 'hidden';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_search( array $args ) : string {
		$args['type'] = 'search';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_number( array $args ) : string {
		$args['type'] = 'number';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_radio( array $args, string $showOnChecked = '' ) : string {
		$args['type']            = 'radio';
		$args['data-wssos-show'] = $showOnChecked;

		$ret = Xml::tags(
			'input',
			$args,
			''
		);

		if ( $showOnChecked !== '' ) {
			$ret .= Core::addShowOnSelectJS();
		}

		return $ret;
	}

	/**
	 * @inheritDoc
	 */
	public function render_checkbox(
		array $args,
		string $showOnChecked = '',
		string $showOnUnchecked = '',
		string $default = '',
		string $defaultName = ''
	) : string {
		$ret = '';

		if ( $default !== '' && $defaultName !== '' ) {
			// Added in v0.8.0.9.6.2. Allowing for a default value for a checkbox
			// for when the checkbox is not checked.
			$ret .= Core::createHiddenField(
				$defaultName,
				$default
			);
		}

		$args['type']                      = 'checkbox';
		$args['data-wssos-show']           = $showOnChecked;
		$args['data-wssos-show-unchecked'] = $showOnUnchecked;

		$ret .= Xml::tags(
			'input',
			$args,
			''
		);

		if ( $showOnChecked !== '' || $showOnUnchecked !== '' ) {
			$ret .= Core::addShowOnSelectJS();
		}

		return $ret;
	}

	/**
	 * @inheritDoc
	 */
	public function render_file( array $args ) : string {
		// FIXME: Can you (attempt to) rewrite this @Charlot?

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
						$thumbWidth = $v;
						break;
					case "slim_thumb_height":
						$thumbHeight = $v;
						break;
					default:
						$ret .= $k . '="' . $v . '" ';
				}
			}
			if ( substr(
					 $k,
					 0,
					 5
				 ) == "data-" ) {
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
			if ( $verbose_id === false ) {
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
			$random         = round( microtime( true ) * 1000 );
			$onChangeScript = 'function WSFile' . $random . '(){' . "\n" . '$("#' . $id . '").on("change", function(){' . "\n" . 'wsfiles( "';
			$onChangeScript .= $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label;
			$onChangeScript .= '", "' . $verbose_custom . '", "' . $error_custom . '");' . "\n" . '});' . "\n" . '};';
			$jsChange       = $onChangeScript . "\n";
			//$ret .= "<script>\n" . $onChangeScript . "\n";
			$jsChange .= "\n" . "wachtff(WSFile" . $random . ");\n";
			Core::includeInlineScript( $jsChange );
			//$ret     .= '<script>$( document ).ready(function() { $("#' . $random . '").on("change", function(){ wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '", "' . $verbose_custom . '", "' . $error_custom . '");});});</script>';
			$css     = file_get_contents( "$IP/extensions/FlexForm/Modules/WSForm_upload.css" );
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
			$css     = str_replace(
				$replace,
				$with,
				$css
			);
			Core::includeInlineCSS( $css );
			//$ret     .= $css;
			if ( ! Core::isLoaded( 'WSFORM_upload.js' ) ) {
				Core::addAsLoaded( 'WSFORM_upload.js' );
				$js = file_get_contents( "$IP/extensions/FlexForm/Modules/WSForm_upload.js" );
				Core::includeInlineScript( $js );
			} else {
				$js = '';
			}
			// As of MW 1.35+ we get errors here. It's replacing spaces with &#160; So now we put the js in the header
			//echo "\n<script>" . $js . "</script>";

			$js           = "";
			$wsFileScript = "\nfunction wsfilesFunc" . $random . "(){\n";
			$wsFileScript .= "\n" . 'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");' . "\n";
			$wsFileScript .= "}\n";
			//$ret .= '<script>'. "\n".'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");</script>';

			Core::includeInlineScript( "\n" . $wsFileScript . "\n" . 'wachtff(wsfilesFunc' . $random . ');' );
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
	public function render_date( array $args ) : string {
		$args['type'] = 'date';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_month( array $args ) : string {
		$args['type'] = 'month';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_week( array $args ) : string {
		$args['type'] = 'week';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_time( array $args ) : string {
		$args['type'] = 'time';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_datetime( array $args ) : string {
		$args['type'] = 'datetime';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_datetimelocal( array $args ) : string {
		$args['type'] = 'datetime-local';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_password( array $args ) : string {
		$args['type'] = 'password';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_email( array $args ) : string {
		$args['type'] = 'email';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_color( array $args ) : string {
		$args['type'] = 'color';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_range( array $args ) : string {
		$args['type'] = 'range';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_image( array $args ) : string {
		$args['type'] = 'image';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_url( array $args ) : string {
		$args['type'] = 'url';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_tel( array $args ) : string {
		$args['type'] = 'tel';

		return Xml::tags(
			'input',
			$args,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_option(
		string $input,
		string $value,
		?string $showOnSelect,
		bool $isSelected,
		array $additionalArgs
	) : string {
		$optionAttributes = array_merge(
			[
				'value' => htmlspecialchars( $value )
			],
			$additionalArgs
		);

		if ( $showOnSelect !== null ) {
			$optionAttributes['data-wssos-show'] = htmlspecialchars( $showOnSelect );
		}

		if ( $isSelected ) {
			$optionAttributes['selected'] = 'selected';
		}

		return Xml::tags(
			'option',
			$optionAttributes,
			htmlspecialchars( $input )
		);
	}


	/**
	 * @inheritDoc
	 */
	public function render_submit( array $args, bool $identifier ) : string {
		$submitAttributes = array_merge(
			$args,
			[
				'type' => $identifier ? 'button' : 'submit'
			]
		);

		return Xml::tags(
			'input',
			$submitAttributes,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_button( string $input, string $buttonType, array $additionalArguments ) : string {
		$tagAttributes = array_merge(
			[ 'type' => $buttonType ],
			$additionalArguments
		);

		return Xml::tags(
			'button',
			$tagAttributes,
			htmlspecialchars( $input )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_reset( array $additionalArgs ) : string {
		$tagAttributes = array_merge(
			[ 'type' => 'reset' ],
			$additionalArgs
		);

		return Xml::tags(
			'input',
			$tagAttributes,
			''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_textarea(
		string $input,
		string $name,
		?string $class,
		?string $editor,
		array $additionalArguments
	) : string {
		$textareaAttributes = $additionalArguments;

		if ( $class !== null ) {
			$textareaAttributes['class'] = $class;
		}

		if ( $editor === 've' ) {
			// TODO: Implement VisualEditor
		}

		return Xml::textarea(
			$name,
			$input,
			40,
			5,
			$textareaAttributes
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render_mobilescreenshot( string $input, array $args, Parser $parser, PPFrame $frame ) : string {
		// TODO: Implement mobilescreenshot
		return '';
	}

}