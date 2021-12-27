<?php

use MediaWiki\MediaWikiServices;

/**
 * This file is loaded by MediaWiki\MediaWikiServices::getInstance() during the
 * bootstrapping of the dependency injection framework.
 *
 * @file
 */

return [
    /**
     * Instantiator function for the Renderer singleton.
     *
     * @return Renderer The Renderer singleton
     */
    "WSForm.Renderer" => static function ( MediaWikiServices $services ): Renderer {
        return new Renderer(
            $services->getMainConfig()->get('WSFormDefaultTheme'),
            $services->getHookContainer()
        );
    },
];
