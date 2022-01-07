<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Render\Themes\LabelRenderer;
use Xml;

class WSFormLabelRenderer implements LabelRenderer {
    /**
     * @inheritDoc
     */
    public function render_label( string $input, string $for, array $args ): string {
        return Xml::label( $input, $for, $args );
    }
}