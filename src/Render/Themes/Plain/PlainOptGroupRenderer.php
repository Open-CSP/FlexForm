<?php
/**
 * Created by  : OpenCSP
 * Project     : FlexForm
 * Filename    : PlainOptGroupRenderer.php
 * Description :
 * Date        : 16-2-2025
 * Time        : 19:10
 */

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\OptGroupRenderer;

class PlainOptGroupRenderer implements OptGroupRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_optgroup( string $input, array $args ): string {
		$ret = '<optgroup ';
		foreach ( $args as $name => $value ) {
			$ret .= sprintf( '%s="%s" ', htmlspecialchars( $name ), htmlspecialchars( $value ) );
		}
		return $ret . '>' . $input . '</optgroup>';
	}
}