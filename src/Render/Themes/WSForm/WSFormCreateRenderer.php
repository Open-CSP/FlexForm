<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Core\Core;
use WSForm\Render\Themes\CreateRenderer;

class WSFormCreateRenderer implements CreateRenderer {
    /**
     * @inheritDoc
     */
    public function render_create( string $template, string $createId, string $write, string $slot, string $option, string $follow, string $fields, bool $leadingZero ): string {
        $template = htmlspecialchars( $template );
        $createId = htmlspecialchars( $createId );
        $write = htmlspecialchars( $write );
        $slot = htmlspecialchars( $slot );
        $option = htmlspecialchars( $option );
        $fields = htmlspecialchars( $fields );

        if ( $follow !== '' ) {
            $follow = Core::createHiddenField( 'mwfollow', htmlspecialchars( $follow ) );
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