<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : FlexFormExceptions.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 12:33
 */

namespace FlexForm;

use Exception;

class FlexFormException extends \Exception {

	/**
	 * @param $msg
	 * @param $val
	 * @param Exception|null $old
	 */
	public function __construct( $msg, $val = 0, Exception $old = null ) {
		parent::__construct( $msg, $val, $old );
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}
