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
use FlexForm\Processors\Definitions;
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
	private function setFFPostFields() {
		foreach ( $_POST as $k => $v ) {
			if ( Definitions::isFlexFormSystemField( $k ) ) {
				unset( $_POST[$k] );
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
			$handler = new $class;
			if ( $this->isPostHandler === true ) {
				$handler->execute( $this->setFFPostFields() );
			} else {
				$handler->execute( $responseHandler );
			}
		} else {

		}
	}

}