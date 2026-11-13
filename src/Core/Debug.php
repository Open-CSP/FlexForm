<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : Debug.php
 * Description :
 * Date        : 6-1-2022
 * Time        : 13:33
 */

namespace FlexForm\Core;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;

class Debug {

	/**
	 * @var array
	 */
	private static array $debugMessages = [];

	/**
	 * Check if debug is enabled in config AND current user is a sysop
	 *
	 * @return bool
	 */
	public static function canViewDebug(): bool {
		if ( !Config::isDebug() ) {
			return false;
		}
		$user = RequestContext::getMain()->getUser();
		if ( !$user->isRegistered() ) {
			return false;
		}
		$userGroups = MediaWikiServices::getInstance()
			->getUserGroupManager()
			->getUserEffectiveGroups( $user );

		return in_array( 'sysop', $userGroups, true );
	}

	/**
	 * @param string $title
	 * @param mixed $details
	 * @param float|bool $duration
	 *
	 * @return void
	 */
	public static function addToDebug( string $title, mixed $details, mixed $duration = false ): void {
		if ( self::canViewDebug() ) {
			$safeTitle = htmlspecialchars( $title, ENT_QUOTES, 'UTF-8' ) . ' (' . microtime() . ')';
			if ( $duration !== false && is_numeric( $duration ) ) {
				$newTitle = '<span class="ff-debug-title">' . $safeTitle . '</span>';
				$newTitle .= '<span class="ff-debug-duration">' . ( $duration / 1000 ) . '</span>';
				$safeTitle = $newTitle;
			}
			self::$debugMessages[$safeTitle] = $details;
		}
	}

	/**
	 * @return string
	 */
	private static function debugCSS(): string {
		return <<<ENDING
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
     content: " seconds";
}


</style>

ENDING;
	}

	/**
	 * @return string
	 */
	public static function createDebugOutput(): string {
		if ( !self::canViewDebug() ) {
			return '';
		}
		$ret = self::debugCSS();
		$ret .= '<h2>FlexForm Debug</h2><div class="wsform-debug">';
		foreach ( self::$debugMessages as $title => $message ) {
			$ret .= '<details><summary>' . $title . '</summary>';
			$ret .= '<div>';
			if ( is_array( $message ) || is_object( $message ) ) {
				$ret .= '<pre>' . htmlspecialchars( print_r( $message, true ), ENT_QUOTES, 'UTF-8' ) . '</pre>';
			} else {
				$ret .= '<p>' . htmlspecialchars( (string)$message, ENT_QUOTES, 'UTF-8' ) . '</p>';
			}
			$ret .= '</div></details>';
		}
		return $ret . '</div>';
	}
}
