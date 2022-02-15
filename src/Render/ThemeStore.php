<?php

namespace FlexForm\Render;

use MediaWiki\HookContainer\HookContainer;
use FlexForm\Render\Themes\Theme;
use FlexForm\Render\Themes\WSForm\WSFormTheme;
use FlexForm\WSFormException;

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
     * @throws WSFormException
     */
    public function setFormThemeName( string $themeName ) {
        if ( !isset( $this->themes[$themeName] ) ) {
            throw new WSFormException( 'Invalid theme ' . $themeName, 0 );
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