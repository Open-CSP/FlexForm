<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : handlers.php
 * Description :
 * Date        : 31-12-2021
 * Time        : 13:26
 */

namespace FlexForm\Processors\Request;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\FlexFormException;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors\Utilities\General;

class Handlers {

	private const HANDLER_PATH = __DIR__ . '/Handlers/';
	private const EXTENSION_PATH = __DIR__ . '/../../Modules/Handlers/';

	/**
	 * @var mixed
	 */
	private $isPostHandler;

	/**
	 * @var array
	 */
	private array $handlersList = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$fileList = glob( self::HANDLER_PATH . '*.php' );
		foreach ( $fileList as $fileHandle ) {
			$bName = str_replace(
				'.php',
				'',
				basename( $fileHandle )
			);
			$this->handlersList[$bName] = $fileHandle;
		}
		unset( $this->handlersList['Handlers'] );
	}

	/**
	 * @param bool $postHandler
	 *
	 * @return void
	 */
	public function setPostHandler( bool $postHandler ) {
		$this->isPostHandler = $postHandler;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function handlerExist( string $name ) : bool {
		if ( $this->isPostHandler === true ) {
			return self::postHandlerExists( $name );
		}
		return array_key_exists(
			$name,
			$this->handlersList
		);
	}

	/**
	 * Function to create submitted postfields to pass on to WSForm extensions
	 *
	 * @return mixed
	 */
	public function setFFPostFields() {
		foreach ( $_POST as $k => $v ) {
			if ( Definitions::isFlexFormSystemField( $k ) ) {
				unset( $_POST[$k] );
			} else {
				if ( is_array( $_POST[$k] ) ) {
					foreach ( $_POST[$k] as $key => $val ) {
						$_POST[$k][$key] = wsSecurity::cleanBraces( wsSecurity::cleanHTML( $val ) );
					}
				} else {
					$clean_html = wsSecurity::cleanHTML( $_POST[ $k ],
						$k );

					$_POST[ $k ] = wsSecurity::cleanBraces( $clean_html );
				}
			}
		}
		$wsPostFields = $_POST;
		unset( $_POST );

		return $wsPostFields;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function postHandlerExists( string $name ): bool {
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Extension to check',
				self::EXTENSION_PATH . $name . '/PostHandler.php'
			);
		}
		if ( file_exists( self::EXTENSION_PATH . $name . '/PostHandler.php' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $name
	 * @param HandleResponse $responseHandler
	 *
	 * @return void
	 * @throws FlexFormException
	 */
	public function handlerExecute( string $name, HandleResponse $responseHandler ) {
		if ( $this->handlerExist( $name ) ) {
			if ( $this->isPostHandler === true ) {
				$class = 'FlexForm\\Modules\\Handlers\\' . $name . '\\' . 'PostHandler';
			} else {
				$class = 'FlexForm\\Processors\\Request\\Handlers\\' . $name;
			}
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Extension class to run ',
					$class
				);
			}
			//echo Debug::createDebugOutput();
			//die();
			if ( class_exists( $class ) ) {
				$handler = new $class;
				if ( method_exists( $handler, 'execute' ) ) {
					if ( $this->isPostHandler === true ) {
						$extensionsConfig = Config::getConfigVariable( 'extensions' );
						$config           = $extensionsConfig[$name] ?? null;
						$responseHandler  = $handler->execute(
							$this->setFFPostFields(),
							$config,
							$responseHandler
						);
					} else {
						$handler->execute( $responseHandler );
					}
					return $responseHandler;
				} else {
					throw new FlexFormException(
						wfMessage( 'flexform-query-handler-no-method' )->text(),
						0
					);
				}
			} else {
				throw new FlexFormException(
					wfMessage( 'flexform-query-handler-no-class' )->text(),
					0
				);
			}
		} else {
			throw new FlexFormException(
				wfMessage( 'flexform-extension-not-found' )->text(),
				0
			);
		}
	}

}