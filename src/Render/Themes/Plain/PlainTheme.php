<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Render\Themes\CreateRenderer;
use FlexForm\Render\Themes\EditRenderer;
use FlexForm\Render\Themes\EmailRenderer;
use FlexForm\Render\Themes\FieldRenderer;
use FlexForm\Render\Themes\FieldsetRenderer;
use FlexForm\Render\Themes\FormRenderer;
use FlexForm\Render\Themes\InstanceRenderer;
use FlexForm\Render\Themes\LabelRenderer;
use FlexForm\Render\Themes\LegendRenderer;
use FlexForm\Render\Themes\SelectRenderer;
use FlexForm\Render\Themes\Theme;
use FlexForm\Render\Themes\TokenRenderer;

/**
 * Class PlainTheme
 *
 * This class is responsible for rendering a form in the theme "plain".
 *
 * @package FlexForm\Render
 */
class PlainTheme implements Theme {
	/**
	 * @inheritDoc
	 */
	public function getFieldRenderer() : FieldRenderer {
		return new PlainFieldRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getEditRenderer() : EditRenderer {
		return new PlainEditRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getCreateRenderer() : CreateRenderer {
		return new PlainCreateRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getEmailRenderer() : EmailRenderer {
		return new PlainEmailRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getInstanceRenderer() : InstanceRenderer {
		return new PlainInstanceRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getFormRenderer() : FormRenderer {
		return new PlainFormRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldsetRenderer() : FieldsetRenderer {
		return new PlainFieldsetRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getSelectRenderer() : SelectRenderer {
		return new PlainSelectRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getTokenRenderer() : TokenRenderer {
		return new PlainTokenRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getLegendRenderer() : LegendRenderer {
		return new PlainLegendRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getLabelRenderer() : LabelRenderer {
		return new PlainLabelRenderer();
	}
}