<?php

namespace WSForm\Render;

/**
 * Class Theme
 *
 * This class is responsible for providing the renderers for a specific theme.
 */
interface Theme {
    /**
     * Get a new FieldRenderer.
     *
     * @return FieldRenderer
     */
    public function getFieldRenderer(): FieldRenderer;

    /**
     * Get a new EditRenderer.
     *
     * @return EditRenderer
     */
    public function getEditRenderer(): EditRenderer;

    /**
     * Get a new CreateRenderer.
     *
     * @return CreateRenderer
     */
    public function getCreateRenderer(): CreateRenderer;

    /**
     * Get a new EmailRenderer.
     *
     * @return EmailRenderer
     */
    public function getEmailRenderer(): EmailRenderer;

    /**
     * Get a new InstanceRenderer.
     *
     * @return InstanceRenderer
     */
    public function getInstanceRenderer(): InstanceRenderer;

    /**
     * Get a new FormRenderer.
     *
     * @return FormRenderer
     */
    public function getFormRenderer(): FormRenderer;

    /**
     * Get a new FieldsetRenderer.
     *
     * @return FieldsetRenderer
     */
    public function getFieldsetRenderer(): FieldsetRenderer;

    /**
     * Get a new SelectRenderer.
     *
     * @return SelectRenderer
     */
    public function getSelectRenderer(): SelectRenderer;

    /**
     * Get a new TokenRenderer.
     *
     * @return TokenRenderer
     */
    public function getTokenRenderer(): TokenRenderer;

    /**
     * Get a new LegendRenderer.
     *
     * @return LegendRenderer
     */
    public function getLegendRenderer(): LegendRenderer;

    /**
     * Get a new LabelRenderer.
     *
     * @return LabelRenderer
     */
    public function getLabelRenderer(): LabelRenderer;
}