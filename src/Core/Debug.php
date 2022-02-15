<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : Debug.php
 * Description :
 * Date        : 6-1-2022
 * Time        : 13:33
 */

namespace FlexForm\Core;

class Debug {
	private static $debugMessages = array();

	public static function addToDebug( $title, $details ) {
		self::$debugMessages[$title] = $details;
	}

	private static function debugCSS(){
		$ret = <<<ENDING
<style>
.wsform-debug {
  padding: 1.5rem;
}
.wsform-debug details {
    border: 1px solid #ddd;
    background: #fff;
    margin-bottom: 1.5rem;
    border-radius: 0.35rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
		0 10px 10px -5px rgba(0, 0, 0, 0.04);
		overflow: scroll;
}
.wsform-debug details summary {
      cursor: pointer;
      padding: 1rem;
      border-bottom: 1px solid #ddd;
    }
.wsform-debug details div {
      padding: 1rem 1.5rem;
    }

</style>

ENDING;
		return $ret;
	}

	public static function createDebugOutput() {
		$ret = self::debugCSS();
		$ret .= '<h2>FlexForm Debug</h2><div class="wsform-debug">';
		foreach( self::$debugMessages as $title=>$message ) {
			$ret .= '<details><summary>'.$title.'</summary>';
			$ret .= '<div>';
			if( is_array( $message ) ) {
				$ret .= "<pre>" . print_r( $message, true ) . '</pre>';
			} else {
				$ret .= '<p>' . $message . '</p>';
			}
			$ret .= '</div></details>';
		}
		return $ret . '</div>';
	}
}