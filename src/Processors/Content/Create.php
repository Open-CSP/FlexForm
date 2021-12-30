<?php
/**
 * Created by  : Designburo.nl
 * Project     : wsformWikiBaseNL
 * Filename    : create.class.php
 * Description :
 * Date        : 19-3-2021
 * Time        : 21:22
 */

namespace wsform\processors\content;

use wsform\processors\system\definitions;
use wsform\processors\wbHandleResponses;

class create {

	private static $ret;

	public static function writePage() {

		$fields = ContentCore::getFields();

		self::$ret = ContentCore::createContent();

		if( ContentCore::$api->getStatus() === false ){
			return wbHandleResponses::createMsg( $api->getStatus( true ), 'error', $returnto);
		}

		if (strpos($writepage,'[') !== false) {
			$writepage = ContentCore::parseTitle( $writepage);
		}

		if ( $writepage !== false ) {
			$title = $writepage;
		}
		if( $option == 'next_available' && $writepage !== false ) {
			// get highest number
			$hnr = $api->getNextAvailable( $title );
			if( $hnr['status'] !== 'error') {
				$title = $writepage . $hnr['result'];
			} else {
				return wbHandleResponses::createMsg( $hnr['message'], 'error', $returnto);
			}
			//$title = $writepage . $api->getNextAvailable( $title );
			//die( $title );
			//$title = $writepage . $api->getWikiListNumber($title);
			if( $title === false ) {
				return wbHandleResponses::createMsg($i18n->wsMessage( 'wsform-mwcreate-wrong-title2' ), 'error', $returnto);
			}
		}
		if ( substr( strtolower( $option ) ,0,6 ) === 'range:' ) {
			$range = substr( $option,6 );
			$rangeCheck = explode('-', $range);

			if( !ctype_digit( $rangeCheck[0] ) || !ctype_digit( $rangeCheck[1] ) ) {
				return wbHandleResponses::createMsg($i18n->wsMessage( 'wsform-mwoption-bad-range' ), 'error', $returnto);
			}

			//$startRange = (int)$range[0];
			//$endRange = (int)$range[1];


			//$tmp  = $api->getWikiListNumber($title, array('start' => $startRange, 'end' => $endRange) );
			$tmp  = $api->getFromRange( $title, $range );
			if( $tmp['status'] === 'error') {
				//echo $tmp['message'];
				return wbHandleResponses::createMsg( $tmp['message'], 'error', $returnto);
			}
			$tmp = $tmp['result'];
			/*
			if($tmp === false) {
				return wbHandleResponses::createMsg($i18n->wsMessage('wsform-mwoption-out-of-range'), 'error', $returnto);
			}
			*/
			if( $leadByZero === true ) {
				$endrangeLength = strlen($range[1]);
				$tmp = str_pad($tmp, $endrangeLength, '0', STR_PAD_LEFT);
			}
			$title = $writepage . $tmp;
		}

		if ( $option == 'add_random' && $writepage !== false ) {
			$title = $writepage . wsUtilities::MakeTitle();
		}


		if ( ! $writepage ) {
			return wbHandleResponses::createMsg( $i18n->wsMessage( 'wsform-mwcreate-wrong-title') );

		}
		// Now add the page to the wiki


		//$api->usr = $etoken;
		$api->logMeIn();
		//die($wsuid);

		$result = $api->savePageToWiki( $title, $ret, $summary );
		if(isset($result['received']['error'])) {
			return wbHandleResponses::createMsg($result['received']['error'],'error',$returnto);
		}
		if( $mwfollow !== false ) {
			if( $mwfollow === 'true' ) {

				$returnto = $api->app['wgScript'] . '/' . $title;
			} else {
				if( strpos( $returnto, '?' ) ) {
					$returnto = $returnto . '&' . $mwfollow . '=' . $title;
				} else {
					$returnto = $returnto . '?' . $mwfollow . '=' . $title;
				}
			}
		}
		$weHaveApi = true;


	}


}