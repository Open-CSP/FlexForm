<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\LabelRenderer;
use Xml;

class PlainLabelRenderer implements LabelRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_label( string $input, string $for, array $args ) : string {
		//if ( isset( $args['style'] ) ) {
		if ( !empty( $for ) ) {
			$args['for'] = $for;
		}
		$ret         = '<label ';
		foreach ( $args as $k => $v ) {
			$ret .= $k . '="' . $v . '" ';
		}
		$ret .= '>' . $input . '</label>';
		return $ret;
			/*} else {
				return trim( Xml::label(
					$input,
					$for,
					$args
				) );
			}*/
	}
}