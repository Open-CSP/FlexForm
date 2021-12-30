<?php

namespace WSForm\Processors\Content;

use WSForm\Processors\Security\wsSecurity;
use WSForm\Processors\Definitions;
use WSForm\Processors\Utilities\General;
use WSForm\Processors\Files\FilesCore;

/**
 * Class core
 *
 * @package wsform\processors\content
 */
class ContentCore {

	public $api = "";

	private $fields = array();

	/**
	 * @return array
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * Experimental function to get a username from session
	 *
	 * @param bool $onlyName
	 * @return string
	 */
	private static function setSummary( bool $onlyName = false ): string {
		//TODO: Still needs work as mwdb is not always a default
		$dbn = General::getPostString('mwdb');
		if( $dbn !== false && isset( $_COOKIE[$dbn.'UserName'] ) ) {
			if($onlyName === true) {
				return ( $_COOKIE[$dbn.'UserName'] );
			} else {
				return ( '[[User:' . $_COOKIE[$dbn . 'UserName'] . ']]' );
			}
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
			return ('Anon user: ' . $ip);
		}
	}

	private function checkFields(){
		if( $this->fields['summary'] === false ) {
			$this->fields['summary'] = $this->setSummary();
		}

		if( isset( $_POST['mwleadingzero'] ) ) {
			$this->fields['leadByZero'] = true;
		}

		if( $this->fields['parsePost'] !== false && is_array( $this->fields['parsePost'] ) ) {
			foreach ( $this->fields['parsePost'] as $pp ) {
				if( isset( $_POST[General::makeUnderscoreFromSpace($pp)] ) ) {
					$_POST[General::makeUnderscoreFromSpace($pp)] = filesCore::parseTitle( $_POST[General::makeUnderscoreFromSpace($pp)] );
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public static function saveToWiki( $api ) {
		self::$fields = definitions::createAndEditFields();
		self::$api = $api;
		/*
		$parsePost    = wsUtilities::getPostString( 'wsparsepost' );
		$parseLast    = wsUtilities::getPostString( 'mwparselast' );
		$etoken       = wsUtilities::getPostString( 'wsedittoken' );
		$template     = wsUtilities::getPostString( 'mwtemplate' );
		$writepage    = wsUtilities::getPostString( 'mwwrite' );
		$option       = wsUtilities::getPostString( 'mwoption' );
		$returnto     = wsUtilities::getPostString( 'mwreturn', false );
		$returnfalse  = wsUtilities::getPostString( 'mwreturnfalse' );
		$mwedit       = wsUtilities::getPostArray( 'mwedit' );
		$writepages   = wsUtilities::getPostArray( 'mwcreatemultiple' );
		$msgOnSuccess = wsUtilities::getPostString( 'mwonsuccess' );
		$mwfollow     = wsUtilities::getPostString( 'mwfollow' );
		$leadByZero   = false;
		$summary      = wsUtilities::getPostString( 'mwwikicomment' );
		*/

		self::checkFields();

		if( self::$fields['returnto'] === false && self::$fields['returnfalse'] === false ) {
			return wbHandleResponses::createMsg('no return url defined','error', self::$fields['returnto'] );
		}

		if ( self::$fields['template'] !== false && self::$fields['writepage'] !== false ) {

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
			if ( is_array( $v ) && !definitions::isWSFormSystemField($k) ) {
				$ret .= "|" . wsUtilities::makeSpaceFromUnderscore( $k ) . "=";
				foreach ( $v as $multiple ) {
					$ret .= wsSecurity::cleanBraces( $multiple ) . ',';
				}
				$ret = rtrim( $ret, ',' ) . PHP_EOL;
			} else {
				if ( !definitions::isWSFormSystemField($k) && $v != "" ) {
					if( !$noTemplate ) {
						$ret .= '|' . wsUtilities::makeSpaceFromUnderscore( $k ) . '=' . wsSecurity::cleanBraces( $v ) . "\n";
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

}