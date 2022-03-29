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

use FlexForm\Core\HandleResponse;

class Handlers {

	private const HANDLER_PATH = __DIR__ . '/Handlers/';

	private $isPostHandler;

	private $handlersList = [];

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

	public function setPostHandler( bool $postHandler ) {
		$this->isPostHandler = $postHandler;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function handlerExist( string $name ) : bool {
		return array_key_exists(
			$name,
			$this->handlersList
		);
	}

	/**
	 * @param string $name
	 * @param HandleResponse $responseHandler
	 *
	 * @return void
	 */
	public function handlerExecute( string $name, HandleResponse $responseHandler ) {
		if ( $this->handlerExist( $name ) ) {
			$class = 'FlexForm\\Processors\\Request\\Handlers\\' . $name;
			$handler = new $class;
			$handler->execute( $responseHandler );
		}
	}

}