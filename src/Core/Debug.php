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

	/**
	 * @var array
	 */
	private static $debugMessages = [];

	/**
	 * @param string $title
	 * @param mixed $details
	 * @param mixed $duration
	 *
	 * @return void
	 */
	public static function addToDebug( string $title, $details, $duration = false ) {
		if ( $duration !== false ) {
			$newTitle = '<span class="ff-debug-title">' . $title . '</span>';
			$newTitle .= '<span class="ff-debug-duration">' . $duration . '</span>';
			$title = $newTitle;
		}
		self::$debugMessages[$title] = $details;
	}

	/**
	 * @return string
	 */
	private static function debugCSS(): string {
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
    
.ff-debug-title {
     text-align:left;
}

.ff-debug-duration {
     float:right;
     color: blue;
}

.ff-debug-duration:after {
     content: " millisecs";
}


</style>

ENDING;
		return $ret;
	}

	/**
	 * @return string
	 */
	public static function createDebugOutput(): string {
		$ret = self::debugCSS();
		$ret .= '<h2>FlexForm Debug</h2><div class="wsform-debug">';
		foreach ( self::$debugMessages as $title => $message ) {
			$ret .= '<details><summary>' . $title . '</summary>';
			$ret .= '<div>';
			if ( is_array( $message ) ) {
				$ret .= "<pre>" . print_r( $message, true ) . '</pre>';
			} else {
				$ret .= '<p>' . $message . '</p>';
			}
			$ret .= '</div></details>';
		}
		return $ret . '</div>';
	}
}