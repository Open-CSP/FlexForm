<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Core\Core;
use WSForm\Render\Themes\CreateRenderer;

class WSFormCreateRenderer implements CreateRenderer {
    /**
     * @inheritDoc
     */
    public function render_create( ?string $follow, ?string $template, ?string $createId, ?string $write, ?string $slot, ?string $option, ?string $fields, bool $leadingZero ): string {
        $template = $template !== null ? htmlspecialchars( $template ) : '';
        $createId = $createId !== null ? htmlspecialchars( $createId ) : '';
        $write = $write !== null ? htmlspecialchars( $write ) : '';
        $slot = $slot !== null ? htmlspecialchars( $slot ) : '';
        $option = $option !== null ? htmlspecialchars( $option ) : '';
        $fields = $fields !== null ? htmlspecialchars( $fields ) : '';

        if ( $follow !== null ) {
            if ( $follow === '' || $follow === '1' || $follow === 'true' ) {
                $follow = 'true';
            } else {
                $follow = htmlspecialchars( $follow );
            }

            $follow = Core::createHiddenField( 'mwfollow', $follow );
        }

        if ( $fields !== '' ) {
            // TODO: Support mwleadingzero with mwcreatemultiple
            $createValue =
                $template . '-^^-' .
                $write . '-^^-' .
                $option . '-^^-' .
                $fields . '-^^-' .
                $slot . '-^^-' .
                $createId;

            return Core::createHiddenField( 'mwcreatemultiple[]', $createValue ) . $follow;
        } else {
            if ( $template !== '' ) {
                $template = Core::createHiddenField( 'mwtemplate', $template );
            }

            if ( $write !== '' ) {
                $write = Core::createHiddenField( 'mwwrite', $write );
            }

            if ( $option !== '' ) {
                $option = Core::createHiddenField( 'mwoption', $option );
            }

            if ( $slot !== '' ) {
                $slot = Core::createHiddenField( 'mwslot', $slot );
            }

            $leadingZero = $leadingZero ?
                Core::createHiddenField( 'mwleadingzero', 'true' ) : '';

            return $template . $write . $option . $follow . $leadingZero . $slot;
        }
    }
}