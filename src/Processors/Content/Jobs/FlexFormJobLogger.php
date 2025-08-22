<?php
/**
 * Created by  : OpenCSP
 * Project     : FlexForm
 * Filename    : FlexFormJobLogger.php
 * Description :
 * Date        : 8-8-2025
 * Time        : 13:54
 */

namespace FlexForm\Processors\Content\Jobs;

use MediaWiki\Logger\LoggerFactory;

class FlexFormJobLogger {

	/**
	 * Log name to push to
	 */
	private const LOG_NAME = 'FlexForm';

	/**
	 * @param string $msg Message to push
	 * @param array $context Additional contextual information as an array
	 *
	 * @return void
	 */
	public static function logInfo( string $msg, array $context = [] ): void {
		$logger = LoggerFactory::getInstance( self::LOG_NAME );
		$logger->info( $msg, $context );
	}

	/**
	 * @param string $msg Message to push
	 * @param array $context Additional contextual information as an array
	 *
	 * @return void
	 */
	public static function logError( string $msg, array $context = [] ): void {
		$logger = LoggerFactory::getInstance( self::LOG_NAME );
		$logger->error( $msg, $context );
	}

	/**
	 * @param string $msg Message to push
	 * @param array $context Additional contextual information as an array
	 *
	 * @return void
	 */
	public static function logWarning( string $msg, array $context = [] ): void {
		$logger = LoggerFactory::getInstance( self::LOG_NAME );
		$logger->warning( $msg, $context );
	}
}
