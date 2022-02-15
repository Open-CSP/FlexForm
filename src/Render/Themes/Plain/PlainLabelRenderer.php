<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\LabelRenderer;
use Xml;

class PlainLabelRenderer implements LabelRenderer {
    /**
     * @inheritDoc
     */
    public function render_label( string $input, string $for, array $args ): string {
        return Xml::label( $input, $for, $args );
    }
}