<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\SelectRenderer;
use Xml;

class PlainSelectRenderer implements SelectRenderer {
	const OPTION_SEPARATOR = '::';

	/**
	 * @inheritDoc
	 */
	public function render_select(
		string $input,
		?string $placeholder,
		array $selectedValues,
		array $options,
		array $additionalArgs
	) : string {
		$tagContent = '';

		if ( $placeholder !== null ) {
			$isSelected = $selectedValues === [];
			$tagContent .= Xml::option(
				htmlspecialchars( $placeholder ),
				'',
				$isSelected,
				[
					'disabled' => 'disabled'
				] );
		}

		foreach ( $options as $option ) {
			if ( ! strpos(
				$option,
				self::OPTION_SEPARATOR
			) ) {
				$text = $valueName = $option;
			} else {
				list ( $text, $valueName ) = explode(
					self::OPTION_SEPARATOR,
					$option,
					2
				);
			}

			$isSelected = in_array(
				$text,
				$selectedValues
			);
			$tagContent .= Xml::option(
				htmlspecialchars( $text ),
				$valueName,
				$isSelected
			);
		}

		$tagContent .= $input;

		return Xml::tags(
			'select',
			$additionalArgs,
			$tagContent
		);
	}

}