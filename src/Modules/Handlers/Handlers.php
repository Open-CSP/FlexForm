<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : handlers.php
 * Description :
 * Date        : 31-12-2021
 * Time        : 13:26
 */

namespace WSForm\Modules\Handlers;

class Handlers {

	const HANDLER_PATH = __DIR__ . '/';

	private $handlersList = array();

	public function __construct(){
		$fileList = glob( self::HANDLER_PATH . '*.php' );
		foreach( $fileList as $fileHandle ) {
			$bName = str_replace( '.php', '', basename( $fileHandle ) );
			$this->handlersList[$bName] = $fileHandle;
		}
		unset( $this->handlersList['Handlers'] );
	}

	public function handlerExist( string $name ): bool {
		return key_exists( $name, $this->handlersList );
	}

	public function handlerExecute( string $name ) {
		if( $this->handlerExist( $name ) ) {
			include $this->handlersList[$name];
		}
	}

	
}