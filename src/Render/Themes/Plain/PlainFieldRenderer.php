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
		return $this->renderSimpelInput( 'text', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_hidden( array $args ) : string {
		return $this->renderSimpelInput( 'hidden', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_message( array $args ) : string {
		return $this->renderSimpelInput( 'hidden', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_secure( array $args ) : string {
		return $this->renderSimpelInput( 'hidden', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_search( array $args ) : string {
		return $this->renderSimpelInput( 'search', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_number( array $args ) : string {
		return $this->renderSimpelInput( 'number', $args );
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

		return $ret;
	}

	/**
	 * @inheritDoc
	 */
	public function render_checkbox(
		array $args,
		string $showOnChecked = '',
		string $showOnUnchecked = '',
		$default = false,
		$defaultName = false
	) : string {
		$ret = '';

		if ( $default !== false && $defaultName !== false ) {
			// Added in v0.8.0.9.6.2. Allowing for a default value for a checkbox
			// for when the checkbox is not checked.
			$ret .= Core::createHiddenField(
				$defaultName,
				$default
			);
		}

		$args['type'] = 'checkbox';
		if ( !empty( $showOnChecked ) ) {
			$args['data-wssos-show'] = $showOnChecked;
		}
		if ( !empty( $showOnUnchecked ) ) {
			$args['data-wssos-show-unchecked'] = $showOnUnchecked;
		}

		$ret .= Xml::tags(
			'input',
			$args,
			''
		);

		return $ret;
	}

	/**
	 * @inheritDoc
	 */
	public function render_file( $args ) : string {
		/*
		 * $result['verbose_div'] = $verboseDiv;
		$result['error_div'] = $errorDiv;
		$result['attributes'] = $attributes;
		$result['function_fields'] = $hiddenFiles;
		 */
		// FIXME: Can you (attempt to) rewrite this @Charlot?

		if ( !is_array( $args ) ) {
			return trim( $args );
		}
		// We need to handle canvas, not real files.
		if ( $args['canvas'] !== '' ) {
			$ret = $args['canvas'];
		} elseif ( $args['mobileScreenshot'] ) {
			$ret = $args['mobileScreenshot'];
		} else {
			$args['attributes']['type'] = 'file';
			$ret                        = Xml::tags(
				'input',
				$args['attributes'],
				''
			);
		}
		//$neededFields = implode( PHP_EOL, $args['function_fields'] );
		/*
		$hiddenField = '';
		foreach ( $args['function_fields'] as $hidden ) {
			if ( ! empty( $hidden ) ) {
				$hiddenField .= trim( $hidden );
			}
		}
		*/
		//$ret = $args['action_fields'] . $ret;

		if ( !$args['canvas'] && !$args['mobileScreenshot'] ) {
			if ( $args['verbose_div']['id'] !== false ) {
				$classes = implode(
					' ',
					$args['verbose_div']['class']
				);
				$ret     .= '<div id="' . $args['verbose_div']['id'] . '" class="' . $classes . '"></div>';
			}
			if ( $args['error_div']['id'] !== false ) {
				$classes = implode(
					' ',
					$args['error_div']['class']
				);
				$ret     .= '<div id="' . $args['error_div']['id'] . '" class="' . $classes . '"></div>';
			}
		}

		return trim( $ret );
	}

	/**
	 * @inheritDoc
	 */
	public function render_date( array $args ) : string {
		return $this->renderSimpelInput( 'date', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_month( array $args ) : string {
		return $this->renderSimpelInput( 'month', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_week( array $args ) : string {
		return $this->renderSimpelInput( 'week', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_time( array $args ) : string {
		return $this->renderSimpelInput( 'time', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_datetime( array $args ) : string {
		return $this->renderSimpelInput( 'datetime', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_datetimelocal( array $args ) : string {
		return $this->renderSimpelInput( 'datetime-local', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_password( array $args ) : string {
		return $this->renderSimpelInput( 'password', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_email( array $args ) : string {
		return $this->renderSimpelInput( 'email', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_color( array $args ) : string {
		return $this->renderSimpelInput( 'color', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_range( array $args ) : string {
		return $this->renderSimpelInput( 'range', $args );
	}

	/**
	 * @inheritDoc
	 */
	public function render_image( array $args ) : string {
		return $this->renderSimpelInput( 'image', $args );
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
				'value' => $value
			],
			$additionalArgs
		);

		if ( $showOnSelect !== null ) {
			$optionAttributes['data-wssos-show'] = $showOnSelect;
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
		$submitAttributes = array_merge( $args,
										 [
											 'type' => $identifier ? 'button' : 'submit'
										 ] );
		$result           = Xml::tags(
			'input',
			$submitAttributes,
			''
		);
		// Add spinner span :
		$result .= Xml::tags(
			'span',
			[ 'class' => 'flex-form-spinner' ],
			''
		);

		return $result;
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
			$input
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

		$ret = '<textarea name="' . $name . '"';
		foreach ( $textareaAttributes as $k => $v ) {
			$ret .= ' ' . $k . '="' . $v . '"';
		}
		$ret .= ">" . $input . "</textarea>";
		return $ret;
	}

	/**
	 * @inheritDoc
	 */
	public function render_mobilescreenshot( string $input, array $args, Parser $parser, PPFrame $frame ) : string {
		// TODO: Implement mobilescreenshot
		return '';
	}

	/**
	 * @param string $type
	 * @param array $args
	 * @param mixed $input
	 *
	 * @return string
	 */
	private function renderSimpelInput( string $type, array $args, $input = false ) {
		$ret = '<input type="' . $type . '"';
		foreach ( $args as $k => $v ) {
			$ret .= ' ' . $k . '="' . $v . '"';
		}
		$ret .= '>';
		if ( $input !== false ) {
			$ret .= $input . '</' . $type . '>';
		}
		return $ret;
	}

}