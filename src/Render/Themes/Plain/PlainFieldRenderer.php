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
	public function render_file( $args ) : string {

		/*
		 * $result['verbose_div'] = $verboseDiv;
		$result['error_div'] = $errorDiv;
		$result['attributes'] = $attributes;
		$result['function_fields'] = $hiddenFiles;
		 */
		// FIXME: Can you (attempt to) rewrite this @Charlot?

		if ( ! is_array ( $args ) ) return trim( $args );

		$args['attributes']['type'] = 'file';
		$ret = Xml::tags(
			'input',
			$args['attributes'],
			''
		);
		//$neededFields = implode( PHP_EOL, $args['function_fields'] );
		$hiddenField = '';
		foreach ( $args['function_fields'] as $hidden ) {
			if ( ! empty( $hidden ) ) {
				$hiddenField .= trim( $hidden );
			}
		}
		$ret = $hiddenField . $ret;
		if ( $args['verbose_div']['id'] !== false ) {
			$classes = implode( ' ', $args['verbose_div']['class'] );
			$ret .= '<div id="' . $args['verbose_div']['id'] . '" class="' . $classes . '"></div>';
		}
		if ( $args['error_div']['id'] !== false ) {
			$classes = implode( ' ', $args['error_div']['class'] );
			$ret .= '<div id="' . $args['error_div']['id'] . '" class="' . $classes . '"></div>';
		}

		return trim( $ret );
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
		$result = Xml::tags(
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