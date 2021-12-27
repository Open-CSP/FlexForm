<?php

use MediaWiki\HookContainer\HookContainer;
use WSForm\Render\Theme;
use WSForm\Render\Themes\WSForm\WSFormTheme;
use WSForm\WSFormException;

/**
 * Class Render
 *
 * This class deals with rendering a form.
 */
class Renderer {
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
        $this->formThemeName = $defaultTheme;
        $this->themes = [
            'wsform' => new WSFormTheme()
        ];

        $hookContainer->run( 'WSFormInitializeThemes', [&$this->themes] );
        $hookContainer->run( 'WSFormDefaultFormTheme', [&$this->formThemeName] );
    }

    /**
     * Returns the requested theme.
     *
     * @param string $theme The name of the theme
     * @return Theme
     * @throws WSFormException
     */
    public function getTheme( string $theme ) {
        if ( !isset( $this->themes[$theme] ) ) {
            throw new WSFormException( 'The theme "' . $theme . '" does not exist.', 0);
        }

        return $this->themes[$theme];
    }

    /**
     * Returns the theme of the current form. This gets set whenever a
     * new form is rendered.
     *
     * @return Theme
     * @throws WSFormException
     */
    public function getFormTheme() {
        return $this->getTheme( $this->formThemeName );
    }

    /**
     * Set the name of the theme to render.
     *
     * @param string $themeName
     */
    public function setFormThemeName( string $themeName ) {
        $this->formThemeName = $themeName;
    }

    /**
     * Returns the name of the theme of the current form.
     *
     * @return string|null
     */
    public function getFormThemeName() {
        return $this->formThemeName;
    }
}