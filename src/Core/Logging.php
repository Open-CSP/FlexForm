<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : Logging.php
 * Description :
 * Date        : 31-1-2023
 * Time        : 21:47
 */

namespace FlexForm\Core;

use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class Logging {

	/**
	 * Logging channel name
	 */
	private const FF_LOG_NAME = 'FlexForm';

	/**
	 * @var LoggerInterface
	 */
	private static LoggerInterface $logger;

	/**
	 * Returns the logger instance.
	 *
	 * @return LoggerInterface
	 */
	public static function getMeLogger(): LoggerInterface {
		if ( !isset( self::$logger ) ) {
			self::$logger = LoggerFactory::getInstance( self::FF_LOG_NAME );
		}

		return self::$logger;
	}

}