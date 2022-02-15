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
    "WSForm.ThemeStore" => static function ( MediaWikiServices $services ): ThemeStore {
        if ( !\WSForm\Core\Config::getConfigStatus() ) {
            \WSForm\Core\Config::setConfigFromMW();
        }

        return new ThemeStore(
            \WSForm\Core\Config::getConfigVariable( 'WSFormDefaultTheme' ),
            $services->getHookContainer()
        );
    },
];
