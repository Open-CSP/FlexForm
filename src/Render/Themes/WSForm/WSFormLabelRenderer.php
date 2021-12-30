<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Render\Themes\LabelRenderer;

class WSFormLabelRenderer implements LabelRenderer {
    /**
     * @inheritDoc
     */
    public function render_label( string $input ): string {
        return '<label>' . $input . '</label>';
    }
}