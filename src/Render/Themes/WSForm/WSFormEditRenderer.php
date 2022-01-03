<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Render\Themes\EditRenderer;

class WSFormEditRenderer implements EditRenderer {
    /**
     * @inheritDoc
     */
    public function render_edit( string $target, string $template, string $formfield, string $usefield, string $slot, string $value ): string {
        $tagValue = htmlspecialchars( $target ) . '-^^-' .
            htmlspecialchars( $template ) . '-^^-' .
            htmlspecialchars( $formfield ) . '-^^-' .
            htmlspecialchars( $usefield ) . '-^^-' .
            htmlspecialchars( $value ) . '-^^-' .
            htmlspecialchars( $slot );

        return sprintf( '<input type="hidden" name="mwedit[]" value="%s">' . PHP_EOL, $tagValue );
    }
}