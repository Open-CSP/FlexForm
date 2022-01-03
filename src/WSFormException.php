<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : WSFormExceptions.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 12:33
 */

namespace WSForm;

use Exception;

class WSFormException extends \Exception {

	public function __construct( $msg, $val = 0, Exception $old = null ){
		parent::__construct( $msg, $val, $old );
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}
