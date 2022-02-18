<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : handlers.php
 * Description :
 * Date        : 31-12-2021
 * Time        : 13:26
 */

namespace FlexForm\Processors\Request\Handlers;

class Handlers {

	private const HANDLER_PATH = __DIR__ . '/Handlers/';

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
	 *
	 * @return void
	 */
	public function handlerExecute( string $name ) {
		if ( $this->handlerExist( $name ) ) {
			include $this->handlersList[$name];
		}
	}


}