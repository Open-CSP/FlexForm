<?php
/**
 * Created by  : OpenCSP
 * Project     : FlexForm
 * Filename    : external.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 13:21
 */

namespace FlexForm\Processors\Request;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors\Utilities\General;
use FlexForm\FlexFormException;
use MediaWiki\MediaWikiServices;

/**
 * Class external
 * <p>Handles external request</p>
 *
 * @package FlexForm\Processors\Request
 */
class External {

	/**
	 * @param HandleResponse $responseHandler
	 *
	 * @return void
	 * @throws FlexFormException
	 */
	public static function handle( HandleResponse $responseHandler ) {
		$external = General::getGetString( 'script' );
		self::doHandle( $external, $responseHandler );
	}

	/**
	 * Run hook
	 * @param string $external
	 * @param HandleResponse $responseHandler
	 *
	 * @return void
	 */
	public static function runHook( string $external, HandleResponse $responseHandler ) {
		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$handler = new Handlers();
		$res = $hookContainer->run( 'FFAfterFormHandling', [ $external, $handler->setFFPostFields(), &$responseHandler ] );
	}

	/**
	 * Handle extensions
	 * @param HandleResponse $responseHandler
	 *
	 * @return void
	 * @throws FlexFormException
	 */
	public static function handlePost( HandleResponse $responseHandler ) {
		$external = General::getPostString( 'mwextension' );
		self::doHandle( $external, $responseHandler, true );
	}

	/**
	 * @param string|bool $external
	 * @param HandleResponse $responseHandler
	 * @param bool $postHandler
	 *
	 * @return void
	 * @throws FlexFormException
	 */
	private static function doHandle( $external, HandleResponse $responseHandler, bool $postHandler = false ) {
		$handler = new Handlers();
		$handler->setPostHandler( $postHandler );
		if ( $external !== false ) {
			$external = wsSecurity::cleanHTML( $external );
			if ( $handler->handlerExist( $external ) ) {
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'Found extension file handler. Executing',
						$external
					);
				}
				$handler->handlerExecute(
					$external,
					$responseHandler
				);
			} else {
				/*
				throw new FlexFormException(
					wfMessage( 'flexform-external-request-not-found' )->text(),
					0
				);
				*/
				// We cannot find the extension in the extension folder, so let's run the hook
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'Extension not found. Running Hook',
						$external
					);
				}
				self::runHook( $external, $responseHandler );

			}
		}
		/* else {
			if ( $postHandler ) {
				$throwMessage = wfMessage( 'flexform-extension-not-found' )->text();
			} else {
				$throwMessage = wfMessage( 'flexform-external-request-not-found' )->text();
			}
			throw new FlexFormException(
				$throwMessage,
				0
			);
		}*/
	}
}
