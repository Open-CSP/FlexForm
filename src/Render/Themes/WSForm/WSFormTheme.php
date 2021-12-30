<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Render\CreateRenderer;
use WSForm\Render\EditRenderer;
use WSForm\Render\EmailRenderer;
use WSForm\Render\FieldRenderer;
use WSForm\Render\FieldsetRenderer;
use WSForm\Render\FormRenderer;
use WSForm\Render\InstanceRenderer;
use WSForm\Render\LabelRenderer;
use WSForm\Render\LegendRenderer;
use WSForm\Render\SelectRenderer;
use WSForm\Render\Theme;
use WSForm\Render\TokenRenderer;

/**
 * Class WSFormTheme
 *
 * This class is responsible for rendering a form in the theme "wsform".
 *
 * @package WSForm\Render
 */
class WSFormTheme implements Theme {
    /**
     * @inheritDoc
     */
    public function getFieldRenderer(): FieldRenderer {
        return new WSFormFieldRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getEditRenderer(): EditRenderer {
        return new WSFormEditRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getCreateRenderer(): CreateRenderer {
        return new WSFormCreateRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getEmailRenderer(): EmailRenderer {
        return new WSFormEmailRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getInstanceRenderer(): InstanceRenderer {
        return new WSFormInstanceRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getFormRenderer(): FormRenderer {
        return new WSFormFormRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getFieldsetRenderer(): FieldsetRenderer {
        return new WSFormFieldsetRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getSelectRenderer(): SelectRenderer {
        return new WSFormSelectRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getTokenRenderer(): TokenRenderer {
        return new WSFormTokenRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getLegendRenderer(): LegendRenderer {
        return new WSFormLegendRenderer();
    }

    /**
     * @inheritDoc
     */
    public function getLabelRenderer(): LabelRenderer {
        return new WSFormLabelRenderer();
    }
}