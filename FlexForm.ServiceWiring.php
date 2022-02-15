<?php

use MediaWiki\MediaWikiServices;
use FlexForm\Render\ThemeStore;

/**
 * This file is loaded by MediaWiki\MediaWikiServices::getInstance() during the
 * bootstrapping of the dependency injection framework.
 *
 * @file
 */

return [
    /**
     * Instantiator function for the ThemeStore singleton.
     *
     * @return ThemeStore The ThemeStore singleton
     */
    "FlexForm.ThemeStore" => static function ( MediaWikiServices $services ): ThemeStore {
        if ( !\FlexForm\Core\Config::getConfigStatus() ) {
            \FlexForm\Core\Config::setConfigFromMW();
        }

        return new ThemeStore(
            \FlexForm\Core\Config::getConfigVariable( 'FlexFormDefaultTheme' ),
            $services->getHookContainer()
        );
    },
];
