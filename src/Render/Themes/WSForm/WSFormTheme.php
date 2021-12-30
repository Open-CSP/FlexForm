<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Render\Themes\CreateRenderer;
use WSForm\Render\Themes\EditRenderer;
use WSForm\Render\Themes\EmailRenderer;
use WSForm\Render\Themes\FieldRenderer;
use WSForm\Render\Themes\FieldsetRenderer;
use WSForm\Render\Themes\FormRenderer;
use WSForm\Render\Themes\InstanceRenderer;
use WSForm\Render\Themes\LabelRenderer;
use WSForm\Render\Themes\LegendRenderer;
use WSForm\Render\Themes\SelectRenderer;
use WSForm\Render\Themes\Theme;
use WSForm\Render\Themes\TokenRenderer;

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