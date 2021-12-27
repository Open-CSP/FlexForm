<?php

use WSForm\Render\Theme;
use WSForm\Render\Themes\WSForm\WSFormTheme;

/**
 * Class Render
 *
 * This class deals with rendering a form.
 */
class Renderer {
    /**
     * @var string The currently configured theme
     */
    private $formTheme = null;

    /**
     * @var Theme[] Array of valid themes
     */
    private $themes;

    /**
     * @var string The name of the default theme
     */
    private $defaultTheme;

    /**
     * Returns the requested theme, or the default theme if the requested theme does not exist.
     *
     * @param string $theme The name of the theme
     * @return Theme
     */
    public function getTheme( $theme ) {
        if ( !isset( $themes ) ) {
            $this->initializeThemes();
        }

        return isset( $this->themes[$theme] ) ?
            $this->themes[$theme] :
            $this->getDefaultTheme();
    }

    /**
     * Returns the currently configured theme. This gets set whenever a new form is rendered.
     *
     * @return Theme
     */
    public function getFormTheme() {
        if ( !isset( $this->formTheme ) ) {
            return $this->getDefaultTheme();
        }

        return $this->getTheme( $this->defaultTheme );
    }

    /**
     * Returns the default theme.
     *
     * @return Theme
     */
    public function getDefaultTheme() {
        if ( !isset( $this->themes[$this->defaultTheme] ) ) {
            throw new LogicException( "Invalid default theme" );
        }

        return $this->themes[$this->defaultTheme];
    }

    /**
     * Set the name of the theme to render.
     *
     * @param string $theme
     */
    public function setFormThemeName(string $theme) {
        $this->formTheme = $theme;
    }

    /**
     * Returns the name of the current form theme.
     *
     * @return string|null
     */
    public function getFormThemeName() {
        return $this->formTheme;
    }

    /**
     * Initializes the set of themes.
     */
    private function initializeThemes() {
        $this->themes = [
            'wsform' => new WSFormTheme()
        ];

        // Allow other extensions to define more themes
        Hooks::run('WSFormInitializeThemes', [&$this->themes]);

        $this->defaultTheme = 'wsform';

        // Allow other extensions to overwrite the default theme
        Hooks::run('WSFormDefaultTheme', [&$this->defaultTheme]);
    }
}