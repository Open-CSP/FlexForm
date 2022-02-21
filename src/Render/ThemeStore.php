<?php

namespace FlexForm\Render;

use MediaWiki\HookContainer\HookContainer;
use FlexForm\Render\Themes\Theme;
use FlexForm\Render\Themes\Plain\PlainTheme;
use FlexForm\FlexFormException;

/**
 * Class Render
 *
 * This class deals with rendering a form using a theme.
 */
class ThemeStore {
	/**
	 * @var string The name of the currently set theme
	 */
	private $formThemeName;

	/**
	 * @var array<string, Theme> Array of valid themes
	 */
	private $themes;

	/**
	 * Renderer constructor.
	 *
	 * @param string $defaultTheme The default theme to use for a form
	 * @param HookContainer $hookContainer
	 */
	public function __construct( string $defaultTheme, HookContainer $hookContainer ) {
		$defaultTheme = 'Plain'; // TODO: Remove when we have more themes. This whole Structure looks weird! @Marijn
		$this->formThemeName = $defaultTheme;
		$this->themes        = [
			'Plain' => new PlainTheme()
		];

		$hookContainer->run(
			'FlexFormInitializeThemes',
			[ &$this->themes ] );
		$hookContainer->run(
			'FlexFormDefaultFormTheme',
			[ &$this->formThemeName ] );
	}

	/**
	 * Returns the requested theme.
	 *
	 * @param string $theme The name of the theme
	 *
	 * @return Theme
	 * @throws FlexFormException
	 */
	public function getTheme( string $theme ) {
		if ( ! isset( $this->themes[$theme] ) ) {
			throw new FlexFormException(
				'The theme "' . $theme . '" does not exist.',
				0
			);
		}

		return $this->themes[$theme];
	}

	/**
	 * Returns the theme of the current form. This gets set whenever a
	 * new form is rendered.
	 *
	 * @return Theme
	 * @throws FlexFormException
	 */
	public function getFormTheme() {
		return $this->getTheme( $this->formThemeName );
	}

	/**
	 * Set the name of the theme to render.
	 *
	 * @param string $themeName
	 *
	 * @throws FlexFormException
	 */
	public function setFormThemeName( string $themeName ) {
		if ( ! isset( $this->themes[$themeName] ) ) {
			throw new FlexFormException(
				'Invalid theme ' . $themeName,
				0
			);
		}

		$this->formThemeName = $themeName;
	}

	/**
	 * Returns the name of the theme of the current form.
	 *
	 * @return string
	 */
	public function getFormThemeName() {
		return $this->formThemeName;
	}
}