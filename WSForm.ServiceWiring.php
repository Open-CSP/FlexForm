<?php

use MediaWiki\MediaWikiServices;
use WSForm\Render\ThemeStore;

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
        return new ThemeStore(
            $services->getMainConfig()->get('WSFormDefaultTheme'),
            $services->getHookContainer()
        );
    },
];
