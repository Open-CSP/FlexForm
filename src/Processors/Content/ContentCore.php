<?php

namespace WSForm\Processors\Content;

use RequestContext;
use WSForm\Core\Config;
use WSForm\Processors\Security\wsSecurity;
use WSForm\Processors\Definitions;
use WSForm\Processors\Utilities\General;
use WSForm\Processors\Files\FilesCore;

/**
 * Class core
 *
 * @package WSForm\Processors\Content
 */
class ContentCore {

	private static $fields = array();

	/**
	 * @return array
	 */
	public static function getFields(): array {
		return self::$fields;
	}

	/**
	 * Experimental function to get a username from session
	 *
	 * @param bool $onlyName
	 * @return string
	 */
	private static function setSummary( bool $onlyName = false ): string {
		$user = RequestContext::getMain()->getUser();
		if( $user->isAnon() === false ) {
			if( $onlyName === true ) {
				return ( $user->getName() );
			} else {
				return ( '[[User:' . $user->getName() . ']]' );
			}
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
			return ('Anon user: ' . $ip);
		}
	}

	private static function checkFields(){
		if( self::$fields['summary'] === false ) {
			self::$fields['summary'] = self::setSummary();
		}

		if( isset( $_POST['mwleadingzero'] ) ) {
			self::$fields['leadByZero'] = true;
		}

		if( self::$fields['parsePost'] !== false && is_array( self::$fields['parsePost'] ) ) {
			$filesCore = new FilesCore();
			foreach ( self::$fields['parsePost'] as $pp ) {
				$pp = General::makeUnderscoreFromSpace( $pp );
				if( isset( $_POST[$pp] ) ) {
					$_POST[$pp] = $filesCore->parseTitle( $_POST[$pp] );
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function saveToWiki() {
		self::$fields = Definitions::createAndEditFields();
		/*
		'parsePost'    => General::getPostString( 'wsparsepost' ),
		'parseLast'    => General::getPostString( 'mwparselast' ),
		'etoken'       => General::getPostString( 'wsedittoken' ),
		'template'     => General::getPostString( 'mwtemplate' ),
		'writepage'    => General::getPostString( 'mwwrite' ),
		'option'       => General::getPostString( 'mwoption' ),
		'returnto'     => General::getPostString( 'mwreturn', false ),
		'returnfalse'  => General::getPostString( 'mwreturnfalse' ),
		'mwedit'       => General::getPostArray( 'mwedit' ),
		'writepages'   => General::getPostArray( 'mwcreatemultiple' ),
		'msgOnSuccess' => General::getPostString( 'mwonsuccess' ),
		'mwfollow'     => General::getPostString( 'mwfollow' ),
		'leadByZero'   => false,
		'summary'      => General::getPostString( 'mwwikicomment' ),
		'slot'		   => General::getPostString( 'mwslot' )
		*/

		self::checkFields();

		/*
		if( self::$fields['returnto'] === false && self::$fields['returnfalse'] === false ) {
			return wbHandleResponses::createMsg('no return url defined','error', self::$fields['returnto'] );
		}
		*/

		if ( self::$fields['template'] !== false && self::$fields['writepage'] !== false ) {
			// Create one page
		}

	}

	/**
	 * Create content
	 * @return string
	 */
	public static function createContent(): string {
		$ret = '';
		$noTemplate = false;

		if( self::$fields['template'] === strtolower( 'wsnone' ) ) {
			$noTemplate = true;
		}
		if( !$noTemplate ) {
			$ret = "{{" . self::$fields['template'] . "\n";
		}
		foreach ( $_POST as $k => $v ) {
			if ( is_array( $v ) && !Definitions::isWSFormSystemField( $k ) ) {
				$ret .= "|" . General::makeSpaceFromUnderscore( $k ) . "=";
				foreach ( $v as $multiple ) {
					$ret .= wsSecurity::cleanBraces( $multiple ) . ',';
				}
				$ret = rtrim( $ret, ',' ) . PHP_EOL;
			} else {
				if ( !Definitions::isWSFormSystemField( $k ) && $v != "" ) {
					if( !$noTemplate ) {
						$ret .= '|' . General::makeSpaceFromUnderscore( $k ) . '=' . wsSecurity::cleanBraces( $v ) . "\n";
					} else {
						$ret = $v . PHP_EOL;
					}
				}
			}
		}
		if( !$noTemplate ) {
			$ret .= "}}";
		}
		return $ret;
	}

	/**
	 * @return int
	 */
	public static function createRandom(): int {
		return time();
	}

	public static function parseTitle( $title ) {
		$tmp = General::get_all_string_between( $title, '[', ']' );
		foreach ( $tmp as $fieldname ) {
			if( isset( $_POST[General::makeUnderscoreFromSpace($fieldname)] ) ) {
				$fn = $_POST[General::makeUnderscoreFromSpace($fieldname)];
				if( is_array( $fn ) ) {
					$imp = implode( ', ', $fn );
					$title = str_replace('[' . $fieldname . ']', $imp, $title);
				} elseif ( $fn !== '' ) {
					if( Config::getConfigVariable( 'create-seo-titles' ) === true ) {
						$fn = $api->urlToSEO( $fn );
					}
					$title = str_replace('[' . $fieldname . ']', $fn, $title);
				} else {
					$title = str_replace('[' . $fieldname . ']', '', $title);
				}
			} else {
				$title = str_replace('[' . $fieldname . ']', '', $title);
			}
			if( $fieldname == 'mwrandom' ) {
				$title = str_replace( '['.$fieldname.']', MakeTitle(), $title );
			}
		}
		return $title;
	}

	public static function getNextAvailable( $nameStartsWith ){
		$render   = new Render();
		$postdata = [
			"action"          => "wsform",
			"format"          => "json",
			"what"            => "nextAvailable",
			"titleStartsWith" => $nameStartsWith
		];
		$result = $render->makeRequest( $postdata );
		if( isset( $result['received']['wsform']['error'] ) ) {
			return(array('status' => 'error', 'message' => $result['received']['wsform']['error']['message']));
		} elseif ( isset( $result['received']['error'] ) ) {
			return(array('status' => 'error', 'message' => $result['received']['error']['code'] . ': ' .
														   $result['received']['error']['info'] ) );
		} else {
			return(array('status' => 'ok', 'result' => $result['received']['wsform']['result']));
		}
		die();
	}

	/**
	 * @param $nameStartsWith
	 * @param $range
	 *
	 * @return array
	 */
	public static function getFromRange( $nameStartsWith, $range ){
		$postdata = [
			 "action" => "wsform",
			 "format" => "json",
			 "what" => "getRange",
			 "titleStartsWith" => $nameStartsWith,
			 "range" => $range
		 ];
		$render = new Render();
		$result = $render->makeRequest( $postdata );

		if( isset( $result['received']['wsform']['error'] ) ) {
			return(array('status' => 'error', 'message' => $result['received']['wsform']['error']['message']));
		} else {
			return(array('status' => 'ok', 'result' => $result['received']['wsform']['result']));
		}
		die();
	}


}